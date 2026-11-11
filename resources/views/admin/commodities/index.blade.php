@extends('layouts.admin')

@section('content')
<div class="space-y-6 pb-8">

    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="space-y-1">
            <div class="flex flex-wrap items-center gap-2">
                <p class="text-[11px] font-bold uppercase tracking-widest text-[#ea580c]">Standarisasi Master Data</p>
                <span class="text-slate-300 text-xs">•</span>
                <span class="text-[11px] font-medium text-slate-500">Acuan Bapanas / Siskaperbapo</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Master Data Komoditas Bahan Baku</h1>
            <p class="text-xs sm:text-sm text-slate-500 max-w-2xl">Kelola daftar komoditas standar, kategori acuan, satuan baku, dan patokan harga eceran tertinggi (HET) pemerintah.</p>
        </div>
        <div class="shrink-0">
            <a href="{{ route('admin.commodities.create') }}"
               class="inline-flex items-center gap-2 bg-[#ea580c] hover:bg-[#c2410c] text-white font-semibold text-xs py-2.5 px-4 rounded-xl shadow-xs transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span>Tambah Komoditas Master</span>
            </a>
        </div>
    </div>

    {{-- Summary Metrics Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-5 tabular-nums">
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between gap-4">
            <div class="min-w-0">
                <p class="text-xs font-semibold text-slate-500">Total Komoditas Master</p>
                <p class="text-2xl sm:text-3xl font-bold text-slate-900 mt-1 leading-tight">{{ $totalCommodities }}</p>
                <p class="text-[11px] font-medium text-slate-400 mt-1">Terdaftar di database</p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-orange-50 text-[#ea580c] flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between gap-4">
            <div class="min-w-0">
                <p class="text-xs font-semibold text-slate-500">Kategori Komoditas</p>
                <p class="text-2xl sm:text-3xl font-bold text-slate-900 mt-1 leading-tight">{{ $totalCategories }}</p>
                <p class="text-[11px] font-medium text-emerald-600 mt-1">Sektor bahan baku aktif</p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between gap-4">
            <div class="min-w-0">
                <p class="text-xs font-semibold text-slate-500">Rata-Rata Ref HET Govt</p>
                <p class="text-2xl font-bold text-[#ea580c] mt-1 leading-tight truncate">
                    {{ $avgGovPrice ? 'Rp ' . number_format($avgGovPrice, 0, ',', '.') : '-' }}
                </p>
                <p class="text-[11px] font-medium text-slate-400 mt-1">Acuan Bapanas / Siskaperbapo</p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </div>
        </div>
    </div>

    {{-- Search and Filter Bar --}}
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs">
        <form method="GET" action="{{ route('admin.commodities.index') }}" class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div class="relative flex-1">
                <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="Cari nama komoditas, kategori, atau satuan baku..."
                       class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200/90 rounded-xl focus:bg-white focus:outline-none focus:border-[#ea580c] focus:ring-2 focus:ring-orange-500/20 text-xs font-medium text-slate-900 transition">
            </div>

            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                <select name="category"
                        onchange="this.form.submit()"
                        class="px-3.5 py-2.5 bg-slate-50 border border-slate-200/90 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:outline-none focus:border-[#ea580c] focus:ring-2 focus:ring-orange-500/20 transition cursor-pointer">
                    <option value="Semua">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ $categoryFilter == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>

                <button type="submit"
                        class="bg-[#161922] hover:bg-slate-800 text-white px-4 py-2.5 rounded-xl font-semibold text-xs transition cursor-pointer">
                    Terapkan Filter
                </button>

                @if($search || ($categoryFilter && $categoryFilter !== 'Semua'))
                    <a href="{{ route('admin.commodities.index') }}"
                       class="px-3 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:text-rose-600 hover:bg-rose-50 hover:border-rose-200 transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Commodities Table --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        @if($commodities->isEmpty())
            <div class="px-6 py-16 text-center space-y-3">
                <div class="w-12 h-12 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto text-slate-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Tidak ada komoditas ditemukan</h3>
                    <p class="text-xs text-slate-500 mt-1">Coba sesuaikan kata kunci pencarian atau tambah komoditas master baru.</p>
                </div>
                <a href="{{ route('admin.commodities.create') }}"
                   class="inline-flex items-center gap-1.5 mt-2 bg-[#ea580c] hover:bg-[#c2410c] text-white font-semibold px-4 py-2.5 rounded-xl text-xs transition shadow-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tambah Komoditas Master</span>
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/90 border-b border-slate-200/80 text-[11px] font-semibold uppercase text-slate-500 tracking-wider">
                            <th class="py-3.5 px-6">Nama Komoditas Master</th>
                            <th class="py-3.5 px-6">Kategori</th>
                            <th class="py-3.5 px-6">Satuan Baku</th>
                            <th class="py-3.5 px-6">Harga Acuan Govt (HET)</th>
                            <th class="py-3.5 px-6">Deskripsi / Catatan</th>
                            <th class="py-3.5 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                        @foreach($commodities as $item)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-2.5">
                                        <span class="w-2 h-2 rounded-full bg-[#ea580c] shrink-0"></span>
                                        <span class="font-semibold text-slate-900 text-[13px]">{{ $item->name }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center px-2.5 py-0.5 bg-slate-100 text-slate-700 text-[11px] font-semibold rounded-full">
                                        {{ $item->category }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 font-semibold text-slate-700">
                                    {{ $item->unit }}
                                </td>
                                <td class="py-4 px-6 tabular-nums">
                                    @if($item->government_ref_price)
                                        <span class="font-bold text-[#ea580c] text-[13px] block">
                                            Rp {{ number_format($item->government_ref_price, 0, ',', '.') }}
                                        </span>
                                        <span class="text-[11px] text-slate-400 font-medium">per {{ $item->unit }}</span>
                                    @else
                                        <span class="text-[11px] font-medium text-slate-400">Belum diset</span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-xs text-slate-500 max-w-xs truncate">
                                    {{ $item->description ?? '-' }}
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.commodities.edit', $item->id) }}"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200/90 rounded-xl transition font-semibold text-xs"
                                           title="Edit Komoditas">
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                            <span>Edit</span>
                                        </a>

                                        <form method="POST" action="{{ route('admin.commodities.destroy', $item->id) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200/70 rounded-xl transition font-semibold text-xs cursor-pointer"
                                                    title="Hapus">
                                                <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
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

            @if($commodities->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/40">
                    {{ $commodities->links() }}
                </div>
            @endif
        @endif
    </div>

</div>
@endsection
