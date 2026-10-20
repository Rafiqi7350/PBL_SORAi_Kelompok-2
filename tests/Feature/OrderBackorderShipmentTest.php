<?php

namespace Tests\Feature;

use App\Models\Commodity;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Partnership;
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

    private function setupTestData(): array
    {
        $supplierUser = User::create([
            'email' => 'supplier_'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
            'role' => 'supplier',
            'status' => 'active',
        ]);

        $supplier = SupplierProfile::create([
            'user_id' => $supplierUser->id,
            'company_name' => 'PT Pemasok Mitra Makmur',
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
            'business_name' => 'Dapur Soto Segar',
            'restaurant_name' => 'Dapur Soto Segar',
            'owner_name' => 'Ibu Siti',
            'phone' => '08987654321',
            'address' => 'Jl. Tebet Raya No. 4',
        ]);

        $commodity = Commodity::create([
            'name' => 'Daging Ayam Broiler',
            'category' => 'Unggas & Daging Segar',
            'unit' => 'kg',
        ]);

        $product = SupplierProduct::create([
            'supplier_id' => $supplier->id,
            'commodity_id' => $commodity->id,
            'price' => 30000,
            'stock_status' => 'Tersedia',
            'stock_capacity' => '500 kg',
        ]);

        $partnership = Partnership::create([
            'supplier_id' => $supplier->id,
            'umkm_id' => $umkm->id,
            'status' => 'active',
        ]);

        return compact('supplierUser', 'supplier', 'umkmUser', 'umkm', 'product', 'partnership');
    }

    private function createOrder($partnership, $product, $qty = 100, $price = 30000): Order
    {
        $order = Order::create([
            'partnership_id' => $partnership->id,
            'order_date' => now()->toDateString(),
            'delivery_date' => now()->addDay()->toDateString(),
            'total_amount' => $qty * $price,
            'order_status' => Order::STATUS_PENDING,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'supplier_product_id' => $product->id,
            'quantity' => $qty,
            'quantity_delivered' => 0,
            'quantity_remaining' => $qty,
            'unit_price' => $price,
            'subtotal' => $qty * $price,
            'fulfillment_item_status' => 'pending',
        ]);

        return $order;
    }

    public function test_supplier_bisa_kirim_penuh_lunas(): void
    {
        $data = $this->setupTestData();
        $order = $this->createOrder($data['partnership'], $data['product'], 100);

        $response = $this->actingAs($data['supplierUser'])
            ->post(route('supplier.pengiriman.kirim', $order), [
                'qty_to_ship' => 100,
                'backorder_action' => 'none',
                'tracking_notes' => 'Armada Pickup Box B 1234 XYZ',
            ]);

        $response->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals(Order::STATUS_SHIPPED, $order->order_status);

        $item = $order->items->first();
        $this->assertEquals(100, (float) $item->quantity_delivered);
        $this->assertEquals(0, (float) $item->quantity_remaining);
        $this->assertEquals('fulfilled', $item->fulfillment_item_status);

        $this->assertDatabaseHas('shipment_confirmations', [
            'order_id' => $order->id,
            'shipment_batch' => 1,
            'shipped_quantity' => 100,
            'backorder_action' => 'none',
        ]);
    }

    public function test_supplier_bisa_create_backorder_dan_kirim_batch_2(): void
    {
        $data = $this->setupTestData();
        $order = $this->createOrder($data['partnership'], $data['product'], 150);

        // Batch 1: Kirim 100 kg dari 150 kg (Ajukan Backorder)
        $response = $this->actingAs($data['supplierUser'])
            ->post(route('supplier.pengiriman.kirim', $order), [
                'qty_to_ship' => 100,
                'backorder_action' => 'create_backorder',
                'tracking_notes' => 'Pengiriman Batch 1',
                'backorder_estimated_delivery_date' => now()->addDays(2)->toDateString(),
                'backorder_notes' => 'Stok sisa panen hanya 100 kg, sisa 50 kg dikirim 2 hari lagi.',
            ]);

        $response->assertSessionHas('success');

        $order->refresh();
        // Status pesanan menjadi waiting_backorder_approval
        $this->assertEquals(Order::STATUS_WAITING_BACKORDER_APPROVAL, $order->order_status);

        $item = $order->items->first();
        $this->assertEquals(100, (float) $item->quantity_delivered);
        $this->assertEquals(50, (float) $item->quantity_remaining);
        $this->assertEquals('backorder', $item->fulfillment_item_status);

        $batch1 = $order->shipmentConfirmations()->where('shipment_batch', 1)->first();
        $this->assertEquals('pending', $batch1->backorder_approval_status);
        $this->assertNull($batch1->shipped_at, 'Kloter 1 tidak boleh langsung berstatus dikirim saat pengajuan backorder.');
        $this->assertEquals('Stok sisa panen hanya 100 kg, sisa 50 kg dikirim 2 hari lagi.', $batch1->backorder_notes);

        // Pemasok belum bisa kirim Batch 2 sebelum UMKM setuju
        $failShipBO = $this->actingAs($data['supplierUser'])
            ->post(route('supplier.pengiriman.kirim-backorder', $order));
        $failShipBO->assertSessionHas('error');

        // Pemasok juga belum bisa kirim Batch 1 sebelum UMKM setuju
        $failShipB1 = $this->actingAs($data['supplierUser'])
            ->post(route('supplier.pengiriman.kirim-batch1', $order));
        $failShipB1->assertSessionHas('error');

        // UMKM menyetujui pengajuan backorder
        $approveResponse = $this->actingAs($data['umkmUser'])
            ->post(route('umkm.pemesanan.terima-backorder', $order));
        $approveResponse->assertSessionHas('success');

        $order->refresh();
        $batch1->refresh();
        $this->assertEquals(Order::STATUS_BACKORDER_PENDING, $order->order_status);
        $this->assertEquals('approved', $batch1->backorder_approval_status);
        $this->assertNotNull($batch1->backorder_approved_at);
        // Persetujuan UMKM BUKAN berarti sekalian barang terkirim:
        $this->assertNull($batch1->shipped_at, 'Persetujuan UMKM bukan berarti barang langsung terkirim.');

        // Pemasok sekarang memberangkatkan barang Kloter 1
        $shipB1Response = $this->actingAs($data['supplierUser'])
            ->post(route('supplier.pengiriman.kirim-batch1', $order), [
                'tracking_notes' => 'Armada Pickup B 1234 XYZ berangkat membawa Kloter 1',
            ]);
        $shipB1Response->assertSessionHas('success');

        $batch1->refresh();
        $this->assertNotNull($batch1->shipped_at, 'Kloter 1 kini resmi terkirim setelah pemasok klik kirim.');

        // Batch 2: Kirim sisa 50 kg setelah restock
        $response2 = $this->actingAs($data['supplierUser'])
            ->post(route('supplier.pengiriman.kirim-backorder', $order), [
                'tracking_notes' => 'Pengiriman Batch 2 Sisa Backorder',
            ]);

        $response2->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals(Order::STATUS_SHIPPED, $order->order_status);

        $item->refresh();
        $this->assertEquals(150, (float) $item->quantity_delivered);
        $this->assertEquals(0, (float) $item->quantity_remaining);
        $this->assertEquals('fulfilled', $item->fulfillment_item_status);

        $this->assertDatabaseHas('shipment_confirmations', [
            'order_id' => $order->id,
            'shipment_batch' => 2,
            'shipped_quantity' => 50,
        ]);

        // Verifikasi bahwa masing-masing batch memiliki Invoice & Pembayaran terpisah
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'shipment_batch' => 1,
            'amount' => 3000000,
            'quantity_billed' => 100,
            'payment_status' => 'UNPAID',
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'shipment_batch' => 2,
            'amount' => 1500000,
            'quantity_billed' => 50,
            'payment_status' => 'UNPAID',
        ]);
    }

    public function test_umkm_bisa_bayar_invoice_per_batch_midtrans(): void
    {
        $data = $this->setupTestData();
        $order = $this->createOrder($data['partnership'], $data['product'], 150);

        // Supplier ajukan backorder (100 kg dikirim kloter 1, 50 kg sisa)
        $this->actingAs($data['supplierUser'])
            ->post(route('supplier.pengiriman.kirim', $order), [
                'qty_to_ship' => 100,
                'backorder_action' => 'create_backorder',
                'backorder_estimated_delivery_date' => now()->addDays(2)->toDateString(),
                'backorder_notes' => 'Restock lusa',
            ]);

        // UMKM setujui backorder
        $this->actingAs($data['umkmUser'])
            ->post(route('umkm.pemesanan.terima-backorder', $order));

        // Supplier berangkatkan Kloter 1 (100 kg)
        $this->actingAs($data['supplierUser'])
            ->post(route('supplier.pengiriman.kirim-batch1', $order));

        // Supplier kirim Batch 2 (50 kg)
        $this->actingAs($data['supplierUser'])
            ->post(route('supplier.pengiriman.kirim-backorder', $order));

        $payment1 = $order->payments()->where('shipment_batch', 1)->first();
        $payment2 = $order->payments()->where('shipment_batch', 2)->first();

        $this->assertNotNull($payment1);
        $this->assertNotNull($payment2);
        $this->assertEquals(3000000, (float) $payment1->amount);
        $this->assertEquals(1500000, (float) $payment2->amount);

        // UMKM membayar Batch 1 saja terlebih dahulu
        $payResponse1 = $this->actingAs($data['umkmUser'])
            ->post(route('umkm.pemesanan.bayar-invoice', $payment1), [
                'payment_method' => 'Midtrans BCA Virtual Account',
            ]);

        $payResponse1->assertSessionHas('success');
        $payment1->refresh();
        $payment2->refresh();

        $this->assertTrue($payment1->isPaid());
        $this->assertFalse($payment2->isPaid());

        // UMKM kemudian membayar Batch 2
        $payResponse2 = $this->actingAs($data['umkmUser'])
            ->post(route('umkm.pemesanan.bayar-invoice', $payment2), [
                'payment_method' => 'Midtrans QRIS',
            ]);

        $payResponse2->assertSessionHas('success');
        $payment2->refresh();
        $this->assertTrue($payment2->isPaid());
    }

    public function test_supplier_bisa_pilih_no_backorder_dan_menunggu_validasi_umkm_sebelum_dikirim(): void
    {
        $data = $this->setupTestData();
        $order = $this->createOrder($data['partnership'], $data['product'], 100, 30000);

        // 1. Pemasok mengajukan No Backorder (hanya sanggup kirim 60 kg, sisa 40 kg ditiadakan)
        $response = $this->actingAs($data['supplierUser'])
            ->post(route('supplier.pengiriman.kirim', $order), [
                'qty_to_ship' => 60,
                'backorder_action' => 'no_backorder',
                'tracking_notes' => 'Sisa 40 kg ditiadakan karena stok gudang habis',
            ]);

        $response->assertSessionHas('success');

        $order->refresh();
        // Menunggu persetujuan UMKM
        $this->assertEquals(Order::STATUS_WAITING_BACKORDER_APPROVAL, $order->order_status);

        $shipment1 = $order->shipmentConfirmations()->where('shipment_batch', 1)->first();
        $this->assertNotNull($shipment1);
        $this->assertEquals('no_backorder', $shipment1->backorder_action);
        $this->assertEquals('pending', $shipment1->backorder_approval_status);
        $this->assertNull($shipment1->shipped_at, 'Barang tidak boleh langsung berstatus dikirim saat pengajuan no backorder.');

        // Invoice belum boleh diterbitkan
        $this->assertDatabaseMissing('payments', [
            'order_id' => $order->id,
            'shipment_batch' => 1,
        ]);

        // 2. UMKM menyetujui pengajuan pemenuhan sebagian tanpa backorder
        $approveResponse = $this->actingAs($data['umkmUser'])
            ->post(route('umkm.pemesanan.terima-backorder', $order));
        $approveResponse->assertSessionHas('success');

        $order->refresh();
        $shipment1->refresh();

        $this->assertEquals('approved', $shipment1->backorder_approval_status);
        // Persetujuan UMKM bukan berarti langsung terkirim (shipped_at tetap null menunggu pemasok kirim)
        $this->assertNull($shipment1->shipped_at, 'Persetujuan UMKM bukan berarti barang sekalian langsung terkirim.');
        // Nilai tagihan disesuaikan menjadi 60 * 30000 = 1.800.000
        $this->assertEquals(1800000, (float) $order->total_amount);

        $item = $order->items->first();
        $this->assertEquals(60, (float) $item->quantity_delivered);
        $this->assertEquals(0, (float) $item->quantity_remaining);
        $this->assertEquals('partially_accepted', $item->fulfillment_item_status);

        // 3. Pemasok memberangkatkan barang setelah disetujui
        $shipResponse = $this->actingAs($data['supplierUser'])
            ->post(route('supplier.pengiriman.kirim-batch1', $order), [
                'tracking_notes' => 'Mobil Pickup berangkat mengantar 60 kg pasokan ayam',
            ]);
        $shipResponse->assertSessionHas('success');

        $order->refresh();
        $shipment1->refresh();

        $this->assertNotNull($shipment1->shipped_at, 'Barang kini resmi terkirim setelah pemasok mengklik tombol kirim.');
        $this->assertEquals(Order::STATUS_PARTIALLY_SHIPPED, $order->order_status);

        // Invoice resmi diterbitkan setelah barang diberangkatkan
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'shipment_batch' => 1,
            'amount' => 1800000,
            'quantity_billed' => 60,
            'payment_status' => 'UNPAID',
        ]);
    }

    public function test_umkm_bisa_menolak_pengajuan_no_backorder_dan_pesanan_dibatalkan(): void
    {
        $data = $this->setupTestData();
        $order = $this->createOrder($data['partnership'], $data['product'], 100, 30000);

        // Pemasok mengajukan no_backorder
        $this->actingAs($data['supplierUser'])
            ->post(route('supplier.pengiriman.kirim', $order), [
                'qty_to_ship' => 60,
                'backorder_action' => 'no_backorder',
                'tracking_notes' => 'Hanya ada 60 kg',
            ]);

        // UMKM menolak pengajuan pemenuhan sebagian tanpa backorder
        $rejectResponse = $this->actingAs($data['umkmUser'])
            ->post(route('umkm.pemesanan.tolak-backorder', $order), [
                'rejection_reason' => 'Restoran kami butuh pasokan lengkap 100 kg, tidak bisa terima 60 kg saja.',
            ]);

        $rejectResponse->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals(Order::STATUS_CANCELLED, $order->order_status);

        $shipment1 = $order->shipmentConfirmations()->where('shipment_batch', 1)->first();
        $this->assertEquals('rejected', $shipment1->backorder_approval_status);
        $this->assertEquals('Restoran kami butuh pasokan lengkap 100 kg, tidak bisa terima 60 kg saja.', $shipment1->backorder_rejection_reason);

        // Tidak ada invoice yang diterbitkan
        $this->assertDatabaseMissing('payments', [
            'order_id' => $order->id,
        ]);
    }

    public function test_umkm_konfirmasi_batch_1_dan_berpindah_ke_backorder(): void
    {
        Storage::fake('public');
        $data = $this->setupTestData();
        $order = $this->createOrder($data['partnership'], $data['product'], 150);

        // Pemasok kirim Batch 1 dengan create_backorder
        $this->actingAs($data['supplierUser'])
            ->post(route('supplier.pengiriman.kirim', $order), [
                'qty_to_ship' => 100,
                'backorder_action' => 'create_backorder',
                'backorder_estimated_delivery_date' => now()->addDays(2)->toDateString(),
                'backorder_notes' => 'Restock lusa',
            ]);

        // UMKM menyetujui pengajuan backorder
        $this->actingAs($data['umkmUser'])
            ->post(route('umkm.pemesanan.terima-backorder', $order));

        // UMKM konfirmasi Batch 1
        $fakePhoto = UploadedFile::fake()->image('dapur_proof.jpg');
        $response = $this->actingAs($data['umkmUser'])
            ->post(route('umkm.pemesanan.konfirmasi', $order), [
                'proof_photo' => $fakePhoto,
                'confirmation_notes' => '100 kg ayam broiler diterima segar.',
            ]);

        $response->assertSessionHas('success');

        $order->refresh();
        // Karena ada backorder aktif disetujui, status berpindah ke backorder_pending
        $this->assertEquals(Order::STATUS_BACKORDER_PENDING, $order->order_status);
    }

    public function test_umkm_konfirmasi_pesanan_satu_satu_sesuai_batch_backorder(): void
    {
        Storage::fake('public');
        $data = $this->setupTestData();
        $order = $this->createOrder($data['partnership'], $data['product'], 150);

        // 1. Pemasok kirim Batch 1 (100 kg) dengan backorder
        $this->actingAs($data['supplierUser'])
            ->post(route('supplier.pengiriman.kirim', $order), [
                'qty_to_ship' => 100,
                'backorder_action' => 'create_backorder',
                'tracking_notes' => 'Armada 1',
                'backorder_estimated_delivery_date' => now()->addDays(2)->toDateString(),
                'backorder_notes' => 'Panen lusa',
            ]);

        // UMKM setujui backorder
        $this->actingAs($data['umkmUser'])
            ->post(route('umkm.pemesanan.terima-backorder', $order));

        // 2. UMKM tidak bisa konfirmasi Batch 2 jika Batch 1 belum dikonfirmasi
        $fakePhotoBO = UploadedFile::fake()->image('bukti_bo.jpg');
        $failBO = $this->actingAs($data['umkmUser'])
            ->post(route('umkm.pemesanan.konfirmasi-backorder', $order), [
                'proof_photo' => $fakePhotoBO,
            ]);
        $failBO->assertSessionHas('error');

        // 3. UMKM konfirmasi Batch 1
        $fakePhoto1 = UploadedFile::fake()->image('bukti_batch1.jpg');
        $resB1 = $this->actingAs($data['umkmUser'])
            ->post(route('umkm.pemesanan.konfirmasi', $order), [
                'proof_photo' => $fakePhoto1,
                'confirmation_notes' => 'Batch 1 diterima 100 kg di dapur.',
            ]);
        $resB1->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals(Order::STATUS_BACKORDER_PENDING, $order->order_status);

        $batch1Conf = $order->shipmentConfirmations()->where('shipment_batch', 1)->first();
        $this->assertNotNull($batch1Conf->received_at);
        $this->assertEquals('Batch 1 diterima 100 kg di dapur.', $batch1Conf->confirmation_notes);

        // 4. UMKM tidak bisa konfirmasi Batch 2 sebelum Pemasok memberangkatkan Batch 2
        $failB2BeforeShipped = $this->actingAs($data['umkmUser'])
            ->post(route('umkm.pemesanan.konfirmasi-backorder', $order), [
                'proof_photo' => $fakePhotoBO,
            ]);
        $failB2BeforeShipped->assertSessionHas('error');

        // 5. Pemasok kirim Batch 2 (50 kg)
        $this->actingAs($data['supplierUser'])
            ->post(route('supplier.pengiriman.kirim-backorder', $order), [
                'tracking_notes' => 'Armada 2 Sisa Backorder',
            ]);

        // 6. UMKM konfirmasi Batch 2
        $fakePhoto2 = UploadedFile::fake()->image('bukti_batch2.jpg');
        $resB2 = $this->actingAs($data['umkmUser'])
            ->post(route('umkm.pemesanan.konfirmasi-backorder', $order), [
                'proof_photo' => $fakePhoto2,
                'confirmation_notes' => 'Batch 2 sisa 50 kg diterima lengkap.',
            ]);
        $resB2->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals(Order::STATUS_COMPLETED, $order->order_status);

        $batch2Conf = $order->shipmentConfirmations()->where('shipment_batch', 2)->first();
        $this->assertNotNull($batch2Conf->received_at);
        $this->assertEquals('Batch 2 sisa 50 kg diterima lengkap.', $batch2Conf->confirmation_notes);
    }

    public function test_umkm_bisa_tolak_pengajuan_backorder_dan_sisa_dibatalkan_serta_tagihan_dipotong(): void
    {
        $data = $this->setupTestData();
        $order = $this->createOrder($data['partnership'], $data['product'], 150, 30000);

        // 1. Pemasok kirim Batch 1 (100 kg dari 150 kg) dengan ajukan backorder
        $this->actingAs($data['supplierUser'])
            ->post(route('supplier.pengiriman.kirim', $order), [
                'qty_to_ship' => 100,
                'backorder_action' => 'create_backorder',
                'tracking_notes' => 'Armada Kloter 1',
                'backorder_estimated_delivery_date' => now()->addDays(3)->toDateString(),
                'backorder_notes' => 'Menunggu panen berikutnya.',
            ]);

        $order->refresh();
        $this->assertEquals(Order::STATUS_WAITING_BACKORDER_APPROVAL, $order->order_status);

        // 2. UMKM menolak pengajuan backorder
        $rejectResponse = $this->actingAs($data['umkmUser'])
            ->post(route('umkm.pemesanan.tolak-backorder', $order), [
                'rejection_reason' => 'Bahan baku mendesak untuk catering siang ini, sisa akan kami beli sendiri di pasar.',
            ]);

        $rejectResponse->assertSessionHas('success');

        $order->refresh();
        $batch1 = $order->shipmentConfirmations()->where('shipment_batch', 1)->first();
        $item = $order->items->first();

        // Verifikasi: Backorder status ditolak dan alasan tercatat
        $this->assertEquals('rejected', $batch1->backorder_approval_status);
        $this->assertEquals('no_backorder', $batch1->backorder_action);
        $this->assertEquals('Bahan baku mendesak untuk catering siang ini, sisa akan kami beli sendiri di pasar.', $batch1->backorder_rejection_reason);

        // Verifikasi: Sisa 50 kg resmi dibatalkan
        $this->assertEquals(100, (float) $item->quantity_delivered);
        $this->assertEquals(0, (float) $item->quantity_remaining);
        $this->assertEquals('cancelled', $item->fulfillment_item_status);

        // Verifikasi: Total tagihan dipotong menjadi 100 kg * 30.000 = 3.000.000 (bukan 4.500.000)
        $this->assertEquals(3000000, (float) $order->total_amount);

        // Verifikasi: Pemasok TIDAK BISA kirim sisa barang
        $failSendAfterReject = $this->actingAs($data['supplierUser'])
            ->post(route('supplier.pengiriman.kirim-backorder', $order));
        $failSendAfterReject->assertSessionHas('error');
    }
}
