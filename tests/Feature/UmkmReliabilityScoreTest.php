<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Partnership;
use App\Models\Payment;
use App\Models\SupplierProfile;
use App\Models\UmkmProfile;
use App\Models\UmkmReliabilityScore;
use App\Models\User;
use App\Services\Reliability\UmkmReliabilityScoreService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UmkmReliabilityScoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_umkm_without_enough_orders_receives_unrated_grade(): void
    {
        $user = User::factory()->create(['role' => 'umkm', 'status' => 'active']);
        $umkm = UmkmProfile::create([
            'user_id' => $user->id,
            'business_name' => 'Katering Ibu Sejahtera',
            'owner_name' => 'Ibu Siti',
            'phone' => '081234567891',
            'address' => 'Jl. Merbabu No. 10',
        ]);

        $service = app(UmkmReliabilityScoreService::class);
        $score = $service->calculate($umkm);

        $this->assertEquals(UmkmReliabilityScore::GRADE_UNRATED, $score->grade);
        $this->assertTrue($score->metrics_breakdown['is_cold_start']);
        $this->assertEquals(0, $score->getRecommendedTopDays());
        $this->assertDatabaseHas('umkm_reliability_scores', [
            'umkm_id' => $umkm->id,
            'grade' => UmkmReliabilityScore::GRADE_UNRATED,
        ]);
    }

    public function test_umkm_with_on_time_payments_receives_high_grade_and_top_recommendation(): void
    {
        $supplierUser = User::factory()->create(['role' => 'supplier', 'status' => 'active']);
        $supplier = SupplierProfile::create([
            'user_id' => $supplierUser->id,
            'company_name' => 'PT Agrikultur Nusantara',
            'owner_name' => 'Bambang Sudiro',
            'phone' => '081299887766',
            'warehouse_address' => 'Gudang Sentral No. 12',
        ]);

        $umkmUser = User::factory()->create(['role' => 'umkm', 'status' => 'active']);
        $umkm = UmkmProfile::create([
            'user_id' => $umkmUser->id,
            'business_name' => 'Restoran Rasa Jaya',
            'owner_name' => 'Budi Santoso',
            'phone' => '081234567892',
            'address' => 'Jl. Semeru No. 5',
        ]);

        $partnership = Partnership::create([
            'umkm_id' => $umkm->id,
            'supplier_id' => $supplier->id,
            'status' => 'active',
        ]);

        // Buat 5 pesanan dengan pembayaran lunas tepat waktu
        for ($i = 0; $i < 5; $i++) {
            $order = Order::create([
                'partnership_id' => $partnership->id,
                'order_date' => now()->subMonths(3)->addDays($i * 10),
                'delivery_date' => now()->subMonths(3)->addDays($i * 10 + 2),
                'total_amount' => 15000000,
                'order_status' => Order::STATUS_COMPLETED,
            ]);

            Payment::create([
                'order_id' => $order->id,
                'amount' => 15000000,
                'payment_status' => 'PAID',
                'transaction_time' => now()->subMonths(3)->addDays($i * 10),
                'due_date' => now()->subMonths(2)->addDays($i * 10),
                'paid_at' => now()->subMonths(2)->addDays($i * 10 - 2), // 2 hari sebelum jatuh tempo
            ]);
        }

        $service = app(UmkmReliabilityScoreService::class);
        $score = $service->calculate($umkm, $partnership);

        $this->assertGreaterThanOrEqual(70.0, (float) $score->final_score);
        $this->assertContains($score->grade, [UmkmReliabilityScore::GRADE_A, UmkmReliabilityScore::GRADE_B]);
        $this->assertTrue($score->isEligibleForTop());
        $this->assertGreaterThanOrEqual(14, $score->getRecommendedTopDays());
    }

    public function test_hard_cap_penalty_applies_when_severe_payment_default_exists(): void
    {
        $supplierUser = User::factory()->create(['role' => 'supplier', 'status' => 'active']);
        $supplier = SupplierProfile::create([
            'user_id' => $supplierUser->id,
            'company_name' => 'CV Sumber Makmur',
            'owner_name' => 'Bambang Sudiro',
            'phone' => '081299887711',
            'warehouse_address' => 'Gudang Sentral No. 12',
        ]);

        $umkmUser = User::factory()->create(['role' => 'umkm', 'status' => 'active']);
        $umkm = UmkmProfile::create([
            'user_id' => $umkmUser->id,
            'business_name' => 'Warung Nasi Berkah',
            'owner_name' => 'Pak Joko',
            'phone' => '081234567893',
            'address' => 'Jl. Bromo No. 12',
        ]);

        $partnership = Partnership::create([
            'umkm_id' => $umkm->id,
            'supplier_id' => $supplier->id,
            'status' => 'active',
        ]);

        // Buat 3 pesanan
        for ($i = 0; $i < 3; $i++) {
            Order::create([
                'partnership_id' => $partnership->id,
                'order_date' => now()->subDays(90 - ($i * 10)),
                'delivery_date' => now()->subDays(90 - ($i * 10) - 2),
                'total_amount' => 5000000,
                'order_status' => Order::STATUS_COMPLETED,
            ]);
        }

        $orders = Order::where('partnership_id', $partnership->id)->get();

        // 1 tagihan menunggak 60 hari belum lunas (severe default)
        Payment::create([
            'order_id' => $orders->first()->id,
            'amount' => 5000000,
            'payment_status' => 'pending',
            'transaction_time' => now()->subDays(90),
            'due_date' => Carbon::now()->subDays(60),
            'paid_at' => null,
        ]);

        $service = app(UmkmReliabilityScoreService::class);
        $score = $service->calculate($umkm, $partnership);

        // Nilai harus terkunci maksimal 49.0
        $this->assertLessThanOrEqual(49.0, (float) $score->final_score);
        $this->assertContains($score->grade, [UmkmReliabilityScore::GRADE_D, UmkmReliabilityScore::GRADE_F]);
        $this->assertEquals(0, $score->getRecommendedTopDays());
    }

    public function test_supplier_can_fetch_privacy_safe_reliability_score_json(): void
    {
        $supplierUser = User::factory()->create(['role' => 'supplier', 'status' => 'active']);
        $supplier = SupplierProfile::create([
            'user_id' => $supplierUser->id,
            'company_name' => 'PT Pangan Mandiri',
            'owner_name' => 'Bambang Sudiro',
            'phone' => '081299887722',
            'warehouse_address' => 'Gudang Sentral No. 12',
        ]);

        $umkmUser = User::factory()->create(['role' => 'umkm', 'status' => 'active']);
        $umkm = UmkmProfile::create([
            'user_id' => $umkmUser->id,
            'business_name' => 'Bakso Sedap Malam',
            'owner_name' => 'Mas Hendra',
            'phone' => '081234567894',
            'address' => 'Jl. Kawi No. 2',
        ]);

        $response = $this->actingAs($supplierUser)
            ->getJson(route('supplier.umkm.reliability-score', $umkm->id));

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'data' => [
                'umkm_id',
                'business_name',
                'final_score',
                'grade',
                'scores' => [
                    'payment_discipline',
                    'order_consistency',
                    'tenure_history',
                ],
                'recommendations',
                'privacy_notice',
            ],
        ]);
        $response->assertJsonPath('data.business_name', 'Bakso Sedap Malam');
    }

    public function test_supplier_can_update_credit_terms_for_partnership(): void
    {
        $supplierUser = User::factory()->create(['role' => 'supplier', 'status' => 'active']);
        $supplier = SupplierProfile::create([
            'user_id' => $supplierUser->id,
            'company_name' => 'PT Pangan Mandiri Berkah',
            'owner_name' => 'Bambang Sudiro',
            'phone' => '081299887733',
            'warehouse_address' => 'Gudang Sentral No. 12',
        ]);

        $umkmUser = User::factory()->create(['role' => 'umkm', 'status' => 'active']);
        $umkm = UmkmProfile::create([
            'user_id' => $umkmUser->id,
            'business_name' => 'Dapur Bunda',
            'owner_name' => 'Bunda Ani',
            'phone' => '081234567895',
            'address' => 'Jl. Ijen No. 10',
        ]);

        $partnership = Partnership::create([
            'umkm_id' => $umkm->id,
            'supplier_id' => $supplier->id,
            'status' => 'active',
            'term_of_payment_days' => 0,
            'credit_limit' => 0.00,
        ]);

        $response = $this->actingAs($supplierUser)
            ->patch(route('supplier.partnerships.update-credit-terms', $partnership->id), [
                'term_of_payment_days' => 30,
                'credit_limit' => 50000000,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('partnerships', [
            'id' => $partnership->id,
            'term_of_payment_days' => 30,
            'credit_limit' => 50000000.00,
        ]);
    }

    public function test_supplier_can_render_pengajuan_masuk_with_reliability_score(): void
    {
        $supplierUser = User::factory()->create(['role' => 'supplier', 'status' => 'active']);
        $supplier = SupplierProfile::create([
            'user_id' => $supplierUser->id,
            'company_name' => 'PT Pangan Mandiri Makmur',
            'owner_name' => 'Bambang Sudiro',
            'phone' => '081299887744',
            'warehouse_address' => 'Gudang Sentral No. 12',
        ]);

        $umkmUser = User::factory()->create(['role' => 'umkm', 'status' => 'active']);
        $umkm = UmkmProfile::create([
            'user_id' => $umkmUser->id,
            'business_name' => 'Kantin Kampus',
            'owner_name' => 'Bu Darmi',
            'phone' => '081234567896',
            'address' => 'Jl. Veteran No. 1',
        ]);

        $partnership = Partnership::create([
            'umkm_id' => $umkm->id,
            'supplier_id' => $supplier->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($supplierUser)->get(route('supplier.pengajuan-masuk.index'));

        $response->assertOk();
        $response->assertSee('KANTIN KAMPUS');
        $response->assertSee('Skor Keandalan Mitra');
        $response->assertSee('GRADE');
    }
}
