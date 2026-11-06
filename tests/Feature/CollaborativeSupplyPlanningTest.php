<?php

namespace Tests\Feature;

use App\Models\Partnership;
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
}
