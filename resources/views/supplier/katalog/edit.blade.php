@extends('layouts.supplier')

@section('content')
<<<<<<< HEAD
<div class="max-w-4xl w-full mx-auto space-y-6 pb-12">
=======
<div x-data="{ 
    commodities: {{ json_encode($commodities) }},
    selectedCommodityId: '{{ old('commodity_id', $product->commodity_id) }}',
    selectedUnit: '{{ old('unit', $product->unit ?: $product->commodity->unit) }}',
    get selectedCommodity() {
        return this.commodities.find(c => c.id === this.selectedCommodityId) || null;
    }
}" class="max-w-4xl w-full mx-auto space-y-6 pb-12">
>>>>>>> 886da98b5b50ea650d3b97d2b27ee989f31d1da7

    <!-- Back link & Title -->
    <div>
        <a href="{{ route('supplier.katalog.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-gray-500 hover:text-black mb-3">
            &larr; Kembali ke Katalog Saya
        </a>
<<<<<<< HEAD
        <h1 class="text-3xl font-extrabold text-[#1E222D] uppercase tracking-tight">Edit Komoditas: {{ $product->commodity->name }}</h1>
        <p class="text-sm font-medium text-gray-600 mt-1">Perbarui penetapan harga grosir, kuota kapasitas pasokan, atau deskripsi mutu produk.</p>
=======
        <h1 class="text-3xl font-extrabold text-[#1E222D] uppercase tracking-tight">Edit Produk: {{ $product->commodity->name }}</h1>
        <p class="text-sm font-medium text-gray-600 mt-1">Perbarui penetapan harga grosir, satuan baku, kuota kapasitas pasokan mingguan, atau status stok.</p>
>>>>>>> 886da98b5b50ea650d3b97d2b27ee989f31d1da7
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-3xl p-8 border-2 border-black shadow-[6px_6px_0px_0px_rgba(30,34,45,1)]">
        <form method="POST" action="{{ route('supplier.katalog.update', $product->id) }}" class="space-y-6">
            @csrf
            @method('PUT')

<<<<<<< HEAD
            <!-- Commodity Master Selection -->
            <div>
                <label class="block text-xs font-extrabold text-[#1E222D] uppercase tracking-wider mb-2">Pilih Komoditas Master (*)</label>
                <select name="commodity_id" required class="w-full px-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-xl font-bold text-sm focus:outline-none focus:border-[#FFB703]">
                    @foreach($commodities as $commodity)
                        <option value="{{ $commodity->id }}" {{ old('commodity_id', $product->commodity_id) == $commodity->id ? 'selected' : '' }}>
                            {{ $commodity->name }} ({{ strtoupper($commodity->category) }}) - Satuan: {{ $commodity->unit }}
                            @if($commodity->government_ref_price)
                                | Ref HET Govt: Rp {{ number_format($commodity->government_ref_price, 0, ',', '.') }}
                            @endif
                        </option>
                    @endforeach
=======
            <!-- Select Commodity -->
            <div>
                <label class="block text-xs font-extrabold text-[#1E222D] uppercase tracking-wider mb-2">Pilih Komoditas dari Master Data (*)</label>
                <select name="commodity_id" x-model="selectedCommodityId" required class="w-full px-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-xl font-bold text-sm focus:outline-none focus:border-[#FFB703]">
                    <template x-for="c in commodities" :key="c.id">
                        <option :value="c.id" :selected="c.id === selectedCommodityId" x-text="c.name + ' (' + c.category.toUpperCase() + ') - Satuan Master: ' + c.unit"></option>
                    </template>
>>>>>>> 886da98b5b50ea650d3b97d2b27ee989f31d1da7
                </select>
                @error('commodity_id')
                    <p class="text-xs text-rose-600 font-bold mt-1">{{ $message }}</p>
                @enderror
            </div>

