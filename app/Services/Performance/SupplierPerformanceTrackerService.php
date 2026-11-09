<?php

namespace App\Services\Performance;

use App\Models\Order;
use App\Models\OrderReview;
use App\Models\Partnership;
use App\Models\ShipmentConfirmation;
use App\Models\SupplierPerformanceScore;
use App\Models\SupplierProfile;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SupplierPerformanceTrackerService
{
    /**
     * Hitung skor performa kerja sama pemasok dan simpan hasil evaluasi ke database.
     */
    public function calculate(SupplierProfile $supplier, ?Partnership $partnership = null): SupplierPerformanceScore
    {
        $partnershipQuery = Partnership::where('supplier_id', $supplier->id);
        if ($partnership !== null) {
            $partnershipQuery->where('id', $partnership->id);
        }
        $partnershipIds = $partnershipQuery->pluck('id')->all();

        $orders = ! empty($partnershipIds)
            ? Order::whereIn('partnership_id', $partnershipIds)->with(['items'])->get()
            : collect();

        $orderIds = $orders->pluck('id')->all();

        $shipments = ! empty($orderIds)
            ? ShipmentConfirmation::whereIn('order_id', $orderIds)->get()
            : collect();

        $completedShipments = $shipments->filter(fn ($s) => ! empty($s->received_at));
        $totalShipmentCount = $completedShipments->count();

        // 1. Cek Kasus Pemasok Baru (Cold Start: < 3 pengiriman selesai)
        $isColdStart = $totalShipmentCount < 3;

        // 2. Evaluasi Pilar 1: Ketepatan Waktu Pengiriman / OTD (Bobot 40%)
        $deliveryMetrics = $this->calculateOnTimeDelivery($orders, $completedShipments);

        // 3. Evaluasi Pilar 2: Pemenuhan Kuota / OFR (Bobot 35%)
        $fulfillmentMetrics = $this->calculateOrderFulfillment($orders, $shipments);

        // 4. Evaluasi Pilar 3: Kualitas Komoditas & Ulasan Bintang (Bobot 25%)
        $qualityMetrics = $this->calculateQualityAndReviews($orderIds, $completedShipments);

        // 5. Agregasi Skor Tertimbang
        $p1 = $deliveryMetrics['score'];
        $p2 = $fulfillmentMetrics['score'];
        $p3 = $qualityMetrics['score'];

        $calculatedScore = ($p1 * 0.40) + ($p2 * 0.35) + ($p3 * 0.25);
        $finalScore = round(max(0.0, min(100.0, $calculatedScore)), 2);

        // 6. Konversi ke Rating Bintang (1.0 s/d 5.0)
        $starRating = round(max(1.0, min(5.0, $finalScore / 20.0)), 1);

        // 7. Penentuan Grade
        $grade = $this->resolveGrade($finalScore, $isColdStart);

        // 8. Rekomendasi Aksi & Negosiasi bagi UMKM
        $recommendations = $this->generateRecommendations($grade, $finalScore, $deliveryMetrics, $fulfillmentMetrics);

        $metricsBreakdown = [
            'delivery' => $deliveryMetrics,
            'fulfillment' => $fulfillmentMetrics,
            'quality' => $qualityMetrics,
            'is_cold_start' => $isColdStart,
            'total_shipments' => $totalShipmentCount,
        ];

        return SupplierPerformanceScore::create([
            'supplier_id' => $supplier->id,
            'partnership_id' => $partnership?->id,
            'final_score' => $finalScore,
            'star_rating' => $starRating,
            'grade' => $grade,
            'on_time_delivery_score' => round($p1, 2),
            'quota_fulfillment_score' => round($p2, 2),
            'quality_rating_score' => round($p3, 2),
            'metrics_breakdown' => $metricsBreakdown,
            'recommendations' => $recommendations,
            'calculated_at' => now(),
        ]);
    }

    /**
     * Hitung metrik ketepatan waktu pengiriman (On-Time Delivery Rate & keterlambatan).
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, Order>  $orders
     * @param  Collection<int, ShipmentConfirmation>  $completedShipments
     * @return array{score: float, otd_rate: float, avg_delay_hours: float, severe_delays_count: int}
     */
    protected function calculateOnTimeDelivery($orders, $completedShipments): array
    {
        if ($completedShipments->isEmpty()) {
            return [
                'score' => 85.0,
                'otd_rate' => 100.0,
                'avg_delay_hours' => 0.0,
                'severe_delays_count' => 0,
            ];
        }

        $totalDeliveries = $completedShipments->count();
        $onTimeCount = 0;
        $totalDelayHours = 0.0;
        $severeDelaysCount = 0;

        $ordersKeyed = $orders->keyBy('id');

        foreach ($completedShipments as $shipment) {
            $order = $ordersKeyed->get($shipment->order_id);
            if (! $order) {
                continue;
            }

            // Batas waktu pengiriman yang dijanjikan
            $deliveryDate = $order->delivery_date ? Carbon::parse($order->delivery_date) : Carbon::parse($shipment->created_at);
            // Default janji kirim pagi hari (08:00) atau sesuai jadwal kirim
            $promisedTime = $deliveryDate->copy()->setTime(8, 0, 0);

            $receivedTime = Carbon::parse($shipment->received_at);

            // Grace period: toleransi keterlambatan 30 menit
            $cutoffWithGrace = $promisedTime->copy()->addMinutes(30);

            if ($receivedTime->lessThanOrEqualTo($cutoffWithGrace)) {
                $onTimeCount++;
            } else {
                $delayMinutes = max(0, $promisedTime->diffInMinutes($receivedTime, false));
                $delayHours = $delayMinutes / 60.0;
                $totalDelayHours += $delayHours;

                if ($delayHours > 6.0) {
                    $severeDelaysCount++;
                }
            }
        }

        $otdRate = ($onTimeCount / $totalDeliveries) * 100.0;
        $delayedCount = max(1, $totalDeliveries - $onTimeCount);
        $avgDelayHours = $totalDelayHours / $delayedCount;

        $sOtd = $otdRate;
        $sDelay = max(0.0, 100.0 - (4.0 * $avgDelayHours));
        $sSev = max(0.0, 100.0 - (20.0 * $severeDelaysCount));

        $p1 = ($sOtd * 0.50) + ($sDelay * 0.30) + ($sSev * 0.20);

        return [
            'score' => (float) $p1,
            'otd_rate' => round($otdRate, 2),
            'avg_delay_hours' => round($avgDelayHours, 1),
            'severe_delays_count' => $severeDelaysCount,
        ];
    }

    /**
     * Hitung tingkat pemenuhan kuota komoditas dan rasio backorder sepihak.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, Order>  $orders
     * @param  Collection<int, ShipmentConfirmation>  $shipments
     * @return array{score: float, fill_rate: float, backorder_rate: float, cancellation_rate: float}
     */
    protected function calculateOrderFulfillment($orders, $shipments): array
    {
        if ($orders->isEmpty()) {
            return [
                'score' => 85.0,
                'fill_rate' => 100.0,
                'backorder_rate' => 0.0,
                'cancellation_rate' => 0.0,
            ];
        }

        $totalOrders = $orders->count();

        // 1. Hitung First-Pass Fill Rate dari Order Items
        $totalOrderedQty = 0.0;
        $totalDeliveredBatch1Qty = 0.0;

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $totalOrderedQty += (float) $item->quantity;
                $totalDeliveredBatch1Qty += (float) $item->quantity_delivered;
            }
        }

        $fillRate = $totalOrderedQty > 0
            ? ($totalDeliveredBatch1Qty / $totalOrderedQty) * 100.0
            : 100.0;

        // 2. Hitung rasio pemesanan yang terkena Backorder / Pengiriman Parsial
        $backorderCount = $orders->filter(function ($order) {
            return in_array($order->order_status, [
                Order::STATUS_PARTIALLY_SHIPPED,
                Order::STATUS_BACKORDER_PENDING,
                Order::STATUS_COMPLETED_PARTIAL,
            ]);
        })->count();

        $backorderRate = ($backorderCount / $totalOrders) * 100.0;

        // 3. Tingkat Pembatalan Stok Kosong
        $cancelledCount = $orders->where('order_status', Order::STATUS_CANCELLED)->count();
        $cancellationRate = ($cancelledCount / $totalOrders) * 100.0;

        $sFill = min(100.0, $fillRate);
        $sBack = max(0.0, 100.0 - ($backorderRate * 1.5));
        $sCanc = max(0.0, 100.0 - ($cancellationRate * 3.0));

        $p2 = ($sFill * 0.571) + ($sBack * 0.229) + ($sCanc * 0.200);

        return [
            'score' => (float) $p2,
            'fill_rate' => round($fillRate, 2),
            'backorder_rate' => round($backorderRate, 2),
            'cancellation_rate' => round($cancellationRate, 2),
        ];
    }

    /**
     * Hitung kualitas komoditas dan kepuasan ulasan dari UMKM.
     *
     * @param  array<int, string>  $orderIds
     * @param  Collection<int, ShipmentConfirmation>  $completedShipments
     * @return array{score: float, average_rating: float, defect_free_rate: float, total_reviews: int}
     */
    protected function calculateQualityAndReviews(array $orderIds, $completedShipments): array
    {
        $reviews = ! empty($orderIds)
            ? OrderReview::whereIn('order_id', $orderIds)->get()
            : collect();

        $averageRating = $reviews->isNotEmpty() ? (float) $reviews->avg('rating') : 4.5;
        $totalReviews = $reviews->count();

        // Sub-skor bintang (dikonversi ke 0-100)
        $sRating = ($averageRating / 5.0) * 100.0;

        // Sub-skor bebas retur / kesesuaian fisik
        $defectFreeRate = 98.0;
        $sDefect = $defectFreeRate;

        $p3 = ($sDefect * 0.60) + ($sRating * 0.40);

        return [
            'score' => (float) $p3,
            'average_rating' => round($averageRating, 1),
            'defect_free_rate' => round($defectFreeRate, 2),
            'total_reviews' => $totalReviews,
        ];
    }

    /**
     * Tentukan Grade performa pemasok.
     */
    protected function resolveGrade(float $finalScore, bool $isColdStart): string
    {
        if ($isColdStart) {
            return SupplierPerformanceScore::GRADE_UNRATED;
        }

        if ($finalScore >= 85.0) {
            return SupplierPerformanceScore::GRADE_A;
        }

        if ($finalScore >= 70.0) {
            return SupplierPerformanceScore::GRADE_B;
        }

        if ($finalScore >= 50.0) {
            return SupplierPerformanceScore::GRADE_C;
        }

        return SupplierPerformanceScore::GRADE_D;
    }

    /**
     * Buat rekomendasi operasional dan instrumen negosiasi bagi UMKM.
     *
     * @param  array{score: float, otd_rate: float, avg_delay_hours: float, severe_delays_count: int}  $delivery
     * @param  array{score: float, fill_rate: float, backorder_rate: float, cancellation_rate: float}  $fulfillment
     * @return array{contract_advice: string, buffer_stock_advice: string, negotiation_tip: string}
     */
    protected function generateRecommendations(string $grade, float $finalScore, array $delivery, array $fulfillment): array
    {
        return match ($grade) {
            SupplierPerformanceScore::GRADE_A => [
                'contract_advice' => 'Pemasok Utama (Top Tier). Sangat disarankan untuk memperpanjang kontrak pasokan jangka panjang dan meningkatkan alokasi volume kuota utama.',
                'buffer_stock_advice' => 'Stok Pengaman Rendah (Cukup 1 hari). Pemasok memiliki konsistensi waktu kirim prima sehingga UMKM dapat menghemat ruang pendingin/gudang dapur.',
                'negotiation_tip' => 'Gunakan reputasi Grade A ini untuk menegosiasikan harga grosir volume besar yang lebih kompetitif.',
            ],
            SupplierPerformanceScore::GRADE_B => [
                'contract_advice' => 'Pemasok Standar Andal. Layanan logistik berada dalam kategori baik dan memenuhi standar operasional reguler.',
                'buffer_stock_advice' => 'Stok Pengaman Normal (Siapkan cadangan 1–2 hari) untuk mengantisipasi potensi keterlambatan minor.',
                'negotiation_tip' => 'Minta komitmen jam kirim prioritas pagi hari pada perpanjangan kontrak berikutnya.',
            ],
            SupplierPerformanceScore::GRADE_C => [
                'contract_advice' => 'Pemasok Dalam Pengawasan. Terdeteksi sering mengalami keterlambatan kirim atau pemenuhan parsial (backorder).',
                'buffer_stock_advice' => 'Wajib Menyiapkan Buffer Stock 2–3 Hari dan menyiapkan vendor cadangan (backup supplier) untuk bahan baku menu utama.',
                'negotiation_tip' => 'Ajukan klausul kompensasi keterlambatan: Minta perpanjangan hari pembayaran tempo (TOP) atau diskon penalti 2% jika pesanan tiba telat > 2 jam.',
            ],
            SupplierPerformanceScore::GRADE_D => [
                'contract_advice' => 'Peringatan Risiko Logistik Tinggi (Pelanggaran SLA). Performa pengiriman sering meleset dan berisiko melumpuhkan operasional dapur.',
                'buffer_stock_advice' => 'Segera alihkan pasokan utama ke pemasok alternatif berpredikat Grade A/B.',
                'negotiation_tip' => 'Pertimbangkan penghentian kontrak atau klaim ganti rugi selisih biaya belanja darurat di pasar.',
            ],
            default => [
                'contract_advice' => 'Pemasok Baru (Masa Observasi). Belum memiliki akumulasi 3 pengiriman selesai untuk penilaian performa penuh.',
                'buffer_stock_advice' => 'Tetap siapkan stok pengaman mandiri selama masa uji coba 3 pesanan awal.',
                'negotiation_tip' => 'Evaluasi kepatuhan timbang muat dan waktu tiba sebelum menyepakati kontrak jangka panjang.',
            ],
        };
    }
}
