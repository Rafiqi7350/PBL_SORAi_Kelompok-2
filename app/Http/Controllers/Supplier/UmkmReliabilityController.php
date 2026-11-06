<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Partnership;
use App\Models\UmkmProfile;
use App\Services\Reliability\UmkmReliabilityScoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UmkmReliabilityController extends Controller
{
    public function __construct(
        protected UmkmReliabilityScoreService $scoreService
    ) {}

    /**
     * Tampilkan detail skor keandalan mitra UMKM (JSON terenkapsulasi & kepatuhan UU PDP).
     */
    public function show(string $umkmId): JsonResponse
    {
        $umkm = UmkmProfile::findOrFail($umkmId);

        $latestScore = $umkm->latestReliabilityScore;
        if (! $latestScore) {
            $latestScore = $this->scoreService->calculate($umkm);
        }

        // Sanitasi Data: Hanya tampilkan indikator reputasi & agregat, tanpa data privat (UU PDP)
        $privacySafeResponse = [
            'status' => 'success',
            'data' => [
                'umkm_id' => $umkm->id,
                'business_name' => $umkm->business_name,
                'final_score' => (float) $latestScore->final_score,
                'grade' => $latestScore->grade,
                'grade_label' => $latestScore->getGradeLabel(),
                'grade_badge_color' => $latestScore->getGradeBadgeColor(),
                'is_eligible_for_top' => $latestScore->isEligibleForTop(),
                'scores' => [
                    'payment_discipline' => (float) $latestScore->payment_discipline_score,
                    'order_consistency' => (float) $latestScore->order_consistency_score,
                    'tenure_history' => (float) $latestScore->tenure_history_score,
                ],
                'recommendations' => $latestScore->recommendations,
                'metrics_summary' => [
                    'on_time_payment_rate' => $latestScore->metrics_breakdown['payment']['otpr'] ?? null,
                    'average_delay_days' => $latestScore->metrics_breakdown['payment']['avg_dpd'] ?? null,
                    'fulfillment_rate' => $latestScore->metrics_breakdown['order']['volume_fulfillment_rate'] ?? null,
                    'cancellation_rate' => $latestScore->metrics_breakdown['order']['cancellation_rate'] ?? null,
                    'tenure_months' => $latestScore->metrics_breakdown['tenure']['tenure_months'] ?? null,
                ],
                'calculated_at' => $latestScore->calculated_at?->translatedFormat('d F Y H:i WIB'),
                'privacy_notice' => 'Data disajikan dalam bentuk agregat performa dagang terstandarisasi. Dokumen sensitif dan mutasi rekening perbankan UMKM dilindungi privasinya sesuai ketentuan UU PDP.',
            ],
        ];

        return response()->json($privacySafeResponse);
    }

    /**
     * Hitung ulang skor keandalan UMKM berdasarkan data transaksi terbaru.
     */
    public function recalculate(string $umkmId): JsonResponse
    {
        $umkm = UmkmProfile::findOrFail($umkmId);
        $score = $this->scoreService->calculate($umkm);

        return response()->json([
            'status' => 'success',
            'message' => 'Skor keandalan UMKM berhasil diperbarui.',
            'data' => [
                'final_score' => (float) $score->final_score,
                'grade' => $score->grade,
                'recommendations' => $score->recommendations,
            ],
        ]);
    }

    /**
     * Perbarui fasilitas Term of Payment dan Limit Kredit kemitraan.
     */
    public function updateCreditTerms(Request $request, string $partnershipId): RedirectResponse
    {
        $supplier = Auth::user()?->supplierProfile;

        $partnership = Partnership::where('supplier_id', $supplier?->id)
            ->findOrFail($partnershipId);

        $validated = $request->validate([
            'term_of_payment_days' => 'required|integer|min:0|max:90',
            'credit_limit' => 'required|numeric|min:0',
        ]);

        $partnership->update([
            'term_of_payment_days' => $validated['term_of_payment_days'],
            'credit_limit' => $validated['credit_limit'],
        ]);

        return redirect()->back()
            ->with('success', 'Fasilitas Term of Payment dan Plafon Kredit kemitraan berhasil diperbarui.');
    }
}
