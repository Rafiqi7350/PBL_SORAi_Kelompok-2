@extends('layouts.supplier')

@section('content')
<div class="space-y-7 max-w-7xl mx-auto pb-12" x-data="contractMonitoringPage()">

    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-200/70 pb-5">
        <div class="space-y-1">
            <span class="text-xs font-mono font-bold uppercase text-[#ea580c] tracking-widest block">
                MONITORING KONTRAK &amp; KEPATUHAN SLA B2B
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight uppercase">
                Contract Monitoring Pemasok
            </h1>
            <p class="text-xs sm:text-sm text-gray-500 max-w-2xl">
                Pantau masa berlaku kontrak kemitraan, realisasi volume pasokan, eksposur kredit tempo (TOP), tagihan jatuh tempo, serta evaluasi berkala mitra UMKM.
            </p>
        </div>

        <div class="flex items-center gap-2.5 self-start sm:self-auto">
            <span class="inline-flex items-center gap-2 px-3.5 py-2 rounded-full text-xs font-bold bg-white text-gray-700 border border-gray-200 shadow-2xs">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>{{ $kpis['active_contracts_count'] }} Kontrak Aktif</span>
            </span>
            @if(($kpis['overdue_invoices_count'] ?? 0) > 0 || ($kpis['expiring_soon_count'] ?? 0) > 0 || ($kpis['expired_count'] ?? 0) > 0)
                <a href="{{ route('supplier.contract-monitoring.index', ['status' => 'attention']) }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 transition">
                    <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
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
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- KPI 1: Kontrak Aktif & Berakhir --}}
        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-xs flex items-center justify-between hover:shadow-md transition">
            <div class="space-y-1">
                <p class="text-[11px] font-mono font-bold text-gray-400 uppercase tracking-wider">Kontrak Aktif</p>
                <h3 class="text-2xl font-black text-gray-900">
                    {{ $kpis['active_contracts_count'] }} <span class="text-xs font-semibold text-gray-400">Kontrak</span>
                </h3>
                <p class="text-[11px] font-semibold {{ ($kpis['expiring_soon_count'] + $kpis['expired_count']) > 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                    @if($kpis['expired_count'] > 0)
                        {{ $kpis['expired_count'] }} lewat masa berlaku · {{ $kpis['expiring_soon_count'] }} akan berakhir
                    @elseif($kpis['expiring_soon_count'] > 0)
                        {{ $kpis['expiring_soon_count'] }} kontrak berakhir &le; 30 hari
                    @else
                        Seluruh kontrak dalam masa aktif
                    @endif
                </p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-orange-50 text-[#ea580c] border border-orange-100 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
        </div>

        {{-- KPI 2: Realisasi Volume Kontrak --}}
        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-xs flex items-center justify-between hover:shadow-md transition">
            <div class="space-y-1">
                <p class="text-[11px] font-mono font-bold text-gray-400 uppercase tracking-wider">Realisasi Volume</p>
                <h3 class="text-2xl font-black text-gray-900">
                    {{ number_format($kpis['total_delivered_volume'], 0, ',', '.') }}
                    <span class="text-xs font-semibold text-gray-400">/ {{ number_format($kpis['total_target_volume'], 0, ',', '.') }}</span>
                </h3>
                <p class="text-[11px] font-semibold text-emerald-600">
                    {{ number_format($kpis['average_volume_fulfillment'], 1) }}% Pemenuhan Kuota Kontrak
                </p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </div>
        </div>

        {{-- KPI 3: Piutang Tempo & Overdue --}}
        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-xs flex items-center justify-between hover:shadow-md transition">
            <div class="space-y-1">
                <p class="text-[11px] font-mono font-bold text-gray-400 uppercase tracking-wider">Piutang &amp; Jatuh Tempo</p>
                <h3 class="text-xl font-black {{ $kpis['overdue_invoices_count'] > 0 ? 'text-rose-600' : 'text-gray-900' }}">
                    Rp {{ number_format($kpis['total_unpaid_amount'], 0, ',', '.') }}
                </h3>
                <p class="text-[11px] font-semibold {{ $kpis['overdue_invoices_count'] > 0 ? 'text-rose-600' : 'text-gray-500' }}">
                    @if($kpis['overdue_invoices_count'] > 0)
                        {{ $kpis['overdue_invoices_count'] }} invoice overdue (Rp {{ number_format($kpis['overdue_total_amount'], 0, ',', '.') }})
                    @else
                        Tidak ada tagihan jatuh tempo
                    @endif
                </p>
            </div>
            <div class="w-12 h-12 rounded-2xl {{ $kpis['overdue_invoices_count'] > 0 ? 'bg-rose-50 text-rose-600 border-rose-100' : 'bg-amber-50 text-amber-600 border-amber-100' }} border flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        {{-- KPI 4: Kepatuhan SLA & Mitra --}}
        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-xs flex items-center justify-between hover:shadow-md transition">
            <div class="space-y-1">
                <p class="text-[11px] font-mono font-bold text-gray-400 uppercase tracking-wider">Kepatuhan SLA (OTD)</p>
                <h3 class="text-2xl font-black text-blue-600">
                    {{ number_format($kpis['average_otd_rate'], 1) }}%
                </h3>
                <p class="text-[11px] font-semibold text-gray-500">
                    Rerata Skor Mitra: {{ $kpis['average_reliability_score'] > 0 ? number_format($kpis['average_reliability_score'], 0).'/100' : 'Observasi' }}
                </p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>
    </div>

    {{-- Alert Panel: Kontrak Hampir Berakhir & Tagihan Jatuh Tempo --}}
    @if(!empty($alerts))
        <div class="bg-white rounded-3xl border border-rose-200/80 shadow-xs p-5 sm:p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-gray-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-extrabold text-gray-900 uppercase tracking-wide">
                            Pusat Peringatan Kontrak &amp; Jatuh Tempo
                        </h2>
                        <p class="text-[11px] text-gray-500">
                            Terdapat {{ count($alerts) }} peringatan aktif terkait masa berlaku kontrak, tagihan overdue, atau batas kredit mitra.
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach(array_slice($alerts, 0, 6) as $alert)
                    @php
                        $isCritical = $alert['severity'] === 'critical';
                    @endphp
                    <div class="p-3.5 rounded-2xl border flex items-start justify-between gap-3 {{ $isCritical ? 'bg-rose-50/60 border-rose-200/80' : 'bg-amber-50/60 border-amber-200/80' }}">
                        <div class="space-y-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-[10px] font-mono font-bold uppercase px-2 py-0.5 rounded-full {{ $isCritical ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $alert['title'] }}
                                </span>
                                <span class="text-[10px] font-mono font-bold text-gray-500">{{ $alert['contract_code'] }}</span>
                            </div>
                            <p class="text-xs font-medium text-gray-800 leading-relaxed">
                                {{ $alert['message'] }}
                            </p>
                        </div>
                        <div class="shrink-0">
                            @if($alert['action_type'] === 'extend')
                                <button type="button"
                                        @click="openExtendModal(@js($alert['modal_payload']))"
                                        class="px-3 py-1.5 rounded-xl text-[11px] font-bold bg-[#ea580c] hover:bg-[#c2410c] text-white transition cursor-pointer whitespace-nowrap">
                                    Perpanjang
                                </button>
                            @else
                                <button type="button"
                                        @click="openDetailModal(@js($alert['modal_payload']))"
                                        class="px-3 py-1.5 rounded-xl text-[11px] font-bold bg-white hover:bg-gray-50 text-gray-800 border border-gray-200 transition cursor-pointer whitespace-nowrap">
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
    <div class="bg-white rounded-3xl border border-gray-100 shadow-xs p-5 space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            {{-- Filter Pills --}}
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('supplier.contract-monitoring.index', array_filter(['status' => 'all', 'search' => $search])) }}"
                   class="rounded-full px-4 py-2 text-xs font-bold transition flex items-center gap-1.5 {{ $statusFilter === 'all' ? 'bg-[#ea580c] text-white shadow-xs' : 'border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                    <span>Semua Kontrak</span>
                    <span class="rounded-full text-[10px] px-1.5 py-0.5 font-mono {{ $statusFilter === 'all' ? 'bg-white text-[#ea580c]' : 'bg-gray-100 text-gray-700' }}">{{ $counts['all'] }}</span>
                </a>

                <a href="{{ route('supplier.contract-monitoring.index', array_filter(['status' => 'active', 'search' => $search])) }}"
                   class="rounded-full px-4 py-2 text-xs font-bold transition flex items-center gap-1.5 {{ $statusFilter === 'active' ? 'bg-[#ea580c] text-white shadow-xs' : 'border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                    <span>Aktif</span>
                    <span class="rounded-full text-[10px] px-1.5 py-0.5 font-mono {{ $statusFilter === 'active' ? 'bg-white text-[#ea580c]' : 'bg-gray-100 text-gray-700' }}">{{ $counts['active'] }}</span>
                </a>

                <a href="{{ route('supplier.contract-monitoring.index', array_filter(['status' => 'attention', 'search' => $search])) }}"
                   class="rounded-full px-4 py-2 text-xs font-bold transition flex items-center gap-1.5 {{ $statusFilter === 'attention' ? 'bg-[#ea580c] text-white shadow-xs' : 'border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                    <span>Perlu Perhatian</span>
                    <span class="rounded-full text-[10px] px-1.5 py-0.5 font-mono {{ $statusFilter === 'attention' ? 'bg-white text-[#ea580c]' : 'bg-amber-100 text-amber-800' }}">{{ $counts['attention'] }}</span>
                </a>

                <a href="{{ route('supplier.contract-monitoring.index', array_filter(['status' => 'overdue', 'search' => $search])) }}"
                   class="rounded-full px-4 py-2 text-xs font-bold transition flex items-center gap-1.5 {{ $statusFilter === 'overdue' ? 'bg-[#ea580c] text-white shadow-xs' : 'border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                    <span>Tagihan Overdue</span>
                    <span class="rounded-full text-[10px] px-1.5 py-0.5 font-mono {{ $statusFilter === 'overdue' ? 'bg-white text-[#ea580c]' : 'bg-rose-100 text-rose-700' }}">{{ $counts['overdue'] }}</span>
                </a>

                <a href="{{ route('supplier.contract-monitoring.index', array_filter(['status' => 'inactive', 'search' => $search])) }}"
                   class="rounded-full px-4 py-2 text-xs font-bold transition flex items-center gap-1.5 {{ $statusFilter === 'inactive' ? 'bg-[#ea580c] text-white shadow-xs' : 'border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                    <span>Dijeda / Berakhir</span>
                    <span class="rounded-full text-[10px] px-1.5 py-0.5 font-mono {{ $statusFilter === 'inactive' ? 'bg-white text-[#ea580c]' : 'bg-gray-100 text-gray-700' }}">{{ $counts['inactive'] }}</span>
                </a>
            </div>

            {{-- Search Form --}}
            <form method="GET" action="{{ route('supplier.contract-monitoring.index') }}" class="flex items-center gap-2 w-full lg:w-80">
                <input type="hidden" name="status" value="{{ $statusFilter }}">
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Cari kode kontrak, UMKM, komoditas..."
                           class="w-full pl-10 pr-4 py-2 rounded-xl bg-gray-50 border border-gray-200 text-xs font-medium text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c] focus:bg-white transition">
                </div>
                <button type="submit"
                        class="px-4 py-2 rounded-xl bg-gray-900 hover:bg-black text-white text-xs font-bold transition cursor-pointer shrink-0">
                    Cari
                </button>
            </form>
        </div>
    </div>

    {{-- Contract List --}}
    @if($contracts->isEmpty())
        <div class="bg-white rounded-3xl border border-gray-100 shadow-xs py-16 px-6 text-center space-y-3">
            <div class="w-14 h-14 rounded-2xl bg-orange-50 text-[#ea580c] border border-orange-100 flex items-center justify-center mx-auto">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <h3 class="text-base font-bold text-gray-900">
                @if($allContractsCount === 0)
                    Belum Ada Kontrak Kemitraan Berjalan
                @else
                    Tidak Ada Kontrak yang Cocok dengan Filter
                @endif
            </h3>
            <p class="text-xs text-gray-500 max-w-md mx-auto">
                @if($allContractsCount === 0)
                    Kontrak akan muncul di sini setelah Anda menyetujui pengajuan kemitraan UMKM dari halaman Pengajuan Masuk.
                @else
                    Coba ubah filter status atau kata kunci pencarian Anda untuk menampilkan daftar kontrak lain.
                @endif
            </p>
            @if($allContractsCount === 0)
                <a href="{{ route('supplier.pengajuan-masuk.index') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-[#ea580c] hover:bg-[#c2410c] text-white text-xs font-bold transition shadow-xs">
                    <span>Buka Pengajuan Masuk</span>
                </a>
            @else
                <a href="{{ route('supplier.contract-monitoring.index') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-gray-900 hover:bg-black text-white text-xs font-bold transition">
                    <span>Reset Filter</span>
                </a>
            @endif
        </div>
    @else
        <div class="space-y-5">
            @foreach($contracts as $contract)
                <div class="bg-white rounded-3xl border {{ $contract['has_alert'] ? 'border-amber-200/90' : 'border-gray-100' }} shadow-xs p-6 space-y-5 hover:shadow-md transition">

                    {{-- Card Top Header --}}
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-gray-100 pb-4">
                        <div class="space-y-1.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-mono text-xs font-extrabold text-[#ea580c] bg-orange-50 border border-orange-200/80 px-2.5 py-0.5 rounded-full">
                                    {{ $contract['contract_code'] }}
                                </span>

                                <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-[11px] font-bold border {{ $contract['status_badge_class'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $contract['status_dot_class'] }}"></span>
                                    {{ $contract['status_label'] }}
                                </span>

                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $contract['reliability_badge_color'] }}">
                                    Mitra Grade {{ $contract['reliability_grade'] }}
                                    @if($contract['reliability_score'] > 0)
                                        ({{ number_format($contract['reliability_score'], 0) }})
                                    @endif
                                </span>

                                @if($contract['has_schedule'])
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $contract['is_auto_order_active'] ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-gray-50 text-gray-500 border-gray-200' }}">
                                        Auto-Order: {{ $contract['is_auto_order_active'] ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                @endif

                                @if($contract['overdue_count'] > 0)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                        Overdue: {{ $contract['overdue_count'] }} Tagihan
                                    </span>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 pt-1">
                                <h3 class="text-lg font-extrabold text-gray-900 tracking-tight">
                                    {{ $contract['umkm_name'] }}
                                </h3>
                                <span class="text-xs font-bold text-[#ea580c]">
                                    {{ $contract['commodity_name'] }}
                                </span>
                                @if($contract['unit_price'] > 0)
                                    <span class="text-xs font-mono text-gray-500">
                                        ({{ $contract['unit_price_formatted'] }} / {{ $contract['unit'] }})
                                    </span>
                                @endif
                            </div>

                            <p class="text-xs text-gray-500 flex flex-wrap items-center gap-x-2 gap-y-1">
                                <span>PIC: <strong class="text-gray-700">{{ $contract['umkm_owner'] }}</strong></span>
                                <span class="text-gray-300">&bull;</span>
                                <span>Periode: <strong class="text-gray-700">{{ $contract['start_date_formatted'] }} &ndash; {{ $contract['end_date_formatted'] }}</strong></span>
                                <span class="text-gray-300">&bull;</span>
                                <span>Jadwal: <strong class="text-gray-700">{{ $contract['frequency_label'] }} ({{ $contract['delivery_time_label'] }})</strong></span>
                            </p>
                        </div>

                        {{-- Quick Action Buttons --}}
                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            <button type="button"
                                    @click="openDetailModal(@js($contract['modal_payload']))"
                                    class="px-3.5 py-2 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-gray-800 text-xs font-bold transition cursor-pointer">
                                Detail &amp; Riwayat
                            </button>

                            <button type="button"
                                    @click="openEvaluateModal(@js($contract['modal_payload']))"
                                    class="px-3.5 py-2 rounded-xl bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white border border-blue-200/80 text-xs font-bold transition cursor-pointer">
                                Catat Evaluasi
                            </button>

                            @if($contract['lifecycle_status'] !== 'terminated')
                                <button type="button"
                                        @click="openExtendModal(@js($contract['modal_payload']))"
                                        class="px-3.5 py-2 rounded-xl bg-[#ea580c] hover:bg-[#c2410c] text-white text-xs font-bold transition shadow-xs cursor-pointer">
                                    Perpanjang
                                </button>
                            @endif

                            <button type="button"
                                    @click="openStatusModal(@js($contract['modal_payload']))"
                                    class="px-3.5 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold transition cursor-pointer">
                                Kelola Status
                            </button>
                        </div>
                    </div>

                    {{-- 3 Monitoring Pillars --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {{-- Pillar 1: Realisasi Volume & Order --}}
                        <div class="bg-gray-50/80 border border-gray-200/70 rounded-2xl p-4 space-y-2.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-gray-700">1. Realisasi Volume Kontrak</span>
                                <span class="font-mono font-extrabold text-[#ea580c]">
                                    {{ number_format($contract['volume_fulfillment_rate'], 1) }}%
                                </span>
                            </div>

                            <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500 {{ $contract['volume_fulfillment_rate'] >= 80 ? 'bg-emerald-500' : 'bg-[#ea580c]' }}"
                                     style="width: {{ min(100, $contract['volume_fulfillment_rate']) }}%"></div>
                            </div>

                            <div class="flex justify-between text-[11px] font-mono text-gray-600">
                                <span>Terkirim: <strong class="text-gray-900">{{ number_format($contract['delivered_volume'], 0, ',', '.') }} {{ $contract['unit'] }}</strong></span>
                                <span>Target: <strong class="text-gray-900">{{ number_format($contract['target_volume'], 0, ',', '.') }} {{ $contract['unit'] }}</strong></span>
                            </div>

                            <div class="pt-1 border-t border-gray-200/70 flex justify-between text-[11px] text-gray-500">
                                <span>Total Order: <strong class="text-gray-800">{{ $contract['total_orders'] }}</strong></span>
                                <span>Selesai: <strong class="text-emerald-700">{{ $contract['completed_orders'] }}</strong> · Backorder/Batal: <strong class="{{ $contract['issue_orders'] > 0 ? 'text-amber-700' : 'text-gray-700' }}">{{ $contract['issue_orders'] }}</strong></span>
                            </div>
                        </div>

                        {{-- Pillar 2: Fasilitas TOP & Eksposur Kredit --}}
                        <div class="bg-gray-50/80 border border-gray-200/70 rounded-2xl p-4 space-y-2.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-gray-700">2. TOP &amp; Eksposur Kredit</span>
                                <span class="font-mono font-bold px-2 py-0.5 rounded-full text-[10px] {{ $contract['term_of_payment_days'] > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-700' }}">
                                    {{ $contract['term_of_payment_days'] > 0 ? "Tempo {$contract['term_of_payment_days']} Hari" : 'Tunai / COD' }}
                                </span>
                            </div>

                            @if($contract['credit_limit'] > 0)
                                <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500 {{ $contract['is_credit_exceeded'] ? 'bg-rose-600' : ($contract['is_credit_warning'] ? 'bg-amber-500' : 'bg-blue-600') }}"
                                         style="width: {{ min(100, $contract['credit_usage_percent']) }}%"></div>
                                </div>
                            @endif

                            <div class="flex justify-between text-[11px] font-mono text-gray-600">
                                <span>Piutang: <strong class="{{ $contract['overdue_count'] > 0 ? 'text-rose-600' : 'text-gray-900' }}">{{ $contract['unpaid_amount_formatted'] }}</strong></span>
                                <span>Plafon: <strong class="text-gray-900">{{ $contract['credit_limit_formatted'] }}</strong></span>
                            </div>

                            <div class="pt-1 border-t border-gray-200/70 flex justify-between items-center text-[11px]">
                                @if($contract['overdue_count'] > 0)
                                    <span class="font-bold text-rose-600">
                                        ⚠ {{ $contract['overdue_count'] }} invoice lewat jatuh tempo ({{ $contract['max_overdue_days'] }} hr)
                                    </span>
                                @else
                                    <span class="text-gray-500">
                                        Terbayar: <strong class="text-emerald-700">{{ $contract['paid_amount_formatted'] }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Pillar 3: Kepatuhan SLA & Evaluasi Periodik --}}
                        <div class="bg-gray-50/80 border border-gray-200/70 rounded-2xl p-4 space-y-2.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-gray-700">3. SLA &amp; Evaluasi Terakhir</span>
                                <span class="font-mono font-bold text-amber-600">
                                    {{ $contract['avg_rating'] !== null ? '★ '.number_format($contract['avg_rating'], 1) : 'Belum Ada Rating' }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-[11px]">
                                <div class="bg-white rounded-xl p-2 border border-gray-100">
                                    <span class="text-gray-400 block text-[10px]">Ketepatan (OTD)</span>
                                    <strong class="font-mono text-gray-900">
                                        {{ $contract['otd_rate'] !== null ? number_format($contract['otd_rate'], 1).'%' : 'Belum Ada' }}
                                    </strong>
                                </div>
                                <div class="bg-white rounded-xl p-2 border border-gray-100">
                                    <span class="text-gray-400 block text-[10px]">Evaluasi Terakhir</span>
                                    <strong class="font-mono text-gray-900">
                                        {{ $contract['latest_evaluation'] ? number_format($contract['latest_evaluation']['rate'], 1).'%' : 'Belum Ada' }}
                                    </strong>
                                </div>
                            </div>

                            <div class="pt-1 border-t border-gray-200/70 flex items-center justify-between gap-2 text-[11px]">
                                <span class="text-gray-500 truncate">
                                    @if($contract['latest_evaluation'])
                                        Periode: <strong class="text-gray-800">{{ $contract['latest_evaluation']['period'] }}</strong>
                                    @else
                                        Belum ada catatan evaluasi periodik
                                    @endif
                                </span>

                                @if($contract['has_schedule'] && $contract['lifecycle_status'] === 'active')
                                    <form method="POST" action="{{ route('supplier.contract-monitoring.toggle-auto-order', $contract['id']) }}" class="shrink-0">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="text-[11px] font-bold underline cursor-pointer {{ $contract['is_auto_order_active'] ? 'text-blue-700 hover:text-blue-900' : 'text-gray-500 hover:text-gray-800' }}">
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
    <div x-cloak x-show="detailModalOpen" class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div @click.away="detailModalOpen = false" class="bg-white max-w-3xl w-full rounded-3xl p-6 sm:p-7 shadow-2xl border border-gray-100 max-h-[90vh] overflow-y-auto space-y-5">
            <template x-if="selectedContract">
                <div class="space-y-5">
                    <div class="flex justify-between items-start border-b border-gray-100 pb-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-mono text-[11px] font-bold bg-orange-100 text-[#ea580c] px-2.5 py-0.5 rounded-full" x-text="selectedContract.contract_code"></span>
                                <span class="text-[11px] font-bold text-gray-500" x-text="selectedContract.status_label"></span>
                            </div>
                            <h3 class="text-xl font-extrabold text-gray-900" x-text="selectedContract.umkm_name"></h3>
                            <p class="text-xs text-gray-500" x-text="selectedContract.commodity_name + ' • ' + selectedContract.umkm_address"></p>
                        </div>
                        <button type="button" @click="detailModalOpen = false" class="text-gray-400 hover:text-gray-700 p-1 rounded-lg hover:bg-gray-100 cursor-pointer font-bold text-lg">✕</button>
                    </div>

                    {{-- Klausul & Jadwal --}}
                    <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100 grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div>
                            <span class="text-[10px] font-bold uppercase text-gray-400 block">Masa Berlaku</span>
                            <strong class="text-gray-900" x-text="selectedContract.start_date_formatted + ' s/d ' + selectedContract.end_date_formatted"></strong>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase text-gray-400 block">Jadwal &amp; Hari Kirim</span>
                            <strong class="text-gray-900" x-text="selectedContract.frequency_label + ' (' + selectedContract.delivery_days_label + ' • ' + selectedContract.delivery_time_label + ')'"></strong>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase text-gray-400 block">Fasilitas Pembayaran &amp; Plafon</span>
                            <strong class="text-gray-900" x-text="(selectedContract.term_of_payment_days > 0 ? 'Tempo ' + selectedContract.term_of_payment_days + ' Hari' : 'Tunai / COD') + ' • Plafon ' + selectedContract.credit_limit_formatted"></strong>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase text-gray-400 block">Kontak PIC Mitra</span>
                            <strong class="text-gray-900" x-text="selectedContract.umkm_owner + ' (' + selectedContract.umkm_phone + ')'"></strong>
                        </div>
                        <div class="sm:col-span-2 pt-2 border-t border-gray-200/70">
                            <span class="text-[10px] font-bold uppercase text-gray-400 block">Detail Kesepakatan &amp; Instruksi Khusus</span>
                            <p class="text-gray-700 mt-0.5" x-text="selectedContract.submission_details + ' — ' + selectedContract.special_instructions"></p>
                        </div>
                    </div>

                    {{-- Riwayat Evaluasi Periodik --}}
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-extrabold uppercase tracking-wider text-gray-700">Riwayat Evaluasi Periodik</h4>
                            <button type="button"
                                    @click="detailModalOpen = false; openEvaluateModal(selectedContract)"
                                    class="text-xs font-bold text-[#ea580c] hover:underline cursor-pointer">
                                + Catat Evaluasi Baru
                            </button>
                        </div>
                        <template x-if="selectedContract.evaluations.length === 0">
                            <p class="text-xs text-gray-400 bg-gray-50 rounded-xl p-3 border border-gray-100">Belum ada evaluasi periodik tercatat untuk kontrak ini.</p>
                        </template>
                        <template x-if="selectedContract.evaluations.length > 0">
                            <div class="space-y-2">
                                <template x-for="ev in selectedContract.evaluations" :key="'eval-' + ev.id">
                                    <div class="bg-blue-50/40 border border-blue-100 rounded-xl p-3 text-xs space-y-1">
                                        <div class="flex justify-between items-center">
                                            <strong class="text-gray-900" x-text="ev.evaluation_period"></strong>
                                            <span class="font-mono font-bold text-blue-700" x-text="'Pemenuhan: ' + ev.fulfillment_rate_percentage + '%'"></span>
                                        </div>
                                        <div class="flex justify-between text-[11px] font-mono text-gray-600">
                                            <span x-text="'Target: ' + ev.target_formatted"></span>
                                            <span x-text="'Realisasi: ' + ev.realized_formatted"></span>
                                        </div>
                                        <p class="text-[11px] text-gray-600 italic" x-text="ev.discrepancy_notes"></p>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    {{-- Tagihan & Jatuh Tempo --}}
                    <div class="space-y-2">
                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-gray-700">Tagihan &amp; Jatuh Tempo</h4>
                        <template x-if="selectedContract.invoices.length === 0">
                            <p class="text-xs text-gray-400 bg-gray-50 rounded-xl p-3 border border-gray-100">Belum ada tagihan invoice pada kontrak ini.</p>
                        </template>
                        <template x-if="selectedContract.invoices.length > 0">
                            <div class="overflow-x-auto rounded-xl border border-gray-200">
                                <table class="w-full text-xs text-left">
                                    <thead class="bg-gray-50 text-[10px] font-mono uppercase text-gray-500">
                                        <tr>
                                            <th class="p-2.5">Invoice</th>
                                            <th class="p-2.5">Jatuh Tempo</th>
                                            <th class="p-2.5">Nominal</th>
                                            <th class="p-2.5">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template x-for="(inv, idx) in selectedContract.invoices" :key="'inv-' + idx">
                                            <tr>
                                                <td class="p-2.5 font-mono font-bold text-gray-900" x-text="inv.invoice_number"></td>
                                                <td class="p-2.5 text-gray-600" x-text="inv.due_date_formatted"></td>
                                                <td class="p-2.5 font-mono font-bold text-gray-900" x-text="inv.amount_formatted"></td>
                                                <td class="p-2.5">
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                                          :class="inv.is_paid ? 'bg-emerald-100 text-emerald-800' : (inv.is_overdue ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800')"
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
                    <div class="space-y-2">
                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-gray-700">Pesanan Terakhir di Bawah Kontrak</h4>
                        <template x-if="selectedContract.recent_orders.length === 0">
                            <p class="text-xs text-gray-400 bg-gray-50 rounded-xl p-3 border border-gray-100">Belum ada pesanan diterbitkan untuk kontrak ini.</p>
                        </template>
                        <template x-if="selectedContract.recent_orders.length > 0">
                            <div class="overflow-x-auto rounded-xl border border-gray-200">
                                <table class="w-full text-xs text-left">
                                    <thead class="bg-gray-50 text-[10px] font-mono uppercase text-gray-500">
                                        <tr>
                                            <th class="p-2.5">Order</th>
                                            <th class="p-2.5">Tgl Kirim</th>
                                            <th class="p-2.5">Realisasi</th>
                                            <th class="p-2.5">Nilai</th>
                                            <th class="p-2.5">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template x-for="ord in selectedContract.recent_orders" :key="'ord-' + ord.id">
                                            <tr>
                                                <td class="p-2.5 font-mono font-bold text-[#ea580c]" x-text="ord.code"></td>
                                                <td class="p-2.5 text-gray-600" x-text="ord.delivery_date"></td>
                                                <td class="p-2.5 font-mono" x-text="ord.delivered_qty + ' / ' + ord.qty"></td>
                                                <td class="p-2.5 font-mono font-bold" x-text="ord.total_amount_formatted"></td>
                                                <td class="p-2.5 text-gray-700 font-semibold" x-text="ord.status_label"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ===================================================== --}}
    {{-- MODAL 2: CATAT EVALUASI PERIODIK                      --}}
    {{-- ===================================================== --}}
    <div x-cloak x-show="evaluateModalOpen" class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div @click.away="evaluateModalOpen = false" class="bg-white max-w-lg w-full rounded-3xl p-6 sm:p-7 shadow-2xl border border-gray-100 space-y-5">
            <div class="flex justify-between items-start border-b border-gray-100 pb-3">
                <div>
                    <span class="text-[10px] font-mono font-bold text-[#ea580c] uppercase">EVALUASI PERIODIK KONTRAK</span>
                    <h3 class="text-lg font-extrabold text-gray-900">Catat Realisasi &amp; Kepatuhan Mitra</h3>
                    <p class="text-xs text-gray-500" x-text="selectedContract ? selectedContract.contract_code + ' • ' + selectedContract.umkm_name : ''"></p>
                </div>
                <button type="button" @click="evaluateModalOpen = false" class="text-gray-400 hover:text-gray-700 font-bold text-lg cursor-pointer">✕</button>
            </div>

            <template x-if="selectedContract">
                <form :action="'{{ url('/supplier/contract-monitoring') }}/' + selectedContract.id + '/evaluations'" method="POST" class="space-y-4 text-xs font-semibold">
                    @csrf

                    <div>
                        <label for="eval-period" class="block text-gray-700 mb-1">Periode Evaluasi</label>
                        <input type="text"
                               id="eval-period"
                               name="evaluation_period"
                               x-model="evalPeriod"
                               required
                               placeholder="Contoh: {{ now()->locale('id')->translatedFormat('F Y') }}"
                               class="w-full p-3 rounded-xl bg-gray-50 border border-gray-200 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ea580c]">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="eval-target" class="block text-gray-700 mb-1">Target Volume (<span x-text="selectedContract.unit"></span>)</label>
                            <input type="number"
                                   id="eval-target"
                                   name="target_volume"
                                   step="0.01"
                                   min="0.01"
                                   x-model.number="evalTargetVolume"
                                   required
                                   class="w-full p-3 rounded-xl bg-gray-50 border border-gray-200 text-gray-900 font-mono focus:outline-none focus:ring-2 focus:ring-[#ea580c]">
                        </div>
                        <div>
                            <label for="eval-realized" class="block text-gray-700 mb-1">Realisasi (<span x-text="selectedContract.unit"></span>)</label>
                            <input type="number"
                                   id="eval-realized"
                                   name="realized_volume"
                                   step="0.01"
                                   min="0"
                                   x-model.number="evalRealizedVolume"
                                   required
                                   class="w-full p-3 rounded-xl bg-gray-50 border border-gray-200 text-gray-900 font-mono focus:outline-none focus:ring-2 focus:ring-[#ea580c]">
                        </div>
                    </div>

                    <div class="bg-blue-50 border border-blue-100 rounded-xl p-3 flex justify-between items-center">
                        <span class="text-blue-800">Persentase Pemenuhan Terkalkulasi:</span>
                        <span class="font-mono font-extrabold text-sm text-blue-900" x-text="computedEvalRate() + '%'"></span>
                    </div>

                    <div>
                        <label for="eval-notes" class="block text-gray-700 mb-1">Catatan Deviasi / Evaluasi</label>
                        <textarea id="eval-notes"
                                  name="discrepancy_notes"
                                  x-model="evalNotes"
                                  rows="3"
                                  placeholder="Catat kendala penyerapan kuota, keterlambatan pembayaran, atau kesesuaian jadwal..."
                                  class="w-full p-3 rounded-xl bg-gray-50 border border-gray-200 text-gray-900 font-medium focus:outline-none focus:ring-2 focus:ring-[#ea580c]"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                        <button type="button" @click="evaluateModalOpen = false" class="px-4 py-2.5 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 cursor-pointer">Batal</button>
                        <button type="submit" class="px-5 py-2.5 rounded-full bg-[#ea580c] hover:bg-[#c2410c] text-white font-bold shadow-xs cursor-pointer">Simpan Evaluasi</button>
                    </div>
                </form>
            </template>
        </div>
    </div>

    {{-- ===================================================== --}}
    {{-- MODAL 3: PERPANJANG MASA KONTRAK                      --}}
    {{-- ===================================================== --}}
    <div x-cloak x-show="extendModalOpen" class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div @click.away="extendModalOpen = false" class="bg-white max-w-md w-full rounded-3xl p-6 sm:p-7 shadow-2xl border border-gray-100 space-y-5">
            <div class="flex justify-between items-start border-b border-gray-100 pb-3">
                <div>
                    <span class="text-[10px] font-mono font-bold text-[#ea580c] uppercase">PERPANJANGAN KONTRAK</span>
                    <h3 class="text-lg font-extrabold text-gray-900">Perpanjang Masa Berlaku</h3>
                    <p class="text-xs text-gray-500" x-text="selectedContract ? selectedContract.contract_code + ' • ' + selectedContract.umkm_name : ''"></p>
                </div>
                <button type="button" @click="extendModalOpen = false" class="text-gray-400 hover:text-gray-700 font-bold text-lg cursor-pointer">✕</button>
            </div>

            <template x-if="selectedContract">
                <form :action="'{{ url('/supplier/contract-monitoring') }}/' + selectedContract.id + '/extend'" method="POST" class="space-y-4 text-xs font-semibold">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="extend-end-date" class="block text-gray-700 mb-1">Tanggal Berakhir Baru</label>
                        <input type="date"
                               id="extend-end-date"
                               name="end_date"
                               min="{{ now()->toDateString() }}"
                               x-model="extendEndDate"
                               required
                               class="w-full p-3 rounded-xl bg-gray-50 border border-gray-200 text-gray-900 font-mono focus:outline-none focus:ring-2 focus:ring-[#ea580c]">
                    </div>

                    <div>
                        <label for="extend-target-volume" class="block text-gray-700 mb-1">Target Volume Kontrak Baru (<span x-text="selectedContract.unit"></span>)</label>
                        <input type="number"
                               id="extend-target-volume"
                               name="total_contract_volume"
                               step="1"
                               min="1"
                               x-model="extendTotalVolume"
                               class="w-full p-3 rounded-xl bg-gray-50 border border-gray-200 text-gray-900 font-mono focus:outline-none focus:ring-2 focus:ring-[#ea580c]">
                        <p class="text-[10px] text-gray-400 mt-1">Opsional — biarkan atau sesuaikan target kuota total kontrak baru.</p>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                        <button type="button" @click="extendModalOpen = false" class="px-4 py-2.5 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 cursor-pointer">Batal</button>
                        <button type="submit" class="px-5 py-2.5 rounded-full bg-[#ea580c] hover:bg-[#c2410c] text-white font-bold shadow-xs cursor-pointer">Simpan Perpanjangan</button>
                    </div>
                </form>
            </template>
        </div>
    </div>

    {{-- ===================================================== --}}
    {{-- MODAL 4: KONTROL STATUS KONTRAK                       --}}
    {{-- ===================================================== --}}
    <div x-cloak x-show="statusModalOpen" class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div @click.away="statusModalOpen = false" class="bg-white max-w-md w-full rounded-3xl p-6 sm:p-7 shadow-2xl border border-gray-100 space-y-5">
            <div class="flex justify-between items-start border-b border-gray-100 pb-3">
                <div>
                    <span class="text-[10px] font-mono font-bold text-[#ea580c] uppercase">KONTROL SIKLUS KONTRAK</span>
                    <h3 class="text-lg font-extrabold text-gray-900">Ubah Status Kemitraan</h3>
                    <p class="text-xs text-gray-500" x-text="selectedContract ? selectedContract.contract_code + ' • ' + selectedContract.umkm_name : ''"></p>
                </div>
                <button type="button" @click="statusModalOpen = false" class="text-gray-400 hover:text-gray-700 font-bold text-lg cursor-pointer">✕</button>
            </div>

            <template x-if="selectedContract">
                <form :action="'{{ url('/supplier/contract-monitoring') }}/' + selectedContract.id + '/status'" method="POST" class="space-y-4 text-xs font-semibold">
                    @csrf
                    @method('PATCH')

                    <div class="space-y-2">
                        <label class="block text-gray-700">Pilih Tindakan Status Kontrak:</label>

                        <label class="flex items-start gap-3 p-3 rounded-2xl border cursor-pointer transition"
                               :class="statusAction === 'activate' ? 'border-emerald-500 bg-emerald-50/50' : 'border-gray-200 hover:bg-gray-50'">
                            <input type="radio" name="action" value="activate" x-model="statusAction" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <span class="font-bold text-gray-900 block">Aktifkan / Lanjutkan Kontrak</span>
                                <span class="text-[11px] text-gray-500 font-normal">Kontrak berjalan normal dan mitra UMKM dapat menerbitkan pesanan rutin.</span>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 p-3 rounded-2xl border cursor-pointer transition"
                               :class="statusAction === 'pause' ? 'border-amber-500 bg-amber-50/50' : 'border-gray-200 hover:bg-gray-50'">
                            <input type="radio" name="action" value="pause" x-model="statusAction" class="mt-0.5 text-amber-600 focus:ring-amber-500">
                            <div>
                                <span class="font-bold text-gray-900 block">Jeda Sementara (Suspend)</span>
                                <span class="text-[11px] text-gray-500 font-normal">Menonaktifkan Auto-Order sementara (misalnya saat ada tagihan overdue atau penyesuaian stok).</span>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 p-3 rounded-2xl border cursor-pointer transition"
                               :class="statusAction === 'terminate' ? 'border-rose-500 bg-rose-50/50' : 'border-gray-200 hover:bg-gray-50'">
                            <input type="radio" name="action" value="terminate" x-model="statusAction" class="mt-0.5 text-rose-600 focus:ring-rose-500">
                            <div>
                                <span class="font-bold text-rose-700 block">Hentikan Kontrak (Terminate)</span>
                                <span class="text-[11px] text-gray-500 font-normal">Mengakhiri kontrak kemitraan dan mematikan seluruh jadwal Auto-Order terkait.</span>
                            </div>
                        </label>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                        <button type="button" @click="statusModalOpen = false" class="px-4 py-2.5 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 cursor-pointer">Batal</button>
                        <button type="submit" class="px-5 py-2.5 rounded-full bg-gray-900 hover:bg-black text-white font-bold shadow-xs cursor-pointer">Terapkan Status</button>
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

