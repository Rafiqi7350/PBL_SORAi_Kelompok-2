@extends('layouts.umkm')

@section('content')
<script>
    function umkmCatalogPage() {
        return {
            selectedCommodity: null,
            payingOrder: null,
            ratingOrder: null,
            ratingScore: 5,
            ratingComment: '',
            notificationMsg: @json(session('success') ?? ''),

            planningForm: {
                commodity: 'Daging Ayam Broiler Segar',
                volume: '200',
                targetDate: '2026-11'
            },

            inputVolume: 100,
            calculateTotal: function() {
                if (!this.selectedCommodity || !this.inputVolume) return 'Rp 0';
                var total = (parseFloat(this.selectedCommodity.numeric_price) || 0) * (parseFloat(this.inputVolume) || 0);
                return 'Rp ' + total.toLocaleString('id-ID');
            },

            orders: [
                { id: 'ORD-2026-001', umkmName: 'Dapur Sambal Malang', supplierName: 'Kelompok Tani Poultry Malang', commodity: 'Daging Ayam Broiler Segar', requestedQty: 100, approvedQty: 100, unit: 'kg', pricePerUnit: 34000, total: 3400000, date: '01/10/2026', status: 'MENUNGGU_KONFIRMASI_BARANG', stockNote: 'Barang Tiba Di Dapur Subuh Tadi', paymentStatus: 'UNPAID', rating: null },
                { id: 'ORD-2026-002', umkmName: 'Dapur Sambal Malang', supplierName: 'Kelompok Tani Poultry Malang', commodity: 'Daging Ayam Broiler Segar', requestedQty: 200, approvedQty: 120, unit: 'kg', pricePerUnit: 34000, total: 4080000, date: '28/09/2026', status: 'PENYESUAIAN_STOK', stockNote: 'Stok Terbatas (Dikirim 120kg dari 200kg)', paymentStatus: 'UNPAID', rating: null },
                { id: 'ORD-2026-003', umkmName: 'Dapur Sambal Malang', supplierName: 'Distributor Rempah Nusantara', commodity: 'Bawang Merah Biru Probolinggo', requestedQty: 50, approvedQty: 50, unit: 'kg', pricePerUnit: 28000, total: 1400000, date: '22/09/2026', status: 'DIKIRIM', stockNote: 'Pengiriman Subuh Dalam Perjalanan', paymentStatus: 'PAID', rating: null },
                { id: 'ORD-2026-004', umkmName: 'Dapur Sambal Malang', supplierName: 'Kelompok Tani Poultry Malang', commodity: 'Daging Ayam Broiler Segar', requestedQty: 150, approvedQty: 150, unit: 'kg', pricePerUnit: 34000, total: 5100000, date: '15/09/2026', status: 'SELESAI', stockNote: 'Selesai & Diterima Dapur', paymentStatus: 'PAID', rating: 5 }
            ],

            confirmGoodsReceived: function(orderId) {
                this.orders = this.orders.map(function(o) {
                    return o.id === orderId ? Object.assign({}, o, { status: 'BARANG_DITERIMA', stockNote: 'Kesesuaian Barang Telah Dikonfirmasi UMKM' }) : o;
                });
                this.notify('✓ Kesesuaian fisik bahan baku pesanan telah divalidasi!');
            },

            openMidtransPayment: function(order) {
                this.payingOrder = order;
            },

            simulateMidtransSuccess: function() {
                if (!this.payingOrder) return;
                var targetId = this.payingOrder.id;
                this.orders = this.orders.map(function(o) {
                    return o.id === targetId ? Object.assign({}, o, { paymentStatus: 'PAID', status: 'SELESAI', stockNote: 'Selesai & Lunas Via Midtrans' }) : o;
                });
                var finishedOrder = Object.assign({}, this.payingOrder);
                this.payingOrder = null;
                this.ratingOrder = finishedOrder;
            },

            saveRating: function() {
                if (!this.ratingOrder) return;
                var targetId = this.ratingOrder.id;
                var score = this.ratingScore;
                this.orders = this.orders.map(function(o) {
                    return o.id === targetId ? Object.assign({}, o, { rating: score }) : o;
                });
                this.ratingOrder = null;
                this.notify('✓ Terima kasih! Ulasan & rating ' + score + ' bintang berhasil dikirim ke Pemasok.');
            },

            sendSupplyPlan: function() {
                this.notify('✓ Estimasi Collaborative Supply Plan (' + this.planningForm.volume + ' kg) berhasil dikirimkan ke Pemasok Mitra!');
            },

            notify: function(msg) {
                var self = this;
                this.notificationMsg = msg;
                setTimeout(function() {
                    if (self.notificationMsg === msg) self.notificationMsg = '';
                }, 5000);
            }
        };
    }
</script>

