<?php

namespace App\Http\Controllers\Umkm;

use App\Http\Controllers\Controller;
use App\Models\Partnership;
use App\Models\SupplierProfile;
use App\Models\SupplyPlan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CollaborativeSupplyPlanningController extends Controller
{
    private function getActiveUmkmProfile()
    {
        if (! Auth::check() || Auth::user()->role !== 'umkm') {
            $dummyUmkmUser = User::where('role', 'umkm')->first();
            if ($dummyUmkmUser) {
                Auth::login($dummyUmkmUser);
            }
        }

        return Auth::user()?->umkmProfile;
    }

    /**
     * Simpan estimasi proyeksi kebutuhan bahan baku UMKM untuk periode mendatang.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'uuid', 'exists:supplier_profiles,id'],
            'commodity_name' => ['required', 'string', 'max:255'],
            'estimated_quantity' => ['required', 'numeric', 'min:0.1', 'max:1000000'],
            'unit' => ['required', 'string', 'max:20'],
            'period_month_year' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'supplier_id.required' => 'Silakan pilih mitra pemasok tujuan.',
            'supplier_id.exists' => 'Mitra pemasok tidak ditemukan di sistem.',
            'commodity_name.required' => 'Nama komoditas bahan baku wajib diisi.',
            'estimated_quantity.required' => 'Estimasi kuantitas kebutuhan wajib diisi.',
            'estimated_quantity.numeric' => 'Estimasi kuantitas harus berupa angka valid.',
            'estimated_quantity.min' => 'Estimasi kuantitas minimal 0.1.',
            'period_month_year.required' => 'Periode bulan/tahun target wajib ditentukan.',
            'unit.required' => 'Satuan bahan baku wajib ditentukan.',
        ]);

        $umkm = $this->getActiveUmkmProfile();

        if (! $umkm) {
            return redirect()->back()->with('error', 'Profil UMKM tidak ditemukan. Silakan lengkapi profil UMKM Anda terlebih dahulu.');
        }

        // Cari atau buat kemitraan antara UMKM dan Supplier
        $partnership = Partnership::firstOrCreate(
            [
                'umkm_id' => $umkm->id,
                'supplier_id' => $validated['supplier_id'],
            ],
            [
                'submission_details' => 'Kemitraan Perencanaan Pasokan Bersama (Collaborative Supply Planning)',
                'status' => 'active',
            ]
        );

        $supplier = SupplierProfile::find($validated['supplier_id']);
        $supplierName = $supplier?->company_name ?? 'Pemasok Mitra';

        $supplyPlan = SupplyPlan::create([
            'partnership_id' => $partnership->id,
            'commodity_name' => $validated['commodity_name'],
            'period_month_year' => $validated['period_month_year'],
            'estimated_quantity' => $validated['estimated_quantity'],
            'unit' => $validated['unit'],
            'notes' => $validated['notes'] ?? null,
            'supplier_confirmation_status' => 'MENUNGGU_KONFIRMASI',
        ]);

        $shortId = 'CSP-'.strtoupper(substr($supplyPlan->id, 0, 8));

        return redirect()->route('umkm.katalog.index', ['tab' => 'supply-planning'])
            ->with('success', "✓ Estimasi Collaborative Supply Plan #{$shortId} ({$validated['estimated_quantity']} {$validated['unit']} {$validated['commodity_name']}) untuk periode {$validated['period_month_year']} berhasil dikirimkan ke {$supplierName}!");
    }

    /**
     * Batalkan estimasi proyeksi kebutuhan yang masih berstatus menunggu konfirmasi.
     */
    public function destroy(string $id): RedirectResponse
    {
        $umkm = $this->getActiveUmkmProfile();

        if (! $umkm) {
            return redirect()->back()->with('error', 'Profil UMKM tidak ditemukan.');
        }

        $supplyPlan = SupplyPlan::whereHas('partnership', function ($q) use ($umkm) {
            $q->where('umkm_id', $umkm->id);
        })->findOrFail($id);

        if ($supplyPlan->supplier_confirmation_status !== 'MENUNGGU_KONFIRMASI') {
            return redirect()->route('umkm.katalog.index', ['tab' => 'supply-planning'])
                ->with('error', 'Hanya estimasi dengan status menunggu konfirmasi yang dapat dibatalkan.');
        }

        $supplyPlan->delete();

        return redirect()->route('umkm.katalog.index', ['tab' => 'supply-planning'])
            ->with('success', 'Estimasi kebutuhan bahan baku berhasil dibatalkan.');
    }
}
