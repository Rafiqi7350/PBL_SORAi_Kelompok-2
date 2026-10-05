@props([
    'title',
    'description',
    'badgeText' => null,
    'iconBg' => 'bg-[#DC4E00]',
    'iconColor' => 'text-white',
    'icon' => null
])

<div class="bg-white rounded-3xl p-6 sm:p-8 shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1.5 flex flex-col justify-between group">
    <div>
        <div class="w-14 h-14 rounded-full {{ $iconBg }} {{ $iconColor }} flex items-center justify-center shadow-sm mb-6 group-hover:scale-110 transition-transform duration-300">
            {!! $icon !!}
        </div>
        <h3 class="font-extrabold text-gray-900 text-lg sm:text-xl tracking-wide uppercase mb-3 leading-snug">
            {{ $title }}
        </h3>
        <p class="text-gray-600 text-sm leading-relaxed">
            {{ $description }}
        </p>
    </div>
    @if(!empty($badgeText))
        <div class="mt-6 pt-4 border-t border-gray-100 flex items-center text-xs font-bold text-[#DC4E00] gap-1 group-hover:gap-2 transition-all">
            <span>{{ $badgeText }}</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </div>
    @endif
</div>
