<?php

namespace App\Http\Controllers\Umkm;

use App\Http\Controllers\Controller;
use App\Models\Commodity;
use App\Models\CommodityUnit;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Partnership;
use App\Models\PartnershipDeliverySchedule;
use App\Models\Payment;
use App\Models\SupplierPerformanceScore;
use App\Models\SupplierProduct;
use App\Models\SupplierProfile;
use App\Models\SupplyPlan;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class KatalogController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $category = $request->input('category');
        $maxPrice = $request->input('max_price');
        $stockStatus = $request->input('stock_status');
        $isHalal = $request->boolean('is_halal');
        $sort = $request->input('sort', 'terbaru');

        $likeOp = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
        $query = SupplierProduct::with(['supplier.user', 'supplier.latestPerformanceScore', 'commodity']);

        if ($search) {
            $query->where(function ($q) use ($search, $likeOp) {
                $q->whereHas('commodity', function ($cq) use ($search, $likeOp) {
                    $cq->where('name', $likeOp, '%'.$search.'%')
                        ->orWhere('category', $likeOp, '%'.$search.'%');
                })->orWhereHas('supplier', function ($sq) use ($search, $likeOp) {
                    $sq->where('company_name', $likeOp, '%'.$search.'%')
                        ->orWhere('warehouse_address', $likeOp, '%'.$search.'%')
                        ->orWhere('delivery_area', $likeOp, '%'.$search.'%');
                })->orWhere('description', $likeOp, '%'.$search.'%')
                    ->orWhere('quality_grade', $likeOp, '%'.$search.'%');
            });
        }

        // Category filter
        if ($category && $category !== 'Semua Kategori') {
            $query->whereHas('commodity', function ($q) use ($category) {
                $q->where('category', $category);
            });
        }

        // Stock Status filter
        if ($stockStatus && $stockStatus !== 'Semua Status') {
            $query->where('stock_status', $stockStatus);
        }

        // Halal filter
        if ($isHalal) {
            $query->where('is_halal_certified', true);
        }

        // Max Price filter
        if ($maxPrice && is_numeric($maxPrice)) {
            $query->where('price', '<=', $maxPrice);
        }

        // Sorting
        switch ($sort) {
            case 'harga_terendah':
                $query->orderBy('price', 'asc');
                break;
            case 'harga_tertinggi':
                $query->orderBy('price', 'desc');
                break;
            case 'kapasitas_terbesar':
                $query->orderBy('stock_capacity', 'desc');
                break;
            case 'terbaru':
            default:
                $query->latest();
                break;
        }

        $products = $query->paginate(12)->withQueryString();

        // Get unique categories for the filter (cached to avoid redundant queries to remote DB)
        $categories = Cache::remember('catalog_categories', 3600, function () {
            return Commodity::select('category')->distinct()->pluck('category');
        });

        // Ambil profil UMKM user yang sedang login atau fallback untuk demo UMKM
        $umkm = Auth::user()?->umkmProfile;
        if (! $umkm && ! Auth::check()) {
            $dummyUmkmUser = User::where('role', 'umkm')->first();
            $umkm = $dummyUmkmUser?->umkmProfile;
        }

        $allPartnerships = $umkm ? Partnership::with([
            'supplier.latestPerformanceScore',
            'latestSupplierPerformanceScore',
            'deliverySchedules.supplierProduct.commodity',
        ])
            ->where('umkm_id', $umkm->id)
            ->latest()
            ->get() : collect();

        $partnershipIds = $allPartnerships->pluck('id');

        // Eliminasi N+1: Ambil semua order hari ini sekaligus dalam 1 query
        $publishedPartnershipIdsToday = ($allPartnerships->isNotEmpty())
            ? Order::whereIn('partnership_id', $partnershipIds)
                ->whereDate('created_at', now()->toDateString())
                ->pluck('partnership_id')
                ->flip()
                ->all()
            : [];

        $autoOrdersList = [];
        $supplyPlans = [];

        foreach ($allPartnerships as $partnership) {
            $supplierName = $partnership->supplier?->company_name ?? 'Pemasok Mitra';
            $isApproved = in_array(strtolower($partnership->status), ['approved', 'active', 'disetujui']);
            $isRejected = in_array(strtolower($partnership->status), ['rejected', 'ditolak']);
            $collabStatus = $isApproved ? 'DISETUJUI' : ($isRejected ? 'DITOLAK' : 'DALAM_REVIEW');

            foreach ($partnership->deliverySchedules as $schedule) {
                $product = $schedule->supplierProduct;
                $commodityName = $product?->commodity?->name ?? 'Komoditas Pasokan';
                $pricePerUnit = (float) ($product?->price ?? 0);
                $volume = (float) $schedule->volume_per_delivery;
                $totalContractVolume = (float) ($schedule->total_contract_volume ?: ($volume * 4));
                $totalPerDelivery = $volume * $pricePerUnit;

                // Format Auto Orders (Hanya untuk yang sudah diapprove / aktif)
                if ($isApproved) {
                    $deliveryDays = is_array($schedule->delivery_days) ? $schedule->delivery_days : [];
                    if (empty($deliveryDays)) {
                        if (str_contains($schedule->frequency_type, 'Senin & Kamis')) {
                            $deliveryDays = ['Senin', 'Kamis'];
                        } elseif (str_contains($schedule->frequency_type, 'Senin, Rabu, Jumat') || str_contains($schedule->frequency_type, '3x Seminggu')) {
                            $deliveryDays = ['Senin', 'Rabu', 'Jumat'];
                        } elseif (str_contains($schedule->frequency_type, 'Setiap Hari')) {
                            $deliveryDays = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
                        } elseif (str_contains($schedule->frequency_type, '1x Seminggu')) {
                            $deliveryDays = ['Senin'];
                        } else {
                            $deliveryDays = ['Senin', 'Kamis'];
                        }
                    }

                    $daysMap = ['Minggu' => 0, 'Senin' => 1, 'Selasa' => 2, 'Rabu' => 3, 'Kamis' => 4, 'Jumat' => 5, 'Sabtu' => 6];
                    $targetDayNumbers = array_map(fn ($d) => $daysMap[$d] ?? 1, $deliveryDays);

                    $deliveryDaysStr = implode(', ', $deliveryDays);
                    $timeStr = $schedule->delivery_time ? substr((string) $schedule->delivery_time, 0, 5).' WIB' : '05:30 WIB';
                    $scheduleNote = 'Setiap '.$deliveryDaysStr.' ('.$timeStr.')';

                    // Cari hari pengiriman berikutnya (mulai dari besok)
                    $nextDate = null;
                    for ($i = 1; $i <= 7; $i++) {
                        $checkDate = now()->addDays($i);
                        if (in_array($checkDate->dayOfWeek, $targetDayNumbers)) {
                            $nextDate = $checkDate;
                            break;
                        }
                    }

                    $nextScheduledPublishText = $nextDate
                        ? $nextDate->translatedFormat('l, d M Y').' ('.$timeStr.')'
                        : now()->addDays(3)->translatedFormat('l, d M Y').' ('.$timeStr.')';

                    $nextDeliveryDate = in_array(now()->dayOfWeek, $targetDayNumbers)
                        ? now()->format('d/m/Y')
                        : ($nextDate ? $nextDate->format('d/m/Y') : now()->addDay()->format('d/m/Y'));

                    $isPublishedToday = isset($publishedPartnershipIdsToday[$partnership->id]);

                    $autoOrdersList[] = [
                        'id' => 'AUTO-'.strtoupper(substr($schedule->id, 0, 8)),
                        'scheduleId' => $schedule->id,
                        'partnershipId' => $partnership->id,
                        'contractId' => 'CTR-'.strtoupper(substr($partnership->id, 0, 8)),
                        'supplierName' => $supplierName,
                        'commodity' => $commodityName,
                        'qty' => $volume,
                        'unit' => $schedule->unit,
                        'pricePerUnit' => $pricePerUnit,
                        'total' => $totalPerDelivery,
                        'deliverySchedule' => $scheduleNote,
                        'deliveryDays' => $deliveryDays,
                        'deliveryTime' => $timeStr,
                        'nextDelivery' => $nextDeliveryDate,
                        'nextScheduledPublish' => $nextScheduledPublishText,
                        'isPublishedToday' => $isPublishedToday,
                        'autoStatus' => $schedule->is_auto_order_active ? 'TERJADWAL' : 'NON-AKTIF',
                        'notes' => $schedule->special_instructions ?? ($partnership->submission_details ?? 'Pasokan terikat kontrak kemitraan resmi.'),
                    ];
                }
            }
        }

        // Ambil data riwayat Collaborative Supply Planning riil dari database untuk UMKM ini
        $realSupplyPlans = ($umkm && $partnershipIds->isNotEmpty())
            ? SupplyPlan::with(['partnership.supplier'])
                ->whereIn('partnership_id', $partnershipIds)
                ->latest()
                ->get()
            : collect();

        $formattedSupplyPlans = $realSupplyPlans->map(function ($plan) {
            return [
                'id' => 'CSP-'.strtoupper(substr($plan->id, 0, 8)),
                'uuid' => $plan->id,
                'commodity' => $plan->commodity_name ?? 'Bahan Baku',
                'volume' => (float) $plan->estimated_quantity,
                'confirmed_volume' => $plan->confirmed_quantity ? (float) $plan->confirmed_quantity : null,
                'unit' => $plan->unit ?? 'kg',
                'targetMonth' => $plan->period_month_year,
                'supplier' => $plan->partnership?->supplier?->company_name ?? 'Pemasok Mitra',
                'status' => $plan->supplier_confirmation_status ?? 'MENUNGGU_KONFIRMASI',
                'note' => $plan->notes,
                'supplier_notes' => $plan->supplier_notes,
                'created_at' => $plan->created_at?->format('d/m/Y H:i'),
                'can_delete' => $plan->supplier_confirmation_status === 'MENUNGGU_KONFIRMASI',
            ];
        })->toArray();

        // Gabungkan dengan supply plans dari schedule (jika belum ada di supply_plans)
        $supplyPlans = ! empty($formattedSupplyPlans) ? $formattedSupplyPlans : $supplyPlans;

        // Ambil daftar supplier aktif untuk dropdown form Collaborative Supply Planning (cached)
        $availableSuppliers = Cache::remember('catalog_available_suppliers', 600, function () {
            return SupplierProfile::whereHas('user', function ($q) {
                $q->where('status', 'active');
            })->orderBy('company_name')->get();
        });

        // Ambil master komoditas & satuan untuk opsi pilihan kebutuhan UMKM (cached)
        $availableCommodities = Cache::remember('catalog_available_commodities', 3600, function () {
            return Commodity::orderBy('name')->get();
        });
        $availableUnits = Cache::remember('catalog_available_units', 3600, function () {
            return CommodityUnit::orderBy('name')->get();
        });

        // Eliminasi query redundan: ekstrak langsung dari memory $allPartnerships
        $appliedProductIds = $allPartnerships
            ->reject(fn ($p) => in_array(strtolower((string) $p->status), ['rejected', 'ditolak', 'cancelled', 'dibatalkan']))
            ->flatMap(fn ($p) => $p->deliverySchedules->pluck('supplier_product_id'))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // Format daftar riwayat pengajuan kemitraan/permintaan UMKM dengan batasan edit < 30 menit
        $partnershipRequests = ($allPartnerships->isNotEmpty())
            ? $allPartnerships->map(function ($p) {
                $schedule = $p->deliverySchedules->first();
                $product = $schedule?->supplierProduct;
                $commodity = $product?->commodity;
                $diffMinutes = $p->created_at ? abs(now()->diffInMinutes($p->created_at)) : 999;
                $diffSeconds = $p->created_at ? max(0, (int) (1800 - abs(now()->diffInSeconds($p->created_at)))) : 0;
                $isPending = in_array(strtolower((string) $p->status), ['pending', 'menunggu', 'dalam_review']);
                $canEdit = $isPending && $diffMinutes < 30;
                $remainingMinutes = ceil($diffSeconds / 60);

                return [
                    'id' => $p->id,
                    'contract_code' => $p->contract_code,
                    'supplier_id' => $p->supplier_id,
                    'supplier_name' => $p->supplier?->company_name ?? 'Pemasok Mitra',
                    'supplier_product_id' => $schedule?->supplier_product_id,
                    'commodity_name' => $commodity?->name ?? 'Komoditas Pasokan',
                    'commodity_image' => $commodity?->image_url ?? $product?->image_url,
                    'price' => (float) ($product?->price ?? 0),
                    'status' => $p->status,
                    'volume_per_delivery' => (float) ($schedule?->volume_per_delivery ?? 0),
                    'total_contract_volume' => (float) ($schedule?->total_contract_volume ?? 0),
                    'unit' => $schedule?->unit ?? 'kg',
                    'frequency_type' => $schedule?->frequency_type ?? '-',
                    'delivery_days' => (array) ($schedule?->delivery_days ?? []),
                    'delivery_time' => $schedule?->delivery_time ? substr((string) $schedule->delivery_time, 0, 5) : '05:30',
                    'start_date' => $schedule?->start_date?->format('Y-m-d'),
                    'start_date_formatted' => $schedule?->start_date?->translatedFormat('d M Y') ?? '-',
                    'is_auto_order_active' => (bool) ($schedule?->is_auto_order_active ?? true),
                    'quality_terms' => $p->quality_terms,
                    'clean_notes' => $p->clean_notes,
                    'submission_details' => $p->submission_details,
                    'created_at' => $p->created_at?->translatedFormat('d M Y, H:i') ?? '-',
                    'created_at_human' => $p->created_at?->diffForHumans() ?? '-',
                    'can_edit' => $canEdit,
                    'diff_minutes' => $diffMinutes,
                    'remaining_minutes' => $remainingMinutes,
                    'remaining_seconds' => $diffSeconds,
                ];
            })
            : collect();

        // Hitung jumlah validasi & backorder dalam 1 query agregat terkonsolidasi (menghemat 3 round-trip ke DB)
        $actionCounts = ($umkm && $partnershipIds->isNotEmpty())
            ? Order::whereIn('partnership_id', $partnershipIds)
                ->selectRaw("
                    COUNT(CASE WHEN order_status IN ('MENUNGGU_KONFIRMASI_BARANG', 'DIKIRIM', 'shipped', 'partially_shipped') AND EXISTS (
                        SELECT 1 FROM shipment_confirmations sc WHERE sc.order_id = orders.id AND sc.shipment_batch = 1 AND sc.shipped_at IS NOT NULL AND sc.received_at IS NULL
                    ) THEN 1 END) as pending_validation,
                    COUNT(CASE WHEN order_status = '".Order::STATUS_WAITING_BACKORDER_APPROVAL."' OR EXISTS (
                        SELECT 1 FROM shipment_confirmations sc WHERE sc.order_id = orders.id AND sc.backorder_action = 'create_backorder' AND sc.backorder_approval_status = 'pending'
                    ) THEN 1 END) as pending_backorder_approval,
                    COUNT(CASE WHEN order_status IN ('".Order::STATUS_BACKORDER_PENDING."', '".Order::STATUS_WAITING_BACKORDER_APPROVAL."') OR (
                        order_status IN ('shipped', 'partially_shipped') AND EXISTS (
                            SELECT 1 FROM shipment_confirmations sc WHERE sc.order_id = orders.id AND sc.backorder_action = 'create_backorder' AND sc.backorder_approval_status != 'rejected'
                        ) AND NOT EXISTS (
                            SELECT 1 FROM shipment_confirmations sc WHERE sc.order_id = orders.id AND sc.shipment_batch = 2 AND sc.received_at IS NOT NULL
                        )
                    ) THEN 1 END) as pending_backorder
                ")->first()
            : null;

        $pendingValidationCount = (int) ($actionCounts->pending_validation ?? 0);
        $pendingBackorderApprovalCount = (int) ($actionCounts->pending_backorder_approval ?? 0);
        $pendingBackorderCount = (int) ($actionCounts->pending_backorder ?? 0);

        // Ambil daftar pesanan backorder untuk UMKM ini (dengan pagination)
        $backorderOrders = ($umkm && $partnershipIds->isNotEmpty()) ? Order::with([
            'partnership.supplier',
            'items.supplierProduct.commodity',
            'shipmentConfirmations',
            'payments',
        ])
            ->whereIn('partnership_id', $partnershipIds)
            ->where(function ($q) {
                $q->whereIn('order_status', [Order::STATUS_BACKORDER_PENDING, Order::STATUS_WAITING_BACKORDER_APPROVAL])
                    ->orWhere(function ($oq) {
                        $oq->whereIn('order_status', ['shipped', 'partially_shipped', Order::STATUS_COMPLETED_PARTIAL, Order::STATUS_CANCELLED])
                            ->whereHas('shipmentConfirmations', function ($sq) {
                                $sq->where(function ($bsq) {
                                    $bsq->whereIn('backorder_action', ['create_backorder', 'no_backorder'])
                                        ->orWhereIn('backorder_approval_status', ['pending', 'approved', 'rejected']);
                                });
                            });
                    });
            })
            ->latest('created_at')
            ->paginate(10, ['*'], 'backorders_page')
            ->withQueryString() : new LengthAwarePaginator([], 0, 10);

        // Ambil daftar pesanan aktif & pengiriman untuk UMKM ini (dengan pagination)
        $dbOrders = ($umkm && $partnershipIds->isNotEmpty()) ? Order::with([
            'partnership.supplier',
            'items.supplierProduct.commodity',
            'shipmentConfirmations',
        ])
            ->whereIn('partnership_id', $partnershipIds)
            ->whereIn('order_status', [
                'MENUNGGU_KONFIRMASI_BARANG',
                'DIKIRIM',
                'shipped',
                'partially_shipped',
                'backorder_pending',
                'BARANG_DITERIMA',
                'completed',
                'completed_partial',
            ])
            ->latest()
            ->paginate(10, ['*'], 'orders_page')
            ->withQueryString() : new LengthAwarePaginator([], 0, 10);

        // Hitung jumlah tagihan batch yang belum dibayar dalam 1 query count efisien
        $pendingPaymentCount = ($umkm && $partnershipIds->isNotEmpty()) ? Payment::whereHas('order', function ($q) use ($partnershipIds) {
            $q->whereIn('partnership_id', $partnershipIds);
        })->whereNotIn('payment_status', ['PAID', 'SETTLEMENT', 'CAPTURE', 'SUCCESS', 'LUNAS', 'paid'])->count() : 0;

        // Ambil seluruh invoice/tagihan per batch untuk UMKM ini (dengan pagination)
        $batchInvoices = ($umkm && $partnershipIds->isNotEmpty()) ? Payment::with([
            'order.partnership.supplier',
            'order.items.supplierProduct.commodity',
            'shipmentConfirmation',
        ])->whereHas('order', function ($q) use ($partnershipIds) {
            $q->whereIn('partnership_id', $partnershipIds);
        })
            ->latest('created_at')
            ->paginate(10, ['*'], 'invoices_page')
            ->withQueryString() : new LengthAwarePaginator([], 0, 10);

        // Ambil daftar pesanan lunas untuk tab Rating & Ulasan (UMKM yang sedang login)
        $reviewOrders = ($umkm && $partnershipIds->isNotEmpty()) ? Order::with([
            'partnership.supplier',
            'items.supplierProduct.commodity',
            'review',
            'payments',
        ])
            ->whereIn('partnership_id', $partnershipIds)
            ->where(function ($q) {
                $q->whereHas('payments', function ($pq) {
                    $pq->whereIn(DB::raw("UPPER(COALESCE(payment_status, ''))"), ['PAID', 'SETTLEMENT', 'CAPTURE', 'SUCCESS', 'LUNAS']);
                })->orWhereIn('order_status', ['completed', 'BARANG_DITERIMA', 'completed_partial']);
            })
            ->latest('created_at')
            ->get() : collect();

        // Hitung total kontrak kemitraan aktif dan pending untuk UMKM ini secara riil (0 jika akun baru)
        $realActiveContractsCount = $allPartnerships->filter(fn ($p) => in_array(strtolower((string) $p->status), ['active', 'approved', 'accepted', 'disetujui']))->count();
        $realPendingContractsCount = $allPartnerships->filter(fn ($p) => in_array(strtolower((string) $p->status), ['pending', 'menunggu', 'dalam_review']))->count();

        // Tidak ada fallback dummy untuk akun baru (jika baru daftar maka murni 0)
        $activeContractsCount = $realActiveContractsCount;
        $pendingContractsCount = $realPendingContractsCount;
        $totalContractsCount = $activeContractsCount + $pendingContractsCount;

        // Distribusi komoditas dalam kontrak aktif (kosong jika belum ada kontrak)
        $activeContractCommodities = [];
        foreach ($allPartnerships as $p) {
            if (in_array(strtolower((string) $p->status), ['active', 'approved', 'accepted', 'disetujui'])) {
                foreach ($p->deliverySchedules as $ds) {
                    $cName = $ds->supplierProduct?->commodity?->name ?? 'Bahan Pokok';
                    $activeContractCommodities[$cName] = ($activeContractCommodities[$cName] ?? 0) + 1;
                }
            }
        }

        // =========================================================================
        // DASHBOARD ANALISIS PENGADAAN (DATA RIIL SESUAI USER LOGIN / SUPABASE)
        // =========================================================================
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        $startOfLastMonth = now()->subMonth()->startOfMonth();
        $endOfLastMonth = now()->subMonth()->endOfMonth();

        // Konsolidasi Query 1: Hitung statistik pesanan & pengeluaran (Bulan ini & Lalu) dalam 1 query agregat murni
        if ($umkm && $partnershipIds->isNotEmpty()) {
            $orderCounts = Order::whereIn('partnership_id', $partnershipIds)
                ->selectRaw("
                    COUNT(CASE WHEN order_status IN ('pending', 'confirmed', 'MENUNGGU_KONFIRMASI_BARANG', 'DIKIRIM', 'shipped', 'partially_shipped', '".Order::STATUS_WAITING_BACKORDER_APPROVAL."', '".Order::STATUS_BACKORDER_PENDING."') THEN 1 END) as active_count,
                    COUNT(CASE WHEN order_status IN ('completed', 'BARANG_DITERIMA', '".Order::STATUS_COMPLETED_PARTIAL."') THEN 1 END) as completed_count,
                    COUNT(CASE WHEN order_status = '".Order::STATUS_CANCELLED."' THEN 1 END) as cancelled_count,
                    COALESCE(SUM(CASE WHEN created_at >= ? AND created_at <= ? AND (order_status IN ('completed', 'BARANG_DITERIMA', '".Order::STATUS_COMPLETED_PARTIAL."') OR EXISTS (SELECT 1 FROM payments p WHERE p.order_id = orders.id AND UPPER(p.payment_status) IN ('PAID', 'SETTLEMENT', 'CAPTURE', 'SUCCESS', 'LUNAS'))) THEN total_amount ELSE 0 END), 0) as spend_this_month,
                    COALESCE(SUM(CASE WHEN created_at >= ? AND created_at <= ? AND (order_status IN ('completed', 'BARANG_DITERIMA', '".Order::STATUS_COMPLETED_PARTIAL."') OR EXISTS (SELECT 1 FROM payments p WHERE p.order_id = orders.id AND UPPER(p.payment_status) IN ('PAID', 'SETTLEMENT', 'CAPTURE', 'SUCCESS', 'LUNAS'))) THEN total_amount ELSE 0 END), 0) as spend_last_month
                ", [$startOfMonth, $endOfMonth, $startOfLastMonth, $endOfLastMonth])
                ->first();

            $activeOrdersCount = (int) ($orderCounts->active_count ?? 0);
            $completedOrdersCount = (int) ($orderCounts->completed_count ?? 0);
            $cancelledOrdersCount = (int) ($orderCounts->cancelled_count ?? 0);
            $totalPengeluaranBulanIni = (float) ($orderCounts->spend_this_month ?? 0.0);
            $totalPengeluaranBulanLalu = (float) ($orderCounts->spend_last_month ?? 0.0);
        } else {
            $activeOrdersCount = 0;
            $completedOrdersCount = 0;
            $cancelledOrdersCount = 0;
            $totalPengeluaranBulanIni = 0.0;
            $totalPengeluaranBulanLalu = 0.0;
        }

        $persentasePenghematan = 0.0;
        if ($totalPengeluaranBulanLalu > 0) {
            $persentasePenghematan = round((($totalPengeluaranBulanLalu - $totalPengeluaranBulanIni) / $totalPengeluaranBulanLalu) * 100, 1);
        }

        // 2. Total Barang Diterima Bulan Ini
        $totalDiterimaKg = ($umkm && $partnershipIds->isNotEmpty())
            ? (float) OrderItem::whereHas('order', function ($oq) use ($partnershipIds, $startOfMonth, $endOfMonth) {
                $oq->whereIn('partnership_id', $partnershipIds)
                    ->whereBetween('created_at', [$startOfMonth, $endOfMonth]);
            })
                ->sum('quantity_delivered')
            : 0.0;

        // 3. Skor Rata-Rata On-Time Delivery Bulan Ini
        $onTimeDeliveryRate = ($umkm && $partnershipIds->isNotEmpty())
            ? (float) (SupplierPerformanceScore::whereIn('partnership_id', $partnershipIds)
                ->avg('on_time_delivery_score') ?? 0.0)
            : 0.0;

        // 4. Target Volume Kontrak Bulan Depan
        $targetBulanDepan = ($umkm && $partnershipIds->isNotEmpty())
            ? (float) PartnershipDeliverySchedule::whereIn('partnership_id', $partnershipIds)
                ->whereHas('partnership', fn ($pq) => $pq->whereIn(DB::raw('LOWER(status)'), ['active', 'approved', 'disetujui']))
                ->sum('total_contract_volume')
            : 0.0;

        // 5. Tren Pengadaan Bulanan (Mei - November 2026)
        $trendMonths = [
            '2026-05' => 'MEI',
            '2026-06' => 'JUN',
            '2026-07' => 'JUL',
            '2026-08' => 'AGU',
            '2026-09' => 'SEP',
            '2026-10' => 'OKT',
            '2026-11' => 'NOV',
        ];

        $monthFormatSql = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : "TO_CHAR(created_at, 'YYYY-MM')";

        // Konsolidasi Query 2: Realisasi volume & Total biaya bulanan dalam 1 query JOIN ringkas
        $monthlyAgg = ($umkm && $partnershipIds->isNotEmpty())
            ? OrderItem::whereHas('order', function ($oq) use ($partnershipIds) {
                $oq->whereIn('partnership_id', $partnershipIds);
            })
                ->selectRaw("{$monthFormatSql} as month_key, SUM(quantity_delivered) as delivered_vol, SUM(subtotal) as cost_total")
                ->groupBy(DB::raw($monthFormatSql))
                ->get()
            : collect();

        $monthlyRealization = $monthlyAgg->pluck('delivered_vol', 'month_key')->toArray();
        $monthlyCost = $monthlyAgg->pluck('cost_total', 'month_key')->toArray();
                ->selectRaw("{$monthFormatSql} as month_key, SUM(quantity_delivered) as delivered_vol")
                ->groupBy(DB::raw($monthFormatSql))
                ->pluck('delivered_vol', 'month_key')
                ->toArray()
            : [];

        $monthlyCost = ($umkm && $partnershipIds->isNotEmpty())
            ? OrderItem::whereHas('order', function ($oq) use ($partnershipIds) {
                $oq->whereIn('partnership_id', $partnershipIds);
            })
                ->selectRaw("{$monthFormatSql} as month_key, SUM(subtotal) as cost_total")
                ->groupBy(DB::raw($monthFormatSql))
                ->pluck('cost_total', 'month_key')
                ->toArray()
            : [];

        $trendsData = [];
        $totalDeliveredAllMonths = 0.0;
        foreach ($trendMonths as $key => $label) {
            $vol = (float) ($monthlyRealization[$key] ?? 0.0);
            $cost = (float) ($monthlyCost[$key] ?? 0.0);
            $totalDeliveredAllMonths += $vol;
            $trendsData[] = [
                'key' => $key,
                'label' => $label,
                'volume' => $vol,
                'cost' => $cost,
            ];
        }

        $currentMonthKey = now()->format('Y-m');
        $realisasiPasokanBulanIni = (float) ($monthlyRealization[$currentMonthKey] ?? ($monthlyRealization['2026-10'] ?? 0.0));

        $dashboardOrders = ($umkm && $partnershipIds->isNotEmpty())
            ? Order::with([
                'partnership.supplier',
                'items.supplierProduct.commodity',
                'payment',
                'payments',
                'review',
            ])
                ->whereIn('partnership_id', $partnershipIds)
                ->latest('created_at')
                ->limit(10)
                ->get()
                ->map(function ($ord) {
                    $firstItem = $ord->items->first();
                    $commodityName = $firstItem?->supplierProduct?->commodity?->name ?? 'Komoditas Pasokan';
                    $unit = $firstItem?->supplierProduct?->unit ?? $firstItem?->supplierProduct?->commodity?->unit ?? 'kg';
                    $deliveredQty = (float) $ord->items->sum('quantity_delivered');
                    $orderedQty = (float) $ord->items->sum('quantity');
                    $qtyDisplay = $deliveredQty > 0 ? $deliveredQty : $orderedQty;

                    $latestPayment = $ord->payments->last() ?? $ord->payment;
                    $paymentStatus = strtoupper($latestPayment?->payment_status ?? ($ord->order_status === 'completed' ? 'PAID' : 'UNPAID'));

                    return [
                        'id' => 'TRX-'.strtoupper(substr((string) $ord->id, 0, 8)),
                        'db_id' => $ord->id,
                        'invoiceNumber' => 'INV-'.strtoupper(substr((string) $ord->id, 0, 8)),
                        'supplierName' => $ord->partnership?->supplier?->company_name ?? 'Mitra Pemasok',
                        'supplierPhone' => $ord->partnership?->supplier?->phone ?? '-',
                        'supplierAddress' => $ord->partnership?->supplier?->warehouse_address ?? 'Malang Raya & Jawa Timur',
                        'commodity' => $commodityName,
                        'approvedQty' => $qtyDisplay,
                        'unit' => $unit,
                        'total' => (float) $ord->total_amount,
                        'date' => $ord->created_at?->format('d/m/Y') ?? '-',
                        'paymentStatus' => $paymentStatus,
                        'paidAt' => $latestPayment?->paid_at?->format('d/m/Y H:i') ?? ($paymentStatus === 'PAID' ? ($ord->created_at?->format('d/m/Y H:i') ?? '-') : null),
                        'orderStatus' => $ord->order_status ?? 'completed',
                        'rating' => $ord->review?->rating ?? null,
                        'review' => $ord->review?->review ?? '',
                    ];
                })
                ->values()
            : collect();

        // Ambil data performa mitra pemasok untuk Tab Monitoring & Evaluasi Kemitraan (Hanya kemitraan aktif)
        $contractedSuppliersPerformance = $allPartnerships
            ->filter(fn ($p) => in_array(strtolower((string) $p->status), ['active', 'approved', 'disetujui']))
            ->map(function ($p) {
                $sup = $p->supplier;
                $score = $p->latestSupplierPerformanceScore ?? $sup?->latestPerformanceScore;

                return [
                    'partnership' => $p,
                    'supplier' => $sup,
                    'score' => $score,
                    'commodity_name' => $p->deliverySchedules->first()?->supplierProduct?->commodity?->name ?? 'Komoditas Pasokan',
                    'volume_per_delivery' => $p->deliverySchedules->sum('volume_per_delivery'),
                    'unit' => $p->deliverySchedules->first()?->unit ?? 'kg',
                    'status' => $p->status,
                ];
            })
            ->values();

        // State fingerprint disederhanakan tanpa beban query remote tambahan
        $stateFingerprint = '';

        return view('umkm.katalog.index', compact(
            'products',
            'categories',
            'search',
            'category',
            'maxPrice',
            'stockStatus',
            'isHalal',
            'sort',
            'autoOrdersList',
            'supplyPlans',
            'availableSuppliers',
            'availableCommodities',
            'availableUnits',
            'appliedProductIds',
            'pendingValidationCount',
            'pendingBackorderCount',
            'pendingBackorderApprovalCount',
            'backorderOrders',
            'pendingPaymentCount',
            'batchInvoices',
            'dbOrders',
            'activeOrdersCount',
            'completedOrdersCount',
            'activeContractsCount',
            'pendingContractsCount',
            'totalContractsCount',
            'activeContractCommodities',
            'reviewOrders',
            'contractedSuppliersPerformance',
            'totalPengeluaranBulanIni',
            'totalPengeluaranBulanLalu',
            'persentasePenghematan',
            'totalDiterimaKg',
            'onTimeDeliveryRate',
            'targetBulanDepan',
            'trendsData',
            'totalDeliveredAllMonths',
            'realisasiPasokanBulanIni',
            'dashboardOrders',
            'stateFingerprint',
            'partnershipRequests',
        ));
    }

    public function checkUpdates(Request $request)
    {
        $umkm = Auth::user()?->umkmProfile;
        if (! $umkm) {
            return response()->json(['updated' => false, 'fingerprint' => '']);
        }

        $allPartnerships = Partnership::where('umkm_id', $umkm->id)->get();
        $partnershipIds = $allPartnerships->pluck('id');

        $latestOrderUpdate = Order::whereIn('partnership_id', $partnershipIds)->max('updated_at');
        $latestPaymentUpdate = Payment::whereHas('order', fn ($q) => $q->whereIn('partnership_id', $partnershipIds))->max('updated_at');
        $latestSupplyPlanUpdate = SupplyPlan::whereIn('partnership_id', $partnershipIds)->max('updated_at');
        $latestPartnershipUpdate = $allPartnerships->max('updated_at');
        $ordersCount = Order::whereIn('partnership_id', $partnershipIds)->count();
        $partnershipsCount = $allPartnerships->count();

        $stateFingerprint = md5(implode('|', [
            $latestOrderUpdate,
            $latestPaymentUpdate,
            $latestSupplyPlanUpdate,
            $latestPartnershipUpdate,
            $ordersCount,
            $partnershipsCount,
        ]));

        return response()->json([
            'updated' => true,
            'fingerprint' => $stateFingerprint,
            'orders_count' => $ordersCount,
            'partnerships_count' => $partnershipsCount,
            'timestamp' => now()->timestamp,
        ]);
    }

    public function dashboardAnalytics(Request $request)
    {
        $umkm = Auth::user()?->umkmProfile;
        if (! $umkm && $request->has('umkm_id')) {
            $umkm = UmkmProfile::find($request->input('umkm_id'));
        }

        $allPartnerships = $umkm ? Partnership::where('umkm_id', $umkm->id)->get() : collect();
        $partnershipIds = $allPartnerships->pluck('id');

        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        $startOfLastMonth = now()->subMonth()->startOfMonth();
        $endOfLastMonth = now()->subMonth()->endOfMonth();

        $curSpend = ($umkm && $partnershipIds->isNotEmpty())
            ? (float) Order::whereIn('partnership_id', $partnershipIds)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->where(function ($q) {
                    $q->whereHas('payments', fn ($pq) => $pq->whereIn(DB::raw("UPPER(COALESCE(payment_status, ''))"), ['PAID', 'SETTLEMENT', 'CAPTURE', 'SUCCESS', 'LUNAS']))
                        ->orWhereIn('order_status', ['completed', 'BARANG_DITERIMA', 'completed_partial']);
                })->sum('total_amount')
            : 0.0;

        $prevSpend = ($umkm && $partnershipIds->isNotEmpty())
            ? (float) Order::whereIn('partnership_id', $partnershipIds)
                ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
                ->where(function ($q) {
                    $q->whereHas('payments', fn ($pq) => $pq->whereIn(DB::raw("UPPER(COALESCE(payment_status, ''))"), ['PAID', 'SETTLEMENT', 'CAPTURE', 'SUCCESS', 'LUNAS']))
                        ->orWhereIn('order_status', ['completed', 'BARANG_DITERIMA', 'completed_partial']);
                })->sum('total_amount')
            : 0.0;

        $savingsPct = $prevSpend > 0 ? round((($prevSpend - $curSpend) / $prevSpend) * 100, 1) : 0.0;

        $activeOrders = ($umkm && $partnershipIds->isNotEmpty())
            ? Order::whereIn('partnership_id', $partnershipIds)
                ->whereIn('order_status', ['pending', 'confirmed', 'MENUNGGU_KONFIRMASI_BARANG', 'DIKIRIM', 'shipped', 'partially_shipped', Order::STATUS_WAITING_BACKORDER_APPROVAL, Order::STATUS_BACKORDER_PENDING])
                ->count()
            : 0;

        $needConfirmation = ($umkm && $partnershipIds->isNotEmpty())
            ? Order::whereIn('partnership_id', $partnershipIds)
                ->whereIn('order_status', ['MENUNGGU_KONFIRMASI_BARANG', 'DIKIRIM', 'shipped', 'partially_shipped'])
                ->whereHas('shipmentConfirmations', function ($sq) {
                    $sq->where('shipment_batch', 1)->whereNotNull('shipped_at')->whereNull('received_at');
                })->count()
            : 0;

        $deliveredKg = ($umkm && $partnershipIds->isNotEmpty())
            ? (float) OrderItem::whereHas('order', function ($oq) use ($partnershipIds, $startOfMonth, $endOfMonth) {
                $oq->whereIn('partnership_id', $partnershipIds)->whereBetween('created_at', [$startOfMonth, $endOfMonth]);
            })->sum('quantity_delivered')
            : 0.0;

        $onTimeRate = ($umkm && $partnershipIds->isNotEmpty())
            ? (float) (SupplierPerformanceScore::whereIn('partnership_id', $partnershipIds)->avg('on_time_delivery_score') ?? 0.0)
            : 0.0;

        return response()->json([
            'success' => true,
            'data' => [
                'summary_cards' => [
                    'total_pengeluaran' => [
                        'amount' => $curSpend,
                        'formatted' => 'Rp '.number_format($curSpend, 0, ',', '.'),
                        'savings_pct' => $savingsPct,
                        'label' => $savingsPct > 0 ? "Hemat {$savingsPct}% dibanding bulan lalu" : 'Belum ada penghematan',
                    ],
                    'pesanan_aktif' => [
                        'total' => $activeOrders,
                        'need_confirmation' => $needConfirmation,
                        'label' => "{$needConfirmation} Perlu Konfirmasi Penerimaan",
                    ],
                    'total_diterima' => [
                        'quantity_kg' => $deliveredKg,
                        'formatted' => number_format($deliveredKg, 0, ',', '.').' kg',
                        'categories' => ['Daging Ayam', 'Bumbu Dapur'],
                    ],
                    'on_time_delivery' => [
                        'score_pct' => $onTimeRate,
                        'formatted' => number_format($onTimeRate, 1).'%',
                    ],
                ],
            ],
        ]);
    }

    public function ajukanKemitraan(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:supplier_profiles,id',
            'supplier_product_id' => 'required|exists:supplier_products,id',
            'volume' => 'nullable|numeric|min:0.1',
            'volume_per_delivery' => 'nullable|numeric|min:0.1',
            'total_contract_volume' => 'nullable|numeric|min:0.1',
            'unit' => 'required|string',
            'frekuensi' => 'nullable|string',
            'frequency_type' => 'nullable|string',
            'delivery_days' => 'nullable|array',
            'delivery_time' => 'nullable|string',
            'start_date' => 'nullable|date',
            'is_auto_order_active' => 'nullable|boolean',
            'submission_details' => 'nullable|string',
            'terms' => 'nullable|array',
            'terms.*' => 'nullable|string',
        ]);

        $product = SupplierProduct::find($request->input('supplier_product_id'));
        if (! $product || ! in_array($product->stock_status, ['Tersedia', 'Stok Terbatas'])) {
            return redirect()->back()->with('error', 'Mohon maaf, komoditas ini sedang tidak tersedia (stok habis).');
        }

        // MOCK LOGIN FOR DEMO PURPOSES:
        // Automatically login the seeded UMKM user if nobody is logged in
        if (! Auth::check()) {
            $dummyUmkmUser = User::where('role', 'umkm')->first();
            if ($dummyUmkmUser) {
                Auth::login($dummyUmkmUser);
            }
        }

        $umkm = Auth::user()?->umkmProfile;

        if (! $umkm) {
            return redirect()->back()->with('error', 'Anda harus memiliki profil UMKM untuk mengajukan kemitraan.');
        }

        // Validasi: Cek apakah UMKM sudah pernah mengajukan permintaan untuk bahan baku ini (belum ditolak)
        $existingPartnership = Partnership::where('umkm_id', $umkm->id)
            ->whereHas('deliverySchedules', function ($query) use ($request) {
                $query->where('supplier_product_id', $request->input('supplier_product_id'));
            })
            ->whereNotIn('status', ['rejected', 'ditolak'])
            ->first();

        if ($existingPartnership) {
            return redirect()->back()->with('error', 'Anda sudah mengajukan permintaan kemitraan untuk bahan baku ini sebelumnya.');
        }

        $volumePerDelivery = (float) ($request->input('volume_per_delivery') ?? $request->input('volume', 100));
        $frequencyType = $request->input('frequency_type') ?? $request->input('frekuensi', '2x Seminggu (Senin & Kamis)');

        $deliveryDays = $request->input('delivery_days', []);
        if (empty($deliveryDays)) {
            if (str_contains($frequencyType, 'Senin & Kamis')) {
                $deliveryDays = ['Senin', 'Kamis'];
            } elseif (str_contains($frequencyType, 'Senin, Rabu, Jumat') || str_contains($frequencyType, '3x Seminggu')) {
                $deliveryDays = ['Senin', 'Rabu', 'Jumat'];
            } elseif (str_contains($frequencyType, 'Setiap Hari')) {
                $deliveryDays = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
            } elseif (str_contains($frequencyType, '1x Seminggu')) {
                $deliveryDays = ['Senin'];
            } else {
                $deliveryDays = ['Senin', 'Kamis'];
            }
        }

        $daysPerWeek = count($deliveryDays) ?: 2;
        $totalContractVolume = $request->input('total_contract_volume') ?: ($volumePerDelivery * $daysPerWeek * 4);
        $deliveryTime = $request->input('delivery_time', '05:30:00');
        if (strlen($deliveryTime) === 5) {
            $deliveryTime .= ':00';
        }

        $frequencyTypeToSave = ($frequencyType === 'Kustom')
            ? $daysPerWeek.'x Seminggu ('.implode(', ', $deliveryDays).')'
            : $frequencyType;

        $selectedTerms = $request->input('terms', []);
        $termsList = is_array($selectedTerms)
            ? array_values(array_filter(array_map('trim', $selectedTerms)))
            : [];

        $customNote = trim((string) $request->input('submission_details', ''));

        if (! empty($termsList)) {
            $termsFormatted = '[Ketentuan Mutu: '.implode(' | ', $termsList).']';
            $finalSubmissionDetails = $customNote !== '' ? ($termsFormatted.' '.$customNote) : $termsFormatted;
        } else {
            $finalSubmissionDetails = $customNote !== '' ? $customNote : null;
        }

        $partnership = Partnership::create([
            'umkm_id' => $umkm->id,
            'supplier_id' => $request->input('supplier_id'),
            'submission_details' => $finalSubmissionDetails,
            'status' => 'pending',
        ]);

        PartnershipDeliverySchedule::create([
            'partnership_id' => $partnership->id,
            'supplier_product_id' => $request->input('supplier_product_id'),
            'volume_per_delivery' => $volumePerDelivery,
            'total_contract_volume' => $totalContractVolume,
            'unit' => $request->input('unit'),
            'frequency_type' => $frequencyTypeToSave,
            'delivery_days' => $deliveryDays,
            'delivery_time' => $deliveryTime,
            'start_date' => $request->input('start_date', now()->addDay()->toDateString()),
            'is_auto_order_active' => $request->boolean('is_auto_order_active', true),
            'special_instructions' => $finalSubmissionDetails,
        ]);

        return redirect()->route('umkm.katalog.index', ['tab' => 'riwayat-permintaan'])
            ->with('success', 'Pengajuan kemitraan pasokan & jadwal rutin berhasil dikirim! Anda dapat mengubah rincian data ini dalam waktu < 30 menit ke depan.');
    }

    /**
     * Memperbarui data pengajuan permintaan kemitraan selama batas waktu (< 30 menit).
     */
    public function updatePermintaan(Request $request, Partnership $partnership)
    {
        $umkm = Auth::user()?->umkmProfile;
        if (! $umkm && ! Auth::check()) {
            $dummyUmkmUser = User::where('role', 'umkm')->first();
            $umkm = $dummyUmkmUser?->umkmProfile;
        }

        if (! $umkm || $partnership->umkm_id !== $umkm->id) {
            abort(403, 'Akses ditolak.');
        }

        $isPending = in_array(strtolower((string) $partnership->status), ['pending', 'menunggu', 'dalam_review']);
        if (! $isPending) {
            return redirect()->back()->with('error', 'Permintaan ini sudah diproses dan tidak dapat diubah.');
        }

        $diffMinutes = $partnership->created_at ? abs(now()->diffInMinutes($partnership->created_at)) : 999;
        if ($diffMinutes >= 30) {
            return redirect()->back()->with('error', 'Batas waktu 30 menit untuk mengubah data permintaan telah berakhir. Data telah dikunci.');
        }

        $request->validate([
            'volume_per_delivery' => 'required|numeric|min:0.1',
            'frequency_type' => 'nullable|string',
            'delivery_days' => 'nullable|array',
            'delivery_time' => 'nullable|string',
            'start_date' => 'nullable|date',
            'is_auto_order_active' => 'nullable|boolean',
            'submission_details' => 'nullable|string',
            'terms' => 'nullable|array',
            'terms.*' => 'nullable|string',
        ]);

        $volumePerDelivery = (float) $request->input('volume_per_delivery');
        $frequencyType = $request->input('frequency_type', '2x Seminggu (Senin & Kamis)');

        $deliveryDays = $request->input('delivery_days', []);
        if (empty($deliveryDays)) {
            if (str_contains($frequencyType, 'Senin & Kamis')) {
                $deliveryDays = ['Senin', 'Kamis'];
            } elseif (str_contains($frequencyType, 'Senin, Rabu, Jumat') || str_contains($frequencyType, '3x Seminggu')) {
                $deliveryDays = ['Senin', 'Rabu', 'Jumat'];
            } elseif (str_contains($frequencyType, 'Setiap Hari')) {
                $deliveryDays = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
            } elseif (str_contains($frequencyType, '1x Seminggu')) {
                $deliveryDays = ['Senin'];
            } else {
                $deliveryDays = ['Senin', 'Kamis'];
            }
        }

        $daysPerWeek = count($deliveryDays) ?: 2;
        $totalContractVolume = $volumePerDelivery * $daysPerWeek * 4;
        $deliveryTime = $request->input('delivery_time', '05:30:00');
        if (strlen($deliveryTime) === 5) {
            $deliveryTime .= ':00';
        }

        $frequencyTypeToSave = ($frequencyType === 'Kustom')
            ? $daysPerWeek.'x Seminggu ('.implode(', ', $deliveryDays).')'
            : $frequencyType;

        $selectedTerms = $request->input('terms', []);
        $termsList = is_array($selectedTerms)
            ? array_values(array_filter(array_map('trim', $selectedTerms)))
            : [];

        $customNote = trim((string) $request->input('submission_details', ''));

        if (! empty($termsList)) {
            $termsFormatted = '[Ketentuan Mutu: '.implode(' | ', $termsList).']';
            $finalSubmissionDetails = $customNote !== '' ? ($termsFormatted.' '.$customNote) : $termsFormatted;
        } else {
            $finalSubmissionDetails = $customNote !== '' ? $customNote : null;
        }

        $partnership->update([
            'submission_details' => $finalSubmissionDetails,
        ]);

        $schedule = $partnership->deliverySchedules()->first();
        if ($schedule) {
            $schedule->update([
                'volume_per_delivery' => $volumePerDelivery,
                'total_contract_volume' => $totalContractVolume,
                'frequency_type' => $frequencyTypeToSave,
                'delivery_days' => $deliveryDays,
                'delivery_time' => $deliveryTime,
                'start_date' => $request->input('start_date', $schedule->start_date),
                'is_auto_order_active' => $request->boolean('is_auto_order_active', true),
                'special_instructions' => $finalSubmissionDetails,
            ]);
        }

        return redirect()->route('umkm.katalog.index', ['tab' => 'riwayat-permintaan'])
            ->with('success', 'Data pengajuan permintaan berhasil diperbarui! Batas waktu edit aktif hingga 30 menit sejak pengajuan.');
    }

    public function batalPermintaan(Request $request, Partnership $partnership)
    {
        $umkm = Auth::user()?->umkmProfile;
        if (! $umkm && ! Auth::check()) {
            $dummyUmkmUser = User::where('role', 'umkm')->first();
            $umkm = $dummyUmkmUser?->umkmProfile;
        }

        if (! $umkm || $partnership->umkm_id !== $umkm->id) {
            abort(403, 'Akses ditolak.');
        }

        $isPending = in_array(strtolower((string) $partnership->status), ['pending', 'menunggu', 'dalam_review']);
        if (! $isPending) {
            return redirect()->back()->with('error', 'Pengajuan ini telah diproses dan tidak dapat dibatalkan.');
        }

        $diffMinutes = $partnership->created_at ? abs(now()->diffInMinutes($partnership->created_at)) : 999;
        if ($diffMinutes >= 30) {
            return redirect()->back()->with('error', 'Batas waktu 30 menit untuk membatalkan pengajuan telah berakhir. Data telah dikunci untuk pemasok.');
        }

        $commodityName = $partnership->deliverySchedules->first()?->supplierProduct?->commodity?->name ?? 'Bahan Baku';

        DB::transaction(function () use ($partnership) {
            $partnership->update([
                'status' => 'cancelled',
            ]);

            $partnership->deliverySchedules()->update([
                'is_auto_order_active' => false,
            ]);
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengajuan kemitraan pasokan untuk '.$commodityName.' berhasil dibatalkan.',
            ]);
        }

        return redirect()->route('umkm.katalog.index', ['tab' => 'riwayat-permintaan'])
            ->with('success', 'Pengajuan kemitraan pasokan untuk '.$commodityName.' berhasil dibatalkan.');
    }
}
