@extends('layouts.admin')

@section('content')
<div class="max-w-3xl w-full mx-auto space-y-6 pb-12">

    <div>
        <a href="{{ route('admin.commodities.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-gray-500 hover:text-black mb-3">
            &larr; Kembali ke Master Komoditas
        </a>
        <h1 class="text-3xl font-extrabold text-[#1E222D] uppercase tracking-tight">Tambah Komoditas Master Baru</h1>
        <p class="text-sm font-medium text-gray-600 mt-1">Daftarkan komoditas bahan baku acuan baru dengan nama unik, kategori, satuan baku, dan harga acuan pemerintah.</p>
    </div>

    <div class="bg-white rounded-3xl p-8 border-2 border-black shadow-[6px_6px_0px_0px_rgba(30,34,45,1)]">
        <form method="POST" action="{{ route('admin.commodities.store') }}" class="space-y-6">
            @csrf

            <!-- Name -->
            <div>
                <label class="block text-xs font-extrabold text-[#1E222D] uppercase tracking-wider mb-2">Nama Komoditas Bahan Baku (*)</label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="Contoh: Daging Ayam Broiler Segar" class="w-full px-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-xl font-bold text-base focus:outline-none focus:border-[#FFB703]">
                <p class="text-[11px] font-semibold text-gray-500 mt-1">💡 Nama komoditas distandarisasi untuk mencegah duplikasi (pemeriksaan bersifat case-insensitive).</p>
                @error('name')
                    <p class="text-xs text-rose-600 font-bold mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Category & Unit Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Category -->
                <div>
                    <label class="block text-xs font-extrabold text-[#1E222D] uppercase tracking-wider mb-2">Kategori Komoditas (*)</label>
                    <input type="text" list="category-list" name="category" value="{{ old('category') }}" required placeholder="Contoh: Daging & Unggas" class="w-full px-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-xl font-bold text-sm focus:outline-none focus:border-[#FFB703]">
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
                    <label class="block text-xs font-extrabold text-[#1E222D] uppercase tracking-wider mb-2">Satuan Baku (*)</label>
                    <input type="text" list="unit-list" name="unit" value="{{ old('unit', 'kg') }}" required placeholder="Contoh: kg, liter, pcs, dus" class="w-full px-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-xl font-bold text-sm focus:outline-none focus:border-[#FFB703]">
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
                <label class="block text-xs font-extrabold text-[#1E222D] uppercase tracking-wider mb-2">Harga Acuan Pemerintah / HET (Rp per Satuan)</label>
                <input type="number" step="100" name="government_ref_price" value="{{ old('government_ref_price') }}" placeholder="Contoh: 36000" class="w-full px-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-xl font-bold text-sm focus:outline-none focus:border-[#FFB703]">
                <p class="text-[11px] font-semibold text-gray-500 mt-1">Diambil dari data resmi Bapanas / Siskaperbapo untuk acuan perbandingan harga pemasok.</p>
                @error('government_ref_price')
                    <p class="text-xs text-rose-600 font-bold mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div>
                <label class="block text-xs font-extrabold text-[#1E222D] uppercase tracking-wider mb-2">Deskripsi / Catatan Standar Spesifikasi</label>
                <textarea name="description" rows="3" placeholder="Tambahkan rincian standar spesifikasi umum komoditas ini..." class="w-full px-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-xl font-medium text-sm focus:outline-none focus:border-[#FFB703]">{{ old('description') }}</textarea>
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-end gap-4 pt-4 border-t-2 border-gray-100">
                <a href="{{ route('admin.commodities.index') }}" class="px-6 py-3 rounded-full font-extrabold text-sm bg-gray-100 text-black hover:bg-gray-200 transition">
                    Batal
                </a>
                <button type="submit" class="px-8 py-3.5 rounded-full font-extrabold text-sm bg-[#FFB703] border-2 border-black text-black hover:bg-[#FFB703]/90 transition shadow-md">
                    Simpan Komoditas Master &rarr;
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
