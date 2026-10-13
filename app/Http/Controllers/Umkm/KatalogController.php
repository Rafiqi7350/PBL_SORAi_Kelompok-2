<?php

namespace App\Http\Controllers\Umkm;

use App\Http\Controllers\Controller;
use App\Models\Commodity;
use App\Models\Partnership;
use App\Models\PartnershipDeliverySchedule;
use App\Models\SupplierProduct;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        $query = SupplierProduct::with(['supplier.user', 'commodity']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('commodity', function ($cq) use ($search) {
                    $cq->where('name', 'ilike', '%'.$search.'%')
                        ->orWhere('category', 'ilike', '%'.$search.'%');
                })->orWhereHas('supplier', function ($sq) use ($search) {
                    $sq->where('company_name', 'ilike', '%'.$search.'%')
                        ->orWhere('warehouse_address', 'ilike', '%'.$search.'%')
                        ->orWhere('delivery_area', 'ilike', '%'.$search.'%');
                })->orWhere('description', 'ilike', '%'.$search.'%')
                    ->orWhere('quality_grade', 'ilike', '%'.$search.'%');
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

        // Get unique categories for the filter
        $categories = Commodity::select('category')->distinct()->pluck('category');

        // Ambil profil UMKM user yang sedang login atau fallback untuk demo UMKM
        $umkm = Auth::user()?->umkmProfile;
        if (! $umkm && ! Auth::check()) {
            $dummyUmkmUser = User::where('role', 'umkm')->first();
            $umkm = $dummyUmkmUser?->umkmProfile;
        }

        $allPartnerships = $umkm ? Partnership::with([
            'supplier',
            'deliverySchedules.supplierProduct.commodity',
        ])
            ->where('umkm_id', $umkm->id)
            ->latest()
            ->get() : collect();

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

                    $autoOrdersList[] = [
                        'id' => 'AUTO-'.strtoupper(substr($schedule->id, 0, 8)),
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
                        'isPublishedToday' => false,
                        'autoStatus' => $schedule->is_auto_order_active ? 'TERJADWAL' : 'NON-AKTIF',
                        'notes' => $schedule->special_instructions ?? ($partnership->submission_details ?? 'Pasokan terikat kontrak kemitraan resmi.'),
                    ];
                }

                // Format Collaborative Supply Planning (Semua pengajuan UMKM)
                $supplyPlans[] = [
                    'id' => 'CSP-'.strtoupper(substr($partnership->id, 0, 8)),
                    'commodity' => $commodityName,
                    'volume' => $totalContractVolume,
                    'unit' => $schedule->unit,
                    'targetMonth' => \Carbon\Carbon::parse($schedule->start_date ?? $partnership->created_at)->translatedFormat('F Y'),
                    'supplier' => $supplierName,
                    'status' => $collabStatus,
                    'note' => $isApproved
                        ? 'Kapasitas siap dipenuhi dengan pengiriman '.$schedule->frequency_type.'.'
                        : ($isRejected ? 'Pemasok belum dapat memenuhi kuota pasokan yang diajukan.' : 'Sedang dalam review kapasitas gudang/ternak pemasok.'),
                ];
            }
        }

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
            'supplyPlans'
        ));
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

        $partnership = Partnership::create([
            'umkm_id' => $umkm->id,
            'supplier_id' => $request->input('supplier_id'),
            'submission_details' => $request->input('submission_details'),
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
            'special_instructions' => $request->input('submission_details'),
        ]);

        return redirect()->route('umkm.katalog.index')->with('success', 'Pengajuan kemitraan pasokan & jadwal rutin berhasil dikirim ke pemasok!');
    }
}
