<?php

namespace Tests\Feature;

use App\Models\Commodity;
use App\Models\DemandForecastRun;
use App\Models\SupplierInventoryItem;
use App\Models\SupplierMaterialSource;
use App\Models\SupplierProfile;
use App\Models\SupplierPurchaseSuggestion;
use App\Models\User;
use App\Services\Planning\SupplierDemandPlanningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierDemandPlanningTest extends TestCase
{
    use RefreshDatabase;

    protected User $supplierUser;

    protected SupplierProfile $supplier;

    protected Commodity $commodity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->supplierUser = User::factory()->create([
            'role' => 'supplier',
            'email' => 'supplier.planning@test.com',
        ]);

        $this->supplier = SupplierProfile::create([
            'user_id' => $this->supplierUser->id,
            'company_name' => 'PT Pasokan Segar Nusantara',
            'owner_name' => 'Budi Santoso',
            'phone' => '081234567899',
            'warehouse_address' => 'Kawasan Industri Rungkut Surabaya',
            'delivery_area' => 'Jawa Timur',
        ]);

        $this->commodity = Commodity::create([
            'name' => 'Daging Ayam Broiler',
            'category' => 'Daging & Unggas',
            'unit' => 'kg',
            'government_ref_price' => 35000.00,
        ]);
    }

    public function test_service_generates_forecast_and_inventory_metrics(): void
    {
        $service = app(SupplierDemandPlanningService::class);
        $run = $service->generateForecastAndPlanning($this->supplier, '7_DAYS');

        $this->assertInstanceOf(DemandForecastRun::class, $run);
        $this->assertEquals('7_DAYS', $run->forecast_horizon);
        $this->assertEquals($this->supplier->id, $run->supplier_id);

        // Pastikan item peramalan harian tercipta
        $this->assertDatabaseHas('demand_forecast_items', [
            'forecast_run_id' => $run->id,
            'commodity_id' => $this->commodity->id,
        ]);

        // Pastikan data inventori dan ambang batas terinisialisasi
        $this->assertDatabaseHas('supplier_inventory_items', [
            'supplier_id' => $this->supplier->id,
            'commodity_id' => $this->commodity->id,
        ]);

        $inv = SupplierInventoryItem::where('supplier_id', $this->supplier->id)
            ->where('commodity_id', $this->commodity->id)
            ->first();

        $this->assertNotNull($inv);
        $this->assertGreaterThan(0, $inv->safety_stock);
        $this->assertGreaterThan(0, $inv->reorder_point);
    }

    public function test_what_if_scenario_simulation_calculates_additional_buffer_and_capital(): void
    {
        $service = app(SupplierDemandPlanningService::class);
        $service->generateForecastAndPlanning($this->supplier, '7_DAYS');

        $simulation = $service->simulateWhatIfScenario(
            $this->supplier,
            demandShockPct: 25.0,
            leadTimeDelayDays: 2,
            scenarioName: 'Lonjakan Hari Raya'
        );

        $this->assertEquals('Lonjakan Hari Raya', $simulation['scenario_name']);
        $this->assertEquals(25.0, $simulation['demand_shock_pct']);
        $this->assertEquals(2, $simulation['lead_time_delay_days']);
        $this->assertGreaterThan(0, $simulation['total_additional_buffer']);
        $this->assertGreaterThan(0, $simulation['total_additional_capital']);
        $this->assertNotEmpty($simulation['commodity_breakdowns']);
    }

    public function test_supplier_can_access_supply_planning_dashboard(): void
    {
        $response = $this->actingAs($this->supplierUser)
            ->get(route('supplier.supply-planning.index'));

        $response->assertOk();
        $response->assertViewIs('supplier.supply-planning.index');
        $response->assertViewHas('supplier');
        $response->assertViewHas('latestForecastRun');
        $response->assertViewHas('inventoryItems');
    }

    public function test_supplier_can_recalculate_demand_forecast_endpoint(): void
    {
        $response = $this->actingAs($this->supplierUser)
            ->postJson(route('supplier.demand-planning.recalculate'), [
                'horizon' => '7_DAYS',
            ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
            'message' => 'Proyeksi demand dan ambang batas inventori berhasil dikalkulasi ulang.',
        ]);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'run_id',
                'horizon',
                'total_projected_volume',
                'items',
                'inventory',
            ],
        ]);
    }

    public function test_supplier_can_simulate_what_if_endpoint(): void
    {
        // Jalankan peramalan dasar terlebih dahulu
        app(SupplierDemandPlanningService::class)->generateForecastAndPlanning($this->supplier, '7_DAYS');

        $response = $this->actingAs($this->supplierUser)
            ->postJson(route('supplier.demand-planning.what-if'), [
                'demand_shock_pct' => 20.0,
                'lead_time_delay_days' => 1,
            ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
        ]);
        $response->assertJsonStructure([
            'status',
            'data' => [
                'scenario_name',
                'total_additional_buffer',
                'total_additional_capital',
                'commodity_breakdowns',
            ],
        ]);
    }

    public function test_supplier_can_approve_purchase_suggestion_to_po(): void
    {
        $source = SupplierMaterialSource::create([
            'supplier_id' => $this->supplier->id,
            'commodity_id' => $this->commodity->id,
            'producer_name' => 'Kelompok Tani Ayam Mandiri',
            'contact_phone' => '081234567890',
            'lead_time_days' => 2,
            'minimum_order_qty' => 50.0,
            'unit_cost_price' => 28000.0,
        ]);

        $suggestion = SupplierPurchaseSuggestion::create([
            'supplier_id' => $this->supplier->id,
            'commodity_id' => $this->commodity->id,
            'material_source_id' => $source->id,
            'suggested_order_date' => now()->toDateString(),
            'target_arrival_date' => now()->addDays(2)->toDateString(),
            'suggested_quantity' => 100.0,
            'estimated_cost' => 2800000.0,
            'urgency_level' => SupplierPurchaseSuggestion::URGENCY_CRITICAL,
            'status' => SupplierPurchaseSuggestion::STATUS_PENDING_REVIEW,
            'po_reference_number' => 'PO-PROP-123456',
        ]);

        $response = $this->actingAs($this->supplierUser)
            ->postJson(route('supplier.demand-planning.purchase-suggestion.approve', ['id' => $suggestion->id]));

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
        ]);

        $this->assertEquals(
            SupplierPurchaseSuggestion::STATUS_ORDERED,
            $suggestion->fresh()->status
        );
        $this->assertStringStartsWith('PO/SORAI/', $suggestion->fresh()->po_reference_number);
    }

    public function test_supplier_can_ignore_purchase_suggestion(): void
    {
        $suggestion = SupplierPurchaseSuggestion::create([
            'supplier_id' => $this->supplier->id,
            'commodity_id' => $this->commodity->id,
            'suggested_order_date' => now()->toDateString(),
            'target_arrival_date' => now()->addDays(2)->toDateString(),
            'suggested_quantity' => 80.0,
            'estimated_cost' => 2000000.0,
            'urgency_level' => SupplierPurchaseSuggestion::URGENCY_WARNING,
            'status' => SupplierPurchaseSuggestion::STATUS_PENDING_REVIEW,
        ]);

        $response = $this->actingAs($this->supplierUser)
            ->postJson(route('supplier.demand-planning.purchase-suggestion.ignore', ['id' => $suggestion->id]));

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
            'message' => 'Rekomendasi pengadaan berhasil diabaikan.',
        ]);

        $this->assertEquals(
            SupplierPurchaseSuggestion::STATUS_IGNORED,
            $suggestion->fresh()->status
        );
    }

    public function test_supplier_can_update_inventory_stock(): void
    {
        $inventory = SupplierInventoryItem::create([
            'supplier_id' => $this->supplier->id,
            'commodity_id' => $this->commodity->id,
            'current_stock' => 50.0,
            'safety_stock' => 30.0,
            'reorder_point' => 70.0,
            'unit' => 'kg',
        ]);

        $response = $this->actingAs($this->supplierUser)
            ->patchJson(route('supplier.demand-planning.inventory.update', ['id' => $inventory->id]), [
                'current_stock' => 120.5,
            ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
        ]);

        $this->assertEquals(120.5, (float) $inventory->fresh()->current_stock);
        $this->assertNotNull($inventory->fresh()->last_restocked_at);
    }

    public function test_update_inventory_stock_rejects_negative_value(): void
    {
        $inventory = SupplierInventoryItem::create([
            'supplier_id' => $this->supplier->id,
            'commodity_id' => $this->commodity->id,
            'current_stock' => 50.0,
            'safety_stock' => 30.0,
            'reorder_point' => 70.0,
            'unit' => 'kg',
        ]);

        $response = $this->actingAs($this->supplierUser)
            ->patchJson(route('supplier.demand-planning.inventory.update', ['id' => $inventory->id]), [
                'current_stock' => -10,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['current_stock']);
    }

    public function test_artisan_calculate_supplier_demand_planning_command(): void
    {
        $this->artisan('supplier-demand:calculate')
            ->assertSuccessful();

        $this->assertDatabaseHas('demand_forecast_runs', [
            'supplier_id' => $this->supplier->id,
        ]);
    }
}
