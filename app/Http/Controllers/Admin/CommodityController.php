<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commodity;
use App\Models\CommodityCategory;
use App\Models\CommodityUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CommodityController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $categoryFilter = $request->input('category');

        $query = Commodity::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('category', 'like', '%' . $search . '%')
                  ->orWhere('unit', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if ($categoryFilter && $categoryFilter !== 'Semua') {
            $query->where('category', $categoryFilter);
        }

        $commodities = $query->orderBy('name', 'asc')->paginate(10)->withQueryString();

        // Master categories & units for modals & filters
        $dbCategories = CommodityCategory::orderBy('name')->pluck('name')->toArray();
        $fallbackCategories = ['Daging & Unggas', 'Bumbu & Rempah', 'Sayuran', 'Sembako', 'Perikanan & Seafood', 'Telur', 'Lainnya'];
        $categories = array_unique(array_merge($dbCategories, $fallbackCategories));

        $dbUnits = CommodityUnit::orderBy('name')->pluck('name')->toArray();
        $fallbackUnits = ['kg', 'liter', 'pcs', 'dus', 'gram', 'ikat', 'karung', 'pack'];
        $units = array_unique(array_merge($dbUnits, $fallbackUnits));

        // Stats
        $totalCommodities = Commodity::count();
        $totalCategories = count($categories);
        $avgGovPrice = Commodity::whereNotNull('government_ref_price')->avg('government_ref_price');

        return view('admin.commodities.index', compact(
            'commodities',
            'categories',
            'units',
            'search',
            'categoryFilter',
            'totalCommodities',
            'totalCategories',
            'avgGovPrice'
        ));
    }

    public function create()
    {
        $dbCategories = CommodityCategory::orderBy('name')->pluck('name')->toArray();
        $fallbackCategories = ['Daging & Unggas', 'Bumbu & Rempah', 'Sayuran', 'Sembako', 'Perikanan & Seafood', 'Telur', 'Lainnya'];
        $categories = array_unique(array_merge($dbCategories, $fallbackCategories));

        $dbUnits = CommodityUnit::orderBy('name')->pluck('name')->toArray();
        $fallbackUnits = ['kg', 'liter', 'pcs', 'dus', 'gram', 'ikat', 'karung', 'pack'];
        $units = array_unique(array_merge($dbUnits, $fallbackUnits));

        return view('admin.commodities.create', compact('categories', 'units'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'unit' => 'required|string|max:50',
            'government_ref_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $normalizedName = trim($request->name);

        // Case-insensitive duplicate name check
        $exists = Commodity::whereRaw('LOWER(name) = ?', [mb_strtolower($normalizedName)])->exists();
        if ($exists) {
            return back()->withInput()->withErrors([
                'name' => 'Nama komoditas "' . $normalizedName . '" sudah terdaftar dalam sistem (terdeteksi duplikasi case-insensitive).'
            ]);
        }

        Commodity::create([
            'id' => (string) Str::uuid(),
            'name' => $normalizedName,
            'category' => trim($request->category),
            'unit' => trim($request->unit),
            'government_ref_price' => $request->government_ref_price,
            'description' => $request->description,
        ]);

        return redirect()->route('admin.commodities.index')
            ->with('success', 'Komoditas master "' . $normalizedName . '" berhasil ditambahkan!');
    }

    public function edit($id)
    {
        $commodity = Commodity::findOrFail($id);

        $dbCategories = CommodityCategory::orderBy('name')->pluck('name')->toArray();
        $fallbackCategories = ['Daging & Unggas', 'Bumbu & Rempah', 'Sayuran', 'Sembako', 'Perikanan & Seafood', 'Telur', 'Lainnya'];
        $categories = array_unique(array_merge($dbCategories, $fallbackCategories));

        $dbUnits = CommodityUnit::orderBy('name')->pluck('name')->toArray();
        $fallbackUnits = ['kg', 'liter', 'pcs', 'dus', 'gram', 'ikat', 'karung', 'pack'];
        $units = array_unique(array_merge($dbUnits, $fallbackUnits));

        return view('admin.commodities.edit', compact('commodity', 'categories', 'units'));
    }

    public function update(Request $request, $id)
    {
        $commodity = Commodity::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'unit' => 'required|string|max:50',
            'government_ref_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $normalizedName = trim($request->name);

        // Case-insensitive duplicate check excluding current ID
        $exists = Commodity::whereRaw('LOWER(name) = ?', [mb_strtolower($normalizedName)])
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'name' => 'Nama komoditas "' . $normalizedName . '" sudah terdaftar pada komoditas lain.'
            ]);
        }

        $commodity->update([
            'name' => $normalizedName,
            'category' => trim($request->category),
            'unit' => trim($request->unit),
            'government_ref_price' => $request->government_ref_price,
            'description' => $request->description,
        ]);

        return redirect()->route('admin.commodities.index')
            ->with('success', 'Data komoditas "' . $normalizedName . '" berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $commodity = Commodity::findOrFail($id);
        $name = $commodity->name;
        $commodity->delete();

        return redirect()->route('admin.commodities.index')
            ->with('success', 'Komoditas master "' . $name . '" berhasil dihapus dari sistem.');
    }
}
