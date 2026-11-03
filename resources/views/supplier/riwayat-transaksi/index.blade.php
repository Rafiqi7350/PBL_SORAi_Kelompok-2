@extends('layouts.supplier')

@section('content')
<div class="space-y-6 w-full" x-data="riwayatTransaksiPage()">

    <!-- HEADER TITLE & SUBTITLE -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="space-y-1">
            <span class="text-xs font-mono font-bold uppercase text-[#ea580c] tracking-widest block">
                MONITORING KEUANGAN & PEMBAYARAN B2B
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight uppercase">
                Riwayat Transaksi Pemasok
            </h1>
            <p class="text-xs sm:text-sm text-gray-500">
                Histori penerimaan dana, pelacakan tagihan invoice pasokan, dan status penyelesaian pembayaran mitra UMKM.
            </p>
        </div>

        <div class="flex items-center gap-2.5 self-start sm:self-auto">
            <button type="button"
                    @click="printRekap()"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 text-xs font-bold transition shadow-xs cursor-pointer">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>Cetak Rekap</span>
            </button>
        </div>
    </div>

    <!-- 2 KARTU STATISTIK ATAS (DIHITUNG DARI DATABASE SUPABASE) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
        
        <!-- CARD 1: TOTAL UANG YANG DIDAPAT (LUNAS) -->
        <div class="bg-white rounded-3xl border border-gray-100 p-6 sm:p-7 shadow-xs relative overflow-hidden group hover:border-emerald-200 transition">
            <div class="absolute -right-8 -bottom-8 w-36 h-36 bg-emerald-50/60 rounded-full blur-2xl pointer-events-none group-hover:scale-110 transition duration-500"></div>
            
            <div class="w-14 h-14 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600 shrink-0 border border-emerald-100/60 shadow-xs">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <div class="mt-4">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">
                    Total Uang yang Didapat
                </span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 mt-1 font-mono-code tracking-tight">
                    {{ $totalUangDidapatFormatted }}
                </h2>
                <p class="text-xs text-gray-500 mt-2 leading-relaxed">
                    Total pembayaran dari <strong>{{ $countLunas }} transaksi</strong> yang telah berstatus lunas (settlement / paid).
                </p>
            </div>
        </div>

        <!-- CARD 2: TOTAL BELUM DIBAYAR (PENDING / UNPAID) -->
        <div class="bg-white rounded-3xl border border-gray-100 p-6 sm:p-7 shadow-xs relative overflow-hidden group hover:border-amber-200 transition">
            <div class="absolute -right-8 -bottom-8 w-36 h-36 bg-amber-50/60 rounded-full blur-2xl pointer-events-none group-hover:scale-110 transition duration-500"></div>

            <div class="w-14 h-14 rounded-2xl bg-amber-50 flex items-center justify-center text-amber-600 shrink-0 border border-amber-100/60 shadow-xs">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <div class="mt-4">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">
                    Total Belum Dibayar
                </span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 mt-1 font-mono-code tracking-tight">
                    {{ $totalBelumDibayarFormatted }}
                </h2>
                <p class="text-xs text-gray-500 mt-2 leading-relaxed">
                    Tagihan terbuka dari <strong>{{ $countBelumDibayar }} invoice</strong> yang masih menunggu pembayaran mitra UMKM.
                </p>
            </div>
        </div>

    </div>

    <!-- MAIN CARD: TABEL HISTORI & RIWAYAT TRANSAKSI (DYNAMIC BINDING DARI SUPABASE) -->
    <div class="bg-white rounded-3xl border border-gray-100 p-6 sm:p-8 space-y-6 shadow-xs">

        <!-- TITLE & FILTER BAR -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-gray-100">
            <div>
                <h2 class="text-lg sm:text-xl font-extrabold text-gray-900 tracking-tight">
                    Histori Seluruh Transaksi
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">
                    Data transaksi pembayaran komoditas dari Supabase. Klik tombol <strong>Detail</strong> atau <strong>Print</strong> untuk mencetak faktur invoice resmi.
                </p>
            </div>

            <!-- TABS STATUS FILTER -->
            <div class="flex items-center gap-1.5 bg-gray-100/80 p-1.5 rounded-2xl self-start lg:self-auto">
                <a href="{{ route('supplier.riwayat-transaksi.index', ['status' => 'all', 'search' => request('search')]) }}"
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ ($statusFilter === 'all' || !$statusFilter) ? 'bg-white text-gray-900 shadow-xs' : 'text-gray-500 hover:text-gray-900' }}">
                    Semua ({{ $totalCount }})
                </a>
                <a href="{{ route('supplier.riwayat-transaksi.index', ['status' => 'paid', 'search' => request('search')]) }}"
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $statusFilter === 'paid' ? 'bg-white text-emerald-600 shadow-xs' : 'text-gray-500 hover:text-gray-900' }}">
                    Lunas ({{ $countLunas }})
                </a>
                <a href="{{ route('supplier.riwayat-transaksi.index', ['status' => 'unpaid', 'search' => request('search')]) }}"
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $statusFilter === 'unpaid' ? 'bg-white text-amber-600 shadow-xs' : 'text-gray-500 hover:text-gray-900' }}">
                    Belum Dibayar ({{ $countBelumDibayar }})
                </a>
            </div>
        </div>

        <!-- SEARCH INPUT -->
        <form method="GET" action="{{ route('supplier.riwayat-transaksi.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
            <input type="hidden" name="status" value="{{ $statusFilter }}">
            <div class="relative w-full sm:w-96">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="Cari No. Invoice atau Mitra UMKM..."
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 text-xs text-gray-800 placeholder-gray-400 focus:outline-hidden focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c] transition">
            </div>
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <button type="submit"
                        class="w-full sm:w-auto px-5 py-2.5 bg-[#ea580c] hover:bg-orange-600 text-white text-xs font-bold rounded-xl transition shadow-xs flex items-center justify-center gap-2 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <span>Cari</span>
                </button>
                @if($search)
                    <a href="{{ route('supplier.riwayat-transaksi.index', ['status' => $statusFilter]) }}"
                       class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-semibold rounded-xl transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>

        <!-- TABEL TRANSAKSI (DYNAMIC FOREACH DARI DATABASE SUPABASE) -->
        <div class="overflow-x-auto border border-gray-100 rounded-2xl">
            <table class="w-full text-xs text-left">
                <thead class="bg-gray-50/80 text-gray-600 uppercase text-[11px] font-semibold border-b border-gray-100 tracking-wider">
                    <tr>
                        <th class="py-3.5 px-5 font-semibold">Tanggal</th>
                        <th class="py-3.5 px-5 font-semibold">No. Invoice</th>
                        <th class="py-3.5 px-5 font-semibold">Mitra UMKM</th>
                        <th class="py-3.5 px-5 font-semibold">Total Tagihan</th>
                        <th class="py-3.5 px-5 font-semibold text-center">Status</th>
                        <th class="py-3.5 px-5 font-semibold text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
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
                        <tr class="hover:bg-orange-50/20 transition group">
                            <!-- TANGGAL (DB: payments.created_at) -->
                            <td class="py-4 px-5 whitespace-nowrap">
                                <span class="font-bold text-gray-900 text-xs">{{ $dateText }}</span>
                            </td>

                            <!-- INVOICE (DB: payments.invoice_number) -->
                            <td class="py-4 px-5 whitespace-nowrap">
                                <span class="font-extrabold text-[#ea580c] font-mono-code text-xs tracking-tight">
                                    {{ $invoiceCode }}
                                </span>
                            </td>

                            <!-- MITRA UMKM (DB: partnerships.umkm.business_name) -->
                            <td class="py-4 px-5 whitespace-nowrap">
                                <span class="font-bold text-gray-900 text-xs">{{ $umkmName }}</span>
                            </td>

                            <!-- TOTAL TAGIHAN (DB: payments.amount) -->
                            <td class="py-4 px-5 whitespace-nowrap">
                                <span class="font-extrabold text-sm text-gray-900 font-mono-code">
                                    {{ $amountFormatted }}
                                </span>
                            </td>

                            <!-- STATUS (DB: payments.payment_status) -->
                            <td class="py-4 px-5 whitespace-nowrap text-center">
                                @if($isPaid)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <svg class="w-3 h-3 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        Lunas
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        <svg class="w-3 h-3 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                                        Belum Dibayar
                                    </span>
                                @endif
                            </td>

                            <!-- AKSI: DETAIL MODAL & PRINT INVOICE -->
                            <td class="py-4 px-5 whitespace-nowrap text-center">
                                <div class="inline-flex items-center gap-2">
                                    <button type="button"
                                            @click="openDetailModal(@js($trxJson))"
                                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-white hover:bg-[#ea580c] text-gray-700 hover:text-white border border-gray-200 hover:border-[#ea580c] text-xs font-bold transition shadow-2xs cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <span>Detail</span>
                                    </button>
                                    <button type="button"
                                            @click="printInvoice(@js($trxJson))"
                                            title="Cetak Faktur Invoice Resmi"
                                            class="p-1.5 rounded-xl bg-white hover:bg-gray-100 text-gray-500 hover:text-[#ea580c] border border-gray-200 text-xs font-bold transition shadow-2xs cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-gray-400">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <span class="text-xs font-medium text-gray-500">Tidak ada riwayat transaksi yang tercatat pada database</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- PAGINATION LINKS (DATABASE PAGINATION) -->
            @if($transactions->hasPages())
                <div class="px-5 py-4 border-t border-gray-100 bg-gray-50/60">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>

    </div>

    <!-- MODAL DETAIL INVOICE & TRANSAKSI LENGKAP (ALPINE.JS) -->
    <div x-show="showModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title"
         role="dialog"
         aria-modal="true">
        
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity"
             @click="closeDetailModal()"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-xl border border-gray-100"
                 @click.stop>
                
                <!-- Modal Header -->
                <div class="bg-gray-50/90 px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono font-bold uppercase text-[#ea580c] tracking-widest block">
                            DETAIL LENGKAP TRANSAKSI
                        </span>
                        <div class="flex items-center gap-2 mt-0.5">
                            <h3 class="text-base font-extrabold text-gray-900 font-mono-code" x-text="selectedTrx.invoice_number"></h3>
                            <span class="text-[10px] px-2 py-0.5 rounded-md bg-gray-200/80 text-gray-700 font-mono-code font-bold" x-text="selectedTrx.order_code"></span>
                        </div>
                    </div>
                    <button type="button"
                            @click="closeDetailModal()"
                            class="w-8 h-8 rounded-full bg-white hover:bg-gray-200 border border-gray-200 flex items-center justify-center text-gray-500 transition cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-5 text-xs text-gray-600">
                    
                    <!-- Status & Nominal Banner -->
                    <div class="p-4 rounded-2xl flex items-center justify-between"
                         :class="selectedTrx.status === 'PAID' ? 'bg-emerald-50 border border-emerald-100' : 'bg-amber-50 border border-amber-100'">
                        <div>
                            <span class="text-[10px] uppercase font-bold block"
                                  :class="selectedTrx.status === 'PAID' ? 'text-emerald-600' : 'text-amber-600'">Status Pembayaran</span>
                            <span class="text-base font-extrabold"
                                  :class="selectedTrx.status === 'PAID' ? 'text-emerald-800' : 'text-amber-800'"
                                  x-text="selectedTrx.status_label"></span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-gray-400 block font-medium">Total Tagihan</span>
                            <span class="text-lg font-extrabold text-gray-900 font-mono-code" x-text="selectedTrx.amount_formatted"></span>
                        </div>
                    </div>

                    <!-- 1. DETAIL KOMODITAS & PASOKAN -->
                    <div class="space-y-2">
                        <h4 class="font-extrabold text-gray-900 text-[11px] uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-[#ea580c]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            <span>Komoditas & Pengiriman</span>
                        </h4>
                        <div class="p-4 rounded-2xl border border-gray-100 bg-gray-50/60 space-y-2.5">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500">Nama Komoditas</span>
                                <span class="font-bold text-gray-900" x-text="selectedTrx.commodity_name"></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500">Volume Pasok</span>
                                <span class="font-bold text-[#ea580c] font-mono-code" x-text="selectedTrx.quantity_formatted"></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500">Tahap Pengiriman</span>
                                <span class="font-semibold text-gray-800 bg-white px-2 py-0.5 rounded border border-gray-200" x-text="selectedTrx.batch_label"></span>
                            </div>
                        </div>
                    </div>

                    <!-- 2. DATA MITRA UMKM PEMESAN -->
                    <div class="space-y-2">
                        <h4 class="font-extrabold text-gray-900 text-[11px] uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-[#ea580c]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            <span>Informasi Mitra UMKM</span>
                        </h4>
                        <div class="p-4 rounded-2xl border border-gray-100 bg-gray-50/60 space-y-2">
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="font-bold text-gray-900 text-sm" x-text="selectedTrx.customer_name"></div>
                                    <div class="text-[11px] text-gray-500 mt-0.5" x-text="'Pemilik: ' + selectedTrx.owner_name + ' • ' + selectedTrx.customer_phone"></div>
                                </div>
                            </div>
                            <div class="pt-2 border-t border-gray-200/60 text-[11px] text-gray-600 flex items-start gap-1.5">
                                <svg class="w-3.5 h-3.5 text-gray-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span x-text="selectedTrx.customer_address"></span>
                            </div>
                        </div>
                    </div>

                    <!-- 3. INFORMASI WAKTU & METODE PEMBAYARAN -->
                    <div class="space-y-2">
                        <h4 class="font-extrabold text-gray-900 text-[11px] uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-[#ea580c]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                            <span>Waktu & Metode Transaksi</span>
                        </h4>
                        <div class="p-4 rounded-2xl border border-gray-100 bg-gray-50/60 space-y-2">
                            <div class="flex justify-between items-center py-0.5">
                                <span class="text-gray-500">Waktu Order / Dibuat</span>
                                <span class="font-bold text-gray-800" x-text="selectedTrx.date + ', ' + selectedTrx.time"></span>
                            </div>
                            <div class="flex justify-between items-center py-0.5">
                                <span class="text-gray-500">Kanal Pembayaran</span>
                                <span class="font-semibold text-gray-800" x-text="selectedTrx.payment_method"></span>
                            </div>
                            <div class="flex justify-between items-center py-0.5">
                                <span class="text-gray-500">Waktu Pelunasan</span>
                                <span class="font-semibold text-gray-800" x-text="selectedTrx.paid_at"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Catatan Tambahan -->
                    <div class="p-3 bg-orange-50/50 rounded-xl border border-orange-100 text-[11px] text-gray-600">
                        <span class="font-bold text-[#ea580c]">Catatan Invoice:</span>
                        <p class="mt-0.5" x-text="selectedTrx.notes"></p>
                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="bg-gray-50/90 px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3">
                    <button type="button"
                            @click="closeDetailModal()"
                            class="px-4 py-2 rounded-xl border border-gray-200 bg-white hover:bg-gray-100 text-gray-700 text-xs font-bold transition cursor-pointer">
                        Tutup
                    </button>
                    <button type="button"
                            @click="printInvoice(selectedTrx)"
                            class="px-5 py-2 rounded-xl bg-[#ea580c] hover:bg-orange-600 text-white text-xs font-bold transition shadow-xs flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
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

            // FUNGSI STANDAR CETAK INVOICE KOMERSIAL RESMI (A4 STANDAR BISNIS)
            printInvoice(trx) {
                const supplier = this.supplier;
                const isPaid = trx.status === 'PAID';
                
                const invoiceHtml = `
                    <div style="max-width:800px; margin:0 auto; padding:24px; font-family:'Segoe UI', Arial, sans-serif; color:#111827; background:#ffffff;">
                        
                        <table style="width:100%; border-collapse:collapse; margin-bottom:20px; border-bottom:2px solid #111827; padding-bottom:16px;">
                            <tr>
                                <td style="vertical-align:top; width:58%;">
                                    <div style="font-size:24px; font-weight:900; color:#ea580c; letter-spacing:1px; line-height:1;">
                                        SORAI <span style="font-size:11px; font-weight:700; color:#6b7280; letter-spacing:1.5px; border-left:1px solid #d1d5db; padding-left:8px;">PORTAL PEMASOK B2B</span>
                                    </div>
                                    <div style="margin-top:10px; font-size:14px; font-weight:800; color:#111827;">${supplier.company_name}</div>
                                    <div style="font-size:11px; color:#4b5563; line-height:1.4; margin-top:3px;">${supplier.warehouse_address}</div>
                                    <div style="font-size:11px; color:#4b5563; margin-top:2px;">Telp / WA: <strong>${supplier.phone}</strong> | NIB: <strong>${supplier.nib_number}</strong></div>
                                </td>
                                <td style="vertical-align:top; text-align:right; width:42%;">
                                    <div style="font-size:28px; font-weight:900; color:#111827; letter-spacing:2px; line-height:1;">INVOICE</div>
                                    <div style="font-size:10px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:1px; margin-top:2px;">FAKTUR PENJUALAN KOMERSIAL</div>
                                    <table style="font-size:11px; margin-top:10px; margin-left:auto; text-align:left; border-collapse:collapse;">
                                        <tr>
                                             <td style="padding:2px 8px; color:#6b7280;">No. Invoice</td>
                                             <td style="padding:2px 8px; font-weight:800; font-family:monospace; color:#ea580c;">: ${trx.invoice_number}</td>
                                        </tr>
                                        <tr>
                                             <td style="padding:2px 8px; color:#6b7280;">No. Pesanan</td>
                                             <td style="padding:2px 8px; font-weight:700; font-family:monospace;">: ${trx.order_code}</td>
                                        </tr>
                                        <tr>
                                             <td style="padding:2px 8px; color:#6b7280;">Tanggal Terbit</td>
                                             <td style="padding:2px 8px; font-weight:600;">: ${trx.date}</td>
                                        </tr>
                                        <tr>
                                             <td style="padding:2px 8px; color:#6b7280;">Jatuh Tempo</td>
                                             <td style="padding:2px 8px; font-weight:600;">: 24 Jam Pasca Kirim</td>
                                        </tr>
                                    </table>
                                    <div style="margin-top:10px;">
                                        ${isPaid
                                            ? '<span style="display:inline-block; border:2px solid #059669; color:#059669; font-weight:900; font-size:12px; padding:4px 14px; border-radius:4px; text-transform:uppercase; letter-spacing:1.5px;">✓ LUNAS / PAID</span>'
                                            : '<span style="display:inline-block; border:2px solid #d97706; color:#d97706; font-weight:900; font-size:12px; padding:4px 14px; border-radius:4px; text-transform:uppercase; letter-spacing:1.5px;">● MENUNGGU PEMBAYARAN</span>'
                                        }
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <table style="width:100%; border-collapse:collapse; margin-bottom:20px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px;">
                            <tr>
                                <td style="padding:14px; width:50%; vertical-align:top; border-right:1px solid #e5e7eb;">
                                    <div style="font-size:10px; font-weight:800; color:#6b7280; text-transform:uppercase; letter-spacing:1px; margin-bottom:4px;">DITUJUKAN KEPADA (BUYER / MITRA UMKM):</div>
                                    <div style="font-size:14px; font-weight:800; color:#111827;">${trx.customer_name}</div>
                                    <div style="font-size:11px; color:#4b5563; margin-top:2px;">Penanggung Jawab: <strong>${trx.owner_name}</strong></div>
                                    <div style="font-size:11px; color:#4b5563;">Kontak Telepon: <strong>${trx.customer_phone}</strong></div>
                                    <div style="font-size:11px; color:#4b5563; margin-top:4px; line-height:1.4;">Alamat Kirim: ${trx.customer_address}</div>
                                </td>
                                <td style="padding:14px; width:50%; vertical-align:top;">
                                    <div style="font-size:10px; font-weight:800; color:#6b7280; text-transform:uppercase; letter-spacing:1px; margin-bottom:4px;">INFORMASI PENGIRIMAN & PEMBAYARAN:</div>
                                    <div style="font-size:11px; color:#4b5563; line-height:1.6;">
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
                                <tr style="background:#111827; color:#ffffff;">
                                    <th style="padding:9px 12px; text-align:center; width:35px; border:1px solid #111827;">NO</th>
                                    <th style="padding:9px 12px; text-align:left; border:1px solid #111827;">DESKRIPSI BARANG / KOMODITAS</th>
                                    <th style="padding:9px 12px; text-align:center; width:90px; border:1px solid #111827;">TAHAP</th>
                                    <th style="padding:9px 12px; text-align:center; width:100px; border:1px solid #111827;">VOLUME</th>
                                    <th style="padding:9px 12px; text-align:right; width:130px; border:1px solid #111827;">TOTAL HARGA (IDR)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="padding:12px; text-align:center; border:1px solid #e5e7eb; vertical-align:top;">1</td>
                                    <td style="padding:12px; border:1px solid #e5e7eb; vertical-align:top;">
                                        <div style="font-weight:800; font-size:12px; color:#111827;">${trx.commodity_name}</div>
                                        <div style="font-size:10px; color:#6b7280; margin-top:3px;">Pasokan bahan baku berkualitas sesuai standar kontrak kemitraan Sorai.</div>
                                    </td>
                                    <td style="padding:12px; text-align:center; border:1px solid #e5e7eb; vertical-align:top;">
                                        <span style="background:#f3f4f6; padding:2px 8px; border-radius:4px; font-weight:700;">${trx.batch_label}</span>
                                    </td>
                                    <td style="padding:12px; text-align:center; font-weight:800; font-family:monospace; border:1px solid #e5e7eb; vertical-align:top;">
                                        ${trx.quantity_formatted}
                                    </td>
                                    <td style="padding:12px; text-align:right; font-weight:800; font-size:13px; font-family:monospace; border:1px solid #e5e7eb; vertical-align:top;">
                                        ${trx.amount_formatted}
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <table style="width:100%; border-collapse:collapse; margin-bottom:25px;">
                            <tr>
                                <td style="width:55%; vertical-align:top; padding-right:20px;">
                                    <div style="font-size:10px; font-weight:800; color:#6b7280; text-transform:uppercase; margin-bottom:4px;">CATATAN INVOICE:</div>
                                    <div style="padding:10px 12px; background:#fff7ed; border-left:3px solid #ea580c; font-size:11px; color:#9a3412; line-height:1.4; border-radius:2px;">
                                        ${trx.notes}
                                    </div>
                                    <div style="font-size:10px; color:#6b7280; margin-top:8px; line-height:1.4;">
                                        <em>*Dokumen faktur ini sah dan diterbitkan secara digital melalui Portal Pemasok Sorai. Seluruh pembayaran diproses melalui Escrow resmi untuk menjamin keamanan transaksi kedua pihak.</em>
                                    </div>
                                </td>
                                <td style="width:45%; vertical-align:top;">
                                    <table style="width:100%; font-size:11px; border-collapse:collapse;">
                                        <tr>
                                            <td style="padding:6px 8px; color:#4b5563;">Subtotal Barang</td>
                                            <td style="padding:6px 8px; text-align:right; font-weight:700; font-family:monospace;">${trx.amount_formatted}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:6px 8px; color:#4b5563;">PPN Bahan Baku Pangan (0%)</td>
                                            <td style="padding:6px 8px; text-align:right; color:#6b7280;">Rp 0</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:6px 8px; color:#4b5563;">Biaya Layanan Escrow Sorai</td>
                                            <td style="padding:6px 8px; text-align:right; color:#059669; font-weight:700;">Rp 0 (Bebas Biaya)</td>
                                        </tr>
                                        <tr style="border-top:2px solid #111827; border-bottom:2px solid #111827; background:#fafafa;">
                                            <td style="padding:10px 8px; font-size:12px; font-weight:900; color:#111827;">TOTAL TAGIHAN:</td>
                                            <td style="padding:10px 8px; text-align:right; font-size:16px; font-weight:900; color:#ea580c; font-family:monospace;">${trx.amount_formatted}</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <table style="width:100%; border-collapse:collapse; margin-top:35px; padding-top:20px; border-top:1px dashed #d1d5db; text-align:center; font-size:11px;">
                            <tr>
                                <td style="width:50%; vertical-align:bottom; padding-bottom:5px;">
                                    <div style="color:#6b7280; font-size:10px; text-transform:uppercase; font-weight:700;">Penerima Barang / Mitra UMKM</div>
                                    <div style="height:65px;"></div>
                                    <div style="font-weight:800; border-top:1px solid #9ca3af; display:inline-block; min-width:200px; padding-top:4px;">${trx.owner_name}</div>
                                    <div style="color:#6b7280; font-size:10px;">${trx.customer_name}</div>
                                </td>
                                <td style="width:50%; vertical-align:bottom; padding-bottom:5px;">
                                    <div style="color:#6b7280; font-size:10px; text-transform:uppercase; font-weight:700;">Diterbitkan Oleh (Pemasok)</div>
                                    <div style="height:65px;"></div>
                                    <div style="font-weight:800; border-top:1px solid #9ca3af; display:inline-block; min-width:200px; padding-top:4px;">${supplier.owner_name}</div>
                                    <div style="color:#6b7280; font-size:10px;">${supplier.company_name}</div>
                                </td>
                            </tr>
                        </table>

                    </div>
                `;

                this.triggerPrint("Faktur_Invoice_" + trx.invoice_number.replace(/[^a-zA-Z0-9]/g, '_'), invoiceHtml);
            },

            // FUNGSI STANDAR CETAK REKAPITULASI LAPORAN TRANSAKSI (A4 STANDAR BISNIS)
            printRekap() {
                const supplier = this.supplier;
                const items = this.allTrx;
                const todayFormatted = new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });

                let rowsHtml = '';
                items.forEach((trx, index) => {
                    const isPaid = trx.status === 'PAID';
                    rowsHtml += `
                        <tr style="border-bottom:1px solid #e5e7eb;">
                            <td style="padding:8px; text-align:center;">${index + 1}</td>
                            <td style="padding:8px;">${trx.date}</td>
                            <td style="padding:8px; font-weight:700; font-family:monospace; color:#ea580c;">${trx.invoice_number}</td>
                            <td style="padding:8px; font-weight:700; color:#111827;">${trx.customer_name}</td>
                            <td style="padding:8px;">${trx.commodity_name} <span style="color:#6b7280; font-size:10px;">(${trx.quantity_formatted})</span></td>
                            <td style="padding:8px; text-align:right; font-weight:800; font-family:monospace;">${trx.amount_formatted}</td>
                            <td style="padding:8px; text-align:center;">
                                <span style="font-weight:700; font-size:10px; padding:2px 8px; border-radius:4px; ${isPaid ? 'color:#059669; background:#ecfdf5;' : 'color:#d97706; background:#fffbeb;'}">
                                    ${trx.status_label}
                                </span>
                            </td>
                        </tr>
                    `;
                });

                const rekapHtml = `
                    <div style="max-width:850px; margin:0 auto; padding:24px; font-family:'Segoe UI', Arial, sans-serif; color:#111827; background:#ffffff;">
                        
                        <table style="width:100%; border-collapse:collapse; margin-bottom:16px; border-bottom:2px solid #111827; padding-bottom:12px;">
                            <tr>
                                <td style="vertical-align:top; width:65%;">
                                    <div style="font-size:24px; font-weight:900; color:#ea580c; letter-spacing:1px; line-height:1;">
                                        SORAI <span style="font-size:11px; font-weight:700; color:#6b7280; letter-spacing:1.5px; border-left:1px solid #d1d5db; padding-left:8px;">PORTAL PEMASOK B2B</span>
                                    </div>
                                    <div style="margin-top:8px; font-size:14px; font-weight:800; color:#111827;">${supplier.company_name}</div>
                                    <div style="font-size:11px; color:#4b5563;">${supplier.warehouse_address} | Telp: ${supplier.phone}</div>
                                </td>
                                <td style="vertical-align:top; text-align:right; width:35%;">
                                    <div style="font-size:16px; font-weight:900; color:#111827; text-transform:uppercase;">REKAPITULASI TRANSAKSI</div>
                                    <div style="font-size:11px; color:#6b7280; margin-top:4px;">Tanggal Cetak: <strong>${todayFormatted}</strong></div>
                                    <div style="font-size:11px; color:#6b7280;">Status Akun: <strong>Pemasok Terverifikasi</strong></div>
                                </td>
                            </tr>
                        </table>

                        <table style="width:100%; border-collapse:collapse; margin-bottom:20px; text-align:center;">
                            <tr>
                                <td style="padding:12px; background:#ecfdf5; border:1px solid #a7f3d0; border-radius:6px; width:48%;">
                                    <div style="font-size:10px; font-weight:800; color:#059669; text-transform:uppercase;">TOTAL DANA DITERIMA (LUNAS)</div>
                                    <div style="font-size:20px; font-weight:900; color:#065f46; font-family:monospace; margin-top:2px;">${this.totalUangDidapat}</div>
                                    <div style="font-size:10px; color:#047857; margin-top:2px;">Dari ${this.countLunas} transaksi tuntas</div>
                                </td>
                                <td style="width:4%;"></td>
                                <td style="padding:12px; background:#fffbeb; border:1px solid #fde68a; border-radius:6px; width:48%;">
                                    <div style="font-size:10px; font-weight:800; color:#d97706; text-transform:uppercase;">TOTAL BELUM DIBAYAR (TERTUNDA)</div>
                                    <div style="font-size:20px; font-weight:900; color:#92400e; font-family:monospace; margin-top:2px;">${this.totalBelumDibayar}</div>
                                    <div style="font-size:10px; color:#b45309; margin-top:2px;">Dari ${this.countBelumDibayar} tagihan berjalan</div>
                                </td>
                            </tr>
                        </table>

                        <table style="width:100%; border-collapse:collapse; margin-bottom:24px; font-size:10.5px;">
                            <thead>
                                <tr style="background:#111827; color:#ffffff;">
                                    <th style="padding:8px; text-align:center; width:30px;">NO</th>
                                    <th style="padding:8px; text-align:left; width:80px;">TANGGAL</th>
                                    <th style="padding:8px; text-align:left; width:120px;">NO. INVOICE</th>
                                    <th style="padding:8px; text-align:left;">MITRA UMKM</th>
                                    <th style="padding:8px; text-align:left;">KOMODITAS & VOLUME</th>
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
                                <td style="width:65%; vertical-align:top; color:#6b7280; font-size:10px; line-height:1.4;">
                                    <div><strong>Catatan Rekapitulasi:</strong></div>
                                    <div>Laporan keuangan ini mencakup seluruh transaksi yang tercatat pada basis data B2B Sorai. Seluruh data diverifikasi secara berkala dengan rekening penampung escrow.</div>
                                </td>
                                <td style="width:35%; text-align:center; vertical-align:bottom;">
                                    <div style="color:#6b7280; font-size:10px;">Disiapkan & Diverifikasi Oleh:</div>
                                    <div style="height:55px;"></div>
                                    <div style="font-weight:800; border-top:1px solid #9ca3af; display:inline-block; min-width:180px; padding-top:4px;">${supplier.owner_name}</div>
                                    <div style="color:#6b7280; font-size:10px;">${supplier.company_name}</div>
                                </td>
                            </tr>
                        </table>

                    </div>
                `;

                this.triggerPrint("Rekapitulasi_Transaksi_Pemasok_" + new Date().toISOString().slice(0, 10), rekapHtml);
            },

            // HELPER CETAK ISOLASI (TANPA MENCETAK TAMPILAN DASHBOARD / POPUP)
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
                frameDoc.write('<style>');
                frameDoc.write('@page { size: A4 portrait; margin: 12mm 10mm; }');
                frameDoc.write('* { box-sizing: border-box; }');
                frameDoc.write('body { margin: 0; padding: 0; background: #ffffff !important; color: #111827 !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }');
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
