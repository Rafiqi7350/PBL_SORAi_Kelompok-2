<?php

namespace App\Http\Controllers\Umkm;

use App\Http\Controllers\Controller;

use App\Models\Commodity;
use App\Models\Partnership;
use App\Models\SupplierProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KatalogController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $category = $request->input('category');
        $maxPrice = $request->input('max_price');
        $sort = $request->input('sort', 'terbaru');

        $query = SupplierProduct::with(['supplier.user', 'commodity']);

        // Search by commodity name, supplier company name, or warehouse address
        if ($search) {
            $query->whereHas('commodity', function ($q) use ($search) {
                $q->where('name', 'ilike', '%' . $search . '%');
            })->orWhereHas('supplier', function ($q) use ($search) {
                $q->where('company_name', 'ilike', '%' . $search . '%')
                  ->orWhere('warehouse_address', 'ilike', '%' . $search . '%');
            });
        }

        // Filter by category
        if ($category && $category !== 'Semua Kategori') {
            $query->whereHas('commodity', function ($q) use ($category) {
                $q->where('category', $category);
            });
        }
        
        // Filter by Max Price
        if ($maxPrice && is_numeric($maxPrice)) {
            $query->where('price', '<=', $maxPrice);
        }

        // Sorting
        switch ($sort) {
            case 'harga_terendah':
                $query->orderBy('price', 'asc');
                break;
            case 'kapasitas_terbesar':
                // Note: stock_capacity is a string, so this sorting might not be perfect in DB, 
                // but we provide it as requested. Ideally it should be numeric.
                $query->orderBy('stock_capacity', 'desc');
                break;
            case 'terbaru':
            default:
                $query->latest();
                break;
        }

        $products = $query->paginate(12)->withQueryString();
        
        // Get unique categories for the filter
        $categories = Commodity::select('category')->distinct()->pluck('category');

        return view('umkm.katalog.index', compact('products', 'categories', 'search', 'category', 'maxPrice', 'sort'));
    }

    public function ajukanKemitraan(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:supplier_profiles,id',
            'volume' => 'required|numeric',
            'unit' => 'required|string',
            'frekuensi' => 'required|string',
            'submission_details' => 'nullable|string',
        ]);

        // MOCK LOGIN FOR DEMO PURPOSES: 
        // Automatically login the seeded UMKM user if nobody is logged in
        if (!Auth::check()) {
            $dummyUmkmUser = \App\Models\User::where('role', 'umkm')->first();
            if ($dummyUmkmUser) {
                Auth::login($dummyUmkmUser);
            }
        }

        $umkm = Auth::user()?->umkmProfile;

        if (!$umkm) {
            return redirect()->back()->with('error', 'Anda harus memiliki profil UMKM untuk mengajukan kemitraan.');
        }

        $deliveryNotes = $request->input('volume') . ' ' . $request->input('unit') . ' | Frekuensi: ' . $request->input('frekuensi');

        Partnership::create([
            'umkm_id' => $umkm->id,
            'supplier_id' => $request->input('supplier_id'),
            'submission_details' => $request->input('submission_details'),
            'delivery_schedule_notes' => $deliveryNotes,
            'status' => 'pending',
        ]);

        return redirect()->route('umkm.katalog.index')->with('success', 'Pengajuan kemitraan berhasil dikirim!');
    }
}
