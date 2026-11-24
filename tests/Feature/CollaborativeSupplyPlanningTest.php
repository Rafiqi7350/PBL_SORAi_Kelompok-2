<?php

namespace Tests\Feature;

use App\Models\Commodity;
use App\Models\Partnership;
use App\Models\PartnershipDeliverySchedule;
use App\Models\SupplierProduct;
use App\Models\SupplierProfile;
use App\Models\SupplyPlan;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CollaborativeSupplyPlanningTest extends TestCase
{
    use RefreshDatabase;

    private User $umkmUser;

    private UmkmProfile $umkmProfile;

    private User $supplierUser;

    private SupplierProfile $supplierProfile;

    private Partnership $partnership;

    protected function setUp(): void
    {
        parent::setUp();

        $this->umkmUser = User::create([
            'email' => 'umkm.csp@example.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'active',
        ]);

        $this->umkmProfile = UmkmProfile::create([
            'user_id' => $this->umkmUser->id,
            'business_name' => 'Dapur Sambal Malang',
            'owner_name' => 'Budi Prakoso',
            'phone' => '081234567890',
            'address' => 'Jl. Ijen No. 10, Malang',
        ]);

        $this->supplierUser = User::create([
            'email' => 'supplier.csp@example.com',
            'password' => Hash::make('password123'),
            'role' => 'supplier',
            'status' => 'active',
        ]);

        $this->supplierProfile = SupplierProfile::create([
            'user_id' => $this->supplierUser->id,
            'company_name' => 'Kelompok Tani Poultry Malang',
            'owner_name' => 'Sutrisno',
            'phone' => '085712345678',
            'warehouse_address' => 'Jl. Peternakan No. 5',
            'max_capacity' => '5 Ton / Minggu',
            'delivery_area' => 'Malang Raya',
        ]);

        $this->partnership = Partnership::create([
            'umkm_id' => $this->umkmProfile->id,
            'supplier_id' => $this->supplierProfile->id,
            'submission_details' => 'Kemitraan Rutin Daging Ayam',
            'status' => 'active',
        ]);
    }

    // UMKM kirim estimasi rencana kebutuhan pasokan mingguan
    public function test_umkm_can_submit_collaborative_supply_plan(): void
    {
        $this->actingAs($this->umkmUser);

        $response = $this->post('/umkm/supply-planning', [
            'supplier_id' => $this->supplierProfile->id,
            'commodity_name' => 'Daging Ayam Broiler Segar',
            'estimated_quantity' => 250,
            'unit' => 'kg',
            'period_month_year' => 'November 2026',
            'notes' => 'Kebutuhan tinggi persiapan akhir tahun',
        ]);

        $response->assertRedirect(route('umkm.katalog.index', ['tab' => 'supply-planning']));

        $this->assertDatabaseHas('supply_plans', [
            'partnership_id' => $this->partnership->id,
            'commodity_name' => 'Daging Ayam Broiler Segar',
            'estimated_quantity' => 250,
            'unit' => 'kg',
            'period_month_year' => 'November 2026',
            'supplier_confirmation_status' => 'MENUNGGU_KONFIRMASI',
        ]);
    }

    // UMKM hapus draft pengajuan supply plan yang masih pending
    public function test_umkm_can_cancel_pending_supply_plan(): void
    {
        $this->actingAs($this->umkmUser);

        $plan = SupplyPlan::create([
            'partnership_id' => $this->partnership->id,
            'commodity_name' => 'Daging Ayam Broiler Segar',
            'estimated_quantity' => 150,
            'unit' => 'kg',
            'period_month_year' => 'November 2026',
            'supplier_confirmation_status' => 'MENUNGGU_KONFIRMASI',
        ]);

        $response = $this->delete("/umkm/supply-planning/{$plan->id}");

        $response->assertRedirect(route('umkm.katalog.index', ['tab' => 'supply-planning']));
        $this->assertDatabaseMissing('supply_plans', ['id' => $plan->id]);
    }

    // Pemasok melihat halaman dashboard agregasi supply plan dari mitra
    public function test_supplier_can_view_collaborative_supply_planning_dashboard(): void
    {
        $this->actingAs($this->supplierUser);

        SupplyPlan::create([
            'partnership_id' => $this->partnership->id,
            'commodity_name' => 'Daging Ayam Broiler Segar',
            'estimated_quantity' => 300,
            'unit' => 'kg',
            'period_month_year' => 'November 2026',
            'supplier_confirmation_status' => 'MENUNGGU_KONFIRMASI',
        ]);

        $response = $this->get('/supplier/supply-planning');

        $response->assertStatus(200);
        $response->assertViewIs('supplier.supply-planning.index');
        $response->assertViewHasAll([
            'totalEstimatedVolume',
            'totalConfirmedVolume',
            'totalUmkmCount',
            'pendingCount',
            'approvedCount',
            'commodityAggregations',
        ]);
    }

    // Pemasok setujui kapasitas pasokan 100%
    public function test_supplier_can_confirm_supply_plan_full(): void
    {
        $this->actingAs($this->supplierUser);

        $plan = SupplyPlan::create([
            'partnership_id' => $this->partnership->id,
            'commodity_name' => 'Daging Ayam Broiler Segar',
            'estimated_quantity' => 200,
            'unit' => 'kg',
            'period_month_year' => 'November 2026',
            'supplier_confirmation_status' => 'MENUNGGU_KONFIRMASI',
        ]);

        $response = $this->patch("/supplier/supply-planning/{$plan->id}/konfirmasi", [
            'action' => 'terima_penuh',
            'supplier_notes' => 'Kapasitas siap dipenuhi 100%',
        ]);

        $response->assertRedirect(route('supplier.supply-planning.index'));

        $plan->refresh();
        $this->assertEquals('DISETUJUI', $plan->supplier_confirmation_status);
        $this->assertEquals(200, (float) $plan->confirmed_quantity);
    }

    // Pemasok sesuaikan kuota karena kapasitas panen terbatas
    public function test_supplier_can_adjust_supply_plan_capacity(): void
    {
        $this->actingAs($this->supplierUser);

        $plan = SupplyPlan::create([
            'partnership_id' => $this->partnership->id,
            'commodity_name' => 'Daging Ayam Broiler Segar',
            'estimated_quantity' => 250,
            'unit' => 'kg',
            'period_month_year' => 'November 2026',
            'supplier_confirmation_status' => 'MENUNGGU_KONFIRMASI',
        ]);

        $response = $this->patch("/supplier/supply-planning/{$plan->id}/konfirmasi", [
            'action' => 'sesuaikan',
            'confirmed_quantity' => 180,
            'supplier_notes' => 'Kapasitas panen terbatas, hanya mampu suplai 180 kg',
        ]);

        $response->assertRedirect(route('supplier.supply-planning.index'));

        $plan->refresh();
        $this->assertEquals('DISESUAIKAN', $plan->supplier_confirmation_status);
        $this->assertEquals(180, (float) $plan->confirmed_quantity);
    }

    // Dropdown perencanaan pasokan hanya memuat mitra pemasok aktif dan komoditas yang disuplai
    public function test_umkm_catalog_supply_planning_dropdown_only_contains_active_partnered_suppliers_and_their_commodities(): void
    {
        $this->actingAs($this->umkmUser);

        // Buat komoditas dan produk untuk supplier yang bermitra ($this->supplierProfile)
        $commAyam = Commodity::create([
            'name' => 'Daging Ayam Broiler Segar',
            'category' => 'Daging & Unggas',
            'unit' => 'kg',
        ]);

        $prodAyam = SupplierProduct::create([
            'supplier_id' => $this->supplierProfile->id,
            'commodity_id' => $commAyam->id,
            'price' => 35000,
            'stock_capacity' => 1000,
            'stock_status' => 'Tersedia',
        ]);

        PartnershipDeliverySchedule::create([
            'partnership_id' => $this->partnership->id,
            'supplier_product_id' => $prodAyam->id,
            'volume_per_delivery' => 50,
            'unit' => 'kg',
            'frequency_type' => '2x Seminggu',
        ]);

        // Buat produk lain milik supplier mitra, tapi TIDAK diajukan dalam jadwal pasokan kontrak
        $commBebek = Commodity::create([
            'name' => 'Bebek Ungkep Frozen',
            'category' => 'Daging & Unggas',
            'unit' => 'ekor',
        ]);

        SupplierProduct::create([
            'supplier_id' => $this->supplierProfile->id,
            'commodity_id' => $commBebek->id,
            'price' => 45000,
            'stock_capacity' => 500,
            'stock_status' => 'Tersedia',
        ]);

        // Buat supplier lain yang BELUM bekerja sama dengan UMKM
        $otherSupplierUser = User::create([
            'email' => 'unpartnered@example.com',
            'password' => Hash::make('password123'),
            'role' => 'supplier',
            'status' => 'active',
        ]);

        $unpartneredSupplier = SupplierProfile::create([
            'user_id' => $otherSupplierUser->id,
            'company_name' => 'Bahan Kue Diva',
            'owner_name' => 'Diva',
            'phone' => '081999888777',
            'warehouse_address' => 'Jl. Kue No. 1',
            'delivery_area' => 'Kota Malang',
        ]);

        $commKue = Commodity::create([
            'name' => 'Tepung Terigu Segitiga',
            'category' => 'Bahan Kue',
            'unit' => 'kg',
        ]);

        SupplierProduct::create([
            'supplier_id' => $unpartneredSupplier->id,
            'commodity_id' => $commKue->id,
            'price' => 15000,
            'stock_capacity' => 500,
            'stock_status' => 'Tersedia',
        ]);

        $response = $this->get(route('umkm.katalog.index', ['tab' => 'supply-planning']));
        $response->assertOk();

        $partnerSuppliers = $response->viewData('partnerSuppliers');
        $this->assertNotNull($partnerSuppliers);

        $supplierIds = $partnerSuppliers->pluck('id')->all();
        // Hanya memuat supplier yang sudah bermitra aktif
        $this->assertContains($this->supplierProfile->id, $supplierIds);
        $this->assertNotContains($unpartneredSupplier->id, $supplierIds);

        // Komoditas untuk supplier mitra hanya komoditas yang disuplai olehnya
        $firstSupplier = $partnerSuppliers->firstWhere('id', $this->supplierProfile->id);
        $commodityNames = collect($firstSupplier['commodities'])->pluck('name')->all();

        // Hanya memuat komoditas yang diajukan dalam jadwal pasokan kontrak aktif
        $this->assertContains('Daging Ayam Broiler Segar', $commodityNames);
        $this->assertNotContains('Bebek Ungkep Frozen', $commodityNames);
        $this->assertNotContains('Tepung Terigu Segitiga', $commodityNames);
    }
}
