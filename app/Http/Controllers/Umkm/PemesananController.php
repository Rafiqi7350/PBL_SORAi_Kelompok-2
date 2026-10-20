<?php

namespace App\Http\Controllers\Umkm;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ShipmentConfirmation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PemesananController extends Controller
{
    /**
     * Tampilkan daftar pesanan UMKM yang sedang login.
     * Mengarahkan ke tab Pesanan & Pengiriman di portal UMKM.
     */
    public function index()
    {
        return redirect()->route('umkm.katalog.index', ['tab' => 'pemesanan']);
    }

    /**
     * Menerbitkan pesanan otomatis berdasarkan jadwal kontrak pengiriman (Auto Order).
     * Pesanan ini langsung masuk ke database orders & order_items dan muncul di portal Pemasok.
     */
    public function terbitkan(Request $request)
    {
        $request->validate([
            'schedule_id' => 'required|exists:partnership_delivery_schedules,id',
        ]);

        $schedule = \App\Models\PartnershipDeliverySchedule::with(['partnership.supplier', 'supplierProduct'])->findOrFail($request->input('schedule_id'));
        $partnership = $schedule->partnership;
        $product = $schedule->supplierProduct;

        if (! $product) {
            return redirect()->back()->with('error', 'Komoditas pada jadwal pengiriman ini tidak ditemukan.');
        }

        $volume = (float) $schedule->volume_per_delivery;
        $price = (float) ($product->price ?? 0);
        $total = $volume * $price;

        DB::transaction(function () use ($partnership, $product, $volume, $price, $total) {
            $order = Order::create([
                'partnership_id' => $partnership->id,
                'order_date' => now()->toDateString(),
                'delivery_date' => now()->toDateString(),
                'total_amount' => $total,
                'order_status' => Order::STATUS_PENDING,
            ]);

            \App\Models\OrderItem::create([
                'order_id' => $order->id,
                'supplier_product_id' => $product->id,
                'quantity' => $volume,
                'quantity_delivered' => 0,
                'quantity_remaining' => $volume,
                'unit_price' => $price,
                'subtotal' => $total,
                'fulfillment_item_status' => 'pending',
            ]);
        });

        return redirect()->route('umkm.katalog.index', ['tab' => 'pemesanan', 'subtab' => 'confirm'])
            ->with('success', '✓ Pesanan otomatis berhasil diterbitkan ke Pemasok! Pesanan telah masuk ke daftar pengiriman mitra pemasok.');
    }

    /**
     * Tampilkan detail modal konfirmasi penerimaan pesanan.
     * Digunakan oleh AJAX untuk mengambil data order & foto bukti.
     */
    public function showKonfirmasi(Order $order)
    {
        $order->load([
            'partnership.supplier',
            'items.supplierProduct.commodity',
            'shipmentConfirmations',
        ]);

        return response()->json([
            'order' => $order,
            'shipments' => $order->shipmentConfirmations,
            'latest_shipment' => $order->latestShipmentConfirmation,
        ]);
    }

