@extends('layouts.admin')

@section('content')
<div class="max-w-3xl w-full mx-auto space-y-6 pb-8">

    <div class="space-y-1.5">
        <a href="{{ route('admin.commodities.index') }}"
           class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-900 mb-2 transition">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Kembali ke Master Komoditas</span>
        </a>
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Edit Komoditas: {{ $commodity->name }}</h1>
        <p class="text-xs sm:text-sm text-slate-500">Perbarui informasi komoditas master, kategori, satuan baku, atau harga acuan pemerintah.</p>
    </div>

    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200/80 shadow-xs">
        <form method="POST" action="{{ route('admin.commodities.update', $commodity->id) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Komoditas Bahan Baku <span class="text-rose-500">*</span></label>
                <input type="text"
                       name="name"
                       value="{{ old('name', $commodity->name) }}"
                       required
                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200/90 rounded-xl font-medium text-sm text-slate-900 focus:bg-white focus:outline-none focus:border-[#ea580c] focus:ring-2 focus:ring-orange-500/20 transition">
                @error('name')
                    <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kategori Komoditas <span class="text-rose-500">*</span></label>
                    <input type="text"
                           list="category-list"
                           name="category"
                           value="{{ old('category', $commodity->category) }}"
                           required
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200/90 rounded-xl font-medium text-sm text-slate-900 focus:bg-white focus:outline-none focus:border-[#ea580c] focus:ring-2 focus:ring-orange-500/20 transition">
                    <datalist id="category-list">
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}"></option>
                        @endforeach
                    </datalist>
                    @error('category')
                        <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Satuan Baku <span class="text-rose-500">*</span></label>
                    <input type="text"
                           list="unit-list"
                           name="unit"
                           value="{{ old('unit', $commodity->unit) }}"
                           required
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200/90 rounded-xl font-medium text-sm text-slate-900 focus:bg-white focus:outline-none focus:border-[#ea580c] focus:ring-2 focus:ring-orange-500/20 transition">
                    <datalist id="unit-list">
                        @foreach($units as $u)
                            <option value="{{ $u }}"></option>
                        @endforeach
                    </datalist>
                    @error('unit')
                        <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Harga Acuan Pemerintah / HET (Rp per Satuan)</label>
                <input type="number"
                       step="100"
                       name="government_ref_price"
                       value="{{ old('government_ref_price', $commodity->government_ref_price) }}"
                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200/90 rounded-xl font-medium text-sm text-slate-900 tabular-nums focus:bg-white focus:outline-none focus:border-[#ea580c] focus:ring-2 focus:ring-orange-500/20 transition">
                @error('government_ref_price')
                    <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Deskripsi / Catatan Standar Spesifikasi</label>
                <textarea name="description"
                          rows="3"
                          class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200/90 rounded-xl text-sm text-slate-900 focus:bg-white focus:outline-none focus:border-[#ea580c] focus:ring-2 focus:ring-orange-500/20 transition">{{ old('description', $commodity->description) }}</textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('admin.commodities.index') }}"
                   class="px-5 py-2.5 rounded-xl font-semibold text-xs bg-white border border-slate-200/90 text-slate-700 hover:bg-slate-50 transition">
                    Batal
                </a>
                <button type="submit"
                        class="px-6 py-2.5 rounded-xl font-semibold text-xs bg-[#ea580c] hover:bg-[#c2410c] text-white transition shadow-xs cursor-pointer">
                    Simpan Perubahan Komoditas
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
