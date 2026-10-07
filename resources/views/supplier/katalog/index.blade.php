@extends('layouts.supplier')

@section('content')
<div x-data="{ showQuickModal: false, selectedProduct: null }" class="max-w-7xl w-full mx-auto space-y-6 pb-12">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-[#E07A5F] text-xs font-black tracking-widest uppercase bg-[#E07A5F]/10 px-3 py-1 rounded-full border border-[#E07A5F]/20">Panel Pemasok</span>
                <span class="text-xs font-bold text-gray-500 font-mono">| {{ $supplier->company_name }}</span>
            </div>
            <h1 class="text-3xl md:text-4xl font-extrabold text-[#1E222D] tracking-tight uppercase">Kelola Katalog & Stok Bahan Baku</h1>
            <p class="text-sm font-medium text-gray-600 mt-1">Atur komoditas pasokan, pembaruan harga grosir, dan pemantauan kapasitas stok harian/mingguan.</p>
        </div>
        <div>
            <a href="{{ route('supplier.katalog.create') }}" class="inline-flex items-center gap-2 bg-[#FFB703] hover:bg-[#FFB703]/90 text-black font-extrabold text-sm py-3.5 px-6 rounded-full shadow-md hover:shadow-lg transition transform hover:-translate-y-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                Tambah Komoditas Baru
            </a>
        </div>
    </div>

    <!-- Summary Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Metric 1: Total Products -->
        <div class="bg-white rounded-3xl p-6 border-2 border-black shadow-[4px_4px_0px_0px_rgba(30,34,45,1)] flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Produk Katalog</p>
                <p class="text-3xl font-black text-[#1E222D] mt-1">{{ $totalProducts }}</p>
                <p class="text-xs font-semibold text-gray-400 mt-1">Komoditas Siap Pasok</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-100 flex items-center justify-center text-amber-700 font-black text-xl">
                📦
            </div>
        </div>

        <!-- Metric 2: Stok Tersedia -->
        <div class="bg-white rounded-3xl p-6 border-2 border-black shadow-[4px_4px_0px_0px_rgba(30,34,45,1)] flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Stok Tersedia</p>
                <p class="text-3xl font-black text-emerald-600 mt-1">{{ $stokTersedia }}</p>
                <p class="text-xs font-semibold text-emerald-600/80 mt-1">Siap Terima Order UMKM</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-100 flex items-center justify-center text-emerald-700 font-black text-xl">
                ✅
            </div>
        </div>

        <!-- Metric 3: Stok Terbatas -->
        <div class="bg-white rounded-3xl p-6 border-2 border-black shadow-[4px_4px_0px_0px_rgba(30,34,45,1)] flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Stok Terbatas</p>
                <p class="text-3xl font-black text-amber-600 mt-1">{{ $stokTerbatas }}</p>
                <p class="text-xs font-semibold text-amber-600/80 mt-1">Hampir Mencapai Batas</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-100 flex items-center justify-center text-amber-700 font-black text-xl">
                ⚠️
            </div>
        </div>

        <!-- Metric 4: Stok Habis -->
        <div class="bg-white rounded-3xl p-6 border-2 border-black shadow-[4px_4px_0px_0px_rgba(30,34,45,1)] flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Stok Habis / Nonaktif</p>
                <p class="text-3xl font-black text-rose-600 mt-1">{{ $stokHabis }}</p>
                <p class="text-xs font-semibold text-rose-600/80 mt-1">Perlu Restok Segera</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-rose-100 flex items-center justify-center text-rose-700 font-black text-xl">
                🚫
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
        <form method="GET" action="{{ route('supplier.katalog.index') }}" class="flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="relative w-full md:w-1/2">
                <svg class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama komoditas atau kategori..." class="w-full pl-12 pr-4 py-3 bg-[#FFFDF5] border-2 border-black rounded-full focus:outline-none focus:border-[#FFB703] text-sm font-medium">
            </div>

            <div class="flex items-center gap-3 w-full md:w-auto">
                <select name="status" onchange="this.form.submit()" class="px-5 py-3 bg-[#FFFDF5] border-2 border-black rounded-full text-sm font-bold text-[#1E222D] focus:outline-none">
                    <option value="Semua" {{ $statusFilter == 'Semua' ? 'selected' : '' }}>Semua Status Stok</option>
                    <option value="Tersedia" {{ $statusFilter == 'Tersedia' ? 'selected' : '' }}>🟢 Tersedia</option>
                    <option value="Stok Terbatas" {{ $statusFilter == 'Stok Terbatas' ? 'selected' : '' }}>🟡 Stok Terbatas</option>
                    <option value="Stok Habis" {{ $statusFilter == 'Stok Habis' ? 'selected' : '' }}>🔴 Stok Habis</option>
                    <option value="Pre-Order" {{ $statusFilter == 'Pre-Order' ? 'selected' : '' }}>🔵 Pre-Order</option>
                </select>

                <button type="submit" class="bg-[#1E222D] text-white px-6 py-3 rounded-full font-bold text-sm hover:bg-black transition">
                    Filter
                </button>

                @if($search || ($statusFilter && $statusFilter !== 'Semua'))
                    <a href="{{ route('supplier.katalog.index') }}" class="text-xs font-bold text-gray-500 hover:text-red-600 underline">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Products Table / List -->
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
        @if($products->isEmpty())
            <div class="p-12 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl">🌾</div>
                <h3 class="text-lg font-extrabold text-[#1E222D]">Belum ada komoditas di katalog</h3>
                <p class="text-sm font-medium text-gray-500 mt-1">Mulai tambahkan produk bahan baku komoditas Anda agar dapat diajukan oleh UMKM.</p>
                <a href="{{ route('supplier.katalog.create') }}" class="inline-block mt-4 bg-[#FFB703] text-black font-extrabold px-6 py-2.5 rounded-full text-sm hover:bg-[#FFB703]/90">
                    + Tambah Produk Sekarang
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#FFFDF5] border-b-2 border-gray-100 text-[11px] font-black uppercase text-gray-500 tracking-wider">
                            <th class="py-4 px-6">Komoditas & Foto</th>
                            <th class="py-4 px-6">Kategori</th>
                            <th class="py-4 px-6">Harga Grosir</th>
                            <th class="py-4 px-6">Harga Ref. Govt (HET)</th>
                            <th class="py-4 px-6">Kapasitas Pasokan</th>
                            <th class="py-4 px-6">Status Stok</th>
                            <th class="py-4 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm font-medium text-gray-700">
                        @foreach($products as $product)
                            <tr class="hover:bg-amber-50/30 transition">
                                <!-- Product Info -->
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-14 h-14 rounded-2xl overflow-hidden bg-gray-100 border border-gray-200 flex-shrink-0">
                                            @if($product->image_url)
                                                <img src="{{ $product->image_url }}" alt="{{ $product->commodity->name }}" class="w-full h-full object-cover" onerror="this.src='https://placehold.co/100?text=Komoditas'">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center font-bold text-gray-400 text-xs bg-gray-200">
                                                    No Image
                                                </div>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="font-extrabold text-[#1E222D] text-base leading-snug uppercase">
                                                {{ $product->commodity->name }}
                                            </p>
                                            <div class="flex items-center gap-2 mt-1">
                                                @if($product->is_halal_certified)
                                                    <span class="text-[10px] font-bold bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full border border-emerald-300">✓ Halal</span>
                                                @endif
                                                @if($product->quality_grade)
                                                    <span class="text-[10px] font-bold bg-amber-100 text-amber-900 px-2 py-0.5 rounded-full">{{ $product->quality_grade }}</span>
                                                @endif
                                                <span class="text-[11px] text-gray-400 font-mono">Min Order: {{ $product->minimum_order ?? '1' }} {{ $product->commodity->unit }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Category -->
                                <td class="py-4 px-6">
                                    <span class="px-3 py-1 bg-gray-100 text-[#1E222D] text-xs font-black rounded-full uppercase">
                                        {{ $product->commodity->category }}
                                    </span>
                                </td>

<<<<<<< HEAD
                                <!-- Price -->
=======
                                <!-- Satuan -->
                                <td class="py-4 px-6 font-mono font-bold text-gray-900">
                                    {{ $product->effective_unit }}
                                </td>

                                <!-- Harga Grosir -->
>>>>>>> 886da98b5b50ea650d3b97d2b27ee989f31d1da7
                                <td class="py-4 px-6">
                                    <p class="font-black text-[#F4511E] text-base">
                                        Rp {{ number_format($product->price, 0, ',', '.') }}
                                    </p>
<<<<<<< HEAD
                                    <p class="text-[11px] font-bold text-gray-400 uppercase">per {{ $product->commodity->unit }}</p>
                                </td>

                                <!-- Ref Price Govt -->
                                <td class="py-4 px-6">
                                    @if($product->commodity->government_ref_price)
                                        <p class="font-extrabold text-gray-700 text-sm">
                                            Rp {{ number_format($product->commodity->government_ref_price, 0, ',', '.') }}
                                        </p>
                                        @php
                                            $diff = $product->commodity->government_ref_price - $product->price;
                                        @endphp
                                        @if($diff > 0)
                                            <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">
                                                Lebih hemat Rp {{ number_format($diff, 0, ',', '.') }}
                                            </span>
                                        @elseif($diff < 0)
                                            <span class="text-[10px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded">
                                                Diatas ref HET
                                            </span>
                                        @else
                                            <span class="text-[10px] font-bold text-gray-500 bg-gray-100 px-2 py-0.5 rounded">
                                                Sesuai Ref Govt
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-xs text-gray-400 font-mono">-</span>
                                    @endif
                                </td>

                                <!-- Capacity -->
                                <td class="py-4 px-6 font-bold text-gray-800">
                                    {{ $product->stock_capacity ?? 'Tidak diset' }}
                                </td>

                                <!-- Stock Status Badge -->
                                <td class="py-4 px-6">
                                    @if($product->stock_status === 'Tersedia')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 border border-emerald-300">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Tersedia
                                        </span>
                                    @elseif($product->stock_status === 'Stok Terbatas')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-800 border border-amber-300">
                                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                            Stok Terbatas
                                        </span>
                                    @elseif($product->stock_status === 'Stok Habis')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-rose-100 text-rose-800 border border-rose-300">
                                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                            Stok Habis
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-sky-100 text-sky-800 border border-sky-300">
                                            <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                                            {{ $product->stock_status }}
=======
                                    <p class="text-[10px] font-bold text-gray-400 uppercase">per {{ $product->effective_unit }}</p>
                                    @if($product->commodity->government_ref_price)
                                        <p class="text-[10px] text-gray-500 font-semibold mt-0.5">
                                            Acuan Govt: Rp {{ number_format($product->commodity->government_ref_price, 0, ',', '.') }}
                                        </p>
                                    @endif
                                </td>

                                <!-- Kapasitas Pasok -->
                                <td class="py-4 px-6 font-bold text-gray-900">
                                    {{ $product->weekly_capacity ? number_format($product->weekly_capacity, 0, ',', '.') . ' ' . $product->effective_unit . ' / minggu' : ($product->stock_capacity ?? '-') }}
                                </td>

                                <!-- Status Stok -->
                                <td class="py-4 px-6">
                                    @if(in_array($product->stock_status, ['ready', 'Tersedia']))
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 border border-emerald-400">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                            🟢 Ready (Pasokan Rutin)
                                        </span>
                                    @elseif(in_array($product->stock_status, ['limited', 'Stok Terbatas']))
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-800 border border-amber-400">
                                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                            🟡 Limited (Terbatas)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-rose-100 text-rose-800 border border-rose-400">
                                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                            🔴 {{ $product->stock_status }}
>>>>>>> 886da98b5b50ea650d3b97d2b27ee989f31d1da7
                                        </span>
                                    @endif
                                </td>

<<<<<<< HEAD
                                <!-- Actions -->
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Quick Stock Update Trigger -->
=======
                                <!-- Aksi -->
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Quick Stock Update Button -->
>>>>>>> 886da98b5b50ea650d3b97d2b27ee989f31d1da7
                                        <button 
                                            @click="selectedProduct = {{ json_encode([
                                                'id' => $product->id,
                                                'name' => $product->commodity->name,
<<<<<<< HEAD
                                                'status' => $product->stock_status,
                                                'capacity' => $product->stock_capacity
                                            ]) }}; showQuickModal = true"
                                            title="Update Stok Cepat"
                                            class="p-2 bg-amber-100 text-amber-900 hover:bg-amber-200 rounded-xl transition text-xs font-extrabold flex items-center gap-1">
                                            ⚡ Quick Update
                                        </button>

                                        <!-- Edit -->
                                        <a href="{{ route('supplier.katalog.edit', $product->id) }}" class="p-2 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-xl transition" title="Edit Detail">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </a>

                                        <!-- Delete -->
                                        <form method="POST" action="{{ route('supplier.katalog.destroy', $product->id) }}" onsubmit="return confirm('Yakin ingin menghapus komoditas ini dari katalog?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 bg-rose-50 text-rose-600 hover:bg-rose-100 rounded-xl transition" title="Hapus">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
