@extends('layouts.supplier')

@section('title', 'Tambah Komoditas Baru — Portal Pemasok SORAI')

@section('content')
<div class="max-w-4xl w-full mx-auto space-y-6 pb-8">

    {{-- Back link & Title --}}
    <div class="border-b border-slate-200/80 pb-5">
        <a href="{{ route('supplier.katalog.index') }}"
           class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-[#ea580c] transition mb-3 group">
            <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1 text-[#ea580c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <span>Kembali ke Katalog Saya</span>
        </a>
        <span class="text-[11px] font-bold uppercase text-[#ea580c] tracking-[0.14em] block mb-1">
            PENAMBAHAN KATALOG KOMODITAS
        </span>
        <h1 class="text-2xl sm:text-[26px] font-bold text-slate-900 uppercase tracking-tight">
            Tambah Komoditas Bahan Baku Baru
        </h1>
        <p class="text-xs sm:text-[13px] text-slate-500 mt-1 leading-relaxed">
            Lengkapi rincian komoditas, penetapan harga grosir, dan spesifikasi stok yang ingin Anda tawarkan ke UMKM.
        </p>
    </div>

    {{-- Form Card --}}
    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
        <form method="POST" action="{{ route('supplier.katalog.store') }}" enctype="multipart/form-data" class="space-y-7">
            @csrf

            {{-- Bagian 1: Identitas Komoditas --}}
            <div class="space-y-4">
                <div class="border-b border-slate-100 pb-2">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800">1. Identitas &amp; Satuan Komoditas</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    {{-- Kategori Komoditas --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kategori Komoditas <span class="text-rose-500">*</span></label>
                        <select name="category_id" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c] transition cursor-pointer">
                            <option value="" disabled selected>-- Pilih Kategori --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="text-xs text-rose-600 font-medium mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Nama Komoditas Bahan Baku --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Komoditas Bahan Baku <span class="text-rose-500">*</span></label>
                        <input type="text" name="commodity_name" value="{{ old('commodity_name') }}" required placeholder="Contoh: Daging Ayam Broiler Segar, Bawang Merah..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c] transition">
                        @error('commodity_name')
                            <p class="text-xs text-rose-600 font-medium mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Satuan Pengukuran Baku <span class="text-rose-500">*</span></label>
                        <select name="unit" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c] transition cursor-pointer">
                            @foreach($units as $unit)
                                <option value="{{ $unit->name }}" {{ old('unit', 'Kilogram') == $unit->name ? 'selected' : '' }}>
                                    {{ $unit->name }} {{ $unit->symbol ? '('.$unit->symbol.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('unit')
                            <p class="text-xs text-rose-600 font-medium mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Harga Grosir (Rp per Satuan) <span class="text-rose-500">*</span></label>
                        <input type="number" step="100" min="0" name="price" value="{{ old('price') }}" required placeholder="Contoh: 32000" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-xs text-slate-900 tabular-nums placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c] transition">
                        @error('price')
                            <p class="text-xs text-rose-600 font-medium mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Bagian 2: Kapasitas & Syarat Pemesanan --}}
            <div class="space-y-4 pt-5 border-t border-slate-100">
                <div class="border-b border-slate-100 pb-2">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800">2. Kapasitas Pasokan &amp; Ketentuan Order</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kapasitas Pasokan / Stok <span class="text-rose-500">*</span></label>
                        <input type="text" name="stock_capacity" value="{{ old('stock_capacity') }}" required placeholder="Contoh: 1.000 kg / minggu" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c] transition">
                        @error('stock_capacity')
                            <p class="text-xs text-rose-600 font-medium mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Status Stok <span class="text-rose-500">*</span></label>
                        <select name="stock_status" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c] transition cursor-pointer">
                            <option value="Tersedia" {{ old('stock_status', 'Tersedia') == 'Tersedia' ? 'selected' : '' }}>Tersedia (Bisa Dipesan)</option>
                            <option value="Stok Terbatas" {{ old('stock_status') == 'Stok Terbatas' ? 'selected' : '' }}>Stok Terbatas</option>
                            <option value="Stok Habis" {{ old('stock_status') == 'Stok Habis' ? 'selected' : '' }}>Stok Habis</option>
                            <option value="Pre-Order" {{ old('stock_status') == 'Pre-Order' ? 'selected' : '' }}>Pre-Order / Inden</option>
                        </select>
                        @error('stock_status')
                            <p class="text-xs text-rose-600 font-medium mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Minimal Pemesanan (Min. Order)</label>
                        <input type="text" name="minimum_order" value="{{ old('minimum_order', '10 kg') }}" placeholder="Contoh: 10 kg" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c] transition">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Grade Mutu / Kualitas</label>
                        <input type="text" name="quality_grade" value="{{ old('quality_grade') }}" placeholder="Contoh: Grade A Super / Fresh Daily" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c] transition">
                    </div>
                </div>
            </div>

            {{-- Bagian 3: Sertifikasi, Foto & Deskripsi --}}
            <div class="space-y-4 pt-5 border-t border-slate-100">
                <div class="border-b border-slate-100 pb-2">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800">3. Sertifikasi Halal, Foto &amp; Deskripsi</h2>
                </div>

                {{-- Halal Checkbox Card --}}
                <div>
                    <label class="flex items-center gap-3.5 cursor-pointer p-4 bg-slate-50/80 border border-slate-200/80 rounded-xl hover:border-orange-200 hover:bg-orange-50/30 transition">
                        <input type="checkbox" name="is_halal_certified" value="1" {{ old('is_halal_certified', '1') ? 'checked' : '' }} class="accent-[#ea580c] w-4 h-4 rounded cursor-pointer">
                        <div>
                            <span class="text-xs font-bold text-slate-900">Memiliki Sertifikat Halal MUI / BPJPH</span>
                            <p class="text-[11px] text-slate-500 font-medium mt-0.5">Tampilkan lencana sertifikasi halal di katalog UMKM</p>
                        </div>
                    </label>
                </div>

                {{-- Foto Produk Komoditas --}}
                <div x-data="{ photoPreview: null }">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Foto Produk Komoditas</label>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4 p-4 bg-slate-50/80 border border-slate-200/80 rounded-xl">
                        <div class="w-20 h-20 rounded-xl bg-white border border-dashed border-slate-300 overflow-hidden flex items-center justify-center shrink-0 relative">
                            <template x-if="photoPreview">
                                <img :src="photoPreview" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!photoPreview">
                                <div class="text-center p-2 text-slate-400">
                                    <svg class="w-6 h-6 mx-auto mb-1 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-[10px] font-semibold block leading-none">Pilih Foto</span>
                                </div>
                            </template>
                        </div>

                        <div class="flex-1 space-y-1.5">
                            <input
                                type="file"
                                name="image"
                                accept="image/png, image/jpeg, image/jpg, image/webp"
                                @change="
                                    const file = $event.target.files[0];
                                    if (file) {
                                        const reader = new FileReader();
                                        reader.onload = (e) => { photoPreview = e.target.result; };
                                        reader.readAsDataURL(file);
                                    } else {
                                        photoPreview = null;
                                    }
                                "
                                class="block w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border file:border-orange-200 file:text-xs file:font-semibold file:bg-orange-50 file:text-[#c2410c] hover:file:bg-orange-100 cursor-pointer"
                            />
                            <div class="flex items-center justify-between text-[11px] font-medium text-slate-500">
                                <span>Pilih file foto dari perangkat Anda (JPG, PNG, WEBP, maks. 2MB).</span>
                                <button type="button" x-show="photoPreview" @click="photoPreview = null; $el.closest('form').querySelector('input[name=image]').value = ''" class="text-rose-600 font-semibold hover:underline cursor-pointer text-xs shrink-0 ml-2">
                                    Hapus
                                </button>
                            </div>
                            @error('image')
                                <p class="text-xs text-rose-600 font-medium mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Deskripsi &amp; Catatan Kualitas Pasokan</label>
                    <textarea name="description" rows="3" placeholder="Jelaskan detail komoditas, keunggulan kesegaran, standar pengemasan, atau garansi retur..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c] transition">{{ old('description') }}</textarea>
                </div>
            </div>

            {{-- Form Actions --}}
            <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-3 pt-5 border-t border-slate-100">
                <a href="{{ route('supplier.katalog.index') }}" class="px-5 py-2.5 rounded-xl font-semibold text-xs border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition text-center">
                    Batal
                </a>
                <button type="submit" data-cy="btn-submit-katalog" class="px-6 py-2.5 rounded-xl font-semibold text-xs bg-[#ea580c] hover:bg-[#c2410c] text-white shadow-2xs transition cursor-pointer inline-flex items-center justify-center gap-1.5">
                    <span>Simpan &amp; Publikasikan ke Katalog</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </button>
            </div>

        </form>
    </div>

</div>
@endsection
