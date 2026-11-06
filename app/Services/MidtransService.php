<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;

class MidtransService
{
    public function __construct()
    {
        $this->setupConfig();
    }

    /**
     * Inisialisasi konfigurasi Midtrans
     */
    protected function setupConfig(): void
    {
        if (class_exists(Config::class)) {
            Config::$serverKey = (string) config('midtrans.server_key');
            Config::$isProduction = (bool) config('midtrans.is_production', false);
            Config::$isSanitized = (bool) config('midtrans.is_sanitized', true);
            Config::$is3ds = (bool) config('midtrans.is_3ds', true);
        }
    }

    /**
     * Buat Snap Token untuk pembayaran invoice/payment tertentu
     *
     * @throws Exception
     */
    public function createSnapToken(Payment $payment): string
    {
        $this->setupConfig();

        if (empty(Config::$serverKey)) {
            throw new Exception('MIDTRANS_SERVER_KEY belum disetel di file .env.');
        }

        $order = $payment->order;
        $order?->loadMissing(['partnership.umkm.user', 'items.supplierProduct.commodity']);

        $umkm = $order?->partnership?->umkm;
        $user = $umkm?->user;

        // Gunakan invoice_number dengan suffix unik agar retry pembayaran tidak ditolak Midtrans (order_id has already been taken)
        $baseInvoice = $payment->invoice_number ?: ('INV-'.substr($payment->id, 0, 8).'-B'.$payment->shipment_batch);
        $cleanBaseInvoice = preg_replace('/[^a-zA-Z0-9_\-\.~]/', '-', $baseInvoice);
        $midtransOrderId = $cleanBaseInvoice.'-'.time();

        // Midtrans membutuhkan nominal IDR dalam integer (tanpa desimal)
        $grossAmount = (int) round((float) $payment->amount);

        if ($grossAmount <= 0) {
            throw new Exception('Nominal tagihan harus lebih dari Rp 0.');
        }

        $batchLabel = $payment->shipment_batch == 2 ? 'Batch 2 (Backorder)' : 'Batch 1';
        $commodityName = $order?->items?->first()?->supplierProduct?->commodity?->name ?? 'Pasokan Bahan Baku';

        $items = [
            [
                'id' => substr($payment->id, 0, 50),
                'price' => $grossAmount,
                'quantity' => 1,
                'name' => mb_strimwidth("{$commodityName} ({$batchLabel})", 0, 50, '...'),
            ],
        ];

        $customerDetails = [
            'first_name' => $umkm?->business_name ?: ($umkm?->owner_name ?: ($user?->name ?: 'UMKM Partner')),
            'email' => $user?->email ?: 'umkm@sorai.id',
            'phone' => $umkm?->phone ?: '081234567890',
            'billing_address' => [
                'first_name' => $umkm?->business_name ?: 'UMKM',
                'address' => $umkm?->address ?: 'Indonesia',
            ],
        ];

        $params = [
            'transaction_details' => [
                'order_id' => $midtransOrderId,
                'gross_amount' => $grossAmount,
            ],
            'item_details' => $items,
            'customer_details' => $customerDetails,
            'callbacks' => [
                'finish' => route('umkm.katalog.index', ['tab' => 'pemesanan', 'subtab' => 'payment']),
            ],
        ];

        try {
            $token = Snap::getSnapToken($params);
            $payment->update([
                'midtrans_transaction_id' => $midtransOrderId,
            ]);

            return $token;
        } catch (Exception $e) {
            Log::error('Midtrans Snap Error: '.$e->getMessage(), ['params' => $params]);

            throw $e;
        }
    }

    /**
     * Verifikasi keaslian webhook notification dari Midtrans
     */
    public function isValidSignature(string $orderId, string $statusCode, string $grossAmount, string $signatureKey): bool
    {
        $serverKey = (string) config('midtrans.server_key');
        $expectedSignature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        return hash_equals($expectedSignature, $signatureKey);
    }

