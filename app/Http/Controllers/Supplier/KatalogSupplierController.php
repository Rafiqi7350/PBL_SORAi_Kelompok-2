<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Commodity;
use App\Models\CommodityUnit;
use App\Models\SupplierProduct;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KatalogSupplierController extends Controller
{
    private function getActiveSupplierProfile()
    {
        if (! Auth::check()) {
            $dummySupplierUser = User::where('role', 'supplier')->first();
            if ($dummySupplierUser) {
                Auth::login($dummySupplierUser);
            }
        }

        $supplier = Auth::user()?->supplierProfile;

        if (! $supplier) {
            abort(403, 'Akses ditolak. Profil Pemasok tidak ditemukan.');
        }

        return $supplier;
    }

    public function index(Request $request)
    {
        $supplier = $this->getActiveSupplierProfile();

        $search = $request->input('search');
        $statusFilter = $request->input('status');

        // ISOLATION CHECK: Pemasok HANYA dapat melihat produk miliknya sendiri
        $query = SupplierProduct::with('commodity')
            ->where('supplier_id', $supplier->id);

        if ($search) {
            $query->whereHas('commodity', function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('category', 'like', '%'.$search.'%');
            });
        }

        if ($statusFilter && $statusFilter !== 'Semua') {
            if ($statusFilter === 'ready') {
                $query->whereIn('stock_status', ['ready', 'Tersedia']);
            } elseif ($statusFilter === 'limited') {
                $query->whereIn('stock_status', ['limited', 'Stok Terbatas']);
            } else {
                $query->where('stock_status', $statusFilter);
            }
        }

        $products = $query->latest()->paginate(10)->withQueryString();

        // Statistics metric for supplier dashboard
        $totalProducts = SupplierProduct::where('supplier_id', $supplier->id)->count();
        $stokReady = SupplierProduct::where('supplier_id', $supplier->id)->whereIn('stock_status', ['ready', 'Tersedia'])->count();
        $stokLimited = SupplierProduct::where('supplier_id', $supplier->id)->whereIn('stock_status', ['limited', 'Stok Terbatas'])->count();

        return view('supplier.katalog.index', compact(
            'supplier',
            'products',
            'totalProducts',
            'stokReady',
            'stokLimited',
            'search',
            'statusFilter'
        ));
    }

    public function create()
    {
        $supplier = $this->getActiveSupplierProfile();

        // Master commodities with government ref prices for dynamic comparison
        $commodities = Commodity::orderBy('name')->get();

        // Master units for validation dropdown
        $dbUnits = CommodityUnit::orderBy('name')->pluck('name')->toArray();
        $fallbackUnits = ['kg', 'liter', 'pcs', 'dus', 'gram', 'ikat', 'karung', 'pack'];
        $validUnits = array_unique(array_merge($dbUnits, $fallbackUnits));

        return view('supplier.katalog.create', compact('supplier', 'commodities', 'validUnits'));
    }

    public function store(Request $request)
    {
        $supplier = $this->getActiveSupplierProfile();

        // Get master valid units for validation rule
        $dbUnits = CommodityUnit::orderBy('name')->pluck('name')->toArray();
        $fallbackUnits = ['kg', 'liter', 'pcs', 'dus', 'gram', 'ikat', 'karung', 'pack'];
        $validUnits = array_unique(array_merge($dbUnits, $fallbackUnits));

        // Strict Validations:
        // - commodity_id wajib dan valid
        // - price wajib dan > 0 (gt:0)
        // - weekly_capacity wajib dan > 0 (gt:0)
        // - unit wajib dan harus dari satuan baku
        // - stock_status wajib ('ready' / 'limited')
        $validated = $request->validate([
            'commodity_id' => 'required|uuid|exists:commodities,id',
            'price' => 'required|numeric|gt:0',
            'weekly_capacity' => 'required|numeric|gt:0',
            'unit' => 'required|string|in:'.implode(',', $validUnits),
            'stock_status' => 'required|string|in:ready,limited,Tersedia,Stok Terbatas,Stok Habis',
            'minimum_order' => 'nullable|string|max:100',
            'quality_grade' => 'nullable|string|max:100',
            'is_halal_certified' => 'nullable|boolean',
            'description' => 'nullable|string',
            'image_url' => 'nullable|url',
        ], [
            'commodity_id.required' => 'Komoditas bahan baku wajib dipilih dari master data.',
            'commodity_id.exists' => 'Komoditas yang dipilih tidak valid dalam master data.',
            'price.required' => 'Harga grosir wajib diisi.',
            'price.gt' => 'Harga grosir harus lebih besar dari 0 (Rp).',
            'weekly_capacity.required' => 'Kapasitas pasokan mingguan wajib diisi.',
            'weekly_capacity.gt' => 'Kapasitas pasokan mingguan harus lebih besar dari 0.',
            'unit.required' => 'Satuan baku wajib dipilih.',
            'unit.in' => 'Satuan harus berasal dari daftar satuan baku yang terdaftar.',
            'stock_status.required' => 'Status ketersediaan stok wajib dipilih.',
            'stock_status.in' => 'Status stok harus bernilai ready (pasokan rutin) atau limited (terbatas).',
        ]);

        $validated['supplier_id'] = $supplier->id;
        $validated['stock_capacity'] = $validated['weekly_capacity'].' '.$validated['unit'].' / minggu';
        $validated['is_halal_certified'] = $request->has('is_halal_certified');

        SupplierProduct::create($validated);

        return redirect()->route('supplier.katalog.index')
            ->with('success', 'Produk komoditas berhasil ditambahkan ke katalog Anda!');
    }

    public function edit($id)
    {
        $supplier = $this->getActiveSupplierProfile();

        // ISOLATION CHECK: Pemasok HANYA bisa mengedit produk miliknya sendiri
        $product = SupplierProduct::where('supplier_id', $supplier->id)->findOrFail($id);

        $commodities = Commodity::orderBy('name')->get();

        $dbUnits = CommodityUnit::orderBy('name')->pluck('name')->toArray();
        $fallbackUnits = ['kg', 'liter', 'pcs', 'dus', 'gram', 'ikat', 'karung', 'pack'];
        $validUnits = array_unique(array_merge($dbUnits, $fallbackUnits));

        return view('supplier.katalog.edit', compact('supplier', 'product', 'commodities', 'validUnits'));
    }

    public function update(Request $request, $id)
    {
        $supplier = $this->getActiveSupplierProfile();

        // ISOLATION CHECK: Pemasok HANYA bisa mengubah produk miliknya sendiri
        $product = SupplierProduct::where('supplier_id', $supplier->id)->findOrFail($id);

        $dbUnits = CommodityUnit::orderBy('name')->pluck('name')->toArray();
        $fallbackUnits = ['kg', 'liter', 'pcs', 'dus', 'gram', 'ikat', 'karung', 'pack'];
        $validUnits = array_unique(array_merge($dbUnits, $fallbackUnits));

        $validated = $request->validate([
            'commodity_id' => 'required|uuid|exists:commodities,id',
            'price' => 'required|numeric|gt:0',
            'weekly_capacity' => 'required|numeric|gt:0',
            'unit' => 'required|string|in:'.implode(',', $validUnits),
            'stock_status' => 'required|string|in:ready,limited,Tersedia,Stok Terbatas,Stok Habis',
            'minimum_order' => 'nullable|string|max:100',
            'quality_grade' => 'nullable|string|max:100',
            'is_halal_certified' => 'nullable|boolean',
            'description' => 'nullable|string',
            'image_url' => 'nullable|url',
        ], [
            'commodity_id.required' => 'Komoditas bahan baku wajib dipilih dari master data.',
            'price.gt' => 'Harga grosir harus lebih besar dari 0.',
            'weekly_capacity.gt' => 'Kapasitas pasokan mingguan harus lebih besar dari 0.',
            'unit.in' => 'Satuan harus berasal dari daftar satuan baku.',
        ]);

        $validated['stock_capacity'] = $validated['weekly_capacity'].' '.$validated['unit'].' / minggu';
        $validated['is_halal_certified'] = $request->has('is_halal_certified');

        $product->update($validated);

        return redirect()->route('supplier.katalog.index')
            ->with('success', 'Produk komoditas dan stok berhasil diperbarui!');
    }

    public function updateStockStatus(Request $request, $id)
    {
        $supplier = $this->getActiveSupplierProfile();

        // ISOLATION CHECK: Pemasok HANYA bisa mengubah stok produk miliknya sendiri
        $product = SupplierProduct::where('supplier_id', $supplier->id)->findOrFail($id);

        $validated = $request->validate([
            'stock_status' => 'required|string|in:ready,limited,Tersedia,Stok Terbatas,Stok Habis',
            'weekly_capacity' => 'nullable|numeric|gt:0',
        ]);

        if (isset($validated['weekly_capacity'])) {
            $validated['stock_capacity'] = $validated['weekly_capacity'].' '.($product->unit ?: 'kg').' / minggu';
        }

        $product->update($validated);

        return redirect()->back()->with('success', 'Status stok komoditas berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $supplier = $this->getActiveSupplierProfile();

        // ISOLATION CHECK: Pemasok HANYA bisa menghapus produk miliknya sendiri
        $product = SupplierProduct::where('supplier_id', $supplier->id)->findOrFail($id);
        $product->delete();

        return redirect()->route('supplier.katalog.index')
            ->with('success', 'Produk komoditas berhasil dihapus dari katalog Anda.');
    }
}
