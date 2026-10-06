<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Commodity;
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

        return Auth::user()?->supplierProfile;
    }

    public function index(Request $request)
    {
        $supplier = $this->getActiveSupplierProfile();

        if (! $supplier) {
            return redirect()->route('register.supplier')
                ->with('error', 'Silakan daftarkan profil Pemasok terlebih dahulu.');
        }

        $search = $request->input('search');
        $statusFilter = $request->input('status');

        $query = SupplierProduct::with('commodity')
            ->where('supplier_id', $supplier->id);

        if ($search) {
            $query->whereHas('commodity', function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('category', 'like', '%'.$search.'%');
            });
        }

        if ($statusFilter && $statusFilter !== 'Semua') {
            $query->where('stock_status', $statusFilter);
        }

        $products = $query->latest()->paginate(10)->withQueryString();

        // Statistics
        $totalProducts = SupplierProduct::where('supplier_id', $supplier->id)->count();
        $stokTersedia = SupplierProduct::where('supplier_id', $supplier->id)->where('stock_status', 'Tersedia')->count();
        $stokTerbatas = SupplierProduct::where('supplier_id', $supplier->id)->where('stock_status', 'Stok Terbatas')->count();
        $stokHabis = SupplierProduct::where('supplier_id', $supplier->id)->where('stock_status', 'Stok Habis')->count();

        $allCommodities = Commodity::orderBy('name')->get();

        return view('supplier.katalog.index', compact(
            'supplier',
            'products',
            'totalProducts',
            'stokTersedia',
            'stokTerbatas',
            'stokHabis',
            'allCommodities',
            'search',
            'statusFilter'
        ));
    }

    public function create()
    {
        $supplier = $this->getActiveSupplierProfile();
        $commodities = Commodity::orderBy('name')->get();

        return view('supplier.katalog.create', compact('supplier', 'commodities'));
    }

    public function store(Request $request)
    {
        $supplier = $this->getActiveSupplierProfile();

        if (! $supplier) {
            return redirect()->back()->with('error', 'Profil Pemasok tidak ditemukan.');
        }

        $validated = $request->validate([
            'commodity_id' => 'required|exists:commodities,id',
            'price' => 'required|numeric|min:0',
            'stock_capacity' => 'required|string|max:255',
            'stock_status' => 'required|string|in:Tersedia,Stok Terbatas,Stok Habis,Pre-Order',
            'minimum_order' => 'nullable|string|max:100',
            'quality_grade' => 'nullable|string|max:100',
            'is_halal_certified' => 'nullable|boolean',
            'description' => 'nullable|string',
            'image_url' => 'nullable|url',
        ]);

        $validated['supplier_id'] = $supplier->id;
        $validated['is_halal_certified'] = $request->has('is_halal_certified');

        SupplierProduct::create($validated);

        return redirect()->route('supplier.katalog.index')
            ->with('success', 'Komoditas baru berhasil ditambahkan ke katalog Anda!');
    }

    public function edit($id)
    {
        $supplier = $this->getActiveSupplierProfile();
        $product = SupplierProduct::where('supplier_id', $supplier->id)->findOrFail($id);
        $commodities = Commodity::orderBy('name')->get();

        return view('supplier.katalog.edit', compact('supplier', 'product', 'commodities'));
    }

    public function update(Request $request, $id)
    {
        $supplier = $this->getActiveSupplierProfile();
        $product = SupplierProduct::where('supplier_id', $supplier->id)->findOrFail($id);

        $validated = $request->validate([
            'commodity_id' => 'required|exists:commodities,id',
            'price' => 'required|numeric|min:0',
            'stock_capacity' => 'required|string|max:255',
            'stock_status' => 'required|string|in:Tersedia,Stok Terbatas,Stok Habis,Pre-Order',
            'minimum_order' => 'nullable|string|max:100',
            'quality_grade' => 'nullable|string|max:100',
            'is_halal_certified' => 'nullable|boolean',
            'description' => 'nullable|string',
            'image_url' => 'nullable|url',
        ]);

        $validated['is_halal_certified'] = $request->has('is_halal_certified');

        $product->update($validated);

        return redirect()->route('supplier.katalog.index')
            ->with('success', 'Informasi produk dan stok berhasil diperbarui!');
    }

    public function updateStockStatus(Request $request, $id)
    {
        $supplier = $this->getActiveSupplierProfile();
        $product = SupplierProduct::where('supplier_id', $supplier->id)->findOrFail($id);

        $validated = $request->validate([
            'stock_status' => 'required|string|in:Tersedia,Stok Terbatas,Stok Habis,Pre-Order',
            'stock_capacity' => 'nullable|string|max:255',
        ]);

        $product->update($validated);

        return redirect()->back()->with('success', 'Status stok komoditas berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $supplier = $this->getActiveSupplierProfile();
        $product = SupplierProduct::where('supplier_id', $supplier->id)->findOrFail($id);
        $product->delete();

        return redirect()->route('supplier.katalog.index')
            ->with('success', 'Komoditas berhasil dihapus dari katalog.');
    }
}
