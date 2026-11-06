<?php

namespace App\Services\Reliability;

use App\Models\Order;
use App\Models\Partnership;
use App\Models\Payment;
use App\Models\UmkmProfile;
use App\Models\UmkmReliabilityScore;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class UmkmReliabilityScoreService
{
    /**
     * Hitung skor keandalan mitra UMKM dan simpan hasil evaluasi ke database.
     */
    public function calculate(UmkmProfile $umkm, ?Partnership $partnership = null): UmkmReliabilityScore
    {
        $partnershipQuery = Partnership::where('umkm_id', $umkm->id);
        if ($partnership !== null) {
            $partnershipQuery->where('id', $partnership->id);
        }
        $partnershipIds = $partnershipQuery->pluck('id')->all();

        $orders = ! empty($partnershipIds)
            ? Order::whereIn('partnership_id', $partnershipIds)->get()
            : collect();
        $totalOrdersCount = $orders->count();

        // 1. Cek Kasus Mitra Baru (Cold Start)
        $isColdStart = $totalOrdersCount < 3;

        // 2. Evaluasi Pilar 1: Kepatuhan Pembayaran Tempo (Bobot 45%)
        $paymentMetrics = $this->calculatePaymentDiscipline($orders);

        // 3. Evaluasi Pilar 2: Konsistensi & Pemenuhan Pesanan (Bobot 35%)
        $orderMetrics = $this->calculateOrderConsistency($orders, $partnershipIds);

        // 4. Evaluasi Pilar 3: Riwayat Transaksi & Umur Kemitraan (Bobot 20%)
        $tenureMetrics = $this->calculateTenureAndHistory($umkm, $orders);

        // 5. Agregasi Skor Tertimbang
        $p1 = $paymentMetrics['score'];
        $p2 = $orderMetrics['score'];
        $p3 = $tenureMetrics['score'];

        $calculatedScore = ($p1 * 0.45) + ($p2 * 0.35) + ($p3 * 0.20);

        // 6. Hard-Cap Penalty: Keterlambatan Parah (> 45 hari) mengunci nilai maksimal 49 (Grade D)
        if ($paymentMetrics['has_severe_default']) {
            $calculatedScore = min($calculatedScore, 49.0);
        }

        $finalScore = round(max(0.0, min(100.0, $calculatedScore)), 2);

        // 7. Penentuan Grade
        $grade = $this->resolveGrade($finalScore, $isColdStart);

        // 8. Rekomendasi Fasilitas Term of Payment
        $recommendations = $this->generateRecommendations($grade, $finalScore, $orders);

        $metricsBreakdown = [
            'payment' => $paymentMetrics,
            'order' => $orderMetrics,
            'tenure' => $tenureMetrics,
            'is_cold_start' => $isColdStart,
            'total_orders' => $totalOrdersCount,
        ];

        return UmkmReliabilityScore::create([
            'umkm_id' => $umkm->id,
            'partnership_id' => $partnership?->id,
            'final_score' => $finalScore,
            'grade' => $grade,
            'payment_discipline_score' => round($p1, 2),
            'order_consistency_score' => round($p2, 2),
            'tenure_history_score' => round($p3, 2),
            'metrics_breakdown' => $metricsBreakdown,
            'recommendations' => $recommendations,
            'calculated_at' => now(),
        ]);
    }

    /**
     * Hitung metrik kepatuhan pembayaran tempo.
     *
     * @param  Collection<int, Order>  $orders
     * @return array{score: float, otpr: float, avg_dpd: float, severe_delinquencies: int, has_severe_default: bool}
     */
    protected function calculatePaymentDiscipline($orders): array
    {
        if ($orders->isEmpty()) {
            return [
                'score' => 70.0,
                'otpr' => 100.0,
                'avg_dpd' => 0.0,
                'severe_delinquencies' => 0,
                'has_severe_default' => false,
            ];
        }

        $orderIds = $orders->pluck('id')->all();
        $payments = ! empty($orderIds) ? Payment::whereIn('order_id', $orderIds)->get() : collect();

        if ($payments->isEmpty()) {
            return [
                'score' => 70.0,
                'otpr' => 100.0,
                'avg_dpd' => 0.0,
                'severe_delinquencies' => 0,
                'has_severe_default' => false,
            ];
        }

        $totalInvoices = $payments->count();
        $onTimeCount = 0;
        $totalDpd = 0;
        $severeDelinquencies = 0;
        $hasSevereDefault = false;

        foreach ($payments as $payment) {
            $dueDate = $payment->due_date
                ?? ($payment->transaction_time ? Carbon::parse($payment->transaction_time)->addDays(30) : Carbon::parse($payment->created_at)->addDays(30));

            $isPaid = $payment->isPaid();
            $effectiveDate = $isPaid && $payment->paid_at ? Carbon::parse($payment->paid_at) : now();

            $dpd = $effectiveDate->isAfter($dueDate) ? (int) $dueDate->diffInDays($effectiveDate) : 0;
            $totalDpd += $dpd;

            if ($dpd === 0) {
                $onTimeCount++;
            }

            if ($dpd > 30) {
                $severeDelinquencies++;
            }

            if (! $isPaid && $dpd > 45) {
                $hasSevereDefault = true;
            }
        }

        $otpr = ($onTimeCount / $totalInvoices) * 100.0;
        $avgDpd = $totalInvoices > 0 ? ($totalDpd / $totalInvoices) : 0.0;

        $sOtpr = $otpr;
        $sDpd = max(0.0, 100.0 - (3.5 * $avgDpd));
        $sDef = max(0.0, 100.0 - (35.0 * $severeDelinquencies));

        $p1 = ($sOtpr * 0.444) + ($sDpd * 0.333) + ($sDef * 0.223);

        return [
            'score' => (float) $p1,
            'otpr' => round($otpr, 2),
            'avg_dpd' => round($avgDpd, 2),
            'severe_delinquencies' => $severeDelinquencies,
            'has_severe_default' => $hasSevereDefault,
        ];
    }

    /**
     * Hitung konsistensi volume pesanan dan cancellation rate.
     *
     * @param  Collection<int, Order>  $orders
     * @param  array<int, string>  $partnershipIds
     * @return array{score: float, volume_fulfillment_rate: float, cancellation_rate: float}
     */
    protected function calculateOrderConsistency($orders, array $partnershipIds): array
    {
        $totalOrders = $orders->count();
        if ($totalOrders === 0) {
            return [
                'score' => 70.0,
                'volume_fulfillment_rate' => 100.0,
                'cancellation_rate' => 0.0,
            ];
        }

        // Cek evaluasi pemenuhan volume kontrak
        $evaluationAgg = ! empty($partnershipIds)
            ? DB::table('partnership_evaluations')
                ->whereIn('partnership_id', $partnershipIds)
                ->avg('fulfillment_rate_percentage')
            : null;

        $volumeFulfillmentRate = $evaluationAgg !== null ? (float) $evaluationAgg : 95.0;

        $rVol = $volumeFulfillmentRate / 100.0;
        if ($rVol >= 0.90 && $rVol <= 1.15) {
            $sVfr = 100.0;
        } else {
            $sVfr = max(0.0, 100.0 - (abs(1.0 - $rVol) * 100.0));
        }

        // Tingkat Pembatalan (Cancellation Rate)
        $cancelledCount = $orders->where('order_status', Order::STATUS_CANCELLED)->count();
        $cancellationRate = ($cancelledCount / $totalOrders) * 100.0;
        $sOcr = max(0.0, 100.0 - ($cancellationRate * 2.5));

        // Keteraturan jadwal (Cadence)
        $sCad = 90.0;

        $p2 = ($sVfr * 0.428) + ($sCad * 0.286) + ($sOcr * 0.286);

        return [
            'score' => (float) $p2,
            'volume_fulfillment_rate' => round($volumeFulfillmentRate, 2),
            'cancellation_rate' => round($cancellationRate, 2),
        ];
    }

    /**
     * Hitung riwayat transaksi dan umur kemitraan.
     *
     * @param  Collection<int, Order>  $orders
     * @return array{score: float, tenure_months: int, total_gmv: float}
     */
    protected function calculateTenureAndHistory(UmkmProfile $umkm, $orders): array
    {
        $tenureMonths = max(1, (int) Carbon::parse($umkm->created_at)->diffInMonths(now()));
        $sTen = min(100.0, ($tenureMonths / 24.0) * 100.0);

        // GMV dari pesanan selesai
        $completedOrders = $orders->filter(function ($order) {
            return in_array($order->order_status, [
                Order::STATUS_COMPLETED,
                Order::STATUS_COMPLETED_PARTIAL,
                'completed',
                'SELESAI',
            ]);
        });

        $totalGmv = (float) $completedOrders->sum('total_amount');
        $sGmv = min(100.0, ($totalGmv / 100000000.0) * 100.0);

        // Dispute / integritas reputasi
        $sDisp = 95.0;

        $p3 = ($sTen * 0.50) + ($sGmv * 0.25) + ($sDisp * 0.25);

        return [
            'score' => (float) $p3,
            'tenure_months' => $tenureMonths,
            'total_gmv' => $totalGmv,
        ];
    }

    /**
     * Tentukan Grade berdasarkan skor akhir dan status cold start.
     */
    protected function resolveGrade(float $finalScore, bool $isColdStart): string
    {
        if ($isColdStart) {
            return UmkmReliabilityScore::GRADE_UNRATED;
        }

        if ($finalScore >= 85.0) {
            return UmkmReliabilityScore::GRADE_A;
        }

        if ($finalScore >= 70.0) {
            return UmkmReliabilityScore::GRADE_B;
        }

        if ($finalScore >= 55.0) {
            return UmkmReliabilityScore::GRADE_C;
        }

        if ($finalScore >= 40.0) {
            return UmkmReliabilityScore::GRADE_D;
        }

        return UmkmReliabilityScore::GRADE_F;
    }

    /**
     * Buat rekomendasi otomatis Term of Payment (TOP).
     *
     * @param  Collection<int, Order>  $orders
     * @return array{max_top_days: int, recommended_credit_limit: float, policy_note: string, contract_action: string}
     */
    protected function generateRecommendations(string $grade, float $finalScore, $orders): array
    {
        $avgMonthlyVolume = (float) $orders->take(6)->avg('total_amount') ?: 10000000.0;

        return match ($grade) {
            UmkmReliabilityScore::GRADE_A => [
                'max_top_days' => 30,
                'recommended_credit_limit' => round($avgMonthlyVolume * 1.5, -5),
                'policy_note' => 'Otomatis setujui fasilitas Term of Payment (TOP) hingga 30 hari kalender. Risiko gagal bayar sangat rendah.',
                'contract_action' => 'Prioritas perpanjangan kontrak otomatis (auto-renewal eligible).',
            ],
            UmkmReliabilityScore::GRADE_B => [
                'max_top_days' => 21,
                'recommended_credit_limit' => round($avgMonthlyVolume * 1.0, -5),
                'policy_note' => 'Fasilitas Term of Payment standar 14-21 hari kalender disetujui.',
                'contract_action' => 'Perpanjangan kontrak reguler dengan evaluasi per 6 bulan.',
            ],
            UmkmReliabilityScore::GRADE_C => [
                'max_top_days' => 7,
                'recommended_credit_limit' => round($avgMonthlyVolume * 0.5, -5),
                'policy_note' => 'Maksimal fasilitas tempo 7 hari kalender atau terapkan Split Payment (DP 50% di muka + 50% saat barang tiba).',
                'contract_action' => 'Evaluasi kuota bulanan wajib sebelum pesanan disetujui.',
            ],
            UmkmReliabilityScore::GRADE_D => [
                'max_top_days' => 0,
                'recommended_credit_limit' => 0.0,
                'policy_note' => 'Hanya transaksi Cash on Delivery (COD) atau pembayaran tunai di muka. Fasilitas tempo ditangguhkan.',
                'contract_action' => 'Perpanjangan kontrak ditangguhkan sementara sampai riwayat pembayaran membaik.',
            ],
            UmkmReliabilityScore::GRADE_F => [
                'max_top_days' => 0,
                'recommended_credit_limit' => 0.0,
                'policy_note' => 'Blacklist fasilitas tempo. Wajib pembayaran penuh 100% di muka (Cash Before Delivery).',
                'contract_action' => 'Rekomendasi peninjauan pemutusan kemitraan.',
            ],
            default => [
                'max_top_days' => 0,
                'recommended_credit_limit' => 0.0,
                'policy_note' => 'Mitra baru (Unrated). Disarankan menggunakan pembayaran tunai/COD untuk minimal 3 transaksi awal.',
                'contract_action' => 'Masa uji coba kemitraan (Trial Period).',
            ],
        };
    }
}
