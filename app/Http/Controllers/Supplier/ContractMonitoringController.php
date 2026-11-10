<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Partnership;
use App\Models\PartnershipEvaluation;
use App\Services\Contract\ContractMonitoringService;
use App\Services\Reliability\UmkmReliabilityScoreService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ContractMonitoringController extends Controller
{
    public function __construct(
        protected ContractMonitoringService $contractService,
        protected UmkmReliabilityScoreService $reliabilityService
    ) {}

    /**
     * Tampilkan halaman Contract Monitoring untuk pemasok aktif.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $supplier = Auth::user()?->supplierProfile;

        if (! $supplier) {
            return redirect()->route('register.supplier')
                ->with('error', 'Silakan daftarkan profil Pemasok terlebih dahulu.');
        }

        $search = trim((string) $request->input('search', ''));
        $statusFilter = strtolower(trim((string) $request->input('status', 'all')));

        if (! in_array($statusFilter, ['all', 'active', 'attention', 'overdue', 'inactive'], true)) {
            $statusFilter = 'all';
        }

        $monitoringData = $this->contractService->buildMonitoringData(
            $supplier,
            $search,
            $statusFilter
        );

        return view('supplier.contract-monitoring.index', [
            'supplier' => $supplier,
            'contracts' => $monitoringData['contracts'],
            'allContractsCount' => $monitoringData['all_contracts_count'],
            'kpis' => $monitoringData['kpis'],
            'counts' => $monitoringData['counts'],
            'alerts' => $monitoringData['alerts'],
            'search' => $search,
            'statusFilter' => $statusFilter,
        ]);
    }

    /**
     * Simpan evaluasi periodik kontrak ke tabel partnership_evaluations.
     */
    public function storeEvaluation(Request $request, string $partnership): RedirectResponse
    {
        $contract = $this->findSupplierContractOrFail($partnership);

        $validated = $request->validate([
            'evaluation_period' => ['required', 'string', 'max:100'],
            'target_volume' => ['required', 'numeric', 'min:0.01', 'max:10000000'],
            'realized_volume' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'discrepancy_notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'evaluation_period.required' => 'Periode evaluasi wajib diisi.',
            'target_volume.required' => 'Target volume wajib diisi.',
            'target_volume.min' => 'Target volume harus lebih besar dari 0.',
            'realized_volume.required' => 'Realisasi volume wajib diisi.',
            'realized_volume.min' => 'Realisasi volume tidak boleh kurang dari 0.',
        ]);

        $targetVolume = (float) $validated['target_volume'];
        $realizedVolume = (float) $validated['realized_volume'];
        $fulfillmentRate = min(999.99, round(($realizedVolume / $targetVolume) * 100, 2));
        $notes = trim((string) ($validated['discrepancy_notes'] ?? ''));

        DB::transaction(function () use ($contract, $validated, $targetVolume, $realizedVolume, $fulfillmentRate, $notes): void {
            PartnershipEvaluation::create([
                'partnership_id' => $contract->id,
                'evaluation_period' => trim($validated['evaluation_period']),
                'target_volume' => $targetVolume,
                'realized_volume' => $realizedVolume,
                'fulfillment_rate_percentage' => $fulfillmentRate,
                'discrepancy_notes' => $notes !== '' ? $notes : null,
            ]);

            if ($contract->umkm) {
                $this->reliabilityService->calculate($contract->umkm, $contract);
            }
        });

        return redirect()->route('supplier.contract-monitoring.index')
            ->with('success', "✓ Evaluasi periode {$validated['evaluation_period']} untuk kontrak {$contract->contract_code} berhasil disimpan.");
    }

    /**
     * Perpanjang tanggal berakhir dan opsional target volume kontrak kemitraan.
     */
    public function extendContract(Request $request, string $partnership): RedirectResponse
    {
        $contract = $this->findSupplierContractOrFail($partnership, ['supplier.products.commodity', 'deliverySchedules']);

        if ($contract->isTerminatedStatus()) {
            return redirect()->back()
                ->with('error', 'Kontrak yang telah dihentikan harus diaktifkan kembali sebelum diperpanjang.');
        }

        $validated = $request->validate([
            'end_date' => ['required', 'date', 'after_or_equal:today'],
            'total_contract_volume' => ['nullable', 'numeric', 'min:1', 'max:10000000'],
            'volume_per_delivery' => ['nullable', 'numeric', 'min:0.1', 'max:1000000'],
            'frequency_type' => ['nullable', 'string', 'max:100'],
        ], [
            'end_date.required' => 'Tanggal berakhir kontrak baru wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal berakhir kontrak baru minimal hari ini.',
            'total_contract_volume.min' => 'Target volume kontrak minimal 1 satuan.',
        ]);

        DB::transaction(function () use ($contract, $validated): void {
            $this->contractService->extendPartnershipContract(
                $contract,
                $validated['end_date'],
                isset($validated['total_contract_volume']) ? (float) $validated['total_contract_volume'] : null,
                isset($validated['volume_per_delivery']) ? (float) $validated['volume_per_delivery'] : null,
                $validated['frequency_type'] ?? null
            );
        });

        $formattedDate = Carbon::parse($validated['end_date'])->locale('id')->translatedFormat('d M Y');

        return redirect()->route('supplier.contract-monitoring.index')
            ->with('success', "✓ Masa berlaku kontrak {$contract->contract_code} berhasil diperpanjang hingga {$formattedDate}.");
    }

    /**
     * Aktifkan atau nonaktifkan fitur Auto-Order pada jadwal kontrak.
     */
    public function toggleAutoOrder(string $partnership): RedirectResponse
    {
        $contract = $this->findSupplierContractOrFail($partnership, ['deliverySchedules']);

        if (! $contract->isActiveStatus()) {
            return redirect()->back()
                ->with('error', 'Auto-Order hanya dapat diubah untuk kontrak berstatus aktif.');
        }

        $schedule = $contract->deliverySchedules->sortByDesc('created_at')->first();

        if (! $schedule) {
            return redirect()->back()
                ->with('error', 'Kontrak ini belum memiliki jadwal pengiriman rutin untuk diatur Auto-Order.');
        }

        if ($schedule->end_date !== null && Carbon::parse($schedule->end_date)->startOfDay()->lt(Carbon::today())) {
            return redirect()->back()
                ->with('error', 'Masa berlaku kontrak telah berakhir. Perpanjang kontrak terlebih dahulu untuk mengatur Auto-Order.');
        }

        $nextState = ! (bool) $schedule->is_auto_order_active;
        $schedule->update(['is_auto_order_active' => $nextState]);

        $statusText = $nextState ? 'DIAKTIFKAN' : 'DINONAKTIFKAN';

        return redirect()->route('supplier.contract-monitoring.index')
            ->with('success', "✓ Auto-Order untuk kontrak {$contract->contract_code} berhasil {$statusText}.");
    }

    /**
     * Ubah status siklus hidup kontrak (aktifkan, jeda sementara, atau hentikan).
     */
    public function updateStatus(Request $request, string $partnership): RedirectResponse
    {
        $contract = $this->findSupplierContractOrFail($partnership, ['deliverySchedules']);

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:activate,pause,terminate'],
        ], [
            'action.required' => 'Aksi perubahan status kontrak wajib dipilih.',
            'action.in' => 'Aksi status kontrak tidak valid.',
        ]);

        $action = $validated['action'];

        DB::transaction(function () use ($contract, $action): void {
            if ($action === 'activate') {
                $contract->update(['status' => Partnership::STATUS_ACTIVE]);
            } elseif ($action === 'pause') {
                $contract->update(['status' => Partnership::STATUS_PAUSED]);
                $contract->deliverySchedules()->update(['is_auto_order_active' => false]);
            } else {
                $contract->update(['status' => Partnership::STATUS_TERMINATED]);
                $contract->deliverySchedules()->update(['is_auto_order_active' => false]);
            }
        });

        $message = match ($action) {
            'activate' => "✓ Kontrak {$contract->contract_code} berhasil diaktifkan kembali.",
            'pause' => "✓ Kontrak {$contract->contract_code} berhasil dijeda sementara dan Auto-Order dinonaktifkan.",
            default => "✓ Kontrak {$contract->contract_code} telah dihentikan dan Auto-Order dinonaktifkan.",
        };

        return redirect()->route('supplier.contract-monitoring.index')
            ->with('success', $message);
    }

    /**
     * Ambil kontrak kemitraan milik pemasok yang sedang login (404 jika milik pemasok lain atau belum disetujui).
     *
     * @param  array<int, string>  $relations
     */
    protected function findSupplierContractOrFail(string $partnershipId, array $relations = ['umkm']): Partnership
    {
        $supplier = Auth::user()?->supplierProfile;

        if (! $supplier) {
            abort(404);
        }

        return Partnership::with($relations)
            ->where('supplier_id', $supplier->id)
            ->whereRaw("LOWER(COALESCE(status, '')) NOT IN ('pending', 'menunggu', 'dalam_review', 'rejected', 'ditolak')")
            ->findOrFail($partnershipId);
    }
}
