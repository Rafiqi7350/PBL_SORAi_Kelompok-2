@extends('layouts.supplier')

@section('content')
<div class="space-y-8" x-data="{
    openModal: false,
    selectedPlan: null,
    actionType: 'terima_penuh',
    confirmedQty: 0,
    supplierNotes: '',

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
    }
}">

    {{-- Hero Section Banner --}}
    <div class="relative overflow-hidden rounded-3xl bg-[#ea580c] p-6 sm:p-8 text-white shadow-lg shadow-orange-500/15" style="background-color: #ea580c;">
        <div class="absolute -right-16 -top-16 h-56 w-56 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="absolute -left-12 -bottom-12 w-48 h-48 bg-white/5 rounded-full blur-xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2 max-w-2xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur-md border border-white/20 text-white text-[11px] font-mono font-bold uppercase tracking-wider">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    Dasar Alokasi Kuota &amp; Rantai Pasok
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                    Collaborative Supply Planning
                </h1>
                <p class="text-xs sm:text-sm text-orange-50/90 leading-relaxed">
                    Estimasi agregat kebutuhan bahan baku dari dapur UMKM mitra untuk periode mendatang sebagai dasar perencanaan kuota panen, stok gudang, dan pasokan armada logistik Anda.
                </p>
            </div>

            <div class="shrink-0 flex flex-col sm:flex-row items-start sm:items-center gap-3">
                <div class="px-4 py-2.5 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 text-xs font-mono font-bold flex items-center gap-2">
                    <svg class="w-4 h-4 text-orange-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>{{ now()->translatedFormat('M Y') }} - {{ now()->addMonths(2)->translatedFormat('M Y') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="bg-emerald-500 text-white p-4 rounded-2xl text-xs font-bold flex items-center justify-between shadow-lg animate-in fade-in">
            <div class="flex items-center gap-2.5">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-white hover:text-emerald-100 font-bold p-1 cursor-pointer">✕</button>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-rose-500 text-white p-4 rounded-2xl text-xs font-bold flex items-center justify-between shadow-lg animate-in fade-in">
            <div class="flex items-center gap-2.5">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-white hover:text-rose-100 font-bold p-1 cursor-pointer">✕</button>
        </div>
    @endif

    {{-- 4 Metric KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Card 1: Total Estimasi --}}
        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm flex items-center justify-between hover:shadow-md transition">
            <div class="space-y-1">
                <p class="text-[11px] font-mono font-bold text-gray-400 uppercase tracking-wider">Total Estimasi Masuk</p>
                <h3 class="text-2xl font-black text-gray-900">
                    {{ number_format($totalEstimatedVolume, 0, ',', '.') }} <span class="text-xs font-semibold text-gray-400">kg/unit</span>
                </h3>
                <p class="text-[10px] text-gray-500 font-medium">Kebutuhan terakumulasi dapur mitra</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-orange-50 text-orange-600 border border-orange-100 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </div>
        </div>

        {{-- Card 2: Kapasitas Disetujui --}}
        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm flex items-center justify-between hover:shadow-md transition">
            <div class="space-y-1">
                <p class="text-[11px] font-mono font-bold text-gray-400 uppercase tracking-wider">Kapasitas Disetujui</p>
                <h3 class="text-2xl font-black text-emerald-600">
                    {{ number_format($totalConfirmedVolume, 0, ',', '.') }} <span class="text-xs font-semibold text-gray-400">kg/unit</span>
                </h3>
                <p class="text-[10px] text-emerald-600 font-semibold">{{ $readinessRate }}% Tingkat Kesiapan Pasok</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        {{-- Card 3: UMKM Mitra Merencanakan --}}
        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm flex items-center justify-between hover:shadow-md transition">
            <div class="space-y-1">
                <p class="text-[11px] font-mono font-bold text-gray-400 uppercase tracking-wider">Mitra Merencanakan</p>
                <h3 class="text-2xl font-black text-gray-900">
                    {{ $totalUmkmCount }} <span class="text-xs font-semibold text-gray-400">Dapur</span>
                </h3>
                <p class="text-[10px] text-gray-500 font-medium">Telah mengirim proyeksi kebutuhan</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
        </div>

        {{-- Card 4: Menunggu Respon --}}
        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm flex items-center justify-between hover:shadow-md transition">
            <div class="space-y-1">
                <p class="text-[11px] font-mono font-bold text-gray-400 uppercase tracking-wider">Menunggu Respon</p>
                <h3 class="text-2xl font-black {{ $pendingCount > 0 ? 'text-orange-600' : 'text-gray-900' }}">
                    {{ $pendingCount }} <span class="text-xs font-semibold text-gray-400">Permintaan</span>
                </h3>
                <p class="text-[10px] text-gray-500 font-medium">Perlu konfirmasi kesiapan panen</p>
            </div>
            <div class="w-12 h-12 rounded-2xl {{ $pendingCount > 0 ? 'bg-orange-500 text-white shadow-md shadow-orange-500/20 animate-pulse' : 'bg-gray-50 text-gray-400 border border-gray-100' }} flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>
    </div>

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
            <div class="overflow-x-auto rounded-2xl border border-gray-200">
                <table class="w-full text-xs text-left">
                    <thead class="bg-gray-50 font-mono text-gray-500 uppercase border-b border-gray-200">
                        <tr>
                            <th class="p-3.5">ID Proyeksi</th>
                            <th class="p-3.5">Dapur UMKM Pemohon</th>
                            <th class="p-3.5">Komoditas &amp; Estimasi Kuantitas</th>
                            <th class="p-3.5">Periode Target</th>
                            <th class="p-3.5">Catatan Dapur</th>
                            <th class="p-3.5">Status &amp; Kuota Disanggupi</th>
                            <th class="p-3.5 text-center">Respon Pasokan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-medium">
                        @foreach($allPlans as $plan)
                            @php
                                $umkm = $plan->partnership?->umkm;
                                $status = $plan->supplier_confirmation_status;
                            @endphp
                            <tr class="hover:bg-orange-50/20 transition">
                                <td class="p-3.5 font-mono font-bold text-[#ea580c] whitespace-nowrap">
                                    CSP-{{ strtoupper(substr($plan->id, 0, 8)) }}
                                </td>
                                <td class="p-3.5">
                                    <div class="space-y-0.5">
                                        <p class="font-bold text-gray-900 text-xs">{{ $umkm?->business_name ?? 'UMKM Mitra' }}</p>
                                        <p class="text-[11px] text-gray-500">{{ $umkm?->owner_name ?? '-' }} ({{ $umkm?->phone ?? '-' }})</p>
                                        <p class="text-[10px] text-gray-400 truncate max-w-xs">{{ $umkm?->address ?? '-' }}</p>
                                    </div>
                                </td>
                                <td class="p-3.5 whitespace-nowrap">
                                    <span class="font-bold text-gray-900 block text-xs">{{ $plan->commodity_name }}</span>
                                    <span class="text-gray-500 font-mono font-semibold text-[11px]">
                                        Estimasi: <span class="text-gray-900 font-bold">{{ number_format($plan->estimated_quantity, 1, ',', '.') }}</span> {{ $plan->unit }}
                                    </span>
                                </td>
                                <td class="p-3.5 font-bold text-gray-800 whitespace-nowrap">
                                    <span class="bg-orange-50 border border-orange-200 text-[#ea580c] px-2.5 py-1 rounded-full text-[11px] font-mono">
                                        {{ $plan->period_month_year }}
                                    </span>
                                </td>
                                <td class="p-3.5 text-gray-600 text-[11px] max-w-xs">
                                    {{ $plan->notes ?? 'Tidak ada catatan spesifik dari dapur.' }}
                                </td>
                                <td class="p-3.5">
                                    @if($status === 'DISETUJUI')
                                        <div class="space-y-1">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                                <span>Disetujui Penuh</span>
                                            </span>
                                            <p class="text-[11px] text-emerald-700 font-bold font-mono">
                                                Siap: {{ number_format($plan->confirmed_quantity ?: $plan->estimated_quantity, 1, ',', '.') }} {{ $plan->unit }}
                                            </p>
                                        </div>
                                    @elseif($status === 'DISESUAIKAN')
                                        <div class="space-y-1">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold inline-flex items-center gap-1.5 bg-blue-50 text-blue-700 border border-blue-200">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                                <span>Disesuaikan</span>
                                            </span>
                                            <p class="text-[11px] text-blue-700 font-bold font-mono">
                                                Sanggup: {{ number_format($plan->confirmed_quantity, 1, ',', '.') }} {{ $plan->unit }}
                                            </p>
                                        </div>
                                    @elseif($status === 'DITOLAK')
                                        <div class="space-y-1">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold inline-flex items-center gap-1.5 bg-rose-50 text-rose-700 border border-rose-200">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                                                <span>Kapasitas Penuh</span>
                                            </span>
                                        </div>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold inline-flex items-center gap-1.5 bg-amber-50 text-amber-700 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            <span>Menunggu Respon</span>
                                        </span>
                                    @endif

                                    @if($plan->supplier_notes)
                                        <p class="text-[10px] text-gray-500 italic mt-1 truncate max-w-xs" title="{{ $plan->supplier_notes }}">
                                            "{{ $plan->supplier_notes }}"
                                        </p>
                                    @endif
                                </td>
                                <td class="p-3.5 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button type="button"
                                                @click="openResponseModal({{ json_encode($plan) }}, 'terima_penuh')"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white font-bold text-[11px] transition shadow-xs cursor-pointer"
                                                title="Setujui 100% Volume">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                            <span>Siap Penuh</span>
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
        @endif
    </div>

    {{-- MODAL RESPON PEMASOK --}}
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
