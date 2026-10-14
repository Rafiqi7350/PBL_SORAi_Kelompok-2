@extends('layouts.supplier')

@section('content')
<div class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="space-y-1">
            <span class="text-xs font-mono font-bold uppercase text-[#ea580c] tracking-widest block">
                INVENTARIS BAHAN BAKU
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight uppercase">
                KELOLA STOK KOMODITAS
            </h1>
        </div>
        <div>
            <a href="{{ route('supplier.katalog.create') }}" class="inline-flex items-center gap-2 bg-[#ea580c] hover:bg-[#c2410c] active:scale-[0.98] text-white font-bold text-xs py-3 px-5 rounded-full shadow-sm hover:shadow-md transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Tambah Komoditas Baru</span>
            </a>
        </div>
    </div>

    <!-- Table Section -->
    <div class="bg-white rounded-[2rem] border border-gray-100 shadow-[0_10px_35px_rgba(0,0,0,0.04)] overflow-hidden">
        @if($products->isEmpty())
            <div class="py-16 px-6 text-center space-y-4">
                <div class="w-16 h-16 rounded-full bg-orange-50 text-[#ea580c] flex items-center justify-center mx-auto text-2xl">
                    📦
                </div>
                <div class="space-y-1">
                    <h3 class="text-base font-bold text-gray-900">Belum Ada Komoditas di Katalog</h3>
                    <p class="text-xs text-gray-500 max-w-sm mx-auto">
                        Mulai tambahkan komoditas bahan baku segar Anda agar dapat dilihat dan dipesan oleh mitra UMKM.
                    </p>
                </div>
                <a href="{{ route('supplier.katalog.create') }}" class="inline-flex items-center gap-2 bg-[#ea580c] hover:bg-[#c2410c] text-white font-bold text-xs py-2.5 px-5 rounded-full shadow-sm transition">
                    + Tambah Komoditas Baru
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/70 border-b border-gray-100 text-[11px] font-mono font-bold uppercase text-gray-500 tracking-wider">
                            <th class="py-4 px-6">GAMBAR</th>
                            <th class="py-4 px-6">KOMODITAS & KATEGORI</th>
                            <th class="py-4 px-6">HARGA GROSIR</th>
                            <th class="py-4 px-6">KAPASITAS PASOK</th>
                            <th class="py-4 px-6 text-center">AKSI (CRUD)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm font-medium text-gray-800">
                        @foreach($products as $product)
                            <tr class="hover:bg-orange-50/20 transition-colors">
                                <!-- Gambar -->
                                <td class="py-4 px-6">
                                    <div class="w-14 h-14 rounded-2xl overflow-hidden bg-gray-100 border border-gray-200/80 flex-shrink-0 shadow-2xs">
                                        @if($product->image_url)
                                            <img src="{{ str_starts_with($product->image_url, 'http') ? $product->image_url : asset($product->image_url) }}"
                                                 alt="{{ $product->commodity->name }}"
                                                 class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex flex-col items-center justify-center font-bold text-gray-400 text-[10px] bg-gray-100">
                                                <span>No Img</span>
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                <!-- Komoditas & Kategori -->
                                <td class="py-4 px-6">
                                    <p class="font-bold text-gray-900 text-sm tracking-tight">
                                        {{ $product->commodity->name }}
                                    </p>
                                    @if(isset($product->commodity->category))
                                        <p class="text-[11px] text-gray-500 font-semibold mt-0.5">
                                            {{ $product->commodity->category }}
                                        </p>
                                    @endif
                                </td>

                                <!-- Harga Grosir -->
                                <td class="py-4 px-6 font-bold text-[#ea580c] font-mono text-sm">
                                    Rp {{ number_format($product->price, 0, ',', '.') }}<span class="text-xs text-gray-500 font-normal">/{{ $product->commodity->unit }}</span>
                                </td>

                                <!-- Kapasitas Pasok -->
                                <td class="py-4 px-6 font-mono text-xs font-semibold text-gray-700">
                                    {{ $product->stock_capacity ?? '-' }}
                                </td>

                                <!-- Aksi CRUD -->
                                <td class="py-4 px-6 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- Edit -->
                                        <a href="{{ route('supplier.katalog.edit', $product->id) }}"
                                           class="px-4 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-full font-bold text-xs border border-gray-200/80 flex items-center gap-1.5 transition shadow-2xs">
                                            <svg class="w-3.5 h-3.5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                            </svg>
                                            <span>Edit</span>
                                        </a>

                                        <!-- Hapus -->
                                        <form method="POST" action="{{ route('supplier.katalog.destroy', $product->id) }}" onsubmit="return confirm('Yakin ingin menghapus komoditas ini?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="px-4 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-full font-bold text-xs border border-rose-200/60 flex items-center gap-1.5 transition cursor-pointer shadow-2xs">
                                                <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
                                                <span>Hapus</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($products->hasPages())
                <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                    {{ $products->links() }}
                </div>
            @endif
        @endif
    </div>

</div>
@endsection