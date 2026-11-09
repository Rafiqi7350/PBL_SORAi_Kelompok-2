@extends('layouts.supplier')

@section('content')
<div class="space-y-8" x-data="{
    activeTab: (new URLSearchParams(window.location.search).get('tab') || 'forecast'),
    forecastHorizon: '{{ $latestForecastRun?->forecast_horizon ?? '7_DAYS' }}',
    isRecalculating: false,
    notificationMsg: '',
    searchCommodityQuery: '',

    // Modal Collaborative Response
    openModal: false,
    selectedPlan: null,
    actionType: 'terima_penuh',
    confirmedQty: 0,
    supplierNotes: '',

    // Modal Edit Stok Fisik
    openInventoryModal: false,
    editingInventory: null,
    editingStockQty: 0,
    isUpdatingStock: false,

    // What-If Simulation State
    simDemandShock: 20,
    simLeadTimeDelay: 2,
    simScenarioName: 'Simulasi Lonjakan Hari Raya & Libur Panjang',
    isSimulating: false,
    simulationResult: @json($whatIfSimulationDefault),

    openResponseModal(plan, action) {
        this.selectedPlan = plan;
        this.actionType = action;
        this.confirmedQty = plan.confirmed_quantity || plan.estimated_quantity;
        if (action === 'terima_penuh') {
            this.supplierNotes = 'Kapasitas siap dipenuhi 100% sesuai estimasi kebutuhan dapur.';
        } else if (action === 'sesuaikan') {
            this.supplierNotes = 'Kapasitas pasokan disesuaikan dengan kuota panen/gudang yang tersedia.';
        } else {
            this.supplierNotes = 'Mohon maaf, kapasitas pasokan kami pada periode ini belum mencukupi.';
        }
        this.openModal = true;
    },

    openStockModal(inv) {
        this.editingInventory = inv;
        this.editingStockQty = inv.current_stock;
        this.openInventoryModal = true;
    },

    recalculateForecast(horizon) {
        var self = this;
        self.isRecalculating = true;
        self.forecastHorizon = horizon;
        fetch('{{ route("supplier.demand-planning.recalculate") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ horizon: horizon })
        })
        .then(function(res) { return res.json(); })
        .then(function(json) {
            self.isRecalculating = false;
            if (json.status === 'success') {
                self.notify('✓ ' + json.message);
                setTimeout(function() { window.location.reload(); }, 600);
            } else {
                alert(json.message || 'Gagal menghitung proyeksi.');
            }
        })
        .catch(function(err) {
            self.isRecalculating = false;
            alert('Terjadi kesalahan jaringan.');
        });
    },

    runWhatIfSimulation() {
        var self = this;
        self.isSimulating = true;
        fetch('{{ route("supplier.demand-planning.what-if") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                demand_shock_pct: self.simDemandShock,
                lead_time_delay_days: self.simLeadTimeDelay,
                scenario_name: self.simScenarioName
            })
        })
        .then(function(res) { return res.json(); })
        .then(function(json) {
            self.isSimulating = false;
            if (json.status === 'success') {
                self.simulationResult = json.data;
                self.notify('✓ Simulasi skenario berhasil dijalankan.');
            }
        })
        .catch(function() {
            self.isSimulating = false;
            alert('Gagal menjalankan simulasi.');
        });
    },

    approvePurchaseOrder(id) {
        var self = this;
        if (!confirm('Terbitkan Purchase Order resmi ke pihak produsen/petani hulu?')) return;
        fetch('{{ url("supplier/demand-planning/purchase-suggestion") }}/' + id + '/approve', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(function(res) { return res.json(); })
        .then(function(json) {
            if (json.status === 'success') {
                self.notify('✓ ' + json.message);
                setTimeout(function() { window.location.reload(); }, 600);
            }
        });
    },

    ignorePurchaseOrder(id) {
        var self = this;
        fetch('{{ url("supplier/demand-planning/purchase-suggestion") }}/' + id + '/ignore', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(function(res) { return res.json(); })
        .then(function(json) {
            if (json.status === 'success') {
                self.notify(json.message);
                setTimeout(function() { window.location.reload(); }, 500);
            }
        });
    },

    submitUpdateStock() {
        var self = this;
        if (!self.editingInventory) return;
        self.isUpdatingStock = true;
        fetch('{{ url("supplier/demand-planning/inventory") }}/' + self.editingInventory.id, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ current_stock: self.editingStockQty })
        })
        .then(function(res) { return res.json(); })
        .then(function(json) {
            self.isUpdatingStock = false;
            self.openInventoryModal = false;
            if (json.status === 'success') {
                self.notify('✓ ' + json.message);
                setTimeout(function() { window.location.reload(); }, 500);
            }
        });
    },

    notify(msg) {
        var self = this;
        self.notificationMsg = msg;
        setTimeout(function() {
            if (self.notificationMsg === msg) self.notificationMsg = '';
        }, 3500);
    }
}">

    {{-- Toast Notification Floating --}}
    <div x-cloak x-show="notificationMsg" x-transition
         class="fixed top-6 right-6 z-50 bg-gray-900 text-white px-5 py-3 rounded-2xl shadow-xl flex items-center gap-3 border border-gray-700 text-xs font-bold font-mono">
        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span>
        <span x-text="notificationMsg"></span>
    </div>

    {{-- Hero Section Banner --}}
    <div class="relative overflow-hidden rounded-3xl bg-[#ea580c] p-6 sm:p-8 text-white shadow-lg shadow-orange-500/15" style="background-color: #ea580c;">
        <div class="absolute -right-16 -top-16 h-56 w-56 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="absolute -left-12 -bottom-12 w-48 h-48 bg-white/5 rounded-full blur-xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2 max-w-2xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur-md border border-white/20 text-white text-[11px] font-mono font-bold uppercase tracking-wider">
                    <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span>Modul Logistik &amp; Perencanaan Pasokan Hulu</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                    Perencanaan Persediaan &amp; Prediksi Permintaan
                </h1>
                <p class="text-xs sm:text-sm text-orange-50/90 leading-relaxed">
                    Agregasi kebutuhan riil dari seluruh UMKM, batas cadangan pengaman (Safety Stock), sistem peringatan dini kehabisan stok, serta rekomendasi otomatis Purchase Order ke petani/produsen.
                </p>
            </div>

            <div class="shrink-0 flex flex-col sm:flex-row items-start sm:items-center gap-2.5">
                <button type="button"
                        @click="recalculateForecast(forecastHorizon)"
                        :disabled="isRecalculating"
                        class="px-4 py-2.5 rounded-2xl bg-white text-[#ea580c] hover:bg-orange-50 font-bold text-xs shadow-md transition flex items-center gap-2 cursor-pointer disabled:opacity-75">
                    <svg :class="isRecalculating ? 'animate-spin text-[#ea580c]' : 'text-[#ea580c]'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span x-text="isRecalculating ? 'Menghitung...' : 'Kalkulasi Ulang Realtime'">Kalkulasi Ulang Realtime</span>
                </button>
            </div>
        </div>
    </div>

    {{-- 4 Metric KPI Cards Utama --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Card 1: Total Proyeksi Demand --}}
        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm flex items-center justify-between hover:shadow-md transition">
            <div class="space-y-1">
                <p class="text-[11px] font-mono font-bold text-gray-400 uppercase tracking-wider">Total Proyeksi 7 Hari</p>
                <h3 class="text-2xl font-black text-[#ea580c] font-mono">
                    {{ number_format($totalProjectedDemand7Days, 1, ',', '.') }} <span class="text-xs font-semibold text-gray-400">kg/unit</span>
                </h3>
                <p class="text-[10px] text-gray-500 font-medium">Agregasi kebutuhan dapur seluruh UMKM</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-orange-50 text-[#ea580c] border border-orange-100 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </div>
        </div>

        {{-- Card 2: Status Buffer Gudang --}}
        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm flex items-center justify-between hover:shadow-md transition">
            <div class="space-y-1">
                <p class="text-[11px] font-mono font-bold text-gray-400 uppercase tracking-wider">Peringatan Stok (EWS)</p>
                <h3 class="text-2xl font-black {{ $criticalInventoryCount > 0 ? 'text-rose-600' : ($warningInventoryCount > 0 ? 'text-amber-600' : 'text-emerald-600') }} font-mono">
                    {{ $criticalInventoryCount }} <span class="text-xs font-semibold text-gray-400">Kritis / {{ $warningInventoryCount }} Siaga</span>
                </h3>
                <p class="text-[10px] text-gray-500 font-medium">Bahan baku di bawah batas aman</p>
            </div>
            <div class="w-12 h-12 rounded-2xl {{ $criticalInventoryCount > 0 ? 'bg-rose-50 text-rose-600 border border-rose-200 animate-pulse' : 'bg-emerald-50 text-emerald-600 border border-emerald-100' }} flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
        </div>

        {{-- Card 3: Saran Purchase Order --}}
        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm flex items-center justify-between hover:shadow-md transition">
            <div class="space-y-1">
                <p class="text-[11px] font-mono font-bold text-gray-400 uppercase tracking-wider">Saran Order Pengadaan</p>
                <h3 class="text-2xl font-black text-blue-600 font-mono">
                    {{ $purchaseSuggestions->where('status', 'PENDING_REVIEW')->count() }} <span class="text-xs font-semibold text-gray-400">PO Tertunda</span>
                </h3>
                <p class="text-[10px] text-gray-500 font-medium">Siap diterbitkan ke produsen hulu</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
            </div>
        </div>

        {{-- Card 4: Model Accuracy MAPE --}}
        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm flex items-center justify-between hover:shadow-md transition">
            <div class="space-y-1">
                <p class="text-[11px] font-mono font-bold text-gray-400 uppercase tracking-wider">Akurasi Algoritma</p>
                <h3 class="text-2xl font-black text-purple-700 font-mono">
                    {{ number_format(100 - (float) ($latestForecastRun?->model_accuracy_mape ?? 8.4), 1) }}%
                </h3>
                <p class="text-[10px] text-gray-500 font-medium">Model Hybrid: Kontrak + CSP + Historis</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-700 border border-purple-100 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
        </div>
    </div>

    {{-- TAB NAVIGATION (5 Modul Utama) --}}
    <div class="flex items-center gap-2 overflow-x-auto pb-2 border-b border-gray-200">
        <button type="button" @click="activeTab = 'forecast'"
                :class="activeTab === 'forecast' ? 'bg-[#ea580c] text-white shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                class="px-5 py-2.5 rounded-full text-xs font-bold transition flex items-center gap-2 shrink-0 cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
            <span>Proyeksi Permintaan 7–90 Hari</span>
        </button>

        <button type="button" @click="activeTab = 'inventory'"
                :class="activeTab === 'inventory' ? 'bg-[#ea580c] text-white shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                class="px-5 py-2.5 rounded-full text-xs font-bold transition flex items-center gap-2 shrink-0 cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            <span>Kontrol Stok &amp; EWS</span>
            @if($criticalInventoryCount > 0)
                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-rose-500 text-white font-mono">{{ $criticalInventoryCount }}</span>
            @endif
        </button>

        <button type="button" @click="activeTab = 'purchase-orders'"
                :class="activeTab === 'purchase-orders' ? 'bg-[#ea580c] text-white shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                class="px-5 py-2.5 rounded-full text-xs font-bold transition flex items-center gap-2 shrink-0 cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <span>Saran Pengadaan (PO Otomatis)</span>
            @if($purchaseSuggestions->where('status', 'PENDING_REVIEW')->count() > 0)
                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-blue-600 text-white font-mono">{{ $purchaseSuggestions->where('status', 'PENDING_REVIEW')->count() }}</span>
            @endif
        </button>

        <button type="button" @click="activeTab = 'what-if'"
                :class="activeTab === 'what-if' ? 'bg-[#ea580c] text-white shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                class="px-5 py-2.5 rounded-full text-xs font-bold transition flex items-center gap-2 shrink-0 cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            <span>Simulator Skenario What-If</span>
        </button>

        <button type="button" @click="activeTab = 'collaborative'"
                :class="activeTab === 'collaborative' ? 'bg-[#ea580c] text-white shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                class="px-5 py-2.5 rounded-full text-xs font-bold transition flex items-center gap-2 shrink-0 cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <span>Respon Kuota Dapur UMKM ({{ $pendingCount }})</span>
        </button>
    </div>

    <!-- ========================================================= -->
    <!-- TAB 1: PROYEKSI & GRAFIK KEBUTUHAN PERMINTAAN            -->
    <!-- ========================================================= -->
    <div x-show="activeTab === 'forecast'" class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
            <div>
                <span class="text-xs font-mono font-bold text-[#ea580c] uppercase tracking-wider block">MULTI-TENANT DEMAND AGGREGATION</span>
                <h3 class="text-xl font-extrabold text-gray-900 mt-1">Grafik Timeline Proyeksi Permintaan Bahan Baku</h3>
                <p class="text-xs text-gray-500 mt-0.5">Rentang keyakinan 95% memperhitungkan lonjakan akhir pekan dapur UMKM dan kontrak jadwal kirim.</p>
            </div>

            {{-- Horizon Selector --}}
            <div class="flex items-center gap-1.5 p-1 bg-gray-100 rounded-2xl self-start sm:self-auto">
                <button type="button" @click="recalculateForecast('7_DAYS')"
                        :class="forecastHorizon === '7_DAYS' ? 'bg-white text-gray-900 shadow-xs font-bold' : 'text-gray-500 hover:text-gray-800 font-semibold'"
                        class="px-3.5 py-1.5 rounded-xl text-xs transition cursor-pointer">
                    7 Hari (Harian)
                </button>
                <button type="button" @click="recalculateForecast('30_DAYS')"
                        :class="forecastHorizon === '30_DAYS' ? 'bg-white text-gray-900 shadow-xs font-bold' : 'text-gray-500 hover:text-gray-800 font-semibold'"
                        class="px-3.5 py-1.5 rounded-xl text-xs transition cursor-pointer">
                    30 Hari
                </button>
                <button type="button" @click="recalculateForecast('90_DAYS')"
                        :class="forecastHorizon === '90_DAYS' ? 'bg-white text-gray-900 shadow-xs font-bold' : 'text-gray-500 hover:text-gray-800 font-semibold'"
                        class="px-3.5 py-1.5 rounded-xl text-xs transition cursor-pointer">
                    90 Hari (Kuartal)
                </button>
            </div>
        </div>

        {{-- Grid Timeline Kebutuhan Harian per Komoditas --}}
        @php
            $forecastByCommodity = $forecastItems->groupBy('commodity_id');
        @endphp

        <div class="grid grid-cols-1 gap-6">
            @foreach($forecastByCommodity as $commId => $items)
                @php
                    $comm = $items->first()?->commodity;
                    $totalCommodityProjected = $items->sum('baseline_demand');
                    $avgDaily = $items->count() > 0 ? $totalCommodityProjected / $items->count() : 0;
                    $maxDemandItem = $items->sortByDesc('baseline_demand')->first();
                @endphp
                <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-5">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-orange-50 text-[#ea580c] font-black flex items-center justify-center text-lg border border-orange-100">
                                {{ substr($comm?->name ?? 'B', 0, 1) }}
                            </div>
                            <div>
                                <h4 class="font-extrabold text-base text-gray-900">{{ $comm?->name ?? 'Komoditas Pasokan' }}</h4>
                                <p class="text-xs text-gray-500">Satuan: <strong class="text-gray-800">{{ $comm?->unit ?? 'kg' }}</strong> • Rerata: <strong class="text-gray-800 font-mono">{{ number_format($avgDaily, 1) }} {{ $comm?->unit ?? 'kg' }}/hari</strong></p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 text-xs">
                            <div class="text-right">
                                <span class="text-gray-400 text-[10px] uppercase font-mono block">Total Kebutuhan Periode</span>
                                <span class="text-xl font-black text-[#ea580c] font-mono">{{ number_format($totalCommodityProjected, 1) }} {{ $comm?->unit ?? 'kg' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Visual Bar Chart Timeline Sederhana (Harian) --}}
                    <div class="space-y-2">
                        <span class="text-[10px] font-mono font-bold text-gray-400 uppercase tracking-wider block">PROYEKSI DEMAND HARIAN (BASELINE VS BATAS ATAS 95%)</span>
                        <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-2.5">
                            @foreach($items->take(7) as $fi)
                                @php
                                    $d = \Carbon\Carbon::parse($fi->forecast_date);
                                    $isWeekend = in_array($d->dayOfWeek, [0, 5, 6]);
                                    $pct = $maxDemandItem && $maxDemandItem->baseline_demand > 0
                                        ? round(($fi->baseline_demand / $maxDemandItem->baseline_demand) * 100)
                                        : 50;
                                @endphp
                                <div class="bg-gray-50/80 p-3 rounded-2xl border border-gray-200/60 space-y-2 text-center {{ $isWeekend ? 'ring-1 ring-orange-200 bg-orange-50/20' : '' }}">
                                    <div class="text-[11px] font-bold {{ $isWeekend ? 'text-[#ea580c]' : 'text-gray-700' }}">
                                        {{ $d->translatedFormat('D, d M') }}
                                    </div>
                                    <div class="text-base font-black text-gray-900 font-mono">
                                        {{ number_format($fi->baseline_demand, 0) }}
                                    </div>
                                    <div class="w-full bg-gray-200 h-1.5 rounded-full overflow-hidden">
                                        <div class="bg-[#ea580c] h-1.5 rounded-full" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <div class="text-[10px] text-gray-400 font-mono">
                                        Maks: {{ number_format($fi->upper_bound_95, 0) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Matriks Breakdown Kontribusi Demand per UMKM (Privacy-Preserving) --}}
        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <div>
                    <h4 class="font-extrabold text-base text-gray-900">Kontribusi Permintaan per UMKM Terkontrak</h4>
                    <p class="text-xs text-gray-500">Pemetaan alokasi volume pasokan berdasarkan jadwal kemitraan resmi.</p>
                </div>
                <span class="text-xs font-mono font-bold text-gray-500 bg-gray-100 px-3 py-1 rounded-full">
                    {{ count($umkmBreakdowns) }} Mitra Terdaftar
                </span>
            </div>

            @if(empty($umkmBreakdowns) || count($umkmBreakdowns) === 0)
                <p class="text-xs text-gray-400 text-center py-6">Belum ada kemitraan terjadwal aktif.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50/80 text-gray-400 uppercase font-mono text-[10px]">
                            <tr>
                                <th class="p-3">Nama UMKM</th>
                                <th class="p-3">Komoditas Bahan</th>
                                <th class="p-3">Volume Kirim</th>
                                <th class="p-3">Status Kontrak</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($umkmBreakdowns as $ub)
                                <tr class="hover:bg-gray-50/50">
                                    <td class="p-3 font-bold text-gray-900">{{ $ub['umkm_name'] }}</td>
                                    <td class="p-3 text-gray-600">{{ $ub['commodity_name'] }}</td>
                                    <td class="p-3 font-mono font-bold text-[#ea580c]">{{ number_format($ub['total_scheduled_volume'], 1) }} {{ $ub['unit'] }}/jadwal</td>
                                    <td class="p-3">
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                            {{ $ub['contract_status'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- TAB 2: KONTROL INVENTORI & EARLY WARNING SYSTEM (EWS)    -->
    <!-- ========================================================= -->
    <div x-show="activeTab === 'inventory'" class="space-y-6">
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <span class="text-xs font-mono font-bold text-[#ea580c] uppercase tracking-wider block">EARLY WARNING &amp; BUFFER STOCK</span>
                <h3 class="text-xl font-extrabold text-gray-900 mt-1">Status Fisik Gudang vs Safety Stock &amp; Reorder Point</h3>
                <p class="text-xs text-gray-500 mt-0.5">Sistem otomatis memberi peringatan siaga saat stok menyentuh ROP untuk mencegah kehabisan stok (*stockout*).</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($inventoryItems as $inv)
                @php
                    $isCritical = $inv->ews_status === 'CRITICAL';
                    $isWarning = $inv->ews_status === 'WARNING';
                @endphp
                <div class="bg-white rounded-3xl p-6 border {{ $isCritical ? 'border-rose-300 shadow-rose-100 shadow-md ring-2 ring-rose-200' : ($isWarning ? 'border-amber-200 shadow-sm' : 'border-gray-100 shadow-sm') }} space-y-4">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $inv->ews_badge_color }}">
                                {{ $inv->ews_label }}
                            </span>
                            <h4 class="font-extrabold text-base text-gray-900 mt-2">{{ $inv->commodity?->name ?? 'Komoditas' }}</h4>
                        </div>
                        <button type="button" @click="openStockModal(@json($inv))" class="text-xs font-bold text-[#ea580c] hover:underline cursor-pointer">
                            Update Stok
                        </button>
                    </div>

                    <div class="grid grid-cols-3 gap-2 text-center text-xs">
                        <div class="bg-gray-50 p-2.5 rounded-2xl border border-gray-100">
                            <span class="text-[10px] text-gray-400 font-mono block">Stok Gudang</span>
                            <span class="font-black text-gray-900 text-sm font-mono">{{ number_format($inv->current_stock, 1) }}</span>
                            <span class="text-[9px] text-gray-400">{{ $inv->unit }}</span>
                        </div>
                        <div class="bg-amber-50/60 p-2.5 rounded-2xl border border-amber-100">
                            <span class="text-[10px] text-amber-700 font-mono block">Reorder Point</span>
                            <span class="font-black text-amber-800 text-sm font-mono">{{ number_format($inv->reorder_point, 1) }}</span>
                            <span class="text-[9px] text-amber-600">{{ $inv->unit }}</span>
                        </div>
                        <div class="bg-rose-50/60 p-2.5 rounded-2xl border border-rose-100">
                            <span class="text-[10px] text-rose-700 font-mono block">Safety Stock</span>
                            <span class="font-black text-rose-800 text-sm font-mono">{{ number_format($inv->safety_stock, 1) }}</span>
                            <span class="text-[9px] text-rose-600">{{ $inv->unit }}</span>
                        </div>
                    </div>

                    {{-- Visual Progress Meter --}}
                    @php
                        $stockVal = (float) $inv->current_stock;
                        $ropVal = max(1.0, (float) $inv->reorder_point);
                        $meterPct = min(100, round(($stockVal / ($ropVal * 1.5)) * 100));
                    @endphp
                    <div class="space-y-1.5 pt-1">
                        <div class="flex justify-between text-[11px] font-semibold text-gray-500">
                            <span>Kecukupan Stok</span>
                            <span class="font-mono font-bold {{ $isCritical ? 'text-rose-600' : ($isWarning ? 'text-amber-600' : 'text-emerald-600') }}">
                                {{ $isCritical ? 'Kritis' : ($isWarning ? 'Siaga Kulakan' : 'Aman') }}
                            </span>
                        </div>
                        <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                            <div class="h-2 rounded-full transition-all duration-500 {{ $isCritical ? 'bg-rose-500' : ($isWarning ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $meterPct }}%"></div>
                        </div>
                    </div>

                    <div class="text-[10px] text-gray-400 flex justify-between pt-2 border-t border-gray-100 font-mono">
                        <span>Restock Terakhir:</span>
                        <span>{{ $inv->last_restocked_at ? $inv->last_restocked_at->translatedFormat('d M Y') : 'Baru' }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- TAB 3: REKOMENDASI PEMESANAN (PURCHASE ORDER ENGINE)     -->
    <!-- ========================================================= -->
    <div x-show="activeTab === 'purchase-orders'" class="space-y-6">
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <span class="text-xs font-mono font-bold text-[#ea580c] uppercase tracking-wider block">AUTOMATED PROCUREMENT RECOMMENDATION</span>
                <h3 class="text-xl font-extrabold text-gray-900 mt-1">Rekomendasi Pemesanan Bahan Baku ke Petani / Produsen Hulu</h3>
                <p class="text-xs text-gray-500 mt-0.5">Dihitung otomatis berdasarkan kebutuhan bersih, ambang batas minimum order (MOQ), dan durasi pengiriman hulu (*lead time*).</p>
            </div>
        </div>

        @if($purchaseSuggestions->isEmpty())
            <div class="bg-white rounded-3xl p-12 text-center border border-gray-100 shadow-sm space-y-3">
                <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center mx-auto border border-emerald-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h4 class="font-bold text-sm text-gray-900">Semua Stok Bahan Baku Aman</h4>
                <p class="text-xs text-gray-500 max-w-sm mx-auto">Saat ini seluruh stok berada di atas Reorder Point. Sistem belum mendeteksi kebutuhan penerbitan Purchase Order baru.</p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4">
                @foreach($purchaseSuggestions as $sugg)
                    @php
                        $isPending = $sugg->status === 'PENDING_REVIEW';
                    @endphp
                    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 pb-4">
                            <div class="flex items-center gap-3">
                                <span class="px-3 py-1 rounded-full text-xs font-bold border {{ $sugg->urgency_badge_color }}">
                                    {{ $sugg->urgency_level }}
                                </span>
                                <div>
                                    <h4 class="font-extrabold text-base text-gray-900">{{ $sugg->commodity?->name ?? 'Komoditas' }}</h4>
                                    <p class="text-xs text-gray-500">Tujuan Produsen: <strong class="text-gray-800">{{ $sugg->materialSource?->producer_name ?? 'Petani / Distributor Mitra' }}</strong></p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                @if($isPending)
                                    <button type="button" @click="approvePurchaseOrder('{{ $sugg->id }}')" class="px-4 py-2 rounded-xl bg-[#ea580c] hover:bg-[#c2410c] text-white font-bold text-xs shadow-sm transition cursor-pointer">
                                        Terbitkan PO Resmi
                                    </button>
                                    <button type="button" @click="ignorePurchaseOrder('{{ $sugg->id }}')" class="px-3.5 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold text-xs transition cursor-pointer">
                                        Abaikan
                                    </button>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-bold font-mono">
                                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        {{ $sugg->po_reference_number ?? 'PO TERBIT' }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                            <div class="bg-gray-50 p-3 rounded-2xl border border-gray-100">
                                <span class="text-[10px] text-gray-400 font-mono block">Rekomendasi Jumlah</span>
                                <span class="font-extrabold text-base text-[#ea580c] font-mono">{{ number_format($sugg->suggested_quantity, 1) }}</span>
                                <span class="text-[10px] text-gray-500">{{ $sugg->commodity?->unit ?? 'kg' }}</span>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-2xl border border-gray-100">
                                <span class="text-[10px] text-gray-400 font-mono block">Estimasi Valuasi</span>
                                <span class="font-extrabold text-base text-gray-900 font-mono">Rp {{ number_format($sugg->estimated_cost, 0, ',', '.') }}</span>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-2xl border border-gray-100">
                                <span class="text-[10px] text-gray-400 font-mono block">Wajib Pesan (Order Date)</span>
                                <span class="font-bold text-gray-800">{{ \Carbon\Carbon::parse($sugg->suggested_order_date)->translatedFormat('d M Y') }}</span>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-2xl border border-gray-100">
                                <span class="text-[10px] text-gray-400 font-mono block">Target Tiba di Gudang</span>
                                <span class="font-bold text-emerald-700">{{ \Carbon\Carbon::parse($sugg->target_arrival_date)->translatedFormat('d M Y') }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- ========================================================= -->
    <!-- TAB 4: SIMULATOR SKENARIO WHAT-IF                        -->
    <!-- ========================================================= -->
    <div x-show="activeTab === 'what-if'" class="space-y-6">
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm space-y-6">
            <div>
                <span class="text-xs font-mono font-bold text-[#ea580c] uppercase tracking-wider block">DYNAMIC SCENARIO MODELING</span>
                <h3 class="text-xl font-extrabold text-gray-900 mt-1">Simulator Skenario Fluktuasi &amp; Keterlambatan Pasokan</h3>
                <p class="text-xs text-gray-500 mt-0.5">Uji ketahanan inventori Anda jika pesanan UMKM melonjak tajam atau terjadi kendala transportasi dari produsen hulu.</p>
            </div>

            {{-- Slider Controls --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50/80 p-5 rounded-2xl border border-gray-200/60">
                <div class="space-y-2">
                    <div class="flex justify-between items-center text-xs font-bold">
                        <label class="text-gray-700">Lonjakan Permintaan Dapur UMKM:</label>
                        <span class="text-[#ea580c] font-mono text-sm" x-text="'+' + simDemandShock + '%'">+20%</span>
                    </div>
                    <input type="range" min="0" max="100" step="5" x-model="simDemandShock" @change="runWhatIfSimulation()"
                           class="w-full accent-[#ea580c] cursor-pointer" />
                    <p class="text-[10px] text-gray-400">Simulasikan lonjakan permintaan akhir pekan, musim libur, atau event besar kuliner.</p>
                </div>

                <div class="space-y-2">
                    <div class="flex justify-between items-center text-xs font-bold">
                        <label class="text-gray-700">Keterlambatan Pasokan Hulu (Lead Time Delay):</label>
                        <span class="text-blue-600 font-mono text-sm" x-text="'+' + simLeadTimeDelay + ' Hari'">+2 Hari</span>
                    </div>
                    <input type="range" min="0" max="7" step="1" x-model="simLeadTimeDelay" @change="runWhatIfSimulation()"
                           class="w-full accent-blue-600 cursor-pointer" />
                    <p class="text-[10px] text-gray-400">Simulasikan cuaca buruk atau hambatan logistik dari pihak petani/produsen.</p>
                </div>
            </div>

            {{-- Hasil Simulasi Realtime --}}
            <template x-if="simulationResult">
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-orange-50/60 p-4 rounded-2xl border border-orange-200">
                            <span class="text-[10px] font-mono uppercase font-bold text-orange-700 block">Tambahan Stok Pengaman yang Dibutuhkan</span>
                            <div class="text-2xl font-black text-[#ea580c] font-mono mt-1" x-text="'+' + simulationResult.total_additional_buffer + ' kg/unit'"></div>
                            <span class="text-[11px] text-gray-600 mt-1 block">Wajib ditambahkan ke Safety Stock agar terhindar dari stockout.</span>
                        </div>
                        <div class="bg-blue-50/60 p-4 rounded-2xl border border-blue-200">
                            <span class="text-[10px] font-mono uppercase font-bold text-blue-700 block">Estimasi Modal Kerja Tambahan (Working Capital)</span>
                            <div class="text-2xl font-black text-blue-700 font-mono mt-1" x-text="'Rp ' + Number(simulationResult.total_additional_capital).toLocaleString('id-ID')"></div>
                            <span class="text-[11px] text-gray-600 mt-1 block">Estimasi pencadangan kas untuk antisipasi lonjakan pembelian hulu.</span>
                        </div>
                    </div>

                    {{-- Tabel Hasil per Komoditas --}}
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-100 text-gray-500 uppercase font-mono text-[10px]">
                                <tr>
                                    <th class="p-3">Komoditas</th>
                                    <th class="p-3">Stok Saat Ini</th>
                                    <th class="p-3">Safety Stock Standar</th>
                                    <th class="p-3">Safety Stock Simulasi</th>
                                    <th class="p-3">Tambahan Buffer</th>
                                    <th class="p-3">Modal Ekstra</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="cb in simulationResult.commodity_breakdowns" :key="cb.commodity_name">
                                    <tr class="hover:bg-gray-50/50">
                                        <td class="p-3 font-bold text-gray-900" x-text="cb.commodity_name"></td>
                                        <td class="p-3 font-mono" x-text="cb.current_stock + ' ' + cb.unit"></td>
                                        <td class="p-3 font-mono text-gray-500" x-text="cb.current_safety_stock + ' ' + cb.unit"></td>
                                        <td class="p-3 font-mono font-bold text-orange-600" x-text="cb.simulated_safety_stock + ' ' + cb.unit"></td>
                                        <td class="p-3 font-mono font-bold text-rose-600" x-text="'+' + cb.delta_buffer_needed + ' ' + cb.unit"></td>
                                        <td class="p-3 font-mono font-bold text-gray-800" x-text="'Rp ' + Number(cb.additional_capital).toLocaleString('id-ID')"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- TAB 5: COLLABORATIVE SUPPLY PLANS (RESPON KUOTA DARI UMKM)-->
    <!-- ========================================================= -->
    <div x-show="activeTab === 'collaborative'" class="space-y-6">
        {{-- SECTION: AGREGASI PERENCANAAN PASOKAN SEBAGAI DASAR PANEN / PENGADAAN --}}
        <div class="bg-white p-6 sm:p-7 rounded-3xl border border-gray-100 shadow-sm space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 pb-4">
                <div>
                    <span class="text-[10px] font-mono font-bold uppercase text-[#ea580c] tracking-wider block">REKAPITULASI KEBUTUHAN MITRA</span>
                    <h2 class="text-lg font-bold text-gray-900">Dasar Perencanaan Pasokan &amp; Kuota Panen Mendatang</h2>
                    <p class="text-xs text-gray-500">Gunakan data agregat ini untuk merencanakan siklus panen, pengadaan stok, atau alokasi kuota gudang sebelum periode dimulai.</p>
                </div>
                <span class="text-xs font-mono font-bold text-gray-600 bg-gray-50 border border-gray-200 px-3.5 py-1.5 rounded-full self-start sm:self-auto">
                    {{ count($commodityAggregations) }} Kelompok Komoditas
                </span>
            </div>

            @if($commodityAggregations->isEmpty())
                <div class="py-12 text-center space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-gray-50 border border-gray-200 text-gray-400 flex items-center justify-center mx-auto">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <p class="text-xs font-semibold text-gray-600">Belum ada estimasi kebutuhan terakumulasi dari UMKM mitra untuk periode mendatang.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($commodityAggregations as $agg)
                        @php
                            $fulfillmentPercent = $agg['total_estimated'] > 0
                                ? min(100, round(($agg['total_confirmed'] / $agg['total_estimated']) * 100))
                                : 0;
                        @endphp
                        <div class="p-5 rounded-2xl bg-[#fafafa] hover:bg-white border border-gray-200/80 hover:border-orange-200 hover:shadow-md transition-all space-y-4">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span class="inline-flex items-center gap-1.5 bg-white border border-gray-200 text-gray-700 px-2.5 py-1 rounded-lg text-[10px] font-mono font-bold">
                                        <svg class="w-3 h-3 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        {{ $agg['period'] }}
                                    </span>
                                    <h4 class="font-extrabold text-sm text-gray-900 mt-2">{{ $agg['commodity'] }}</h4>
                                </div>
                                <span class="text-[11px] font-mono font-bold px-2.5 py-1 rounded-full shrink-0 {{ $agg['pending_items'] > 0 ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                                    {{ $agg['pending_items'] > 0 ? $agg['pending_items'].' Pending' : 'Semua Siap' }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div class="bg-white p-3 rounded-xl border border-gray-100">
                                    <span class="text-[10px] text-gray-400 block font-mono">Total Kebutuhan</span>
                                    <span class="font-extrabold text-gray-900 text-sm">
                                        {{ number_format($agg['total_estimated'], 0, ',', '.') }} {{ $agg['unit'] }}
                                    </span>
                                </div>
                                <div class="bg-white p-3 rounded-xl border border-gray-100">
                                    <span class="text-[10px] text-gray-400 block font-mono">Disanggupi Pasok</span>
                                    <span class="font-extrabold text-emerald-600 text-sm">
                                        {{ number_format($agg['total_confirmed'], 0, ',', '.') }} {{ $agg['unit'] }}
                                    </span>
                                </div>
                            </div>

                            {{-- Progress bar kesiapan --}}
                            <div class="space-y-1.5 pt-1">
                                <div class="flex justify-between text-[11px] font-semibold text-gray-600">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        {{ $agg['umkm_count'] }} Mitra UMKM
                                    </span>
                                    <span class="font-mono font-bold {{ $fulfillmentPercent >= 100 ? 'text-emerald-600' : 'text-orange-600' }}">{{ $fulfillmentPercent }}%</span>
                                </div>
                                <div class="w-full bg-gray-200/80 rounded-full h-2.5 overflow-hidden p-0.5">
                                    <div class="h-full rounded-full transition-all duration-500 {{ $fulfillmentPercent >= 100 ? 'bg-emerald-500' : 'bg-[#ea580c]' }}" style="width: {{ $fulfillmentPercent }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- SECTION: DAFTAR DETAIL PENGAJUAN ESTIMASI DARI UMKM --}}
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 sm:p-7 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-100 pb-4">
                <div>
                    <h3 class="font-bold text-lg text-gray-900">Daftar Detail Estimasi Kebutuhan Masuk dari Dapur UMKM</h3>
                    <p class="text-xs text-gray-500">Tinjau dan tentukan kesiapan pasokan panen Anda untuk masing-masing permintaan UMKM.</p>
                </div>

                {{-- Filter Controls --}}
                <form action="{{ route('supplier.supply-planning.index') }}" method="GET" class="flex flex-wrap items-center gap-2 text-xs">
                    <input type="hidden" name="tab" value="collaborative">
                    <select name="status" onchange="this.form.submit()" class="p-2.5 rounded-xl bg-gray-50 text-gray-800 font-semibold border border-gray-200 focus:ring-2 focus:ring-[#ea580c] focus:outline-none">
                        <option value="semua" {{ $statusFilter === 'semua' || !$statusFilter ? 'selected' : '' }}>Semua Status</option>
                        <option value="MENUNGGU_KONFIRMASI" {{ $statusFilter === 'MENUNGGU_KONFIRMASI' ? 'selected' : '' }}>Menunggu Konfirmasi</option>
                        <option value="DISETUJUI" {{ $statusFilter === 'DISETUJUI' ? 'selected' : '' }}>Disetujui Penuh</option>
                        <option value="DISESUAIKAN" {{ $statusFilter === 'DISESUAIKAN' ? 'selected' : '' }}>Disesuaikan</option>
                        <option value="DITOLAK" {{ $statusFilter === 'DITOLAK' ? 'selected' : '' }}>Ditolak / Penuh</option>
                    </select>

                    @if($availablePeriods->isNotEmpty())
                        <select name="period" onchange="this.form.submit()" class="p-2.5 rounded-xl bg-gray-50 text-gray-800 font-semibold border border-gray-200 focus:ring-2 focus:ring-[#ea580c] focus:outline-none">
                            <option value="semua" {{ $periodFilter === 'semua' || !$periodFilter ? 'selected' : '' }}>Semua Periode</option>
                            @foreach($availablePeriods as $per)
                                <option value="{{ $per }}" {{ $periodFilter === $per ? 'selected' : '' }}>{{ $per }}</option>
                            @endforeach
                        </select>
                    @endif
                </form>
            </div>

            @if($allPlans->isEmpty())
                <div class="py-16 text-center space-y-3">
                    <div class="w-14 h-14 rounded-2xl bg-orange-50 text-orange-600 border border-orange-100 flex items-center justify-center mx-auto">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h4 class="font-bold text-gray-900 text-sm">Tidak Ada Data Estimasi Kebutuhan</h4>
                    <p class="text-xs text-gray-500 max-w-sm mx-auto">
                        @if($statusFilter && $statusFilter !== 'semua')
                            Tidak ditemukan estimasi kebutuhan dengan filter status tersebut.
                        @else
                            Belum ada UMKM mitra yang mengajukan estimasi Collaborative Supply Planning pada periode ini.
                        @endif
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50 text-gray-400 uppercase font-mono text-[10px]">
                            <tr>
                                <th class="p-4 rounded-l-2xl">Mitra UMKM</th>
                                <th class="p-4">Komoditas Bahan Baku</th>
                                <th class="p-4">Periode Target</th>
                                <th class="p-4">Estimasi Dapur</th>
                                <th class="p-4">Disetujui Pemasok</th>
                                <th class="p-4">Status Respon</th>
                                <th class="p-4 rounded-r-2xl text-right">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($allPlans as $plan)
                                <tr class="hover:bg-gray-50/50 transition">
                                    <td class="p-4 font-bold text-gray-900">
                                        {{ $plan->partnership?->umkm?->business_name ?? 'UMKM Mitra' }}
                                    </td>
                                    <td class="p-4 font-semibold text-gray-800">
                                        {{ $plan->commodity_name }}
                                    </td>
                                    <td class="p-4 font-mono font-medium text-gray-600">
                                        {{ $plan->period_month_year }}
                                    </td>
                                    <td class="p-4 font-mono font-bold text-[#ea580c]">
                                        {{ number_format($plan->estimated_quantity, 1) }} {{ $plan->unit }}
                                    </td>
                                    <td class="p-4 font-mono font-bold text-emerald-600">
                                        {{ $plan->confirmed_quantity ? number_format($plan->confirmed_quantity, 1) . ' ' . $plan->unit : '-' }}
                                    </td>
                                    <td class="p-4">
                                        @if($plan->supplier_confirmation_status === 'DISETUJUI')
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                                Disetujui
                                            </span>
                                        @elseif($plan->supplier_confirmation_status === 'DISESUAIKAN')
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
                                                Disesuaikan
                                            </span>
                                        @elseif($plan->supplier_confirmation_status === 'DITOLAK')
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">
                                                Ditolak
                                            </span>
                                        @else
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 animate-pulse">
                                                Menunggu Respon
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button"
                                                    @click="openResponseModal({{ json_encode($plan) }}, 'terima_penuh')"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white font-bold text-[11px] transition shadow-xs cursor-pointer"
                                                    title="Terima 100% Sesuai Kebutuhan">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                                <span>Siap</span>
                                            </button>

                                            <button type="button"
                                                    @click="openResponseModal({{ json_encode($plan) }}, 'sesuaikan')"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-blue-50 text-blue-700 hover:bg-blue-600 hover:text-white font-bold text-[11px] transition shadow-xs cursor-pointer"
                                                    title="Sesuaikan Kapasitas Pasok">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                                <span>Sesuaikan</span>
                                            </button>

                                            <button type="button"
                                                    @click="openResponseModal({{ json_encode($plan) }}, 'tolak')"
                                                    class="p-1.5 rounded-xl bg-gray-100 text-gray-500 hover:bg-rose-50 hover:text-rose-600 font-bold text-[11px] transition cursor-pointer"
                                                    title="Tolak / Kapasitas Belum Ada">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($allPlans->hasPages())
                    <div class="p-4 border-t border-gray-100">
                        {{ $allPlans->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>

    {{-- MODAL UPDATE STOK GUDANG --}}
    <div x-cloak x-show="openInventoryModal" class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="openInventoryModal = false" class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-100 space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h3 class="font-bold text-base text-gray-900">Perbarui Stok Fisik Gudang</h3>
                <button type="button" @click="openInventoryModal = false" class="text-gray-400 hover:text-gray-600 text-lg font-bold cursor-pointer">✕</button>
            </div>

            <template x-if="editingInventory">
                <div class="space-y-4 text-xs">
                    <div class="bg-gray-50 p-3 rounded-2xl">
                        <span class="text-gray-400 block font-mono">Bahan Baku:</span>
                        <span class="font-extrabold text-gray-900 text-sm" x-text="editingInventory.commodity?.name"></span>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-gray-700 font-bold">Stok Fisik Tersedia Saat Ini (<span x-text="editingInventory.unit"></span>):</label>
                        <input type="number" step="0.1" min="0" x-model="editingStockQty" class="w-full p-3 rounded-2xl bg-[#f1f2f4] text-gray-800 font-mono font-bold text-base border-0 focus:ring-2 focus:ring-[#ea580c]" />
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                        <button type="button" @click="openInventoryModal = false" class="px-5 py-2.5 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold transition cursor-pointer">
                            Batal
                        </button>
                        <button type="button" @click="submitUpdateStock()" :disabled="isUpdatingStock" class="px-6 py-2.5 rounded-full bg-[#ea580c] hover:bg-[#c2410c] text-white font-bold shadow-sm transition cursor-pointer">
                            <span x-text="isUpdatingStock ? 'Menyimpan...' : 'Simpan Stok'">Simpan Stok</span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- MODAL RESPON PEMASOK UNTUK SUPPLY PLAN UMKM --}}
    <div x-cloak x-show="openModal" class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="openModal = false" class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-gray-100 space-y-5 animate-in fade-in zoom-in-95 duration-200">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-orange-50 border border-orange-100 text-orange-600 flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-gray-900">Respon Estimasi Pasokan UMKM</h3>
                        <p class="text-xs text-gray-500 font-mono" x-text="selectedPlan ? 'CSP-' + selectedPlan.id.substring(0,8).toUpperCase() : ''"></p>
                    </div>
                </div>
                <button type="button" @click="openModal = false" class="text-gray-400 hover:text-gray-600 text-lg font-bold p-1 cursor-pointer">✕</button>
            </div>

            <template x-if="selectedPlan">
                <form :action="'{{ url('/supplier/supply-planning') }}/' + selectedPlan.id + '/konfirmasi'" method="POST" class="space-y-4 text-xs font-semibold">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="action" :value="actionType">

                    {{-- Summary info --}}
                    <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100 space-y-2">
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-gray-500">Komoditas Bahan Baku:</span>
                            <span class="font-extrabold text-gray-900" x-text="selectedPlan.commodity_name"></span>
                        </div>
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-gray-500">Permintaan Estimasi Dapur:</span>
                            <span class="font-extrabold text-[#ea580c]" x-text="selectedPlan.estimated_quantity + ' ' + selectedPlan.unit"></span>
                        </div>
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-gray-500">Periode Target:</span>
                            <span class="font-bold text-gray-900" x-text="selectedPlan.period_month_year"></span>
                        </div>
                    </div>

                    {{-- Action Mode Selector --}}
                    <div>
                        <label class="block mb-1.5 text-gray-700">Keputusan Kesiapan Pasok:</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button" @click="actionType = 'terima_penuh'; supplierNotes = 'Kapasitas siap dipenuhi 100% sesuai estimasi kebutuhan dapur.'"
                                    :class="actionType === 'terima_penuh' ? 'bg-emerald-600 text-white font-bold shadow-sm' : 'bg-gray-50 text-gray-700 border border-gray-200 hover:bg-gray-100'"
                                    class="p-2.5 rounded-xl transition text-center cursor-pointer flex items-center justify-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                <span>Siap Penuh</span>
                            </button>
                            <button type="button" @click="actionType = 'sesuaikan'; supplierNotes = 'Kapasitas pasokan disesuaikan dengan kuota panen/gudang yang tersedia.'"
                                    :class="actionType === 'sesuaikan' ? 'bg-blue-600 text-white font-bold shadow-sm' : 'bg-gray-50 text-gray-700 border border-gray-200 hover:bg-gray-100'"
                                    class="p-2.5 rounded-xl transition text-center cursor-pointer flex items-center justify-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                <span>Sesuaikan</span>
                            </button>
                            <button type="button" @click="actionType = 'tolak'; supplierNotes = 'Mohon maaf, kapasitas pasokan kami pada periode ini belum mencukupi.'"
                                    :class="actionType === 'tolak' ? 'bg-rose-600 text-white font-bold shadow-sm' : 'bg-gray-50 text-gray-700 border border-gray-200 hover:bg-gray-100'"
                                    class="p-2.5 rounded-xl transition text-center cursor-pointer flex items-center justify-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                                <span>Tolak</span>
                            </button>
                        </div>
                    </div>

                    {{-- Input Kuota Disesuaikan --}}
                    <div x-show="actionType === 'sesuaikan'" class="space-y-1.5">
                        <label class="block text-gray-700">Kuantitas Pasok yang Mampu Disanggupi (<span x-text="selectedPlan.unit"></span>):</label>
                        <input type="number" step="0.1" min="0.1" name="confirmed_quantity" x-model="confirmedQty" class="w-full p-3 rounded-2xl bg-[#f1f2f4] text-gray-800 font-bold border-0 focus:ring-2 focus:ring-[#ea580c]" />
                        <p class="text-[10px] text-gray-400">Masukkan jumlah kapasitas panen riil yang dapat dialokasikan untuk UMKM ini.</p>
                    </div>

                    {{-- Input Catatan Respon --}}
                    <div class="space-y-1.5">
                        <label class="block text-gray-700">Catatan Konfirmasi Kesiapan untuk Dapur UMKM:</label>
                        <textarea name="supplier_notes" x-model="supplierNotes" rows="3" class="w-full p-3 rounded-2xl bg-[#f1f2f4] text-gray-800 font-medium border-0 focus:ring-2 focus:ring-[#ea580c]"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                        <button type="button" @click="openModal = false" class="px-5 py-2.5 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="px-6 py-2.5 rounded-full bg-[#ea580c] hover:bg-[#c2410c] text-white font-bold shadow-sm transition cursor-pointer">
                            Simpan Respon Pasokan
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>

</div>
@endsection
