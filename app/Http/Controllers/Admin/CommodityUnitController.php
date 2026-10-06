<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
            'symbol' => 'nullable|string|max:20',
            'description' => 'nullable|string',
        ]);

        CommodityUnit::create([
            'name' => trim($request->name),
            'symbol' => $request->symbol,
            'description' => $request->description,
        ]);

        return redirect()->route('admin.units.index')
            ->with('success', 'Satuan baku baru berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $unit = CommodityUnit::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:50|unique:commodity_units,name,' . $id,
            'symbol' => 'nullable|string|max:20',
            'description' => 'nullable|string',
        ]);

        $unit->update([
            'name' => trim($request->name),
            'symbol' => $request->symbol,
            'description' => $request->description,
        ]);

        return redirect()->route('admin.units.index')
            ->with('success', 'Satuan baku berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $unit = CommodityUnit::findOrFail($id);
        $unit->delete();

        return redirect()->route('admin.units.index')
            ->with('success', 'Satuan baku berhasil dihapus.');
    }
}