    /**
     * Memproses callback/notification webhook dari Midtrans
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    public function handleNotification(array $payload): array
    {
        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signatureKey = (string) ($payload['signature_key'] ?? '');
        $transactionStatus = (string) ($payload['transaction_status'] ?? '');
        $fraudStatus = (string) ($payload['fraud_status'] ?? '');
        $paymentType = (string) ($payload['payment_type'] ?? 'midtrans');
        $transactionId = (string) ($payload['transaction_id'] ?? '');

        // Verifikasi signature
        if (! $this->isValidSignature($orderId, $statusCode, $grossAmount, $signatureKey)) {
            Log::warning('Midtrans Webhook: Invalid Signature', ['order_id' => $orderId]);
            throw new Exception('Invalid Midtrans signature.');
        }

        // Cari record payment berdasarkan midtrans_transaction_id atau invoice_number
        $payment = Payment::where('midtrans_transaction_id', $orderId)
            ->orWhere('invoice_number', $orderId)
            ->first();

        // Fallback: hapus suffix timestamp (misal: "INV-20261102-B1-FE55-1730616000" -> "INV-20261102-B1-FE55")
        if (! $payment && str_contains($orderId, '-')) {
            $cleanInvoice = preg_replace('/-\d{9,12}$/', '', $orderId);
            $payment = Payment::where('invoice_number', $cleanInvoice)
                ->orWhere('midtrans_transaction_id', $cleanInvoice)
                ->first();
        }

        // Fallback jika tidak ditemukan dengan invoice_number langsung
        if (! $payment && str_starts_with($orderId, 'INV-')) {
            $parts = explode('-', $orderId);
            if (isset($parts[1])) {
                $payment = Payment::where('id', 'like', $parts[1].'%')->first();
            }
        }

        if (! $payment) {
            Log::warning('Midtrans Webhook: Payment not found for order_id: '.$orderId);

            return [
                'status' => 'not_found',
                'message' => 'Payment record not found.',
            ];
        }

        $newPaymentStatus = $payment->payment_status;
        $isPaid = false;

        if ($transactionStatus === 'capture') {
            if ($fraudStatus === 'challenge') {
                $newPaymentStatus = 'CHALLENGE';
            } elseif ($fraudStatus === 'accept') {
                $newPaymentStatus = 'PAID';
                $isPaid = true;
            }
        } elseif ($transactionStatus === 'settlement') {
            $newPaymentStatus = 'PAID';
            $isPaid = true;
        } elseif ($transactionStatus === 'pending') {
            $newPaymentStatus = 'PENDING';
        } elseif (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
            $newPaymentStatus = strtoupper($transactionStatus);
        }

        DB::transaction(function () use ($payment, $newPaymentStatus, $paymentType, $transactionId, $isPaid) {
            $updateData = [
                'payment_status' => $newPaymentStatus,
                'payment_method' => 'Midtrans ('.$paymentType.')',
                'midtrans_transaction_id' => $transactionId ?: $payment->midtrans_transaction_id,
                'transaction_time' => now(),
            ];

            if ($isPaid && ! $payment->paid_at) {
                $updateData['paid_at'] = now();
            }

            $payment->update($updateData);

            // Cek update status order jika seluruh pembayaran lunas
            $order = $payment->order;
            if ($order && $isPaid) {
                $hasUnpaid = $order->payments()
                    ->whereNotIn('payment_status', ['PAID', 'settlement', 'capture', 'success', 'LUNAS'])
                    ->exists();

                if (! $hasUnpaid && in_array($order->order_status, [Order::STATUS_COMPLETED, 'BARANG_DITERIMA'])) {
                    $order->update([
                        'order_status' => Order::STATUS_COMPLETED,
                    ]);
                }
            }
        });

        Log::info("Midtrans Webhook Success: Payment {$payment->id} status updated to {$newPaymentStatus}");

        return [
            'status' => 'success',
            'payment_id' => $payment->id,
            'payment_status' => $newPaymentStatus,
        ];
    }

    /**
     * Periksa status transaksi secara manual ke API Midtrans
     *
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    public function syncTransactionStatus(Payment $payment): array
    {
        $this->setupConfig();

        if (empty(Config::$serverKey)) {
            throw new Exception('MIDTRANS_SERVER_KEY belum disetel di file .env.');
        }

        $orderId = $payment->midtrans_transaction_id ?: ($payment->invoice_number ?: ('INV-'.substr($payment->id, 0, 8).'-B'.$payment->shipment_batch));

        try {
            /** @var object $statusObj */
            $statusObj = Transaction::status($orderId);
            $payload = (array) $statusObj;

            // Update status lokal
            $transactionStatus = $payload['transaction_status'] ?? '';
            $fraudStatus = $payload['fraud_status'] ?? '';
            $paymentType = $payload['payment_type'] ?? 'midtrans';
            $transactionId = $payload['transaction_id'] ?? '';

            $newStatus = $payment->payment_status;
            $isPaid = false;

            if ($transactionStatus === 'settlement' || ($transactionStatus === 'capture' && $fraudStatus === 'accept')) {
                $newStatus = 'PAID';
                $isPaid = true;
            } elseif ($transactionStatus === 'pending') {
                $newStatus = 'PENDING';
            } elseif (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
                $newStatus = strtoupper($transactionStatus);
            }

            $payment->update([
                'payment_status' => $newStatus,
                'payment_method' => 'Midtrans ('.$paymentType.')',
                'midtrans_transaction_id' => $transactionId ?: $payment->midtrans_transaction_id,
                'transaction_time' => now(),
                'paid_at' => $isPaid ? ($payment->paid_at ?: now()) : $payment->paid_at,
            ]);

            return [
                'status' => 'success',
                'midtrans_status' => $transactionStatus,
                'payment_status' => $newStatus,
            ];
        } catch (Exception $e) {
            Log::warning("Sync Midtrans Status Error for {$orderId}: ".$e->getMessage());
            throw $e;
        }
    }
}