<div x-data="umkmCatalogPage()" class="space-y-6 pb-12">

    <!-- FLOATING NOTIFIKASI TOAST -->
    <div x-cloak x-show="notificationMsg" x-transition class="fixed top-20 right-4 sm:right-8 z-50 max-w-md w-full">
        <div class="bg-emerald-600 text-white p-4 rounded-2xl font-medium text-xs flex justify-between items-center shadow-xl">
            <span x-text="notificationMsg"></span>
            <button type="button" @click="notificationMsg = ''" class="font-bold text-base ml-2 hover:opacity-75">✕</button>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- TAB 1: KATALOG BAHAN BAKU                                -->
    <!-- ========================================================= -->
    <div x-show="activeTab === 'katalog'" class="space-y-6">

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2">
            <div>
                <span class="text-xs font-bold text-orange-600 tracking-wider uppercase font-mono">KATALOG LOKAL</span>
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">Pilih & Ajukan Kemitraan Pasokan</h2>
            </div>

        </div>

        <!-- CONTAINER SEARCH & FILTER CLEAN -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 space-y-4">
            <form method="GET" action="{{ route('umkm.katalog.index') }}">
                <input type="hidden" name="tab" value="katalog">

                <div class="relative">
                    <span class="absolute left-4 top-3.5 text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </span>
                    <input 
                        type="text" 
                        name="search"
                        value="{{ $search }}"
                        placeholder="Cari komoditas bahan baku (misal: Ayam, Bawang, Cabai, Telur) atau nama pemasok..."
                        class="w-full pl-11 pr-6 py-3 text-xs font-medium bg-[#f1f2f4] rounded-full text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c] border-0 transition"
                        @input.debounce.700ms="$event.target.form.submit()"
                        {{ request()->has('search') ? 'autofocus' : '' }}
                        onfocus="var val = this.value; this.value = ''; this.value = val;"
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 pt-3 border-t border-gray-100 text-xs font-semibold">
                    <div>
                        <label class="block text-gray-600 mb-1.5 text-[11px]">Kategori Bahan Baku:</label>
                        <select 
                            name="category"
                            onchange="this.form.submit()"
                            class="w-full px-3 py-2.5 rounded-xl bg-[#f1f2f4] text-gray-800 font-medium focus:outline-none focus:ring-2 focus:ring-[#ea580c] border-0">
                            <option value="Semua Kategori">Semua Kategori</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" {{ $category == $cat ? 'selected' : '' }}>{{ strtoupper($cat) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-600 mb-1.5 text-[11px]">Rentang Harga Maksimal (Rp):</label>
                        <input 
                            type="number" 
                            name="max_price"
                            placeholder="Contoh: 35000" 
                            value="{{ $maxPrice }}"
                            @input.debounce.700ms="$event.target.form.submit()"
                            class="w-full px-3 py-2.5 rounded-xl bg-[#f1f2f4] text-gray-800 font-medium focus:outline-none focus:ring-2 focus:ring-[#ea580c] border-0"
                        />
                    </div>

                    <div>
                        <label class="block text-gray-600 mb-1.5 text-[11px]">Status Ketersediaan Stok:</label>
                        <select 
                            name="stock_status"
                            onchange="this.form.submit()"
                            class="w-full px-3 py-2.5 rounded-xl bg-[#f1f2f4] text-gray-800 font-medium focus:outline-none focus:ring-2 focus:ring-[#ea580c] border-0">
                            <option value="Semua Status" {{ ($stockStatus ?? '') == 'Semua Status' ? 'selected' : '' }}>Semua Status Stok</option>
                            <option value="Tersedia" {{ ($stockStatus ?? '') == 'Tersedia' ? 'selected' : '' }}>🟢 Stok Tersedia</option>
                            <option value="Stok Terbatas" {{ ($stockStatus ?? '') == 'Stok Terbatas' ? 'selected' : '' }}>🟡 Stok Terbatas</option>
                            <option value="Stok Habis" {{ ($stockStatus ?? '') == 'Stok Habis' ? 'selected' : '' }}>🔴 Stok Habis</option>
                            <option value="Pre-Order" {{ ($stockStatus ?? '') == 'Pre-Order' ? 'selected' : '' }}>⏳ Pre-Order</option>
                        </select>
                    </div>

                    <div class="flex items-end justify-between pb-1 gap-2">
                        <label class="flex items-center gap-2 cursor-pointer font-medium text-gray-700">
                            <input 
                                type="checkbox" 
                                name="is_halal"
                                value="1"
                                {{ !empty($isHalal) ? 'checked' : '' }}
                                onchange="this.form.submit()"
                                class="accent-[#ea580c] w-4 h-4 rounded cursor-pointer"
                            />
                            <span>Hanya Sertifikat Halal</span>
                        </label>

                        @if($search || ($category && $category !== 'Semua Kategori') || $maxPrice || !empty($isHalal) || ($stockStatus && $stockStatus !== 'Semua Status'))
                            <a href="{{ route('umkm.katalog.index', ['tab' => 'katalog']) }}" class="text-xs font-bold text-rose-600 hover:underline">
                                Reset Filter
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <!-- GRID KATALOG KOMODITAS BAHAN BAKU -->
        @if($products->isEmpty())
            <div class="bg-white rounded-3xl p-12 text-center shadow-sm border border-gray-100 space-y-2">
                <span class="text-3xl block">📦</span>
                <h3 class="text-lg font-bold text-gray-900">Tidak Ada Komoditas Bahan Baku Ditemukan</h3>
                <p class="text-xs text-gray-500 font-medium">Silakan sesuaikan kata kunci pencarian, kategori, atau rentang harga maksimal Anda.</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 w-full pt-2">
                @foreach($products as $product)
                    <div class="bg-white rounded-3xl overflow-hidden flex flex-col justify-between shadow-sm hover:shadow-md transition-all border border-gray-100 group">
                        
                        <!-- Gambar Komoditas & Badges -->
                        <div class="relative h-44 w-full bg-gray-100 overflow-hidden">
                            @if($product->image_url)
                                <img src="{{ $product->image_url }}" alt="{{ $product->commodity->name }}" class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-500" onerror="this.src='https://images.unsplash.com/photo-1587593810167-a84920ea0781?w=500&auto=format&fit=crop&q=60'">
                            @else
                                <img src="https://images.unsplash.com/photo-1587593810167-a84920ea0781?w=500&auto=format&fit=crop&q=60" alt="{{ $product->commodity->name }}" class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-500">
                            @endif

                            <span class="absolute top-3 left-3 bg-white/95 text-gray-800 text-[10px] font-bold px-2.5 py-1 rounded-full shadow-sm uppercase">
                                {{ $product->commodity->category }}
                            </span>

                            <div class="absolute top-3 right-3">
                                @if($product->stock_status === 'Tersedia')
                                    <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2.5 py-1 rounded-full shadow-sm">
                                        ● Stok Ada
                                    </span>
                                @elseif($product->stock_status === 'Stok Terbatas')
                                    <span class="bg-amber-100 text-amber-800 text-[10px] font-bold px-2.5 py-1 rounded-full shadow-sm">
                                        ⚠️ Terbatas
                                    </span>
                                @elseif($product->stock_status === 'Pre-Order')
                                    <span class="bg-blue-100 text-blue-800 text-[10px] font-bold px-2.5 py-1 rounded-full shadow-sm">
                                        ⏳ Pre-Order
                                    </span>
                                @else
                                    <span class="bg-rose-100 text-rose-800 text-[10px] font-bold px-2.5 py-1 rounded-full shadow-sm">
                                        ✕ Habis
                                    </span>
                                @endif
                            </div>

                            @if($product->is_halal_certified)
                                <span class="absolute bottom-2.5 left-3 bg-white/95 text-emerald-800 text-[9px] font-bold px-2 py-0.5 rounded-full shadow-sm">
                                    ✓ Halal Resmi
                                </span>
                            @endif
                        </div>

                        <!-- Konten Kartu -->
                        <div class="p-5 flex-1 flex flex-col justify-between space-y-3">
                            <div class="space-y-1">
                                <h3 class="font-bold text-base text-gray-900 group-hover:text-orange-600 transition leading-snug">
                                    {{ $product->commodity->name }}
                                </h3>
                                <p class="text-xs font-semibold text-gray-600 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 0h4m-4 0H9m4 0V5" />
                                    </svg>
                                    <span class="truncate">{{ $product->supplier->company_name }}</span>
                                </p>
                                <p class="text-[11px] text-gray-400 truncate">
                                    📍 {{ $product->supplier->warehouse_address }}
                                </p>
                            </div>

                            <!-- Acuan HET Pemerintah / Penghematan -->
                            @if($product->commodity->government_ref_price)
                                @php
                                    $diff = $product->commodity->government_ref_price - $product->price;
                                @endphp
                                <div class="bg-gray-50 p-2.5 rounded-xl border border-gray-100 text-[11px]">
                                    <div class="flex justify-between text-gray-500">
                                        <span>HET Acuan:</span>
                                        <span class="font-semibold text-gray-700">Rp {{ number_format($product->commodity->government_ref_price, 0, ',', '.') }}</span>
                                    </div>
                                    @if($diff > 0)
                                        <p class="text-emerald-700 font-bold mt-0.5">
                                            ⚡ Hemat Rp {{ number_format($diff, 0, ',', '.') }}/{{ $product->commodity->unit }}
                                        </p>
                                    @endif
                                </div>
                            @endif

                            <div class="border-t border-gray-100 pt-3 space-y-2">
                                <div class="flex justify-between items-baseline">
                                    <span class="text-[11px] text-gray-500">Harga Grosir</span>
                                    <span class="font-bold text-base text-orange-600">
                                        Rp {{ number_format($product->price, 0, ',', '.') }}<span class="text-xs text-gray-400 font-normal">/{{ $product->commodity->unit }}</span>
                                    </span>
                                </div>

                                @if($product->stock_status !== 'Stok Habis')
                                    <button 
                                        type="button"
                                        @click="selectedCommodity = {{ json_encode([
                                            'id' => $product->id,
                                            'supplier_id' => $product->supplier->id,
                                            'company_name' => $product->supplier->company_name,
                                            'commodity_name' => $product->commodity->name,
                                            'unit' => $product->commodity->unit,
                                            'price' => 'Rp ' . number_format($product->price, 0, ',', '.') . ' / ' . $product->commodity->unit,
                                            'numeric_price' => $product->price,
                                            'stock_status' => $product->stock_status,
                                            'stock_capacity' => $product->stock_capacity,
                                            'is_halal' => $product->is_halal_certified,
                                            'gov_ref_price' => $product->commodity->government_ref_price ? 'Rp ' . number_format($product->commodity->government_ref_price, 0, ',', '.') : null
                                        ]) }}; inputVolume = 100;" 
                                        class="w-full bg-[#ea580c] hover:bg-[#c2410c] text-white font-bold py-2.5 rounded-full text-xs transition shadow-sm active:scale-95 cursor-pointer">
                                        Ajukan Permintaan →
                                    </button>
                                @else
                                    <button 
                                        type="button"
                                        disabled
                                        class="w-full bg-gray-200 text-gray-500 font-bold py-2.5 rounded-full text-xs cursor-not-allowed">
                                        Stok Tidak Tersedia
                                    </button>
                                @endif
                            </div>
                        </div>

                    </div>
                @endforeach
            </div>

            <div class="mt-8 flex justify-center">
                {{ $products->links() }}
            </div>
        @endif

    </div>

    <!-- ========================================================= -->
    <!-- TAB 2: PEMESANAN & SUPPLY PLANNING                       -->
    <!-- ========================================================= -->
    <div x-show="activeTab === 'pemesanan'" class="space-y-8">

        <!-- COLLABORATIVE SUPPLY PLANNING BOX -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 space-y-4">
            <div class="flex items-center gap-3 border-b border-gray-100 pb-3">
                <span class="p-2.5 bg-[#ea580c] rounded-full text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </span>
                <div>
                    <h3 class="font-bold text-lg text-gray-900">🌾 Collaborative Supply Planning (Proyeksi Pasokan)</h3>
                    <p class="text-xs text-gray-500">Sampaikan estimasi proyeksi kebutuhan dapur bulan depan agar pemasok dapat menyesuaikan kapasitas panen/ternak.</p>
                </div>
            </div>

            <form @submit.prevent="sendSupplyPlan()" class="grid grid-cols-1 sm:grid-cols-4 gap-4 text-xs font-semibold">
                <div>
                    <label class="block mb-1.5 text-gray-700">Pilih Komoditas Pasokan:</label>
                    <select x-model="planningForm.commodity" class="w-full p-2.5 rounded-xl bg-[#f1f2f4] text-gray-800 font-medium border-0 focus:outline-none focus:ring-2 focus:ring-[#ea580c]">
                        <option>Daging Ayam Broiler Segar</option>
                        <option>Bawang Merah Biru Probolinggo</option>
                        <option>Telur Ayam Ras Grade A</option>
                        <option>Cabai Rawit Merah Super</option>
                    </select>
                </div>

                <div>
                    <label class="block mb-1.5 text-gray-700">Estimasi Volume (kg):</label>
                    <input type="number" x-model="planningForm.volume" required min="1" class="w-full p-2.5 rounded-xl bg-[#f1f2f4] text-gray-800 font-medium border-0 focus:outline-none focus:ring-2 focus:ring-[#ea580c]" />
                </div>

                <div>
                    <label class="block mb-1.5 text-gray-700">Proyeksi Bulan Target:</label>
                    <input type="month" x-model="planningForm.targetDate" class="w-full p-2.5 rounded-xl bg-[#f1f2f4] text-gray-800 font-medium border-0 focus:outline-none focus:ring-2 focus:ring-[#ea580c]" />
                </div>

                <div class="flex items-end">
                    <button type="submit" class="w-full bg-[#18181b] hover:bg-black text-white p-3 rounded-full font-bold shadow-sm transition cursor-pointer">
                        Kirim Proyeksi →
                    </button>
                </div>
            </form>
        </div>

        <!-- KONFIRMASI PENERIMAAN BARANG & PEMBAYARAN MIDTRANS -->
        <div class="space-y-4">
            <div class="flex justify-between items-center">
                <h3 class="font-bold text-xl text-gray-900">🚚 Pemesanan Rutin & Konfirmasi Barang</h3>
                <span class="font-mono text-xs font-semibold text-orange-600">Status Real-Time</span>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-gray-50 text-gray-600 font-semibold uppercase text-[11px]">
                            <tr>
                                <th class="p-4">No. Pesanan</th>
                                <th class="p-4">Pemasok Mitra</th>
                                <th class="p-4">Detail Komoditas</th>
                                <th class="p-4">Total Tagihan</th>
                                <th class="p-4">Status & Catatan Pengiriman</th>
                                <th class="p-4 text-center">Aksi Konfirmasi & Bayar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="ord in orders" :key="ord.id">
                                <tr class="hover:bg-gray-50/50 transition">
                                    <td class="p-4 text-orange-600 font-bold font-mono" x-text="ord.id"></td>
                                    <td class="p-4 font-bold text-gray-900" x-text="ord.supplierName"></td>
                                    <td class="p-4">
                                        <span class="font-semibold text-gray-800" x-text="ord.commodity"></span>
                                        <span class="block text-gray-400 text-[11px]" x-text="ord.approvedQty + ' ' + ord.unit"></span>
                                    </td>
                                    <td class="p-4 font-bold text-gray-900" x-text="'Rp ' + ord.total.toLocaleString('id-ID')"></td>
                                    <td class="p-4">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold block w-fit mb-1"
                                              :class="{
                                                  'bg-amber-100 text-amber-800': ord.status === 'PENYESUAIAN_STOK',
                                                  'bg-emerald-100 text-emerald-800': ord.status === 'SELESAI' || ord.status === 'BARANG_DITERIMA',
                                                  'bg-[#ea580c] text-white': ord.status === 'MENUNGGU_KONFIRMASI_BARANG',
                                                  'bg-sky-100 text-sky-800': ord.status === 'DIKIRIM'
                                              }"
                                              x-text="ord.status">
                                        </span>
                                        <span class="text-[11px] text-gray-500 block" x-text="ord.stockNote"></span>
                                    </td>
                                    <td class="p-4 text-center space-y-1.5 min-w-[180px]">
                                        <template x-if="ord.status === 'MENUNGGU_KONFIRMASI_BARANG'">
                                            <button type="button" @click="confirmGoodsReceived(ord.id)" class="w-full bg-emerald-100 hover:bg-emerald-200 text-emerald-800 py-1.5 rounded-full text-[11px] font-bold transition cursor-pointer">
                                                ✓ Validasi Barang Sesuai
                                            </button>
                                        </template>

                                        <template x-if="ord.paymentStatus === 'UNPAID'">
                                            <button type="button" @click="openMidtransPayment(ord)" class="w-full bg-[#ea580c] hover:bg-orange-700 text-white py-1.5 rounded-full text-[11px] font-bold shadow-sm transition cursor-pointer">
                                                💳 Bayar Midtrans →
                                            </button>
                                        </template>

                                        <template x-if="ord.paymentStatus === 'PAID'">
                                            <span class="text-emerald-700 font-bold text-[11px] block py-1">
                                                ✓ Lunas (Paid)
                                            </span>
                                        </template>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- ========================================================= -->
    <!-- TAB 3: DASHBOARD ANALISIS UMKM                           -->
    <!-- ========================================================= -->
    <div x-show="activeTab === 'dashboard'" class="space-y-8">

        <!-- METRICS CARDS UMKM -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 space-y-1">
                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">TOTAL PENGELUARAN BULAN INI</span>
                <p class="text-2xl font-bold text-orange-600">Rp 12.220.000</p>
                <p class="text-[11px] text-emerald-600 font-medium">✓ Hemat 12% via Harga Grosir</p>
            </div>

            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 space-y-1">
                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">PESANAN AKTIF</span>
                <p class="text-2xl font-bold text-gray-900">2 Transaksi</p>
                <p class="text-[11px] text-amber-600 font-medium">● 1 Perlu Konfirmasi Penerimaan</p>
            </div>

            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 space-y-1">
                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">TOTAL DITERIMA</span>
                <p class="text-2xl font-bold text-gray-900">380 kg</p>
                <p class="text-[11px] text-gray-500 font-medium">Daging Ayam & Bumbu</p>
            </div>

            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 space-y-1">
                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">ON-TIME DELIVERY</span>
                <p class="text-2xl font-bold text-emerald-700">100%</p>
                <p class="text-[11px] text-emerald-600 font-medium">✓ Tiba Sebelum Jam 07.00 WIB</p>
            </div>
        </div>

        <!-- VISUAL DIAGRAM TREN PENGADAAN (BAR CHART) -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 space-y-6">
            <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                <div>
                    <h3 class="font-bold text-lg text-gray-900">Grafik Tren Pengadaan Rutin (Kg/Bulan)</h3>
                    <p class="text-xs text-gray-500">Realisasi pasok dari Kelompok Tani Poultry Malang</p>
                </div>
                <span class="text-xs font-mono font-semibold text-orange-600">B2B Analytics</span>
            </div>

            <div class="h-48 flex items-end justify-between gap-4 pt-8 px-4 border-b border-gray-100 pb-2">
                @foreach([
                    ['month' => 'Mei', 'value' => 200],
                    ['month' => 'Jun', 'value' => 280],
                    ['month' => 'Jul', 'value' => 350],
                    ['month' => 'Agu', 'value' => 320],
                    ['month' => 'Sep', 'value' => 410],
                    ['month' => 'Okt (Aktif)', 'value' => 380]
                ] as $bar)
                    <div class="flex-1 flex flex-col items-center gap-2 h-full justify-end">
                        <div class="w-full flex items-end justify-center h-full">
                            <div style="height: {{ ($bar['value'] / 450) * 100 }}%" class="w-2/3 bg-[#ea580c] rounded-t-lg relative group transition-all hover:bg-[#c2410c]">
                                <span class="absolute -top-7 left-1/2 -translate-x-1/2 bg-black text-white text-[10px] font-mono px-2 py-0.5 rounded opacity-0 group-hover:opacity-100 transition whitespace-nowrap">
                                    {{ $bar['value'] }}kg
                                </span>
                            </div>
                        </div>
                        <span class="text-xs font-medium text-gray-600">{{ $bar['month'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- HISTORI TRANSAKSI & RATING -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden space-y-4 p-6">
            <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                <h3 class="font-bold text-lg text-gray-900">Riwayat & Status Transaksi Dapur</h3>
                <span class="text-xs font-semibold text-orange-600">Pembayaran & Rating</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-[11px] font-semibold">
                        <tr>
                            <th class="p-3">ID Transaksi</th>
                            <th class="p-3">Pemasok</th>
                            <th class="p-3">Komoditas</th>
                            <th class="p-3">Total Tagihan</th>
                            <th class="p-3">Status Bayar</th>
                            <th class="p-3">Ulasan Rating</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="ord in orders" :key="'dash-' + ord.id">
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="p-3 text-orange-600 font-bold font-mono" x-text="ord.id"></td>
                                <td class="p-3 font-semibold text-gray-900" x-text="ord.supplierName"></td>
                                <td class="p-3 text-gray-700" x-text="ord.commodity + ' (' + ord.approvedQty + ' ' + ord.unit + ')'"></td>
                                <td class="p-3 font-bold text-gray-900" x-text="'Rp ' + ord.total.toLocaleString('id-ID')"></td>
                                <td class="p-3">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold"
                                          :class="ord.paymentStatus === 'PAID' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                                          x-text="ord.paymentStatus">
                                    </span>
                                </td>
                                <td class="p-3">
                                    <template x-if="ord.rating">
                                        <span class="flex items-center gap-1 font-bold text-amber-500">
                                            ★ <span x-text="ord.rating + '.0 / 5'"></span>
                                        </span>
                                    </template>
                                    <template x-if="!ord.rating">
                                        <span class="text-[11px] text-gray-400">Belum Ada Rating</span>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- ========================================================= -->
    <!-- TAB 4: CONTRACT PERFORMANCE MONITORING                   -->
    <!-- ========================================================= -->
    <div x-show="activeTab === 'monitoring'" class="space-y-6">

        <div class="border-b border-gray-200 pb-3">
            <span class="text-xs font-bold text-orange-600 uppercase font-mono">METRIK REALISASI KEMITRAAN</span>
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">Contract Performance Monitoring</h2>
        </div>

        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 space-y-4">
            <span class="text-xs font-semibold text-gray-500 uppercase">Kesesuaian Kontrak Dengan Realisasi Pasokan</span>
            <div class="grid sm:grid-cols-2 gap-4">
                <div class="bg-[#f8f9fa] p-6 rounded-2xl border border-gray-100 space-y-1">
                    <p class="text-xs text-gray-500 font-medium">Tingkat Kesesuaian Volume Pasokan:</p>
                    <p class="text-4xl font-bold text-emerald-700">98.5%</p>
                    <p class="text-xs text-gray-400">Realisasi pasok daging & bumbu dapur mendekati 100% dari kontrak.</p>
                </div>
                <div class="bg-[#f8f9fa] p-6 rounded-2xl border border-gray-100 space-y-1">
                    <p class="text-xs text-gray-500 font-medium">Kesesuaian Jam Pengiriman Subuh:</p>
                    <p class="text-4xl font-bold text-emerald-700">100% On-Time</p>
                    <p class="text-xs text-gray-400">Seluruh armada pengiriman tiba di dapur sebelum jam 07.00 WIB.</p>
                </div>
            </div>
        </div>

    </div>

    <!-- ========================================================= -->
    <!-- MODAL FORM PENGAJUAN PERMINTAAN KATALOG                   -->
    <!-- ========================================================= -->
    <div x-cloak x-show="selectedCommodity" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div @click.away="selectedCommodity = null" class="bg-white max-w-lg w-full rounded-[2rem] p-6 sm:p-8 space-y-6 shadow-2xl relative border border-gray-100">
            
            <div class="flex justify-between items-start border-b border-gray-100 pb-4">
                <div>
                    <span class="text-xs font-bold text-orange-600 uppercase font-mono">FORM PENGAJUAN PERMINTAAN</span>
                    <h3 class="text-2xl font-bold text-gray-900" x-text="selectedCommodity?.commodity_name"></h3>
                    <p class="text-xs text-gray-600">Pemasok: <span class="font-bold text-gray-900" x-text="selectedCommodity?.company_name"></span></p>
                </div>
                <button type="button" @click="selectedCommodity = null" class="text-gray-400 hover:text-gray-700 font-bold text-lg cursor-pointer">✕</button>
            </div>

            <!-- Quick Details -->
            <div class="bg-[#f8f9fa] p-3.5 rounded-2xl border border-gray-100 flex justify-between items-center text-xs">
                <div>
                    <span class="text-gray-400 block text-[10px]">Harga Satuan:</span>
                    <span class="text-orange-600 font-bold text-sm" x-text="selectedCommodity?.price"></span>
                </div>
                <div>
                    <span class="text-gray-400 block text-[10px]">Kapasitas:</span>
                    <span class="text-gray-800 font-semibold" x-text="selectedCommodity?.stock_capacity ?? '-'"></span>
                </div>
                <template x-if="selectedCommodity?.is_halal">
                    <span class="bg-emerald-100 text-emerald-800 px-2.5 py-1 rounded-full text-[10px] font-bold">✓ Halal Resmi</span>
                </template>
            </div>

            <form method="POST" action="{{ route('umkm.katalog.ajukan') }}" class="space-y-4 text-xs font-semibold">
                @csrf
                <input type="hidden" name="supplier_product_id" :value="selectedCommodity?.id">
                <input type="hidden" name="supplier_id" :value="selectedCommodity?.supplier_id">
                <input type="hidden" name="unit" :value="selectedCommodity?.unit">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-gray-700 mb-1">Volume Kebutuhan</label>
                        <div class="flex items-center gap-2">
                            <input 
                                type="number" 
                                name="volume" 
                                required 
                                x-model="inputVolume"
                                min="1"
                                class="w-full px-4 py-2.5 rounded-xl bg-[#f1f2f4] text-gray-900 font-medium focus:outline-none focus:ring-2 focus:ring-[#ea580c] border-0"
                            />
                            <span class="font-bold text-gray-700" x-text="selectedCommodity?.unit"></span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-1">Frekuensi Pengiriman</label>
                        <select name="frekuensi" required class="w-full px-3 py-2.5 rounded-xl bg-[#f1f2f4] text-gray-900 font-medium focus:outline-none focus:ring-2 focus:ring-[#ea580c] border-0">
                            <option value="2x Seminggu (Senin & Kamis)">2x Seminggu (Senin & Kamis)</option>
                            <option value="3x Seminggu (Senin, Rabu, Jumat)">3x Seminggu (Senin, Rabu, Jumat)</option>
                            <option value="Setiap Hari">Setiap Hari</option>
                            <option value="1x Seminggu">1x Seminggu</option>
                        </select>
                    </div>
                </div>

                <!-- Live Subtotal Calculation -->
                <div class="bg-gray-50 p-3.5 rounded-2xl border border-gray-100 flex justify-between items-center">
                    <div>
                        <span class="text-[10px] text-gray-500 font-mono uppercase block">Estimasi Total Nilai Pasok:</span>
                        <span class="text-[10px] text-gray-400">(Volume × Harga Grosir)</span>
                    </div>
                    <span class="font-bold text-lg text-orange-600" x-text="calculateTotal()"></span>
                </div>

                <div>
                    <label class="block text-gray-700 mb-1">Catatan Spesifikasi Mutu Dapur</label>
                    <textarea 
                        name="submission_details" 
                        rows="3" 
                        placeholder="Contoh: Pemotongan ayam dibagi 8 bagian, toleransi pengiriman jam 06.00 WIB."
                        class="w-full px-4 py-2.5 rounded-xl bg-[#f1f2f4] text-gray-900 font-medium placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c] border-0"
                    ></textarea>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" @click="selectedCommodity = null" class="w-1/2 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-full font-bold transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="w-1/2 bg-[#ea580c] hover:bg-[#c2410c] text-white py-3 rounded-full font-bold shadow-sm transition cursor-pointer">
                        Kirim Permintaan →
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- MODAL MIDTRANS PAYMENT SIMULATOR                          -->
    <!-- ========================================================= -->
    <div x-cloak x-show="payingOrder" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div @click.away="payingOrder = null" class="bg-white max-w-md w-full rounded-[2rem] p-6 space-y-6 shadow-2xl border border-gray-100">
            <div class="flex justify-between items-start border-b border-gray-100 pb-3">
                <div>
                    <span class="text-xs font-bold text-orange-600 uppercase font-mono">MIDTRANS PAYMENT GATEWAY</span>
                    <h3 class="text-xl font-bold text-gray-900">Pembayaran Pasokan</h3>
                </div>
                <button type="button" @click="payingOrder = null" class="text-gray-400 hover:text-gray-700 font-bold text-lg cursor-pointer">✕</button>
            </div>

            <div class="bg-[#f8f9fa] p-4 rounded-2xl border border-gray-100 space-y-2 text-xs font-mono">
                <div class="flex justify-between text-gray-600"><span>No. Order:</span><span class="font-bold text-gray-900" x-text="payingOrder?.id"></span></div>
                <div class="flex justify-between text-gray-600"><span>Pemasok:</span><span class="font-bold text-gray-900" x-text="payingOrder?.supplierName"></span></div>
                <div class="flex justify-between text-gray-600"><span>Komoditas:</span><span class="font-bold text-gray-900" x-text="payingOrder?.commodity"></span></div>
                <div class="flex justify-between text-base text-orange-600 font-bold pt-2 border-t border-gray-200">
                    <span>Total Bayar:</span>
                    <span x-text="'Rp ' + (payingOrder?.total ? payingOrder.total.toLocaleString('id-ID') : 0)"></span>
                </div>
            </div>

            <div class="space-y-2 text-xs font-semibold">
                <span class="block text-gray-600 text-[11px]">Pilih Metode Pembayaran VA Midtrans:</span>
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" class="p-2.5 bg-[#f1f2f4] hover:bg-gray-200 rounded-xl text-center font-bold text-gray-800 cursor-pointer">BCA VA</button>
                    <button type="button" class="p-2.5 bg-[#f1f2f4] hover:bg-gray-200 rounded-xl text-center font-bold text-gray-800 cursor-pointer">Mandiri</button>
                    <button type="button" class="p-2.5 bg-[#f1f2f4] hover:bg-gray-200 rounded-xl text-center font-bold text-gray-800 cursor-pointer">QRIS</button>
                </div>
            </div>

            <button type="button" @click="simulateMidtransSuccess()" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white py-3.5 rounded-full font-bold text-xs shadow-sm transition cursor-pointer">
                Simulasi Pembayaran Berhasil (Sukses) ✓
            </button>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- MODAL RATING & ULASAN PEMASOK                             -->
    <!-- ========================================================= -->
    <div x-cloak x-show="ratingOrder" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div @click.away="ratingOrder = null" class="bg-white max-w-md w-full rounded-[2rem] p-6 space-y-6 shadow-2xl border border-gray-100">
            <div class="flex justify-between items-start border-b border-gray-100 pb-3">
                <div>
                    <span class="text-xs font-bold text-amber-500 uppercase font-mono">BERIKAN RATING PEMASOK</span>
                    <h3 class="text-xl font-bold text-gray-900" x-text="ratingOrder?.supplierName"></h3>
                </div>
                <button type="button" @click="ratingOrder = null" class="text-gray-400 hover:text-gray-700 font-bold text-lg cursor-pointer">✕</button>
            </div>

            <form @submit.prevent="saveRating()" class="space-y-4 text-xs font-semibold">
                <div>
                    <label class="block mb-2 text-gray-700">Pilih Skor Bintang Rating (1-5):</label>
                    <div class="flex gap-2">
                        <template x-for="star in [1, 2, 3, 4, 5]" :key="star">
                            <button 
                                type="button" 
                                @click="ratingScore = star" 
                                class="p-3 bg-[#f1f2f4] hover:bg-gray-200 rounded-xl text-lg transition cursor-pointer"
                                :class="ratingScore >= star ? 'text-amber-400' : 'text-gray-300'">
                                ★
                            </button>
                        </template>
                    </div>
                </div>

                <div>
                    <label class="block mb-1 text-gray-700">Tulis Ulasan Kepuasan Layanan & Mutu:</label>
                    <textarea 
                        rows="3" 
                        x-model="ratingComment" 
                        placeholder="Contoh: Pengiriman subuh sangat tepat waktu, daging ayam segar dan higienis!" 
                        class="w-full p-3.5 bg-[#f1f2f4] rounded-xl text-gray-900 placeholder-gray-400 font-medium focus:outline-none focus:ring-2 focus:ring-[#ea580c] border-0"
                    ></textarea>
                </div>

                <button type="submit" class="w-full bg-[#ea580c] hover:bg-[#c2410c] text-white py-3.5 rounded-full font-bold text-xs shadow-sm transition cursor-pointer">
                    Kirim Rating & Selesaikan Transaksi ★
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
