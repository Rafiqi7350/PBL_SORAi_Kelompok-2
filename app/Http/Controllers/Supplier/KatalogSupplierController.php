<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Commodity;
use App\Models\CommodityCategory;
use App\Models\CommodityUnit;
use App\Models\SupplierProduct;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class KatalogSupplierController extends Controller
{
    private function getActiveSupplierProfile()
    {
        // Jika belum login ATAU login sebagai non-supplier, auto-login ke demo supplier
        if (! Auth::check() || Auth::user()->role !== 'supplier') {
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
        $categories = CommodityCategory::orderBy('name')->get();
        $units = CommodityUnit::orderBy('name')->get();

        return view('supplier.katalog.create', compact('supplier', 'categories', 'units'));
    }

    public function store(Request $request)
    {
        $supplier = $this->getActiveSupplierProfile();

        if (! $supplier) {
            return redirect()->back()->with('error', 'Profil Pemasok tidak ditemukan.');
        }

        $validated = $request->validate([
            'category_id' => 'required|string',
            'commodity_name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'government_ref_price' => 'nullable|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'stock_capacity' => 'required|string|max:255',
            'stock_status' => 'required|string|in:Tersedia,Stok Terbatas,Stok Habis,Pre-Order',
            'minimum_order' => 'nullable|string|max:100',
            'quality_grade' => 'nullable|string|max:100',
            'is_halal_certified' => 'nullable|boolean',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $category = CommodityCategory::find($request->input('category_id'));
        $categoryName = $category ? $category->name : $request->input('category_id');

        $commodityName = trim($request->input('commodity_name'));
        $unitName = trim($request->input('unit'));

        // Hubungkan atau buat komoditas baru di tabel commodities
        $commodity = Commodity::firstOrCreate(
            [
                'name' => $commodityName,
            ],
            [
                'category' => $categoryName,
                'unit' => $unitName,
                'government_ref_price' => $request->input('government_ref_price'),
                'description' => $request->input('description'),
            ]
        );

        if ($commodity->category !== $categoryName || $commodity->unit !== $unitName) {
            $commodity->update([
                'category' => $categoryName,
                'unit' => $unitName,
            ]);
        }

        $productData = [
            'supplier_id' => $supplier->id,
            'commodity_id' => $commodity->id,
            'price' => $validated['price'],
            'stock_capacity' => $validated['stock_capacity'],
            'stock_status' => $validated['stock_status'],
            'minimum_order' => $validated['minimum_order'] ?? null,
            'quality_grade' => $validated['quality_grade'] ?? null,
            'is_halal_certified' => $request->has('is_halal_certified'),
            'description' => $validated['description'] ?? null,
        ];

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('commodities', 'public');
            $productData['image_url'] = '/storage/'.$path;
        } else {
            $productData['image_url'] = null;
        }

        SupplierProduct::create($productData);

        return redirect()->route('supplier.katalog.index')
            ->with('success', 'Komoditas baru berhasil ditambahkan ke katalog Anda!');
    }

    public function edit($id)
    {
        $supplier = $this->getActiveSupplierProfile();

        if (! $supplier) {
            return redirect()->route('register.supplier')
                ->with('error', 'Silakan daftarkan profil Pemasok terlebih dahulu.');
        }

        $product = SupplierProduct::with('commodity')->where('supplier_id', $supplier->id)->findOrFail($id);
        $categories = CommodityCategory::orderBy('name')->get();
        $units = CommodityUnit::orderBy('name')->get();

        return view('supplier.katalog.edit', compact('supplier', 'product', 'categories', 'units'));
    }

    public function update(Request $request, $id)
    {
        $supplier = $this->getActiveSupplierProfile();

        if (! $supplier) {
            return redirect()->back()->with('error', 'Profil Pemasok tidak ditemukan.');
        }

        $product = SupplierProduct::where('supplier_id', $supplier->id)->findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'required|string',
            'commodity_name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'government_ref_price' => 'nullable|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'stock_capacity' => 'required|string|max:255',
            'stock_status' => 'required|string|in:Tersedia,Stok Terbatas,Stok Habis,Pre-Order',
            'minimum_order' => 'nullable|string|max:100',
            'quality_grade' => 'nullable|string|max:100',
            'is_halal_certified' => 'nullable|boolean',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $category = CommodityCategory::find($request->input('category_id'));
        $categoryName = $category ? $category->name : $request->input('category_id');

        $commodityName = trim($request->input('commodity_name'));
        $unitName = trim($request->input('unit'));

        if ($product->commodity) {
            $product->commodity->update([
                'name' => $commodityName,
                'category' => $categoryName,
                'unit' => $unitName,
                'government_ref_price' => $request->input('government_ref_price'),
            ]);
        } else {
            $commodity = Commodity::firstOrCreate(
                ['name' => $commodityName],
                [
                    'category' => $categoryName,
                    'unit' => $unitName,
                    'government_ref_price' => $request->input('government_ref_price'),
                ]
            );
            $product->commodity_id = $commodity->id;
        }

        $productData = [
            'price' => $validated['price'],
            'stock_capacity' => $validated['stock_capacity'],
            'stock_status' => $validated['stock_status'],
            'minimum_order' => $validated['minimum_order'] ?? null,
            'quality_grade' => $validated['quality_grade'] ?? null,
            'is_halal_certified' => $request->has('is_halal_certified'),
            'description' => $validated['description'] ?? null,
        ];

        if ($request->hasFile('image')) {
            if ($product->image_url && str_starts_with($product->image_url, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $product->image_url);
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('image')->store('commodities', 'public');
            $productData['image_url'] = '/storage/'.$path;
        }

        $product->update($productData);

        return redirect()->route('supplier.katalog.index')
            ->with('success', 'Informasi produk dan stok berhasil diperbarui!');
    }

    public function updateStockStatus(Request $request, $id)
    {
        $supplier = $this->getActiveSupplierProfile();

        if (! $supplier) {
            return redirect()->back()->with('error', 'Profil Pemasok tidak ditemukan.');
        }

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

        if (! $supplier) {
            return redirect()->back()->with('error', 'Profil Pemasok tidak ditemukan.');
        }

        $product = SupplierProduct::where('supplier_id', $supplier->id)->findOrFail($id);
        $product->delete();

        return redirect()->route('supplier.katalog.index')
            ->with('success', 'Komoditas berhasil dihapus dari katalog.');
    }
}
