@extends('layouts.supplier')

@section('title', 'Contract Monitoring Pemasok — Portal Pemasok SORAI')

@section('content')
<div class="space-y-7 w-full" x-data="contractMonitoringPage()">

    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 border-b border-slate-200/80 pb-5">
        <div class="space-y-1">
            <span class="text-[11px] font-bold uppercase text-[#ea580c] tracking-[0.14em] block">
                MONITORING KONTRAK &amp; KEPATUHAN SLA B2B
            </span>
            <h1 class="text-2xl sm:text-[26px] font-bold text-slate-900 tracking-tight uppercase">
                Contract Monitoring Pemasok
            </h1>
            <p class="text-xs sm:text-[13px] text-slate-500 max-w-2xl leading-relaxed">
                Pantau masa berlaku kontrak kemitraan, realisasi volume pasokan, eksposur kredit tempo (TOP), tagihan jatuh tempo, serta evaluasi berkala mitra UMKM.
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

    {{-- 4 KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        {{-- KPI 1: Kontrak Aktif & Berakhir --}}
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)] hover:border-slate-300/80 transition flex flex-col justify-between gap-4">
            <div class="flex items-start justify-between gap-3">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Kontrak Aktif</span>
                <div class="w-10 h-10 rounded-xl bg-orange-50 text-[#ea580c] border border-orange-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>
            <div class="space-y-1">
                <h3 class="text-2xl font-bold text-slate-900 tracking-tight tabular-nums">
                    {{ $kpis['active_contracts_count'] }} <span class="text-xs font-semibold text-slate-500">Kontrak</span>
                </h3>
                <p class="text-[11px] font-semibold {{ ($kpis['expiring_soon_count'] + $kpis['expired_count']) > 0 ? 'text-amber-700' : 'text-emerald-700' }} tabular-nums">
                    @if($kpis['expired_count'] > 0)
                        {{ $kpis['expired_count'] }} lewat masa berlaku &middot; {{ $kpis['expiring_soon_count'] }} akan berakhir
                    @elseif($kpis['expiring_soon_count'] > 0)
                        {{ $kpis['expiring_soon_count'] }} kontrak berakhir &le; 30 hari
                    @else
                        Seluruh kontrak dalam masa aktif
                    @endif
                </p>
            </div>
        </div>

        {{-- KPI 2: Realisasi Volume Kontrak --}}
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)] hover:border-slate-300/80 transition flex flex-col justify-between gap-4">
            <div class="flex items-start justify-between gap-3">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Realisasi Volume</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
            </div>
            <div class="space-y-1">
                <h3 class="text-2xl font-bold text-slate-900 tracking-tight tabular-nums">
                    {{ number_format($kpis['total_delivered_volume'], 0, ',', '.') }}
                    <span class="text-xs font-semibold text-slate-500">/ {{ number_format($kpis['total_target_volume'], 0, ',', '.') }}</span>
                </h3>
                <p class="text-[11px] font-semibold text-emerald-700 tabular-nums">
                    {{ number_format($kpis['average_volume_fulfillment'], 1) }}% Pemenuhan Kuota Kontrak
                </p>
            </div>
        </div>

        {{-- KPI 3: Piutang Tempo & Overdue --}}
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)] hover:border-slate-300/80 transition flex flex-col justify-between gap-4">
            <div class="flex items-start justify-between gap-3">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Piutang &amp; Jatuh Tempo</span>
                <div class="w-10 h-10 rounded-xl {{ $kpis['overdue_invoices_count'] > 0 ? 'bg-rose-50 text-rose-600 border-rose-100' : 'bg-amber-50 text-amber-600 border-amber-100' }} border flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="space-y-1">
                <h3 class="text-xl sm:text-2xl font-bold {{ $kpis['overdue_invoices_count'] > 0 ? 'text-rose-600' : 'text-slate-900' }} tracking-tight tabular-nums">
                    Rp {{ number_format($kpis['total_unpaid_amount'], 0, ',', '.') }}
                </h3>
                <p class="text-[11px] font-semibold {{ $kpis['overdue_invoices_count'] > 0 ? 'text-rose-600' : 'text-slate-500' }} tabular-nums">
                    @if($kpis['overdue_invoices_count'] > 0)
                        {{ $kpis['overdue_invoices_count'] }} invoice overdue (Rp {{ number_format($kpis['overdue_total_amount'], 0, ',', '.') }})
                    @else
                        Tidak ada tagihan jatuh tempo
                    @endif
                </p>
            </div>
        </div>

        {{-- KPI 4: Kepatuhan SLA & Mitra --}}
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)] hover:border-slate-300/80 transition flex flex-col justify-between gap-4">
            <div class="flex items-start justify-between gap-3">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Kepatuhan SLA (OTD)</span>
                <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 border border-sky-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="space-y-1">
                <h3 class="text-2xl font-bold text-sky-700 tracking-tight tabular-nums">
                    {{ number_format($kpis['average_otd_rate'], 1) }}%
                </h3>
                <p class="text-[11px] font-semibold text-slate-500 tabular-nums">
                    Rerata Skor Mitra: {{ $kpis['average_reliability_score'] > 0 ? number_format($kpis['average_reliability_score'], 0).'/100' : 'Observasi' }}
                </p>
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

    {{-- Filter & Search Bar --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)] p-5">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            {{-- Filter Pills --}}
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('supplier.contract-monitoring.index', array_filter(['status' => 'all', 'search' => $search])) }}"
                   class="rounded-full px-4 py-2 text-xs font-semibold transition inline-flex items-center gap-1.5 {{ $statusFilter === 'all' ? 'bg-[#ea580c] text-white shadow-2xs' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                    <span>Semua Kontrak</span>
                    <span class="rounded-full text-[10px] px-1.5 py-0.5 font-bold tabular-nums {{ $statusFilter === 'all' ? 'bg-white text-[#ea580c]' : 'bg-slate-100 text-slate-700' }}">{{ $counts['all'] }}</span>
                </a>

                <a href="{{ route('supplier.contract-monitoring.index', array_filter(['status' => 'active', 'search' => $search])) }}"
                   class="rounded-full px-4 py-2 text-xs font-semibold transition inline-flex items-center gap-1.5 {{ $statusFilter === 'active' ? 'bg-[#ea580c] text-white shadow-2xs' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                    <span>Aktif</span>
                    <span class="rounded-full text-[10px] px-1.5 py-0.5 font-bold tabular-nums {{ $statusFilter === 'active' ? 'bg-white text-[#ea580c]' : 'bg-slate-100 text-slate-700' }}">{{ $counts['active'] }}</span>
                </a>

                <a href="{{ route('supplier.contract-monitoring.index', array_filter(['status' => 'attention', 'search' => $search])) }}"
                   class="rounded-full px-4 py-2 text-xs font-semibold transition inline-flex items-center gap-1.5 {{ $statusFilter === 'attention' ? 'bg-[#ea580c] text-white shadow-2xs' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                    <span>Perlu Perhatian</span>
                    <span class="rounded-full text-[10px] px-1.5 py-0.5 font-bold tabular-nums {{ $statusFilter === 'attention' ? 'bg-white text-[#ea580c]' : 'bg-amber-100 text-amber-800' }}">{{ $counts['attention'] }}</span>
                </a>

                <a href="{{ route('supplier.contract-monitoring.index', array_filter(['status' => 'overdue', 'search' => $search])) }}"
                   class="rounded-full px-4 py-2 text-xs font-semibold transition inline-flex items-center gap-1.5 {{ $statusFilter === 'overdue' ? 'bg-[#ea580c] text-white shadow-2xs' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                    <span>Tagihan Overdue</span>
                    <span class="rounded-full text-[10px] px-1.5 py-0.5 font-bold tabular-nums {{ $statusFilter === 'overdue' ? 'bg-white text-[#ea580c]' : 'bg-rose-100 text-rose-700' }}">{{ $counts['overdue'] }}</span>
                </a>

                <a href="{{ route('supplier.contract-monitoring.index', array_filter(['status' => 'inactive', 'search' => $search])) }}"
                   class="rounded-full px-4 py-2 text-xs font-semibold transition inline-flex items-center gap-1.5 {{ $statusFilter === 'inactive' ? 'bg-[#ea580c] text-white shadow-2xs' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                    <span>Dijeda / Berakhir</span>
                    <span class="rounded-full text-[10px] px-1.5 py-0.5 font-bold tabular-nums {{ $statusFilter === 'inactive' ? 'bg-white text-[#ea580c]' : 'bg-slate-100 text-slate-700' }}">{{ $counts['inactive'] }}</span>
                </a>
            </div>

            {{-- Search Form --}}
            <form method="GET" action="{{ route('supplier.contract-monitoring.index') }}" class="flex items-center gap-2 w-full lg:w-80">
                <input type="hidden" name="status" value="{{ $statusFilter }}">
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Cari kode kontrak, UMKM, komoditas..."
                           class="w-full pl-10 pr-4 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-medium text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c] focus:bg-white transition">
                </div>
                <button type="submit"
                        class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition cursor-pointer shrink-0">
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
        <div class="space-y-5">
            @foreach($contracts as $contract)
                <div class="bg-white rounded-2xl border {{ $contract['has_alert'] ? 'border-amber-300/80' : 'border-slate-200/80' }} shadow-[0_1px_2px_rgba(15,23,42,0.04)] p-6 space-y-5 hover:border-slate-300/80 transition">

                    {{-- Card Top Header --}}
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                        <div class="space-y-1.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-xs font-bold text-[#ea580c] bg-orange-50 border border-orange-200/80 px-2.5 py-0.5 rounded-full tabular-nums">
                                    {{ $contract['contract_code'] }}
                                </span>

                                <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-[11px] font-semibold border {{ $contract['status_badge_class'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $contract['status_dot_class'] }}"></span>
                                    {{ $contract['status_label'] }}
                                </span>

                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $contract['reliability_badge_color'] }} tabular-nums">
                                    Mitra Grade {{ $contract['reliability_grade'] }}
                                    @if($contract['reliability_score'] > 0)
                                        ({{ number_format($contract['reliability_score'], 0) }})
                                    @endif
                                </span>

                                @if($contract['has_schedule'])
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $contract['is_auto_order_active'] ? 'bg-sky-50 text-sky-700 border-sky-200' : 'bg-slate-50 text-slate-600 border-slate-200' }}">
                                        Auto-Order: {{ $contract['is_auto_order_active'] ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                @endif

                                @if($contract['overdue_count'] > 0)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200 tabular-nums">
                                        Overdue: {{ $contract['overdue_count'] }} Tagihan
                                    </span>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 pt-1">
                                <h3 class="text-lg font-bold text-slate-900 tracking-tight">
                                    {{ $contract['umkm_name'] }}
                                </h3>
                                <span class="text-xs font-bold text-[#ea580c]">
                                    {{ $contract['commodity_name'] }}
                                </span>
                                @if($contract['unit_price'] > 0)
                                    <span class="text-xs font-medium text-slate-500 tabular-nums">
                                        ({{ $contract['unit_price_formatted'] }} / {{ $contract['unit'] }})
                                    </span>
                                @endif
                            </div>

                            <p class="text-xs text-slate-500 flex flex-wrap items-center gap-x-2 gap-y-1 tabular-nums">
                                <span>PIC: <strong class="text-slate-700">{{ $contract['umkm_owner'] }}</strong></span>
                                <span class="text-slate-300">&bull;</span>
                                <span>Periode: <strong class="text-slate-700">{{ $contract['start_date_formatted'] }} &ndash; {{ $contract['end_date_formatted'] }}</strong></span>
                                <span class="text-slate-300">&bull;</span>
                                <span>Jadwal: <strong class="text-slate-700">{{ $contract['frequency_label'] }} ({{ $contract['delivery_time_label'] }})</strong></span>
                            </p>
                        </div>

                        {{-- Quick Action Buttons --}}
                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            <button type="button"
                                    @click="openDetailModal(@js($contract['modal_payload']))"
                                    class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-800 text-xs font-semibold transition cursor-pointer">
                                Detail &amp; Riwayat
                            </button>

                            <button type="button"
                                    @click="openEvaluateModal(@js($contract['modal_payload']))"
                                    class="px-3.5 py-2 rounded-xl bg-sky-50 hover:bg-sky-600 text-sky-700 hover:text-white border border-sky-200/80 text-xs font-semibold transition cursor-pointer">
                                Catat Evaluasi
                            </button>

                            @if($contract['lifecycle_status'] !== 'terminated')
                                <button type="button"
                                        @click="openExtendModal(@js($contract['modal_payload']))"
                                        class="px-3.5 py-2 rounded-xl bg-[#ea580c] hover:bg-[#c2410c] text-white text-xs font-semibold transition shadow-2xs cursor-pointer">
                                    Perpanjang
                                </button>
                            @endif

                            <button type="button"
                                    @click="openStatusModal(@js($contract['modal_payload']))"
                                    class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition cursor-pointer">
                                Kelola Status
                            </button>
                        </div>
                    </div>

                    {{-- 3 Monitoring Pillars --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {{-- Pillar 1: Realisasi Volume & Order --}}
                        <div class="bg-slate-50/75 border border-slate-200/70 rounded-xl p-4 space-y-2.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-700">1. Realisasi Volume Kontrak</span>
                                <span class="font-bold text-[#ea580c] tabular-nums">
                                    {{ number_format($contract['volume_fulfillment_rate'], 1) }}%
                                </span>
                            </div>

                            <div class="w-full bg-slate-200/80 rounded-full h-2 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500 {{ $contract['volume_fulfillment_rate'] >= 80 ? 'bg-emerald-500' : 'bg-[#ea580c]' }}"
                                     style="width: {{ min(100, $contract['volume_fulfillment_rate']) }}%"></div>
                            </div>

                            <div class="flex justify-between text-[11px] text-slate-600 tabular-nums">
                                <span>Terkirim: <strong class="text-slate-900">{{ number_format($contract['delivered_volume'], 0, ',', '.') }} {{ $contract['unit'] }}</strong></span>
                                <span>Target: <strong class="text-slate-900">{{ number_format($contract['target_volume'], 0, ',', '.') }} {{ $contract['unit'] }}</strong></span>
                            </div>

                            <div class="pt-1.5 border-t border-slate-200/70 flex justify-between text-[11px] text-slate-500 tabular-nums">
                                <span>Total Order: <strong class="text-slate-800">{{ $contract['total_orders'] }}</strong></span>
                                <span>Selesai: <strong class="text-emerald-700">{{ $contract['completed_orders'] }}</strong> &middot; Backorder/Batal: <strong class="{{ $contract['issue_orders'] > 0 ? 'text-amber-700' : 'text-slate-700' }}">{{ $contract['issue_orders'] }}</strong></span>
                            </div>
                        </div>

                        {{-- Pillar 2: Fasilitas TOP & Eksposur Kredit --}}
                        <div class="bg-slate-50/75 border border-slate-200/70 rounded-xl p-4 space-y-2.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-700">2. TOP &amp; Eksposur Kredit</span>
                                <span class="font-bold px-2.5 py-0.5 rounded-full text-[10px] tabular-nums {{ $contract['term_of_payment_days'] > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                                    {{ $contract['term_of_payment_days'] > 0 ? "Tempo {$contract['term_of_payment_days']} Hari" : 'Tunai / COD' }}
                                </span>
                            </div>

                            @if($contract['credit_limit'] > 0)
                                <div class="w-full bg-slate-200/80 rounded-full h-2 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500 {{ $contract['is_credit_exceeded'] ? 'bg-rose-600' : ($contract['is_credit_warning'] ? 'bg-amber-500' : 'bg-sky-600') }}"
                                         style="width: {{ min(100, $contract['credit_usage_percent']) }}%"></div>
                                </div>
                            @endif

                            <div class="flex justify-between text-[11px] text-slate-600 tabular-nums">
                                <span>Piutang: <strong class="{{ $contract['overdue_count'] > 0 ? 'text-rose-600' : 'text-slate-900' }}">{{ $contract['unpaid_amount_formatted'] }}</strong></span>
                                <span>Plafon: <strong class="text-slate-900">{{ $contract['credit_limit_formatted'] }}</strong></span>
                            </div>

                            <div class="pt-1.5 border-t border-slate-200/70 flex justify-between items-center text-[11px] tabular-nums">
                                @if($contract['overdue_count'] > 0)
                                    <span class="font-bold text-rose-600 inline-flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        <span>{{ $contract['overdue_count'] }} invoice lewat jatuh tempo ({{ $contract['max_overdue_days'] }} hr)</span>
                                    </span>
                                @else
                                    <span class="text-slate-500">
                                        Terbayar: <strong class="text-emerald-700">{{ $contract['paid_amount_formatted'] }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Pillar 3: Kepatuhan SLA & Evaluasi Periodik --}}
                        <div class="bg-slate-50/75 border border-slate-200/70 rounded-xl p-4 space-y-2.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-700">3. SLA &amp; Evaluasi Terakhir</span>
                                <span class="font-bold text-amber-600 tabular-nums">
                                    {{ $contract['avg_rating'] !== null ? '★ '.number_format($contract['avg_rating'], 1) : 'Belum Ada Rating' }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-[11px]">
                                <div class="bg-white rounded-lg p-2.5 border border-slate-200/70">
                                    <span class="text-slate-500 block text-[10px] font-medium">Ketepatan (OTD)</span>
                                    <strong class="text-slate-900 tabular-nums">
                                        {{ $contract['otd_rate'] !== null ? number_format($contract['otd_rate'], 1).'%' : 'Belum Ada' }}
                                    </strong>
                                </div>
                                <div class="bg-white rounded-lg p-2.5 border border-slate-200/70">
                                    <span class="text-slate-500 block text-[10px] font-medium">Evaluasi Terakhir</span>
                                    <strong class="text-slate-900 tabular-nums">
                                        {{ $contract['latest_evaluation'] ? number_format($contract['latest_evaluation']['rate'], 1).'%' : 'Belum Ada' }}
                                    </strong>
                                </div>
                            </div>

                            <div class="pt-1.5 border-t border-slate-200/70 flex items-center justify-between gap-2 text-[11px]">
                                <span class="text-slate-500 truncate">
                                    @if($contract['latest_evaluation'])
                                        Periode: <strong class="text-slate-800">{{ $contract['latest_evaluation']['period'] }}</strong>
                                    @else
                                        Belum ada catatan evaluasi periodik
                                    @endif
                                </span>

                                @if($contract['has_schedule'] && $contract['lifecycle_status'] === 'active')
                                    <form method="POST" action="{{ route('supplier.contract-monitoring.toggle-auto-order', $contract['id']) }}" class="shrink-0">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="text-[11px] font-semibold underline cursor-pointer {{ $contract['is_auto_order_active'] ? 'text-sky-700 hover:text-sky-900' : 'text-slate-500 hover:text-slate-800' }}">
                                            {{ $contract['is_auto_order_active'] ? 'Jeda Auto-Order' : 'Aktifkan Auto-Order' }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
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
                                <span class="text-[10px] font-bold uppercase text-slate-500 tracking-wider block">Fasilitas Pembayaran &amp; Plafon</span>
                                <strong class="text-slate-900 tabular-nums mt-0.5 block" x-text="(selectedContract.term_of_payment_days > 0 ? 'Tempo ' + selectedContract.term_of_payment_days + ' Hari' : 'Tunai / COD') + ' • Plafon ' + selectedContract.credit_limit_formatted"></strong>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase text-slate-500 tracking-wider block">Kontak PIC Mitra</span>
                                <strong class="text-slate-900 tabular-nums mt-0.5 block" x-text="selectedContract.umkm_owner + ' (' + selectedContract.umkm_phone + ')'"></strong>
                            </div>
                            <div class="sm:col-span-2 pt-2.5 border-t border-slate-200/70">
                                <span class="text-[10px] font-bold uppercase text-slate-500 tracking-wider block">Detail Kesepakatan &amp; Instruksi Khusus</span>
                                <p class="text-slate-700 mt-1 leading-relaxed" x-text="selectedContract.submission_details + ' — ' + selectedContract.special_instructions"></p>
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
