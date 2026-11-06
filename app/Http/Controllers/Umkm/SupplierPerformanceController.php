<?php

namespace App\Http\Controllers\Umkm;

use App\Http\Controllers\Controller;
use App\Models\Partnership;
use App\Models\SupplierProfile;
use App\Services\Performance\SupplierPerformanceTrackerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierPerformanceController extends Controller
{
    public function __construct(
        protected SupplierPerformanceTrackerService $trackerService
    ) {}

    /**
     * Tampilkan detail evaluasi skor performa pemasok (OTD, Pemenuhan Kuota, Kualitas & Bintang).
     */
    public function show(string $supplierId, Request $request): JsonResponse
    {
        $supplier = SupplierProfile::findOrFail($supplierId);

        $partnership = null;
        if ($request->has('partnership_id')) {
            $partnership = Partnership::find($request->query('partnership_id'));
        }

        $latestScore = $partnership?->latestSupplierPerformanceScore ?? $supplier->latestPerformanceScore;

        if (! $latestScore) {
            $latestScore = $this->trackerService->calculate($supplier, $partnership);
        }

        $metrics = $latestScore->metrics_breakdown ?? [];

        return response()->json([
            'status' => 'success',
            'data' => [
                'supplier_id' => $supplier->id,
                'company_name' => $supplier->company_name,
                'owner_name' => $supplier->owner_name,
                'warehouse_address' => $supplier->warehouse_address,
                'delivery_area' => $supplier->delivery_area ?? 'Lokal',
                'final_score' => (float) $latestScore->final_score,
                'star_rating' => (float) $latestScore->star_rating,
                'grade' => $latestScore->grade,
                'grade_label' => $latestScore->getGradeLabel(),
                'grade_badge_color' => $latestScore->getGradeBadgeColor(),
                'is_recommended_for_renewal' => $latestScore->isRecommendedForRenewal(),
                'scores' => [
                    'on_time_delivery' => (float) $latestScore->on_time_delivery_score,
                    'quota_fulfillment' => (float) $latestScore->quota_fulfillment_score,
                    'quality_rating' => (float) $latestScore->quality_rating_score,
                ],
                'metrics_summary' => [
                    'otd_rate' => $metrics['delivery']['otd_rate'] ?? 100.0,
                    'avg_delay_hours' => $metrics['delivery']['avg_delay_hours'] ?? 0.0,
                    'severe_delays_count' => $metrics['delivery']['severe_delays_count'] ?? 0,
                    'fill_rate' => $metrics['fulfillment']['fill_rate'] ?? 100.0,
                    'backorder_rate' => $metrics['fulfillment']['backorder_rate'] ?? 0.0,
                    'cancellation_rate' => $metrics['fulfillment']['cancellation_rate'] ?? 0.0,
                    'average_rating' => $metrics['quality']['average_rating'] ?? 5.0,
                    'defect_free_rate' => $metrics['quality']['defect_free_rate'] ?? 100.0,
                    'total_reviews' => $metrics['quality']['total_reviews'] ?? 0,
                    'total_shipments' => $metrics['total_shipments'] ?? 0,
                    'is_cold_start' => (bool) ($metrics['is_cold_start'] ?? false),
                ],
                'recommendations' => $latestScore->recommendations ?? [
                    'contract_advice' => 'Evaluasi berkala standar pemenuhan kontrak.',
                    'buffer_stock_advice' => 'Siapkan cadangan bahan baku proporsional.',
                    'negotiation_tip' => 'Diskusikan komitmen SLA pengiriman pada siklus berikutnya.',
                ],
                'calculated_at' => $latestScore->calculated_at?->translatedFormat('d F Y H:i WIB') ?? now()->translatedFormat('d F Y H:i WIB'),
            ],
        ]);
    }

    /**
     * Hitung ulang skor performa pemasok berdasarkan data riwayat transaksi & pengiriman terbaru.
     */
    public function recalculate(string $supplierId, Request $request): JsonResponse
    {
        $supplier = SupplierProfile::findOrFail($supplierId);

        $partnership = null;
        if ($request->has('partnership_id')) {
            $partnership = Partnership::find($request->input('partnership_id'));
        }

        $newScore = $this->trackerService->calculate($supplier, $partnership);

        return response()->json([
            'status' => 'success',
            'message' => 'Skor performa pemasok berhasil dikalkulasi ulang.',
            'data' => [
                'final_score' => (float) $newScore->final_score,
                'star_rating' => (float) $newScore->star_rating,
                'grade' => $newScore->grade,
                'grade_label' => $newScore->getGradeLabel(),
                'recommendations' => $newScore->recommendations,
            ],
        ]);
    }
}
