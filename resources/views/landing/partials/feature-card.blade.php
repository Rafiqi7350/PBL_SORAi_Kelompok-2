@props([
    'title',
    'description',
    'badgeText' => null,
    'iconBg' => 'bg-orange-50',
    'iconColor' => 'text-orange-600',
    'icon' => null
])

<div class="bg-white rounded-2xl p-7 lg:p-9 border border-slate-200/80 shadow-xs hover:shadow-md hover:border-orange-200 transition-all duration-300 flex flex-col justify-between group">
    <div>
        <div class="w-12 h-12 rounded-xl {{ $iconBg }} {{ $iconColor }} flex items-center justify-center mb-6 group-hover:scale-105 transition-transform duration-300">
            {!! $icon !!}
        </div>
        <h3 class="font-bold text-slate-900 text-lg lg:text-xl tracking-tight mb-3 leading-snug">
            {{ $title }}
        </h3>
        <p class="text-slate-600 text-sm lg:text-base leading-relaxed">
            {{ $description }}
        </p>
    </div>
    @if(!empty($badgeText))
        <div class="mt-8 pt-5 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-slate-500">
            <span class="inline-flex items-center gap-1.5 text-orange-600">
                <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span>
                {{ $badgeText }}
            </span>
            <svg class="w-4 h-4 text-slate-400 group-hover:translate-x-1 group-hover:text-orange-600 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </div>
    @endif
</div>

