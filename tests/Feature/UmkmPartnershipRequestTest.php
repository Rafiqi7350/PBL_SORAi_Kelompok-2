<?php

namespace Tests\Feature;

use App\Models\Commodity;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Partnership;
use App\Models\PartnershipDeliverySchedule;
use App\Models\SupplierProduct;
use App\Models\SupplierProfile;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UmkmPartnershipRequestTest extends TestCase
{
    use RefreshDatabase;

    protected User $umkmUser;

    protected UmkmProfile $umkm;

    protected User $supplierUser;

    protected SupplierProfile $supplier;

    protected Commodity $commodity;

    protected SupplierProduct $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->umkmUser = User::factory()->create([
            'role' => 'umkm',
            'status' => 'active',
            'email' => 'dapur.sedap@test.com',
        ]);

        $this->umkm = UmkmProfile::create([
            'user_id' => $this->umkmUser->id,
            'business_name' => 'Dapur Sedap Gurih',
            'owner_name' => 'Siti Khadijah',
            'phone' => '081234567890',
            'address' => 'Jl. Ijen No. 25, Malang',
        ]);

        $this->supplierUser = User::factory()->create([
            'role' => 'supplier',
            'status' => 'active',
            'email' => 'poultry.malang@test.com',
        ]);

        $this->supplier = SupplierProfile::create([
            'user_id' => $this->supplierUser->id,
            'company_name' => 'Kelompok Tani Poultry Malang',
            'owner_name' => 'Pak Haji Tani',
            'phone' => '089887766554',
            'warehouse_address' => 'Jl. Kebon Agung No. 12, Malang',
            'delivery_area' => 'Jawa Timur',
        ]);

        $this->commodity = Commodity::create([
            'name' => 'Daging Ayam Karkas',
            'category' => 'Daging & Unggas',
            'unit' => 'kg',
            'government_ref_price' => 35000.00,
        ]);

        $this->product = SupplierProduct::create([
            'supplier_id' => $this->supplier->id,
            'commodity_id' => $this->commodity->id,
            'price' => 34000.00,
            'stock_capacity' => 1500.00,
            'stock_status' => 'Tersedia',
            'quality_grade' => 'Grade A',
            'is_halal_certified' => true,
        ]);
    }

    public function test_umkm_can_submit_partnership_request_and_redirects_to_riwayat_permintaan(): void
    {
        $response = $this->actingAs($this->umkmUser)->post(route('umkm.katalog.ajukan'), [
            'supplier_id' => $this->supplier->id,
            'supplier_product_id' => $this->product->id,
            'volume_per_delivery' => 120,
            'unit' => 'kg',
            'frequency_type' => '2x Seminggu (Senin & Kamis)',
            'delivery_days' => ['Senin', 'Kamis'],
            'delivery_time' => '05:30',
            'start_date' => now()->addDay()->toDateString(),
            'is_auto_order_active' => 1,
            'terms' => ['Garansi Segar & Bebas Busuk', 'Wajib Tiba Tepat Waktu'],
            'submission_details' => 'Pemotongan 8 bagian, kirim subuh.',
        ]);

        $response->assertRedirect(route('umkm.katalog.index', ['tab' => 'riwayat-permintaan']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('partnerships', [
            'umkm_id' => $this->umkm->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'pending',
        ]);

        $partnership = Partnership::where('umkm_id', $this->umkm->id)->latest()->first();
        $this->assertNotNull($partnership);

        $this->assertDatabaseHas('partnership_delivery_schedules', [
            'partnership_id' => $partnership->id,
            'supplier_product_id' => $this->product->id,
            'volume_per_delivery' => 120,
        ]);
    }

    public function test_umkm_can_view_riwayat_permintaan_tab(): void
    {
        $partnership = Partnership::create([
            'umkm_id' => $this->umkm->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'pending',
            'submission_details' => '[Ketentuan Mutu: Garansi Segar] Pesanan rutin.',
        ]);

        PartnershipDeliverySchedule::create([
            'partnership_id' => $partnership->id,
            'supplier_product_id' => $this->product->id,
            'volume_per_delivery' => 80.0,
            'total_contract_volume' => 640.0,
            'unit' => 'kg',
            'frequency_type' => '2x Seminggu',
            'delivery_days' => ['Senin', 'Kamis'],
            'delivery_time' => '06:00:00',
            'start_date' => now()->addDays(2)->toDateString(),
            'is_auto_order_active' => true,
        ]);

        $response = $this->actingAs($this->umkmUser)->get(route('umkm.katalog.index', ['tab' => 'riwayat-permintaan']));

        $response->assertOk();
        $response->assertSee('Riwayat Pengajuan Permintaan');
        $response->assertSee($this->commodity->name);
        $response->assertSee($this->supplier->company_name);
    }

    public function test_umkm_can_update_partnership_request_within_30_minutes(): void
    {
        $partnership = Partnership::create([
            'umkm_id' => $this->umkm->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'pending',
            'submission_details' => 'Catatan awal',
        ]);
        // Set created_at to 10 minutes ago (< 30 minutes)
        $partnership->created_at = now()->subMinutes(10);
        $partnership->save();

        $schedule = PartnershipDeliverySchedule::create([
            'partnership_id' => $partnership->id,
            'supplier_product_id' => $this->product->id,
            'volume_per_delivery' => 50.0,
            'total_contract_volume' => 400.0,
            'unit' => 'kg',
            'frequency_type' => '2x Seminggu',
            'delivery_days' => ['Senin', 'Kamis'],
            'delivery_time' => '05:30:00',
            'start_date' => now()->addDay()->toDateString(),
            'is_auto_order_active' => true,
        ]);

        $response = $this->actingAs($this->umkmUser)->put(route('umkm.katalog.permintaan.update', $partnership->id), [
            'volume_per_delivery' => 150,
            'frequency_type' => '3x Seminggu (Senin, Rabu, Jumat)',
            'delivery_days' => ['Senin', 'Rabu', 'Jumat'],
            'delivery_time' => '05:00',
            'start_date' => now()->addDays(3)->toDateString(),
            'is_auto_order_active' => 1,
            'terms' => ['Kemasan Higienis'],
            'submission_details' => 'Catatan revisi dapur terbaru',
        ]);

        $response->assertRedirect(route('umkm.katalog.index', ['tab' => 'riwayat-permintaan']));
        $response->assertSessionHas('success');

        $schedule->refresh();
        $this->assertEquals(150, (float) $schedule->volume_per_delivery);
        $this->assertEquals('05:00:00', $schedule->delivery_time);

        $partnership->refresh();
        $this->assertStringContainsString('Catatan revisi dapur terbaru', (string) $partnership->submission_details);
    }

    public function test_umkm_cannot_update_partnership_request_after_30_minutes(): void
    {
        $partnership = Partnership::create([
            'umkm_id' => $this->umkm->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'pending',
            'submission_details' => 'Catatan awal',
        ]);
        // Set created_at to 35 minutes ago (>= 30 minutes)
        Partnership::where('id', $partnership->id)->update(['created_at' => now()->subMinutes(35)]);
        $partnership->refresh();

        PartnershipDeliverySchedule::create([
            'partnership_id' => $partnership->id,
            'supplier_product_id' => $this->product->id,
            'volume_per_delivery' => 50.0,
            'total_contract_volume' => 400.0,
            'unit' => 'kg',
            'frequency_type' => '2x Seminggu',
            'delivery_days' => ['Senin', 'Kamis'],
            'delivery_time' => '05:30:00',
            'start_date' => now()->addDay()->toDateString(),
            'is_auto_order_active' => true,
        ]);

        $response = $this->actingAs($this->umkmUser)->put(route('umkm.katalog.permintaan.update', $partnership->id), [
            'volume_per_delivery' => 200,
        ]);

        $response->assertSessionHas('error');
    }

    public function test_umkm_cannot_update_another_umkms_partnership_request(): void
    {
        $otherUmkmUser = User::factory()->create(['role' => 'umkm', 'status' => 'active']);
        $otherUmkm = UmkmProfile::create([
            'user_id' => $otherUmkmUser->id,
            'business_name' => 'Warung Lain',
            'owner_name' => 'Bambang',
            'phone' => '081299988877',
            'address' => 'Surabaya',
        ]);

        $partnership = Partnership::create([
            'umkm_id' => $otherUmkm->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->umkmUser)->put(route('umkm.katalog.permintaan.update', $partnership->id), [
            'volume_per_delivery' => 100,
        ]);

        $response->assertForbidden();
    }

    public function test_umkm_cannot_update_request_if_already_approved(): void
    {
        $partnership = Partnership::create([
            'umkm_id' => $this->umkm->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'active', // already approved/active
        ]);

        $response = $this->actingAs($this->umkmUser)->put(route('umkm.katalog.permintaan.update', $partnership->id), [
            'volume_per_delivery' => 100,
        ]);

        $response->assertSessionHas('error');
    }

    public function test_umkm_portal_renders_guide_modal_and_tab_explanations(): void
    {
        $response = $this->actingAs($this->umkmUser)->get(route('umkm.katalog.index'));

        $response->assertOk();
        $response->assertSee('Selamat Datang di Portal Dapur UMKM');
        $response->assertSee('Panduan Interaktif Dapur');
        $response->assertSee('Alur Proses Bisnis UMKM');
        $response->assertSee('Penjelasan Fungsi Setiap Tab');
        $response->assertSee('Jangan tampilkan panduan ini lagi');
        $response->assertSee('Katalog Bahan Baku');
        $response->assertSee('Riwayat Permintaan');
        $response->assertSee('Perencanaan Pasokan');
        $response->assertSee('Pesanan & Pengiriman');
        $response->assertSee('Analisis Pengadaan');
        $response->assertSee('Pemantauan & SLA');
    }

    public function test_umkm_can_cancel_partnership_request_within_30_minutes(): void
    {
        $partnership = Partnership::create([
            'umkm_id' => $this->umkm->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'pending',
            'submission_details' => 'Catatan awal',
        ]);
        $partnership->created_at = now()->subMinutes(5);
        $partnership->save();

        $schedule = PartnershipDeliverySchedule::create([
            'partnership_id' => $partnership->id,
            'supplier_product_id' => $this->product->id,
            'volume_per_delivery' => 50.0,
            'total_contract_volume' => 400.0,
            'unit' => 'kg',
            'frequency_type' => '2x Seminggu',
            'delivery_days' => ['Senin', 'Kamis'],
            'delivery_time' => '05:30:00',
            'start_date' => now()->addDay()->toDateString(),
            'is_auto_order_active' => true,
        ]);

        $response = $this->actingAs($this->umkmUser)->post(route('umkm.katalog.permintaan.batal', $partnership->id));

        $response->assertRedirect(route('umkm.katalog.index', ['tab' => 'riwayat-permintaan']));
        $response->assertSessionHas('success');

        $partnership->refresh();
        $this->assertEquals('cancelled', $partnership->status);

        $schedule->refresh();
        $this->assertFalse((bool) $schedule->is_auto_order_active);
    }

    public function test_umkm_cannot_cancel_partnership_request_after_30_minutes(): void
    {
        $partnership = Partnership::create([
            'umkm_id' => $this->umkm->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'pending',
        ]);
        Partnership::where('id', $partnership->id)->update(['created_at' => now()->subMinutes(35)]);

        $response = $this->actingAs($this->umkmUser)->post(route('umkm.katalog.permintaan.batal', $partnership->id));

        $response->assertSessionHas('error');
        $partnership->refresh();
        $this->assertEquals('pending', $partnership->status);
    }

    public function test_umkm_cannot_cancel_another_umkms_partnership_request(): void
    {
        $otherUmkmUser = User::factory()->create(['role' => 'umkm', 'status' => 'active']);
        $otherUmkm = UmkmProfile::create([
            'user_id' => $otherUmkmUser->id,
            'business_name' => 'Warung Lain',
            'owner_name' => 'Bambang',
            'phone' => '081299988877',
            'address' => 'Surabaya',
        ]);

        $partnership = Partnership::create([
            'umkm_id' => $otherUmkm->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->umkmUser)->post(route('umkm.katalog.permintaan.batal', $partnership->id));

        $response->assertForbidden();
        $partnership->refresh();
        $this->assertEquals('pending', $partnership->status);
    }

    public function test_umkm_cannot_cancel_request_if_already_approved(): void
    {
        $partnership = Partnership::create([
            'umkm_id' => $this->umkm->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->umkmUser)->post(route('umkm.katalog.permintaan.batal', $partnership->id));

        $response->assertSessionHas('error');
        $partnership->refresh();
        $this->assertEquals('active', $partnership->status);
    }

    public function test_umkm_can_cancel_order_within_30_minutes(): void
    {
        $partnership = Partnership::create([
            'umkm_id' => $this->umkm->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'active',
        ]);

        $order = Order::create([
            'partnership_id' => $partnership->id,
            'order_date' => now()->toDateString(),
            'delivery_date' => now()->toDateString(),
            'total_amount' => 340000.0,
            'order_status' => Order::STATUS_PENDING,
        ]);
        $order->created_at = now()->subMinutes(10);
        $order->save();

        $item = OrderItem::create([
            'order_id' => $order->id,
            'supplier_product_id' => $this->product->id,
            'quantity' => 10,
            'quantity_delivered' => 0,
            'quantity_remaining' => 10,
            'unit_price' => 34000.0,
            'subtotal' => 340000.0,
            'fulfillment_item_status' => 'pending',
        ]);

        $response = $this->actingAs($this->umkmUser)->post(route('umkm.pemesanan.batal', $order->id));

        $response->assertRedirect(route('umkm.katalog.index', ['tab' => 'pemesanan', 'subtab' => 'auto-order']));
        $response->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals(Order::STATUS_CANCELLED, $order->order_status);

        $item->refresh();
        $this->assertEquals('cancelled', $item->fulfillment_item_status);
        $this->assertEquals(0, (float) $item->quantity_remaining);
    }

    public function test_umkm_cannot_cancel_order_after_30_minutes_or_if_shipped(): void
    {
        $partnership = Partnership::create([
            'umkm_id' => $this->umkm->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'active',
        ]);

        $order = Order::create([
            'partnership_id' => $partnership->id,
            'order_date' => now()->toDateString(),
            'delivery_date' => now()->toDateString(),
            'total_amount' => 340000.0,
            'order_status' => Order::STATUS_PENDING,
        ]);
        Order::where('id', $order->id)->update(['created_at' => now()->subMinutes(40)]);

        $response = $this->actingAs($this->umkmUser)->post(route('umkm.pemesanan.batal', $order->id));

        $response->assertSessionHas('error');
        $order->refresh();
        $this->assertEquals(Order::STATUS_PENDING, $order->order_status);
    }
}
