@extends('layouts.admin')

@section('content')
<div class="max-w-7xl w-full mx-auto space-y-6 pb-12">

    <!-- Header & Action -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-orange-600 text-[10px] font-bold tracking-widest uppercase bg-orange-50 px-3 py-1 rounded-full border border-orange-100">Administrator Master Data</span>
                <span class="text-xs font-semibold text-slate-400">| Acuan Bapanas / Siskaperbapo</span>
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 tracking-tight">Master Data Komoditas Bahan Baku</h1>
            <p class="text-xs sm:text-sm font-medium text-slate-500 mt-1">Kelola data komoditas standar, kategori, satuan baku, dan patokan harga acuan pemerintah.</p>
        </div>
        <div>
            <a href="{{ route('admin.commodities.create') }}" class="inline-flex items-center gap-2 bg-[#ea580c] hover:bg-orange-600 text-white font-bold text-xs py-3 px-5 rounded-xl shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                <span>Tambah Komoditas Master</span>
            </a>
        </div>
    </div>

    <!-- Summary Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="bg-white rounded-[1.75rem] p-6 border border-gray-100 shadow-[0_10px_35px_rgba(0,0,0,0.03)] flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Komoditas Master</p>
                <p class="text-3xl font-extrabold text-slate-900 mt-1">{{ $totalCommodities }}</p>
                <p class="text-xs font-medium text-slate-400 mt-1">Terdaftar di Database</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-orange-100/80 flex items-center justify-center text-orange-600 font-bold">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </div>
        </div>

        <div class="bg-white rounded-[1.75rem] p-6 border border-gray-100 shadow-[0_10px_35px_rgba(0,0,0,0.03)] flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Kategori Komoditas</p>
                <p class="text-3xl font-extrabold text-emerald-600 mt-1">{{ $totalCategories }}</p>
                <p class="text-xs font-medium text-emerald-600 mt-1">Sektor Bahan Baku</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-100/80 flex items-center justify-center text-emerald-600 font-bold">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
            </div>
        </div>

        <div class="bg-white rounded-[1.75rem] p-6 border border-gray-100 shadow-[0_10px_35px_rgba(0,0,0,0.03)] flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Rata-Rata Ref HET Govt</p>
                <p class="text-2xl font-extrabold text-orange-600 mt-1">
                    {{ $avgGovPrice ? 'Rp ' . number_format($avgGovPrice, 0, ',', '.') : '-' }}
                </p>
                <p class="text-xs font-medium text-slate-400 mt-1">Acuan Bapanas/Siskaperbapo</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-rose-100/80 flex items-center justify-center text-rose-600 font-bold">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 012 2h2a2 2 0 012-2z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Search and Filter Bar -->
    <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-[0_10px_35px_rgba(0,0,0,0.03)]">
        <form method="GET" action="{{ route('admin.commodities.index') }}" class="flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="relative w-full md:w-1/2">
                <svg class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama komoditas, kategori, atau satuan baku..." class="w-full pl-11 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 text-xs font-semibold text-slate-900">
            </div>

            <div class="flex items-center gap-3 w-full md:w-auto">
                <select name="category" onchange="this.form.submit()" class="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <option value="Semua">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ $categoryFilter == $cat ? 'selected' : '' }}>{{ strtoupper($cat) }}</option>
                    @endforeach
                </select>

                <button type="submit" class="bg-slate-900 text-white px-5 py-2.5 rounded-xl font-bold text-xs hover:bg-black transition cursor-pointer">
                    Filter
                </button>

                @if($search || ($categoryFilter && $categoryFilter !== 'Semua'))
                    <a href="{{ route('admin.commodities.index') }}" class="text-xs font-bold text-rose-600 hover:underline">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Commodities Table -->
    <div class="bg-white rounded-[1.75rem] border border-gray-100 shadow-[0_10px_35px_rgba(0,0,0,0.03)] overflow-hidden">
        @if($commodities->isEmpty())
            <div class="p-12 text-center">
                <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900">Tidak ada komoditas ditemukan</h3>
                <p class="text-xs font-medium text-slate-400 mt-1">Coba sesuaikan kata kunci pencarian atau tambah komoditas baru.</p>
                <a href="{{ route('admin.commodities.create') }}" class="inline-block mt-4 bg-[#ea580c] text-white font-bold px-5 py-2.5 rounded-xl text-xs hover:bg-orange-600 transition shadow-sm">
                    + Tambah Komoditas Sekarang
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100 text-[10px] font-bold uppercase text-slate-400 tracking-wider">
                            <th class="py-4 px-6">Nama Komoditas Master</th>
                            <th class="py-4 px-6">Kategori</th>
                            <th class="py-4 px-6">Satuan Baku</th>
                            <th class="py-4 px-6">Harga Acuan Govt (HET)</th>
                            <th class="py-4 px-6">Deskripsi / Catatan</th>
                            <th class="py-4 px-6 text-right">Aksi Management</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                        @foreach($commodities as $item)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-2.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-orange-500"></span>
                                        <span class="font-extrabold text-slate-900 text-sm uppercase">{{ $item->name }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="px-3 py-1 bg-slate-100 text-slate-700 text-[11px] font-bold rounded-full uppercase">
                                        {{ $item->category }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 font-mono font-bold text-slate-800">
                                    {{ $item->unit }}
                                </td>
                                <td class="py-4 px-6">
                                    @if($item->government_ref_price)
                                        <span class="font-extrabold text-orange-600 text-sm">
                                            Rp {{ number_format($item->government_ref_price, 0, ',', '.') }}
                                        </span>
                                        <span class="text-[10px] text-slate-400 font-semibold block">per {{ $item->unit }}</span>
                                    @else
                                        <span class="text-[11px] font-mono text-slate-400">Belum Diset</span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-xs text-slate-500 max-w-xs truncate">
                                    {{ $item->description ?? '-' }}
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Edit -->
                                        <a href="{{ route('admin.commodities.edit', $item->id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl transition font-bold text-xs" title="Edit Komoditas">
                                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                            <span>Edit</span>
                                        </a>

                                        <!-- Delete -->
                                        <form method="POST" action="{{ route('admin.commodities.destroy', $item->id) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-xl transition font-bold text-xs cursor-pointer" title="Hapus">
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

            <!-- Pagination -->
            <div class="p-6 border-t border-slate-100">
                {{ $commodities->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
