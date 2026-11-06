@extends('layouts.supplier')

@section('content')
<div class="space-y-8">

    {{-- Header Section --}}
    <div class="space-y-1">
        <span class="text-xs font-mono font-bold uppercase text-[#ea580c] tracking-widest block">
            PERMINTAAN KEMITRAAN B2B
        </span>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight uppercase">
            PENGAJUAN MASUK &amp; PERSETUJUAN
        </h1>
        <div class="w-16 h-1 bg-[#ea580c] rounded-full mt-2"></div>
    </div>

    {{-- Section: Menunggu Persetujuan --}}
    <div class="space-y-5">
        <div class="flex items-center gap-3">
            <h2 class="text-sm font-black uppercase tracking-widest text-gray-800">
                MENUNGGU PERSETUJUAN
            </h2>
            <span class="bg-[#ea580c] text-white text-[11px] font-black px-2.5 py-0.5 rounded-full">
                {{ $totalPending }}
            </span>
        </div>

        @if($pendingPartnerships->isEmpty())
            {{-- Empty State --}}
            <div class="bg-white rounded-[2rem] border border-gray-100 shadow-[0_10px_35px_rgba(0,0,0,0.04)] py-20 px-6 text-center space-y-4">
                <div class="w-16 h-16 rounded-full bg-orange-50 text-[#ea580c] flex items-center justify-center mx-auto text-3xl">
                    📋
                </div>
                <div class="space-y-1">
                    <h3 class="text-base font-bold text-gray-900">Tidak Ada Pengajuan Masuk</h3>
                    <p class="text-xs text-gray-500 max-w-sm mx-auto">
                        Belum ada UMKM yang mengajukan kemitraan kepada Anda. Pengajuan baru akan muncul di sini.
                    </p>
                </div>
            </div>
        @else
            {{-- Cards Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                @foreach($pendingPartnerships as $partnership)
                    @php
                        $schedule = $partnership->activeDeliverySchedule;
                        $umkm = $partnership->umkm;
                        $umkmName = $umkm?->business_name ?? 'UMKM Tidak Dikenal';
                        $commodity = $schedule?->supplierProduct?->commodity;
                        $commodityName = $commodity?->name ?? '-';

                        // Skor Keandalan Mitra UMKM
                        $scoreObj = $umkm?->latestReliabilityScore;
                        $grade = $scoreObj?->grade ?? 'UNRATED';
                        $finalScore = $scoreObj ? number_format($scoreObj->final_score, 0) : '0';
                        $badgeColor = $scoreObj?->getGradeBadgeColor() ?? 'bg-gray-100 text-gray-700 border-gray-200';
                        $gradeLabel = $scoreObj?->getGradeLabel() ?? 'Belum Terperingkat';
                        $recommendation = $scoreObj?->recommendations ?? [];
                        $maxTop = (int) ($recommendation['max_top_days'] ?? 0);
                        $policyNote = $recommendation['policy_note'] ?? 'Evaluasi pembayaran tunai untuk pesanan awal.';

                        // Format delivery days
                        $dayMap = [
                            'Senin' => 'Senin', 'Selasa' => 'Selasa', 'Rabu' => 'Rabu',
                            'Kamis' => 'Kamis', 'Jumat' => 'Jumat', 'Sabtu' => 'Sabtu', 'Minggu' => 'Minggu',
                        ];
                        $deliveryDays = $schedule?->delivery_days ?? [];
                        $deliveryDaysStr = is_array($deliveryDays) ? implode(', ', $deliveryDays) : $deliveryDays;

                        // Format durasi
                        $startDate = $schedule?->start_date;
                        $endDate = $schedule?->end_date;
                        $durasi = '-';
                        if ($startDate && $endDate) {
                            $diffMonths = $startDate->diffInMonths($endDate);
                            $durasi = $diffMonths > 0 ? $diffMonths . ' Bulan' : $startDate->diffInDays($endDate) . ' Hari';
                        } elseif ($schedule) {
                            $durasi = 'Uji Coba';
                        }

                        // Format frekuensi
                        $freqType = $schedule?->frequency_type ?? '-';
                        $frequencyLabel = match($freqType) {
                            'daily'   => 'Setiap Hari',
                            'weekly'  => '1x Seminggu',
                            'twice_weekly' => '2x Seminggu',
                            'thrice_weekly' => '3x Seminggu',
                            'monthly' => '1x Sebulan',
                            default   => $freqType,
                        };
                    @endphp

                    <div class="bg-white rounded-[2rem] border border-gray-100 shadow-[0_8px_30px_rgba(0,0,0,0.05)] p-6 space-y-4 hover:shadow-[0_12px_40px_rgba(234,88,12,0.1)] hover:-translate-y-0.5 transition-all duration-200 flex flex-col justify-between">

                        <div class="space-y-4">
                            {{-- Header Badge & Grade --}}
                            <div class="flex items-center justify-between gap-2">
                                <span class="inline-flex items-center gap-1.5 bg-[#fef9c3] text-[#a16207] border border-[#fde047]/60 text-[10px] font-black uppercase tracking-wider px-3 py-1 rounded-full">
                                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ $partnership->created_at->format('d/m/Y') }}
                                </span>

                                {{-- Grade Badge --}}
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[11px] font-black uppercase border tracking-wider {{ $badgeColor }}">
                                    <span>GRADE {{ $grade }}</span>
                                    <span class="text-[9px] font-normal opacity-80">({{ $finalScore }}/100)</span>
                                </span>
                            </div>

                            {{-- UMKM Name --}}
                            <div class="space-y-0.5">
                                <h3 class="text-base font-extrabold text-gray-900 uppercase tracking-tight leading-snug">
                                    {{ strtoupper($umkmName) }}
                                </h3>
                                <p class="text-xs font-semibold text-[#ea580c]">
                                    Komoditas: {{ $commodityName }}
                                </p>
                            </div>

                            {{-- Skor Keandalan & Rekomendasi TOP Box --}}
                            <div class="bg-gradient-to-br from-gray-50 to-orange-50/30 border border-gray-200/80 rounded-2xl p-3.5 space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs">🛡️</span>
                                        <span class="text-[11px] font-bold text-gray-800">Skor Keandalan Mitra</span>
                                    </div>
                                    <span class="text-[10px] font-semibold text-gray-500">{{ $gradeLabel }}</span>
                                </div>

                                {{-- 3 Pillars Progress Bar --}}
                                <div class="grid grid-cols-3 gap-2 text-[10px]">
                                    <div class="bg-white p-2 rounded-xl border border-gray-100 shadow-2xs">
                                        <p class="text-gray-400 font-semibold text-[9px]">Pembayaran</p>
                                        <p class="font-extrabold text-gray-800">{{ number_format($scoreObj?->payment_discipline_score ?? 70, 0) }}<span class="text-[8px] text-gray-400">/100</span></p>
                                    </div>
                                    <div class="bg-white p-2 rounded-xl border border-gray-100 shadow-2xs">
                                        <p class="text-gray-400 font-semibold text-[9px]">Konsistensi</p>
                                        <p class="font-extrabold text-gray-800">{{ number_format($scoreObj?->order_consistency_score ?? 70, 0) }}<span class="text-[8px] text-gray-400">/100</span></p>
                                    </div>
                                    <div class="bg-white p-2 rounded-xl border border-gray-100 shadow-2xs">
                                        <p class="text-gray-400 font-semibold text-[9px]">Kemitraan</p>
                                        <p class="font-extrabold text-gray-800">{{ number_format($scoreObj?->tenure_history_score ?? 70, 0) }}<span class="text-[8px] text-gray-400">/100</span></p>
                                    </div>
                                </div>

                                {{-- Rekomendasi Otomatis Fasilitas TOP --}}
                                <div class="bg-white/90 border border-orange-200/80 rounded-xl p-2.5 space-y-1">
                                    <div class="flex items-center gap-1.5 text-[10px] font-bold text-[#c2410c]">
                                        <svg class="w-3.5 h-3.5 text-[#ea580c]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>Rekomendasi Sistem:</span>
                                        <span class="font-extrabold underline">{{ $maxTop > 0 ? "Tempo {$maxTop} Hari" : "Pembayaran Tunai / COD" }}</span>
                                    </div>
                                    <p class="text-[10px] text-gray-600 leading-tight">
                                        {{ $policyNote }}
                                    </p>
                                </div>
                            </div>

                            {{-- Detail Pengajuan Komoditas --}}
                            <div class="bg-[#fffbf0] border border-[#fde68a]/60 rounded-2xl p-3.5 space-y-2">
                                <div class="grid grid-cols-3 gap-2">
                                    <div class="space-y-0.5">
                                        <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Volume:</p>
                                        <p class="text-xs font-black text-gray-800">
                                            {{ $schedule?->volume_per_delivery ? number_format($schedule->volume_per_delivery, 0) . ' ' . ($schedule->unit ?? 'kg') : '-' }}
                                        </p>
                                    </div>
                                    <div class="space-y-0.5">
                                        <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Frekuensi:</p>
                                        <p class="text-xs font-black text-gray-800 leading-snug">
                                            {{ $frequencyLabel }}
                                        </p>
                                    </div>
                                    <div class="space-y-0.5">
                                        <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Durasi:</p>
                                        <p class="text-xs font-black text-gray-800">
                                            {{ $durasi }}
                                        </p>
                                    </div>
                                </div>

                                @if($partnership->submission_details)
                                    <div class="pt-2 border-t border-[#fde68a]/60">
                                        <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400 mb-0.5">Catatan UMKM:</p>
                                        <p class="text-[11px] text-gray-600 font-medium leading-relaxed line-clamp-2">
                                            {{ $partnership->submission_details }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Action Buttons & TOP Selection --}}
                        <div class="space-y-3 pt-2">
                            {{-- Form Terima dengan Pilihan Fasilitas Pembayaran --}}
                            <form method="POST" action="{{ route('supplier.pengajuan-masuk.terima', $partnership->id) }}" class="space-y-2">
                                @csrf
                                @method('PATCH')

                                <div class="flex items-center gap-2">
                                    <div class="flex-1">
                                        <label for="top-{{ $partnership->id }}" class="text-[9px] font-bold uppercase tracking-wider text-gray-400 block mb-1">
                                            Fasilitas Pembayaran (TOP):
                                        </label>
                                        <select id="top-{{ $partnership->id }}" name="term_of_payment_days" class="w-full text-xs font-semibold bg-gray-50 border border-gray-200 rounded-xl px-2.5 py-1.5 text-gray-800 focus:outline-none focus:border-[#ea580c] focus:bg-white">
                                            <option value="0" {{ $maxTop === 0 ? 'selected' : '' }}>Tunai / COD (0 Hari)</option>
                                            <option value="7" {{ $maxTop === 7 ? 'selected' : '' }}>Tempo 7 Hari Kalender</option>
                                            <option value="14" {{ $maxTop === 14 ? 'selected' : '' }}>Tempo 14 Hari Kalender</option>
                                            <option value="21" {{ $maxTop === 21 ? 'selected' : '' }}>Tempo 21 Hari Kalender</option>
                                            <option value="30" {{ $maxTop === 30 ? 'selected' : '' }}>Tempo 30 Hari Kalender</option>
                                            <option value="45" {{ $maxTop === 45 ? 'selected' : '' }}>Tempo 45 Hari Kalender</option>
                                        </select>
                                    </div>

                                    <div class="flex-1">
                                        <label for="limit-{{ $partnership->id }}" class="text-[9px] font-bold uppercase tracking-wider text-gray-400 block mb-1">
                                            Plafon Kredit (Rp):
                                        </label>
                                        <input type="number" id="limit-{{ $partnership->id }}" name="credit_limit" 
                                               value="{{ (int) ($recommendation['recommended_credit_limit'] ?? 10000000) }}"
                                               step="500000" min="0"
                                               class="w-full text-xs font-semibold bg-gray-50 border border-gray-200 rounded-xl px-2.5 py-1.5 text-gray-800 focus:outline-none focus:border-[#ea580c] focus:bg-white" />
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 pt-1">
                                    <button type="submit" id="terima-{{ $partnership->id }}"
                                            class="w-full inline-flex items-center justify-center gap-1.5 bg-[#ea580c] hover:bg-[#c2410c] active:scale-[0.97] text-white font-bold text-xs py-2.5 px-3 rounded-full shadow-sm hover:shadow-md transition-all duration-200 cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                        </svg>
                                        <span>Terima Kemitraan</span>
                                    </button>
                                </div>
                            </form>

                            {{-- Tolak Button --}}
                            <form method="POST" action="{{ route('supplier.pengajuan-masuk.tolak', $partnership->id) }}"
                                  onsubmit="return confirm('Yakin ingin menolak pengajuan kemitraan dari {{ addslashes($umkmName) }}?');">
                                @csrf
                                @method('PATCH')
                                <button type="submit" id="tolak-{{ $partnership->id }}"
                                        class="w-full inline-flex items-center justify-center gap-1 text-gray-400 hover:text-rose-600 text-[11px] font-semibold py-1 transition-colors duration-150 cursor-pointer">
                                    <span>Tolak Pengajuan</span>
                                </button>
                            </form>
                        </div>

                    </div>
                @endforeach
            </div>

            @if($pendingPartnerships->hasPages())
                <div class="pt-4">
                    {{ $pendingPartnerships->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- Section: Kemitraan Aktif & Manajemen Tempo --}}
    <div class="space-y-5 pt-6 border-t border-gray-100">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h2 class="text-sm font-black uppercase tracking-widest text-gray-800">
                    KEMITRAAN AKTIF &amp; FASILITAS TEMPO
                </h2>
                <span class="bg-emerald-600 text-white text-[11px] font-black px-2.5 py-0.5 rounded-full">
                    {{ $totalActive ?? 0 }}
                </span>
            </div>
            <p class="text-xs text-gray-400 font-medium hidden sm:block">
                Mitra resmi yang telah disetujui dan memiliki kuota pasokan berjalan
            </p>
        </div>

        @if(!isset($activePartnerships) || $activePartnerships->isEmpty())
            <div class="bg-white rounded-2xl border border-dashed border-gray-200 py-12 px-6 text-center space-y-2">
                <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto text-lg font-bold">
                    ✓
                </div>
                <h3 class="text-sm font-bold text-gray-800">Belum Ada Kemitraan Aktif</h3>
                <p class="text-xs text-gray-400 max-w-sm mx-auto">
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
                        $actScore = $actUmkm?->latestReliabilityScore;
                        $actGrade = $actScore?->grade ?? 'UNRATED';
                        $actScoreVal = $actScore ? number_format($actScore->final_score, 0) : '0';
                        $actBadgeColor = $actScore?->getGradeBadgeColor() ?? 'bg-gray-100 text-gray-700 border-gray-200';
                    @endphp

                    <div class="bg-white rounded-[2rem] border border-gray-100 shadow-[0_8px_30px_rgba(0,0,0,0.04)] p-6 space-y-4 hover:shadow-md transition-all duration-200 flex flex-col justify-between">
                        <div class="space-y-4">
                            {{-- Header Badge & Grade --}}
                            <div class="flex items-center justify-between gap-2">
                                <span class="inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-black uppercase tracking-wider px-3 py-1 rounded-full">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    KEMITRAAN AKTIF
                                </span>

                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase border tracking-wider {{ $actBadgeColor }}">
                                    <span>GRADE {{ $actGrade }}</span>
                                    <span class="text-[9px] font-normal opacity-80">({{ $actScoreVal }}/100)</span>
                                </span>
                            </div>

                            {{-- Mitra Name --}}
                            <div class="space-y-0.5">
                                <h3 class="text-base font-extrabold text-gray-900 uppercase tracking-tight">
                                    {{ strtoupper($actUmkm?->business_name ?? 'UMKM Mitra') }}
                                </h3>
                                <p class="text-xs font-semibold text-[#ea580c]">
                                    Komoditas: {{ $actCommodity?->name ?? 'Pasokan Rutin' }}
                                </p>
                            </div>

                            {{-- Status Fasilitas Pembayaran Tempo Aktif --}}
                            <div class="bg-emerald-50/50 border border-emerald-200/80 rounded-2xl p-3.5 space-y-2">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-800 block">
                                    Fasilitas Pembayaran yang Berlaku:
                                </span>

                                <div class="grid grid-cols-2 gap-2 text-left">
                                    <div class="bg-white p-2 rounded-xl border border-emerald-100 shadow-2xs">
                                        <p class="text-[9px] font-semibold text-gray-400">Jangka Waktu:</p>
                                        <p class="text-xs font-black text-gray-900">
                                            {{ $active->term_of_payment_days > 0 ? "Tempo {$active->term_of_payment_days} Hari" : 'Tunai / COD' }}
                                        </p>
                                    </div>
                                    <div class="bg-white p-2 rounded-xl border border-emerald-100 shadow-2xs">
                                        <p class="text-[9px] font-semibold text-gray-400">Plafon Kredit:</p>
                                        <p class="text-xs font-black text-gray-900">
                                            Rp {{ number_format($active->credit_limit, 0, ',', '.') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- Jadwal Pasokan --}}
                            <div class="bg-gray-50 border border-gray-100 rounded-2xl p-3 text-[11px] space-y-1">
                                <div class="flex justify-between text-gray-500">
                                    <span>Volume Rutin:</span>
                                    <span class="font-bold text-gray-800">{{ $actSchedule?->volume_per_delivery ? number_format($actSchedule->volume_per_delivery, 0) . ' ' . ($actSchedule->unit ?? 'kg') : '-' }}</span>
                                </div>
                                <div class="flex justify-between text-gray-500">
                                    <span>Jadwal Pengiriman:</span>
                                    <span class="font-bold text-gray-800">{{ $actSchedule?->frequency_type ?? '-' }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Update Credit Terms Button / Form --}}
                        <div class="pt-2">
                            <form method="POST" action="{{ route('supplier.partnerships.update-credit-terms', $active->id) }}" class="space-y-2">
                                @csrf
                                @method('PATCH')
                                <details class="group bg-gray-50 rounded-xl border border-gray-200 p-2.5">
                                    <summary class="text-[11px] font-bold text-gray-700 cursor-pointer flex items-center justify-between select-none">
                                        <span>⚙️ Sesuaikan Fasilitas Pembayaran</span>
                                        <span class="text-xs text-gray-400 group-open:rotate-180 transition-transform">▼</span>
                                    </summary>
                                    <div class="space-y-2 pt-2.5 mt-2 border-t border-gray-200">
                                        <div>
                                            <label class="text-[9px] font-bold uppercase text-gray-400 block mb-1">Hari Tempo (TOP):</label>
                                            <select name="term_of_payment_days" class="w-full text-xs font-semibold bg-white border border-gray-200 rounded-lg px-2 py-1.5">
                                                <option value="0" {{ $active->term_of_payment_days === 0 ? 'selected' : '' }}>Tunai / COD (0 Hari)</option>
                                                <option value="7" {{ $active->term_of_payment_days === 7 ? 'selected' : '' }}>Tempo 7 Hari</option>
                                                <option value="14" {{ $active->term_of_payment_days === 14 ? 'selected' : '' }}>Tempo 14 Hari</option>
                                                <option value="21" {{ $active->term_of_payment_days === 21 ? 'selected' : '' }}>Tempo 21 Hari</option>
                                                <option value="30" {{ $active->term_of_payment_days === 30 ? 'selected' : '' }}>Tempo 30 Hari</option>
                                                <option value="45" {{ $active->term_of_payment_days === 45 ? 'selected' : '' }}>Tempo 45 Hari</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="text-[9px] font-bold uppercase text-gray-400 block mb-1">Plafon Kredit (Rp):</label>
                                            <input type="number" name="credit_limit" value="{{ (int) $active->credit_limit }}" step="500000" min="0" class="w-full text-xs font-semibold bg-white border border-gray-200 rounded-lg px-2 py-1.5" />
                                        </div>
                                        <button type="submit" class="w-full text-center bg-gray-900 hover:bg-black text-white font-bold text-[10px] py-2 rounded-lg transition-colors cursor-pointer">
                                            Simpan Perubahan
                                        </button>
                                    </div>
                                </details>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            @if(isset($activePartnerships) && $activePartnerships->hasPages())
                <div class="pt-4">
                    {{ $activePartnerships->links() }}
                </div>
            @endif
        @endif
    </div>

</div>
@endsection