<<<<<<< HEAD
            <!-- Price & Capacity -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-extrabold text-[#1E222D] uppercase tracking-wider mb-2">Harga Grosir (Rp per Satuan) (*)</label>
                    <input type="number" step="100" name="price" value="{{ old('price', $product->price) }}" required class="w-full px-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-xl font-bold text-sm focus:outline-none focus:border-[#FFB703]">
=======
            <!-- Pembanding Harga Acuan Pemerintah (Bapanas / Siskaperbapo) -->
            <div x-show="selectedCommodity" x-transition class="bg-amber-50 border-2 border-amber-400 p-4 rounded-2xl flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">🏛️</span>
                    <div>
                        <p class="text-xs font-extrabold text-amber-900 uppercase">Harga Acuan Pemerintah (Bapanas / Siskaperbapo)</p>
                        <p class="text-xs text-amber-800 font-medium">Gunakan patokan resmi ini sebagai pembanding penawaran harga grosir Anda.</p>
                    </div>
                </div>
                <div class="text-right flex-shrink-0">
                    <template x-if="selectedCommodity?.government_ref_price">
                        <div>
                            <span class="text-xl font-black text-[#F4511E]" x-text="'Rp ' + (parseFloat(selectedCommodity.government_ref_price).toLocaleString('id-ID'))"></span>
                            <span class="text-xs font-bold text-gray-600" x-text="' / ' + selectedCommodity.unit"></span>
                        </div>
                    </template>
                    <template x-if="!selectedCommodity?.government_ref_price">
                        <span class="text-xs font-bold text-gray-500 font-mono">Belum ada HET acuan</span>
                    </template>
                </div>
            </div>

            <!-- Price & Unit -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Harga Grosir -->
                <div>
                    <label class="block text-xs font-extrabold text-[#1E222D] uppercase tracking-wider mb-2">Harga Grosir (Rp per Satuan) (*)</label>
                    <input type="number" step="100" min="1" name="price" value="{{ old('price', $product->price) }}" required class="w-full px-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-xl font-bold text-sm focus:outline-none focus:border-[#FFB703]">
                    <p class="text-[11px] font-semibold text-gray-500 mt-1">Harus angka bernilai > 0 (Rp).</p>
>>>>>>> 886da98b5b50ea650d3b97d2b27ee989f31d1da7
                    @error('price')
                        <p class="text-xs text-rose-600 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

<<<<<<< HEAD
                <div>
                    <label class="block text-xs font-extrabold text-[#1E222D] uppercase tracking-wider mb-2">Kapasitas Pasokan / Stok (*)</label>
                    <input type="text" name="stock_capacity" value="{{ old('stock_capacity', $product->stock_capacity) }}" required class="w-full px-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-xl font-bold text-sm focus:outline-none focus:border-[#FFB703]">
                    @error('stock_capacity')
=======
                <!-- Satuan Baku -->
                <div>
                    <label class="block text-xs font-extrabold text-[#1E222D] uppercase tracking-wider mb-2">Satuan Baku (*)</label>
                    <select name="unit" x-model="selectedUnit" required class="w-full px-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-xl font-bold text-sm focus:outline-none focus:border-[#FFB703]">
                        @foreach($validUnits as $u)
                            <option value="{{ $u }}" {{ old('unit', $product->effective_unit) == $u ? 'selected' : '' }}>{{ $u }}</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] font-semibold text-gray-500 mt-1">Harus berasal dari daftar Satuan Baku terdaftar.</p>
                    @error('unit')
>>>>>>> 886da98b5b50ea650d3b97d2b27ee989f31d1da7
                        <p class="text-xs text-rose-600 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

