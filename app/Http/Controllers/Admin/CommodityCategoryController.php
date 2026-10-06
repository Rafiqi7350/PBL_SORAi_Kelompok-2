<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommodityCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CommodityCategoryController extends Controller
{
    public function index()
    {
        $categories = CommodityCategory::latest()->paginate(10);
        return view('admin.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:commodity_categories,name',
            'description' => 'nullable|string',
        ]);

        CommodityCategory::create([
            'name' => trim($request->name),
            'slug' => Str::slug($request->name),
            'description' => $request->description,
        ]);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Kategori komoditas baru berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $category = CommodityCategory::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:100|unique:commodity_categories,name,' . $id,
            'description' => 'nullable|string',
        ]);

        $category->update([
            'name' => trim($request->name),
            'slug' => Str::slug($request->name),
            'description' => $request->description,
        ]);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Kategori komoditas berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $category = CommodityCategory::findOrFail($id);
        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Kategori komoditas berhasil dihapus.');
    }
}
