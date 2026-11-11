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
            showReviewModal: false,
            isSubmittingReview: false,
            notificationMsg: '',
            init() {},
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
            isLoadingSnap: false,
            loadingInvoiceId: null,

            openBatchInvoicePayment: function(inv) {
                this.payingInvoice = inv;
                this.selectedPaymentMethod = 'BCA Virtual Account';
            },

            payBatchInvoiceSnap: function(inv) {
                var self = this;
                self.isLoadingSnap = true;
                self.loadingInvoiceId = inv.id;

                fetch('/umkm/pemesanan/pembayaran/' + inv.id + '/snap-token', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(function(res) {
                    return res.json();
                })
                .then(function(data) {
                    self.isLoadingSnap = false;
                    self.loadingInvoiceId = null;

                    if (!data.success) {
                        if (data.is_mock) {
                            // Midtrans belum disetel, buka fallback simulator cepat
                            self.openBatchInvoicePayment(inv);
                            return;
                        }
                        alert(data.message || 'Gagal memproses transaksi Midtrans.');
                        return;
                    }

                    if (typeof window.snap === 'undefined') {
                        alert('Midtrans Snap SDK tidak termuat. Membuka opsi simulator lokal.');
                        self.openBatchInvoicePayment(inv);
                        return;
                    }

                    window.snap.pay(data.snap_token, {
                        onSuccess: function(result) {
                            window.location.href = "{{ route('umkm.katalog.index', ['tab' => 'pemesanan', 'subtab' => 'payment']) }}";
                        },
                        onPending: function(result) {
                            window.location.href = "{{ route('umkm.katalog.index', ['tab' => 'pemesanan', 'subtab' => 'payment']) }}";
                        },
                        onError: function(result) {
                            alert('Pembayaran via Midtrans gagal atau dibatalkan.');
                        },
                        onClose: function() {
                            self.notify('Jendela pembayaran ditutup. Klik tombol bayar kembali untuk melanjutkan atau memilih metode pembayaran lain.');
                        }
                    });
                })
                .catch(function(err) {
                    self.isLoadingSnap = false;
                    self.loadingInvoiceId = null;
                    console.error('Midtrans fetch error:', err);
                    self.openBatchInvoicePayment(inv);
                });
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
                this.notify('Pembayaran Midtrans berhasil lunas. Silakan berikan ulasan dan rating.');
            },

            saveRating: function() {
                if (!this.ratingInputOrder) return;
                var self = this;
                var orderId = this.ratingInputOrder.id;
                var currentStars = parseInt(this.stars) || 5;
                var currentReview = (this.reviewText || '').trim();

                if (!currentReview) {
                    alert('Mohon tuliskan ulasan kualitas bahan baku & pengiriman.');
                    return;
                }

                self.isSubmittingReview = true;
                var token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
                         || '{{ csrf_token() }}';

                fetch('/umkm/pemesanan/' + orderId + '/ulasan', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({
                        rating: currentStars,
                        review: currentReview
                    })
                })
                .then(function(res) {
                    return res.json().then(function(data) {
                        if (!res.ok) {
                            throw data;
                        }
                        return data;
                    });
                })
                .then(function(resData) {
                    self.isSubmittingReview = false;
                    self.showReviewModal = false;
                    self.ratingInputOrder = null;
                    self.notify(resData.message || '✓ Ulasan & Rating berhasil disimpan!');
                    setTimeout(function() {
                        window.location.href = '{{ route("umkm.katalog.index", ["tab" => "pemesanan", "subtab" => "rating"]) }}';
                    }, 400);
                })
                .catch(function(err) {
                    self.isSubmittingReview = false;
                    var msg = err.message || (err.errors ? Object.values(err.errors).flat().join(', ') : 'Gagal menyimpan ulasan.');
                    alert('Gagal: ' + msg);
                });
            },

            openRatingForOrder: function(ord) {
                this.ratingInputOrder = ord;
                this.stars = ord.rating ? parseInt(ord.rating) : 5;
                this.reviewText = ord.review || '';
                this.showReviewModal = true;
                this.orderSubTab = 'rating';
            },

            closeReviewModal: function() {
                this.showReviewModal = false;
                this.ratingInputOrder = null;
                this.reviewText = '';
                this.stars = 5;
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

            selectedSupplierPerformance: null,
            isLoadingPerformance: false,
            isRecalculatingPerformance: false,
            recalculatingSupplierId: null,
            monitoringSearchQuery: '',
            supplierScores: {
                @if(isset($contractedSuppliersPerformance))
                    @foreach($contractedSuppliersPerformance as $item)
                        @php
                            $sup = $item['supplier'];
                            $score = $item['score'];
                            $m = $score?->metrics_breakdown ?? [];
                        @endphp
                        '{{ $sup?->id }}': {
                            supplier_id: '{{ $sup?->id }}',
                            company_name: @json($sup?->company_name ?? 'Pemasok Mitra'),
                            warehouse_address: @json($sup?->warehouse_address ?? 'Area Lokal'),
                            commodity_name: @json($item['commodity_name'] ?? ''),
                            final_score: {{ (float) ($score?->final_score ?? 100.0) }},
                            star_rating: {{ (float) ($score?->star_rating ?? 5.0) }},
                            grade: '{{ $score?->grade ?? "UNRATED" }}',
                            grade_label: @json($score ? $score->getGradeLabel() : "Vendor Baru"),
                            grade_badge_color: '{{ $score ? $score->getGradeBadgeColor() : "bg-gray-100 text-gray-700 border-gray-200" }}',
                            otd_rate: {{ (float) ($m['delivery']['otd_rate'] ?? 100.0) }},
                            avg_delay: {{ (float) ($m['delivery']['avg_delay_hours'] ?? 0.0) }},
                            fill_rate: {{ (float) ($m['fulfillment']['fill_rate'] ?? 100.0) }},
                            backorder_rate: {{ (float) ($m['fulfillment']['backorder_rate'] ?? 0.0) }},
                            defect_free: {{ (float) ($m['quality']['defect_free_rate'] ?? 100.0) }},
                            total_shipments: {{ (int) ($m['total_shipments'] ?? 0) }},
                            is_recommended_for_renewal: {{ ($score && $score->isRecommendedForRenewal()) ? 'true' : 'false' }},
                            is_cold_start: {{ empty($m) || (!empty($m['is_cold_start'])) ? 'true' : 'false' }},
                        },
                    @endforeach
                @endif
            },

            isSupplierMatched: function(supplierId, companyName, address, commodity) {
                if (!this.monitoringSearchQuery) return true;
                var q = this.monitoringSearchQuery.toLowerCase().trim();
                var s = this.supplierScores[supplierId] || {};
                var name = (s.company_name || companyName || '').toLowerCase();
                var addr = (s.warehouse_address || address || '').toLowerCase();
                var comm = (s.commodity_name || commodity || '').toLowerCase();
                var gr = (s.grade || '').toLowerCase();
                return name.includes(q) || addr.includes(q) || comm.includes(q) || ('grade ' + gr).includes(q);
            },

            get filteredSupplierCount() {
                var self = this;
                var keys = Object.keys(this.supplierScores);
                if (!self.monitoringSearchQuery) return keys.length;
                var q = self.monitoringSearchQuery.toLowerCase().trim();
                var count = 0;
                for (var i = 0; i < keys.length; i++) {
                    var s = self.supplierScores[keys[i]];
                    if (!s) continue;
                    var name = (s.company_name || '').toLowerCase();
                    var addr = (s.warehouse_address || '').toLowerCase();
                    var comm = (s.commodity_name || '').toLowerCase();
                    var gr = (s.grade || '').toLowerCase();
                    if (name.includes(q) || addr.includes(q) || comm.includes(q) || ('grade ' + gr).includes(q)) {
                        count++;
                    }
                }
                return count;
            },

            get monitoringAvgScore() {
                var keys = Object.keys(this.supplierScores);
                if (!keys.length) return '{{ number_format($avgScore ?? 92.5, 1) }}';
                var sum = 0;
                for (var i = 0; i < keys.length; i++) {
                    sum += Number(this.supplierScores[keys[i]].final_score || 0);
                }
                return (sum / keys.length).toFixed(1);
            },

            get monitoringAvgStar() {
                var keys = Object.keys(this.supplierScores);
                if (!keys.length) return '{{ number_format($avgStar ?? 4.8, 1) }}';
                var sum = 0;
                for (var i = 0; i < keys.length; i++) {
                    sum += Number(this.supplierScores[keys[i]].star_rating || 0);
                }
                return (sum / keys.length).toFixed(1);
            },

            get monitoringAvgOtd() {
                var keys = Object.keys(this.supplierScores);
                if (!keys.length) return '{{ number_format($avgOtd ?? 98.5, 1) }}';
                var sum = 0;
                for (var i = 0; i < keys.length; i++) {
                    sum += Number(this.supplierScores[keys[i]].otd_rate || 0);
                }
                return (sum / keys.length).toFixed(1);
            },

            get monitoringAvgFill() {
                var keys = Object.keys(this.supplierScores);
                if (!keys.length) return '{{ number_format($avgFulfillment ?? 99.2, 1) }}';
                var sum = 0;
                for (var i = 0; i < keys.length; i++) {
                    sum += Number(this.supplierScores[keys[i]].fill_rate || 0);
                }
                return (sum / keys.length).toFixed(1);
            },

            openSupplierTrackerModal: function(supplierId, partnershipId) {
                var self = this;
                self.isLoadingPerformance = true;
                self.selectedSupplierPerformance = null;
                var url = '{{ url("umkm/suppliers") }}/' + supplierId + '/performance';
                if (partnershipId) {
                    url += '?partnership_id=' + partnershipId;
                }
                fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(function(res) { return res.json(); })
                .then(function(json) {
                    self.isLoadingPerformance = false;
                    if (json.status === 'success') {
                        self.selectedSupplierPerformance = json.data;
                    } else {
                        alert('Gagal memuat evaluasi performa pemasok.');
                    }
                })
                .catch(function(err) {
                    self.isLoadingPerformance = false;
                    alert('Terjadi kendala jaringan saat memuat data performa.');
                });
            },

            recalculateSupplierTracker: function(supplierId, partnershipId, openModalAfter) {
                var self = this;
                if (!supplierId) return;
                self.recalculatingSupplierId = supplierId;
                self.isRecalculatingPerformance = true;
                var url = '{{ url("umkm/suppliers") }}/' + supplierId + '/recalculate-performance';
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        partnership_id: partnershipId || null
                    })
                })
                .then(function(res) { return res.json(); })
                .then(function(json) {
                    self.recalculatingSupplierId = null;
                    self.isRecalculatingPerformance = false;
                    if (json.status === 'success' && json.data) {
                        var d = json.data;
                        if (self.supplierScores[supplierId]) {
                            self.supplierScores[supplierId].final_score = d.final_score;
                            self.supplierScores[supplierId].star_rating = d.star_rating;
                            self.supplierScores[supplierId].grade = d.grade;
                            self.supplierScores[supplierId].grade_label = d.grade_label;
                            self.supplierScores[supplierId].grade_badge_color = d.grade_badge_color;
                            if (d.metrics_summary) {
                                self.supplierScores[supplierId].otd_rate = d.metrics_summary.otd_rate;
                                self.supplierScores[supplierId].avg_delay = d.metrics_summary.avg_delay_hours;
                                self.supplierScores[supplierId].fill_rate = d.metrics_summary.fill_rate;
                                self.supplierScores[supplierId].backorder_rate = d.metrics_summary.backorder_rate;
                                self.supplierScores[supplierId].defect_free = d.metrics_summary.defect_free_rate;
                                self.supplierScores[supplierId].total_shipments = d.metrics_summary.total_shipments;
                                self.supplierScores[supplierId].is_cold_start = d.metrics_summary.is_cold_start;
                            }
                            self.supplierScores[supplierId].is_recommended_for_renewal = d.is_recommended_for_renewal;
                        }

                        if (self.selectedSupplierPerformance && self.selectedSupplierPerformance.supplier_id === supplierId) {
                            self.selectedSupplierPerformance = d;
                        }

                        self.notify('✓ Skor ' + (d.company_name || 'pemasok') + ' berhasil dikalkulasi ulang secara realtime!');

                        if (openModalAfter) {
                            self.openSupplierTrackerModal(supplierId, partnershipId);
                        }
                    } else {
                        alert('Gagal memperbarui skor: ' + (json.message || 'Error'));
                    }
                })
                .catch(function(err) {
                    self.recalculatingSupplierId = null;
                    self.isRecalculatingPerformance = false;
                    alert('Terjadi kesalahan saat kalkulasi performa.');
                });
            },

            closeSupplierTrackerModal: function() {
                this.selectedSupplierPerformance = null;
                this.isLoadingPerformance = false;
            },

            notify: function(msg) {
                var self = this;
                this.notificationMsg = msg;
                setTimeout(function() {
                    if (self.notificationMsg === msg) self.notificationMsg = '';
                }, 3000);
            }
        };
    }
</script>

