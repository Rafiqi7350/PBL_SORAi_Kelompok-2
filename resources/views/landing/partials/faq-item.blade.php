@props([
    'id',
    'question',
    'answer'
])

<div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs transition-all duration-200 overflow-hidden hover:border-slate-300"
     :class="{ 'border-orange-200 ring-1 ring-orange-100': activeFaq === {{ $id }} }">
    <button @click="activeFaq = (activeFaq === {{ $id }} ? null : {{ $id }})"
            class="w-full text-left p-6 lg:p-7 font-bold text-slate-900 text-base lg:text-lg flex justify-between items-center gap-4 focus:outline-none cursor-pointer group">
        <span class="tracking-tight group-hover:text-orange-600 transition-colors">{{ $question }}</span>
        <div class="w-9 h-9 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center shrink-0 transition-all duration-300 group-hover:bg-orange-50 group-hover:text-orange-600"
             :class="{ 'rotate-180 !bg-orange-600 !text-white': activeFaq === {{ $id }} }">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
    </button>
    <div x-show="activeFaq === {{ $id }}"
         x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="px-6 lg:px-7 pb-7 text-sm lg:text-base text-slate-600 leading-relaxed border-t border-slate-100 pt-5">
        {{ $answer }}
    </div>
</div>

