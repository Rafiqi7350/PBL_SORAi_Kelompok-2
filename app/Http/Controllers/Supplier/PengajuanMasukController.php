<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Partnership;
use App\Models\User;
use App\Services\Reliability\UmkmReliabilityScoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PengajuanMasukController extends Controller
{
    public function __construct(
        protected UmkmReliabilityScoreService $reliabilityService
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

    public function index()
    {
        $supplier = $this->getActiveSupplierProfile();

        if (! $supplier) {
            return redirect()->route('register.supplier')
                ->with('error', 'Silakan daftarkan profil Pemasok terlebih dahulu.');
        }

        $pendingPartnerships = Partnership::with([
            'umkm.latestReliabilityScore',
            'activeDeliverySchedule.supplierProduct.commodity',
        ])
            ->where('supplier_id', $supplier->id)
            ->where('status', 'pending')
            ->latest()
            ->paginate(9, ['*'], 'pending_page')
            ->withQueryString();

        $activePartnerships = Partnership::with([
            'umkm.latestReliabilityScore',
            'activeDeliverySchedule.supplierProduct.commodity',
        ])
            ->where('supplier_id', $supplier->id)
            ->whereIn('status', ['active', 'approved', 'disetujui'])
            ->latest()
            ->paginate(9, ['*'], 'active_page')
            ->withQueryString();

        // Pastikan setiap UMKM memiliki skor kalkulasi keandalan
        foreach ($pendingPartnerships as $partnership) {
            if ($partnership->umkm && ! $partnership->umkm->latestReliabilityScore) {
                $score = $this->reliabilityService->calculate($partnership->umkm, $partnership);
                $partnership->umkm->setRelation('latestReliabilityScore', $score);
            }
        }

        foreach ($activePartnerships as $partnership) {
            if ($partnership->umkm && ! $partnership->umkm->latestReliabilityScore) {
                $score = $this->reliabilityService->calculate($partnership->umkm, $partnership);
                $partnership->umkm->setRelation('latestReliabilityScore', $score);
            }
        }

        $totalPending = $pendingPartnerships->total();
        $totalActive = $activePartnerships->total();

        return view('supplier.pengajuan-masuk.index', compact(
            'supplier',
            'pendingPartnerships',
            'totalPending',
            'activePartnerships',
            'totalActive'
        ));
    }

    public function terima(Request $request, string $id)
    {
        $supplier = $this->getActiveSupplierProfile();

        if (! $supplier) {
            return redirect()->back()->with('error', 'Profil Pemasok tidak ditemukan.');
        }

        $partnership = Partnership::where('supplier_id', $supplier->id)
            ->where('status', 'pending')
            ->findOrFail($id);

        $validated = $request->validate([
            'term_of_payment_days' => 'nullable|integer|min:0|max:90',
            'credit_limit' => 'nullable|numeric|min:0',
        ]);

        $partnership->update([
            'status' => 'active',
            'term_of_payment_days' => $validated['term_of_payment_days'] ?? $partnership->term_of_payment_days ?? 0,
            'credit_limit' => $validated['credit_limit'] ?? $partnership->credit_limit ?? 0.00,
        ]);

        return redirect()->route('supplier.pengajuan-masuk.index')
            ->with('success', 'Kemitraan berhasil diterima dan fasilitas pembayaran telah disetujui!');
    }

    public function tolak(string $id)
    {
        $supplier = $this->getActiveSupplierProfile();

        if (! $supplier) {
            return redirect()->back()->with('error', 'Profil Pemasok tidak ditemukan.');
        }

        $partnership = Partnership::where('supplier_id', $supplier->id)
            ->where('status', 'pending')
            ->findOrFail($id);

        $partnership->update(['status' => 'rejected']);

        return redirect()->route('supplier.pengajuan-masuk.index')
            ->with('success', 'Pengajuan kemitraan telah ditolak.');
    }
}
