@extends('layouts.supplier')

@section('title', 'Riwayat Transaksi — Portal Pemasok SORAI')

@section('content')
<div class="space-y-7 w-full" x-data="riwayatTransaksiPage()">

    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 border-b border-slate-200/80 pb-5">
        <div class="space-y-1">
            <span class="text-[11px] font-bold uppercase text-[#ea580c] tracking-[0.14em] block">
                MONITORING KEUANGAN &amp; PEMBAYARAN B2B
            </span>
            <h1 class="text-2xl sm:text-[26px] font-bold text-slate-900 tracking-tight uppercase">
                Riwayat Transaksi Pemasok
            </h1>
            <p class="text-xs sm:text-[13px] text-slate-500 max-w-2xl leading-relaxed">
                Histori penerimaan dana, pelacakan tagihan invoice pasokan, dan status penyelesaian pembayaran mitra UMKM.
            </p>
        </div>

        <div class="flex items-center gap-2.5 shrink-0">
            <button type="button"
                    @click="printRekap()"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition shadow-2xs cursor-pointer">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>Cetak Rekap</span>
            </button>
        </div>
    </div>

    {{-- 2 Kartu Statistik Keuangan --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

        {{-- Card 1: Total Uang yang Didapat (Lunas) --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-[0_1px_2px_rgba(15,23,42,0.04)] hover:border-emerald-200 transition flex items-start justify-between gap-4">
            <div class="space-y-1.5 min-w-0">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">
                    Total Uang yang Didapat
                </span>
                <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight tabular-nums">
                    {{ $totalUangDidapatFormatted }}
                </h2>
                <p class="text-xs text-slate-500 leading-relaxed pt-1">
                    Total pembayaran dari <strong class="text-slate-700 tabular-nums">{{ $countLunas }} transaksi</strong> yang telah berstatus lunas (settlement / paid).
                </p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 shrink-0 border border-emerald-100">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        {{-- Card 2: Total Belum Dibayar (Pending / Unpaid) --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-[0_1px_2px_rgba(15,23,42,0.04)] hover:border-amber-200 transition flex items-start justify-between gap-4">
            <div class="space-y-1.5 min-w-0">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">
                    Total Belum Dibayar
                </span>
                <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight tabular-nums">
                    {{ $totalBelumDibayarFormatted }}
                </h2>
                <p class="text-xs text-slate-500 leading-relaxed pt-1">
                    Tagihan terbuka dari <strong class="text-slate-700 tabular-nums">{{ $countBelumDibayar }} invoice</strong> yang masih menunggu pembayaran mitra UMKM.
                </p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600 shrink-0 border border-amber-100">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

    </div>

    {{-- Main Card: Tabel Histori & Riwayat Transaksi --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-7 space-y-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">

        {{-- Title & Status Filter Bar --}}
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div>
                <h2 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight">
                    Histori Seluruh Transaksi
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Klik <strong class="text-slate-700">Detail</strong> untuk meninjau rincian tagihan atau ikon cetak untuk faktur invoice resmi.
                </p>
            </div>

            {{-- Status Filter Segmented Bar --}}
            <div class="inline-flex flex-wrap items-center gap-1 bg-slate-100/90 p-1 rounded-xl self-start lg:self-auto">
                <a href="{{ route('supplier.riwayat-transaksi.index', ['status' => 'all', 'search' => request('search')]) }}"
                   class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition tabular-nums {{ ($statusFilter === 'all' || !$statusFilter) ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                    Semua ({{ $totalCount }})
                </a>
                <a href="{{ route('supplier.riwayat-transaksi.index', ['status' => 'paid', 'search' => request('search')]) }}"
                   class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition tabular-nums {{ $statusFilter === 'paid' ? 'bg-white text-emerald-700 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                    Lunas ({{ $countLunas }})
                </a>
                <a href="{{ route('supplier.riwayat-transaksi.index', ['status' => 'unpaid', 'search' => request('search')]) }}"
                   class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition tabular-nums {{ $statusFilter === 'unpaid' ? 'bg-white text-amber-700 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                    Belum Dibayar ({{ $countBelumDibayar }})
                </a>
            </div>
        </div>

        {{-- Search Input --}}
        <form method="GET" action="{{ route('supplier.riwayat-transaksi.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
            <input type="hidden" name="status" value="{{ $statusFilter }}">
            <div class="relative w-full sm:max-w-sm">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="Cari No. Invoice atau Mitra UMKM..."
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs font-medium text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c] transition">
            </div>
            <div class="flex items-center gap-2">
                <button type="submit"
                        class="px-5 py-2.5 bg-[#ea580c] hover:bg-[#c2410c] text-white text-xs font-semibold rounded-xl transition shadow-2xs inline-flex items-center justify-center gap-1.5 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <span>Cari</span>
                </button>
                @if($search)
                    <a href="{{ route('supplier.riwayat-transaksi.index', ['status' => $statusFilter]) }}"
                       class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>

        {{-- Tabel Transaksi --}}
        <div class="overflow-x-auto border border-slate-200/80 rounded-xl">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-50 text-slate-500 uppercase text-[11px] font-bold border-b border-slate-200/80 tracking-wider">
                    <tr>
                        <th class="py-3.5 px-5">Tanggal</th>
                        <th class="py-3.5 px-5">No. Invoice</th>
                        <th class="py-3.5 px-5">Mitra UMKM</th>
                        <th class="py-3.5 px-5">Total Tagihan</th>
                        <th class="py-3.5 px-5 text-center">Status</th>
                        <th class="py-3.5 px-5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($transactions as $payment)
                        @php
                            $order = $payment->order;
                            $umkm = $order?->partnership?->umkm;
                            $firstItem = $order?->items?->first();
                            $commodity = $firstItem?->supplierProduct?->commodity;
                            $isPaid = $payment->isPaid();

                            $dateText = $payment->created_at ? $payment->created_at->locale('id')->translatedFormat('d M Y') : '-';
                            $timeText = $payment->created_at ? $payment->created_at->format('H:i').' WIB' : '-';
                            $invoiceCode = $payment->invoice_number ?: ('INV/'.strtoupper(substr((string) ($order?->id ?? $payment->id), 0, 8)).'/B'.($payment->shipment_batch ?? 1));
                            $orderCode = 'ORD-'.($payment->created_at ? $payment->created_at->format('Y') : '2026').'-'.strtoupper(substr((string) ($order?->id ?? $payment->id), 0, 6));
                            $umkmName = $umkm?->business_name ?: ($umkm?->owner_name ?: 'Mitra UMKM');
                            $ownerName = $umkm?->owner_name ?: 'Pengelola UMKM';
                            $amountFormatted = 'Rp '.number_format((float) $payment->amount, 0, ',', '.');
                            $qtyFormatted = number_format((float) ($payment->quantity_billed ?? $firstItem?->quantity ?? 0), 0, ',', '.').' '.($commodity?->unit ?? 'Kg');

                            $trxJson = [
                                'id' => (string) $payment->id,
                                'invoice_number' => $invoiceCode,
                                'order_code' => $orderCode,
                                'date' => $dateText,
                                'time' => $timeText,
                                'customer_name' => $umkmName,
                                'owner_name' => $ownerName,
                                'customer_phone' => $umkm?->phone ?: '-',
                                'customer_address' => $umkm?->address ?: 'Kota Malang, Jawa Timur',
                                'commodity_name' => $commodity?->name ?: 'Komoditas Pangan Segar',
                                'quantity_formatted' => $qtyFormatted,
                                'batch_label' => 'Batch '.($payment->shipment_batch ?? 1),
                                'amount' => (float) $payment->amount,
                                'amount_formatted' => $amountFormatted,
                                'status' => $isPaid ? 'PAID' : 'UNPAID',
                                'status_label' => $isPaid ? 'Lunas' : 'Belum Dibayar',
                                'payment_method' => $payment->payment_method ?: ($isPaid ? 'Transfer Bank Escrow' : 'Menunggu Pembayaran (Midtrans)'),
                                'paid_at' => $payment->paid_at ? \Carbon\Carbon::parse($payment->paid_at)->locale('id')->translatedFormat('d M Y, H:i').' WIB' : '-',
                                'notes' => $payment->notes ?: 'Tagihan suplai bahan baku sesuai kontrak kemitraan.',
                            ];
                        @endphp
                        <tr class="hover:bg-orange-50/30 transition-colors">
                            <td class="py-3.5 px-5 whitespace-nowrap">
                                <span class="font-semibold text-slate-900 tabular-nums">{{ $dateText }}</span>
                            </td>

                            <td class="py-3.5 px-5 whitespace-nowrap">
                                <span class="font-bold text-[#ea580c] tracking-tight tabular-nums">
                                    {{ $invoiceCode }}
                                </span>
                            </td>

                            <td class="py-3.5 px-5 whitespace-nowrap">
                                <span class="font-semibold text-slate-900">{{ $umkmName }}</span>
                            </td>

                            <td class="py-3.5 px-5 whitespace-nowrap">
                                <span class="font-bold text-slate-900 tabular-nums">
                                    {{ $amountFormatted }}
                                </span>
                            </td>

                            <td class="py-3.5 px-5 whitespace-nowrap text-center">
                                @if($isPaid)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Lunas</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200/80">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        <span>Belum Dibayar</span>
                                    </span>
                                @endif
                            </td>

                            <td class="py-3.5 px-5 whitespace-nowrap text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button"
                                            @click="openDetailModal(@js($trxJson))"
                                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-white hover:bg-[#ea580c] text-slate-700 hover:text-white border border-slate-200 hover:border-[#ea580c] text-xs font-semibold transition cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <span>Detail</span>
                                    </button>
                                    <button type="button"
                                            @click="printInvoice(@js($trxJson))"
                                            title="Cetak Faktur Invoice Resmi"
                                            class="p-2 rounded-lg bg-white hover:bg-slate-50 text-slate-500 hover:text-[#ea580c] border border-slate-200 transition cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <span class="text-xs font-medium text-slate-500">Tidak ada riwayat transaksi yang tercatat pada database</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- Pagination Links --}}
            @if($transactions->hasPages())
                <div class="px-5 py-4 border-t border-slate-100 bg-slate-50/70">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>

    </div>

    {{-- MODAL DETAIL INVOICE & TRANSAKSI LENGKAP --}}
    <div x-show="showModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title"
         role="dialog"
         aria-modal="true">

        <div class="fixed inset-0 bg-slate-950/55 backdrop-blur-xs transition-opacity"
             @click="closeDetailModal()"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-xl border border-slate-200/80"
                 @click.stop>

                {{-- Modal Header --}}
                <div class="bg-slate-50/90 px-6 py-5 border-b border-slate-100 flex items-center justify-between gap-4">
                    <div>
                        <span class="text-[11px] font-bold uppercase text-[#ea580c] tracking-wider block">
                            Detail Lengkap Transaksi
                        </span>
                        <div class="flex items-center gap-2 mt-1 flex-wrap">
                            <h3 class="text-base font-bold text-slate-900 tabular-nums" x-text="selectedTrx.invoice_number"></h3>
                            <span class="text-[11px] px-2 py-0.5 rounded-md bg-slate-200/80 text-slate-700 font-semibold tabular-nums" x-text="selectedTrx.order_code"></span>
                        </div>
                    </div>
                    <button type="button"
                            @click="closeDetailModal()"
                            class="w-8 h-8 rounded-full bg-white hover:bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-500 transition cursor-pointer shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="p-6 space-y-5 text-xs text-slate-600">

                    {{-- Status & Nominal Banner --}}
                    <div class="p-4 rounded-xl flex items-center justify-between gap-4 border"
                         :class="selectedTrx.status === 'PAID' ? 'bg-emerald-50/70 border-emerald-200/80' : 'bg-amber-50/70 border-amber-200/80'">
                        <div>
                            <span class="text-[10px] uppercase font-bold block tracking-wider"
                                  :class="selectedTrx.status === 'PAID' ? 'text-emerald-700' : 'text-amber-700'">Status Pembayaran</span>
                            <span class="text-base font-bold mt-0.5 block"
                                  :class="selectedTrx.status === 'PAID' ? 'text-emerald-900' : 'text-amber-900'"
                                  x-text="selectedTrx.status_label"></span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-slate-500 block font-semibold uppercase tracking-wider">Total Tagihan</span>
                            <span class="text-lg font-bold text-slate-900 tabular-nums mt-0.5 block" x-text="selectedTrx.amount_formatted"></span>
                        </div>
                    </div>

                    {{-- 1. Detail Komoditas & Pasokan --}}
                    <div class="space-y-2">
                        <h4 class="font-bold text-slate-800 text-[11px] uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-[#ea580c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <span>Komoditas &amp; Pengiriman</span>
                        </h4>
                        <div class="p-4 rounded-xl border border-slate-200/70 bg-slate-50/70 space-y-2.5">
                            <div class="flex justify-between items-center gap-4">
                                <span class="text-slate-500">Nama Komoditas</span>
                                <span class="font-semibold text-slate-900 text-right" x-text="selectedTrx.commodity_name"></span>
                            </div>
                            <div class="flex justify-between items-center gap-4">
                                <span class="text-slate-500">Volume Pasok</span>
                                <span class="font-bold text-[#ea580c] tabular-nums" x-text="selectedTrx.quantity_formatted"></span>
                            </div>
                            <div class="flex justify-between items-center gap-4">
                                <span class="text-slate-500">Tahap Pengiriman</span>
                                <span class="font-semibold text-slate-800 bg-white px-2.5 py-0.5 rounded-md border border-slate-200 text-[11px]" x-text="selectedTrx.batch_label"></span>
                            </div>
                        </div>
                    </div>

                    {{-- 2. Data Mitra UMKM Pemesan --}}
                    <div class="space-y-2">
                        <h4 class="font-bold text-slate-800 text-[11px] uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-[#ea580c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <span>Informasi Mitra UMKM</span>
                        </h4>
                        <div class="p-4 rounded-xl border border-slate-200/70 bg-slate-50/70 space-y-2">
                            <div>
                                <div class="font-bold text-slate-900 text-sm" x-text="selectedTrx.customer_name"></div>
                                <div class="text-[11px] text-slate-500 mt-0.5 tabular-nums" x-text="'Pemilik: ' + selectedTrx.owner_name + ' • ' + selectedTrx.customer_phone"></div>
                            </div>
                            <div class="pt-2 border-t border-slate-200/70 text-[11px] text-slate-600 flex items-start gap-1.5">
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span x-text="selectedTrx.customer_address"></span>
                            </div>
                        </div>
                    </div>

                    {{-- 3. Informasi Waktu & Metode Pembayaran --}}
                    <div class="space-y-2">
                        <h4 class="font-bold text-slate-800 text-[11px] uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-[#ea580c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                            <span>Waktu &amp; Metode Transaksi</span>
                        </h4>
                        <div class="p-4 rounded-xl border border-slate-200/70 bg-slate-50/70 space-y-2">
                            <div class="flex justify-between items-center py-0.5 gap-4">
                                <span class="text-slate-500">Waktu Order / Dibuat</span>
                                <span class="font-semibold text-slate-800 tabular-nums" x-text="selectedTrx.date + ', ' + selectedTrx.time"></span>
                            </div>
                            <div class="flex justify-between items-center py-0.5 gap-4">
                                <span class="text-slate-500">Kanal Pembayaran</span>
                                <span class="font-semibold text-slate-800" x-text="selectedTrx.payment_method"></span>
                            </div>
                            <div class="flex justify-between items-center py-0.5 gap-4">
                                <span class="text-slate-500">Waktu Pelunasan</span>
                                <span class="font-semibold text-slate-800 tabular-nums" x-text="selectedTrx.paid_at"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Catatan Tambahan --}}
                    <div class="p-3.5 bg-orange-50/70 rounded-xl border border-orange-200/70 text-[11px] text-slate-600">
                        <span class="font-bold text-[#c2410c]">Catatan Invoice:</span>
                        <p class="mt-0.5 leading-relaxed" x-text="selectedTrx.notes"></p>
                    </div>

                </div>

                {{-- Modal Footer --}}
                <div class="bg-slate-50/90 px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
                    <button type="button"
                            @click="closeDetailModal()"
                            class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 text-xs font-semibold transition cursor-pointer">
                        Tutup
                    </button>
                    <button type="button"
                            @click="printInvoice(selectedTrx)"
                            class="px-5 py-2 rounded-xl bg-[#ea580c] hover:bg-[#c2410c] text-white text-xs font-semibold transition shadow-2xs inline-flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        <span>Cetak Invoice</span>
                    </button>
                </div>

            </div>
        </div>
    </div>

</div>

<script>
    function riwayatTransaksiPage() {
        return {
            showModal: false,
            selectedTrx: {},
            supplier: @js($supplierData),
            allTrx: @js($allTransactions),
            totalUangDidapat: @js($totalUangDidapatFormatted),
            totalBelumDibayar: @js($totalBelumDibayarFormatted),
            countLunas: @js($countLunas),
            countBelumDibayar: @js($countBelumDibayar),

            openDetailModal(trx) {
                this.selectedTrx = trx;
                this.showModal = true;
            },

            closeDetailModal() {
                this.showModal = false;
                this.selectedTrx = {};
            },

            printInvoice(trx) {
                const supplier = this.supplier;
                const isPaid = trx.status === 'PAID';

                const invoiceHtml = `
                    <div style="max-width:800px; margin:0 auto; padding:24px; font-family:'Montserrat', 'Segoe UI', Arial, sans-serif; color:#0f172a; background:#ffffff;">

                        <table style="width:100%; border-collapse:collapse; margin-bottom:20px; border-bottom:2px solid #0f172a; padding-bottom:16px;">
                            <tr>
                                <td style="vertical-align:top; width:58%;">
                                    <div style="font-size:24px; font-weight:800; color:#ea580c; letter-spacing:1px; line-height:1;">
                                        SORAI <span style="font-size:11px; font-weight:700; color:#64748b; letter-spacing:1.5px; border-left:1px solid #cbd5e1; padding-left:8px;">PORTAL PEMASOK B2B</span>
                                    </div>
                                    <div style="margin-top:10px; font-size:14px; font-weight:700; color:#0f172a;">${supplier.company_name}</div>
                                    <div style="font-size:11px; color:#475569; line-height:1.4; margin-top:3px;">${supplier.warehouse_address}</div>
                                    <div style="font-size:11px; color:#475569; margin-top:2px;">Telp / WA: <strong>${supplier.phone}</strong> | NIB: <strong>${supplier.nib_number}</strong></div>
                                </td>
                                <td style="vertical-align:top; text-align:right; width:42%;">
                                    <div style="font-size:26px; font-weight:800; color:#0f172a; letter-spacing:2px; line-height:1;">INVOICE</div>
                                    <div style="font-size:10px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:1px; margin-top:2px;">FAKTUR PENJUALAN KOMERSIAL</div>
                                    <table style="font-size:11px; margin-top:10px; margin-left:auto; text-align:left; border-collapse:collapse; font-variant-numeric:tabular-nums;">
                                        <tr>
                                            <td style="padding:2px 8px; color:#64748b;">No. Invoice</td>
                                            <td style="padding:2px 8px; font-weight:700; color:#ea580c;">: ${trx.invoice_number}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:2px 8px; color:#64748b;">No. Pesanan</td>
                                            <td style="padding:2px 8px; font-weight:700;">: ${trx.order_code}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:2px 8px; color:#64748b;">Tanggal Terbit</td>
                                            <td style="padding:2px 8px; font-weight:600;">: ${trx.date}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:2px 8px; color:#64748b;">Jatuh Tempo</td>
                                            <td style="padding:2px 8px; font-weight:600;">: 24 Jam Pasca Kirim</td>
                                        </tr>
                                    </table>
                                    <div style="margin-top:10px;">
                                        ${isPaid
                                            ? '<span style="display:inline-block; border:2px solid #059669; color:#059669; font-weight:800; font-size:11px; padding:4px 14px; border-radius:4px; text-transform:uppercase; letter-spacing:1.2px;">LUNAS / PAID</span>'
                                            : '<span style="display:inline-block; border:2px solid #d97706; color:#d97706; font-weight:800; font-size:11px; padding:4px 14px; border-radius:4px; text-transform:uppercase; letter-spacing:1.2px;">MENUNGGU PEMBAYARAN</span>'
                                        }
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <table style="width:100%; border-collapse:collapse; margin-bottom:20px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;">
                            <tr>
                                <td style="padding:14px; width:50%; vertical-align:top; border-right:1px solid #e2e8f0;">
                                    <div style="font-size:10px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:1px; margin-bottom:4px;">DITUJUKAN KEPADA (BUYER / MITRA UMKM):</div>
                                    <div style="font-size:14px; font-weight:700; color:#0f172a;">${trx.customer_name}</div>
                                    <div style="font-size:11px; color:#475569; margin-top:2px;">Penanggung Jawab: <strong>${trx.owner_name}</strong></div>
                                    <div style="font-size:11px; color:#475569;">Kontak Telepon: <strong>${trx.customer_phone}</strong></div>
                                    <div style="font-size:11px; color:#475569; margin-top:4px; line-height:1.4;">Alamat Kirim: ${trx.customer_address}</div>
                                </td>
                                <td style="padding:14px; width:50%; vertical-align:top;">
                                    <div style="font-size:10px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:1px; margin-bottom:4px;">INFORMASI PENGIRIMAN &amp; PEMBAYARAN:</div>
                                    <div style="font-size:11px; color:#475569; line-height:1.6;">
                                        <div>Kanal Pembayaran: <strong>${trx.payment_method}</strong></div>
                                        <div>Waktu Transaksi: <strong>${trx.date}, ${trx.time}</strong></div>
                                        <div>Waktu Pelunasan: <strong>${trx.paid_at}</strong></div>
                                        <div>Sistem Pengaman: <strong>Rekening Escrow B2B SORAI</strong></div>
                                        <div>Tahap Logistik: <strong>${trx.batch_label}</strong></div>
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <table style="width:100%; border-collapse:collapse; margin-bottom:20px; font-size:11px;">
                            <thead>
                                <tr style="background:#0f172a; color:#ffffff;">
                                    <th style="padding:9px 12px; text-align:center; width:35px; border:1px solid #0f172a;">NO</th>
                                    <th style="padding:9px 12px; text-align:left; border:1px solid #0f172a;">DESKRIPSI BARANG / KOMODITAS</th>
                                    <th style="padding:9px 12px; text-align:center; width:90px; border:1px solid #0f172a;">TAHAP</th>
                                    <th style="padding:9px 12px; text-align:center; width:100px; border:1px solid #0f172a;">VOLUME</th>
                                    <th style="padding:9px 12px; text-align:right; width:130px; border:1px solid #0f172a;">TOTAL HARGA (IDR)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="padding:12px; text-align:center; border:1px solid #e2e8f0; vertical-align:top;">1</td>
                                    <td style="padding:12px; border:1px solid #e2e8f0; vertical-align:top;">
                                        <div style="font-weight:700; font-size:12px; color:#0f172a;">${trx.commodity_name}</div>
                                        <div style="font-size:10px; color:#64748b; margin-top:3px;">Pasokan bahan baku berkualitas sesuai standar kontrak kemitraan Sorai.</div>
                                    </td>
                                    <td style="padding:12px; text-align:center; border:1px solid #e2e8f0; vertical-align:top;">
                                        <span style="background:#f1f5f9; padding:2px 8px; border-radius:4px; font-weight:700;">${trx.batch_label}</span>
                                    </td>
                                    <td style="padding:12px; text-align:center; font-weight:700; font-variant-numeric:tabular-nums; border:1px solid #e2e8f0; vertical-align:top;">
                                        ${trx.quantity_formatted}
                                    </td>
                                    <td style="padding:12px; text-align:right; font-weight:700; font-size:13px; font-variant-numeric:tabular-nums; border:1px solid #e2e8f0; vertical-align:top;">
                                        ${trx.amount_formatted}
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <table style="width:100%; border-collapse:collapse; margin-bottom:25px;">
                            <tr>
                                <td style="width:55%; vertical-align:top; padding-right:20px;">
                                    <div style="font-size:10px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:4px;">CATATAN INVOICE:</div>
                                    <div style="padding:10px 12px; background:#fff7ed; border-left:3px solid #ea580c; font-size:11px; color:#9a3412; line-height:1.4; border-radius:2px;">
                                        ${trx.notes}
                                    </div>
                                    <div style="font-size:10px; color:#64748b; margin-top:8px; line-height:1.4;">
                                        <em>*Dokumen faktur ini sah dan diterbitkan secara digital melalui Portal Pemasok Sorai. Seluruh pembayaran diproses melalui Escrow resmi untuk menjamin keamanan transaksi kedua pihak.</em>
                                    </div>
                                </td>
                                <td style="width:45%; vertical-align:top;">
                                    <table style="width:100%; font-size:11px; border-collapse:collapse; font-variant-numeric:tabular-nums;">
                                        <tr>
                                            <td style="padding:6px 8px; color:#475569;">Subtotal Barang</td>
                                            <td style="padding:6px 8px; text-align:right; font-weight:700;">${trx.amount_formatted}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:6px 8px; color:#475569;">PPN Bahan Baku Pangan (0%)</td>
                                            <td style="padding:6px 8px; text-align:right; color:#64748b;">Rp 0</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:6px 8px; color:#475569;">Biaya Layanan Escrow Sorai</td>
                                            <td style="padding:6px 8px; text-align:right; color:#059669; font-weight:700;">Rp 0 (Bebas Biaya)</td>
                                        </tr>
                                        <tr style="border-top:2px solid #0f172a; border-bottom:2px solid #0f172a; background:#f8fafc;">
                                            <td style="padding:10px 8px; font-size:12px; font-weight:800; color:#0f172a;">TOTAL TAGIHAN:</td>
                                            <td style="padding:10px 8px; text-align:right; font-size:16px; font-weight:800; color:#ea580c;">${trx.amount_formatted}</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <table style="width:100%; border-collapse:collapse; margin-top:35px; padding-top:20px; border-top:1px dashed #cbd5e1; text-align:center; font-size:11px;">
                            <tr>
                                <td style="width:50%; vertical-align:bottom; padding-bottom:5px;">
                                    <div style="color:#64748b; font-size:10px; text-transform:uppercase; font-weight:700;">Penerima Barang / Mitra UMKM</div>
                                    <div style="height:65px;"></div>
                                    <div style="font-weight:700; border-top:1px solid #94a3b8; display:inline-block; min-width:200px; padding-top:4px;">${trx.owner_name}</div>
                                    <div style="color:#64748b; font-size:10px;">${trx.customer_name}</div>
                                </td>
                                <td style="width:50%; vertical-align:bottom; padding-bottom:5px;">
                                    <div style="color:#64748b; font-size:10px; text-transform:uppercase; font-weight:700;">Diterbitkan Oleh (Pemasok)</div>
                                    <div style="height:65px;"></div>
                                    <div style="font-weight:700; border-top:1px solid #94a3b8; display:inline-block; min-width:200px; padding-top:4px;">${supplier.owner_name}</div>
                                    <div style="color:#64748b; font-size:10px;">${supplier.company_name}</div>
                                </td>
                            </tr>
                        </table>

                    </div>
                `;

                this.triggerPrint("Faktur_Invoice_" + trx.invoice_number.replace(/[^a-zA-Z0-9]/g, '_'), invoiceHtml);
            },

            printRekap() {
                const supplier = this.supplier;
                const items = this.allTrx;
                const todayFormatted = new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });

                let rowsHtml = '';
                items.forEach((trx, index) => {
                    const isPaid = trx.status === 'PAID';
                    rowsHtml += `
                        <tr style="border-bottom:1px solid #e2e8f0; font-variant-numeric:tabular-nums;">
                            <td style="padding:8px; text-align:center;">${index + 1}</td>
                            <td style="padding:8px;">${trx.date}</td>
                            <td style="padding:8px; font-weight:700; color:#ea580c;">${trx.invoice_number}</td>
                            <td style="padding:8px; font-weight:700; color:#0f172a;">${trx.customer_name}</td>
                            <td style="padding:8px;">${trx.commodity_name} <span style="color:#64748b; font-size:10px;">(${trx.quantity_formatted})</span></td>
                            <td style="padding:8px; text-align:right; font-weight:700;">${trx.amount_formatted}</td>
                            <td style="padding:8px; text-align:center;">
                                <span style="font-weight:700; font-size:10px; padding:2px 8px; border-radius:4px; ${isPaid ? 'color:#059669; background:#ecfdf5;' : 'color:#d97706; background:#fffbeb;'}">
                                    ${trx.status_label}
                                </span>
                            </td>
                        </tr>
                    `;
                });

                const rekapHtml = `
                    <div style="max-width:850px; margin:0 auto; padding:24px; font-family:'Montserrat', 'Segoe UI', Arial, sans-serif; color:#0f172a; background:#ffffff;">

                        <table style="width:100%; border-collapse:collapse; margin-bottom:16px; border-bottom:2px solid #0f172a; padding-bottom:12px;">
                            <tr>
                                <td style="vertical-align:top; width:65%;">
                                    <div style="font-size:24px; font-weight:800; color:#ea580c; letter-spacing:1px; line-height:1;">
                                        SORAI <span style="font-size:11px; font-weight:700; color:#64748b; letter-spacing:1.5px; border-left:1px solid #cbd5e1; padding-left:8px;">PORTAL PEMASOK B2B</span>
                                    </div>
                                    <div style="margin-top:8px; font-size:14px; font-weight:700; color:#0f172a;">${supplier.company_name}</div>
                                    <div style="font-size:11px; color:#475569;">${supplier.warehouse_address} | Telp: ${supplier.phone}</div>
                                </td>
                                <td style="vertical-align:top; text-align:right; width:35%;">
                                    <div style="font-size:16px; font-weight:800; color:#0f172a; text-transform:uppercase;">REKAPITULASI TRANSAKSI</div>
                                    <div style="font-size:11px; color:#64748b; margin-top:4px;">Tanggal Cetak: <strong>${todayFormatted}</strong></div>
                                    <div style="font-size:11px; color:#64748b;">Status Akun: <strong>Pemasok Terverifikasi</strong></div>
                                </td>
                            </tr>
                        </table>

                        <table style="width:100%; border-collapse:collapse; margin-bottom:20px; text-align:center; font-variant-numeric:tabular-nums;">
                            <tr>
                                <td style="padding:12px; background:#ecfdf5; border:1px solid #a7f3d0; border-radius:6px; width:48%;">
                                    <div style="font-size:10px; font-weight:700; color:#059669; text-transform:uppercase;">TOTAL DANA DITERIMA (LUNAS)</div>
                                    <div style="font-size:20px; font-weight:800; color:#065f46; margin-top:2px;">${this.totalUangDidapat}</div>
                                    <div style="font-size:10px; color:#047857; margin-top:2px;">Dari ${this.countLunas} transaksi tuntas</div>
                                </td>
                                <td style="width:4%;"></td>
                                <td style="padding:12px; background:#fffbeb; border:1px solid #fde68a; border-radius:6px; width:48%;">
                                    <div style="font-size:10px; font-weight:700; color:#d97706; text-transform:uppercase;">TOTAL BELUM DIBAYAR (TERTUNDA)</div>
                                    <div style="font-size:20px; font-weight:800; color:#92400e; margin-top:2px;">${this.totalBelumDibayar}</div>
                                    <div style="font-size:10px; color:#b45309; margin-top:2px;">Dari ${this.countBelumDibayar} tagihan berjalan</div>
                                </td>
                            </tr>
                        </table>

                        <table style="width:100%; border-collapse:collapse; margin-bottom:24px; font-size:10.5px;">
                            <thead>
                                <tr style="background:#0f172a; color:#ffffff;">
                                    <th style="padding:8px; text-align:center; width:30px;">NO</th>
                                    <th style="padding:8px; text-align:left; width:80px;">TANGGAL</th>
                                    <th style="padding:8px; text-align:left; width:120px;">NO. INVOICE</th>
                                    <th style="padding:8px; text-align:left;">MITRA UMKM</th>
                                    <th style="padding:8px; text-align:left;">KOMODITAS &amp; VOLUME</th>
                                    <th style="padding:8px; text-align:right; width:110px;">TOTAL TAGIHAN</th>
                                    <th style="padding:8px; text-align:center; width:95px;">STATUS</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${rowsHtml}
                            </tbody>
                        </table>

                        <table style="width:100%; border-collapse:collapse; margin-top:30px; font-size:11px;">
                            <tr>
                                <td style="width:65%; vertical-align:top; color:#64748b; font-size:10px; line-height:1.4;">
                                    <div><strong>Catatan Rekapitulasi:</strong></div>
                                    <div>Laporan keuangan ini mencakup seluruh transaksi yang tercatat pada basis data B2B Sorai. Seluruh data diverifikasi secara berkala dengan rekening penampung escrow.</div>
                                </td>
                                <td style="width:35%; text-align:center; vertical-align:bottom;">
                                    <div style="color:#64748b; font-size:10px;">Disiapkan &amp; Diverifikasi Oleh:</div>
                                    <div style="height:55px;"></div>
                                    <div style="font-weight:700; border-top:1px solid #94a3b8; display:inline-block; min-width:180px; padding-top:4px;">${supplier.owner_name}</div>
                                    <div style="color:#64748b; font-size:10px;">${supplier.company_name}</div>
                                </td>
                            </tr>
                        </table>

                    </div>
                `;

                this.triggerPrint("Rekapitulasi_Transaksi_Pemasok_" + new Date().toISOString().slice(0, 10), rekapHtml);
            },

            triggerPrint(docTitle, contentHtml) {
                const printFrame = document.createElement('iframe');
                printFrame.style.position = 'fixed';
                printFrame.style.right = '0';
                printFrame.style.bottom = '0';
                printFrame.style.width = '0';
                printFrame.style.height = '0';
                printFrame.style.border = '0';
                printFrame.style.opacity = '0';
                document.body.appendChild(printFrame);

                const frameDoc = printFrame.contentWindow.document;
                frameDoc.open();
                frameDoc.write('<!DOCTYPE html><html lang="id"><head>');
                frameDoc.write('<meta charset="utf-8">');
                frameDoc.write('<title>' + docTitle + '</title>');
                frameDoc.write('<link rel="preconnect" href="https://fonts.googleapis.com">');
                frameDoc.write('<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>');
                frameDoc.write('<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">');
                frameDoc.write('<style>');
                frameDoc.write('@page { size: A4 portrait; margin: 12mm 10mm; }');
                frameDoc.write('* { box-sizing: border-box; font-family: "Montserrat", "Segoe UI", Arial, sans-serif; }');
                frameDoc.write('body { margin: 0; padding: 0; background: #ffffff !important; color: #0f172a !important; font-family: "Montserrat", "Segoe UI", Arial, sans-serif; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }');
                frameDoc.write('</' + 'style>');
                frameDoc.write('</' + 'head>');
                frameDoc.write('<body>');
                frameDoc.write(contentHtml);
                frameDoc.write('</' + 'body>');
                frameDoc.write('</' + 'html>');
                frameDoc.close();

                printFrame.contentWindow.focus();
                setTimeout(() => {
                    printFrame.contentWindow.print();
                    setTimeout(() => {
                        if (document.body.contains(printFrame)) {
                            document.body.removeChild(printFrame);
                        }
                    }, 1500);
                }, 400);
            }
        };
    }
</script>
@endsection
