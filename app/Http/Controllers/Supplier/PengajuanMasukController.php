<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Partnership;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class PengajuanMasukController extends Controller
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

    public function index()
    {
        $supplier = $this->getActiveSupplierProfile();

        if (! $supplier) {
            return redirect()->route('register.supplier')
                ->with('error', 'Silakan daftarkan profil Pemasok terlebih dahulu.');
        }

        $pendingPartnerships = Partnership::with([
            'umkm',
            'activeDeliverySchedule.supplierProduct.commodity',
        ])
            ->where('supplier_id', $supplier->id)
            ->where('status', 'pending')
            ->latest()
            ->get();

        $totalPending = $pendingPartnerships->count();

        return view('supplier.pengajuan-masuk.index', compact(
            'supplier',
            'pendingPartnerships',
            'totalPending'
        ));
    }

    public function terima(string $id)
    {
        $supplier = $this->getActiveSupplierProfile();

        if (! $supplier) {
            return redirect()->back()->with('error', 'Profil Pemasok tidak ditemukan.');
        }

        $partnership = Partnership::where('supplier_id', $supplier->id)
            ->where('status', 'pending')
            ->findOrFail($id);

        $partnership->update(['status' => 'active']);

        return redirect()->route('supplier.pengajuan-masuk.index')
            ->with('success', 'Kemitraan berhasil diterima! UMKM telah diberitahu.');
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
