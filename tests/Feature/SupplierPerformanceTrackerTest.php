<?php

namespace Tests\Feature;

use App\Models\Commodity;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReview;
use App\Models\Partnership;
use App\Models\ShipmentConfirmation;
use App\Models\SupplierPerformanceScore;
use App\Models\SupplierProduct;
use App\Models\SupplierProfile;
use App\Models\UmkmProfile;
use App\Models\User;
use App\Services\Performance\SupplierPerformanceTrackerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierPerformanceTrackerTest extends TestCase
{
    use RefreshDatabase;

    protected SupplierProfile $supplier;

    protected UmkmProfile $umkm;

    protected Partnership $partnership;

    protected User $umkmUser;

    protected SupplierProduct $product;

    protected function setUp(): void
    {
        parent::setUp();

        $supplierUser = User::factory()->create(['role' => 'supplier', 'status' => 'active']);
        $this->supplier = SupplierProfile::create([
            'user_id' => $supplierUser->id,
            'company_name' => 'CV Sumber Unggas Segar',
            'owner_name' => 'Hendra Setiawan',
            'phone' => '081234567890',
            'warehouse_address' => 'Kawasan Industri Cikarang Blok A4',
            'delivery_area' => 'Jabodetabek & Bandung',
        ]);

        $commodity = Commodity::create([
            'name' => 'Daging Ayam Broiler',
            'category' => 'Daging & Unggas',
            'unit' => 'kg',
        ]);

        $this->product = SupplierProduct::create([
            'supplier_id' => $this->supplier->id,
            'commodity_id' => $commodity->id,
            'price' => 30000,
            'stock_capacity' => '500 kg',
            'stock_status' => 'Tersedia',
        ]);

        $this->umkmUser = User::factory()->create(['role' => 'umkm', 'status' => 'active']);
        $this->umkm = UmkmProfile::create([
            'user_id' => $this->umkmUser->id,
            'business_name' => 'Resto Ayam Kremes Makmur',
            'owner_name' => 'Ibu Ratna',
            'phone' => '081298765432',
            'address' => 'Jl. Margonda Raya No. 12, Depok',
        ]);

        $this->partnership = Partnership::create([
            'umkm_id' => $this->umkm->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'active',
        ]);
    }

    public function test_it_assigns_unrated_grade_to_supplier_with_fewer_than_three_deliveries(): void
    {
        $service = app(SupplierPerformanceTrackerService::class);
        $score = $service->calculate($this->supplier);

        $this->assertInstanceOf(SupplierPerformanceScore::class, $score);
        $this->assertEquals(SupplierPerformanceScore::GRADE_UNRATED, $score->grade);
        $this->assertTrue($score->metrics_breakdown['is_cold_start']);
        $this->assertEquals(0, $score->metrics_breakdown['total_shipments']);
    }

    public function test_it_calculates_grade_a_for_on_time_and_full_deliveries(): void
    {
        // Buat 4 pengiriman selesai, semua tepat waktu dan pemenuhan 100%
        for ($i = 1; $i <= 4; $i++) {
            $deliveryDate = Carbon::today()->subDays($i);

            $order = Order::create([
                'partnership_id' => $this->partnership->id,
                'order_date' => $deliveryDate->toDateString(),
                'order_status' => Order::STATUS_COMPLETED,
                'total_amount' => 1500000,
                'delivery_date' => $deliveryDate->toDateString(),
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'supplier_product_id' => $this->product->id,
                'quantity' => 50,
                'quantity_delivered' => 50,
                'unit_price' => 30000,
                'subtotal' => 1500000,
            ]);

            ShipmentConfirmation::create([
                'order_id' => $order->id,
                'shipment_batch' => 1,
                'fulfillment_status' => 'fulfilled',
                'shipped_at' => $deliveryDate->copy()->setTime(6, 0, 0),
                'received_at' => $deliveryDate->copy()->setTime(7, 30, 0), // Sebelum 08:30 (On-Time)
            ]);

            OrderReview::create([
                'order_id' => $order->id,
                'supplier_id' => $this->supplier->id,
                'umkm_id' => $this->umkm->id,
                'rating' => 5,
                'review' => 'Kualitas karkas segar sempurna dan tiba tepat waktu.',
            ]);
        }

        $service = app(SupplierPerformanceTrackerService::class);
        $score = $service->calculate($this->supplier);

        $this->assertEquals(SupplierPerformanceScore::GRADE_A, $score->grade);
        $this->assertGreaterThanOrEqual(85.0, (float) $score->final_score);
        $this->assertGreaterThanOrEqual(4.5, (float) $score->star_rating);
        $this->assertTrue($score->isRecommendedForRenewal());
        $this->assertEquals(100.0, $score->metrics_breakdown['delivery']['otd_rate']);
        $this->assertEquals(100.0, $score->metrics_breakdown['fulfillment']['fill_rate']);
    }

    public function test_it_penalizes_delays_and_backorders(): void
    {
        // Buat 4 pengiriman yang mengalami keterlambatan parah (> 6 jam) dan terkena backorder
        for ($i = 1; $i <= 4; $i++) {
            $deliveryDate = Carbon::today()->subDays($i);

            $order = Order::create([
                'partnership_id' => $this->partnership->id,
                'order_date' => $deliveryDate->toDateString(),
                'order_status' => Order::STATUS_BACKORDER_PENDING,
                'total_amount' => 1000000,
                'delivery_date' => $deliveryDate->toDateString(),
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'supplier_product_id' => $this->product->id,
                'quantity' => 100,
                'quantity_delivered' => 50, // Hanya terkirim 50%
                'unit_price' => 10000,
                'subtotal' => 1000000,
            ]);

            ShipmentConfirmation::create([
                'order_id' => $order->id,
                'shipment_batch' => 1,
                'fulfillment_status' => 'partial',
                'shipped_at' => $deliveryDate->copy()->setTime(14, 0, 0),
                'received_at' => $deliveryDate->copy()->setTime(16, 0, 0), // Telat 8 jam dari target 08:00
                'backorder_action' => 'create_backorder',
            ]);
        }

        $service = app(SupplierPerformanceTrackerService::class);
        $score = $service->calculate($this->supplier);

        $this->assertContains($score->grade, [SupplierPerformanceScore::GRADE_C, SupplierPerformanceScore::GRADE_D]);
        $this->assertLessThan(75.0, (float) $score->final_score);
        $this->assertEquals(0.0, $score->metrics_breakdown['delivery']['otd_rate']);
        $this->assertGreaterThanOrEqual(1, $score->metrics_breakdown['delivery']['severe_delays_count']);
        $this->assertStringContainsString('Buffer Stock', $score->recommendations['buffer_stock_advice']);
    }

    public function test_umkm_can_view_supplier_performance_endpoint(): void
    {
        $response = $this->actingAs($this->umkmUser)
            ->getJson(route('umkm.suppliers.performance', ['supplierId' => $this->supplier->id]));

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'data' => [
                'supplier_id',
                'company_name',
                'final_score',
                'star_rating',
                'grade',
                'scores' => ['on_time_delivery', 'quota_fulfillment', 'quality_rating'],
                'metrics_summary',
                'recommendations' => ['contract_advice', 'buffer_stock_advice', 'negotiation_tip'],
            ],
        ]);
        $response->assertJsonPath('data.company_name', 'CV Sumber Unggas Segar');
    }

    public function test_umkm_can_recalculate_supplier_performance_endpoint(): void
    {
        $response = $this->actingAs($this->umkmUser)
            ->postJson(route('umkm.suppliers.recalculate-performance', ['supplierId' => $this->supplier->id]));

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
            'message' => 'Skor performa pemasok berhasil dikalkulasi ulang.',
        ]);
    }

    public function test_artisan_command_calculates_all_supplier_scores(): void
    {
        $this->artisan('supplier-performance:calculate')
            ->assertSuccessful();

        $this->assertDatabaseHas('supplier_performance_scores', [
            'supplier_id' => $this->supplier->id,
        ]);
    }
}
