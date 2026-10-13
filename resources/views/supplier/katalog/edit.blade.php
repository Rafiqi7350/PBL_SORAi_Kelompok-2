@extends('layouts.supplier')

@section('content')
<div class="max-w-4xl w-full mx-auto space-y-6 pb-12">

    <!-- Back link & Title -->
    <div>
        <a href="{{ route('supplier.katalog.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-gray-500 hover:text-[#ea580c] transition mb-3 group">
            <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1 text-[#ea580c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <span>Kembali ke Katalog Saya</span>
        </a>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 uppercase tracking-tight">Edit Komoditas: {{ $product->commodity?->name ?? 'Komoditas' }}</h1>
        <p class="text-xs text-gray-500 mt-1">Perbarui penetapan harga grosir, kuota kapasitas pasokan, atau deskripsi mutu produk.</p>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-[2rem] p-8 sm:p-10 border border-gray-100 shadow-[0_10px_35px_rgba(0,0,0,0.04)]">
        <form method="POST" action="{{ route('supplier.katalog.update', $product->id) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            @php
                $currentCategory = $product->commodity ? $product->commodity->category : '';
                $currentUnit = $product->commodity ? $product->commodity->unit : 'Kilogram';
            @endphp

            <!-- Category & Commodity Name Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- 1. Kategori Komoditas (dari commodity_categories) -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Kategori Komoditas <span class="text-rose-500">*</span></label>
                    <select name="category_id" required class="w-full px-4 py-3 bg-[#f1f2f4] border-0 rounded-xl font-medium text-xs text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ea580c] transition cursor-pointer">
                        <option value="" disabled>-- Pilih Kategori --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ (old('category_id') == $category->id || strtolower($category->name) == strtolower(old('category_id', $currentCategory))) ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="text-xs text-rose-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 2. Nama Komoditas Bahan Baku (Input Sendiri ke tabel commodities) -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Nama Komoditas Bahan Baku <span class="text-rose-500">*</span></label>
                    <input type="text" name="commodity_name" value="{{ old('commodity_name', $product->commodity?->name) }}" required placeholder="Contoh: Daging Ayam Broiler Segar, Bawang Merah Biru Probolinggo..." class="w-full px-4 py-3 bg-[#f1f2f4] border-0 rounded-xl font-medium text-xs text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c] transition">
                    @error('commodity_name')
                        <p class="text-xs text-rose-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Satuan Baku & Harga Grosir -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Satuan Pengukuran Baku <span class="text-rose-500">*</span></label>
                    <select name="unit" required class="w-full px-4 py-3 bg-[#f1f2f4] border-0 rounded-xl font-medium text-xs text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ea580c] transition cursor-pointer">
                        @foreach($units as $unit)
                            <option value="{{ $unit->name }}" {{ old('unit', $currentUnit) == $unit->name ? 'selected' : '' }}>
                                {{ $unit->name }} {{ $unit->symbol ? '('.$unit->symbol.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('unit')
                        <p class="text-xs text-rose-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Harga Grosir (Rp per Satuan) <span class="text-rose-500">*</span></label>
                    <input type="number" step="100" name="price" value="{{ old('price', $product->price) }}" required class="w-full px-4 py-3 bg-[#f1f2f4] border-0 rounded-xl font-medium text-xs text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c] transition">
                    @error('price')
                        <p class="text-xs text-rose-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Kapasitas Pasokan & Status Stok -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Kapasitas Pasokan / Stok <span class="text-rose-500">*</span></label>
                    <input type="text" name="stock_capacity" value="{{ old('stock_capacity', $product->stock_capacity) }}" required class="w-full px-4 py-3 bg-[#f1f2f4] border-0 rounded-xl font-medium text-xs text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c] transition">
                    @error('stock_capacity')
                        <p class="text-xs text-rose-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Status Stok <span class="text-rose-500">*</span></label>
                    <select name="stock_status" required class="w-full px-4 py-3 bg-[#f1f2f4] border-0 rounded-xl font-medium text-xs text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ea580c] transition cursor-pointer">
                        <option value="Tersedia" {{ old('stock_status', $product->stock_status) == 'Tersedia' ? 'selected' : '' }}>🟢 Tersedia (Bisa Dipesan)</option>
                        <option value="Stok Terbatas" {{ old('stock_status', $product->stock_status) == 'Stok Terbatas' ? 'selected' : '' }}>🟡 Stok Terbatas</option>
                        <option value="Stok Habis" {{ old('stock_status', $product->stock_status) == 'Stok Habis' ? 'selected' : '' }}>🔴 Stok Habis</option>
                        <option value="Pre-Order" {{ old('stock_status', $product->stock_status) == 'Pre-Order' ? 'selected' : '' }}>🔵 Pre-Order / Inden</option>
                    </select>
                    @error('stock_status')
                        <p class="text-xs text-rose-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Minimal Pemesanan & Grade Mutu -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Minimal Pemesanan (Min. Order)</label>
                    <input type="text" name="minimum_order" value="{{ old('minimum_order', $product->minimum_order) }}" placeholder="Contoh: 10 kg" class="w-full px-4 py-3 bg-[#f1f2f4] border-0 rounded-xl font-medium text-xs text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c] transition">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Grade Mutu / Kualitas</label>
                    <input type="text" name="quality_grade" value="{{ old('quality_grade', $product->quality_grade) }}" class="w-full px-4 py-3 bg-[#f1f2f4] border-0 rounded-xl font-medium text-xs text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c] transition">
                </div>
            </div>

            <!-- Halal Checkbox Card -->
            <div>
                <label class="flex items-center gap-3 cursor-pointer p-4 bg-[#f8f9fa] border border-gray-100 rounded-2xl hover:bg-orange-50/30 transition">
                    <input type="checkbox" name="is_halal_certified" value="1" {{ old('is_halal_certified', $product->is_halal_certified) ? 'checked' : '' }} class="accent-[#ea580c] w-5 h-5 rounded cursor-pointer">
                    <div>
                        <span class="text-xs font-bold text-gray-900">Memiliki Sertifikat Halal MUI / BPJPH</span>
                        <p class="text-[11px] text-gray-500 font-medium">Tampilkan badge sertifikasi halal di katalog UMKM</p>
                    </div>
                </label>
            </div>

            <!-- Foto Produk Komoditas (File Upload) -->
            <div x-data="{ photoPreview: '{{ $product->image_url ? (str_starts_with($product->image_url, "http") ? $product->image_url : asset($product->image_url)) : "" }}' }">
                <label class="block text-xs font-bold text-gray-700 mb-1.5">Foto Produk Komoditas</label>
                <div class="flex flex-col sm:flex-row sm:items-center gap-4 p-4 bg-[#f8f9fa] border border-gray-100 rounded-2xl">
                    <div class="w-20 h-20 rounded-2xl bg-white border-2 border-dashed border-gray-300 overflow-hidden flex items-center justify-center shrink-0 shadow-2xs relative">
                        <template x-if="photoPreview">
                            <img :src="photoPreview" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!photoPreview">
                            <div class="text-center p-2 text-gray-400">
                                <svg class="w-6 h-6 mx-auto mb-0.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span class="text-[9px] font-bold block leading-none">Pilih Foto</span>
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
                                }
                            "
                            class="block w-full text-xs text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-orange-50 file:text-[#ea580c] hover:file:bg-orange-100 cursor-pointer"
                        />
                        <p class="text-[11px] font-medium text-gray-400">Pilih file baru jika ingin mengganti foto komoditas (JPG, PNG, WEBP, maks. 5MB). Biarkan kosong jika tidak ingin mengubah foto saat ini.</p>
                        @error('image')
                            <p class="text-xs text-rose-600 font-medium mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1.5">Deskripsi & Catatan Kualitas Pasokan</label>
                <textarea name="description" rows="3" class="w-full px-4 py-3 bg-[#f1f2f4] border-0 rounded-xl font-medium text-xs text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c] transition">{{ old('description', $product->description) }}</textarea>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                <a href="{{ route('supplier.katalog.index') }}" class="px-6 py-3 rounded-full font-bold text-xs bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
                    Batal
                </a>
                <button type="submit" class="px-8 py-3 rounded-full font-bold text-xs bg-[#ea580c] hover:bg-[#c2410c] text-white shadow-sm hover:shadow-md transition cursor-pointer flex items-center gap-1">
                    <span>Simpan Perubahan →</span>
                </button>
            </div>

        </form>
    </div>

</div>
@endsection