<div x-data="umkmCatalogPage()" class="pb-12">

    <!-- FLOATING NOTIFIKASI TOAST (CLIENT-SIDE) -->
    <div x-cloak x-show="notificationMsg"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
         class="fixed top-28 right-4 sm:right-6 z-50 max-w-sm sm:max-w-md w-full pointer-events-none">
        <div class="bg-emerald-600 border border-emerald-500/40 text-white px-4 py-3.5 rounded-2xl font-medium text-xs sm:text-sm flex items-center justify-between gap-3 shadow-lg pointer-events-auto">
            <div class="flex items-center gap-2.5 min-w-0">
                <span class="w-6 h-6 rounded-lg bg-white/15 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </span>
                <span x-text="notificationMsg" class="leading-snug"></span>
            </div>
            <button type="button" @click="notificationMsg = ''" class="text-white/80 hover:text-white hover:bg-white/15 cursor-pointer p-1 rounded-lg transition shrink-0" aria-label="Tutup notifikasi">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- TAB 1: KATALOG BAHAN BAKU                                -->
    <!-- ========================================================= -->
    <div x-show="activeTab === 'katalog'" class="space-y-6">

        <!-- PAGE HEADER -->
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 border-b border-slate-200/80 pb-5">
            <div>
                <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-[#ea580c] tracking-[0.14em] uppercase">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#ea580c]"></span>
                    <span>Katalog Lokal</span>
                </span>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight mt-1">Pilih &amp; Ajukan Kemitraan Pasokan</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl">Bandingkan komoditas dari pemasok terverifikasi, periksa skor SLA, dan ajukan jadwal pengiriman rutin untuk dapur Anda.</p>
            </div>
            <div class="shrink-0 self-start sm:self-end">
                <span class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white border border-slate-200/90 shadow-2xs text-xs font-semibold text-slate-700 tabular-nums">
                    <svg class="w-4 h-4 text-[#ea580c]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.9">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <span><strong class="text-slate-900">{{ method_exists($products, 'total') ? $products->total() : $products->count() }}</strong> Komoditas Tersedia</span>
                </span>
            </div>
        </div>

        <!-- CONTAINER SEARCH & FILTER CLEAN -->
        <div class="bg-white p-5 sm:p-6 rounded-2xl shadow-xs border border-slate-200/80 space-y-4">
            <form method="GET" action="{{ route('umkm.katalog.index') }}" class="space-y-4">
                <input type="hidden" name="tab" value="katalog">

                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 pointer-events-none">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </span>
                    <input 
                        type="text" 
                        name="search"
                        value="{{ $search }}"
                        placeholder="Cari komoditas bahan baku (misal: Ayam, Bawang, Cabai, Telur) atau nama pemasok..."
                        class="w-full pl-10 pr-4 py-2.5 text-xs font-medium bg-slate-50 border border-slate-200/90 rounded-xl text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/15 focus:border-[#ea580c] transition"
                        @input.debounce.700ms="$event.target.form.submit()"
                        {{ request()->has('search') ? 'autofocus' : '' }}
                        onfocus="var val = this.value; this.value = ''; this.value = val;"
                    />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 pt-4 border-t border-slate-100 text-xs">
                    <div>
                        <label class="block text-slate-600 font-semibold mb-1.5 text-[11px]">Kategori Bahan Baku</label>
                        <select 
                            name="category"
                            onchange="this.form.submit()"
                            class="w-full px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200/90 text-slate-800 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/15 focus:border-[#ea580c] transition cursor-pointer">
                            <option value="Semua Kategori">Semua Kategori</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" {{ $category == $cat ? 'selected' : '' }}>{{ strtoupper($cat) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-slate-600 font-semibold mb-1.5 text-[11px]">Harga Maksimal (Rp)</label>
                        <input 
                            type="number" 
                            name="max_price"
                            placeholder="Contoh: 35000" 
                            value="{{ $maxPrice }}"
                            @input.debounce.700ms="$event.target.form.submit()"
                            class="w-full px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200/90 text-slate-800 font-medium tabular-nums placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/15 focus:border-[#ea580c] transition"
                        />
                    </div>

                    <div>
                        <label class="block text-slate-600 font-semibold mb-1.5 text-[11px]">Ketersediaan Stok</label>
                        <select 
                            name="stock_status"
                            onchange="this.form.submit()"
                            class="w-full px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200/90 text-slate-800 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/15 focus:border-[#ea580c] transition cursor-pointer">
                            <option value="Semua Status" {{ ($stockStatus ?? '') == 'Semua Status' ? 'selected' : '' }}>Semua Status Stok</option>
                            <option value="Tersedia" {{ ($stockStatus ?? '') == 'Tersedia' ? 'selected' : '' }}>Stok Tersedia</option>
                            <option value="Stok Terbatas" {{ ($stockStatus ?? '') == 'Stok Terbatas' ? 'selected' : '' }}>Stok Terbatas</option>
                            <option value="Stok Habis" {{ ($stockStatus ?? '') == 'Stok Habis' ? 'selected' : '' }}>Stok Habis</option>
                            <option value="Pre-Order" {{ ($stockStatus ?? '') == 'Pre-Order' ? 'selected' : '' }}>Pre-Order</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-slate-600 font-semibold mb-1.5 text-[11px]">Urutkan</label>
                        <select 
                            name="sort"
                            onchange="this.form.submit()"
                            class="w-full px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200/90 text-slate-800 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/15 focus:border-[#ea580c] transition cursor-pointer">
                            <option value="terbaru" {{ ($sort ?? 'terbaru') == 'terbaru' ? 'selected' : '' }}>Terbaru</option>
                            <option value="harga_terendah" {{ ($sort ?? '') == 'harga_terendah' ? 'selected' : '' }}>Harga Terendah</option>
                            <option value="harga_tertinggi" {{ ($sort ?? '') == 'harga_tertinggi' ? 'selected' : '' }}>Harga Tertinggi</option>
                            <option value="kapasitas_terbesar" {{ ($sort ?? '') == 'kapasitas_terbesar' ? 'selected' : '' }}>Kapasitas Terbesar</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-between lg:items-end lg:pb-1.5 gap-2">
                        <label class="flex items-center gap-2 cursor-pointer font-semibold text-slate-700 bg-slate-50 border border-slate-200/80 px-3 py-2.5 rounded-xl hover:bg-slate-100/70 transition">
                            <input 
                                type="checkbox" 
                                name="is_halal"
                                value="1"
                                {{ !empty($isHalal) ? 'checked' : '' }}
                                onchange="this.form.submit()"
                                class="accent-[#ea580c] w-4 h-4 rounded cursor-pointer"
                            />
                            <span class="text-[11px]">Sertifikat Halal</span>
                        </label>

                        @if($search || ($category && $category !== 'Semua Kategori') || $maxPrice || !empty($isHalal) || ($stockStatus && $stockStatus !== 'Semua Status') || ($sort && $sort !== 'terbaru'))
                            <a href="{{ route('umkm.katalog.index', ['tab' => 'katalog']) }}" class="text-xs font-semibold text-rose-600 hover:text-rose-700 hover:underline whitespace-nowrap">
                                Reset
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <!-- GRID KATALOG KOMODITAS BAHAN BAKU -->
        @if($products->isEmpty())
            <div class="bg-white rounded-2xl p-12 text-center shadow-xs border border-slate-200/80 space-y-3">
                <div class="w-12 h-12 mx-auto rounded-2xl bg-slate-100 border border-slate-200/80 flex items-center justify-center text-slate-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <div class="space-y-1">
                    <h3 class="text-base font-bold text-slate-900">Tidak Ada Komoditas Bahan Baku Ditemukan</h3>
                    <p class="text-xs text-slate-500 font-medium max-w-md mx-auto">Silakan sesuaikan kata kunci pencarian, kategori, status stok, atau rentang harga maksimal Anda.</p>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 w-full pt-1">
                @foreach($products as $product)
                    <div class="bg-white rounded-2xl overflow-hidden flex flex-col justify-between shadow-xs hover:shadow-md hover:border-slate-300/80 transition-all duration-200 border border-slate-200/80 group">
                        
                        <!-- Gambar Komoditas & Badges -->
                        <div class="relative h-44 w-full bg-slate-100 overflow-hidden flex items-center justify-center">
                            @if($product->display_image_url)
                                <img src="{{ $product->display_image_url }}" 
                                     alt="{{ $product->commodity->name }}" 
                                     class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-500">
                            @else
                                <div class="w-full h-full flex flex-col items-center justify-center text-slate-400 bg-slate-50 border-b border-slate-100">
                                    <svg class="w-9 h-9 mb-1 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-[10px] font-semibold text-slate-400">Belum Ada Foto</span>
                                </div>
                            @endif

                            @if(in_array($product->id, $appliedProductIds ?? []))
                                <span class="absolute top-3 left-3 bg-emerald-600 text-white text-[10px] font-semibold px-2.5 py-1 rounded-lg shadow-xs inline-flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>Sudah Diajukan</span>
                                </span>
                            @else
                                <span class="absolute top-3 left-3 bg-white/95 backdrop-blur-xs text-slate-700 border border-slate-200/70 text-[10px] font-bold px-2.5 py-1 rounded-lg shadow-2xs uppercase tracking-wide">
                                    {{ $product->commodity->category }}
                                </span>
                            @endif

                            <div class="absolute top-3 right-3">
                                @if($product->stock_status === 'Tersedia')
                                    <span class="bg-emerald-50/95 backdrop-blur-xs text-emerald-700 border border-emerald-200/80 text-[10px] font-semibold px-2.5 py-1 rounded-lg shadow-2xs inline-flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Stok Ada</span>
                                    </span>
                                @elseif($product->stock_status === 'Stok Terbatas')
                                    <span class="bg-amber-50/95 backdrop-blur-xs text-amber-800 border border-amber-200/80 text-[10px] font-semibold px-2.5 py-1 rounded-lg shadow-2xs inline-flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        <span>Terbatas</span>
                                    </span>
                                @elseif($product->stock_status === 'Pre-Order')
                                    <span class="bg-sky-50/95 backdrop-blur-xs text-sky-700 border border-sky-200/80 text-[10px] font-semibold px-2.5 py-1 rounded-lg shadow-2xs inline-flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                                        <span>Pre-Order</span>
                                    </span>
                                @else
                                    <span class="bg-rose-50/95 backdrop-blur-xs text-rose-700 border border-rose-200/80 text-[10px] font-semibold px-2.5 py-1 rounded-lg shadow-2xs inline-flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        <span>Habis</span>
                                    </span>
                                @endif
                            </div>

                            @if($product->is_halal_certified)
                                <span class="absolute bottom-2.5 left-3 bg-white/95 backdrop-blur-xs text-emerald-700 border border-emerald-200/70 text-[10px] font-semibold px-2 py-0.5 rounded-lg shadow-2xs inline-flex items-center gap-1">
                                    <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Halal Resmi</span>
                                </span>
                            @endif
                        </div>

                        <!-- Konten Kartu -->
                        <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                            <div class="space-y-2">
                                <h3 class="font-bold text-[15px] text-slate-900 group-hover:text-[#ea580c] transition leading-snug line-clamp-2">
                                    {{ $product->commodity->name }}
                                </h3>
                                <div class="space-y-1">
                                    <p class="text-xs font-semibold text-slate-700 flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 0h4m-4 0H9m4 0V5" />
                                        </svg>
                                        <span class="truncate">{{ $product->supplier->company_name }}</span>
                                    </p>
                                    <p class="text-[11px] text-slate-500 truncate flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <span class="truncate">{{ $product->supplier->warehouse_address }}</span>
                                    </p>
                                </div>

                                @php
                                    $perfScore = $product->supplier?->latestPerformanceScore;
                                @endphp
                                <div class="pt-2.5 flex items-center justify-between gap-2 text-[11px] border-t border-slate-100 tabular-nums">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        @if($perfScore && $perfScore->grade !== \App\Models\SupplierPerformanceScore::GRADE_UNRATED)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-bold border {{ $perfScore->getGradeBadgeColor() }}">
                                                Grade {{ $perfScore->grade }}
                                            </span>
                                            <span class="text-slate-800 font-semibold inline-flex items-center gap-0.5 text-[11px]">
                                                <svg class="w-3 h-3 text-amber-500 fill-amber-500" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                {{ number_format($perfScore->star_rating, 1) }}
                                            </span>
                                            <span class="text-slate-400 text-[10px] font-medium">
                                                {{ number_format($perfScore->metrics_breakdown['delivery']['otd_rate'] ?? 100, 0) }}% OTD
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200/80">
                                                Vendor Baru
                                            </span>
                                        @endif
                                    </div>
                                    <button 
                                        type="button" 
                                        @click.stop="openSupplierTrackerModal('{{ $product->supplier->id }}')" 
                                        class="font-semibold text-slate-600 hover:text-[#ea580c] transition inline-flex items-center gap-0.5 cursor-pointer shrink-0">
                                        <span>Rapor SLA</span>
                                        <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                </div>
                            </div>

                            <div class="border-t border-slate-100 pt-3.5 space-y-3">
                                <div class="flex justify-between items-baseline gap-2">
                                    <span class="text-[11px] font-medium text-slate-500">Harga Grosir</span>
                                    <span class="font-bold text-base text-[#ea580c] tabular-nums">
                                        Rp {{ number_format($product->price, 0, ',', '.') }}<span class="text-xs text-slate-400 font-medium">/{{ $product->commodity->unit }}</span>
                                    </span>
                                </div>

                                @php
                                    $isAlreadyApplied = in_array($product->id, $appliedProductIds ?? []);
                                @endphp

                                @if($isAlreadyApplied)
                                    <button 
                                        type="button"
                                        disabled
                                        class="w-full bg-emerald-50 border border-emerald-200/80 text-emerald-700 font-semibold py-2.5 px-3 rounded-xl text-xs cursor-not-allowed flex items-center justify-center gap-1.5">
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
                                        class="w-full bg-[#ea580c] hover:bg-[#c2410c] text-white font-semibold py-2.5 px-3 rounded-xl text-xs transition shadow-xs hover:shadow-sm active:scale-[0.98] cursor-pointer flex items-center justify-center gap-1.5">
                                        <span>Ajukan Permintaan</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                        </svg>
                                    </button>
                                @else
                                    <button 
                                        type="button"
                                        disabled
                                        class="w-full bg-slate-100 border border-slate-200/80 text-slate-400 font-semibold py-2.5 px-3 rounded-xl text-xs cursor-not-allowed">
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

        <!-- 1. ARCHITECTURAL HEADER -->
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 border-b border-slate-200/80 pb-5">
            <div>
                <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-[#ea580c] tracking-[0.14em] uppercase">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#ea580c]"></span>
                    <span>Kolaborasi Rantai Pasok</span>
                </span>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight mt-1">Collaborative Supply Planning</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl">Sampaikan proyeksi kebutuhan bahan baku dapur untuk periode mendatang agar pemasok dapat menyiapkan kuota panen dan stok secara presisi.</p>
            </div>
            <div class="shrink-0 self-start sm:self-end">
                <span class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white border border-slate-200/90 shadow-2xs text-xs font-semibold text-slate-700 tabular-nums">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Sinkronisasi Mitra Aktif</span>
                </span>
            </div>
        </div>

        <!-- 4 CLEAN KPI CARDS -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-1.5">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Total Rencana</span>
                <p class="text-2xl font-bold text-slate-900 tabular-nums" x-text="supplyPlans.length"></p>
                <p class="text-[11px] text-slate-400">Estimasi kebutuhan diajukan</p>
            </div>
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-1.5">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Siap 100%</span>
                <p class="text-2xl font-bold text-emerald-600 tabular-nums" x-text="planningCountApproved"></p>
                <p class="text-[11px] text-slate-400">Disetujui penuh oleh mitra</p>
            </div>
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-1.5">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Disesuaikan</span>
                <p class="text-2xl font-bold text-sky-600 tabular-nums" x-text="planningCountAdjusted"></p>
                <p class="text-[11px] text-slate-400">Penyesuaian kuota pemasok</p>
            </div>
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-1.5">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Menunggu</span>
                <p class="text-2xl font-bold text-amber-600 tabular-nums" x-text="planningCountPending"></p>
                <p class="text-[11px] text-slate-400">Menunggu tinjauan mitra</p>
            </div>
        </div>

        <!-- 2. MAIN 2-COLUMN SECTION: FORM (LEFT) & MANFAAT (RIGHT) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- LEFT: FORM INPUT ESTIMASI (8 COLS) -->
            <div class="lg:col-span-8 bg-white rounded-2xl p-6 sm:p-7 border border-slate-200/80 shadow-xs space-y-5">
                <div class="flex items-center gap-3.5 border-b border-slate-100 pb-4">
                    <div class="w-10 h-10 rounded-xl bg-orange-50 border border-orange-200/70 text-[#ea580c] flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight">Formulir Estimasi Kebutuhan Pasokan</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Kirimkan estimasi kuantitas dapur untuk periode mendatang langsung ke pemasok.</p>
                    </div>
                </div>

                <form action="{{ route('umkm.supply-planning.store') }}" method="POST" class="space-y-4 text-xs">
                    @csrf

                    <!-- Group 1: Pemasok & Komoditas -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-1.5 text-slate-700 font-semibold">
                                Mitra Pemasok Terpilih <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <select name="supplier_id" x-model="planningForm.supplier_id" required class="w-full pl-3.5 pr-9 py-2.5 rounded-xl bg-slate-50 border border-slate-200/90 text-slate-800 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/15 focus:border-[#ea580c] appearance-none cursor-pointer transition">
                                    <option value="" disabled>-- Pilih Mitra Pemasok --</option>
                                    @forelse($availableSuppliers as $sup)
                                        <option value="{{ $sup->id }}">{{ $sup->company_name }} ({{ $sup->delivery_area ?? 'Lokal' }})</option>
                                    @empty
                                        <option value="" disabled>Belum ada pemasok terdaftar</option>
                                    @endforelse
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block mb-1.5 text-slate-700 font-semibold">
                                Komoditas Bahan Baku <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <select name="commodity_name" x-model="planningForm.commodity" required class="w-full pl-3.5 pr-9 py-2.5 rounded-xl bg-slate-50 border border-slate-200/90 text-slate-800 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/15 focus:border-[#ea580c] appearance-none cursor-pointer transition">
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
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Group 2: Estimasi Volume + Satuan & Periode Bulan -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-1.5 text-slate-700 font-semibold">
                                Estimasi Kuantitas &amp; Satuan <span class="text-rose-500">*</span>
                            </label>
                            <div class="flex rounded-xl overflow-hidden bg-slate-50 border border-slate-200/90 focus-within:bg-white focus-within:border-[#ea580c] focus-within:ring-2 focus-within:ring-orange-500/15 transition">
                                <input type="number" step="0.1" min="0.1" name="estimated_quantity" x-model="planningForm.volume" required placeholder="Contoh: 200" class="w-full px-3.5 py-2.5 bg-transparent text-slate-900 font-semibold tabular-nums placeholder:text-slate-400 border-0 focus:outline-none" />
                                <select name="unit" x-model="planningForm.unit" class="bg-slate-100 border-l border-slate-200/80 px-3 py-2.5 text-slate-700 font-semibold focus:outline-none cursor-pointer text-xs">
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
                            <label class="block mb-1.5 text-slate-700 font-semibold">
                                Periode Bulan Target <span class="text-rose-500">*</span>
                            </label>
                            <input type="month" name="period_month_year" x-model="planningForm.targetDate" value="{{ now()->addMonth()->format('Y-m') }}" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200/90 text-slate-900 font-semibold tabular-nums focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/15 focus:border-[#ea580c] transition" />
                        </div>
                    </div>

                    <!-- Group 3: Catatan Khusus Kebutuhan Dapur -->
                    <div>
                        <label class="block mb-1.5 text-slate-700 font-semibold">
                            Catatan Khusus Kebutuhan Dapur (Opsional)
                        </label>
                        <textarea name="notes" x-model="planningForm.notes" rows="3" placeholder="Contoh: Potongan dada fillet dingin 4°C, pengiriman subuh jam 05.00 WIB 2x seminggu..." class="w-full p-3.5 rounded-xl bg-slate-50 border border-slate-200/90 text-slate-800 font-medium placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/15 focus:border-[#ea580c] transition"></textarea>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t border-slate-100">
                        <span class="text-[11px] text-slate-500 font-medium flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-[#ea580c] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Data langsung disinkronkan ke dasbor pemasok mitra</span>
                        </span>
                        <button type="submit" class="w-full sm:w-auto bg-[#ea580c] hover:bg-[#c2410c] text-white px-6 py-2.5 rounded-xl font-semibold shadow-xs hover:shadow-sm transition flex items-center justify-center gap-2 cursor-pointer active:scale-[0.98]">
                            <span>Kirim Estimasi ke Pemasok</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    </div>
                </form>
            </div>

            <!-- RIGHT: MANFAAT & ALUR KOLABORASI (4 COLS) -->
            <div class="lg:col-span-4 space-y-5">
                <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs space-y-5">
                    <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100">
                        <div class="w-8 h-8 rounded-xl bg-orange-50 text-[#ea580c] border border-orange-200/70 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800">
                            Keuntungan Perencanaan
                        </h4>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-xl bg-orange-50 text-[#ea580c] border border-orange-200/60 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h5 class="font-bold text-slate-900">Sinkronisasi Siklus Panen</h5>
                                <p class="text-[11px] text-slate-500 leading-relaxed mt-0.5">Pemasok menyiapkan kapasitas kandang dan panen sesuai kebutuhan bulanan Anda.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-200/60 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <div>
                                <h5 class="font-bold text-slate-900">Stabilitas Harga Grosir</h5>
                                <p class="text-[11px] text-slate-500 leading-relaxed mt-0.5">Dapur terhindar dari lonjakan harga mendadak akibat kelangkaan stok di pasar.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 border border-sky-200/60 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div>
                                <h5 class="font-bold text-slate-900">Prioritas Pasokan Subuh</h5>
                                <p class="text-[11px] text-slate-500 leading-relaxed mt-0.5">Dapur Anda masuk antrean alokasi pengiriman pertama sebelum operasional dimulai.</p>
                            </div>
                        </div>
                    </div>

                    <!-- 3-Step Flow Checklist -->
                    <div class="pt-4 border-t border-slate-100 space-y-2.5">
                        <span class="text-[10px] font-bold uppercase text-slate-400 block tracking-wider">Alur Kerja Kolaborasi</span>
                        <div class="space-y-2 text-[11px] text-slate-600 font-medium">
                            <div class="flex items-center gap-2.5">
                                <span class="w-5 h-5 rounded-md bg-orange-50 border border-orange-200/70 text-[#ea580c] text-[10px] font-bold flex items-center justify-center shrink-0 tabular-nums">1</span>
                                <span>UMKM kirim estimasi kebutuhan</span>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <span class="w-5 h-5 rounded-md bg-orange-50 border border-orange-200/70 text-[#ea580c] text-[10px] font-bold flex items-center justify-center shrink-0 tabular-nums">2</span>
                                <span>Pemasok konfirmasi kuota pasokan</span>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <span class="w-5 h-5 rounded-md bg-orange-50 border border-orange-200/70 text-[#ea580c] text-[10px] font-bold flex items-center justify-center shrink-0 tabular-nums">3</span>
                                <span>Pasokan terjamin aman &amp; stabil</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- 3. SECTION: RIWAYAT & STATUS KESEPAKATAN SUPPLY PLANNING -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 sm:p-7 space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                <div>
                    <h3 class="font-bold text-lg text-slate-900 tracking-tight">Riwayat Estimasi &amp; Status Respon Mitra</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Pantau konfirmasi kesiapan panen dan alokasi kuota pasokan dari pemasok mitra.</p>
                </div>
                <span class="text-xs font-semibold text-slate-600 bg-slate-50 border border-slate-200/90 px-3.5 py-1.5 rounded-xl self-start sm:self-auto tabular-nums">
                    Total: <strong class="text-[#ea580c]" x-text="supplyPlans.length"></strong> Permintaan
                </span>
            </div>

            <!-- FILTER BAR -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 w-full">
                <div class="md:col-span-5 lg:col-span-6 relative w-full">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input type="text"
                           x-model="planningSearchQuery"
                           placeholder="Cari komoditas bahan baku atau nama pemasok mitra..."
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200/90 text-xs font-medium text-slate-800 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/15 focus:border-[#ea580c] transition" />
                </div>

                <div class="md:col-span-7 lg:col-span-6 grid grid-cols-4 gap-1 bg-slate-100 p-1 rounded-xl border border-slate-200/60 text-xs font-semibold w-full">
                    <button type="button"
                            @click="planningFilterStatus = 'all'"
                            :class="planningFilterStatus === 'all' ? 'bg-[#ea580c] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/60'"
                            class="py-2 px-1 text-center rounded-lg transition text-[11px] cursor-pointer flex items-center justify-center gap-1 tabular-nums">
                        <span>Semua</span>
                        <span class="opacity-80 text-[10px]" x-text="'(' + supplyPlans.length + ')'"></span>
                    </button>
                    <button type="button"
                            @click="planningFilterStatus = 'MENUNGGU_KONFIRMASI'"
                            :class="planningFilterStatus === 'MENUNGGU_KONFIRMASI' ? 'bg-[#ea580c] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/60'"
                            class="py-2 px-1 text-center rounded-lg transition text-[11px] cursor-pointer flex items-center justify-center gap-1 tabular-nums">
                        <span>Menunggu</span>
                        <span class="opacity-80 text-[10px]" x-text="'(' + planningCountPending + ')'"></span>
                    </button>
                    <button type="button"
                            @click="planningFilterStatus = 'DISETUJUI'"
                            :class="planningFilterStatus === 'DISETUJUI' ? 'bg-[#ea580c] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/60'"
                            class="py-2 px-1 text-center rounded-lg transition text-[11px] cursor-pointer flex items-center justify-center gap-1 tabular-nums">
                        <span>Disetujui</span>
                        <span class="opacity-80 text-[10px]" x-text="'(' + planningCountApproved + ')'"></span>
                    </button>
                    <button type="button"
                            @click="planningFilterStatus = 'DISESUAIKAN'"
                            :class="planningFilterStatus === 'DISESUAIKAN' ? 'bg-[#ea580c] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/60'"
                            class="py-2 px-1 text-center rounded-lg transition text-[11px] cursor-pointer flex items-center justify-center gap-1 tabular-nums">
                        <span>Disesuaikan</span>
                        <span class="opacity-80 text-[10px]" x-text="'(' + planningCountAdjusted + ')'"></span>
                    </button>
                </div>
            </div>

            <!-- TABLE -->
            <div class="overflow-x-auto rounded-xl border border-slate-200/80">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-50/90 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-200/80 text-[11px]">
                        <tr>
                            <th class="px-4 py-3.5">ID &amp; Waktu</th>
                            <th class="px-4 py-3.5">Mitra Pemasok</th>
                            <th class="px-4 py-3.5">Bahan Baku &amp; Volume</th>
                            <th class="px-4 py-3.5">Periode Target</th>
                            <th class="px-4 py-3.5">Status Kolaborasi</th>
                            <th class="px-4 py-3.5">Catatan Respon Mitra</th>
                            <th class="px-4 py-3.5 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        <template x-for="plan in filteredSupplyPlans" :key="plan.id">
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="px-4 py-3.5 whitespace-nowrap tabular-nums">
                                    <span class="font-bold text-[#ea580c] block" x-text="plan.id"></span>
                                    <span class="text-[10px] text-slate-400 block mt-0.5" x-text="plan.created_at || '-'"></span>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-lg bg-orange-50 text-[#ea580c] border border-orange-200/70 flex items-center justify-center shrink-0">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                        </div>
                                        <span class="font-semibold text-slate-900" x-text="plan.supplier"></span>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap tabular-nums">
                                    <span class="font-semibold text-slate-900 block" x-text="plan.commodity"></span>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-slate-600 text-[11px]" x-text="'Permintaan: ' + plan.volume + ' ' + plan.unit"></span>
                                        <template x-if="plan.confirmed_volume">
                                            <span class="text-emerald-700 bg-emerald-50 border border-emerald-200/80 px-1.5 py-0.5 rounded-md font-semibold text-[10px]" x-text="'Disetujui: ' + plan.confirmed_volume + ' ' + plan.unit"></span>
                                        </template>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 font-semibold text-slate-800 whitespace-nowrap tabular-nums">
                                    <span class="bg-slate-100 text-slate-700 border border-slate-200/70 px-2.5 py-1 rounded-lg text-[11px] inline-flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-[#ea580c]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span x-text="plan.targetMonth"></span>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <template x-if="plan.status === 'DISETUJUI'">
                                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-semibold inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                            <span>Disetujui Penuh</span>
                                        </span>
                                    </template>
                                    <template x-if="plan.status === 'DISESUAIKAN'">
                                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-semibold inline-flex items-center gap-1.5 bg-sky-50 text-sky-700 border border-sky-200/80">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                            <span>Disesuaikan Pemasok</span>
                                        </span>
                                    </template>
                                    <template x-if="plan.status === 'DITOLAK'">
                                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-semibold inline-flex items-center gap-1.5 bg-rose-50 text-rose-700 border border-rose-200/80">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                                            <span>Kapasitas Penuh</span>
                                        </span>
                                    </template>
                                    <template x-if="plan.status === 'MENUNGGU_KONFIRMASI' || plan.status === 'DALAM_REVIEW'">
                                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-semibold inline-flex items-center gap-1.5 bg-amber-50 text-amber-800 border border-amber-200/80">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            <span>Menunggu Konfirmasi</span>
                                        </span>
                                    </template>
                                </td>
                                <td class="px-4 py-3.5 text-slate-600 text-[11px] max-w-xs">
                                    <template x-if="plan.supplier_notes">
                                        <div class="bg-orange-50/60 border border-orange-200/70 rounded-xl p-2.5 text-slate-800 space-y-0.5">
                                            <span class="font-bold text-[10px] text-[#ea580c] uppercase block">Respon Pemasok:</span>
                                            <span x-text="plan.supplier_notes"></span>
                                        </div>
                                    </template>
                                    <template x-if="!plan.supplier_notes">
                                        <span class="italic text-slate-400" x-text="plan.note || 'Menunggu peninjauan kuota panen/stok mitra.'"></span>
                                    </template>
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <template x-if="plan.can_delete && plan.uuid">
                                        <form :action="'{{ url('/umkm/supply-planning') }}/' + plan.uuid" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan estimasi kebutuhan ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-rose-600 hover:text-white hover:bg-rose-600 border border-rose-200 px-3 py-1.5 rounded-xl font-semibold transition inline-flex items-center gap-1 cursor-pointer">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                                <span>Batalkan</span>
                                            </button>
                                        </form>
                                    </template>
                                    <template x-if="!plan.can_delete || !plan.uuid">
                                        <span class="text-slate-300 text-xs">—</span>
                                    </template>
                                </td>
                            </tr>
                        </template>

                        <template x-if="filteredSupplyPlans.length === 0">
                            <tr>
                                <td colspan="7" class="p-10 text-center text-slate-500">
                                    <div class="py-8 space-y-3">
                                        <div class="w-12 h-12 rounded-2xl bg-orange-50 text-[#ea580c] border border-orange-200/70 flex items-center justify-center mx-auto">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <h4 class="font-bold text-slate-900 text-sm">Tidak Ada Data Estimasi Pasokan</h4>
                                        <p class="text-xs text-slate-500 max-w-md mx-auto">
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
    <!-- TAB: PESANAN & PENGIRIMAN (5 SUB-TABS)                     -->
    <!-- ========================================================= -->
    <div x-cloak x-show="activeTab === 'pemesanan'" class="space-y-6">

        <!-- HEADER TITLE -->
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 border-b border-slate-200/80 pb-5">
            <div>
                <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-[#ea580c] tracking-[0.14em] uppercase">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#ea580c]"></span>
                    <span>Daftar Transaksi Aktif</span>
                </span>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight mt-1">Pesanan &amp; Pengiriman</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl">Kelola jadwal pemesanan otomatis kontrak terikat, konfirmasi kedatangan fisik bahan baku, pembayaran Midtrans, serta evaluasi mutu pemasok.</p>
            </div>
        </div>

        <!-- SUB-NAVIGASI TAB CLEAN UNDERLINE DESIGN -->
        <div class="border-b border-slate-200/90 flex items-center gap-6 sm:gap-8 text-xs font-semibold overflow-x-auto">
            <!-- 1. Pemesanan Otomatis -->
            <button 
                type="button"
                @click="orderSubTab = 'auto-order'"
                class="pb-3.5 relative transition flex items-center gap-2 cursor-pointer whitespace-nowrap shrink-0"
                :class="orderSubTab === 'auto-order' ? 'text-slate-900 font-bold' : 'text-slate-500 hover:text-slate-900'">
                <span>Pemesanan Otomatis</span>
                <template x-if="orderSubTab === 'auto-order'">
                    <span class="absolute bottom-0 left-0 w-full h-0.5 bg-[#ea580c] rounded-full"></span>
                </template>
            </button>

            <!-- 2. Persetujuan Backorder -->
            <button 
                type="button"
                @click="orderSubTab = 'backorder'"
                class="pb-3.5 relative transition flex items-center gap-2 cursor-pointer whitespace-nowrap shrink-0"
                :class="orderSubTab === 'backorder' ? 'text-slate-900 font-bold' : 'text-slate-500 hover:text-slate-900'">
                <span>Persetujuan Backorder</span>
                <template x-if="pendingBackorderApprovalCount > 0">
                    <span class="bg-rose-600 text-white text-[10px] px-2 py-0.5 rounded-md font-semibold tabular-nums animate-pulse"
                          x-text="pendingBackorderApprovalCount + ' Pengajuan'">
                    </span>
                </template>
                <template x-if="pendingBackorderApprovalCount === 0 && pendingBackorderCount > 0">
                    <span class="bg-amber-100 text-amber-900 border border-amber-200 text-[10px] px-2 py-0.5 rounded-md font-bold tabular-nums"
                          x-text="pendingBackorderCount">
                    </span>
                </template>
                <template x-if="orderSubTab === 'backorder'">
                    <span class="absolute bottom-0 left-0 w-full h-0.5 bg-[#ea580c] rounded-full"></span>
                </template>
            </button>

            <!-- 3. Konfirmasi Pesanan -->
            <button 
                type="button"
                @click="orderSubTab = 'confirm'"
                class="pb-3.5 relative transition flex items-center gap-2 cursor-pointer whitespace-nowrap shrink-0"
                :class="orderSubTab === 'confirm' ? 'text-slate-900 font-bold' : 'text-slate-500 hover:text-slate-900'">
                <span>Konfirmasi Pesanan</span>
                <template x-if="pendingValidationCount > 0">
                    <span class="bg-[#ea580c] text-white text-[10px] px-2 py-0.5 rounded-md font-semibold tabular-nums"
                          x-text="pendingValidationCount">
                    </span>
                </template>
                <template x-if="orderSubTab === 'confirm'">
                    <span class="absolute bottom-0 left-0 w-full h-0.5 bg-[#ea580c] rounded-full"></span>
                </template>
            </button>

            <!-- 4. Pembayaran Midtrans -->
            <button 
                type="button"
                @click="orderSubTab = 'payment'"
                class="pb-3.5 relative transition flex items-center gap-2 cursor-pointer whitespace-nowrap shrink-0"
                :class="orderSubTab === 'payment' ? 'text-slate-900 font-bold' : 'text-slate-500 hover:text-slate-900'">
                <span>Pembayaran Midtrans</span>
                <template x-if="pendingPaymentCount > 0">
                    <span class="bg-amber-500 text-white text-[10px] px-2 py-0.5 rounded-md font-semibold tabular-nums"
                          x-text="pendingPaymentCount">
                    </span>
                </template>
                <template x-if="orderSubTab === 'payment'">
                    <span class="absolute bottom-0 left-0 w-full h-0.5 bg-[#ea580c] rounded-full"></span>
                </template>
            </button>

            <!-- 5. Rating & Ulasan -->
            <button 
                type="button"
                @click="orderSubTab = 'rating'"
                class="pb-3.5 relative transition flex items-center gap-2 cursor-pointer whitespace-nowrap shrink-0"
                :class="orderSubTab === 'rating' ? 'text-slate-900 font-bold' : 'text-slate-500 hover:text-slate-900'">
                <span>Rating &amp; Ulasan</span>
                @if(isset($reviewOrders) && $reviewOrders->isNotEmpty())
                    <span class="bg-orange-50 text-[#ea580c] border border-orange-200/70 text-[10px] px-2 py-0.5 rounded-md font-bold tabular-nums">
                        {{ $reviewOrders->count() }}
                    </span>
                @endif
                <template x-if="orderSubTab === 'rating'">
                    <span class="absolute bottom-0 left-0 w-full h-0.5 bg-[#ea580c] rounded-full"></span>
                </template>
            </button>
        </div>

        <!-- SUB-TAB 1: PEMESANAN OTOMATIS -->
        <div x-show="orderSubTab === 'auto-order'" class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden p-6 space-y-5 shadow-xs">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-3 border-b border-slate-100 pb-4">
                <div>
                    <span class="text-[10px] font-bold text-[#ea580c] uppercase tracking-wider">Kontrak Terikat Aktif</span>
                    <h3 class="font-bold text-lg text-slate-900 mt-0.5">Daftar Jadwal Pemesanan Otomatis</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Pesanan rutin di bawah ini diterbitkan otomatis berdasarkan kontrak terikat dengan pemasok mitra.</p>
                </div>
                <span class="bg-orange-50 border border-orange-200/80 text-[#ea580c] font-semibold text-xs px-3.5 py-1.5 rounded-xl w-fit tabular-nums">
                    <span x-text="autoOrdersList.length"></span> Kontrak Aktif
                </span>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200/80">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-50/90 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-200/80 text-[11px]">
                        <tr>
                            <th class="px-4 py-3.5">ID Auto Order</th>
                            <th class="px-4 py-3.5">ID Kontrak</th>
                            <th class="px-4 py-3.5">Pemasok Mitra</th>
                            <th class="px-4 py-3.5">Komoditas &amp; Volume</th>
                            <th class="px-4 py-3.5">Total Nilai Kontrak</th>
                            <th class="px-4 py-3.5">Jadwal Pengiriman</th>
                            <th class="px-4 py-3.5 text-center">Aksi Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        <template x-for="autoOrd in autoOrdersList" :key="autoOrd.id">
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="px-4 py-3.5 font-bold text-[#ea580c] whitespace-nowrap tabular-nums" x-text="autoOrd.id"></td>
                                <td class="px-4 py-3.5 text-slate-600 whitespace-nowrap tabular-nums" x-text="autoOrd.contractId"></td>
                                <td class="px-4 py-3.5 font-semibold text-slate-900" x-text="autoOrd.supplierName"></td>
                                <td class="px-4 py-3.5 tabular-nums">
                                    <span class="text-slate-700" x-text="autoOrd.commodity"></span>
                                    <span class="font-bold text-slate-900" x-text="' (' + autoOrd.qty + ' ' + autoOrd.unit + ')'"></span>
                                </td>
                                <td class="px-4 py-3.5 font-bold text-slate-900 whitespace-nowrap tabular-nums" x-text="'Rp ' + autoOrd.total.toLocaleString('id-ID')"></td>
                                <td class="px-4 py-3.5">
                                    <template x-if="isPublishedToday(autoOrd)">
                                        <div class="space-y-1">
                                            <span class="inline-flex items-center gap-1 bg-emerald-50 border border-emerald-200/80 text-emerald-700 text-[10px] font-semibold px-2.5 py-0.5 rounded-lg">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                                <span>Terbit Hari Ini</span>
                                            </span>
                                            <span class="block font-semibold text-slate-700 text-[11px]">Jadwal Selanjutnya:</span>
                                            <span class="block text-emerald-700 font-bold text-[11px]" x-text="getNextDeliveryScheduleText(autoOrd)"></span>
                                        </div>
                                    </template>
                                    <template x-if="!isPublishedToday(autoOrd)">
                                        <div>
                                            <span class="block font-semibold text-slate-900" x-text="autoOrd.nextDelivery"></span>
                                            <span class="text-[10px] text-slate-500" x-text="autoOrd.deliverySchedule"></span>
                                        </div>
                                    </template>
                                </td>
                                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                    <template x-if="!isPublishedToday(autoOrd)">
                                        <button 
                                            type="button"
                                            @click="previewAutoOrder = autoOrd"
                                            class="bg-orange-50 hover:bg-[#ea580c] hover:text-white text-[#ea580c] border border-orange-200/80 px-3.5 py-1.5 rounded-xl font-semibold text-[11px] transition inline-flex items-center gap-1.5 cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <span>Preview &amp; Terbitkan</span>
                                        </button>
                                    </template>
                                    <template x-if="isPublishedToday(autoOrd)">
                                        <button 
                                            type="button"
                                            @click="previewAutoOrder = autoOrd"
                                            class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200/80 px-3.5 py-1.5 rounded-xl font-semibold text-[11px] transition inline-flex items-center gap-1.5 cursor-pointer">
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
                                <td colspan="7" class="p-10 text-center text-slate-500">
                                    <div class="py-6 space-y-3 max-w-md mx-auto">
                                        <div class="w-12 h-12 mx-auto rounded-2xl bg-orange-50 border border-orange-200/70 flex items-center justify-center text-[#ea580c]">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                                            </svg>
                                        </div>
                                        <p class="font-bold text-slate-900 text-sm">Belum Ada Jadwal Pemesanan Otomatis Aktif</p>
                                        <p class="text-xs text-slate-500 leading-relaxed">
                                            Data kontrak pemesanan rutin akan tampil otomatis setelah pengajuan kemitraan pasokan Anda disetujui oleh pemasok mitra.
                                        </p>
                                        <div class="pt-1">
                                            <button type="button" @click="activeTab = 'katalog'" class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#ea580c] hover:bg-[#c2410c] text-white rounded-xl text-xs font-semibold transition shadow-xs cursor-pointer">
                                                <span>Pilih Bahan Baku di Katalog</span>
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
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
        <div x-show="orderSubTab === 'confirm'" class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden p-6 space-y-5 shadow-xs">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2 border-b border-slate-100 pb-4">
                <div>
                    <h3 class="font-bold text-lg text-slate-900">Validasi &amp; Konfirmasi Kedatangan Barang (Batch 1)</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Periksa kuantitas dan mutu bahan baku saat tiba di dapur. Unggah foto bukti penerimaan fisik untuk mengkonfirmasi.</p>
                </div>
            </div>

            @php
                $umkmProfile = auth()->user()?->umkmProfile;
            @endphp

            @if($dbOrders->isEmpty())
                <div class="py-14 text-center space-y-3">
                    <div class="w-12 h-12 mx-auto rounded-2xl bg-orange-50 border border-orange-200/70 flex items-center justify-center text-[#ea580c]">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <p class="font-bold text-slate-900 text-sm">Belum ada pesanan yang perlu dikonfirmasi</p>
                        <p class="text-xs text-slate-500">Pesanan yang dikirim oleh pemasok akan muncul di sini untuk divalidasi.</p>
                    </div>
                </div>
            @else
                <div class="overflow-x-auto rounded-xl border border-slate-200/80">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-slate-50/90 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-200/80 text-[11px]">
                            <tr>
                                <th class="px-4 py-3.5">ID Pesanan</th>
                                <th class="px-4 py-3.5">Pemasok Mitra</th>
                                <th class="px-4 py-3.5">Detail Komoditas &amp; Pemenuhan</th>
                                <th class="px-4 py-3.5">Total Tagihan</th>
                                <th class="px-4 py-3.5">Status Pengiriman</th>
                                <th class="px-4 py-3.5">Bukti Foto</th>
                                <th class="px-4 py-3.5 text-center">Aksi Konfirmasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
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
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="px-4 py-3.5 font-bold text-[#ea580c] whitespace-nowrap tabular-nums">
                                        #ORD-{{ strtoupper(substr($dbOrder->id, 0, 8)) }}
                                    </td>
                                    <td class="px-4 py-3.5 font-semibold text-slate-900">
                                        {{ $dbOrder->partnership?->supplier?->company_name ?? 'Pemasok Mitra' }}
                                    </td>
                                    <td class="px-4 py-3.5 space-y-1.5 tabular-nums">
                                        @foreach($dbOrder->items as $item)
                                            <span class="font-semibold text-slate-900 block text-xs">
                                                {{ $item->supplierProduct?->commodity?->name ?? 'Bahan Baku' }}
                                            </span>
                                        @endforeach

                                        <!-- LABEL RINCIAN PEMENUHAN (SPLIT SHIPMENT / BACKORDER) -->
                                        @if($dbOrder->order_status === 'cancelled')
                                            <div class="bg-rose-50 border border-rose-200/80 text-rose-700 px-2.5 py-1.5 rounded-lg text-[11px] leading-tight inline-flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                                <span>Pesanan Dibatalkan: Stok gudang pemasok kosong.</span>
                                            </div>
                                        @elseif($batch1 && $batch1->backorder_action === 'create_backorder')
                                            <div class="bg-orange-50/80 border border-orange-200/80 text-orange-950 px-2.5 py-1.5 rounded-lg text-[11px] leading-tight">
                                                <span>Terkirim Parsial: <strong>{{ number_format($deliveredQty, 0) }}/{{ number_format($totalQty, 0) }} {{ $unit }}</strong></span>
                                                <span class="text-[#ea580c] block font-semibold mt-0.5">— Sisa {{ number_format($remainingQty, 0) }} {{ $unit }} dalam antrean Backorder</span>
                                            </div>
                                        @elseif($batch1 && $batch1->backorder_action === 'no_backorder')
                                            <div class="bg-rose-50 border border-rose-200/80 text-rose-950 px-2.5 py-1.5 rounded-lg text-[11px] leading-tight">
                                                <span>Terkirim Parsial: <strong>{{ number_format($deliveredQty, 0) }}/{{ number_format($totalQty, 0) }} {{ $unit }}</strong></span>
                                                <span class="text-rose-700 block font-semibold mt-0.5">— Sisa {{ number_format(max(0, $totalQty - $deliveredQty), 0) }} {{ $unit }} Dibatalkan (Tagihan Disesuaikan)</span>
                                            </div>
                                        @else
                                            <div class="text-slate-500 text-[11px]">
                                                Pengiriman Penuh: <strong class="text-slate-700">{{ number_format($totalQty, 0) }} {{ $unit }}</strong>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 font-bold text-slate-900 whitespace-nowrap tabular-nums">
                                        Rp {{ number_format($dbOrder->total_amount, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3.5">
                                        @if($dbOrder->order_status === 'cancelled')
                                            <span class="bg-rose-50 border border-rose-200/80 text-rose-700 px-2.5 py-1 rounded-lg text-[10px] font-semibold inline-flex items-center gap-1">
                                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                                <span>Dibatalkan Pemasok</span>
                                            </span>
                                        @elseif(in_array($dbOrder->order_status, ['pending', 'confirmed']))
                                            <span class="bg-amber-50 border border-amber-200/80 text-amber-800 px-2.5 py-1 rounded-lg text-[10px] font-semibold inline-flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                <span>Menunggu Pemasok Cek Stok</span>
                                            </span>
                                        @elseif($batch1 && $batch1->backorder_action === 'create_backorder')
                                            @if($batch1->shipped_at === null)
                                                @if($batch1->backorder_approval_status === 'pending' || $dbOrder->order_status === 'waiting_backorder_approval')
                                                    <span class="bg-amber-50 text-amber-900 border border-amber-300/80 px-2.5 py-1 rounded-lg text-[10px] font-semibold inline-flex items-center gap-1.5">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                        <span>Menunggu Persetujuan Backorder</span>
                                                    </span>
                                                @else
                                                    <span class="bg-purple-50 border border-purple-200/80 text-purple-800 px-2.5 py-1 rounded-lg text-[10px] font-semibold inline-flex items-center gap-1.5">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-purple-500 animate-pulse"></span>
                                                        <span>Disetujui — Menunggu Pengiriman</span>
                                                    </span>
                                                @endif
                                            @elseif(! $isBatch1Confirmed)
                                                <span class="bg-sky-50 border border-sky-200/80 text-sky-800 px-2.5 py-1 rounded-lg text-[10px] font-semibold inline-flex items-center gap-1.5 tabular-nums">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-500 animate-ping"></span>
                                                    <span>Kloter 1 Dikirim ({{ number_format($deliveredQty, 0) }} {{ $unit }})</span>
                                                </span>
                                            @elseif($isBatch2Shipped && ! $isBatch2Confirmed)
                                                <span class="bg-sky-50 text-sky-900 border border-sky-300/80 px-2.5 py-1 rounded-lg text-[10px] font-semibold inline-flex items-center gap-1.5 animate-pulse">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-600"></span>
                                                    <span>Kloter 2 (Backorder) Sedang Dikirim</span>
                                                </span>
                                            @elseif($isBatch2Confirmed)
                                                <span class="bg-emerald-50 border border-emerald-200/80 text-emerald-700 px-2.5 py-1 rounded-lg text-[10px] font-semibold inline-flex items-center gap-1">
                                                    <svg class="w-3 h-3 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    <span>Selesai Lengkap (Kloter 1 &amp; 2)</span>
                                                </span>
                                            @else
                                                <span class="bg-purple-50 border border-purple-200/80 text-purple-800 px-2.5 py-1 rounded-lg text-[10px] font-semibold inline-flex items-center gap-1">
                                                    <svg class="w-3 h-3 text-purple-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    <span>Kloter 1 Diterima (Menunggu Kloter 2)</span>
                                                </span>
                                            @endif
                                        @elseif($batch1 && $batch1->backorder_action === 'no_backorder')
                                            @if(! $isBatch1Confirmed)
                                                <span class="bg-sky-50 border border-sky-200/80 text-sky-800 px-2.5 py-1 rounded-lg text-[10px] font-semibold inline-flex items-center gap-1.5 tabular-nums">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-500 animate-ping"></span>
                                                    <span>Armada Mengirim ({{ number_format($deliveredQty, 0) }} {{ $unit }})</span>
                                                </span>
                                            @else
                                                <span class="bg-slate-100 border border-slate-200/80 text-slate-700 px-2.5 py-1 rounded-lg text-[10px] font-semibold inline-flex items-center gap-1">
                                                    <svg class="w-3 h-3 text-slate-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    <span>Diterima (Selesai Sebagian)</span>
                                                </span>
                                            @endif
                                        @else
                                            @if(! $isBatch1Confirmed)
                                                <span class="bg-sky-50 border border-sky-200/80 text-sky-800 px-2.5 py-1 rounded-lg text-[10px] font-semibold inline-flex items-center gap-1.5">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-500 animate-ping"></span>
                                                    <span>Armada Sedang Mengirim</span>
                                                </span>
                                            @else
                                                <span class="bg-emerald-50 border border-emerald-200/80 text-emerald-700 px-2.5 py-1 rounded-lg text-[10px] font-semibold inline-flex items-center gap-1">
                                                    <svg class="w-3 h-3 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    <span>Barang Diterima Penuh</span>
                                                </span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <div class="flex items-center gap-1.5">
                                            @if($batch1?->proof_photo_url)
                                                <a href="{{ $batch1->proof_photo_url }}" target="_blank" title="Bukti Foto Kloter 1">
                                                    <img src="{{ $batch1->proof_photo_url }}"
                                                         alt="Bukti Kloter 1"
                                                         class="w-10 h-10 object-cover rounded-xl border border-slate-200 hover:scale-105 transition cursor-pointer shadow-2xs">
                                                </a>
                                            @endif
                                            @if($batch2?->proof_photo_url)
                                                <a href="{{ $batch2->proof_photo_url }}" target="_blank" title="Bukti Foto Kloter 2 (Sisa Backorder)">
                                                    <img src="{{ $batch2->proof_photo_url }}"
                                                         alt="Bukti Kloter 2"
                                                         class="w-10 h-10 object-cover rounded-xl border-2 border-amber-400 hover:scale-105 transition cursor-pointer shadow-2xs">
                                                </a>
                                            @endif
                                            @if(! $batch1?->proof_photo_url && ! $batch2?->proof_photo_url)
                                                <span class="text-slate-300 text-[11px]">—</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        @if($dbOrder->order_status === 'cancelled')
                                            <span class="text-xs text-rose-600 font-semibold">Pesanan Batal</span>
                                        @elseif(in_array($dbOrder->order_status, ['pending', 'confirmed']))
                                            <span class="text-xs text-slate-400 font-medium italic">Menunggu Armada Dikirim</span>
                                        @elseif($batch1 && $batch1->shipped_at === null)
                                            <span class="text-xs text-amber-700 font-medium">
                                                @if($batch1->backorder_approval_status === 'pending' || $dbOrder->order_status === 'waiting_backorder_approval')
                                                    <button type="button" @click="orderSubTab = 'backorder'" class="text-[#ea580c] font-semibold underline cursor-pointer">
                                                        Tinjau di Tab Backorder
                                                    </button>
                                                @else
                                                    Pemasok Sedang Siapkan Barang
                                                @endif
                                            </span>
                                        @elseif(! $isBatch1Confirmed)
                                            <button
                                                type="button"
                                                @click="confirmingOrderId = '{{ $dbOrder->id }}'; confirmPhotoPreview = null; confirmNotes = '';"
                                                class="bg-[#ea580c] hover:bg-[#c2410c] text-white px-3.5 py-2 rounded-xl font-semibold text-[11px] shadow-xs transition cursor-pointer whitespace-nowrap inline-flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                @if($batch1 && $batch1->backorder_action === 'create_backorder')
                                                    <span>Konfirmasi Terima Kloter 1</span>
                                                @else
                                                    <span>Konfirmasi Penerimaan Fisik</span>
                                                @endif
                                            </button>
                                        @elseif($batch1 && $batch1->backorder_action === 'create_backorder' && $isBatch2Shipped && ! $isBatch2Confirmed)
                                            <button
                                                type="button"
                                                @click="confirmingBackorderId = '{{ $dbOrder->id }}'; confirmBackorderPhotoPreview = null; confirmBackorderNotes = '';"
                                                class="bg-amber-500 hover:bg-amber-600 text-white px-3.5 py-2 rounded-xl font-semibold text-[11px] shadow-xs transition cursor-pointer whitespace-nowrap animate-pulse inline-flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                <span>Konfirmasi Sisa Backorder (Kloter 2)</span>
                                            </button>
                                        @else
                                            <div class="space-y-1.5">
                                                <span class="text-emerald-700 font-semibold text-[11px] inline-flex items-center gap-1">
                                                    <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    @if($isBatch2Confirmed)
                                                        <span>Selesai Lengkap (Kloter 1 &amp; 2)</span>
                                                    @elseif($batch1 && $batch1->backorder_action === 'create_backorder')
                                                        <span>Kloter 1 Diterima</span>
                                                    @else
                                                        <span>Barang Diterima</span>
                                                    @endif
                                                </span>
                                                <button type="button"
                                                        @click="orderSubTab = 'payment'"
                                                        class="bg-orange-50 hover:bg-orange-100 text-[#ea580c] px-3 py-1 rounded-lg font-semibold text-[10px] block mx-auto border border-orange-200/80 cursor-pointer transition">
                                                    Bayar Tagihan Invoice →
                                                </button>
                                                @if($batch1 && $batch1->backorder_action === 'create_backorder' && ! $isBatch2Shipped)
                                                    <span class="text-[10px] text-slate-400 block">
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

                @if(isset($dbOrders) && method_exists($dbOrders, 'hasPages') && $dbOrders->hasPages())
                    <div class="pt-2">
                        {{ $dbOrders->links() }}
                    </div>
                @endif
            @endif
        </div>

        <!-- MODAL KONFIRMASI PENERIMAAN BATCH 1 + UPLOAD FOTO (TERHUBUNG DATABASE) -->
        <div x-cloak x-show="confirmingOrderId" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="confirmingOrderId = null" class="bg-white max-w-lg w-full rounded-2xl p-6 space-y-5 shadow-xl border border-slate-200/80">
                <div class="flex justify-between items-start border-b border-slate-100 pb-4">
                    <div>
                        <span class="text-[11px] font-bold text-[#ea580c] uppercase tracking-wider">Konfirmasi Penerimaan Fisik Batch 1</span>
                        <h3 class="text-lg font-bold text-slate-900 mt-1">Upload Bukti Penerimaan Dapur</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Foto barang kloter pertama yang sudah tiba di dapur Anda sebagai bukti konfirmasi resmi.</p>
                    </div>
                    <button type="button" @click="confirmingOrderId = null" class="text-slate-400 hover:text-slate-700 cursor-pointer p-1.5 rounded-lg hover:bg-slate-100 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>

                <template x-if="confirmingOrderId">
                    <form :action="`/umkm/pemesanan/${confirmingOrderId}/konfirmasi`" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-2">Foto Bukti Penerimaan Barang Batch 1 <span class="text-rose-500">*</span></label>
                            <div class="border-2 border-dashed border-slate-200 rounded-xl p-4 text-center cursor-pointer hover:border-[#ea580c] bg-slate-50/50 transition"
                                 @click="$refs.photoInput.click()">
                                <template x-if="!confirmPhotoPreview">
                                    <div class="space-y-2 py-3">
                                        <div class="w-11 h-11 mx-auto rounded-xl bg-orange-50 border border-orange-200/70 flex items-center justify-center text-[#ea580c]">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </div>
                                        <p class="text-xs text-slate-700 font-semibold">Klik untuk pilih foto atau seret ke sini</p>
                                        <p class="text-[11px] text-slate-400">JPG, PNG, WEBP — Maks. 5MB</p>
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

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Catatan Kondisi Kedatangan (Opsional)</label>
                            <textarea
                                name="confirmation_notes"
                                rows="3"
                                x-model="confirmNotes"
                                placeholder="Contoh: Kondisi bahan baku segar, kuantitas kloter 1 sesuai timbangan dapur."
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200/90 text-slate-900 text-xs font-medium placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/15 focus:border-[#ea580c] transition">
                            </textarea>
                        </div>

                        <div class="flex gap-3 pt-2">
                            <button type="button" @click="confirmingOrderId = null"
                                class="w-1/2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200/90 py-2.5 rounded-xl font-semibold text-xs transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit"
                                class="w-1/2 bg-[#ea580c] hover:bg-[#c2410c] text-white py-2.5 rounded-xl font-semibold text-xs shadow-xs transition cursor-pointer inline-flex items-center justify-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>Konfirmasi Batch 1</span>
                            </button>
                        </div>
                    </form>
                </template>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- SUB-TAB 3: PESANAN BACKORDER                             -->
        <!-- ======================================================== -->
        <div x-show="orderSubTab === 'backorder'" class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden p-6 space-y-5 shadow-xs">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-3 border-b border-slate-100 pb-4">
                <div>
                    <span class="inline-flex items-center gap-1.5 text-[10px] font-bold text-amber-800 uppercase tracking-wider bg-amber-50 px-2.5 py-0.5 rounded-lg border border-amber-200/80">
                        Split Shipment / Backorder
                    </span>
                    <h3 class="font-bold text-lg text-slate-900 mt-1.5">Pemantauan Pengiriman Sisa Pasokan (Backorder)</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Pesanan di bawah ini dikirim secara bertahap oleh pemasok mitra. Konfirmasi kedatangan fisik Batch 2 saat sisa barang tiba di dapur.
                    </p>
                </div>
                <span class="bg-amber-50 border border-amber-200/80 text-amber-900 font-semibold text-xs px-3.5 py-1.5 rounded-xl w-fit tabular-nums">
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
            <div class="flex flex-wrap items-center gap-2 pt-1">
                <button type="button"
                        @click="backorderFilter = 'all'"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition cursor-pointer tabular-nums"
                        :class="backorderFilter === 'all' ? 'bg-[#ea580c] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200/70'">
                    Semua ({{ $backorderOrders->count() }})
                </button>
                <button type="button"
                        @click="backorderFilter = 'pending'"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition cursor-pointer inline-flex items-center gap-1.5 tabular-nums"
                        :class="backorderFilter === 'pending' ? 'bg-amber-500 text-white shadow-xs' : 'bg-amber-50 text-amber-900 border border-amber-200/80 hover:bg-amber-100'">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Menunggu Keputusan</span>
                    @if($countPendingApproval > 0)
                        <span class="bg-rose-600 text-white text-[10px] px-1.5 py-0.5 rounded-md font-bold">
                            {{ $countPendingApproval }}
                        </span>
                    @endif
                </button>
                <button type="button"
                        @click="backorderFilter = 'approved'"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition cursor-pointer inline-flex items-center gap-1.5 tabular-nums"
                        :class="backorderFilter === 'approved' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-emerald-50 text-emerald-800 border border-emerald-200/80 hover:bg-emerald-100'">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span>Disetujui ({{ $countApproved }})</span>
                </button>
                <button type="button"
                        @click="backorderFilter = 'rejected'"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition cursor-pointer inline-flex items-center gap-1.5 tabular-nums"
                        :class="backorderFilter === 'rejected' ? 'bg-rose-600 text-white shadow-xs' : 'bg-rose-50 text-rose-700 border border-rose-200/80 hover:bg-rose-100'">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span>Ditolak ({{ $countRejected }})</span>
                </button>
            </div>

            @if($backorderOrders->isEmpty())
                <div class="py-14 text-center space-y-3">
                    <div class="w-12 h-12 mx-auto rounded-2xl bg-amber-50 border border-amber-200/70 flex items-center justify-center text-amber-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <p class="font-bold text-slate-900 text-sm">Tidak Ada Pesanan Dalam Status Backorder</p>
                        <p class="text-xs text-slate-500">Semua pesanan Anda terpenuhi penuh atau tidak ada sisa pasokan yang tertunda.</p>
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
                             class="bg-slate-50/70 rounded-2xl border border-slate-200/80 p-5 space-y-4 transition hover:border-amber-300/80">
                            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2 border-b border-slate-200/70 pb-3">
                                <div class="flex items-center gap-3">
                                    <span class="font-bold text-xs text-[#ea580c] tabular-nums">
                                        #ORD-{{ strtoupper(substr($boOrder->id, 0, 8)) }}
                                    </span>
                                    <span class="text-xs font-bold text-slate-900">
                                        {{ $boOrder->partnership?->supplier?->company_name ?? 'Pemasok Mitra' }}
                                    </span>
                                </div>

                                <!-- STATUS INDIKATOR -->
                                <div>
                                    @if($isBatch2Received)
                                        <span class="bg-emerald-50 border border-emerald-200/80 text-emerald-700 text-[11px] font-semibold px-3 py-1 rounded-lg inline-flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            <span>Sisa Backorder Telah Diterima Penuh</span>
                                        </span>
                                    @elseif($approvalStatus === 'pending' || $boOrder->order_status === 'waiting_backorder_approval')
                                        <span class="bg-amber-50 text-amber-900 border border-amber-300/80 text-[11px] font-semibold px-3 py-1 rounded-lg inline-flex items-center gap-1.5 animate-pulse">
                                            <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                            <span>Menunggu Persetujuan Anda</span>
                                        </span>
                                    @elseif($approvalStatus === 'rejected')
                                        <span class="bg-rose-50 border border-rose-200/80 text-rose-700 text-[11px] font-semibold px-3 py-1 rounded-lg inline-flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            <span>Pengajuan Ditolak ({{ $batch1?->backorder_action === 'no_backorder' ? 'Pesanan Dibatalkan' : 'Selesai Sebagian' }})</span>
                                        </span>
                                    @elseif($isBatch2Shipped)
                                        <span class="bg-sky-50 border border-sky-200/80 text-sky-800 text-[11px] font-semibold px-3 py-1 rounded-lg inline-flex items-center gap-1.5 animate-pulse">
                                            <svg class="w-3.5 h-3.5 text-sky-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                                            <span>Pengiriman Sisa Backorder Sedang Menuju Dapur</span>
                                        </span>
                                    @elseif($batch1 && $batch1->shipped_at === null)
                                        <span class="bg-purple-50 text-purple-800 border border-purple-200/80 text-[11px] font-semibold px-3 py-1 rounded-lg inline-flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-purple-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span>Disetujui — Menunggu Pemasok Memberangkatkan Barang</span>
                                        </span>
                                    @elseif($batch1 && $batch1->backorder_action === 'no_backorder')
                                        <span class="bg-emerald-50 border border-emerald-200/80 text-emerald-700 text-[11px] font-semibold px-3 py-1 rounded-lg inline-flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            <span>Barang Terkirim — Sisa Ditiadakan (No Backorder)</span>
                                        </span>
                                    @else
                                        <span class="bg-amber-50 border border-amber-200/80 text-amber-900 text-[11px] font-semibold px-3 py-1 rounded-lg inline-flex items-center gap-1.5 tabular-nums">
                                            <svg class="w-3.5 h-3.5 text-amber-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span>Kloter 1 Terkirim — Menunggu Jadwal Kirim Sisa Backorder ({{ number_format($remainingQty, 0) }} {{ $unit }})</span>
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- KOTAK PERSETUJUAN & KEPUTUSAN BACKORDER / NO BACKORDER (ALUR 2) -->
                            @if($approvalStatus === 'pending' || $boOrder->order_status === 'waiting_backorder_approval')
                                <div class="bg-amber-50/75 border border-amber-300/80 rounded-2xl p-5 space-y-3.5 shadow-2xs">
                                    <div class="flex items-start gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0">
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        </div>
                                        <div class="space-y-1.5 flex-1">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-900 bg-amber-200/60 px-2.5 py-0.5 rounded-lg">
                                                    {{ $batch1?->backorder_action === 'no_backorder' ? 'Pengajuan Pemenuhan Sebagian (Tanpa Backorder)' : 'Pengajuan Backorder dari Pemasok' }}
                                                </span>
                                                @if($batch1?->backorder_action !== 'no_backorder')
                                                    <span class="text-xs font-semibold text-amber-900 tabular-nums">
                                                        Janji Kirim: <strong>{{ $estimatedDateText }}</strong>
                                                    </span>
                                                @else
                                                    <span class="text-xs font-semibold text-rose-700">
                                                        Sisa Pesanan Ditiadakan (Stok Habis)
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="text-xs text-slate-800 leading-relaxed pt-0.5 tabular-nums">
                                                @if($batch1?->backorder_action === 'no_backorder')
                                                    Pemasok hanya sanggup mengirimkan <strong class="text-emerald-700 font-bold">{{ number_format($deliveredQty, 0) }} {{ $unit }}</strong> dan <strong class="text-rose-700 font-bold">tidak dapat mengirimkan sisa {{ number_format($remainingQty, 0) }} {{ $unit }}</strong> karena stok gudang habis. Jika Anda setuju, sisa pesanan ditiadakan, tagihan disesuaikan hanya untuk {{ number_format($deliveredQty, 0) }} {{ $unit }}, dan pemasok akan memberangkatkan barang.
                                                @else
                                                    Pemasok hanya dapat mengirimkan <strong class="text-emerald-700 font-bold">{{ number_format($deliveredQty, 0) }} {{ $unit }}</strong> saat ini dan mengajukan pengiriman sisa <strong class="text-[#ea580c] font-bold">{{ number_format($remainingQty, 0) }} {{ $unit }}</strong> pada tanggal <strong class="text-slate-900 underline">{{ $estimatedDateText }}</strong>. Apakah Anda setuju?
                                                @endif
                                            </p>
                                            @if($batch1?->backorder_notes)
                                                <div class="bg-white/90 border border-amber-200/80 rounded-xl p-2.5 text-[11px] text-slate-700 space-y-0.5 mt-1">
                                                    <span class="font-bold text-amber-900 block text-[10px] uppercase">Alasan / Catatan dari Pemasok:</span>
                                                    <p class="italic">"{{ $batch1->backorder_notes }}"</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- 2 TOMBOL TINDAKAN: SETUJUI VS TOLAK -->
                                    <div class="pt-3 border-t border-amber-200/80 flex flex-wrap items-center justify-end gap-2.5">
                                        <button type="button"
                                                @click="rejectingBackorderId = '{{ $boOrder->id }}'; rejectionReason = ''; rejectingAction = '{{ $batch1?->backorder_action ?? 'create_backorder' }}';"
                                                class="bg-white hover:bg-rose-50 text-rose-700 border border-rose-200 hover:border-rose-300 px-4 py-2 rounded-xl text-xs font-semibold transition cursor-pointer flex items-center gap-1.5 tabular-nums">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            <span>{{ $batch1?->backorder_action === 'no_backorder' ? 'Tolak & Batalkan Pesanan' : 'Tolak & Batalkan Sisa (' . number_format($remainingQty, 0) . ' ' . $unit . ')' }}</span>
                                        </button>

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
                                                class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 rounded-xl text-xs font-semibold transition shadow-xs cursor-pointer flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            <span>{{ $batch1?->backorder_action === 'no_backorder' ? 'Setujui Pemenuhan Sebagian' : 'Terima Backorder' }}</span>
                                        </button>
                                    </div>
                                </div>
                            @elseif($approvalStatus === 'rejected')
                                <div class="bg-rose-50/90 border border-rose-200/80 rounded-xl p-3.5 text-xs text-rose-900 flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <strong class="font-bold flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg><span>Pengajuan {{ $batch1?->backorder_action === 'no_backorder' ? 'Pemenuhan Sebagian' : 'Backorder' }} Ditolak</span></strong>
                                        <span class="text-[11px] text-rose-700 block mt-0.5 tabular-nums">
                                            {{ $batch1?->backorder_action === 'no_backorder' ? 'Seluruh pesanan dibatalkan sesuai keputusan penolakan Anda.' : 'Sisa barang ditiadakan permanen. Total tagihan telah dipotong hanya untuk ' . number_format($deliveredQty, 0) . ' ' . $unit . '.' }}
                                        </span>
                                        @if($batch1?->backorder_rejection_reason)
                                            <p class="text-[10px] text-slate-600 italic mt-1">Alasan UMKM: "{{ $batch1->backorder_rejection_reason }}"</p>
                                        @endif
                                    </div>
                                    <span class="bg-rose-100 text-rose-800 text-[10px] font-semibold px-2.5 py-1 rounded-lg whitespace-nowrap">
                                        {{ $batch1?->backorder_action === 'no_backorder' ? 'Pesanan Dibatalkan' : 'Selesai Sebagian' }}
                                    </span>
                                </div>
                            @else
                                <div class="bg-emerald-50/75 border border-emerald-200/80 rounded-xl px-3.5 py-2.5 text-[11px] text-emerald-900 flex flex-wrap items-center justify-between gap-1 tabular-nums">
                                    <span class="font-semibold flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        <span>Pengajuan {{ $batch1?->backorder_action === 'no_backorder' ? 'Pemenuhan Sebagian (No Backorder)' : 'Backorder' }} Disetujui</span>
                                    </span>
                                    @if($batch1?->backorder_action !== 'no_backorder')
                                        <span class="text-emerald-800 text-[11px]">
                                            Janji tiba sisa barang: <strong>{{ $estimatedDateText }}</strong>
                                        </span>
                                    @else
                                        <span class="text-emerald-800 text-[11px]">
                                            Sisa ditiadakan. Tagihan disesuaikan untuk {{ number_format($deliveredQty, 0) }} {{ $unit }}.
                                        </span>
                                    @endif
                                </div>
                            @endif

                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                                <!-- KOMODITAS & RINCIAN VOLUME -->
                                <div class="md:col-span-4 space-y-1 tabular-nums">
                                    <span class="text-[10px] uppercase text-slate-400 font-bold tracking-wider">Komoditas Bahan Baku</span>
                                    <h4 class="font-bold text-sm text-slate-900">
                                        {{ $firstItem?->supplierProduct?->commodity?->name ?? 'Bahan Baku' }}
                                    </h4>
                                    <p class="text-xs text-slate-500">
                                        Total Kontrak: <strong class="text-slate-700">{{ number_format($totalQty, 0) }} {{ $unit }}</strong>
                                    </p>
                                </div>

                                <!-- PERBANDINGAN BATCH 1 & BATCH 2 -->
                                <div class="md:col-span-5 bg-white p-3.5 rounded-xl border border-slate-200/80 space-y-1.5 text-xs tabular-nums">
                                    <div class="flex justify-between items-center text-emerald-800 font-semibold">
                                        <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg><span>Kloter 1 Diterima:</span></span>
                                        <strong>{{ number_format($deliveredQty, 0) }} {{ $unit }}</strong>
                                    </div>
                                    <div class="flex justify-between items-center text-amber-900 font-bold border-t border-slate-100 pt-1.5">
                                        <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg><span>Sisa Backorder (Kloter 2):</span></span>
                                        <span class="text-amber-600 font-bold text-sm">{{ number_format($remainingQty, 0) }} {{ $unit }}</span>
                                    </div>
                                    @if($batch2?->tracking_notes)
                                        <p class="text-[10px] text-slate-500 italic pt-1 border-t border-slate-100">
                                            Resi/Armada Kloter 2: "{{ $batch2->tracking_notes }}"
                                        </p>
                                    @endif
                                </div>

                                <!-- STATUS KEPUTUSAN & PENGALIHAN KE TAB KONFIRMASI PESANAN -->
                                <div class="md:col-span-3 flex flex-col md:items-end justify-center space-y-1.5 md:text-right">
                                    @if($approvalStatus === 'pending')
                                        <span class="text-xs text-amber-700 font-bold inline-flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span>Menunggu Keputusan</span>
                                        </span>
                                        <span class="text-[11px] text-slate-500">
                                            Tentukan persetujuan pada kotak di atas.
                                        </span>
                                    @elseif($approvalStatus === 'rejected')
                                        <span class="text-xs text-rose-600 font-bold inline-flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            <span>Pengajuan Ditolak</span>
                                        </span>
                                        <span class="text-[11px] text-slate-400">
                                            Sisa pesanan dibatalkan permanen.
                                        </span>
                                    @else
                                        <span class="text-xs text-emerald-700 font-bold inline-flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            <span>Backorder Disetujui</span>
                                        </span>
                                        <p class="text-[10px] text-slate-500">
                                            Konfirmasi fisik Kloter 1 &amp; 2 dipantau di tab Konfirmasi Pesanan.
                                        </p>
                                        <button type="button"
                                                @click="orderSubTab = 'confirm'"
                                                class="bg-orange-50 hover:bg-orange-100 text-[#ea580c] border border-orange-200/80 px-3.5 py-1.5 rounded-xl font-semibold text-[11px] inline-flex items-center gap-1 transition cursor-pointer mt-1">
                                            <span>Buka Konfirmasi Pesanan →</span>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if(isset($backorderOrders) && method_exists($backorderOrders, 'hasPages') && $backorderOrders->hasPages())
                    <div class="pt-2">
                        {{ $backorderOrders->links() }}
                    </div>
                @endif
            @endif
        </div>

        <!-- MODAL KONFIRMASI TERIMA SISA BACKORDER (BATCH 2) -->
        <div x-cloak x-show="confirmingBackorderId" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="confirmingBackorderId = null" class="bg-white max-w-lg w-full rounded-2xl p-6 space-y-5 shadow-xl border border-slate-200/80">
                <div class="flex justify-between items-start border-b border-slate-100 pb-4">
                    <div>
                        <span class="text-[11px] font-bold text-amber-600 uppercase tracking-wider">Konfirmasi Sisa Backorder (Batch 2)</span>
                        <h3 class="text-lg font-bold text-slate-900 mt-1">Upload Bukti Penerimaan Batch 2</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Pastikan sisa barang pasokan telah Anda terima dengan baik di dapur.</p>
                    </div>
                    <button type="button" @click="confirmingBackorderId = null" class="text-slate-400 hover:text-slate-700 cursor-pointer p-1.5 rounded-lg hover:bg-slate-100 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>

                <template x-if="confirmingBackorderId">
                    <form :action="`/umkm/pemesanan/${confirmingBackorderId}/konfirmasi-backorder`" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-2">Foto Bukti Penerimaan Sisa Backorder <span class="text-rose-500">*</span></label>
                            <div class="border-2 border-dashed border-slate-200 rounded-xl p-4 text-center cursor-pointer hover:border-amber-500 bg-slate-50/50 transition"
                                 @click="$refs.photoBackorderInput.click()">
                                <template x-if="!confirmBackorderPhotoPreview">
                                    <div class="space-y-2 py-3">
                                        <div class="w-11 h-11 mx-auto rounded-xl bg-amber-50 border border-amber-200/70 flex items-center justify-center text-amber-600">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </div>
                                        <p class="text-xs text-slate-700 font-semibold">Pilih foto penerimaan Batch 2</p>
                                        <p class="text-[11px] text-slate-400">JPG, PNG, WEBP — Maks. 5MB</p>
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

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Catatan Penerimaan Batch 2 (Opsional)</label>
                            <textarea
                                name="confirmation_notes"
                                rows="3"
                                x-model="confirmBackorderNotes"
                                placeholder="Contoh: Sisa 50 kg diterima lengkap dan mutu baik."
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200/90 text-slate-900 text-xs font-medium placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition">
                            </textarea>
                        </div>

                        <div class="flex gap-3 pt-2">
                            <button type="button" @click="confirmingBackorderId = null"
                                class="w-1/2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200/90 py-2.5 rounded-xl font-semibold text-xs transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit"
                                class="w-1/2 bg-amber-500 hover:bg-amber-600 text-white py-2.5 rounded-xl font-semibold text-xs shadow-xs transition cursor-pointer flex items-center justify-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>Konfirmasi Selesai Penuh</span>
                            </button>
                        </div>
                    </form>
                </template>
            </div>
        </div>

        <!-- MODAL TOLAK BACKORDER / NO BACKORDER & ISI ALASAN (ALUR 2.b) -->
        <div x-cloak x-show="rejectingBackorderId" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="rejectingBackorderId = null" class="bg-white max-w-md w-full rounded-2xl p-6 space-y-4 shadow-xl border border-slate-200/80">
                <div class="flex justify-between items-start border-b border-slate-100 pb-3">
                    <div>
                        <span class="text-[10px] font-bold text-rose-600 uppercase tracking-wider">Keputusan Penolakan</span>
                        <h3 class="text-lg font-bold text-slate-900 mt-0.5" x-text="rejectingAction === 'no_backorder' ? 'Tolak & Batalkan Seluruh Pesanan' : 'Tolak & Batalkan Sisa Barang'"></h3>
                        <p class="text-xs text-slate-500 mt-0.5" x-text="rejectingAction === 'no_backorder' ? 'Seluruh pesanan akan dibatalkan karena Anda menolak pemenuhan sebagian tanpa sisa.' : 'Sisa pesanan tidak akan dikirim lagi dan tagihan Anda akan otomatis dipotong.'"></p>
                    </div>
                    <button type="button" @click="rejectingBackorderId = null" class="text-slate-400 hover:text-slate-700 cursor-pointer p-1.5 rounded-lg hover:bg-slate-100 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>

                <template x-if="rejectingBackorderId">
                    <form :action="`/umkm/pemesanan/${rejectingBackorderId}/tolak-backorder`" method="POST" class="space-y-4">
                        @csrf
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-slate-700">
                                Alasan Singkat Penolakan <span class="text-rose-500">*</span>
                            </label>
                            <textarea name="rejection_reason"
                                      x-model="rejectionReason"
                                      rows="3"
                                      required
                                      placeholder="Contoh: Kebutuhan dapur sangat mendesak, sisa bahan baku akan kami beli mandiri hari ini."
                                      class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200/90 text-slate-900 text-xs font-medium placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition"></textarea>
                            <p class="text-[11px] text-slate-400" x-text="rejectingAction === 'no_backorder' ? 'Pemasok akan menerima pemberitahuan bahwa seluruh pesanan dibatalkan.' : 'Pemasok akan menerima pemberitahuan bahwa sisa pesanan tidak perlu dikirimkan lagi.'"></p>
                        </div>

                        <div class="flex gap-3 pt-1">
                            <button type="button" @click="rejectingBackorderId = null"
                                    class="w-1/2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200/90 py-2.5 rounded-xl font-semibold text-xs transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit"
                                    class="w-1/2 bg-rose-600 hover:bg-rose-700 text-white py-2.5 px-3 rounded-xl font-semibold text-xs shadow-xs transition cursor-pointer">
                                <span x-text="rejectingAction === 'no_backorder' ? 'Tolak & Batalkan Pesanan' : 'Tolak & Batalkan Sisa'"></span>
                            </button>
                        </div>
                    </form>
                </template>
            </div>
        </div>

        <!-- MODAL PERSETUJUAN TERIMA BACKORDER / NO BACKORDER -->
        <div x-cloak x-show="approvingBackorderOrder" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="approvingBackorderOrder = null" class="bg-white max-w-md w-full rounded-2xl p-6 space-y-4 shadow-xl border border-slate-200/80">
                <div class="flex justify-between items-start border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-200/80 text-emerald-600 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider">Konfirmasi Persetujuan</span>
                            <h3 class="text-base font-bold text-slate-900" x-text="approvingBackorderOrder?.action === 'no_backorder' ? 'Setujui Pemenuhan Sebagian?' : 'Setujui Pengajuan Backorder?'"></h3>
                        </div>
                    </div>
                    <button type="button" @click="approvingBackorderOrder = null" class="text-slate-400 hover:text-slate-700 cursor-pointer p-1.5 rounded-lg hover:bg-slate-100 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>

                <template x-if="approvingBackorderOrder">
                    <form :action="approvingBackorderOrder.actionUrl" method="POST" class="space-y-4">
                        @csrf
                        <div class="bg-emerald-50/70 border border-emerald-200/80 rounded-xl p-4 space-y-2.5 text-xs tabular-nums">
                            <template x-if="approvingBackorderOrder.action === 'no_backorder'">
                                <div class="space-y-2">
                                    <p class="text-slate-600">
                                        Pemasok (<strong class="text-slate-900" x-text="approvingBackorderOrder.supplier"></strong>) mengajukan pemenuhan sebagian tanpa backorder:
                                    </p>
                                    <div class="flex items-center justify-between bg-white px-3.5 py-2.5 rounded-xl border border-emerald-200/80">
                                        <span class="text-slate-700 font-semibold" x-text="approvingBackorderOrder.commodity"></span>
                                        <span class="text-emerald-700 font-bold text-sm" x-text="'Dikirim: ' + approvingBackorderOrder.deliveredQty + ' ' + approvingBackorderOrder.unit"></span>
                                    </div>
                                    <div class="text-[11px] text-rose-700 bg-rose-50 border border-rose-200/80 p-2.5 rounded-xl space-y-0.5">
                                        <strong class="flex items-center gap-1.5 font-semibold"><svg class="w-3.5 h-3.5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg><span>Sisa Pasokan Ditiadakan:</span></strong>
                                        <p>Sisa <strong x-text="approvingBackorderOrder.remainingQty + ' ' + approvingBackorderOrder.unit"></strong> ditiadakan &amp; tagihan otomatis disesuaikan.</p>
                                    </div>
                                </div>
                            </template>

                            <template x-if="approvingBackorderOrder.action !== 'no_backorder'">
                                <div class="space-y-2">
                                    <p class="text-slate-600">
                                        Pemasok (<strong class="text-slate-900" x-text="approvingBackorderOrder.supplier"></strong>) mengajukan sisa pengiriman barang:
                                    </p>
                                    <div class="flex items-center justify-between bg-white px-3.5 py-2.5 rounded-xl border border-emerald-200/80">
                                        <span class="text-slate-700 font-semibold" x-text="approvingBackorderOrder.commodity"></span>
                                        <span class="text-emerald-700 font-bold text-sm" x-text="approvingBackorderOrder.remainingQty + ' ' + approvingBackorderOrder.unit"></span>
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
                                <p class="text-slate-600 italic bg-white/80 p-2.5 rounded-lg border border-emerald-100">
                                    Catatan pemasok: "<span x-text="approvingBackorderOrder.notes"></span>"
                                </p>
                            </template>
                        </div>

                        <p class="text-[11px] text-slate-500 leading-relaxed" x-text="approvingBackorderOrder.action === 'no_backorder' ? 'Dengan menyetujui, pemasok akan segera memberangkatkan barang yang tersedia. Invoice resmi diterbitkan saat barang diberangkatkan.' : 'Dengan menyetujui, pemasok akan mempersiapkan pengiriman Batch 2 sesuai tanggal di atas. Tagihan Invoice Batch 2 akan terbit saat barang kloter 2 tiba di dapur.'">
                        </p>

                        <div class="flex gap-3 pt-1">
                            <button type="button" @click="approvingBackorderOrder = null"
                                    class="w-1/2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200/90 py-2.5 rounded-xl font-semibold text-xs transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit"
                                    class="w-1/2 bg-emerald-600 hover:bg-emerald-700 text-white py-2.5 px-3 rounded-xl font-semibold text-xs shadow-xs transition cursor-pointer flex items-center justify-center gap-1.5">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span x-text="approvingBackorderOrder.action === 'no_backorder' ? 'Setujui Sebagian' : 'Ya, Setujui Backorder'"></span>
                            </button>
                        </div>
                    </form>
                </template>
            </div>
        </div>

        <!-- SUB-TAB 4: PEMBAYARAN MIDTRANS (INVOICE PER BATCH) -->
        <div x-show="orderSubTab === 'payment'" class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden p-6 space-y-5 shadow-xs">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2 border-b border-slate-100 pb-4">
                <div>
                    <span class="text-[10px] font-bold text-[#ea580c] uppercase tracking-wider">Tagihan Resmi Sesuai Batch Pengiriman</span>
                    <h3 class="font-bold text-lg text-slate-900 mt-0.5">Pembayaran Tagihan Invoice Via Midtrans</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Setiap kloter pengiriman (Batch 1 maupun Batch 2 Backorder) diterbitkan invoice terpisah sesuai fisik barang yang tiba.</p>
                </div>
            </div>

            @if($batchInvoices->isEmpty())
                <div class="py-14 text-center space-y-3">
                    <div class="w-12 h-12 mx-auto rounded-2xl bg-orange-50 border border-orange-200/70 flex items-center justify-center text-[#ea580c]">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <p class="font-bold text-slate-900 text-sm">Belum Ada Tagihan Invoice Pasokan</p>
                        <p class="text-xs text-slate-500">Invoice pembayaran akan otomatis diterbitkan untuk setiap kloter pengiriman saat pemasok memproses pengiriman bahan baku.</p>
                    </div>
                </div>
            @else
                <div class="overflow-x-auto rounded-xl border border-slate-200/80">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-slate-50/90 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-200/80 text-[11px]">
                            <tr>
                                <th class="px-4 py-3.5">No. Invoice &amp; Batch</th>
                                <th class="px-4 py-3.5">Pemasok Mitra</th>
                                <th class="px-4 py-3.5">Detail Komoditas &amp; Kuantitas</th>
                                <th class="px-4 py-3.5">Nominal Tagihan Batch</th>
                                <th class="px-4 py-3.5">Status Pembayaran</th>
                                <th class="px-4 py-3.5 text-center">Aksi Bayar Online</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            @foreach($batchInvoices as $inv)
                                @php
                                    $invOrder = $inv->order;
                                    $firstItem = $invOrder?->items?->first();
                                    $commodityName = $firstItem?->supplierProduct?->commodity?->name ?? 'Komoditas Pasokan';
                                    $unit = $firstItem?->supplierProduct?->commodity?->unit ?? 'kg';
                                    $isPaid = $inv->isPaid();
                                    $batchBadge = $inv->shipment_batch == 2 ? 'Kloter 2 (Backorder)' : 'Kloter 1';
                                @endphp
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="px-4 py-3.5 space-y-1 tabular-nums">
                                        <span class="font-bold text-[#ea580c] block text-xs">
                                            {{ $inv->invoice_number ?? ('INV/' . strtoupper(substr($inv->order_id, 0, 8)) . '/B' . $inv->shipment_batch) }}
                                        </span>
                                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold px-2.5 py-0.5 rounded-lg {{ $inv->shipment_batch == 2 ? 'bg-amber-50 text-amber-800 border border-amber-200/80' : 'bg-orange-50 text-[#ea580c] border border-orange-200/70' }}">
                                            {{ $batchBadge }}
                                        </span>
                                        <span class="block text-[10px] text-slate-400">
                                            Ref: #ORD-{{ strtoupper(substr($inv->order_id, 0, 8)) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 font-semibold text-slate-900">
                                        {{ $invOrder?->partnership?->supplier?->company_name ?? 'Pemasok Mitra' }}
                                    </td>
                                    <td class="px-4 py-3.5 space-y-1 tabular-nums">
                                        <strong class="text-slate-900 block text-xs">{{ $commodityName }}</strong>
                                        <div class="text-[11px] text-slate-600">
                                            Kuantitas Batch: <strong class="text-slate-800">{{ number_format((float) ($inv->quantity_billed ?: 0), 1) }} {{ $unit }}</strong>
                                        </div>
                                        @if($inv->notes)
                                            <span class="text-[10px] text-slate-400 italic block">{{ $inv->notes }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap tabular-nums">
                                        <span class="font-bold text-slate-900 text-sm block">
                                            Rp {{ number_format($inv->amount, 0, ',', '.') }}
                                        </span>
                                        <span class="text-[10px] text-slate-400 block">Sesuai kuantitas batch</span>
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap">
                                        @if($isPaid)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                                                <svg class="w-3 h-3 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span>Lunas</span>
                                            </span>
                                            @if($inv->paid_at || $inv->transaction_time)
                                                <span class="block text-[10px] text-slate-400 mt-1 tabular-nums">
                                                    {{ ($inv->paid_at ?? $inv->transaction_time)->format('d/m/Y H:i') }}
                                                </span>
                                            @endif
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-amber-50 text-amber-800 border border-amber-200/80">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                <span>Menunggu Pembayaran</span>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                        @if(! $isPaid)
                                            <div class="flex items-center justify-center gap-1.5 mx-auto">
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
                                                    id="btn-bayar-batch"
                                                    class="bg-[#ea580c] hover:bg-[#c2410c] text-white px-3.5 py-2 rounded-xl font-semibold text-[11px] shadow-xs transition cursor-pointer inline-flex items-center justify-center gap-1.5">
                                                    <svg class="w-3.5 h-3.5 text-white/90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                                    </svg>
                                                    <span>Bayar Tagihan</span>
                                                </button>

                                                <form action="{{ route('umkm.pemesanan.sync-status', $inv) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" title="Sinkronkan status transaksi dari server Midtrans" class="bg-white hover:bg-slate-50 text-slate-700 border border-slate-200/90 px-3 py-2 rounded-xl text-[11px] font-semibold transition inline-flex items-center gap-1 cursor-pointer">
                                                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                        <span>Cek Status</span>
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <div class="inline-flex flex-col items-center">
                                                <span class="text-emerald-700 font-semibold text-[11px] inline-flex items-center gap-1">
                                                    <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    <span>Lunas via {{ $inv->payment_method ?? 'Midtrans' }}</span>
                                                </span>
                                                @if($inv->midtrans_transaction_id)
                                                    <span class="text-[10px] text-slate-400 mt-0.5 tabular-nums">Ref: {{ $inv->midtrans_transaction_id }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if(isset($batchInvoices) && method_exists($batchInvoices, 'hasPages') && $batchInvoices->hasPages())
                    <div class="pt-2">
                        {{ $batchInvoices->links() }}
                    </div>
                @endif
            @endif
        </div>

        <!-- SUB-TAB 5: RATING & ULASAN -->
        <div x-show="orderSubTab === 'rating'" class="space-y-6">

            <!-- MODAL FORMULIR RATING & ULASAN -->
            <div x-cloak x-show="showReviewModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
                <div @click.away="closeReviewModal()" class="bg-white max-w-lg w-full rounded-2xl p-6 space-y-5 shadow-xl border border-slate-200/80">
                    <div class="flex justify-between items-start border-b border-slate-100 pb-3.5">
                        <div>
                            <span class="text-[11px] font-bold text-[#ea580c] uppercase tracking-wider block">Form Penilaian Mutu Pasokan</span>
                            <h4 class="font-bold text-lg text-slate-900 mt-0.5" x-text="ratingInputOrder ? 'Ulas Pesanan: ' + (ratingInputOrder.code || ratingInputOrder.id) : 'Beri Ulasan Pasokan'"></h4>
                            <p class="text-xs text-slate-500 mt-0.5" x-text="ratingInputOrder ? 'Pemasok: ' + (ratingInputOrder.supplierName || '-') + ' (' + (ratingInputOrder.commodity || '-') + ')' : ''"></p>
                        </div>
                        <button type="button" @click="closeReviewModal()" class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg hover:bg-slate-100 cursor-pointer transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="saveRating()" class="space-y-4 text-xs">
                        @csrf
                        <div>
                            <label class="block mb-2 text-slate-700 font-semibold">Pilih Skor Bintang Kepuasan (1–5 Bintang) <span class="text-rose-500">*</span></label>
                            <div class="flex items-center gap-2">
                                <template x-for="star in [1, 2, 3, 4, 5]" :key="'star-' + star">
                                    <button 
                                        type="button" 
                                        @click="stars = star" 
                                        class="p-2.5 rounded-xl border transition cursor-pointer hover:scale-105"
                                        :class="star <= stars ? 'border-amber-300 bg-amber-50 text-amber-500' : 'border-slate-200 bg-slate-50 text-slate-300 hover:text-slate-400'">
                                        <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    </button>
                                </template>
                                <span class="ml-2 text-sm font-bold text-amber-600 tabular-nums" x-text="stars + '.0 / 5.0'"></span>
                            </div>
                        </div>

                        <div>
                            <label class="block mb-1.5 text-slate-700 font-semibold">Ulasan Kualitas Bahan Baku &amp; Ketepatan Pengiriman <span class="text-rose-500">*</span></label>
                            <textarea 
                                rows="3" 
                                x-model="reviewText" 
                                required 
                                maxlength="1000"
                                placeholder="Contoh: Kualitas daging sangat segar, higienis, dan pengiriman tepat waktu sesuai standar dapur kami..." 
                                class="w-full p-3.5 rounded-xl bg-slate-50 border border-slate-200/90 text-slate-900 font-medium placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/15 focus:border-[#ea580c] transition"></textarea>
                            <div class="flex justify-between text-[11px] text-slate-400 mt-1 tabular-nums">
                                <span>Wajib diisi</span>
                                <span x-text="(reviewText.length || 0) + ' / 1000 karakter'"></span>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100">
                            <button type="button" @click="closeReviewModal()" class="px-5 py-2.5 rounded-xl bg-white hover:bg-slate-50 border border-slate-200/90 text-slate-700 font-semibold transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit" 
                                    :disabled="isSubmittingReview"
                                    class="bg-[#ea580c] hover:bg-[#c2410c] text-white px-6 py-2.5 rounded-xl font-semibold shadow-xs transition cursor-pointer disabled:opacity-50 inline-flex items-center gap-1.5">
                                <span x-show="!isSubmittingReview">Simpan Ulasan</span>
                                <svg x-show="!isSubmittingReview" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                <span x-show="isSubmittingReview">Menyimpan...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- RIWAYAT ULASAN & DAFTAR PESANAN SELESAI DARI DATABASE -->
            <div class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden p-6 sm:p-7 space-y-5 shadow-xs">
                <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-3 border-b border-slate-100 pb-4">
                    <div>
                        <span class="text-[10px] font-bold text-[#ea580c] uppercase tracking-wider block">
                            Evaluasi Mutu Bahan Baku Pasokan
                        </span>
                        <h3 class="font-bold text-lg text-slate-900 mt-0.5">Rating &amp; Ulasan Pemasok</h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Daftar pesanan kemitraan yang pembayarannya telah lunas. Berikan evaluasi mutu komoditas untuk menjaga standar kualitas dapur UMKM Anda.
                        </p>
                    </div>
                    <span class="bg-orange-50 border border-orange-200/80 text-[#ea580c] font-semibold text-xs px-3.5 py-1.5 rounded-xl w-fit tabular-nums">
                        {{ $reviewOrders->count() }} Pesanan Lunas
                    </span>
                </div>

                <div class="space-y-4">
                    @forelse($reviewOrders as $ord)
                        @php
                            $supplier = $ord->partnership?->supplier;
                            $firstItem = $ord->items?->first();
                            $commodity = $firstItem?->supplierProduct?->commodity;
                            $commodityName = $commodity?->name ?? 'Komoditas Pasokan';
                            $quantity = $firstItem?->quantity ?? $ord->total_ordered_quantity ?? 0;
                            $unit = $commodity?->unit ?? 'kg';
                            $orderCode = 'ORD-' . strtoupper(substr($ord->id, 0, 8));
                            $review = $ord->review;
                            $hasReview = !is_null($review);
                            $ratingVal = $hasReview ? (int) $review->rating : 0;
                            $reviewTextVal = $hasReview ? $review->review : '';
                        @endphp
                        <div class="bg-slate-50/70 hover:bg-slate-50 p-5 rounded-2xl space-y-3 border border-slate-200/80 transition">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-bold text-slate-900 text-sm">
                                            {{ $supplier?->company_name ?? 'Pemasok Mitra' }}
                                        </h4>
                                        <span class="text-[10px] px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200/80 font-semibold">
                                            Lunas
                                        </span>
                                    </div>
                                    <div class="text-xs text-slate-600 flex flex-wrap items-center gap-1.5 tabular-nums">
                                        <span class="font-bold text-[#ea580c]">{{ $orderCode }}</span>
                                        <span class="text-slate-300">•</span>
                                        <span class="font-semibold text-slate-800">{{ $commodityName }}</span>
                                        <span class="text-slate-500">({{ number_format((float) $quantity, 1) }} {{ $unit }})</span>
                                        @if($ord->order_date)
                                            <span class="text-slate-300">•</span>
                                            <span class="text-slate-500 text-[11px]">{{ \Carbon\Carbon::parse($ord->order_date)->format('d M Y') }}</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center gap-2.5 flex-wrap">
                                    <div class="flex items-center gap-1.5 bg-white px-3 py-1.5 rounded-xl border border-slate-200/80 tabular-nums">
                                        <div class="flex items-center gap-0.5">
                                            @for($s = 1; $s <= 5; $s++)
                                                <svg class="w-3.5 h-3.5 {{ $s <= $ratingVal ? 'text-amber-400 fill-amber-400' : 'text-slate-200 fill-slate-200' }}" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                            @endfor
                                        </div>
                                        @if($hasReview)
                                            <span class="text-xs font-bold text-amber-700 ml-1">
                                                {{ $ratingVal }}.0 / 5
                                            </span>
                                        @else
                                            <span class="text-[10px] font-medium text-slate-400 ml-1">
                                                (Belum Diulas)
                                            </span>
                                        @endif
                                    </div>

                                    <button 
                                        type="button" 
                                        @click="openRatingForOrder({
                                            id: '{{ $ord->id }}',
                                            code: '{{ $orderCode }}',
                                            supplierName: '{{ addslashes($supplier?->company_name ?? 'Pemasok Mitra') }}',
                                            commodity: '{{ addslashes($commodityName . ' (' . number_format((float) $quantity, 1) . ' ' . $unit . ')') }}',
                                            rating: {{ $ratingVal > 0 ? $ratingVal : 5 }},
                                            review: {{ json_encode($reviewTextVal) }}
                                        })"
                                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white hover:bg-[#ea580c] text-slate-700 hover:text-white border border-slate-200/90 hover:border-[#ea580c] text-xs font-semibold transition cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        <span>{{ $hasReview ? 'Edit Ulasan' : 'Beri Ulasan' }}</span>
                                    </button>
                                </div>
                            </div>

                            <!-- ISI TEKS ULASAN -->
                            @if($hasReview && filled($reviewTextVal))
                                <div class="bg-white p-3.5 rounded-xl border border-slate-200/70 flex items-start gap-2.5">
                                    <svg class="w-4 h-4 text-[#ea580c] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                    </svg>
                                    <div class="flex-1">
                                        <p class="text-xs text-slate-700 font-medium leading-relaxed">
                                            "{{ $reviewTextVal }}"
                                        </p>
                                        @if($review->updated_at)
                                            <span class="text-[10px] text-slate-400 block mt-1.5">
                                                Terakhir diperbarui: {{ $review->updated_at->locale('id')->diffForHumans() }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="bg-white/70 p-3.5 rounded-xl border border-dashed border-slate-200 flex items-center justify-between gap-2">
                                    <span class="text-xs text-slate-400 italic">
                                        Belum ada ulasan untuk kualitas bahan baku pesanan ini.
                                    </span>
                                    <span class="text-[11px] font-semibold text-[#ea580c]">
                                        Klik tombol "Beri Ulasan"
                                    </span>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-12 bg-slate-50/50 rounded-2xl border border-dashed border-slate-200">
                            <div class="w-12 h-12 mx-auto rounded-2xl bg-orange-50 border border-orange-200/70 flex items-center justify-center text-[#ea580c] mb-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                            </div>
                            <h4 class="text-sm font-bold text-slate-900">Belum Ada Pesanan yang Selesai Dibayar</h4>
                            <p class="text-xs text-slate-500 max-w-md mx-auto mt-1">
                                Pesanan yang telah diselesaikan pembayarannya akan muncul di sini untuk Anda berikan ulasan dan rating mutu pasokan.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

    </div> <!-- /TAB: PESANAN & PENGIRIMAN -->

    <!-- ========================================================= -->
    <!-- TAB 4: DASHBOARD ANALISIS UMKM                            -->
    <!-- ========================================================= -->
    <div x-show="activeTab === 'dashboard'" class="space-y-6">

        <!-- PAGE HEADER -->
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 border-b border-slate-200/80 pb-5">
            <div>
                <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-[#ea580c] tracking-[0.14em] uppercase">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#ea580c]"></span>
                    <span>Ringkasan Eksekutif</span>
                </span>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight mt-1">Dashboard Analisis Pengadaan</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl">Pantau tren pengeluaran bulanan, sebaran kontrak kemitraan aktif, dan histori transaksi bahan baku dapur UMKM Anda.</p>
            </div>
        </div>

        <!-- METRICS CARDS UMKM -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 tabular-nums">
            <div class="bg-white p-5 sm:p-6 rounded-2xl shadow-xs border border-slate-200/80 space-y-1.5">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Total Pengeluaran Bulan Ini</span>
                <p class="text-2xl font-bold text-[#ea580c]">Rp 12.220.000</p>
                <p class="text-[11px] text-emerald-600 font-semibold inline-flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span>Hemat 12% via Harga Grosir</span>
                </p>
            </div>

            <div class="bg-white p-5 sm:p-6 rounded-2xl shadow-xs border border-slate-200/80 space-y-1.5">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Pesanan Aktif</span>
                <p class="text-2xl font-bold text-slate-900">2 Transaksi</p>
                <p class="text-[11px] text-amber-600 font-semibold inline-flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    <span>1 Perlu Konfirmasi Penerimaan</span>
                </p>
            </div>

            <div class="bg-white p-5 sm:p-6 rounded-2xl shadow-xs border border-slate-200/80 space-y-1.5">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Total Diterima</span>
                <p class="text-2xl font-bold text-slate-900">380 kg</p>
                <p class="text-[11px] text-slate-500 font-medium">Daging Ayam &amp; Bumbu Dapur</p>
            </div>

            <div class="bg-white p-5 sm:p-6 rounded-2xl shadow-xs border border-slate-200/80 space-y-1.5">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">On-Time Delivery</span>
                <p class="text-2xl font-bold text-emerald-600">100%</p>
                <p class="text-[11px] text-emerald-600 font-semibold inline-flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span>Tiba Sebelum Jam 07.00 WIB</span>
                </p>
            </div>
        </div>

        <!-- VISUAL DIAGRAM TREN PENGADAAN (LINE CHART) & DISTRIBUSI KONTRAK (DONUT CHART) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-stretch" x-data="{ chartMetric: 'volume' }">

            <!-- KOLOM KIRI (lg:col-span-8): WAVE LINE CHART TREN PENGADAAN -->
            <div class="lg:col-span-8 bg-white p-6 rounded-2xl shadow-xs border border-slate-200/80 flex flex-col justify-between space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-base sm:text-lg text-slate-900 tracking-tight">Tren Pengadaan &amp; Realisasi Pasokan</h3>
                            <span class="text-[10px] font-semibold bg-orange-50 text-[#ea580c] border border-orange-200/70 px-2 py-0.5 rounded-md">B2B Analytics</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">Pemantauan realisasi volume pasokan komoditas bulanan dari kontrak kemitraan aktif</p>
                    </div>

                    <!-- Metric Filter Pill -->
                    <div class="inline-flex items-center p-1 bg-slate-100 rounded-xl border border-slate-200/60 text-xs font-semibold self-start sm:self-auto">
                        <button type="button"
                                @click="chartMetric = 'volume'"
                                :class="chartMetric === 'volume' ? 'bg-[#ea580c] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                                class="px-3 py-1.5 rounded-lg transition cursor-pointer">
                            Volume (Kg)
                        </button>
                        <button type="button"
                                @click="chartMetric = 'biaya'"
                                :class="chartMetric === 'biaya' ? 'bg-[#ea580c] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                                class="px-3 py-1.5 rounded-lg transition cursor-pointer">
                            Biaya (Rp)
                        </button>
                    </div>
                </div>

                <!-- WAVE SVG LINE CHART -->
                <div class="relative w-full overflow-hidden pt-2">
                    <svg viewBox="0 0 700 240" class="w-full h-auto overflow-visible">
                        <defs>
                            <linearGradient id="umkmWaveGrad" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#ea580c" stop-opacity="0.24" />
                                <stop offset="100%" stop-color="#ea580c" stop-opacity="0.0" />
                            </linearGradient>
                            <linearGradient id="umkmLineGrad" x1="0" y1="0" x2="1" y2="0">
                                <stop offset="0%" stop-color="#f97316" />
                                <stop offset="100%" stop-color="#ea580c" />
                            </linearGradient>
                        </defs>

                        <!-- Horizontal Grid Lines & Y-Axis Labels -->
                        <line x1="45" y1="20" x2="685" y2="20" stroke="#f1f5f9" stroke-dasharray="4" stroke-width="1.5" />
                        <text x="35" y="24" class="text-[10px] fill-slate-400 font-semibold" text-anchor="end" x-text="chartMetric === 'volume' ? '500kg' : '15jt'"></text>

                        <line x1="45" y1="65" x2="685" y2="65" stroke="#f1f5f9" stroke-dasharray="4" stroke-width="1.5" />
                        <text x="35" y="69" class="text-[10px] fill-slate-400 font-semibold" text-anchor="end" x-text="chartMetric === 'volume' ? '400kg' : '12jt'"></text>

                        <line x1="45" y1="110" x2="685" y2="110" stroke="#f1f5f9" stroke-dasharray="4" stroke-width="1.5" />
                        <text x="35" y="114" class="text-[10px] fill-slate-400 font-semibold" text-anchor="end" x-text="chartMetric === 'volume' ? '300kg' : '9jt'"></text>

                        <line x1="45" y1="155" x2="685" y2="155" stroke="#f1f5f9" stroke-dasharray="4" stroke-width="1.5" />
                        <text x="35" y="159" class="text-[10px] fill-slate-400 font-semibold" text-anchor="end" x-text="chartMetric === 'volume' ? '200kg' : '6jt'"></text>

                        <line x1="45" y1="200" x2="685" y2="200" stroke="#e2e8f0" stroke-width="1.5" />
                        <text x="35" y="204" class="text-[10px] fill-slate-400 font-semibold" text-anchor="end">0</text>

                        <!-- Area Fill Under Path -->
                        <path
                            d="M45,160 C110,150 160,135 220,115 C280,95 330,120 390,85 C450,50 510,75 570,45 C620,25 655,35 685,30 L685,200 L45,200 Z"
                            fill="url(#umkmWaveGrad)"
                        />

                        <!-- Smooth Wave Line Path -->
                        <path
                            d="M45,160 C110,150 160,135 220,115 C280,95 330,120 390,85 C450,50 510,75 570,45 C620,25 655,35 685,30"
                            fill="none"
                            stroke="url(#umkmLineGrad)"
                            stroke-width="3"
                            stroke-linecap="round"
                        />

                        <!-- Data Points (Circles) -->
                        <g class="cursor-pointer group">
                            <circle cx="45" cy="160" r="4" fill="#ffffff" stroke="#ea580c" stroke-width="2.5" />
                        </g>
                        <g class="cursor-pointer group">
                            <circle cx="150" cy="138" r="4" fill="#ffffff" stroke="#ea580c" stroke-width="2.5" />
                        </g>
                        <g class="cursor-pointer group">
                            <circle cx="260" cy="102" r="4" fill="#ffffff" stroke="#ea580c" stroke-width="2.5" />
                        </g>
                        <g class="cursor-pointer group">
                            <circle cx="370" cy="92" r="4" fill="#ffffff" stroke="#ea580c" stroke-width="2.5" />
                        </g>
                        <g class="cursor-pointer group">
                            <circle cx="480" cy="62" r="4" fill="#ffffff" stroke="#ea580c" stroke-width="2.5" />
                        </g>
                        <g class="cursor-pointer group">
                            <circle cx="585" cy="42" r="5" fill="#ea580c" stroke="#ffffff" stroke-width="2.5" class="animate-pulse" />
                        </g>
                        <g class="cursor-pointer group">
                            <circle cx="685" cy="30" r="4" fill="#ffffff" stroke="#ea580c" stroke-width="2.5" />
                        </g>

                        <!-- X-Axis Labels -->
                        <text x="45" y="222" class="text-[10px] fill-slate-400 font-bold" text-anchor="middle">MEI</text>
                        <text x="150" y="222" class="text-[10px] fill-slate-400 font-bold" text-anchor="middle">JUN</text>
                        <text x="260" y="222" class="text-[10px] fill-slate-400 font-bold" text-anchor="middle">JUL</text>
                        <text x="370" y="222" class="text-[10px] fill-slate-400 font-bold" text-anchor="middle">AGU</text>
                        <text x="480" y="222" class="text-[10px] fill-slate-400 font-bold" text-anchor="middle">SEP</text>
                        <text x="585" y="222" class="text-[10px] fill-[#ea580c] font-bold" text-anchor="middle">OKT (AKTIF)</text>
                        <text x="685" y="222" class="text-[10px] fill-slate-400 font-bold" text-anchor="middle">EST. NOV</text>
                    </svg>
                </div>

                <!-- Footer Summary Line Chart -->
                <div class="flex items-center justify-between text-xs text-slate-500 pt-3 border-t border-slate-100 flex-wrap gap-2 tabular-nums">
                    <span class="inline-flex items-center gap-1.5 font-medium">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Realisasi Pasokan: <strong class="text-slate-900 font-semibold">380 Kg</strong> bulan ini (100% tepat waktu sebelum 07.00 WIB)
                    </span>
                    <span class="text-[11px] font-medium text-slate-400">Target Bulan Depan: 420 Kg</span>
                </div>
            </div>

            <!-- KOLOM KANAN (lg:col-span-4): DONUT / PIE CHART DISTRIBUSI KONTRAK AKTIF -->
            <div class="lg:col-span-4 bg-white p-6 rounded-2xl shadow-xs border border-slate-200/80 flex flex-col items-center justify-between space-y-4">
                <div class="w-full text-left border-b border-slate-100 pb-3">
                    <h3 class="text-base font-bold text-slate-900 tracking-tight">Distribusi Kontrak</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Berdasarkan status kemitraan</p>
                </div>

                @php
                    $safeActive = max(1, $activeContractsCount ?? 2);
                    $safePending = max(0, $pendingContractsCount ?? 0);
                    $totalC = max(1, $safeActive + $safePending);
                    $circumference = 240;

                    if ($safePending > 0) {
                        $activeRatio = $safeActive / $totalC;
                        $pendingRatio = $safePending / $totalC;
                        $activeDash = max(30, round($activeRatio * ($circumference - 30)));
                        $pendingDash = max(20, round($pendingRatio * ($circumference - 30)));
                        $pendingOffset = -($activeDash + 15);
                    } else {
                        $activeDash = 230;
                        $pendingDash = 0;
                        $pendingOffset = 0;
                    }
                @endphp

                <!-- SVG DONUT / PIE CHART -->
                <div class="relative w-44 h-44 sm:w-48 sm:h-48 flex items-center justify-center my-2">
                    <svg viewBox="0 0 100 100" class="w-full h-full transform -rotate-90">
                        <circle cx="50" cy="50" r="38" fill="none" stroke="#f1f5f9" stroke-width="11" />
                        <circle
                            cx="50"
                            cy="50"
                            r="38"
                            fill="none"
                            stroke="#ea580c"
                            stroke-width="11"
                            stroke-dasharray="{{ $activeDash }} {{ $circumference }}"
                            stroke-linecap="round"
                            class="transition-all duration-700 ease-out"
                        />

                        @if($safePending > 0)
                            <circle
                                cx="50"
                                cy="50"
                                r="38"
                                fill="none"
                                stroke="#0284c7"
                                stroke-width="11"
                                stroke-dasharray="{{ $pendingDash }} {{ $circumference }}"
                                stroke-dashoffset="{{ $pendingOffset }}"
                                stroke-linecap="round"
                                class="transition-all duration-700 ease-out"
                            />
                        @endif
                    </svg>

                    <div class="absolute inset-0 flex flex-col items-center justify-center text-center select-none pointer-events-none tabular-nums">
                        <span class="text-[10px] font-semibold text-slate-400 tracking-wider uppercase block leading-none">Total Kontrak</span>
                        <span class="text-3xl font-bold text-slate-900 mt-1.5 leading-none tracking-tight">{{ $activeContractsCount ?? 2 }}</span>
                        <span class="text-[10px] text-[#ea580c] font-bold uppercase tracking-wide mt-1">Aktif Berjalan</span>
                    </div>
                </div>

                <!-- Dot Indicators -->
                <div class="w-full pt-3 border-t border-slate-100 space-y-2.5 tabular-nums">
                    <div class="flex items-center justify-center gap-5">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#ea580c]"></span>
                            <span class="text-xs font-semibold text-slate-700">Aktif ({{ $activeContractsCount ?? 2 }})</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#0284c7]"></span>
                            <span class="text-xs font-medium text-slate-500">Menunggu ({{ $pendingContractsCount ?? 0 }})</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-center gap-1.5 flex-wrap pt-1">
                        @foreach(array_slice($activeContractCommodities ?? ['Daging Ayam' => 1, 'Bumbu Dapur' => 1], 0, 3, true) as $cName => $cQty)
                            <span class="text-[10px] font-semibold bg-slate-50 border border-slate-200/80 text-slate-600 px-2.5 py-0.5 rounded-lg truncate max-w-[140px]">
                                {{ $cName }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>

        <!-- HISTORI TRANSAKSI & RATING -->
        <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden space-y-4 p-6">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3.5">
                <h3 class="font-bold text-lg text-slate-900">Riwayat &amp; Status Transaksi Dapur</h3>
                <span class="text-xs font-semibold text-[#ea580c] bg-orange-50 border border-orange-200/70 px-3 py-1 rounded-lg">Pembayaran &amp; Rating</span>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200/80">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-50/90 text-slate-500 uppercase tracking-wider text-[11px] font-semibold border-b border-slate-200/80">
                        <tr>
                            <th class="px-4 py-3.5">ID Transaksi</th>
                            <th class="px-4 py-3.5">Pemasok</th>
                            <th class="px-4 py-3.5">Komoditas</th>
                            <th class="px-4 py-3.5">Total Tagihan</th>
                            <th class="px-4 py-3.5">Status Bayar</th>
                            <th class="px-4 py-3.5">Ulasan Rating</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium tabular-nums">
                        <template x-for="ord in orders" :key="'dash-' + ord.id">
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="px-4 py-3.5 text-[#ea580c] font-bold whitespace-nowrap" x-text="ord.id"></td>
                                <td class="px-4 py-3.5 font-semibold text-slate-900" x-text="ord.supplierName"></td>
                                <td class="px-4 py-3.5 text-slate-700" x-text="ord.commodity + ' (' + ord.approvedQty + ' ' + ord.unit + ')'"></td>
                                <td class="px-4 py-3.5 font-bold text-slate-900 whitespace-nowrap" x-text="'Rp ' + ord.total.toLocaleString('id-ID')"></td>
                                <td class="px-4 py-3.5">
                                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-semibold border"
                                          :class="ord.paymentStatus === 'PAID' ? 'bg-emerald-50 text-emerald-700 border-emerald-200/80' : 'bg-rose-50 text-rose-700 border-rose-200/80'"
                                          x-text="ord.paymentStatus">
                                    </span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <template x-if="ord.rating">
                                        <span class="inline-flex items-center gap-1 font-semibold text-amber-600">
                                            <svg class="w-3.5 h-3.5 text-amber-400 fill-amber-400" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                            <span x-text="ord.rating + '.0 / 5'"></span>
                                        </span>
                                    </template>
                                    <template x-if="!ord.rating">
                                        <span class="text-[11px] text-slate-400">Belum Ada Rating</span>
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
    <!-- TAB 5: SUPPLIER PERFORMANCE TRACKER & CONTRACT MONITORING -->
    <!-- ========================================================= -->
    <div x-show="activeTab === 'monitoring'" class="space-y-6">

        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-slate-200/80 pb-5">
            <div>
                <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-[#ea580c] tracking-[0.14em] uppercase">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#ea580c]"></span>
                    <span>Logistik &amp; Kepatuhan Kontrak</span>
                </span>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight mt-1">Monitoring Performa &amp; SLA Pemasok</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl">Pemantauan realisasi jadwal kirim (OTD), pemenuhan volume pasokan, dan kepatuhan kontrak setiap pemasok mitra.</p>
            </div>
            <div class="flex items-center gap-2 shrink-0 self-start md:self-end">
                <span class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>SLA Tracking Aktif</span>
                </span>
            </div>
        </div>

        @php
            $perfItems = $contractedSuppliersPerformance ?? collect();
            $scoredItems = $perfItems->filter(fn($item) => !empty($item['score']));
            $avgScore = $scoredItems->isNotEmpty() ? $scoredItems->avg(fn($item) => (float) $item['score']->final_score) : 92.5;
            $avgOtd = $scoredItems->isNotEmpty() ? $scoredItems->avg(fn($item) => (float) ($item['score']->metrics_breakdown['delivery']['otd_rate'] ?? 98.0)) : 98.5;
            $avgFulfillment = $scoredItems->isNotEmpty() ? $scoredItems->avg(fn($item) => (float) ($item['score']->metrics_breakdown['fulfillment']['fill_rate'] ?? 99.0)) : 99.2;
            $avgStar = $scoredItems->isNotEmpty() ? $scoredItems->avg(fn($item) => (float) $item['score']->star_rating) : 4.8;
        @endphp

        <!-- 4 KARTU RINGKASAN METRIK UTAMA SLA -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 tabular-nums">
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-2">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Skor Rata-Rata</span>
                    <span class="text-amber-600 font-semibold text-xs inline-flex items-center gap-1 bg-amber-50 border border-amber-200/70 px-2 py-0.5 rounded-lg">
                        <svg class="w-3 h-3 text-amber-500 fill-amber-500" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <span x-text="monitoringAvgStar">{{ number_format($avgStar, 1) }}</span>
                    </span>
                </div>
                <div class="text-2xl sm:text-3xl font-bold text-[#ea580c] tracking-tight">
                    <span x-text="monitoringAvgScore">{{ number_format($avgScore, 1) }}</span><span class="text-xs font-medium text-slate-400">/100</span>
                </div>
                <p class="text-[11px] text-slate-400">Indeks komposit mitra pasokan</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-2">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">On-Time Delivery</span>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-lg bg-emerald-50 border border-emerald-200/70 text-emerald-700">Bobot 40%</span>
                </div>
                <div class="text-2xl sm:text-3xl font-bold text-emerald-600 tracking-tight">
                    <span x-text="monitoringAvgOtd">{{ number_format($avgOtd, 1) }}</span><span class="text-sm font-semibold text-emerald-600/75">%</span>
                </div>
                <p class="text-[11px] text-slate-400">Tiba sebelum batas jam dapur</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-2">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Pemenuhan Kuota</span>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-lg bg-sky-50 border border-sky-200/70 text-sky-700">Bobot 35%</span>
                </div>
                <div class="text-2xl sm:text-3xl font-bold text-sky-600 tracking-tight">
                    <span x-text="monitoringAvgFill">{{ number_format($avgFulfillment, 1) }}</span><span class="text-sm font-semibold text-sky-600/75">%</span>
                </div>
                <p class="text-[11px] text-slate-400">Pengiriman utuh tanpa retur</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-2">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Mitra Terkontrak</span>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-lg bg-purple-50 border border-purple-200/70 text-purple-700">Aktif</span>
                </div>
                <div class="text-2xl sm:text-3xl font-bold text-purple-700 tracking-tight">
                    <span>{{ $perfItems->count() }}</span> <span class="text-xs font-medium text-slate-400">Pemasok</span>
                </div>
                <p class="text-[11px] text-slate-400">Mitra terikat kontrak formal</p>
            </div>
        </div>

        <!-- DAFTAR EVALUASI PER KEMITRAAN / PEMASOK -->
        <div class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <h3 class="font-bold text-base text-slate-900 flex items-center gap-2 tabular-nums">
                    <span>Daftar Kontrak &amp; Realisasi Layanan Vendor</span>
                    <span class="text-xs font-medium text-slate-400" x-text="'(' + filteredSupplierCount + ' Mitra Ditampilkan)'">({{ $perfItems->count() }} Mitra Aktif)</span>
                </h3>

                <!-- SEARCH BAR INPUT -->
                <div class="relative w-full sm:w-80">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </span>
                    <input 
                        type="text" 
                        x-model="monitoringSearchQuery" 
                        placeholder="Cari pemasok, komoditas, atau alamat..." 
                        class="w-full pl-10 pr-9 py-2.5 rounded-xl bg-white border border-slate-200/90 text-xs font-medium text-slate-800 placeholder:text-slate-400 shadow-2xs focus:outline-none focus:ring-2 focus:ring-orange-500/15 focus:border-[#ea580c] transition"
                    />
                    <button 
                        type="button" 
                        x-show="monitoringSearchQuery" 
                        @click="monitoringSearchQuery = ''" 
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            @if($perfItems->isEmpty())
                <div class="bg-white rounded-2xl p-10 text-center border border-slate-200/80 shadow-xs space-y-3">
                    <div class="w-12 h-12 bg-orange-50 text-[#ea580c] rounded-2xl flex items-center justify-center mx-auto border border-orange-200/70">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h4 class="font-bold text-sm text-slate-900">Belum Ada Kemitraan Aktif</h4>
                    <p class="text-xs text-slate-500 max-w-md mx-auto">Ajukan kemitraan pasokan komoditas di tab Katalog Bahan Baku untuk mengaktifkan pemantauan realisasi SLA pemasok secara otomatis.</p>
                    <button type="button" @click="activeTab = 'katalog'" class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#ea580c] hover:bg-[#c2410c] text-white rounded-xl text-xs font-semibold transition shadow-xs cursor-pointer">
                        <span>Buka Katalog Bahan Baku</span>
                    </button>
                </div>
            @else
                <!-- State Jika Tidak Ada Hasil Pencarian -->
                <div x-show="filteredSupplierCount === 0 && monitoringSearchQuery" class="bg-white rounded-2xl p-8 text-center border border-slate-200/80 shadow-xs space-y-3">
                    <div class="w-11 h-11 bg-slate-100 text-slate-500 rounded-2xl flex items-center justify-center mx-auto border border-slate-200/80">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <h4 class="font-bold text-sm text-slate-900">Tidak ada pemasok yang cocok</h4>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto">Tidak ditemukan mitra pemasok dengan kata kunci "<span class="font-semibold text-slate-800" x-text="monitoringSearchQuery"></span>".</p>
                    <button type="button" @click="monitoringSearchQuery = ''" class="px-4 py-2 rounded-xl text-xs font-semibold text-[#ea580c] bg-orange-50 hover:bg-orange-100 border border-orange-200/80 transition cursor-pointer">
                        Reset Pencarian
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-5">
                    @foreach($perfItems as $item)
                        @php
                            $sup = $item['supplier'];
                            $score = $item['score'];
                            $p = $item['partnership'];
                            $metrics = $score?->metrics_breakdown ?? [];
                            $gradeBadge = $score ? $score->getGradeBadgeColor() : 'bg-slate-100 text-slate-700 border-slate-200';
                            $gradeLabel = $score ? $score->getGradeLabel() : 'Vendor Baru';
                            $grade = $score?->grade ?? 'UNRATED';
                            $otdRate = $metrics['delivery']['otd_rate'] ?? 100.0;
                            $fillRate = $metrics['fulfillment']['fill_rate'] ?? 100.0;
                            $avgDelay = $metrics['delivery']['avg_delay_hours'] ?? 0.0;
                            $backorderRate = $metrics['fulfillment']['backorder_rate'] ?? 0.0;
                            $defectFree = $metrics['quality']['defect_free_rate'] ?? 100.0;
                        @endphp
                        <div x-show="isSupplierMatched('{{ $sup?->id }}', '{{ addslashes($sup?->company_name ?? '') }}', '{{ addslashes($sup?->warehouse_address ?? '') }}', '{{ addslashes($item['commodity_name'] ?? '') }}')" class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs hover:shadow-md hover:border-slate-300/80 transition-all duration-200 space-y-5">
                            <!-- Card Header -->
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                                <div class="flex items-start gap-3.5">
                                    <div class="w-11 h-11 rounded-xl bg-orange-50 text-[#ea580c] font-bold flex items-center justify-center text-base border border-orange-200/70 shrink-0">
                                        {{ substr($sup?->company_name ?? 'P', 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 flex-wrap tabular-nums">
                                            <h4 class="font-bold text-base text-slate-900">{{ $sup?->company_name ?? 'Pemasok Mitra' }}</h4>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold border transition-colors"
                                                  :class="supplierScores['{{ $sup?->id }}']?.grade_badge_color || '{{ $gradeBadge }}'"
                                                  x-text="'Grade ' + (supplierScores['{{ $sup?->id }}']?.grade || '{{ $grade }}')">
                                                Grade {{ $grade }}
                                            </span>
                                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-600">
                                                <svg class="w-3.5 h-3.5 text-amber-500 fill-amber-500" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                <span x-text="Number(supplierScores['{{ $sup?->id }}']?.star_rating || {{ $score?->star_rating ?? 5.0 }}).toFixed(1)">
                                                    {{ number_format($score?->star_rating ?? 5.0, 1) }}
                                                </span>
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5 flex-wrap tabular-nums">
                                            <span>{{ $sup?->warehouse_address ?? 'Area Lokal' }}</span>
                                            <span class="text-slate-300">•</span>
                                            <span>Komoditas: <strong class="text-slate-800 font-semibold">{{ $item['commodity_name'] }}</strong> ({{ number_format($item['volume_per_delivery'], 0) }} {{ $item['unit'] }}/jadwal)</span>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 self-end sm:self-center">
                                    <button 
                                        type="button" 
                                        @click="recalculateSupplierTracker('{{ $sup?->id }}', '{{ $p?->id }}', false)" 
                                        :disabled="recalculatingSupplierId === '{{ $sup?->id }}'"
                                        class="px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200/90 transition flex items-center gap-1.5 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                                        <svg :class="recalculatingSupplierId === '{{ $sup?->id }}' ? 'animate-spin text-[#ea580c]' : 'text-slate-400'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        <span x-text="recalculatingSupplierId === '{{ $sup?->id }}' ? 'Menghitung...' : 'Hitung Ulang'">Hitung Ulang</span>
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="openSupplierTrackerModal('{{ $sup?->id }}', '{{ $p?->id }}')" 
                                        class="px-4 py-2 rounded-xl text-xs font-semibold text-white bg-[#ea580c] hover:bg-[#c2410c] shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                                        <span>Rapor Detail SLA</span>
                                        <svg class="w-3.5 h-3.5 text-white/85" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- 3 Pilar Penilaian Indikator Performa -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 tabular-nums">
                                <!-- Pilar 1: On-Time Delivery -->
                                <div class="bg-emerald-50/55 p-4 rounded-xl border border-emerald-200/60 space-y-2">
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="font-semibold text-slate-800">1. Ketepatan Waktu (OTD)</span>
                                        <span class="font-bold text-emerald-700" x-text="Number(supplierScores['{{ $sup?->id }}']?.otd_rate ?? {{ $otdRate }}).toFixed(1) + '%'">{{ number_format($otdRate, 1) }}%</span>
                                    </div>
                                    <div class="w-full bg-emerald-100 h-2 rounded-full overflow-hidden">
                                        <div class="bg-emerald-600 h-2 rounded-full transition-all duration-500" :style="'width: ' + Math.min(100, (supplierScores['{{ $sup?->id }}']?.otd_rate ?? {{ $otdRate }})) + '%'" style="width: {{ min(100, $otdRate) }}%"></div>
                                    </div>
                                    <div class="flex justify-between text-[11px] text-slate-500">
                                        <span x-text="'Rerata telat: ' + Number(supplierScores['{{ $sup?->id }}']?.avg_delay ?? {{ $avgDelay }}).toFixed(1) + ' jam'">Rerata telat: {{ number_format($avgDelay, 1) }} jam</span>
                                        <span class="text-emerald-700 font-semibold">Bobot 40%</span>
                                    </div>
                                </div>

                                <!-- Pilar 2: Order Fulfillment -->
                                <div class="bg-sky-50/55 p-4 rounded-xl border border-sky-200/60 space-y-2">
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="font-semibold text-slate-800">2. Pemenuhan Kuota (OFR)</span>
                                        <span class="font-bold text-sky-700" x-text="Number(supplierScores['{{ $sup?->id }}']?.fill_rate ?? {{ $fillRate }}).toFixed(1) + '%'">{{ number_format($fillRate, 1) }}%</span>
                                    </div>
                                    <div class="w-full bg-sky-100 h-2 rounded-full overflow-hidden">
                                        <div class="bg-sky-600 h-2 rounded-full transition-all duration-500" :style="'width: ' + Math.min(100, (supplierScores['{{ $sup?->id }}']?.fill_rate ?? {{ $fillRate }})) + '%'" style="width: {{ min(100, $fillRate) }}%"></div>
                                    </div>
                                    <div class="flex justify-between text-[11px] text-slate-500">
                                        <span x-text="'Rasio Backorder: ' + Number(supplierScores['{{ $sup?->id }}']?.backorder_rate ?? {{ $backorderRate }}).toFixed(1) + '%'">Rasio Backorder: {{ number_format($backorderRate, 1) }}%</span>
                                        <span class="text-sky-700 font-semibold">Bobot 35%</span>
                                    </div>
                                </div>

                                <!-- Pilar 3: Kualitas & Ulasan -->
                                <div class="bg-amber-50/55 p-4 rounded-xl border border-amber-200/60 space-y-2">
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="font-semibold text-slate-800">3. Bebas Retur &amp; Cacat</span>
                                        <span class="font-bold text-amber-700" x-text="Number(supplierScores['{{ $sup?->id }}']?.defect_free ?? {{ $defectFree }}).toFixed(1) + '%'">{{ number_format($defectFree, 1) }}%</span>
                                    </div>
                                    <div class="w-full bg-amber-100 h-2 rounded-full overflow-hidden">
                                        <div class="bg-amber-500 h-2 rounded-full transition-all duration-500" :style="'width: ' + Math.min(100, (supplierScores['{{ $sup?->id }}']?.defect_free ?? {{ $defectFree }})) + '%'" style="width: {{ min(100, $defectFree) }}%"></div>
                                    </div>
                                    <div class="flex justify-between text-[11px] text-slate-500">
                                        <span>Kesesuaian Spesifikasi</span>
                                        <span class="text-amber-700 font-semibold">Bobot 25%</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Status Operasional Kontrak & Parameter Logistik -->
                            <div class="bg-slate-50/80 rounded-xl p-3.5 border border-slate-200/70 flex flex-wrap items-center justify-between gap-3 text-xs tabular-nums">
                                <div class="flex items-center gap-2">
                                    <span class="text-slate-500 font-medium">Status Kepatuhan SLA:</span>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg font-semibold text-[11px] transition-colors"
                                          :class="{
                                              'bg-emerald-50 border border-emerald-200/80 text-emerald-700': supplierScores['{{ $sup?->id }}']?.grade === 'A',
                                              'bg-sky-50 border border-sky-200/80 text-sky-700': supplierScores['{{ $sup?->id }}']?.grade === 'B',
                                              'bg-amber-50 border border-amber-200/80 text-amber-800': supplierScores['{{ $sup?->id }}']?.grade === 'C',
                                              'bg-rose-50 border border-rose-200/80 text-rose-700': supplierScores['{{ $sup?->id }}']?.grade === 'D',
                                              'bg-slate-100 border border-slate-200/80 text-slate-700': !supplierScores['{{ $sup?->id }}'] || supplierScores['{{ $sup?->id }}']?.grade === 'UNRATED'
                                          }">
                                        <template x-if="supplierScores['{{ $sup?->id }}']?.grade === 'A'">
                                            <span class="flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                <span>SLA Prima (Top Tier)</span>
                                            </span>
                                        </template>
                                        <template x-if="supplierScores['{{ $sup?->id }}']?.grade === 'B'">
                                            <span class="flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                <span>SLA Standar Baik</span>
                                            </span>
                                        </template>
                                        <template x-if="supplierScores['{{ $sup?->id }}']?.grade === 'C'">
                                            <span class="flex items-center gap-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                <span>Perhatian: Defisit Keterlambatan</span>
                                            </span>
                                        </template>
                                        <template x-if="supplierScores['{{ $sup?->id }}']?.grade === 'D'">
                                            <span class="flex items-center gap-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                                <span>Peringatan Pelanggaran SLA</span>
                                            </span>
                                        </template>
                                        <template x-if="!supplierScores['{{ $sup?->id }}'] || supplierScores['{{ $sup?->id }}']?.grade === 'UNRATED'">
                                            <span>Masa Observasi (Vendor Baru)</span>
                                        </template>
                                    </span>
                                </div>

                                <div class="flex flex-wrap items-center gap-4 text-slate-600 text-[11px]">
                                    <div>
                                        <span class="text-slate-400">Target Buffer Dapur:</span>
                                        <span class="font-bold text-slate-800 ml-1" x-text="(supplierScores['{{ $sup?->id }}']?.grade === 'A' || supplierScores['{{ $sup?->id }}']?.grade === 'B') ? '1 Hari' : '2–3 Hari'">
                                            {{ ($score && in_array($score->grade, ['A', 'B'])) ? '1 Hari' : '2–3 Hari' }}
                                        </span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400">Rekomendasi Kuota:</span>
                                        <span class="font-bold text-slate-800 ml-1" x-text="supplierScores['{{ $sup?->id }}']?.is_recommended_for_renewal ? 'Prioritas Utama' : 'Evaluasi Cadangan'">
                                            {{ ($score && $score->isRecommendedForRenewal()) ? 'Prioritas Utama' : 'Evaluasi Cadangan' }}
                                        </span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400">Pengiriman Tuntas:</span>
                                        <span class="font-bold text-slate-800 ml-1" x-text="(supplierScores['{{ $sup?->id }}']?.total_shipments || 0) + ' Order'">{{ $metrics['total_shipments'] ?? 0 }} Order</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

    <!-- ========================================================= -->
    <!-- MODAL FORM PENGAJUAN PERMINTAAN KATALOG                   -->
    <!-- ========================================================= -->
    <div x-cloak x-show="selectedCommodity" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div @click.away="selectedCommodity = null" class="bg-white max-w-2xl w-full rounded-2xl p-6 sm:p-7 space-y-5 shadow-xl relative border border-slate-200/80 max-h-[92vh] overflow-y-auto">
            
            <div class="flex justify-between items-start border-b border-slate-100 pb-4">
                <div class="flex items-center gap-4">
                    <template x-if="selectedCommodity?.image_url">
                        <img :src="selectedCommodity?.image_url" :alt="selectedCommodity?.commodity_name" class="w-16 h-16 rounded-xl object-cover border border-slate-200/80 shrink-0">
                    </template>
                    <template x-if="!selectedCommodity?.image_url">
                        <div class="w-16 h-16 rounded-xl bg-slate-50 border border-slate-200/80 flex flex-col items-center justify-center shrink-0 text-slate-400">
                            <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </template>
                    <div>
                        <span class="text-[11px] font-bold text-[#ea580c] uppercase tracking-wider">Pengajuan Kemitraan &amp; Jadwal Pasokan</span>
                        <h3 class="text-xl font-bold text-slate-900 tracking-tight mt-0.5" x-text="selectedCommodity?.commodity_name"></h3>
                        <p class="text-xs text-slate-500 mt-0.5">Mitra Pemasok: <span class="font-semibold text-slate-900" x-text="selectedCommodity?.company_name"></span></p>
                    </div>
                </div>
                <button type="button" @click="selectedCommodity = null" class="text-slate-400 hover:text-slate-700 cursor-pointer p-1.5 rounded-lg hover:bg-slate-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Quick Details Komoditas -->
            <div class="bg-slate-50/90 p-4 rounded-xl border border-slate-200/70 flex flex-wrap justify-between items-center gap-3 text-xs tabular-nums">
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-semibold tracking-wider">Harga Grosir</span>
                    <span class="text-[#ea580c] font-bold text-sm" x-text="selectedCommodity?.price"></span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-semibold tracking-wider">Kapasitas Stok</span>
                    <span class="text-slate-800 font-semibold" x-text="selectedCommodity?.stock_capacity ?? '-'"></span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-semibold tracking-wider">Status Ketersediaan</span>
                    <span class="text-emerald-700 font-bold" x-text="selectedCommodity?.stock_status"></span>
                </div>
                <template x-if="selectedCommodity?.is_halal">
                    <span class="bg-emerald-50 text-emerald-700 border border-emerald-200/80 px-3 py-1 rounded-lg text-[10px] font-semibold inline-flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span>Sertifikat Halal</span>
                    </span>
                </template>
            </div>

            <form method="POST" action="{{ route('umkm.katalog.ajukan') }}" class="space-y-4 text-xs">
                @csrf
                <input type="hidden" name="supplier_product_id" :value="selectedCommodity?.id">
                <input type="hidden" name="supplier_id" :value="selectedCommodity?.supplier_id">
                <input type="hidden" name="unit" :value="selectedCommodity?.unit">
                <input type="hidden" name="volume" :value="formVolume">
                <input type="hidden" name="total_contract_volume" :value="calculateMonthlyVolume()">

                <template x-for="day in formDays" :key="day">
                    <input type="hidden" name="delivery_days[]" :value="day">
                </template>

                <!-- 1. Volume Pengiriman per Jadwal -->
                <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-200/80 space-y-2.5">
                    <div class="flex flex-wrap justify-between items-center gap-1">
                        <label class="text-slate-800 font-semibold">Volume per Pengiriman</label>
                        <span class="text-[11px] text-slate-500">Kuantitas bahan baku setiap jadwal pengiriman</span>
                    </div>
                    <div class="flex flex-wrap sm:flex-nowrap items-center gap-2">
                        <div class="relative flex-1 min-w-[160px]">
                            <input 
                                type="number" 
                                name="volume_per_delivery" 
                                required 
                                x-model.number="formVolume"
                                min="0.1"
                                step="any"
                                class="w-full pl-3.5 pr-16 py-2.5 rounded-xl bg-white text-slate-900 font-bold tabular-nums border border-slate-200/90 focus:outline-none focus:ring-2 focus:ring-orange-500/15 focus:border-[#ea580c] transition"
                            />
                            <span class="absolute right-3.5 top-2.5 text-slate-400 font-bold uppercase text-xs" x-text="selectedCommodity?.unit"></span>
                        </div>
                        <div class="flex items-center gap-1.5 tabular-nums">
                            <button type="button" @click="formVolume = 50" class="px-2.5 py-2 rounded-lg bg-white border border-slate-200/90 text-slate-700 font-semibold hover:border-[#ea580c] hover:text-[#ea580c] transition text-[11px] cursor-pointer">50</button>
                            <button type="button" @click="formVolume = 100" class="px-2.5 py-2 rounded-lg bg-white border border-slate-200/90 text-slate-700 font-semibold hover:border-[#ea580c] hover:text-[#ea580c] transition text-[11px] cursor-pointer">100</button>
                            <button type="button" @click="formVolume = 200" class="px-2.5 py-2 rounded-lg bg-white border border-slate-200/90 text-slate-700 font-semibold hover:border-[#ea580c] hover:text-[#ea580c] transition text-[11px] cursor-pointer">200</button>
                            <button type="button" @click="formVolume = 500" class="px-2.5 py-2 rounded-lg bg-white border border-slate-200/90 text-slate-700 font-semibold hover:border-[#ea580c] hover:text-[#ea580c] transition text-[11px] cursor-pointer">500</button>
                        </div>
                    </div>
                </div>

                <!-- 2. Frekuensi & Jadwal Pengiriman -->
                <div class="space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div>
                            <label class="block text-slate-700 font-semibold mb-1.5">Pilihan Pola Frekuensi</label>
                            <select 
                                name="frequency_type" 
                                x-model="formFrequency"
                                @change="onFrequencySelect($event.target.value)"
                                required 
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200/90 text-slate-900 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/15 focus:border-[#ea580c] transition cursor-pointer">
                                <option value="2x Seminggu (Senin & Kamis)">2x Seminggu (Senin &amp; Kamis)</option>
                                <option value="3x Seminggu (Senin, Rabu, Jumat)">3x Seminggu (Senin, Rabu, Jumat)</option>
                                <option value="Setiap Hari">Setiap Hari (Senin - Minggu)</option>
                                <option value="1x Seminggu">1x Seminggu (Senin)</option>
                                <option value="Kustom">Kustomisasi Hari Sendiri</option>
                            </select>
                            <p x-show="formFrequency === 'Kustom'" class="text-[10px] text-[#ea580c] font-semibold mt-1 tracking-tight tabular-nums">
                                Pola Kustom: <span x-text="formDays.length + ' Hari/Minggu (' + formDays.join(', ') + ')'"></span>
                            </p>
                        </div>

                        <div>
                            <label class="block text-slate-700 font-semibold mb-1.5">Target Jam Tiba di Dapur (WIB)</label>
                            <input 
                                type="time" 
                                name="delivery_time" 
                                x-model="formTime"
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200/90 text-slate-900 font-medium tabular-nums focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/15 focus:border-[#ea580c] transition"
                            />
                        </div>
                    </div>

                    <!-- Interactive Days Selector Pills -->
                    <div class="bg-slate-50/70 p-3.5 rounded-xl border border-slate-200/80 space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-700 font-semibold text-[11px]">Hari Pengiriman Terjadwal</span>
                            <span class="text-slate-400 text-[10px] tabular-nums" x-text="formDays.length + ' hari terpilih'"></span>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="day in availableDays" :key="'pill-' + day">
                                <button 
                                    type="button"
                                    @click="toggleDay(day)"
                                    class="px-3 py-1.5 rounded-xl text-xs font-semibold transition cursor-pointer inline-flex items-center gap-1"
                                    :class="formDays.includes(day) 
                                        ? 'bg-[#ea580c] text-white shadow-2xs' 
                                        : 'bg-white border border-slate-200/90 text-slate-600 hover:bg-slate-100'">
                                    <span x-text="day"></span>
                                    <svg x-show="formDays.includes(day)" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- 3. Tanggal Mulai Pengiriman Perdana -->
                <div>
                    <label class="block text-slate-700 font-semibold mb-1.5">Tanggal Mulai Pengiriman Perdana</label>
                    <input 
                        type="date" 
                        name="start_date" 
                        x-model="formStartDate"
                        required 
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200/90 text-slate-900 font-medium tabular-nums focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/15 focus:border-[#ea580c] transition"
                    />
                </div>

                <!-- 4. Live Ringkasan Kalkulasi & Proyeksi Kontrak Bulanan -->
                <div class="bg-orange-50/75 p-4 rounded-xl border border-orange-200/80 space-y-2 tabular-nums">
                    <span class="text-[10px] text-[#ea580c] uppercase font-bold tracking-wider block">
                        Ringkasan Proyeksi &amp; Estimasi Nilai Kemitraan
                    </span>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 pt-1">
                        <div class="bg-white p-3 rounded-xl border border-orange-200/60">
                            <span class="text-[10px] text-slate-500 block">Subtotal / Pengiriman</span>
                            <span class="font-bold text-slate-900 text-xs mt-0.5 block" x-text="calculateDeliverySubtotal()"></span>
                        </div>
                        <div class="bg-white p-3 rounded-xl border border-orange-200/60">
                            <span class="text-[10px] text-slate-500 block">Proyeksi Pasok Bulanan</span>
                            <span class="font-bold text-slate-900 text-xs mt-0.5 block" x-text="calculateMonthlyVolume() + ' ' + (selectedCommodity?.unit ?? 'kg')"></span>
                        </div>
                        <div class="bg-white p-3 rounded-xl border border-orange-200/60 col-span-2 sm:col-span-1">
                            <span class="text-[10px] text-[#ea580c] font-semibold block">Estimasi Nilai / Bulan</span>
                            <span class="font-bold text-[#ea580c] text-sm mt-0.5 block" x-text="calculateMonthlyTotal()"></span>
                        </div>
                    </div>
                </div>

                <!-- 5. Opsi Pemesanan Otomatis (Auto-Order Routine) -->
                <div class="bg-slate-50/80 p-3.5 rounded-xl border border-slate-200/80 flex items-start gap-3">
                    <input 
                        type="checkbox" 
                        id="autoOrderCheck"
                        name="is_auto_order_active" 
                        value="1" 
                        x-model="formAutoOrder"
                        class="accent-[#ea580c] w-4 h-4 rounded cursor-pointer mt-0.5"
                    />
                    <label for="autoOrderCheck" class="cursor-pointer space-y-0.5">
                        <span class="block text-slate-900 font-semibold text-xs">Aktifkan Pemesanan Otomatis (Auto-Order Routine)</span>
                        <span class="block text-slate-500 text-[11px] font-normal leading-relaxed">
                            Sistem akan otomatis menerbitkan jadwal pesanan di subuh hari pengiriman tanpa perlu konfirmasi manual berulang kali.
                        </span>
                    </label>
                </div>

                <!-- 6. Catatan Spesifikasi Mutu Dapur -->
                <div>
                    <label class="block text-slate-700 font-semibold mb-1.5">Catatan Spesifikasi Mutu &amp; Instruksi Dapur (Opsional)</label>
                    <textarea 
                        name="submission_details" 
                        rows="2" 
                        placeholder="Contoh: Pemotongan ayam karkas dibagi 8 bagian, toleransi tiba sebelum jam 06.00 WIB, kemasan tertutup rapi."
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200/90 text-slate-900 font-medium placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/15 focus:border-[#ea580c] transition"
                    ></textarea>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="selectedCommodity = null" class="w-1/2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200/90 py-2.5 rounded-xl font-semibold transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="w-1/2 bg-[#ea580c] hover:bg-[#c2410c] text-white py-2.5 px-4 rounded-xl font-semibold shadow-xs transition cursor-pointer inline-flex items-center justify-center gap-1.5">
                        <span>Kirim Pengajuan Kemitraan</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- MODAL PREVIEW DETAIL PESANAN OTOMATIS                     -->
    <!-- ========================================================= -->
    <div x-cloak x-show="previewAutoOrder" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div @click.away="previewAutoOrder = null" class="bg-white max-w-lg w-full rounded-2xl p-6 space-y-5 shadow-xl border border-slate-200/80">
            <div class="flex justify-between items-start border-b border-slate-100 pb-4">
                <div>
                    <span class="text-[10px] font-bold bg-orange-50 border border-orange-200/70 text-[#ea580c] px-2.5 py-0.5 rounded-lg uppercase tracking-wider tabular-nums">
                        Detail Kontrak · <span x-text="previewAutoOrder?.contractId"></span>
                    </span>
                    <h3 class="text-lg font-bold text-slate-900 mt-2 tracking-tight">
                        Preview Pesanan Otomatis
                    </h3>
                    <p class="text-xs font-semibold text-[#ea580c] tabular-nums" x-text="previewAutoOrder?.id"></p>
                </div>
                <button type="button" @click="previewAutoOrder = null" class="text-slate-400 hover:text-slate-700 cursor-pointer p-1.5 rounded-lg hover:bg-slate-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-4 text-xs tabular-nums">
                <div class="bg-slate-50/90 p-4 rounded-xl space-y-2.5 border border-slate-200/70">
                    <div class="flex justify-between items-center text-slate-800">
                        <span class="text-slate-500 font-medium">Pemasok Mitra</span>
                        <span class="font-bold text-slate-900" x-text="previewAutoOrder?.supplierName"></span>
                    </div>
                    <div class="flex justify-between items-center text-slate-800">
                        <span class="text-slate-500 font-medium">Komoditas Pasok</span>
                        <span class="font-bold text-[#ea580c]" x-text="previewAutoOrder?.commodity"></span>
                    </div>
                    <div class="flex justify-between items-center text-slate-800">
                        <span class="text-slate-500 font-medium">Volume Terikat Kontrak</span>
                        <span class="font-bold text-slate-900" x-text="(previewAutoOrder?.qty ?? 0) + ' ' + (previewAutoOrder?.unit ?? 'kg')"></span>
                    </div>
                    <div class="flex justify-between items-center text-slate-800">
                        <span class="text-slate-500 font-medium">Harga Satuan Grosir</span>
                        <span class="font-semibold text-slate-900" x-text="'Rp ' + (previewAutoOrder?.pricePerUnit ? previewAutoOrder.pricePerUnit.toLocaleString('id-ID') : 0) + ' / ' + (previewAutoOrder?.unit ?? 'kg')"></span>
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-500 font-medium">Jadwal Rutin</span>
                        <span class="font-semibold text-slate-900" x-text="previewAutoOrder?.deliverySchedule"></span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-500 font-medium">Estimasi Tiba Berikutnya</span>
                        <span class="font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200/80 px-2.5 py-0.5 rounded-lg" x-text="previewAutoOrder?.nextDelivery"></span>
                    </div>
                </div>

                <div class="bg-white p-3.5 rounded-xl border border-slate-200/80 space-y-1">
                    <span class="text-[10px] uppercase text-slate-400 block font-bold tracking-wider">Catatan Khusus Dapur</span>
                    <p class="font-medium text-slate-700 leading-relaxed" x-text="previewAutoOrder?.notes"></p>
                </div>

                <div class="p-4 bg-orange-50/75 rounded-xl flex justify-between items-center border border-orange-200/80">
                    <div>
                        <span class="text-[10px] uppercase text-[#ea580c] block font-bold tracking-wider">Total Tagihan Rutin</span>
                        <span class="text-xl font-bold text-slate-900" x-text="'Rp ' + (previewAutoOrder?.total ? previewAutoOrder.total.toLocaleString('id-ID') : 0)"></span>
                    </div>
                    <span class="bg-white border border-orange-200/80 text-[#ea580c] font-semibold text-[11px] px-3 py-1 rounded-lg">
                        Tagihan Otomatis
                    </span>
                </div>
            </div>

            <div class="flex gap-3 pt-1">
                <button 
                    type="button" 
                    @click="previewAutoOrder = null" 
                    class="w-1/2 bg-white hover:bg-slate-50 border border-slate-200/90 text-slate-700 py-2.5 rounded-xl font-semibold text-xs transition cursor-pointer">
                    Tutup
                </button>
                <template x-if="!isPublishedToday(previewAutoOrder)">
                    <form action="{{ route('umkm.pemesanan.terbitkan') }}" method="POST" class="w-1/2">
                        @csrf
                        <input type="hidden" name="schedule_id" :value="previewAutoOrder?.scheduleId">
                        <button 
                            type="submit" 
                            class="w-full bg-[#ea580c] hover:bg-[#c2410c] text-white py-2.5 px-3 rounded-xl font-semibold text-xs transition shadow-xs cursor-pointer flex items-center justify-center gap-1.5">
                            <span>Terbitkan Pesanan Sekarang</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    </form>
                </template>

                <template x-if="isPublishedToday(previewAutoOrder)">
                    <button 
                        type="button" 
                        disabled
                        class="w-1/2 bg-emerald-50 border border-emerald-200/80 text-emerald-700 py-2 px-3 rounded-xl font-semibold text-[10px] cursor-not-allowed flex flex-col items-center justify-center text-center leading-tight">
                        <span class="flex items-center gap-1 text-emerald-700 font-bold text-[11px]">
                            <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>Sudah Diterbitkan Hari Ini</span>
                        </span>
                        <span class="text-[9px] text-emerald-600 font-medium mt-0.5 truncate max-w-full" x-text="'Jadwal Selanjutnya: ' + getNextDeliveryScheduleText(previewAutoOrder)"></span>
                    </button>
                </template>
            </div>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- MODAL RINCIAN PEMBAYARAN MIDTRANS (BATCH INVOICE)         -->
    <!-- ========================================================= -->
    <div x-cloak x-show="payingInvoice" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div @click.away="payingInvoice = null" class="bg-white max-w-md w-full rounded-2xl p-6 space-y-5 shadow-xl border border-slate-200/80">
            <div class="flex justify-between items-start border-b border-slate-100 pb-3.5">
                <div class="space-y-0.5">
                    <div class="inline-flex items-center gap-1.5 text-[10px] font-bold text-[#ea580c] uppercase tracking-wider">
                        <svg class="w-3.5 h-3.5 text-[#ea580c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>Midtrans Secure Payment</span>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">Rincian Tagihan Pembayaran</h3>
                    <p class="text-xs text-slate-500 tabular-nums" x-text="'No. Invoice: ' + payingInvoice?.invoiceNumber"></p>
                </div>
                <button type="button" @click="payingInvoice = null" class="text-slate-400 hover:text-slate-700 cursor-pointer p-1.5 rounded-lg hover:bg-slate-100 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>

            <!-- DETAIL INVOICE BATCH -->
            <div class="bg-slate-50/90 p-4 rounded-xl border border-slate-200/70 space-y-2.5 text-xs tabular-nums">
                <div class="flex justify-between text-slate-600">
                    <span>Kloter Pengiriman</span>
                    <span class="font-bold text-[#ea580c]" x-text="payingInvoice?.batchName"></span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Ref. Pesanan</span>
                    <span class="font-bold text-slate-800" x-text="'#ORD-' + payingInvoice?.orderCode"></span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Mitra Pemasok</span>
                    <span class="font-bold text-slate-900" x-text="payingInvoice?.supplierName"></span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Komoditas &amp; Kuantitas</span>
                    <span class="font-bold text-slate-900" x-text="payingInvoice?.commodity + ' (' + payingInvoice?.quantity + ')'"></span>
                </div>
                <div class="flex justify-between text-sm text-[#ea580c] font-bold pt-2.5 border-t border-slate-200">
                    <span class="text-slate-800 font-semibold">Total Pembayaran</span>
                    <span x-text="'Rp ' + (payingInvoice?.amount ? payingInvoice.amount.toLocaleString('id-ID') : 0)"></span>
                </div>
            </div>

            <form :action="`/umkm/pemesanan/pembayaran/${payingInvoice?.id}/bayar`" method="POST" class="space-y-3.5">
                @csrf
                <input type="hidden" name="payment_method" :value="selectedPaymentMethod">

                <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-3 flex items-start gap-2.5 text-xs text-slate-600">
                    <svg class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-[11px] text-slate-600 leading-relaxed">Metode pembayaran (QRIS, Virtual Account BCA, Mandiri, BNI, BRI, e-Wallet) dapat dipilih langsung di jendela resmi Midtrans.</p>
                </div>

                <div class="space-y-2 pt-1">
                    <button type="button" 
                            @click="payingInvoice ? payBatchInvoiceSnap(payingInvoice) : null; payingInvoice = null;"
                            class="w-full bg-[#ea580c] hover:bg-[#c2410c] text-white py-2.5 rounded-xl font-semibold text-xs shadow-xs transition cursor-pointer flex items-center justify-center gap-2">
                        <svg class="w-4 h-4 text-white/90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <span>Lanjut ke Pembayaran Midtrans</span>
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="payingInvoice = null" class="w-1/3 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200/90 py-2.5 rounded-xl font-semibold text-xs transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" id="btn-simulasi-bayar" class="w-2/3 bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200/90 py-2.5 rounded-xl font-semibold text-[11px] transition cursor-pointer">
                            Simulasi Offline (Demo)
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL FALLBACK UNTUK MOCK ORDER DEMO -->
    <div x-cloak x-show="payingOrder" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div @click.away="payingOrder = null" class="bg-white max-w-md w-full rounded-2xl p-6 space-y-5 shadow-xl border border-slate-200/80">
            <div class="flex justify-between items-start border-b border-slate-100 pb-3.5">
                <div class="space-y-0.5">
                    <span class="text-[10px] font-bold text-[#ea580c] uppercase tracking-wider">Midtrans Payment Gateway</span>
                    <h3 class="text-lg font-bold text-slate-900">Pembayaran Tagihan Pasokan</h3>
                </div>
                <button type="button" @click="payingOrder = null" class="text-slate-400 hover:text-slate-700 cursor-pointer p-1.5 rounded-lg hover:bg-slate-100 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>

            <div class="bg-slate-50/90 p-4 rounded-xl border border-slate-200/70 space-y-2.5 text-xs tabular-nums">
                <div class="flex justify-between text-slate-600"><span>No. Order</span><span class="font-bold text-[#ea580c]" x-text="payingOrder?.id"></span></div>
                <div class="flex justify-between text-slate-600"><span>Pemasok</span><span class="font-bold text-slate-900" x-text="payingOrder?.supplierName"></span></div>
                <div class="flex justify-between text-slate-600"><span>Komoditas</span><span class="font-bold text-slate-900" x-text="payingOrder?.commodity"></span></div>
                <div class="flex justify-between text-sm text-[#ea580c] font-bold pt-2.5 border-t border-slate-200">
                    <span class="text-slate-800 font-semibold">Total Tagihan</span>
                    <span x-text="'Rp ' + (payingOrder?.total ? payingOrder.total.toLocaleString('id-ID') : 0)"></span>
                </div>
            </div>

            <button type="button" @click="simulateMidtransSuccess()" class="w-full bg-[#ea580c] hover:bg-[#c2410c] text-white py-2.5 rounded-xl font-semibold text-xs shadow-xs transition cursor-pointer">
                Simulasi Pembayaran Berhasil (Demo)
            </button>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- MODAL DETAIL EVALUASI PERFORMA PEMASOK (SLA TRACKER)     -->
    <!-- ========================================================= -->
    <div x-cloak x-show="selectedSupplierPerformance || isLoadingPerformance" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div @click.away="closeSupplierTrackerModal()" class="bg-white max-w-2xl w-full rounded-2xl p-6 sm:p-7 space-y-5 shadow-xl relative border border-slate-200/80 max-h-[90vh] overflow-y-auto">
            
            <!-- Loading State -->
            <template x-if="isLoadingPerformance">
                <div class="py-16 text-center space-y-3">
                    <div class="w-8 h-8 border-3 border-[#ea580c] border-t-transparent rounded-full animate-spin mx-auto"></div>
                    <p class="text-xs font-semibold text-slate-500">Memuat analisis performa &amp; data riil logistik...</p>
                </div>
            </template>

            <!-- Loaded Content -->
            <template x-if="!isLoadingPerformance && selectedSupplierPerformance">
                <div class="space-y-5">
                    <!-- Modal Header -->
                    <div class="flex justify-between items-start border-b border-slate-100 pb-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-bold text-[#ea580c] uppercase tracking-wider">Rapor SLA &amp; Performa Pemasok</span>
                                <span :class="selectedSupplierPerformance?.grade_badge_color" class="px-2 py-0.5 rounded-md text-[10px] font-semibold border" x-text="'Grade ' + selectedSupplierPerformance?.grade"></span>
                            </div>
                            <h3 class="text-xl font-bold text-slate-900 tracking-tight" x-text="selectedSupplierPerformance?.company_name"></h3>
                            <p class="text-xs text-slate-500 flex items-center gap-1.5 flex-wrap">
                                <span x-text="selectedSupplierPerformance?.warehouse_address"></span>
                                <span class="text-slate-300">•</span>
                                <span x-text="'Penanggung Jawab: ' + (selectedSupplierPerformance?.owner_name || '-')"></span>
                            </p>
                        </div>
                        <button type="button" @click="closeSupplierTrackerModal()" class="text-slate-400 hover:text-slate-700 cursor-pointer p-1.5 rounded-lg hover:bg-slate-100 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Score Card -->
                    <div class="bg-orange-50/70 rounded-2xl p-5 border border-orange-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4 tabular-nums">
                        <div class="space-y-1">
                            <span class="text-[10px] font-bold text-[#ea580c] uppercase tracking-wider block">Skor Performa &amp; Kepatuhan Kontrak</span>
                            <div class="flex items-baseline gap-2">
                                <span class="text-3xl sm:text-4xl font-bold text-[#ea580c] tracking-tight" x-text="selectedSupplierPerformance?.final_score"></span>
                                <span class="text-xs font-medium text-slate-400">/ 100</span>
                                <span class="bg-amber-100 text-amber-800 font-semibold px-2.5 py-0.5 rounded-lg text-xs inline-flex items-center gap-1 ml-2">
                                    <svg class="w-3.5 h-3.5 text-amber-500 fill-amber-500" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    <span x-text="selectedSupplierPerformance?.star_rating + ' / 5.0'"></span>
                                </span>
                            </div>
                            <p class="text-xs font-semibold text-slate-700 mt-1" x-text="selectedSupplierPerformance?.grade_label"></p>
                        </div>
                        <div class="sm:text-right sm:border-l sm:border-orange-200/70 sm:pl-5 text-xs space-y-1">
                            <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Total Riwayat Pengiriman</div>
                            <div class="text-2xl font-bold text-slate-900" x-text="selectedSupplierPerformance?.metrics_summary?.total_shipments + ' Order'"></div>
                            <div class="text-[10px] text-slate-400" x-text="'Kalkulasi: ' + selectedSupplierPerformance?.calculated_at"></div>
                        </div>
                    </div>

                    <!-- 3 Pilar Penilaian Metrik Riil -->
                    <div class="space-y-2.5 tabular-nums">
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Rincian 3 Pilar Evaluasi Objektif</span>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                            <!-- Pilar 1 -->
                            <div class="bg-emerald-50/60 rounded-xl border border-emerald-200/60 p-3.5 space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="font-semibold text-slate-800">1. On-Time Delivery</span>
                                    <span class="font-bold text-emerald-700" x-text="selectedSupplierPerformance?.scores?.on_time_delivery + ' pt'"></span>
                                </div>
                                <div class="text-[11px] text-slate-600 space-y-1 pt-1.5 border-t border-emerald-200/60">
                                    <div class="flex justify-between"><span>Ketepatan</span><span class="font-semibold text-slate-900" x-text="selectedSupplierPerformance?.metrics_summary?.otd_rate + '%'"></span></div>
                                    <div class="flex justify-between"><span>Rerata Telat</span><span class="font-semibold text-slate-900" x-text="selectedSupplierPerformance?.metrics_summary?.avg_delay_hours + ' jam'"></span></div>
                                    <div class="flex justify-between"><span>Telat Parah (&gt;6j)</span><span class="font-semibold" :class="selectedSupplierPerformance?.metrics_summary?.severe_delays_count > 0 ? 'text-rose-600' : 'text-slate-900'" x-text="selectedSupplierPerformance?.metrics_summary?.severe_delays_count + 'x'"></span></div>
                                </div>
                            </div>

                            <!-- Pilar 2 -->
                            <div class="bg-sky-50/60 rounded-xl border border-sky-200/60 p-3.5 space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="font-semibold text-slate-800">2. Pemenuhan Kuota</span>
                                    <span class="font-bold text-sky-700" x-text="selectedSupplierPerformance?.scores?.quota_fulfillment + ' pt'"></span>
                                </div>
                                <div class="text-[11px] text-slate-600 space-y-1 pt-1.5 border-t border-sky-200/60">
                                    <div class="flex justify-between"><span>Fill Rate</span><span class="font-semibold text-slate-900" x-text="selectedSupplierPerformance?.metrics_summary?.fill_rate + '%'"></span></div>
                                    <div class="flex justify-between"><span>Rasio Backorder</span><span class="font-semibold text-slate-900" x-text="selectedSupplierPerformance?.metrics_summary?.backorder_rate + '%'"></span></div>
                                    <div class="flex justify-between"><span>Pembatalan</span><span class="font-semibold" :class="selectedSupplierPerformance?.metrics_summary?.cancellation_rate > 0 ? 'text-rose-600' : 'text-slate-900'" x-text="selectedSupplierPerformance?.metrics_summary?.cancellation_rate + '%'"></span></div>
                                </div>
                            </div>

                            <!-- Pilar 3 -->
                            <div class="bg-amber-50/60 rounded-xl border border-amber-200/60 p-3.5 space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="font-semibold text-slate-800">3. Kualitas &amp; Ulasan</span>
                                    <span class="font-bold text-amber-700" x-text="selectedSupplierPerformance?.scores?.quality_rating + ' pt'"></span>
                                </div>
                                <div class="text-[11px] text-slate-600 space-y-1 pt-1.5 border-t border-amber-200/60">
                                    <div class="flex justify-between"><span>Bebas Retur</span><span class="font-semibold text-slate-900" x-text="selectedSupplierPerformance?.metrics_summary?.defect_free_rate + '%'"></span></div>
                                    <div class="flex justify-between"><span>Rating Bintang</span><span class="font-semibold text-slate-900" x-text="selectedSupplierPerformance?.metrics_summary?.average_rating + ' / 5.0'"></span></div>
                                    <div class="flex justify-between"><span>Total Ulasan</span><span class="font-semibold text-slate-900" x-text="selectedSupplierPerformance?.metrics_summary?.total_reviews + ' review'"></span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Parameter Status Logistik & Kontrak -->
                    <div class="bg-slate-50/90 p-4 rounded-xl border border-slate-200/80 space-y-2 tabular-nums">
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Parameter Status Logistik &amp; Kontrak</span>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-xs">
                            <div class="bg-white p-3 rounded-xl border border-slate-200/70">
                                <span class="text-slate-400 text-[10px] uppercase font-semibold block">Cadangan Dapur (Buffer)</span>
                                <span class="font-bold text-slate-900 text-sm mt-0.5 block" x-text="selectedSupplierPerformance?.final_score >= 70 ? '1 Hari Operasional' : '2–3 Hari Operasional'"></span>
                                <span class="text-[10px] text-slate-500">Target stok pengaman</span>
                            </div>
                            <div class="bg-white p-3 rounded-xl border border-slate-200/70">
                                <span class="text-slate-400 text-[10px] uppercase font-semibold block">Status Perpanjangan</span>
                                <span class="font-bold text-sm mt-0.5 block" :class="selectedSupplierPerformance?.is_recommended_for_renewal ? 'text-emerald-700' : 'text-amber-700'" x-text="selectedSupplierPerformance?.is_recommended_for_renewal ? 'Prioritas Kuota Utama' : 'Perlu Evaluasi Ulang'"></span>
                                <span class="text-[10px] text-slate-500">Alokasi volume pasokan</span>
                            </div>
                            <div class="bg-white p-3 rounded-xl border border-slate-200/70">
                                <span class="text-slate-400 text-[10px] uppercase font-semibold block">Klausul Penalti SLA</span>
                                <span class="font-bold text-slate-900 text-sm mt-0.5 block" x-text="selectedSupplierPerformance?.metrics_summary?.avg_delay_hours > 2 ? 'Klaim Kompensasi' : 'Standar Kepatuhan Normal'"></span>
                                <span class="text-[10px] text-slate-500">Penyesuaian termin TOP</span>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex items-center justify-between gap-2 pt-2">
                        <button 
                            type="button" 
                            @click="recalculateSupplierTracker(selectedSupplierPerformance?.supplier_id, null, false)"
                            :disabled="recalculatingSupplierId === selectedSupplierPerformance?.supplier_id"
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200/90 transition flex items-center gap-1.5 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                            <svg :class="recalculatingSupplierId === selectedSupplierPerformance?.supplier_id ? 'animate-spin text-[#ea580c]' : 'text-slate-400'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span x-text="recalculatingSupplierId === selectedSupplierPerformance?.supplier_id ? 'Menghitung...' : 'Kalkulasi Ulang Realtime'">Kalkulasi Ulang Realtime</span>
                        </button>
                        <button 
                            type="button" 
                            @click="closeSupplierTrackerModal()" 
                            class="px-6 py-2 rounded-xl text-xs font-semibold bg-[#ea580c] hover:bg-[#c2410c] text-white transition shadow-xs cursor-pointer">
                            Tutup Rapor
                        </button>
                    </div>
                </div>
            </template>

        </div>
    </div>

</div>
@endsection
