@extends('layouts.supplier')

@section('title', 'Detail Kontrak — ' . $contract['umkm_name'] . ' — Portal Pemasok SORAI')

@section('content')
<div class="space-y-6">

    {{-- Breadcrumb & Back Action --}}
    <div class="flex items-center justify-between gap-3 flex-wrap">
        <a href="{{ route('supplier.contract-monitoring.index') }}"
           class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-[#ea580c] transition group">
            <svg class="w-4 h-4 text-slate-400 group-hover:text-[#ea580c] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <span>Kembali ke Pemantauan Kontrak</span>
        </a>

        <div class="flex items-center gap-2">
            <span class="text-xs font-mono font-bold px-2.5 py-1 rounded-lg bg-orange-50 text-[#ea580c] border border-orange-200/80 tabular-nums">
                {{ $contract['contract_code'] }}
            </span>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border {{ $contract['status_badge_class'] }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $contract['status_dot_class'] }}"></span>
                <span>{{ $contract['status_label'] }}</span>
            </span>
        </div>
    </div>

    {{-- Header Banner Card --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-7 shadow-[0_1px_2px_rgba(15,23,42,0.04)] flex flex-col md:flex-row md:items-center justify-between gap-5">
        <div class="space-y-1.5">
            <span class="text-[11px] font-bold uppercase text-[#ea580c] tracking-wider block font-mono">
                DETAIL KONTRAK KEMITRAAN RESMI
            </span>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                {{ $contract['umkm_name'] }}
            </h1>
            <p class="text-xs text-slate-500 flex items-center gap-2 flex-wrap">
                <span>Komoditas: <strong class="text-slate-800 font-semibold">{{ $contract['commodity_name'] }}</strong></span>
                <span class="text-slate-300">•</span>
                <span>Masa Berlaku: <strong class="text-slate-800 font-semibold tabular-nums">{{ $contract['start_date_formatted'] }} s/d {{ $contract['end_date_formatted'] }}</strong></span>
                <span class="text-slate-300">•</span>
                <span>Sisa Durasi: <strong class="{{ ($contract['days_remaining'] ?? 99) <= 14 ? 'text-rose-600' : 'text-slate-800' }} font-semibold">{{ $contract['days_remaining'] !== null ? ($contract['days_remaining'] . ' Hari') : 'Berkelanjutan' }}</strong></span>
            </p>
        </div>

        {{-- Auto-Order Toggle & Quick Actions --}}
        <div class="flex items-center gap-3 self-start md:self-auto shrink-0 flex-wrap">
            @if($contract['has_schedule'])
                <form method="POST" action="{{ route('supplier.contract-monitoring.toggle-auto-order', $contract['id']) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit"
                            title="Klik untuk mengubah status pesanan otomatis"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border text-xs font-semibold transition shadow-2xs cursor-pointer {{ $contract['is_auto_order_active'] ? 'bg-sky-50 text-sky-800 border-sky-200 hover:bg-sky-100' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100' }}">
                        <span class="w-2 h-2 rounded-full {{ $contract['is_auto_order_active'] ? 'bg-sky-500' : 'bg-slate-400' }}"></span>
                        <span>Auto-Order: {{ $contract['is_auto_order_active'] ? 'Aktif' : 'Nonaktif' }}</span>
                    </button>
                </form>
            @endif

            <a href="#kelola-kontrak" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition shadow-2xs">
                <span>Kelola Status</span>
            </a>
        </div>
    </div>

    {{-- Main Grid: 2 Columns Layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left Column: 2 Spans --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- 1. Realisasi Volume & Logistik Pasokan --}}
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 space-y-4 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#ea580c]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        <span>Realisasi Volume &amp; Komitmen Kontrak</span>
                    </h3>
                    <span class="text-xs font-bold text-[#ea580c] tabular-nums">
                        {{ number_format($contract['volume_fulfillment_rate'], 1) }}% Tercapai
                    </span>
                </div>

                <div class="space-y-3">
                    <div class="flex justify-between items-baseline text-xs tabular-nums">
                        <span class="text-slate-500">Volume Terkirim:</span>
                        <span class="text-base font-bold text-slate-900">
                            {{ number_format($contract['delivered_volume'], 0, ',', '.') }} / {{ number_format($contract['target_volume'], 0, ',', '.') }} {{ $contract['unit'] }}
                        </span>
                    </div>

                    <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                        <div class="h-2.5 rounded-full transition-all duration-500 {{ $contract['volume_fulfillment_rate'] >= 80 ? 'bg-emerald-500' : 'bg-[#ea580c]' }}"
                             style="width: {{ min(100, $contract['volume_fulfillment_rate']) }}%"></div>
                    </div>

                    <div class="grid grid-cols-3 gap-3 pt-2 text-center text-xs tabular-nums">
                        <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                            <span class="text-[10px] text-slate-400 uppercase font-semibold block">Total Order</span>
                            <span class="text-base font-bold text-slate-900 mt-0.5 block">{{ $contract['total_orders'] }}</span>
                        </div>
                        <div class="bg-emerald-50/70 p-3 rounded-xl border border-emerald-100">
                            <span class="text-[10px] text-emerald-700 uppercase font-semibold block">Selesai</span>
                            <span class="text-base font-bold text-emerald-800 mt-0.5 block">{{ $contract['completed_orders'] }}</span>
                        </div>
                        <div class="bg-amber-50/70 p-3 rounded-xl border border-amber-100">
                            <span class="text-[10px] text-amber-700 uppercase font-semibold block">Isu / Kendala</span>
                            <span class="text-base font-bold text-amber-800 mt-0.5 block">{{ $contract['issue_orders'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. Detail Spesifikasi Pasokan & Jadwal Dapur --}}
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 space-y-4 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 pb-3 border-b border-slate-100 flex items-center gap-2">
                    <svg class="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span>Ketentuan Jadwal &amp; Pengiriman Dapur</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div class="bg-slate-50/80 p-3.5 rounded-xl border border-slate-200/70">
                        <span class="text-[10px] text-slate-400 uppercase font-semibold block">Komoditas &amp; Harga</span>
                        <strong class="text-slate-900 text-sm mt-0.5 block">{{ $contract['commodity_name'] }}</strong>
                        <span class="text-slate-600 font-medium tabular-nums mt-0.5 block">{{ $contract['unit_price_formatted'] }} / {{ $contract['unit'] }}</span>
                    </div>

                    <div class="bg-slate-50/80 p-3.5 rounded-xl border border-slate-200/70">
                        <span class="text-[10px] text-slate-400 uppercase font-semibold block">Volume Rutin Tiap Kirim</span>
                        <strong class="text-slate-900 text-sm mt-0.5 block tabular-nums">{{ number_format($contract['volume_per_delivery'], 0, ',', '.') }} {{ $contract['unit'] }}</strong>
                        <span class="text-slate-500 mt-0.5 block">Sesuai alokasi kapasitas armada</span>
                    </div>

                    <div class="bg-slate-50/80 p-3.5 rounded-xl border border-slate-200/70">
                        <span class="text-[10px] text-slate-400 uppercase font-semibold block">Pola Frekuensi Kirim</span>
                        <strong class="text-slate-900 mt-0.5 block">{{ $contract['frequency_label'] }}</strong>
                        <span class="text-slate-600 text-[11px] mt-0.5 block">Hari: {{ $contract['delivery_days_label'] }}</span>
                    </div>

                    <div class="bg-slate-50/80 p-3.5 rounded-xl border border-slate-200/70">
                        <span class="text-[10px] text-slate-400 uppercase font-semibold block">Target Jam Tiba di Dapur</span>
                        <strong class="text-slate-900 text-sm mt-0.5 block tabular-nums">{{ $contract['delivery_time_label'] }}</strong>
                        <span class="text-slate-500 mt-0.5 block">Maksimal toleransi keterlambatan 30 mnt</span>
                    </div>
                </div>

                {{-- Ketentuan Mutu & Catatan Khusus --}}
                <div class="pt-3 border-t border-slate-100 space-y-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">
                        Instruksi Khusus &amp; Spesifikasi Mutu Dapur
                    </span>
                    <p class="p-3.5 rounded-xl bg-orange-50/50 border border-orange-100 text-slate-700 text-xs leading-relaxed">
                        {{ $contract['submission_details'] ?: ($contract['special_instructions'] ?: 'Standar kualitas kesegaran bahan pangan terikat kontrak kemitraan resmi.') }}
                    </p>
                </div>
            </div>

            {{-- 3. Riwayat Evaluasi Periodik Kontrak --}}
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 space-y-4 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900">
                            Riwayat Evaluasi Periodik
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Catatan performa realisasi pasokan berkala antara pemasok dan dapur UMKM.</p>
                    </div>
                </div>

                @if(!empty($contract['evaluations']) && count($contract['evaluations']) > 0)
                    <div class="space-y-3">
                        @foreach($contract['evaluations'] as $eval)
                            <div class="p-4 rounded-xl border border-slate-200/80 bg-slate-50/60 space-y-2 text-xs">
                                <div class="flex justify-between items-center">
                                    <span class="font-bold text-slate-900">{{ $eval['evaluation_period'] }}</span>
                                    <span class="font-bold px-2 py-0.5 rounded text-[10px] {{ $eval['fulfillment_rate_percentage'] >= 80 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }} tabular-nums">
                                        {{ number_format($eval['fulfillment_rate_percentage'], 1) }}% Pemenuhan
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-600 flex justify-between tabular-nums">
                                    <span>Target: {{ $eval['target_formatted'] }}</span>
                                    <span>Realisasi: {{ $eval['realized_formatted'] }}</span>
                                </div>
                                @if(!empty($eval['discrepancy_notes']))
                                    <p class="text-[11px] text-slate-700 italic bg-white p-2.5 rounded-lg border border-slate-200/70">
                                        "{{ $eval['discrepancy_notes'] }}"
                                    </p>
                                @endif
                                <div class="text-[10px] text-slate-400">
                                    Dicatat pada: {{ $eval['created_at_formatted'] }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-8 text-center text-slate-400 text-xs">
                        Belum ada catatan evaluasi periodik untuk kontrak kemitraan ini.
                    </div>
                @endif

                {{-- Form Catat Evaluasi Baru --}}
                <div class="pt-4 border-t border-slate-100">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800 mb-3">
                        + Tambah Catatan Evaluasi Periodik
                    </h4>
                    <form method="POST" action="{{ route('supplier.contract-monitoring.evaluations.store', $contract['id']) }}" class="space-y-3">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Periode Evaluasi <span class="text-rose-500">*</span></label>
                                <input type="text" name="evaluation_period" required placeholder="Contoh: November 2026" class="w-full text-xs font-medium bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c]">
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Target Volume ({{ $contract['unit'] }}) <span class="text-rose-500">*</span></label>
                                <input type="number" step="0.01" min="0.01" name="target_volume" required value="{{ (float) $contract['target_volume'] }}" class="w-full text-xs font-semibold tabular-nums bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c]">
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Realisasi ({{ $contract['unit'] }}) <span class="text-rose-500">*</span></label>
                                <input type="number" step="0.01" min="0" name="realized_volume" required value="{{ (float) $contract['delivered_volume'] }}" class="w-full text-xs font-semibold tabular-nums bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c]">
                            </div>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Catatan Evaluasi / Discrepancy</label>
                            <textarea name="discrepancy_notes" rows="2" placeholder="Catatan kesesuaian komoditas, evaluasi ketepatan jadwal, atau kendala logistik..." class="w-full text-xs font-medium bg-slate-50 border border-slate-200 rounded-xl p-3 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c]"></textarea>
                        </div>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition shadow-2xs cursor-pointer">
                            Simpan Evaluasi
                        </button>
                    </form>
                </div>
            </div>

        </div>

        {{-- Right Column: 1 Span --}}
        <div class="space-y-6">

            {{-- Profil Mitra UMKM --}}
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 sm:p-6 space-y-4 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 pb-3 border-b border-slate-100 flex items-center gap-2">
                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <span>Informasi Mitra UMKM</span>
                </h3>

                <div class="space-y-3 text-xs">
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase font-semibold block">Nama Bisnis / Dapur</span>
                        <strong class="text-slate-900 text-sm mt-0.5 block">{{ $contract['umkm_name'] }}</strong>
                    </div>

                    <div>
                        <span class="text-[10px] text-slate-400 uppercase font-semibold block">Penanggung Jawab (PIC)</span>
                        <strong class="text-slate-800 mt-0.5 block">{{ $contract['umkm_owner'] }}</strong>
                    </div>

                    <div>
                        <span class="text-[10px] text-slate-400 uppercase font-semibold block">Kontak Telepon / WhatsApp</span>
                        <span class="text-slate-700 font-mono mt-0.5 block">{{ $contract['umkm_phone'] }}</span>
                    </div>

                    <div>
                        <span class="text-[10px] text-slate-400 uppercase font-semibold block">Alamat Dapur / Drop Point</span>
                        <p class="text-slate-700 mt-0.5 leading-relaxed">{{ $contract['umkm_address'] }}</p>
                    </div>

                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-[10px] text-slate-400 uppercase font-semibold">Tingkat Keandalan Mitra</span>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $contract['reliability_badge_color'] }} tabular-nums">
                            Grade {{ $contract['reliability_grade'] }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Skor Kepatuhan & SLA --}}
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 sm:p-6 space-y-4 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 pb-3 border-b border-slate-100 flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Kepatuhan SLA &amp; Rating</span>
                </h3>

                <div class="grid grid-cols-2 gap-3 text-xs tabular-nums text-center">
                    <div class="bg-amber-50/70 p-3 rounded-xl border border-amber-200/70">
                        <span class="text-[10px] text-amber-800 uppercase font-semibold block">Rating Kepuasan</span>
                        <span class="text-xl font-bold text-amber-600 mt-1 block">
                            {{ $contract['avg_rating'] !== null ? '★ '.number_format($contract['avg_rating'], 1) : '★ 5.0' }}
                        </span>
                    </div>

                    <div class="bg-emerald-50/70 p-3 rounded-xl border border-emerald-200/70">
                        <span class="text-[10px] text-emerald-800 uppercase font-semibold block">On-Time Delivery</span>
                        <span class="text-xl font-bold text-emerald-700 mt-1 block">
                            {{ $contract['otd_rate'] !== null ? number_format($contract['otd_rate'], 1).'%' : '100%' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Kelola Status & Perpanjang Kontrak --}}
            <div id="kelola-kontrak" class="bg-white rounded-2xl border border-slate-200/80 p-5 sm:p-6 space-y-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 pb-3 border-b border-slate-100 flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span>Kelola Kontrak Kemitraan</span>
                </h3>

                {{-- Form Perpanjangan Kontrak --}}
                @if($contract['lifecycle_status'] !== 'terminated')
                    <div class="space-y-2 text-xs">
                        <span class="text-[10px] font-bold text-slate-500 uppercase block">Perpanjang Masa Berlaku Kontrak</span>
                        <form method="POST" action="{{ route('supplier.contract-monitoring.extend', $contract['id']) }}" class="space-y-2.5">
                            @csrf
                            @method('PATCH')
                            <input type="date"
                                   name="end_date"
                                   required
                                   min="{{ now()->addDay()->toDateString() }}"
                                   class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c]">
                            <button type="submit" class="w-full bg-[#ea580c] hover:bg-[#c2410c] text-white font-semibold text-xs py-2.5 rounded-xl transition shadow-2xs cursor-pointer">
                                Perpanjang Kontrak
                            </button>
                        </form>
                    </div>
                @endif

                {{-- Form Ubah Status Kontrak (Jeda / Aktifkan / Hentikan) --}}
                <div class="pt-3 border-t border-slate-100 space-y-2 text-xs">
                    <span class="text-[10px] font-bold text-slate-500 uppercase block">Ubah Status Siklus Kemitraan</span>
                    <form method="POST" action="{{ route('supplier.contract-monitoring.status.update', $contract['id']) }}" class="space-y-2.5">
                        @csrf
                        @method('PATCH')
                        <select name="action" class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c] cursor-pointer">
                            @if($contract['lifecycle_status'] === 'active')
                                <option value="pause">Jeda Pengiriman Sementara</option>
                                <option value="terminate">Hentikan Kontrak Permanen</option>
                            @elseif($contract['lifecycle_status'] === 'paused')
                                <option value="activate">Aktifkan Kembali Kontrak</option>
                                <option value="terminate">Hentikan Kontrak Permanen</option>
                            @else
                                <option value="activate">Aktifkan Kembali</option>
                            @endif
                        </select>
                        <button type="submit" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-800 font-semibold text-xs py-2.5 rounded-xl transition cursor-pointer">
                            Terapkan Status
                        </button>
                    </form>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
