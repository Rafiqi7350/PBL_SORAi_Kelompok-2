@extends('layouts.umkm')

@section('content')
<script>
    function umkmCatalogPage() {
        return {
            selectedCommodity: null,
            orderSubTab: (new URLSearchParams(window.location.search).get('subtab') || 'auto-order'), // 'auto-order' | 'confirm' | 'backorder' | 'payment' | 'rating'
            backorderFilter: 'all', // 'all' | 'pending' | 'approved' | 'rejected'
            payingOrder: null,
            selectedPaymentMethod: 'bca',
            previewAutoOrder: null,
            ratingInputOrder: null,
            stars: 5,
            reviewText: '',
            notificationMsg: @json(session('success') ?? ''),
            init() {
                var self = this;
                if (this.notificationMsg) {
                    setTimeout(function() { self.notificationMsg = ''; }, 4000);
                }
            },
            pendingValidationCount: {{ $pendingValidationCount ?? 0 }},
            pendingBackorderCount: {{ $pendingBackorderCount ?? 0 }},
            pendingBackorderApprovalCount: {{ $pendingBackorderApprovalCount ?? 0 }},
            rejectingBackorderId: null,
            rejectionReason: '',
            rejectingAction: 'create_backorder',
            approvingBackorderOrder: null,
            pendingPaymentCount: {{ $pendingPaymentCount ?? 0 }},

            planningForm: {
                commodity: '{{ $availableCommodities->first()?->name ?? "Daging Ayam Broiler Segar" }}',
                volume: 200,
                unit: 'kg',
                targetDate: '{{ now()->addMonth()->format("Y-m") }}',
                supplier_id: '{{ $availableSuppliers->first()?->id ?? "" }}',
                notes: 'Standar potongan karkas dingin, pengiriman pagi pukul 05:00 WIB.'
            },

            supplyPlans: @json($supplyPlans ?? []),
            planningFilterStatus: 'all',
            planningSearchQuery: '',

            get filteredSupplyPlans() {
                var self = this;
                return this.supplyPlans.filter(function(p) {
                    var matchStatus = (self.planningFilterStatus === 'all') || (p.status === self.planningFilterStatus);
                    var q = self.planningSearchQuery.toLowerCase().trim();
                    var matchQuery = !q || (p.commodity && p.commodity.toLowerCase().includes(q)) || (p.supplier && p.supplier.toLowerCase().includes(q)) || (p.id && p.id.toLowerCase().includes(q));
                    return matchStatus && matchQuery;
                });
            },
            get planningCountApproved() {
                return this.supplyPlans.filter(function(p) { return p.status === 'DISETUJUI'; }).length;
            },
            get planningCountAdjusted() {
                return this.supplyPlans.filter(function(p) { return p.status === 'DISESUAIKAN'; }).length;
            },
            get planningCountPending() {
                return this.supplyPlans.filter(function(p) { return p.status === 'MENUNGGU_KONFIRMASI' || p.status === 'DALAM_REVIEW'; }).length;
            },
            getSelectedSupplierName: function() {
                var suppliers = @json($availableSuppliers ?? []);
                for (var i = 0; i < suppliers.length; i++) {
                    if (suppliers[i].id === this.planningForm.supplier_id) {
                        return suppliers[i].company_name;
                    }
                }
                return 'Pemasok Mitra Terpilih';
            },
            formatMonthPreview: function(ym) {
                if (!ym) return '-';
                var parts = ym.split('-');
                if (parts.length !== 2) return ym;
                var monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                var m = parseInt(parts[1], 10);
                if (m >= 1 && m <= 12) {
                    return monthNames[m - 1] + ' ' + parts[0];
                }
                return ym;
            },

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
                { id: 'ORD-2026-001', umkmName: 'Dapur Sambal Malang', supplierName: 'Kelompok Tani Poultry Malang', commodity: 'Daging Ayam Broiler Segar', requestedQty: 100, approvedQty: 100, unit: 'kg', pricePerUnit: 34000, total: 3400000, date: '01/10/2026', status: 'BARANG_DITERIMA', stockNote: 'Barang Tiba Di Dapur Subuh Tadi', paymentStatus: 'PAID', rating: null, review: '' },
                { id: 'ORD-2026-002', umkmName: 'Dapur Sambal Malang', supplierName: 'Kelompok Tani Poultry Malang', commodity: 'Daging Ayam Broiler Segar', requestedQty: 200, approvedQty: 120, unit: 'kg', pricePerUnit: 34000, total: 4080000, date: '28/09/2026', status: 'PENYESUAIAN_STOK', stockNote: 'Stok Terbatas (Dikirim 120kg dari 200kg)', paymentStatus: 'PAID', rating: null, review: '' },
                { id: 'ORD-2026-003', umkmName: 'Dapur Sambal Malang', supplierName: 'Distributor Rempah Nusantara', commodity: 'Bawang Merah Biru Probolinggo', requestedQty: 50, approvedQty: 50, unit: 'kg', pricePerUnit: 28000, total: 1400000, date: '22/09/2026', status: 'BARANG_DITERIMA', stockNote: 'Pengiriman Subuh Dalam Perjalanan', paymentStatus: 'PAID', rating: null, review: '' },
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
                // Buka modal konfirmasi dengan upload foto (terhubung ke DB)
                this.confirmingOrderId = orderId;
                this.confirmPhotoPreview = null;
                this.confirmNotes = '';
            },

            confirmingOrderId: null,
            confirmPhotoPreview: null,
            confirmNotes: '',

            confirmingBackorderId: null,
            confirmBackorderPhotoPreview: null,
            confirmBackorderNotes: '',

            onConfirmPhotoChange: function(event) {
                var file = event.target.files[0];
                if (file) {
                    this.confirmPhotoPreview = URL.createObjectURL(file);
                }
            },

            onConfirmBackorderPhotoChange: function(event) {
                var file = event.target.files[0];
                if (file) {
                    this.confirmBackorderPhotoPreview = URL.createObjectURL(file);
                }
            },

            payingInvoice: null,
            payingOrder: null,
            selectedPaymentMethod: 'BCA Virtual Account',

            openBatchInvoicePayment: function(inv) {
                this.payingInvoice = inv;
                this.selectedPaymentMethod = 'BCA Virtual Account';
            },

            openMidtransPayment: function(order) {
                this.payingOrder = order;
                this.selectedPaymentMethod = 'BCA Virtual Account';
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
            <button type="button" @click="notificationMsg = ''" class="font-bold text-base ml-2 hover:opacity-75 cursor-pointer">✕</button>
        </div>
    </div>

    @if(session('error'))
        <div x-data="{ showError: true }" x-init="setTimeout(() => showError = false, 4500)" x-cloak x-show="showError" x-transition class="fixed top-20 right-4 sm:right-8 z-50 max-w-md w-full">
            <div class="bg-rose-600 text-white p-4 rounded-2xl font-medium text-xs flex justify-between items-center shadow-xl">
                <span>{{ session('error') }}</span>
                <button type="button" @click="showError = false" class="font-bold text-base ml-2 hover:opacity-75 cursor-pointer">✕</button>
            </div>
        </div>
    @endif

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
                        <div class="relative h-44 w-full bg-gray-100 overflow-hidden flex items-center justify-center">
                            @if($product->display_image_url)
                                <img src="{{ $product->display_image_url }}" 
                                     alt="{{ $product->commodity->name }}" 
                                     class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-500">
                            @else
                                <div class="w-full h-full flex flex-col items-center justify-center text-gray-400 bg-gray-50 border-b border-gray-100">
                                    <svg class="w-10 h-10 mb-1 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-[10px] font-bold text-gray-400">Belum Ada Foto</span>
                                </div>
                            @endif

                            @if(in_array($product->id, $appliedProductIds ?? []))
                                <span class="absolute top-3 left-3 bg-emerald-600 text-white text-[10px] font-bold px-2.5 py-1 rounded-full shadow-sm flex items-center gap-1">
                                    <span>✓ Sudah Diajukan</span>
                                </span>
                            @else
                                <span class="absolute top-3 left-3 bg-white/95 text-gray-800 text-[10px] font-bold px-2.5 py-1 rounded-full shadow-sm uppercase">
                                    {{ $product->commodity->category }}
                                </span>
                            @endif

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

                                @php
                                    $isAlreadyApplied = in_array($product->id, $appliedProductIds ?? []);
                                @endphp

                                @if($isAlreadyApplied)
                                    <button 
                                        type="button"
                                        disabled
                                        class="w-full bg-emerald-50 border border-emerald-200 text-emerald-800 font-bold py-2.5 rounded-full text-xs cursor-not-allowed flex items-center justify-center gap-1.5 shadow-2xs">
                                        <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                        <span>Sudah Diajukan Kemitraan</span>
                                    </button>
                                @elseif($product->stock_status !== 'Stok Habis')
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
                                             'image_url' => $product->display_image_url,
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

        <!-- 1. HERO HEADER WITH KPI CHIPS (SOLID ORANGE) -->
        <div class="rounded-3xl p-6 sm:p-8 text-white shadow-lg relative overflow-hidden bg-[#ea580c]" style="background-color: #ea580c;">
            <!-- Ambient subtle blur accents -->
            <div class="absolute -right-16 -top-16 w-64 h-64 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="absolute -left-12 -bottom-12 w-48 h-48 bg-white/5 rounded-full blur-xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div class="space-y-2 max-w-2xl">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 backdrop-blur-md text-[11px] font-bold tracking-wider uppercase text-white border border-white/25">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span>Kolaborasi Rantai Pasok Pangan</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
                        Collaborative Supply Planning
                    </h2>
                    <p class="text-xs sm:text-sm text-orange-50/95 leading-relaxed">
                        Sampaikan estimasi proyeksi kebutuhan bahan baku dapur Anda untuk periode mendatang. Pemasok mitra dapat merencanakan kuota panen & persediaan secara presisi tanpa risiko kelangkaan stok.
                    </p>
                </div>

                <!-- 4 Quick Stats Pills (Clean Uniform White Contrast) -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 sm:gap-3 bg-white/15 backdrop-blur-md p-3 rounded-2xl border border-white/25">
                    <div class="px-3.5 py-2.5 rounded-xl bg-white/15 text-center">
                        <span class="block text-[10px] text-white/80 uppercase font-mono font-bold tracking-wider">Total Rencana</span>
                        <span class="text-xl font-black text-white" x-text="supplyPlans.length"></span>
                    </div>
                    <div class="px-3.5 py-2.5 rounded-xl bg-white/15 text-center">
                        <span class="block text-[10px] text-white/80 uppercase font-mono font-bold tracking-wider">Siap 100%</span>
                        <span class="text-xl font-black text-white" x-text="planningCountApproved"></span>
                    </div>
                    <div class="px-3.5 py-2.5 rounded-xl bg-white/15 text-center">
                        <span class="block text-[10px] text-white/80 uppercase font-mono font-bold tracking-wider">Disesuaikan</span>
                        <span class="text-xl font-black text-white" x-text="planningCountAdjusted"></span>
                    </div>
                    <div class="px-3.5 py-2.5 rounded-xl bg-white/15 text-center">
                        <span class="block text-[10px] text-white/80 uppercase font-mono font-bold tracking-wider">Menunggu</span>
                        <span class="text-xl font-black text-white" x-text="planningCountPending"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. MAIN 2-COLUMN SECTION: FORM (LEFT) & MANFAAT (RIGHT) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- LEFT: FORM INPUT ESTIMASI (8 COLS) -->
            <div class="lg:col-span-8 bg-white rounded-3xl p-6 sm:p-7 border border-gray-200/80 shadow-sm space-y-6">
                <div class="flex items-center gap-3 border-b border-gray-100 pb-4">
                    <div class="w-10 h-10 rounded-2xl bg-orange-50 border border-orange-100 text-[#ea580c] flex items-center justify-center font-bold text-lg shadow-xs">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-extrabold text-gray-900 tracking-tight">Formulir Estimasi Kebutuhan Pasokan</h3>
                        <p class="text-xs text-gray-500">Kirimkan estimasi kuantitas dapur untuk periode mendatang langsung ke pemasok.</p>
                    </div>
                </div>

                <form action="{{ route('umkm.supply-planning.store') }}" method="POST" class="space-y-5 text-xs font-semibold">
                    @csrf

                    <!-- Group 1: Pemasok & Komoditas -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-1.5 text-gray-700 font-bold">
                                Mitra Pemasok Terpilih <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <select name="supplier_id" x-model="planningForm.supplier_id" required class="w-full pl-3.5 pr-8 py-3 rounded-2xl bg-[#f1f2f4] text-gray-800 font-medium border-0 focus:outline-none focus:ring-2 focus:ring-[#ea580c] appearance-none cursor-pointer">
                                    <option value="" disabled>-- Pilih Mitra Pemasok --</option>
                                    @forelse($availableSuppliers as $sup)
                                        <option value="{{ $sup->id }}">{{ $sup->company_name }} ({{ $sup->delivery_area ?? 'Lokal' }})</option>
                                    @empty
                                        <option value="" disabled>Belum ada pemasok terdaftar</option>
                                    @endforelse
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block mb-1.5 text-gray-700 font-bold">
                                Komoditas Bahan Baku <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <select name="commodity_name" x-model="planningForm.commodity" required class="w-full pl-3.5 pr-8 py-3 rounded-2xl bg-[#f1f2f4] text-gray-800 font-medium border-0 focus:outline-none focus:ring-2 focus:ring-[#ea580c] appearance-none cursor-pointer">
                                    @forelse($availableCommodities as $comm)
                                        <option value="{{ $comm->name }}">{{ $comm->name }} ({{ $comm->category }})</option>
                                    @empty
                                        <option>Daging Ayam Broiler Segar</option>
                                        <option>Bawang Merah Biru Probolinggo</option>
                                        <option>Telur Ayam Ras Grade A</option>
                                        <option>Cabai Rawit Merah Super</option>
                                        <option>Daging Sapi Lokal Segar</option>
                                    @endforelse
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Group 2: Estimasi Volume + Satuan & Periode Bulan -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-1.5 text-gray-700 font-bold">
                                Estimasi Kuantitas & Satuan <span class="text-red-500">*</span>
                            </label>
                            <div class="flex rounded-2xl overflow-hidden bg-[#f1f2f4] focus-within:ring-2 focus-within:ring-[#ea580c]">
                                <input type="number" step="0.1" min="0.1" name="estimated_quantity" x-model="planningForm.volume" required placeholder="Contoh: 200" class="w-full p-3 bg-transparent text-gray-800 font-bold border-0 focus:outline-none" />
                                <select name="unit" x-model="planningForm.unit" class="bg-gray-200/80 px-3 py-3 text-gray-700 font-bold border-0 focus:outline-none cursor-pointer text-xs">
                                    @forelse($availableUnits as $unit)
                                        <option value="{{ $unit->name }}">{{ $unit->name }} ({{ $unit->symbol }})</option>
                                    @empty
                                        <option value="kg">kg</option>
                                        <option value="liter">liter</option>
                                        <option value="pcs">pcs</option>
                                        <option value="ikat">ikat</option>
                                        <option value="karung">karung</option>
                                        <option value="dus">dus</option>
                                    @endforelse
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block mb-1.5 text-gray-700 font-bold">
                                Periode Bulan Target <span class="text-red-500">*</span>
                            </label>
                            <input type="month" name="period_month_year" x-model="planningForm.targetDate" value="{{ now()->addMonth()->format('Y-m') }}" required class="w-full p-3 rounded-2xl bg-[#f1f2f4] text-gray-800 font-bold border-0 focus:outline-none focus:ring-2 focus:ring-[#ea580c]" />
                        </div>
                    </div>

                    <!-- Group 3: Catatan Khusus Kebutuhan Dapur -->
                    <div>
                        <label class="block mb-1.5 text-gray-700 font-bold">
                            Catatan Khusus Kebutuhan Dapur (Opsional)
                        </label>
                        <textarea name="notes" x-model="planningForm.notes" rows="3" placeholder="Contoh: Potongan dada fillet dingin 4°C, pengiriman subuh jam 05.00 WIB 2x seminggu..." class="w-full p-3.5 rounded-2xl bg-[#f1f2f4] text-gray-800 font-medium border-0 focus:outline-none focus:ring-2 focus:ring-[#ea580c]"></textarea>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-3 border-t border-gray-100">
                        <span class="text-[11px] text-gray-500 font-medium flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-[#ea580c]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Data langsung disinkronkan ke dasbor mitra
                        </span>
                        <button type="submit" class="w-full sm:w-auto bg-[#ea580c] hover:bg-[#c2410c] text-white px-7 py-3 rounded-full font-bold shadow-md hover:shadow-lg transition flex items-center justify-center gap-2 cursor-pointer active:scale-95">
                            <span>Kirim Estimasi ke Pemasok</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    </div>
                </form>
            </div>

            <!-- RIGHT: MANFAAT & ALUR KOLABORASI (4 COLS) -->
            <div class="lg:col-span-4 space-y-5">
                <div class="bg-white rounded-3xl p-6 border border-gray-200/80 shadow-sm space-y-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-gray-100">
                        <div class="w-8 h-8 rounded-xl bg-orange-50 text-[#ea580c] border border-orange-100 flex items-center justify-center font-bold text-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <h4 class="text-xs font-mono font-bold uppercase tracking-wider text-gray-800">
                            Keuntungan Perencanaan
                        </h4>
                    </div>

                    <div class="space-y-3.5 text-xs">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-xl bg-orange-50 text-[#ea580c] border border-orange-100 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h5 class="font-bold text-gray-900">Sinkronisasi Siklus Panen</h5>
                                <p class="text-[11px] text-gray-500 leading-relaxed mt-0.5">Petani/pemasok menyiapkan kapasitas kandang & panen sesuai kebutuhan bulanan Anda.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <div>
                                <h5 class="font-bold text-gray-900">Proteksi Stabilitas Harga Grosir</h5>
                                <p class="text-[11px] text-gray-500 leading-relaxed mt-0.5">Dapur terhindar dari lonjakan harga mendadak akibat kelangkaan bahan di pasar eceran.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div>
                                <h5 class="font-bold text-gray-900">Prioritas Pasokan Subuh</h5>
                                <p class="text-[11px] text-gray-500 leading-relaxed mt-0.5">Dapur Anda masuk antrean alokasi pengiriman pertama sebelum operasional dimulai.</p>
                            </div>
                        </div>
                    </div>

                    <!-- 3-Step Flow Checklist -->
                    <div class="pt-3 border-t border-gray-100 space-y-2">
                        <span class="text-[10px] font-mono font-bold uppercase text-gray-400 block tracking-wider">Alur Kerja Kolaborasi</span>
                        <div class="space-y-2 text-[11px] text-gray-600 font-medium">
                            <div class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-orange-100 text-[#ea580c] text-[10px] font-bold flex items-center justify-center shrink-0">1</span>
                                <span>UMKM kirim estimasi kebutuhan</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-orange-100 text-[#ea580c] text-[10px] font-bold flex items-center justify-center shrink-0">2</span>
                                <span>Pemasok konfirmasi kuota pasokan</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-orange-100 text-[#ea580c] text-[10px] font-bold flex items-center justify-center shrink-0">3</span>
                                <span>Pasokan terjamin aman & stabil</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- 3. SECTION: RIWAYAT & STATUS KESEPAKATAN SUPPLY PLANNING -->
        <div class="bg-white rounded-3xl border border-gray-200/80 shadow-sm p-6 sm:p-7 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 pb-4">
                <div>
                    <h3 class="font-extrabold text-lg text-gray-900 tracking-tight">Riwayat Estimasi &amp; Status Respon Mitra</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Pantau konfirmasi kesiapan panen dan alokasi kuota pasokan dari pemasok mitra untuk dapur Anda.</p>
                </div>
                <span class="text-xs font-mono font-bold text-gray-600 bg-gray-50 border border-gray-200 px-3.5 py-1.5 rounded-full self-start sm:self-auto">
                    Total: <span class="text-[#ea580c] font-black" x-text="supplyPlans.length"></span> Permintaan
                </span>
            </div>

            <!-- FILTER BAR: PENCARIAN & FILTERING PANJANG MENYATU SAMPAI KE SAMPING -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 w-full">
                <!-- Field Cari Full Width di Kolom Kiri -->
                <div class="md:col-span-5 lg:col-span-6 relative w-full">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input type="text"
                           x-model="planningSearchQuery"
                           placeholder="Cari komoditas bahan baku atau nama pemasok mitra..."
                           class="w-full pl-10 pr-4 py-2.5 rounded-2xl bg-[#f1f2f4] text-xs font-medium text-gray-800 placeholder-gray-400 border-0 focus:outline-none focus:ring-2 focus:ring-[#ea580c] transition" />
                </div>

                <!-- Filtering Buttons Full Width di Kolom Kanan Menempel ke Ujung Samping -->
                <div class="md:col-span-7 lg:col-span-6 grid grid-cols-4 gap-1.5 bg-[#f1f2f4] p-1.5 rounded-2xl text-xs font-bold w-full">
                    <button type="button"
                            @click="planningFilterStatus = 'all'"
                            :class="planningFilterStatus === 'all' ? 'bg-[#ea580c] text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-200/60'"
                            class="py-2 px-1 text-center rounded-xl transition text-[11px] cursor-pointer flex items-center justify-center gap-1">
                        <span>Semua</span>
                        <span class="opacity-80 font-mono text-[10px]" x-text="'(' + supplyPlans.length + ')'"></span>
                    </button>
                    <button type="button"
                            @click="planningFilterStatus = 'MENUNGGU_KONFIRMASI'"
                            :class="planningFilterStatus === 'MENUNGGU_KONFIRMASI' ? 'bg-[#ea580c] text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-200/60'"
                            class="py-2 px-1 text-center rounded-xl transition text-[11px] cursor-pointer flex items-center justify-center gap-1">
                        <span>Menunggu</span>
                        <span class="opacity-80 font-mono text-[10px]" x-text="'(' + planningCountPending + ')'"></span>
                    </button>
                    <button type="button"
                            @click="planningFilterStatus = 'DISETUJUI'"
                            :class="planningFilterStatus === 'DISETUJUI' ? 'bg-[#ea580c] text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-200/60'"
                            class="py-2 px-1 text-center rounded-xl transition text-[11px] cursor-pointer flex items-center justify-center gap-1">
                        <span>Disetujui</span>
                        <span class="opacity-80 font-mono text-[10px]" x-text="'(' + planningCountApproved + ')'"></span>
                    </button>
                    <button type="button"
                            @click="planningFilterStatus = 'DISESUAIKAN'"
                            :class="planningFilterStatus === 'DISESUAIKAN' ? 'bg-[#ea580c] text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-200/60'"
                            class="py-2 px-1 text-center rounded-xl transition text-[11px] cursor-pointer flex items-center justify-center gap-1">
                        <span>Disesuaikan</span>
                        <span class="opacity-80 font-mono text-[10px]" x-text="'(' + planningCountAdjusted + ')'"></span>
                    </button>
                </div>
            </div>

            <!-- TABLE -->
            <div class="overflow-x-auto rounded-2xl border border-gray-100">
                <table class="w-full text-xs text-left">
                    <thead class="bg-gray-50/80 font-mono text-gray-500 uppercase border-b border-gray-100 text-[11px]">
                        <tr>
                            <th class="p-3.5">ID & Waktu</th>
                            <th class="p-3.5">Mitra Pemasok</th>
                            <th class="p-3.5">Bahan Baku & Volume</th>
                            <th class="p-3.5">Periode Target</th>
                            <th class="p-3.5">Status Kolaborasi</th>
                            <th class="p-3.5">Catatan Respon Mitra</th>
                            <th class="p-3.5 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-medium">
                        <template x-for="plan in filteredSupplyPlans" :key="plan.id">
                            <tr class="hover:bg-orange-50/20 transition">
                                <td class="p-3.5 whitespace-nowrap">
                                    <span class="font-mono font-extrabold text-[#ea580c] block" x-text="plan.id"></span>
                                    <span class="text-[10px] text-gray-400 block" x-text="plan.created_at || '-'"></span>
                                </td>
                                <td class="p-3.5 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-orange-50 text-[#ea580c] border border-orange-100 flex items-center justify-center shrink-0">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                        </div>
                                        <span class="font-bold text-gray-900" x-text="plan.supplier"></span>
                                    </div>
                                </td>
                                <td class="p-3.5 whitespace-nowrap">
                                    <span class="font-bold text-gray-900 block" x-text="plan.commodity"></span>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-gray-600 font-mono text-[11px]" x-text="'Permintaan: ' + plan.volume + ' ' + plan.unit"></span>
                                        <template x-if="plan.confirmed_volume">
                                            <span class="text-emerald-700 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded-md font-bold text-[10px] font-mono" x-text="'Disetujui: ' + plan.confirmed_volume + ' ' + plan.unit"></span>
                                        </template>
                                    </div>
                                </td>
                                <td class="p-3.5 font-bold text-gray-800 whitespace-nowrap">
                                    <span class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded-full text-[11px] font-mono inline-flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-[#ea580c]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span x-text="plan.targetMonth"></span>
                                    </span>
                                </td>
                                <td class="p-3.5 whitespace-nowrap">
                                    <template x-if="plan.status === 'DISETUJUI'">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                            <span>Disetujui Penuh</span>
                                        </span>
                                    </template>
                                    <template x-if="plan.status === 'DISESUAIKAN'">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold inline-flex items-center gap-1.5 bg-blue-50 text-blue-700 border border-blue-200">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                            <span>Disesuaikan Pemasok</span>
                                        </span>
                                    </template>
                                    <template x-if="plan.status === 'DITOLAK'">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold inline-flex items-center gap-1.5 bg-rose-50 text-rose-700 border border-rose-200">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                                            <span>Kapasitas Penuh</span>
                                        </span>
                                    </template>
                                    <template x-if="plan.status === 'MENUNGGU_KONFIRMASI' || plan.status === 'DALAM_REVIEW'">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold inline-flex items-center gap-1.5 bg-amber-50 text-amber-700 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            <span>Menunggu Konfirmasi</span>
                                        </span>
                                    </template>
                                </td>
                                <td class="p-3.5 text-gray-600 text-[11px] max-w-xs">
                                    <template x-if="plan.supplier_notes">
                                        <div class="bg-orange-50/60 border border-orange-100 rounded-xl p-2 text-gray-800 space-y-0.5">
                                            <span class="font-bold text-[9px] text-orange-600 uppercase font-mono block">Respon Pemasok:</span>
                                            <span x-text="plan.supplier_notes"></span>
                                        </div>
                                    </template>
                                    <template x-if="!plan.supplier_notes">
                                        <span class="italic text-gray-400" x-text="plan.note || 'Menunggu peninjauan kuota panen/stok mitra.'"></span>
                                    </template>
                                </td>
                                <td class="p-3.5 text-center">
                                    <template x-if="plan.can_delete && plan.uuid">
                                        <form :action="'{{ url('/umkm/supply-planning') }}/' + plan.uuid" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan estimasi kebutuhan ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-red-500 hover:text-white hover:bg-red-500 border border-red-200 px-3 py-1 rounded-full font-bold transition inline-flex items-center gap-1 shadow-xs cursor-pointer">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                                                <span>Batalkan</span>
                                            </button>
                                        </form>
                                    </template>
                                    <template x-if="!plan.can_delete || !plan.uuid">
                                        <span class="text-gray-300 text-xs font-mono">—</span>
                                    </template>
                                </td>
                            </tr>
                        </template>

                        <template x-if="filteredSupplyPlans.length === 0">
                            <tr>
                                <td colspan="7" class="p-10 text-center text-gray-500">
                                    <div class="py-8 space-y-3">
                                        <div class="w-14 h-14 rounded-2xl bg-orange-50 text-[#ea580c] border border-orange-100 flex items-center justify-center mx-auto">
                                            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <h4 class="font-extrabold text-gray-800 text-sm">Tidak Ada Data Estimasi Pasokan</h4>
                                        <p class="text-xs text-gray-500 max-w-md mx-auto">
                                            Gunakan formulir di atas untuk menyampaikan proyeksi kebutuhan bahan baku dapur Anda kepada mitra pemasok.
                                        </p>
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
            <!-- 1. Pemesanan Otomatis -->
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

            <!-- 2. Persetujuan Backorder -->
            <button 
                type="button"
                @click="orderSubTab = 'backorder'"
                class="pb-3 relative transition flex items-center gap-2 cursor-pointer"
                :class="orderSubTab === 'backorder' ? 'text-gray-900 font-extrabold' : 'text-gray-500 hover:text-gray-900'">
                <span>Persetujuan Backorder</span>
                <template x-if="pendingBackorderApprovalCount > 0">
                    <span class="bg-rose-500 text-white text-[10px] px-2 py-0.5 rounded-full font-mono font-bold shadow-xs animate-pulse"
                          x-text="pendingBackorderApprovalCount + ' Pengajuan'">
                    </span>
                </template>
                <template x-if="pendingBackorderApprovalCount === 0 && pendingBackorderCount > 0">
                    <span class="bg-amber-400 text-amber-950 text-[10px] px-2 py-0.5 rounded-full font-mono font-black shadow-xs"
                          x-text="pendingBackorderCount">
                    </span>
                </template>
                <template x-if="orderSubTab === 'backorder'">
                    <span class="absolute bottom-0 left-0 w-full h-1 bg-[#ea580c] rounded-t-full"></span>
                </template>
            </button>

            <!-- 3. Konfirmasi Pesanan -->
            <button 
                type="button"
                @click="orderSubTab = 'confirm'"
                class="pb-3 relative transition flex items-center gap-2 cursor-pointer"
                :class="orderSubTab === 'confirm' ? 'text-gray-900 font-extrabold' : 'text-gray-500 hover:text-gray-900'">
                <span>Konfirmasi Pesanan</span>
                <template x-if="pendingValidationCount > 0">
                    <span class="bg-[#ea580c] text-white text-[10px] px-2 py-0.5 rounded-full font-mono font-bold"
                          x-text="pendingValidationCount">
                    </span>
                </template>
                <template x-if="orderSubTab === 'confirm'">
                    <span class="absolute bottom-0 left-0 w-full h-1 bg-[#ea580c] rounded-t-full"></span>
                </template>
            </button>

            <!-- 4. Pembayaran Midtrans -->
            <button 
                type="button"
                @click="orderSubTab = 'payment'"
                class="pb-3 relative transition flex items-center gap-2 cursor-pointer"
                :class="orderSubTab === 'payment' ? 'text-gray-900 font-extrabold' : 'text-gray-500 hover:text-gray-900'">
                <span>Pembayaran Midtrans</span>
                <template x-if="pendingPaymentCount > 0">
                    <span class="bg-amber-500 text-white text-[10px] px-2 py-0.5 rounded-full font-mono font-bold"
                          x-text="pendingPaymentCount">
                    </span>
                </template>
                <template x-if="orderSubTab === 'payment'">
                    <span class="absolute bottom-0 left-0 w-full h-1 bg-[#ea580c] rounded-t-full"></span>
                </template>
            </button>

            <!-- 5. Rating & Ulasan -->
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

        <!-- SUB-TAB 2: KONFIRMASI PENERIMAAN BARANG (TERHUBUNG DATABASE) -->
        <div x-show="orderSubTab === 'confirm'" class="bg-white rounded-3xl border border-gray-100 overflow-hidden p-6 space-y-4 shadow-sm">
            <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                <div>
                    <h3 class="font-bold text-lg text-gray-900">Validasi & Konfirmasi Kedatangan Barang (Batch 1)</h3>
                    <p class="text-xs text-gray-500">Periksa kuantitas dan mutu bahan baku saat tiba di dapur. Upload foto bukti penerimaan fisik untuk mengkonfirmasi.</p>
                </div>
            </div>

            @php
                $umkmProfile = auth()->user()?->umkmProfile;
                $dbOrders = $umkmProfile
                    ? \App\Models\Order::with(['partnership.supplier', 'items.supplierProduct.commodity', 'shipmentConfirmations'])
                        ->whereHas('partnership', fn($q) => $q->where('umkm_id', $umkmProfile->id))
                        ->whereIn('order_status', [
                            'MENUNGGU_KONFIRMASI_BARANG',
                            'DIKIRIM',
                            'shipped',
                            'partially_shipped',
                            'backorder_pending',
                            'BARANG_DITERIMA',
                            'completed',
                            'completed_partial',
                        ])
                        ->latest()->get()
                    : collect();
            @endphp

            @if($dbOrders->isEmpty())
                <div class="py-14 text-center space-y-3">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-orange-50 border border-orange-100/80 flex items-center justify-center text-[#ea580c] shadow-xs">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <p class="font-bold text-gray-800 text-sm">Belum ada pesanan yang perlu dikonfirmasi</p>
                        <p class="text-xs text-gray-400">Pesanan yang dikirim oleh pemasok akan muncul di sini untuk divalidasi.</p>
                    </div>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-gray-50 font-mono text-gray-500 uppercase border-b border-gray-100">
                            <tr>
                                <th class="p-3.5">ID Pesanan</th>
                                <th class="p-3.5">Pemasok Mitra</th>
                                <th class="p-3.5">Detail Komoditas & Pemenuhan</th>
                                <th class="p-3.5">Total Tagihan</th>
                                <th class="p-3.5">Status Pengiriman</th>
                                <th class="p-3.5">Bukti Foto</th>
                                <th class="p-3.5 text-center">Aksi Konfirmasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 font-medium">
                            @foreach($dbOrders as $dbOrder)
                                @php
                                    $firstItem = $dbOrder->items->first();
                                    $totalQty = (float) $dbOrder->items->sum('quantity');
                                    $deliveredQty = (float) $dbOrder->items->sum('quantity_delivered');
                                    $remainingQty = (float) $dbOrder->items->sum('quantity_remaining');
                                    $unit = $firstItem?->supplierProduct?->commodity?->unit ?? 'kg';
                                    $batch1 = $dbOrder->shipmentConfirmations->firstWhere('shipment_batch', 1) ?? $dbOrder->shipmentConfirmation;
                                    $batch2 = $dbOrder->shipmentConfirmations->firstWhere('shipment_batch', 2);
                                    $isBatch1Confirmed = $batch1 && $batch1->received_at !== null;
                                    $isBatch2Confirmed = $batch2 && $batch2->received_at !== null;
                                    $isBatch2Shipped = $batch2 && $batch2->shipped_at !== null;
                                @endphp
                                <tr class="hover:bg-gray-50/50 transition">
                                    <td class="p-3.5 font-mono font-bold text-[#ea580c] whitespace-nowrap">
                                        #ORD-{{ strtoupper(substr($dbOrder->id, 0, 8)) }}
                                    </td>
                                    <td class="p-3.5 font-bold text-gray-900">
                                        {{ $dbOrder->partnership?->supplier?->company_name ?? 'Pemasok Mitra' }}
                                    </td>
                                    <td class="p-3.5 space-y-1">
                                        @foreach($dbOrder->items as $item)
                                            <span class="font-bold text-gray-900 block text-xs">
                                                {{ $item->supplierProduct?->commodity?->name ?? 'Bahan Baku' }}
                                            </span>
                                        @endforeach

                                        <!-- LABEL RINCIAN PEMENUHAN (SPLIT SHIPMENT / BACKORDER) -->
                                        @if($dbOrder->order_status === 'cancelled')
                                            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-2.5 py-1 rounded-lg text-[11px] font-mono leading-tight">
                                                <span>✕ Pesanan Dibatalkan: Stok gudang pemasok kosong.</span>
                                            </div>
                                        @elseif($batch1 && $batch1->backorder_action === 'create_backorder')
                                            <div class="bg-orange-50 border border-orange-200 text-orange-950 px-2.5 py-1 rounded-lg text-[11px] font-mono leading-tight">
                                                <span>Terkirim Parsial: <strong>{{ number_format($deliveredQty, 0) }}/{{ number_format($totalQty, 0) }} {{ $unit }}</strong></span>
                                                <span class="text-[#ea580c] block font-semibold">— Sisa {{ number_format($remainingQty, 0) }} {{ $unit }} dalam antrean Backorder Pemasok</span>
                                            </div>
                                        @elseif($batch1 && $batch1->backorder_action === 'no_backorder')
                                            <div class="bg-rose-50 border border-rose-200 text-rose-950 px-2.5 py-1 rounded-lg text-[11px] font-mono leading-tight">
                                                <span>Terkirim Parsial: <strong>{{ number_format($deliveredQty, 0) }}/{{ number_format($totalQty, 0) }} {{ $unit }}</strong></span>
                                                <span class="text-rose-700 block font-semibold">— Sisa {{ number_format(max(0, $totalQty - $deliveredQty), 0) }} {{ $unit }} Dibatalkan Pemasok (Total Tagihan Disesuaikan)</span>
                                            </div>
                                        @else
                                            <div class="text-gray-500 text-[11px]">
                                                Pengiriman Penuh: <strong>{{ number_format($totalQty, 0) }} {{ $unit }}</strong>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="p-3.5 font-bold text-gray-900 whitespace-nowrap">
                                        Rp {{ number_format($dbOrder->total_amount, 0, ',', '.') }}
                                    </td>
                                    <td class="p-3.5">
                                        @if($dbOrder->order_status === 'cancelled')
                                            <span class="bg-rose-100 text-rose-800 px-2.5 py-1 rounded-full text-[10px] font-bold">
                                                ✕ Dibatalkan Pemasok (Stok Kosong)
                                            </span>
                                        @elseif(in_array($dbOrder->order_status, ['pending', 'confirmed']))
                                            <span class="bg-amber-100 text-amber-800 px-2.5 py-1 rounded-full text-[10px] font-bold inline-flex items-center gap-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                Menunggu Pemasok Cek Stok & Kirim
                                            </span>
                                        @elseif($batch1 && $batch1->backorder_action === 'create_backorder')
                                            {{-- Skenario Backorder --}}
                                            @if($batch1->shipped_at === null)
                                                @if($batch1->backorder_approval_status === 'pending' || $dbOrder->order_status === 'waiting_backorder_approval')
                                                    <span class="bg-amber-100 text-amber-900 border border-amber-300 px-2.5 py-1 rounded-full text-[10px] font-bold inline-flex items-center gap-1">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                        Menunggu Persetujuan Backorder Anda
                                                    </span>
                                                @else
                                                    <span class="bg-purple-100 text-purple-800 px-2.5 py-1 rounded-full text-[10px] font-bold inline-flex items-center gap-1">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-purple-500 animate-pulse"></span>
                                                        Disetujui — Menunggu Pemasok Berangkatkan Barang
                                                    </span>
                                                @endif
                                            @elseif(! $isBatch1Confirmed)
                                                <span class="bg-sky-100 text-sky-800 px-2.5 py-1 rounded-full text-[10px] font-bold inline-flex items-center gap-1">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-500 animate-ping"></span>
                                                    Kloter 1 Sedang Dikirim ({{ number_format($deliveredQty, 0) }} {{ $unit }})
                                                </span>
                                            @elseif($isBatch2Shipped && ! $isBatch2Confirmed)
                                                <span class="bg-sky-100 text-sky-900 border border-sky-300 px-2.5 py-1 rounded-full text-[10px] font-bold inline-flex items-center gap-1 animate-pulse">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-600"></span>
                                                    Kloter 2 (Sisa Backorder) Sedang Dikirim!
                                                </span>
                                            @elseif($isBatch2Confirmed)
                                                <span class="bg-emerald-100 text-emerald-800 px-2.5 py-1 rounded-full text-[10px] font-bold inline-flex items-center gap-1">
                                                    <svg class="w-3 h-3 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    <span>Selesai Lengkap (Kloter 1 & 2 Diterima)</span>
                                                </span>
                                            @else
                                                <span class="bg-purple-100 text-purple-800 px-2.5 py-1 rounded-full text-[10px] font-bold inline-flex items-center gap-1">
                                                    <svg class="w-3 h-3 text-purple-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    <span>Kloter 1 Diterima (Menunggu Sisa Kloter 2)</span>
                                                </span>
                                            @endif
                                        @elseif($batch1 && $batch1->backorder_action === 'no_backorder')
                                            {{-- Skenario No Backorder --}}
                                            @if(! $isBatch1Confirmed)
                                                <span class="bg-sky-100 text-sky-800 px-2.5 py-1 rounded-full text-[10px] font-bold inline-flex items-center gap-1">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-500 animate-ping"></span>
                                                    Armada Sedang Mengirim (Sesuai Stok: {{ number_format($deliveredQty, 0) }} {{ $unit }})
                                                </span>
                                            @else
                                                <span class="bg-stone-100 text-stone-800 px-2.5 py-1 rounded-full text-[10px] font-bold inline-flex items-center gap-1">
                                                    <svg class="w-3 h-3 text-stone-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    <span>Diterima (Selesai Sebagian)</span>
                                                </span>
                                            @endif
                                        @else
                                            {{-- Skenario Kirim Penuh / Biasa --}}
                                            @if(! $isBatch1Confirmed)
                                                <span class="bg-sky-100 text-sky-800 px-2.5 py-1 rounded-full text-[10px] font-bold inline-flex items-center gap-1">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-500 animate-ping"></span>
                                                    Armada Pemasok Sedang Mengirim
                                                </span>
                                            @else
                                                <span class="bg-emerald-100 text-emerald-800 px-2.5 py-1 rounded-full text-[10px] font-bold inline-flex items-center gap-1">
                                                    <svg class="w-3 h-3 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    <span>Barang Diterima Penuh</span>
                                                </span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="p-3.5">
                                        <div class="flex items-center gap-1.5">
                                            @if($batch1?->proof_photo_url)
                                                <a href="{{ $batch1->proof_photo_url }}" target="_blank" title="Bukti Foto Kloter 1">
                                                    <img src="{{ $batch1->proof_photo_url }}"
                                                         alt="Bukti Kloter 1"
                                                         class="w-10 h-10 object-cover rounded-xl border border-gray-200 hover:scale-110 transition cursor-pointer shadow-xs">
                                                </a>
                                            @endif
                                            @if($batch2?->proof_photo_url)
                                                <a href="{{ $batch2->proof_photo_url }}" target="_blank" title="Bukti Foto Kloter 2 (Sisa Backorder)">
                                                    <img src="{{ $batch2->proof_photo_url }}"
                                                         alt="Bukti Kloter 2"
                                                         class="w-10 h-10 object-cover rounded-xl border-2 border-amber-400 hover:scale-110 transition cursor-pointer shadow-xs">
                                                </a>
                                            @endif
                                            @if(! $batch1?->proof_photo_url && ! $batch2?->proof_photo_url)
                                                <span class="text-gray-300 text-[11px]">—</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="p-3.5 text-center">
                                        @if($dbOrder->order_status === 'cancelled')
                                            <span class="text-xs text-rose-500 font-bold">Pesanan Batal</span>
                                        @elseif(in_array($dbOrder->order_status, ['pending', 'confirmed']))
                                            <span class="text-xs text-gray-400 font-medium italic">Menunggu Armada Dikirim</span>
                                        @elseif($batch1 && $batch1->shipped_at === null)
                                            <span class="text-xs text-amber-600 font-medium italic">
                                                @if($batch1->backorder_approval_status === 'pending' || $dbOrder->order_status === 'waiting_backorder_approval')
                                                    <button type="button" @click="orderSubTab = 'backorder'" class="text-[#ea580c] font-bold underline cursor-pointer">
                                                        Tinjau di Tab Backorder →
                                                    </button>
                                                @else
                                                    Pemasok Sedang Siapkan Barang
                                                @endif
                                            </span>
                                        @elseif(! $isBatch1Confirmed)
                                            {{-- Konfirmasi Kloter 1 (Bisa Pesanan Biasa, No Backorder, atau Kloter 1 Backorder) --}}
                                            <button
                                                type="button"
                                                @click="confirmingOrderId = '{{ $dbOrder->id }}'; confirmPhotoPreview = null; confirmNotes = '';"
                                                class="bg-[#ea580c] hover:bg-[#c2410c] text-white px-4 py-2 rounded-full font-bold text-[11px] shadow-xs transition cursor-pointer whitespace-nowrap inline-flex items-center gap-1.5">
                                                @if($batch1 && $batch1->backorder_action === 'create_backorder')
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg><span>Konfirmasi Terima Kloter 1</span>
                                                @else
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg><span>Konfirmasi Penerimaan Fisik</span>
                                                @endif
                                            </button>
                                        @elseif($batch1 && $batch1->backorder_action === 'create_backorder' && $isBatch2Shipped && ! $isBatch2Confirmed)
                                            {{-- Kloter 1 sudah diterima, Kloter 2 (Sisa Backorder) tiba! Konfirmasi Kloter 2 ada di sini! --}}
                                            <button
                                                type="button"
                                                @click="confirmingBackorderId = '{{ $dbOrder->id }}'; confirmBackorderPhotoPreview = null; confirmBackorderNotes = '';"
                                                class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-full font-bold text-[11px] shadow-xs transition cursor-pointer whitespace-nowrap animate-pulse inline-flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg><span>Konfirmasi Sisa Backorder (Kloter 2)</span>
                                            </button>
                                        @else
                                            {{-- Sudah Selesai Diterima --}}
                                            <div class="space-y-1">
                                                <span class="text-emerald-700 font-bold text-[11px] inline-flex items-center gap-1">
                                                    <svg class="w-3 h-3 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    @if($isBatch2Confirmed)
                                                        <span>Selesai Lengkap (Kloter 1 & 2)</span>
                                                    @elseif($batch1 && $batch1->backorder_action === 'create_backorder')
                                                        <span>Kloter 1 Diterima</span>
                                                    @else
                                                        <span>Barang Diterima</span>
                                                    @endif
                                                </span>
                                                <button type="button"
                                                        @click="orderSubTab = 'payment'"
                                                        class="bg-orange-50 hover:bg-orange-100 text-[#ea580c] px-3 py-1 rounded-full font-bold text-[10px] block mx-auto border border-orange-200 cursor-pointer">
                                                    Bayar Tagihan Invoice →
                                                </button>
                                                @if($batch1 && $batch1->backorder_action === 'create_backorder' && ! $isBatch2Shipped)
                                                    <span class="text-[10px] text-gray-400 block font-sans">
                                                        Menunggu pengiriman sisa kloter 2
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- MODAL KONFIRMASI PENERIMAAN BATCH 1 + UPLOAD FOTO (TERHUBUNG DATABASE) -->
        <div x-cloak x-show="confirmingOrderId" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div @click.away="confirmingOrderId = null" class="bg-white max-w-lg w-full rounded-[2rem] p-6 space-y-5 shadow-2xl border border-gray-100">
                <div class="flex justify-between items-start border-b border-gray-100 pb-4">
                    <div>
                        <span class="text-xs font-bold text-[#ea580c] uppercase font-mono tracking-wider">KONFIRMASI PENERIMAAN FISIK BATCH 1</span>
                        <h3 class="text-xl font-bold text-gray-900 mt-1">Upload Bukti Penerimaan Dapur</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Foto barang kloter pertama yang sudah tiba di dapur Anda sebagai bukti konfirmasi resmi.</p>
                    </div>
                    <button type="button" @click="confirmingOrderId = null" class="text-gray-400 hover:text-gray-700 cursor-pointer p-1 rounded-lg hover:bg-gray-100 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>

                <template x-if="confirmingOrderId">
                    <form :action="`/umkm/pemesanan/${confirmingOrderId}/konfirmasi`" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        <!-- Preview Foto -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-2">Foto Bukti Penerimaan Barang Batch 1 <span class="text-rose-500">*</span></label>
                            <div class="border-2 border-dashed border-gray-200 rounded-2xl p-4 text-center cursor-pointer hover:border-[#ea580c] transition"
                                 @click="$refs.photoInput.click()">
                                <template x-if="!confirmPhotoPreview">
                                    <div class="space-y-2 py-3">
                                        <div class="w-11 h-11 mx-auto rounded-xl bg-orange-50 border border-orange-100 flex items-center justify-center text-[#ea580c]">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </div>
                                        <p class="text-xs text-gray-600 font-bold">Klik untuk pilih foto atau seret ke sini</p>
                                        <p class="text-[11px] text-gray-400">JPG, PNG, WEBP — Maks. 5MB</p>
                                    </div>
                                </template>
                                <template x-if="confirmPhotoPreview">
                                    <img :src="confirmPhotoPreview" class="max-h-48 mx-auto rounded-xl object-contain" alt="Preview">
                                </template>
                            </div>
                            <input
                                type="file"
                                name="proof_photo"
                                required
                                accept="image/*"
                                x-ref="photoInput"
                                class="hidden"
                                @change="onConfirmPhotoChange($event)">
                        </div>

                        <!-- Catatan Konfirmasi -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Catatan Kondisi Kedatangan (Opsional)</label>
                            <textarea
                                name="confirmation_notes"
                                rows="3"
                                x-model="confirmNotes"
                                placeholder="Contoh: Kondisi bahan baku segar, kuantitas kloter 1 sesuai timbangan dapur."
                                class="w-full px-4 py-2.5 rounded-xl bg-[#f1f2f4] text-gray-900 text-xs font-medium placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c] border-0">
                            </textarea>
                        </div>

                        <div class="flex gap-3 pt-2">
                            <button type="button" @click="confirmingOrderId = null"
                                class="w-1/2 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-full font-bold text-xs transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit"
                                class="w-1/2 bg-[#ea580c] hover:bg-[#c2410c] text-white py-3 rounded-full font-bold text-xs shadow-sm transition cursor-pointer">
                                ✓ Konfirmasi Batch 1
                            </button>
                        </div>
                    </form>
                </template>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- SUB-TAB 3: PESANAN BACKORDER (TAB BARU KHUSUS UMKM)       -->
        <!-- ======================================================== -->
        <div x-show="orderSubTab === 'backorder'" class="bg-white rounded-3xl border border-gray-100 overflow-hidden p-6 space-y-4 shadow-sm">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-3 border-b border-gray-100 pb-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-mono font-extrabold text-amber-700 uppercase tracking-widest bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200">
                            SPLIT SHIPMENT / BACKORDER
                        </span>
                    </div>
                    <h3 class="font-bold text-lg text-gray-900 mt-0.5">Pemantauan Pengiriman Sisa Pasokan (Backorder)</h3>
                    <p class="text-xs text-gray-500">
                        Pesanan di bawah ini dikirim secara bertahap oleh pemasok mitra. Konfirmasi kedatangan fisik Batch 2 saat sisa barang tiba di dapur.
                    </p>
                </div>
                <span class="bg-amber-50 border border-amber-200 text-amber-900 font-mono font-bold text-xs px-3.5 py-1.5 rounded-full w-fit">
                    {{ $pendingBackorderCount }} Antrean Backorder
                </span>
            </div>

            @php
                $backorderOrders = $backorderOrders ?? ($umkmProfile
                    ? \App\Models\Order::with(['partnership.supplier', 'items.supplierProduct.commodity', 'shipmentConfirmations', 'payments'])
                        ->whereHas('partnership', fn($q) => $q->where('umkm_id', $umkmProfile->id))
                        ->where(function ($q) {
                            $q->whereIn('order_status', [\App\Models\Order::STATUS_BACKORDER_PENDING, \App\Models\Order::STATUS_WAITING_BACKORDER_APPROVAL])
                                ->orWhere(function ($oq) {
                                    $oq->whereIn('order_status', ['shipped', 'partially_shipped', \App\Models\Order::STATUS_COMPLETED_PARTIAL, \App\Models\Order::STATUS_CANCELLED])
                                        ->whereHas('shipmentConfirmations', function ($sq) {
                                            $sq->where(function ($bsq) {
                                                $bsq->whereIn('backorder_action', ['create_backorder', 'no_backorder'])
                                                    ->orWhereIn('backorder_approval_status', ['pending', 'approved', 'rejected']);
                                            });
                                        });
                                });
                        })
                        ->latest('created_at')->get()
                    : collect());

                $countPendingApproval = $backorderOrders->filter(function($o) {
                    $b1 = $o->shipmentConfirmations->firstWhere('shipment_batch', 1);
                    return $o->order_status === \App\Models\Order::STATUS_WAITING_BACKORDER_APPROVAL || ($b1 && $b1->backorder_approval_status === 'pending');
                })->count();

                $countApproved = $backorderOrders->filter(function($o) {
                    $b1 = $o->shipmentConfirmations->firstWhere('shipment_batch', 1);
                    return $b1 && $b1->backorder_approval_status === 'approved';
                })->count();

                $countRejected = $backorderOrders->filter(function($o) {
                    $b1 = $o->shipmentConfirmations->firstWhere('shipment_batch', 1);
                    return $b1 && $b1->backorder_approval_status === 'rejected';
                })->count();
            @endphp

            <!-- QUICK FILTER STATUS BACKORDER -->
            <div class="flex flex-wrap items-center gap-2 pt-1 pb-1">
                <button type="button"
                        @click="backorderFilter = 'all'"
                        class="px-3.5 py-1.5 rounded-full text-xs font-bold transition cursor-pointer"
                        :class="backorderFilter === 'all' ? 'bg-[#ea580c] text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                    Semua ({{ $backorderOrders->count() }})
                </button>
                <button type="button"
                        @click="backorderFilter = 'pending'"
                        class="px-3.5 py-1.5 rounded-full text-xs font-bold transition cursor-pointer flex items-center gap-1.5"
                        :class="backorderFilter === 'pending' ? 'bg-amber-500 text-white shadow-xs' : 'bg-amber-50 text-amber-900 border border-amber-200 hover:bg-amber-100'">
                    <span>⏳ Menunggu Keputusan</span>
                    @if($countPendingApproval > 0)
                        <span class="bg-rose-500 text-white text-[10px] px-1.5 py-0.5 rounded-full font-bold">
                            {{ $countPendingApproval }}
                        </span>
                    @endif
                </button>
                <button type="button"
                        @click="backorderFilter = 'approved'"
                        class="px-3.5 py-1.5 rounded-full text-xs font-bold transition cursor-pointer"
                        :class="backorderFilter === 'approved' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-emerald-50 text-emerald-900 border border-emerald-200 hover:bg-emerald-100'">
                    ✓ Disetujui ({{ $countApproved }})
                </button>
                <button type="button"
                        @click="backorderFilter = 'rejected'"
                        class="px-3.5 py-1.5 rounded-full text-xs font-bold transition cursor-pointer"
                        :class="backorderFilter === 'rejected' ? 'bg-rose-600 text-white shadow-xs' : 'bg-rose-50 text-rose-800 border border-rose-200 hover:bg-rose-100'">
                    ✕ Ditolak ({{ $countRejected }})
                </button>
            </div>

            @if($backorderOrders->isEmpty())
                <div class="py-14 text-center space-y-3">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600 shadow-xs">
                        <span>📦</span>
                    </div>
                    <div class="space-y-1">
                        <p class="font-bold text-gray-800 text-sm">Tidak Ada Pesanan Dalam Status Backorder</p>
                        <p class="text-xs text-gray-400">Semua pesanan Anda terpenuhi penuh atau tidak ada sisa pasokan yang tertunda.</p>
                    </div>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($backorderOrders as $boOrder)
                        @php
                            $firstItem = $boOrder->items->first();
                            $totalQty = (float) $boOrder->items->sum('quantity');
                            $deliveredQty = (float) $boOrder->items->sum('quantity_delivered');
                            $remainingQty = (float) $boOrder->items->sum('quantity_remaining');
                            $unit = $firstItem?->supplierProduct?->commodity?->unit ?? 'kg';
                            $batch1 = $boOrder->shipmentConfirmations->firstWhere('shipment_batch', 1);
                            $batch2 = $boOrder->shipmentConfirmations->firstWhere('shipment_batch', 2);
                            $isBatch2Shipped = $batch2 && $batch2->shipped_at !== null;
                            $isBatch2Received = $batch2 && $batch2->received_at !== null;
                            $approvalStatus = $batch1?->backorder_approval_status ?? ($boOrder->order_status === 'waiting_backorder_approval' ? 'pending' : 'approved');
                            $estimatedDateText = $batch1?->backorder_estimated_delivery_date
                                ? \Carbon\Carbon::parse($batch1->backorder_estimated_delivery_date)->translatedFormat('l, d F Y')
                                : 'Sesuai jadwal restock pemasok';
                        @endphp

                        <div x-show="backorderFilter === 'all' || backorderFilter === '{{ $approvalStatus }}'"
                             class="bg-gray-50/70 rounded-3xl border border-gray-200/80 p-5 space-y-4 transition hover:border-amber-300">
                            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2 border-b border-gray-200/60 pb-3">
                                <div class="flex items-center gap-3">
                                    <span class="font-mono font-bold text-xs text-[#ea580c]">
                                        #ORD-{{ strtoupper(substr($boOrder->id, 0, 8)) }}
                                    </span>
                                    <span class="text-xs font-bold text-gray-900">
                                        {{ $boOrder->partnership?->supplier?->company_name ?? 'Pemasok Mitra' }}
                                    </span>
                                </div>

                                <!-- STATUS INDIKATOR -->
                                <div>
                                    @if($isBatch2Received)
                                        <span class="bg-emerald-100 text-emerald-800 text-[11px] font-bold px-3 py-1 rounded-full inline-flex items-center gap-1.5">
                                            <svg class="w-3 h-3 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            <span>Sisa Backorder Telah Diterima Penuh</span>
                                        </span>
                                    @elseif($approvalStatus === 'pending' || $boOrder->order_status === 'waiting_backorder_approval')
                                        <span class="bg-amber-100 text-amber-900 border border-amber-300 text-[11px] font-bold px-3 py-1 rounded-full inline-flex items-center gap-1.5 animate-pulse">
                                            <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                            <span>Menunggu Persetujuan Anda</span>
                                        </span>
                                    @elseif($approvalStatus === 'rejected')
                                        <span class="bg-rose-100 text-rose-800 text-[11px] font-bold px-3 py-1 rounded-full inline-flex items-center gap-1.5">
                                            <svg class="w-3 h-3 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            <span>Pengajuan Ditolak ({{ $batch1?->backorder_action === 'no_backorder' ? 'Pesanan Dibatalkan' : 'Selesai Sebagian' }})
                                        </span>
                                    @elseif($isBatch2Shipped)
                                        <span class="bg-sky-100 text-sky-800 text-[11px] font-bold px-3 py-1 rounded-full inline-flex items-center gap-1.5 animate-pulse">
                                            <svg class="w-3.5 h-3.5 text-sky-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                                            <span>Pengiriman Sisa Backorder Sedang Menuju Dapur</span>
                                        </span>
                                    @elseif($batch1 && $batch1->shipped_at === null)
                                        <span class="bg-purple-100 text-purple-900 border border-purple-200 text-[11px] font-bold px-3 py-1 rounded-full inline-flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-purple-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span>Disetujui — Menunggu Pemasok Memberangkatkan Barang</span>
                                        </span>
                                    @elseif($batch1 && $batch1->backorder_action === 'no_backorder')
                                        <span class="bg-emerald-100 text-emerald-800 text-[11px] font-bold px-3 py-1 rounded-full inline-flex items-center gap-1.5">
                                            <svg class="w-3 h-3 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            <span>Barang Terkirim — Sisa Ditiadakan (No Backorder)</span>
                                        </span>
                                    @else
                                        <span class="bg-amber-100 text-amber-900 text-[11px] font-bold px-3 py-1 rounded-full inline-flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-amber-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span>Kloter 1 Terkirim — Menunggu Jadwal Kirim Sisa Backorder ({{ number_format($remainingQty, 0) }} {{ $unit }})
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- KOTAK PERSETUJUAN & KEPUTUSAN BACKORDER / NO BACKORDER (ALUR 2) -->
                            @if($approvalStatus === 'pending' || $boOrder->order_status === 'waiting_backorder_approval')
                                <div class="bg-gradient-to-r from-amber-50 to-orange-50 border-2 border-amber-300 rounded-2xl p-4.5 space-y-3.5 shadow-xs">
                                    <div class="flex items-start gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-xs">
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        </div>
                                        <div class="space-y-1 flex-1">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <span class="text-[10px] font-mono font-extrabold uppercase tracking-widest text-amber-900 bg-amber-200/70 px-2.5 py-0.5 rounded-full">
                                                    {{ $batch1?->backorder_action === 'no_backorder' ? 'PENGAJUAN PEMENUHAN SEBAGIAN (TANPA BACKORDER)' : 'PENGAJUAN BACKORDER DARI PEMASOK' }}
                                                </span>
                                                @if($batch1?->backorder_action !== 'no_backorder')
                                                    <span class="text-xs font-mono font-bold text-amber-900">
                                                        Janji Kirim: <strong>{{ $estimatedDateText }}</strong>
                                                    </span>
                                                @else
                                                    <span class="text-xs font-mono font-bold text-rose-700">
                                                        Sisa Pesanan Ditiadakan (Stok Habis)
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="text-xs text-gray-800 leading-relaxed pt-0.5">
                                                @if($batch1?->backorder_action === 'no_backorder')
                                                    Pemasok hanya sanggup mengirimkan <strong class="text-emerald-700 font-extrabold">{{ number_format($deliveredQty, 0) }} {{ $unit }}</strong> dan <strong class="text-rose-700 font-extrabold">tidak dapat mengirimkan sisa {{ number_format($remainingQty, 0) }} {{ $unit }}</strong> karena stok gudang habis. Jika Anda setuju, sisa pesanan ditiadakan, tagihan disesuaikan hanya untuk {{ number_format($deliveredQty, 0) }} {{ $unit }}, dan pemasok akan memberangkatkan barang.
                                                @else
                                                    Pemasok hanya dapat mengirimkan <strong class="text-emerald-700 font-extrabold">{{ number_format($deliveredQty, 0) }} {{ $unit }}</strong> saat ini dan mengajukan pengiriman sisa <strong class="text-[#ea580c] font-extrabold">{{ number_format($remainingQty, 0) }} {{ $unit }}</strong> pada tanggal <strong class="text-gray-900 underline">{{ $estimatedDateText }}</strong>. Apakah Anda setuju?
                                                @endif
                                            </p>
                                            @if($batch1?->backorder_notes)
                                                <div class="bg-white/80 border border-amber-200/80 rounded-xl p-2.5 text-[11px] text-gray-700 space-y-0.5 mt-1.5">
                                                    <span class="font-bold text-amber-900 block text-[10px] uppercase font-mono">Alasan / Catatan dari Pemasok:</span>
                                                    <p class="italic">"{{ $batch1->backorder_notes }}"</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- 2 TOMBOL TINDAKAN: SETUJUI VS TOLAK -->
                                    <div class="pt-2 border-t border-amber-200/80 flex flex-wrap items-center justify-end gap-2.5">
                                        <!-- Opsi Tolak (Buka Modal Alasan Penolakan) -->
                                        <button type="button"
                                                @click="rejectingBackorderId = '{{ $boOrder->id }}'; rejectionReason = ''; rejectingAction = '{{ $batch1?->backorder_action ?? 'create_backorder' }}';"
                                                class="bg-white hover:bg-rose-50 text-rose-700 border border-rose-300 hover:border-rose-400 px-4 py-2 rounded-xl text-xs font-bold transition shadow-xs cursor-pointer flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            <span>{{ $batch1?->backorder_action === 'no_backorder' ? 'Tolak & Batalkan Pesanan' : 'Tolak & Batalkan Sisa (' . number_format($remainingQty, 0) . ' ' . $unit . ')' }}</span>
                                        </button>

                                        <!-- Opsi Terima (Buka Modal Kustom) -->
                                        <button type="button"
                                                @click="approvingBackorderOrder = {
                                                    id: '{{ $boOrder->id }}',
                                                    orderCode: '#ORD-{{ strtoupper(substr($boOrder->id, 0, 8)) }}',
                                                    supplier: '{{ addslashes($boOrder->partnership?->supplier?->company_name ?? 'Pemasok Mitra') }}',
                                                    commodity: '{{ addslashes($firstItem?->supplierProduct?->commodity?->name ?? 'Bahan Baku') }}',
                                                    deliveredQty: '{{ number_format($deliveredQty, 0) }}',
                                                    remainingQty: '{{ number_format($remainingQty, 0) }}',
                                                    unit: '{{ $unit }}',
                                                    eta: '{{ $estimatedDateText }}',
                                                    action: '{{ $batch1?->backorder_action ?? 'create_backorder' }}',
                                                    notes: '{{ addslashes($batch1?->backorder_notes ?? '') }}',
                                                    actionUrl: '{{ route('umkm.pemesanan.terima-backorder', $boOrder) }}'
                                                }"
                                                class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 rounded-xl text-xs font-extrabold transition shadow-xs cursor-pointer flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            <span>{{ $batch1?->backorder_action === 'no_backorder' ? 'Setujui Pemenuhan Sebagian' : 'Terima Backorder' }}</span>
                                        </button>
                                    </div>
                                </div>
                            @elseif($approvalStatus === 'rejected')
                                <div class="bg-rose-50 border border-rose-200 rounded-2xl p-3 text-xs text-rose-900 flex items-center justify-between">
                                    <div>
                                        <strong class="font-bold flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg><span>Pengajuan {{ $batch1?->backorder_action === 'no_backorder' ? 'Pemenuhan Sebagian' : 'Backorder' }} Ditolak</span></strong>
                                        <span class="text-[11px] text-rose-700">
                                            {{ $batch1?->backorder_action === 'no_backorder' ? 'Seluruh pesanan dibatalkan sesuai keputusan penolakan Anda.' : 'Sisa barang ditiadakan permanen. Total tagihan telah dipotong hanya untuk ' . number_format($deliveredQty, 0) . ' ' . $unit . '.' }}
                                        </span>
                                        @if($batch1?->backorder_rejection_reason)
                                            <p class="text-[10px] text-gray-600 italic mt-0.5">Alasan UMKM: "{{ $batch1->backorder_rejection_reason }}"</p>
                                        @endif
                                    </div>
                                    <span class="bg-rose-100 text-rose-800 text-[10px] font-bold px-2.5 py-1 rounded-full whitespace-nowrap">
                                        {{ $batch1?->backorder_action === 'no_backorder' ? 'Pesanan Dibatalkan' : 'Selesai Sebagian' }}
                                    </span>
                                </div>
                            @else
                                <div class="bg-emerald-50/70 border border-emerald-200 rounded-xl px-3 py-2 text-[11px] text-emerald-900 flex flex-wrap items-center justify-between gap-1">
                                    <span class="font-bold flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        <span>Pengajuan {{ $batch1?->backorder_action === 'no_backorder' ? 'Pemenuhan Sebagian (No Backorder)' : 'Backorder' }} Disetujui</span>
                                    </span>
                                    @if($batch1?->backorder_action !== 'no_backorder')
                                        <span class="text-emerald-800 font-mono text-[10px]">
                                            Janji tiba sisa barang: <strong>{{ $estimatedDateText }}</strong>
                                        </span>
                                    @else
                                        <span class="text-emerald-800 font-mono text-[10px]">
                                            Sisa ditiadakan. Tagihan disesuaikan untuk {{ number_format($deliveredQty, 0) }} {{ $unit }}.
                                        </span>
                                    @endif
                                </div>
                            @endif

                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                                <!-- KOMODITAS & RINCIAN VOLUME -->
                                <div class="md:col-span-4 space-y-1">
                                    <span class="text-[10px] font-mono uppercase text-gray-400 font-bold">Komoditas Bahan Baku</span>
                                    <h4 class="font-extrabold text-sm text-gray-900">
                                        {{ $firstItem?->supplierProduct?->commodity?->name ?? 'Bahan Baku' }}
                                    </h4>
                                    <p class="text-xs text-gray-500 font-mono">
                                        Total Kontrak: <strong>{{ number_format($totalQty, 0) }} {{ $unit }}</strong>
                                    </p>
                                </div>

                                <!-- PERBANDINGAN BATCH 1 & BATCH 2 -->
                                <div class="md:col-span-5 bg-white p-3 rounded-2xl border border-gray-200/80 space-y-1.5 text-xs font-mono">
                                    <div class="flex justify-between items-center text-emerald-800 font-semibold">
                                        <span class="flex items-center gap-1"><svg class="w-3 h-3 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg><span>Kloter 1 Diterima:</span></span>
                                        <strong>{{ number_format($deliveredQty, 0) }} {{ $unit }}</strong>
                                    </div>
                                    <div class="flex justify-between items-center text-amber-900 font-bold border-t border-gray-100 pt-1">
                                        <span class="flex items-center gap-1"><svg class="w-3.5 h-3.5 text-amber-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg><span>Sisa Backorder (Kloter 2):</span></span>
                                        <span class="text-amber-600 font-black text-sm">{{ number_format($remainingQty, 0) }} {{ $unit }}</span>
                                    </div>
                                    @if($batch2?->tracking_notes)
                                        <p class="text-[10px] text-gray-500 font-sans italic pt-1 border-t border-gray-100">
                                            Resi/Armada Kloter 2: "{{ $batch2->tracking_notes }}"
                                        </p>
                                    @endif
                                </div>

                                <!-- STATUS KEPUTUSAN & PENGALIHAN KE TAB KONFIRMASI PESANAN -->
                                <div class="md:col-span-3 flex flex-col items-end justify-center space-y-1.5 text-right">
                                    @if($approvalStatus === 'pending')
                                        <span class="text-xs text-amber-700 font-bold block">
                                            ⚠️ Menunggu Keputusan
                                        </span>
                                        <span class="text-[11px] text-gray-500">
                                            Tentukan persetujuan pada kotak di atas.
                                        </span>
                                    @elseif($approvalStatus === 'rejected')
                                        <span class="text-xs text-rose-600 font-bold block">
                                            ✕ Pengajuan Ditolak
                                        </span>
                                        <span class="text-[11px] text-gray-400">
                                            Sisa pesanan dibatalkan permanen.
                                        </span>
                                    @else
                                        <span class="text-xs text-emerald-700 font-bold block">
                                            ✓ Backorder Disetujui
                                        </span>
                                        <p class="text-[10px] text-gray-500">
                                            Konfirmasi penerimaan fisik (Kloter 1 maupun Kloter 2) dipantau di tab Konfirmasi Pesanan.
                                        </p>
                                        <button type="button"
                                                @click="orderSubTab = 'confirm'"
                                                class="bg-orange-50 hover:bg-orange-100 text-[#ea580c] border border-orange-200 px-3.5 py-1.5 rounded-full font-bold text-[11px] inline-flex items-center gap-1 shadow-2xs transition cursor-pointer mt-1">
                                            <span>Buka Konfirmasi Pesanan →</span>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- MODAL KONFIRMASI TERIMA SISA BACKORDER (BATCH 2) -->
        <div x-cloak x-show="confirmingBackorderId" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div @click.away="confirmingBackorderId = null" class="bg-white max-w-lg w-full rounded-[2rem] p-6 space-y-5 shadow-2xl border border-gray-100">
                <div class="flex justify-between items-start border-b border-gray-100 pb-4">
                    <div>
                        <span class="text-xs font-bold text-amber-600 uppercase font-mono tracking-wider">KONFIRMASI PENERIMAAN SISA BACKORDER (BATCH 2)</span>
                        <h3 class="text-xl font-bold text-gray-900 mt-1">Upload Bukti Penerimaan Batch 2</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Pastikan sisa barang pasokan telah Anda terima dengan baik di dapur.</p>
                    </div>
                    <button type="button" @click="confirmingBackorderId = null" class="text-gray-400 hover:text-gray-700 cursor-pointer p-1 rounded-lg hover:bg-gray-100 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>

                <template x-if="confirmingBackorderId">
                    <form :action="`/umkm/pemesanan/${confirmingBackorderId}/konfirmasi-backorder`" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        <!-- Preview Foto -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-2">Foto Bukti Penerimaan Sisa Backorder <span class="text-rose-500">*</span></label>
                            <div class="border-2 border-dashed border-gray-200 rounded-2xl p-4 text-center cursor-pointer hover:border-amber-500 transition"
                                 @click="$refs.photoBackorderInput.click()">
                                <template x-if="!confirmBackorderPhotoPreview">
                                    <div class="space-y-2 py-3">
                                        <div class="w-11 h-11 mx-auto rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </div>
                                        <p class="text-xs text-gray-600 font-bold">Pilih foto penerimaan Batch 2</p>
                                        <p class="text-[11px] text-gray-400">JPG, PNG, WEBP — Maks. 5MB</p>
                                    </div>
                                </template>
                                <template x-if="confirmBackorderPhotoPreview">
                                    <img :src="confirmBackorderPhotoPreview" class="max-h-48 mx-auto rounded-xl object-contain" alt="Preview">
                                </template>
                            </div>
                            <input
                                type="file"
                                name="proof_photo"
                                required
                                accept="image/*"
                                x-ref="photoBackorderInput"
                                class="hidden"
                                @change="onConfirmBackorderPhotoChange($event)">
                        </div>

                        <!-- Catatan Konfirmasi -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Catatan Penerimaan Batch 2 (Opsional)</label>
                            <textarea
                                name="confirmation_notes"
                                rows="3"
                                x-model="confirmBackorderNotes"
                                placeholder="Contoh: Sisa 50 kg diterima lengkap dan mutu baik."
                                class="w-full px-4 py-2.5 rounded-xl bg-[#f1f2f4] text-gray-900 text-xs font-medium placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-500 border-0">
                            </textarea>
                        </div>

                        <div class="flex gap-3 pt-2">
                            <button type="button" @click="confirmingBackorderId = null"
                                class="w-1/2 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-full font-bold text-xs transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit"
                                class="w-1/2 bg-amber-500 hover:bg-amber-600 text-white py-3 rounded-full font-bold text-xs shadow-sm transition cursor-pointer flex items-center justify-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>Konfirmasi Selesai Penuh</span>
                            </button>
                        </div>
                    </form>
                </template>
            </div>
        </div>

        <!-- MODAL TOLAK BACKORDER / NO BACKORDER & ISI ALASAN (ALUR 2.b) -->
        <div x-cloak x-show="rejectingBackorderId" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div @click.away="rejectingBackorderId = null" class="bg-white max-w-md w-full rounded-[2rem] p-6 space-y-4 shadow-2xl border border-gray-100">
                <div class="flex justify-between items-start border-b border-gray-100 pb-3">
                    <div>
                        <span class="text-[10px] font-bold text-rose-600 uppercase font-mono tracking-wider">KEPUTUSAN PENOLAKAN</span>
                        <h3 class="text-lg font-bold text-gray-900 mt-0.5" x-text="rejectingAction === 'no_backorder' ? 'Tolak & Batalkan Seluruh Pesanan' : 'Tolak & Batalkan Sisa Barang'"></h3>
                        <p class="text-xs text-gray-500" x-text="rejectingAction === 'no_backorder' ? 'Seluruh pesanan akan dibatalkan karena Anda menolak pemenuhan sebagian tanpa sisa.' : 'Sisa pesanan tidak akan dikirim lagi dan tagihan Anda akan otomatis dipotong.'"></p>
                    </div>
                    <button type="button" @click="rejectingBackorderId = null" class="text-gray-400 hover:text-gray-700 cursor-pointer p-1 rounded-lg hover:bg-gray-100 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>

                <template x-if="rejectingBackorderId">
                    <form :action="`/umkm/pemesanan/${rejectingBackorderId}/tolak-backorder`" method="POST" class="space-y-4">
                        @csrf
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-gray-700">
                                Alasan Singkat Penolakan: <span class="text-rose-500">*</span>
                            </label>
                            <textarea name="rejection_reason"
                                      x-model="rejectionReason"
                                      rows="3"
                                      required
                                      placeholder="Contoh: Kebutuhan dapur sangat mendesak, sisa bahan baku akan kami beli mandiri hari ini."
                                      class="w-full px-4 py-2.5 rounded-xl bg-[#f1f2f4] text-gray-900 text-xs font-medium placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-rose-500 border-0"></textarea>
                            <p class="text-[11px] text-gray-400" x-text="rejectingAction === 'no_backorder' ? 'Pemasok akan menerima pemberitahuan bahwa seluruh pesanan dibatalkan.' : 'Pemasok akan menerima pemberitahuan bahwa sisa pesanan tidak perlu dikirimkan lagi.'"></p>
                        </div>

                        <div class="flex gap-3 pt-2">
                            <button type="button" @click="rejectingBackorderId = null"
                                    class="w-1/2 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-full font-bold text-xs transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit"
                                    class="w-1/2 bg-rose-600 hover:bg-rose-700 text-white py-3 rounded-full font-bold text-xs shadow-sm transition cursor-pointer">
                                <span x-text="rejectingAction === 'no_backorder' ? 'Ya, Tolak & Batalkan Pesanan' : 'Ya, Tolak & Batalkan Sisa'"></span>
                            </button>
                        </div>
                    </form>
                </template>
            </div>
        </div>

        <!-- MODAL PERSETUJUAN TERIMA BACKORDER / NO BACKORDER (CUSTOM MODAL PREMIUM) -->
        <div x-cloak x-show="approvingBackorderOrder" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div @click.away="approvingBackorderOrder = null" class="bg-white max-w-md w-full rounded-[2rem] p-6 space-y-4 shadow-2xl border border-gray-100">
                <div class="flex justify-between items-start border-b border-gray-100 pb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center shadow-xs">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-emerald-700 uppercase font-mono tracking-wider">KONFIRMASI PERSETUJUAN</span>
                            <h3 class="text-base font-bold text-gray-900" x-text="approvingBackorderOrder?.action === 'no_backorder' ? 'Setujui Pemenuhan Sebagian?' : 'Setujui Pengajuan Backorder?'"></h3>
                        </div>
                    </div>
                    <button type="button" @click="approvingBackorderOrder = null" class="text-gray-400 hover:text-gray-700 cursor-pointer p-1 rounded-lg hover:bg-gray-100 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>

                <template x-if="approvingBackorderOrder">
                    <form :action="approvingBackorderOrder.actionUrl" method="POST" class="space-y-4">
                        @csrf
                        <div class="bg-emerald-50/60 border border-emerald-200 rounded-2xl p-4 space-y-2.5 text-xs">
                            <template x-if="approvingBackorderOrder.action === 'no_backorder'">
                                <div class="space-y-2">
                                    <p class="text-gray-600">
                                        Pemasok (<strong class="text-gray-900" x-text="approvingBackorderOrder.supplier"></strong>) mengajukan pemenuhan sebagian tanpa backorder:
                                    </p>
                                    <div class="flex items-center justify-between bg-white px-3.5 py-2.5 rounded-xl border border-emerald-200/80 shadow-2xs font-mono">
                                        <span class="text-gray-700 font-bold" x-text="approvingBackorderOrder.commodity"></span>
                                        <span class="text-emerald-700 font-extrabold text-sm" x-text="'Dikirim: ' + approvingBackorderOrder.deliveredQty + ' ' + approvingBackorderOrder.unit"></span>
                                    </div>
                                    <div class="text-[11px] text-rose-700 bg-rose-50 border border-rose-200 p-2.5 rounded-xl space-y-0.5">
                                        <strong class="flex items-center gap-1.5 font-bold"><svg class="w-3.5 h-3.5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg><span>Sisa Pasokan Ditiadakan:</span></strong>
                                        <p>Sisa <strong x-text="approvingBackorderOrder.remainingQty + ' ' + approvingBackorderOrder.unit"></strong> ditiadakan & tagihan otomatis disesuaikan.</p>
                                    </div>
                                </div>
                            </template>

                            <template x-if="approvingBackorderOrder.action !== 'no_backorder'">
                                <div class="space-y-2">
                                    <p class="text-gray-600">
                                        Pemasok (<strong class="text-gray-900" x-text="approvingBackorderOrder.supplier"></strong>) mengajukan sisa pengiriman barang:
                                    </p>
                                    <div class="flex items-center justify-between bg-white px-3.5 py-2.5 rounded-xl border border-emerald-200/80 shadow-2xs font-mono">
                                        <span class="text-gray-700 font-bold" x-text="approvingBackorderOrder.commodity"></span>
                                        <span class="text-emerald-700 font-extrabold text-sm" x-text="approvingBackorderOrder.remainingQty + ' ' + approvingBackorderOrder.unit"></span>
                                    </div>
                                    <div class="text-[11px] text-emerald-900 space-y-1.5 pt-1">
                                        <p class="flex items-center gap-1.5 font-medium">
                                            <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <span>Estimasi kirim sisa barang: <strong class="underline decoration-emerald-400" x-text="approvingBackorderOrder.eta"></strong></span>
                                        </p>
                                    </div>
                                </div>
                            </template>

                            <template x-if="approvingBackorderOrder.notes">
                                <p class="text-gray-500 italic bg-white/70 p-2 rounded-lg border border-emerald-100">
                                    Catatan pemasok: "<span x-text="approvingBackorderOrder.notes"></span>"
                                </p>
                            </template>
                        </div>

                        <p class="text-[11px] text-gray-500 leading-normal" x-text="approvingBackorderOrder.action === 'no_backorder' ? 'Dengan menyetujui, pemasok akan segera memberangkatkan barang yang tersedia. Invoice resmi diterbitkan saat barang diberangkatkan.' : 'Dengan menyetujui, pemasok akan mempersiapkan pengiriman Batch 2 sesuai tanggal di atas. Tagihan Invoice Batch 2 akan terbit saat barang kloter 2 tiba di dapur.'">
                        </p>

                        <div class="flex gap-3 pt-1">
                            <button type="button" @click="approvingBackorderOrder = null"
                                    class="w-1/2 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-full font-bold text-xs transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit"
                                    class="w-1/2 bg-emerald-600 hover:bg-emerald-700 text-white py-3 rounded-full font-bold text-xs shadow-sm transition cursor-pointer flex items-center justify-center gap-1.5">
                                <span>✓</span>
                                <span x-text="approvingBackorderOrder.action === 'no_backorder' ? 'Ya, Setujui Pemenuhan Sebagian' : 'Ya, Setujui Backorder'"></span>
                            </button>
                        </div>
                    </form>
                </template>
            </div>
        </div>

        <!-- SUB-TAB 4: PEMBAYARAN MIDTRANS (INVOICE PER BATCH) -->
        <div x-show="orderSubTab === 'payment'" class="bg-white rounded-3xl border border-gray-100 overflow-hidden p-6 space-y-4 shadow-sm">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2 border-b border-gray-100 pb-4">
                <div>
                    <span class="text-[10px] font-bold text-[#ea580c] uppercase font-mono tracking-wider">TAGIHAN RESMI SESUAI BATCH PENGIRIMAN</span>
                    <h3 class="font-extrabold text-xl text-gray-900 mt-0.5">Pembayaran Tagihan Invoice Via Midtrans</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Setiap kloter pengiriman (Batch 1 maupun Batch 2 Backorder) diterbitkan invoice terpisah sesuai fisik barang yang tiba.</p>
                </div>
            </div>

            @if($batchInvoices->isEmpty())
                <div class="py-14 text-center space-y-3">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-orange-50 border border-orange-100/80 flex items-center justify-center text-[#ea580c] shadow-xs">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <p class="font-bold text-gray-800 text-sm">Belum Ada Tagihan Invoice Pasokan</p>
                        <p class="text-xs text-gray-400">Invoice pembayaran akan otomatis diterbitkan untuk setiap kloter batch pengiriman saat pemasok memproses pengiriman bahan baku.</p>
                    </div>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-gray-50 font-mono text-gray-500 uppercase border-b border-gray-100">
                            <tr>
                                <th class="p-3.5">No. Invoice & Batch</th>
                                <th class="p-3.5">Pemasok Mitra</th>
                                <th class="p-3.5">Detail Komoditas & Kuantitas Batch</th>
                                <th class="p-3.5">Nominal Tagihan Batch</th>
                                <th class="p-3.5">Status Pembayaran</th>
                                <th class="p-3.5 text-center">Aksi Bayar Online</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 font-medium">
                            @foreach($batchInvoices as $inv)
                                @php
                                    $invOrder = $inv->order;
                                    $firstItem = $invOrder?->items?->first();
                                    $commodityName = $firstItem?->supplierProduct?->commodity?->name ?? 'Komoditas Pasokan';
                                    $unit = $firstItem?->supplierProduct?->commodity?->unit ?? 'kg';
                                    $isPaid = $inv->isPaid();
                                    $batchBadge = $inv->shipment_batch == 2 ? 'Kloter 2 (Backorder)' : 'Kloter 1';
                                @endphp
                                <tr class="hover:bg-gray-50/50 transition">
                                    <td class="p-3.5 space-y-1">
                                        <span class="font-mono font-bold text-[#ea580c] block text-xs">
                                            {{ $inv->invoice_number ?? ('INV/' . strtoupper(substr($inv->order_id, 0, 8)) . '/B' . $inv->shipment_batch) }}
                                        </span>
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2.5 py-0.5 rounded-full {{ $inv->shipment_batch == 2 ? 'bg-amber-100 text-amber-900 border border-amber-300' : 'bg-orange-50 text-[#ea580c] border border-orange-200' }}">
                                            {{ $batchBadge }}
                                        </span>
                                        <span class="block text-[10px] text-gray-400 font-mono">
                                            Ref: #ORD-{{ strtoupper(substr($inv->order_id, 0, 8)) }}
                                        </span>
                                    </td>
                                    <td class="p-3.5 font-bold text-gray-900">
                                        {{ $invOrder?->partnership?->supplier?->company_name ?? 'Pemasok Mitra' }}
                                    </td>
                                    <td class="p-3.5 space-y-1">
                                        <strong class="text-gray-900 block text-xs">{{ $commodityName }}</strong>
                                        <div class="text-[11px] text-gray-600 font-mono">
                                            Kuantitas Batch: <strong>{{ number_format((float) ($inv->quantity_billed ?: 0), 1) }} {{ $unit }}</strong>
                                        </div>
                                        @if($inv->notes)
                                            <span class="text-[10px] text-gray-400 italic block">{{ $inv->notes }}</span>
                                        @endif
                                    </td>
                                    <td class="p-3.5 whitespace-nowrap">
                                        <span class="font-extrabold text-gray-900 text-sm block font-mono">
                                            Rp {{ number_format($inv->amount, 0, ',', '.') }}
                                        </span>
                                        <span class="text-[10px] text-gray-400 block font-mono">Sesuai kuantitas batch</span>
                                    </td>
                                    <td class="p-3.5 whitespace-nowrap">
                                        @if($isPaid)
                                            <span class="bg-emerald-100 text-emerald-800 px-2.5 py-1 rounded-full text-[10px] font-bold inline-flex items-center gap-1">
                                                <span>✓ LUNAS</span>
                                            </span>
                                            @if($inv->paid_at || $inv->transaction_time)
                                                <span class="block text-[10px] text-gray-400 mt-1 font-mono">
                                                    {{ ($inv->paid_at ?? $inv->transaction_time)->format('d/m/Y H:i') }}
                                                </span>
                                            @endif
                                        @else
                                            <span class="bg-rose-100 text-rose-800 px-2.5 py-1 rounded-full text-[10px] font-bold inline-flex items-center gap-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-ping"></span>
                                                BELUM DIBAYAR
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-3.5 text-center whitespace-nowrap">
                                        @if(! $isPaid)
                                            <button 
                                                type="button"
                                                @click="openBatchInvoicePayment({
                                                    id: '{{ $inv->id }}',
                                                    invoiceNumber: '{{ $inv->invoice_number }}',
                                                    batch: {{ $inv->shipment_batch }},
                                                    batchName: '{{ $batchBadge }}',
                                                    orderCode: '{{ strtoupper(substr($inv->order_id, 0, 8)) }}',
                                                    supplierName: '{{ addslashes($invOrder?->partnership?->supplier?->company_name ?? 'Pemasok') }}',
                                                    commodity: '{{ addslashes($commodityName) }}',
                                                    quantity: '{{ number_format((float) ($inv->quantity_billed ?: 0), 1) }} {{ $unit }}',
                                                    amount: {{ (float) $inv->amount }}
                                                })" 
                                                class="bg-[#ea580c] hover:bg-[#c2410c] text-white px-4 py-2 rounded-full font-bold text-[11px] shadow-sm transition cursor-pointer flex items-center justify-center gap-1.5 mx-auto">
                                                <span>Bayar {{ $batchBadge }} →</span>
                                            </button>
                                        @else
                                            <span class="text-emerald-700 font-bold text-[11px] inline-flex items-center gap-1">
                                                ✓ Lunas via {{ $inv->payment_method ?? 'Midtrans' }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
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

    </div> <!-- /TAB: PESANAN & PENGIRIMAN -->

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
                <div class="flex items-center gap-4">
                    <template x-if="selectedCommodity?.image_url">
                        <img :src="selectedCommodity?.image_url" :alt="selectedCommodity?.commodity_name" class="w-16 h-16 rounded-2xl object-cover border border-gray-200/80 shadow-2xs shrink-0">
                    </template>
                    <template x-if="!selectedCommodity?.image_url">
                        <div class="w-16 h-16 rounded-2xl bg-gray-100 border border-gray-200 flex flex-col items-center justify-center shrink-0 text-gray-400">
                            <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </template>
                    <div>
                        <span class="text-xs font-bold text-orange-600 uppercase font-mono">FORM PENGAJUAN KEMITRAAN & JADWAL PASOKAN</span>
                        <h3 class="text-2xl font-bold text-gray-900 tracking-tight" x-text="selectedCommodity?.commodity_name"></h3>
                        <p class="text-xs text-gray-600">Mitra Pemasok: <span class="font-bold text-gray-900" x-text="selectedCommodity?.company_name"></span></p>
                    </div>
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
                    <form action="{{ route('umkm.pemesanan.terbitkan') }}" method="POST" class="w-1/2">
                        @csrf
                        <input type="hidden" name="schedule_id" :value="previewAutoOrder?.scheduleId">
                        <button 
                            type="submit" 
                            class="w-full bg-[#ea580c] hover:bg-[#c2410c] text-white py-3 rounded-full font-bold text-xs hover:brightness-95 transition shadow-sm cursor-pointer flex items-center justify-center gap-1.5">
                            <span>Terbitkan Pesanan Sekarang</span>
                            <span>→</span>
                        </button>
                    </form>
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
    <!-- MODAL MIDTRANS PAYMENT SIMULATOR (BATCH INVOICE)          -->
    <!-- ========================================================= -->
    <div x-cloak x-show="payingInvoice" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div @click.away="payingInvoice = null" class="bg-white max-w-md w-full rounded-3xl p-6 sm:p-7 space-y-5 shadow-2xl border border-gray-100">
            <div class="flex justify-between items-start border-b border-gray-100 pb-3">
                <div>
                    <span class="text-xs font-bold text-[#ea580c] uppercase font-mono">MIDTRANS PAYMENT GATEWAY</span>
                    <h3 class="text-xl font-extrabold text-gray-900 mt-0.5">Pembayaran Tagihan Batch</h3>
                    <p class="text-xs text-gray-500 font-mono mt-0.5" x-text="'No. Invoice: ' + payingInvoice?.invoiceNumber"></p>
                </div>
                <button type="button" @click="payingInvoice = null" class="text-gray-400 hover:text-gray-700 cursor-pointer p-1 rounded-lg hover:bg-gray-100 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>

            <!-- DETAIL INVOICE BATCH -->
            <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100 space-y-2 text-xs font-mono">
                <div class="flex justify-between text-gray-600">
                    <span>Kloter Pengiriman:</span>
                    <span class="font-bold text-[#ea580c]" x-text="payingInvoice?.batchName"></span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>Ref. Pesanan:</span>
                    <span class="font-bold text-gray-800" x-text="'#ORD-' + payingInvoice?.orderCode"></span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>Pemasok:</span>
                    <span class="font-bold text-gray-900" x-text="payingInvoice?.supplierName"></span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>Kuantitas Batch:</span>
                    <span class="font-bold text-gray-900" x-text="payingInvoice?.commodity + ' (' + payingInvoice?.quantity + ')'"></span>
                </div>
                <div class="flex justify-between text-base text-[#ea580c] font-black pt-2 border-t border-gray-200">
                    <span>Total Tagihan Batch:</span>
                    <span x-text="'Rp ' + (payingInvoice?.amount ? payingInvoice.amount.toLocaleString('id-ID') : 0)"></span>
                </div>
            </div>

            <form :action="`/umkm/pemesanan/pembayaran/${payingInvoice?.id}/bayar`" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="payment_method" :value="selectedPaymentMethod">

                <div class="space-y-2 text-xs font-semibold">
                    <span class="block text-gray-600 text-[11px]">Pilih Metode Pembayaran Midtrans:</span>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button"
                                @click="selectedPaymentMethod = 'Midtrans BCA Virtual Account'"
                                :class="selectedPaymentMethod === 'Midtrans BCA Virtual Account' ? 'border-[#ea580c] bg-orange-50 text-[#ea580c]' : 'border-gray-200 bg-[#f1f2f4] text-gray-800'"
                                class="p-2.5 rounded-xl border text-center font-bold cursor-pointer transition text-xs">
                            BCA VA
                        </button>
                        <button type="button"
                                @click="selectedPaymentMethod = 'Midtrans Mandiri Virtual Account'"
                                :class="selectedPaymentMethod === 'Midtrans Mandiri Virtual Account' ? 'border-[#ea580c] bg-orange-50 text-[#ea580c]' : 'border-gray-200 bg-[#f1f2f4] text-gray-800'"
                                class="p-2.5 rounded-xl border text-center font-bold cursor-pointer transition text-xs">
                            Mandiri VA
                        </button>
                        <button type="button"
                                @click="selectedPaymentMethod = 'Midtrans QRIS'"
                                :class="selectedPaymentMethod === 'Midtrans QRIS' ? 'border-[#ea580c] bg-orange-50 text-[#ea580c]' : 'border-gray-200 bg-[#f1f2f4] text-gray-800'"
                                class="p-2.5 rounded-xl border text-center font-bold cursor-pointer transition text-xs">
                            QRIS
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <button type="button" @click="payingInvoice = null" class="w-1/3 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3.5 rounded-full font-bold text-xs transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="w-2/3 bg-[#ea580c] hover:bg-[#c2410c] text-white py-3.5 rounded-full font-bold text-xs shadow-md transition cursor-pointer flex items-center justify-center gap-1.5">
                        <span>Bayar Batch Ini via Midtrans</span><svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL FALLBACK UNTUK MOCK ORDER DEMO -->
    <div x-cloak x-show="payingOrder" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div @click.away="payingOrder = null" class="bg-white max-w-md w-full rounded-3xl p-6 sm:p-7 space-y-5 shadow-2xl border border-gray-100">
            <div class="flex justify-between items-start border-b border-gray-100 pb-3">
                <div>
                    <span class="text-xs font-bold text-[#ea580c] uppercase font-mono">MIDTRANS PAYMENT GATEWAY</span>
                    <h3 class="text-xl font-extrabold text-gray-900">Pembayaran Tagihan Pasokan</h3>
                </div>
                <button type="button" @click="payingOrder = null" class="text-gray-400 hover:text-gray-700 cursor-pointer p-1 rounded-lg hover:bg-gray-100 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
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

            <button type="button" @click="simulateMidtransSuccess()" class="w-full bg-[#ea580c] hover:bg-[#c2410c] text-white py-3.5 rounded-full font-bold text-xs shadow-md transition cursor-pointer">
                Simulasi Pembayaran Berhasil (Sukses)
            </button>
        </div>
    </div>

</div>
@endsection
