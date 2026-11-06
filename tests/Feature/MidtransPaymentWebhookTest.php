<?php

namespace Tests\Feature;

use App\Models\Commodity;
use App\Models\CommodityCategory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Partnership;
use App\Models\Payment;
use App\Models\SupplierProduct;
use App\Models\SupplierProfile;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MidtransPaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    private User $umkmUser;

    private UmkmProfile $umkmProfile;

    private User $supplierUser;

    private SupplierProfile $supplierProfile;

    private Partnership $partnership;

    private Order $order;

    private Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->umkmUser = User::create([
            'email' => 'umkm.midtrans@example.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'active',
        ]);

        $this->umkmProfile = UmkmProfile::create([
            'user_id' => $this->umkmUser->id,
            'business_name' => 'Dapur Kuliner Mantap',
            'owner_name' => 'Budi Santoso',
            'phone' => '081234567890',
            'address' => 'Jl. Ijen No. 10, Malang',
        ]);

        $this->supplierUser = User::create([
            'email' => 'supplier.midtrans@example.com',
            'password' => Hash::make('password123'),
            'role' => 'supplier',
            'status' => 'active',
        ]);

        $category = CommodityCategory::create(['name' => 'Daging & Unggas']);

        $this->supplierProfile = SupplierProfile::create([
            'user_id' => $this->supplierUser->id,
            'commodity_id' => $category->id,
            'company_name' => 'Kelompok Tani Poultry',
            'owner_name' => 'Pak Joko',
            'phone' => '085712345678',
            'warehouse_address' => 'Jl. Peternakan 1',
            'max_capacity' => '5 Ton / Minggu',
            'delivery_area' => 'Malang',
        ]);

        $commodity = Commodity::create([
            'name' => 'Daging Ayam Broiler Segar',
            'category' => 'Daging & Unggas',
            'unit' => 'kg',
            'government_ref_price' => 35000,
        ]);

        $product = SupplierProduct::create([
            'supplier_id' => $this->supplierProfile->id,
            'commodity_id' => $commodity->id,
            'price' => 30000,
            'stock_capacity' => 1000,
            'stock_status' => 'Tersedia',
            'delivery_area' => 'Malang',
            'unit' => 'kg',
        ]);

        $this->partnership = Partnership::create([
            'umkm_id' => $this->umkmProfile->id,
            'supplier_id' => $this->supplierProfile->id,
            'submission_details' => 'Kontrak Daging Ayam',
            'status' => 'active',
        ]);

        $this->order = Order::create([
            'partnership_id' => $this->partnership->id,
            'order_date' => now()->toDateString(),
            'delivery_date' => now()->toDateString(),
            'total_amount' => 1500000,
            'order_status' => Order::STATUS_SHIPPED,
        ]);

        OrderItem::create([
            'order_id' => $this->order->id,
            'supplier_product_id' => $product->id,
            'quantity' => 50,
            'quantity_delivered' => 50,
            'quantity_remaining' => 0,
            'unit_price' => 30000,
            'subtotal' => 1500000,
            'fulfillment_item_status' => 'fulfilled',
        ]);

        $this->payment = Payment::create([
            'order_id' => $this->order->id,
            'shipment_batch' => 1,
            'invoice_number' => 'INV-TEST-MIDTRANS-001',
            'midtrans_transaction_id' => 'INV-TEST-MIDTRANS-001',
            'amount' => 1500000,
            'payment_status' => 'UNPAID',
        ]);
    }

    // Webhook callback ditolak 403 jika signature SHA-512 tidak valid
    public function test_webhook_rejects_invalid_signature(): void
    {
        config(['midtrans.server_key' => 'secret_server_key_testing']);

        $response = $this->postJson('/midtrans/notification', [
            'order_id' => 'INV-TEST-MIDTRANS-001',
            'status_code' => '200',
            'gross_amount' => '1500000.00',
            'signature_key' => 'signature_palsu_yang_tidak_valid_1234567890abcdef',
            'transaction_status' => 'settlement',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
        ]);

        $this->payment->refresh();
        $this->assertEquals('UNPAID', $this->payment->payment_status);
    }

    // Callback settlement berhasil update status invoice menjadi PAID
    public function test_webhook_successfully_updates_payment_to_paid_on_settlement(): void
    {
        $serverKey = 'secret_server_key_testing';
        config(['midtrans.server_key' => $serverKey]);

        $orderId = 'INV-TEST-MIDTRANS-001';
        $statusCode = '200';
        $grossAmount = '1500000.00';
        $validSignature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        $response = $this->postJson('/midtrans/notification', [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $validSignature,
            'transaction_status' => 'settlement',
            'payment_type' => 'bank_transfer',
            'transaction_id' => 'TRX-MIDTRANS-99999',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->payment->refresh();
        $this->assertEquals('PAID', $this->payment->payment_status);
        $this->assertNotNull($this->payment->paid_at);
    }

    // Request snap token menggunakan mock jika server key belum diatur
    public function test_snap_token_endpoint_indicates_mock_when_server_key_is_empty(): void
    {
        config(['midtrans.server_key' => '']);

        $this->actingAs($this->umkmUser);

        $response = $this->postJson("/umkm/pemesanan/pembayaran/{$this->payment->id}/snap-token");

        $response->assertStatus(200);
        $response->assertJson([
            'is_mock' => true,
        ]);
    }
}
