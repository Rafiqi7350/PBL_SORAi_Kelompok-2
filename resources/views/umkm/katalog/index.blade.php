@extends('layouts.umkm')

@section('content')
<div x-data="{ 
    showModal: false, 
    selectedProduct: null, 
    inputVolume: 100,
    calculateTotal() {
        if (!this.selectedProduct || !this.inputVolume) return 'Rp 0';
        const total = (parseFloat(this.selectedProduct.numeric_price) || 0) * (parseFloat(this.inputVolume) || 0);
        return 'Rp ' + total.toLocaleString('id-ID');
    }
}" class="max-w-7xl w-full mx-auto space-y-6 pb-12">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-[#E07A5F] text-xs font-black tracking-widest uppercase bg-[#E07A5F]/10 px-3 py-1 rounded-full border border-[#E07A5F]/20">Portal Pasokan UMKM</span>
                <span class="text-xs font-bold text-gray-500 font-mono">| Komoditas & Stok Real-Time</span>
            </div>
            <h1 class="text-3xl md:text-4xl font-extrabold text-[#1E222D] tracking-tight uppercase">Katalog Komoditas Bahan Baku Lokal</h1>
            <p class="text-sm font-medium text-gray-600 mt-1">Jelajahi harga grosir komoditas, acuan harga pemerintah (HET), ketersediaan stok, dan ajukan kemitraan pasokan langsung.</p>
        </div>
        <div>
            <a href="{{ route('supplier.katalog.index') }}" class="inline-flex items-center gap-2 bg-[#1E222D] hover:bg-black text-white font-extrabold text-xs py-3 px-5 rounded-full shadow-md transition">
                <svg class="w-4 h-4 text-[#FFB703]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                Masuk Mode Pemasok / Kelola Stok
            </a>
        </div>
    </div>

    <!-- Search and Filter Bar -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border-2 border-black shadow-[4px_4px_0px_0px_rgba(30,34,45,1)]">
        <form method="GET" action="{{ route('umkm.katalog.index') }}" class="flex flex-col gap-5">
            <!-- Search Row -->
            <div class="flex flex-col md:flex-row items-center gap-3">
                <div class="relative w-full flex-1">
                    <svg class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari komoditas bahan baku (misal: Ayam, Bawang, Cabai, Beras) atau nama pemasok..." class="w-full pl-12 pr-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-full focus:outline-none focus:border-[#FFB703] text-sm font-bold">
                </div>

                <div class="w-full md:w-48">
                    <select name="sort" class="w-full px-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-full text-xs font-extrabold text-[#1E222D] focus:outline-none" onchange="this.form.submit()">
                        <option value="terbaru" {{ $sort == 'terbaru' ? 'selected' : '' }}>Sort: Terbaru</option>
                        <option value="harga_terendah" {{ $sort == 'harga_terendah' ? 'selected' : '' }}>Sort: Harga Terendah</option>
                        <option value="harga_tertinggi" {{ $sort == 'harga_tertinggi' ? 'selected' : '' }}>Sort: Harga Tertinggi</option>
                        <option value="kapasitas_terbesar" {{ $sort == 'kapasitas_terbesar' ? 'selected' : '' }}>Sort: Kapasitas Terbesar</option>
                    </select>
                </div>

                <button type="submit" class="w-full md:w-auto px-8 py-3 bg-[#FFB703] text-black font-extrabold text-sm rounded-full border-2 border-black hover:bg-[#FFB703]/90 transition">
                    Cari Pasokan
                </button>
            </div>

            <!-- Filters Row -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 border-t border-gray-100 pt-4">
                <!-- Category Filter -->
                <div>
                    <label class="block text-[11px] font-extrabold text-gray-700 uppercase mb-1">Kategori Komoditas:</label>
                    <select name="category" class="w-full px-3 py-2 bg-[#FFFDF5] border-2 border-black rounded-xl text-xs font-bold text-[#1E222D]" onchange="this.form.submit()">
                        <option value="Semua Kategori">Semua Kategori</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ $category == $cat ? 'selected' : '' }}>{{ strtoupper($cat) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Stock Status Filter -->
                <div>
                    <label class="block text-[11px] font-extrabold text-gray-700 uppercase mb-1">Status Ketersediaan Stok:</label>
                    <select name="stock_status" class="w-full px-3 py-2 bg-[#FFFDF5] border-2 border-black rounded-xl text-xs font-bold text-[#1E222D]" onchange="this.form.submit()">
                        <option value="Semua Status" {{ ($stockStatus ?? '') == 'Semua Status' ? 'selected' : '' }}>Semua Status Stok</option>
                        <option value="Tersedia" {{ ($stockStatus ?? '') == 'Tersedia' ? 'selected' : '' }}>🟢 Stok Tersedia</option>
                        <option value="Stok Terbatas" {{ ($stockStatus ?? '') == 'Stok Terbatas' ? 'selected' : '' }}>🟡 Stok Terbatas</option>
                        <option value="Stok Habis" {{ ($stockStatus ?? '') == 'Stok Habis' ? 'selected' : '' }}>🔴 Stok Habis</option>
                        <option value="Pre-Order" {{ ($stockStatus ?? '') == 'Pre-Order' ? 'selected' : '' }}>🔵 Pre-Order / Inden</option>
                    </select>
                </div>

                <!-- Max Price -->
                <div>
                    <label class="block text-[11px] font-extrabold text-gray-700 uppercase mb-1">Harga Maksimal (Rp/Unit):</label>
                    <input type="number" name="max_price" value="{{ $maxPrice }}" placeholder="Contoh: 35000" class="w-full px-3 py-2 bg-[#FFFDF5] border-2 border-black rounded-xl text-xs font-bold" onblur="this.form.submit()" onkeydown="if(event.key === 'Enter'){this.form.submit();}">
                </div>

                <!-- Halal & Reset -->
                <div class="flex items-center justify-between gap-2 pt-4 sm:pt-0">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_halal" value="1" {{ !empty($isHalal) ? 'checked' : '' }} onchange="this.form.submit()" class="w-4 h-4 text-black bg-[#FFFDF5] border-2 border-black rounded">
                        <span class="text-xs font-bold text-[#1E222D]">Sertifikat Halal</span>
                    </label>

                    @if($search || $category || $maxPrice || !empty($isHalal) || ($stockStatus && $stockStatus !== 'Semua Status'))
                        <a href="{{ route('umkm.katalog.index') }}" class="text-xs font-bold text-rose-600 hover:underline">
                            Reset Filter
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Product Grid -->
    @if($products->isEmpty())
        <div class="bg-white rounded-3xl p-12 text-center border-2 border-black shadow-[4px_4px_0px_0px_rgba(30,34,45,1)]">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
            <h3 class="text-xl font-extrabold text-[#1E222D]">Tidak ada komoditas bahan baku ditemukan</h3>
            <p class="text-gray-500 mt-1 font-medium text-sm">Coba sesuaikan kata kunci pencarian, kategori, atau rentang harga.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 pt-2">
            @foreach($products as $product)
                <div class="bg-white rounded-3xl border-2 border-black shadow-[4px_4px_0px_0px_rgba(30,34,45,1)] overflow-hidden flex flex-col relative group transition transform hover:-translate-y-1">
                    
                    <!-- Card Image & Badges -->
                    <div class="h-48 w-full bg-gradient-to-br from-gray-800 to-gray-950 relative overflow-hidden">
                        @if($product->image_url)
                            <img src="{{ $product->image_url }}" alt="{{ $product->commodity->name }}" class="w-full h-full object-cover opacity-90 group-hover:opacity-100 transition-opacity" onerror="this.src='https://placehold.co/400x300?text={{ urlencode($product->commodity->name) }}'">
                        @else
                            <img src="https://placehold.co/400x300?text={{ urlencode($product->commodity->name) }}" alt="{{ $product->commodity->name }}" class="w-full h-full object-cover opacity-80 group-hover:opacity-100 transition-opacity">
                        @endif

                        <!-- Category Pill -->
                        <span class="absolute top-3 left-3 bg-white text-[#1E222D] text-[10px] font-black px-3 py-1 rounded-full shadow-md uppercase tracking-wider border border-black">
                            {{ $product->commodity->category }}
                        </span>

                        <!-- Stock Status Badge -->
                        <div class="absolute top-3 right-3">
                            @if($product->stock_status === 'Tersedia')
                                <span class="bg-emerald-500 text-white text-[10px] font-black px-2.5 py-1 rounded-full shadow-md border border-black flex items-center gap-1 uppercase">
                                    <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span> Stok Tersedia
                                </span>
                            @elseif($product->stock_status === 'Stok Terbatas')
                                <span class="bg-amber-400 text-black text-[10px] font-black px-2.5 py-1 rounded-full shadow-md border border-black flex items-center gap-1 uppercase">
                                    ⚠️ Stok Terbatas
                                </span>
                            @elseif($product->stock_status === 'Stok Habis')
                                <span class="bg-rose-500 text-white text-[10px] font-black px-2.5 py-1 rounded-full shadow-md border border-black uppercase">
                                    🚫 Stok Habis
                                </span>
                            @else
                                <span class="bg-sky-500 text-white text-[10px] font-black px-2.5 py-1 rounded-full shadow-md border border-black uppercase">
                                    {{ $product->stock_status }}
                                </span>
                            @endif
                        </div>

                        <!-- Halal & Grade overlay bottom -->
                        <div class="absolute bottom-3 left-3 right-3 flex items-center justify-between gap-1">
                            @if($product->is_halal_certified)
                                <span class="bg-emerald-950/80 backdrop-blur-md text-emerald-300 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full border border-emerald-500/40">
                                    ✓ Halal MUI / BPJPH
                                </span>
                            @else
                                <span></span>
                            @endif

                            @if($product->quality_grade)
                                <span class="bg-black/75 backdrop-blur-md text-amber-300 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full border border-amber-400/40">
                                    {{ $product->quality_grade }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-5 flex-1 flex flex-col bg-white">
                        <h3 class="text-lg font-black text-[#1E222D] mb-1 leading-snug uppercase">
                            {{ $product->commodity->name }}
                        </h3>

                        <!-- Supplier Info -->
                        <div class="flex items-center gap-1.5 text-xs font-bold text-gray-700 mb-1">
                            <span class="text-amber-500">🏭</span>
                            <span class="line-clamp-1">{{ $product->supplier->company_name }}</span>
                            @if($product->supplier->nib_number)
                                <span class="text-[9px] bg-blue-100 text-blue-800 font-mono px-1.5 py-0.5 rounded">NIB</span>
                            @endif
                        </div>

                        <div class="flex items-start gap-1 text-[11px] text-gray-500 mb-3">
                            <span class="text-rose-400">📍</span>
                            <span class="line-clamp-1 font-mono">{{ $product->supplier->warehouse_address }}</span>
                        </div>

                        <!-- Government Price Comparison (HET) -->
                        @if($product->commodity->government_ref_price)
                            @php
                                $diff = $product->commodity->government_ref_price - $product->price;
                            @endphp
                            <div class="mb-4 bg-[#FFFDF5] border border-amber-200 p-2.5 rounded-2xl">
                                <div class="flex justify-between items-center text-[10px] font-bold text-gray-600 mb-0.5">
                                    <span>Acuan HET Govt:</span>
                                    <span class="font-mono">Rp {{ number_format($product->commodity->government_ref_price, 0, ',', '.') }}</span>
                                </div>
                                @if($diff > 0)
                                    <p class="text-[11px] font-black text-emerald-700 flex items-center gap-1">
                                        ⚡ Lebih hemat Rp {{ number_format($diff, 0, ',', '.') }}/{{ $product->commodity->unit }}
                                    </p>
                                @elseif($diff < 0)
                                    <p class="text-[11px] font-bold text-amber-700">
                                        Selisih Rp {{ number_format(abs($diff), 0, ',', '.') }} dari Ref
                                    </p>
                                @else
                                    <p class="text-[11px] font-bold text-gray-700">
                                        Sesuai Acuan HET Pemerintah
                                    </p>
                                @endif
                            </div>
                        @endif

                        <!-- Capacity & Minimum Order -->
                        <div class="flex items-center justify-between text-[11px] font-bold text-gray-500 mb-4">
                            <span>Pasokan: <strong class="text-gray-900">{{ $product->stock_capacity ?? '-' }}</strong></span>
                            <span>Min. Order: <strong class="text-gray-900">{{ $product->minimum_order ?? '1' }} {{ $product->commodity->unit }}</strong></span>
                        </div>

                        <!-- Pricing -->
                        <div class="flex justify-between items-end mb-4 mt-auto border-t border-gray-100 pt-3">
                            <div>
                                <p class="text-[10px] text-gray-400 font-extrabold uppercase tracking-wider">Harga Grosir Supplier</p>
                                <p class="text-xl font-black text-[#F4511E]">
                                    Rp {{ number_format($product->price, 0, ',', '.') }}
                                    <span class="text-xs font-bold text-gray-500">/{{ $product->commodity->unit }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <button 
                            @click="selectedProduct = {{ json_encode([
                                'id' => $product->id,
                                'supplier_id' => $product->supplier->id,
                                'company_name' => $product->supplier->company_name,
                                'commodity_name' => $product->commodity->name,
                                'unit' => $product->commodity->unit,
                                'price' => 'Rp ' . number_format($product->price, 0, ',', '.') . ' / ' . $product->commodity->unit,
                                'numeric_price' => $product->price,
                                'stock_status' => $product->stock_status,
                                'stock_capacity' => $product->stock_capacity,
                                'quality_grade' => $product->quality_grade,
                                'is_halal' => $product->is_halal_certified,
                                'description' => $product->description,
                                'gov_ref_price' => $product->commodity->government_ref_price ? 'Rp ' . number_format($product->commodity->government_ref_price, 0, ',', '.') : null
                            ]) }}; inputVolume = 100; showModal = true"
                            class="w-full bg-[#FFB703] hover:bg-[#FFB703]/90 text-black border-2 border-black font-extrabold text-sm py-3 px-4 rounded-full transition shadow-[2px_2px_0px_0px_rgba(30,34,45,1)] flex items-center justify-center gap-1">
                            Ajukan Kemitraan Pasokan &rarr;
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8 flex justify-center">
            {{ $products->links() }}
        </div>
    @endif

    <!-- Alpine.js Modal for Detail Komoditas & Ajukan Kemitraan -->
    <div x-show="showModal" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-title" role="dialog" aria-modal="true" style="display: none;">
        
        <!-- Backdrop -->
        <div x-show="showModal" x-transition.opacity class="fixed inset-0 bg-gray-900/80 transition-opacity backdrop-blur-sm"></div>

        <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
            <!-- Modal Panel -->
            <div x-show="showModal" @click.away="showModal = false" x-transition class="relative transform overflow-hidden rounded-3xl bg-[#FFFDF5] text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl border-2 border-black p-6 md:p-8">
                
                <!-- Close Button -->
                <button @click="showModal = false" class="absolute top-6 right-6 text-gray-500 hover:text-gray-900 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>

                <!-- Header -->
                <div class="mb-5">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-[#F4511E] text-xs font-black tracking-widest uppercase">Form Kemitraan Pasokan</span>
                        <template x-if="selectedProduct?.is_halal">
                            <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded-full border border-emerald-300">✓ Sertifikat Halal</span>
                        </template>
                    </div>
                    <h3 class="text-3xl font-extrabold leading-tight text-[#1E222D] uppercase tracking-tight" id="modal-title" x-text="selectedProduct?.commodity_name"></h3>
                    <p class="text-sm text-gray-700 font-bold mt-1">Pemasok Mitra: <span class="text-[#F4511E]" x-text="selectedProduct?.company_name"></span></p>
                </div>

                <!-- Product Quick Details Banner -->
                <div class="bg-white rounded-2xl p-4 border border-gray-200 mb-6 flex flex-wrap items-center justify-between gap-3 text-xs">
                    <div>
                        <span class="text-gray-500 font-bold uppercase block">Harga Grosir:</span>
                        <span class="text-base font-black text-[#F4511E]" x-text="selectedProduct?.price"></span>
                    </div>
                    <template x-if="selectedProduct?.gov_ref_price">
                        <div>
                            <span class="text-gray-500 font-bold uppercase block">Ref HET Pemerintah:</span>
                            <span class="font-bold text-gray-800" x-text="selectedProduct?.gov_ref_price"></span>
                        </div>
                    </template>
                    <div>
                        <span class="text-gray-500 font-bold uppercase block">Status Stok:</span>
                        <span class="font-extrabold text-emerald-700" x-text="selectedProduct?.stock_status"></span>
                    </div>
                    <div>
                        <span class="text-gray-500 font-bold uppercase block">Kapasitas:</span>
                        <span class="font-bold text-gray-800" x-text="selectedProduct?.stock_capacity"></span>
                    </div>
                </div>

                <form id="kemitraan-form" method="POST" action="{{ route('umkm.katalog.ajukan') }}" class="space-y-5">
                    @csrf
                    <input type="hidden" name="supplier_id" :value="selectedProduct?.supplier_id">
                    <input type="hidden" name="unit" :value="selectedProduct?.unit">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Volume Kebutuhan -->
                        <div>
                            <label class="block text-xs font-extrabold text-[#1E222D] uppercase mb-1">Volume Kebutuhan Pasokan (*)</label>
                            <div class="flex items-center gap-2">
                                <input type="number" name="volume" min="1" x-model="inputVolume" required placeholder="100" class="w-full px-4 py-3 bg-white border-2 border-black rounded-xl font-bold text-base focus:outline-none focus:border-[#FFB703]">
                                <span class="text-base font-extrabold text-[#1E222D]" x-text="selectedProduct?.unit"></span>
                            </div>
                        </div>

                        <!-- Frekuensi Pengiriman -->
                        <div>
                            <label class="block text-xs font-extrabold text-[#1E222D] uppercase mb-1">Frekuensi Pasokan (*)</label>
                            <select name="frekuensi" required class="w-full px-4 py-3 bg-white border-2 border-black rounded-xl text-xs font-bold focus:outline-none focus:border-[#FFB703]">
                                <option value="" disabled selected>Pilih Jadwal Pasokan...</option>
                                <option value="Harian">Harian (Setiap Hari)</option>
                                <option value="1x Seminggu">1x Seminggu</option>
                                <option value="2x Seminggu (Senin & Kamis)">2x Seminggu (Senin & Kamis)</option>
                                <option value="2x Sebulan">2x Sebulan</option>
                                <option value="Fleksibel / Sesuai Panggilan">Fleksibel / Sesuai Panggilan Dapur</option>
                            </select>
                        </div>
                    </div>

                    <!-- Live Total Calculator Card -->
                    <div class="bg-amber-100/70 border-2 border-black rounded-2xl p-4 flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-extrabold text-gray-700 uppercase">Estimasi Biaya Pasokan per Siklus</p>
                            <p class="text-xs text-gray-600 font-medium">(Volume × Harga Grosir Supplier)</p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-black text-[#1E222D]" x-text="calculateTotal()"></p>
                        </div>
                    </div>
                    
                    <!-- Catatan Spesifikasi Mutu Dapur -->
                    <div>
                        <label class="block text-xs font-extrabold text-[#1E222D] uppercase mb-1">Catatan Spesifikasi Mutu Dapur (*)</label>
                        <textarea name="submission_details" rows="3" required placeholder="Contoh: Ayam dipotong 8 bagian, toleransi waktu antar jam 06:00 WIB, wajib dikemas steril." class="w-full px-4 py-3 bg-white border-2 border-black rounded-xl text-xs font-medium text-gray-700 focus:outline-none focus:border-[#FFB703]"></textarea>
                    </div>

                    <!-- Footer Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3 pt-3">
                        <button type="button" @click="showModal = false" class="w-full sm:w-1/2 rounded-full bg-gray-100 py-3.5 text-xs font-extrabold text-black hover:bg-gray-200 transition">
                            Batal
                        </button>
                        <button type="submit" class="w-full sm:w-1/2 rounded-full bg-[#FFB703] border-2 border-black py-3.5 text-xs font-extrabold text-black hover:bg-[#FFB703]/90 transition shadow-md">
                            Kirim Pengajuan Kemitraan &rarr;
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
