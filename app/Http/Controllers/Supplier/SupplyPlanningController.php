<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\SupplyPlan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SupplyPlanningController extends Controller
{
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
     * Tampilkan dasbor Collaborative Supply Planning untuk pemasok:
     * Agregasi estimasi kebutuhan bahan baku dari seluruh UMKM sebagai dasar perencanaan pasokan.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $supplier = $this->getActiveSupplierProfile();

        if (! $supplier) {
            return redirect()->route('register.supplier')
                ->with('error', 'Silakan daftarkan profil Pemasok terlebih dahulu.');
        }

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

        $allPlans = (clone $query)->latest()->get();

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

        // Agregasi Kebutuhan per Komoditas dan Periode untuk Perencanaan Pasokan Petani / Gudang
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

        // Periode unik untuk filter
        $availablePeriods = $totalBasePlans->pluck('period_month_year')->unique()->values();

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
            'periodFilter'
        ));
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
