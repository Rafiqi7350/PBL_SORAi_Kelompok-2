<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Partnership;
use App\Models\Payment;
use App\Models\SupplierProfile;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MidtransPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function setupTestOrderAndPayment(): array
    {
        $supplierUser = User::create([
            'email' => 'supplier_'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
            'role' => 'supplier',
            'status' => 'active',
        ]);

        $supplier = SupplierProfile::create([
            'user_id' => $supplierUser->id,
            'company_name' => 'PT Mitra Pasokan Pangan',
            'owner_name' => 'Pak Joko',
            'phone' => '08123456789',
            'warehouse_address' => 'Gudang Cikarang',
            'max_capacity' => '10 Ton',
            'delivery_area' => 'Jabodetabek',
            'nib_number' => '1234567890123',
        ]);

        $umkmUser = User::create([
            'email' => 'umkm_'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'active',
        ]);

        $umkm = UmkmProfile::create([
            'user_id' => $umkmUser->id,
            'business_name' => 'Dapur Kuliner Nusantara',
            'owner_name' => 'Ibu Siti',
            'phone' => '08987654321',
            'address' => 'Jl. Tebet No. 10',
        ]);

        $partnership = Partnership::create([
            'umkm_id' => $umkm->id,
            'supplier_id' => $supplier->id,
            'status' => 'approved',
        ]);

        $order = Order::create([
            'partnership_id' => $partnership->id,
            'order_date' => now()->toDateString(),
            'delivery_date' => now()->addDays(2)->toDateString(),
            'total_amount' => 500000,
            'order_status' => 'shipped',
        ]);

        $invoiceNumber = 'INV-TEST-'.strtoupper(uniqid());

        $payment = Payment::create([
            'order_id' => $order->id,
            'shipment_batch' => 1,
            'invoice_number' => $invoiceNumber,
            'amount' => 500000,
            'quantity_billed' => 25,
            'payment_status' => 'BELUM_DIBAYAR',
        ]);

        return compact('umkmUser', 'order', 'payment', 'invoiceNumber');
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        Config::set('midtrans.server_key', 'test-server-key-123');

        $response = $this->postJson('/midtrans/notification', [
            'order_id' => 'INV-TEST-FAKE',
            'status_code' => '200',
            'gross_amount' => '500000.00',
            'signature_key' => 'invalidsignaturehash123',
            'transaction_status' => 'settlement',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Invalid Midtrans signature.',
        ]);
    }

    public function test_webhook_successfully_updates_payment_to_paid_on_settlement(): void
    {
        $serverKey = 'test-server-key-123';
        Config::set('midtrans.server_key', $serverKey);

        $data = $this->setupTestOrderAndPayment();
        $invoiceNumber = $data['invoiceNumber'];
        $payment = $data['payment'];

        $statusCode = '200';
        $grossAmount = '500000.00';
        $validSignature = hash('sha512', $invoiceNumber.$statusCode.$grossAmount.$serverKey);

        $response = $this->postJson('/midtrans/notification', [
            'order_id' => $invoiceNumber,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $validSignature,
            'transaction_status' => 'settlement',
            'payment_type' => 'bank_transfer',
            'transaction_id' => 'MID-REAL-TEST-999',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $payment->refresh();
        $this->assertEquals('PAID', $payment->payment_status);
        $this->assertEquals('Midtrans (bank_transfer)', $payment->payment_method);
        $this->assertEquals('MID-REAL-TEST-999', $payment->midtrans_transaction_id);
        $this->assertNotNull($payment->paid_at);
    }

    public function test_snap_token_endpoint_indicates_mock_when_server_key_is_empty(): void
    {
        Config::set('midtrans.server_key', '');

        $data = $this->setupTestOrderAndPayment();
        $umkmUser = $data['umkmUser'];
        $payment = $data['payment'];

        $response = $this->actingAs($umkmUser)
            ->postJson("/umkm/pemesanan/pembayaran/{$payment->id}/snap-token");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
            'is_mock' => true,
        ]);
    }
}
