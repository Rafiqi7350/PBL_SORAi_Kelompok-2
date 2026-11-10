<?php

namespace App\Services\Contract;

use App\Models\Order;
use App\Models\Partnership;
use App\Models\PartnershipDeliverySchedule;
use App\Models\Payment;
use App\Models\SupplierProfile;
use App\Services\Reliability\UmkmReliabilityScoreService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ContractMonitoringService
{
    public function __construct(
        protected UmkmReliabilityScoreService $reliabilityService
    ) {}

    /**
     * Susun seluruh data pemantauan kontrak kemitraan untuk pemasok aktif.
     *
     * @return array{
     *     contracts: Collection<int, array<string, mixed>>,
     *     all_contracts_count: int,
     *     kpis: array<string, float|int>,
     *     counts: array<string, int>,
     *     alerts: array<int, array<string, mixed>>
     * }
     */
    public function buildMonitoringData(
        SupplierProfile $supplier,
        string $search = '',
        string $statusFilter = 'all'
    ): array {
        $partnerships = Partnership::with([
            'umkm.latestReliabilityScore',
            'latestSupplierPerformanceScore',
            'deliverySchedules.supplierProduct.commodity',
            'evaluations',
            'orders.items.supplierProduct.commodity',
            'orders.shipmentConfirmations',
            'orders.payments',
            'orders.review',
        ])
            ->where('supplier_id', $supplier->id)
            ->whereRaw("LOWER(COALESCE(status, '')) NOT IN ('pending', 'menunggu', 'dalam_review', 'rejected', 'ditolak')")
            ->latest()
            ->get();

        $allContracts = $partnerships->map(function (Partnership $partnership): array {
            return $this->mapPartnershipToContractItem($partnership);
        });

        $kpis = $this->computeKpis($allContracts);
        $counts = $this->computeCounts($allContracts);
        $alerts = $this->buildAlerts($allContracts);

        $filteredContracts = $this->filterContracts($allContracts, $search, $statusFilter);

        return [
            'contracts' => $filteredContracts->values(),
            'all_contracts_count' => $allContracts->count(),
            'kpis' => $kpis,
            'counts' => $counts,
            'alerts' => $alerts,
        ];
    }

    /**
     * Hitung ulang metrik satu kontrak menjadi struktur data pemantauan lengkap.
     *
     * @return array<string, mixed>
     */
    protected function mapPartnershipToContractItem(Partnership $partnership): array
    {
        $today = Carbon::today();
        $schedule = $partnership->deliverySchedules->sortByDesc('created_at')->first();
        $umkm = $partnership->umkm;

        $reliabilityScore = $umkm?->latestReliabilityScore;
        if ($umkm && ! $reliabilityScore) {
            $reliabilityScore = $this->reliabilityService->calculate($umkm, $partnership);
            $umkm->setRelation('latestReliabilityScore', $reliabilityScore);
        }

        $firstOrderItem = $partnership->orders
            ->flatMap(fn (Order $order) => $order->items)
            ->first();

        $commodity = $schedule?->supplierProduct?->commodity ?? $firstOrderItem?->supplierProduct?->commodity;
        $commodityName = $commodity?->name ?? 'Pasokan Komoditas Kemitraan';
        $unit = $schedule?->unit ?? $commodity?->unit ?? 'kg';
        $unitPrice = (float) ($schedule?->supplierProduct?->price ?? $firstOrderItem?->unit_price ?? 0);

        $startDate = $schedule?->start_date ? Carbon::parse($schedule->start_date)->startOfDay() : null;
        $endDate = $schedule?->end_date ? Carbon::parse($schedule->end_date)->startOfDay() : null;
        $daysRemaining = $endDate ? (int) $today->diffInDays($endDate, false) : null;

        $lifecycleStatus = $this->resolveLifecycleStatus($partnership, $daysRemaining);
        $isExpired = $lifecycleStatus === 'expired';
        $isExpiringSoon = $lifecycleStatus === 'active' && $daysRemaining !== null && $daysRemaining >= 0 && $daysRemaining <= 30;

        $nonCancelledOrders = $partnership->orders->filter(function (Order $order): bool {
            return strtolower((string) $order->order_status) !== Order::STATUS_CANCELLED;
        });

        $orderedVolume = (float) $nonCancelledOrders->sum(fn (Order $order) => $order->items->sum('quantity'));
        $deliveredVolume = (float) $nonCancelledOrders->sum(fn (Order $order) => $order->items->sum('quantity_delivered'));
        $remainingBackorderVolume = (float) $nonCancelledOrders->sum(fn (Order $order) => $order->items->sum('quantity_remaining'));

        $volumePerDelivery = (float) ($schedule?->volume_per_delivery ?? 0);
        $scheduleTargetVolume = (float) ($schedule?->total_contract_volume ?? 0);
        $effectiveTargetVolume = $scheduleTargetVolume > 0 ? $scheduleTargetVolume : $orderedVolume;

        $volumeFulfillmentRate = $effectiveTargetVolume > 0
            ? min(100.0, round(($deliveredVolume / $effectiveTargetVolume) * 100, 1))
            : 0.0;

        $totalOrders = $partnership->orders->count();
        $completedOrders = $partnership->orders->filter(function (Order $order): bool {
            return in_array(strtolower((string) $order->order_status), [
                Order::STATUS_COMPLETED,
                Order::STATUS_COMPLETED_PARTIAL,
                'barang_diterima',
            ], true);
        })->count();

        $issueOrders = $partnership->orders->filter(function (Order $order): bool {
            return in_array(strtolower((string) $order->order_status), [
                Order::STATUS_WAITING_BACKORDER_APPROVAL,
                Order::STATUS_BACKORDER_PENDING,
                Order::STATUS_PARTIALLY_SHIPPED,
                Order::STATUS_CANCELLED,
            ], true);
        })->count();

        $slaStats = $this->computeContractSlaStats($partnership);
        $financialStats = $this->computeContractFinancialStats($partnership, $today);

        $creditLimit = (float) ($partnership->credit_limit ?? 0);
        $unpaidAmount = $financialStats['unpaid_amount'];
        $creditUsagePercent = $creditLimit > 0 ? round(($unpaidAmount / $creditLimit) * 100, 1) : 0.0;
        $isCreditExceeded = $creditLimit > 0 && $unpaidAmount > $creditLimit;
        $isCreditWarning = $creditLimit > 0 && ! $isCreditExceeded && $unpaidAmount >= ($creditLimit * 0.8);

        $hasAlert = $isExpired
            || $isExpiringSoon
            || $financialStats['overdue_count'] > 0
            || $isCreditExceeded
            || $isCreditWarning;

        $frequencyLabel = $this->formatFrequencyLabel($schedule?->frequency_type);
        $deliveryDays = is_array($schedule?->delivery_days) ? $schedule->delivery_days : [];
        $deliveryDaysLabel = ! empty($deliveryDays) ? implode(', ', $deliveryDays) : '-';
        $deliveryTimeLabel = $schedule?->delivery_time ? substr((string) $schedule->delivery_time, 0, 5).' WIB' : '05:30 WIB';

        $latestEvaluation = $partnership->evaluations->first();

        $recentOrders = $partnership->orders
            ->sortByDesc('order_date')
            ->take(6)
            ->values()
            ->map(function (Order $order) use ($unit): array {
                $qty = (float) $order->items->sum('quantity');
                $delivered = (float) $order->items->sum('quantity_delivered');

                return [
                    'id' => (string) $order->id,
                    'code' => 'ORD-'.strtoupper(substr((string) $order->id, 0, 8)),
                    'order_date' => $order->order_date ? Carbon::parse($order->order_date)->locale('id')->translatedFormat('d M Y') : '-',
                    'delivery_date' => $order->delivery_date ? Carbon::parse($order->delivery_date)->locale('id')->translatedFormat('d M Y') : '-',
                    'status' => (string) $order->order_status,
                    'status_label' => $this->formatOrderStatusLabel((string) $order->order_status),
                    'qty' => number_format($qty, 0, ',', '.').' '.$unit,
                    'delivered_qty' => number_format($delivered, 0, ',', '.').' '.$unit,
                    'total_amount_formatted' => 'Rp '.number_format((float) $order->total_amount, 0, ',', '.'),
                ];
            })->all();

        $evaluationsList = $partnership->evaluations
            ->take(6)
            ->values()
            ->map(function ($eval) use ($unit): array {
                return [
                    'id' => (string) $eval->id,
                    'evaluation_period' => (string) $eval->evaluation_period,
                    'target_volume' => (float) $eval->target_volume,
                    'realized_volume' => (float) $eval->realized_volume,
                    'target_formatted' => number_format((float) $eval->target_volume, 1, ',', '.').' '.$unit,
                    'realized_formatted' => number_format((float) $eval->realized_volume, 1, ',', '.').' '.$unit,
                    'fulfillment_rate_percentage' => (float) $eval->fulfillment_rate_percentage,
                    'discrepancy_notes' => $eval->discrepancy_notes ?: 'Tidak ada catatan deviasi.',
                    'created_at_formatted' => $eval->created_at ? Carbon::parse($eval->created_at)->locale('id')->translatedFormat('d M Y H:i') : '-',
                ];
            })->all();

        $statusMeta = $this->getLifecycleStatusMeta($lifecycleStatus, $isExpiringSoon, $daysRemaining);

        $contractData = [
            'id' => (string) $partnership->id,
            'contract_code' => $partnership->contract_code,
            'partnership' => $partnership,
            'schedule' => $schedule,
            'has_schedule' => $schedule !== null,
            'is_auto_order_active' => (bool) ($schedule?->is_auto_order_active ?? false),
            'umkm_name' => $umkm?->business_name ?? 'Mitra UMKM',
            'umkm_owner' => $umkm?->owner_name ?? '-',
            'umkm_phone' => $umkm?->phone ?? '-',
            'umkm_address' => $umkm?->address ?? '-',
            'reliability_grade' => $reliabilityScore?->grade ?? 'UNRATED',
            'reliability_score' => (float) ($reliabilityScore?->final_score ?? 0),
            'reliability_badge_color' => $reliabilityScore?->getGradeBadgeColor() ?? 'bg-gray-100 text-gray-700 border-gray-200',
            'reliability_label' => $reliabilityScore?->getGradeLabel() ?? 'Belum Terperingkat',
            'commodity_name' => $commodityName,
            'unit' => $unit,
            'unit_price' => $unitPrice,
            'unit_price_formatted' => 'Rp '.number_format($unitPrice, 0, ',', '.'),
            'frequency_label' => $frequencyLabel,
            'delivery_days_label' => $deliveryDaysLabel,
            'delivery_time_label' => $deliveryTimeLabel,
            'start_date_formatted' => $startDate
                ? $startDate->locale('id')->translatedFormat('d M Y')
                : ($partnership->created_at ? $partnership->created_at->locale('id')->translatedFormat('d M Y') : $today->locale('id')->translatedFormat('d M Y')),
            'end_date_formatted' => $endDate ? $endDate->locale('id')->translatedFormat('d M Y') : 'Berkelanjutan',
            'end_date_input' => $endDate ? $endDate->toDateString() : now()->addMonths(3)->toDateString(),
            'days_remaining' => $daysRemaining,
            'is_expired' => $isExpired,
            'is_expiring_soon' => $isExpiringSoon,
            'lifecycle_status' => $lifecycleStatus,
            'status_label' => $statusMeta['label'],
            'status_badge_class' => $statusMeta['badge_class'],
            'status_dot_class' => $statusMeta['dot_class'],
            'volume_per_delivery' => $volumePerDelivery,
            'target_volume' => $effectiveTargetVolume,
            'has_explicit_target_volume' => $scheduleTargetVolume > 0,
            'ordered_volume' => $orderedVolume,
            'delivered_volume' => $deliveredVolume,
            'remaining_backorder_volume' => $remainingBackorderVolume,
            'volume_fulfillment_rate' => $volumeFulfillmentRate,
            'total_orders' => $totalOrders,
            'completed_orders' => $completedOrders,
            'issue_orders' => $issueOrders,
            'otd_rate' => $slaStats['otd_rate'],
            'completed_shipments_count' => $slaStats['completed_shipments_count'],
            'avg_rating' => $slaStats['avg_rating'],
            'reviews_count' => $slaStats['reviews_count'],
            'term_of_payment_days' => (int) ($partnership->term_of_payment_days ?? 0),
            'credit_limit' => $creditLimit,
            'credit_limit_formatted' => 'Rp '.number_format($creditLimit, 0, ',', '.'),
            'unpaid_amount' => $unpaidAmount,
            'unpaid_amount_formatted' => 'Rp '.number_format($unpaidAmount, 0, ',', '.'),
            'paid_amount' => $financialStats['paid_amount'],
            'paid_amount_formatted' => 'Rp '.number_format($financialStats['paid_amount'], 0, ',', '.'),
            'credit_usage_percent' => $creditUsagePercent,
            'is_credit_warning' => $isCreditWarning,
            'is_credit_exceeded' => $isCreditExceeded,
            'overdue_count' => $financialStats['overdue_count'],
            'overdue_amount' => $financialStats['overdue_amount'],
            'overdue_amount_formatted' => 'Rp '.number_format($financialStats['overdue_amount'], 0, ',', '.'),
            'max_overdue_days' => $financialStats['max_overdue_days'],
            'invoices' => $financialStats['invoices'],
            'recent_orders' => $recentOrders,
            'evaluations' => $evaluationsList,
            'latest_evaluation' => $latestEvaluation ? [
                'period' => (string) $latestEvaluation->evaluation_period,
                'rate' => (float) $latestEvaluation->fulfillment_rate_percentage,
                'notes' => $latestEvaluation->discrepancy_notes,
            ] : null,
            'submission_details' => $partnership->submission_details ?: 'Kesepakatan pasokan rutin B2B melalui portal SORAI.',
            'special_instructions' => $schedule?->special_instructions ?: 'Tidak ada instruksi pengiriman khusus.',
            'has_alert' => $hasAlert,
        ];

        $contractData['modal_payload'] = [
            'id' => $contractData['id'],
            'contract_code' => $contractData['contract_code'],
            'umkm_name' => $contractData['umkm_name'],
            'umkm_owner' => $contractData['umkm_owner'],
            'umkm_phone' => $contractData['umkm_phone'],
            'umkm_address' => $contractData['umkm_address'],
            'reliability_grade' => $contractData['reliability_grade'],
            'reliability_score' => $contractData['reliability_score'],
            'reliability_label' => $contractData['reliability_label'],
            'commodity_name' => $contractData['commodity_name'],
            'unit' => $contractData['unit'],
            'unit_price_formatted' => $contractData['unit_price_formatted'],
            'frequency_label' => $contractData['frequency_label'],
            'delivery_days_label' => $contractData['delivery_days_label'],
            'delivery_time_label' => $contractData['delivery_time_label'],
            'start_date_formatted' => $contractData['start_date_formatted'],
            'end_date_formatted' => $contractData['end_date_formatted'],
            'end_date_input' => $contractData['end_date_input'],
            'days_remaining' => $contractData['days_remaining'],
            'lifecycle_status' => $contractData['lifecycle_status'],
            'status_label' => $contractData['status_label'],
            'is_auto_order_active' => $contractData['is_auto_order_active'],
            'has_schedule' => $contractData['has_schedule'],
            'volume_per_delivery' => $contractData['volume_per_delivery'],
            'target_volume' => $contractData['target_volume'],
            'ordered_volume' => $contractData['ordered_volume'],
            'delivered_volume' => $contractData['delivered_volume'],
            'volume_fulfillment_rate' => $contractData['volume_fulfillment_rate'],
            'term_of_payment_days' => $contractData['term_of_payment_days'],
            'credit_limit' => $contractData['credit_limit'],
            'credit_limit_formatted' => $contractData['credit_limit_formatted'],
            'unpaid_amount_formatted' => $contractData['unpaid_amount_formatted'],
            'paid_amount_formatted' => $contractData['paid_amount_formatted'],
            'credit_usage_percent' => $contractData['credit_usage_percent'],
            'overdue_count' => $contractData['overdue_count'],
            'overdue_amount_formatted' => $contractData['overdue_amount_formatted'],
            'max_overdue_days' => $contractData['max_overdue_days'],
            'otd_rate' => $contractData['otd_rate'],
            'avg_rating' => $contractData['avg_rating'],
            'submission_details' => $contractData['submission_details'],
            'special_instructions' => $contractData['special_instructions'],
            'recent_orders' => $contractData['recent_orders'],
            'invoices' => $contractData['invoices'],
            'evaluations' => $contractData['evaluations'],
        ];

        return $contractData;
    }

    protected function resolveLifecycleStatus(Partnership $partnership, ?int $daysRemaining): string
    {
        if ($partnership->isTerminatedStatus()) {
            return 'terminated';
        }

        if ($partnership->isPausedStatus()) {
            return 'paused';
        }

        if ($daysRemaining !== null && $daysRemaining < 0) {
            return 'expired';
        }

        return 'active';
    }

    /**
     * @return array{label: string, badge_class: string, dot_class: string}
     */
    protected function getLifecycleStatusMeta(string $lifecycleStatus, bool $isExpiringSoon, ?int $daysRemaining): array
    {
        if ($lifecycleStatus === 'terminated') {
            return [
                'label' => 'Dihentikan / Berakhir',
                'badge_class' => 'bg-gray-100 text-gray-700 border-gray-200',
                'dot_class' => 'bg-gray-500',
            ];
        }

        if ($lifecycleStatus === 'paused') {
            return [
                'label' => 'Dijeda Sementara',
                'badge_class' => 'bg-slate-100 text-slate-700 border-slate-200',
                'dot_class' => 'bg-slate-500',
            ];
        }

        if ($lifecycleStatus === 'expired') {
            return [
                'label' => 'Lewat Masa Berlaku',
                'badge_class' => 'bg-rose-50 text-rose-700 border-rose-200',
                'dot_class' => 'bg-rose-500 animate-pulse',
            ];
        }

        if ($isExpiringSoon) {
            $daysLabel = $daysRemaining === 0 ? 'Berakhir Hari Ini' : "Berakhir {$daysRemaining} Hari Lagi";

            return [
                'label' => $daysLabel,
                'badge_class' => 'bg-amber-50 text-amber-800 border-amber-200',
                'dot_class' => 'bg-amber-500 animate-pulse',
            ];
        }

        return [
            'label' => 'Kontrak Aktif',
            'badge_class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'dot_class' => 'bg-emerald-500',
        ];
    }

    /**
     * @return array{otd_rate: float|null, completed_shipments_count: int, avg_rating: float|null, reviews_count: int}
     */
    protected function computeContractSlaStats(Partnership $partnership): array
    {
        $completedShipments = 0;
        $onTimeShipments = 0;
        $ratings = [];

        foreach ($partnership->orders as $order) {
            if ($order->review && $order->review->rating) {
                $ratings[] = (float) $order->review->rating;
            }

            foreach ($order->shipmentConfirmations as $shipment) {
                if (! $shipment->received_at) {
                    continue;
                }

                $completedShipments++;
                $deliveryDate = $order->delivery_date
                    ? Carbon::parse($order->delivery_date)
                    : Carbon::parse($shipment->created_at);
                $cutoff = $deliveryDate->copy()->setTime(8, 30, 0);

                if (Carbon::parse($shipment->received_at)->lessThanOrEqualTo($cutoff)) {
                    $onTimeShipments++;
                }
            }
        }

        $otdRate = $completedShipments > 0
            ? round(($onTimeShipments / $completedShipments) * 100, 1)
            : null;

        $avgRating = ! empty($ratings)
            ? round(array_sum($ratings) / count($ratings), 1)
            : null;

        return [
            'otd_rate' => $otdRate,
            'completed_shipments_count' => $completedShipments,
            'avg_rating' => $avgRating,
            'reviews_count' => count($ratings),
        ];
    }

    /**
     * @return array{
     *     paid_amount: float,
     *     unpaid_amount: float,
     *     overdue_count: int,
     *     overdue_amount: float,
     *     max_overdue_days: int,
     *     invoices: array<int, array<string, mixed>>
     * }
     */
    protected function computeContractFinancialStats(Partnership $partnership, Carbon $today): array
    {
        $payments = $partnership->orders
            ->flatMap(fn (Order $order) => $order->payments)
            ->sortByDesc('created_at');

        $paidAmount = 0.0;
        $unpaidAmount = 0.0;
        $overdueCount = 0;
        $overdueAmount = 0.0;
        $maxOverdueDays = 0;
        $invoices = [];

        foreach ($payments as $payment) {
            /** @var Payment $payment */
            $amount = (float) $payment->amount;
            $isPaid = $payment->isPaid();
            $effectiveDueDate = $this->resolveEffectiveDueDate($payment, $partnership);

            $isOverdue = false;
            $overdueDays = 0;

            if ($isPaid) {
                $paidAmount += $amount;
            } else {
                $unpaidAmount += $amount;

                if ($effectiveDueDate !== null && $effectiveDueDate->lt($today)) {
                    $isOverdue = true;
                    $overdueDays = (int) $effectiveDueDate->diffInDays($today);
                    $overdueCount++;
                    $overdueAmount += $amount;
                    $maxOverdueDays = max($maxOverdueDays, $overdueDays);
                }
            }

            if (count($invoices) < 8) {
                $invoices[] = [
                    'invoice_number' => $payment->invoice_number ?: ('INV-'.strtoupper(substr((string) $payment->id, 0, 8))),
                    'amount_formatted' => 'Rp '.number_format($amount, 0, ',', '.'),
                    'due_date_formatted' => $effectiveDueDate ? $effectiveDueDate->locale('id')->translatedFormat('d M Y') : 'Tunai / COD',
                    'is_paid' => $isPaid,
                    'is_overdue' => $isOverdue,
                    'overdue_days' => $overdueDays,
                    'status_label' => $isPaid ? 'Lunas' : ($isOverdue ? "Terlambat {$overdueDays} Hari" : 'Menunggu Pembayaran'),
                ];
            }
        }

        return [
            'paid_amount' => $paidAmount,
            'unpaid_amount' => $unpaidAmount,
            'overdue_count' => $overdueCount,
            'overdue_amount' => $overdueAmount,
            'max_overdue_days' => $maxOverdueDays,
            'invoices' => $invoices,
        ];
    }

    protected function resolveEffectiveDueDate(Payment $payment, Partnership $partnership): ?Carbon
    {
        if ($payment->due_date !== null) {
            return Carbon::parse($payment->due_date)->startOfDay();
        }

        if ($partnership->term_of_payment_days > 0) {
            $baseDate = $payment->transaction_time ?? $payment->created_at;

            if ($baseDate !== null) {
                return Carbon::parse($baseDate)->addDays((int) $partnership->term_of_payment_days)->startOfDay();
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $contracts
     * @return array<string, float|int>
     */
    protected function computeKpis(Collection $contracts): array
    {
        $activeContracts = $contracts->where('lifecycle_status', 'active');
        $expiringSoonCount = $contracts->where('is_expiring_soon', true)->count();
        $expiredCount = $contracts->where('lifecycle_status', 'expired')->count();

        $totalDeliveredVolume = (float) $contracts->sum('delivered_volume');
        $totalTargetVolume = (float) $contracts->sum('target_volume');
        $avgFulfillment = $totalTargetVolume > 0
            ? min(100.0, round(($totalDeliveredVolume / $totalTargetVolume) * 100, 1))
            : 0.0;

        $totalUnpaidAmount = (float) $contracts->sum('unpaid_amount');
        $totalCreditLimit = (float) $contracts->sum('credit_limit');
        $overdueContractsCount = $contracts->filter(fn (array $c): bool => $c['overdue_count'] > 0)->count();
        $overdueInvoicesCount = (int) $contracts->sum('overdue_count');
        $overdueTotalAmount = (float) $contracts->sum('overdue_amount');

        $contractsWithOtd = $contracts->filter(fn (array $c): bool => $c['otd_rate'] !== null);
        $avgOtdRate = $contractsWithOtd->isNotEmpty()
            ? round((float) $contractsWithOtd->avg('otd_rate'), 1)
            : 100.0;

        $ratedContracts = $contracts->filter(fn (array $c): bool => $c['reliability_score'] > 0);
        $avgReliabilityScore = $ratedContracts->isNotEmpty()
            ? round((float) $ratedContracts->avg('reliability_score'), 1)
            : 0.0;

        return [
            'active_contracts_count' => $activeContracts->count(),
            'expiring_soon_count' => $expiringSoonCount,
            'expired_count' => $expiredCount,
            'total_delivered_volume' => $totalDeliveredVolume,
            'total_target_volume' => $totalTargetVolume,
            'average_volume_fulfillment' => $avgFulfillment,
            'total_unpaid_amount' => $totalUnpaidAmount,
            'total_credit_limit' => $totalCreditLimit,
            'overdue_contracts_count' => $overdueContractsCount,
            'overdue_invoices_count' => $overdueInvoicesCount,
            'overdue_total_amount' => $overdueTotalAmount,
            'average_otd_rate' => $avgOtdRate,
            'average_reliability_score' => $avgReliabilityScore,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $contracts
     * @return array<string, int>
     */
    protected function computeCounts(Collection $contracts): array
    {
        return [
            'all' => $contracts->count(),
            'active' => $contracts->where('lifecycle_status', 'active')->count(),
            'attention' => $contracts->where('has_alert', true)->count(),
            'overdue' => $contracts->filter(fn (array $c): bool => $c['overdue_count'] > 0)->count(),
            'inactive' => $contracts->filter(fn (array $c): bool => in_array($c['lifecycle_status'], ['paused', 'terminated'], true))->count(),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $contracts
     * @return array<int, array<string, mixed>>
     */
    protected function buildAlerts(Collection $contracts): array
    {
        $alerts = [];

        foreach ($contracts as $contract) {
            if ($contract['lifecycle_status'] === 'expired') {
                $daysPast = abs((int) ($contract['days_remaining'] ?? 0));
                $alerts[] = [
                    'severity' => 'critical',
                    'type' => 'expired',
                    'contract_id' => $contract['id'],
                    'contract_code' => $contract['contract_code'],
                    'umkm_name' => $contract['umkm_name'],
                    'title' => 'Kontrak Lewat Masa Berlaku',
                    'message' => "Kontrak {$contract['contract_code']} ({$contract['umkm_name']}) telah berakhir {$daysPast} hari lalu pada {$contract['end_date_formatted']}.",
                    'action_type' => 'extend',
                    'modal_payload' => $contract['modal_payload'],
                ];
            } elseif ($contract['is_expiring_soon']) {
                $days = (int) ($contract['days_remaining'] ?? 0);
                $timeText = $days === 0 ? 'hari ini' : "dalam {$days} hari";
                $alerts[] = [
                    'severity' => 'warning',
                    'type' => 'expiring_soon',
                    'contract_id' => $contract['id'],
                    'contract_code' => $contract['contract_code'],
                    'umkm_name' => $contract['umkm_name'],
                    'title' => 'Kontrak Mendekati Berakhir',
                    'message' => "Kontrak {$contract['contract_code']} ({$contract['umkm_name']}) akan berakhir {$timeText} ({$contract['end_date_formatted']}).",
                    'action_type' => 'extend',
                    'modal_payload' => $contract['modal_payload'],
                ];
            }

            if ($contract['overdue_count'] > 0) {
                $alerts[] = [
                    'severity' => 'critical',
                    'type' => 'overdue',
                    'contract_id' => $contract['id'],
                    'contract_code' => $contract['contract_code'],
                    'umkm_name' => $contract['umkm_name'],
                    'title' => 'Tagihan Jatuh Tempo (Overdue)',
                    'message' => "{$contract['umkm_name']} memiliki {$contract['overdue_count']} tagihan overdue senilai {$contract['overdue_amount_formatted']} (maks. terlambat {$contract['max_overdue_days']} hari).",
                    'action_type' => 'detail',
                    'modal_payload' => $contract['modal_payload'],
                ];
            }

            if ($contract['is_credit_exceeded']) {
                $alerts[] = [
                    'severity' => 'critical',
                    'type' => 'credit_exceeded',
                    'contract_id' => $contract['id'],
                    'contract_code' => $contract['contract_code'],
                    'umkm_name' => $contract['umkm_name'],
                    'title' => 'Plafon Kredit Terlampaui',
                    'message' => "Piutang berjalan {$contract['umkm_name']} ({$contract['unpaid_amount_formatted']}) melebihi plafon kredit ({$contract['credit_limit_formatted']}).",
                    'action_type' => 'detail',
                    'modal_payload' => $contract['modal_payload'],
                ];
            } elseif ($contract['is_credit_warning']) {
                $alerts[] = [
                    'severity' => 'warning',
                    'type' => 'credit_warning',
                    'contract_id' => $contract['id'],
                    'contract_code' => $contract['contract_code'],
                    'umkm_name' => $contract['umkm_name'],
                    'title' => 'Pemakaian Plafon Kredit Tinggi',
                    'message' => "Pemakaian kredit {$contract['umkm_name']} mencapai {$contract['credit_usage_percent']}% dari batas {$contract['credit_limit_formatted']}.",
                    'action_type' => 'detail',
                    'modal_payload' => $contract['modal_payload'],
                ];
            }
        }

        usort($alerts, function (array $a, array $b): int {
            $weightA = $a['severity'] === 'critical' ? 0 : 1;
            $weightB = $b['severity'] === 'critical' ? 0 : 1;

            return $weightA <=> $weightB;
        });

        return $alerts;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $contracts
     * @return Collection<int, array<string, mixed>>
     */
    protected function filterContracts(Collection $contracts, string $search, string $statusFilter): Collection
    {
        $filtered = $contracts;

        if ($statusFilter === 'active') {
            $filtered = $filtered->where('lifecycle_status', 'active');
        } elseif ($statusFilter === 'attention') {
            $filtered = $filtered->where('has_alert', true);
        } elseif ($statusFilter === 'overdue') {
            $filtered = $filtered->filter(fn (array $c): bool => $c['overdue_count'] > 0);
        } elseif ($statusFilter === 'inactive') {
            $filtered = $filtered->filter(fn (array $c): bool => in_array($c['lifecycle_status'], ['paused', 'terminated'], true));
        }

        $trimmedSearch = mb_strtolower(trim($search));
        if ($trimmedSearch !== '') {
            $filtered = $filtered->filter(function (array $c) use ($trimmedSearch): bool {
                $haystacks = [
                    mb_strtolower((string) $c['contract_code']),
                    mb_strtolower((string) $c['umkm_name']),
                    mb_strtolower((string) $c['umkm_owner']),
                    mb_strtolower((string) $c['commodity_name']),
                    mb_strtolower((string) $c['submission_details']),
                ];

                foreach ($haystacks as $haystack) {
                    if (str_contains($haystack, $trimmedSearch)) {
                        return true;
                    }
                }

                return false;
            });
        }

        return $filtered;
    }

    protected function formatFrequencyLabel(?string $freqType): string
    {
        if ($freqType === null || $freqType === '') {
            return 'Sesuai Permintaan';
        }

        return match ($freqType) {
            'daily' => 'Setiap Hari',
            'weekly' => '1x Seminggu',
            'twice_weekly' => '2x Seminggu',
            'thrice_weekly' => '3x Seminggu',
            'monthly' => '1x Sebulan',
            default => $freqType,
        };
    }

    protected function formatOrderStatusLabel(string $status): string
    {
        return match (strtolower($status)) {
            Order::STATUS_PENDING, Order::STATUS_CONFIRMED, 'menunggu_konfirmasi_barang' => 'Menunggu Kirim',
            Order::STATUS_WAITING_BACKORDER_APPROVAL => 'Validasi Backorder',
            Order::STATUS_BACKORDER_PENDING => 'Backorder Menunggu Sisa',
            Order::STATUS_SHIPPED, Order::STATUS_PARTIALLY_SHIPPED, 'dikirim' => 'Dalam Pengiriman',
            Order::STATUS_COMPLETED, Order::STATUS_COMPLETED_PARTIAL, 'barang_diterima' => 'Selesai',
            Order::STATUS_CANCELLED => 'Dibatalkan',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    /**
     * Buat atau perbarui jadwal pasokan saat pemasok memperpanjang masa kontrak.
     */
    public function extendPartnershipContract(
        Partnership $partnership,
        string $endDate,
        ?float $totalContractVolume = null,
        ?float $volumePerDelivery = null,
        ?string $frequencyType = null
    ): PartnershipDeliverySchedule {
        $schedule = $partnership->deliverySchedules()->latest('created_at')->first();

        if ($schedule) {
            $updates = [
                'end_date' => $endDate,
            ];

            if ($totalContractVolume !== null) {
                $updates['total_contract_volume'] = $totalContractVolume;
            }

            $schedule->update($updates);

            if (! $partnership->isTerminatedStatus() && ! $partnership->isActiveStatus()) {
                $partnership->update(['status' => Partnership::STATUS_ACTIVE]);
            }

            return $schedule->fresh();
        }

        $supplierProduct = $partnership->supplier?->products()->first();
        $unit = $supplierProduct?->commodity?->unit ?? 'kg';
        $resolvedVolumePerDelivery = $volumePerDelivery ?? 50.0;
        $resolvedTotalVolume = $totalContractVolume ?? ($resolvedVolumePerDelivery * 8);

        if (! $partnership->isTerminatedStatus() && ! $partnership->isActiveStatus()) {
            $partnership->update(['status' => Partnership::STATUS_ACTIVE]);
        }

        return PartnershipDeliverySchedule::create([
            'partnership_id' => $partnership->id,
            'supplier_product_id' => $supplierProduct?->id,
            'volume_per_delivery' => $resolvedVolumePerDelivery,
            'total_contract_volume' => $resolvedTotalVolume,
            'unit' => $unit,
            'frequency_type' => $frequencyType ?: '2x Seminggu (Senin & Kamis)',
            'delivery_days' => ['Senin', 'Kamis'],
            'delivery_time' => '05:30:00',
            'is_auto_order_active' => true,
            'start_date' => now()->toDateString(),
            'end_date' => $endDate,
        ]);
    }
}
