@extends('layouts.supplier')

@section('title', 'Dashboard & Analisis — Portal Pemasok SORAI')

@section('content')
<div class="space-y-7">

    {{-- Header Page --}}
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 border-b border-slate-200/80 pb-5">
        <div class="space-y-1">
            <span class="text-[11px] font-bold text-[#ea580c] uppercase tracking-[0.14em] block">
                PANEL ANALISIS B2B PEMASOK
            </span>
            <h1 class="text-2xl sm:text-[26px] font-bold text-slate-900 tracking-tight">
                Dashboard Analisis Operasional &amp; Kemitraan
            </h1>
            <p class="text-xs sm:text-[13px] text-slate-500 max-w-2xl leading-relaxed">
                Monitoring terpadu pesanan masuk, ketersediaan stok bahan baku, logistik pengiriman subuh, dan performa kerja sama mitra UMKM.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <span class="inline-flex items-center gap-2 px-3.5 py-2 rounded-full text-xs font-semibold bg-white text-slate-700 border border-slate-200 shadow-2xs">
                <span class="w-2 h-2 rounded-full bg-[#ea580c]"></span>
                <span>Periode: {{ $formattedPeriod ?? now()->translatedFormat('F Y') }}</span>
            </span>
            <span class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                <span>Mitra Terverifikasi</span>
            </span>
        </div>
    </div>

    {{-- 4 Pilar Metrics Cards Pemasok --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

        {{-- 1. Total Omset Diterima --}}
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)] hover:border-slate-300/80 transition flex flex-col justify-between gap-4">
            <div class="flex items-start justify-between gap-3">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                    Total Omset Diterima
                </span>
                <div class="w-10 h-10 rounded-xl bg-orange-50 text-[#ea580c] border border-orange-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="space-y-1.5">
                <p class="text-2xl font-bold text-[#ea580c] tracking-tight tabular-nums">
                    Rp {{ number_format($totalOmset, 0, ',', '.') }}
                </p>
                <p class="text-[11px] font-semibold text-emerald-700 inline-flex items-center gap-1 tabular-nums">
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                    <span>+{{ $growthPercentage }}% dari bulan lalu</span>
                </p>
            </div>
        </div>

        {{-- 2. Pesanan Diproses --}}
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)] hover:border-slate-300/80 transition flex flex-col justify-between gap-4">
            <div class="flex items-start justify-between gap-3">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                    Pesanan Diproses
                </span>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
            </div>
            <div class="space-y-1.5">
                <p class="text-2xl font-bold text-slate-900 tracking-tight tabular-nums">
                    {{ $activeOrdersCount }} <span class="text-sm font-semibold text-slate-500">Pesanan</span>
                </p>
                <a href="{{ route('supplier.pengiriman.index', ['tab' => 'pending']) }}"
                   class="text-[11px] font-semibold text-amber-700 hover:text-amber-800 inline-flex items-center gap-1.5 tabular-nums transition-colors">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    <span>{{ $perluPenyesuaianCount }} Perlu Penyesuaian Stok</span>
                </a>
            </div>
        </div>

        {{-- 3. Total Stok Terjual --}}
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)] hover:border-slate-300/80 transition flex flex-col justify-between gap-4">
            <div class="flex items-start justify-between gap-3">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                    Total Stok Terjual
                </span>
                <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 border border-sky-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>
            <div class="space-y-1.5">
                <p class="text-2xl font-bold text-slate-900 tracking-tight tabular-nums">
                    {{ number_format($totalStokTerjual, 0, ',', '.') }} <span class="text-sm font-semibold text-slate-500">kg</span>
                </p>
                <p class="text-[11px] font-semibold text-slate-600 truncate">
                    Utama: {{ $topCommodityName }}
                </p>
            </div>
        </div>

        {{-- 4. Fulfillment Rate --}}
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)] hover:border-slate-300/80 transition flex flex-col justify-between gap-4">
            <div class="flex items-start justify-between gap-3">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                    Fulfillment Rate
                </span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="space-y-1.5">
                <p class="text-2xl font-bold text-emerald-700 tracking-tight tabular-nums">
                    {{ $fulfillmentRate }}%
                </p>
                <p class="text-[11px] font-semibold text-emerald-700 inline-flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Kualitas Sesuai Kontrak</span>
                </p>
            </div>
        </div>

    </div>

    {{-- Grafik Tren Penjualan Bahan Baku (Kg/Bulan) --}}
    <div class="bg-white rounded-2xl p-6 sm:p-7 border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)] space-y-6">

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
            <div>
                <h2 class="font-bold text-base sm:text-lg text-slate-900 tracking-tight">
                    Grafik Tren Penjualan Bahan Baku (Kg/Bulan)
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Perbandingan target pasok dan realisasi pengiriman komoditas ke UMKM mitra
                </p>
            </div>
            <div class="flex items-center gap-4 text-xs font-semibold text-slate-700 bg-slate-50 border border-slate-200/70 px-3.5 py-2 rounded-xl self-start sm:self-auto">
                <span class="flex items-center gap-2">
                    <span class="w-3 h-3 bg-amber-300 rounded-xs inline-block"></span>
                    <span>Target</span>
                </span>
                <span class="flex items-center gap-2">
                    <span class="w-3 h-3 bg-[#ea580c] rounded-xs inline-block"></span>
                    <span>Realisasi</span>
                </span>
            </div>
        </div>

        @php
            $maxChartVal = max(500, ...array_column($monthsTrend, 'target'), ...array_column($monthsTrend, 'realisasi'));
        @endphp
        <div class="pt-4">
            <div class="relative h-52 sm:h-60 flex items-end justify-between gap-3 sm:gap-6 px-2 sm:px-6">
                {{-- Subtle Horizontal Reference Gridlines --}}
                <div class="pointer-events-none absolute inset-x-0 inset-y-0 flex flex-col justify-between">
                    <div class="border-b border-dashed border-slate-200/70 w-full flex justify-end"><span class="text-[10px] font-medium text-slate-400 tabular-nums -mt-2 pr-1">{{ number_format($maxChartVal, 0, ',', '.') }} kg</span></div>
                    <div class="border-b border-dashed border-slate-200/70 w-full flex justify-end"><span class="text-[10px] font-medium text-slate-400 tabular-nums -mt-2 pr-1">{{ number_format($maxChartVal * 0.66, 0, ',', '.') }} kg</span></div>
                    <div class="border-b border-dashed border-slate-200/70 w-full flex justify-end"><span class="text-[10px] font-medium text-slate-400 tabular-nums -mt-2 pr-1">{{ number_format($maxChartVal * 0.33, 0, ',', '.') }} kg</span></div>
                    <div class="border-b border-slate-200 w-full"></div>
                </div>

                @foreach($monthsTrend as $bar)
                    <div class="relative z-10 flex-1 flex flex-col items-center h-full justify-end">
                        <div class="w-full flex items-end justify-center gap-1.5 sm:gap-2.5 h-full">
                            {{-- Target --}}
                            <div style="height: {{ max(2, ($bar['target'] / $maxChartVal) * 100) }}%"
                                 class="w-1/2 max-w-[44px] bg-amber-300/90 hover:bg-amber-400 rounded-t-lg relative group transition-all">
                                <span class="absolute -top-8 left-1/2 -translate-x-1/2 bg-slate-900 text-white text-[10px] font-semibold px-2.5 py-1 rounded-lg opacity-0 group-hover:opacity-100 transition whitespace-nowrap z-20 shadow-lg pointer-events-none tabular-nums">
                                    Target: {{ number_format($bar['target'], 0, ',', '.') }} kg
                                </span>
                            </div>
                            {{-- Realisasi --}}
                            <div style="height: {{ max(2, ($bar['realisasi'] / $maxChartVal) * 100) }}%"
                                 class="w-1/2 max-w-[44px] bg-[#ea580c] hover:bg-[#c2410c] rounded-t-lg relative group transition-all">
                                <span class="absolute -top-8 left-1/2 -translate-x-1/2 bg-slate-900 text-white text-[10px] font-semibold px-2.5 py-1 rounded-lg opacity-0 group-hover:opacity-100 transition whitespace-nowrap z-20 shadow-lg pointer-events-none tabular-nums">
                                    Realisasi: {{ number_format($bar['realisasi'], 0, ',', '.') }} kg
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Label Bulan --}}
            <div class="flex justify-between items-center px-2 sm:px-6 pt-3">
                @foreach($monthsTrend as $bar)
                    <div class="flex-1 text-center text-xs font-semibold {{ str_contains($bar['month'], 'Aktif') ? 'text-[#ea580c]' : 'text-slate-600' }}">
                        {{ $bar['month'] }}
                    </div>
                @endforeach
            </div>
        </div>

    </div>

    {{-- Histori & Riwayat Transaksi Terbaru --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)] overflow-hidden">

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-6 sm:px-7 py-5 border-b border-slate-100">
            <div>
                <h2 class="font-bold text-base sm:text-lg text-slate-900 tracking-tight">
                    Histori &amp; Riwayat Transaksi Terbaru
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Catatan pesanan pasokan komoditas yang telah diproses untuk mitra UMKM
                </p>
            </div>
            <a href="{{ route('supplier.riwayat-transaksi.index') }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#ea580c] hover:text-[#c2410c] bg-orange-50/80 hover:bg-orange-100/80 px-3.5 py-2 rounded-xl border border-orange-200/70 transition self-start sm:self-auto">
                <span>Lihat Semua Transaksi</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left border-collapse">
                <thead class="bg-slate-50/90 text-slate-500 uppercase text-[11px] font-bold tracking-wider border-b border-slate-200/80">
                    <tr>
                        <th class="py-3.5 px-6">Tanggal</th>
                        <th class="py-3.5 px-5">ID Transaksi</th>
                        <th class="py-3.5 px-5">Mitra UMKM</th>
                        <th class="py-3.5 px-5">Komoditas Pasok</th>
                        <th class="py-3.5 px-5">Volume Pasok</th>
                        <th class="py-3.5 px-5">Total Pembayaran</th>
                        <th class="py-3.5 px-6 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($transactions as $trx)
                        @php
                            $statusKey = $trx['status'];
                            $badgeClasses = match($statusKey) {
                                'MENUNGGU_KONFIRMASI_BARANG' => 'bg-amber-50 text-amber-800 border border-amber-200/80',
                                'PENYESUAIAN_STOK' => 'bg-sky-50 text-sky-800 border border-sky-200/80',
                                'DIKIRIM' => 'bg-indigo-50 text-indigo-800 border border-indigo-200/80',
                                'SELESAI' => 'bg-emerald-50 text-emerald-800 border border-emerald-200/80',
                                default => 'bg-emerald-50 text-emerald-800 border border-emerald-200/80',
                            };
                            $statusLabel = match($statusKey) {
                                'MENUNGGU_KONFIRMASI_BARANG' => 'Menunggu Konfirmasi Barang',
                                'PENYESUAIAN_STOK' => 'Penyesuaian Stok',
                                'DIKIRIM' => 'Dikirim',
                                'SELESAI' => 'Selesai',
                                default => ucwords(strtolower(str_replace('_', ' ', $statusKey))),
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-4 px-6 text-xs text-slate-500 font-medium whitespace-nowrap tabular-nums">
                                {{ $trx['date'] ?? '01/10/2026' }}
                            </td>
                            <td class="py-4 px-5 text-xs font-bold text-[#ea580c] whitespace-nowrap tabular-nums">
                                {{ $trx['id'] }}
                            </td>
                            <td class="py-4 px-5 font-semibold text-slate-900 whitespace-nowrap">
                                {{ $trx['customerName'] }}
                            </td>
                            <td class="py-4 px-5 text-slate-700 font-medium">
                                {{ $trx['commodity'] }}
                            </td>
                            <td class="py-4 px-5 text-slate-800 font-semibold whitespace-nowrap tabular-nums">
                                {{ $trx['volume'] }}
                            </td>
                            <td class="py-4 px-5 font-bold text-slate-900 whitespace-nowrap tabular-nums">
                                {{ $trx['total'] }}
                            </td>
                            <td class="py-4 px-6 text-center whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold {{ $badgeClasses }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 px-6 text-center">
                                <div class="flex flex-col items-center gap-2 text-slate-400">
                                    <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <span class="text-xs font-medium text-slate-500">Belum ada data transaksi tercatat.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

</div>
@endsection
