<?php

namespace Tests\Feature\Supplier;

use App\Models\Commodity;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReview;
use App\Models\Partnership;
use App\Models\PartnershipDeliverySchedule;
use App\Models\PartnershipEvaluation;
use App\Models\Payment;
use App\Models\ShipmentConfirmation;
use App\Models\SupplierProduct;
use App\Models\SupplierProfile;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ContractMonitoringTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function protectedRoutesProvider(): array
    {
        return [
            'index page' => ['get', '/supplier/contract-monitoring'],
            'store evaluation' => ['post', '/supplier/contract-monitoring/00000000-0000-0000-0000-000000000001/evaluations'],
            'extend contract' => ['patch', '/supplier/contract-monitoring/00000000-0000-0000-0000-000000000001/extend'],
            'toggle auto order' => ['patch', '/supplier/contract-monitoring/00000000-0000-0000-0000-000000000001/toggle-auto-order'],
            'update status' => ['patch', '/supplier/contract-monitoring/00000000-0000-0000-0000-000000000001/status'],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function nonSupplierRolesProvider(): array
    {
        return [
            'umkm user' => ['umkm'],
            'admin user' => ['admin'],
        ];
    }

    /**
     * @return array<string, array{0: array<string, string>, 1: array<int, string>}>
     */
    public static function filterCasesProvider(): array
    {
        return [
            'inactive status shows paused only' => [
                ['status' => 'inactive'],
                ['Bakso Nonaktif Jaya'],
            ],
            'overdue status shows overdue only' => [
                ['status' => 'overdue'],
                ['Resto Menunggak Berat'],
            ],
            'attention status shows overdue or expiring only' => [
                ['status' => 'attention'],
                ['Resto Menunggak Berat'],
            ],
            'active status excludes paused' => [
                ['status' => 'active'],
                ['Resto Menunggak Berat', 'Katering Sehat Prima'],
            ],
            'search narrows results case-insensitively and ignores invalid status' => [
                ['status' => "invalid' OR 1=1--", 'search' => 'sehat prima'],
                ['Katering Sehat Prima'],
            ],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: array<string, mixed>}>
     */
    public static function writeEndpointsProvider(): array
    {
        return [
            'store evaluation' => [
                'post',
                'supplier.contract-monitoring.evaluations.store',
                [
                    'evaluation_period' => 'Juni 2026',
                    'target_volume' => 100,
                    'realized_volume' => 90,
                    'discrepancy_notes' => 'Normal',
                ],
            ],
            'extend contract' => [
                'patch',
                'supplier.contract-monitoring.extend',
                [
                    'end_date' => '2026-12-31',
                    'total_contract_volume' => 500,
                ],
            ],
            'toggle auto order' => [
                'patch',
                'supplier.contract-monitoring.toggle-auto-order',
                [],
            ],
            'update status' => [
                'patch',
                'supplier.contract-monitoring.status.update',
                ['action' => 'pause'],
            ],
        ];
    }

    #[DataProvider('protectedRoutesProvider')]
    public function test_unauthenticated_request_redirects_to_login(string $method, string $uri): void
    {
        $response = $this->{$method}($uri);

        $response->assertRedirect(route('login'));
    }

    #[DataProvider('nonSupplierRolesProvider')]
    public function test_non_supplier_role_is_forbidden_from_contract_monitoring(string $role): void
    {
        $user = User::factory()->create(['role' => $role, 'status' => 'active']);

        $response = $this->actingAs($user)->get(route('supplier.contract-monitoring.index'));

        $response->assertForbidden();
    }

    public function test_supplier_without_profile_redirects_to_supplier_registration(): void
    {
        $user = User::factory()->create(['role' => 'supplier', 'status' => 'active']);

        $response = $this->actingAs($user)->get(route('supplier.contract-monitoring.index'));

        $response->assertRedirect(route('register.supplier'));
        $response->assertSessionHas('error', 'Silakan daftarkan profil Pemasok terlebih dahulu.');
    }

    public function test_supplier_without_partnerships_sees_empty_state(): void
    {
        ['user' => $supplierUser] = $this->createSupplierContext();

        $response = $this->actingAs($supplierUser)->get(route('supplier.contract-monitoring.index'));

        $response->assertSee('Belum Ada Kontrak Kemitraan Berjalan');
    }

    public function test_supplier_sees_contract_metrics_expiring_alert_overdue_alert_and_credit_warning(): void
    {
        $this->travelTo('2026-06-15 09:00:00');
        ['user' => $supplierUser, 'supplier' => $supplier, 'product' => $product] = $this->createSupplierContext();
        ['umkm' => $umkm] = $this->createUmkmContext('Warung Nusantara Jaya');

        $partnership = Partnership::create([
            'umkm_id' => $umkm->id,
            'supplier_id' => $supplier->id,
            'status' => Partnership::STATUS_ACTIVE,
            'term_of_payment_days' => 14,
            'credit_limit' => 10000000,
            'submission_details' => 'Pasokan rutin restoran harian.',
        ]);

        PartnershipDeliverySchedule::create([
            'partnership_id' => $partnership->id,
            'supplier_product_id' => $product->id,
            'volume_per_delivery' => 50,
            'total_contract_volume' => 200,
            'unit' => 'kg',
            'frequency_type' => 'weekly',
            'delivery_days' => ['Senin', 'Kamis'],
            'delivery_time' => '05:30:00',
            'is_auto_order_active' => true,
            'start_date' => '2026-05-01',
            'end_date' => '2026-06-25',
        ]);

        $order = Order::create([
            'partnership_id' => $partnership->id,
            'order_date' => '2026-06-05',
            'delivery_date' => '2026-06-06',
            'total_amount' => 8500000,
            'order_status' => Order::STATUS_COMPLETED,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'supplier_product_id' => $product->id,
            'quantity' => 100,
            'quantity_delivered' => 80,
            'quantity_remaining' => 20,
            'unit_price' => 85000,
            'subtotal' => 8500000,
        ]);

        ShipmentConfirmation::create([
            'order_id' => $order->id,
            'shipment_batch' => 1,
            'fulfillment_status' => 'partial',
            'shipped_at' => '2026-06-06 06:00:00',
            'received_at' => '2026-06-06 07:30:00',
        ]);

        Payment::create([
            'order_id' => $order->id,
            'invoice_number' => 'INV-2026-001',
            'amount' => 8500000,
            'payment_status' => 'pending',
            'transaction_time' => '2026-06-06 08:00:00',
            'due_date' => '2026-06-10',
        ]);

        OrderReview::create([
            'order_id' => $order->id,
            'supplier_id' => $supplier->id,
            'umkm_id' => $umkm->id,
            'rating' => 5,
            'review' => 'Pengiriman pagi sangat tepat waktu.',
        ]);

        $response = $this->actingAs($supplierUser)->get(route('supplier.contract-monitoring.index'));

        $response->assertSee('Warung Nusantara Jaya');
        $response->assertSee($partnership->contract_code);
        $response->assertSee('Berakhir 10 Hari Lagi');
        $response->assertSee('Kontrak Mendekati Berakhir');
        $response->assertSee('Tagihan Jatuh Tempo (Overdue)');
        $response->assertSee('Pemakaian Plafon Kredit Tinggi');
        $response->assertSee('Overdue: 1 Tagihan');
        $response->assertSee('Rp 8.500.000');
        $response->assertSee('40.0%');
        $response->assertSee('100.0%');
        $response->assertSee('★ 5.0');
    }

    public function test_overdue_detection_falls_back_to_term_of_payment_days_when_due_date_is_null(): void
    {
        $this->travelTo('2026-06-15 09:00:00');
        ['user' => $supplierUser, 'supplier' => $supplier] = $this->createSupplierContext();
        ['umkm' => $umkm] = $this->createUmkmContext('Kedai Kopi Maju');

        $partnership = Partnership::create([
            'umkm_id' => $umkm->id,
            'supplier_id' => $supplier->id,
            'status' => Partnership::STATUS_ACTIVE,
            'term_of_payment_days' => 7,
            'credit_limit' => 20000000,
        ]);

        $order = Order::create([
            'partnership_id' => $partnership->id,
            'order_date' => '2026-06-01',
            'delivery_date' => '2026-06-02',
            'total_amount' => 3000000,
            'order_status' => Order::STATUS_COMPLETED,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'amount' => 3000000,
            'payment_status' => 'pending',
            'transaction_time' => '2026-06-01 10:00:00',
            'due_date' => null,
        ]);

        $response = $this->actingAs($supplierUser)->get(route('supplier.contract-monitoring.index'));

        $response->assertSee('Kedai Kopi Maju');
        $response->assertSee('Overdue: 1 Tagihan');
        $response->assertSee('maks. terlambat 7 hari');
    }

    public function test_pending_rejected_and_other_supplier_partnerships_are_excluded_from_monitoring(): void
    {
        ['user' => $supplierUser, 'supplier' => $supplier] = $this->createSupplierContext('PT Supplier Utama');
        ['supplier' => $otherSupplier] = $this->createSupplierContext('PT Supplier Lain');
        ['umkm' => $activeUmkm] = $this->createUmkmContext('UMKM Kontrak Resmi');
        ['umkm' => $pendingUmkm] = $this->createUmkmContext('UMKM Masih Pending');
        ['umkm' => $rejectedUmkm] = $this->createUmkmContext('UMKM Sudah Ditolak');
        ['umkm' => $foreignUmkm] = $this->createUmkmContext('UMKM Milik Tetangga');

        Partnership::create([
            'umkm_id' => $activeUmkm->id,
            'supplier_id' => $supplier->id,
            'status' => Partnership::STATUS_ACTIVE,
        ]);
        Partnership::create([
            'umkm_id' => $pendingUmkm->id,
            'supplier_id' => $supplier->id,
            'status' => 'pending',
        ]);
        Partnership::create([
            'umkm_id' => $rejectedUmkm->id,
            'supplier_id' => $supplier->id,
            'status' => 'rejected',
        ]);
        Partnership::create([
            'umkm_id' => $foreignUmkm->id,
            'supplier_id' => $otherSupplier->id,
            'status' => Partnership::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($supplierUser)->get(route('supplier.contract-monitoring.index'));

        $response->assertSee('UMKM Kontrak Resmi');
        $response->assertDontSee('UMKM Masih Pending');
        $response->assertDontSee('UMKM Sudah Ditolak');
        $response->assertDontSee('UMKM Milik Tetangga');
    }

    /**
     * @param  array<string, string>  $query
     * @param  array<int, string>  $expectedUmkmNames
     */
    #[DataProvider('filterCasesProvider')]
    public function test_status_filters_and_search_filter_contracts_accurately(array $query, array $expectedUmkmNames): void
    {
        $this->travelTo('2026-06-15 09:00:00');
        ['user' => $supplierUser, 'supplier' => $supplier, 'product' => $product] = $this->createSupplierContext();

        ['umkm' => $healthyUmkm] = $this->createUmkmContext('Katering Sehat Prima');
        $healthyPartnership = Partnership::create([
            'umkm_id' => $healthyUmkm->id,
            'supplier_id' => $supplier->id,
            'status' => Partnership::STATUS_ACTIVE,
        ]);
        PartnershipDeliverySchedule::create([
            'partnership_id' => $healthyPartnership->id,
            'supplier_product_id' => $product->id,
            'volume_per_delivery' => 50,
            'total_contract_volume' => 400,
            'unit' => 'kg',
            'frequency_type' => 'weekly',
            'end_date' => '2026-10-01',
        ]);

        $this->travel(1)->minute();
        ['umkm' => $pausedUmkm] = $this->createUmkmContext('Bakso Nonaktif Jaya');
        Partnership::create([
            'umkm_id' => $pausedUmkm->id,
            'supplier_id' => $supplier->id,
            'status' => Partnership::STATUS_PAUSED,
        ]);

        $this->travel(1)->minute();
        ['umkm' => $overdueUmkm] = $this->createUmkmContext('Resto Menunggak Berat');
        $overduePartnership = Partnership::create([
            'umkm_id' => $overdueUmkm->id,
            'supplier_id' => $supplier->id,
            'status' => Partnership::STATUS_ACTIVE,
            'term_of_payment_days' => 7,
        ]);
        $overdueOrder = Order::create([
            'partnership_id' => $overduePartnership->id,
            'order_date' => '2026-05-01',
            'delivery_date' => '2026-05-02',
            'total_amount' => 1500000,
            'order_status' => Order::STATUS_COMPLETED,
        ]);
        Payment::create([
            'order_id' => $overdueOrder->id,
            'amount' => 1500000,
            'payment_status' => 'pending',
            'due_date' => '2026-06-01',
        ]);

        $response = $this->actingAs($supplierUser)
            ->get(route('supplier.contract-monitoring.index', $query));

        $response->assertOk();
        $response->assertViewHas('contracts', function ($contracts) use ($expectedUmkmNames): bool {
            return $contracts->pluck('umkm_name')->all() === $expectedUmkmNames;
        });
    }

    public function test_expired_contract_and_exceeded_credit_limit_display_critical_alerts(): void
    {
        $this->travelTo('2026-06-15 09:00:00');
        ['user' => $supplierUser, 'supplier' => $supplier, 'product' => $product] = $this->createSupplierContext();
        ['umkm' => $umkm] = $this->createUmkmContext('Warung Telat Bayar');

        $partnership = Partnership::create([
            'umkm_id' => $umkm->id,
            'supplier_id' => $supplier->id,
            'status' => Partnership::STATUS_ACTIVE,
            'term_of_payment_days' => 14,
            'credit_limit' => 2000000,
        ]);

        PartnershipDeliverySchedule::create([
            'partnership_id' => $partnership->id,
            'supplier_product_id' => $product->id,
            'volume_per_delivery' => 50,
            'total_contract_volume' => 500,
            'unit' => 'kg',
            'frequency_type' => 'weekly',
            'start_date' => '2026-04-01',
            'end_date' => '2026-06-10',
        ]);

        $order = Order::create([
            'partnership_id' => $partnership->id,
            'order_date' => '2026-06-05',
            'delivery_date' => '2026-06-06',
            'total_amount' => 2500000,
            'order_status' => Order::STATUS_COMPLETED,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'amount' => 2500000,
            'payment_status' => 'pending',
            'due_date' => '2026-06-20',
        ]);

        $response = $this->actingAs($supplierUser)->get(route('supplier.contract-monitoring.index'));

        $response->assertSee('Lewat Masa Berlaku');
        $response->assertSee('Kontrak Lewat Masa Berlaku');
        $response->assertSee('Plafon Kredit Terlampaui');
    }

    #[DataProvider('writeEndpointsProvider')]
    public function test_cross_tenant_write_actions_return_404_not_found(
        string $method,
        string $routeName,
        array $payload
    ): void {
        ['user' => $supplierUser] = $this->createSupplierContext('PT Pemasok A');
        ['supplier' => $otherSupplier] = $this->createSupplierContext('PT Pemasok B');
        ['umkm' => $umkm] = $this->createUmkmContext('Mitra Milik B');

        $foreignPartnership = Partnership::create([
            'umkm_id' => $umkm->id,
            'supplier_id' => $otherSupplier->id,
            'status' => Partnership::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($supplierUser)
            ->{$method}(route($routeName, $foreignPartnership->id), $payload);

        $response->assertNotFound();
    }

    public function test_write_action_against_pending_partnership_returns_404_not_found(): void
    {
        ['user' => $supplierUser, 'supplier' => $supplier] = $this->createSupplierContext();
        ['umkm' => $umkm] = $this->createUmkmContext('Calon Mitra');

        $pendingPartnership = Partnership::create([
            'umkm_id' => $umkm->id,
            'supplier_id' => $supplier->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($supplierUser)->patch(
            route('supplier.contract-monitoring.status.update', $pendingPartnership->id),
            ['action' => 'pause']
        );

        $response->assertNotFound();
        $this->assertSame('pending', $pendingPartnership->fresh()->status);
    }

    public function test_rendered_page_escapes_xss_in_umkm_name_and_evaluation_notes(): void
    {
        ['user' => $supplierUser, 'supplier' => $supplier] = $this->createSupplierContext();
        ['umkm' => $umkm] = $this->createUmkmContext("Warung <script>alert('xss')</script> Jaya");

        $partnership = Partnership::create([
            'umkm_id' => $umkm->id,
            'supplier_id' => $supplier->id,
            'status' => Partnership::STATUS_ACTIVE,
        ]);

        PartnershipEvaluation::create([
            'partnership_id' => $partnership->id,
            'evaluation_period' => 'Mei 2026',
            'target_volume' => 100,
            'realized_volume' => 95,
            'fulfillment_rate_percentage' => 95.00,
            'discrepancy_notes' => '<img src=x onerror=alert(1)>',
        ]);

        $response = $this->actingAs($supplierUser)->get(route('supplier.contract-monitoring.index'));

        $response->assertSee('&lt;script&gt;', false);
        $response->assertDontSee("<script>alert('xss')</script>", false);
        $response->assertDontSee('<img src=x onerror=alert(1)>', false);
    }

    public function test_store_evaluation_rejects_empty_payload_with_indonesian_validation_messages(): void
    {
        ['user' => $supplierUser, 'supplier' => $supplier] = $this->createSupplierContext();
        ['umkm' => $umkm] = $this->createUmkmContext();

        $partnership = Partnership::create([
            'umkm_id' => $umkm->id,
            'supplier_id' => $supplier->id,
            'status' => Partnership::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($supplierUser)
            ->from(route('supplier.contract-monitoring.index'))
            ->post(route('supplier.contract-monitoring.evaluations.store', $partnership->id), []);

        $response->assertRedirect(route('supplier.contract-monitoring.index'));
        $response->assertSessionHasErrors([
            'evaluation_period' => 'Periode evaluasi wajib diisi.',
            'target_volume' => 'Target volume wajib diisi.',
            'realized_volume' => 'Realisasi volume wajib diisi.',
        ]);
        $this->assertDatabaseCount('partnership_evaluations', 0);
    }

    public function test_store_evaluation_rejects_zero_target_volume(): void
    {
        ['user' => $supplierUser, 'supplier' => $supplier] = $this->createSupplierContext();
        ['umkm' => $umkm] = $this->createUmkmContext();

        $partnership = Partnership::create([
            'umkm_id' => $umkm->id,
            'supplier_id' => $supplier->id,
            'status' => Partnership::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($supplierUser)
            ->post(route('supplier.contract-monitoring.evaluations.store', $partnership->id), [
                'evaluation_period' => 'Juni 2026',
                'target_volume' => 0,
                'realized_volume' => 50,
            ]);

        $response->assertSessionHasErrors([
            'target_volume' => 'Target volume harus lebih besar dari 0.',
        ]);
        $this->assertDatabaseCount('partnership_evaluations', 0);
    }

    public function test_store_evaluation_persists_record_and_recalculates_umkm_reliability_score(): void
    {
        ['user' => $supplierUser, 'supplier' => $supplier] = $this->createSupplierContext();
        ['umkm' => $umkm] = $this->createUmkmContext('RM Sederhana');

        $partnership = Partnership::create([
            'umkm_id' => $umkm->id,
            'supplier_id' => $supplier->id,
            'status' => Partnership::STATUS_ACTIVE,
        ]);

        Order::create([
            'partnership_id' => $partnership->id,
            'order_date' => '2026-06-01',
            'delivery_date' => '2026-06-02',
            'total_amount' => 2500000,
            'order_status' => Order::STATUS_COMPLETED,
        ]);

        $response = $this->actingAs($supplierUser)->post(
            route('supplier.contract-monitoring.evaluations.store', $partnership->id),
            [
                'evaluation_period' => 'Juni 2026',
                'target_volume' => 200,
                'realized_volume' => 170,
                'discrepancy_notes' => 'Penyesuaian permintaan libur sekolah.',
            ]
        );

        $response->assertRedirect(route('supplier.contract-monitoring.index'));
        $response->assertSessionHas('success');

        $evaluation = PartnershipEvaluation::firstWhere('partnership_id', $partnership->id);
        $this->assertNotNull($evaluation);
        $this->assertSame('Juni 2026', $evaluation->evaluation_period);
        $this->assertSame('200.00', (string) $evaluation->target_volume);
        $this->assertSame('170.00', (string) $evaluation->realized_volume);
        $this->assertSame('85.00', (string) $evaluation->fulfillment_rate_percentage);
        $this->assertSame('Penyesuaian permintaan libur sekolah.', $evaluation->discrepancy_notes);

        $this->assertDatabaseHas('umkm_reliability_scores', [
            'umkm_id' => $umkm->id,
            'partnership_id' => $partnership->id,
        ]);
    }

    public function test_store_evaluation_caps_fulfillment_rate_at_999_point_99_when_over_quota(): void
    {
        ['user' => $supplierUser, 'supplier' => $supplier] = $this->createSupplierContext();
        ['umkm' => $umkm] = $this->createUmkmContext();

        $partnership = Partnership::create([
            'umkm_id' => $umkm->id,
            'supplier_id' => $supplier->id,
            'status' => Partnership::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($supplierUser)->post(
            route('supplier.contract-monitoring.evaluations.store', $partnership->id),
            [
                'evaluation_period' => 'Lonjakan Event',
                'target_volume' => 10,
                'realized_volume' => 500,
            ]
        );

        $response->assertRedirect(route('supplier.contract-monitoring.index'));
        $evaluation = PartnershipEvaluation::firstWhere('partnership_id', $partnership->id);
        $this->assertNotNull($evaluation);
        $this->assertSame('999.99', (string) $evaluation->fulfillment_rate_percentage);
    }

    public function test_extend_contract_updates_existing_schedule_and_reactivates_paused_contract(): void
    {
        $this->travelTo('2026-06-15 09:00:00');
        ['user' => $supplierUser, 'supplier' => $supplier, 'product' => $product] = $this->createSupplierContext();
        ['umkm' => $umkm] = $this->createUmkmContext();

        $partnership = Partnership::create([
            'umkm_id' => $umkm->id,
            'supplier_id' => $supplier->id,
            'status' => Partnership::STATUS_PAUSED,
        ]);

        $schedule = PartnershipDeliverySchedule::create([
            'partnership_id' => $partnership->id,
            'supplier_product_id' => $product->id,
            'volume_per_delivery' => 25,
            'total_contract_volume' => 200,
            'unit' => 'kg',
            'frequency_type' => 'weekly',
            'start_date' => '2026-03-01',
            'end_date' => '2026-06-01',
            'is_auto_order_active' => false,
        ]);

        $response = $this->actingAs($supplierUser)->patch(
            route('supplier.contract-monitoring.extend', $partnership->id),
            [
                'end_date' => '2026-09-30',
                'total_contract_volume' => 600,
            ]
        );

        $response->assertRedirect(route('supplier.contract-monitoring.index'));
        $response->assertSessionHas('success');

        $freshSchedule = $schedule->fresh();
        $this->assertSame('2026-09-30', $freshSchedule->end_date->toDateString());
        $this->assertSame('600.00', (string) $freshSchedule->total_contract_volume);
        $this->assertSame(Partnership::STATUS_ACTIVE, $partnership->fresh()->status);
    }

    public function test_extend_contract_creates_schedule_when_none_exists(): void
    {
        $this->travelTo('2026-06-15 09:00:00');
        ['user' => $supplierUser, 'supplier' => $supplier] = $this->createSupplierContext();
        ['umkm' => $umkm] = $this->createUmkmContext();

        $partnership = Partnership::create([
            'umkm_id' => $umkm->id,
            'supplier_id' => $supplier->id,
            'status' => Partnership::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($supplierUser)->patch(
            route('supplier.contract-monitoring.extend', $partnership->id),
            [
                'end_date' => '2026-10-15',
                'total_contract_volume' => 800,
            ]
        );

        $response->assertRedirect(route('supplier.contract-monitoring.index'));
        $schedule = PartnershipDeliverySchedule::firstWhere('partnership_id', $partnership->id);
        $this->assertNotNull($schedule);
        $this->assertSame('2026-10-15', $schedule->end_date->toDateString());
        $this->assertSame('800.00', (string) $schedule->total_contract_volume);
        $this->assertTrue($schedule->is_auto_order_active);
    }

    public function test_extend_contract_rejects_past_end_date_with_validation_error(): void
    {
        $this->travelTo('2026-06-15 09:00:00');
        ['user' => $supplierUser, 'supplier' => $supplier, 'product' => $product] = $this->createSupplierContext();
        ['umkm' => $umkm] = $this->createUmkmContext();

        $partnership = Partnership::create([
            'umkm_id' => $umkm->id,
            'supplier_id' => $supplier->id,
            'status' => Partnership::STATUS_ACTIVE,
        ]);

        $schedule = PartnershipDeliverySchedule::create([
            'partnership_id' => $partnership->id,
            'supplier_product_id' => $product->id,
            'volume_per_delivery' => 25,
            'total_contract_volume' => 200,
            'unit' => 'kg',
            'frequency_type' => 'weekly',
            'end_date' => '2026-07-01',
        ]);

        $response = $this->actingAs($supplierUser)->patch(
            route('supplier.contract-monitoring.extend', $partnership->id),
            ['end_date' => '2026-06-14']
        );

        $response->assertSessionHasErrors([
            'end_date' => 'Tanggal berakhir kontrak baru minimal hari ini.',
        ]);
        $this->assertSame('2026-07-01', $schedule->fresh()->end_date->toDateString());
    }

    public function test_extend_contract_rejects_terminated_contract(): void
    {
        $this->travelTo('2026-06-15 09:00:00');
        ['user' => $supplierUser, 'supplier' => $supplier, 'product' => $product] = $this->createSupplierContext();
        ['umkm' => $umkm] = $this->createUmkmContext();

        $partnership = Partnership::create([
            'umkm_id' => $umkm->id,
            'supplier_id' => $supplier->id,
            'status' => Partnership::STATUS_TERMINATED,
        ]);

        $schedule = PartnershipDeliverySchedule::create([
            'partnership_id' => $partnership->id,
            'supplier_product_id' => $product->id,
            'volume_per_delivery' => 25,
            'total_contract_volume' => 200,
            'unit' => 'kg',
            'frequency_type' => 'weekly',
            'end_date' => '2026-06-30',
        ]);

        $response = $this->actingAs($supplierUser)
            ->from(route('supplier.contract-monitoring.index'))
            ->patch(route('supplier.contract-monitoring.extend', $partnership->id), [
                'end_date' => '2026-12-31',
            ]);

        $response->assertRedirect(route('supplier.contract-monitoring.index'));
        $response->assertSessionHas('error', 'Kontrak yang telah dihentikan harus diaktifkan kembali sebelum diperpanjang.');
        $this->assertSame('2026-06-30', $schedule->fresh()->end_date->toDateString());
    }

    public function test_toggle_auto_order_switches_schedule_flag_on_active_contract(): void
    {
        $this->travelTo('2026-06-15 09:00:00');
        ['user' => $supplierUser, 'supplier' => $supplier, 'product' => $product] = $this->createSupplierContext();
        ['umkm' => $umkm] = $this->createUmkmContext();

        $partnership = Partnership::create([
            'umkm_id' => $umkm->id,
            'supplier_id' => $supplier->id,
            'status' => Partnership::STATUS_ACTIVE,
        ]);

        $schedule = PartnershipDeliverySchedule::create([
            'partnership_id' => $partnership->id,
            'supplier_product_id' => $product->id,
            'volume_per_delivery' => 30,
            'total_contract_volume' => 300,
            'unit' => 'kg',
            'frequency_type' => 'weekly',
            'is_auto_order_active' => true,
            'end_date' => '2026-08-01',
        ]);

        $firstToggle = $this->actingAs($supplierUser)->patch(
            route('supplier.contract-monitoring.toggle-auto-order', $partnership->id)
        );

        $firstToggle->assertRedirect(route('supplier.contract-monitoring.index'));
        $firstToggle->assertSessionHas('success');
        $this->assertFalse($schedule->fresh()->is_auto_order_active);

        $secondToggle = $this->actingAs($supplierUser)->patch(
            route('supplier.contract-monitoring.toggle-auto-order', $partnership->id)
        );

        $secondToggle->assertRedirect(route('supplier.contract-monitoring.index'));
        $this->assertTrue($schedule->fresh()->is_auto_order_active);
    }

    public function test_toggle_auto_order_rejects_expired_contract_without_changing_flag(): void
    {
        $this->travelTo('2026-06-15 09:00:00');
        ['user' => $supplierUser, 'supplier' => $supplier, 'product' => $product] = $this->createSupplierContext();
        ['umkm' => $umkm] = $this->createUmkmContext();

        $partnership = Partnership::create([
            'umkm_id' => $umkm->id,
            'supplier_id' => $supplier->id,
            'status' => Partnership::STATUS_ACTIVE,
        ]);

        $schedule = PartnershipDeliverySchedule::create([
            'partnership_id' => $partnership->id,
            'supplier_product_id' => $product->id,
            'volume_per_delivery' => 30,
            'total_contract_volume' => 300,
            'unit' => 'kg',
            'frequency_type' => 'weekly',
            'is_auto_order_active' => true,
            'end_date' => '2026-06-01',
        ]);

        $response = $this->actingAs($supplierUser)
            ->from(route('supplier.contract-monitoring.index'))
            ->patch(route('supplier.contract-monitoring.toggle-auto-order', $partnership->id));

        $response->assertRedirect(route('supplier.contract-monitoring.index'));
        $response->assertSessionHas('error', 'Masa berlaku kontrak telah berakhir. Perpanjang kontrak terlebih dahulu untuk mengatur Auto-Order.');
        $this->assertTrue($schedule->fresh()->is_auto_order_active);
    }

    public function test_toggle_auto_order_rejects_paused_contract_and_contract_without_schedule(): void
    {
        ['user' => $supplierUser, 'supplier' => $supplier, 'product' => $product] = $this->createSupplierContext();
        ['umkm' => $pausedUmkm] = $this->createUmkmContext('UMKM Dijeda');
        ['umkm' => $noScheduleUmkm] = $this->createUmkmContext('UMKM Tanpa Jadwal');

        $pausedPartnership = Partnership::create([
            'umkm_id' => $pausedUmkm->id,
            'supplier_id' => $supplier->id,
            'status' => Partnership::STATUS_PAUSED,
        ]);
        $pausedSchedule = PartnershipDeliverySchedule::create([
            'partnership_id' => $pausedPartnership->id,
            'supplier_product_id' => $product->id,
            'volume_per_delivery' => 20,
            'total_contract_volume' => 200,
            'unit' => 'kg',
            'frequency_type' => 'weekly',
            'is_auto_order_active' => false,
            'end_date' => '2026-12-31',
        ]);

        $noSchedulePartnership = Partnership::create([
            'umkm_id' => $noScheduleUmkm->id,
            'supplier_id' => $supplier->id,
            'status' => Partnership::STATUS_ACTIVE,
        ]);

        $pausedResponse = $this->actingAs($supplierUser)
            ->from(route('supplier.contract-monitoring.index'))
            ->patch(route('supplier.contract-monitoring.toggle-auto-order', $pausedPartnership->id));

        $pausedResponse->assertRedirect(route('supplier.contract-monitoring.index'));
        $pausedResponse->assertSessionHas('error', 'Auto-Order hanya dapat diubah untuk kontrak berstatus aktif.');
        $this->assertFalse($pausedSchedule->fresh()->is_auto_order_active);

        $noScheduleResponse = $this->actingAs($supplierUser)
            ->from(route('supplier.contract-monitoring.index'))
            ->patch(route('supplier.contract-monitoring.toggle-auto-order', $noSchedulePartnership->id));

        $noScheduleResponse->assertRedirect(route('supplier.contract-monitoring.index'));
        $noScheduleResponse->assertSessionHas('error', 'Kontrak ini belum memiliki jadwal pengiriman rutin untuk diatur Auto-Order.');
    }

    public function test_update_status_pauses_terminates_and_reactivates_contract_with_auto_order_side_effects(): void
    {
        ['user' => $supplierUser, 'supplier' => $supplier, 'product' => $product] = $this->createSupplierContext();
        ['umkm' => $umkm] = $this->createUmkmContext();

        $partnership = Partnership::create([
            'umkm_id' => $umkm->id,
            'supplier_id' => $supplier->id,
            'status' => Partnership::STATUS_ACTIVE,
        ]);

        $schedule = PartnershipDeliverySchedule::create([
            'partnership_id' => $partnership->id,
            'supplier_product_id' => $product->id,
            'volume_per_delivery' => 40,
            'total_contract_volume' => 400,
            'unit' => 'kg',
            'frequency_type' => 'weekly',
            'is_auto_order_active' => true,
            'end_date' => '2026-12-01',
        ]);

        $pauseResponse = $this->actingAs($supplierUser)->patch(
            route('supplier.contract-monitoring.status.update', $partnership->id),
            ['action' => 'pause']
        );

        $pauseResponse->assertRedirect(route('supplier.contract-monitoring.index'));
        $this->assertSame(Partnership::STATUS_PAUSED, $partnership->fresh()->status);
        $this->assertFalse($schedule->fresh()->is_auto_order_active);

        $activateResponse = $this->actingAs($supplierUser)->patch(
            route('supplier.contract-monitoring.status.update', $partnership->id),
            ['action' => 'activate']
        );

        $activateResponse->assertRedirect(route('supplier.contract-monitoring.index'));
        $this->assertSame(Partnership::STATUS_ACTIVE, $partnership->fresh()->status);

        $schedule->update(['is_auto_order_active' => true]);

        $terminateResponse = $this->actingAs($supplierUser)->patch(
            route('supplier.contract-monitoring.status.update', $partnership->id),
            ['action' => 'terminate']
        );

        $terminateResponse->assertRedirect(route('supplier.contract-monitoring.index'));
        $this->assertSame(Partnership::STATUS_TERMINATED, $partnership->fresh()->status);
        $this->assertFalse($schedule->fresh()->is_auto_order_active);
    }

    public function test_update_status_rejects_invalid_action_with_validation_message(): void
    {
        ['user' => $supplierUser, 'supplier' => $supplier] = $this->createSupplierContext();
        ['umkm' => $umkm] = $this->createUmkmContext();

        $partnership = Partnership::create([
            'umkm_id' => $umkm->id,
            'supplier_id' => $supplier->id,
            'status' => Partnership::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($supplierUser)->patch(
            route('supplier.contract-monitoring.status.update', $partnership->id),
            ['action' => 'delete_forever']
        );

        $response->assertSessionHasErrors([
            'action' => 'Aksi status kontrak tidak valid.',
        ]);
        $this->assertSame(Partnership::STATUS_ACTIVE, $partnership->fresh()->status);
    }

    /**
     * @return array{user: User, supplier: SupplierProfile, product: SupplierProduct}
     */
    private function createSupplierContext(string $companyName = 'PT Agrikultur Sejahtera'): array
    {
        $user = User::factory()->create(['role' => 'supplier', 'status' => 'active']);
        $supplier = SupplierProfile::create([
            'user_id' => $user->id,
            'company_name' => $companyName,
            'owner_name' => 'Budi Pemasok',
            'phone' => '081200001111',
            'warehouse_address' => 'Gudang Utama Blok A',
        ]);

        $commodity = Commodity::create([
            'name' => 'Beras Pandan Wangi '.uniqid(),
            'category' => 'Bahan Pokok',
            'unit' => 'kg',
        ]);

        $product = SupplierProduct::create([
            'supplier_id' => $supplier->id,
            'commodity_id' => $commodity->id,
            'price' => 15000,
            'stock_capacity' => '1000 kg',
            'stock_status' => 'Tersedia',
        ]);

        return [
            'user' => $user,
            'supplier' => $supplier,
            'product' => $product,
        ];
    }

    /**
     * @return array{user: User, umkm: UmkmProfile}
     */
    private function createUmkmContext(string $businessName = 'Katering Mitra Mandiri'): array
    {
        $user = User::factory()->create(['role' => 'umkm', 'status' => 'active']);
        $umkm = UmkmProfile::create([
            'user_id' => $user->id,
            'business_name' => $businessName,
            'owner_name' => 'Ibu Sari',
            'phone' => '081300002222',
            'address' => 'Jl. Merdeka No. 10',
        ]);

        return [
            'user' => $user,
            'umkm' => $umkm,
        ];
    }
}
