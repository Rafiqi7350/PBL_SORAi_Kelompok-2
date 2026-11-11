@extends('layouts.supplier')

@section('title', 'Pengajuan Masuk & Persetujuan — Portal Pemasok SORAI')

@section('content')
<div class="space-y-8">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 border-b border-slate-200/80 pb-5">
        <div class="space-y-1">
            <span class="text-[11px] font-bold uppercase text-[#ea580c] tracking-[0.14em] block">
                PERMINTAAN KEMITRAAN B2B
            </span>
            <h1 class="text-2xl sm:text-[26px] font-bold text-slate-900 tracking-tight uppercase">
                PENGAJUAN MASUK &amp; PERSETUJUAN
            </h1>
            <p class="text-xs sm:text-[13px] text-slate-500 max-w-2xl leading-relaxed">
                Tinjau profil keandalan mitra UMKM, atur fasilitas tempo pembayaran (TOP) serta plafon kredit, dan kelola kemitraan yang sedang berjalan.
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
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-5">
                @foreach($pendingPartnerships as $partnership)
                    @php
                        $schedule = $partnership->activeDeliverySchedule;
                        $umkm = $partnership->umkm;
                        $umkmName = $umkm?->business_name ?? 'UMKM Tidak Dikenal';
                        $commodity = $schedule?->supplierProduct?->commodity;
                        $commodityName = $commodity?->name ?? '-';

                        $scoreObj = $umkm?->latestReliabilityScore;
                        $grade = $scoreObj?->grade ?? 'UNRATED';
                        $finalScore = $scoreObj ? number_format($scoreObj->final_score, 0) : '0';
                        $badgeColor = $scoreObj?->getGradeBadgeColor() ?? 'bg-slate-100 text-slate-700 border-slate-200';
                        $gradeLabel = $scoreObj?->getGradeLabel() ?? 'Belum Terperingkat';
                        $recommendation = $scoreObj?->recommendations ?? [];
                        $maxTop = (int) ($recommendation['max_top_days'] ?? 0);
                        $policyNote = $recommendation['policy_note'] ?? 'Evaluasi pembayaran tunai untuk pesanan awal.';

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
                    @endphp

                    <article class="bg-white rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)] hover:border-slate-300/90 hover:shadow-sm p-6 flex flex-col justify-between gap-5 transition-all">

                        <div class="space-y-4">
                            {{-- Top Badges --}}
                            <div class="flex items-center justify-between gap-2">
                                <span class="inline-flex items-center gap-1.5 bg-amber-50 text-amber-800 border border-amber-200/80 text-[11px] font-semibold px-2.5 py-1 rounded-full tabular-nums">
                                    <svg class="w-3 h-3 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>{{ $partnership->created_at->format('d/m/Y') }}</span>
                                </span>

                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold uppercase border tracking-wide tabular-nums {{ $badgeColor }}">
                                    <span>GRADE {{ $grade }}</span>
                                    <span class="font-medium opacity-80">({{ $finalScore }}/100)</span>
                                </span>
                            </div>

                            {{-- UMKM Identity --}}
                            <div class="space-y-0.5">
                                <h3 class="text-base font-bold text-slate-900 uppercase tracking-tight leading-snug">
                                    {{ strtoupper($umkmName) }}
                                </h3>
                                <p class="text-xs font-semibold text-[#ea580c]">
                                    Komoditas: {{ $commodityName }}
                                </p>
                            </div>

                            {{-- Skor Keandalan & Rekomendasi TOP --}}
                            <div class="bg-slate-50/90 border border-slate-200/70 rounded-xl p-3.5 space-y-3">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-[#ea580c] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                        </svg>
                                        <span class="text-[11px] font-bold text-slate-800">Skor Keandalan Mitra</span>
                                    </div>
                                    <span class="text-[10px] font-semibold text-slate-500">{{ $gradeLabel }}</span>
                                </div>

                                {{-- 3 Pillars --}}
                                <div class="grid grid-cols-3 gap-2">
                                    <div class="bg-white p-2.5 rounded-lg border border-slate-200/70">
                                        <p class="text-[10px] font-medium text-slate-500">Pembayaran</p>
                                        <p class="text-xs font-bold text-slate-900 mt-0.5 tabular-nums">
                                            {{ number_format($scoreObj?->payment_discipline_score ?? 70, 0) }}<span class="text-[10px] font-medium text-slate-400">/100</span>
                                        </p>
                                    </div>
                                    <div class="bg-white p-2.5 rounded-lg border border-slate-200/70">
                                        <p class="text-[10px] font-medium text-slate-500">Konsistensi</p>
                                        <p class="text-xs font-bold text-slate-900 mt-0.5 tabular-nums">
                                            {{ number_format($scoreObj?->order_consistency_score ?? 70, 0) }}<span class="text-[10px] font-medium text-slate-400">/100</span>
                                        </p>
                                    </div>
                                    <div class="bg-white p-2.5 rounded-lg border border-slate-200/70">
                                        <p class="text-[10px] font-medium text-slate-500">Kemitraan</p>
                                        <p class="text-xs font-bold text-slate-900 mt-0.5 tabular-nums">
                                            {{ number_format($scoreObj?->tenure_history_score ?? 70, 0) }}<span class="text-[10px] font-medium text-slate-400">/100</span>
                                        </p>
                                    </div>
                                </div>

                                {{-- Rekomendasi TOP --}}
                                <div class="bg-white border border-orange-200/80 rounded-lg p-2.5 space-y-1">
                                    <div class="flex items-center gap-1.5 text-[11px] font-bold text-[#c2410c]">
                                        <svg class="w-3.5 h-3.5 text-[#ea580c] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span>Rekomendasi:</span>
                                        <span>{{ $maxTop > 0 ? "Tempo {$maxTop} Hari" : 'Pembayaran Tunai / COD' }}</span>
                                    </div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        {{ $policyNote }}
                                    </p>
                                </div>
                            </div>

                            {{-- Detail Kebutuhan --}}
                            <div class="bg-slate-50/70 border border-slate-200/70 rounded-xl p-3.5 space-y-2.5">
                                <div class="grid grid-cols-3 gap-2">
                                    <div class="space-y-0.5">
                                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Volume</p>
                                        <p class="text-xs font-bold text-slate-800 tabular-nums">
                                            {{ $schedule?->volume_per_delivery ? number_format($schedule->volume_per_delivery, 0).' '.($schedule->unit ?? 'kg') : '-' }}
                                        </p>
                                    </div>
                                    <div class="space-y-0.5">
                                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Frekuensi</p>
                                        <p class="text-xs font-bold text-slate-800 leading-snug">
                                            {{ $frequencyLabel }}
                                        </p>
                                    </div>
                                    <div class="space-y-0.5">
                                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Durasi</p>
                                        <p class="text-xs font-bold text-slate-800 tabular-nums">
                                            {{ $durasi }}
                                        </p>
                                    </div>
                                </div>

                                @if($partnership->submission_details)
                                    <div class="pt-2 border-t border-slate-200/70">
                                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-0.5">Catatan UMKM</p>
                                        <p class="text-[11px] text-slate-600 font-medium leading-relaxed line-clamp-2">
                                            {{ $partnership->submission_details }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Form Persetujuan & Penolakan --}}
                        <div class="space-y-2.5 pt-3 border-t border-slate-100">
                            <form method="POST" action="{{ route('supplier.pengajuan-masuk.terima', $partnership->id) }}" class="space-y-3">
                                @csrf
                                @method('PATCH')

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <div>
                                        <label for="top-{{ $partnership->id }}" class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block mb-1">
                                            Fasilitas Pembayaran (TOP)
                                        </label>
                                        <select id="top-{{ $partnership->id }}"
                                                name="term_of_payment_days"
                                                class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c] focus:bg-white transition cursor-pointer">
                                            <option value="0" {{ $maxTop === 0 ? 'selected' : '' }}>Tunai / COD (0 Hari)</option>
                                            <option value="7" {{ $maxTop === 7 ? 'selected' : '' }}>Tempo 7 Hari Kalender</option>
                                            <option value="14" {{ $maxTop === 14 ? 'selected' : '' }}>Tempo 14 Hari Kalender</option>
                                            <option value="21" {{ $maxTop === 21 ? 'selected' : '' }}>Tempo 21 Hari Kalender</option>
                                            <option value="30" {{ $maxTop === 30 ? 'selected' : '' }}>Tempo 30 Hari Kalender</option>
                                            <option value="45" {{ $maxTop === 45 ? 'selected' : '' }}>Tempo 45 Hari Kalender</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="limit-{{ $partnership->id }}" class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block mb-1">
                                            Plafon Kredit (Rp)
                                        </label>
                                        <input type="number"
                                               id="limit-{{ $partnership->id }}"
                                               name="credit_limit"
                                               value="{{ (int) ($recommendation['recommended_credit_limit'] ?? 10000000) }}"
                                               step="500000"
                                               min="0"
                                               class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 tabular-nums focus:outline-none focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c] focus:bg-white transition" />
                                    </div>
                                </div>

                                <button type="submit"
                                        id="terima-{{ $partnership->id }}"
                                        class="w-full inline-flex items-center justify-center gap-1.5 bg-[#ea580c] hover:bg-[#c2410c] text-white font-bold text-xs py-2.5 px-4 rounded-xl shadow-2xs transition cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                    </svg>
                                    <span>Terima Kemitraan</span>
                                </button>
                            </form>

                            <form method="POST"
                                  action="{{ route('supplier.pengajuan-masuk.tolak', $partnership->id) }}"
                                  onsubmit="return confirm('Yakin ingin menolak pengajuan kemitraan dari {{ addslashes($umkmName) }}?');">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        id="tolak-{{ $partnership->id }}"
                                        class="w-full inline-flex items-center justify-center gap-1.5 text-slate-500 hover:text-rose-700 hover:bg-rose-50 border border-transparent hover:border-rose-200 rounded-xl text-xs font-semibold py-2 transition cursor-pointer">
                                    <span>Tolak Pengajuan</span>
                                </button>
                            </form>
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

    {{-- Section 2: Kemitraan Aktif & Manajemen Tempo --}}
    <section class="space-y-4 pt-6 border-t border-slate-200/80">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div class="flex items-center gap-2.5">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800">
                    KEMITRAAN AKTIF &amp; FASILITAS TEMPO
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
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-5">
                @foreach($activePartnerships as $active)
                    @php
                        $actSchedule = $active->activeDeliverySchedule;
                        $actUmkm = $active->umkm;
                        $actCommodity = $actSchedule?->supplierProduct?->commodity;
                        $actScore = $actUmkm?->latestReliabilityScore;
                        $actGrade = $actScore?->grade ?? 'UNRATED';
                        $actScoreVal = $actScore ? number_format($actScore->final_score, 0) : '0';
                        $actBadgeColor = $actScore?->getGradeBadgeColor() ?? 'bg-slate-100 text-slate-700 border-slate-200';
                    @endphp

                    <article class="bg-white rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)] hover:border-slate-300/90 p-6 flex flex-col justify-between gap-4 transition-all">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between gap-2">
                                <span class="inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200/80 text-[11px] font-semibold uppercase tracking-wide px-2.5 py-1 rounded-full">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    <span>KEMITRAAN AKTIF</span>
                                </span>

                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold uppercase border tracking-wide tabular-nums {{ $actBadgeColor }}">
                                    <span>GRADE {{ $actGrade }}</span>
                                    <span class="font-medium opacity-80">({{ $actScoreVal }}/100)</span>
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

                            {{-- Fasilitas Pembayaran Aktif --}}
                            <div class="bg-emerald-50/50 border border-emerald-200/70 rounded-xl p-3.5 space-y-2.5">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-800 block">
                                    Fasilitas Pembayaran Berlaku
                                </span>

                                <div class="grid grid-cols-2 gap-2.5">
                                    <div class="bg-white p-2.5 rounded-lg border border-emerald-100">
                                        <p class="text-[10px] font-medium text-slate-500">Jangka Waktu</p>
                                        <p class="text-xs font-bold text-slate-900 mt-0.5 tabular-nums">
                                            {{ $active->term_of_payment_days > 0 ? "Tempo {$active->term_of_payment_days} Hari" : 'Tunai / COD' }}
                                        </p>
                                    </div>
                                    <div class="bg-white p-2.5 rounded-lg border border-emerald-100">
                                        <p class="text-[10px] font-medium text-slate-500">Plafon Kredit</p>
                                        <p class="text-xs font-bold text-slate-900 mt-0.5 tabular-nums">
                                            Rp {{ number_format($active->credit_limit, 0, ',', '.') }}
                                        </p>
                                    </div>
                                </div>
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

                        {{-- Update Credit Terms --}}
                        <div class="pt-2">
                            <form method="POST" action="{{ route('supplier.partnerships.update-credit-terms', $active->id) }}">
                                @csrf
                                @method('PATCH')
                                <details class="group bg-slate-50/90 rounded-xl border border-slate-200/80 p-3">
                                    <summary class="text-xs font-semibold text-slate-700 cursor-pointer flex items-center justify-between select-none list-none">
                                        <span class="inline-flex items-center gap-2">
                                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            <span>Sesuaikan Fasilitas Pembayaran</span>
                                        </span>
                                        <svg class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </summary>
                                    <div class="space-y-3 pt-3 mt-3 border-t border-slate-200/80">
                                        <div>
                                            <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block mb-1">Hari Tempo (TOP)</label>
                                            <select name="term_of_payment_days" class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-xl px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c]">
                                                <option value="0" {{ $active->term_of_payment_days === 0 ? 'selected' : '' }}>Tunai / COD (0 Hari)</option>
                                                <option value="7" {{ $active->term_of_payment_days === 7 ? 'selected' : '' }}>Tempo 7 Hari</option>
                                                <option value="14" {{ $active->term_of_payment_days === 14 ? 'selected' : '' }}>Tempo 14 Hari</option>
                                                <option value="21" {{ $active->term_of_payment_days === 21 ? 'selected' : '' }}>Tempo 21 Hari</option>
                                                <option value="30" {{ $active->term_of_payment_days === 30 ? 'selected' : '' }}>Tempo 30 Hari</option>
                                                <option value="45" {{ $active->term_of_payment_days === 45 ? 'selected' : '' }}>Tempo 45 Hari</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block mb-1">Plafon Kredit (Rp)</label>
                                            <input type="number"
                                                   name="credit_limit"
                                                   value="{{ (int) $active->credit_limit }}"
                                                   step="500000"
                                                   min="0"
                                                   class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-xl px-3 py-2 text-slate-800 tabular-nums focus:outline-none focus:ring-2 focus:ring-[#ea580c]/20 focus:border-[#ea580c]" />
                                        </div>
                                        <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs py-2.5 rounded-xl transition cursor-pointer">
                                            Simpan Perubahan
                                        </button>
                                    </div>
                                </details>
                            </form>
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

</div>
@endsection
