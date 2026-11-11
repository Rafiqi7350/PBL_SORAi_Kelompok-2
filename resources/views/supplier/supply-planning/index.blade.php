@extends('layouts.supplier')

@section('title', 'Supply Planning & Prediksi Permintaan — Portal Pemasok SORAI')

@section('content')
<script>
function supplierDemandPlanningPage() {
    return {
        activeTab: (new URLSearchParams(window.location.search).get('tab') || 'forecast'),
        forecastHorizon: '{{ $latestForecastRun?->forecast_horizon ?? '7_DAYS' }}',
        isRecalculating: false,
        notificationMsg: '',
        searchCommodityQuery: '',

        // Data arrays passed safely in JS literal
        inventoryItemsData: @json($inventoryItems),
        allPlansData: @json($allPlans->items()),

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

        setTab(tab) {
            this.activeTab = tab;
            try {
                const url = new URL(window.location);
                url.searchParams.set('tab', tab);
                window.history.replaceState({}, '', url);
            } catch(e) {}
        },

        openResponseModal(planId, action) {
            var plan = this.allPlansData.find(function(p) { return p.id === planId; });
            if (!plan) return;
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

        openStockModal(invId) {
            var inv = this.inventoryItemsData.find(function(i) { return i.id === invId; });
            if (!inv) return;
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
    };
}
</script>

<div class="space-y-7" x-data="supplierDemandPlanningPage()">

    {{-- Toast Notification Floating --}}
    <div x-cloak x-show="notificationMsg" x-transition
         class="fixed top-6 right-6 z-50 bg-slate-900 text-white px-4 py-3 rounded-xl shadow-xl flex items-center gap-2.5 border border-slate-700 text-xs font-semibold tabular-nums">
        <span class="w-2 h-2 rounded-full bg-emerald-400 shrink-0"></span>
        <span x-text="notificationMsg"></span>
    </div>

    {{-- Header Page --}}
    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4 border-b border-slate-200/80 pb-5">
        <div class="space-y-1">
            <span class="text-[11px] font-bold uppercase text-[#ea580c] tracking-[0.14em] block">
                MODUL LOGISTIK &amp; COLLABORATIVE SUPPLY PLANNING
            </span>
            <h1 class="text-2xl sm:text-[26px] font-bold text-slate-900 tracking-tight uppercase">
                Perencanaan Persediaan &amp; Prediksi Permintaan
            </h1>
            <p class="text-xs sm:text-[13px] text-slate-500 max-w-2xl leading-relaxed">
                Agregasi kebutuhan riil UMKM, batas cadangan pengaman (Safety Stock), sistem peringatan dini kehabisan stok, serta rekomendasi otomatis Purchase Order ke petani/produsen hulu.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <button type="button"
                    @click="setTab('collaborative')"
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-full text-xs font-semibold bg-white text-slate-700 border border-slate-200 hover:bg-slate-50 transition shadow-2xs tabular-nums cursor-pointer">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>Kapasitas Disetujui: {{ number_format($totalConfirmedVolume, 0, ',', '.') }}</span>
            </button>

            <button type="button"
                    @click="recalculateForecast(forecastHorizon)"
                    :disabled="isRecalculating"
                    class="px-4 py-2.5 rounded-xl bg-[#ea580c] hover:bg-[#c2410c] text-white font-semibold text-xs shadow-2xs transition inline-flex items-center gap-2 cursor-pointer disabled:opacity-75">
                <svg :class="isRecalculating ? 'animate-spin' : ''" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span x-text="isRecalculating ? 'Menghitung...' : 'Kalkulasi Ulang Realtime'">Kalkulasi Ulang Realtime</span>
            </button>
        </div>
    </div>

    {{-- 4 Metric KPI Cards Utama --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        {{-- Card 1: Total Proyeksi Demand --}}
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)] hover:border-slate-300/80 transition flex flex-col justify-between gap-4">
            <div class="flex items-start justify-between gap-3">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Proyeksi 7 Hari</span>
                <div class="w-10 h-10 rounded-xl bg-orange-50 text-[#ea580c] border border-orange-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
            </div>
            <div class="space-y-1">
                <h3 class="text-2xl font-bold text-[#ea580c] tracking-tight tabular-nums">
                    {{ number_format($totalProjectedDemand7Days, 1, ',', '.') }} <span class="text-xs font-semibold text-slate-500">kg/unit</span>
                </h3>
                <p class="text-[11px] text-slate-500 font-medium">Agregasi kebutuhan dapur seluruh UMKM</p>
            </div>
        </div>

        {{-- Card 2: Status Buffer Gudang --}}
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)] hover:border-slate-300/80 transition flex flex-col justify-between gap-4">
            <div class="flex items-start justify-between gap-3">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Peringatan Stok (EWS)</span>
                <div class="w-10 h-10 rounded-xl {{ $criticalInventoryCount > 0 ? 'bg-rose-50 text-rose-600 border-rose-200' : ($warningInventoryCount > 0 ? 'bg-amber-50 text-amber-600 border-amber-200' : 'bg-emerald-50 text-emerald-600 border-emerald-100') }} border flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>
            <div class="space-y-1">
                <h3 class="text-2xl font-bold {{ $criticalInventoryCount > 0 ? 'text-rose-600' : ($warningInventoryCount > 0 ? 'text-amber-600' : 'text-emerald-600') }} tracking-tight tabular-nums">
                    {{ $criticalInventoryCount }} <span class="text-xs font-semibold text-slate-500">Kritis / {{ $warningInventoryCount }} Siaga</span>
                </h3>
                <p class="text-[11px] text-slate-500 font-medium">Bahan baku di bawah batas aman</p>
            </div>
        </div>

        {{-- Card 3: Saran Purchase Order --}}
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)] hover:border-slate-300/80 transition flex flex-col justify-between gap-4">
            <div class="flex items-start justify-between gap-3">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Saran Order Pengadaan</span>
                <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 border border-sky-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                </div>
            </div>
            <div class="space-y-1">
                <h3 class="text-2xl font-bold text-sky-700 tracking-tight tabular-nums">
                    {{ $purchaseSuggestions->where('status', 'PENDING_REVIEW')->count() }} <span class="text-xs font-semibold text-slate-500">PO Tertunda</span>
                </h3>
                <p class="text-[11px] text-slate-500 font-medium">Siap diterbitkan ke produsen hulu</p>
            </div>
        </div>

        {{-- Card 4: Model Accuracy MAPE --}}
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)] hover:border-slate-300/80 transition flex flex-col justify-between gap-4">
            <div class="flex items-start justify-between gap-3">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Akurasi Algoritma</span>
                <div class="w-10 h-10 rounded-xl bg-violet-50 text-violet-700 border border-violet-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
            </div>
            <div class="space-y-1">
                <h3 class="text-2xl font-bold text-violet-700 tracking-tight tabular-nums">
                    {{ number_format(100 - (float) ($latestForecastRun?->model_accuracy_mape ?? 8.4), 1) }}%
                </h3>
                <p class="text-[11px] text-slate-500 font-medium">Model Hybrid: Kontrak + CSP + Historis</p>
            </div>
        </div>
    </div>

    {{-- TAB NAVIGATION (5 Modul Utama) --}}
    <div class="flex items-center gap-2 overflow-x-auto pb-3 border-b border-slate-200/80">
        <button type="button" @click="setTab('forecast')"
                :class="activeTab === 'forecast' ? 'bg-[#ea580c] text-white shadow-2xs' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'"
                class="px-4 py-2 rounded-full text-xs font-semibold transition inline-flex items-center gap-2 shrink-0 cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
            </svg>
            <span>Proyeksi Permintaan 7–90 Hari</span>
        </button>

        <button type="button" @click="setTab('inventory')"
                :class="activeTab === 'inventory' ? 'bg-[#ea580c] text-white shadow-2xs' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'"
                class="px-4 py-2 rounded-full text-xs font-semibold transition inline-flex items-center gap-2 shrink-0 cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <span>Kontrol Stok &amp; EWS</span>
            @if($criticalInventoryCount > 0)
                <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold tabular-nums"
                      :class="activeTab === 'inventory' ? 'bg-white text-[#ea580c]' : 'bg-rose-600 text-white'">{{ $criticalInventoryCount }}</span>
            @endif
        </button>

        <button type="button" @click="setTab('purchase-orders')"
                :class="activeTab === 'purchase-orders' ? 'bg-[#ea580c] text-white shadow-2xs' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'"
                class="px-4 py-2 rounded-full text-xs font-semibold transition inline-flex items-center gap-2 shrink-0 cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <span>Saran Pengadaan (PO Otomatis)</span>
            @if($purchaseSuggestions->where('status', 'PENDING_REVIEW')->count() > 0)
                <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold tabular-nums"
                      :class="activeTab === 'purchase-orders' ? 'bg-white text-[#ea580c]' : 'bg-sky-600 text-white'">{{ $purchaseSuggestions->where('status', 'PENDING_REVIEW')->count() }}</span>
            @endif
        </button>

        <button type="button" @click="setTab('what-if')"
                :class="activeTab === 'what-if' ? 'bg-[#ea580c] text-white shadow-2xs' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'"
                class="px-4 py-2 rounded-full text-xs font-semibold transition inline-flex items-center gap-2 shrink-0 cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <span>Simulator Skenario What-If</span>
        </button>

        <button type="button" @click="setTab('collaborative')"
                :class="activeTab === 'collaborative' ? 'bg-[#ea580c] text-white shadow-2xs' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'"
                class="px-4 py-2 rounded-full text-xs font-semibold transition inline-flex items-center gap-2 shrink-0 cursor-pointer tabular-nums">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <span>Respon Kuota Dapur UMKM ({{ $pendingCount }})</span>
        </button>
    </div>

    {{-- ========================================================= --}}
    {{-- TAB 1: PROYEKSI & GRAFIK KEBUTUHAN PERMINTAAN             --}}
    {{-- ========================================================= --}}
    <div x-show="activeTab === 'forecast'" class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
            <div>
                <span class="text-[11px] font-bold text-[#ea580c] uppercase tracking-wider block">Multi-Tenant Demand Aggregation</span>
                <h2 class="text-lg font-bold text-slate-900 mt-1">Grafik Timeline Proyeksi Permintaan Bahan Baku</h2>
                <p class="text-xs text-slate-500 mt-0.5">Rentang keyakinan 95% memperhitungkan lonjakan akhir pekan dapur UMKM dan kontrak jadwal kirim.</p>
            </div>

            {{-- Horizon Selector --}}
            <div class="inline-flex items-center gap-1 p-1 bg-slate-100 rounded-xl self-start sm:self-auto shrink-0">
                <button type="button" @click="recalculateForecast('7_DAYS')"
                        :class="forecastHorizon === '7_DAYS' ? 'bg-white text-slate-900 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900 font-semibold'"
                        class="px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer">
                    7 Hari (Harian)
                </button>
                <button type="button" @click="recalculateForecast('30_DAYS')"
                        :class="forecastHorizon === '30_DAYS' ? 'bg-white text-slate-900 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900 font-semibold'"
                        class="px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer">
                    30 Hari
                </button>
                <button type="button" @click="recalculateForecast('90_DAYS')"
                        :class="forecastHorizon === '90_DAYS' ? 'bg-white text-slate-900 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900 font-semibold'"
                        class="px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer">
                    90 Hari (Kuartal)
                </button>
            </div>
        </div>

        {{-- Grid Timeline Kebutuhan Harian per Komoditas --}}
        @php
            $forecastByCommodity = $forecastItems->groupBy('commodity_id');
        @endphp

        <div class="grid grid-cols-1 gap-5">
            @foreach($forecastByCommodity as $commId => $items)
                @php
                    $comm = $items->first()?->commodity;
                    $totalCommodityProjected = $items->sum('baseline_demand');
                    $avgDaily = $items->count() > 0 ? $totalCommodityProjected / $items->count() : 0;
                    $maxDemandItem = $items->sortByDesc('baseline_demand')->first();
                @endphp
                <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)] space-y-5">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 rounded-xl bg-orange-50 text-[#ea580c] font-bold flex items-center justify-center text-base border border-orange-100 shrink-0">
                                {{ substr($comm?->name ?? 'B', 0, 1) }}
                            </div>
                            <div>
                                <h3 class="font-bold text-base text-slate-900">{{ $comm?->name ?? 'Komoditas Pasokan' }}</h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Satuan: <strong class="text-slate-800">{{ $comm?->unit ?? 'kg' }}</strong>
                                    &bull;
                                    Rerata: <strong class="text-slate-800 tabular-nums">{{ number_format($avgDaily, 1) }} {{ $comm?->unit ?? 'kg' }}/hari</strong>
                                </p>
                            </div>
                        </div>

                        <div class="sm:text-right">
                            <span class="text-slate-500 text-[10px] font-bold uppercase tracking-wider block">Total Kebutuhan Periode</span>
                            <span class="text-xl font-bold text-[#ea580c] tabular-nums">{{ number_format($totalCommodityProjected, 1) }} {{ $comm?->unit ?? 'kg' }}</span>
                        </div>
                    </div>

                    {{-- Visual Bar Chart Timeline Sederhana (Harian) --}}
                    <div class="space-y-2.5">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Proyeksi Demand Harian (Baseline vs Batas Atas 95%)</span>
                        <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-2.5">
                            @foreach($items->take(7) as $fi)
                                @php
                                    $d = \Carbon\Carbon::parse($fi->forecast_date);
                                    $isWeekend = in_array($d->dayOfWeek, [0, 5, 6]);
                                    $pct = $maxDemandItem && $maxDemandItem->baseline_demand > 0
                                        ? round(($fi->baseline_demand / $maxDemandItem->baseline_demand) * 100)
                                        : 50;
                                @endphp
                                <div class="p-3 rounded-xl border space-y-2 text-center {{ $isWeekend ? 'border-orange-200 bg-orange-50/40' : 'border-slate-200/70 bg-slate-50/70' }}">
                                    <div class="text-[11px] font-semibold {{ $isWeekend ? 'text-[#c2410c]' : 'text-slate-700' }}">
                                        {{ $d->translatedFormat('D, d M') }}
                                    </div>
                                    <div class="text-base font-bold text-slate-900 tabular-nums">
                                        {{ number_format($fi->baseline_demand, 0) }}
                                    </div>
                                    <div class="w-full bg-slate-200/80 h-1.5 rounded-full overflow-hidden">
                                        <div class="bg-[#ea580c] h-1.5 rounded-full" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <div class="text-[10px] text-slate-500 font-medium tabular-nums">
                                        Maks: {{ number_format($fi->upper_bound_95, 0) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Matriks Breakdown Kontribusi Demand per UMKM --}}
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)] space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-4">
                <div>
                    <h3 class="font-bold text-base text-slate-900">Kontribusi Permintaan per UMKM Terkontrak</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Pemetaan alokasi volume pasokan berdasarkan jadwal kemitraan resmi.</p>
                </div>
                <span class="text-xs font-semibold text-slate-600 bg-slate-100 border border-slate-200/70 px-3 py-1 rounded-full self-start sm:self-auto tabular-nums">
                    {{ count($umkmBreakdowns) }} Mitra Terdaftar
                </span>
            </div>

            @if(empty($umkmBreakdowns) || count($umkmBreakdowns) === 0)
                <p class="text-xs text-slate-400 text-center py-6">Belum ada kemitraan terjadwal aktif.</p>
            @else
                <div class="overflow-x-auto border border-slate-200/80 rounded-xl">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 uppercase font-bold text-[11px] border-b border-slate-200/80 tracking-wider">
                            <tr>
                                <th class="py-3 px-4">Nama UMKM</th>
                                <th class="py-3 px-4">Komoditas Bahan</th>
                                <th class="py-3 px-4">Volume Kirim</th>
                                <th class="py-3 px-4">Status Kontrak</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($umkmBreakdowns as $ub)
                                <tr class="hover:bg-orange-50/30 transition-colors">
                                    <td class="py-3.5 px-4 font-semibold text-slate-900">{{ $ub['umkm_name'] }}</td>
                                    <td class="py-3.5 px-4 text-slate-600">{{ $ub['commodity_name'] }}</td>
                                    <td class="py-3.5 px-4 font-bold text-[#ea580c] tabular-nums">{{ number_format($ub['total_scheduled_volume'], 1) }} {{ $ub['unit'] }}/jadwal</td>
                                    <td class="py-3.5 px-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/80">
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

    {{-- ========================================================= --}}
    {{-- TAB 2: KONTROL INVENTORI & EARLY WARNING SYSTEM (EWS)     --}}
    {{-- ========================================================= --}}
    <div x-show="activeTab === 'inventory'" class="space-y-6">
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
            <span class="text-[11px] font-bold text-[#ea580c] uppercase tracking-wider block">Early Warning &amp; Buffer Stock</span>
            <h2 class="text-lg font-bold text-slate-900 mt-1">Status Fisik Gudang vs Safety Stock &amp; Reorder Point</h2>
            <p class="text-xs text-slate-500 mt-0.5">Sistem otomatis memberi peringatan siaga saat stok menyentuh ROP untuk mencegah kehabisan stok (stockout).</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($inventoryItems as $inv)
                @php
                    $isCritical = $inv->ews_status === 'CRITICAL';
                    $isWarning = $inv->ews_status === 'WARNING';
                @endphp
                <div class="bg-white rounded-2xl p-6 border {{ $isCritical ? 'border-rose-300 ring-1 ring-rose-200' : ($isWarning ? 'border-amber-200' : 'border-slate-200/80') }} shadow-[0_1px_2px_rgba(15,23,42,0.04)] space-y-4">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $inv->ews_badge_color }}">
                                {{ $inv->ews_label }}
                            </span>
                            <h3 class="font-bold text-base text-slate-900 mt-2">{{ $inv->commodity?->name ?? 'Komoditas' }}</h3>
                        </div>
                        <button type="button" @click="openStockModal('{{ $inv->id }}')" class="text-xs font-semibold text-[#ea580c] hover:text-[#c2410c] hover:underline cursor-pointer shrink-0">
                            Update Stok
                        </button>
                    </div>

                    <div class="grid grid-cols-3 gap-2 text-center text-xs tabular-nums">
                        <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-200/70">
                            <span class="text-[10px] text-slate-500 font-semibold block">Stok Gudang</span>
                            <span class="font-bold text-slate-900 text-sm">{{ number_format($inv->current_stock, 1) }}</span>
                            <span class="text-[10px] text-slate-500 block">{{ $inv->unit }}</span>
                        </div>
                        <div class="bg-amber-50/60 p-2.5 rounded-xl border border-amber-200/70">
                            <span class="text-[10px] text-amber-700 font-semibold block">Reorder Point</span>
                            <span class="font-bold text-amber-900 text-sm">{{ number_format($inv->reorder_point, 1) }}</span>
                            <span class="text-[10px] text-amber-700 block">{{ $inv->unit }}</span>
                        </div>
                        <div class="bg-rose-50/60 p-2.5 rounded-xl border border-rose-200/70">
                            <span class="text-[10px] text-rose-700 font-semibold block">Safety Stock</span>
                            <span class="font-bold text-rose-900 text-sm">{{ number_format($inv->safety_stock, 1) }}</span>
                            <span class="text-[10px] text-rose-700 block">{{ $inv->unit }}</span>
                        </div>
                    </div>

                    {{-- Visual Progress Meter --}}
                    @php
                        $stockVal = (float) $inv->current_stock;
                        $ropVal = max(1.0, (float) $inv->reorder_point);
                        $meterPct = min(100, round(($stockVal / ($ropVal * 1.5)) * 100));
                    @endphp
                    <div class="space-y-1.5 pt-1">
                        <div class="flex justify-between text-[11px] font-semibold text-slate-500">
                            <span>Kecukupan Stok</span>
                            <span class="font-bold {{ $isCritical ? 'text-rose-600' : ($isWarning ? 'text-amber-600' : 'text-emerald-600') }}">
                                {{ $isCritical ? 'Kritis' : ($isWarning ? 'Siaga Kulakan' : 'Aman') }}
                            </span>
                        </div>
                        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                            <div class="h-2 rounded-full transition-all duration-500 {{ $isCritical ? 'bg-rose-500' : ($isWarning ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $meterPct }}%"></div>
                        </div>
                    </div>

                    <div class="text-[11px] text-slate-500 flex justify-between pt-2.5 border-t border-slate-100 tabular-nums">
                        <span>Restock Terakhir:</span>
                        <span class="font-medium text-slate-700">{{ $inv->last_restocked_at ? $inv->last_restocked_at->translatedFormat('d M Y') : 'Baru' }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- TAB 3: REKOMENDASI PEMESANAN (PURCHASE ORDER ENGINE)      --}}
    {{-- ========================================================= --}}
    <div x-show="activeTab === 'purchase-orders'" class="space-y-6">
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
            <span class="text-[11px] font-bold text-[#ea580c] uppercase tracking-wider block">Automated Procurement Recommendation</span>
            <h2 class="text-lg font-bold text-slate-900 mt-1">Rekomendasi Pemesanan Bahan Baku ke Petani / Produsen Hulu</h2>
            <p class="text-xs text-slate-500 mt-0.5">Dihitung otomatis berdasarkan kebutuhan bersih, ambang batas minimum order (MOQ), dan durasi pengiriman hulu (lead time).</p>
        </div>

        @if($purchaseSuggestions->isEmpty())
            <div class="bg-white rounded-2xl p-12 text-center border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)] space-y-3">
                <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center mx-auto border border-emerald-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h3 class="font-bold text-sm text-slate-900">Semua Stok Bahan Baku Aman</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto leading-relaxed">Saat ini seluruh stok berada di atas Reorder Point. Sistem belum mendeteksi kebutuhan penerbitan Purchase Order baru.</p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4">
                @foreach($purchaseSuggestions as $sugg)
                    @php
                        $isPending = $sugg->status === 'PENDING_REVIEW';
                    @endphp
                    <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)] hover:border-slate-300/80 transition space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                            <div class="flex items-center gap-3">
                                <span class="px-3 py-1 rounded-full text-[11px] font-bold border {{ $sugg->urgency_badge_color }}">
                                    {{ $sugg->urgency_level }}
                                </span>
                                <div>
                                    <h3 class="font-bold text-base text-slate-900">{{ $sugg->commodity?->name ?? 'Komoditas' }}</h3>
                                    <p class="text-xs text-slate-500">Tujuan Produsen: <strong class="text-slate-800">{{ $sugg->materialSource?->producer_name ?? 'Petani / Distributor Mitra' }}</strong></p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                @if($isPending)
                                    <button type="button" @click="approvePurchaseOrder('{{ $sugg->id }}')" class="px-4 py-2 rounded-xl bg-[#ea580c] hover:bg-[#c2410c] text-white font-semibold text-xs shadow-2xs transition cursor-pointer">
                                        Terbitkan PO Resmi
                                    </button>
                                    <button type="button" @click="ignorePurchaseOrder('{{ $sugg->id }}')" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition cursor-pointer">
                                        Abaikan
                                    </button>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-bold tabular-nums">
                                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <span>{{ $sugg->po_reference_number ?? 'PO TERBIT' }}</span>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs tabular-nums">
                            <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/70">
                                <span class="text-[10px] text-slate-500 font-semibold uppercase tracking-wider block">Rekomendasi Jumlah</span>
                                <span class="font-bold text-base text-[#ea580c] mt-0.5 inline-block">{{ number_format($sugg->suggested_quantity, 1) }}</span>
                                <span class="text-[11px] text-slate-500">{{ $sugg->commodity?->unit ?? 'kg' }}</span>
                            </div>
                            <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/70">
                                <span class="text-[10px] text-slate-500 font-semibold uppercase tracking-wider block">Estimasi Valuasi</span>
                                <span class="font-bold text-base text-slate-900 mt-0.5 block">Rp {{ number_format($sugg->estimated_cost, 0, ',', '.') }}</span>
                            </div>
                            <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/70">
                                <span class="text-[10px] text-slate-500 font-semibold uppercase tracking-wider block">Wajib Pesan (Order Date)</span>
                                <span class="font-bold text-slate-800 mt-0.5 block">{{ \Carbon\Carbon::parse($sugg->suggested_order_date)->translatedFormat('d M Y') }}</span>
                            </div>
                            <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/70">
                                <span class="text-[10px] text-slate-500 font-semibold uppercase tracking-wider block">Target Tiba di Gudang</span>
                                <span class="font-bold text-emerald-700 mt-0.5 block">{{ \Carbon\Carbon::parse($sugg->target_arrival_date)->translatedFormat('d M Y') }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ========================================================= --}}
    {{-- TAB 4: SIMULATOR SKENARIO WHAT-IF                         --}}
    {{-- ========================================================= --}}
    <div x-show="activeTab === 'what-if'" class="space-y-6">
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)] space-y-6">
            <div>
                <span class="text-[11px] font-bold text-[#ea580c] uppercase tracking-wider block">Dynamic Scenario Modeling</span>
                <h2 class="text-lg font-bold text-slate-900 mt-1">Simulator Skenario Fluktuasi &amp; Keterlambatan Pasokan</h2>
                <p class="text-xs text-slate-500 mt-0.5">Uji ketahanan inventori Anda jika pesanan UMKM melonjak tajam atau terjadi kendala transportasi dari produsen hulu.</p>
            </div>

            {{-- Slider Controls --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50/80 p-5 rounded-xl border border-slate-200/70">
                <div class="space-y-2">
                    <div class="flex justify-between items-center text-xs font-semibold">
                        <label class="text-slate-700">Lonjakan Permintaan Dapur UMKM:</label>
                        <span class="text-[#ea580c] font-bold text-sm tabular-nums" x-text="'+' + simDemandShock + '%'">+20%</span>
                    </div>
                    <input type="range" min="0" max="100" step="5" x-model="simDemandShock" @change="runWhatIfSimulation()"
                           class="w-full accent-[#ea580c] cursor-pointer" />
                    <p class="text-[11px] text-slate-500">Simulasikan lonjakan permintaan akhir pekan, musim libur, atau event besar kuliner.</p>
                </div>

                <div class="space-y-2">
                    <div class="flex justify-between items-center text-xs font-semibold">
                        <label class="text-slate-700">Keterlambatan Pasokan Hulu (Lead Time Delay):</label>
                        <span class="text-sky-700 font-bold text-sm tabular-nums" x-text="'+' + simLeadTimeDelay + ' Hari'">+2 Hari</span>
                    </div>
                    <input type="range" min="0" max="7" step="1" x-model="simLeadTimeDelay" @change="runWhatIfSimulation()"
                           class="w-full accent-sky-600 cursor-pointer" />
                    <p class="text-[11px] text-slate-500">Simulasikan cuaca buruk atau hambatan logistik dari pihak petani/produsen.</p>
                </div>
            </div>

            {{-- Hasil Simulasi Realtime --}}
            <template x-if="simulationResult">
                <div class="space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-orange-50/70 p-4 rounded-xl border border-orange-200/80">
                            <span class="text-[10px] uppercase font-bold text-[#c2410c] tracking-wider block">Tambahan Stok Pengaman yang Dibutuhkan</span>
                            <div class="text-2xl font-bold text-[#ea580c] tabular-nums mt-1" x-text="'+' + simulationResult.total_additional_buffer + ' kg/unit'"></div>
                            <span class="text-[11px] text-slate-600 mt-1 block">Wajib ditambahkan ke Safety Stock agar terhindar dari stockout.</span>
                        </div>
                        <div class="bg-sky-50/70 p-4 rounded-xl border border-sky-200/80">
                            <span class="text-[10px] uppercase font-bold text-sky-700 tracking-wider block">Estimasi Modal Kerja Tambahan (Working Capital)</span>
                            <div class="text-2xl font-bold text-sky-800 tabular-nums mt-1" x-text="'Rp ' + Number(simulationResult.total_additional_capital).toLocaleString('id-ID')"></div>
                            <span class="text-[11px] text-slate-600 mt-1 block">Estimasi pencadangan kas untuk antisipasi lonjakan pembelian hulu.</span>
                        </div>
                    </div>

                    {{-- Tabel Hasil per Komoditas --}}
                    <div class="overflow-x-auto border border-slate-200/80 rounded-xl">
                        <table class="w-full text-left text-xs tabular-nums">
                            <thead class="bg-slate-50 text-slate-500 uppercase font-bold text-[11px] border-b border-slate-200/80 tracking-wider">
                                <tr>
                                    <th class="py-3 px-4">Komoditas</th>
                                    <th class="py-3 px-4">Stok Saat Ini</th>
                                    <th class="py-3 px-4">Safety Stock Standar</th>
                                    <th class="py-3 px-4">Safety Stock Simulasi</th>
                                    <th class="py-3 px-4">Tambahan Buffer</th>
                                    <th class="py-3 px-4">Modal Ekstra</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="cb in simulationResult.commodity_breakdowns" :key="cb.commodity_name">
                                    <tr class="hover:bg-orange-50/30 transition-colors">
                                        <td class="py-3.5 px-4 font-semibold text-slate-900" x-text="cb.commodity_name"></td>
                                        <td class="py-3.5 px-4 text-slate-700" x-text="cb.current_stock + ' ' + cb.unit"></td>
                                        <td class="py-3.5 px-4 text-slate-500" x-text="cb.current_safety_stock + ' ' + cb.unit"></td>
                                        <td class="py-3.5 px-4 font-bold text-[#ea580c]" x-text="cb.simulated_safety_stock + ' ' + cb.unit"></td>
                                        <td class="py-3.5 px-4 font-bold text-rose-600" x-text="'+' + cb.delta_buffer_needed + ' ' + cb.unit"></td>
                                        <td class="py-3.5 px-4 font-bold text-slate-900" x-text="'Rp ' + Number(cb.additional_capital).toLocaleString('id-ID')"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- TAB 5: COLLABORATIVE SUPPLY PLANS (RESPON KUOTA DARI UMKM)--}}
    {{-- ========================================================= --}}
    <div x-show="activeTab === 'collaborative'" class="space-y-6">

        {{-- Mini Summary Strip CSP --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 tabular-nums">
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
                <span class="text-[11px] font-bold uppercase text-slate-500 tracking-wider block">Total Estimasi Masuk</span>
                <p class="text-xl font-bold text-slate-900 mt-1">{{ number_format($totalEstimatedVolume, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
                <span class="text-[11px] font-bold uppercase text-slate-500 tracking-wider block">Kapasitas Disetujui</span>
                <p class="text-xl font-bold text-emerald-700 mt-1">{{ number_format($totalConfirmedVolume, 0, ',', '.') }} <span class="text-xs font-semibold text-slate-500">({{ $readinessRate }}%)</span></p>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
                <span class="text-[11px] font-bold uppercase text-slate-500 tracking-wider block">Mitra Merencanakan</span>
                <p class="text-xl font-bold text-[#ea580c] mt-1">{{ $totalUmkmCount }} <span class="text-xs font-semibold text-slate-500">UMKM</span></p>
            </div>
        </div>

        {{-- Section: Agregasi Perencanaan Pasokan Sebagai Dasar Panen / Pengadaan --}}
        <div class="bg-white p-6 sm:p-7 rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)] space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                <div>
                    <span class="text-[11px] font-bold uppercase text-[#ea580c] tracking-wider block">Rekapitulasi Kebutuhan Mitra</span>
                    <h2 class="text-lg font-bold text-slate-900 mt-0.5">Dasar Perencanaan Pasokan &amp; Kuota Panen Mendatang</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Gunakan data agregat ini untuk merencanakan siklus panen, pengadaan stok, atau alokasi kuota gudang sebelum periode dimulai.</p>
                </div>
                <span class="text-xs font-semibold text-slate-700 bg-slate-50 border border-slate-200 px-3.5 py-1.5 rounded-full self-start sm:self-auto shrink-0 tabular-nums">
                    {{ count($commodityAggregations) }} Kelompok Komoditas
                </span>
            </div>

            @if($commodityAggregations->isEmpty())
                <div class="py-12 text-center space-y-3">
                    <div class="w-12 h-12 rounded-xl bg-slate-50 border border-slate-200 text-slate-400 flex items-center justify-center mx-auto">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <p class="text-xs font-semibold text-slate-600">Belum ada estimasi kebutuhan terakumulasi dari UMKM mitra untuk periode mendatang.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($commodityAggregations as $agg)
                        @php
                            $fulfillmentPercent = $agg['total_estimated'] > 0
                                ? min(100, round(($agg['total_confirmed'] / $agg['total_estimated']) * 100))
                                : 0;
                        @endphp
                        <div class="p-5 rounded-xl bg-slate-50/70 hover:bg-white border border-slate-200/80 hover:border-orange-200 transition-all space-y-4">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span class="inline-flex items-center gap-1.5 bg-white border border-slate-200 text-slate-700 px-2.5 py-1 rounded-lg text-[10px] font-bold">
                                        <svg class="w-3 h-3 text-[#ea580c]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span>{{ $agg['period'] }}</span>
                                    </span>
                                    <h3 class="font-bold text-sm text-slate-900 mt-2">{{ $agg['commodity'] }}</h3>
                                </div>
                                <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full shrink-0 tabular-nums {{ $agg['pending_items'] > 0 ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                                    {{ $agg['pending_items'] > 0 ? $agg['pending_items'].' Pending' : 'Semua Siap' }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-xs tabular-nums">
                                <div class="bg-white p-3 rounded-lg border border-slate-200/70">
                                    <span class="text-[10px] text-slate-500 font-semibold block">Total Kebutuhan</span>
                                    <span class="font-bold text-slate-900 text-sm">
                                        {{ number_format($agg['total_estimated'], 0, ',', '.') }} {{ $agg['unit'] }}
                                    </span>
                                </div>
                                <div class="bg-white p-3 rounded-lg border border-slate-200/70">
                                    <span class="text-[10px] text-slate-500 font-semibold block">Disanggupi Pasok</span>
                                    <span class="font-bold text-emerald-700 text-sm">
                                        {{ number_format($agg['total_confirmed'], 0, ',', '.') }} {{ $agg['unit'] }}
                                    </span>
                                </div>
                            </div>

                            {{-- Progress bar kesiapan --}}
                            <div class="space-y-1.5 pt-1">
                                <div class="flex justify-between text-[11px] font-semibold text-slate-600 tabular-nums">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        <span>{{ $agg['umkm_count'] }} Mitra UMKM</span>
                                    </span>
                                    <span class="font-bold {{ $fulfillmentPercent >= 100 ? 'text-emerald-600' : 'text-[#ea580c]' }}">{{ $fulfillmentPercent }}%</span>
                                </div>
                                <div class="w-full bg-slate-200/80 rounded-full h-2 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500 {{ $fulfillmentPercent >= 100 ? 'bg-emerald-500' : 'bg-[#ea580c]' }}" style="width: {{ $fulfillmentPercent }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Section: Daftar Detail Pengajuan Estimasi Dari UMKM --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)] p-6 sm:p-7 space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                <div>
                    <h2 class="font-bold text-base sm:text-lg text-slate-900">Daftar Detail Estimasi Kebutuhan Masuk dari Dapur UMKM</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Tinjau dan tentukan kesiapan pasokan panen Anda untuk masing-masing permintaan UMKM.</p>
                </div>

                {{-- Filter Controls --}}
                <form action="{{ route('supplier.supply-planning.index') }}" method="GET" class="flex flex-wrap items-center gap-2 text-xs">
                    <input type="hidden" name="tab" value="collaborative">
                    <select name="status" onchange="this.form.submit()" class="px-3.5 py-2 rounded-xl bg-slate-50 text-slate-800 font-semibold border border-slate-200 focus:bg-white focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c] focus:outline-none cursor-pointer">
                        <option value="semua" {{ $statusFilter === 'semua' || !$statusFilter ? 'selected' : '' }}>Semua Status</option>
                        <option value="MENUNGGU_KONFIRMASI" {{ $statusFilter === 'MENUNGGU_KONFIRMASI' ? 'selected' : '' }}>Menunggu Konfirmasi</option>
                        <option value="DISETUJUI" {{ $statusFilter === 'DISETUJUI' ? 'selected' : '' }}>Disetujui Penuh</option>
                        <option value="DISESUAIKAN" {{ $statusFilter === 'DISESUAIKAN' ? 'selected' : '' }}>Disesuaikan</option>
                        <option value="DITOLAK" {{ $statusFilter === 'DITOLAK' ? 'selected' : '' }}>Ditolak / Penuh</option>
                    </select>

                    @if($availablePeriods->isNotEmpty())
                        <select name="period" onchange="this.form.submit()" class="px-3.5 py-2 rounded-xl bg-slate-50 text-slate-800 font-semibold border border-slate-200 focus:bg-white focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c] focus:outline-none cursor-pointer">
                            <option value="semua" {{ $periodFilter === 'semua' || !$periodFilter ? 'selected' : '' }}>Semua Periode</option>
                            @foreach($availablePeriods as $per)
                                <option value="{{ $per }}" {{ $periodFilter === $per ? 'selected' : '' }}>{{ $per }}</option>
                            @endforeach
                        </select>
                    @endif
                </form>
            </div>

            @if($allPlans->isEmpty())
                <div class="py-14 text-center space-y-3">
                    <div class="w-13 h-13 rounded-2xl bg-orange-50 text-[#ea580c] border border-orange-100 flex items-center justify-center mx-auto">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-slate-900 text-sm">Tidak Ada Data Estimasi Kebutuhan</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto leading-relaxed">
                        @if($statusFilter && $statusFilter !== 'semua')
                            Tidak ditemukan estimasi kebutuhan dengan filter status tersebut.
                        @else
                            Belum ada UMKM mitra yang mengajukan estimasi Collaborative Supply Planning pada periode ini.
                        @endif
                    </p>
                </div>
            @else
                <div class="overflow-x-auto border border-slate-200/80 rounded-xl">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 uppercase font-bold text-[11px] border-b border-slate-200/80 tracking-wider">
                            <tr>
                                <th class="py-3.5 px-4">Mitra UMKM</th>
                                <th class="py-3.5 px-4">Komoditas Bahan Baku</th>
                                <th class="py-3.5 px-4">Periode Target</th>
                                <th class="py-3.5 px-4">Estimasi Dapur</th>
                                <th class="py-3.5 px-4">Disetujui Pemasok</th>
                                <th class="py-3.5 px-4">Status Respon</th>
                                <th class="py-3.5 px-4 text-right">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($allPlans as $plan)
                                <tr class="hover:bg-orange-50/30 transition-colors">
                                    <td class="py-3.5 px-4 font-semibold text-slate-900">
                                        {{ $plan->partnership?->umkm?->business_name ?? 'UMKM Mitra' }}
                                    </td>
                                    <td class="py-3.5 px-4 font-semibold text-slate-700">
                                        {{ $plan->commodity_name }}
                                    </td>
                                    <td class="py-3.5 px-4 font-medium text-slate-600 tabular-nums">
                                        {{ $plan->period_month_year }}
                                    </td>
                                    <td class="py-3.5 px-4 font-bold text-[#ea580c] tabular-nums">
                                        {{ number_format($plan->estimated_quantity, 1) }} {{ $plan->unit }}
                                    </td>
                                    <td class="py-3.5 px-4 font-bold text-emerald-700 tabular-nums">
                                        {{ $plan->confirmed_quantity ? number_format($plan->confirmed_quantity, 1) . ' ' . $plan->unit : '-' }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        @if($plan->supplier_confirmation_status === 'DISETUJUI')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                                                Disetujui
                                            </span>
                                        @elseif($plan->supplier_confirmation_status === 'DISESUAIKAN')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-sky-50 text-sky-700 border border-sky-200/80">
                                                Disesuaikan
                                            </span>
                                        @elseif($plan->supplier_confirmation_status === 'DITOLAK')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200/80">
                                                Ditolak
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200/80">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                <span>Menunggu Respon</span>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button"
                                                    @click="openResponseModal('{{ $plan->id }}', 'terima_penuh')"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white border border-emerald-200/70 font-semibold text-[11px] transition cursor-pointer"
                                                    title="Terima 100% Sesuai Kebutuhan">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span>Siap Penuh</span>
                                            </button>

                                            <button type="button"
                                                    @click="openResponseModal('{{ $plan->id }}', 'sesuaikan')"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-sky-50 text-sky-700 hover:bg-sky-600 hover:text-white border border-sky-200/70 font-semibold text-[11px] transition cursor-pointer"
                                                    title="Sesuaikan Kapasitas Pasok">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                </svg>
                                                <span>Sesuaikan</span>
                                            </button>

                                            <button type="button"
                                                    @click="openResponseModal('{{ $plan->id }}', 'tolak')"
                                                    class="p-1.5 rounded-lg bg-slate-100 text-slate-500 hover:bg-rose-50 hover:text-rose-600 font-semibold text-[11px] transition cursor-pointer"
                                                    title="Tolak / Kapasitas Belum Ada">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($allPlans->hasPages())
                    <div class="pt-2">
                        {{ $allPlans->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>

    {{-- MODAL UPDATE STOK GUDANG --}}
    <div x-cloak x-show="openInventoryModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/55 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="openInventoryModal = false" class="bg-white rounded-2xl max-w-md w-full overflow-hidden shadow-2xl border border-slate-200/80">
            <div class="bg-slate-50/90 px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-4">
                <h3 class="font-bold text-base text-slate-900">Perbarui Stok Fisik Gudang</h3>
                <button type="button" @click="openInventoryModal = false" class="w-8 h-8 rounded-full bg-white hover:bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-500 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <template x-if="editingInventory">
                <div class="p-6 space-y-4 text-xs">
                    <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/70">
                        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Bahan Baku</span>
                        <span class="font-bold text-slate-900 text-sm mt-0.5 block" x-text="editingInventory.commodity?.name"></span>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-slate-700 font-semibold">Stok Fisik Tersedia Saat Ini (<span x-text="editingInventory.unit"></span>):</label>
                        <input type="number" step="0.1" min="0" x-model="editingStockQty" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-bold text-sm tabular-nums focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c]" />
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" @click="openInventoryModal = false" class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold transition cursor-pointer">
                            Batal
                        </button>
                        <button type="button" @click="submitUpdateStock()" :disabled="isUpdatingStock" class="px-5 py-2 rounded-xl bg-[#ea580c] hover:bg-[#c2410c] text-white font-semibold shadow-2xs transition cursor-pointer">
                            <span x-text="isUpdatingStock ? 'Menyimpan...' : 'Simpan Stok'">Simpan Stok</span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- MODAL RESPON PEMASOK UNTUK SUPPLY PLAN UMKM --}}
    <div x-cloak x-show="openModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/55 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="openModal = false" class="bg-white rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-200/80">
            <div class="bg-slate-50/90 px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-orange-50 border border-orange-100 text-[#ea580c] flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-slate-900">Respon Estimasi Pasokan UMKM</h3>
                        <p class="text-[11px] font-semibold text-slate-500 tabular-nums" x-text="selectedPlan ? 'CSP-' + selectedPlan.id.substring(0,8).toUpperCase() : ''"></p>
                    </div>
                </div>
                <button type="button" @click="openModal = false" class="w-8 h-8 rounded-full bg-white hover:bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-500 transition cursor-pointer shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <template x-if="selectedPlan">
                <form :action="'{{ url('/supplier/supply-planning') }}/' + selectedPlan.id + '/konfirmasi'" method="POST" class="p-6 space-y-4 text-xs">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="action" :value="actionType">

                    {{-- Summary info --}}
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200/70 space-y-2">
                        <div class="flex justify-between items-center gap-4">
                            <span class="text-slate-500">Komoditas Bahan Baku:</span>
                            <span class="font-bold text-slate-900 text-right" x-text="selectedPlan.commodity_name"></span>
                        </div>
                        <div class="flex justify-between items-center gap-4">
                            <span class="text-slate-500">Permintaan Estimasi Dapur:</span>
                            <span class="font-bold text-[#ea580c] tabular-nums" x-text="selectedPlan.estimated_quantity + ' ' + selectedPlan.unit"></span>
                        </div>
                        <div class="flex justify-between items-center gap-4">
                            <span class="text-slate-500">Periode Target:</span>
                            <span class="font-semibold text-slate-900 tabular-nums" x-text="selectedPlan.period_month_year"></span>
                        </div>
                    </div>

                    {{-- Action Mode Selector --}}
                    <div>
                        <label class="block mb-1.5 text-slate-700 font-semibold">Keputusan Kesiapan Pasok:</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button" @click="actionType = 'terima_penuh'; supplierNotes = 'Kapasitas siap dipenuhi 100% sesuai estimasi kebutuhan dapur.'"
                                    :class="actionType === 'terima_penuh' ? 'bg-emerald-600 text-white font-bold shadow-2xs' : 'bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100 font-semibold'"
                                    class="p-2.5 rounded-xl transition text-center cursor-pointer flex items-center justify-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Siap Penuh</span>
                            </button>
                            <button type="button" @click="actionType = 'sesuaikan'; supplierNotes = 'Kapasitas pasokan disesuaikan dengan kuota panen/gudang yang tersedia.'"
                                    :class="actionType === 'sesuaikan' ? 'bg-sky-600 text-white font-bold shadow-2xs' : 'bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100 font-semibold'"
                                    class="p-2.5 rounded-xl transition text-center cursor-pointer flex items-center justify-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                <span>Sesuaikan</span>
                            </button>
                            <button type="button" @click="actionType = 'tolak'; supplierNotes = 'Mohon maaf, kapasitas pasokan kami pada periode ini belum mencukupi.'"
                                    :class="actionType === 'tolak' ? 'bg-rose-600 text-white font-bold shadow-2xs' : 'bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100 font-semibold'"
                                    class="p-2.5 rounded-xl transition text-center cursor-pointer flex items-center justify-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                <span>Tolak</span>
                            </button>
                        </div>
                    </div>

                    {{-- Input Kuota Disesuaikan --}}
                    <div x-show="actionType === 'sesuaikan'" class="space-y-1.5">
                        <label class="block text-slate-700 font-semibold">Kuantitas Pasok yang Mampu Disanggupi (<span x-text="selectedPlan.unit"></span>):</label>
                        <input type="number" step="0.1" min="0.1" name="confirmed_quantity" x-model="confirmedQty" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-bold tabular-nums focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c]" />
                        <p class="text-[11px] text-slate-500">Masukkan jumlah kapasitas panen riil yang dapat dialokasikan untuk UMKM ini.</p>
                    </div>

                    {{-- Input Catatan Respon --}}
                    <div class="space-y-1.5">
                        <label class="block text-slate-700 font-semibold">Catatan Konfirmasi Kesiapan untuk Dapur UMKM:</label>
                        <textarea name="supplier_notes" x-model="supplierNotes" rows="3" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c]"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" @click="openModal = false" class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-[#ea580c] hover:bg-[#c2410c] text-white font-semibold shadow-2xs transition cursor-pointer">
                            Simpan Respon Pasokan
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>

</div>
@endsection
