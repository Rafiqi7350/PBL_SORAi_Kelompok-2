@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi Halaman" class="flex flex-col sm:flex-row items-center justify-between gap-3 px-1 py-3 text-xs select-none">
        <div class="text-slate-600 dark:text-slate-400 font-medium text-center sm:text-left">
            <span>Menampilkan</span>
            @if ($paginator->firstItem())
                <span class="font-bold text-slate-900 dark:text-white">{{ $paginator->firstItem() }}</span>
                <span>sampai</span>
                <span class="font-bold text-slate-900 dark:text-white">{{ $paginator->lastItem() }}</span>
            @else
                {{ $paginator->count() }}
            @endif
            <span>dari</span>
            <span class="font-bold text-slate-900 dark:text-white">{{ $paginator->total() }}</span>
            <span>data</span>
        </div>

        <div class="inline-flex items-center gap-1.5">
            {{-- Tombol Sebelumnya --}}
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-slate-400 dark:text-slate-500 bg-slate-100/70 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/50 cursor-not-allowed font-medium text-xs">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <span class="hidden sm:inline">Sebelumnya</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200/90 dark:border-slate-700 hover:bg-orange-50 hover:text-[#ea580c] dark:hover:bg-slate-700 transition font-medium text-xs shadow-2xs">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <span class="hidden sm:inline">Sebelumnya</span>
                </a>
            @endif

            {{-- Elemen Nomor Halaman --}}
            <div class="inline-flex items-center gap-1">
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <span aria-disabled="true" class="px-2 py-1 text-slate-400 dark:text-slate-500 font-bold">&hellip;</span>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="inline-flex items-center justify-center min-w-[32px] h-8 px-2.5 rounded-xl bg-[#ea580c] text-white font-bold text-xs shadow-2xs">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}" class="inline-flex items-center justify-center min-w-[32px] h-8 px-2.5 rounded-xl bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/90 dark:border-slate-700 hover:bg-orange-50 hover:text-[#ea580c] dark:hover:bg-slate-700 transition font-semibold text-xs shadow-2xs">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            {{-- Tombol Selanjutnya --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200/90 dark:border-slate-700 hover:bg-orange-50 hover:text-[#ea580c] dark:hover:bg-slate-700 transition font-medium text-xs shadow-2xs">
                    <span class="hidden sm:inline">Selanjutnya</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            @else
                <span aria-disabled="true" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-slate-400 dark:text-slate-500 bg-slate-100/70 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/50 cursor-not-allowed font-medium text-xs">
                    <span class="hidden sm:inline">Selanjutnya</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </span>
            @endif
        </div>
    </nav>
@endif

