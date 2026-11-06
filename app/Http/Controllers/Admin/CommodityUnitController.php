<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commodity;
use App\Models\CommodityUnit;
use Illuminate\Http\Request;

class CommodityUnitController extends Controller
{
    public function index()
    {
        $units = CommodityUnit::latest()->paginate(10);

        return view('admin.units.index', compact('units'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:commodity_units,name',
            'symbol' => 'required|string|max:20',
            'description' => 'nullable|string',
        ], [
            'name.required' => 'Nama satuan baku wajib diisi.',
            'name.unique' => 'Nama satuan baku sudah terdaftar.',
            'symbol.required' => 'Simbol satuan baku wajib diisi.',
        ]);

        CommodityUnit::create([
            'name' => trim($request->name),
            'symbol' => $request->symbol,
            'description' => $request->description,
        ]);

        return redirect('/admin?tab=master')
            ->with('success', 'Satuan baku baru berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $unit = CommodityUnit::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:50|unique:commodity_units,name,'.$id,
            'symbol' => 'nullable|string|max:20',
            'description' => 'nullable|string',
        ]);

        $unit->update([
            'name' => trim($request->name),
            'symbol' => $request->symbol,
            'description' => $request->description,
        ]);

        return redirect('/admin?tab=master')
            ->with('success', 'Satuan baku berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $unit = CommodityUnit::findOrFail($id);

        if (Commodity::where('unit', $unit->name)->orWhere('unit', $unit->symbol)->exists()) {
            return redirect('/admin?tab=master')
                ->with('error', 'Satuan baku "'.$unit->name.'" tidak dapat dihapus karena masih digunakan oleh komoditas master.');
        }

        $unit->delete();

        return redirect('/admin?tab=master')
            ->with('success', 'Satuan baku berhasil dihapus.');
    }
}
