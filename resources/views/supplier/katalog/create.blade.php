@extends('layouts.supplier')

@section('content')
<div class="max-w-4xl w-full mx-auto space-y-6 pb-12">

    <!-- Back link & Title -->
    <div>
        <a href="{{ route('supplier.katalog.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-gray-500 hover:text-black mb-3">
            &larr; Kembali ke Katalog Saya
        </a>
        <h1 class="text-3xl font-extrabold text-[#1E222D] uppercase tracking-tight">Tambah Komoditas Bahan Baku Baru</h1>
        <p class="text-sm font-medium text-gray-600 mt-1">Lengkapi rincian komoditas, penetapan harga grosir, dan spesifikasi stok yang ingin Anda tawarkan ke UMKM.</p>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-3xl p-8 border-2 border-black shadow-[6px_6px_0px_0px_rgba(30,34,45,1)]">
        <form method="POST" action="{{ route('supplier.katalog.store') }}" class="space-y-6">
            @csrf

            <!-- Commodity Master Selection -->
            <div>
                <label class="block text-xs font-extrabold text-[#1E222D] uppercase tracking-wider mb-2">Pilih Komoditas Master (*)</label>
                <select name="commodity_id" required class="w-full px-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-xl font-bold text-sm focus:outline-none focus:border-[#FFB703]">
                    <option value="" disabled selected>-- Pilih Jenis Komoditas --</option>
                    @foreach($commodities as $commodity)
                        <option value="{{ $commodity->id }}" {{ old('commodity_id') == $commodity->id ? 'selected' : '' }}>
                            {{ $commodity->name }} ({{ strtoupper($commodity->category) }}) - Satuan: {{ $commodity->unit }}
                            @if($commodity->government_ref_price)
                                | Ref HET Govt: Rp {{ number_format($commodity->government_ref_price, 0, ',', '.') }}
                            @endif
                        </option>
                    @endforeach
                </select>
                @error('commodity_id')
                    <p class="text-xs text-rose-600 font-bold mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Price & Capacity -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-extrabold text-[#1E222D] uppercase tracking-wider mb-2">Harga Grosir (Rp per Satuan) (*)</label>
                    <input type="number" step="100" name="price" value="{{ old('price') }}" required placeholder="Contoh: 32000" class="w-full px-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-xl font-bold text-sm focus:outline-none focus:border-[#FFB703]">
                    @error('price')
                        <p class="text-xs text-rose-600 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-extrabold text-[#1E222D] uppercase tracking-wider mb-2">Kapasitas Pasokan / Stok (*)</label>
                    <input type="text" name="stock_capacity" value="{{ old('stock_capacity') }}" required placeholder="Contoh: 1.000 kg / minggu" class="w-full px-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-xl font-bold text-sm focus:outline-none focus:border-[#FFB703]">
                    @error('stock_capacity')
                        <p class="text-xs text-rose-600 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Stock Status & Minimum Order -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-extrabold text-[#1E222D] uppercase tracking-wider mb-2">Status Stok (*)</label>
                    <select name="stock_status" required class="w-full px-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-xl font-bold text-sm focus:outline-none focus:border-[#FFB703]">
                        <option value="Tersedia" {{ old('stock_status') == 'Tersedia' ? 'selected' : '' }}>🟢 Tersedia (Bisa Dipesan Direct)</option>
                        <option value="Stok Terbatas" {{ old('stock_status') == 'Stok Terbatas' ? 'selected' : '' }}>🟡 Stok Terbatas</option>
                        <option value="Stok Habis" {{ old('stock_status') == 'Stok Habis' ? 'selected' : '' }}>🔴 Stok Habis</option>
                        <option value="Pre-Order" {{ old('stock_status') == 'Pre-Order' ? 'selected' : '' }}>🔵 Pre-Order / Inden</option>
                    </select>
                    @error('stock_status')
                        <p class="text-xs text-rose-600 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-extrabold text-[#1E222D] uppercase tracking-wider mb-2">Minimal Pemesanan (Min. Order)</label>
                    <input type="text" name="minimum_order" value="{{ old('minimum_order', '10') }}" placeholder="Contoh: 10 kg" class="w-full px-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-xl font-bold text-sm focus:outline-none focus:border-[#FFB703]">
                </div>
            </div>

            <!-- Quality Grade & Halal Checkbox -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                <div>
                    <label class="block text-xs font-extrabold text-[#1E222D] uppercase tracking-wider mb-2">Grade Mutu / Kualitas</label>
                    <input type="text" name="quality_grade" value="{{ old('quality_grade') }}" placeholder="Contoh: Grade A Super / Fresh Daily" class="w-full px-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-xl font-bold text-sm focus:outline-none focus:border-[#FFB703]">
                </div>

                <div class="pt-4">
                    <label class="flex items-center gap-3 cursor-pointer p-3 bg-[#FFFDF5] border-2 border-black rounded-xl">
                        <input type="checkbox" name="is_halal_certified" value="1" {{ old('is_halal_certified') ? 'checked' : '' }} class="w-5 h-5 text-black border-2 border-black rounded focus:ring-0">
                        <div>
                            <span class="text-xs font-black text-[#1E222D] uppercase">Memiliki Sertifikat Halal MUI / BPJPH</span>
                            <p class="text-[11px] text-gray-500 font-medium">Tampilkan badge sertifikasi halal di katalog UMKM</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Image URL -->
            <div>
                <label class="block text-xs font-extrabold text-[#1E222D] uppercase tracking-wider mb-2">URL Foto Produk Komoditas</label>
                <input type="url" name="image_url" value="{{ old('image_url') }}" placeholder="https://images.unsplash.com/..." class="w-full px-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-xl font-bold text-sm focus:outline-none focus:border-[#FFB703]">
                <p class="text-[11px] font-medium text-gray-500 mt-1">Gunakan URL gambar komoditas yang jernih agar menarik minat mitra UMKM.</p>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-xs font-extrabold text-[#1E222D] uppercase tracking-wider mb-2">Deskripsi & Catatan Kualitas Pasokan</label>
                <textarea name="description" rows="3" placeholder="Jelaskan detail komoditas, keunggulan kesegaran, standar pengemasan, atau garansi retur..." class="w-full px-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-xl font-medium text-sm focus:outline-none focus:border-[#FFB703]">{{ old('description') }}</textarea>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end gap-4 pt-4 border-t-2 border-gray-100">
                <a href="{{ route('supplier.katalog.index') }}" class="px-6 py-3 rounded-full font-extrabold text-sm bg-gray-100 text-black hover:bg-gray-200 transition">
                    Batal
                </a>
                <button type="submit" class="px-8 py-3.5 rounded-full font-extrabold text-sm bg-[#FFB703] text-black hover:bg-[#FFB703]/90 transition shadow-md">
                    Simpan & Publikasikan ke Katalog &rarr;
                </button>
            </div>

        </form>
    </div>

</div>
@endsection
