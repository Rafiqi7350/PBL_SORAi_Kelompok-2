<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Partnership;
use App\Models\SupplierInventoryItem;
use App\Models\SupplierPurchaseSuggestion;
use App\Models\SupplyPlan;
use App\Models\User;
use App\Services\Planning\SupplierDemandPlanningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SupplyPlanningController extends Controller
{
    public function __construct(
        protected SupplierDemandPlanningService $planningService
    ) {}

    private function getActiveSupplierProfile()
    {
        if (! Auth::check() || Auth::user()->role !== 'supplier') {
            $dummySupplierUser = User::where('role', 'supplier')->first();
            if ($dummySupplierUser) {
                Auth::login($dummySupplierUser);
            }
        }

        return Auth::user()?->supplierProfile;
    }

    /**
     * Tampilkan dasbor Collaborative Supply Planning & Demand Forecasting untuk pemasok.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $supplier = $this->getActiveSupplierProfile();

        if (! $supplier) {
            return redirect()->route('register.supplier')
                ->with('error', 'Silakan daftarkan profil Pemasok terlebih dahulu.');
        }

        // Filter Collaborative Supply Plans
        $statusFilter = $request->input('status');
        $periodFilter = $request->input('period');

        $query = SupplyPlan::with(['partnership.umkm.user'])
            ->whereHas('partnership', function ($q) use ($supplier) {
                $q->where('supplier_id', $supplier->id);
            });

        if ($statusFilter && $statusFilter !== 'semua') {
            $query->where('supplier_confirmation_status', $statusFilter);
        }

        if ($periodFilter && $periodFilter !== 'semua') {
            $query->where('period_month_year', $periodFilter);
        }

        $allPlans = (clone $query)->latest()->paginate(10)->withQueryString();

        // Ambil semua daftar tanpa filter untuk statistik & agregasi
        $totalBasePlans = SupplyPlan::with(['partnership.umkm'])
            ->whereHas('partnership', function ($q) use ($supplier) {
                $q->where('supplier_id', $supplier->id);
            })->get();

        $totalEstimatedVolume = $totalBasePlans->sum('estimated_quantity');
        $totalConfirmedVolume = $totalBasePlans->whereIn('supplier_confirmation_status', ['DISETUJUI', 'DISESUAIKAN'])->sum(function ($p) {
            return $p->confirmed_quantity ?: $p->estimated_quantity;
        });

        $totalUmkmCount = $totalBasePlans->pluck('partnership.umkm_id')->unique()->count();
        $pendingCount = $totalBasePlans->where('supplier_confirmation_status', 'MENUNGGU_KONFIRMASI')->count();
        $approvedCount = $totalBasePlans->whereIn('supplier_confirmation_status', ['DISETUJUI', 'DISESUAIKAN'])->count();

        $readinessRate = $totalBasePlans->count() > 0
            ? round(($approvedCount / $totalBasePlans->count()) * 100)
            : 0;

        // Agregasi Kebutuhan per Komoditas dan Periode untuk Perencanaan Pasokan
        $commodityAggregations = $totalBasePlans->groupBy(function ($item) {
            return $item->period_month_year.'___'.$item->commodity_name;
        })->map(function ($group) {
            $first = $group->first();

            return [
                'period' => $first->period_month_year,
                'commodity' => $first->commodity_name,
                'unit' => $first->unit ?? 'kg',
                'total_estimated' => $group->sum('estimated_quantity'),
                'total_confirmed' => $group->sum(fn ($i) => $i->confirmed_quantity ?: ($i->supplier_confirmation_status === 'DISETUJUI' ? $i->estimated_quantity : 0)),
                'umkm_count' => $group->pluck('partnership.umkm_id')->unique()->count(),
                'pending_items' => $group->where('supplier_confirmation_status', 'MENUNGGU_KONFIRMASI')->count(),
            ];
        })->values();

        $availablePeriods = $totalBasePlans->pluck('period_month_year')->unique()->values();

        // =========================================================================
        // DATA MODUL PERENCANAAN PERSEDIAAN & DEMAND FORECASTING PEMASOK
        // =========================================================================
        $latestForecastRun = $supplier->forecastRuns()->latest('run_at')->first();

        // Inisialisasi otomatis jika belum ada kalkulasi peramalan
        if (! $latestForecastRun) {
            $latestForecastRun = $this->planningService->generateForecastAndPlanning($supplier, '7_DAYS');
        }

        $forecastItems = $latestForecastRun
            ? $latestForecastRun->items()->with('commodity')->orderBy('forecast_date')->get()
            : collect();

        // Inventori bahan baku dan status EWS
        $inventoryItems = $supplier->inventoryItems()->with('commodity')->get();

        // Rekomendasi Pengadaan (Purchase Suggestions)
        $purchaseSuggestions = $supplier->purchaseSuggestions()
            ->with(['commodity', 'materialSource'])
            ->latest()
            ->get();

        // Multi-tenant Breakdown Kebutuhan per UMKM (Privacy-Preserving)
        $umkmBreakdowns = Partnership::where('supplier_id', $supplier->id)
            ->with(['umkm', 'deliverySchedules.supplierProduct.commodity'])
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'umkm_name' => $p->umkm?->business_name ?? 'UMKM Mitra',
                    'contract_status' => $p->status,
                    'schedules_count' => $p->deliverySchedules->count(),
                    'total_scheduled_volume' => (float) $p->deliverySchedules->sum('volume_per_delivery'),
                    'commodity_name' => $p->deliverySchedules->first()?->supplierProduct?->commodity?->name ?? 'Bahan Baku',
                    'unit' => $p->deliverySchedules->first()?->unit ?? 'kg',
                    'delivery_days' => $p->deliverySchedules->first()?->delivery_days ?? [1, 3, 5],
                ];
            });

        // Simulasi What-If default
        $whatIfSimulationDefault = $this->planningService->simulateWhatIfScenario($supplier, 20.0, 2);

        // Ringkasan Total Kebutuhan Proyeksi
        $totalProjectedDemand7Days = $forecastItems->sum('baseline_demand');
        $criticalInventoryCount = $inventoryItems->filter(fn ($i) => $i->ews_status === 'CRITICAL')->count();
        $warningInventoryCount = $inventoryItems->filter(fn ($i) => $i->ews_status === 'WARNING')->count();

        return view('supplier.supply-planning.index', compact(
            'supplier',
            'allPlans',
            'totalEstimatedVolume',
            'totalConfirmedVolume',
            'totalUmkmCount',
            'pendingCount',
            'approvedCount',
            'readinessRate',
            'commodityAggregations',
            'availablePeriods',
            'statusFilter',
            'periodFilter',
            // Data Demand Planning
            'latestForecastRun',
            'forecastItems',
            'inventoryItems',
            'purchaseSuggestions',
            'umkmBreakdowns',
            'whatIfSimulationDefault',
            'totalProjectedDemand7Days',
            'criticalInventoryCount',
            'warningInventoryCount'
        ));
    }

    /**
     * Hitung ulang proyeksi demand dan stok pengaman via endpoint realtime.
     */
    public function recalculateForecast(Request $request): JsonResponse
    {
        $supplier = $this->getActiveSupplierProfile();

        if (! $supplier) {
            return response()->json(['status' => 'error', 'message' => 'Profil pemasok tidak ditemukan.'], 403);
        }

        $horizon = (string) $request->input('horizon', '7_DAYS');
        $run = $this->planningService->generateForecastAndPlanning($supplier, $horizon);

        $items = $run->items()->with('commodity')->orderBy('forecast_date')->get();
        $inventory = $supplier->inventoryItems()->with('commodity')->get()->map(function ($inv) {
            return [
                'id' => $inv->id,
                'commodity_name' => $inv->commodity?->name ?? 'Komoditas',
                'current_stock' => (float) $inv->current_stock,
                'safety_stock' => (float) $inv->safety_stock,
                'reorder_point' => (float) $inv->reorder_point,
                'unit' => $inv->unit,
                'ews_status' => $inv->ews_status,
                'ews_label' => $inv->ews_label,
                'ews_badge_color' => $inv->ews_badge_color,
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Proyeksi demand dan ambang batas inventori berhasil dikalkulasi ulang.',
            'data' => [
                'run_id' => $run->id,
                'horizon' => $run->forecast_horizon,
                'total_projected_volume' => (float) $items->sum('baseline_demand'),
                'items' => $items,
                'inventory' => $inventory,
            ],
        ]);
    }

    /**
     * Jalankan simulasi skenario "What-If" untuk pemasok.
     */
    public function simulateWhatIf(Request $request): JsonResponse
    {
        $supplier = $this->getActiveSupplierProfile();

        if (! $supplier) {
            return response()->json(['status' => 'error', 'message' => 'Profil pemasok tidak ditemukan.'], 403);
        }

        $demandShockPct = (float) $request->input('demand_shock_pct', 20.0);
        $leadTimeDelayDays = (int) $request->input('lead_time_delay_days', 2);
        $scenarioName = (string) $request->input('scenario_name', 'Simulasi Skenario Interaktif');

        $result = $this->planningService->simulateWhatIfScenario(
            $supplier,
            $demandShockPct,
            $leadTimeDelayDays,
            $scenarioName
        );

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }

    /**
     * Setujui rekomendasi pengadaan dan ubah menjadi PO resmi.
     */
    public function approvePurchaseSuggestion(string $id): JsonResponse|RedirectResponse
    {
        $supplier = $this->getActiveSupplierProfile();

        $suggestion = SupplierPurchaseSuggestion::where('supplier_id', $supplier->id)->findOrFail($id);

        $poNumber = 'PO/SORAI/'.date('Y/m').'/'.strtoupper(substr($suggestion->id, 0, 8));

        $suggestion->update([
            'status' => SupplierPurchaseSuggestion::STATUS_ORDERED,
            'po_reference_number' => $poNumber,
        ]);

        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => "Purchase Order resmi {$poNumber} berhasil diterbitkan.",
                'data' => $suggestion,
            ]);
        }

        return redirect()->back()->with('success', "✓ Purchase Order resmi {$poNumber} berhasil diterbitkan.");
    }

    /**
     * Abaikan rekomendasi pengadaan.
     */
    public function ignorePurchaseSuggestion(string $id): JsonResponse|RedirectResponse
    {
        $supplier = $this->getActiveSupplierProfile();

        $suggestion = SupplierPurchaseSuggestion::where('supplier_id', $supplier->id)->findOrFail($id);
        $suggestion->update(['status' => SupplierPurchaseSuggestion::STATUS_IGNORED]);

        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Rekomendasi pengadaan berhasil diabaikan.',
            ]);
        }

        return redirect()->back()->with('info', 'Rekomendasi pengadaan berhasil diabaikan.');
    }

    /**
     * Perbarui stok fisik bahan baku pemasok di gudang.
     */
    public function updateInventoryItem(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $supplier = $this->getActiveSupplierProfile();

        $validated = $request->validate([
            'current_stock' => 'required|numeric|min:0|max:1000000',
        ]);

        $item = SupplierInventoryItem::where('supplier_id', $supplier->id)->findOrFail($id);
        $item->update([
            'current_stock' => $validated['current_stock'],
            'last_restocked_at' => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Stok gudang berhasil diperbarui.',
                'data' => $item,
            ]);
        }

        return redirect()->back()->with('success', '✓ Stok gudang berhasil diperbarui.');
    }

    /**
     * Konfirmasi respon pemasok terhadap estimasi kebutuhan bahan baku UMKM.
     */
    public function konfirmasi(Request $request, string $id): RedirectResponse
    {
        $supplier = $this->getActiveSupplierProfile();

        if (! $supplier) {
            return redirect()->back()->with('error', 'Profil Pemasok tidak ditemukan.');
        }

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:terima_penuh,sesuaikan,tolak'],
            'confirmed_quantity' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'supplier_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $supplyPlan = SupplyPlan::whereHas('partnership', function ($q) use ($supplier) {
            $q->where('supplier_id', $supplier->id);
        })->findOrFail($id);

        $umkmName = $supplyPlan->partnership?->umkm?->business_name ?? 'UMKM';

        if ($validated['action'] === 'terima_penuh') {
            $supplyPlan->update([
                'supplier_confirmation_status' => 'DISETUJUI',
                'confirmed_quantity' => $supplyPlan->estimated_quantity,
                'supplier_notes' => $validated['supplier_notes'] ?? 'Kapasitas siap dipenuhi 100% sesuai estimasi kebutuhan dapur.',
            ]);

            $message = "✓ Estimasi pasokan {$supplyPlan->commodity_name} untuk {$umkmName} berhasil DISETUJUI PENUH ({$supplyPlan->estimated_quantity} {$supplyPlan->unit}).";
        } elseif ($validated['action'] === 'sesuaikan') {
            $confirmedQty = $validated['confirmed_quantity'] ?? $supplyPlan->estimated_quantity;
            $supplyPlan->update([
                'supplier_confirmation_status' => 'DISESUAIKAN',
                'confirmed_quantity' => $confirmedQty,
                'supplier_notes' => $validated['supplier_notes'] ?? 'Kapasitas pasok disesuaikan dengan kuota panen/gudang yang tersedia.',
            ]);

            $message = "✓ Kuota pasokan {$supplyPlan->commodity_name} untuk {$umkmName} berhasil DISESUAIKAN menjadi {$confirmedQty} {$supplyPlan->unit}.";
        } else {
            $supplyPlan->update([
                'supplier_confirmation_status' => 'DITOLAK',
                'confirmed_quantity' => 0,
                'supplier_notes' => $validated['supplier_notes'] ?? 'Mohon maaf, kapasitas pasokan kami pada periode ini belum mencukupi.',
            ]);

            $message = "Estimasi pasokan {$supplyPlan->commodity_name} untuk {$umkmName} telah ditolak/diberi catatan kapasitas penuh.";
        }

        return redirect()->route('supplier.supply-planning.index')->with('success', $message);
    }
}
