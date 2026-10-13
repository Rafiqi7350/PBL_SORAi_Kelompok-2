@extends('layouts.umkm')

@section('content')
<script>
    function umkmCatalogPage() {
        return {
            selectedCommodity: null,
            orderSubTab: 'auto-order', // 'auto-order' | 'confirm' | 'payment' | 'rating'
            payingOrder: null,
            selectedPaymentMethod: 'bca',
            previewAutoOrder: null,
            ratingInputOrder: null,
            stars: 5,
            reviewText: '',
            notificationMsg: @json(session('success') ?? ''),

            planningForm: {
                commodity: 'Daging Ayam Broiler Segar',
                volume: 200,
                targetDate: '2026-11',
                supplier: 'Kelompok Tani Poultry Malang',
                notes: 'Standar potongan karkas dingin, pengiriman pagi pukul 05:00 WIB.'
            },

            supplyPlans: @json($supplyPlans ?? []),

            formVolume: 100,
            formFrequency: '2x Seminggu (Senin & Kamis)',
            formDays: ['Senin', 'Kamis'],
            formTime: '05:30',
            formStartDate: '{{ now()->addDay()->toDateString() }}',
            formAutoOrder: true,
            availableDays: ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'],

            openAjukanModal: function(commodity) {
                this.selectedCommodity = commodity;
                this.formVolume = 100;
                this.formFrequency = '2x Seminggu (Senin & Kamis)';
                this.formDays = ['Senin', 'Kamis'];
                this.formTime = '05:30';
                this.formStartDate = '{{ now()->addDay()->toDateString() }}';
                this.formAutoOrder = true;
            },

            syncFrequencyFromDays: function() {
                var self = this;
                this.formDays.sort(function(a, b) {
                    return self.availableDays.indexOf(a) - self.availableDays.indexOf(b);
                });

                var daysStr = this.formDays.join(',');

                if (this.formDays.length === 7) {
                    this.formFrequency = 'Setiap Hari';
                } else if (daysStr === 'Senin,Kamis') {
                    this.formFrequency = '2x Seminggu (Senin & Kamis)';
                } else if (daysStr === 'Senin,Rabu,Jumat') {
                    this.formFrequency = '3x Seminggu (Senin, Rabu, Jumat)';
                } else if (daysStr === 'Senin') {
                    this.formFrequency = '1x Seminggu';
                } else {
                    this.formFrequency = 'Kustom';
                }
            },

            onFrequencySelect: function(freq) {
                this.formFrequency = freq;
                if (freq === '2x Seminggu (Senin & Kamis)') {
                    this.formDays = ['Senin', 'Kamis'];
                } else if (freq === '3x Seminggu (Senin, Rabu, Jumat)') {
                    this.formDays = ['Senin', 'Rabu', 'Jumat'];
                } else if (freq === 'Setiap Hari') {
                    this.formDays = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
                } else if (freq === '1x Seminggu') {
                    this.formDays = ['Senin'];
                }
            },

            toggleDay: function(day) {
                if (this.formDays.includes(day)) {
                    if (this.formDays.length > 1) {
                        this.formDays = this.formDays.filter(function(d) { return d !== day; });
                    }
                } else {
                    this.formDays.push(day);
                }
                this.syncFrequencyFromDays();
            },

            calculateDeliverySubtotal: function() {
                if (!this.selectedCommodity || !this.formVolume) return 'Rp 0';
                var total = (parseFloat(this.selectedCommodity.numeric_price) || 0) * (parseFloat(this.formVolume) || 0);
                return 'Rp ' + Math.round(total).toLocaleString('id-ID');
            },

            calculateMonthlyVolume: function() {
                var vol = parseFloat(this.formVolume) || 0;
                var daysCount = (this.formDays && this.formDays.length) ? this.formDays.length : 1;
                return Math.round(vol * daysCount * 4);
            },

            calculateMonthlyTotal: function() {
                if (!this.selectedCommodity) return 'Rp 0';
                var totalVol = this.calculateMonthlyVolume();
                var price = parseFloat(this.selectedCommodity.numeric_price) || 0;
                return 'Rp ' + Math.round(totalVol * price).toLocaleString('id-ID');
            },

            inputVolume: 100,
            calculateTotal: function() {
                return this.calculateDeliverySubtotal();
            },

            orders: [
                { id: 'ORD-2026-001', umkmName: 'Dapur Sambal Malang', supplierName: 'Kelompok Tani Poultry Malang', commodity: 'Daging Ayam Broiler Segar', requestedQty: 100, approvedQty: 100, unit: 'kg', pricePerUnit: 34000, total: 3400000, date: '01/10/2026', status: 'MENUNGGU_KONFIRMASI_BARANG', stockNote: 'Barang Tiba Di Dapur Subuh Tadi', paymentStatus: 'UNPAID', rating: null, review: '' },
                { id: 'ORD-2026-002', umkmName: 'Dapur Sambal Malang', supplierName: 'Kelompok Tani Poultry Malang', commodity: 'Daging Ayam Broiler Segar', requestedQty: 200, approvedQty: 120, unit: 'kg', pricePerUnit: 34000, total: 4080000, date: '28/09/2026', status: 'PENYESUAIAN_STOK', stockNote: 'Stok Terbatas (Dikirim 120kg dari 200kg)', paymentStatus: 'UNPAID', rating: null, review: '' },
                { id: 'ORD-2026-003', umkmName: 'Dapur Sambal Malang', supplierName: 'Distributor Rempah Nusantara', commodity: 'Bawang Merah Biru Probolinggo', requestedQty: 50, approvedQty: 50, unit: 'kg', pricePerUnit: 28000, total: 1400000, date: '22/09/2026', status: 'BARANG_DITERIMA', stockNote: 'Pengiriman Subuh Dalam Perjalanan', paymentStatus: 'UNPAID', rating: null, review: '' },
                { id: 'ORD-2026-004', umkmName: 'Dapur Sambal Malang', supplierName: 'Kelompok Tani Poultry Malang', commodity: 'Daging Ayam Broiler Segar', requestedQty: 150, approvedQty: 150, unit: 'kg', pricePerUnit: 34000, total: 5100000, date: '15/09/2026', status: 'SELESAI', stockNote: 'Selesai & Diterima Dapur', paymentStatus: 'PAID', rating: 5, review: 'Kualitas daging sangat segar dan datang tepat waktu.' }
            ],

            autoOrdersList: @json($autoOrdersList ?? []),

            isPublishedToday: function(autoOrd) {
                if (!autoOrd) return false;
                if (autoOrd.isPublishedToday) return true;
                var todayStr = new Date().toLocaleDateString('id-ID');
                return this.orders.some(function(o) {
                    return (o.stockNote && o.stockNote.includes(autoOrd.contractId)) && (o.date === todayStr || o.publishedToday);
                });
            },

            getNextDeliveryScheduleText: function(autoOrd) {
                if (!autoOrd) return 'Jadwal Kontrak Berikutnya';
                if (autoOrd.nextScheduledPublish) return autoOrd.nextScheduledPublish;

                var days = autoOrd.deliveryDays && autoOrd.deliveryDays.length ? autoOrd.deliveryDays : ['Senin', 'Kamis'];
                var daysOrder = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                var now = new Date();

                for (var offset = 1; offset <= 7; offset++) {
                    var targetDate = new Date(now);
                    targetDate.setDate(now.getDate() + offset);
                    var dayName = daysOrder[targetDate.getDay()];
                    if (days.includes(dayName)) {
                        var dateFormatted = targetDate.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
                        var timeStr = autoOrd.deliveryTime ? autoOrd.deliveryTime : '05:30 WIB';
                        return dayName + ', ' + dateFormatted + ' (' + timeStr + ')';
                    }
                }
                return autoOrd.nextDelivery || 'Sesuai Jadwal Kontrak';
            },

            handlePublishAutoOrder: function(autoOrd) {
                if (!autoOrd || this.isPublishedToday(autoOrd)) return;

                autoOrd.isPublishedToday = true;
                autoOrd.publishedDate = new Date().toLocaleDateString('id-ID');

                var nextText = this.getNextDeliveryScheduleText(autoOrd);
                autoOrd.nextScheduledPublish = nextText;

                var newId = 'ORD-2026-00' + (this.orders.length + 1);
                var newOrd = {
                    id: newId,
                    umkmName: 'Dapur Sambal Malang',
                    supplierName: autoOrd.supplierName,
                    commodity: autoOrd.commodity,
                    requestedQty: autoOrd.qty,
                    approvedQty: autoOrd.qty,
                    unit: autoOrd.unit,
                    pricePerUnit: autoOrd.pricePerUnit,
                    total: autoOrd.total,
                    date: new Date().toLocaleDateString('id-ID'),
                    status: 'MENUNGGU_KONFIRMASI_BARANG',
                    stockNote: 'Diterbitkan Otomatis via Kontrak ' + autoOrd.contractId,
                    paymentStatus: 'UNPAID',
                    publishedToday: true,
                    rating: null,
                    review: ''
                };
                this.orders.unshift(newOrd);
                this.previewAutoOrder = null;
                this.notify('✓ Pesanan berhasil diterbitkan untuk hari ini! Jadwal penerbitan selanjutnya: ' + nextText);
                this.orderSubTab = 'confirm';
            },

            confirmGoodsReceived: function(orderId) {
                this.orders = this.orders.map(function(o) {
                    return o.id === orderId ? Object.assign({}, o, { status: 'BARANG_DITERIMA', stockNote: 'Kesesuaian Barang Telah Dikonfirmasi UMKM' }) : o;
                });
                this.notify('✓ Barang berhasil dikonfirmasi sesuai! Silakan lanjutkan ke Pembayaran.');
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
                this.ratingInputOrder = finishedOrder;
                this.orderSubTab = 'rating';
                this.notify('✓ Pembayaran Midtrans berhasil lunas! Silakan berikan ulasan & rating.');
            },

            saveRating: function() {
                if (!this.ratingInputOrder) return;
                var targetId = this.ratingInputOrder.id;
                var currentStars = this.stars;
                var currentReview = this.reviewText;
                this.orders = this.orders.map(function(o) {
                    return o.id === targetId ? Object.assign({}, o, { rating: currentStars, review: currentReview }) : o;
                });
                this.notify('✓ Ulasan & Rating ' + currentStars + ' Bintang berhasil tersimpan untuk pesanan ' + targetId + '!');
                this.ratingInputOrder = null;
                this.reviewText = '';
                this.stars = 5;
            },

            openRatingForOrder: function(ord) {
                this.ratingInputOrder = ord;
                this.stars = ord.rating || 5;
                this.reviewText = ord.review || '';
                this.orderSubTab = 'rating';
            },

            sendSupplyPlan: function() {
                var newPlan = {
                    id: 'CSP-2026-00' + (this.supplyPlans.length + 1),
                    commodity: this.planningForm.commodity,
                    volume: parseFloat(this.planningForm.volume) || 100,
                    unit: 'kg',
                    targetMonth: this.planningForm.targetDate,
                    supplier: this.planningForm.supplier || 'Kelompok Tani Poultry Malang',
                    status: 'DALAM_REVIEW',
                    note: this.planningForm.notes || 'Estimasi kebutuhan bahan baku dapur.'
                };
                this.supplyPlans.unshift(newPlan);
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

<div x-data="umkmCatalogPage()" class="pb-12">

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
                                        @click="openAjukanModal({{ json_encode([
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
                                         ]) }})" 
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
    <!-- TAB: COLLABORATIVE SUPPLY PLANNING (PROYEKSI PASOKAN)      -->
    <!-- ========================================================= -->
    <div x-cloak x-show="activeTab === 'supply-planning'" class="space-y-6">

        <!-- HEADER TITLE -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-gray-200">
            <div>
                <span class="text-xs font-mono font-bold text-[#ea580c] uppercase tracking-wider">KOLABORASI RANTAI PASOK MITRA</span>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">Collaborative Supply Planning</h2>
                <p class="text-xs text-gray-500 mt-0.5">Sampaikan estimasi proyeksi kebutuhan dapur UMKM bulan depan agar pemasok mitra dapat merencanakan kapasitas panen dan ternak secara presisi tanpa risiko kelangkaan stok.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="bg-orange-50 border border-orange-200 text-[#ea580c] px-3.5 py-1.5 rounded-full text-xs font-bold font-mono">
                    Proyeksi Q4 2026
                </span>
            </div>
        </div>

        <!-- 3 VALUE HIGHLIGHT CARDS -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm space-y-2">
                <div class="w-9 h-9 rounded-full bg-orange-100 text-[#ea580c] flex items-center justify-center font-bold text-lg">
                    🌾
                </div>
                <h4 class="font-bold text-sm text-gray-900">Sinkronisasi Panen & Ternak</h4>
                <p class="text-xs text-gray-500 leading-relaxed">Pemasok mitra menyiapkan kapasitas kandang dan petik ladang sesuai proyeksi bulanan dapur Anda.</p>
            </div>

            <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm space-y-2">
                <div class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-lg">
                    🔒
                </div>
                <h4 class="font-bold text-sm text-gray-900">Kunci Stabilitas Harga Grosir</h4>
                <p class="text-xs text-gray-500 leading-relaxed">Melindungi margin operasional dapur dari volatilitas dan lonjakan harga pasar eceran mendadak.</p>
            </div>

            <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm space-y-2">
                <div class="w-9 h-9 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-lg">
                    🚚
                </div>
                <h4 class="font-bold text-sm text-gray-900">Prioritas Suplai Subuh Hari</h4>
                <p class="text-xs text-gray-500 leading-relaxed">Pesanan Anda masuk ke antrean pemotongan dan distribusi pertama sebelum jam 07.00 WIB pagi.</p>
            </div>
        </div>

        <!-- FORM COLLABORATIVE SUPPLY PLANNING -->
        <div class="bg-white p-6 sm:p-7 rounded-3xl shadow-sm border border-gray-100 space-y-5">
            <div class="flex items-center gap-3 border-b border-gray-100 pb-4">
                <div class="w-10 h-10 rounded-2xl bg-[#ea580c] text-white flex items-center justify-center shadow-md">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-lg text-gray-900">Form Pengajuan Proyeksi Pasokan Dapur</h3>
                    <p class="text-xs text-gray-500">Estimasi ini akan dikirimkan langsung ke dashboard pemasok mitra untuk dicocokkan dengan siklus panen.</p>
                </div>
            </div>

            <form @submit.prevent="sendSupplyPlan()" class="space-y-4 text-xs font-semibold">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block mb-1.5 text-gray-700">Pilih Komoditas Pasokan:</label>
                        <select x-model="planningForm.commodity" class="w-full p-3 rounded-2xl bg-[#f1f2f4] text-gray-800 font-medium border-0 focus:outline-none focus:ring-2 focus:ring-[#ea580c]">
                            <option>Daging Ayam Broiler Segar</option>
                            <option>Bawang Merah Biru Probolinggo</option>
                            <option>Telur Ayam Ras Grade A</option>
                            <option>Cabai Rawit Merah Super</option>
                            <option>Daging Sapi Lokal Segar</option>
                        </select>
                    </div>

                    <div>
                        <label class="block mb-1.5 text-gray-700">Mitra Pemasok Terpilih:</label>
                        <select x-model="planningForm.supplier" class="w-full p-3 rounded-2xl bg-[#f1f2f4] text-gray-800 font-medium border-0 focus:outline-none focus:ring-2 focus:ring-[#ea580c]">
                            <option>Kelompok Tani Poultry Malang</option>
                            <option>Distributor Rempah Nusantara</option>
                            <option>Peternakan Ayam Bertelur Blitar</option>
                            <option>Kelompok Tani Sayur Batu</option>
                        </select>
                    </div>

                    <div>
                        <label class="block mb-1.5 text-gray-700">Estimasi Volume (kg / satuan):</label>
                        <input type="number" x-model="planningForm.volume" required min="1" placeholder="Contoh: 200" class="w-full p-3 rounded-2xl bg-[#f1f2f4] text-gray-800 font-medium border-0 focus:outline-none focus:ring-2 focus:ring-[#ea580c]" />
                    </div>

                    <div>
                        <label class="block mb-1.5 text-gray-700">Proyeksi Bulan Target:</label>
                        <input type="month" x-model="planningForm.targetDate" required class="w-full p-3 rounded-2xl bg-[#f1f2f4] text-gray-800 font-medium border-0 focus:outline-none focus:ring-2 focus:ring-[#ea580c]" />
                    </div>
                </div>

                <div>
                    <label class="block mb-1.5 text-gray-700">Catatan Khusus Kebutuhan Dapur (Opsional):</label>
                    <input type="text" x-model="planningForm.notes" placeholder="Contoh: Potongan dada fillet suhu dingin, frekuensi pengiriman 2x seminggu subuh..." class="w-full p-3 rounded-2xl bg-[#f1f2f4] text-gray-800 font-medium border-0 focus:outline-none focus:ring-2 focus:ring-[#ea580c]" />
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" class="bg-[#ea580c] hover:bg-[#c2410c] text-white px-8 py-3 rounded-full font-bold shadow-sm transition inline-flex items-center gap-2 cursor-pointer">
                        <span>Kirim Estimasi Proyeksi ke Pemasok</span>
                        <span>→</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- RIWAYAT & STATUS KESEPAKATAN SUPPLY PLANNING -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 space-y-4">
            <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                <div>
                    <h3 class="font-bold text-lg text-gray-900">📋 Riwayat Proyeksi & Respon Kapasitas Mitra</h3>
                    <p class="text-xs text-gray-500">Pantau status konfirmasi kesiapan panen dari masing-masing pemasok mitra.</p>
                </div>
                <span class="text-xs font-mono font-bold text-[#ea580c]" x-text="supplyPlans.length + ' Proyeksi Tercatat'"></span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-gray-50 font-mono text-gray-500 uppercase border-b border-gray-100">
                        <tr>
                            <th class="p-3.5">ID Proyeksi</th>
                            <th class="p-3.5">Pemasok Mitra</th>
                            <th class="p-3.5">Komoditas & Volume</th>
                            <th class="p-3.5">Bulan Target</th>
                            <th class="p-3.5">Status Kolaborasi</th>
                            <th class="p-3.5">Catatan Respon Pemasok</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-medium">
                        <template x-for="plan in supplyPlans" :key="plan.id">
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="p-3.5 font-mono font-bold text-[#ea580c]" x-text="plan.id"></td>
                                <td class="p-3.5 font-bold text-gray-900" x-text="plan.supplier"></td>
                                <td class="p-3.5">
                                    <span class="font-semibold text-gray-900" x-text="plan.commodity"></span>
                                    <span class="text-gray-500 font-bold block text-[11px]" x-text="plan.volume + ' ' + plan.unit"></span>
                                </td>
                                <td class="p-3.5 font-bold text-gray-800" x-text="plan.targetMonth"></td>
                                <td class="p-3.5">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold inline-block"
                                          :class="plan.status === 'DISETUJUI' ? 'bg-emerald-100 text-emerald-800' : (plan.status === 'DITOLAK' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800')"
                                          x-text="plan.status === 'DISETUJUI' ? '✓ Disetujui Mitra' : (plan.status === 'DITOLAK' ? '✕ Pengajuan Ditolak' : '● Menunggu Konfirmasi')">
                                    </span>
                                </td>
                                <td class="p-3.5 text-gray-600 italic text-[11px]" x-text="plan.note"></td>
                            </tr>
                        </template>

                        <template x-if="supplyPlans.length === 0">
                            <tr>
                                <td colspan="6" class="p-8 text-center text-gray-500">
                                    <div class="py-6 space-y-2">
                                        <span class="text-3xl block">📋</span>
                                        <p class="font-bold text-gray-800 text-sm">Belum Ada Riwayat Pengajuan Kemitraan</p>
                                        <p class="text-xs text-gray-500 max-w-sm mx-auto">Silakan ajukan permintaan kemitraan pasokan bahan baku melalui tab Katalog untuk memulai kolaborasi rantai pasok.</p>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- ========================================================= -->
    <!-- TAB: PESANAN & PENGIRIMAN (4 SUB-TABS DENGAN TEMA ORANGE) -->
    <!-- ========================================================= -->
    <div x-cloak x-show="activeTab === 'pemesanan'" class="space-y-4">

        <!-- HEADER TITLE -->
        <div class="border-b border-gray-200 pb-3">
            <span class="text-xs font-mono font-bold text-[#ea580c] uppercase tracking-wider">DAFTAR TRANSAKSI AKTIF</span>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">Pesanan & Pengiriman</h2>
            <p class="text-xs text-gray-500 mt-0.5">Kelola jadwal pemesanan otomatis dari kontrak terikat, konfirmasi fisik kedatangan bahan baku di dapur, pembayaran online Midtrans, hingga evaluasi mutu pemasok.</p>
        </div>

        <!-- SUB-NAVIGASI TAB CLEAN UNDERLINE DESIGN (ORANGE ACCENTS) -->
        <div class="border-b border-gray-200 flex flex-wrap items-center gap-6 sm:gap-8 text-xs font-bold">
            <button 
                type="button"
                @click="orderSubTab = 'auto-order'"
                class="pb-3 relative transition flex items-center gap-2 cursor-pointer"
                :class="orderSubTab === 'auto-order' ? 'text-gray-900 font-extrabold' : 'text-gray-500 hover:text-gray-900'">
                <span>Pemesanan Otomatis</span>
                <template x-if="orderSubTab === 'auto-order'">
                    <span class="absolute bottom-0 left-0 w-full h-1 bg-[#ea580c] rounded-t-full"></span>
                </template>
            </button>

            <button 
                type="button"
                @click="orderSubTab = 'confirm'"
                class="pb-3 relative transition flex items-center gap-2 cursor-pointer"
                :class="orderSubTab === 'confirm' ? 'text-gray-900 font-extrabold' : 'text-gray-500 hover:text-gray-900'">
                <span>Konfirmasi Penerimaan</span>
                <template x-if="orders.filter(o => o.status === 'MENUNGGU_KONFIRMASI_BARANG').length > 0">
                    <span class="bg-[#ea580c] text-white text-[10px] px-2 py-0.5 rounded-full font-mono font-bold"
                          x-text="orders.filter(o => o.status === 'MENUNGGU_KONFIRMASI_BARANG').length">
                    </span>
                </template>
                <template x-if="orderSubTab === 'confirm'">
                    <span class="absolute bottom-0 left-0 w-full h-1 bg-[#ea580c] rounded-t-full"></span>
                </template>
            </button>

            <button 
                type="button"
                @click="orderSubTab = 'payment'"
                class="pb-3 relative transition flex items-center gap-2 cursor-pointer"
                :class="orderSubTab === 'payment' ? 'text-gray-900 font-extrabold' : 'text-gray-500 hover:text-gray-900'">
                <span>Pembayaran Midtrans</span>
                <template x-if="orders.filter(o => o.status === 'BARANG_DITERIMA' && o.paymentStatus === 'UNPAID').length > 0">
                    <span class="bg-amber-500 text-white text-[10px] px-2 py-0.5 rounded-full font-mono font-bold"
                          x-text="orders.filter(o => o.status === 'BARANG_DITERIMA' && o.paymentStatus === 'UNPAID').length">
                    </span>
                </template>
                <template x-if="orderSubTab === 'payment'">
                    <span class="absolute bottom-0 left-0 w-full h-1 bg-[#ea580c] rounded-t-full"></span>
                </template>
            </button>

            <button 
                type="button"
                @click="orderSubTab = 'rating'"
                class="pb-3 relative transition flex items-center gap-2 cursor-pointer"
                :class="orderSubTab === 'rating' ? 'text-gray-900 font-extrabold' : 'text-gray-500 hover:text-gray-900'">
                <span>Rating & Ulasan</span>
                <template x-if="orderSubTab === 'rating'">
                    <span class="absolute bottom-0 left-0 w-full h-1 bg-[#ea580c] rounded-t-full"></span>
                </template>
            </button>
        </div>

        <!-- SUB-TAB 1: PEMESANAN OTOMATIS -->
        <div x-show="orderSubTab === 'auto-order'" class="bg-white rounded-3xl border border-gray-100 overflow-hidden p-6 space-y-4 shadow-sm">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-3 border-b border-gray-100 pb-4">
                <div>
                    <span class="font-mono text-[10px] font-bold text-[#ea580c] uppercase">KONTRAK TERIKAT AKTIF</span>
                    <h3 class="font-extrabold text-xl text-gray-900">Daftar Jadwal Pemesanan Otomatis</h3>
                    <p class="text-xs text-gray-500">Pesanan rutin di bawah ini digenerate secara otomatis berdasarkan kontrak terikat dengan pemasok mitra.</p>
                </div>
                <span class="bg-orange-50 border border-orange-200 text-[#ea580c] font-mono font-bold text-xs px-3.5 py-1.5 rounded-full w-fit">
                    <span x-text="autoOrdersList.length"></span> Kontrak Aktif
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-gray-50 font-mono text-gray-500 uppercase border-b border-gray-100">
                        <tr>
                            <th class="p-3.5">ID Auto Order</th>
                            <th class="p-3.5">ID Kontrak</th>
                            <th class="p-3.5">Pemasok Mitra</th>
                            <th class="p-3.5">Komoditas & Volume</th>
                            <th class="p-3.5">Total Nilai Kontrak</th>
                            <th class="p-3.5">Jadwal Pengiriman</th>
                            <th class="p-3.5 text-center">Aksi Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-medium">
                        <template x-for="autoOrd in autoOrdersList" :key="autoOrd.id">
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="p-3.5 font-mono font-bold text-[#ea580c]" x-text="autoOrd.id"></td>
                                <td class="p-3.5 font-mono text-gray-600" x-text="autoOrd.contractId"></td>
                                <td class="p-3.5 font-bold text-gray-900" x-text="autoOrd.supplierName"></td>
                                <td class="p-3.5">
                                    <span x-text="autoOrd.commodity"></span>
                                    <span class="font-bold text-gray-900" x-text="' (' + autoOrd.qty + ' ' + autoOrd.unit + ')'"></span>
                                </td>
                                <td class="p-3.5 font-bold text-gray-900" x-text="'Rp ' + autoOrd.total.toLocaleString('id-ID')"></td>
                                <td class="p-3.5">
                                    <template x-if="isPublishedToday(autoOrd)">
                                        <div class="space-y-1">
                                            <span class="inline-flex items-center gap-1 bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2.5 py-0.5 rounded-full">
                                                <span>✓ Terbit Hari Ini</span>
                                            </span>
                                            <span class="block font-bold text-gray-900 text-xs">Jadwal Selanjutnya:</span>
                                            <span class="block text-emerald-700 font-extrabold text-[11px]" x-text="getNextDeliveryScheduleText(autoOrd)"></span>
                                        </div>
                                    </template>
                                    <template x-if="!isPublishedToday(autoOrd)">
                                        <div>
                                            <span class="block font-bold text-gray-900" x-text="autoOrd.nextDelivery"></span>
                                            <span class="text-[10px] text-gray-500" x-text="autoOrd.deliverySchedule"></span>
                                        </div>
                                    </template>
                                </td>
                                <td class="p-3.5 text-center">
                                    <template x-if="!isPublishedToday(autoOrd)">
                                        <button 
                                            type="button"
                                            @click="previewAutoOrder = autoOrd"
                                            class="bg-orange-50 hover:bg-[#ea580c] hover:text-white text-[#ea580c] border border-orange-200 px-3.5 py-1.5 rounded-full font-bold text-[11px] transition inline-flex items-center gap-1.5 shadow-sm cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <span>Preview & Terbitkan</span>
                                        </button>
                                    </template>
                                    <template x-if="isPublishedToday(autoOrd)">
                                        <button 
                                            type="button"
                                            @click="previewAutoOrder = autoOrd"
                                            class="bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 px-3.5 py-1.5 rounded-full font-bold text-[11px] transition inline-flex items-center gap-1.5 shadow-xs cursor-pointer">
                                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                            <span>Detail Kontrak</span>
                                        </button>
                                    </template>
                                </td>
                            </tr>
                        </template>

                        <template x-if="autoOrdersList.length === 0">
                            <tr>
                                <td colspan="7" class="p-10 text-center text-gray-500">
                                    <div class="py-6 space-y-3 max-w-md mx-auto">
                                        <span class="text-4xl block">🚚</span>
                                        <p class="font-extrabold text-gray-900 text-sm">Belum Ada Jadwal Pemesanan Otomatis Aktif</p>
                                        <p class="text-xs text-gray-500 leading-relaxed">
                                            Data kontrak pemesanan rutin akan tampil di sini secara otomatis setelah pengajuan kemitraan pasokan Anda disetujui (approve) oleh mitra pemasok.
                                        </p>
                                        <div class="pt-2">
                                            <button type="button" @click="activeTab = 'katalog'" class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#ea580c] hover:bg-[#c2410c] text-white rounded-full text-xs font-bold transition shadow-sm cursor-pointer">
                                                <span>Pilih Bahan Baku di Katalog</span>
                                                <span>→</span>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SUB-TAB 2: KONFIRMASI PENERIMAAN BARANG -->
        <div x-show="orderSubTab === 'confirm'" class="bg-white rounded-3xl border border-gray-100 overflow-hidden p-6 space-y-4 shadow-sm">
            <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                <div>
                    <h3 class="font-bold text-lg text-gray-900">Validasi & Konfirmasi Barang Tiba di Dapur</h3>
                    <p class="text-xs text-gray-500">Periksa kuantitas dan mutu bahan baku saat tiba. Klik validasi untuk menyelesaikan status penerimaan.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-gray-50 font-mono text-gray-500 uppercase border-b border-gray-100">
                        <tr>
                            <th class="p-3.5">ID Pesanan</th>
                            <th class="p-3.5">Pemasok Mitra</th>
                            <th class="p-3.5">Detail Komoditas</th>
                            <th class="p-3.5">Total Tagihan</th>
                            <th class="p-3.5">Status</th>
                            <th class="p-3.5 text-center">Aksi Konfirmasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-medium">
                        <template x-for="ord in orders" :key="'confirm-' + ord.id">
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="p-3.5 font-mono font-bold text-[#ea580c]" x-text="ord.id"></td>
                                <td class="p-3.5 font-bold text-gray-900" x-text="ord.supplierName"></td>
                                <td class="p-3.5">
                                    <span class="font-semibold text-gray-900" x-text="ord.commodity"></span>
                                    <span class="text-gray-500 block text-[11px]" x-text="'(' + ord.approvedQty + ' ' + ord.unit + ')'"></span>
                                </td>
                                <td class="p-3.5 font-bold text-gray-900" x-text="'Rp ' + ord.total.toLocaleString('id-ID')"></td>
                                <td class="p-3.5">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold"
                                          :class="ord.status === 'MENUNGGU_KONFIRMASI_BARANG' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'"
                                          x-text="ord.status === 'MENUNGGU_KONFIRMASI_BARANG' ? 'Menunggu Validasi' : 'Barang Diterima'">
                                    </span>
                                </td>
                                <td class="p-3.5 text-center">
                                    <template x-if="ord.status === 'MENUNGGU_KONFIRMASI_BARANG'">
                                        <button 
                                            type="button"
                                            @click="confirmGoodsReceived(ord.id)" 
                                            class="bg-[#ea580c] hover:bg-[#c2410c] text-white px-4 py-1.5 rounded-full font-bold text-[11px] shadow-sm transition cursor-pointer">
                                            ✓ Validasi Barang Sesuai
                                        </button>
                                    </template>
                                    <template x-if="ord.status !== 'MENUNGGU_KONFIRMASI_BARANG'">
                                        <span class="text-emerald-700 font-bold text-[11px] inline-flex items-center gap-1">
                                            ✓ Telah Dikonfirmasi
                                        </span>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SUB-TAB 3: PEMBAYARAN MIDTRANS -->
        <div x-show="orderSubTab === 'payment'" class="bg-white rounded-3xl border border-gray-100 overflow-hidden p-6 space-y-4 shadow-sm">
            <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                <div>
                    <h3 class="font-bold text-lg text-gray-900">Pembayaran Tagihan Pasokan Via Midtrans</h3>
                    <p class="text-xs text-gray-500">Bayar tagihan pasokan bahan baku secara resmi dan terintegrasi melalui Virtual Account / QRIS Midtrans.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-gray-50 font-mono text-gray-500 uppercase border-b border-gray-100">
                        <tr>
                            <th class="p-3.5">ID Pesanan</th>
                            <th class="p-3.5">Pemasok</th>
                            <th class="p-3.5">Komoditas & Volume</th>
                            <th class="p-3.5">Total Tagihan</th>
                            <th class="p-3.5">Status Pembayaran</th>
                            <th class="p-3.5 text-center">Aksi Bayar Online</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-medium">
                        <template x-for="ord in orders" :key="'pay-' + ord.id">
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="p-3.5 font-mono font-bold text-[#ea580c]" x-text="ord.id"></td>
                                <td class="p-3.5 font-bold text-gray-900" x-text="ord.supplierName"></td>
                                <td class="p-3.5 text-gray-700" x-text="ord.commodity + ' (' + ord.approvedQty + ' ' + ord.unit + ')'"></td>
                                <td class="p-3.5 font-bold text-gray-900" x-text="'Rp ' + ord.total.toLocaleString('id-ID')"></td>
                                <td class="p-3.5">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold"
                                          :class="ord.paymentStatus === 'PAID' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                                          x-text="ord.paymentStatus === 'PAID' ? '✓ LUNAS' : 'BELUM DIBAYAR'">
                                    </span>
                                </td>
                                <td class="p-3.5 text-center">
                                    <template x-if="ord.paymentStatus === 'UNPAID'">
                                        <button 
                                            type="button"
                                            @click="openMidtransPayment(ord)" 
                                            class="bg-[#ea580c] hover:bg-[#c2410c] text-white px-4 py-1.5 rounded-full font-bold text-[11px] shadow-sm transition cursor-pointer">
                                            Bayar Midtrans →
                                        </button>
                                    </template>
                                    <template x-if="ord.paymentStatus === 'PAID'">
                                        <span class="text-emerald-700 font-bold text-[11px]">✓ Lunas</span>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SUB-TAB 4: RATING & ULASAN -->
        <div x-show="orderSubTab === 'rating'" class="space-y-6">

            <!-- FORM BERI ULASAN AKTIF -->
            <template x-if="ratingInputOrder">
                <div class="bg-white p-6 sm:p-7 rounded-3xl border-2 border-[#ea580c] space-y-4 shadow-xl">
                    <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                        <div>
                            <span class="text-xs font-mono font-bold text-[#ea580c] uppercase">FORM PENILAIAN MUTU PASOKAN</span>
                            <h4 class="font-extrabold text-lg text-gray-900" x-text="'Beri Ulasan Pasokan: ' + ratingInputOrder.id"></h4>
                            <p class="text-xs text-gray-500" x-text="'Pemasok: ' + ratingInputOrder.supplierName + ' (' + ratingInputOrder.commodity + ')'"></p>
                        </div>
                        <button type="button" @click="ratingInputOrder = null" class="text-gray-400 hover:text-gray-700 font-bold text-lg cursor-pointer">✕</button>
                    </div>

                    <form @submit.prevent="saveRating()" class="space-y-4 text-xs font-semibold">
                        <div>
                            <label class="block mb-2 text-gray-700">Pilih Skor Bintang Kepuasan (1-5 Bintang):</label>
                            <div class="flex gap-2">
                                <template x-for="star in [1, 2, 3, 4, 5]" :key="'star-' + star">
                                    <button 
                                        type="button" 
                                        @click="stars = star" 
                                        class="p-2.5 rounded-2xl border transition text-2xl cursor-pointer"
                                        :class="star <= stars ? 'border-amber-400 bg-amber-50 text-amber-500' : 'border-gray-200 bg-gray-50 text-gray-300'">
                                        ★
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div>
                            <label class="block mb-1.5 text-gray-700">Tulis Ulasan Kualitas Bahan Baku & Ketepatan Pengiriman:</label>
                            <textarea 
                                rows="3" 
                                x-model="reviewText" 
                                required 
                                placeholder="Contoh: Kualitas daging sangat segar dan datang tepat waktu sebelum subuh..." 
                                class="w-full p-3.5 rounded-2xl bg-[#f1f2f4] text-gray-900 font-medium placeholder-gray-400 border-0 focus:outline-none focus:ring-2 focus:ring-[#ea580c]"></textarea>
                        </div>

                        <div class="flex justify-end gap-3 pt-1">
                            <button type="button" @click="ratingInputOrder = null" class="px-5 py-2.5 rounded-full border border-gray-200 text-gray-700 font-bold hover:bg-gray-50 cursor-pointer">
                                Batal
                            </button>
                            <button type="submit" class="bg-[#ea580c] hover:bg-[#c2410c] text-white px-7 py-2.5 rounded-full font-extrabold shadow-sm transition cursor-pointer">
                                Simpan Ulasan →
                            </button>
                        </div>
                    </form>
                </div>
            </template>

            <!-- RIWAYAT ULASAN -->
            <div class="bg-white rounded-3xl border border-gray-100 overflow-hidden p-6 space-y-4 shadow-sm">
                <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                    <div>
                        <h3 class="font-bold text-lg text-gray-900">Riwayat Ulasan & Rating Pemasok</h3>
                        <p class="text-xs text-gray-500">Evaluasi mutu bahan baku yang telah diterima dan diselesaikan oleh Dapur UMKM Anda.</p>
                    </div>
                </div>

                <div class="space-y-3">
                    <template x-for="ord in orders.filter(o => o.paymentStatus === 'PAID')" :key="'review-list-' + ord.id">
                        <div class="bg-gray-50 p-4 rounded-2xl space-y-2 border border-gray-100">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                                <div>
                                    <h4 class="font-bold text-gray-900 text-sm" x-text="ord.supplierName"></h4>
                                    <span class="text-[11px] text-gray-500 font-mono" x-text="ord.id + ' • ' + ord.commodity + ' (' + ord.approvedQty + ' ' + ord.unit + ')'"></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="flex text-amber-400 text-sm">
                                        <template x-for="s in [1, 2, 3, 4, 5]" :key="'score-' + ord.id + '-' + s">
                                            <span :class="s <= (ord.rating || 5) ? 'text-amber-400' : 'text-gray-300'">★</span>
                                        </template>
                                    </div>
                                    <button 
                                        type="button" 
                                        @click="openRatingForOrder(ord)" 
                                        class="text-[10px] font-bold text-[#ea580c] hover:underline cursor-pointer ml-2">
                                        Edit / Ulas
                                    </button>
                                </div>
                            </div>
                            <p class="text-xs text-gray-600 italic bg-white p-3 rounded-xl border border-gray-100" x-text="'&ldquo;' + (ord.review || 'Transaksi pasokan selesai dengan baik.') + '&rdquo;'"></p>
                        </div>
                    </template>
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
        <div @click.away="selectedCommodity = null" class="bg-white max-w-2xl w-full rounded-[2.2rem] p-6 sm:p-8 space-y-5 shadow-2xl relative border border-gray-100 max-h-[92vh] overflow-y-auto">
            
            <div class="flex justify-between items-start border-b border-gray-100 pb-4">
                <div>
                    <span class="text-xs font-bold text-orange-600 uppercase font-mono">FORM PENGAJUAN KEMITRAAN & JADWAL PASOKAN</span>
                    <h3 class="text-2xl font-bold text-gray-900 tracking-tight" x-text="selectedCommodity?.commodity_name"></h3>
                    <p class="text-xs text-gray-600">Mitra Pemasok: <span class="font-bold text-gray-900" x-text="selectedCommodity?.company_name"></span></p>
                </div>
                <button type="button" @click="selectedCommodity = null" class="text-gray-400 hover:text-gray-700 font-bold text-lg cursor-pointer p-1">✕</button>
            </div>

            <!-- Quick Details Komoditas -->
            <div class="bg-[#f8f9fa] p-4 rounded-2xl border border-gray-100 flex flex-wrap justify-between items-center gap-3 text-xs">
                <div>
                    <span class="text-gray-400 block text-[10px] uppercase font-mono">Harga Grosir:</span>
                    <span class="text-orange-600 font-bold text-sm" x-text="selectedCommodity?.price"></span>
                </div>
                <div>
                    <span class="text-gray-400 block text-[10px] uppercase font-mono">Kapasitas Stok:</span>
                    <span class="text-gray-800 font-semibold" x-text="selectedCommodity?.stock_capacity ?? '-'"></span>
                </div>
                <div>
                    <span class="text-gray-400 block text-[10px] uppercase font-mono">Status Ketersediaan:</span>
                    <span class="text-emerald-700 font-bold" x-text="selectedCommodity?.stock_status"></span>
                </div>
                <template x-if="selectedCommodity?.is_halal">
                    <span class="bg-emerald-100 text-emerald-800 px-3 py-1 rounded-full text-[10px] font-bold">✓ Sertifikat Halal</span>
                </template>
            </div>

            <form method="POST" action="{{ route('umkm.katalog.ajukan') }}" class="space-y-4 text-xs font-semibold">
                @csrf
                <input type="hidden" name="supplier_product_id" :value="selectedCommodity?.id">
                <input type="hidden" name="supplier_id" :value="selectedCommodity?.supplier_id">
                <input type="hidden" name="unit" :value="selectedCommodity?.unit">
                <input type="hidden" name="volume" :value="formVolume">
                <input type="hidden" name="total_contract_volume" :value="calculateMonthlyVolume()">

                <!-- Hidden inputs untuk delivery_days[] yang terpilih -->
                <template x-for="day in formDays" :key="day">
                    <input type="hidden" name="delivery_days[]" :value="day">
                </template>

                <!-- 1. Volume Pengiriman per Jadwal -->
                <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100 space-y-2">
                    <div class="flex justify-between items-center">
                        <label class="text-gray-800 font-bold">Volume per Pengiriman:</label>
                        <span class="text-[11px] text-gray-500 font-normal">Kuantitas bahan baku yang dikirim di setiap jadwal</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1">
                            <input 
                                type="number" 
                                name="volume_per_delivery" 
                                required 
                                x-model.number="formVolume"
                                min="0.1"
                                step="any"
                                class="w-full pl-4 pr-16 py-2.5 rounded-xl bg-white text-gray-900 font-bold border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#ea580c]"
                            />
                            <span class="absolute right-4 top-2.5 text-gray-400 font-bold uppercase text-xs" x-text="selectedCommodity?.unit"></span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button type="button" @click="formVolume = 50" class="px-2.5 py-2 rounded-lg bg-white border border-gray-200 text-gray-700 hover:border-[#ea580c] hover:text-[#ea580c] transition text-[11px] cursor-pointer">50</button>
                            <button type="button" @click="formVolume = 100" class="px-2.5 py-2 rounded-lg bg-white border border-gray-200 text-gray-700 hover:border-[#ea580c] hover:text-[#ea580c] transition text-[11px] cursor-pointer">100</button>
                            <button type="button" @click="formVolume = 200" class="px-2.5 py-2 rounded-lg bg-white border border-gray-200 text-gray-700 hover:border-[#ea580c] hover:text-[#ea580c] transition text-[11px] cursor-pointer">200</button>
                            <button type="button" @click="formVolume = 500" class="px-2.5 py-2 rounded-lg bg-white border border-gray-200 text-gray-700 hover:border-[#ea580c] hover:text-[#ea580c] transition text-[11px] cursor-pointer">500</button>
                        </div>
                    </div>
                </div>

                <!-- 2. Frekuensi & Jadwal Pengiriman (Data Tabel Baru) -->
                <div class="space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-gray-700 mb-1">Pilihan Pola Frekuensi:</label>
                            <select 
                                name="frequency_type" 
                                x-model="formFrequency"
                                @change="onFrequencySelect($event.target.value)"
                                required 
                                class="w-full px-3 py-2.5 rounded-xl bg-[#f1f2f4] text-gray-900 font-medium focus:outline-none focus:ring-2 focus:ring-[#ea580c] border-0">
                                <option value="2x Seminggu (Senin & Kamis)">2x Seminggu (Senin & Kamis)</option>
                                <option value="3x Seminggu (Senin, Rabu, Jumat)">3x Seminggu (Senin, Rabu, Jumat)</option>
                                <option value="Setiap Hari">Setiap Hari (Senin - Minggu)</option>
                                <option value="1x Seminggu">1x Seminggu (Senin)</option>
                                <option value="Kustom">Kustomisasi Hari Sendiri</option>
                            </select>
                            <p x-show="formFrequency === 'Kustom'" class="text-[10px] text-[#ea580c] font-bold mt-1 tracking-tight">
                                ✦ Pola Kustom: <span x-text="formDays.length + ' Hari/Minggu (' + formDays.join(', ') + ')'"></span>
                            </p>
                        </div>

                        <div>
                            <label class="block text-gray-700 mb-1">Target Jam Tiba di Dapur (WIB):</label>
                            <input 
                                type="time" 
                                name="delivery_time" 
                                x-model="formTime"
                                required
                                class="w-full px-3 py-2.5 rounded-xl bg-[#f1f2f4] text-gray-900 font-medium focus:outline-none focus:ring-2 focus:ring-[#ea580c] border-0"
                            />
                        </div>
                    </div>

                    <!-- Interactive Days Selector Pills -->
                    <div class="bg-gray-50/70 p-3.5 rounded-2xl border border-gray-100 space-y-1.5">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-700 font-bold text-[11px]">Hari Pengiriman Terjadwal:</span>
                            <span class="text-gray-400 text-[10px]" x-text="formDays.length + ' hari terpilih'"></span>
                        </div>
                        <div class="flex flex-wrap gap-1.5 pt-1">
                            <template x-for="day in availableDays" :key="'pill-' + day">
                                <button 
                                    type="button"
                                    @click="toggleDay(day)"
                                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer"
                                    :class="formDays.includes(day) 
                                        ? 'bg-[#ea580c] text-white shadow-sm ring-2 ring-orange-200' 
                                        : 'bg-[#f1f2f4] text-gray-600 hover:bg-gray-200'">
                                    <span x-text="day"></span>
                                    <span x-show="formDays.includes(day)" class="ml-1 text-[10px]">✓</span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- 3. Tanggal Mulai Pengiriman Perdana -->
                <div>
                    <label class="block text-gray-700 mb-1">Tanggal Mulai Pengiriman Perdana:</label>
                    <input 
                        type="date" 
                        name="start_date" 
                        x-model="formStartDate"
                        required 
                        class="w-full px-3 py-2.5 rounded-xl bg-[#f1f2f4] text-gray-900 font-medium focus:outline-none focus:ring-2 focus:ring-[#ea580c] border-0"
                    />
                </div>

                <!-- 4. Live Ringkasan Kalkulasi & Proyeksi Kontrak Bulanan -->
                <div class="bg-orange-50/80 p-4 rounded-2xl border border-orange-200/80 space-y-2">
                    <span class="text-[10px] text-[#ea580c] font-mono uppercase font-bold block">
                        RINGKASAN PROYEKSI & ESTIMASI NILAI KEMITRAAN:
                    </span>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 pt-1">
                        <div class="bg-white p-2.5 rounded-xl border border-orange-100 shadow-xs">
                            <span class="text-[10px] text-gray-500 block">Subtotal / Pengiriman:</span>
                            <span class="font-bold text-gray-900 text-xs" x-text="calculateDeliverySubtotal()"></span>
                        </div>
                        <div class="bg-white p-2.5 rounded-xl border border-orange-100 shadow-xs">
                            <span class="text-[10px] text-gray-500 block">Proyeksi Pasok Bulanan:</span>
                            <span class="font-bold text-gray-900 text-xs" x-text="calculateMonthlyVolume() + ' ' + (selectedCommodity?.unit ?? 'kg')"></span>
                        </div>
                        <div class="bg-white p-2.5 rounded-xl border border-orange-100 shadow-xs col-span-2 sm:col-span-1">
                            <span class="text-[10px] text-[#ea580c] font-bold block">Estimasi Nilai / Bulan:</span>
                            <span class="font-black text-[#ea580c] text-sm" x-text="calculateMonthlyTotal()"></span>
                        </div>
                    </div>
                </div>

                <!-- 5. Opsi Pemesanan Otomatis (Auto-Order Routine) -->
                <div class="bg-gray-50 p-3.5 rounded-2xl border border-gray-100 flex items-start gap-3">
                    <input 
                        type="checkbox" 
                        id="autoOrderCheck"
                        name="is_auto_order_active" 
                        value="1" 
                        x-model="formAutoOrder"
                        class="accent-[#ea580c] w-4 h-4 rounded cursor-pointer mt-0.5"
                    />
                    <label for="autoOrderCheck" class="cursor-pointer space-y-0.5">
                        <span class="block text-gray-900 font-bold text-xs">Aktifkan Pemesanan Otomatis (Auto-Order Routine)</span>
                        <span class="block text-gray-500 text-[11px] font-normal leading-relaxed">
                            Sistem akan otomatis menerbitkan jadwal pesanan di subuh hari pengiriman tanpa perlu konfirmasi manual berulang kali.
                        </span>
                    </label>
                </div>

                <!-- 6. Catatan Spesifikasi Mutu Dapur -->
                <div>
                    <label class="block text-gray-700 mb-1">Catatan Spesifikasi Mutu & Instruksi Khusus Dapur (Opsional):</label>
                    <textarea 
                        name="submission_details" 
                        rows="2" 
                        placeholder="Contoh: Pemotongan ayam karkas dibagi 8 bagian, toleransi tiba sebelum jam 06.00 WIB, kemasan tertutup rapi."
                        class="w-full px-4 py-2.5 rounded-xl bg-[#f1f2f4] text-gray-900 font-medium placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c] border-0"
                    ></textarea>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="selectedCommodity = null" class="w-1/2 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-full font-bold transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="w-1/2 bg-[#ea580c] hover:bg-[#c2410c] text-white py-3 rounded-full font-bold shadow-sm transition cursor-pointer">
                        Kirim Pengajuan Kemitraan →
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- MODAL PREVIEW DETAIL PESANAN OTOMATIS                     -->
    <!-- ========================================================= -->
    <div x-cloak x-show="previewAutoOrder" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div @click.away="previewAutoOrder = null" class="bg-white max-w-lg w-full rounded-3xl p-6 sm:p-8 space-y-6 shadow-2xl border border-gray-100">
            <div class="flex justify-between items-start border-b border-gray-100 pb-4">
                <div>
                    <span class="font-mono text-[10px] font-bold bg-orange-100 text-[#ea580c] px-3 py-1 rounded-full uppercase">
                        DETAIL KONTRAK · <span x-text="previewAutoOrder?.contractId"></span>
                    </span>
                    <h3 class="text-2xl font-black text-gray-900 uppercase pt-2 tracking-tight leading-tight">
                        Preview Pesanan Otomatis
                    </h3>
                    <p class="text-xs font-mono font-bold text-[#ea580c]" x-text="previewAutoOrder?.id"></p>
                </div>
                <button type="button" @click="previewAutoOrder = null" class="text-gray-400 hover:text-gray-700 font-bold text-lg cursor-pointer">
                    ✕
                </button>
            </div>

            <div class="space-y-4 text-xs font-semibold">
                <div class="bg-gray-50 p-4 rounded-2xl space-y-2.5 border border-gray-100">
                    <div class="flex justify-between items-center text-gray-800">
                        <span class="text-gray-500 font-medium">Pemasok Mitra:</span>
                        <span class="font-bold text-sm text-gray-900" x-text="previewAutoOrder?.supplierName"></span>
                    </div>
                    <div class="flex justify-between items-center text-gray-800">
                        <span class="text-gray-500 font-medium">Komoditas Pasok:</span>
                        <span class="font-bold text-[#ea580c]" x-text="previewAutoOrder?.commodity"></span>
                    </div>
                    <div class="flex justify-between items-center text-gray-800">
                        <span class="text-gray-500 font-medium">Volume Terikat Kontrak:</span>
                        <span class="font-bold text-gray-900" x-text="(previewAutoOrder?.qty ?? 0) + ' ' + (previewAutoOrder?.unit ?? 'kg')"></span>
                    </div>
                    <div class="flex justify-between items-center text-gray-800">
                        <span class="text-gray-500 font-medium">Harga Satuan Grosir:</span>
                        <span class="font-mono text-gray-900" x-text="'Rp ' + (previewAutoOrder?.pricePerUnit ? previewAutoOrder.pricePerUnit.toLocaleString('id-ID') : 0) + ' / ' + (previewAutoOrder?.unit ?? 'kg')"></span>
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-gray-500">Jadwal Rutin:</span>
                        <span class="font-mono font-bold text-gray-900" x-text="previewAutoOrder?.deliverySchedule"></span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-gray-500">Estimasi Tiba Berikutnya:</span>
                        <span class="font-mono font-bold text-emerald-800 bg-emerald-100 px-2.5 py-0.5 rounded-full" x-text="previewAutoOrder?.nextDelivery"></span>
                    </div>
                </div>

                <div class="bg-white p-3.5 rounded-2xl border border-gray-200 space-y-1">
                    <span class="text-[10px] font-mono uppercase text-gray-400 block font-bold">CATATAN KHUSUS DAPUR:</span>
                    <p class="font-medium text-gray-700 leading-relaxed" x-text="previewAutoOrder?.notes"></p>
                </div>

                <div class="p-4 bg-orange-50 rounded-2xl flex justify-between items-center border border-orange-200">
                    <div>
                        <span class="text-[10px] font-mono uppercase text-[#ea580c] block font-bold">TOTAL TAGIHAN RUTIN:</span>
                        <span class="text-2xl font-black text-gray-900" x-text="'Rp ' + (previewAutoOrder?.total ? previewAutoOrder.total.toLocaleString('id-ID') : 0)"></span>
                    </div>
                    <span class="bg-white border border-orange-200 text-[#ea580c] font-bold text-[11px] px-3 py-1 rounded-full">
                        Tagihan Otomatis
                    </span>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button 
                    type="button" 
                    @click="previewAutoOrder = null" 
                    class="w-1/2 border border-gray-200 text-gray-700 py-3 rounded-full font-bold text-xs hover:bg-gray-50 transition cursor-pointer">
                    Tutup
                </button>
                <template x-if="!isPublishedToday(previewAutoOrder)">
                    <button 
                        type="button" 
                        @click="handlePublishAutoOrder(previewAutoOrder)" 
                        class="w-1/2 bg-[#ea580c] hover:bg-[#c2410c] text-white py-3 rounded-full font-bold text-xs hover:brightness-95 transition shadow-sm cursor-pointer flex items-center justify-center gap-1.5">
                        <span>Terbitkan Pesanan Sekarang</span>
                        <span>→</span>
                    </button>
                </template>

                <template x-if="isPublishedToday(previewAutoOrder)">
                    <button 
                        type="button" 
                        disabled
                        class="w-1/2 bg-emerald-50 border border-emerald-200 text-emerald-800 py-2.5 px-3 rounded-full font-bold text-[10px] cursor-not-allowed flex flex-col items-center justify-center text-center leading-tight shadow-xs">
                        <span class="flex items-center gap-1 text-emerald-700 font-extrabold text-[11px]">
                            <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>Sudah Diterbitkan Hari Ini</span>
                        </span>
                        <span class="text-[9px] text-emerald-600 font-semibold mt-0.5 truncate max-w-full" x-text="'Jadwal Selanjutnya: ' + getNextDeliveryScheduleText(previewAutoOrder)"></span>
                    </button>
                </template>
            </div>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- MODAL MIDTRANS PAYMENT SIMULATOR                          -->
    <!-- ========================================================= -->
    <div x-cloak x-show="payingOrder" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div @click.away="payingOrder = null" class="bg-white max-w-md w-full rounded-3xl p-6 sm:p-7 space-y-5 shadow-2xl border border-gray-100">
            <div class="flex justify-between items-start border-b border-gray-100 pb-3">
                <div>
                    <span class="text-xs font-bold text-[#ea580c] uppercase font-mono">MIDTRANS PAYMENT GATEWAY</span>
                    <h3 class="text-xl font-extrabold text-gray-900">Pembayaran Tagihan Pasokan</h3>
                </div>
                <button type="button" @click="payingOrder = null" class="text-gray-400 hover:text-gray-700 font-bold text-lg cursor-pointer">✕</button>
            </div>

            <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100 space-y-2 text-xs font-mono">
                <div class="flex justify-between text-gray-600"><span>No. Order:</span><span class="font-bold text-[#ea580c]" x-text="payingOrder?.id"></span></div>
                <div class="flex justify-between text-gray-600"><span>Pemasok:</span><span class="font-bold text-gray-900" x-text="payingOrder?.supplierName"></span></div>
                <div class="flex justify-between text-gray-600"><span>Komoditas:</span><span class="font-bold text-gray-900" x-text="payingOrder?.commodity"></span></div>
                <div class="flex justify-between text-base text-[#ea580c] font-bold pt-2 border-t border-gray-200">
                    <span>Total Tagihan:</span>
                    <span x-text="'Rp ' + (payingOrder?.total ? payingOrder.total.toLocaleString('id-ID') : 0)"></span>
                </div>
            </div>

            <div class="space-y-2 text-xs font-semibold">
                <span class="block text-gray-600 text-[11px]">Pilih Metode Pembayaran Midtrans:</span>
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" @click="selectedPaymentMethod = 'bca'" :class="selectedPaymentMethod === 'bca' ? 'border-[#ea580c] bg-orange-50 text-[#ea580c]' : 'border-gray-200 bg-[#f1f2f4] text-gray-800'" class="p-2.5 rounded-xl border text-center font-bold cursor-pointer transition">BCA VA</button>
                    <button type="button" @click="selectedPaymentMethod = 'mandiri'" :class="selectedPaymentMethod === 'mandiri' ? 'border-[#ea580c] bg-orange-50 text-[#ea580c]' : 'border-gray-200 bg-[#f1f2f4] text-gray-800'" class="p-2.5 rounded-xl border text-center font-bold cursor-pointer transition">Mandiri VA</button>
                    <button type="button" @click="selectedPaymentMethod = 'qris'" :class="selectedPaymentMethod === 'qris' ? 'border-[#ea580c] bg-orange-50 text-[#ea580c]' : 'border-gray-200 bg-[#f1f2f4] text-gray-800'" class="p-2.5 rounded-xl border text-center font-bold cursor-pointer transition">QRIS</button>
                </div>
            </div>

            <button type="button" @click="simulateMidtransSuccess()" class="w-full bg-[#ea580c] hover:bg-[#c2410c] text-white py-3.5 rounded-full font-bold text-xs shadow-md transition cursor-pointer">
                Simulasi Pembayaran Berhasil (Sukses) ✓
            </button>
        </div>
    </div>

</div>
@endsection
