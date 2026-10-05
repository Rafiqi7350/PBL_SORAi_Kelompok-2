@props([
    'id',
    'question',
    'answer'
])

<div class="bg-white rounded-2xl sm:rounded-3xl shadow-sm border-l-4 border-l-[#DC4E00] border border-gray-100/80 overflow-hidden transition-all duration-300 hover:shadow-md">
    <button @click="activeFaq = (activeFaq === {{ $id }} ? null : {{ $id }})"
            class="w-full text-left p-5 sm:p-6 font-extrabold text-gray-900 text-base sm:text-lg flex justify-between items-center gap-4 focus:outline-none cursor-pointer group">
        <span class="tracking-tight">{{ $question }}</span>
        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-[#DC4E00] text-white flex items-center justify-center shrink-0 shadow-sm transition-all duration-300"
             :class="{ 'rotate-180 bg-[#F5B800] text-black': activeFaq === {{ $id }} }">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
    </button>
    <div x-show="activeFaq === {{ $id }}"
         x-cloak
         class="px-5 sm:px-6 pb-6 text-sm sm:text-base text-gray-600 leading-relaxed border-t border-gray-100 pt-4">
        {{ $answer }}
    </div>
</div>
