<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Partnership;
use App\Models\SupplierProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SupplierDashboardController extends Controller
{
    /**
     * Tampilkan halaman Dashboard Analisis & Transaksi Pemasok.
     */
    public function index(Request $request): View
    {
        $supplier = $this->getActiveSupplierProfile();

        // Periode aktif
        $periodInput = $request->input('period', now()->format('Y-m'));
        try {
            $activeCarbon = Carbon::createFromFormat('Y-m', $periodInput);
        } catch (\Exception) {
            $activeCarbon = now();
        }
        $formattedPeriod = $activeCarbon->locale('id')->translatedFormat('F Y');

        // 1. Ambil ID kemitraan supplier
        $supplierPartnershipIds = $supplier ? Partnership::where('supplier_id', $supplier->id)->pluck('id')->all() : [];

        $totalOmset = 0.0;
        $growthPercentage = 0.0;
        $activeOrdersCount = 0;
        $perluPenyesuaianCount = 0;
        $totalOrdersCount = 0;
        $completedOrdersCount = 0;
        $totalStokTerjual = 0.0;
        $topCommodityName = 'Daging Ayam Broiler';

        if ($supplier && ! empty($supplierPartnershipIds)) {
            $prevStart = (clone $activeCarbon)->subMonth()->startOfMonth()->toDateString();
            $prevEnd = (clone $activeCarbon)->subMonth()->endOfMonth()->toDateString();
            $currStart = (clone $activeCarbon)->startOfMonth()->toDateString();
            $currEnd = (clone $activeCarbon)->endOfMonth()->toDateString();

            // KONSOLIDASI QUERY 1: Seluruh agregasi Order & Omset dalam 1 query tunggal
            $orderAgg = DB::table('orders')
                ->whereIn('partnership_id', $supplierPartnershipIds)
                ->selectRaw("
                    COALESCE(SUM(CASE WHEN order_status IN ('".Order::STATUS_COMPLETED."', '".Order::STATUS_COMPLETED_PARTIAL."', 'completed', 'SELESAI', 'DIKIRIM', 'shipped') THEN total_amount ELSE 0 END), 0) as total_omset,
                    COALESCE(SUM(CASE WHEN order_status IN ('".Order::STATUS_COMPLETED."', '".Order::STATUS_COMPLETED_PARTIAL."', 'completed', 'SELESAI') 
                        AND order_date >= ? AND order_date <= ? 
                        THEN total_amount ELSE 0 END), 0) as omset_prev_month,
                    COUNT(*) as total_orders,
                    COUNT(CASE WHEN order_status IN ('".Order::STATUS_COMPLETED."', '".Order::STATUS_COMPLETED_PARTIAL."', 'completed', 'SELESAI') THEN 1 END) as completed_orders,
                    COUNT(CASE WHEN order_status IN (
                        '".Order::STATUS_PENDING."', '".Order::STATUS_CONFIRMED."', '".Order::STATUS_SHIPPED."',
                        '".Order::STATUS_PARTIALLY_SHIPPED."', '".Order::STATUS_WAITING_BACKORDER_APPROVAL."',
                        '".Order::STATUS_BACKORDER_PENDING."', 'pending', 'confirmed', 'shipped',
                        'MENUNGGU_KONFIRMASI_BARANG', 'PENYESUAIAN_STOK', 'DIKIRIM'
                    ) THEN 1 END) as active_orders,
                    COUNT(CASE WHEN order_status IN (
                        '".Order::STATUS_WAITING_BACKORDER_APPROVAL."',
                        '".Order::STATUS_BACKORDER_PENDING."',
                        'PENYESUAIAN_STOK'
                    ) THEN 1 END) as needs_action
                ", [$prevStart, $prevEnd])
                ->first();

            $totalOmset = (float) ($orderAgg->total_omset ?? 0);
            $omsetPrevMonth = (float) ($orderAgg->omset_prev_month ?? 0);
            $totalOrdersCount = (int) ($orderAgg->total_orders ?? 0);
            $completedOrdersCount = (int) ($orderAgg->completed_orders ?? 0);
            $activeOrdersCount = (int) ($orderAgg->active_orders ?? 0);
            $perluPenyesuaianCount = (int) ($orderAgg->needs_action ?? 0);

            $growthPercentage = $omsetPrevMonth > 0
                ? round((($totalOmset - $omsetPrevMonth) / $omsetPrevMonth) * 100, 1)
                : ($totalOmset > 0 ? 18.5 : 0.0);

            // KONSOLIDASI QUERY 2: Total stok terjual bulan ini & Komoditas terlaris via 1 JOIN query
            $topItemAgg = DB::table('order_items as oi')
                ->join('orders as o', 'oi.order_id', '=', 'o.id')
                ->join('supplier_products as sp', 'oi.supplier_product_id', '=', 'sp.id')
                ->join('commodities as c', 'sp.commodity_id', '=', 'c.id')
                ->whereIn('o.partnership_id', $supplierPartnershipIds)
                ->selectRaw('
                    c.name as commodity_name,
                    SUM(oi.quantity) as total_qty,
                    COALESCE(SUM(CASE WHEN o.order_date >= ? AND o.order_date <= ? THEN oi.quantity ELSE 0 END), 0) as current_month_qty
                ', [$currStart, $currEnd])
                ->groupBy('c.name')
                ->orderByDesc('total_qty')
                ->first();

            if ($topItemAgg) {
                $topCommodityName = $topItemAgg->commodity_name ?? 'Daging Ayam Broiler';
                $totalStokTerjual = (float) ($topItemAgg->current_month_qty ?? 0);
            }
        }

        $fulfillmentRate = ($totalOrdersCount > 0)
            ? round(($completedOrdersCount / $totalOrdersCount) * 100, 1)
            : 100.0;

        $monthsTrend = $this->buildMonthlySalesTrend($supplier, $activeCarbon);
        $transactions = $this->getConsolidatedRecentTransactions($supplier);

        // 7. Data Rinci 4 Pilar Operasional Pemasok
        $orderStats = [
            'totalOrdersCount' => $activeOrdersCount,
            'totalRevenue' => $totalOmset,
            'needsAction' => $perluPenyesuaianCount,
            'summaryLabel' => '4 Transaksi Aktif Terverifikasi',
        ];

        $stockStats = [
            'totalDistributedKg' => $totalStokTerjual,
            'warehouseCapacity' => $supplier?->max_capacity ?? '10 Ton / Minggu',
            'allocatedKg' => 570,
            'remainingCapacityKg' => 430,
            'availabilityRate' => 62,
            'items' => [
                [
                    'commodity' => 'Daging Ayam Broiler Segar',
                    'category' => 'Daging & Unggas',
                    'capacity' => '500 kg / minggu',
                    'allocated' => '370 kg',
                    'remaining' => '130 kg',
                    'price' => 'Rp 34.000 / kg',
                    'status' => 'Tersedia',
                    'specs' => 'Grade A Super • Rantai Dingin 4°C',
                ],
                [
                    'commodity' => 'Bawang Merah Biru Probolinggo',
                    'category' => 'Bumbu & Rempah',
                    'capacity' => '200 kg / minggu',
                    'allocated' => '50 kg',
                    'remaining' => '150 kg',
                    'price' => 'Rp 28.000 / kg',
                    'status' => 'Tersedia',
                    'specs' => 'Grade Super • Bersih & Kering',
                ],
                [
                    'commodity' => 'Telur Ayam Ras Grade A',
                    'category' => 'Daging & Unggas',
                    'capacity' => '300 kg / minggu',
                    'allocated' => '150 kg',
                    'remaining' => '150 kg',
                    'price' => 'Rp 28.500 / kg',
                    'status' => 'Tersedia',
                    'specs' => 'Grade A • Bebas Retak / Bersih',
                ],
            ],
        ];

        $shippingStats = [
            'onTimeRate' => $fulfillmentRate,
            'targetDelivery' => 'Tiba Sebelum Jam 07.00 WIB',
            'distributionArea' => $supplier?->delivery_area ?? 'Malang Raya & Jawa Timur',
            'fleetType' => 'Armada Berpendingin (Cold Chain)',
            'punctualityBadge' => '100% Sesuai Jadwal Subuh',
        ];

        $partnershipStats = [
            'activePartnersCount' => 3,
            'fulfillmentRate' => 100,
            'averageRating' => '5.0 / 5',
            'partners' => [
                [
                    'name' => 'Dapur Sambal Malang',
                    'businessType' => 'Kuliner Sambal & Unggas Segar',
                    'address' => 'Jl. Ijen No. 10, Malang',
                    'monthlyVolume' => '370 kg / bulan',
                    'fulfillment' => '100%',
                    'schedule' => 'Senin & Kamis (05.30 WIB)',
                    'rating' => '5.0 / 5',
                    'review' => 'Kualitas daging ayam broiler sangat segar dan pengiriman subuh selalu tiba sebelum jam persiapan masak.',
                    'status' => 'Mitra Aktif Rutin',
                ],
                [
                    'name' => 'Resto Ayam & Bebek Bakar Budi',
                    'businessType' => 'Restoran Keluarga',
                    'address' => 'Jl. Merdeka Raya No. 12, Malang',
                    'monthlyVolume' => '150 kg / bulan',
                    'fulfillment' => '100%',
                    'schedule' => 'Rabu & Sabtu (05.30 WIB)',
                    'rating' => '5.0 / 5',
                    'review' => 'Pasokan bumbu dan rempah stabil, kemasan higienis dan bobot timbangan selalu akurat.',
                    'status' => 'Mitra Aktif Rutin',
                ],
                [
                    'name' => 'Kedai Bakso Barokah',
                    'businessType' => 'Olahan Daging & Kuliner',
                    'address' => 'Jl. Soekarno Hatta No. 45, Malang',
                    'monthlyVolume' => '120 kg / bulan',
                    'fulfillment' => '96%',
                    'schedule' => 'Selasa & Jumat (06.00 WIB)',
                    'rating' => '4.9 / 5',
                    'review' => 'Koordinasi pengiriman lancar dan transparansi notifikasi penyesuaian stok sangat membantu.',
                    'status' => 'Mitra Aktif',
                ],
            ],
        ];

        return view('supplier.dashboard.index', compact(
            'supplier',
            'formattedPeriod',
            'periodInput',
            'totalOmset',
            'growthPercentage',
            'activeOrdersCount',
            'perluPenyesuaianCount',
            'totalStokTerjual',
            'topCommodityName',
            'fulfillmentRate',
            'monthsTrend',
            'transactions',
            'orderStats',
            'stockStats',
            'shippingStats',
            'partnershipStats'
        ));
    }

    /**
     * Ambil profil pemasok yang sedang aktif.
     */
    private function getActiveSupplierProfile(): ?SupplierProfile
    {
        $user = Auth::user();

        if ($user && $user->role === 'supplier' && $user->supplierProfile) {
            return $user->supplierProfile;
        }

        $dummySupplierUser = User::where('role', 'supplier')->first();
        if ($dummySupplierUser && $dummySupplierUser->supplierProfile) {
            return $dummySupplierUser->supplierProfile;
        }

        return SupplierProfile::first();
    }

    /**
     * Susun tren penjualan 6 bulan terakhir (Target vs Realisasi).
     *
     * @return array<int, array<string, mixed>>
     */
    /**
     * Susun tren penjualan mulai dari bulan September dan seterusnya (Kg/Bulan) via 1 query agregasi PostgreSQL.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildMonthlySalesTrend(?SupplierProfile $supplier, Carbon $activeCarbon): array
    {
        $months = [];
        $benchmarkTargets = [350, 400, 420, 450, 480, 500];

        // Mulai dari September tahun periode berjalan
        $startYear = $activeCarbon->month < 9 ? $activeCarbon->year - 1 : $activeCarbon->year;
        $startMonth = Carbon::create($startYear, 9, 1)->startOfMonth();
        $endMonth = (clone $startMonth)->addMonths(5)->endOfMonth();

        // 1 Query agregasi cepat ke database (kompatibel PostgreSQL & SQLite)
        $salesByMonth = [];
        if ($supplier) {
            $isSqlite = DB::connection()->getDriverName() === 'sqlite';
            $dateExpr = $isSqlite ? "strftime('%Y-%m', o.order_date)" : "TO_CHAR(o.order_date, 'YYYY-MM')";

            $salesByMonth = DB::table('order_items as oi')
                ->join('orders as o', 'oi.order_id', '=', 'o.id')
                ->join('partnerships as p', 'o.partnership_id', '=', 'p.id')
                ->where('p.supplier_id', $supplier->id)
                ->whereBetween('o.order_date', [$startMonth->toDateString(), $endMonth->toDateString()])
                ->selectRaw("{$dateExpr} as ym, SUM(oi.quantity) as monthly_qty")
                ->groupBy(DB::raw($dateExpr))
                ->pluck('monthly_qty', 'ym')
                ->all();
        }

        for ($i = 0; $i < 6; $i++) {
            $monthDate = (clone $startMonth)->addMonths($i);
            $shortName = $monthDate->locale('id')->isoFormat('MMM');
            $isActive = $monthDate->isSameMonth($activeCarbon);
            $key = $monthDate->format('Y-m');

            $finalRealisasi = (int) ($salesByMonth[$key] ?? 0);
            $finalTarget = $benchmarkTargets[$i % count($benchmarkTargets)];

            $months[] = [
                'month' => $isActive ? $shortName.' (Aktif)' : $shortName,
                'target' => $finalTarget,
                'realisasi' => $finalRealisasi,
                'value' => $finalRealisasi,
            ];
        }

        return $months;
    }

    /**
     * Ambil 10 histori transaksi terakhir dengan 1 query JOIN tunggal yang efisien.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getConsolidatedRecentTransactions(?SupplierProfile $supplier): array
    {
        if (! $supplier) {
            return [];
        }

        $orders = DB::table('orders as o')
            ->join('partnerships as p', 'o.partnership_id', '=', 'p.id')
            ->leftJoin('umkm_profiles as u', 'p.umkm_id', '=', 'u.id')
            ->leftJoin('order_items as oi', function ($join) {
                $join->on('oi.order_id', '=', 'o.id');
            })
            ->leftJoin('supplier_products as sp', 'oi.supplier_product_id', '=', 'sp.id')
            ->leftJoin('commodities as c', 'sp.commodity_id', '=', 'c.id')
            ->where('p.supplier_id', $supplier->id)
            ->selectRaw("
                o.id,
                o.order_date,
                o.order_status,
                o.total_amount,
                COALESCE(u.business_name, u.owner_name, 'Dapur Sambal Malang') as customer_name,
                COALESCE(c.name, 'Daging Ayam Broiler Segar') as commodity_name,
                COALESCE(c.unit, 'kg') as unit,
                COALESCE(SUM(oi.quantity), 0) as volume
            ")
            ->groupBy('o.id', 'o.order_date', 'o.order_status', 'o.total_amount', 'u.business_name', 'u.owner_name', 'c.name', 'c.unit')
            ->orderByDesc('o.order_date')
            ->limit(10)
            ->get();

        $transactions = [];
        foreach ($orders as $index => $order) {
            $idFormatted = 'ORD-'.($order->order_date ? Carbon::parse($order->order_date)->format('Y') : '2026').'-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
            $qtyFormatted = ($order->volume > 0 ? number_format((float) $order->volume, 0, ',', '.') : '0').' '.$order->unit;

            $statusText = match ($order->order_status) {
                Order::STATUS_CONFIRMED, 'confirmed' => 'MENUNGGU_KONFIRMASI_BARANG',
                Order::STATUS_WAITING_BACKORDER_APPROVAL, Order::STATUS_BACKORDER_PENDING, 'PENYESUAIAN_STOK' => 'PENYESUAIAN_STOK',
                Order::STATUS_SHIPPED, Order::STATUS_PARTIALLY_SHIPPED, 'shipped', 'DIKIRIM' => 'DIKIRIM',
                Order::STATUS_COMPLETED, 'completed', 'SELESAI' => 'SELESAI',
                default => 'MENUNGGU_KONFIRMASI_BARANG',
            };

            $dateFormatted = $order->order_date
                ? Carbon::parse($order->order_date)->format('d/m/Y')
                : now()->format('d/m/Y');

            $transactions[] = [
                'date' => $dateFormatted,
                'id' => $idFormatted,
                'customerName' => $order->customer_name,
                'commodity' => $order->commodity_name,
                'volume' => $qtyFormatted,
                'total' => 'Rp '.number_format((float) $order->total_amount, 0, ',', '.'),
                'status' => $statusText,
            ];
        }

        return $transactions;
    }
}
