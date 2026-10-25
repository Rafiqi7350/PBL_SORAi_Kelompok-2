@extends('layouts.supplier')

@section('content')
<div class="space-y-8 max-w-7xl mx-auto pb-12">

    <!-- HEADER TITLE & INFORMASI PEMASOK -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-100 pb-4">
        <div>
            <span class="text-xs font-bold text-orange-600 uppercase font-mono tracking-wider">PANEL ANALISIS B2B PEMASOK</span>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Dashboard Analisis Operasional & Kemitraan</h2>
            <p class="text-xs text-gray-500 mt-0.5">Monitoring terpadu pesanan masuk, ketersediaan stok bahan baku, logistik pengiriman subuh, dan performa kerja sama mitra UMKM.</p>
        </div>
        <div class="flex items-center gap-2 self-start sm:self-auto">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-orange-50 text-orange-700 border border-orange-100">
                <span class="w-2 h-2 rounded-full bg-orange-500 animate-pulse"></span>
                Periode: {{ $formattedPeriod ?? now()->translatedFormat('F Y') }}
            </span>
            <span class="px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                ✓ Mitra Terverifikasi
            </span>
        </div>
    </div>

    <!-- 4 PILAR METRICS CARDS PEMASOK -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        
        <!-- 1. TOTAL OMSET DITERIMA -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 space-y-1">
            <span class="text-[11px] font-mono font-bold text-gray-400 uppercase tracking-wider">TOTAL OMSET DITERIMA</span>
            <p class="text-2xl font-bold text-orange-600">Rp {{ number_format($totalOmset, 0, ',', '.') }}</p>
            <p class="text-[11px] text-emerald-600 font-medium">▲ +{{ $growthPercentage }}% dari bulan lalu</p>
        </div>

        <!-- 2. PESANAN DIPROSES -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 space-y-1">
            <span class="text-[11px] font-mono font-bold text-gray-400 uppercase tracking-wider">PESANAN DIPROSES</span>
            <p class="text-2xl font-bold text-gray-900">{{ $activeOrdersCount }} Pesanan</p>
            <p class="text-[11px] text-amber-600 font-medium">● {{ $perluPenyesuaianCount }} Perlu Penyesuaian Stok</p>
        </div>

        <!-- 3. TOTAL STOK TERJUAL -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 space-y-1">
            <span class="text-[11px] font-mono font-bold text-gray-400 uppercase tracking-wider">TOTAL STOK TERJUAL</span>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($totalStokTerjual, 0, ',', '.') }} kg</p>
            <p class="text-[11px] text-teal-600 font-medium">{{ $topCommodityName }}</p>
        </div>

        <!-- 4. FULFILLMENT RATE -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 space-y-1">
            <span class="text-[11px] font-mono font-bold text-gray-400 uppercase tracking-wider">FULFILLMENT RATE</span>
            <p class="text-2xl font-bold text-emerald-700">{{ $fulfillmentRate }}%</p>
            <p class="text-[11px] text-teal-600 font-medium">✓ Kualitas Sesuai Kontrak</p>
        </div>

    </div>

    <!-- VISUAL DIAGRAM TREN PENJUALAN BAHAN BAKU (KG/BULAN) -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-gray-100 space-y-6">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="font-bold text-lg text-gray-900">Grafik Tren Penjualan Bahan Baku (Kg/Bulan)</h3>
                <p class="text-xs text-gray-500 mt-1">Realisasi pasok komoditas daging ayam ke UMKM Mitra</p>
            </div>
            <div class="flex items-center gap-4 text-xs font-medium text-gray-700 self-start sm:self-auto">
                <span class="flex items-center gap-1.5">
                    <span class="w-3.5 h-3.5 bg-[#f6c95d] rounded-xs inline-block"></span>
                    Target
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3.5 h-3.5 bg-[#ea580c] rounded-xs inline-block"></span>
                    Realisasi
                </span>
            </div>
        </div>

        @php
            $maxChartVal = max(500, ...array_column($monthsTrend, 'target'), ...array_column($monthsTrend, 'realisasi'));
        @endphp
        <div class="pt-6">
            <div class="h-44 sm:h-52 flex items-end justify-between gap-2 sm:gap-6 px-2 sm:px-6">
                @foreach($monthsTrend as $bar)
                    <div class="flex-1 flex flex-col items-center h-full justify-end">
                        <div class="w-full flex items-end justify-center gap-1 sm:gap-2 h-full">
                            <!-- Target (Kuning) -->
                            <div style="height: {{ ($bar['target'] / $maxChartVal) * 100 }}%" class="w-1/2 max-w-[56px] bg-[#f6c95d] rounded-t-lg relative group transition-all hover:brightness-105">
                                <span class="absolute -top-7 left-1/2 -translate-x-1/2 bg-black text-white text-[10px] font-sans px-2 py-0.5 rounded opacity-0 group-hover:opacity-100 transition whitespace-nowrap z-20">
                                    Target: {{ $bar['target'] }}kg
                                </span>
                            </div>
                            <!-- Realisasi (Oranye) -->
                            <div style="height: {{ ($bar['realisasi'] / $maxChartVal) * 100 }}%" class="w-1/2 max-w-[56px] bg-[#ea580c] rounded-t-lg relative group transition-all hover:brightness-105">
                                <span class="absolute -top-7 left-1/2 -translate-x-1/2 bg-black text-white text-[10px] font-sans px-2 py-0.5 rounded opacity-0 group-hover:opacity-100 transition whitespace-nowrap z-20">
                                    Realisasi: {{ $bar['realisasi'] }}kg
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <!-- Garis pemisah halus sesuai tema Sorai (tanpa garis hitam) -->
            <div class="border-b border-gray-100 w-full mt-0"></div>
            <!-- Label bulan di bawah garis -->
            <div class="flex justify-between items-center px-2 sm:px-6 pt-2.5">
                @foreach($monthsTrend as $bar)
                    <div class="flex-1 text-center font-sans text-xs font-medium text-gray-600">
                        {{ $bar['month'] }}
                    </div>
                @endforeach
            </div>
        </div>

    </div>

    <!-- HISTORI & RIWAYAT TRANSAKSI SELESAI -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden space-y-4 p-6 sm:p-8">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-gray-100 pb-4">
            <div>
                <h3 class="font-bold text-lg text-gray-900">Histori & Riwayat Transaksi Selesai</h3>
                <p class="text-xs text-gray-500 mt-0.5">Catatan pesanan pasokan komoditas yang telah diproses untuk mitra UMKM</p>
            </div>
            <span class="text-xs font-semibold text-orange-600 self-start sm:self-auto bg-orange-50 px-3 py-1 rounded-full border border-orange-100">
                Database B2B SORAI
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-gray-50/80 text-gray-600 uppercase text-[11px] font-semibold border-b border-gray-100">
                    <tr>
                        <th class="py-3.5 px-4 font-semibold">Tanggal</th>
                        <th class="py-3.5 px-4 font-semibold">ID Transaksi</th>
                        <th class="py-3.5 px-4 font-semibold">Mitra UMKM</th>
                        <th class="py-3.5 px-4 font-semibold">Komoditas Pasok</th>
                        <th class="py-3.5 px-4 font-semibold">Volume Pasok</th>
                        <th class="py-3.5 px-4 font-semibold">Total Pembayaran</th>
                        <th class="py-3.5 px-4 font-semibold text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($transactions as $trx)
                        @php
                            $statusKey = $trx['status'];
                            $badgeClasses = match($statusKey) {
                                'MENUNGGU_KONFIRMASI_BARANG' => 'bg-amber-100 text-amber-800',
                                'PENYESUAIAN_STOK' => 'bg-blue-100 text-blue-800',
                                'DIKIRIM' => 'bg-purple-100 text-purple-800',
                                'SELESAI' => 'bg-emerald-100 text-emerald-800',
                                default => 'bg-emerald-100 text-emerald-800',
                            };
                            $statusLabel = match($statusKey) {
                                'MENUNGGU_KONFIRMASI_BARANG' => 'Menunggu Konfirmasi Barang',
                                'PENYESUAIAN_STOK' => 'Penyesuaian Stok',
                                'DIKIRIM' => 'Dikirim',
                                'SELESAI' => 'Selesai',
                                default => ucwords(strtolower(str_replace('_', ' ', $statusKey))),
                            };
                        @endphp
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="py-3.5 px-4 text-xs text-gray-500 font-medium whitespace-nowrap">
                                {{ $trx['date'] ?? '01/10/2026' }}
                            </td>
                            <td class="py-3.5 px-4 font-mono text-xs font-bold text-orange-600 whitespace-nowrap">
                                {{ $trx['id'] }}
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-gray-900 whitespace-nowrap">
                                {{ $trx['customerName'] }}
                            </td>
                            <td class="py-3.5 px-4 text-gray-700 font-medium">
                                {{ $trx['commodity'] }}
                            </td>
                            <td class="py-3.5 px-4 text-gray-800 font-medium whitespace-nowrap">
                                {{ $trx['volume'] }}
                            </td>
                            <td class="py-3.5 px-4 font-bold text-gray-900 whitespace-nowrap">
                                {{ $trx['total'] }}
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold {{ $badgeClasses }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-gray-400 text-xs">
                                Belum ada data transaksi selesai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

</div>
@endsection
