@extends('layouts.umkm')

@section('content')
<div x-data="{ showModal: false, selectedProduct: null }" class="max-w-6xl w-full mx-auto space-y-6 pb-10">

    <!-- Header Section -->
    <div class="mb-4">
        <p class="text-[#E07A5F] text-sm font-bold tracking-wider mb-1 uppercase">Katalog Lokal</p>
        <h1 class="text-3xl md:text-4xl font-extrabold text-[#1E222D] tracking-tight uppercase">Pilih & Ajukan Kemitraan Pasokan</h1>
    </div>

    <!-- Search and Filter Bar -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
        <form method="GET" action="{{ route('umkm.katalog.index') }}" class="flex flex-col gap-5">
            <!-- Search -->
            <div class="relative w-full">
                <svg class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari komoditas bahan baku (misal: Ayam, Bawang, Cabai) atau nama pemasok..." class="w-full pl-12 pr-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-full focus:outline-none focus:ring-0 focus:border-[#FFB703] transition text-sm font-medium">
            </div>

            <!-- Filters -->
            <div class="flex flex-col md:flex-row items-end gap-6 border-t border-gray-100 pt-5">
                <div class="w-full md:w-1/3">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5 font-mono">Kategori Bahan Baku:</label>
                    <select name="category" class="w-full px-4 py-2.5 bg-[#FFFDF5] border-2 border-black rounded-xl focus:outline-none focus:ring-0 focus:border-[#FFB703] text-sm font-bold" onchange="this.form.submit()">
                        <option value="Semua Kategori">Semua Kategori</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ $category == $cat ? 'selected' : '' }}>{{ strtoupper($cat) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full md:w-1/3">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5 font-mono">Rentang Harga Maksimal (Rp):</label>
                    <input type="text" name="max_price" value="{{ $maxPrice }}" placeholder="Contoh: 35000" class="w-full px-4 py-2.5 bg-[#FFFDF5] border-2 border-black rounded-xl focus:outline-none focus:ring-0 focus:border-[#FFB703] text-sm font-bold" onblur="this.form.submit()" onkeydown="if(event.key === 'Enter'){this.form.submit();}">
                </div>

                <div class="w-full md:w-1/3 flex items-center mb-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" class="w-4 h-4 text-black bg-[#FFFDF5] border-2 border-black rounded focus:ring-0 focus:ring-offset-0">
                        <span class="text-xs font-bold text-[#1E222D]">Hanya Tampilkan Sertifikat Halal</span>
                    </label>
                </div>
            </div>
            <!-- Hidden Submit -->
            <button type="submit" class="hidden">Cari</button>
        </form>
    </div>

    <!-- Product Grid -->
    @if($products->isEmpty())
        <div class="bg-white rounded-3xl p-12 text-center border-2 border-black shadow-[0_4px_0_0_rgba(0,0,0,1)]">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
            <h3 class="text-lg font-bold text-gray-900">Tidak ada produk ditemukan</h3>
            <p class="text-gray-500 mt-1 font-medium text-sm">Coba sesuaikan kata kunci pencarian atau rentang harga.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 pt-2">
            @foreach($products as $product)
                <div class="bg-white rounded-3xl rounded-t-3xl border border-gray-100 overflow-hidden shadow-sm flex flex-col relative group pb-5">
                    
                    <!-- Card Header / Image Placeholder -->
                    <div class="h-44 w-full bg-gradient-to-br from-gray-700 to-gray-900 relative rounded-t-3xl overflow-hidden">
                        @if($product->image_url)
                            <img src="{{ $product->image_url }}" alt="{{ $product->commodity->name }}" class="w-full h-full object-cover opacity-90 group-hover:opacity-100 transition-opacity" onerror="this.src='https://source.unsplash.com/400x300/?food,{{ urlencode($product->commodity->category) }}'">
                        @else
                            <img src="https://source.unsplash.com/400x300/?food,{{ urlencode($product->commodity->category) }}" alt="{{ $product->commodity->name }}" class="w-full h-full object-cover opacity-80 group-hover:opacity-100 transition-opacity" onerror="this.style.display='none'">
                        @endif
                        
                        <span class="absolute top-4 left-4 bg-white text-[#1E222D] text-[10px] font-black px-3 py-1.5 rounded-full shadow-sm uppercase tracking-wider border border-gray-100">
                            {{ $product->commodity->category }}
                        </span>
                    </div>

                    <!-- Card Body -->
                    <div class="p-5 flex-1 flex flex-col bg-white">
                        <h3 class="text-[17px] font-black text-[#F4511E] mb-3 leading-tight uppercase">
                            {{ $product->commodity->name }}
                        </h3>
                        
                        <div class="flex items-start gap-2 mb-2">
                            <svg class="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            <span class="text-[13px] font-bold text-[#1E222D]">{{ $product->supplier->company_name }}</span>
                        </div>

                        <div class="flex items-start gap-2 text-sm text-gray-500 mb-6">
                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-pink-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                            <span class="line-clamp-1 text-[12px] font-mono font-medium">{{ $product->supplier->warehouse_address }}</span>
                        </div>
                        
                        <!-- Capacity info added as per data structure, keeping it subtle -->
                        @if($product->stock_capacity)
                        <div class="mb-4">
                            <span class="text-[11px] bg-gray-100 text-gray-600 px-2 py-1 rounded font-bold">Kapasitas: {{ $product->stock_capacity }}</span>
                        </div>
                        @endif

                        <!-- Pricing -->
                        <div class="flex justify-between items-end mb-4 mt-auto">
                            <p class="text-[11px] text-gray-400 font-bold uppercase tracking-wider">Harga Grosir</p>
                            <p class="text-xl font-black text-[#F4511E]">Rp {{ number_format($product->price, 0, ',', '.') }}<span class="text-xs font-bold text-gray-500">/{{ $product->commodity->unit }}</span></p>
                        </div>
                        
                        <!-- Card Action -->
                        <button 
                            @click="selectedProduct = {{ json_encode([
                                'supplier_id' => $product->supplier->id,
                                'company_name' => $product->supplier->company_name,
                                'commodity_name' => $product->commodity->name,
                                'unit' => $product->commodity->unit,
                                'price' => 'Rp ' . number_format($product->price, 0, ',', '.') . ' / ' . $product->commodity->unit
                            ]) }}; showModal = true"
                            class="w-full bg-[#FFB703] hover:bg-[#FFB703]/90 text-black font-extrabold text-sm py-2.5 px-4 rounded-full border-2 border-black shadow-[0_2px_0_0_rgba(0,0,0,1)] transition flex items-center justify-center gap-1">
                            Ajukan Permintaan &rarr;
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8 flex justify-center">
            {{ $products->links() }}
        </div>
    @endif

    <!-- Alpine.js Modal for "Ajukan Kemitraan" -->
    <div x-show="showModal" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-title" role="dialog" aria-modal="true" style="display: none;">
        
        <!-- Backdrop -->
        <div x-show="showModal"
             x-transition.opacity
             class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm"></div>

        <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
            <!-- Modal Panel -->
            <div x-show="showModal"
                 @click.away="showModal = false"
                 x-transition
                 class="relative transform overflow-hidden rounded-3xl bg-[#FFFDF5] text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl border-0 p-6 md:p-10">
                
                <!-- Close Button -->
                <button @click="showModal = false" class="absolute top-6 right-6 text-gray-500 hover:text-gray-900 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>

                <!-- Header -->
                <div class="mb-6">
                    <p class="text-[#F4511E] text-sm font-extrabold tracking-widest uppercase mb-1">Form Pengajuan Permintaan</p>
                    <h3 class="text-3xl font-extrabold leading-9 text-[#1E222D] uppercase tracking-tight" id="modal-title" x-text="selectedProduct?.commodity_name"></h3>
                    <p class="text-base text-gray-600 font-bold mt-2">Pemasok: <span x-text="selectedProduct?.company_name"></span></p>
                </div>

                <hr class="border-t-2 border-[#1E222D] mb-8">
                
                <form id="kemitraan-form" method="POST" action="{{ route('umkm.katalog.ajukan') }}" class="space-y-6">
                    @csrf
                    <input type="hidden" name="supplier_id" :value="selectedProduct?.supplier_id">
                    <input type="hidden" name="unit" :value="selectedProduct?.unit">
                    
                    <div class="flex flex-col md:flex-row gap-6">
                        <!-- Volume Kebutuhan -->
                        <div class="w-full md:w-1/2">
                            <label class="block text-[15px] font-bold text-[#1E222D] mb-2">Volume Kebutuhan</label>
                            <div class="flex items-center gap-3">
                                <input type="number" name="volume" required placeholder="100" class="w-full px-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-xl focus:outline-none focus:ring-0 focus:border-[#FFB703] text-lg font-bold">
                                <span class="text-lg font-bold text-[#1E222D]" x-text="selectedProduct?.unit"></span>
                            </div>
                        </div>

                        <!-- Frekuensi Pengiriman -->
                        <div class="w-full md:w-1/2">
                            <label class="block text-[15px] font-bold text-[#1E222D] mb-2">Frekuensi Pengiriman</label>
                            <select name="frekuensi" required class="w-full px-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-xl focus:outline-none focus:ring-0 focus:border-[#FFB703] text-sm font-bold">
                                <option value="" disabled selected>Pilih Frekuensi...</option>
                                <option value="Harian">Harian</option>
                                <option value="1x Seminggu">1x Seminggu</option>
                                <option value="2x Seminggu (Senin & Kamis)">2x Seminggu (Senin & Kamis)</option>
                                <option value="2x Sebulan">2x Sebulan</option>
                                <option value="Fleksibel / Sesuai Panggilan">Fleksibel / Sesuai Panggilan</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Catatan Spesifikasi Mutu Dapur -->
                    <div>
                        <label class="block text-[15px] font-bold text-[#1E222D] mb-2">Catatan Spesifikasi Mutu Dapur</label>
                        <textarea name="submission_details" rows="3" required placeholder="Contoh: Pemotongan ayam dibagi 8 bagian, toleransi pengiriman jam 06.00 WIB." class="w-full px-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-xl focus:outline-none focus:ring-0 focus:border-[#FFB703] text-[15px] font-medium text-gray-700"></textarea>
                    </div>

                    <!-- Footer Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 mt-10">
                        <button type="button" @click="showModal = false" class="w-full sm:w-1/2 inline-flex justify-center rounded-full bg-white px-6 py-4 text-[15px] font-extrabold text-black border-2 border-black hover:bg-gray-50 transition">
                            Batal
                        </button>
                        <button type="submit" class="w-full sm:w-1/2 inline-flex justify-center rounded-full bg-[#FFB703] px-6 py-4 text-[15px] font-extrabold text-black border-2 border-black shadow-[0_2px_0_0_rgba(0,0,0,1)] hover:bg-[#FFB703]/90 transition">
                            Kirim Permintaan &rarr;
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
