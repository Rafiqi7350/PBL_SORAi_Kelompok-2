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
                        $umkmName = $partnership->umkm?->business_name ?? 'UMKM Tidak Dikenal';
                        $commodity = $schedule?->supplierProduct?->commodity;
                        $commodityName = $commodity?->name ?? '-';

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

                    <div class="bg-white rounded-[2rem] border border-gray-100 shadow-[0_8px_30px_rgba(0,0,0,0.05)] p-6 space-y-5 hover:shadow-[0_12px_40px_rgba(234,88,12,0.1)] hover:-translate-y-0.5 transition-all duration-200">

                        {{-- Badge Tanggal --}}
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5 bg-[#fef9c3] text-[#a16207] border border-[#fde047]/60 text-[10px] font-black uppercase tracking-wider px-3 py-1 rounded-full">
                                <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                </svg>
                                PENGAJUAN BARU &bull; {{ $partnership->created_at->format('d/m/Y') }}
                            </span>
                        </div>

                        {{-- UMKM Name --}}
                        <div class="space-y-1">
                            <h3 class="text-lg font-extrabold text-gray-900 uppercase tracking-tight leading-snug">
                                {{ strtoupper($umkmName) }}
                            </h3>
                            <p class="text-xs font-semibold text-[#ea580c]">
                                Komoditas: {{ $commodityName }}
                            </p>
                        </div>

                        {{-- Detail Pengajuan --}}
                        <div class="bg-[#fffbf0] border border-[#fde68a]/60 rounded-2xl p-4 space-y-3">
                            <div class="grid grid-cols-3 gap-3">
                                {{-- Volume --}}
                                <div class="space-y-1">
                                    <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Volume:</p>
                                    <p class="text-xs font-black text-gray-800">
                                        {{ $schedule?->volume_per_delivery ? number_format($schedule->volume_per_delivery, 0) . ' ' . ($schedule->unit ?? 'kg') : '-' }}
                                    </p>
                                </div>

                                {{-- Frekuensi --}}
                                <div class="space-y-1">
                                    <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Frekuensi:</p>
                                    <p class="text-xs font-black text-gray-800 leading-snug">
                                        {{ $frequencyLabel }}
                                        @if($deliveryDaysStr)
                                            <br><span class="font-normal text-gray-500 text-[10px]">({{ $deliveryDaysStr }})</span>
                                        @endif
                                    </p>
                                </div>

                                {{-- Durasi --}}
                                <div class="space-y-1">
                                    <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Durasi:</p>
                                    <p class="text-xs font-black text-gray-800">
                                        {{ $durasi }}
                                    </p>
                                </div>
                            </div>

                            @if($partnership->submission_details)
                                <div class="pt-2 border-t border-[#fde68a]/60">
                                    <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400 mb-1">Catatan UMKM:</p>
                                    <p class="text-[11px] text-gray-600 font-medium leading-relaxed line-clamp-2">
                                        {{ $partnership->submission_details }}
                                    </p>
                                </div>
                            @endif
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex items-center gap-3 pt-1">
                            {{-- Tolak --}}
                            <form method="POST" action="{{ route('supplier.pengajuan-masuk.tolak', $partnership->id) }}"
                                  onsubmit="return confirm('Yakin ingin menolak pengajuan kemitraan dari {{ addslashes($umkmName) }}?');"
                                  class="flex-1">
                                @csrf
                                @method('PATCH')
                                <button type="submit" id="tolak-{{ $partnership->id }}"
                                        class="w-full inline-flex items-center justify-center gap-2 bg-white hover:bg-rose-50 border-2 border-rose-300 hover:border-rose-400 text-rose-500 hover:text-rose-600 font-bold text-xs py-3 px-4 rounded-full transition-all duration-200 cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    <span>Tolak Kemitraan</span>
                                </button>
                            </form>

                            {{-- Terima --}}
                            <form method="POST" action="{{ route('supplier.pengajuan-masuk.terima', $partnership->id) }}"
                                  class="flex-1">
                                @csrf
                                @method('PATCH')
                                <button type="submit" id="terima-{{ $partnership->id }}"
                                        class="w-full inline-flex items-center justify-center gap-2 bg-[#ea580c] hover:bg-[#c2410c] active:scale-[0.97] text-white font-bold text-xs py-3 px-4 rounded-full shadow-sm hover:shadow-md transition-all duration-200 cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                    </svg>
                                    <span>Terima Kemitraan →</span>
                                </button>
                            </form>
                        </div>

                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>
@endsection
