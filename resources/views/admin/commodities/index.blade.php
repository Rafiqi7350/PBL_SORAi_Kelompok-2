@extends('layouts.admin')

@section('content')
<div class="max-w-7xl w-full mx-auto space-y-6 pb-12">

    <!-- Header & Action -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-[#F4511E] text-xs font-black tracking-widest uppercase bg-[#F4511E]/10 px-3 py-1 rounded-full border border-[#F4511E]/20">Administrator Master Data</span>
                <span class="text-xs font-bold text-gray-500 font-mono">| Acuan Bapanas / Siskaperbapo</span>
            </div>
            <h1 class="text-3xl md:text-4xl font-extrabold text-[#1E222D] tracking-tight uppercase">Master Data Komoditas Bahan Baku</h1>
            <p class="text-sm font-medium text-gray-600 mt-1">Kelola data komoditas standar, kategori, satuan baku, dan patokan harga acuan pemerintah.</p>
        </div>
        <div>
            <a href="{{ route('admin.commodities.create') }}" class="inline-flex items-center gap-2 bg-[#FFB703] hover:bg-[#FFB703]/90 text-black border-2 border-black font-extrabold text-sm py-3.5 px-6 rounded-full shadow-[4px_4px_0px_0px_rgba(30,34,45,1)] transition transform hover:-translate-y-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                + Tambah Komoditas Master
            </a>
        </div>
    </div>

    <!-- Summary Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="bg-white rounded-3xl p-6 border-2 border-black shadow-[4px_4px_0px_0px_rgba(30,34,45,1)] flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Komoditas Master</p>
                <p class="text-3xl font-black text-[#1E222D] mt-1">{{ $totalCommodities }}</p>
                <p class="text-xs font-semibold text-gray-400 mt-1">Terdaftar di Database</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-100 flex items-center justify-center text-amber-700 font-black text-xl">
                🌾
            </div>
        </div>

        <div class="bg-white rounded-3xl p-6 border-2 border-black shadow-[4px_4px_0px_0px_rgba(30,34,45,1)] flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Kategori Komoditas</p>
                <p class="text-3xl font-black text-emerald-700 mt-1">{{ $totalCategories }}</p>
                <p class="text-xs font-semibold text-emerald-600 mt-1">Sektor Bahan Baku</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-100 flex items-center justify-center text-emerald-700 font-black text-xl">
                🏷️
            </div>
        </div>

        <div class="bg-white rounded-3xl p-6 border-2 border-black shadow-[4px_4px_0px_0px_rgba(30,34,45,1)] flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Rata-Rata Ref HET Govt</p>
                <p class="text-2xl font-black text-[#F4511E] mt-1">
                    {{ $avgGovPrice ? 'Rp ' . number_format($avgGovPrice, 0, ',', '.') : '-' }}
                </p>
                <p class="text-xs font-semibold text-gray-400 mt-1">Acuan Bapanas/Siskaperbapo</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-rose-100 flex items-center justify-center text-rose-700 font-black text-xl">
                📊
            </div>
        </div>
    </div>

    <!-- Search and Filter Bar -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border-2 border-black shadow-[4px_4px_0px_0px_rgba(30,34,45,1)]">
        <form method="GET" action="{{ route('admin.commodities.index') }}" class="flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="relative w-full md:w-1/2">
                <svg class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama komoditas, kategori, atau satuan baku..." class="w-full pl-12 pr-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-full focus:outline-none focus:border-[#FFB703] text-sm font-bold">
            </div>

            <div class="flex items-center gap-3 w-full md:w-auto">
                <select name="category" onchange="this.form.submit()" class="px-5 py-3 bg-[#FFFDF5] border-2 border-black rounded-full text-xs font-extrabold text-[#1E222D]">
                    <option value="Semua">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ $categoryFilter == $cat ? 'selected' : '' }}>{{ strtoupper($cat) }}</option>
                    @endforeach
                </select>

                <button type="submit" class="bg-[#1E222D] text-white px-6 py-3 rounded-full font-bold text-xs hover:bg-black transition">
                    Filter
                </button>

                @if($search || ($categoryFilter && $categoryFilter !== 'Semua'))
                    <a href="{{ route('admin.commodities.index') }}" class="text-xs font-bold text-rose-600 hover:underline">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Commodities Table -->
    <div class="bg-white rounded-3xl border-2 border-black shadow-[4px_4px_0px_0px_rgba(30,34,45,1)] overflow-hidden">
        @if($commodities->isEmpty())
            <div class="p-12 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl">🌾</div>
                <h3 class="text-lg font-extrabold text-[#1E222D]">Tidak ada komoditas ditemukan</h3>
                <p class="text-sm font-medium text-gray-500 mt-1">Coba sesuaikan kata kunci pencarian atau tambah komoditas baru.</p>
                <a href="{{ route('admin.commodities.create') }}" class="inline-block mt-4 bg-[#FFB703] text-black font-extrabold px-6 py-2.5 rounded-full text-xs hover:bg-[#FFB703]/90">
                    + Tambah Komoditas Sekarang
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#FFFDF5] border-b-2 border-black text-[11px] font-black uppercase text-gray-600 tracking-wider">
                            <th class="py-4 px-6">Nama Komoditas Master</th>
                            <th class="py-4 px-6">Kategori</th>
                            <th class="py-4 px-6">Satuan Baku</th>
                            <th class="py-4 px-6">Harga Acuan Govt (HET)</th>
                            <th class="py-4 px-6">Deskripsi / Catatan</th>
                            <th class="py-4 px-6 text-right">Aksi Management</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm font-medium text-gray-800">
                        @foreach($commodities as $item)
                            <tr class="hover:bg-amber-50/40 transition">
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-[#FFB703]"></span>
                                        <span class="font-black text-[#1E222D] text-base uppercase">{{ $item->name }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="px-3 py-1 bg-gray-100 text-[#1E222D] text-xs font-black rounded-full uppercase border border-gray-200">
                                        {{ $item->category }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 font-mono font-bold text-gray-900">
                                    {{ $item->unit }}
                                </td>
                                <td class="py-4 px-6">
                                    @if($item->government_ref_price)
                                        <span class="font-extrabold text-[#F4511E] text-base">
                                            Rp {{ number_format($item->government_ref_price, 0, ',', '.') }}
                                        </span>
                                        <span class="text-[10px] text-gray-400 font-bold block">per {{ $item->unit }}</span>
                                    @else
                                        <span class="text-xs font-mono text-gray-400">Belum Diset</span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-xs text-gray-500 max-w-xs truncate">
                                    {{ $item->description ?? '-' }}
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Edit -->
                                        <a href="{{ route('admin.commodities.edit', $item->id) }}" class="p-2 bg-gray-100 hover:bg-amber-200 text-gray-900 rounded-xl transition font-bold" title="Edit Komoditas">
                                            ✏️ Edit
                                        </a>

                                        <!-- Delete -->
                                        <form method="POST" action="{{ route('admin.commodities.destroy', $item->id) }}" onsubmit="return confirm('Yakin ingin menghapus komoditas {{ $item->name }}? Tindakan ini tidak dapat dibatalkan.');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 bg-rose-50 hover:bg-rose-200 text-rose-700 rounded-xl transition font-bold" title="Hapus">
                                                🗑️ Hapus
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
            <div class="p-6 border-t-2 border-gray-100">
                {{ $commodities->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
