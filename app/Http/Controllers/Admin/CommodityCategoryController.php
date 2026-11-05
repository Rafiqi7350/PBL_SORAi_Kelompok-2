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
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.unique' => 'Nama kategori sudah terdaftar.',
        ]);

        CommodityCategory::create([
            'name' => trim($request->name),
            'slug' => Str::slug($request->name),
            'description' => $request->description,
        ]);

        return redirect('/admin?tab=master')
            ->with('success', 'Kategori komoditas baru berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $category = CommodityCategory::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:100|unique:commodity_categories,name,'.$id,
            'description' => 'nullable|string',
        ]);

        $category->update([
            'name' => trim($request->name),
            'slug' => Str::slug($request->name),
            'description' => $request->description,
        ]);

        return redirect('/admin?tab=master')
            ->with('success', 'Kategori komoditas berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $category = CommodityCategory::findOrFail($id);

        if (\App\Models\Commodity::where('category', $category->name)->exists()) {
            return redirect('/admin?tab=master')
                ->with('error', 'Kategori "'.$category->name.'" tidak dapat dihapus karena masih digunakan oleh komoditas master.');
        }

        $category->delete();

        return redirect('/admin?tab=master')
            ->with('success', 'Kategori komoditas berhasil dihapus.');
    }
}
