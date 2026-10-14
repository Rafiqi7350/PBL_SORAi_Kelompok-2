<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Partnership;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PengajuanMasukController extends Controller
{
    private function getActiveSupplierProfile()
    {
        // Mengikuti pola KatalogSupplierController (auto-login demo bila belum login).
        if (! Auth::check()) {
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

        $pendingPartnerships = Partnership::with(['umkm', 'commodity'])
            ->where('supplier_id', $supplier->id)
            ->pending()
            ->oldest()
            ->get();

        $historyPartnerships = Partnership::with(['umkm', 'commodity'])
            ->where('supplier_id', $supplier->id)
            ->whereIn('status', [Partnership::STATUS_ACCEPTED, Partnership::STATUS_REJECTED])
            ->latest('responded_at')
            ->limit(10)
            ->get();

        return view('supplier.pengajuan.index', compact(
            'supplier',
            'pendingPartnerships',
            'historyPartnerships'
        ));
    }

    public function accept(string $id): RedirectResponse
    {
        $supplier = $this->getActiveSupplierProfile();

        if (! $supplier) {
            return redirect()->route('register.supplier')
                ->with('error', 'Profil Pemasok tidak ditemukan.');
        }

        $partnership = Partnership::with('umkm')
            ->where('supplier_id', $supplier->id)
            ->findOrFail($id);

        if (! $partnership->isPending()) {
            return redirect()->route('supplier.pengajuan.index')
                ->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        $partnership->update([
            'status' => Partnership::STATUS_ACCEPTED,
            'rejection_reason' => null,
            'responded_at' => now(),
        ]);

        return redirect()->route('supplier.pengajuan.index')
            ->with('success', 'Kemitraan dengan '.($partnership->umkm?->business_name ?? 'UMKM').' berhasil diterima!');
    }

    public function reject(Request $request, string $id): RedirectResponse
    {
        $supplier = $this->getActiveSupplierProfile();

        if (! $supplier) {
            return redirect()->route('register.supplier')
                ->with('error', 'Profil Pemasok tidak ditemukan.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $partnership = Partnership::with('umkm')
            ->where('supplier_id', $supplier->id)
            ->findOrFail($id);

        if (! $partnership->isPending()) {
            return redirect()->route('supplier.pengajuan.index')
                ->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        $partnership->update([
            'status' => Partnership::STATUS_REJECTED,
            'rejection_reason' => $validated['rejection_reason'] ?? null,
            'responded_at' => now(),
        ]);

        return redirect()->route('supplier.pengajuan.index')
            ->with('success', 'Pengajuan dari '.($partnership->umkm?->business_name ?? 'UMKM').' telah ditolak.');
    }
}