<<<<<<< HEAD
            <!-- Stock Status & Minimum Order -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-extrabold text-[#1E222D] uppercase tracking-wider mb-2">Status Stok (*)</label>
                    <select name="stock_status" required class="w-full px-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-xl font-bold text-sm focus:outline-none focus:border-[#FFB703]">
                        <option value="Tersedia" {{ old('stock_status', $product->stock_status) == 'Tersedia' ? 'selected' : '' }}>🟢 Tersedia (Bisa Dipesan Direct)</option>
                        <option value="Stok Terbatas" {{ old('stock_status', $product->stock_status) == 'Stok Terbatas' ? 'selected' : '' }}>🟡 Stok Terbatas</option>
                        <option value="Stok Habis" {{ old('stock_status', $product->stock_status) == 'Stok Habis' ? 'selected' : '' }}>🔴 Stok Habis</option>
                        <option value="Pre-Order" {{ old('stock_status', $product->stock_status) == 'Pre-Order' ? 'selected' : '' }}>🔵 Pre-Order / Inden</option>
=======
            <!-- Weekly Capacity & Stock Status -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Kapasitas Pasok Mingguan -->
                <div>
                    <label class="block text-xs font-extrabold text-[#1E222D] uppercase tracking-wider mb-2">Kapasitas Pasok Mingguan (*)</label>
                    <input type="number" step="0.1" min="0.1" name="weekly_capacity" value="{{ old('weekly_capacity', $product->weekly_capacity ?: (float) preg_replace('/[^0-9.]/', '', $product->stock_capacity ?: '100')) }}" required class="w-full px-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-xl font-bold text-sm focus:outline-none focus:border-[#FFB703]">
                    <p class="text-[11px] font-semibold text-gray-500 mt-1">Harus angka bernilai > 0 per minggu.</p>
                    @error('weekly_capacity')
                        <p class="text-xs text-rose-600 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status Stok -->
                <div>
                    <label class="block text-xs font-extrabold text-[#1E222D] uppercase tracking-wider mb-2">Status Ketersediaan Stok (*)</label>
                    <select name="stock_status" required class="w-full px-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-xl font-bold text-sm focus:outline-none focus:border-[#FFB703]">
                        <option value="ready" {{ in_array(old('stock_status', $product->stock_status), ['ready', 'Tersedia']) ? 'selected' : '' }}>🟢 Ready (Pasokan Rutin Siap Antar)</option>
                        <option value="limited" {{ in_array(old('stock_status', $product->stock_status), ['limited', 'Stok Terbatas']) ? 'selected' : '' }}>🟡 Limited (Stok Kuota Terbatas)</option>
>>>>>>> 886da98b5b50ea650d3b97d2b27ee989f31d1da7
                    </select>
                    @error('stock_status')
                        <p class="text-xs text-rose-600 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>
<<<<<<< HEAD

=======
            </div>

            <!-- Additional Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
>>>>>>> 886da98b5b50ea650d3b97d2b27ee989f31d1da7
                <div>
                    <label class="block text-xs font-extrabold text-[#1E222D] uppercase tracking-wider mb-2">Minimal Pemesanan (Min. Order)</label>
                    <input type="text" name="minimum_order" value="{{ old('minimum_order', $product->minimum_order) }}" class="w-full px-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-xl font-bold text-sm focus:outline-none focus:border-[#FFB703]">
                </div>
<<<<<<< HEAD
            </div>

            <!-- Quality Grade & Halal Checkbox -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
=======

>>>>>>> 886da98b5b50ea650d3b97d2b27ee989f31d1da7
                <div>
                    <label class="block text-xs font-extrabold text-[#1E222D] uppercase tracking-wider mb-2">Grade Mutu / Kualitas</label>
                    <input type="text" name="quality_grade" value="{{ old('quality_grade', $product->quality_grade) }}" class="w-full px-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-xl font-bold text-sm focus:outline-none focus:border-[#FFB703]">
                </div>
<<<<<<< HEAD

                <div class="pt-4">
                    <label class="flex items-center gap-3 cursor-pointer p-3 bg-[#FFFDF5] border-2 border-black rounded-xl">
                        <input type="checkbox" name="is_halal_certified" value="1" {{ old('is_halal_certified', $product->is_halal_certified) ? 'checked' : '' }} class="w-5 h-5 text-black border-2 border-black rounded focus:ring-0">
                        <div>
                            <span class="text-xs font-black text-[#1E222D] uppercase">Memiliki Sertifikat Halal MUI / BPJPH</span>
                            <p class="text-[11px] text-gray-500 font-medium">Tampilkan badge sertifikasi halal di katalog UMKM</p>
                        </div>
                    </label>
                </div>
=======
            </div>

            <div class="pt-2">
                <label class="flex items-center gap-3 cursor-pointer p-3 bg-[#FFFDF5] border-2 border-black rounded-xl">
                    <input type="checkbox" name="is_halal_certified" value="1" {{ old('is_halal_certified', $product->is_halal_certified) ? 'checked' : '' }} class="w-5 h-5 text-black border-2 border-black rounded focus:ring-0">
                    <div>
                        <span class="text-xs font-black text-[#1E222D] uppercase">Memiliki Sertifikat Halal MUI / BPJPH</span>
                        <p class="text-[11px] text-gray-500 font-medium">Tampilkan badge sertifikasi halal di katalog UMKM</p>
                    </div>
                </label>
>>>>>>> 886da98b5b50ea650d3b97d2b27ee989f31d1da7
            </div>

            <!-- Image URL -->
            <div>
                <label class="block text-xs font-extrabold text-[#1E222D] uppercase tracking-wider mb-2">URL Foto Produk Komoditas</label>
                <input type="url" name="image_url" value="{{ old('image_url', $product->image_url) }}" class="w-full px-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-xl font-bold text-sm focus:outline-none focus:border-[#FFB703]">
            </div>

            <!-- Description -->
            <div>
                <label class="block text-xs font-extrabold text-[#1E222D] uppercase tracking-wider mb-2">Deskripsi & Catatan Kualitas Pasokan</label>
                <textarea name="description" rows="3" class="w-full px-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-xl font-medium text-sm focus:outline-none focus:border-[#FFB703]">{{ old('description', $product->description) }}</textarea>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end gap-4 pt-4 border-t-2 border-gray-100">
<<<<<<< HEAD
                <a href="{{ route('supplier.katalog.index') }}" class="px-6 py-3 rounded-full font-extrabold text-sm bg-gray-100 text-black hover:bg-gray-200 transition">
                    Batal
                </a>
                <button type="submit" class="px-8 py-3.5 rounded-full font-extrabold text-sm bg-[#FFB703] text-black hover:bg-[#FFB703]/90 transition shadow-md">
=======
                <a href="{{ route('supplier.katalog.index') }}" class="px-6 py-3 rounded-full font-extrabold text-xs bg-gray-100 text-black hover:bg-gray-200 transition">
                    Batal
                </a>
                <button type="submit" class="px-8 py-3.5 rounded-full font-extrabold text-xs bg-[#FFB703] border-2 border-black text-black hover:bg-[#FFB703]/90 transition shadow-md">
>>>>>>> 886da98b5b50ea650d3b97d2b27ee989f31d1da7
                    Simpan Perubahan &rarr;
                </button>
            </div>

        </form>
    </div>

</div>
@endsection
