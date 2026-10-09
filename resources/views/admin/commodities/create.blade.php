@extends('layouts.admin')

@section('content')
<div class="max-w-3xl w-full mx-auto space-y-6 pb-12">

    <div>
        <a href="{{ route('admin.commodities.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-slate-900 mb-3 transition">
            &larr; Kembali ke Master Komoditas
        </a>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Tambah Komoditas Master Baru</h1>
        <p class="text-xs sm:text-sm font-medium text-slate-500 mt-1">Daftarkan komoditas bahan baku acuan baru dengan nama unik, kategori, satuan baku, dan harga acuan pemerintah.</p>
    </div>

    <div class="bg-white rounded-[1.75rem] p-8 border border-gray-100 shadow-[0_10px_35px_rgba(0,0,0,0.03)]">
        <form method="POST" action="{{ route('admin.commodities.store') }}" class="space-y-6">
            @csrf

            <!-- Name -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Nama Komoditas Bahan Baku (*)</label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="Contoh: Daging Ayam Broiler Segar" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-bold text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 text-slate-900">
                <p class="text-[11px] font-medium text-slate-400 mt-1 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-orange-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Nama komoditas distandarisasi untuk mencegah duplikasi (pemeriksaan bersifat case-insensitive).</span>
                </p>
                @error('name')
                    <p class="text-xs text-rose-600 font-bold mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Category & Unit Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Category -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Kategori Komoditas (*)</label>
                    <input type="text" list="category-list" name="category" value="{{ old('category') }}" required placeholder="Contoh: Daging & Unggas" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-bold text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 text-slate-900">
                    <datalist id="category-list">
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}"></option>
                        @endforeach
                    </datalist>
                    @error('category')
                        <p class="text-xs text-rose-600 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Unit -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Satuan Baku (*)</label>
                    <input type="text" list="unit-list" name="unit" value="{{ old('unit', 'kg') }}" required placeholder="Contoh: kg, liter, pcs, dus" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-bold text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 text-slate-900">
                    <datalist id="unit-list">
                        @foreach($units as $u)
                            <option value="{{ $u }}"></option>
                        @endforeach
                    </datalist>
                    @error('unit')
                        <p class="text-xs text-rose-600 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Government Reference Price -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Harga Acuan Pemerintah / HET (Rp per Satuan)</label>
                <input type="number" step="100" name="government_ref_price" value="{{ old('government_ref_price') }}" placeholder="Contoh: 36000" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-bold text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 text-slate-900">
                <p class="text-[11px] font-medium text-slate-400 mt-1">Diambil dari data resmi Bapanas / Siskaperbapo untuk acuan perbandingan harga pemasok.</p>
                @error('government_ref_price')
                    <p class="text-xs text-rose-600 font-bold mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Deskripsi / Catatan Standar Spesifikasi</label>
                <textarea name="description" rows="3" placeholder="Tambahkan rincian standar spesifikasi umum komoditas ini..." class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-medium text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 text-slate-900">{{ old('description') }}</textarea>
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-end gap-4 pt-4 border-t border-slate-100">
                <a href="{{ route('admin.commodities.index') }}" class="px-6 py-3 rounded-xl font-bold text-xs bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                    Batal
                </a>
                <button type="submit" class="px-7 py-3 rounded-xl font-bold text-xs bg-[#ea580c] hover:bg-orange-600 text-white transition shadow-sm cursor-pointer">
                    Simpan Komoditas Master &rarr;
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
