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

    private function createUmkmUser(): User
    {
        $user = User::create([
            'email' => 'umkm_collab_'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'active',
        ]);

        UmkmProfile::create([
            'user_id' => $user->id,
            'business_name' => 'Dapur Kuliner Uji',
            'owner_name' => 'Budi Santoso',
            'phone' => '081234567890',
            'address' => 'Jl. Pengujian No. 10',
        ]);

        return $user;
    }

    private function createSupplierUser(): User
    {
        $user = User::create([
            'email' => 'supplier_collab_'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
            'role' => 'supplier',
            'status' => 'active',
        ]);

        SupplierProfile::create([
            'user_id' => $user->id,
            'company_name' => 'CV Sumber Pasokan Pangan',
            'owner_name' => 'Pak Tani Jaya',
            'phone' => '089988776655',
            'warehouse_address' => 'Jl. Pergudangan No. 5',
            'max_capacity' => '5 Ton / Minggu',
            'delivery_area' => 'Malang Raya',
            'nib_number' => '9120004561234',
        ]);

        return $user;
    }

    public function test_umkm_can_submit_collaborative_supply_plan(): void
    {
        $umkmUser = $this->createUmkmUser();
        $supplierUser = $this->createSupplierUser();
        $supplierProfile = $supplierUser->supplierProfile;

        $targetPeriod = now()->addMonth()->format('Y-m');

        $response = $this->actingAs($umkmUser)->post('/umkm/supply-planning', [
            'supplier_id' => $supplierProfile->id,
            'commodity_name' => 'Daging Ayam Broiler Segar',
            'estimated_quantity' => 250,
            'unit' => 'kg',
            'period_month_year' => $targetPeriod,
            'notes' => 'Kebutuhan fillet dada dingin, jadwal pasokan subuh.',
        ]);

        $response->assertRedirect(route('umkm.katalog.index', ['tab' => 'supply-planning']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('supply_plans', [
            'commodity_name' => 'Daging Ayam Broiler Segar',
            'period_month_year' => $targetPeriod,
            'estimated_quantity' => 250,
            'unit' => 'kg',
            'supplier_confirmation_status' => 'MENUNGGU_KONFIRMASI',
        ]);
    }

    public function test_umkm_can_cancel_pending_supply_plan(): void
    {
        $umkmUser = $this->createUmkmUser();
        $supplierUser = $this->createSupplierUser();

        $partnership = Partnership::create([
            'umkm_id' => $umkmUser->umkmProfile->id,
            'supplier_id' => $supplierUser->supplierProfile->id,
            'status' => 'active',
        ]);

        $plan = SupplyPlan::create([
            'partnership_id' => $partnership->id,
            'commodity_name' => 'Bawang Merah Probolinggo',
            'period_month_year' => '2026-11',
            'estimated_quantity' => 100,
            'unit' => 'kg',
            'supplier_confirmation_status' => 'MENUNGGU_KONFIRMASI',
        ]);

        $response = $this->actingAs($umkmUser)->delete("/umkm/supply-planning/{$plan->id}");

        $response->assertRedirect(route('umkm.katalog.index', ['tab' => 'supply-planning']));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('supply_plans', [
            'id' => $plan->id,
        ]);
    }

    public function test_supplier_can_view_collaborative_supply_planning_dashboard(): void
    {
        $supplierUser = $this->createSupplierUser();

        $response = $this->actingAs($supplierUser)->get('/supplier/supply-planning');

        $response->assertStatus(200);
        $response->assertSee('Collaborative Supply Planning');
        $response->assertSee('Total Estimasi Masuk');
        $response->assertSee('Kapasitas Disetujui');
    }

    public function test_supplier_can_confirm_supply_plan_full(): void
    {
        $umkmUser = $this->createUmkmUser();
        $supplierUser = $this->createSupplierUser();

        $partnership = Partnership::create([
            'umkm_id' => $umkmUser->umkmProfile->id,
            'supplier_id' => $supplierUser->supplierProfile->id,
            'status' => 'active',
        ]);

        $plan = SupplyPlan::create([
            'partnership_id' => $partnership->id,
            'commodity_name' => 'Daging Ayam Broiler Segar',
            'period_month_year' => '2026-11',
            'estimated_quantity' => 300,
            'unit' => 'kg',
            'supplier_confirmation_status' => 'MENUNGGU_KONFIRMASI',
        ]);

        $response = $this->actingAs($supplierUser)->patch("/supplier/supply-planning/{$plan->id}/konfirmasi", [
            'action' => 'terima_penuh',
            'supplier_notes' => 'Kapasitas kandang siap 100% untuk pasokan November.',
        ]);

        $response->assertRedirect(route('supplier.supply-planning.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('supply_plans', [
            'id' => $plan->id,
            'supplier_confirmation_status' => 'DISETUJUI',
            'confirmed_quantity' => 300,
        ]);
    }

    public function test_supplier_can_adjust_supply_plan_capacity(): void
    {
        $umkmUser = $this->createUmkmUser();
        $supplierUser = $this->createSupplierUser();

        $partnership = Partnership::create([
            'umkm_id' => $umkmUser->umkmProfile->id,
            'supplier_id' => $supplierUser->supplierProfile->id,
            'status' => 'active',
        ]);

        $plan = SupplyPlan::create([
            'partnership_id' => $partnership->id,
            'commodity_name' => 'Cabai Rawit Merah',
            'period_month_year' => '2026-12',
            'estimated_quantity' => 200,
            'unit' => 'kg',
            'supplier_confirmation_status' => 'MENUNGGU_KONFIRMASI',
        ]);

        $response = $this->actingAs($supplierUser)->patch("/supplier/supply-planning/{$plan->id}/konfirmasi", [
            'action' => 'sesuaikan',
            'confirmed_quantity' => 150,
            'supplier_notes' => 'Hanya sanggup 150 kg karena musim hujan.',
        ]);

        $response->assertRedirect(route('supplier.supply-planning.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('supply_plans', [
            'id' => $plan->id,
            'supplier_confirmation_status' => 'DISESUAIKAN',
            'confirmed_quantity' => 150,
        ]);
    }
}