=======
                                                'status' => in_array($product->stock_status, ['ready', 'Tersedia']) ? 'ready' : 'limited',
                                                'capacity' => $product->weekly_capacity ?? ''
                                            ]) }}; showQuickModal = true"
                                            title="Update Stok Cepat"
                                            class="p-2 bg-amber-100 text-amber-900 hover:bg-amber-200 rounded-xl transition text-xs font-extrabold flex items-center gap-1">
                                            ⚡ Quick Status
                                        </button>

                                        <!-- Edit -->
                                        <a href="{{ route('supplier.katalog.edit', $product->id) }}" class="p-2 bg-gray-100 text-gray-900 hover:bg-gray-200 rounded-xl transition font-bold text-xs" title="Edit Product">
                                            ✏️ Edit
                                        </a>

                                        <!-- Delete -->
                                        <form method="POST" action="{{ route('supplier.katalog.destroy', $product->id) }}" onsubmit="return confirm('Yakin ingin menghapus produk komoditas {{ $product->commodity->name }} dari katalog Anda?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 bg-rose-50 text-rose-600 hover:bg-rose-100 rounded-xl transition font-bold text-xs" title="Hapus">
                                                🗑️ Hapus
>>>>>>> 886da98b5b50ea650d3b97d2b27ee989f31d1da7
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
<<<<<<< HEAD
            <div class="p-6 border-t border-gray-100">
