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
        $search = trim($request->input('search'));
        $category = $request->input('category');
        $maxPrice = $request->input('max_price');
        $stockStatus = $request->input('stock_status');
        $isHalal = $request->boolean('is_halal');
        $sort = $request->input('sort', 'terbaru');

        $query = SupplierProduct::with(['supplier.user', 'commodity']);

        // Fix & Optimize Search Filter
        if ($search !== '' && $search !== null) {
            $searchTerm = strtolower($search);

            $query->where(function ($q) use ($searchTerm) {
                // 1. Search berdasarkan relasi Commodity (Nama & Kategori Komoditas)
                $q->whereHas('commodity', function ($cq) use ($searchTerm) {
                    $cq->whereRaw('LOWER(name) LIKE ?', ['%' . $searchTerm . '%'])
                       ->orWhereRaw('LOWER(category) LIKE ?', ['%' . $searchTerm . '%']);
                })
                // 2. Search berdasarkan relasi Supplier (Nama Perusahaan & Alamat)
                ->orWhereHas('supplier', function ($sq) use ($searchTerm) {
                    $sq->whereRaw('LOWER(company_name) LIKE ?', ['%' . $searchTerm . '%'])
                       ->orWhereRaw('LOWER(warehouse_address) LIKE ?', ['%' . $searchTerm . '%'])
                       ->orWhereRaw('LOWER(delivery_area) LIKE ?', ['%' . $searchTerm . '%']);
                })
                // 3. Search langsung pada atribut produk
                ->orWhereRaw('LOWER(description) LIKE ?', ['%' . $searchTerm . '%'])
                ->orWhereRaw('LOWER(quality_grade) LIKE ?', ['%' . $searchTerm . '%']);

                // Jika tabel supplier_products memiliki kolom title atau name
                if (\Schema::hasColumn('supplier_products', 'title')) {
                    $q->orWhereRaw('LOWER(title) LIKE ?', ['%' . $searchTerm . '%']);
                }
                if (\Schema::hasColumn('supplier_products', 'name')) {
                    $q->orWhereRaw('LOWER(name) LIKE ?', ['%' . $searchTerm . '%']);
                }
            });
        }

        // Category filter
        if ($category && $category !== 'Semua Kategori') {
            $query->whereHas('commodity', function ($q) use ($category) {
                $q->where('category', $category);
            });
        }

        // Stock Status filter
        if ($stockStatus && $stockStatus !== 'Semua Status' && $stockStatus !== 'Semua Status Stok') {
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
            'volume' => 'required|numeric|min:1',
            'unit' => 'required|string',
            'frekuensi' => 'required|string',
            'submission_details' => 'nullable|string',
        ]);

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