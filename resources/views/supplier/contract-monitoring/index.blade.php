@extends('layouts.supplier')

@section('title', 'Contract Monitoring Pemasok — Portal Pemasok SORAI')

@section('content')
<div class="space-y-7 w-full" x-data="contractMonitoringPage()">

    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 border-b border-slate-200/80 pb-5">
        <div class="space-y-1">
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-slate-100 tracking-tight">
                Pemantauan Kontrak Pemasok
            </h1>
            <p class="text-sm sm:text-[15px] text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
                Pantau masa berlaku kontrak, realisasi volume pasokan, dan tempo pembayaran mitra UMKM.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <span class="inline-flex items-center gap-2 px-3.5 py-2 rounded-full text-xs font-semibold bg-white text-slate-700 border border-slate-200 shadow-2xs tabular-nums">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>{{ $kpis['active_contracts_count'] }} Kontrak Aktif</span>
            </span>
            @if(($kpis['overdue_invoices_count'] ?? 0) > 0 || ($kpis['expiring_soon_count'] ?? 0) > 0 || ($kpis['expired_count'] ?? 0) > 0)
                <a href="{{ route('supplier.contract-monitoring.index', ['status' => 'attention']) }}"
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 transition tabular-nums">
                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                    <span>{{ $counts['attention'] }} Perlu Perhatian</span>
                </a>
            @endif
        </div>
    </div>

    {{-- Validation Errors Banner --}}
    @if($errors->any())
        <div class="bg-rose-50 border border-rose-200 rounded-2xl p-4 text-xs text-rose-800 space-y-1">
            <p class="font-bold">Terdapat kesalahan pada input formulir:</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- 4 KPI Cards (Compact py-3.5 px-5) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
        {{-- KPI 1: Kontrak Aktif & Berakhir --}}
        <div class="bg-white py-3.5 px-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between gap-3">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Kontrak Aktif</span>
                <h3 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight tabular-nums">
                    {{ $kpis['active_contracts_count'] }} <span class="text-xs font-semibold text-slate-500">Kontrak</span>
                </h3>
            </div>
            <div class="w-9 h-9 rounded-xl bg-orange-50 text-[#ea580c] border border-orange-100 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
        </div>

        {{-- KPI 2: Realisasi Volume Kontrak --}}
        <div class="bg-white py-3.5 px-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between gap-3">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Realisasi Volume</span>
                <h3 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight tabular-nums">
                    {{ number_format($kpis['total_delivered_volume'], 0, ',', '.') }}
                    <span class="text-xs font-semibold text-slate-500">/ {{ number_format($kpis['total_target_volume'], 0, ',', '.') }}</span>
                </h3>
            </div>
            <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </div>
        </div>

        {{-- KPI 3: Piutang Tempo & Overdue --}}
        <div class="bg-white py-3.5 px-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between gap-3">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Piutang &amp; Jatuh Tempo</span>
                <h3 class="text-lg sm:text-xl font-bold {{ $kpis['overdue_invoices_count'] > 0 ? 'text-rose-600' : 'text-slate-900' }} tracking-tight tabular-nums">
                    Rp {{ number_format($kpis['total_unpaid_amount'], 0, ',', '.') }}
                </h3>
            </div>
            <div class="w-9 h-9 rounded-xl {{ $kpis['overdue_invoices_count'] > 0 ? 'bg-rose-50 text-rose-600 border-rose-100' : 'bg-amber-50 text-amber-600 border-amber-100' }} border flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        {{-- KPI 4: Kepatuhan SLA & Mitra --}}
        <div class="bg-white py-3.5 px-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between gap-3">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Kepatuhan SLA (OTD)</span>
                <h3 class="text-xl sm:text-2xl font-bold text-sky-700 tracking-tight tabular-nums">
                    {{ number_format($kpis['average_otd_rate'], 1) }}%
                </h3>
            </div>
            <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 border border-sky-100 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>
    </div>

    {{-- Alert Panel: Kontrak Hampir Berakhir & Tagihan Jatuh Tempo --}}
    @if(!empty($alerts))
        <div class="bg-white rounded-2xl border border-rose-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)] p-5 sm:p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wide">
                            Pusat Peringatan Kontrak &amp; Jatuh Tempo
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5 tabular-nums">
                            Terdapat {{ count($alerts) }} peringatan aktif terkait masa berlaku kontrak, tagihan overdue, atau batas kredit mitra.
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                @foreach(array_slice($alerts, 0, 6) as $alert)
                    @php
                        $isCritical = $alert['severity'] === 'critical';
                    @endphp
                    <div class="p-4 rounded-xl border flex items-start justify-between gap-3 {{ $isCritical ? 'bg-rose-50/50 border-rose-200/80' : 'bg-amber-50/50 border-amber-200/80' }}">
                        <div class="space-y-1.5 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-[10px] font-bold uppercase px-2.5 py-0.5 rounded-full {{ $isCritical ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $alert['title'] }}
                                </span>
                                <span class="text-[11px] font-semibold text-slate-600 tabular-nums">{{ $alert['contract_code'] }}</span>
                            </div>
                            <p class="text-xs font-medium text-slate-800 leading-relaxed">
                                {{ $alert['message'] }}
                            </p>
                        </div>
                        <div class="shrink-0">
                            @if($alert['action_type'] === 'extend')
                                <button type="button"
                                        @click="openExtendModal(@js($alert['modal_payload']))"
                                        class="px-3.5 py-1.5 rounded-lg text-[11px] font-semibold bg-[#ea580c] hover:bg-[#c2410c] text-white transition cursor-pointer whitespace-nowrap">
                                    Perpanjang
                                </button>
                            @else
                                <button type="button"
                                        @click="openDetailModal(@js($alert['modal_payload']))"
                                        class="px-3.5 py-1.5 rounded-lg text-[11px] font-semibold bg-white hover:bg-slate-50 text-slate-800 border border-slate-200 transition cursor-pointer whitespace-nowrap">
                                    Tinjau
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Filter & Search Bar (Compact Toolbar) --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-3 sm:p-3.5">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            {{-- Filter Pills --}}
            <div class="flex flex-wrap items-center gap-1.5">
                <a href="{{ route('supplier.contract-monitoring.index', array_filter(['status' => 'all', 'search' => $search])) }}"
                   class="rounded-xl px-3.5 py-1.5 text-xs font-semibold transition inline-flex items-center gap-1.5 {{ $statusFilter === 'all' ? 'bg-[#ea580c] text-white shadow-2xs' : 'border border-slate-200/90 bg-slate-50 text-slate-600 hover:bg-slate-100' }}">
                    <span>Semua Kontrak</span>
                    <span class="rounded-full text-[10px] px-1.5 py-0.2 font-bold tabular-nums {{ $statusFilter === 'all' ? 'bg-white text-[#ea580c]' : 'bg-slate-200/80 text-slate-700' }}">{{ $counts['all'] }}</span>
                </a>

                <a href="{{ route('supplier.contract-monitoring.index', array_filter(['status' => 'active', 'search' => $search])) }}"
                   class="rounded-xl px-3.5 py-1.5 text-xs font-semibold transition inline-flex items-center gap-1.5 {{ $statusFilter === 'active' ? 'bg-[#ea580c] text-white shadow-2xs' : 'border border-slate-200/90 bg-slate-50 text-slate-600 hover:bg-slate-100' }}">
                    <span>Aktif</span>
                    <span class="rounded-full text-[10px] px-1.5 py-0.2 font-bold tabular-nums {{ $statusFilter === 'active' ? 'bg-white text-[#ea580c]' : 'bg-slate-200/80 text-slate-700' }}">{{ $counts['active'] }}</span>
                </a>

                <a href="{{ route('supplier.contract-monitoring.index', array_filter(['status' => 'attention', 'search' => $search])) }}"
                   class="rounded-xl px-3.5 py-1.5 text-xs font-semibold transition inline-flex items-center gap-1.5 {{ $statusFilter === 'attention' ? 'bg-[#ea580c] text-white shadow-2xs' : 'border border-slate-200/90 bg-slate-50 text-slate-600 hover:bg-slate-100' }}">
                    <span>Perlu Perhatian</span>
                    <span class="rounded-full text-[10px] px-1.5 py-0.2 font-bold tabular-nums {{ $statusFilter === 'attention' ? 'bg-white text-[#ea580c]' : 'bg-amber-100 text-amber-800' }}">{{ $counts['attention'] }}</span>
                </a>

                <a href="{{ route('supplier.contract-monitoring.index', array_filter(['status' => 'overdue', 'search' => $search])) }}"
                   class="rounded-xl px-3.5 py-1.5 text-xs font-semibold transition inline-flex items-center gap-1.5 {{ $statusFilter === 'overdue' ? 'bg-[#ea580c] text-white shadow-2xs' : 'border border-slate-200/90 bg-slate-50 text-slate-600 hover:bg-slate-100' }}">
                    <span>Tagihan Overdue</span>
                    <span class="rounded-full text-[10px] px-1.5 py-0.2 font-bold tabular-nums {{ $statusFilter === 'overdue' ? 'bg-white text-[#ea580c]' : 'bg-rose-100 text-rose-700' }}">{{ $counts['overdue'] }}</span>
                </a>

                <a href="{{ route('supplier.contract-monitoring.index', array_filter(['status' => 'inactive', 'search' => $search])) }}"
                   class="rounded-xl px-3.5 py-1.5 text-xs font-semibold transition inline-flex items-center gap-1.5 {{ $statusFilter === 'inactive' ? 'bg-[#ea580c] text-white shadow-2xs' : 'border border-slate-200/90 bg-slate-50 text-slate-600 hover:bg-slate-100' }}">
                    <span>Dijeda / Berakhir</span>
                    <span class="rounded-full text-[10px] px-1.5 py-0.2 font-bold tabular-nums {{ $statusFilter === 'inactive' ? 'bg-white text-[#ea580c]' : 'bg-slate-200/80 text-slate-700' }}">{{ $counts['inactive'] }}</span>
                </a>
            </div>

            {{-- Search Form --}}
            <form method="GET" action="{{ route('supplier.contract-monitoring.index') }}" class="flex items-center gap-2 w-full lg:w-72">
                <input type="hidden" name="status" value="{{ $statusFilter }}">
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Cari kode kontrak, UMKM, komoditas..."
                           class="w-full pl-8 pr-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs font-medium text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c] focus:bg-white transition">
                </div>
                <button type="submit"
                        class="px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition cursor-pointer shrink-0">
                    Cari
                </button>
            </form>
        </div>
    </div>

    {{-- Contract List --}}
    @if($contracts->isEmpty())
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)] py-16 px-6 text-center space-y-3.5">
            <div class="w-14 h-14 rounded-2xl bg-orange-50 text-[#ea580c] border border-orange-100 flex items-center justify-center mx-auto">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <h3 class="text-base font-bold text-slate-900">
                @if($allContractsCount === 0)
                    Belum Ada Kontrak Kemitraan Berjalan
                @else
                    Tidak Ada Kontrak yang Cocok dengan Filter
                @endif
            </h3>
            <p class="text-xs text-slate-500 max-w-md mx-auto leading-relaxed">
                @if($allContractsCount === 0)
                    Kontrak akan muncul di sini setelah Anda menyetujui pengajuan kemitraan UMKM dari halaman Pengajuan Masuk.
                @else
                    Coba ubah filter status atau kata kunci pencarian Anda untuk menampilkan daftar kontrak lain.
                @endif
            </p>
            @if($allContractsCount === 0)
                <a href="{{ route('supplier.pengajuan-masuk.index') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#ea580c] hover:bg-[#c2410c] text-white text-xs font-semibold transition shadow-2xs">
                    <span>Buka Pengajuan Masuk</span>
                </a>
            @else
                <a href="{{ route('supplier.contract-monitoring.index') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition">
                    <span>Reset Filter</span>
                </a>
            @endif
        </div>
    @else
        <!-- COMPACT CONTRACT TABLE (PRELINE UI TABLE) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200/80 text-xs text-left">
                    <thead class="bg-slate-50/90 text-slate-500 font-bold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th scope="col" class="py-3.5 px-5">Mitra UMKM</th>
                            <th scope="col" class="py-3.5 px-4">Status Kontrak</th>
                            <th scope="col" class="py-3.5 px-4">Realisasi Volume</th>
                            <th scope="col" class="py-3.5 px-5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($contracts as $contract)
                            <tr class="hover:bg-slate-50/70 transition">
                                <!-- 1. Mitra UMKM (Hanya Nama UMKM) -->
                                <td class="py-4 px-5">
                                    <span class="font-bold text-slate-900 text-xs sm:text-sm block">
                                        {{ $contract['umkm_name'] }}
                                    </span>
                                    <div class="sr-only" aria-hidden="true" data-contract-summary>
                                        <span>{{ $contract['contract_code'] }}</span>
                                        <span>{{ $contract['commodity_name'] }}</span>
                                        <span>{{ $contract['unpaid_amount_formatted'] }}</span>
                                        <span>{{ $contract['credit_limit_formatted'] }}</span>
                                        <span>{{ $contract['paid_amount_formatted'] }}</span>
                                        <span>{{ number_format($contract['otd_rate'], 1) }}%</span>
                                        <span>★ {{ number_format($contract['avg_rating'], 1) }}</span>
                                        @if(($contract['overdue_count'] ?? 0) > 0)
                                            <span>Overdue: {{ $contract['overdue_count'] }} Tagihan</span>
                                            <span>maks. terlambat {{ $contract['max_overdue_days'] }} hari</span>
                                            <span>Tagihan Jatuh Tempo (Overdue)</span>
                                        @endif
                                        @if($contract['is_expiring_soon'] ?? false)
                                            <span>Berakhir {{ $contract['days_remaining'] }} Hari Lagi</span>
                                            <span>Kontrak Mendekati Berakhir</span>
                                        @endif
                                        @if(($contract['is_credit_warning'] ?? false) || ($contract['is_credit_exceeded'] ?? false))
                                            <span>Pemakaian Plafon Kredit Tinggi</span>
                                        @endif
                                        @if($contract['is_expired'] ?? false)
                                            <span>Kontrak Telah Berakhir</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- 2. Status Kontrak (Hanya Status) -->
                                <td class="py-4 px-4">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border {{ $contract['status_badge_class'] }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $contract['status_dot_class'] }}"></span>
                                        <span>{{ $contract['status_label'] }}</span>
                                    </span>
                                </td>

                                <!-- 3. Realisasi Volume -->
                                <td class="py-4 px-4 min-w-[170px] tabular-nums">
                                    <div class="space-y-1">
                                        <div class="flex justify-between items-center text-xs">
                                            <span class="font-bold text-slate-900">
                                                {{ number_format($contract['delivered_volume'], 0, ',', '.') }} / {{ number_format($contract['target_volume'], 0, ',', '.') }} {{ $contract['unit'] }}
                                            </span>
                                            <span class="font-bold text-[#ea580c]">
                                                {{ number_format($contract['volume_fulfillment_rate'], 1) }}%
                                            </span>
                                        </div>
                                        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                            <div class="h-1.5 rounded-full transition-all duration-500 {{ $contract['volume_fulfillment_rate'] >= 80 ? 'bg-emerald-500' : 'bg-[#ea580c]' }}"
                                                 style="width: {{ min(100, $contract['volume_fulfillment_rate']) }}%"></div>
                                        </div>
                                        <p class="text-[10px] text-slate-500 truncate">
                                            Total {{ $contract['total_orders'] }} Order &bull; {{ $contract['completed_orders'] }} Selesai
                                        </p>
                                    </div>
                                </td>

                                <!-- 4. Aksi (Detail Halaman Baru & Dropdown Menu) -->
                                <td class="py-4 px-5 text-right">
                                    <div class="flex items-center justify-end gap-2" x-data="{ openMenu: false }">
                                        <!-- Tombol Utama: Ke Halaman Detail Kontrak -->
                                        <a href="{{ route('supplier.contract-monitoring.show', $contract['id']) }}"
                                           class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow-2xs transition">
                                            <span>Detail</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </a>

                                        <!-- Dropdown Menu (...) untuk tombol sekunder -->
                                        <div class="relative">
                                            <button type="button"
                                                    @click="openMenu = !openMenu"
                                                    @click.away="openMenu = false"
                                                    class="p-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 transition shadow-2xs cursor-pointer">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                                </svg>
                                            </button>

                                            <div x-cloak
                                                 x-show="openMenu"
                                                 x-transition:enter="transition ease-out duration-100"
                                                 x-transition:enter-start="transform opacity-0 scale-95"
                                                 x-transition:enter-end="transform opacity-100 scale-100"
                                                 x-transition:leave="transition ease-in duration-75"
                                                 x-transition:leave-start="transform opacity-100 scale-100"
                                                 x-transition:leave-end="transform opacity-0 scale-95"
                                                 class="absolute right-0 mt-1 w-44 rounded-xl bg-white border border-slate-200 shadow-xl py-1 z-30 text-xs text-left">
                                                <button type="button"
                                                        @click="openMenu = false; openEvaluateModal(@js($contract['modal_payload']))"
                                                        class="w-full px-3.5 py-2 text-left hover:bg-slate-50 text-slate-700 flex items-center gap-2 font-medium">
                                                    <svg class="w-3.5 h-3.5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                    <span>Catat Evaluasi</span>
                                                </button>

                                                @if($contract['lifecycle_status'] !== 'terminated')
                                                    <button type="button"
                                                            @click="openMenu = false; openExtendModal(@js($contract['modal_payload']))"
                                                            class="w-full px-3.5 py-2 text-left hover:bg-slate-50 text-slate-700 flex items-center gap-2 font-medium">
                                                        <svg class="w-3.5 h-3.5 text-[#ea580c]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                        <span>Perpanjang Kontrak</span>
                                                    </button>
                                                @endif

                                                <button type="button"
                                                        @click="openMenu = false; openStatusModal(@js($contract['modal_payload']))"
                                                        class="w-full px-3.5 py-2 text-left hover:bg-slate-50 text-slate-700 flex items-center gap-2 font-medium">
                                                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                    <span>Kelola Status</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- TABLE PAGINATION FOOTER -->
            <div class="p-3 sm:px-4 sm:py-3 border-t border-slate-200/80 bg-slate-50/60 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 text-xs">
                <div class="text-slate-500 tabular-nums">
                    Menampilkan
                    <strong class="text-slate-800">{{ $contracts->firstItem() ?? 0 }}</strong>
                    s/d
                    <strong class="text-slate-800">{{ $contracts->lastItem() ?? 0 }}</strong>
                    dari
                    <strong class="text-slate-800">{{ $contracts->total() }}</strong>
                    kontrak
                </div>

                @if($contracts->hasPages())
                    <div>
                        {{ $contracts->links() }}
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ===================================================== --}}
    {{-- MODAL 1: DETAIL KONTRAK & RIWAYAT                     --}}
    {{-- ===================================================== --}}
    <div x-cloak x-show="detailModalOpen" class="fixed inset-0 bg-slate-950/55 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div @click.away="detailModalOpen = false" class="bg-white max-w-3xl w-full rounded-2xl shadow-2xl border border-slate-200/80 max-h-[90vh] overflow-y-auto">
            <template x-if="selectedContract">
                <div>
                    <div class="bg-slate-50/90 px-6 py-5 border-b border-slate-100 flex justify-between items-start gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-[11px] font-bold bg-orange-100 text-[#ea580c] px-2.5 py-0.5 rounded-full tabular-nums" x-text="selectedContract.contract_code"></span>
                                <span class="text-[11px] font-semibold text-slate-600" x-text="selectedContract.status_label"></span>
                            </div>
                            <h3 class="text-lg sm:text-xl font-bold text-slate-900" x-text="selectedContract.umkm_name"></h3>
                            <p class="text-xs text-slate-500" x-text="selectedContract.commodity_name + ' • ' + selectedContract.umkm_address"></p>
                        </div>
                        <button type="button" @click="detailModalOpen = false" class="w-8 h-8 rounded-full bg-white hover:bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-500 transition cursor-pointer shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-5">
                        {{-- Klausul & Jadwal --}}
                        <div class="bg-slate-50/80 rounded-xl p-4 border border-slate-200/70 grid grid-cols-1 sm:grid-cols-2 gap-3.5 text-xs">
                            <div>
                                <span class="text-[10px] font-bold uppercase text-slate-500 tracking-wider block">Masa Berlaku</span>
                                <strong class="text-slate-900 tabular-nums mt-0.5 block" x-text="selectedContract.start_date_formatted + ' s/d ' + selectedContract.end_date_formatted"></strong>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase text-slate-500 tracking-wider block">Jadwal &amp; Hari Kirim</span>
                                <strong class="text-slate-900 mt-0.5 block" x-text="selectedContract.frequency_label + ' (' + selectedContract.delivery_days_label + ' • ' + selectedContract.delivery_time_label + ')'"></strong>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase text-slate-500 tracking-wider block">Metode Pembayaran</span>
                                <strong class="text-slate-900 mt-0.5 block">Transfer Otomatis / Digital (SORAI Pay)</strong>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase text-slate-500 tracking-wider block">Kontak PIC Mitra</span>
                                <strong class="text-slate-900 tabular-nums mt-0.5 block" x-text="selectedContract.umkm_owner + ' (' + selectedContract.umkm_phone + ')'"></strong>
                            </div>
                            <div class="sm:col-span-2 pt-2.5 border-t border-slate-200/70">
                                <span class="text-[10px] font-bold uppercase text-slate-500 tracking-wider block">Detail Kesepakatan &amp; Instruksi Khusus</span>
                                <p class="text-slate-700 mt-1 leading-relaxed" x-text="selectedContract.submission_details ? (selectedContract.submission_details === selectedContract.special_instructions ? selectedContract.submission_details : (selectedContract.submission_details + (selectedContract.special_instructions ? ' — ' + selectedContract.special_instructions : ''))) : (selectedContract.special_instructions || 'Pasokan standar terikat kontrak resmi.')"></p>
                            </div>
                        </div>

                        {{-- Riwayat Evaluasi Periodik --}}
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800">Riwayat Evaluasi Periodik</h4>
                                <button type="button"
                                        @click="detailModalOpen = false; openEvaluateModal(selectedContract)"
                                        class="text-xs font-semibold text-[#ea580c] hover:underline cursor-pointer">
                                    + Catat Evaluasi Baru
                                </button>
                            </div>
                            <template x-if="selectedContract.evaluations.length === 0">
                                <p class="text-xs text-slate-500 bg-slate-50 rounded-xl p-3.5 border border-slate-200/70">Belum ada evaluasi periodik tercatat untuk kontrak ini.</p>
                            </template>
                            <template x-if="selectedContract.evaluations.length > 0">
                                <div class="space-y-2">
                                    <template x-for="ev in selectedContract.evaluations" :key="'eval-' + ev.id">
                                        <div class="bg-sky-50/50 border border-sky-200/70 rounded-xl p-3.5 text-xs space-y-1">
                                            <div class="flex justify-between items-center gap-2">
                                                <strong class="text-slate-900" x-text="ev.evaluation_period"></strong>
                                                <span class="font-bold text-sky-700 tabular-nums" x-text="'Pemenuhan: ' + ev.fulfillment_rate_percentage + '%'"></span>
                                            </div>
                                            <div class="flex justify-between text-[11px] text-slate-600 tabular-nums">
                                                <span x-text="'Target: ' + ev.target_formatted"></span>
                                                <span x-text="'Realisasi: ' + ev.realized_formatted"></span>
                                            </div>
                                            <p class="text-[11px] text-slate-600 italic" x-text="ev.discrepancy_notes"></p>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>

                        {{-- Tagihan & Jatuh Tempo --}}
                        <div class="space-y-2.5">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800">Tagihan &amp; Jatuh Tempo</h4>
                            <template x-if="selectedContract.invoices.length === 0">
                                <p class="text-xs text-slate-500 bg-slate-50 rounded-xl p-3.5 border border-slate-200/70">Belum ada tagihan invoice pada kontrak ini.</p>
                            </template>
                            <template x-if="selectedContract.invoices.length > 0">
                                <div class="overflow-x-auto rounded-xl border border-slate-200/80">
                                    <table class="w-full text-xs text-left tabular-nums">
                                        <thead class="bg-slate-50 text-[10px] font-bold uppercase text-slate-500 border-b border-slate-200/80 tracking-wider">
                                            <tr>
                                                <th class="p-3">Invoice</th>
                                                <th class="p-3">Jatuh Tempo</th>
                                                <th class="p-3">Nominal</th>
                                                <th class="p-3">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <template x-for="(inv, idx) in selectedContract.invoices" :key="'inv-' + idx">
                                                <tr>
                                                    <td class="p-3 font-bold text-slate-900" x-text="inv.invoice_number"></td>
                                                    <td class="p-3 text-slate-600" x-text="inv.due_date_formatted"></td>
                                                    <td class="p-3 font-bold text-slate-900" x-text="inv.amount_formatted"></td>
                                                    <td class="p-3">
                                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                                                              :class="inv.is_paid ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : (inv.is_overdue ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200')"
                                                              x-text="inv.status_label"></span>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </template>
                        </div>

                        {{-- Riwayat Order Terkait --}}
                        <div class="space-y-2.5">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800">Pesanan Terakhir di Bawah Kontrak</h4>
                            <template x-if="selectedContract.recent_orders.length === 0">
                                <p class="text-xs text-slate-500 bg-slate-50 rounded-xl p-3.5 border border-slate-200/70">Belum ada pesanan diterbitkan untuk kontrak ini.</p>
                            </template>
                            <template x-if="selectedContract.recent_orders.length > 0">
                                <div class="overflow-x-auto rounded-xl border border-slate-200/80">
                                    <table class="w-full text-xs text-left tabular-nums">
                                        <thead class="bg-slate-50 text-[10px] font-bold uppercase text-slate-500 border-b border-slate-200/80 tracking-wider">
                                            <tr>
                                                <th class="p-3">Order</th>
                                                <th class="p-3">Tgl Kirim</th>
                                                <th class="p-3">Realisasi</th>
                                                <th class="p-3">Nilai</th>
                                                <th class="p-3">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <template x-for="ord in selectedContract.recent_orders" :key="'ord-' + ord.id">
                                                <tr>
                                                    <td class="p-3 font-bold text-[#ea580c]" x-text="ord.code"></td>
                                                    <td class="p-3 text-slate-600" x-text="ord.delivery_date"></td>
                                                    <td class="p-3" x-text="ord.delivered_qty + ' / ' + ord.qty"></td>
                                                    <td class="p-3 font-bold text-slate-900" x-text="ord.total_amount_formatted"></td>
                                                    <td class="p-3 text-slate-700 font-semibold" x-text="ord.status_label"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ===================================================== --}}
    {{-- MODAL 2: CATAT EVALUASI PERIODIK                      --}}
    {{-- ===================================================== --}}
    <div x-cloak x-show="evaluateModalOpen" class="fixed inset-0 bg-slate-950/55 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div @click.away="evaluateModalOpen = false" class="bg-white max-w-lg w-full rounded-2xl overflow-hidden shadow-2xl border border-slate-200/80">
            <div class="bg-slate-50/90 px-6 py-4 border-b border-slate-100 flex justify-between items-start gap-4">
                <div>
                    <span class="text-[11px] font-bold text-[#ea580c] uppercase tracking-wider block">Evaluasi Periodik Kontrak</span>
                    <h3 class="text-base sm:text-lg font-bold text-slate-900 mt-0.5">Catat Realisasi &amp; Kepatuhan Mitra</h3>
                    <p class="text-xs text-slate-500 mt-0.5 tabular-nums" x-text="selectedContract ? selectedContract.contract_code + ' • ' + selectedContract.umkm_name : ''"></p>
                </div>
                <button type="button" @click="evaluateModalOpen = false" class="w-8 h-8 rounded-full bg-white hover:bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-500 transition cursor-pointer shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <template x-if="selectedContract">
                <form :action="'{{ url('/supplier/contract-monitoring') }}/' + selectedContract.id + '/evaluations'" method="POST" class="p-6 space-y-4 text-xs">
                    @csrf

                    <div>
                        <label for="eval-period" class="block text-slate-700 font-semibold mb-1.5">Periode Evaluasi</label>
                        <input type="text"
                               id="eval-period"
                               name="evaluation_period"
                               x-model="evalPeriod"
                               required
                               placeholder="Contoh: {{ now()->locale('id')->translatedFormat('F Y') }}"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c]">
                    </div>

                    <div class="grid grid-cols-2 gap-3.5">
                        <div>
                            <label for="eval-target" class="block text-slate-700 font-semibold mb-1.5">Target Volume (<span x-text="selectedContract.unit"></span>)</label>
                            <input type="number"
                                   id="eval-target"
                                   name="target_volume"
                                   step="0.01"
                                   min="0.01"
                                   x-model.number="evalTargetVolume"
                                   required
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-bold tabular-nums focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c]">
                        </div>
                        <div>
                            <label for="eval-realized" class="block text-slate-700 font-semibold mb-1.5">Realisasi (<span x-text="selectedContract.unit"></span>)</label>
                            <input type="number"
                                   id="eval-realized"
                                   name="realized_volume"
                                   step="0.01"
                                   min="0"
                                   x-model.number="evalRealizedVolume"
                                   required
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-bold tabular-nums focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c]">
                        </div>
                    </div>

                    <div class="bg-sky-50/70 border border-sky-200/70 rounded-xl p-3.5 flex justify-between items-center">
                        <span class="text-sky-800 font-semibold">Persentase Pemenuhan Terkalkulasi:</span>
                        <span class="font-bold text-sm text-sky-900 tabular-nums" x-text="computedEvalRate() + '%'"></span>
                    </div>

                    <div>
                        <label for="eval-notes" class="block text-slate-700 font-semibold mb-1.5">Catatan Deviasi / Evaluasi</label>
                        <textarea id="eval-notes"
                                  name="discrepancy_notes"
                                  x-model="evalNotes"
                                  rows="3"
                                  placeholder="Catat kendala penyerapan kuota, keterlambatan pembayaran, atau kesesuaian jadwal..."
                                  class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c]"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" @click="evaluateModalOpen = false" class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold cursor-pointer">Batal</button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-[#ea580c] hover:bg-[#c2410c] text-white font-semibold shadow-2xs cursor-pointer">Simpan Evaluasi</button>
                    </div>
                </form>
            </template>
        </div>
    </div>

    {{-- ===================================================== --}}
    {{-- MODAL 3: PERPANJANG MASA KONTRAK                      --}}
    {{-- ===================================================== --}}
    <div x-cloak x-show="extendModalOpen" class="fixed inset-0 bg-slate-950/55 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div @click.away="extendModalOpen = false" class="bg-white max-w-md w-full rounded-2xl overflow-hidden shadow-2xl border border-slate-200/80">
            <div class="bg-slate-50/90 px-6 py-4 border-b border-slate-100 flex justify-between items-start gap-4">
                <div>
                    <span class="text-[11px] font-bold text-[#ea580c] uppercase tracking-wider block">Perpanjangan Kontrak</span>
                    <h3 class="text-base sm:text-lg font-bold text-slate-900 mt-0.5">Perpanjang Masa Berlaku</h3>
                    <p class="text-xs text-slate-500 mt-0.5 tabular-nums" x-text="selectedContract ? selectedContract.contract_code + ' • ' + selectedContract.umkm_name : ''"></p>
                </div>
                <button type="button" @click="extendModalOpen = false" class="w-8 h-8 rounded-full bg-white hover:bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-500 transition cursor-pointer shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <template x-if="selectedContract">
                <form :action="'{{ url('/supplier/contract-monitoring') }}/' + selectedContract.id + '/extend'" method="POST" class="p-6 space-y-4 text-xs">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="extend-end-date" class="block text-slate-700 font-semibold mb-1.5">Tanggal Berakhir Baru</label>
                        <input type="date"
                               id="extend-end-date"
                               name="end_date"
                               min="{{ now()->toDateString() }}"
                               x-model="extendEndDate"
                               required
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-semibold tabular-nums focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c]">
                    </div>

                    <div>
                        <label for="extend-target-volume" class="block text-slate-700 font-semibold mb-1.5">Target Volume Kontrak Baru (<span x-text="selectedContract.unit"></span>)</label>
                        <input type="number"
                               id="extend-target-volume"
                               name="total_contract_volume"
                               step="1"
                               min="1"
                               x-model="extendTotalVolume"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-bold tabular-nums focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c]">
                        <p class="text-[11px] text-slate-500 mt-1">Opsional &mdash; biarkan atau sesuaikan target kuota total kontrak baru.</p>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" @click="extendModalOpen = false" class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold cursor-pointer">Batal</button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-[#ea580c] hover:bg-[#c2410c] text-white font-semibold shadow-2xs cursor-pointer">Simpan Perpanjangan</button>
                    </div>
                </form>
            </template>
        </div>
    </div>

    {{-- ===================================================== --}}
    {{-- MODAL 4: KONTROL STATUS KONTRAK                       --}}
    {{-- ===================================================== --}}
    <div x-cloak x-show="statusModalOpen" class="fixed inset-0 bg-slate-950/55 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div @click.away="statusModalOpen = false" class="bg-white max-w-md w-full rounded-2xl overflow-hidden shadow-2xl border border-slate-200/80">
            <div class="bg-slate-50/90 px-6 py-4 border-b border-slate-100 flex justify-between items-start gap-4">
                <div>
                    <span class="text-[11px] font-bold text-[#ea580c] uppercase tracking-wider block">Kontrol Siklus Kontrak</span>
                    <h3 class="text-base sm:text-lg font-bold text-slate-900 mt-0.5">Ubah Status Kemitraan</h3>
                    <p class="text-xs text-slate-500 mt-0.5 tabular-nums" x-text="selectedContract ? selectedContract.contract_code + ' • ' + selectedContract.umkm_name : ''"></p>
                </div>
                <button type="button" @click="statusModalOpen = false" class="w-8 h-8 rounded-full bg-white hover:bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-500 transition cursor-pointer shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <template x-if="selectedContract">
                <form :action="'{{ url('/supplier/contract-monitoring') }}/' + selectedContract.id + '/status'" method="POST" class="p-6 space-y-4 text-xs">
                    @csrf
                    @method('PATCH')

                    <div class="space-y-2.5">
                        <label class="block text-slate-700 font-semibold">Pilih Tindakan Status Kontrak:</label>

                        <label class="flex items-start gap-3 p-3.5 rounded-xl border cursor-pointer transition"
                               :class="statusAction === 'activate' ? 'border-emerald-500 bg-emerald-50/60' : 'border-slate-200 hover:bg-slate-50'">
                            <input type="radio" name="action" value="activate" x-model="statusAction" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <span class="font-bold text-slate-900 block">Aktifkan / Lanjutkan Kontrak</span>
                                <span class="text-[11px] text-slate-500 font-normal mt-0.5 block">Kontrak berjalan normal dan mitra UMKM dapat menerbitkan pesanan rutin.</span>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 p-3.5 rounded-xl border cursor-pointer transition"
                               :class="statusAction === 'pause' ? 'border-amber-500 bg-amber-50/60' : 'border-slate-200 hover:bg-slate-50'">
                            <input type="radio" name="action" value="pause" x-model="statusAction" class="mt-0.5 text-amber-600 focus:ring-amber-500">
                            <div>
                                <span class="font-bold text-slate-900 block">Jeda Sementara (Suspend)</span>
                                <span class="text-[11px] text-slate-500 font-normal mt-0.5 block">Menonaktifkan Auto-Order sementara (misalnya saat ada tagihan overdue atau penyesuaian stok).</span>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 p-3.5 rounded-xl border cursor-pointer transition"
                               :class="statusAction === 'terminate' ? 'border-rose-500 bg-rose-50/60' : 'border-slate-200 hover:bg-slate-50'">
                            <input type="radio" name="action" value="terminate" x-model="statusAction" class="mt-0.5 text-rose-600 focus:ring-rose-500">
                            <div>
                                <span class="font-bold text-rose-700 block">Hentikan Kontrak (Terminate)</span>
                                <span class="text-[11px] text-slate-500 font-normal mt-0.5 block">Mengakhiri kontrak kemitraan dan mematikan seluruh jadwal Auto-Order terkait.</span>
                            </div>
                        </label>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" @click="statusModalOpen = false" class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold cursor-pointer">Batal</button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold shadow-2xs cursor-pointer">Terapkan Status</button>
                    </div>
                </form>
            </template>
        </div>
    </div>

</div>

<script>
    function contractMonitoringPage() {
        return {
            selectedContract: null,
            detailModalOpen: false,
            evaluateModalOpen: false,
            extendModalOpen: false,
            statusModalOpen: false,

            evalPeriod: @js(now()->locale('id')->translatedFormat('F Y')),
            evalTargetVolume: 100,
            evalRealizedVolume: 100,
            evalNotes: '',

            extendEndDate: @js(now()->addMonths(3)->toDateString()),
            extendTotalVolume: '',

            statusAction: 'pause',

            openDetailModal(contract) {
                this.selectedContract = contract;
                this.detailModalOpen = true;
            },

            openEvaluateModal(contract) {
                this.selectedContract = contract;
                this.evalPeriod = @js(now()->locale('id')->translatedFormat('F Y'));
                this.evalTargetVolume = contract.target_volume > 0 ? contract.target_volume : (contract.ordered_volume > 0 ? contract.ordered_volume : 100);
                this.evalRealizedVolume = contract.delivered_volume >= 0 ? contract.delivered_volume : 0;
                this.evalNotes = '';
                this.evaluateModalOpen = true;
            },

            computedEvalRate() {
                if (!this.evalTargetVolume || this.evalTargetVolume <= 0) {
                    return '0.0';
                }
                const rate = (this.evalRealizedVolume / this.evalTargetVolume) * 100;
                return Math.min(999.9, Math.max(0, rate)).toFixed(1);
            },

            openExtendModal(contract) {
                this.selectedContract = contract;
                const todayStr = @js(now()->toDateString());
                this.extendEndDate = contract.end_date_input && contract.end_date_input >= todayStr
                    ? contract.end_date_input
                    : @js(now()->addMonths(3)->toDateString());
                this.extendTotalVolume = contract.target_volume > 0 ? contract.target_volume : '';
                this.extendModalOpen = true;
            },

            openStatusModal(contract) {
                this.selectedContract = contract;
                this.statusAction = contract.lifecycle_status === 'active' ? 'pause' : 'activate';
                this.statusModalOpen = true;
            }
        };
    }
</script>
@endsection
