@extends('layouts.supplier')

@section('content')
<div class="max-w-6xl w-full mx-auto space-y-8 pb-12"
     x-data="{ showRejectModal: false, rejectAction: '', rejectName: '' }">

    <!-- Header -->
    <div class="border-b-2 border-gray-300 pb-5">
        <p class="text-[#F4511E] text-xs font-bold font-mono-code tracking-widest uppercase mb-1">PERMINTAAN KEMITRAAN B2B</p>
        <h1 class="text-3xl md:text-4xl font-black text-[#1E222D] tracking-tight uppercase">PENGAJUAN MASUK &amp; PERSETUJUAN</h1>
    </div>

    <!-- Menunggu Persetujuan -->
    <section class="space-y-5">
        <div class="flex items-center gap-3">
            <span class="w-7 h-7 rounded-full bg-[#FFB703]/60 flex-shrink-0"></span>
            <h2 class="text-xl font-extrabold text-[#1E222D] uppercase">Menunggu Persetujuan ({{ $pendingPartnerships->count() }})</h2>
        </div>

        @if($pendingPartnerships->isEmpty())
            <div class="bg-white rounded-3xl border-2 border-black p-12 text-center shadow-[4px_4px_0px_0px_rgba(30,34,45,1)]">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl">📭</div>
                <h3 class="text-lg font-extrabold text-[#1E222D]">Belum ada pengajuan masuk</h3>
                <p class="text-sm font-medium text-gray-500 mt-1">Pengajuan kemitraan baru dari UMKM akan muncul di sini.</p>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach($pendingPartnerships as $partnership)
                    <div class="bg-white rounded-[2rem] shadow-lg p-6 md:p-8 flex flex-col">
                        <span class="self-start bg-[#FFE08A] border border-black text-[11px] font-bold font-mono-code uppercase rounded-full px-3 py-1">
                            Pengajuan Baru · {{ $partnership->created_at->format('d/m/Y') }}
                        </span>

                        <h3 class="mt-4 text-2xl font-black text-[#1E222D] uppercase leading-tight">
                            {{ $partnership->umkm?->business_name ?? 'UMKM tidak diketahui' }}
                        </h3>
                        <p class="text-sm font-bold text-[#F4511E] mt-1">
                            Komoditas: {{ $partnership->commodity?->name ?? '-' }}
                        </p>

                        <hr class="my-5 border-t-2 border-gray-200">

                        <div class="bg-[#FFE9A8] border-2 border-black rounded-2xl p-5 grid grid-cols-3 gap-4 text-sm">
                            <div>
                                <p class="text-xs font-mono-code text-gray-600">Volume:</p>
                                <p class="font-mono-code font-bold text-[#1E222D]">{{ $partnership->volume_label }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-mono-code text-gray-600">Frekuensi:</p>
                                <p class="font-mono-code font-bold text-[#1E222D]">{{ $partnership->frequency_label }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-mono-code text-gray-600">Durasi:</p>
                                <p class="font-mono-code font-bold text-[#1E222D]">{{ $partnership->duration ?? '-' }}</p>
                            </div>
                        </div>

                        @if($partnership->submission_details)
                            <div class="mt-4">
                                <p class="text-[11px] font-extrabold text-gray-500 uppercase">Catatan Spesifikasi Mutu Dapur</p>
                                <p class="text-sm font-medium text-gray-700 mt-1">{{ $partnership->submission_details }}</p>
                            </div>
                        @endif

                        <div class="mt-6 grid grid-cols-2 gap-3">
                            <button type="button"
                                    @click="rejectAction = '{{ route('supplier.pengajuan.reject', $partnership->id) }}'; rejectName = @js($partnership->umkm?->business_name ?? 'UMKM'); showRejectModal = true"
                                    class="flex items-center justify-center gap-2 bg-rose-100 hover:bg-rose-200 text-rose-900 border-2 border-black font-extrabold text-sm py-3 px-4 rounded-full transition">
                                ✕ Tolak Kemitraan
                            </button>

                            <form method="POST" action="{{ route('supplier.pengajuan.accept', $partnership->id) }}"
                                  onsubmit="return confirm('Terima kemitraan dengan {{ e($partnership->umkm?->business_name) }}?');">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="w-full flex items-center justify-center gap-2 bg-[#FFB703] hover:bg-[#FFB703]/90 text-black border-2 border-black font-extrabold text-sm py-3 px-4 rounded-full transition">
                                    ✓ Terima Kemitraan →
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <!-- Riwayat Keputusan -->
    @if($historyPartnerships->isNotEmpty())
        <section class="space-y-4">
            <h2 class="text-xl font-extrabold text-[#1E222D] uppercase">Riwayat Keputusan</h2>
            <div class="bg-white rounded-3xl border-2 border-black shadow-[4px_4px_0px_0px_rgba(30,34,45,1)] overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#FFFDF5] border-b-2 border-black text-xs font-bold font-mono-code text-gray-700 uppercase tracking-wider">
                            <th class="py-4 px-6">UMKM</th>
                            <th class="py-4 px-6">KOMODITAS</th>
                            <th class="py-4 px-6">STATUS</th>
                            <th class="py-4 px-6">DIPROSES</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm">
                        @foreach($historyPartnerships as $item)
                            <tr>
                                <td class="py-4 px-6 font-bold text-[#1E222D]">{{ $item->umkm?->business_name ?? '-' }}</td>
                                <td class="py-4 px-6 font-medium text-gray-700">{{ $item->commodity?->name ?? '-' }}</td>
                                <td class="py-4 px-6">
                                    @if($item->status === 'accepted')
                                        <span class="bg-emerald-100 text-emerald-800 border border-emerald-300 text-xs font-bold px-3 py-1 rounded-full">Diterima</span>
                                    @else
                                        <span class="bg-rose-100 text-rose-800 border border-rose-300 text-xs font-bold px-3 py-1 rounded-full">Ditolak</span>
                                        @if($item->rejection_reason)
                                            <p class="text-xs text-gray-500 mt-1">{{ $item->rejection_reason }}</p>
                                        @endif
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-gray-600 font-mono-code text-xs">{{ $item->responded_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <!-- Modal Tolak -->
    <div x-show="showRejectModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-900/80 backdrop-blur-sm"></div>
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click.away="showRejectModal = false" class="relative w-full max-w-lg rounded-3xl bg-[#FFFDF5] border-2 border-black p-6 md:p-8 shadow-2xl">
                <p class="text-[#F4511E] text-xs font-black tracking-widest uppercase">Tolak Kemitraan</p>
                <h3 class="text-2xl font-extrabold text-[#1E222D] uppercase mt-1" x-text="rejectName"></h3>

                <form method="POST" :action="rejectAction" class="mt-5 space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="block text-xs font-extrabold text-[#1E222D] uppercase mb-1">Alasan Penolakan (opsional)</label>
                        <textarea name="rejection_reason" rows="3" maxlength="500"
                                  placeholder="Contoh: Kapasitas pasokan minggu ini sudah penuh."
                                  class="w-full px-4 py-3 bg-white border-2 border-black rounded-xl text-xs font-medium text-gray-700 focus:outline-none focus:border-[#FFB703]"></textarea>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button type="button" @click="showRejectModal = false"
                                class="w-full sm:w-1/2 rounded-full bg-gray-100 py-3 text-xs font-extrabold text-black hover:bg-gray-200 transition">
                            Batal
                        </button>
                        <button type="submit"
                                class="w-full sm:w-1/2 rounded-full bg-rose-500 border-2 border-black py-3 text-xs font-extrabold text-white hover:bg-rose-600 transition">
                            Ya, Tolak Pengajuan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection