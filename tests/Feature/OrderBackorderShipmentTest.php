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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderBackorderShipmentTest extends TestCase
{
    use RefreshDatabase;

    private User $umkmUser;

    private UmkmProfile $umkmProfile;

    private User $supplierUser;

    private SupplierProfile $supplierProfile;

    private Partnership $partnership;

    private SupplierProduct $product;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->umkmUser = User::create([
            'email' => 'umkm.order@example.com',
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
            'email' => 'supplier.order@example.com',
            'password' => Hash::make('password123'),
            'role' => 'supplier',
            'status' => 'active',
        ]);

        $category = CommodityCategory::create(['name' => 'Daging & Unggas']);

        $this->supplierProfile = SupplierProfile::create([
            'user_id' => $this->supplierUser->id,
            'commodity_id' => $category->id,
            'company_name' => 'Kelompok Tani Ternak Mandiri',
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

        $this->product = SupplierProduct::create([
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
    }

    private function createOrder(float $qty = 100, float $price = 30000): Order
    {
        $order = Order::create([
            'partnership_id' => $this->partnership->id,
            'order_date' => now()->toDateString(),
            'delivery_date' => now()->toDateString(),
            'total_amount' => $qty * $price,
            'order_status' => Order::STATUS_PENDING,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'supplier_product_id' => $this->product->id,
            'quantity' => $qty,
            'quantity_delivered' => 0,
            'quantity_remaining' => $qty,
            'unit_price' => $price,
            'subtotal' => $qty * $price,
            'fulfillment_item_status' => 'pending',
        ]);

        return $order;
    }

    // Pemasok kirim seluruh barang langsung dan invoice terbit
    public function test_supplier_bisa_kirim_penuh_lunas(): void
    {
        $this->actingAs($this->supplierUser);
        $order = $this->createOrder(100, 30000);

        $response = $this->post("/supplier/pengiriman/{$order->id}/kirim", [
            'qty_to_ship' => 100,
            'backorder_action' => 'none',
            'tracking_notes' => 'Armada sedang berangkat.',
        ]);

        $response->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertEquals(Order::STATUS_SHIPPED, $order->order_status);

        $this->assertDatabaseHas('shipment_confirmations', [
            'order_id' => $order->id,
            'shipment_batch' => 1,
            'shipped_quantity' => 100,
            'fulfillment_status' => 'DIKIRIM',
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'shipment_batch' => 1,
            'amount' => 3000000,
            'payment_status' => 'UNPAID',
        ]);
    }

    // Alur backorder dari pengajuan pemasok, approval UMKM, sampai kirim batch 2
    public function test_supplier_bisa_create_backorder_dan_kirim_batch_2(): void
    {
        $order = $this->createOrder(100, 30000);

        // Pemasok kirim sebagian (60 kg) dan ajukan backorder
        $this->actingAs($this->supplierUser);
        $this->post("/supplier/pengiriman/{$order->id}/kirim", [
            'qty_to_ship' => 60,
            'backorder_action' => 'create_backorder',
            'backorder_estimated_delivery_date' => now()->addDays(2)->toDateString(),
            'backorder_notes' => 'Menunggu pasokan panen berikutnya',
        ]);

        $order->refresh();
        $this->assertEquals(Order::STATUS_WAITING_BACKORDER_APPROVAL, $order->order_status);

        // UMKM menyetujui backorder
        $this->actingAs($this->umkmUser);
        $this->post("/umkm/pemesanan/{$order->id}/terima-backorder");

        $order->refresh();
        $this->assertEquals(Order::STATUS_BACKORDER_PENDING, $order->order_status);

        // Pemasok kirim kloter 1
        $this->actingAs($this->supplierUser);
        $this->post("/supplier/pengiriman/{$order->id}/kirim-batch1");

        // Pemasok kirim kloter 2 sisa backorder
        $this->post("/supplier/pengiriman/{$order->id}/kirim-backorder", [
            'tracking_notes' => 'Pengiriman sisa 40 kg ayam broiler.',
        ]);

        $this->assertDatabaseHas('shipment_confirmations', [
            'order_id' => $order->id,
            'shipment_batch' => 2,
            'shipped_quantity' => 40,
            'fulfillment_status' => 'DIKIRIM_SISA_BACKORDER',
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'shipment_batch' => 2,
            'amount' => 1200000,
            'payment_status' => 'UNPAID',
        ]);
    }

    // Pembayaran invoice per termin batch oleh UMKM
    public function test_umkm_bisa_bayar_invoice_per_batch_midtrans(): void
    {
        $this->actingAs($this->umkmUser);
        $order = $this->createOrder(100, 30000);

        $payment = Payment::create([
            'order_id' => $order->id,
            'shipment_batch' => 1,
            'invoice_number' => 'INV-TEST-001',
            'amount' => 1800000,
            'payment_status' => 'UNPAID',
        ]);

        $response = $this->post("/umkm/pemesanan/pembayaran/{$payment->id}/bayar", [
            'payment_method' => 'Midtrans BCA Virtual Account',
        ]);

        $response->assertSessionHasNoErrors();

        $payment->refresh();
        $this->assertEquals('PAID', $payment->payment_status);
        $this->assertEquals('Midtrans BCA Virtual Account', $payment->payment_method);
        $this->assertNotNull($payment->paid_at);
        $this->assertNotNull($payment->midtrans_transaction_id);
    }

    // Pemasok pilih no backorder dan menunggu validasi UMKM
    public function test_supplier_bisa_pilih_no_backorder_dan_menunggu_validasi_umkm_sebelum_dikirim(): void
    {
        $this->actingAs($this->supplierUser);
        $order = $this->createOrder(100, 30000);

        $this->post("/supplier/pengiriman/{$order->id}/kirim", [
            'qty_to_ship' => 70,
            'backorder_action' => 'no_backorder',
            'tracking_notes' => 'Sisa 30 kg ditiadakan karena stok habis.',
        ]);

        $order->refresh();
        $this->assertEquals(Order::STATUS_WAITING_BACKORDER_APPROVAL, $order->order_status);

        $shipment = $order->shipmentConfirmations()->where('shipment_batch', 1)->first();
        $this->assertNotNull($shipment);
        $this->assertEquals('no_backorder', $shipment->backorder_action);
        $this->assertEquals('pending', $shipment->backorder_approval_status);
        $this->assertNull($shipment->shipped_at);
    }

    // UMKM menolak pengajuan no backorder sehingga order batal
    public function test_umkm_bisa_menolak_pengajuan_no_backorder_dan_pesanan_dibatalkan(): void
    {
        $order = $this->createOrder(100, 30000);

        // Supplier ajukan no_backorder
        $this->actingAs($this->supplierUser);
        $this->post("/supplier/pengiriman/{$order->id}/kirim", [
            'qty_to_ship' => 50,
            'backorder_action' => 'no_backorder',
        ]);

        // UMKM tolak pengajuan
        $this->actingAs($this->umkmUser);
        $this->post("/umkm/pemesanan/{$order->id}/tolak-backorder", [
            'rejection_reason' => 'Kami memerlukan pasokan utuh 100 kg sesuai kontrak.',
        ]);

        $order->refresh();
        $this->assertEquals(Order::STATUS_CANCELLED, $order->order_status);
    }

    // UMKM konfirmasi terima kloter 1 dan upload foto bukti fisik
    public function test_umkm_konfirmasi_batch_1_dan_berpindah_ke_backorder(): void
    {
        $order = $this->createOrder(100, 30000);

        // Backorder disetujui
        $this->actingAs($this->supplierUser);
        $this->post("/supplier/pengiriman/{$order->id}/kirim", [
            'qty_to_ship' => 60,
            'backorder_action' => 'create_backorder',
            'backorder_estimated_delivery_date' => now()->addDays(2)->toDateString(),
            'backorder_notes' => 'Batch 2 akan dikirim 2 hari lagi',
        ]);

        $this->actingAs($this->umkmUser);
        $this->post("/umkm/pemesanan/{$order->id}/terima-backorder");

        $this->actingAs($this->supplierUser);
        $this->post("/supplier/pengiriman/{$order->id}/kirim-batch1");

        // Konfirmasi penerimaan dengan upload foto
        $this->actingAs($this->umkmUser);
        $file = UploadedFile::fake()->image('bukti_batch1.jpg');

        $response = $this->post("/umkm/pemesanan/{$order->id}/konfirmasi", [
            'proof_photo' => $file,
            'confirmation_notes' => 'Daging ayam segar tiba di dapur pukul 05:45 WIB.',
        ]);

        $response->assertSessionHasNoErrors();

        $batch1 = $order->shipmentConfirmations()->where('shipment_batch', 1)->first();
        $this->assertNotNull($batch1->received_at);
        $this->assertNotNull($batch1->proof_photo_url);
        $this->assertEquals('BARANG_DITERIMA', $batch1->fulfillment_status);

        $order->refresh();
        $this->assertEquals(Order::STATUS_BACKORDER_PENDING, $order->order_status);
    }

    // Konfirmasi bertahap kloter 1 lalu kloter 2
    public function test_umkm_konfirmasi_pesanan_satu_satu_sesuai_batch_backorder(): void
    {
        $order = $this->createOrder(100, 30000);

        $this->actingAs($this->supplierUser);
        $this->post("/supplier/pengiriman/{$order->id}/kirim", [
            'qty_to_ship' => 60,
            'backorder_action' => 'create_backorder',
            'backorder_estimated_delivery_date' => now()->addDays(2)->toDateString(),
            'backorder_notes' => 'Batch 2 menyusul',
        ]);

        $this->actingAs($this->umkmUser);
        $this->post("/umkm/pemesanan/{$order->id}/terima-backorder");

        $this->actingAs($this->supplierUser);
        $this->post("/supplier/pengiriman/{$order->id}/kirim-batch1");

        // Konfirmasi kloter 1
        $this->actingAs($this->umkmUser);
        $this->post("/umkm/pemesanan/{$order->id}/konfirmasi", [
            'proof_photo' => UploadedFile::fake()->image('bukti_b1.jpg'),
        ]);

        // Supplier kirim kloter 2
        $this->actingAs($this->supplierUser);
        $this->post("/supplier/pengiriman/{$order->id}/kirim-backorder");

        // Konfirmasi kloter 2
        $this->actingAs($this->umkmUser);
        $response = $this->post("/umkm/pemesanan/{$order->id}/konfirmasi-backorder", [
            'proof_photo' => UploadedFile::fake()->image('bukti_b2.jpg'),
            'confirmation_notes' => 'Sisa 40 kg diterima lengkap.',
        ]);

        $response->assertSessionHasNoErrors();

        $batch2 = $order->shipmentConfirmations()->where('shipment_batch', 2)->first();
        $this->assertNotNull($batch2->received_at);
        $this->assertNotNull($batch2->proof_photo_url);

        $order->refresh();
        $this->assertEquals(Order::STATUS_COMPLETED, $order->order_status);
    }

    // UMKM tolak sisa backorder dan tagihan otomatis disesuaikan
    public function test_umkm_bisa_tolak_pengajuan_backorder_dan_sisa_dibatalkan_serta_tagihan_dipotong(): void
    {
        $order = $this->createOrder(100, 30000);

        // Supplier ajukan backorder
        $this->actingAs($this->supplierUser);
        $this->post("/supplier/pengiriman/{$order->id}/kirim", [
            'qty_to_ship' => 60,
            'backorder_action' => 'create_backorder',
            'backorder_estimated_delivery_date' => now()->addDays(3)->toDateString(),
            'backorder_notes' => 'Menunggu panen',
        ]);

        // UMKM tolak backorder
        $this->actingAs($this->umkmUser);
        $this->post("/umkm/pemesanan/{$order->id}/tolak-backorder", [
            'rejection_reason' => 'Kami tidak ingin menunggu pengiriman kloter 2.',
        ]);

        $order->refresh();
        // Tagihan dipotong hanya untuk 60 kg: 60 * 30.000 = Rp 1.800.000
        $this->assertEquals(1800000, (float) $order->total_amount);
        $this->assertEquals(0, (float) $order->items->first()->quantity_remaining);

        $batch1 = $order->shipmentConfirmations()->where('shipment_batch', 1)->first();
        $this->assertEquals('rejected', $batch1->backorder_approval_status);
    }
}