=======
            <div class="p-6 border-t-2 border-gray-100">
>>>>>>> 886da98b5b50ea650d3b97d2b27ee989f31d1da7
                {{ $products->links() }}
            </div>
        @endif
    </div>

    <!-- Quick Stock Update Modal (Alpine.js) -->
<<<<<<< HEAD
    <div x-show="showQuickModal" 
         class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
=======
    <div x-show="showQuickModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
>>>>>>> 886da98b5b50ea650d3b97d2b27ee989f31d1da7
        <div x-show="showQuickModal" x-transition.opacity class="fixed inset-0 bg-gray-900/70 backdrop-blur-sm"></div>

        <div class="flex min-h-screen items-center justify-center p-4">
            <div x-show="showQuickModal" @click.away="showQuickModal = false" x-transition class="relative w-full max-w-lg rounded-3xl bg-[#FFFDF5] p-8 shadow-2xl border-2 border-black">
                <div class="flex justify-between items-center mb-4">
                    <div>
<<<<<<< HEAD
                        <span class="text-xs font-black text-[#E07A5F] tracking-widest uppercase">Pembaruan Cepat Stok</span>
=======
                        <span class="text-xs font-black text-[#E07A5F] tracking-widest uppercase">Quick Update Status Stok</span>
>>>>>>> 886da98b5b50ea650d3b97d2b27ee989f31d1da7
                        <h3 class="text-2xl font-extrabold text-[#1E222D] uppercase" x-text="selectedProduct?.name"></h3>
                    </div>
                    <button @click="showQuickModal = false" class="text-gray-400 hover:text-black font-extrabold text-xl">&times;</button>
                </div>

                <form :action="'{{ url('/supplier/katalog') }}/' + selectedProduct?.id + '/quick-stock'" method="POST" class="space-y-5">
                    @csrf
                    @method('PATCH')

                    <div>
