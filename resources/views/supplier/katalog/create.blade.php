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
        <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 uppercase tracking-tight">Tambah Komoditas Bahan Baku Baru</h1>
        <p class="text-xs text-gray-500 mt-1">Lengkapi rincian komoditas, penetapan harga grosir, dan spesifikasi stok yang ingin Anda tawarkan ke UMKM.</p>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-[2rem] p-8 sm:p-10 border border-gray-100 shadow-[0_10px_35px_rgba(0,0,0,0.04)]">
        <form method="POST" action="{{ route('supplier.katalog.store') }}" class="space-y-6">
            @csrf

            <!-- Commodity Master Selection -->
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1.5">Pilih Komoditas Master <span class="text-rose-500">*</span></label>
                <select name="commodity_id" required class="w-full px-4 py-3 bg-[#f1f2f4] border-0 rounded-xl font-medium text-xs text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ea580c] transition cursor-pointer">
                    <option value="" disabled selected>-- Pilih Jenis Komoditas --</option>
                    @foreach($commodities as $commodity)
                        <option value="{{ $commodity->id }}" {{ old('commodity_id') == $commodity->id ? 'selected' : '' }}>
                            {{ $commodity->name }} ({{ strtoupper($commodity->category) }}) - Satuan: {{ $commodity->unit }}
                            @if($commodity->government_ref_price)
                                | Ref HET: Rp {{ number_format($commodity->government_ref_price, 0, ',', '.') }}
                            @endif
                        </option>
                    @endforeach
                </select>
                @error('commodity_id')
                    <p class="text-xs text-rose-600 font-medium mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Price & Capacity -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Harga Grosir (Rp per Satuan) <span class="text-rose-500">*</span></label>
                    <input type="number" step="100" name="price" value="{{ old('price') }}" required placeholder="Contoh: 32000" class="w-full px-4 py-3 bg-[#f1f2f4] border-0 rounded-xl font-medium text-xs text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c] transition">
                    @error('price')
                        <p class="text-xs text-rose-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Kapasitas Pasokan / Stok <span class="text-rose-500">*</span></label>
                    <input type="text" name="stock_capacity" value="{{ old('stock_capacity') }}" required placeholder="Contoh: 1.000 kg / minggu" class="w-full px-4 py-3 bg-[#f1f2f4] border-0 rounded-xl font-medium text-xs text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c] transition">
                    @error('stock_capacity')
                        <p class="text-xs text-rose-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Stock Status & Minimum Order -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Status Stok <span class="text-rose-500">*</span></label>
                    <select name="stock_status" required class="w-full px-4 py-3 bg-[#f1f2f4] border-0 rounded-xl font-medium text-xs text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ea580c] transition cursor-pointer">
                        <option value="Tersedia" {{ old('stock_status', 'Tersedia') == 'Tersedia' ? 'selected' : '' }}>🟢 Tersedia (Bisa Dipesan)</option>
                        <option value="Stok Terbatas" {{ old('stock_status') == 'Stok Terbatas' ? 'selected' : '' }}>🟡 Stok Terbatas</option>
                        <option value="Stok Habis" {{ old('stock_status') == 'Stok Habis' ? 'selected' : '' }}>🔴 Stok Habis</option>
                        <option value="Pre-Order" {{ old('stock_status') == 'Pre-Order' ? 'selected' : '' }}>🔵 Pre-Order / Inden</option>
                    </select>
                    @error('stock_status')
                        <p class="text-xs text-rose-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Minimal Pemesanan (Min. Order)</label>
                    <input type="text" name="minimum_order" value="{{ old('minimum_order', '10 kg') }}" placeholder="Contoh: 10 kg" class="w-full px-4 py-3 bg-[#f1f2f4] border-0 rounded-xl font-medium text-xs text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c] transition">
                </div>
            </div>

            <!-- Quality Grade & Halal Checkbox -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Grade Mutu / Kualitas</label>
                    <input type="text" name="quality_grade" value="{{ old('quality_grade') }}" placeholder="Contoh: Grade A Super / Fresh Daily" class="w-full px-4 py-3 bg-[#f1f2f4] border-0 rounded-xl font-medium text-xs text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c] transition">
                </div>

                <div class="pt-2">
                    <label class="flex items-center gap-3 cursor-pointer p-4 bg-[#f8f9fa] border border-gray-100 rounded-2xl hover:bg-orange-50/30 transition">
                        <input type="checkbox" name="is_halal_certified" value="1" {{ old('is_halal_certified') ? 'checked' : '' }} class="accent-[#ea580c] w-5 h-5 rounded cursor-pointer">
                        <div>
                            <span class="text-xs font-bold text-gray-900">Memiliki Sertifikat Halal MUI / BPJPH</span>
                            <p class="text-[11px] text-gray-500 font-medium">Tampilkan badge sertifikasi halal di katalog UMKM</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Image URL -->
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1.5">URL Foto Produk Komoditas</label>
                <input type="url" name="image_url" value="{{ old('image_url') }}" placeholder="https://images.unsplash.com/..." class="w-full px-4 py-3 bg-[#f1f2f4] border-0 rounded-xl font-medium text-xs text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c] transition">
                <p class="text-[11px] font-medium text-gray-400 mt-1">Gunakan URL gambar komoditas yang jernih agar menarik minat mitra UMKM.</p>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1.5">Deskripsi & Catatan Kualitas Pasokan</label>
                <textarea name="description" rows="3" placeholder="Jelaskan detail komoditas, keunggulan kesegaran, standar pengemasan, atau garansi retur..." class="w-full px-4 py-3 bg-[#f1f2f4] border-0 rounded-xl font-medium text-xs text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c] transition">{{ old('description') }}</textarea>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                <a href="{{ route('supplier.katalog.index') }}" class="px-6 py-3 rounded-full font-bold text-xs bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
                    Batal
                </a>
                <button type="submit" class="px-8 py-3 rounded-full font-bold text-xs bg-[#ea580c] hover:bg-[#c2410c] text-white shadow-sm hover:shadow-md transition cursor-pointer flex items-center gap-1">
                    <span>Simpan & Publikasikan ke Katalog →</span>
                </button>
            </div>

        </form>
    </div>

</div>
@endsection