    /**
     * UMKM mengkonfirmasi penerimaan fisik Batch 1 beserta upload foto bukti.
     */
    public function konfirmasi(Request $request, Order $order)
    {
        if (in_array($order->order_status, [Order::STATUS_PENDING, Order::STATUS_CONFIRMED])) {
            return redirect()->back()->with('error', 'Pesanan belum dikirim oleh armada pemasok mitra.');
        }

        $request->validate([
            'proof_photo' => 'required|image|max:5120', // max 5MB
            'confirmation_notes' => 'nullable|string|max:500',
        ]);

        $umkm = Auth::user()?->umkmProfile;

        // Upload foto bukti ke storage
        $photoPath = $request->file('proof_photo')->store('shipment-proofs', 'public');
        $photoUrl = Storage::url($photoPath);

        DB::transaction(function () use ($request, $order, $umkm, $photoUrl) {
            // Cari shipment confirmation kloter 1 (atau buat jika belum ada)
            $batch1 = $order->shipmentConfirmations()->where('shipment_batch', 1)->first();

            if (! $batch1) {
                $batch1 = ShipmentConfirmation::create([
                    'order_id' => $order->id,
                    'shipment_batch' => 1,
                    'shipped_quantity' => $order->items->sum('quantity_delivered') ?: $order->items->sum('quantity'),
                    'backorder_action' => 'none',
                    'fulfillment_status' => 'BARANG_DITERIMA',
                    'shipped_at' => now(),
                ]);
            }

            // Update data penerimaan fisik batch 1
            $batch1->update([
                'received_at' => now(),
                'fulfillment_status' => 'BARANG_DITERIMA',
                'proof_photo_url' => $photoUrl,
                'confirmation_notes' => $request->input('confirmation_notes'),
                'confirmed_by_umkm_id' => $umkm?->id,
            ]);

            // Evaluasi status kelanjutan pesanan berdasarkan keputusan backorder
            if ($batch1->backorder_action === 'create_backorder') {
                if ($batch1->backorder_approval_status === 'pending') {
                    $order->update([
                        'order_status' => Order::STATUS_WAITING_BACKORDER_APPROVAL,
                    ]);
                } else {
                    $order->update([
                        'order_status' => Order::STATUS_BACKORDER_PENDING,
                    ]);
                }
            } elseif ($batch1->backorder_action === 'no_backorder' || $batch1->backorder_approval_status === 'rejected') {
                // Selesai sebagian karena sisa dibatalkan
                $order->update([
                    'order_status' => Order::STATUS_COMPLETED_PARTIAL,
                ]);
            } else {
                // Kirim Penuh lunas diterima
                $order->update([
                    'order_status' => Order::STATUS_COMPLETED,
                ]);
            }
        });

        // Flash message sesuai keputusan
        $batch1 = $order->shipmentConfirmations()->where('shipment_batch', 1)->first();
        if ($batch1?->backorder_action === 'create_backorder') {
            return redirect()->route('umkm.katalog.index', ['tab' => 'pemesanan', 'subtab' => 'payment'])
                ->with('success', '✓ Penerimaan fisik Batch 1 berhasil dikonfirmasi! Invoice Batch 1 kini siap dibayar di tab Pembayaran Midtrans, sementara sisa Backorder dipantau di tab "Pesanan Backorder".');
        } elseif ($batch1?->backorder_action === 'no_backorder') {
            return redirect()->route('umkm.katalog.index', ['tab' => 'pemesanan', 'subtab' => 'payment'])
                ->with('success', '✓ Penerimaan fisik Batch 1 berhasil dikonfirmasi (No Backorder). Invoice siap dibayar di tab Pembayaran Midtrans.');
        }

        return redirect()->route('umkm.katalog.index', ['tab' => 'pemesanan', 'subtab' => 'payment'])
            ->with('success', '✓ Penerimaan fisik seluruh bahan baku (Pengiriman Penuh) berhasil dikonfirmasi! Silakan lunasi invoice di tab Pembayaran Midtrans.');
    }

    /**
     * UMKM mengkonfirmasi penerimaan fisik sisa barang Backorder (Batch 2).
     */
    public function konfirmasiBackorder(Request $request, Order $order)
    {
        // Pastikan Batch 1 sudah diterima terlebih dahulu
        $batch1 = $order->shipmentConfirmations()->where('shipment_batch', 1)->first();
        if (! $batch1 || ! $batch1->received_at) {
            return redirect()->back()->with('error', 'Silakan konfirmasi penerimaan fisik Batch 1 terlebih dahulu sebelum mengonfirmasi sisa Backorder.');
        }

        // Pastikan Batch 2 sudah dikirim oleh pemasok
        $batch2 = $order->shipmentConfirmations()->where('shipment_batch', 2)->first();
        if (! $batch2 || ! $batch2->shipped_at) {
            return redirect()->back()->with('error', 'Pemasok belum mengirimkan pengiriman sisa Backorder (Batch 2).');
        }

        $request->validate([
            'proof_photo' => 'required|image|max:5120',
            'confirmation_notes' => 'nullable|string|max:500',
        ]);

        $umkm = Auth::user()?->umkmProfile;

        $photoPath = $request->file('proof_photo')->store('shipment-proofs', 'public');
        $photoUrl = Storage::url($photoPath);

        DB::transaction(function () use ($request, $order, $umkm, $photoUrl, $batch2) {
            $batch2->update([
                'received_at' => now(),
                'fulfillment_status' => 'BARANG_DITERIMA',
                'proof_photo_url' => $photoUrl,
                'confirmation_notes' => $request->input('confirmation_notes'),
                'confirmed_by_umkm_id' => $umkm?->id,
            ]);

            // Pesanan kini selesai penuh!
            $order->update([
                'order_status' => Order::STATUS_COMPLETED,
            ]);
        });

        return redirect()->route('umkm.katalog.index', ['tab' => 'pemesanan', 'subtab' => 'payment'])
            ->with('success', '✓ Penerimaan fisik sisa Backorder (Batch 2) berhasil dikonfirmasi! Invoice Batch 2 kini siap Anda bayar di tab Pembayaran Midtrans.');
    }

