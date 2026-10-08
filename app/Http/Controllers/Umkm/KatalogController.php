<?php

namespace App\Http\Controllers\Umkm;

use App\Http\Controllers\Controller;
use App\Models\Commodity;
use App\Models\Partnership;
use App\Models\SupplierProduct;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KatalogController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $category = $request->input('category');
        $maxPrice = $request->input('max_price');
        $stockStatus = $request->input('stock_status');
        $isHalal = $request->boolean('is_halal');
        $sort = $request->input('sort', 'terbaru');

        $query = SupplierProduct::with(['supplier.user', 'commodity']);

        // Search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('commodity', function ($cq) use ($search) {
                    $cq->where('name', 'like', '%'.$search.'%')
                        ->orWhere('category', 'like', '%'.$search.'%');
                })->orWhereHas('supplier', function ($sq) use ($search) {
                    $sq->where('company_name', 'like', '%'.$search.'%')
                        ->orWhere('warehouse_address', 'like', '%'.$search.'%')
                        ->orWhere('delivery_area', 'like', '%'.$search.'%');
                })->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('quality_grade', 'like', '%'.$search.'%');
            });
        }

        // Category filter
        if ($category && $category !== 'Semua Kategori') {
            $query->whereHas('commodity', function ($q) use ($category) {
                $q->where('category', $category);
            });
        }

        // Stock Status filter
        if ($stockStatus && $stockStatus !== 'Semua Status') {
            $query->where('stock_status', $stockStatus);
        }

        // Halal filter
        if ($isHalal) {
            $query->where('is_halal_certified', true);
        }

        // Max Price filter
        if ($maxPrice && is_numeric($maxPrice)) {
            $query->where('price', '<=', $maxPrice);
        }

        // Sorting
        switch ($sort) {
            case 'harga_terendah':
                $query->orderBy('price', 'asc');
                break;
            case 'harga_tertinggi':
                $query->orderBy('price', 'desc');
                break;
            case 'kapasitas_terbesar':
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

        return view('umkm.katalog.index', compact(
            'products',
            'categories',
            'search',
            'category',
            'maxPrice',
            'stockStatus',
            'isHalal',
            'sort'
        ));
    }

    public function ajukanKemitraan(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:supplier_profiles,id',
            'supplier_product_id' => 'required|exists:supplier_products,id',
            'volume' => 'required|numeric|min:1',
            'unit' => 'required|string',
            'frekuensi' => 'required|string',
            'submission_details' => 'nullable|string',
        ]);

        $product = SupplierProduct::find($request->input('supplier_product_id'));
        if (!$product || !in_array($product->stock_status, ['Tersedia', 'Stok Terbatas'])) {
            return redirect()->back()->with('error', 'Mohon maaf, komoditas ini sedang tidak tersedia (stok habis).');
        }

        // MOCK LOGIN FOR DEMO PURPOSES:
        // Automatically login the seeded UMKM user if nobody is logged in
        if (! Auth::check()) {
            $dummyUmkmUser = User::where('role', 'umkm')->first();
            if ($dummyUmkmUser) {
                Auth::login($dummyUmkmUser);
            }
        }

        $umkm = Auth::user()?->umkmProfile;

        if (! $umkm) {
            return redirect()->back()->with('error', 'Anda harus memiliki profil UMKM untuk mengajukan kemitraan.');
        }

        $deliveryNotes = $request->input('volume').' '.$request->input('unit').' | Frekuensi: '.$request->input('frekuensi');

        Partnership::create([
            'umkm_id' => $umkm->id,
            'supplier_id' => $request->input('supplier_id'),
            'submission_details' => $request->input('submission_details'),
            'delivery_schedule_notes' => $deliveryNotes,
            'status' => 'pending',
        ]);

        return redirect()->route('umkm.katalog.index')->with('success', 'Pengajuan kemitraan pasokan berhasil dikirim ke pemasok!');
    }
}
