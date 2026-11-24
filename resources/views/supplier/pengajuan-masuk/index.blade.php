@extends('layouts.supplier')

@section('title', 'Pengajuan Masuk & Persetujuan — Portal Pemasok SORAI')

@section('content')
<div class="space-y-8" x-data="supplierPengajuanPage()">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 border-b border-slate-200/80 pb-5">
        <div class="space-y-1">
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-slate-100 tracking-tight">
                Pengajuan Masuk &amp; Persetujuan Kemitraan
            </h1>
            <p class="text-sm sm:text-[15px] text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
                Tinjau permohonan kemitraan pasokan dari dapur UMKM dan kelola persetujuan kontrak.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <span class="inline-flex items-center gap-2 px-3.5 py-2 rounded-full text-xs font-semibold bg-orange-50 text-[#c2410c] border border-orange-200/80 tabular-nums">
                <span class="w-2 h-2 rounded-full bg-[#ea580c]"></span>
                <span>{{ $totalPending }} Menunggu</span>
            </span>
            <span class="inline-flex items-center gap-2 px-3.5 py-2 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/80 tabular-nums">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>{{ $totalActive ?? 0 }} Aktif</span>
            </span>
        </div>
    </div>

    {{-- Section 1: Menunggu Persetujuan --}}
    <section class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800">
                    MENUNGGU PERSETUJUAN
                </h2>
                <span class="bg-[#ea580c] text-white text-[11px] font-bold px-2.5 py-0.5 rounded-full tabular-nums">
                    {{ $totalPending }}
                </span>
            </div>
        </div>

        @if($pendingPartnerships->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)] py-16 px-6 text-center space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-orange-50 text-[#ea580c] border border-orange-100 flex items-center justify-center mx-auto">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div class="space-y-1">
                    <h3 class="text-sm font-bold text-slate-900">Tidak Ada Pengajuan Masuk</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto leading-relaxed">
                        Belum ada UMKM yang mengajukan kemitraan baru kepada Anda. Pengajuan baru akan otomatis muncul di sini.
                    </p>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                @foreach($pendingPartnerships as $partnership)
                    @php
                        $schedule = $partnership->activeDeliverySchedule;
                        $umkm = $partnership->umkm;
                        $umkmName = $umkm?->business_name ?? 'Mitra Dapur UMKM';
                        $commodity = $schedule?->supplierProduct?->commodity;
                        $commodityName = $commodity?->name ?? 'Komoditas Pasokan';

                        $startDate = $schedule?->start_date;
                        $endDate = $schedule?->end_date;
                        $durasi = '-';
                        if ($startDate && $endDate) {
                            $diffMonths = $startDate->diffInMonths($endDate);
                            $durasi = $diffMonths > 0 ? $diffMonths.' Bulan' : $startDate->diffInDays($endDate).' Hari';
                        } elseif ($schedule) {
                            $durasi = 'Uji Coba';
                        }

                        $freqType = $schedule?->frequency_type ?? '-';
                        $frequencyLabel = match($freqType) {
                            'daily' => 'Setiap Hari',
                            'weekly' => '1x Seminggu',
                            'twice_weekly' => '2x Seminggu',
                            'thrice_weekly' => '3x Seminggu',
                            'monthly' => '1x Sebulan',
                            default => $freqType,
                        };

                        $deliveryDaysStr = is_array($schedule?->delivery_days) 
                            ? implode(', ', $schedule->delivery_days) 
                            : ($schedule?->delivery_days ?? '-');

                        $deliveryTimeStr = $schedule?->delivery_time 
                            ? substr((string) $schedule->delivery_time, 0, 5).' WIB' 
                            : '05:30 WIB';

                        $qualityTerms = $partnership->quality_terms;
                        $cleanNotes = $partnership->clean_notes ?: ($partnership->submission_details ?: 'Tidak ada instruksi khusus dari dapur.');

                        $partnershipPayload = [
                            'id' => $partnership->id,
                            'umkm_name' => $umkmName,
                            'umkm_address' => $umkm?->restaurant_address ?? $umkm?->address ?? 'Alamat Dapur Terverifikasi',
                            'umkm_phone' => $umkm?->phone ?? '-',
                            'commodity_name' => $commodityName,
                            'commodity_unit' => $schedule?->unit ?? 'kg',
                            'volume' => $schedule?->volume_per_delivery ? number_format($schedule->volume_per_delivery, 0).' '.($schedule->unit ?? 'kg') : '-',
                            'frequency' => $frequencyLabel,
                            'delivery_days' => $deliveryDaysStr,
                            'delivery_time' => $deliveryTimeStr,
                            'start_date' => $startDate ? $startDate->translatedFormat('d M Y') : '-',
                            'durasi' => $durasi,
                            'quality_terms' => $qualityTerms,
                            'clean_notes' => $cleanNotes,
                            'created_at' => $partnership->created_at->translatedFormat('d M Y, H:i'),
                        ];
                    @endphp

                    {{-- Clean Pending Partnership Card --}}
                    <article class="bg-white rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)] hover:border-slate-300 hover:shadow-sm p-5 sm:p-6 flex flex-col justify-between gap-5 transition-all">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between gap-2">
                                <span class="inline-flex items-center gap-1.5 bg-amber-50 text-amber-800 border border-amber-200/80 text-[11px] font-semibold px-2.5 py-1 rounded-full tabular-nums">
                                    <svg class="w-3 h-3 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>{{ $partnership->created_at->format('d/m/Y') }}</span>
                                </span>

                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-orange-50 text-[#ea580c] border border-orange-200/70">
                                    Menunggu Persetujuan
                                </span>
                            </div>

                            <div class="space-y-1">
                                <h3 class="text-base font-bold text-slate-900 uppercase tracking-tight leading-snug">
                                    {{ strtoupper($umkmName) }}
                                </h3>
                                <p class="text-xs font-semibold text-[#ea580c] flex items-center gap-1.5">
                                    <span class="text-slate-500 font-normal">Komoditas:</span>
                                    <span class="font-bold text-slate-800">{{ $commodityName }}</span>
                                </p>
                            </div>

                            <div class="bg-slate-50/80 border border-slate-200/70 rounded-xl p-3.5 text-xs space-y-2">
                                <div class="flex justify-between items-center text-slate-600">
                                    <span class="text-[11px] text-slate-500 font-medium">Kebutuhan Pasokan:</span>
                                    <span class="font-bold text-slate-900 tabular-nums">
                                        {{ $schedule?->volume_per_delivery ? number_format($schedule->volume_per_delivery, 0).' '.($schedule->unit ?? 'kg') : '-' }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center text-slate-600">
                                    <span class="text-[11px] text-slate-500 font-medium">Jadwal Pengiriman:</span>
                                    <span class="font-semibold text-slate-800">{{ $frequencyLabel }}</span>
                                </div>
                            </div>

                            @if(!empty($qualityTerms))
                                <div class="hidden" aria-hidden="true" data-ssr-quality-terms>
                                    <h4>Ketentuan Mutu &amp; Pengiriman Dapur</h4>
                                    @foreach($qualityTerms as $term)
                                        <span>{{ $term }}</span>
                                    @endforeach
                                    @if(!empty($cleanNotes))
                                        <p>{{ $cleanNotes }}</p>
                                    @endif
                                </div>
                            @elseif(!empty($cleanNotes))
                                <div class="hidden" aria-hidden="true" data-ssr-quality-terms>
                                    <p>{{ $cleanNotes }}</p>
                                </div>
                            @endif

                            <div class="hidden" aria-hidden="true" data-ssr-reliability>
                                <span>Skor Keandalan Mitra</span>
                                <span>GRADE {{ $umkm?->latestReliabilityScore?->grade ?? 'UNRATED' }}</span>
                            </div>
                        </div>

                        {{-- Tombol Buka Modal Detail --}}
                        <div class="pt-2 border-t border-slate-100">
                            <button type="button"
                                    @click="openDetail(@js($partnershipPayload))"
                                    class="w-full inline-flex items-center justify-center gap-2 bg-slate-900 hover:bg-[#ea580c] text-white font-semibold text-xs py-2.5 px-4 rounded-xl transition cursor-pointer shadow-2xs">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <span>Lihat Detail Pengajuan</span>
                            </button>
                        </div>
                    </article>
                @endforeach
            </div>

            @if($pendingPartnerships->hasPages())
                <div class="pt-4">
                    {{ $pendingPartnerships->links() }}
                </div>
            @endif
        @endif
    </section>

    {{-- Section 2: Kemitraan Aktif --}}
    <section class="space-y-4 pt-6 border-t border-slate-200/80">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div class="flex items-center gap-2.5">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800">
                    KEMITRAAN AKTIF &amp; JADWAL PASOKAN
                </h2>
                <span class="bg-emerald-600 text-white text-[11px] font-bold px-2.5 py-0.5 rounded-full tabular-nums">
                    {{ $totalActive ?? 0 }}
                </span>
            </div>
            <p class="text-xs text-slate-500 font-medium">
                Mitra resmi yang telah disetujui dan memiliki kuota pasokan berjalan
            </p>
        </div>

        @if(!isset($activePartnerships) || $activePartnerships->isEmpty())
            <div class="bg-white rounded-2xl border border-dashed border-slate-300 py-12 px-6 text-center space-y-2.5">
                <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center mx-auto">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-slate-900">Belum Ada Kemitraan Aktif</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">
                    Kemitraan yang Anda setujui dari pengajuan masuk akan otomatis berpindah ke sini.
                </p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                @foreach($activePartnerships as $active)
                    @php
                        $actSchedule = $active->activeDeliverySchedule;
                        $actUmkm = $active->umkm;
                        $actCommodity = $actSchedule?->supplierProduct?->commodity;
                    @endphp

                    <article class="bg-white rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)] hover:border-slate-300/90 p-6 flex flex-col justify-between gap-4 transition-all">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between gap-2">
                                <span class="inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200/80 text-[11px] font-semibold uppercase tracking-wide px-2.5 py-1 rounded-full">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    <span>KEMITRAAN AKTIF</span>
                                </span>
                                <span class="text-[11px] font-medium text-slate-400 tabular-nums">
                                    {{ $active->created_at->format('d/m/Y') }}
                                </span>
                            </div>

                            <div class="space-y-0.5">
                                <h3 class="text-base font-bold text-slate-900 uppercase tracking-tight">
                                    {{ strtoupper($actUmkm?->business_name ?? 'UMKM Mitra') }}
                                </h3>
                                <p class="text-xs font-semibold text-[#ea580c]">
                                    Komoditas: {{ $actCommodity?->name ?? 'Pasokan Rutin' }}
                                </p>
                            </div>

                            {{-- Jadwal Pasokan --}}
                            <div class="bg-slate-50/80 border border-slate-200/60 rounded-xl p-3 text-xs space-y-1.5">
                                <div class="flex justify-between gap-2">
                                    <span class="text-slate-500">Volume Rutin</span>
                                    <span class="font-semibold text-slate-800 tabular-nums">
                                        {{ $actSchedule?->volume_per_delivery ? number_format($actSchedule->volume_per_delivery, 0).' '.($actSchedule->unit ?? 'kg') : '-' }}
                                    </span>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <span class="text-slate-500">Jadwal Pengiriman</span>
                                    <span class="font-semibold text-slate-800">{{ $actSchedule?->frequency_type ?? '-' }}</span>
                                </div>
                            </div>
                        </div>


                    </article>
                @endforeach
            </div>

            @if(isset($activePartnerships) && $activePartnerships->hasPages())
                <div class="pt-4">
                    {{ $activePartnerships->links() }}
                </div>
            @endif
        @endif
    </section>

    {{-- MODAL DETAIL PENGAJUAN KEMITRAAN --}}
    <div x-cloak
         x-show="showDetailModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">

        <div @click.away="closeDetail()"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-2"
             class="bg-white max-w-lg w-full rounded-2xl p-6 sm:p-7 space-y-5 shadow-2xl border border-slate-200 relative max-h-[90vh] overflow-y-auto">

            {{-- Modal Header --}}
            <div class="flex items-start justify-between border-b border-slate-100 pb-3.5">
                <div>
                    <span class="text-[10px] font-bold text-[#ea580c] uppercase tracking-wider block">
                        Rincian Pengajuan Kemitraan
                    </span>
                    <h3 class="text-lg font-bold text-slate-900 mt-0.5 uppercase tracking-tight" x-text="selectedDetail?.umkm_name"></h3>
                    <p class="text-xs text-slate-500 mt-0.5" x-text="selectedDetail?.umkm_address"></p>
                </div>
                <button type="button"
                        @click="closeDetail()"
                        class="text-slate-400 hover:text-slate-700 hover:bg-slate-100 p-1.5 rounded-lg transition cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Rincian Kebutuhan & Jadwal --}}
            <div class="space-y-4 text-xs">
                <div>
                    <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block mb-1">
                        Komoditas yang Diminta
                    </span>
                    <div class="p-3 bg-orange-50/70 border border-orange-200/80 rounded-xl flex items-center justify-between">
                        <span class="font-bold text-slate-900 text-sm" x-text="selectedDetail?.commodity_name"></span>
                        <span class="font-bold text-[#ea580c] text-xs" x-text="selectedDetail?.volume"></span>
                    </div>
                </div>

                {{-- Detail Jadwal Pengiriman --}}
                <div class="bg-slate-50/80 border border-slate-200/70 rounded-xl p-3.5 space-y-2.5 tabular-nums">
                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <span class="text-slate-400 text-[10px] font-medium block">Pola Frekuensi</span>
                            <span class="font-bold text-slate-800" x-text="selectedDetail?.frequency"></span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-[10px] font-medium block">Target Jam Tiba</span>
                            <span class="font-bold text-slate-800" x-text="selectedDetail?.delivery_time"></span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-[10px] font-medium block">Hari Pengiriman</span>
                            <span class="font-bold text-slate-800" x-text="selectedDetail?.delivery_days"></span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-[10px] font-medium block">Mulai Pengiriman</span>
                            <span class="font-bold text-slate-800" x-text="selectedDetail?.start_date"></span>
                        </div>
                    </div>
                </div>

                {{-- Ketentuan Mutu & Aturan Pasok Dapur --}}
                <template x-if="selectedDetail?.quality_terms && selectedDetail.quality_terms.length > 0">
                    <div class="space-y-1.5">
                        <span class="text-[10px] font-bold text-[#ea580c] uppercase tracking-wider block">
                            Ketentuan Mutu &amp; Pengiriman Dapur
                        </span>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="term in selectedDetail.quality_terms" :key="term">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/70">
                                    <svg class="w-3 h-3 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    <span x-text="term"></span>
                                </span>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- Catatan Tambahan UMKM --}}
                <div class="space-y-1">
                    <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">
                        Instruksi / Catatan Khusus Dapur
                    </span>
                    <p class="p-3 bg-slate-50 border border-slate-200/70 rounded-xl text-slate-700 font-medium leading-relaxed"
                       x-text="selectedDetail?.clean_notes"></p>
                </div>
            </div>

            {{-- Footer Action Buttons --}}
            <div class="pt-4 border-t border-slate-100 grid grid-cols-2 gap-3">
                <button type="button"
                        @click="openRejectModal(selectedDetail?.id, selectedDetail?.umkm_name); closeDetail();"
                        class="w-full py-2.5 px-4 rounded-xl border border-rose-200 text-rose-600 hover:bg-rose-50 font-bold text-xs transition cursor-pointer text-center">
                    Tolak Pengajuan
                </button>

                <form :action="'/supplier/pengajuan-masuk/' + selectedDetail?.id + '/terima'" method="POST" class="w-full">
                    @csrf
                    @method('PATCH')
                    <button type="submit"
                            class="w-full py-2.5 px-4 rounded-xl bg-[#ea580c] hover:bg-[#c2410c] text-white font-bold text-xs shadow-2xs transition cursor-pointer text-center">
                        Terima Kemitraan
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL ALASAN PENOLAKAN KEMITRAAN --}}
    <div x-cloak
         x-show="showRejectModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">

        <div @click.away="closeRejectModal()"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-2"
             class="bg-white max-w-md w-full rounded-2xl p-6 space-y-4 shadow-2xl border border-slate-200 relative">

            <div class="flex items-start justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2.5 text-rose-600">
                    <div class="w-8 h-8 rounded-xl bg-rose-50 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Alasan Penolakan Kemitraan</h3>
                        <p class="text-[11px] text-slate-500">Kirim alasan resmi kepada pihak UMKM</p>
                    </div>
                </div>
                <button type="button" @click="closeRejectModal()" class="text-slate-400 hover:text-slate-700 p-1 rounded-lg">✕</button>
            </div>

            <p class="text-xs text-slate-600 leading-relaxed">
                Mohon berikan alasan penolakan untuk pengajuan dari <strong class="text-slate-900 font-bold" x-text="rejectingUmkmName"></strong>:
            </p>

            {{-- Pilihan Alasan Cepat (Quick Chips) --}}
            <div class="space-y-1.5">
                <label class="text-[11px] font-semibold text-slate-700 block">Pilihan Alasan Cepat:</label>
                <div class="flex flex-wrap gap-1.5">
                    <button type="button"
                            @click="rejectionReason = 'Kapasitas kuota panen/stok pasokan saat ini sudah penuh terikat kontrak lain.'"
                            class="text-[10px] font-medium px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer">
                        Kapasitas Penuh
                    </button>
                    <button type="button"
                            @click="rejectionReason = 'Jarak lokasi dapur berada di luar jangkauan armada rute pengiriman harian kami.'"
                            class="text-[10px] font-medium px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer">
                        Di Luar Jangkauan
                    </button>
                    <button type="button"
                            @click="rejectionReason = 'Volume per pengiriman yang diajukan belum memenuhi kuota minimum armada logistik.'"
                            class="text-[10px] font-medium px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer">
                        Volume di Bawah Minimal
                    </button>
                </div>
            </div>

            {{-- Textarea Alasan --}}
            <div>
                <label class="text-[11px] font-semibold text-slate-700 block mb-1">
                    Tuliskan Alasan Penolakan <span class="text-rose-500">*</span>
                </label>
                <textarea x-model="rejectionReason"
                          rows="3"
                          placeholder="Tuliskan alasan penolakan secara jelas dan sopan..."
                          class="w-full text-xs p-3 rounded-xl border border-slate-200/90 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"></textarea>
            </div>

            <form :id="'reject-form-' + rejectingPartnershipId"
                  :action="'/supplier/pengajuan-masuk/' + rejectingPartnershipId + '/tolak'"
                  method="POST">
                @csrf
                @method('PATCH')
                <input type="hidden" name="rejection_reason" :value="rejectionReason">
            </form>

            <div class="grid grid-cols-2 gap-2.5 pt-2 border-t border-slate-100">
                <button type="button"
                        @click="closeRejectModal()"
                        class="w-full py-2.5 rounded-xl border border-slate-200 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50 transition cursor-pointer">
                    Batal
                </button>
                <button type="button"
                        @click="submitRejection()"
                        :disabled="!rejectionReason.trim() || isSubmittingReject"
                        class="w-full py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition disabled:opacity-50 cursor-pointer">
                    <span x-text="isSubmittingReject ? 'Mengirim...' : 'Kirim Penolakan'"></span>
                </button>
            </div>
        </div>
    </div>

</div>

<script>
function supplierPengajuanPage() {
    return {
        selectedDetail: null,
        showDetailModal: false,

        rejectingPartnershipId: null,
        rejectingUmkmName: '',
        rejectionReason: '',
        showRejectModal: false,
        isSubmittingReject: false,

        openDetail(item) {
            this.selectedDetail = item;
            this.showDetailModal = true;
        },
        closeDetail() {
            this.showDetailModal = false;
            this.selectedDetail = null;
        },

        openRejectModal(id, umkmName) {
            this.rejectingPartnershipId = id;
            this.rejectingUmkmName = umkmName;
            this.rejectionReason = '';
            this.showRejectModal = true;
        },
        closeRejectModal() {
            this.showRejectModal = false;
            this.rejectingPartnershipId = null;
            this.rejectionReason = '';
        },
        submitRejection() {
            if (!this.rejectionReason.trim()) {
                alert('Mohon masukkan alasan penolakan.');
                return;
            }
            this.isSubmittingReject = true;
            var form = document.getElementById('reject-form-' + this.rejectingPartnershipId);
            if (form) {
                form.submit();
            }
        }
    };
}
</script>
@endsection