    /**
     * UMKM melakukan pembayaran tagihan/invoice untuk batch pengiriman tertentu via Midtrans Simulator.
     */
    public function bayarInvoice(Request $request, Payment $payment)
    {
        $request->validate([
            'payment_method' => ['nullable', 'string', 'max:100'],
        ]);

        $method = $request->input('payment_method', 'Midtrans BCA Virtual Account');

        $payment->update([
            'payment_method' => $method,
            'payment_status' => 'PAID',
            'midtrans_transaction_id' => 'MID-'.date('YmdHis').'-'.strtoupper(substr(uniqid(), -5)),
            'transaction_time' => now(),
            'paid_at' => now(),
        ]);

        $batchText = $payment->shipment_batch == 2 ? 'Batch 2 (Backorder)' : 'Batch 1';
        $invText = $payment->invoice_number ?: 'Invoice';

        // Cek apakah seluruh invoice untuk order ini sudah lunas
        $order = $payment->order;
        if ($order) {
            $hasUnpaidPayments = $order->payments()->whereNotIn('payment_status', ['PAID', 'settlement', 'capture', 'success'])->exists();
            if (! $hasUnpaidPayments && in_array($order->order_status, [Order::STATUS_COMPLETED, 'BARANG_DITERIMA'])) {
                // Semua batch sudah lunas dan barang sudah diterima
                return redirect()->route('umkm.katalog.index', ['tab' => 'pemesanan', 'subtab' => 'rating'])
                    ->with('success', "✓ Pembayaran {$invText} ({$batchText}) sebesar Rp ".number_format($payment->amount, 0, ',', '.').' berhasil lunas via Midtrans! Seluruh tagihan pesanan telah selesai dibayar.');
            }
        }

        return redirect()->route('umkm.katalog.index', ['tab' => 'pemesanan', 'subtab' => 'payment'])
            ->with('success', "✓ Pembayaran {$invText} ({$batchText}) sebesar Rp ".number_format($payment->amount, 0, ',', '.').' berhasil lunas via Midtrans!');
    }

    /**
     * UMKM menyetujui pengajuan backorder / pemenuhan sebagian dari pemasok.
     */
    public function terimaBackorder(Request $request, Order $order)
    {
        $batch1 = $order->shipmentConfirmations()->where('shipment_batch', 1)->first();

        if (! $batch1 || ! in_array($batch1->backorder_action, ['create_backorder', 'no_backorder'])) {
            return redirect()->back()->with('error', 'Tidak ada pengajuan backorder / pemenuhan sebagian yang dapat disetujui untuk pesanan ini.');
        }

        DB::transaction(function () use ($order, $batch1) {
            $batch1->update([
                'backorder_approval_status' => 'approved',
                'backorder_approved_at' => now(),
            ]);

            if ($batch1->backorder_action === 'no_backorder') {
                // UMKM menyetujui pemenuhan sebagian tanpa backorder:
                // Sisa kuantitas resmi dibatalkan & total tagihan disesuaikan hanya untuk kuantitas yang disetujui.
                $newTotalAmount = 0;
                foreach ($order->items as $item) {
                    $deliveredQty = (float) $item->quantity_delivered;
                    $newSubtotal = $deliveredQty * (float) $item->unit_price;
                    $newTotalAmount += $newSubtotal;

                    $item->update([
                        'quantity_remaining' => 0,
                        'fulfillment_item_status' => 'partially_accepted',
                        'subtotal' => $newSubtotal,
                    ]);
                }

                $order->update([
                    'total_amount' => $newTotalAmount,
                    'order_status' => Order::STATUS_BACKORDER_PENDING,
                ]);
            } else {
                // Status pesanan resmi masuk ke antrean backorder aktif
                $order->update([
                    'order_status' => Order::STATUS_BACKORDER_PENDING,
                ]);
            }
        });

        $msg = $batch1->backorder_action === 'no_backorder'
            ? '✓ Anda menyetujui pemenuhan sebagian (tanpa backorder). Sisa pesanan ditiadakan & total tagihan disesuaikan. Menunggu pemasok memberangkatkan barang.'
            : '✓ Anda menyetujui pengajuan Backorder! Sisa barang akan dikirim oleh pemasok sesuai estimasi jadwal.';

        return redirect()->route('umkm.katalog.index', ['tab' => 'pemesanan', 'subtab' => 'backorder'])
            ->with('success', $msg);
    }

