@extends('layouts.supplier')

@section('content')
<div class="p-6 sm:p-8 space-y-6" x-data="supplierPengirimanPage()">

        <!-- HEADER TITLE & SUBTITLE (CLEAN & MINIMALIST SESUAI BUILDMATCH) -->
    <div class="space-y-1">
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">
            Verifikasi Proyek
        </h1>
        <p class="text-xs text-gray-400">
            Pantau status progres pekerjaan proyek aktif dan pelepasan dana dari escrow ke mitra.
        </p>
    </div>

    <!-- 3 METRIC STAT CARDS (SESUAI GAMBAR REFERENSI) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <!-- 1. Proyek Aktif -->
        <div class="bg-white rounded-2xl border border-gray-100 p-5 sm:p-6 flex items-center gap-4 shadow-xs">
            <div class="w-12 h-12 rounded-xl bg-orange-50 flex items-center justify-center text-[#ea580c] shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>
            </div>
            <div>
                <span class="text-xs font-medium text-gray-500">Proyek Aktif</span>
                <h3 class="text-2xl font-bold text-gray-900 mt-0.5">{{ $countPending + $countShipping }}</h3>
            </div>
        </div>

        <!-- 2. Selesai -->
        <div class="bg-white rounded-2xl border border-gray-100 p-5 sm:p-6 flex items-center gap-4 shadow-xs">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>
            </div>
            <div>
                <span class="text-xs font-medium text-gray-500">Selesai</span>
                <h3 class="text-2xl font-bold text-gray-900 mt-0.5">{{ $countCompleted }}</h3>
            </div>
        </div>

        <!-- 3. Dibatalkan / Refund -->
        <div class="bg-white rounded-2xl border border-gray-100 p-5 sm:p-6 flex items-center gap-4 shadow-xs">
            <div class="w-12 h-12 rounded-xl bg-rose-50 flex items-center justify-center text-rose-500 shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>
            </div>
            <div>
                <span class="text-xs font-medium text-gray-500">Dibatalkan / Refund</span>
                <h3 class="text-2xl font-bold text-gray-900 mt-0.5">{{ $countBackorder }}</h3>
            </div>
        </div>
    </div>

    <!-- MAIN CONTAINER CARD: DAFTAR PROYEK & KONTRAK -->
    <div class="bg-white rounded-2xl border border-gray-100 p-6 sm:p-7 space-y-5 shadow-xs">

        <!-- TITLE -->
        <div>
            <h2 class="text-sm font-bold text-gray-900">Daftar Proyek & Kontrak</h2>
        </div>

        <!-- FILTER PILLS (PERSIS SESUAI GAMBAR REFERENSI) -->
        <div class="flex flex-wrap items-center gap-2 pb-1">
            <!-- 1. Menunggu Verifikasi (Perlu Dikirim) -->
            <a href="{{ route('supplier.pengiriman.index', ['tab' => 'pending']) }}"
               class="rounded-full px-3.5 py-1.5 text-xs transition flex items-center gap-2 font-medium {{ $tab === 'pending' ? 'bg-[#ea580c] text-white shadow-xs' : 'border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                <span>Menunggu Verifikasi</span>
                <span class="rounded-full text-[10px] w-4 h-4 flex items-center justify-center font-bold font-mono {{ $tab === 'pending' ? 'bg-white text-[#ea580c]' : 'bg-gray-100 text-gray-600' }}">
                    {{ $countPending }}
                </span>
            </a>

            <!-- 2. Semua Proyek -->
            <a href="{{ route('supplier.pengiriman.index', ['tab' => 'all']) }}"
               class="rounded-full px-3.5 py-1.5 text-xs transition flex items-center gap-1.5 font-medium {{ $tab === 'all' ? 'bg-[#ea580c] text-white shadow-xs' : 'border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                <span>Semua Proyek ({{ $countAll }})</span>
            </a>

            <!-- 3. Berjalan -->
            <a href="{{ route('supplier.pengiriman.index', ['tab' => 'shipping']) }}"
               class="rounded-full px-3.5 py-1.5 text-xs transition flex items-center gap-1.5 font-medium {{ $tab === 'shipping' ? 'bg-[#ea580c] text-white shadow-xs' : 'border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                <span>Berjalan ({{ $countShipping }})</span>
            </a>

            <!-- 4. Selesai -->
            <a href="{{ route('supplier.pengiriman.index', ['tab' => 'completed']) }}"
               class="rounded-full px-3.5 py-1.5 text-xs transition flex items-center gap-1.5 font-medium {{ $tab === 'completed' ? 'bg-[#ea580c] text-white shadow-xs' : 'border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                <span>Selesai ({{ $countCompleted }})</span>
            </a>

            <!-- 5. Dibatalkan -->
            <a href="{{ route('supplier.pengiriman.index', ['tab' => 'backorder']) }}"
               class="rounded-full px-3.5 py-1.5 text-xs transition flex items-center gap-1.5 font-medium {{ $tab === 'backorder' ? 'bg-[#ea580c] text-white shadow-xs' : 'border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                <span>Dibatalkan ({{ $countBackorder }})</span>
            </a>
        </div>

        @if($orders->isEmpty())
            <div class="py-14 text-center space-y-2">
                <div class="w-12 h-12 mx-auto rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center text-gray-400">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <p class="text-xs text-gray-500 font-medium">Tidak ada data proyek atau pesanan dengan filter ini.</p>
            </div>
        @else
                        <!-- TABEL DAFTAR PESANAN & PENGIRIMAN (CLEAN & ESENSIAL) -->
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="text-[11px] font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100">
                        <tr>
                            <th class="py-3 px-4 font-semibold">Komoditas & ID</th>
                            <th class="py-3 px-4 font-semibold">Pemesan (UMKM)</th>
                            <th class="py-3 px-4 font-semibold">Kuantitas</th>
                            <th class="py-3 px-4 font-semibold">Total Tagihan</th>
                            <th class="py-3 px-4 font-semibold">Status</th>
                            <th class="py-3 px-4 font-semibold text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($orders as $order)
                            @php
                                $firstItem = $order->items->first();
                                $commodity = $firstItem?->supplierProduct?->commodity;
                                $commodityName = $commodity?->name ?? 'Pasokan Bahan';
                                $commodityCategory = $commodity?->category ?? 'Bahan Pokok';
                                $unit = $commodity?->unit ?? 'kg';
                                $orderedQty = (float) $order->items->sum('quantity');
                                $deliveredQty = (float) $order->items->sum('quantity_delivered');
                                $remainingQty = (float) $order->items->sum('quantity_remaining');
                                $batch1 = $order->shipmentConfirmations->firstWhere('shipment_batch', 1);
                                $hasBackorderFlow = $batch1 && in_array($batch1->backorder_action, ['create_backorder', 'no_backorder']);
                                $approvalStatus = $batch1?->backorder_approval_status ?? 'pending';
                            @endphp

                            <tr class="hover:bg-gray-50/50 transition">
                                <!-- 1. KOMODITAS & ID -->
                                <td class="py-4 px-4 space-y-0.5">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="font-semibold text-gray-900 text-xs">
                                            {{ strtolower($commodityName) }}
                                        </span>
                                        @if($order->order_status === 'waiting_backorder_approval')
                                            <span class="text-[10px] text-amber-800 bg-amber-50 px-1.5 py-0.2 rounded font-medium">
                                                ● Perlu Validasi
                                            </span>
                                        @elseif($hasBackorderFlow)
                                            <span class="text-[10px] text-[#ea580c] bg-orange-50 px-1.5 py-0.2 rounded font-medium">
                                                {{ $batch1->backorder_action === 'no_backorder' ? 'Parsial' : 'Split' }}
                                            </span>
                                        @endif
                                    </div>
                                    <span class="text-[10px] text-gray-400 font-mono block">
                                        #ORD-{{ strtolower(substr($order->id, 0, 7)) }}
                                    </span>
                                </td>

                                <!-- 2. PEMESAN (UMKM) -->
                                <td class="py-4 px-4 space-y-0.5">
                                    <span class="text-xs font-semibold text-gray-900 block">
                                        {{ $order->partnership?->umkm?->restaurant_name ?? 'Dapur UMKM' }}
                                    </span>
                                    <span class="text-[11px] text-gray-400 block font-normal">
                                        {{ $order->partnership?->umkm?->owner_name ?? 'Mitra UMKM' }}
                                    </span>
                                </td>

                                <!-- 3. KUANTITAS & PEMENUHAN -->
                                <td class="py-4 px-4 whitespace-nowrap space-y-0.5">
                                    <div class="text-xs font-semibold text-gray-800 font-mono">
                                        {{ number_format($deliveredQty, 0) }}/{{ number_format($orderedQty, 0) }} {{ $unit }}
                                    </div>
                                    @if($remainingQty > 0)
                                        <span class="text-[10px] text-amber-600 font-mono block">
                                            Sisa: {{ number_format($remainingQty, 0) }} {{ $unit }}
                                        </span>
                                    @else
                                        <span class="text-[10px] text-gray-400 font-mono block">
                                            Terkirim penuh
                                        </span>
                                    @endif
                                </td>

                                <!-- 4. TOTAL TAGIHAN -->
                                <td class="py-4 px-4 whitespace-nowrap space-y-0.5">
                                    <span class="font-bold text-gray-900 font-mono text-xs block">
                                        Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                                    </span>
                                    @php
                                        $allPaid = $order->payments->isNotEmpty() && $order->payments->every(fn($p) => $p->isPaid());
                                    @endphp
                                    <span class="text-[10px] font-mono {{ $allPaid ? 'text-emerald-600 font-semibold' : 'text-gray-400' }}">
                                        {{ $allPaid ? '✓ Lunas' : 'Tagihan Terbit' }}
                                    </span>
                                </td>

                                <!-- 5. STATUS -->
                                <td class="py-4 px-4 whitespace-nowrap">
                                    @if(in_array($order->order_status, ['pending', 'confirmed', 'MENUNGGU_KONFIRMASI_BARANG']))
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium text-amber-700 bg-amber-50">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            Perlu Kirim
                                        </span>
                                    @elseif($order->order_status === 'waiting_backorder_approval')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium text-amber-800 bg-amber-100">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-600 animate-pulse"></span>
                                            Validasi UMKM
                                        </span>
                                    @elseif(in_array($order->order_status, ['shipped', 'DIKIRIM', 'partially_shipped']))
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium text-blue-600 bg-blue-50">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                            Berjalan
                                        </span>
                                    @elseif($order->order_status === 'backorder_pending')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium text-purple-700 bg-purple-50">
                                            <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                                            Backorder
                                        </span>
                                    @elseif(in_array($order->order_status, ['completed', 'completed_partial', 'BARANG_DITERIMA']))
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium text-emerald-700 bg-emerald-50">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Selesai
                                        </span>
                                    @elseif($order->order_status === 'cancelled')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium text-rose-700 bg-rose-50">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            Dibatalkan
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium text-gray-600 bg-gray-50">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                            {{ ucfirst($order->order_status) }}
                                        </span>
                                    @endif
                                </td>

                                <!-- 6. AKSI (TOMBOL UTAMA & TOMBOL MODAL DETAIL) -->
                                <td class="py-4 px-4 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1.5">
                                        @if(in_array($order->order_status, ['pending', 'confirmed', 'MENUNGGU_KONFIRMASI_BARANG']))
                                            <button type="button"
                                                    @click="openKirimModal({
                                                        id: '{{ $order->id }}',
                                                        orderCode: '{{ strtoupper(substr($order->id, 0, 8)) }}',
                                                        restaurant: '{{ addslashes($order->partnership?->umkm?->restaurant_name ?? 'Dapur UMKM') }}',
                                                        commodity: '{{ addslashes($commodityName) }}',
                                                        unit: '{{ $unit }}',
                                                        orderedQty: {{ $orderedQty }},
                                                        price: {{ (float) ($firstItem?->unit_price ?? 0) }}
                                                    })"
                                                    class="bg-[#ea580c] hover:bg-[#c2410c] text-white px-3.5 py-1.5 rounded-full text-xs font-semibold shadow-xs transition cursor-pointer">
                                                Cek & Kirim
                                            </button>
                                        @elseif($hasBackorderFlow && $approvalStatus === 'approved' && $batch1 && $batch1->shipped_at === null)
                                            <button type="button"
                                                    @click="openKirimBatch1Modal({
                                                        id: '{{ $order->id }}',
                                                        orderCode: '{{ strtoupper(substr($order->id, 0, 8)) }}',
                                                        restaurant: '{{ addslashes($order->partnership?->umkm?->restaurant_name ?? 'Dapur UMKM') }}',
                                                        commodity: '{{ addslashes($commodityName) }}',
                                                        unit: '{{ $unit }}',
                                                        qty: {{ (float) ($batch1->shipped_quantity ?? 0) }},
                                                        action: '{{ $batch1->backorder_action }}'
                                                    })"
                                                    class="bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-1.5 rounded-full text-xs font-semibold shadow-xs transition cursor-pointer">
                                                Berangkatkan
                                            </button>
                                        @elseif($batch1 && $batch1->backorder_action === 'create_backorder' && $remainingQty > 0)
                                            <button type="button"
                                                    @click="openBackorderModal({
                                                        id: '{{ $order->id }}',
                                                        orderCode: '{{ strtoupper(substr($order->id, 0, 8)) }}',
                                                        restaurant: '{{ addslashes($order->partnership?->umkm?->restaurant_name ?? 'Dapur UMKM') }}',
                                                        commodity: '{{ addslashes($commodityName) }}',
                                                        unit: '{{ $unit }}',
                                                        remainingQty: {{ $remainingQty }}
                                                    })"
                                                    class="bg-[#ea580c] hover:bg-[#c2410c] text-white px-3.5 py-1.5 rounded-full text-xs font-semibold shadow-xs transition cursor-pointer">
                                                Kirim Sisa
                                            </button>
                                        @endif

                                        <!-- TOMBOL LIHAT DETAIL (MEMBUKA MODAL POP-UP) -->
                                        <button type="button"
                                                @click="openDetailModal({
                                                    id: '{{ $order->id }}',
                                                    orderCode: '{{ strtoupper(substr($order->id, 0, 8)) }}',
                                                    orderDate: '{{ $order->order_date ? $order->order_date->format('d M Y') : '-' }}',
                                                    deliveryDate: '{{ $order->delivery_date ? $order->delivery_date->format('d M Y') : '-' }}',
                                                    restaurant: '{{ addslashes($order->partnership?->umkm?->restaurant_name ?? 'Dapur UMKM') }}',
                                                    owner: '{{ addslashes($order->partnership?->umkm?->owner_name ?? 'Mitra UMKM') }}',
                                                    phone: '{{ addslashes($order->partnership?->umkm?->user?->phone_number ?? $order->partnership?->umkm?->phone ?? '-') }}',
                                                    address: '{{ addslashes($order->partnership?->umkm?->address ?? 'Alamat Dapur Terdaftar') }}',
                                                    commodity: '{{ addslashes($commodityName) }}',
                                                    category: '{{ addslashes($commodityCategory) }}',
                                                    unit: '{{ $unit }}',
                                                    orderedQty: {{ $orderedQty }},
                                                    deliveredQty: {{ $deliveredQty }},
                                                    remainingQty: {{ $remainingQty }},
                                                    price: {{ (float) ($firstItem?->unit_price ?? 0) }},
                                                    totalAmount: 'Rp {{ number_format($order->total_amount, 0, ',', '.') }}',
                                                    status: '{{ $order->order_status }}',
                                                    hasBackorderFlow: {{ $hasBackorderFlow ? 'true' : 'false' }},
                                                    approvalStatus: '{{ $approvalStatus }}',
                                                    rejectionReason: '{{ addslashes($batch1?->backorder_rejection_reason ?? '') }}',
                                                    backorderAction: '{{ $batch1?->backorder_action ?? '' }}',
                                                    backorderEta: '{{ $batch1?->backorder_estimated_delivery_date ? \Carbon\Carbon::parse($batch1->backorder_estimated_delivery_date)->translatedFormat('d M Y') : '' }}',
                                                    shipments: [
                                                        @foreach($order->shipmentConfirmations as $conf)
                                                        @php
                                                            $batchPayment = $order->payments->firstWhere('shipment_batch', $conf->shipment_batch);
                                                        @endphp
                                                        {
                                                            batch: {{ $conf->shipment_batch }},
                                                            qty: '{{ number_format((float) $conf->shipped_quantity, 1) }}',
                                                            shippedAt: '{{ $conf->shipped_at ? $conf->shipped_at->format('d/m/Y H:i') : '-' }}',
                                                            receivedAt: '{{ $conf->received_at ? $conf->received_at->format('d/m/Y H:i') : '' }}',
                                                            trackingNotes: '{{ addslashes($conf->tracking_notes ?? '') }}',
                                                            action: '{{ $conf->backorder_action }}',
                                                            invoiceNumber: '{{ $batchPayment?->invoice_number ?? '-' }}',
                                                            invoiceAmount: 'Rp {{ number_format($batchPayment?->amount ?? 0, 0, ',', '.') }}',
                                                            isPaid: {{ $batchPayment && $batchPayment->isPaid() ? 'true' : 'false' }}
                                                        },
                                                        @endforeach
                                                    ]
                                                })"
                                                class="border border-gray-200 text-gray-700 hover:bg-gray-50 px-3.5 py-1.5 rounded-full text-xs font-medium transition cursor-pointer">
                                            Detail
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <div class="pt-2 border-t border-gray-50">
                {{ $orders->links() }}
            </div>
        @endif

    </div>

    <!-- ======================================================== -->
    <!-- MODAL VIEW DETAIL PESANAN LENGKAP                        -->
    <!-- ======================================================== -->
    <div x-cloak x-show="modalDetailOpen" class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div @click.away="modalDetailOpen = false" class="bg-white max-w-2xl w-full rounded-3xl p-6 sm:p-7 space-y-5 shadow-2xl border border-gray-100 max-h-[90vh] overflow-y-auto">
            <!-- HEADER -->
            <div class="flex justify-between items-start border-b border-gray-100 pb-4">
                <div>
                    <span class="text-[10px] font-bold text-[#ea580c] uppercase font-mono tracking-wider">RINCIAN PESANAN PASOKAN</span>
                    <h3 class="text-xl font-extrabold text-gray-900 mt-0.5" x-text="'Pesanan #ORD-' + (activeDetail ? activeDetail.orderCode : '')"></h3>
                    <p class="text-xs text-gray-400" x-text="'Target Pengiriman: ' + (activeDetail ? activeDetail.deliveryDate : '-')"></p>
                </div>
                <button type="button" @click="modalDetailOpen = false" class="text-gray-400 hover:text-gray-700 cursor-pointer p-1 rounded-lg hover:bg-gray-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <template x-if="activeDetail">
                <div class="space-y-4">
                    <!-- SECTION 1: INFO PEMESAN (UMKM) -->
                    <div class="bg-gray-50/80 rounded-2xl p-4 border border-gray-100 space-y-2">
                        <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider block">INFORMASI PEMESAN (DAPUR UMKM)</span>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                            <div>
                                <span class="text-gray-500 text-[11px] block">Restoran / Usaha:</span>
                                <strong class="text-gray-900 text-sm" x-text="activeDetail.restaurant"></strong>
                            </div>
                            <div>
                                <span class="text-gray-500 text-[11px] block">Pemilik / Kontak:</span>
                                <strong class="text-gray-900" x-text="activeDetail.owner + (activeDetail.phone !== '-' ? ' • ' + activeDetail.phone : '')"></strong>
                            </div>
                            <div class="sm:col-span-2">
                                <span class="text-gray-500 text-[11px] block">Alamat Dapur Pengantaran:</span>
                                <p class="text-gray-800 font-medium mt-0.5 flex items-start gap-1.5">
                                    <svg class="w-4 h-4 text-gray-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span x-text="activeDetail.address"></span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: DETAIL BAHAN BAKU & PEMENUHAN -->
                    <div class="border border-gray-100 rounded-2xl p-4 space-y-3 text-xs">
                        <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider block">DETAIL BAHAN BAKU & TAGIHAN</span>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 font-mono">
                            <div class="bg-gray-50 p-3 rounded-xl">
                                <span class="text-gray-500 text-[10px] block font-sans">Komoditas:</span>
                                <strong class="text-gray-900 text-xs block font-sans" x-text="activeDetail.commodity"></strong>
                                <span class="text-[10px] text-gray-400 font-sans" x-text="activeDetail.category"></span>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-xl">
                                <span class="text-gray-500 text-[10px] block font-sans">Target Pesanan:</span>
                                <strong class="text-gray-900 text-xs" x-text="activeDetail.orderedQty + ' ' + activeDetail.unit"></strong>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-xl">
                                <span class="text-gray-500 text-[10px] block font-sans">Telah Dikirim:</span>
                                <strong class="text-emerald-700 text-xs" x-text="activeDetail.deliveredQty + ' ' + activeDetail.unit"></strong>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-xl">
                                <span class="text-gray-500 text-[10px] block font-sans">Total Tagihan:</span>
                                <strong class="text-[#ea580c] text-xs" x-text="activeDetail.totalAmount"></strong>
                            </div>
                        </div>

                        <!-- SISA BACKORDER IF ANY -->
                        <template x-if="activeDetail.remainingQty > 0">
                            <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-xs flex justify-between items-center text-amber-900 font-mono">
                                <span class="font-sans font-medium">Sisa Pasokan Belum Dikirim (Backorder):</span>
                                <strong class="text-amber-800" x-text="activeDetail.remainingQty + ' ' + activeDetail.unit"></strong>
                            </div>
                        </template>

                        <!-- STATUS PERSETUJUAN BACKORDER IF PENDING/REJECTED -->
                        <template x-if="activeDetail.hasBackorderFlow && ['pending', 'rejected'].includes(activeDetail.approvalStatus)">
                            <div class="p-3 rounded-xl text-xs space-y-1"
                                 :class="activeDetail.approvalStatus === 'pending' ? 'bg-amber-50 border border-amber-200 text-amber-900' : 'bg-rose-50 border border-rose-200 text-rose-900'">
                                <strong class="block" x-text="activeDetail.approvalStatus === 'pending' ? 'Menunggu Persetujuan UMKM' : 'Pengajuan Ditolak UMKM'"></strong>
                                <p class="text-[11px]" x-text="activeDetail.approvalStatus === 'pending' ? 'Pengajuan split shipment / sisa barang sedang menunggu approve/tolak dari pihak UMKM.' : (activeDetail.rejectionReason ? 'Alasan: &ldquo;' + activeDetail.rejectionReason + '&rdquo;' : 'UMKM menolak opsi pemenuhan sebagian.')"></p>
                            </div>
                        </template>
                    </div>

                    <!-- SECTION 3: RIWAYAT KLOTER PENGIRIMAN & INVOICE -->
                    <div class="space-y-2">
                        <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider block">RIWAYAT KLOTER PENGIRIMAN & TAGIHAN</span>
                        
                        <template x-if="activeDetail.shipments.length === 0">
                            <div class="text-center py-6 bg-gray-50 rounded-2xl border border-gray-100 text-xs text-gray-400">
                                Belum ada pengiriman yang diberangkatkan untuk pesanan ini.
                            </div>
                        </template>

                        <template x-if="activeDetail.shipments.length > 0">
                            <div class="space-y-2.5">
                                <template x-for="ship in activeDetail.shipments" :key="'ship-modal-' + ship.batch">
                                    <div class="bg-gray-50/70 border border-gray-200/80 rounded-2xl p-4 space-y-2 text-xs">
                                        <div class="flex justify-between items-center">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-[#ea580c] font-mono text-xs" x-text="'Kloter ' + ship.batch"></span>
                                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                                      :class="ship.batch === 2 ? 'bg-amber-100 text-amber-800' : 'bg-orange-100 text-[#ea580c]'"
                                                      x-text="ship.batch === 2 ? 'Batch 2 (Backorder)' : 'Batch 1'"></span>
                                            </div>
                                            <span class="text-[10px] text-gray-400 font-mono" x-text="'Berangkat: ' + ship.shippedAt + ' WIB'"></span>
                                        </div>

                                        <div class="flex justify-between items-center text-xs">
                                            <span class="text-gray-600">Kuantitas Dikirim:</span>
                                            <strong class="font-mono text-gray-900" x-text="ship.qty + ' ' + activeDetail.unit"></strong>
                                        </div>

                                        <template x-if="ship.trackingNotes">
                                            <p class="text-[11px] text-gray-600 italic bg-white p-2.5 rounded-xl border border-gray-100" x-text="'Catatan Kurir/Armada: &ldquo;' + ship.trackingNotes + '&rdquo;'"></p>
                                        </template>

                                        <template x-if="ship.receivedAt">
                                            <div class="text-[11px] text-emerald-700 font-bold flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                <span x-text="'Telah Diterima Dapur UMKM: ' + ship.receivedAt + ' WIB'"></span>
                                            </div>
                                        </template>

                                        <!-- INVOICE INFO -->
                                        <div class="border-t border-gray-200/70 pt-2 flex justify-between items-center text-[11px] font-mono">
                                            <span class="text-gray-500" x-text="'Invoice: ' + ship.invoiceNumber"></span>
                                            <span class="px-2.5 py-0.5 rounded-full font-bold text-[10px]"
                                                  :class="ship.isPaid ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                                                  x-text="ship.isPaid ? '✓ LUNAS (' + ship.invoiceAmount + ')' : 'BELUM BAYAR (' + ship.invoiceAmount + ')'"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- FOOTER BUTTONS -->
                    <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-3">
                        <button type="button" @click="modalDetailOpen = false" class="px-5 py-2.5 rounded-full text-xs font-semibold text-gray-600 hover:bg-gray-100 transition cursor-pointer">
                            Tutup
                        </button>
                        
                        <template x-if="['pending', 'confirmed', 'MENUNGGU_KONFIRMASI_BARANG'].includes(activeDetail.status)">
                            <button type="button"
                                    @click="modalDetailOpen = false; openKirimModal(activeDetail);"
                                    class="bg-[#ea580c] hover:bg-[#c2410c] text-white px-5 py-2.5 rounded-full text-xs font-bold transition shadow-xs flex items-center gap-1.5 cursor-pointer">
                                <span>Cek Stok & Kirim Pesanan →</span>
                            </button>
                        </template>

                        <template x-if="activeDetail.hasBackorderFlow && activeDetail.approvalStatus === 'approved' && activeDetail.shipments.length === 0">
                            <button type="button"
                                    @click="modalDetailOpen = false; openKirimBatch1Modal(activeDetail);"
                                    class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-full text-xs font-bold transition shadow-xs flex items-center gap-1.5 cursor-pointer">
                                <span>Berangkatkan Kloter 1 →</span>
                            </button>
                        </template>

                        <template x-if="activeDetail.backorderAction === 'create_backorder' && activeDetail.remainingQty > 0">
                            <button type="button"
                                    @click="modalDetailOpen = false; openBackorderModal(activeDetail);"
                                    class="bg-amber-500 hover:bg-amber-600 text-white px-5 py-2.5 rounded-full text-xs font-bold transition shadow-xs flex items-center gap-1.5 cursor-pointer">
                                <span>Kirim Sisa Backorder →</span>
                            </button>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL 1: CEK MANUAL STOK GUDANG & PROSES PENGIRIMAN      -->
    <!-- ======================================================== -->
    <div x-cloak x-show="modalKirimOpen" class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div @click.away="modalKirimOpen = false" class="bg-white max-w-xl w-full rounded-3xl p-6 sm:p-7 space-y-5 shadow-2xl border border-gray-100 max-h-[92vh] overflow-y-auto">
            
            <!-- HEADER MODAL -->
            <div class="flex justify-between items-start border-b border-gray-100 pb-4">
                <div>
                    <span class="text-[10px] font-bold text-[#ea580c] uppercase font-mono tracking-wider">VERIFIKASI MANUAL STOK GUDANG</span>
                    <h3 class="text-xl font-extrabold text-gray-900 mt-0.5">Cek Stok Fisik & Kirim Pesanan</h3>
                    <p class="text-xs text-gray-500" x-text="'Pesanan #' + activeOrder.orderCode + ' — ' + activeOrder.restaurant"></p>
                </div>
                <button type="button" @click="modalKirimOpen = false" class="text-gray-400 hover:text-gray-700 cursor-pointer p-1 rounded-lg hover:bg-gray-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- FORM PENGIRIMAN -->
            <form :action="`/supplier/pengiriman/${activeOrder.id}/kirim`" method="POST" class="space-y-4">
                @csrf

                <!-- INFO PESANAN DARI UMKM -->
                <div class="bg-orange-50/70 border border-orange-200/80 rounded-2xl p-4 flex justify-between items-center text-xs">
                    <div>
                        <span class="text-gray-500 block text-[11px]">Komoditas Dipesan UMKM</span>
                        <strong class="text-gray-900 font-bold text-sm" x-text="activeOrder.commodity"></strong>
                    </div>
                    <div class="text-right">
                        <span class="text-gray-500 block text-[11px]">Target Kuantitas Pesanan</span>
                        <strong class="text-[#ea580c] font-mono text-base font-extrabold" x-text="activeOrder.orderedQty + ' ' + activeOrder.unit"></strong>
                    </div>
                </div>

                <!-- LANGKAH 1: PILIH HASIL CEK MANUAL STOK FISIK DI GUDANG -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-gray-800">
                        Hasil Pengecekan Manual Stok di Gudang: <span class="text-rose-500">*</span>
                    </label>

                    <div class="space-y-2.5">
                        <!-- OPSI 1: STOK SESUAI / CUKUP (LANGSUNG KIRIM PENUH) -->
                        <div @click="selectStockOption('full')"
                             class="cursor-pointer border-2 rounded-2xl p-4 transition flex items-start gap-3"
                             :class="selectedOption === 'full' ? 'border-emerald-500 bg-emerald-50/50 shadow-xs ring-2 ring-emerald-500/20' : 'border-gray-200 hover:border-gray-300 bg-white'">
                            <div class="mt-0.5">
                                <input type="radio" name="_opt_radio" value="full" :checked="selectedOption === 'full'" class="text-emerald-600 focus:ring-emerald-500">
                            </div>
                            <div class="flex-1 text-xs">
                                <div class="flex items-center justify-between">
                                    <strong class="font-extrabold text-gray-900 text-sm flex items-center gap-1.5">
                                        <span>Stok Cukup Sesuai Pesanan</span>
                                    </strong>
                                    <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2.5 py-0.5 rounded-full">Tinggal Kirim</span>
                                </div>
                                <p class="text-gray-600 mt-1 leading-relaxed text-[11px]">
                                    Stok fisik di gudang lengkap (<strong x-text="activeOrder.orderedQty + ' ' + activeOrder.unit"></strong>). Barang langsung dikirim penuh sekaligus ke UMKM.
                                </p>
                            </div>
                        </div>

                        <!-- OPSI 2: STOK SEBAGIAN / KURANG (CREATE BACKORDER) -->
                        <div @click="selectStockOption('partial')"
                             class="cursor-pointer border-2 rounded-2xl p-4 transition flex items-start gap-3"
                             :class="selectedOption === 'partial' ? 'border-[#ea580c] bg-orange-50/50 shadow-xs ring-2 ring-orange-500/20' : 'border-gray-200 hover:border-gray-300 bg-white'">
                            <div class="mt-0.5">
                                <input type="radio" name="_opt_radio" value="partial" :checked="selectedOption === 'partial'" class="text-[#ea580c] focus:ring-[#ea580c]">
                            </div>
                            <div class="flex-1 text-xs">
                                <div class="flex items-center justify-between">
                                    <strong class="font-extrabold text-gray-900 text-sm flex items-center gap-1.5">
                                        <span>Stok Kurang (Create Backorder)</span>
                                    </strong>
                                    <span class="bg-orange-100 text-[#ea580c] text-[10px] font-bold px-2.5 py-0.5 rounded-full">Kirim Nanti Sisa</span>
                                </div>
                                <p class="text-gray-600 mt-1 leading-relaxed text-[11px]">
                                    Stok fisik hanya ada sebagian. Kirim yang ada sekarang (Batch 1), dan terbitkan <strong>Backorder</strong> untuk sisa pesanan yang akan dikirim nanti (Batch 2) saat restock.
                                </p>
                            </div>
                        </div>

                        <!-- OPSI 3: BENAR-BENAR TIDAK ADA STOK / KOSONG (NO BACKORDER) -->
                        <div @click="selectStockOption('no_backorder')"
                             class="cursor-pointer border-2 rounded-2xl p-4 transition flex items-start gap-3"
                             :class="selectedOption === 'no_backorder' ? 'border-rose-500 bg-rose-50/50 shadow-xs ring-2 ring-rose-500/20' : 'border-gray-200 hover:border-gray-300 bg-white'">
                            <div class="mt-0.5">
                                <input type="radio" name="_opt_radio" value="no_backorder" :checked="selectedOption === 'no_backorder'" class="text-rose-600 focus:ring-rose-500">
                            </div>
                            <div class="flex-1 text-xs">
                                <div class="flex items-center justify-between">
                                    <strong class="font-extrabold text-gray-900 text-sm flex items-center gap-1.5">
                                        <span>Stok Kosong / Tidak Sanggup (No Backorder)</span>
                                    </strong>
                                    <span class="bg-rose-100 text-rose-800 text-[10px] font-bold px-2.5 py-0.5 rounded-full">Batalkan / Tanpa Sisa</span>
                                </div>
                                <p class="text-gray-600 mt-1 leading-relaxed text-[11px]">
                                    Stok di gudang benar-benar habis atau tidak bisa restock sisa barang. Pesanan/sisa dibatalkan permanen dan tagihan UMKM disesuaikan.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LANGKAH 2: DETAIL KUANTITAS BERDASARKAN HASIL CEK STOK -->
                <input type="hidden" name="backorder_action" :value="backorderAction">

                <!-- KONDISI A: FULL SHIPMENT -->
                <template x-if="selectedOption === 'full'">
                    <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 space-y-1.5 text-xs">
                        <div class="flex items-center justify-between font-bold text-emerald-800">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>Pengiriman Penuh (100% Terpenuhi)</span>
                            </span>
                            <span class="font-mono text-sm" x-text="activeOrder.orderedQty + ' ' + activeOrder.unit"></span>
                        </div>
                        <p class="text-emerald-700 text-[11px]">
                            Seluruh kuantitas pesanan akan dikirimkan langsung. Tidak ada kewajiban sisa barang dan tagihan penuh diterbitkan untuk UMKM.
                        </p>
                        <input type="hidden" name="qty_to_ship" :value="activeOrder.orderedQty">
                    </div>
                </template>

                <!-- KONDISI B: PARTIAL + CREATE BACKORDER -->
                <template x-if="selectedOption === 'partial'">
                    <div class="bg-orange-50/60 border border-orange-200 rounded-2xl p-4 space-y-3 text-xs">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-gray-800">
                                Berapa Kuantitas Fisik yang Ada di Gudang Sekarang? <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="number"
                                       step="0.1"
                                       min="0.1"
                                       :max="activeOrder.orderedQty - 0.1"
                                       x-model.number="qtyToShip"
                                       name="qty_to_ship"
                                       required
                                       class="w-full bg-white border border-orange-300 rounded-xl px-4 py-2.5 text-sm font-bold font-mono focus:ring-2 focus:ring-[#ea580c] outline-hidden">
                                <span class="absolute right-4 top-2.5 text-xs font-bold text-gray-400" x-text="activeOrder.unit"></span>
                            </div>
                            <p class="text-[11px] text-gray-500">Kuantitas ini akan langsung diberangkatkan pada Batch 1.</p>
                        </div>

                        <!-- RINCIAN PEMBAGIAN BATCH 1 & BATCH 2 -->
                        <div class="bg-white rounded-xl p-3 border border-orange-200 space-y-2">
                            <div class="flex justify-between items-center text-xs font-mono">
                                <span class="text-gray-600">Kirim Sekarang (Batch 1):</span>
                                <strong class="text-[#ea580c] font-bold" x-text="qtyToShip + ' ' + activeOrder.unit"></strong>
                            </div>
                            <div class="flex justify-between items-center text-xs font-mono border-t border-gray-100 pt-1.5">
                                <span class="text-amber-800 font-bold flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <span>Sisa Backorder (Batch 2 Nanti):</span>
                                </span>
                                <strong class="text-amber-600 font-extrabold" x-text="(Math.max(0, activeOrder.orderedQty - qtyToShip)).toFixed(1) + ' ' + activeOrder.unit"></strong>
                            </div>
                        </div>

                        <!-- INPUT ESTIMASI TANGGAL KIRIM SISA & CATATAN KE UMKM -->
                        <div class="space-y-3 pt-1 border-t border-orange-200/70">
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-gray-800">
                                    Estimasi Tanggal Pengiriman Sisa Barang (Kloter 2): <span class="text-rose-500">*</span>
                                </label>
                                <input type="date"
                                       name="backorder_estimated_delivery_date"
                                       min="{{ date('Y-m-d') }}"
                                       :required="selectedOption === 'partial'"
                                       class="w-full bg-white border border-orange-300 rounded-xl px-4 py-2.5 text-xs font-bold focus:ring-2 focus:ring-[#ea580c] outline-hidden">
                                <p class="text-[11px] text-gray-500">Tanggal janji restock kapan sisa barang akan dikirim ke dapur UMKM.</p>
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-gray-800">
                                    Catatan / Alasan Pengajuan Backorder ke UMKM: <span class="text-rose-500">*</span>
                                </label>
                                <textarea name="backorder_notes"
                                          rows="2"
                                          :required="selectedOption === 'partial'"
                                          placeholder="Contoh: Stok di gudang hanya tersisa 100 kg. Sisa 50 kg akan dipanen kembali dan dikirim lusa pagi pukul 06:00 WIB."
                                          class="w-full bg-white border border-orange-300 rounded-xl p-3 text-xs focus:ring-2 focus:ring-[#ea580c] outline-hidden"></textarea>
                                <p class="text-[11px] text-gray-500">Alasan ini akan ditampilkan pada kotak persetujuan Backorder di sisi UMKM.</p>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- KONDISI C: NO BACKORDER / STOK BENAR-BENAR KOSONG -->
                <template x-if="selectedOption === 'no_backorder'">
                    <div class="bg-rose-50/70 border border-rose-200 rounded-2xl p-4 space-y-3 text-xs">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-gray-800">
                                Kuantitas Fisik yang Bisa Dikirim (Isi 0 jika benar-benar tidak ada stok):
                            </label>
                            <div class="relative">
                                <input type="number"
                                       step="0.1"
                                       min="0"
                                       :max="activeOrder.orderedQty - 0.1"
                                       x-model.number="qtyToShip"
                                       name="qty_to_ship"
                                       required
                                       class="w-full bg-white border border-rose-300 rounded-xl px-4 py-2.5 text-sm font-bold font-mono focus:ring-2 focus:ring-rose-500 outline-hidden">
                                <span class="absolute right-4 top-2.5 text-xs font-bold text-gray-400" x-text="activeOrder.unit"></span>
                            </div>
                        </div>

                        <!-- ALERT KONDISI KOSONG VS PARSIAL BATAL SISA -->
                        <template x-if="qtyToShip == 0">
                            <div class="bg-rose-100 text-rose-900 p-3 rounded-xl text-[11px] leading-relaxed border border-rose-200 space-y-1">
                                <strong class="flex items-center gap-1.5 font-bold">
                                    <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    <span>Stok Benar-benar Kosong di Gudang:</span>
                                </strong>
                                <span>Seluruh pesanan ini akan dibatalkan otomatis. UMKM tidak akan ditagih, dan jadwal pesanan ini ditandai dibatalkan.</span>
                            </div>
                        </template>

                        <template x-if="qtyToShip > 0">
                            <div class="bg-white rounded-xl p-3 border border-rose-200 space-y-1.5 text-[11px]">
                                <div class="flex justify-between items-center text-xs font-mono">
                                    <span class="text-gray-600">Dikirim Hari Ini:</span>
                                    <strong class="text-gray-900 font-bold" x-text="qtyToShip + ' ' + activeOrder.unit"></strong>
                                </div>
                                <div class="flex justify-between items-center text-xs font-mono text-rose-700 border-t border-gray-100 pt-1">
                                    <span>Sisa Pesanan Dibatalkan Permanen:</span>
                                    <strong x-text="(activeOrder.orderedQty - qtyToShip).toFixed(1) + ' ' + activeOrder.unit"></strong>
                                </div>
                                <div class="flex justify-between items-center text-xs font-mono font-bold text-gray-900 border-t border-gray-100 pt-1">
                                    <span>Penyesuaian Total Tagihan:</span>
                                    <span class="text-[#ea580c]" x-text="'Rp ' + Math.round(qtyToShip * activeOrder.price).toLocaleString('id-ID')"></span>
                                </div>
                                <p class="text-[10px] text-amber-800 bg-amber-50 p-2.5 rounded-lg border border-amber-200 mt-1 flex items-start gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-amber-700 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>Pengajuan ini akan menunggu validasi & persetujuan (approve/tolak) dari UMKM terlebih dahulu sebelum barang diberangkatkan.</span>
                                </p>
                            </div>
                        </template>
                    </div>
                </template>

                <!-- INPUT CATATAN PENGIRIMAN & NOMOR RESI/KURIR -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-700">Catatan Pengiriman / Info Armada (Opsional)</label>
                    <textarea name="tracking_notes"
                              rows="2"
                              placeholder="Contoh: Kurir internal Pak Budi (08123456789), Mobil Pickup B 1234 XYZ, perkiraan tiba pukul 07:00 WIB."
                              class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-xs focus:ring-2 focus:ring-[#ea580c] focus:bg-white outline-hidden"></textarea>
                </div>

                <!-- SUBMIT BUTTON -->
                <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-3">
                    <button type="button" @click="modalKirimOpen = false" class="px-4 py-2 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-100 transition">
                        Batal
                    </button>
                    
                    <!-- TOMBOL SESUAI PILIHAN -->
                    <template x-if="selectedOption === 'full'">
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-xl text-xs font-bold transition shadow-xs flex items-center gap-2 cursor-pointer">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            <span>Stok Sesuai, Kirim Penuh Sekarang →</span>
                        </button>
                    </template>

                    <template x-if="selectedOption === 'partial'">
                        <button type="submit" class="bg-[#ea580c] hover:bg-[#c2410c] text-white px-5 py-2.5 rounded-xl text-xs font-bold transition shadow-xs flex items-center gap-2 cursor-pointer">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            <span>Ajukan Backorder ke UMKM →</span>
                        </button>
                    </template>

                    <template x-if="selectedOption === 'no_backorder' && qtyToShip == 0">
                        <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white px-5 py-2.5 rounded-xl text-xs font-bold transition shadow-xs flex items-center gap-2 cursor-pointer">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span>Konfirmasi Stok Kosong & Batalkan Pesanan</span>
                        </button>
                    </template>

                    <template x-if="selectedOption === 'no_backorder' && qtyToShip > 0">
                        <button type="submit" class="bg-stone-700 hover:bg-stone-800 text-white px-5 py-2.5 rounded-xl text-xs font-bold transition shadow-xs flex items-center gap-2 cursor-pointer">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            <span>Ajukan Pemenuhan Sebagian (Tanpa Backorder) →</span>
                        </button>
                    </template>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL 1.b: BERANGKATKAN BARANG (SETELAH UMKM APPROVE)    -->
    <!-- ======================================================== -->
    <div x-cloak x-show="modalKirimBatch1Open" class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div @click.away="modalKirimBatch1Open = false" class="bg-white max-w-lg w-full rounded-3xl p-6 space-y-5 shadow-2xl border border-gray-100">
            <div class="flex justify-between items-start border-b border-gray-100 pb-4">
                <div>
                    <span class="text-[10px] font-bold text-emerald-600 uppercase font-mono tracking-wider" x-text="activeBatch1.action === 'no_backorder' ? 'PENGIRIMAN PASOKAN (NO BACKORDER)' : 'KLOTER 1: PENGIRIMAN PASOKAN'"></span>
                    <h3 class="text-xl font-extrabold text-gray-900 mt-0.5" x-text="activeBatch1.action === 'no_backorder' ? 'Berangkatkan Barang Pesanan' : 'Berangkatkan Barang Kloter 1'"></h3>
                    <p class="text-xs text-gray-500" x-text="'Pesanan #' + activeBatch1.orderCode + ' — ' + activeBatch1.restaurant"></p>
                </div>
                <button type="button" @click="modalKirimBatch1Open = false" class="text-gray-400 hover:text-gray-700 cursor-pointer p-1 rounded-lg hover:bg-gray-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form :action="`/supplier/pengiriman/${activeBatch1.id}/kirim-batch1`" method="POST" class="space-y-4">
                @csrf

                <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 space-y-1 text-xs">
                    <div class="flex justify-between items-center">
                        <span class="text-emerald-800">Komoditas:</span>
                        <strong class="text-emerald-950 font-bold" x-text="activeBatch1.commodity"></strong>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-emerald-800" x-text="activeBatch1.action === 'no_backorder' ? 'Kuantitas yang Dikirim:' : 'Kuantitas Kloter 1 yang Dikirim:'"></span>
                        <strong class="text-emerald-700 font-extrabold font-mono" x-text="activeBatch1.qty + ' ' + activeBatch1.unit"></strong>
                    </div>
                    <p class="text-[11px] text-emerald-800 pt-1 border-t border-emerald-200/60 mt-1 flex items-start gap-1.5">
                        <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        <span x-text="activeBatch1.action === 'no_backorder' ? 'Pengajuan pemenuhan sebagian (no backorder) telah disetujui pihak UMKM. Tagihan resmi otomatis terbit saat barang diberangkatkan.' : 'Pengajuan Backorder telah disetujui pihak UMKM. Invoice Batch 1 akan otomatis terbit saat barang diberangkatkan.'"></span>
                    </p>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-700">Catatan Pengiriman / Info Armada (Opsional)</label>
                    <textarea name="tracking_notes"
                              rows="2"
                              placeholder="Contoh: Kurir internal Pak Joko (08123456789), Mobil Pickup B 1234 XYZ."
                              class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-xs focus:ring-2 focus:ring-emerald-500 focus:bg-white outline-hidden"></textarea>
                </div>

                <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-3">
                    <button type="button" @click="modalKirimBatch1Open = false" class="px-4 py-2 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-100 transition">
                        Batal
                    </button>
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-xl text-xs font-bold transition shadow-xs flex items-center gap-2 cursor-pointer">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                        <span x-text="activeBatch1.action === 'no_backorder' ? 'Berangkatkan Barang Sekarang →' : 'Berangkatkan Kloter 1 Sekarang →'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL 2: KIRIM SISA BACKORDER (BATCH 2)                  -->
    <!-- ======================================================== -->
    <div x-cloak x-show="modalBackorderOpen" class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div @click.away="modalBackorderOpen = false" class="bg-white max-w-lg w-full rounded-3xl p-6 space-y-5 shadow-2xl border border-gray-100">
            <div class="flex justify-between items-start border-b border-gray-100 pb-4">
                <div>
                    <span class="text-[10px] font-bold text-amber-600 uppercase font-mono tracking-wider">KLOTER 2: PEMENUHAN BACKORDER</span>
                    <h3 class="text-xl font-extrabold text-gray-900 mt-0.5">Kirim Sisa Barang ke UMKM</h3>
                    <p class="text-xs text-gray-500" x-text="'Pesanan #' + activeBackorder.orderCode + ' — ' + activeBackorder.restaurant"></p>
                </div>
                <button type="button" @click="modalBackorderOpen = false" class="text-gray-400 hover:text-gray-700 cursor-pointer p-1 rounded-lg hover:bg-gray-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form :action="`/supplier/pengiriman/${activeBackorder.id}/kirim-backorder`" method="POST" class="space-y-4">
                @csrf

                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 space-y-1 text-xs">
                    <div class="flex justify-between items-center">
                        <span class="text-amber-800">Komoditas:</span>
                        <strong class="text-amber-950 font-bold" x-text="activeBackorder.commodity"></strong>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-amber-800">Sisa Kewajiban yang Dikirim:</span>
                        <strong class="text-amber-600 font-extrabold font-mono" x-text="activeBackorder.remainingQty + ' ' + activeBackorder.unit"></strong>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-700">Catatan Pengiriman Kloter 2 (Opsional)</label>
                    <textarea name="tracking_notes"
                              rows="2"
                              placeholder="Contoh: Sisa 50 kg dikirim pagi ini dengan kurir mobil box B 9876 KLM."
                              class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-xs focus:ring-2 focus:ring-[#ea580c] focus:bg-white outline-hidden"></textarea>
                </div>

                <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-3">
                    <button type="button" @click="modalBackorderOpen = false" class="px-4 py-2 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-100 transition">
                        Batal
                    </button>
                    <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white px-5 py-2.5 rounded-xl text-xs font-bold transition shadow-xs flex items-center gap-2 cursor-pointer">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        <span>Kirim Sisa Backorder Sekarang →</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
    function supplierPengirimanPage() {
        return {
            modalDetailOpen: false,
            activeDetail: null,
            modalKirimOpen: false,
            modalKirimBatch1Open: false,
            modalBackorderOpen: false,
            selectedOption: 'full',
            activeOrder: {
                id: '',
                orderCode: '',
                restaurant: '',
                commodity: '',
                unit: 'kg',
                orderedQty: 0,
                price: 0
            },
            activeBatch1: {
                id: '',
                orderCode: '',
                restaurant: '',
                commodity: '',
                unit: 'kg',
                qty: 0,
                action: 'create_backorder'
            },
            activeBackorder: {
                id: '',
                orderCode: '',
                restaurant: '',
                commodity: '',
                unit: 'kg',
                remainingQty: 0
            },
            qtyToShip: 0,
            backorderAction: 'none',

            openDetailModal(ord) {
                this.activeDetail = ord;
                this.modalDetailOpen = true;
            },

            openKirimModal(ord) {
                this.activeOrder = ord;
                this.selectedOption = 'full';
                this.qtyToShip = ord.orderedQty;
                this.backorderAction = 'none';
                this.modalKirimOpen = true;
            },

            openKirimBatch1Modal(ord) {
                this.activeBatch1 = {
                    id: ord.id,
                    orderCode: ord.orderCode,
                    restaurant: ord.restaurant,
                    commodity: ord.commodity,
                    unit: ord.unit,
                    qty: ord.qty,
                    action: ord.action || 'create_backorder'
                };
                this.modalKirimBatch1Open = true;
            },

            selectStockOption(opt) {
                this.selectedOption = opt;
                if (opt === 'full') {
                    this.qtyToShip = this.activeOrder.orderedQty;
                    this.backorderAction = 'none';
                } else if (opt === 'partial') {
                    this.backorderAction = 'create_backorder';
                    if (this.qtyToShip >= this.activeOrder.orderedQty || this.qtyToShip <= 0) {
                        this.qtyToShip = Math.max(0.1, Math.round(this.activeOrder.orderedQty * 0.7 * 10) / 10);
                    }
                } else if (opt === 'no_backorder') {
                    this.backorderAction = 'no_backorder';
                    this.qtyToShip = 0;
                }
            },

            openBackorderModal(ord) {
                this.activeBackorder = ord;
                this.modalBackorderOpen = true;
            }
        };
    }
</script>
@endsection
