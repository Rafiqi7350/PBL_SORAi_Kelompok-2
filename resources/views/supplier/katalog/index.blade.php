@extends('layouts.supplier')

@section('title', 'Kelola Katalog Komoditas — Portal Pemasok SORAI')

@section('content')
<div class="space-y-7">

    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 border-b border-slate-200/80 pb-5">
        <div class="space-y-1">
            <span class="text-[11px] font-bold uppercase text-[#ea580c] tracking-[0.14em] block">
                INVENTARIS BAHAN BAKU
            </span>
            <h1 class="text-2xl sm:text-[26px] font-bold text-slate-900 tracking-tight uppercase">
                KELOLA STOK KOMODITAS
            </h1>
            <p class="text-xs sm:text-[13px] text-slate-500 max-w-2xl leading-relaxed">
                Atur daftar bahan baku grosir, harga jual per satuan, kapasitas suplai mingguan, serta status ketersediaan stok untuk mitra UMKM.
            </p>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <span class="hidden sm:inline-flex items-center gap-2 px-3.5 py-2 rounded-full text-xs font-semibold bg-white text-slate-700 border border-slate-200 shadow-2xs tabular-nums">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>{{ method_exists($products, 'total') ? $products->total() : $products->count() }} Komoditas</span>
            </span>

            <a href="{{ route('supplier.katalog.create') }}"
               data-cy="btn-tambah-komoditas"
               class="inline-flex items-center gap-2 bg-[#ea580c] hover:bg-[#c2410c] text-white font-semibold text-xs py-2.5 px-5 rounded-xl shadow-2xs transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Tambah Komoditas Baru</span>
            </a>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)] overflow-hidden">
        @if($products->isEmpty())
            <div class="py-16 px-6 text-center space-y-4">
                <div class="w-14 h-14 rounded-2xl bg-orange-50 text-[#ea580c] border border-orange-100 flex items-center justify-center mx-auto">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <div class="space-y-1">
                    <h3 class="text-base font-bold text-slate-900">Belum Ada Komoditas di Katalog</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto leading-relaxed">
                        Mulai tambahkan komoditas bahan baku segar Anda agar dapat dilihat dan dipesan oleh mitra UMKM.
                    </p>
                </div>
                <a href="{{ route('supplier.katalog.create') }}"
                   class="inline-flex items-center gap-2 bg-[#ea580c] hover:bg-[#c2410c] text-white font-semibold text-xs py-2.5 px-5 rounded-xl shadow-2xs transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>Tambah Komoditas Baru</span>
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200/80 text-[11px] font-bold uppercase text-slate-500 tracking-wider">
                            <th class="py-3.5 px-6">GAMBAR</th>
                            <th class="py-3.5 px-6">KOMODITAS &amp; KATEGORI</th>
                            <th class="py-3.5 px-6">HARGA GROSIR</th>
                            <th class="py-3.5 px-6">KAPASITAS PASOK</th>
                            <th class="py-3.5 px-6 text-center">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-800">
                        @foreach($products as $product)
                            <tr class="hover:bg-orange-50/30 transition-colors">
                                {{-- Gambar --}}
                                <td class="py-4 px-6">
                                    <div class="w-13 h-13 rounded-xl overflow-hidden bg-slate-100 border border-slate-200/80 flex-shrink-0">
                                        @if($product->display_image_url)
                                            <img src="{{ $product->display_image_url }}"
                                                 alt="{{ $product->commodity->name }}"
                                                 class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex flex-col items-center justify-center font-semibold text-slate-400 text-[10px] bg-slate-100">
                                                <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                {{-- Komoditas & Kategori --}}
                                <td class="py-4 px-6">
                                    <p class="font-bold text-slate-900 text-sm tracking-tight">
                                        {{ $product->commodity->name }}
                                    </p>
                                    @if(isset($product->commodity->category))
                                        <p class="text-[11px] text-slate-500 font-medium mt-0.5">
                                            {{ $product->commodity->category }}
                                        </p>
                                    @endif
                                </td>

                                {{-- Harga Grosir --}}
                                <td class="py-4 px-6 font-bold text-[#ea580c] text-sm tabular-nums whitespace-nowrap">
                                    Rp {{ number_format($product->price, 0, ',', '.') }}<span class="text-xs text-slate-500 font-normal">/{{ $product->commodity->unit }}</span>
                                </td>

                                {{-- Kapasitas Pasok & Quick Stock Toggle --}}
                                <td class="py-4 px-6 text-xs font-semibold text-slate-700 tabular-nums">
                                    <div class="space-y-1.5">
                                        <p>{{ $product->stock_capacity ?? '-' }}</p>
                                        <form method="POST" action="{{ route('supplier.katalog.quick-stock', $product->id) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="stock_status" value="{{ $product->stock_status === 'Tersedia' ? 'Stok Habis' : 'Tersedia' }}">
                                            <button type="submit"
                                                    data-cy="quick-stock-btn"
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold border transition cursor-pointer {{ $product->stock_status === 'Tersedia' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100' }}"
                                                    title="Klik untuk ubah cepat status stok">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $product->stock_status === 'Tersedia' ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                                <span>{{ $product->stock_status === 'Tersedia' ? 'TERSEDIA' : 'HABIS' }}</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>

                                {{-- Aksi CRUD --}}
                                <td class="py-4 px-6 text-center">
                                    <div class="inline-flex items-center justify-center gap-2">
                                        <a href="{{ route('supplier.katalog.edit', $product->id) }}"
                                           class="px-3.5 py-1.5 bg-white hover:bg-slate-100 text-slate-700 rounded-lg font-semibold text-xs border border-slate-200 inline-flex items-center gap-1.5 transition">
                                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                            </svg>
                                            <span>Edit</span>
                                        </a>

                                        <form method="POST" action="{{ route('supplier.katalog.destroy', $product->id) }}" onsubmit="return confirm('Yakin ingin menghapus komoditas ini?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="px-3.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg font-semibold text-xs border border-rose-200/80 inline-flex items-center gap-1.5 transition cursor-pointer">
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

            {{-- Pagination --}}
            @if($products->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/70">
                    {{ $products->links() }}
                </div>
            @endif
        @endif
    </div>

</div>
@endsection