    /**
     * UMKM menolak pengajuan backorder / pemenuhan sebagian dari pemasok.
     */
    public function tolakBackorder(Request $request, Order $order)
    {
        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $batch1 = $order->shipmentConfirmations()->where('shipment_batch', 1)->first();

        if (! $batch1 || ! in_array($batch1->backorder_action, ['create_backorder', 'no_backorder'])) {
            return redirect()->back()->with('error', 'Tidak ada pengajuan yang dapat ditolak untuk pesanan ini.');
        }

        DB::transaction(function () use ($request, $order, $batch1) {
            $reason = $request->input('rejection_reason');

            $batch1->update([
                'backorder_approval_status' => 'rejected',
                'backorder_rejection_reason' => $reason,
                'backorder_rejected_at' => now(),
            ]);

            if ($batch1->backorder_action === 'no_backorder') {
                // Pemasok mengajukan pemenuhan sebagian tanpa backorder, namun ditolak oleh UMKM.
                // Seluruh pesanan resmi dibatalkan karena UMKM tidak menerima pasokan parsial tanpa sisa.
                foreach ($order->items as $item) {
                    $item->update([
                        'quantity_remaining' => 0,
                        'fulfillment_item_status' => 'cancelled',
                    ]);
                }

                $order->update([
                    'order_status' => Order::STATUS_CANCELLED,
                ]);
            } else {
                $batch1->update([
                    'backorder_action' => 'no_backorder',
                ]);

                // Sisa kuantitas resmi dibatalkan
                $newTotalAmount = 0;
                foreach ($order->items as $item) {
                    $deliveredQty = (float) $item->quantity_delivered;
                    $newSubtotal = $deliveredQty * (float) $item->unit_price;
                    $newTotalAmount += $newSubtotal;

                    $item->update([
                        'quantity_remaining' => 0,
                        'fulfillment_item_status' => 'cancelled',
                        'subtotal' => $newSubtotal,
                    ]);
                }

                // Total tagihan pesanan otomatis dipotong dan dihitung ulang hanya untuk kloter 1
                $order->update([
                    'total_amount' => $newTotalAmount,
                    'order_status' => $batch1->shipped_at ? ($batch1->received_at ? Order::STATUS_COMPLETED_PARTIAL : Order::STATUS_PARTIALLY_SHIPPED) : Order::STATUS_BACKORDER_PENDING,
                ]);

                // Sesuaikan invoice Batch 1 jika sudah ada
                $payment1 = $order->payments()->where('shipment_batch', 1)->first();
                if ($payment1) {
                    $payment1->update([
                        'amount' => $newTotalAmount,
                        'notes' => 'Tagihan disesuaikan (Backorder ditolak UMKM: '.$reason.')',
                    ]);
                }
            }

            // Hapus record invoice batch 2 jika pernah terbuat
            $order->payments()->where('shipment_batch', 2)->delete();
        });

        $msg = $batch1->backorder_action === 'no_backorder'
            ? '✓ Pengajuan pemenuhan sebagian berhasil ditolak. Seluruh pesanan telah dibatalkan.'
            : '✓ Pengajuan Backorder berhasil ditolak. Sisa pesanan dibatalkan dan total tagihan disesuaikan hanya untuk kloter yang dikirim.';

        return redirect()->route('umkm.katalog.index', ['tab' => 'pemesanan', 'subtab' => 'backorder'])
            ->with('success', $msg);
    }
}

