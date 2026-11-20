@extends('layouts.umkm')

@section('content')
@php
    $serverActiveTab = request('tab') ?: 'katalog';
    $serverSubTab = request('subtab') ?: 'auto-order';
@endphp
<script>
    function umkmCatalogPage() {
        return {
            selectedCommodity: null,
            orderSubTab: (function() {
                try {
                    var fromUrl = (new URLSearchParams(window.location.search)).get('subtab');
                    if (fromUrl) return fromUrl;
                    var fromStorage = sessionStorage.getItem('sorai_umkm_order_subtab');
                    if (fromStorage) return fromStorage;
                } catch (e) {}
                return 'auto-order';
            })(),
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
            // Auto-Reload / Live-Sync State
            lastFingerprint: '{{ $stateFingerprint ?? "" }}',
            isSyncing: false,
            hasPendingSync: false,
            pollTimer: null,
            liveSyncActive: true,

            isAnyModalOpen: function() {
                var isTyping = Boolean(
                    document.activeElement &&
                    ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName) &&
                    document.activeElement.type !== 'button' &&
                    document.activeElement.type !== 'submit'
                );

                return Boolean(
                    isTyping ||
                    this.selectedCommodity ||
                    this.showAjukanConfirmModal ||
                    this.editingPermintaan ||
                    this.cancellingPermintaan ||
                    this.cancellingOrder ||
                    this.confirmingOrderId ||
                    this.confirmingBackorderId ||
                    this.rejectingBackorderId ||
                    this.approvingBackorderOrder ||
                    this.previewAutoOrder ||
                    this.payingOrder ||
                    this.payingInvoice ||
                    this.showReviewModal ||
                    this.ratingInputOrder ||
                    (this.selectedSupplierPerformance && !this.selectedSupplierPerformance.isClosed)
                );
            },

            init: function() {
                var self = this;

                // Jalankan filter katalog awal jika ada parameter search dari URL
                if (this.catalogSearch && this.catalogSearch.trim() !== '') {
                    this.$nextTick(function() {
                        self.filterCatalog();
                    });
                }

                // Pantau orderSubTab secara reaktif: simpan ke sessionStorage & URL replaceState
                this.$watch('orderSubTab', function(val) {
                    if (!val) return;
                    try {
                        sessionStorage.setItem('sorai_umkm_order_subtab', val);
                        var url = new URL(window.location.href);
                        url.searchParams.set('tab', 'pemesanan');
                        url.searchParams.set('subtab', val);
                        window.history.replaceState({}, '', url.toString());
                    } catch (e) {}
                });

                // Hitung mundur live timer sisa waktu edit 30 menit
                setInterval(function() {
                    if (self.hasPendingSync && !self.isAnyModalOpen()) {
                        self.hasPendingSync = false;
                        self.performSafeReload();
                    }
                }, 2000);

                // Hitung mundur live timer sisa waktu edit 30 menit
                setInterval(function() {
                    if (self.partnershipRequests && self.partnershipRequests.length > 0) {
                        self.partnershipRequests.forEach(function(item) {
                            if (item.remaining_seconds > 0) {
                                item.remaining_seconds -= 1;
                                item.remaining_minutes = Math.max(1, Math.ceil(item.remaining_seconds / 60));
                                if (item.remaining_seconds <= 0) {
                                    item.can_edit = false;
                                }
                            }
                        });
                    }
                }, 1000);
            },

            performSafeReload: function() {
                var self = this;
                try {
                    var targetUrl = new URL(window.location.href);
                    // Ambil tab aktif dari URL atau sessionStorage atau 'katalog'
                    var currentTab = (new URLSearchParams(window.location.search)).get('tab') 
                        || sessionStorage.getItem('sorai_umkm_active_tab') 
                        || 'katalog';
                    
                    targetUrl.searchParams.set('tab', currentTab);

                    if (currentTab === 'pemesanan') {
                        var currentSubTab = self.orderSubTab 
                            || (new URLSearchParams(window.location.search)).get('subtab')
                            || sessionStorage.getItem('sorai_umkm_order_subtab') 
                            || 'auto-order';
                        targetUrl.searchParams.set('subtab', currentSubTab);
                    } else {
                        targetUrl.searchParams.delete('subtab');
                    }

                    window.location.href = targetUrl.toString();
                } catch (e) {
                    window.location.reload();
                }
            },

            checkForUpdates: function() {
                var self = this;
                if (!self.liveSyncActive || self.isSyncing) return;

                fetch('{{ route("umkm.check-updates") }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(function(res) {
                    if (!res.ok) throw new Error('Network error');
                    return res.json();
                })
                .then(function(data) {
                    if (data && data.fingerprint) {
                        if (!self.lastFingerprint) {
                            self.lastFingerprint = data.fingerprint;
                            return;
                        }

                        if (data.fingerprint !== self.lastFingerprint) {
                            self.lastFingerprint = data.fingerprint;
                            self.handleNewDataDetected();
                        }
                    }
                }, 1000);
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
            selectedQualityTerms: ['Garansi Segar & Bebas Busuk', 'Wajib Tiba Tepat Waktu', 'Kemasan Tertutup & Higienis'],
            availableQualityTerms: [
                { id: 'freshness', label: 'Garansi Segar & Bebas Busuk', desc: 'Bebas aroma tidak sedap / ganti baru jika cacat' },
                { id: 'ontime', label: 'Wajib Tiba Tepat Waktu', desc: 'Sesuai jam buka dapur (toleransi maks. 30 menit)' },
                { id: 'packaging', label: 'Kemasan Tertutup & Higienis', desc: 'Bahan baku terlindung dari debu & kontaminasi' },
                { id: 'return_claim', label: 'Klaim Retur Maks. 2 Jam', desc: 'Waktu inspeksi fisik dapur pasca terima bahan' },
                { id: 'cold_chain', label: 'Suhu Dingin Terjaga (Chilled)', desc: 'Komoditas daging / sayur tetap dingin di jalan' }
            ],

            toggleQualityTerm: function(label) {
                var idx = this.selectedQualityTerms.indexOf(label);
                if (idx > -1) {
                    this.selectedQualityTerms.splice(idx, 1);
                } else {
                    this.selectedQualityTerms.push(label);
                }
            },

            openAjukanModal: function(commodity) {
                this.selectedCommodity = commodity;
                this.formVolume = 100;
                this.formFrequency = '2x Seminggu (Senin & Kamis)';
                this.formDays = ['Senin', 'Kamis'];
                this.formTime = '05:30';
                this.formStartDate = '{{ now()->addDay()->toDateString() }}';
                this.formAutoOrder = true;
                this.selectedQualityTerms = ['Garansi Segar & Bebas Busuk', 'Wajib Tiba Tepat Waktu', 'Kemasan Tertutup & Higienis'];
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

            orders: @json($dashboardOrders ?? []),

            autoOrdersList: @json($autoOrdersList ?? []),

            partnershipRequests: @json($partnershipRequests ?? []),
            showAjukanConfirmModal: false,
            editingPermintaan: null,
            cancellingPermintaan: null,
            cancellingOrder: null,
            openBatalPermintaanModal: function(req) {
                if (!req || !req.can_edit) {
                    alert('Batas waktu pembatalan 30 menit telah berakhir atau pengajuan sudah diproses.');
                    return;
                }
                this.cancellingPermintaan = req;
            },
            closeBatalPermintaanModal: function() {
                this.cancellingPermintaan = null;
            },
            openBatalOrderModal: function(id, code, timeLeft) {
                this.cancellingOrder = { id: id, code: code, timeLeft: timeLeft };
            },
            closeBatalOrderModal: function() {
                this.cancellingOrder = null;
            },
            editForm: {
                id: '',
                volume_per_delivery: 100,
                unit: 'kg',
                frequency_type: '',
                delivery_days: [],
                delivery_time: '05:30',
                start_date: '',
                is_auto_order_active: true,
                terms: [],
                submission_details: ''
            },
            openAjukanConfirmModal: function() {
                if (!this.formVolume || parseFloat(this.formVolume) <= 0) {
                    alert('Mohon masukkan volume per pengiriman yang valid.');
                    return;
                }
                if (!this.formDays || this.formDays.length === 0) {
                    alert('Mohon pilih minimal 1 hari jadwal pengiriman.');
                    return;
                }
                this.showAjukanConfirmModal = true;
            },
            submitFinalAjukan: function() {
                if (this.$refs.ajukanForm) {
                    this.$refs.ajukanForm.submit();
                }
            },
            openEditPermintaanModal: function(req) {
                if (!req || !req.can_edit) {
                    alert('Batas waktu edit 30 menit telah berakhir atau status pengajuan sudah diproses.');
                    return;
                }
                this.editingPermintaan = req;
                this.editForm = {
                    id: req.id,
                    volume_per_delivery: req.volume_per_delivery,
                    unit: req.unit,
                    frequency_type: req.frequency_type,
                    delivery_days: Array.isArray(req.delivery_days) ? [...req.delivery_days] : ['Senin', 'Kamis'],
                    delivery_time: req.delivery_time || '05:30',
                    start_date: req.start_date || '',
                    is_auto_order_active: Boolean(req.is_auto_order_active),
                    terms: Array.isArray(req.quality_terms) ? [...req.quality_terms] : [],
                    submission_details: req.clean_notes || ''
                };
            },
            closeEditPermintaanModal: function() {
                this.editingPermintaan = null;
            },
            toggleEditDay: function(day) {
                var idx = this.editForm.delivery_days.indexOf(day);
                if (idx > -1) {
                    if (this.editForm.delivery_days.length > 1) {
                        this.editForm.delivery_days.splice(idx, 1);
                    }
                } else {
                    this.editForm.delivery_days.push(day);
                }
            },
            toggleEditQualityTerm: function(label) {
                var idx = this.editForm.terms.indexOf(label);
                if (idx > -1) {
                    this.editForm.terms.splice(idx, 1);
                } else {
                    this.editForm.terms.push(label);
                }
            },
            calculateEditMonthlyVolume: function() {
                if (!this.editingPermintaan) return 0;
                var vol = parseFloat(this.editForm.volume_per_delivery) || 0;
                var daysCount = (this.editForm.delivery_days && this.editForm.delivery_days.length) ? this.editForm.delivery_days.length : 1;
                return Math.round(vol * daysCount * 4);
            },
            calculateEditMonthlyTotal: function() {
                if (!this.editingPermintaan) return 'Rp 0';
                var totalVol = this.calculateEditMonthlyVolume();
                var price = parseFloat(this.editingPermintaan.price) || 0;
                return 'Rp ' + Math.round(totalVol * price).toLocaleString('id-ID');
            },

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
                    umkmName: '{{ addslashes($authUser?->umkmProfile?->business_name ?? 'Dapur UMKM') }}',
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
                this.notify('Pesanan berhasil diterbitkan untuk hari ini! Jadwal penerbitan selanjutnya: ' + nextText);
                this.orderSubTab = 'auto-order';
            },

            confirmGoodsReceived: function(orderId) {
                this.openConfirmModal(orderId);
            },

            openConfirmModal: function(orderId) {
                this.stopCamera();
                this.resetConfirmPhoto();
                this.confirmingOrderId = orderId;
                this.confirmNotes = '';
            },

            closeConfirmModal: function() {
                this.stopCamera();
                this.confirmingOrderId = null;
                this.resetConfirmPhoto();
            },

            openConfirmBackorderModal: function(orderId) {
                this.stopCamera();
                this.resetConfirmBackorderPhoto();
                this.confirmingBackorderId = orderId;
                this.confirmBackorderNotes = '';
            },

            closeConfirmBackorderModal: function() {
                this.stopCamera();
                this.confirmingBackorderId = null;
                this.resetConfirmBackorderPhoto();
            },

            confirmingOrderId: null,
            confirmPhotoPreview: null,
            confirmPhotoSource: null,
            confirmNotes: '',

            confirmingBackorderId: null,
            confirmBackorderPhotoPreview: null,
            confirmBackorderPhotoSource: null,
            confirmBackorderNotes: '',

            cameraActive: false,
            cameraTarget: null,
            cameraStream: null,
            cameraError: null,
            isTakingPhoto: false,

            startCamera: async function(target) {
                var self = this;
                self.cameraTarget = target;
                self.cameraError = null;
                self.isTakingPhoto = false;

                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    if (target === 'batch1' && self.$refs.cameraInput) {
                        self.$refs.cameraInput.click();
                    } else if (target === 'batch2' && self.$refs.cameraBackorderInput) {
                        self.$refs.cameraBackorderInput.click();
                    } else {
                        alert('Perangkat Anda tidak mendukung akses kamera langsung pada browser ini.');
                    }
                    return;
                }

                self.stopCamera();

                try {
                    var constraints = {
                        video: {
                            facingMode: { ideal: 'environment' },
                            width: { ideal: 1280 },
                            height: { ideal: 720 }
                        },
                        audio: false
                    };

                    var stream = await navigator.mediaDevices.getUserMedia(constraints);
                    self.cameraStream = stream;
                    self.cameraActive = true;
                    self.cameraTarget = target;

                    self.$nextTick(function() {
                        var videoId = target === 'batch1' ? 'cameraVideoBatch1' : 'cameraVideoBatch2';
                        var videoEl = document.getElementById(videoId);
                        if (videoEl) {
                            videoEl.srcObject = stream;
                            videoEl.play().catch(function(e) {
                                console.warn('Video play catch:', e);
                            });
                        }
                    });
                } catch (err) {
                    console.warn('Akses kamera tidak diizinkan atau gagal:', err);
                    self.cameraActive = false;
                    self.cameraStream = null;
                    self.cameraTarget = null;
                    
                    if (target === 'batch1' && self.$refs.cameraInput) {
                        self.$refs.cameraInput.click();
                    } else if (target === 'batch2' && self.$refs.cameraBackorderInput) {
                        self.$refs.cameraBackorderInput.click();
                    } else {
                        alert('Tidak dapat mengakses kamera. Pastikan izin kamera telah diizinkan di browser Anda.');
                    }
                }
            },

            captureCameraPhoto: function(target) {
                var self = this;
                var videoId = target === 'batch1' ? 'cameraVideoBatch1' : 'cameraVideoBatch2';
                var videoEl = document.getElementById(videoId);

                if (!videoEl || !videoEl.videoWidth || !videoEl.videoHeight) {
                    alert('Kamera sedang memuat, silakan tunggu sebentar lalu tekan jepret lagi.');
                    return;
                }

                self.isTakingPhoto = true;

                try {
                    var canvas = document.createElement('canvas');
                    canvas.width = videoEl.videoWidth;
                    canvas.height = videoEl.videoHeight;
                    var ctx = canvas.getContext('2d');
                    ctx.drawImage(videoEl, 0, 0, canvas.width, canvas.height);

                    canvas.toBlob(function(blob) {
                        if (!blob) {
                            alert('Gagal mengambil gambar dari kamera.');
                            self.isTakingPhoto = false;
                            return;
                        }

                        var fileName = 'bukti_kamera_' + Date.now() + '.jpg';
                        var file = new File([blob], fileName, { type: 'image/jpeg' });

                        if (target === 'batch1') {
                            self.confirmPhotoPreview = URL.createObjectURL(blob);
                            self.confirmPhotoSource = 'camera';
                            if (window.DataTransfer && self.$refs.photoInput) {
                                try {
                                    var dt = new DataTransfer();
                                    dt.items.add(file);
                                    self.$refs.photoInput.files = dt.files;
                                } catch (e) {
                                    console.warn('DataTransfer error:', e);
                                }
                            }
                        } else {
                            self.confirmBackorderPhotoPreview = URL.createObjectURL(blob);
                            self.confirmBackorderPhotoSource = 'camera';
                            if (window.DataTransfer && self.$refs.photoBackorderInput) {
                                try {
                                    var dt = new DataTransfer();
                                    dt.items.add(file);
                                    self.$refs.photoBackorderInput.files = dt.files;
                                } catch (e) {
                                    console.warn('DataTransfer error:', e);
                                }
                            }
                        }

                        self.stopCamera();
                        self.isTakingPhoto = false;
                    }, 'image/jpeg', 0.88);
                } catch (e) {
                    console.error('Capture error:', e);
                    self.isTakingPhoto = false;
                    alert('Gagal memproses gambar kamera.');
                }
            },

            stopCamera: function() {
                if (this.cameraStream) {
                    this.cameraStream.getTracks().forEach(function(track) {
                        try {
                            track.stop();
                        } catch (e) {}
                    });
                    this.cameraStream = null;
                }
                this.cameraActive = false;
                this.cameraTarget = null;
                this.isTakingPhoto = false;
            },

            onPhotoPicked: function(event, source) {
                var file = event.target.files[0];
                if (file) {
                    this.confirmPhotoPreview = URL.createObjectURL(file);
                    this.confirmPhotoSource = source;
                    if (window.DataTransfer && this.$refs.photoInput) {
                        try {
                            var dt = new DataTransfer();
                            dt.items.add(file);
                            this.$refs.photoInput.files = dt.files;
                        } catch (e) {}
                    }
                }
            },

            resetConfirmPhoto: function() {
                this.stopCamera();
                this.confirmPhotoPreview = null;
                this.confirmPhotoSource = null;
                if (this.$refs.photoInput) this.$refs.photoInput.value = '';
                if (this.$refs.cameraInput) this.$refs.cameraInput.value = '';
                if (this.$refs.galleryInput) this.$refs.galleryInput.value = '';
            },

            onBackorderPhotoPicked: function(event, source) {
                var file = event.target.files[0];
                if (file) {
                    this.confirmBackorderPhotoPreview = URL.createObjectURL(file);
                    this.confirmBackorderPhotoSource = source;
                    if (window.DataTransfer && this.$refs.photoBackorderInput) {
                        try {
                            var dt = new DataTransfer();
                            dt.items.add(file);
                            this.$refs.photoBackorderInput.files = dt.files;
                        } catch (e) {}
                    }
                }
            },

            resetConfirmBackorderPhoto: function() {
                this.stopCamera();
                this.confirmBackorderPhotoPreview = null;
                this.confirmBackorderPhotoSource = null;
                if (this.$refs.photoBackorderInput) this.$refs.photoBackorderInput.value = '';
                if (this.$refs.cameraBackorderInput) this.$refs.cameraBackorderInput.value = '';
                if (this.$refs.galleryBackorderInput) this.$refs.galleryBackorderInput.value = '';
            },

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
                    self.notify(resData.message || 'Ulasan & Rating berhasil disimpan!');
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
                this.notify('Estimasi Collaborative Supply Plan (' + this.planningForm.volume + ' kg) berhasil dikirimkan ke Pemasok Mitra!');
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
                if (!keys.length) return '0.0';
                var sum = 0;
                for (var i = 0; i < keys.length; i++) {
                    sum += Number(this.supplierScores[keys[i]].final_score || 0);
                }
                return (sum / keys.length).toFixed(1);
            },

            get monitoringAvgStar() {
                var keys = Object.keys(this.supplierScores);
                if (!keys.length) return '0.0';
                var sum = 0;
                for (var i = 0; i < keys.length; i++) {
                    sum += Number(this.supplierScores[keys[i]].star_rating || 0);
                }
                return (sum / keys.length).toFixed(1);
            },

            get monitoringAvgOtd() {
                var keys = Object.keys(this.supplierScores);
                if (!keys.length) return '0.0';
                var sum = 0;
                for (var i = 0; i < keys.length; i++) {
                    sum += Number(this.supplierScores[keys[i]].otd_rate || 0);
                }
                return (sum / keys.length).toFixed(1);
            },

            get monitoringAvgFill() {
                var keys = Object.keys(this.supplierScores);
                if (!keys.length) return '0.0';
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

                        self.notify('Skor ' + (d.company_name || 'pemasok') + ' berhasil dikalkulasi ulang secara realtime!');

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

            triggerPrint: function(docTitle, contentHtml) {
                var printWindow = window.open('', '_blank', 'width=900,height=900,menubar=no,toolbar=no,location=no,status=no');
                if (!printWindow) {
                    alert('Mohon izinkan pop-up pada browser Anda untuk mencetak atau menyimpan invoice.');
                    return;
                }

                var topToolbar = '<div class="no-print-bar" style="background:#f8fafc;border:1px solid #e2e8f0;padding:12px 20px;margin-bottom:24px;border-radius:12px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 1px 3px rgba(0,0,0,0.05);font-family:sans-serif;">'
                    + '<div style="font-size:13px;color:#334155;font-weight:700;">Pratinjau Faktur Invoice &bull; ' + docTitle + '</div>'
                    + '<div style="display:flex;gap:8px;">'
                    + '<button type="button" onclick="window.print()" style="background:#ea580c;color:#fff;border:none;padding:9px 20px;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px;">Cetak / Simpan PDF</button>'
                    + '<button type="button" onclick="window.close()" style="background:#fff;color:#475569;border:1px solid #cbd5e1;padding:9px 18px;border-radius:8px;font-weight:600;cursor:pointer;font-size:13px;">Tutup</button>'
                    + '</div></div>';

                var printStyle = '<' + 'style>'
                    + '@page { size: A4 portrait; margin: 12mm 10mm; }'
                    + '* { box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; }'
                    + 'body { margin: 0; padding: 24px; background: #ffffff !important; color: #0f172a !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }'
                    + '@media print { .no-print-bar { display: none !important; } body { padding: 0 !important; } }'
                    + '<' + '/style>';

                var fullDoc = '<' + '!DOCTYPE html><' + 'html lang="id"><' + 'head>'
                    + '<' + 'meta charset="utf-8">'
                    + '<' + 'meta name="viewport" content="width=device-width, initial-scale=1.0">'
                    + '<' + 'title>' + docTitle + '<' + '/title>'
                    + printStyle
                    + '<' + '/head><' + 'body>'
                    + topToolbar
                    + contentHtml
                    + '<' + '/body><' + '/html>';

                printWindow.document.open();
                printWindow.document.write(fullDoc);
                printWindow.document.close();
                printWindow.focus();

                setTimeout(function() {
                    try { printWindow.print(); } catch (e) {}
                }, 400);
            },

            printUmkmBatchInvoice: function(inv) {
                const invoiceHtml = `
                    <div style="max-width:850px; margin:0 auto; padding:20px; color:#0f172a; background:#ffffff;">
                        <table style="width:100%; border-collapse:collapse; margin-bottom:24px; border-bottom:2px solid #0f172a; padding-bottom:14px;">
                            <tr>
                                <td style="vertical-align:top; width:58%;">
                                    <div style="font-size:26px; font-weight:800; color:#ea580c; letter-spacing:1px; line-height:1;">
                                        SORAI <span style="font-size:11px; font-weight:700; color:#64748b; letter-spacing:1.5px; border-left:1px solid #cbd5e1; padding-left:8px;">PORTAL UMKM B2B</span>
                                    </div>
                                    <div style="margin-top:10px; font-size:14px; font-weight:700; color:#0f172a;">${inv.umkmName}</div>
                                    <div style="font-size:11px; color:#475569;">Penanggung Jawab: ${inv.umkmOwner} | Telp: ${inv.umkmPhone}</div>
                                    <div style="font-size:11px; color:#475569; margin-top:2px;">Alamat Dapur: ${inv.umkmAddress}</div>
                                </td>
                                <td style="vertical-align:top; text-align:right; width:42%;">
                                    <div style="font-size:24px; font-weight:800; color:#0f172a; letter-spacing:1.5px; line-height:1;">BUKTI PEMBAYARAN</div>
                                    <div style="font-size:10px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:1px; margin-top:3px;">FAKTUR RESMI BATCH INVOICE</div>
                                    <table style="font-size:11px; margin-top:10px; margin-left:auto; text-align:left; border-collapse:collapse; font-variant-numeric:tabular-nums;">
                                        <tr>
                                            <td style="padding:2px 8px; color:#64748b;">No. Faktur</td>
                                            <td style="padding:2px 8px; font-weight:700; color:#ea580c;">: ${inv.invoiceNumber}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:2px 8px; color:#64748b;">Ref. Pesanan</td>
                                            <td style="padding:2px 8px; font-weight:700;">: ${inv.orderCode}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:2px 8px; color:#64748b;">Tanggal Lunas</td>
                                            <td style="padding:2px 8px; font-weight:600;">: ${inv.paidAt}</td>
                                        </tr>
                                    </table>
                                    <div style="margin-top:10px;">
                                        <span style="display:inline-block; border:2px solid #059669; color:#059669; background:#ecfdf5; font-weight:800; font-size:11px; padding:4px 14px; border-radius:4px; text-transform:uppercase; letter-spacing:1.2px;">
                                            LUNAS / TERVERIFIKASI
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <table style="width:100%; border-collapse:collapse; margin-bottom:20px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;">
                            <tr>
                                <td style="padding:14px; width:50%; vertical-align:top; border-right:1px solid #e2e8f0;">
                                    <div style="font-size:10px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:1px; margin-bottom:4px;">DITERBITKAN OLEH (MITRA PEMASOK):</div>
                                    <div style="font-size:14px; font-weight:700; color:#0f172a;">${inv.supplierName}</div>
                                    <div style="font-size:11px; color:#475569; margin-top:2px;">Kontak Supplier: <strong>${inv.supplierPhone}</strong></div>
                                    <div style="font-size:11px; color:#475569; margin-top:2px;">Gudang / Distribusi: ${inv.supplierAddress}</div>
                                </td>
                                <td style="padding:14px; width:50%; vertical-align:top;">
                                    <div style="font-size:10px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:1px; margin-bottom:4px;">INFORMASI PENYELESAIAN TRANSAKSI:</div>
                                    <div style="font-size:11px; color:#475569; line-height:1.6;">
                                        <div>Metode Bayar: <strong>${inv.paymentMethod}</strong></div>
                                        <div>Nomor Referensi: <strong>${inv.refNumber}</strong></div>
                                        <div>Jaminan Transaksi: <strong>Escrow Resmi Sorai B2B</strong></div>
                                        <div>Tahapan Pengiriman: <strong>${inv.batchName}</strong></div>
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <table style="width:100%; border-collapse:collapse; margin-bottom:20px; font-size:11px;">
                            <thead>
                                <tr style="background:#0f172a; color:#ffffff;">
                                    <th style="padding:9px 12px; text-align:center; width:35px; border:1px solid #0f172a;">NO</th>
                                    <th style="padding:9px 12px; text-align:left; border:1px solid #0f172a;">DESKRIPSI BAHAN BAKU / KOMODITAS</th>
                                    <th style="padding:9px 12px; text-align:center; width:110px; border:1px solid #0f172a;">KLOTER</th>
                                    <th style="padding:9px 12px; text-align:center; width:100px; border:1px solid #0f172a;">VOLUME</th>
                                    <th style="padding:9px 12px; text-align:right; width:140px; border:1px solid #0f172a;">TOTAL LUNAS (IDR)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="padding:12px; text-align:center; border:1px solid #e2e8f0; vertical-align:top;">1</td>
                                    <td style="padding:12px; border:1px solid #e2e8f0; vertical-align:top;">
                                        <div style="font-weight:700; font-size:12px; color:#0f172a;">${inv.commodityName}</div>
                                        <div style="font-size:10px; color:#64748b; margin-top:3px;">Pasokan bahan baku berkualitas sesuai penerimaan fisik dapur.</div>
                                    </td>
                                    <td style="padding:12px; text-align:center; border:1px solid #e2e8f0; vertical-align:top;">
                                        <span style="background:#f1f5f9; padding:2px 8px; border-radius:4px; font-weight:700;">${inv.batchName}</span>
                                    </td>
                                    <td style="padding:12px; text-align:center; font-weight:700; font-variant-numeric:tabular-nums; border:1px solid #e2e8f0; vertical-align:top;">
                                        ${inv.quantity}
                                    </td>
                                    <td style="padding:12px; text-align:right; font-weight:700; font-size:13px; font-variant-numeric:tabular-nums; border:1px solid #e2e8f0; vertical-align:top;">
                                        ${inv.amount}
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <table style="width:100%; border-collapse:collapse; margin-bottom:25px;">
                            <tr>
                                <td style="width:55%; vertical-align:top; padding-right:20px;">
                                    <div style="font-size:10px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:4px;">STATUS KEABSAHAN TRANSAKSI:</div>
                                    <div style="padding:10px 12px; background:#f0fdf4; border-left:3px solid #16a34a; font-size:11px; color:#166534; line-height:1.4; border-radius:2px;">
                                        Tagihan kloter ini telah terverifikasi lunas secara otomatis melalui gateway pembayaran resmi. Dana diteruskan ke pemasok dengan aman.
                                    </div>
                                    <div style="font-size:10px; color:#64748b; margin-top:8px; line-height:1.4;">
                                        <em>*Dokumen ini diterbitkan secara sah oleh sistem Sorai B2B Procurement dan dapat digunakan sebagai bukti pembukuan / arsip pengeluaran resmi UMKM.</em>
                                    </div>
                                </td>
                                <td style="width:45%; vertical-align:top;">
                                    <table style="width:100%; font-size:11px; border-collapse:collapse; font-variant-numeric:tabular-nums;">
                                        <tr>
                                            <td style="padding:6px 8px; color:#475569;">Subtotal Batch</td>
                                            <td style="padding:6px 8px; text-align:right; font-weight:700;">${inv.amount}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:6px 8px; color:#475569;">PPN Pangan (0%)</td>
                                            <td style="padding:6px 8px; text-align:right; color:#64748b;">Rp 0</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:6px 8px; color:#475569;">Biaya Layanan Platform</td>
                                            <td style="padding:6px 8px; text-align:right; color:#059669; font-weight:700;">Rp 0 (Gratis)</td>
                                        </tr>
                                        <tr style="border-top:2px solid #0f172a; border-bottom:2px solid #0f172a; background:#f8fafc;">
                                            <td style="padding:10px 8px; font-size:12px; font-weight:800; color:#0f172a;">TOTAL DIBAYAR:</td>
                                            <td style="padding:10px 8px; text-align:right; font-size:16px; font-weight:800; color:#ea580c;">${inv.amount}</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <table style="width:100%; border-collapse:collapse; margin-top:35px; padding-top:20px; border-top:1px dashed #cbd5e1; text-align:center; font-size:11px;">
                            <tr>
                                <td style="width:50%; vertical-align:bottom; padding-bottom:5px;">
                                    <div style="color:#64748b; font-size:10px; text-transform:uppercase; font-weight:700;">Dapur Pemesan / Mitra UMKM</div>
                                    <div style="height:60px;"></div>
                                    <div style="font-weight:700; border-top:1px solid #94a3b8; display:inline-block; min-width:180px; padding-top:4px;">${inv.umkmOwner}</div>
                                    <div style="color:#64748b; font-size:10px;">${inv.umkmName}</div>
                                </td>
                                <td style="width:50%; vertical-align:bottom; padding-bottom:5px;">
                                    <div style="color:#64748b; font-size:10px; text-transform:uppercase; font-weight:700;">Mitra Pemasok Terverifikasi</div>
                                    <div style="height:60px;"></div>
                                    <div style="font-weight:700; border-top:1px solid #94a3b8; display:inline-block; min-width:180px; padding-top:4px;">${inv.supplierName}</div>
                                    <div style="color:#64748b; font-size:10px;">Distributor Resmi Sorai</div>
                                </td>
                            </tr>
                        </table>
                    </div>
                `;
                this.triggerPrint("Faktur_Invoice_" + inv.invoiceNumber.replace(/[^a-zA-Z0-9]/g, '_'), invoiceHtml);
            },

            printUmkmOrderInvoice: function(ord) {
                const totalFormatted = 'Rp ' + Number(ord.total || 0).toLocaleString('id-ID');
                const isPaid = ord.paymentStatus === 'PAID';
                const invoiceNum = ord.invoiceNumber || ('INV-' + ord.id);
                const orderDate = ord.date || new Date().toLocaleDateString('id-ID');

                const invoiceHtml = `
                    <div style="max-width:850px; margin:0 auto; padding:20px; color:#0f172a; background:#ffffff;">
                        <table style="width:100%; border-collapse:collapse; margin-bottom:24px; border-bottom:2px solid #0f172a; padding-bottom:14px;">
                            <tr>
                                <td style="vertical-align:top; width:58%;">
                                    <div style="font-size:26px; font-weight:800; color:#ea580c; letter-spacing:1px; line-height:1;">
                                        SORAI <span style="font-size:11px; font-weight:700; color:#64748b; letter-spacing:1.5px; border-left:1px solid #cbd5e1; padding-left:8px;">FAKTUR PENGADAAN DAPUR</span>
                                    </div>
                                    <div style="margin-top:10px; font-size:14px; font-weight:700; color:#0f172a;">Dapur UMKM Pemesan</div>
                                    <div style="font-size:11px; color:#475569;">Pemasok Mitra: <strong>${ord.supplierName}</strong></div>
                                    <div style="font-size:11px; color:#475569; margin-top:2px;">Alamat Pemasok: ${ord.supplierAddress || 'Malang Raya'} | Telp: ${ord.supplierPhone || '-'}</div>
                                </td>
                                <td style="vertical-align:top; text-align:right; width:42%;">
                                    <div style="font-size:24px; font-weight:800; color:#0f172a; letter-spacing:1.5px; line-height:1;">FAKTUR TRANSAKSI</div>
                                    <div style="font-size:10px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:1px; margin-top:3px;">ARSIP KESELURUHAN PENGADAAN</div>
                                    <table style="font-size:11px; margin-top:10px; margin-left:auto; text-align:left; border-collapse:collapse; font-variant-numeric:tabular-nums;">
                                        <tr>
                                            <td style="padding:2px 8px; color:#64748b;">No. Transaksi</td>
                                            <td style="padding:2px 8px; font-weight:700; color:#ea580c;">: ${ord.id}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:2px 8px; color:#64748b;">No. Faktur</td>
                                            <td style="padding:2px 8px; font-weight:700;">: ${invoiceNum}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:2px 8px; color:#64748b;">Tanggal Transaksi</td>
                                            <td style="padding:2px 8px; font-weight:600;">: ${orderDate}</td>
                                        </tr>
                                    </table>
                                    <div style="margin-top:10px;">
                                        ${isPaid
                                            ? '<span style="display:inline-block; border:2px solid #059669; color:#059669; background:#ecfdf5; font-weight:800; font-size:11px; padding:4px 14px; border-radius:4px; text-transform:uppercase; letter-spacing:1.2px;">LUNAS / PAID</span>'
                                            : '<span style="display:inline-block; border:2px solid #d97706; color:#d97706; background:#fffbeb; font-weight:800; font-size:11px; padding:4px 14px; border-radius:4px; text-transform:uppercase; letter-spacing:1.2px;">MENUNGGU PEMBAYARAN</span>'
                                        }
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <table style="width:100%; border-collapse:collapse; margin-bottom:20px; font-size:11px;">
                            <thead>
                                <tr style="background:#0f172a; color:#ffffff;">
                                    <th style="padding:9px 12px; text-align:center; width:35px; border:1px solid #0f172a;">NO</th>
                                    <th style="padding:9px 12px; text-align:left; border:1px solid #0f172a;">RINCIAN KOMODITAS BAHAN BAKU</th>
                                    <th style="padding:9px 12px; text-align:center; width:120px; border:1px solid #0f172a;">VOLUME TOTAL</th>
                                    <th style="padding:9px 12px; text-align:center; width:120px; border:1px solid #0f172a;">STATUS BAYAR</th>
                                    <th style="padding:9px 12px; text-align:right; width:150px; border:1px solid #0f172a;">TOTAL NILAI TRANSAKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="padding:12px; text-align:center; border:1px solid #e2e8f0; vertical-align:top;">1</td>
                                    <td style="padding:12px; border:1px solid #e2e8f0; vertical-align:top;">
                                        <div style="font-weight:700; font-size:12px; color:#0f172a;">${ord.commodity}</div>
                                        <div style="font-size:10px; color:#64748b; margin-top:3px;">Pemasok: ${ord.supplierName} &bull; Pengiriman langsung ke dapur operasional.</div>
                                        ${ord.rating ? `<div style="margin-top:5px; font-size:10px; color:#d97706; font-weight:700;">Rating Dapur: ${ord.rating}.0 / 5</div>` : ''}
                                    </td>
                                    <td style="padding:12px; text-align:center; font-weight:700; font-variant-numeric:tabular-nums; border:1px solid #e2e8f0; vertical-align:top;">
                                        ${ord.approvedQty} ${ord.unit}
                                    </td>
                                    <td style="padding:12px; text-align:center; border:1px solid #e2e8f0; vertical-align:top;">
                                        <span style="display:inline-block; padding:2px 8px; border-radius:4px; font-weight:700; ${isPaid ? 'color:#059669; background:#ecfdf5;' : 'color:#d97706; background:#fffbeb;'}">
                                            ${ord.paymentStatus}
                                        </span>
                                    </td>
                                    <td style="padding:12px; text-align:right; font-weight:700; font-size:13px; font-variant-numeric:tabular-nums; border:1px solid #e2e8f0; vertical-align:top;">
                                        ${totalFormatted}
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <table style="width:100%; border-collapse:collapse; margin-bottom:25px;">
                            <tr>
                                <td style="width:55%; vertical-align:top; padding-right:20px;">
                                    <div style="font-size:10px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:4px;">CATATAN TRANSAKSI:</div>
                                    <div style="padding:10px 12px; background:#f8fafc; border-left:3px solid #ea580c; font-size:11px; color:#334155; line-height:1.4; border-radius:2px;">
                                        Faktur pengadaan ini merupakan ringkasan resmi transaksi pemesanan bahan baku dapur yang dikelola melalui ekosistem B2B SORAI.
                                    </div>
                                    <div style="font-size:10px; color:#64748b; margin-top:8px; line-height:1.4;">
                                        <em>*Validasi digital otomatis. Tidak membutuhkan stempel basah.</em>
                                    </div>
                                </td>
                                <td style="width:45%; vertical-align:top;">
                                    <table style="width:100%; font-size:11px; border-collapse:collapse; font-variant-numeric:tabular-nums;">
                                        <tr>
                                            <td style="padding:6px 8px; color:#475569;">Subtotal Transaksi</td>
                                            <td style="padding:6px 8px; text-align:right; font-weight:700;">${totalFormatted}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:6px 8px; color:#475569;">PPN Pangan 0%</td>
                                            <td style="padding:6px 8px; text-align:right; color:#64748b;">Rp 0</td>
                                        </tr>
                                        <tr style="border-top:2px solid #0f172a; border-bottom:2px solid #0f172a; background:#f8fafc;">
                                            <td style="padding:10px 8px; font-size:12px; font-weight:800; color:#0f172a;">TOTAL TAGIHAN:</td>
                                            <td style="padding:10px 8px; text-align:right; font-size:16px; font-weight:800; color:#ea580c;">${totalFormatted}</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <table style="width:100%; border-collapse:collapse; margin-top:30px; text-align:center; font-size:11px; border-top:1px dashed #cbd5e1; padding-top:20px;">
                            <tr>
                                <td style="width:50%; vertical-align:bottom; padding-bottom:5px;">
                                    <div style="color:#64748b; font-size:10px; text-transform:uppercase; font-weight:700;">Pengelola Dapur UMKM</div>
                                    <div style="height:55px;"></div>
                                    <div style="font-weight:700; border-top:1px solid #94a3b8; display:inline-block; min-width:180px; padding-top:4px;">Dapur UMKM</div>
                                    <div style="color:#64748b; font-size:10px;">Pembeli Terverifikasi</div>
                                </td>
                                <td style="width:50%; vertical-align:bottom; padding-bottom:5px;">
                                    <div style="color:#64748b; font-size:10px; text-transform:uppercase; font-weight:700;">Pemasok Terdaftar</div>
                                    <div style="height:55px;"></div>
                                    <div style="font-weight:700; border-top:1px solid #94a3b8; display:inline-block; min-width:180px; padding-top:4px;">${ord.supplierName}</div>
                                    <div style="color:#64748b; font-size:10px;">Mitra Suplai B2B Sorai</div>
                                </td>
                            </tr>
                        </table>
                    </div>
                `;
                this.triggerPrint("Faktur_Transaksi_" + ord.id, invoiceHtml);
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
    <!-- TAB: RIWAYAT PENGAJUAN PERMINTAAN (< 30 MENIT EDIT)       -->
    <!-- ========================================================= -->
    <div {{ $serverActiveTab === 'riwayat-permintaan' ? '' : 'x-cloak style=display:none;' }} x-show="activeTab === 'riwayat-permintaan'" class="space-y-6">
    <div x-cloak x-show="activeTab === 'riwayat-permintaan'" class="space-y-6">

        <!-- HEADER SECTION -->
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 border-b border-slate-200/80 pb-4">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Riwayat Pengajuan Permintaan</h2>
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-orange-50 border border-orange-200/80 text-[#ea580c] tabular-nums" x-text="partnershipRequests.length + ' Pengajuan'"></span>
                </div>
                <p class="text-sm sm:text-[15px] text-slate-600 mt-1 leading-relaxed">
                    Pantau status pengajuan kemitraan pasokan bahan baku dapur Anda. Anda dapat mengubah rincian data dalam batas waktu <strong>&lt; 30 menit</strong> setelah pengajuan dikirim.
                </p>
            </div>
            <div class="shrink-0 self-start sm:self-end flex items-center gap-2">
                <button type="button"
                        @click="setTab('katalog')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#ea580c] hover:bg-[#c2410c] text-white text-xs font-semibold shadow-xs transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Ajukan Pasokan Baru</span>
                </button>
            </div>
        </div>

        <!-- INFO BANNER PERATURAN 30 MENIT -->
        <div class="p-4 rounded-2xl bg-amber-50/80 border border-amber-200/80 flex items-start gap-3.5 text-xs text-amber-950">
            <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="space-y-0.5 leading-relaxed">
                <p class="font-bold text-amber-900">Ketentuan Perubahan Data Pengajuan (&lt; 30 Menit)</p>
                <p class="text-[11px] text-amber-800">
                    Sistem memberikan fleksibilitas bagi dapur Anda untuk merevisi volume, jadwal tiba, atau spesifikasi mutu selama <strong>30 menit pertama</strong>. Setelah melewati 30 menit atau disetujui oleh pemasok, data pengajuan otomatis terkunci permanen untuk kepastian operasional armada pemasok.
                </p>
            </div>
        </div>

            </div>
        </div>

        <!-- INFO BANNER PERATURAN 30 MENIT -->
        <div class="p-4 rounded-2xl bg-amber-50/80 border border-amber-200/80 flex items-start gap-3.5 text-xs text-amber-950">
            <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="space-y-0.5 leading-relaxed">
                <p class="font-bold text-amber-900">Ketentuan Perubahan Data Pengajuan (&lt; 30 Menit)</p>
                <p class="text-[11px] text-amber-800">
                    Sistem memberikan fleksibilitas bagi dapur Anda untuk merevisi volume, jadwal tiba, atau spesifikasi mutu selama <strong>30 menit pertama</strong>. Setelah melewati 30 menit atau disetujui oleh pemasok, data pengajuan otomatis terkunci permanen untuk kepastian operasional armada pemasok.
                </p>
            </div>
        </div>

        <!-- DAFTAR RIWAYAT PERMINTAAN -->
        <div class="space-y-4">
            <!-- EMPTY STATE -->
            <template x-if="partnershipRequests.length === 0">
                <div class="bg-white rounded-2xl border border-slate-200/80 p-12 text-center space-y-4">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-orange-50 border border-orange-100 text-[#ea580c] flex items-center justify-center shadow-2xs">
                        <svg class="w-8 h-8 text-[#ea580c]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-base font-bold text-slate-900">Belum Ada Pengajuan Permintaan</h3>
                        <p class="text-xs text-slate-500 max-w-sm mx-auto">
                            Anda belum mengajukan kemitraan pasokan bahan baku. Silakan telusuri katalog komoditas untuk memulai pengajuan pasokan dapur Anda.
                        </p>
                    </div>
                    <button type="button"
                            @click="setTab('katalog')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#ea580c] hover:bg-[#c2410c] text-white text-xs font-semibold shadow-xs transition cursor-pointer">
                        <span>Buka Katalog Bahan Baku</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </div>
            </template>

            <!-- LIST OF CARDS -->
            <template x-for="item in partnershipRequests" :key="item.id">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs hover:border-slate-300 transition overflow-hidden">
                    <div class="p-5 sm:p-6 space-y-4">

                        <!-- CARD HEADER -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3.5">
                            <div class="flex items-start sm:items-center gap-3">
                                <template x-if="item.commodity_image">
                                    <img :src="item.commodity_image" :alt="item.commodity_name" class="w-12 h-12 rounded-xl object-cover border border-slate-200/80 shrink-0">
                                </template>
                                <template x-if="!item.commodity_image">
                                    <div class="w-12 h-12 rounded-xl bg-orange-50 border border-orange-100 text-[#ea580c] flex items-center justify-center shrink-0 font-bold text-sm">
                                        <span x-text="item.commodity_name ? item.commodity_name.charAt(0) : 'P'"></span>
                                    </div>
                                </template>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="font-bold text-base text-slate-900 tracking-tight" x-text="item.commodity_name"></h3>
                                        <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded-md bg-slate-100 text-slate-600" x-text="item.contract_code"></span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        Pemasok: <strong class="text-slate-800" x-text="item.supplier_name"></strong> &bull; Diajukan: <span x-text="item.created_at_human"></span>
                                    </p>
                                </div>
                            </div>

                            <!-- STATUS BADGE -->
                            <div class="flex items-center gap-2 shrink-0">
                                <template x-if="item.status === 'pending' || item.status === 'menunggu' || item.status === 'dalam_review'">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 border border-amber-200/80 text-amber-800 text-[11px] font-semibold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        <span>Menunggu Persetujuan Pemasok</span>
                                    </span>
                                </template>
                                <template x-if="item.status === 'active' || item.status === 'approved' || item.status === 'disetujui'">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-[11px] font-semibold">
                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        <span>Disetujui &amp; Aktif</span>
                                    </span>
                                </template>
                                <template x-if="item.status === 'rejected' || item.status === 'ditolak'">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-50 border border-rose-200 text-rose-800 text-[11px] font-semibold">
                                        <span>Ditolak</span>
                                    </span>
                                </template>
                                <template x-if="item.status === 'cancelled' || item.status === 'dibatalkan'">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 border border-slate-200 text-slate-700 text-[11px] font-semibold">
                                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        <span>Dibatalkan oleh UMKM</span>
                                    </span>
                                </template>
                            </div>
                        </div>

                        <!-- DETAILS GRID -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs tabular-nums bg-slate-50/70 p-3.5 rounded-xl border border-slate-100">
                            <div>
                                <span class="text-[10px] uppercase font-semibold text-slate-400 block tracking-wider">Volume per Kirim</span>
                                <span class="font-bold text-slate-900 text-sm mt-0.5 block" x-text="item.volume_per_delivery + ' ' + item.unit"></span>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-semibold text-slate-400 block tracking-wider">Pola Frekuensi</span>
                                <span class="font-semibold text-slate-800 text-xs mt-0.5 block" x-text="item.frequency_type"></span>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-semibold text-slate-400 block tracking-wider">Jam Tiba Dapur</span>
                                <span class="font-semibold text-slate-800 text-xs mt-0.5 block" x-text="item.delivery_time + ' WIB'"></span>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-semibold text-slate-400 block tracking-wider">Mulai Kirim Perdana</span>
                                <span class="font-semibold text-slate-800 text-xs mt-0.5 block" x-text="item.start_date_formatted"></span>
                            </div>
                        </div>

                        <!-- KETENTUAN MUTU & CATATAN KHUSUS -->
                        <div class="space-y-2 text-xs">
                            <template x-if="item.quality_terms && item.quality_terms.length > 0">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="text-[11px] text-slate-400 font-semibold mr-1">Aturan Mutu:</span>
                                    <template x-for="qt in item.quality_terms" :key="qt">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-white border border-slate-200 text-slate-700 text-[10px] font-medium" x-text="qt"></span>
                                    </template>
                                </div>
                            </template>

                            <template x-if="item.clean_notes">
                                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-slate-600 text-[11px]">
                                    <span class="font-bold text-slate-700">Catatan Dapur:</span>
                                    <span x-text="item.clean_notes"></span>
                                </div>
                            </template>
                        </div>

                        <!-- CARD FOOTER (SLA TIMER & TOMBOL UBAH) -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 border-t border-slate-100">
                            <!-- Left SLA Indicator -->
                            <div>
                                <template x-if="item.can_edit">
                                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs font-medium">
                                        <span class="relative flex h-2 w-2">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-600"></span>
                                        </span>
                                        <span>Dapat diubah bebas &bull; Sisa waktu: <strong class="text-emerald-800 font-bold" x-text="item.remaining_minutes + ' menit'"></strong></span>
                                    </div>
                                </template>

                                <template x-if="!item.can_edit">
                                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-500 text-xs font-medium">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        <span>Data Terkunci (&gt; 30 menit sejak pengajuan)</span>
                                    </div>
                                </template>
                            </div>

                            <!-- Right Action Buttons -->
                            <div class="flex items-center gap-2">
                                <template x-if="item.can_edit">
                                    <div class="flex items-center gap-2">
                                        <button type="button"
                                                @click="openEditPermintaanModal(item)"
                                                class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-orange-50 border border-orange-300 text-[#ea580c] hover:border-[#ea580c] font-bold text-xs rounded-xl shadow-2xs transition cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            <span>Ubah Data</span>
                                        </button>

                                        <button type="button"
                                                @click="openBatalPermintaanModal(item)"
                                                class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-rose-50 border border-rose-300 text-rose-600 hover:border-rose-500 font-bold text-xs rounded-xl shadow-2xs transition cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            <span>Batalkan Pengajuan</span>
                                        </button>
                                    </div>
                                </template>

                                <template x-if="!item.can_edit">
                                    <button type="button"
                                            disabled
                                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-50 border border-slate-200 text-slate-400 font-semibold text-xs rounded-xl cursor-not-allowed">
                                        <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        <span>Terkunci</span>
                                    </button>
                                </template>
                            </div>
                        </div>

                    </div>
                </div>
            </template>
        </div>

    </div>

    <!-- ========================================================= -->
    <!-- TAB 1: KATALOG BAHAN BAKU                                -->
    <!-- ========================================================= -->
    <div {{ $serverActiveTab === 'katalog' ? '' : 'x-cloak style=display:none;' }} x-show="activeTab === 'katalog'" class="space-y-6">
    <div x-show="activeTab === 'katalog'" class="space-y-6">

        <!-- 1. HERO BANNER: PASOKAN BAHAN BAKU SEGAR LANGSUNG DARI PETANI LOKAL -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-[#ea580c] via-[#f97316] to-[#f59e0b] text-white p-6 sm:p-8 lg:p-8 shadow-xl border border-orange-400/30">
            <!-- Ambient glowing background lights -->
            <div class="absolute -right-16 -top-16 w-80 h-80 bg-amber-300/30 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute left-1/3 -bottom-20 w-64 h-64 bg-orange-400/25 rounded-full blur-2xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col lg:flex-row items-center justify-between gap-6 sm:gap-8">
                <!-- Sisi Kiri: Informasi Bahan Baku Segar, Headline & Call to Action -->
                <div class="space-y-3.5 max-w-xl w-full">
                    <!-- Badge Pusat Pasokan Bahan Baku Segar -->
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-gradient-to-r from-amber-200 to-amber-100 text-amber-950 font-extrabold text-[11px] shadow-sm uppercase tracking-wider border border-amber-300/80">
                        <svg class="w-3.5 h-3.5 text-amber-800 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <span>Pusat Pasokan Bahan Baku Dapur UMKM</span>
                    </div>

                    <!-- Headline & Deskripsi Non-Diskon Sesuai Kebutuhan Bisnis Kuliner -->
                    <div>
                        <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black tracking-tight text-white leading-tight drop-shadow-xs">
                            Bahan Baku Segar Langsung dari Petani Lokal
                        </h1>
                        <h2 class="text-xs sm:text-sm lg:text-base font-semibold text-amber-100 mt-2 leading-relaxed">
                            Pasokan terpercaya daging ayam karkas, cabai, bumbu rempah &amp; sembako segar berkualitas untuk menjaga kelancaran usaha kuliner Anda setiap hari.
                        </h2>
                    </div>

                    <!-- Poin Keunggulan Pasokan (Clean SVG Icons, Tanpa Emot) -->
                    <div class="flex flex-wrap items-center gap-2 pt-1 text-xs">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/20 backdrop-blur-md text-white font-semibold border border-white/25 text-[11px] shadow-2xs">
                            <svg class="w-3.5 h-3.5 text-amber-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            <span>Komoditas Segar Pilihan</span>
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/20 backdrop-blur-md text-white font-semibold border border-white/25 text-[11px] shadow-2xs">
                            <svg class="w-3.5 h-3.5 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Halal &amp; Higienis Terverifikasi</span>
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/20 backdrop-blur-md text-white font-semibold border border-white/25 text-[11px] shadow-2xs">
                            <svg class="w-3.5 h-3.5 text-sky-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <span>Proteksi Rekening Bersama Escrow</span>
                        </span>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap items-center gap-2.5 pt-2">
                        <button type="button"
                                @click="document.getElementById('katalog-search-bar')?.scrollIntoView({ behavior: 'smooth' })"
                                class="px-5 py-2.5 rounded-xl bg-white hover:bg-amber-50 text-[#ea580c] text-xs font-black transition shadow-md hover:shadow-lg flex items-center justify-center gap-1.5 group/btn cursor-pointer">
                        <a href="#katalog-search-bar"
                           class="px-5 py-2.5 rounded-xl bg-white hover:bg-amber-50 text-[#ea580c] text-xs font-black transition shadow-md hover:shadow-lg flex items-center justify-center gap-1.5 group/btn cursor-pointer">
                            <span>Lihat Semua Bahan Baku</span>
                            <svg class="w-3.5 h-3.5 group-hover/btn:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </button>
                        </a>

                        <button type="button" 
                                @click="$dispatch('open-umkm-guide')" 
                                class="px-4 py-2.5 rounded-xl bg-white/15 hover:bg-white/25 text-white border border-white/25 backdrop-blur-md text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer shadow-xs">
                            <span class="w-4 h-4 rounded-full bg-white text-[#ea580c] flex items-center justify-center text-[10px] font-black">?</span>
                            <span>Cara Kerja Pasokan Dapur</span>
                        </button>

                        <template x-if="partnershipRequests.length > 0">
                            <button type="button" 
                                    @click="setTab('riwayat-permintaan')" 
                                    class="px-4 py-2.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-amber-950 text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer shadow-xs">
                                <span>Riwayat Permintaan (<span x-text="partnershipRequests.length"></span>)</span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Sisi Kanan: Showcase Gambar Bahan Baku Segar & Live Price Tags -->
                <div class="relative w-full lg:w-[46%] shrink-0">
                    <div class="relative rounded-2xl overflow-hidden shadow-2xl border-2 border-white/40 aspect-[16/10] bg-orange-700/40 group">
                        <img src="{{ asset('images/banner-fresh-food-hd.jpg') }}" 
                             alt="Bahan Baku Segar Langsung Petani" 
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">

                        <!-- Soft edge vignette for high contrast -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/15 to-transparent pointer-events-none"></div>

                        <!-- Live Price Tags Komoditas Tersedia (Tanpa Diskon, Tanpa Emot) -->
                        @if($products->isNotEmpty())
                            <div class="absolute inset-x-3 bottom-3 flex flex-col gap-1.5">
                                @foreach($products->take(2) as $sp)
                                    @php
                                        $pPrice = (float) ($sp->price ?? 0);
                                        $isSpDaging = str_contains(strtolower($sp->commodity?->category ?? ''), 'daging');
                                        $isSpBumbu = str_contains(strtolower($sp->commodity?->category ?? ''), 'bumbu') || str_contains(strtolower($sp->commodity?->category ?? ''), 'rempah');
                                    @endphp
                                    <div class="bg-white/95 backdrop-blur-md px-3.5 py-2 rounded-xl shadow-lg border border-white/80 flex items-center justify-between gap-2 transition hover:bg-white">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <div class="w-7 h-7 rounded-lg bg-orange-50 border border-orange-200/60 flex items-center justify-center shrink-0">
                                                @if($isSpDaging)
                                                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 2a4 4 0 00-4 4c0 1.95 1.4 3.58 3.25 3.93A7.502 7.502 0 004 17.5V20a1 1 0 001 1h14a1 1 0 001-1v-2.5a7.502 7.502 0 00-7.25-7.57A4.001 4.001 0 0012 2z"/>
                                                    </svg>
                                                @elseif($isSpBumbu)
                                                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343a7.975 7.975 0 012.344 5.657c0 2.12-.838 4.156-2.343 5.657z"/>
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                    </svg>
                                                @endif
                                            </div>
                                            <div class="min-w-0">
                                                <span class="font-bold text-slate-900 text-xs block truncate">{{ $sp->commodity?->name }}</span>
                                                <span class="text-[10px] text-slate-500 font-medium block truncate">Mitra: {{ $sp->supplier?->company_name }}</span>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2 shrink-0 tabular-nums">
                                            <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 text-[9px] font-bold px-2 py-0.5 rounded-md">
                                                Tersedia Harian
                                            </span>
                                            <div class="text-right">
                                                <span class="font-black text-[#ea580c] text-xs sm:text-sm block leading-tight">
                                                    Rp {{ number_format((float) $pPrice, 0, ',', '.') }}
                                                </span>
                                                <span class="text-[10px] text-slate-400 font-medium block leading-none">/{{ $sp->commodity?->unit }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Hidden anchor & Accessibility Title -->
        <div id="katalog-search-bar" class="sr-only">Katalog Bahan Baku</div>

        <!-- 2. QUICK CATEGORY FILTER PILLS (CHIPS DENGAN CLEAN SVG ICONS) -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs">
            <a href="{{ route('umkm.katalog.index', array_merge(request()->except(['category', 'page']), ['tab' => 'katalog', 'category' => 'Semua Kategori'])) }}"
               class="px-4 py-2.5 rounded-xl font-bold transition flex items-center gap-2 shrink-0 {{ empty($category) || $category === 'Semua Kategori' ? 'bg-[#ea580c] text-white shadow-xs' : 'bg-white hover:bg-slate-50 text-slate-700 border border-slate-200/90' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                <span>Semua Kategori</span>
            </a>
            @foreach($categories as $cat)
                @php
                    $isDaging = str_contains(strtolower($cat), 'daging');
                    $isBumbu = str_contains(strtolower($cat), 'bumbu') || str_contains(strtolower($cat), 'rempah');
                    $isSembako = str_contains(strtolower($cat), 'sembako');
                    $isSayuran = str_contains(strtolower($cat), 'sayur');
                    $isBuah = str_contains(strtolower($cat), 'buah');
                @endphp
                <a href="{{ route('umkm.katalog.index', array_merge(request()->except(['category', 'page']), ['tab' => 'katalog', 'category' => $cat])) }}"
                   class="px-4 py-2.5 rounded-xl font-bold transition flex items-center gap-2 shrink-0 {{ $category === $cat ? 'bg-[#ea580c] text-white shadow-xs' : 'bg-white hover:bg-slate-50 text-slate-700 border border-slate-200/90' }}">
                    @if($isDaging)
                        <svg class="w-4 h-4 shrink-0 {{ $category === $cat ? 'text-white' : 'text-rose-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 2a4 4 0 00-4 4c0 1.95 1.4 3.58 3.25 3.93A7.502 7.502 0 004 17.5V20a1 1 0 001 1h14a1 1 0 001-1v-2.5a7.502 7.502 0 00-7.25-7.57A4.001 4.001 0 0012 2z"/>
                        </svg>
                    @elseif($isBumbu)
                        <svg class="w-4 h-4 shrink-0 {{ $category === $cat ? 'text-white' : 'text-amber-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343a7.975 7.975 0 012.344 5.657c0 2.12-.838 4.156-2.343 5.657z"/>
                        </svg>
                    @elseif($isSembako)
                        <svg class="w-4 h-4 shrink-0 {{ $category === $cat ? 'text-white' : 'text-emerald-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    @elseif($isSayuran)
                        <svg class="w-4 h-4 shrink-0 {{ $category === $cat ? 'text-white' : 'text-emerald-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21V3m0 0c-4.418 0-8 3.582-8 8 0 2.21 1.79 4 4 4h4c2.21 0 4-1.79 4-4 0-4.418-3.582-8-8-8z"/>
                        </svg>
                    @else
                        <svg class="w-4 h-4 shrink-0 {{ $category === $cat ? 'text-white' : 'text-[#ea580c]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    @endif
                    <span>{{ $cat }}</span>
                </a>
            @endforeach
        </div>

        <!-- 3. CONTAINER SEARCH & ADVANCED FILTERS -->
        <div class="bg-white p-5 sm:p-6 rounded-3xl shadow-xs border border-slate-200/80 space-y-4">
            <form method="GET" action="{{ route('umkm.katalog.index') }}" @submit.prevent="filterCatalog()" class="space-y-4">
            <form method="GET" action="{{ route('umkm.katalog.index') }}" class="space-y-4">
                <input type="hidden" name="tab" value="katalog">
                @if($category && $category !== 'Semua Kategori')
                    <input type="hidden" name="category" value="{{ $category }}">
                @endif

                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 pointer-events-none">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </span>
                    <input 
                        type="text" 
                        name="search"
                        x-model="catalogSearch"
                        @input="filterCatalog()"
                        placeholder="Cari nama bahan baku (misal: Daging Ayam, Bawang Merah, Cabai) atau nama pemasok..."
                        class="w-full pl-10 pr-24 py-3 text-xs sm:text-sm font-medium bg-slate-50 border border-slate-200/90 rounded-2xl text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/15 focus:border-[#ea580c] transition"
                        value="{{ $search }}"
                        placeholder="Cari nama bahan baku (misal: Daging Ayam, Bawang Merah, Cabai) atau nama pemasok..."
                        class="w-full pl-10 pr-10 py-3 text-xs sm:text-sm font-medium bg-slate-50 border border-slate-200/90 rounded-2xl text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/15 focus:border-[#ea580c] transition"
                        @input.debounce.700ms="$event.target.form.submit()"
                        {{ request()->has('search') ? 'autofocus' : '' }}
                    />
                    <div class="absolute inset-y-0 right-0 pr-2 flex items-center gap-1">
                        <button type="button"
                                x-show="catalogSearch && catalogSearch.length > 0"
                                @click="clearSearch()"
                                class="p-1.5 text-slate-400 hover:text-slate-700 transition cursor-pointer"
                                title="Hapus pencarian">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                        <button type="button"
                                @click="filterCatalog()"
                                class="px-3.5 py-1.5 bg-slate-900 hover:bg-[#ea580c] text-white rounded-xl text-xs font-semibold transition-colors cursor-pointer shadow-2xs">
                            Cari
                        </button>
                    </div>
                    @if($search)
                        <a href="{{ route('umkm.katalog.index', array_merge(request()->except(['search', 'page']), ['tab' => 'katalog'])) }}"
                           class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-700 transition"
                           title="Hapus pencarian">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </a>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 pt-4 border-t border-slate-100 text-xs">
                    <div>
                        <label class="block text-slate-600 font-semibold mb-1.5 text-[11px]">Harga Maksimal (Rp)</label>
                        <input 
                            type="number" 
                            name="max_price"
                            placeholder="Contoh: 50000" 
                            value="{{ $maxPrice }}"
                            @input.debounce.700ms="$event.target.form.submit()"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200/90 text-slate-800 font-medium tabular-nums placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/15 focus:border-[#ea580c] transition"
                        />
                    </div>

                    <div>
                        <label class="block text-slate-600 font-semibold mb-1.5 text-[11px]">Ketersediaan Stok</label>
                        <select 
                            name="stock_status"
                            onchange="this.form.submit()"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200/90 text-slate-800 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/15 focus:border-[#ea580c] transition cursor-pointer">
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
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200/90 text-slate-800 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/15 focus:border-[#ea580c] transition cursor-pointer">
                            <option value="terbaru" {{ ($sort ?? 'terbaru') == 'terbaru' ? 'selected' : '' }}>Terbaru</option>
                            <option value="harga_terendah" {{ ($sort ?? '') == 'harga_terendah' ? 'selected' : '' }}>Harga Terendah</option>
                            <option value="harga_tertinggi" {{ ($sort ?? '') == 'harga_tertinggi' ? 'selected' : '' }}>Harga Tertinggi</option>
                            <option value="kapasitas_terbesar" {{ ($sort ?? '') == 'kapasitas_terbesar' ? 'selected' : '' }}>Kapasitas Terbesar</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-between sm:items-end sm:pb-0.5 gap-2">
                        <label class="flex items-center gap-2 cursor-pointer font-semibold text-slate-700 bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 rounded-xl hover:bg-slate-100/70 transition w-full justify-center">
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
                            <a href="{{ route('umkm.katalog.index', ['tab' => 'katalog']) }}" class="text-xs font-semibold text-rose-600 hover:text-rose-700 hover:underline whitespace-nowrap px-2">
                                Reset
                            </a>
                        @else
                            <button type="button" x-show="catalogSearch && catalogSearch.length > 0" @click="clearSearch()" class="text-xs font-semibold text-rose-600 hover:text-rose-700 hover:underline whitespace-nowrap px-2 cursor-pointer">
                                Reset
                            </button>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <!-- 4. HASIL PENCARIAN & STATISTIK KOMODITAS -->
        <div class="flex items-center justify-between text-xs text-slate-500 px-1">
            <div>
                Menampilkan <strong class="text-slate-900 font-bold" x-text="visibleProductCount">{{ method_exists($products, 'total') ? $products->total() : $products->count() }}</strong> pilihan bahan baku segar
                Menampilkan <strong class="text-slate-900 font-bold">{{ method_exists($products, 'total') ? $products->total() : $products->count() }}</strong> pilihan bahan baku segar
                @if($category && $category !== 'Semua Kategori')
                    pada kategori <span class="text-[#ea580c] font-bold">{{ $category }}</span>
                @endif
            </div>
            <div class="hidden sm:flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 text-[11px] text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full font-semibold border border-emerald-200/60">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    <span>Harga Grosir Terverifikasi</span>
                </span>
            </div>
        </div>

        <!-- 5. GRID KATALOG KOMODITAS BAHAN BAKU -->
        @if($products->isEmpty())
            <div class="bg-white rounded-3xl p-12 text-center shadow-xs border border-slate-200/80 space-y-4">
                <div class="w-16 h-16 mx-auto rounded-3xl bg-orange-50 border border-orange-100 flex items-center justify-center text-[#ea580c]">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <div class="space-y-1">
                    <h3 class="text-lg font-bold text-slate-900">Tidak Ada Komoditas Bahan Baku Ditemukan</h3>
                    <p class="text-xs text-slate-500 font-medium max-w-md mx-auto leading-relaxed">
                        Silakan sesuaikan kata kunci pencarian, ubah pilihan kategori, atau reset filter Anda untuk menemukan komoditas lainnya.
                    </p>
                </div>
                <div>
                    <a href="{{ route('umkm.katalog.index', ['tab' => 'katalog']) }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#ea580c] hover:bg-[#c2410c] text-white text-xs font-bold transition shadow-xs">
                        <span>Tampilkan Semua Komoditas</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>
            </div>
        @else
            <!-- Empty state untuk pencarian live di client jika tidak ada komoditas cocok -->
            <div x-cloak x-show="visibleProductCount === 0" class="bg-white rounded-3xl p-12 text-center shadow-xs border border-slate-200/80 space-y-4">
                <div class="w-16 h-16 mx-auto rounded-3xl bg-orange-50 border border-orange-100 flex items-center justify-center text-[#ea580c]">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <div class="space-y-1">
                    <h3 class="text-lg font-bold text-slate-900">Tidak Ada Komoditas Bahan Baku Ditemukan</h3>
                    <p class="text-xs text-slate-500 font-medium max-w-md mx-auto leading-relaxed">
                        Tidak ada bahan baku yang cocok dengan kata kunci "<span class="font-bold text-slate-800" x-text="catalogSearch"></span>". Silakan sesuaikan kata kunci atau bersihkan pencarian.
                    </p>
                </div>
                <div>
                    <button type="button" @click="clearSearch()" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#ea580c] hover:bg-[#c2410c] text-white text-xs font-bold transition shadow-xs cursor-pointer">
                        <span>Tampilkan Semua Komoditas</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </div>
            </div>

            <!-- Grid Kartu Bahan Baku -->
            <div x-show="visibleProductCount > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 w-full pt-1">
                @foreach($products as $product)
                    <div class="catalog-product-card bg-white rounded-3xl overflow-hidden flex flex-col justify-between shadow-xs hover:shadow-lg hover:border-orange-200 transition-all duration-300 border border-slate-200/80 group"
                         data-searchable="{{ strtolower(e(($product->commodity?->name ?? '') . ' ' . ($product->commodity?->category ?? '') . ' ' . ($product->supplier?->company_name ?? '') . ' ' . ($product->supplier?->delivery_area ?? '') . ' ' . ($product->quality_grade ?? '') . ' ' . ($product->description ?? ''))) }}">
                    <div class="bg-white rounded-3xl overflow-hidden flex flex-col justify-between shadow-xs hover:shadow-lg hover:border-orange-200 transition-all duration-300 border border-slate-200/80 group">
                        
                        <!-- Gambar Komoditas & Badges -->
                        <div class="relative h-48 w-full bg-slate-100 overflow-hidden flex items-center justify-center">
                            @if($product->display_image_url)
                                <img src="{{ $product->display_image_url }}" 
                                     alt="{{ $product->commodity->name }}" 
                                     class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-500">
                            @else
                                @php
                                    $fallbackGradient = match(strtolower($product->commodity->category)) {
                                        'daging & unggas' => 'from-rose-50 via-orange-50 to-amber-50 text-rose-500',
                                        'bumbu & rempah' => 'from-amber-50 via-orange-50 to-red-50 text-amber-600',
                                        'sembako' => 'from-emerald-50 via-teal-50 to-amber-50 text-emerald-600',
                                        default => 'from-orange-50 via-amber-50 to-slate-50 text-[#ea580c]'
                                    };
                                    $isCatDaging = str_contains(strtolower($product->commodity->category), 'daging');
                                    $isCatBumbu = str_contains(strtolower($product->commodity->category), 'bumbu') || str_contains(strtolower($product->commodity->category), 'rempah');
                                    $isCatSembako = str_contains(strtolower($product->commodity->category), 'sembako');
                                @endphp
                                <div class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br {{ $fallbackGradient }} p-4 text-center">
                                    <div class="w-12 h-12 rounded-2xl bg-white/85 backdrop-blur-xs flex items-center justify-center shadow-xs mb-2 text-[#ea580c]">
                                        @if($isCatDaging)
                                            <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 2a4 4 0 00-4 4c0 1.95 1.4 3.58 3.25 3.93A7.502 7.502 0 004 17.5V20a1 1 0 001 1h14a1 1 0 001-1v-2.5a7.502 7.502 0 00-7.25-7.57A4.001 4.001 0 0012 2z"/>
                                            </svg>
                                        @elseif($isCatBumbu)
                                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343a7.975 7.975 0 012.344 5.657c0 2.12-.838 4.156-2.343 5.657z"/>
                                            </svg>
                                        @elseif($isCatSembako)
                                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                        @else
                                            <svg class="w-6 h-6 text-[#ea580c]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                            </svg>
                                        @endif
                                    </div>
                                    <span class="text-xs font-bold text-slate-800 line-clamp-1">{{ $product->commodity->name }}</span>
                                    <span class="text-[10px] text-slate-500 font-medium">Komoditas Mitra Lokal</span>
                                </div>
                            @endif

                            <!-- Top-Left Badge: Applied or Category -->
                            @if(in_array($product->id, $appliedProductIds ?? []))
                                <span class="absolute top-3 left-3 bg-emerald-600/95 backdrop-blur-xs text-white text-[10px] font-bold px-2.5 py-1 rounded-lg shadow-sm inline-flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>Sudah Diajukan</span>
                                </span>
                            @else
                                <span class="absolute top-3 left-3 bg-white/95 backdrop-blur-md text-slate-800 border border-slate-200/70 text-[10px] font-bold px-2.5 py-1 rounded-lg shadow-2xs uppercase tracking-wide">
                                    {{ $product->commodity->category }}
                                </span>
                            @endif

                            <!-- Top-Right Badge: Stock Status -->
                            <div class="absolute top-3 right-3">
                                @if($product->stock_status === 'Tersedia')
                                    <span class="bg-emerald-500 text-white text-[10px] font-bold px-2.5 py-1 rounded-lg shadow-sm inline-flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                        <span>Tersedia</span>
                                    </span>
                                @elseif($product->stock_status === 'Stok Terbatas')
                                    <span class="bg-amber-500 text-white text-[10px] font-bold px-2.5 py-1 rounded-lg shadow-sm inline-flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                        <span>Terbatas</span>
                                    </span>
                                @elseif($product->stock_status === 'Pre-Order')
                                    <span class="bg-sky-500 text-white text-[10px] font-bold px-2.5 py-1 rounded-lg shadow-sm inline-flex items-center gap-1.5">
                                        <span>Pre-Order</span>
                                    </span>
                                @else
                                    <span class="bg-slate-700 text-white text-[10px] font-bold px-2.5 py-1 rounded-lg shadow-sm inline-flex items-center gap-1.5">
                                        <span>Habis</span>
                                    </span>
                                @endif
                            </div>

                            <!-- Bottom-Left Badge: Halal Certified -->
                            @if($product->is_halal_certified)
                                <span class="absolute bottom-2.5 left-3 bg-emerald-50/95 backdrop-blur-md text-emerald-800 border border-emerald-200 text-[10px] font-bold px-2 py-0.5 rounded-lg shadow-2xs inline-flex items-center gap-1">
                                    <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Halal</span>
                                </span>
                            @endif

                            <!-- Bottom-Right Badge: Quality Grade -->
                            @if(!empty($product->quality_grade))
                                <span class="absolute bottom-2.5 right-3 bg-white/95 backdrop-blur-md text-slate-800 border border-slate-200 text-[10px] font-bold px-2 py-0.5 rounded-lg shadow-2xs">
                                    {{ $product->quality_grade }}
                                </span>
                            @endif
                        </div>

                        <!-- Konten Kartu -->
                        <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                            <div class="space-y-2.5">
                                <h3 class="font-bold text-[15px] sm:text-base text-slate-900 group-hover:text-[#ea580c] transition leading-snug line-clamp-1">
                                    {{ $product->commodity->name }}
                                </h3>

                                <div class="space-y-1">
                                    <div class="flex items-center gap-1.5 text-xs font-semibold text-slate-700">
                                        <svg class="w-3.5 h-3.5 text-[#ea580c] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 0h4m-4 0H9m4 0V5" />
                                        </svg>
                                        <span class="truncate font-bold text-slate-800">{{ $product->supplier->company_name }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-[11px] text-slate-500">
                                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <span class="truncate">{{ $product->supplier->warehouse_address }}</span>
                                    </div>
                                </div>

                                <!-- SLA & Performa Pemasok -->
                                @php
                                    $perfScore = $product->supplier?->latestPerformanceScore;
                                @endphp
                                <div class="pt-2 flex items-center justify-between gap-2 text-[11px] border-t border-slate-100 tabular-nums">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        @if($perfScore && $perfScore->grade !== \App\Models\SupplierPerformanceScore::GRADE_UNRATED)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-bold border {{ $perfScore->getGradeBadgeColor() }}">
                                                Grade {{ $perfScore->grade }}
                                            </span>
                                            <span class="text-slate-800 font-bold inline-flex items-center gap-0.5 text-[11px]">
                                                <svg class="w-3 h-3 text-amber-500 fill-amber-500" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                {{ number_format((float) ($perfScore->star_rating ?? 0), 1) }}
                                            </span>
                                            <span class="text-slate-500 text-[10px] font-medium">
                                                {{ number_format((float) ($perfScore->metrics_breakdown['delivery']['otd_rate'] ?? 100), 0) }}% Tepat Waktu
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200/80">
                                                Vendor Terverifikasi
                                            </span>
                                        @endif
                                    </div>
                                    <button 
                                        type="button" 
                                        @click.stop="openSupplierTrackerModal('{{ $product->supplier->id }}')" 
                                        class="font-bold text-[#ea580c] hover:underline transition inline-flex items-center gap-0.5 cursor-pointer shrink-0 text-[11px]">
                                        <span>Rapor SLA</span>
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Harga & Kalkulasi Acuan Pemerintah -->
                            <div class="border-t border-slate-100 pt-3 space-y-2">
                                <div class="flex justify-between items-baseline gap-2">
                                    <span class="text-[11px] font-semibold text-slate-500">Harga Pasokan</span>
                                    <span class="font-extrabold text-lg text-[#ea580c] tabular-nums">
                                        Rp {{ number_format((float) ($product->price ?? 0), 0, ',', '.') }}<span class="text-xs text-slate-400 font-medium">/{{ $product->commodity->unit }}</span>
                                    </span>
                                </div>

                                @if($product->commodity->government_ref_price && (float) $product->price < (float) $product->commodity->government_ref_price)
                                    @php
                                        $diff = (float) $product->commodity->government_ref_price - (float) $product->price;
                                        $pct = round(($diff / (float) $product->commodity->government_ref_price) * 100);
                                    @endphp
                                    <div class="bg-emerald-50/80 border border-emerald-200/70 rounded-lg px-2.5 py-1 flex items-center justify-between text-[10px] font-bold text-emerald-800">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                                            <span>Hemat {{ $pct }}%</span>
                                        </span>
                                        <span class="text-emerald-700/80 font-medium">Acuan: Rp {{ number_format((float) ($product->commodity->government_ref_price ?? 0), 0, ',', '.') }}</span>
                                    </div>
                                @endif

                                <!-- Kapasitas Pasokan -->
                                <div class="flex items-center justify-between text-[11px] text-slate-500 pt-0.5">
                                    <span>Kapasitas Stok</span>
                                    <span class="font-semibold text-slate-800 tabular-nums">{{ number_format((float) ($product->stock_capacity ?? 0), 0, ',', '.') }} {{ $product->commodity->unit }}</span>
                                </div>

                                <!-- CTA Action Button -->
                                @php
                                    $isAlreadyApplied = in_array($product->id, $appliedProductIds ?? []);
                                @endphp

                                <div class="pt-1.5">
                                    @if($isAlreadyApplied)
                                        <button 
                                            type="button"
                                            @click="setTab('riwayat-permintaan')"
                                            class="w-full bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-800 font-bold py-2.5 px-3 rounded-xl text-xs transition flex items-center justify-center gap-1.5 cursor-pointer shadow-2xs">
                                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                            <span>Kemitraan Diajukan</span>
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
                                                 'price' => 'Rp ' . number_format((float) ($product->price ?? 0), 0, ',', '.') . ' / ' . $product->commodity->unit,
                                                 'numeric_price' => $product->price,
                                                 'stock_status' => $product->stock_status,
                                                 'stock_capacity' => $product->stock_capacity,
                                                 'is_halal' => $product->is_halal_certified,
                                                 'image_url' => $product->display_image_url,
                                             ]) }})" 
                                            class="w-full bg-[#ea580c] hover:bg-[#c2410c] active:scale-[0.98] text-white font-bold py-2.5 px-3 rounded-xl text-xs transition shadow-xs hover:shadow flex items-center justify-center gap-2 cursor-pointer group/btn">
                                            <span>Jadwalkan Pasokan</span>
                                            <svg class="w-3.5 h-3.5 group-hover/btn:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
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

                    </div>
                @endforeach
            </div>

2            <!-- 6. PAGINASI KATALOG -->
            <div x-show="visibleProductCount > 0 && (!catalogSearch || catalogSearch.trim() === '')" class="mt-8 flex justify-center">
            <!-- 6. TIPS & JAMINAN KEAMANAN DAPUR UMKM -->
            <div class="bg-gradient-to-r from-orange-50/80 via-amber-50/50 to-white rounded-3xl p-5 sm:p-6 border border-orange-200/70 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-2xs mt-4">
                <div class="flex items-start gap-3.5">
                    <div class="w-10 h-10 rounded-2xl bg-orange-100 text-[#ea580c] flex items-center justify-center shrink-0 mt-0.5 shadow-2xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm">Jaminan Proteksi Rekening Bersama (Escrow SORAI)</h4>
                        <p class="text-xs text-slate-600 mt-0.5 leading-relaxed max-w-2xl">
                            Setiap pembayaran faktur yang Anda lakukan aman terlindungi di rekening bersama SORAI. Dana baru dicairkan kepada pemasok setelah Anda memeriksa dan memvalidasi langsung penerimaan bahan baku segar di dapur Anda.
                        </p>
                    </div>
                </div>
                <button type="button" @click="$dispatch('open-umkm-guide')" class="px-4 py-2.5 bg-white hover:bg-orange-50 text-[#ea580c] border border-orange-200 font-bold text-xs rounded-xl transition shrink-0 cursor-pointer shadow-2xs">
                    Pelajari Alur Lengkap
                </button>
            </div>

            <!-- 7. PAGINASI KATALOG -->
            <div class="mt-8 flex justify-center">
                {{ $products->links() }}
            </div>
        @endif

    </div>

    <!-- ========================================================= -->
    <!-- TAB: COLLABORATIVE SUPPLY PLANNING (PROYEKSI PASOKAN)      -->
    <!-- ========================================================= -->
    <div {{ $serverActiveTab === 'supply-planning' ? '' : 'x-cloak style=display:none;' }} x-show="activeTab === 'supply-planning'" class="space-y-6">

        <!-- 1. ARCHITECTURAL HEADER -->
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 border-b border-slate-200/80 pb-4">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Perencanaan Pasokan</h2>
                <p class="text-sm sm:text-[15px] text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">Kelola proyeksi kebutuhan bahan baku dapur secara terencana bersama mitra.</p>
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
                                <input type="number" step="0.1" min="0.1" name="estimated_quantity" x-model="planningForm.volume" required placeholder="Masukkan kuantitas kebutuhan" class="w-full px-3.5 py-2.5 bg-transparent text-slate-900 font-semibold tabular-nums placeholder:text-slate-400 border-0 focus:outline-none" />
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
                        <textarea name="notes" x-model="planningForm.notes" rows="3" placeholder="Masukkan catatan spesifikasi kebutuhan pasokan (misal: suhu dingin 4°C, pengiriman subuh jam 05.00 WIB)..." class="w-full p-3.5 rounded-xl bg-slate-50 border border-slate-200/90 text-slate-800 font-medium placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/15 focus:border-[#ea580c] transition"></textarea>
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

                    <div class="space-y-3 text-xs">
                        <div class="flex items-center gap-3 p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="w-2 h-2 rounded-full bg-orange-500 shrink-0"></span>
                            <span class="font-semibold text-slate-800">Sinkronisasi Panen:</span>
                            <span class="text-slate-500">Kapasitas disiapkan sesuai kebutuhan.</span>
                        </div>
                        <div class="flex items-center gap-3 p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                            <span class="font-semibold text-slate-800">Stabilitas Harga:</span>
                            <span class="text-slate-500">Aman dari lonjakan harga pasar.</span>
                        </div>
                        <div class="flex items-center gap-3 p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="w-2 h-2 rounded-full bg-sky-500 shrink-0"></span>
                            <span class="font-semibold text-slate-800">Prioritas Pasokan:</span>
                            <span class="text-slate-500">Pengiriman sebelum dapur beroperasi.</span>
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
    <div {{ $serverActiveTab === 'pemesanan' ? '' : 'x-cloak style=display:none;' }} x-show="activeTab === 'pemesanan'" class="space-y-6">

        <!-- HEADER TITLE -->
        <div class="border-b border-slate-200/80 pb-4">
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Pesanan &amp; Pengiriman</h2>
            <p class="text-sm sm:text-[15px] text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">Pantau status pengiriman pesanan rutin, konfirmasi fisik kedatangan bahan baku, dan pelunasan aman.</p>
        </div>

        <!-- PANDUAN LANGKAH ALUR PENGADAAN DAPUR UNTUK UMKM -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 shadow-xs space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-orange-100 text-[#ea580c] flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    </span>
                    <span class="text-xs font-bold text-slate-900">Alur Pengadaan Bahan Baku Dapur:</span>
                </div>
                <span class="text-[11px] text-slate-400 hidden sm:inline">Klik tahapan di bawah untuk membuka langsung status pesanan Anda</span>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5 sm:gap-3 text-xs">
                <!-- Tahap 1: Pengiriman -->
                <button type="button" 
                        @click="orderSubTab = 'auto-order'"
                        class="p-3 rounded-xl border text-left transition cursor-pointer flex flex-col justify-between"
                        :class="orderSubTab === 'auto-order' ? 'bg-orange-50/70 border-orange-300 ring-1 ring-orange-400/30' : 'bg-slate-50/70 border-slate-200/90 hover:bg-slate-100/70'">
                    <div class="flex items-center justify-between gap-1 mb-1.5">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Langkah 1</span>
                        <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <p class="font-bold text-slate-900 text-xs">1. Jadwal Dikirim</p>
                    <p class="text-[10px] text-slate-500 mt-0.5 leading-snug">Pemasok siapkan armada &amp; jalan ke dapur</p>
                </button>

                <!-- Tahap 2: Cek Fisik Barang -->
                <button type="button" 
                        @click="orderSubTab = 'confirm'"
                        class="p-3 rounded-xl border text-left transition cursor-pointer flex flex-col justify-between relative"
                        :class="orderSubTab === 'confirm' ? 'bg-orange-50/70 border-orange-300 ring-1 ring-orange-400/30' : 'bg-slate-50/70 border-slate-200/90 hover:bg-slate-100/70'">
                    <div class="flex items-center justify-between gap-1 mb-1.5">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Langkah 2</span>
                        <template x-if="pendingValidationCount > 0">
                            <span class="inline-flex items-center gap-1 text-[10px] bg-[#ea580c] text-white px-1.5 py-0.2 rounded font-bold" x-text="pendingValidationCount + ' Perlu Cek'"></span>
                        </template>
                    </div>
                    <p class="font-bold text-slate-900 text-xs">2. Cek Fisik &amp; Terima</p>
                    <p class="text-[10px] text-slate-500 mt-0.5 leading-snug">Periksa timbangan &amp; foto bukti kedatangan</p>
                </button>

                <!-- Tahap 3: Bayar Invoice Aman -->
                <button type="button" 
                        @click="orderSubTab = 'payment'"
                        class="p-3 rounded-xl border text-left transition cursor-pointer flex flex-col justify-between"
                        :class="orderSubTab === 'payment' ? 'bg-orange-50/70 border-orange-300 ring-1 ring-orange-400/30' : 'bg-slate-50/70 border-slate-200/90 hover:bg-slate-100/70'">
                    <div class="flex items-center justify-between gap-1 mb-1.5">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Langkah 3</span>
                        <template x-if="pendingPaymentCount > 0">
                            <span class="inline-flex items-center gap-1 text-[10px] bg-amber-500 text-white px-1.5 py-0.2 rounded font-bold" x-text="pendingPaymentCount + ' Siap Bayar'"></span>
                        </template>
                    </div>
                    <p class="font-bold text-slate-900 text-xs">3. Bayar via Midtrans</p>
                    <p class="text-[10px] text-slate-500 mt-0.5 leading-snug">Dana aman di Rekening Bersama per kloter</p>
                </button>

                <!-- Tahap 4: Ulas Mutu -->
                <button type="button" 
                        @click="orderSubTab = 'rating'"
                        class="p-3 rounded-xl border text-left transition cursor-pointer flex flex-col justify-between"
                        :class="orderSubTab === 'rating' ? 'bg-orange-50/70 border-orange-300 ring-1 ring-orange-400/30' : 'bg-slate-50/70 border-slate-200/90 hover:bg-slate-100/70'">
                    <div class="flex items-center justify-between gap-1 mb-1.5">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Langkah 4</span>
                        <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    </div>
                    <p class="font-bold text-slate-900 text-xs">4. Nilai Mutu Pemasok</p>
                    <p class="text-[10px] text-slate-500 mt-0.5 leading-snug">Rating kesegaran komoditas &amp; ketepatan waktu</p>
                </button>
            </div>
        </div>

        <!-- SUB-NAVIGASI TAB CLEAN UNDERLINE DESIGN -->
        <div class="border-b border-slate-200/90 flex items-center gap-6 sm:gap-8 text-xs font-semibold overflow-x-auto">
            <!-- 1. Jadwal Pengiriman -->
            <button 
                type="button"
                @click="orderSubTab = 'auto-order'"
                class="pb-3.5 relative transition flex items-center gap-2 cursor-pointer whitespace-nowrap shrink-0"
                :class="orderSubTab === 'auto-order' ? 'text-slate-900 font-bold' : 'text-slate-500 hover:text-slate-900'">
                <span>1. Jadwal Pengiriman</span>
                <template x-if="orderSubTab === 'auto-order'">
                    <span class="absolute bottom-0 left-0 w-full h-0.5 bg-[#ea580c] rounded-full"></span>
                </template>
            </button>

            <!-- 2. Konfirmasi Terima Barang -->
            <button 
                type="button"
                @click="orderSubTab = 'confirm'"
                class="pb-3.5 relative transition flex items-center gap-2 cursor-pointer whitespace-nowrap shrink-0"
                :class="orderSubTab === 'confirm' ? 'text-slate-900 font-bold' : 'text-slate-500 hover:text-slate-900'">
                <span>2. Konfirmasi Terima Barang</span>
                <template x-if="pendingValidationCount > 0">
                    <span class="bg-[#ea580c] text-white text-[10px] px-2 py-0.5 rounded-md font-semibold tabular-nums"
                          x-text="pendingValidationCount">
                    </span>
                </template>
                <template x-if="orderSubTab === 'confirm'">
                    <span class="absolute bottom-0 left-0 w-full h-0.5 bg-[#ea580c] rounded-full"></span>
                </template>
            </button>

            <!-- 3. Pembayaran Midtrans -->
            <button 
                type="button"
                @click="orderSubTab = 'payment'"
                class="pb-3.5 relative transition flex items-center gap-2 cursor-pointer whitespace-nowrap shrink-0"
                :class="orderSubTab === 'payment' ? 'text-slate-900 font-bold' : 'text-slate-500 hover:text-slate-900'">
                <span>3. Bayar Tagihan (Midtrans)</span>
                <template x-if="pendingPaymentCount > 0">
                    <span class="bg-amber-500 text-white text-[10px] px-2 py-0.5 rounded-md font-semibold tabular-nums"
                          x-text="pendingPaymentCount">
                    </span>
                </template>
                <template x-if="orderSubTab === 'payment'">
                    <span class="absolute bottom-0 left-0 w-full h-0.5 bg-[#ea580c] rounded-full"></span>
                </template>
            </button>

            <!-- 4. Pasokan Menyusul (Backorder) -->
            <button 
                type="button"
                @click="orderSubTab = 'backorder'"
                class="pb-3.5 relative transition flex items-center gap-2 cursor-pointer whitespace-nowrap shrink-0"
                :class="orderSubTab === 'backorder' ? 'text-slate-900 font-bold' : 'text-slate-500 hover:text-slate-900'">
                <span>Pasokan Menyusul (Kloter 2)</span>
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

            <!-- 5. Rating & Ulasan -->
            <button 
                type="button"
                @click="orderSubTab = 'rating'"
                class="pb-3.5 relative transition flex items-center gap-2 cursor-pointer whitespace-nowrap shrink-0"
                :class="orderSubTab === 'rating' ? 'text-slate-900 font-bold' : 'text-slate-500 hover:text-slate-900'">
                <span>4. Rating &amp; Ulasan</span>
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
        <div {{ $serverSubTab === 'auto-order' ? '' : 'x-cloak style=display:none;' }} x-show="orderSubTab === 'auto-order'" class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden p-6 space-y-5 shadow-xs">
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
        <div {{ $serverSubTab === 'confirm' ? '' : 'x-cloak style=display:none;' }} x-show="orderSubTab === 'confirm'" class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden p-6 space-y-5 shadow-xs">
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
                                                <span>Terkirim Parsial: <strong>{{ number_format((float) ($deliveredQty ?? 0), 0) }}/{{ number_format((float) ($totalQty ?? 0), 0) }} {{ $unit }}</strong></span>
                                                <span class="text-[#ea580c] block font-semibold mt-0.5">— Sisa {{ number_format((float) ($remainingQty ?? 0), 0) }} {{ $unit }} dalam antrean Backorder</span>
                                            </div>
                                        @elseif($batch1 && $batch1->backorder_action === 'no_backorder')
                                            <div class="bg-rose-50 border border-rose-200/80 text-rose-950 px-2.5 py-1.5 rounded-lg text-[11px] leading-tight">
                                                <span>Terkirim Parsial: <strong>{{ number_format((float) ($deliveredQty ?? 0), 0) }}/{{ number_format((float) ($totalQty ?? 0), 0) }} {{ $unit }}</strong></span>
                                                <span class="text-rose-700 block font-semibold mt-0.5">— Sisa {{ number_format((float) max(0, (float)$totalQty - (float)$deliveredQty), 0) }} {{ $unit }} Dibatalkan (Tagihan Disesuaikan)</span>
                                            </div>
                                        @else
                                            <div class="text-slate-500 text-[11px]">
                                                Pengiriman Penuh: <strong class="text-slate-700">{{ number_format((float) ($totalQty ?? 0), 0) }} {{ $unit }}</strong>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 font-bold text-slate-900 whitespace-nowrap tabular-nums">
                                        Rp {{ number_format((float) ($dbOrder->total_amount ?? 0), 0, ',', '.') }}
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
                                                    <span>Kloter 1 Dikirim ({{ number_format((float) ($deliveredQty ?? 0), 0) }} {{ $unit }})</span>
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
                                                    <span>Armada Mengirim ({{ number_format((float) ($deliveredQty ?? 0), 0) }} {{ $unit }})</span>
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
                                            @php
                                                $orderDiffMinutes = $dbOrder->created_at ? abs(now()->diffInMinutes($dbOrder->created_at)) : 999;
                                                $orderCanCancel = $orderDiffMinutes < 30 && (! $batch1 || $batch1->shipped_at === null);
                                            @endphp
                                            <div class="flex flex-col items-center gap-1.5">
                                                <span class="text-xs text-slate-400 font-medium italic">Menunggu Armada Dikirim</span>
                                                @if($orderCanCancel)
                                                    <button type="button"
                                                            @click="openBatalOrderModal('{{ $dbOrder->id }}', '#ORD-{{ strtoupper(substr($dbOrder->id, 0, 8)) }}', '{{ 30 - $orderDiffMinutes }} menit')"
                                                            class="px-2.5 py-1 text-[10px] font-bold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-lg transition cursor-pointer inline-flex items-center gap-1 shadow-2xs">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                        <span>Batalkan Pesanan (&lt;30m)</span>
                                                    </button>
                                                @endif
                                            </div>
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
                                                @click="openConfirmModal('{{ $dbOrder->id }}')"
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
                                                @click="openConfirmBackorderModal('{{ $dbOrder->id }}')"
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
            <div @click.away="closeConfirmModal()" class="bg-white max-w-lg w-full rounded-2xl p-6 space-y-5 shadow-xl border border-slate-200/80">
                <div class="flex justify-between items-start border-b border-slate-100 pb-4">
                    <div>
                        <span class="text-[11px] font-bold text-[#ea580c] uppercase tracking-wider">Konfirmasi Penerimaan Fisik Batch 1</span>
                        <h3 class="text-lg font-bold text-slate-900 mt-1">Upload Bukti Penerimaan Dapur</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Foto barang kloter pertama yang sudah tiba di dapur Anda sebagai bukti konfirmasi resmi.</p>
                    </div>
                    <button type="button" @click="closeConfirmModal()" class="text-slate-400 hover:text-slate-700 cursor-pointer p-1.5 rounded-lg hover:bg-slate-100 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>

                <template x-if="confirmingOrderId">
                    <form :action="`/umkm/pemesanan/${confirmingOrderId}/konfirmasi`" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-2">Foto Bukti Penerimaan Barang Batch 1 <span class="text-rose-500">*</span></label>
                            
                            <div class="space-y-3">
                                <!-- State 1: Pilihan Foto Kamera atau Galeri (sebelum ada foto) -->
                                <template x-if="!confirmPhotoPreview && (!cameraActive || cameraTarget !== 'batch1')">
                                    <div class="space-y-2.5">
                                        <div class="grid grid-cols-2 gap-3">
                                            <!-- Tombol 1: Buka Kamera Langsung (Webcam / HP) -->
                                            <button 
                                                type="button" 
                                                @click="startCamera('batch1')"
                                                class="p-4 rounded-xl border border-slate-200/90 hover:border-[#ea580c] bg-slate-50/60 hover:bg-orange-50/40 text-center transition flex flex-col items-center justify-center gap-2 group cursor-pointer shadow-2xs">
                                                <div class="w-11 h-11 rounded-xl bg-orange-100 text-[#ea580c] group-hover:scale-110 flex items-center justify-center transition">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                </div>
                                                <span class="text-xs font-bold text-slate-800 group-hover:text-[#ea580c] transition block">Foto Kamera</span>
                                                <span class="text-[10px] text-slate-400 block -mt-1">Nyalakan kamera &amp; jepret langsung</span>
                                            </button>

                                            <!-- Tombol 2: Pilih dari Galeri File -->
                                            <button 
                                                type="button" 
                                                @click="confirmPhotoSource = 'gallery'; $refs.galleryInput.click()"
                                                class="p-4 rounded-xl border border-slate-200/90 hover:border-[#ea580c] bg-slate-50/60 hover:bg-orange-50/40 text-center transition flex flex-col items-center justify-center gap-2 group cursor-pointer shadow-2xs">
                                                <div class="w-11 h-11 rounded-xl bg-sky-100 text-sky-700 group-hover:scale-110 flex items-center justify-center transition">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                                <span class="text-xs font-bold text-slate-800 group-hover:text-[#ea580c] transition block">Pilih Galeri</span>
                                                <span class="text-[10px] text-slate-400 block -mt-1">Dari galeri foto perangkat</span>
                                            </button>
                                        </div>
                                        <p class="text-[11px] text-slate-400 text-center">Format: JPG, PNG, WEBP &bull; Maks. 5MB</p>
                                    </div>
                                </template>

                                <!-- State 2: Viewfinder Kamera Langsung Aktif -->
                                <template x-if="cameraActive && cameraTarget === 'batch1'">
                                    <div class="space-y-3 bg-slate-900 rounded-2xl p-3 border border-slate-800 text-white shadow-inner">
                                        <div class="relative rounded-xl overflow-hidden bg-black aspect-video flex items-center justify-center border border-white/10">
                                            <video id="cameraVideoBatch1" autoplay playsinline muted class="w-full h-full object-cover"></video>
                                            <div class="absolute inset-4 border-2 border-dashed border-white/40 rounded-lg pointer-events-none flex items-center justify-center">
                                                <span class="text-[11px] font-medium text-white/90 bg-black/60 px-2.5 py-1 rounded-md backdrop-blur-xs">Arahkan kamera ke barang penerimaan</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between gap-2 px-1">
                                            <button 
                                                type="button" 
                                                @click="stopCamera()" 
                                                class="text-xs font-semibold text-slate-300 hover:text-white bg-white/10 hover:bg-white/20 px-3.5 py-2 rounded-xl transition cursor-pointer">
                                                Batal Kamera
                                            </button>
                                            <button 
                                                type="button" 
                                                @click="captureCameraPhoto('batch1')" 
                                                :disabled="isTakingPhoto"
                                                class="text-xs font-bold text-white bg-[#ea580c] hover:bg-[#c2410c] px-4 py-2 rounded-xl transition shadow-md flex items-center gap-2 cursor-pointer disabled:opacity-50">
                                                <svg class="w-4 h-4 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                                <span x-text="isTakingPhoto ? 'Mengambil...' : 'Jepret Foto'"></span>
                                            </button>
                                        </div>
                                    </div>
                                </template>

                                <!-- State 3: Foto Sudah Tersedia (Preview) -->
                                <template x-if="confirmPhotoPreview">
                                    <div class="space-y-3">
                                        <div class="relative rounded-2xl overflow-hidden border border-slate-200/90 bg-slate-900/5 p-2 text-center">
                                            <img :src="confirmPhotoPreview" class="max-h-52 mx-auto rounded-xl object-contain shadow-xs" alt="Bukti Penerimaan">
                                            <span class="absolute top-4 left-4 text-[10px] font-bold px-2.5 py-1 rounded-lg text-white shadow-xs backdrop-blur-md"
                                                  :class="confirmPhotoSource === 'camera' ? 'bg-[#ea580c]/90' : 'bg-sky-600/90'"
                                                  x-text="confirmPhotoSource === 'camera' ? 'Kamera Langsung' : 'Unggahan Galeri'">
                                            </span>
                                        </div>
                                        <div class="flex items-center justify-center gap-2">
                                            <button type="button" @click="startCamera('batch1')" class="text-[11px] font-semibold text-slate-600 hover:text-[#ea580c] bg-slate-100 hover:bg-orange-50 px-3 py-1.5 rounded-lg transition inline-flex items-center gap-1 cursor-pointer">
                                                <span>Foto Ulang Kamera</span>
                                            </button>
                                            <button type="button" @click="confirmPhotoSource = 'gallery'; $refs.galleryInput.click()" class="text-[11px] font-semibold text-slate-600 hover:text-sky-600 bg-slate-100 hover:bg-sky-50 px-3 py-1.5 rounded-lg transition inline-flex items-center gap-1 cursor-pointer">
                                                <span>Ganti Galeri</span>
                                            </button>
                                            <button type="button" @click="resetConfirmPhoto()" class="text-[11px] font-semibold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 px-3 py-1.5 rounded-lg transition inline-flex items-center gap-1 cursor-pointer">
                                                <span>Hapus Foto</span>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Input Kamera Fallback (capture="environment") -->
                            <input
                                type="file"
                                :name="confirmPhotoSource === 'camera_file' ? 'proof_photo' : ''"
                                accept="image/*"
                                capture="environment"
                                x-ref="cameraInput"
                                class="hidden"
                                @change="onPhotoPicked($event, 'camera_file')">

                            <!-- Input Galeri Foto (tanpa capture) -->
                            <input
                                type="file"
                                :name="confirmPhotoSource === 'gallery' ? 'proof_photo' : ''"
                                accept="image/*"
                                x-ref="galleryInput"
                                class="hidden"
                                @change="onPhotoPicked($event, 'gallery')">

                            <!-- Input Form Utama (Menerima file dari capture live camera atau fallback) -->
                            <input
                                type="file"
                                :name="confirmPhotoSource === 'camera' || !confirmPhotoSource ? 'proof_photo' : ''"
                                accept="image/*"
                                x-ref="photoInput"
                                class="hidden"
                                :required="!confirmPhotoPreview">
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-semibold text-slate-700">Catatan Kondisi Kedatangan (Opsional)</label>
                                <span class="text-[10px] text-slate-400">Pilih cepat:</span>
                            </div>

                            <!-- Quick Tags untuk Dapur Cepat -->
                            <div class="flex flex-wrap gap-1.5 mb-2">
                                <button type="button" 
                                        @click="confirmNotes = (confirmNotes ? confirmNotes + ', ' : '') + 'Kondisi segar & timbangan pas.'"
                                        class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-orange-50 text-slate-700 hover:text-orange-700 text-[11px] font-medium border border-slate-200/80 transition cursor-pointer">
                                    + Segar &amp; Pas
                                </button>
                                <button type="button" 
                                        @click="confirmNotes = (confirmNotes ? confirmNotes + ', ' : '') + 'Kemasan rapi & higienis.'"
                                        class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-orange-50 text-slate-700 hover:text-orange-700 text-[11px] font-medium border border-slate-200/80 transition cursor-pointer">
                                    + Kemasan Rapi
                                </button>
                                <button type="button" 
                                        @click="confirmNotes = (confirmNotes ? confirmNotes + ', ' : '') + 'Ada sedikit susut timbangan / cacat fisik.'"
                                        class="px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-800 text-[11px] font-medium border border-amber-200 transition cursor-pointer">
                                    + Ada Cacat/Susut
                                </button>
                            </div>

                            <textarea
                                name="confirmation_notes"
                                rows="3"
                                x-model="confirmNotes"
                                placeholder="Masukkan catatan kondisi fisik bahan baku yang diterima di dapur..."
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200/90 text-slate-900 text-xs font-medium placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/15 focus:border-[#ea580c] transition">
                            </textarea>
                        </div>

                        <div class="flex gap-3 pt-2">
                            <button type="button" @click="closeConfirmModal()"
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
        <div {{ $serverSubTab === 'backorder' ? '' : 'x-cloak style=display:none;' }} x-show="orderSubTab === 'backorder'" class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden p-6 space-y-5 shadow-xs">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-3 border-b border-slate-100 pb-4">
                <div>
                    <span class="inline-flex items-center gap-1.5 text-[10px] font-bold text-amber-800 uppercase tracking-wider bg-amber-50 px-2.5 py-0.5 rounded-lg border border-amber-200/80">
                        Pengiriman Bertahap &bull; Kloter 2
                    </span>
                    <h3 class="font-bold text-lg text-slate-900 mt-1.5">Pemantauan Sisa Pasokan Menyusul (Kloter 2)</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Pesanan di bawah ini dikirim bertahap oleh pemasok mitra. Anda hanya membayar kuantitas bahan baku yang telah tiba &amp; dicek di dapur, sedangkan sisa bahan dipantau di sini hingga tiba lengkap.
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
                                            <span>Kloter 1 Terkirim — Menunggu Jadwal Kirim Sisa Backorder ({{ number_format((float) ($remainingQty ?? 0), 0) }} {{ $unit }})</span>
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
                                                    Pemasok hanya sanggup mengirimkan <strong class="text-emerald-700 font-bold">{{ number_format((float) ($deliveredQty ?? 0), 0) }} {{ $unit }}</strong> dan <strong class="text-rose-700 font-bold">tidak dapat mengirimkan sisa {{ number_format((float) ($remainingQty ?? 0), 0) }} {{ $unit }}</strong> karena stok gudang habis. Jika Anda setuju, sisa pesanan ditiadakan, tagihan disesuaikan hanya untuk {{ number_format((float) ($deliveredQty ?? 0), 0) }} {{ $unit }}, dan pemasok akan memberangkatkan barang.
                                                @else
                                                    Pemasok hanya dapat mengirimkan <strong class="text-emerald-700 font-bold">{{ number_format((float) ($deliveredQty ?? 0), 0) }} {{ $unit }}</strong> saat ini dan mengajukan pengiriman sisa <strong class="text-[#ea580c] font-bold">{{ number_format((float) ($remainingQty ?? 0), 0) }} {{ $unit }}</strong> pada tanggal <strong class="text-slate-900 underline">{{ $estimatedDateText }}</strong>. Apakah Anda setuju?
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
                                            <span>{{ $batch1?->backorder_action === 'no_backorder' ? 'Tolak & Batalkan Pesanan' : 'Tolak & Batalkan Sisa (' . number_format((float) ($remainingQty ?? 0), 0) . ' ' . $unit . ')' }}</span>
                                        </button>

                                        <button type="button"
                                                @click="approvingBackorderOrder = {
                                                    id: '{{ $boOrder->id }}',
                                                    orderCode: '#ORD-{{ strtoupper(substr($boOrder->id, 0, 8)) }}',
                                                    supplier: '{{ addslashes($boOrder->partnership?->supplier?->company_name ?? 'Pemasok Mitra') }}',
                                                    commodity: '{{ addslashes($firstItem?->supplierProduct?->commodity?->name ?? 'Bahan Baku') }}',
                                                    deliveredQty: '{{ number_format((float) ($deliveredQty ?? 0), 0) }}',
                                                    remainingQty: '{{ number_format((float) ($remainingQty ?? 0), 0) }}',
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
                                            {{ $batch1?->backorder_action === 'no_backorder' ? 'Seluruh pesanan dibatalkan sesuai keputusan penolakan Anda.' : 'Sisa barang ditiadakan permanen. Total tagihan telah dipotong hanya untuk ' . number_format((float) ($deliveredQty ?? 0), 0) . ' ' . $unit . '.' }}
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
                                            Sisa ditiadakan. Tagihan disesuaikan untuk {{ number_format((float) ($deliveredQty ?? 0), 0) }} {{ $unit }}.
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
                                        Total Kontrak: <strong class="text-slate-700">{{ number_format((float) ($totalQty ?? 0), 0) }} {{ $unit }}</strong>
                                    </p>
                                </div>

                                <!-- PERBANDINGAN BATCH 1 & BATCH 2 -->
                                <div class="md:col-span-5 bg-white p-3.5 rounded-xl border border-slate-200/80 space-y-1.5 text-xs tabular-nums">
                                    <div class="flex justify-between items-center text-emerald-800 font-semibold">
                                        <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg><span>Kloter 1 Diterima:</span></span>
                                        <strong>{{ number_format((float) ($deliveredQty ?? 0), 0) }} {{ $unit }}</strong>
                                    </div>
                                    <div class="flex justify-between items-center text-amber-900 font-bold border-t border-slate-100 pt-1.5">
                                        <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg><span>Sisa Backorder (Kloter 2):</span></span>
                                        <span class="text-amber-600 font-bold text-sm">{{ number_format((float) ($remainingQty ?? 0), 0) }} {{ $unit }}</span>
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
            <div @click.away="closeConfirmBackorderModal()" class="bg-white max-w-lg w-full rounded-2xl p-6 space-y-5 shadow-xl border border-slate-200/80">
                <div class="flex justify-between items-start border-b border-slate-100 pb-4">
                    <div>
                        <span class="text-[11px] font-bold text-amber-600 uppercase tracking-wider">Konfirmasi Sisa Backorder (Batch 2)</span>
                        <h3 class="text-lg font-bold text-slate-900 mt-1">Upload Bukti Penerimaan Batch 2</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Pastikan sisa barang pasokan telah Anda terima dengan baik di dapur.</p>
                    </div>
                    <button type="button" @click="closeConfirmBackorderModal()" class="text-slate-400 hover:text-slate-700 cursor-pointer p-1.5 rounded-lg hover:bg-slate-100 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>

                <template x-if="confirmingBackorderId">
                    <form :action="`/umkm/pemesanan/${confirmingBackorderId}/konfirmasi-backorder`" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-2">Foto Bukti Penerimaan Sisa Backorder <span class="text-rose-500">*</span></label>
                            
                            <div class="space-y-3">
                                <!-- State 1: Pilihan Foto Kamera atau Galeri (sebelum ada foto) -->
                                <template x-if="!confirmBackorderPhotoPreview && (!cameraActive || cameraTarget !== 'batch2')">
                                    <div class="space-y-2.5">
                                        <div class="grid grid-cols-2 gap-3">
                                            <!-- Tombol 1: Buka Kamera Langsung (Webcam / HP) -->
                                            <button 
                                                type="button" 
                                                @click="startCamera('batch2')"
                                                class="p-4 rounded-xl border border-slate-200/90 hover:border-amber-500 bg-slate-50/60 hover:bg-amber-50/40 text-center transition flex flex-col items-center justify-center gap-2 group cursor-pointer shadow-2xs">
                                                <div class="w-11 h-11 rounded-xl bg-amber-100 text-amber-700 group-hover:scale-110 flex items-center justify-center transition">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                </div>
                                                <span class="text-xs font-bold text-slate-800 group-hover:text-amber-700 transition block">Foto Kamera</span>
                                                <span class="text-[10px] text-slate-400 block -mt-1">Nyalakan kamera &amp; jepret langsung</span>
                                            </button>

                                            <!-- Tombol 2: Pilih dari Galeri File -->
                                            <button 
                                                type="button" 
                                                @click="confirmBackorderPhotoSource = 'gallery'; $refs.galleryBackorderInput.click()"
                                                class="p-4 rounded-xl border border-slate-200/90 hover:border-amber-500 bg-slate-50/60 hover:bg-amber-50/40 text-center transition flex flex-col items-center justify-center gap-2 group cursor-pointer shadow-2xs">
                                                <div class="w-11 h-11 rounded-xl bg-sky-100 text-sky-700 group-hover:scale-110 flex items-center justify-center transition">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                                <span class="text-xs font-bold text-slate-800 group-hover:text-amber-700 transition block">Pilih Galeri</span>
                                                <span class="text-[10px] text-slate-400 block -mt-1">Dari galeri foto perangkat</span>
                                            </button>
                                        </div>
                                        <p class="text-[11px] text-slate-400 text-center">Format: JPG, PNG, WEBP &bull; Maks. 5MB</p>
                                    </div>
                                </template>

                                <!-- State 2: Viewfinder Kamera Langsung Aktif -->
                                <template x-if="cameraActive && cameraTarget === 'batch2'">
                                    <div class="space-y-3 bg-slate-900 rounded-2xl p-3 border border-slate-800 text-white shadow-inner">
                                        <div class="relative rounded-xl overflow-hidden bg-black aspect-video flex items-center justify-center border border-white/10">
                                            <video id="cameraVideoBatch2" autoplay playsinline muted class="w-full h-full object-cover"></video>
                                            <div class="absolute inset-4 border-2 border-dashed border-white/40 rounded-lg pointer-events-none flex items-center justify-center">
                                                <span class="text-[11px] font-medium text-white/90 bg-black/60 px-2.5 py-1 rounded-md backdrop-blur-xs">Arahkan kamera ke barang Batch 2</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between gap-2 px-1">
                                            <button 
                                                type="button" 
                                                @click="stopCamera()" 
                                                class="text-xs font-semibold text-slate-300 hover:text-white bg-white/10 hover:bg-white/20 px-3.5 py-2 rounded-xl transition cursor-pointer">
                                                Batal Kamera
                                            </button>
                                            <button 
                                                type="button" 
                                                @click="captureCameraPhoto('batch2')" 
                                                :disabled="isTakingPhoto"
                                                class="text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 px-4 py-2 rounded-xl transition shadow-md flex items-center gap-2 cursor-pointer disabled:opacity-50">
                                                <svg class="w-4 h-4 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                                <span x-text="isTakingPhoto ? 'Mengambil...' : 'Jepret Foto'"></span>
                                            </button>
                                        </div>
                                    </div>
                                </template>

                                <!-- State 3: Foto Sudah Tersedia (Preview) -->
                                <template x-if="confirmBackorderPhotoPreview">
                                    <div class="space-y-3">
                                        <div class="relative rounded-2xl overflow-hidden border border-slate-200/90 bg-slate-900/5 p-2 text-center">
                                            <img :src="confirmBackorderPhotoPreview" class="max-h-52 mx-auto rounded-xl object-contain shadow-xs" alt="Bukti Penerimaan Batch 2">
                                            <span class="absolute top-4 left-4 text-[10px] font-bold px-2.5 py-1 rounded-lg text-white shadow-xs backdrop-blur-md"
                                                  :class="confirmBackorderPhotoSource === 'camera' ? 'bg-amber-600/90' : 'bg-sky-600/90'"
                                                  x-text="confirmBackorderPhotoSource === 'camera' ? 'Kamera Langsung' : 'Unggahan Galeri'">
                                            </span>
                                        </div>
                                        <div class="flex items-center justify-center gap-2">
                                            <button type="button" @click="startCamera('batch2')" class="text-[11px] font-semibold text-slate-600 hover:text-amber-700 bg-slate-100 hover:bg-amber-50 px-3 py-1.5 rounded-lg transition inline-flex items-center gap-1 cursor-pointer">
                                                <span>Foto Ulang Kamera</span>
                                            </button>
                                            <button type="button" @click="confirmBackorderPhotoSource = 'gallery'; $refs.galleryBackorderInput.click()" class="text-[11px] font-semibold text-slate-600 hover:text-sky-600 bg-slate-100 hover:bg-sky-50 px-3 py-1.5 rounded-lg transition inline-flex items-center gap-1 cursor-pointer">
                                                <span>Ganti Galeri</span>
                                            </button>
                                            <button type="button" @click="resetConfirmBackorderPhoto()" class="text-[11px] font-semibold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 px-3 py-1.5 rounded-lg transition inline-flex items-center gap-1 cursor-pointer">
                                                <span>Hapus Foto</span>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Input Kamera Fallback (capture="environment") -->
                            <input
                                type="file"
                                :name="confirmBackorderPhotoSource === 'camera_file' ? 'proof_photo' : ''"
                                accept="image/*"
                                capture="environment"
                                x-ref="cameraBackorderInput"
                                class="hidden"
                                @change="onBackorderPhotoPicked($event, 'camera_file')">

                            <!-- Input Galeri Foto (tanpa capture) -->
                            <input
                                type="file"
                                :name="confirmBackorderPhotoSource === 'gallery' ? 'proof_photo' : ''"
                                accept="image/*"
                                x-ref="galleryBackorderInput"
                                class="hidden"
                                @change="onBackorderPhotoPicked($event, 'gallery')">

                            <!-- Input Form Utama (Menerima file dari capture live camera atau fallback) -->
                            <input
                                type="file"
                                :name="confirmBackorderPhotoSource === 'camera' || !confirmBackorderPhotoSource ? 'proof_photo' : ''"
                                accept="image/*"
                                x-ref="photoBackorderInput"
                                class="hidden"
                                :required="!confirmBackorderPhotoPreview">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Catatan Penerimaan Batch 2 (Opsional)</label>
                            <textarea
                                name="confirmation_notes"
                                rows="3"
                                x-model="confirmBackorderNotes"
                                placeholder="Masukkan catatan penerimaan sisa pasokan..."
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200/90 text-slate-900 text-xs font-medium placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition">
                            </textarea>
                        </div>

                        <div class="flex gap-3 pt-2">
                            <button type="button" @click="closeConfirmBackorderModal()"
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
                                      placeholder="Masukkan alasan penolakan sisa pasokan (misal: kebutuhan dapur mendesak, sisa bahan dibeli mandiri)..."
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
        <div {{ $serverSubTab === 'payment' ? '' : 'x-cloak style=display:none;' }} x-show="orderSubTab === 'payment'" class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden p-6 space-y-5 shadow-xs">
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
                                            Rp {{ number_format((float) ($inv->amount ?? 0), 0, ',', '.') }}
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
                                            <div class="inline-flex flex-col items-center gap-1.5">
                                                <span class="text-emerald-700 font-semibold text-[11px] inline-flex items-center gap-1">
                                                    <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    <span>Lunas via {{ $inv->payment_method ?? 'Midtrans' }}</span>
                                                </span>
                                                @if($inv->midtrans_transaction_id)
                                                    <span class="text-[10px] text-slate-400 tabular-nums">Ref: {{ $inv->midtrans_transaction_id }}</span>
                                                @endif
                                                <button type="button"
                                                        @click="printUmkmBatchInvoice({
                                                            invoiceNumber: '{{ $inv->invoice_number ?? ('INV/' . strtoupper(substr($inv->order_id, 0, 8)) . '/B' . $inv->shipment_batch) }}',
                                                            orderCode: 'ORD-{{ strtoupper(substr($inv->order_id, 0, 8)) }}',
                                                            date: '{{ ($inv->paid_at ?? $inv->created_at)->format('d F Y') }}',
                                                            supplierName: '{{ addslashes($invOrder?->partnership?->supplier?->company_name ?? 'Pemasok Mitra') }}',
                                                            supplierPhone: '{{ addslashes($invOrder?->partnership?->supplier?->phone ?? '-') }}',
                                                            supplierAddress: '{{ addslashes($invOrder?->partnership?->supplier?->warehouse_address ?? 'Malang Raya') }}',
                                                            umkmName: '{{ addslashes($umkmBusinessName ?? 'Dapur UMKM') }}',
                                                            umkmOwner: '{{ addslashes($authUser?->umkmProfile?->owner_name ?? $authUser?->name ?? 'Pemilik Dapur') }}',
                                                            umkmPhone: '{{ addslashes($authUser?->umkmProfile?->phone ?? '-') }}',
                                                            umkmAddress: '{{ addslashes($authUser?->umkmProfile?->address ?? 'Malang') }}',
                                                            commodityName: '{{ addslashes($commodityName) }}',
                                                            batchName: '{{ $batchBadge }}',
                                                            quantity: '{{ number_format((float) ($inv->quantity_billed ?: 0), 1) }} {{ $unit }}',
                                                            amount: 'Rp {{ number_format((float) ($inv->amount ?? 0), 0, ',', '.') }}',
                                                            paymentMethod: '{{ $inv->payment_method ?? 'Midtrans Instant' }}',
                                                            paidAt: '{{ ($inv->paid_at ?? $inv->transaction_time ?? now())->format('d/m/Y H:i') }} WIB',
                                                            refNumber: '{{ $inv->midtrans_transaction_id ?? '-' }}'
                                                        })"
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-orange-50 hover:bg-orange-100 text-[#ea580c] border border-orange-200/80 rounded-xl font-bold text-[10px] transition cursor-pointer">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                                    <span>Cetak / Simpan Invoice</span>
                                                </button>
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
        <div {{ $serverSubTab === 'rating' ? '' : 'x-cloak style=display:none;' }} x-show="orderSubTab === 'rating'" class="space-y-6">

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
                                placeholder="Masukkan ulasan kualitas komoditas, kesegaran bahan, dan ketepatan pengiriman armada..." 
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
    <div {{ $serverActiveTab === 'dashboard' ? '' : 'x-cloak style=display:none;' }} x-show="activeTab === 'dashboard'" class="space-y-6">

        <!-- PAGE HEADER -->
        <div class="border-b border-slate-200/80 pb-4">
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Analisis Pengadaan</h2>
            <p class="text-sm sm:text-[15px] text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">Pantau ringkasan pengeluaran bulanan, kemitraan aktif, dan riwayat belanja dapur.</p>
        </div>

        <!-- METRICS CARDS UMKM -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 tabular-nums">
            <div class="bg-white p-5 sm:p-6 rounded-2xl shadow-xs border border-slate-200/80 space-y-1.5">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Total Pengeluaran Bulan Ini</span>
                <p class="text-2xl font-bold text-[#ea580c]">Rp {{ number_format((float) ($totalPengeluaranBulanIni ?? 0), 0, ',', '.') }}</p>
            </div>

            <div class="bg-white p-5 sm:p-6 rounded-2xl shadow-xs border border-slate-200/80 space-y-1.5">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Pesanan Aktif</span>
                <p class="text-2xl font-bold text-slate-900">{{ $activeOrdersCount ?? 0 }} Transaksi</p>
            </div>

            <div class="bg-white p-5 sm:p-6 rounded-2xl shadow-xs border border-slate-200/80 space-y-1.5">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Total Diterima</span>
                <p class="text-2xl font-bold text-slate-900">{{ number_format((float) ($totalDiterimaKg ?? 0), 0, ',', '.') }} kg</p>
            </div>

            <div class="bg-white p-5 sm:p-6 rounded-2xl shadow-xs border border-slate-200/80 space-y-1.5">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Ketepatan Waktu Kirim</span>
                @if(($onTimeDeliveryRate ?? 0) > 0)
                    <p class="text-2xl font-bold text-emerald-600">{{ number_format((float) ($onTimeDeliveryRate ?? 0), 1) }}%</p>
                @else
                    <p class="text-2xl font-bold text-slate-400">0%</p>
                @endif
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
                            <span class="text-[10px] font-semibold bg-orange-50 text-[#ea580c] border border-orange-200/70 px-2 py-0.5 rounded-md">Analisis Pasokan</span>
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
                @php
                    $chartPoints = [];
                    $xList = [45, 150, 260, 370, 480, 585, 685];
                    $maxVolumeInChart = 500;
                    if (!empty($trendsData)) {
                        foreach ($trendsData as $td) {
                            if ($td['volume'] > $maxVolumeInChart) {
                                $maxVolumeInChart = $td['volume'] * 1.25;
                            }
                        }
                    }

                    $hasVolume = ($totalDeliveredAllMonths ?? 0) > 0;
                    foreach (($trendsData ?? []) as $i => $td) {
                        $cx = $xList[$i] ?? (45 + $i * 105);
                        $cy = $hasVolume && $maxVolumeInChart > 0
                            ? (200 - round(($td['volume'] / $maxVolumeInChart) * 170))
                            : 200;
                        $chartPoints[] = [
                            'cx' => $cx,
                            'cy' => $cy,
                            'vol' => $td['volume'],
                            'cost' => $td['cost'],
                            'label' => $td['label'],
                            'is_active' => ($i === 5 || $i === 6)
                        ];
                    }

                    if ($hasVolume && count($chartPoints) > 1) {
                        $wavePath = "M" . $chartPoints[0]['cx'] . "," . $chartPoints[0]['cy'];
                        for ($i = 0; $i < count($chartPoints) - 1; $i++) {
                            $curr = $chartPoints[$i];
                            $next = $chartPoints[$i+1];
                            $midX = ($curr['cx'] + $next['cx']) / 2;
                            $wavePath .= " C" . $midX . "," . $curr['cy'] . " " . $midX . "," . $next['cy'] . " " . $next['cx'] . "," . $next['cy'];
                        }
                        $areaPath = $wavePath . " L685,200 L45,200 Z";
                    } else {
                        $wavePath = "M45,200 L685,200";
                        $areaPath = "M45,200 L685,200 Z";
                    }
                @endphp
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
                        <path d="{{ $areaPath }}" fill="url(#umkmWaveGrad)" />

                        <!-- Smooth Wave Line Path -->
                        <path d="{{ $wavePath }}" fill="none" stroke="url(#umkmLineGrad)" stroke-width="3" stroke-linecap="round" />

                        <!-- Data Points (Circles) -->
                        @foreach($chartPoints as $pt)
                            <g class="cursor-pointer group">
                                <circle cx="{{ $pt['cx'] }}" cy="{{ $pt['cy'] }}" r="{{ $pt['is_active'] && $hasVolume ? '5' : '4' }}"
                                        fill="{{ $pt['is_active'] && $hasVolume ? '#ea580c' : '#ffffff' }}"
                                        stroke="{{ $pt['is_active'] && $hasVolume ? '#ffffff' : '#ea580c' }}"
                                        stroke-width="2.5"
                                        class="{{ $pt['is_active'] && $hasVolume ? 'animate-pulse' : '' }}" />
                                <title>{{ $pt['label'] }}: {{ number_format((float) ($pt['vol'] ?? 0), 0, ',', '.') }} kg</title>
                            </g>
                        @endforeach

                        @if(!$hasVolume)
                            <text x="365" y="105" class="text-xs fill-slate-400 font-medium" text-anchor="middle">Belum ada histori pengadaan. Grafik akan otomatis terbentuk setelah transaksi pertama.</text>
                        @endif

                        <!-- X-Axis Labels -->
                        <text x="45" y="222" class="text-[10px] fill-slate-400 font-bold" text-anchor="middle">MEI</text>
                        <text x="150" y="222" class="text-[10px] fill-slate-400 font-bold" text-anchor="middle">JUN</text>
                        <text x="260" y="222" class="text-[10px] fill-slate-400 font-bold" text-anchor="middle">JUL</text>
                        <text x="370" y="222" class="text-[10px] fill-slate-400 font-bold" text-anchor="middle">AGU</text>
                        <text x="480" y="222" class="text-[10px] fill-slate-400 font-bold" text-anchor="middle">SEP</text>
                        <text x="585" y="222" class="text-[10px] fill-[#ea580c] font-bold" text-anchor="middle">OKT</text>
                        <text x="685" y="222" class="text-[10px] fill-slate-400 font-bold" text-anchor="middle">NOV (AKTIF)</text>
                    </svg>
                </div>

                <!-- Footer Summary Line Chart -->
                <div class="flex items-center justify-between text-xs text-slate-500 pt-3 border-t border-slate-100 flex-wrap gap-2 tabular-nums">
                    <span class="inline-flex items-center gap-1.5 font-medium">
                        <span class="w-2 h-2 rounded-full {{ ($realisasiPasokanBulanIni ?? 0) > 0 ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                        Realisasi Pasokan: <strong class="text-slate-900 font-semibold">{{ number_format((float) ($realisasiPasokanBulanIni ?? 0), 0, ',', '.') }} Kg</strong> bulan ini
                    </span>
                    <span class="text-[11px] font-medium text-slate-400">Target Bulan Depan: {{ number_format((float) ($targetBulanDepan ?? 0), 0, ',', '.') }} Kg</span>
                </div>
            </div>

            <!-- KOLOM KANAN (lg:col-span-4): DONUT / PIE CHART DISTRIBUSI KONTRAK AKTIF -->
            <div class="lg:col-span-4 bg-white p-6 rounded-2xl shadow-xs border border-slate-200/80 flex flex-col items-center justify-between space-y-4">
                <div class="w-full text-left border-b border-slate-100 pb-3">
                    <h3 class="text-base font-bold text-slate-900 tracking-tight">Distribusi Kontrak</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Berdasarkan status kemitraan</p>
                </div>

                @php
                    $safeActive = (int) ($activeContractsCount ?? 0);
                    $safePending = (int) ($pendingContractsCount ?? 0);
                    $totalC = $safeActive + $safePending;
                    $circumference = 240;

                    if ($totalC > 0) {
                        if ($safePending > 0) {
                            $activeRatio = $safeActive / $totalC;
                            $pendingRatio = $safePending / $totalC;
                            $activeDash = max(20, round($activeRatio * ($circumference - 30)));
                            $pendingDash = max(20, round($pendingRatio * ($circumference - 30)));
                            $pendingOffset = -($activeDash + 15);
                        } else {
                            $activeDash = 230;
                            $pendingDash = 0;
                            $pendingOffset = 0;
                        }
                    } else {
                        $activeDash = 0;
                        $pendingDash = 0;
                        $pendingOffset = 0;
                    }
                @endphp

                <!-- SVG DONUT / PIE CHART -->
                <div class="relative w-44 h-44 sm:w-48 sm:h-48 flex items-center justify-center my-2">
                    <svg viewBox="0 0 100 100" class="w-full h-full transform -rotate-90">
                        <circle cx="50" cy="50" r="38" fill="none" stroke="#f1f5f9" stroke-width="11" />
                        @if($safeActive > 0)
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
                        @endif

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
                        <span class="text-3xl font-bold text-slate-900 mt-1.5 leading-none tracking-tight">{{ $safeActive }}</span>
                        <span class="text-[10px] {{ $safeActive > 0 ? 'text-[#ea580c]' : 'text-slate-400' }} font-bold uppercase tracking-wide mt-1">
                            {{ $safeActive > 0 ? 'Aktif Berjalan' : 'Belum Ada Kontrak' }}
                        </span>
                    </div>
                </div>

                <!-- Dot Indicators -->
                <div class="w-full pt-3 border-t border-slate-100 space-y-2.5 tabular-nums">
                    <div class="flex items-center justify-center gap-5">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full {{ $safeActive > 0 ? 'bg-[#ea580c]' : 'bg-slate-300' }}"></span>
                            <span class="text-xs font-semibold text-slate-700">Aktif ({{ $safeActive }})</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full {{ $safePending > 0 ? 'bg-[#0284c7]' : 'bg-slate-300' }}"></span>
                            <span class="text-xs font-medium text-slate-500">Menunggu ({{ $safePending }})</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-center gap-1.5 flex-wrap pt-1">
                        @forelse(array_slice($activeContractCommodities ?? [], 0, 3, true) as $cName => $cQty)
                            <span class="text-[10px] font-semibold bg-slate-50 border border-slate-200/80 text-slate-600 px-2.5 py-0.5 rounded-lg truncate max-w-[140px]">
                                {{ $cName }}
                            </span>
                        @empty
                            <span class="text-[10px] font-medium text-slate-400 italic">
                                Belum ada kontrak aktif
                            </span>
                        @endforelse
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
                            <th class="px-4 py-3.5 text-center">Faktur Invoice</th>
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
                                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                    <button type="button"
                                            @click="printUmkmOrderInvoice(ord)"
                                            title="Cetak / Simpan Faktur Transaksi"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-orange-50 text-slate-700 hover:text-[#ea580c] border border-slate-200 hover:border-orange-200/80 rounded-xl font-semibold text-[11px] transition shadow-2xs cursor-pointer">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        <span>Faktur Invoice</span>
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <template x-if="orders.length === 0">
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center">
                                    <div class="max-w-xs mx-auto space-y-3">
                                        <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-800 text-sm">Belum Ada Riwayat Transaksi</p>
                                            <p class="text-xs text-slate-500 mt-0.5">Transaksi bahan baku dapur Anda akan tercatat di sini setelah pesanan diterbitkan.</p>
                                        </div>
                                        <button type="button" @click="setTab('katalog')" class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#ea580c] hover:bg-[#c2410c] text-white rounded-xl text-xs font-semibold transition shadow-xs cursor-pointer">
                                            <span>Mulai Belanja di Katalog</span>
                                        </button>
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
    <!-- TAB 5: SUPPLIER PERFORMANCE TRACKER & CONTRACT MONITORING -->
    <!-- ========================================================= -->
    <div {{ $serverActiveTab === 'monitoring' ? '' : 'x-cloak style=display:none;' }} x-show="activeTab === 'monitoring'" class="space-y-6">

        <div class="border-b border-slate-200/80 pb-4">
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Pemantauan &amp; SLA Pemasok</h2>
            <p class="text-sm sm:text-[15px] text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">Pantau ketepatan waktu kirim dan tingkat pemenuhan pasokan setiap mitra.</p>
        </div>

        @php
            $perfItems = $contractedSuppliersPerformance ?? collect();
            $scoredItems = $perfItems->filter(fn($item) => !empty($item['score']));
            $avgScore = $scoredItems->isNotEmpty() ? $scoredItems->avg(fn($item) => (float) $item['score']->final_score) : 0.0;
            $avgOtd = $scoredItems->isNotEmpty() ? $scoredItems->avg(fn($item) => (float) ($item['score']->metrics_breakdown['delivery']['otd_rate'] ?? 0.0)) : 0.0;
            $avgFulfillment = $scoredItems->isNotEmpty() ? $scoredItems->avg(fn($item) => (float) ($item['score']->metrics_breakdown['fulfillment']['fill_rate'] ?? 0.0)) : 0.0;
            $avgStar = $scoredItems->isNotEmpty() ? $scoredItems->avg(fn($item) => (float) $item['score']->star_rating) : 0.0;
        @endphp

        <!-- 4 KARTU RINGKASAN METRIK UTAMA SLA -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 tabular-nums">
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-2">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Skor Rata-Rata</span>
                <div class="text-2xl sm:text-3xl font-bold text-[#ea580c] tracking-tight">
                    <span x-text="monitoringAvgScore">{{ number_format((float) ($avgScore ?? 0), 1) }}</span><span class="text-xs font-medium text-slate-400">/100</span>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-2">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Ketepatan Waktu Kirim</span>
                <div class="text-2xl sm:text-3xl font-bold text-emerald-600 tracking-tight">
                    <span x-text="monitoringAvgOtd">{{ number_format((float) ($avgOtd ?? 0), 1) }}</span><span class="text-sm font-semibold text-emerald-600/75">%</span>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-2">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Pemenuhan Kuota</span>
                <div class="text-2xl sm:text-3xl font-bold text-sky-600 tracking-tight">
                    <span x-text="monitoringAvgFill">{{ number_format((float) ($avgFulfillment ?? 0), 1) }}</span><span class="text-sm font-semibold text-sky-600/75">%</span>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-2">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Mitra Terkontrak</span>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-lg bg-purple-50 border border-purple-200/70 text-purple-700">Aktif</span>
                </div>
                <div class="text-2xl sm:text-3xl font-bold text-purple-700 tracking-tight">
                    <span>{{ $perfItems->count() }}</span> <span class="text-xs font-medium text-slate-400">Pemasok</span>
                </div>
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
                                                    {{ number_format((float) ($score?->star_rating ?? 5.0), 1) }}
                                                </span>
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5 flex-wrap tabular-nums">
                                            <span>{{ $sup?->warehouse_address ?? 'Area Lokal' }}</span>
                                            <span class="text-slate-300">•</span>
                                            <span>Komoditas: <strong class="text-slate-800 font-semibold">{{ $item['commodity_name'] }}</strong> ({{ number_format((float) ($item['volume_per_delivery'] ?? 0), 0) }} {{ $item['unit'] }}/jadwal)</span>
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
                                        <span class="font-bold text-emerald-700" x-text="Number(supplierScores['{{ $sup?->id }}']?.otd_rate ?? {{ $otdRate }}).toFixed(1) + '%'">{{ number_format((float) ($otdRate ?? 0), 1) }}%</span>
                                    </div>
                                    <div class="w-full bg-emerald-100 h-2 rounded-full overflow-hidden">
                                        <div class="bg-emerald-600 h-2 rounded-full transition-all duration-500" :style="'width: ' + Math.min(100, (supplierScores['{{ $sup?->id }}']?.otd_rate ?? {{ $otdRate }})) + '%'" style="width: {{ min(100, $otdRate) }}%"></div>
                                    </div>
                                    <div class="flex justify-between text-[11px] text-slate-500">
                                        <span x-text="'Rerata telat: ' + Number(supplierScores['{{ $sup?->id }}']?.avg_delay ?? {{ $avgDelay }}).toFixed(1) + ' jam'">Rerata telat: {{ number_format((float) ($avgDelay ?? 0), 1) }} jam</span>
                                    </div>
                                </div>

                                <!-- Pilar 2: Order Fulfillment -->
                                <div class="bg-sky-50/55 p-4 rounded-xl border border-sky-200/60 space-y-2">
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="font-semibold text-slate-800">2. Pemenuhan Kuota (OFR)</span>
                                        <span class="font-bold text-sky-700" x-text="Number(supplierScores['{{ $sup?->id }}']?.fill_rate ?? {{ $fillRate }}).toFixed(1) + '%'">{{ number_format((float) ($fillRate ?? 0), 1) }}%</span>
                                    </div>
                                    <div class="w-full bg-sky-100 h-2 rounded-full overflow-hidden">
                                        <div class="bg-sky-600 h-2 rounded-full transition-all duration-500" :style="'width: ' + Math.min(100, (supplierScores['{{ $sup?->id }}']?.fill_rate ?? {{ $fillRate }})) + '%'" style="width: {{ min(100, $fillRate) }}%"></div>
                                    </div>
                                    <div class="flex justify-between text-[11px] text-slate-500">
                                        <span x-text="'Rasio Backorder: ' + Number(supplierScores['{{ $sup?->id }}']?.backorder_rate ?? {{ $backorderRate }}).toFixed(1) + '%'">Rasio Backorder: {{ number_format((float) ($backorderRate ?? 0), 1) }}%</span>
                                    </div>
                                </div>

                                <!-- Pilar 3: Kualitas & Ulasan -->
                                <div class="bg-amber-50/55 p-4 rounded-xl border border-amber-200/60 space-y-2">
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="font-semibold text-slate-800">3. Bebas Retur &amp; Cacat</span>
                                        <span class="font-bold text-amber-700" x-text="Number(supplierScores['{{ $sup?->id }}']?.defect_free ?? {{ $defectFree }}).toFixed(1) + '%'">{{ number_format((float) ($defectFree ?? 0), 1) }}%</span>
                                    </div>
                                    <div class="w-full bg-amber-100 h-2 rounded-full overflow-hidden">
                                        <div class="bg-amber-500 h-2 rounded-full transition-all duration-500" :style="'width: ' + Math.min(100, (supplierScores['{{ $sup?->id }}']?.defect_free ?? {{ $defectFree }})) + '%'" style="width: {{ min(100, $defectFree) }}%"></div>
                                    </div>
                                    <div class="flex justify-between text-[11px] text-slate-500">
                                        <span>Kesesuaian Spesifikasi</span>
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

            <form x-ref="ajukanForm" method="POST" action="{{ route('umkm.katalog.ajukan') }}" class="space-y-4 text-xs">
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

                <!-- 5. Opsi Pemesanan Otomatis -->
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
                        <span class="block text-slate-900 font-semibold text-xs">Pemesanan Otomatis</span>
                        <span class="block text-slate-500 text-[11px] font-normal leading-relaxed">
                            Terbitkan jadwal pesanan otomatis sesuai hari pengiriman tanpa konfirmasi manual berulang.
                        </span>
                    </label>
                </div>

                <!-- 6. Ketentuan Mutu & Aturan Pasok Bahan Baku (Chips Preset) -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <label class="block text-slate-800 font-semibold text-xs">
                            Ketentuan Mutu &amp; Aturan Pasok (SLA Dapur)
                        </label>
                        <span class="text-[10px] text-slate-400 font-medium">Pilih aturan penerimaan bahan</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <template x-for="item in availableQualityTerms" :key="item.id">
                            <button 
                                type="button"
                                @click="toggleQualityTerm(item.label)"
                                class="p-2.5 rounded-xl border text-left transition flex items-start gap-2.5 cursor-pointer"
                                :class="selectedQualityTerms.includes(item.label) 
                                    ? 'bg-orange-50/90 border-[#ea580c] text-orange-950 shadow-2xs' 
                                    : 'bg-white border-slate-200/90 text-slate-700 hover:bg-slate-50 hover:border-slate-300'">
                                <div class="mt-0.5 w-4 h-4 rounded-md flex items-center justify-center shrink-0 transition"
                                     :class="selectedQualityTerms.includes(item.label) ? 'bg-[#ea580c] text-white' : 'border border-slate-300 bg-white'">
                                    <template x-if="selectedQualityTerms.includes(item.label)">
                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </template>
                                </div>
                                <div class="min-w-0">
                                    <span class="text-xs font-bold block leading-tight" x-text="item.label"></span>
                                    <span class="text-[10px] text-slate-500 block mt-0.5 leading-snug" x-text="item.desc"></span>
                                </div>
                            </button>
                        </template>
                    </div>

                    <!-- Hidden Inputs for Form Submission -->
                    <template x-for="t in selectedQualityTerms" :key="'term-input-' + t">
                        <input type="hidden" name="terms[]" :value="t">
                    </template>
                </div>

                <!-- 7. Catatan Spesifikasi Tambahan -->
                <div>
                    <label class="block text-slate-700 font-semibold mb-1 text-xs">Catatan Spesifikasi Tambahan (Opsional)</label>
                    <textarea 
                        name="submission_details" 
                        rows="2" 
                        placeholder="Masukkan spesifikasi khusus dapur Anda (misal: pemotongan ayam 8 bagian, toleransi tiba sebelum jam 06.00 WIB)..."
                        class="w-full px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200/90 text-slate-900 font-medium text-xs placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/15 focus:border-[#ea580c] transition"
                    ></textarea>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="selectedCommodity = null" class="w-1/2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200/90 py-2.5 rounded-xl font-semibold transition cursor-pointer">
                        Batal
                    </button>
                    <button type="button" @click="openAjukanConfirmModal()" class="w-1/2 bg-[#ea580c] hover:bg-[#c2410c] text-white py-2.5 px-4 rounded-xl font-semibold shadow-xs transition cursor-pointer inline-flex items-center justify-center gap-1.5">
                        <span>Kirim Pengajuan Kemitraan</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- POP UP KONFIRMASI: APAKAH DATA PENGAJUAN SUDAH SESUAI?    -->
    <!-- ========================================================= -->
    <div x-cloak x-show="showAjukanConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-fade-in">
        <div @click.away="showAjukanConfirmModal = false" class="bg-white max-w-lg w-full rounded-3xl p-6 sm:p-7 shadow-2xl border border-slate-100 space-y-5 animate-scale-up">

            <!-- Header Modal -->
            <div class="text-center space-y-2">
                <div class="w-12 h-12 mx-auto rounded-2xl bg-orange-100 text-[#ea580c] flex items-center justify-center shadow-xs">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 tracking-tight">
                    Apakah Data Pengajuan Anda Sudah Sesuai?
                </h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto leading-relaxed">
                    Mohon pastikan rincian volume pasokan, jadwal kedatangan, dan komitmen mutu berikut sudah sesuai kebutuhan dapur Anda.
                </p>
            </div>

            <!-- Ringkasan Rincian Pengajuan -->
            <div class="bg-slate-50 rounded-2xl border border-slate-200/80 p-4 space-y-2.5 text-xs">
                <div class="flex items-start justify-between gap-3 pb-2 border-b border-slate-200/70">
                    <span class="text-slate-500 font-medium shrink-0">Komoditas &amp; Pemasok:</span>
                    <span class="font-bold text-slate-900 text-right">
                        <span x-text="selectedCommodity?.commodity_name"></span> &bull; <span class="text-slate-600" x-text="selectedCommodity?.company_name"></span>
                    </span>
                </div>

                <div class="flex items-start justify-between gap-3 pb-2 border-b border-slate-200/70">
                    <span class="text-slate-500 font-medium shrink-0">Volume per Kirim:</span>
                    <span class="font-bold text-slate-900 text-right tabular-nums" x-text="formVolume + ' ' + (selectedCommodity?.unit ?? 'kg')"></span>
                </div>

                <div class="flex items-start justify-between gap-3 pb-2 border-b border-slate-200/70">
                    <span class="text-slate-500 font-medium shrink-0">Pola &amp; Hari Kirim:</span>
                    <span class="font-semibold text-slate-800 text-right" x-text="formFrequency + ' (' + formDays.join(', ') + ')'"></span>
                </div>

                <div class="flex items-start justify-between gap-3 pb-2 border-b border-slate-200/70">
                    <span class="text-slate-500 font-medium shrink-0">Jam Tiba di Dapur:</span>
                    <span class="font-semibold text-slate-800 text-right font-mono" x-text="formTime + ' WIB'"></span>
                </div>

                <div class="flex items-start justify-between gap-3 pb-2 border-b border-slate-200/70">
                    <span class="text-slate-500 font-medium shrink-0">Mulai Kirim Perdana:</span>
                    <span class="font-semibold text-slate-800 text-right" x-text="formStartDate"></span>
                </div>

                <div class="flex items-start justify-between gap-3 pb-2 border-b border-slate-200/70">
                    <span class="text-slate-500 font-medium shrink-0">Proyeksi Pengeluaran:</span>
                    <span class="font-bold text-[#ea580c] text-right text-sm tabular-nums" x-text="calculateMonthlyTotal() + ' / bulan'"></span>
                </div>

                <div class="flex items-start justify-between gap-3 pt-0.5">
                    <span class="text-slate-500 font-medium shrink-0">Ketentuan Mutu:</span>
                    <span class="font-medium text-slate-700 text-right text-[11px]" x-text="selectedQualityTerms.join(', ') || 'Standar Pemasok'"></span>
                </div>
            </div>

            <!-- Notice Sisa Waktu 30 Menit -->
            <div class="bg-amber-50 border border-amber-200/90 rounded-xl p-3 flex items-start gap-2.5 text-[11px] text-amber-900">
                <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span><strong>Catatan Penting:</strong> Anda memiliki waktu <strong>&lt; 30 menit</strong> setelah pengajuan dikirim untuk mengubah data jika ada penyesuaian. Setelah 30 menit, data terkunci otomatis.</span>
            </div>

            <!-- Tombol Aksi Modal -->
            <div class="grid grid-cols-2 gap-3 pt-1">
                <button type="button"
                        @click="showAjukanConfirmModal = false"
                        class="w-full bg-slate-100 hover:bg-slate-200 active:scale-[0.99] text-slate-700 font-bold text-xs py-3 rounded-full transition cursor-pointer">
                    Periksa Kembali
                </button>

                <button type="button"
                        @click="submitFinalAjukan()"
                        class="w-full bg-[#ea580c] hover:bg-[#c2410c] active:scale-[0.99] text-white font-bold text-xs py-3 rounded-full shadow-sm transition cursor-pointer flex items-center justify-center gap-1.5">
                    <span>Ya, Kirim Pengajuan</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </button>
            </div>

        </div>
    </div>

    <!-- ========================================================= -->
    <!-- MODAL EDIT DATA PENGAJUAN PERMINTAAN (< 30 MENIT)         -->
    <!-- ========================================================= -->
    <div x-cloak x-show="editingPermintaan" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div @click.away="closeEditPermintaanModal()" class="bg-white max-w-xl w-full rounded-2xl p-6 sm:p-7 space-y-5 shadow-xl relative border border-slate-200/80 max-h-[92vh] overflow-y-auto">

            <div class="flex justify-between items-start border-b border-slate-100 pb-3.5">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] font-bold text-[#ea580c] uppercase tracking-wider">Ubah Data Pengajuan</span>
                        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-orange-50 text-[#ea580c] font-bold" x-text="editingPermintaan?.contract_code"></span>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 tracking-tight mt-0.5" x-text="editingPermintaan?.commodity_name"></h3>
                    <p class="text-xs text-slate-500 mt-0.5">Pemasok: <span class="font-semibold text-slate-900" x-text="editingPermintaan?.supplier_name"></span></p>
                </div>
                <button type="button" @click="closeEditPermintaanModal()" class="text-slate-400 hover:text-slate-700 cursor-pointer p-1.5 rounded-lg hover:bg-slate-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Sisa Waktu Warning Banner -->
            <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl flex items-center justify-between gap-3 text-xs text-amber-900">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Sisa Waktu Edit: <strong class="font-bold text-amber-950" x-text="editingPermintaan?.remaining_minutes + ' menit'"></strong></span>
                </div>
                <span class="text-[11px] text-amber-700 font-medium">Batas maksimum: 30 menit</span>
            </div>

            <form :action="'{{ url('umkm/katalog/permintaan') }}/' + editingPermintaan?.id" method="POST" class="space-y-4 text-xs">
                @csrf
                @method('PUT')

                <template x-for="day in editForm.delivery_days" :key="'edit-day-' + day">
                    <input type="hidden" name="delivery_days[]" :value="day">
                </template>

                <!-- 1. Volume Pengiriman per Jadwal -->
                <div class="bg-slate-50/70 p-3.5 rounded-xl border border-slate-200/80 space-y-2">
                    <label class="text-slate-800 font-semibold block">Volume per Pengiriman</label>
                    <div class="relative">
                        <input type="number"
                               name="volume_per_delivery"
                               required
                               x-model.number="editForm.volume_per_delivery"
                               min="0.1"
                               step="any"
                               class="w-full pl-3.5 pr-14 py-2.5 rounded-xl bg-white text-slate-900 font-bold tabular-nums border border-slate-200/90 focus:outline-none focus:ring-2 focus:ring-orange-500/15 focus:border-[#ea580c] transition" />
                        <span class="absolute right-3.5 top-2.5 text-slate-400 font-bold uppercase text-xs" x-text="editForm.unit"></span>
                    </div>
                </div>

                <!-- 2. Hari Pengiriman Terjadwal -->
                <div class="bg-slate-50/70 p-3.5 rounded-xl border border-slate-200/80 space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-700 font-semibold text-[11px]">Hari Pengiriman Terjadwal</span>
                        <span class="text-slate-400 text-[10px] tabular-nums" x-text="editForm.delivery_days.length + ' hari terpilih'"></span>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="day in availableDays" :key="'edit-pill-' + day">
                            <button type="button"
                                    @click="toggleEditDay(day)"
                                    class="px-3 py-1.5 rounded-xl text-xs font-semibold transition cursor-pointer inline-flex items-center gap-1"
                                    :class="editForm.delivery_days.includes(day)
                                        ? 'bg-[#ea580c] text-white shadow-2xs'
                                        : 'bg-white border border-slate-200/90 text-slate-600 hover:bg-slate-100'">
                                <span x-text="day"></span>
                                <svg x-show="editForm.delivery_days.includes(day)" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- 3. Jam Tiba & Tanggal Mulai -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-slate-700 font-semibold mb-1.5">Target Jam Tiba di Dapur (WIB)</label>
                        <input type="time"
                               name="delivery_time"
                               x-model="editForm.delivery_time"
                               required
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200/90 text-slate-900 font-medium tabular-nums focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/15 focus:border-[#ea580c] transition" />
                    </div>

                    <div>
                        <label class="block text-slate-700 font-semibold mb-1.5">Tanggal Mulai Pengiriman</label>
                        <input type="date"
                               name="start_date"
                               x-model="editForm.start_date"
                               required
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200/90 text-slate-900 font-medium tabular-nums focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/15 focus:border-[#ea580c] transition" />
                    </div>
                </div>

                <!-- 4. Ketentuan Mutu -->
                <div class="space-y-2">
                    <label class="block text-slate-800 font-semibold text-xs">Ketentuan Mutu &amp; Aturan Pasok</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <template x-for="item in availableQualityTerms" :key="'edit-term-' + item.id">
                            <button type="button"
                                    @click="toggleEditQualityTerm(item.label)"
                                    class="p-2.5 rounded-xl border text-left transition flex items-start gap-2.5 cursor-pointer"
                                    :class="editForm.terms.includes(item.label)
                                        ? 'bg-orange-50/90 border-[#ea580c] text-orange-950 shadow-2xs'
                                        : 'bg-white border-slate-200/90 text-slate-700 hover:bg-slate-50 hover:border-slate-300'">
                                <div class="mt-0.5 w-4 h-4 rounded-md flex items-center justify-center shrink-0 transition"
                                     :class="editForm.terms.includes(item.label) ? 'bg-[#ea580c] text-white' : 'border border-slate-300 bg-white'">
                                    <template x-if="editForm.terms.includes(item.label)">
                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </template>
                                </div>
                                <div class="min-w-0">
                                    <span class="text-xs font-bold block leading-tight" x-text="item.label"></span>
                                    <span class="text-[10px] text-slate-500 block mt-0.5 leading-snug" x-text="item.desc"></span>
                                </div>
                            </button>
                        </template>
                    </div>

                    <template x-for="t in editForm.terms" :key="'edit-hidden-term-' + t">
                        <input type="hidden" name="terms[]" :value="t">
                    </template>
                </div>

                <!-- 5. Catatan Spesifikasi Tambahan -->
                <div>
                    <label class="block text-slate-700 font-semibold mb-1 text-xs">Catatan Spesifikasi Tambahan (Opsional)</label>
                    <textarea name="submission_details"
                              rows="2"
                              x-model="editForm.submission_details"
                              placeholder="Masukkan catatan spesifikasi khusus dapur Anda..."
                              class="w-full px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200/90 text-slate-900 font-medium text-xs placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/15 focus:border-[#ea580c] transition"></textarea>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="closeEditPermintaanModal()" class="w-1/2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200/90 py-2.5 rounded-xl font-semibold transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="w-1/2 bg-[#ea580c] hover:bg-[#c2410c] text-white py-2.5 px-4 rounded-xl font-semibold shadow-xs transition cursor-pointer inline-flex items-center justify-center gap-1.5">
                        <span>Simpan Perubahan Data</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- ========================================================= -->
    <!-- MODAL KONFIRMASI BATALKAN PENGAJUAN KEMITRAAN (< 30 MENIT)-->
    <!-- ========================================================= -->
    <div x-cloak x-show="cancellingPermintaan" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div @click.away="closeBatalPermintaanModal()" class="bg-white max-w-md w-full rounded-2xl p-6 sm:p-7 space-y-5 shadow-xl relative border border-slate-200/80">

            <div class="flex items-start gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900 tracking-tight">Batalkan Pengajuan Pasokan?</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Kode Kontrak: <span class="font-mono font-bold text-slate-700" x-text="cancellingPermintaan?.contract_code"></span>
                    </p>
                </div>
            </div>

            <!-- Detail Ringkasan Pengajuan yang Akan Dibatalkan -->
            <div class="bg-rose-50/60 border border-rose-200/70 rounded-xl p-3.5 space-y-2 text-xs">
                <div class="flex justify-between items-center text-slate-700">
                    <span class="text-slate-500">Komoditas Bahan Baku:</span>
                    <strong class="font-bold text-slate-900" x-text="cancellingPermintaan?.commodity_name"></strong>
                </div>
                <div class="flex justify-between items-center text-slate-700">
                    <span class="text-slate-500">Mitra Pemasok:</span>
                    <span class="font-semibold text-slate-800" x-text="cancellingPermintaan?.supplier_name"></span>
                </div>
                <div class="flex justify-between items-center text-slate-700">
                    <span class="text-slate-500">Volume Pasokan:</span>
                    <span class="font-semibold text-slate-800" x-text="cancellingPermintaan?.volume_per_delivery + ' ' + cancellingPermintaan?.unit + '/kirim'"></span>
                </div>
                <div class="flex justify-between items-center text-rose-800 pt-1.5 border-t border-rose-200/60 font-semibold">
                    <span>Sisa Waktu Pembatalan:</span>
                    <span class="font-bold" x-text="cancellingPermintaan?.remaining_minutes + ' menit'"></span>
                </div>
            </div>

            <p class="text-xs text-slate-600 leading-relaxed">
                Pengajuan ini masih dalam batas waktu <strong>&lt; 30 menit</strong> dan belum diproses oleh pihak pemasok. Jika dibatalkan sekarang, pengajuan akan ditarik permanen dan komoditas ini dapat Anda ajukan kembali jika dibutuhkan.
            </p>

            <!-- Action Buttons -->
            <form :action="'{{ url('umkm/katalog/permintaan') }}/' + cancellingPermintaan?.id + '/batal'" method="POST" class="pt-1">
                @csrf
                <div class="flex gap-2.5">
                    <button type="button"
                            @click="closeBatalPermintaanModal()"
                            class="w-1/2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200/90 py-2.5 rounded-xl font-semibold text-xs transition cursor-pointer">
                        Kembali
                    </button>
                    <button type="submit"
                            class="w-1/2 bg-rose-600 hover:bg-rose-700 text-white py-2.5 px-3 rounded-xl font-bold text-xs shadow-xs transition cursor-pointer flex items-center justify-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span>Ya, Batalkan</span>
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- ========================================================= -->
    <!-- MODAL KONFIRMASI BATALKAN PESANAN HARIAN (< 30 MENIT)     -->
    <!-- ========================================================= -->
    <div x-cloak x-show="cancellingOrder" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div @click.away="closeBatalOrderModal()" class="bg-white max-w-md w-full rounded-2xl p-6 sm:p-7 space-y-5 shadow-xl relative border border-slate-200/80">

            <div class="flex items-start gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900 tracking-tight">Batalkan Pesanan Ini?</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Nomor Pesanan: <span class="font-mono font-bold text-slate-700" x-text="cancellingOrder?.code"></span>
                    </p>
                </div>
            </div>

            <div class="bg-rose-50/60 border border-rose-200/70 rounded-xl p-3.5 space-y-1.5 text-xs">
                <p class="text-slate-700">
                    Pesanan ini diterbitkan dalam waktu <strong>&lt; 30 menit</strong> yang lalu (<span x-text="cancellingOrder?.timeLeft"></span> sisa waktu) dan armada pemasok belum diberangkatkan.
                </p>
                <p class="text-rose-800 font-semibold">
                    Apakah Anda yakin ingin membatalkan pengiriman pesanan ini untuk hari ini?
                </p>
            </div>

            <!-- Action Buttons -->
            <form :action="'{{ url('umkm/pemesanan') }}/' + cancellingOrder?.id + '/batal'" method="POST" class="pt-1">
                @csrf
                <div class="flex gap-2.5">
                    <button type="button"
                            @click="closeBatalOrderModal()"
                            class="w-1/2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200/90 py-2.5 rounded-xl font-semibold text-xs transition cursor-pointer">
                        Kembali
                    </button>
                    <button type="submit"
                            class="w-1/2 bg-rose-600 hover:bg-rose-700 text-white py-2.5 px-3 rounded-xl font-bold text-xs shadow-xs transition cursor-pointer flex items-center justify-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span>Ya, Batalkan Pesanan</span>
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

                <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-3.5 space-y-2.5 text-xs text-slate-600">
                    <div class="flex items-start gap-2.5">
                        <svg class="w-4 h-4 text-[#ea580c] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <div class="space-y-0.5 leading-relaxed">
                            <p class="font-bold text-slate-800 text-[11px]">Proteksi Rekening Bersama SORAI</p>
                            <p class="text-[11px] text-slate-600">Dana pembayaran ditahan aman per kloter kedatangan fisik. Pilihan kanal bayar (QRIS, VA Bank BCA/Mandiri/BNI/BRI, e-Wallet) dipilih langsung di jendela Midtrans.</p>
                        </div>
                    </div>
                    <div class="pt-2 border-t border-slate-200/80 flex items-center justify-between text-[11px] text-slate-500 font-medium">
                        <span>Batas Pelunasan:</span>
                        <span class="font-bold text-amber-800 bg-amber-50 px-2 py-0.5 rounded border border-amber-200/80">Maks. 1×24 Jam</span>
                    </div>
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