<<<<<<< HEAD
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-2">Status Ketersediaan Stok</label>
                        <select name="stock_status" x-model="selectedProduct.status" required class="w-full px-4 py-3 bg-white border-2 border-black rounded-xl font-bold text-sm">
                            <option value="Tersedia">🟢 Tersedia (Bisa Dipesan)</option>
                            <option value="Stok Terbatas">🟡 Stok Terbatas (Peringatan Kuota)</option>
                            <option value="Stok Habis">🔴 Stok Habis (Sementara Kosong)</option>
                            <option value="Pre-Order">🔵 Pre-Order (Pemesanan Awal)</option>
=======
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-2">Status Ketersediaan Stok (*)</label>
                        <select name="stock_status" x-model="selectedProduct.status" required class="w-full px-4 py-3 bg-white border-2 border-black rounded-xl font-bold text-sm">
                            <option value="ready">🟢 Ready (Ready Pasokan Rutin)</option>
                            <option value="limited">🟡 Limited (Terbatas)</option>
                            <option value="Stok Habis">🔴 Stok Habis</option>
>>>>>>> 886da98b5b50ea650d3b97d2b27ee989f31d1da7
                        </select>
                    </div>

                    <div>
<<<<<<< HEAD
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-2">Kapasitas Stok Pasokan</label>
                        <input type="text" name="stock_capacity" x-model="selectedProduct.capacity" placeholder="Contoh: 800 kg/minggu" class="w-full px-4 py-3 bg-white border-2 border-black rounded-xl font-bold text-sm">
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="showQuickModal = false" class="w-1/2 py-3 bg-gray-100 rounded-full font-bold text-sm hover:bg-gray-200">Batal</button>
                        <button type="submit" class="w-1/2 py-3 bg-[#FFB703] rounded-full font-extrabold text-sm hover:bg-[#FFB703]/90">Simpan Stok &rarr;</button>
=======
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-2">Kapasitas Pasok Mingguan</label>
                        <input type="number" step="0.1" min="0.1" name="weekly_capacity" x-model="selectedProduct.capacity" placeholder="Contoh: 500" class="w-full px-4 py-3 bg-white border-2 border-black rounded-xl font-bold text-sm">
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="showQuickModal = false" class="w-1/2 py-3 bg-gray-100 rounded-full font-bold text-xs hover:bg-gray-200">Batal</button>
                        <button type="submit" class="w-1/2 py-3 bg-[#FFB703] border-2 border-black rounded-full font-extrabold text-xs hover:bg-[#FFB703]/90">Simpan Stok &rarr;</button>
>>>>>>> 886da98b5b50ea650d3b97d2b27ee989f31d1da7
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
