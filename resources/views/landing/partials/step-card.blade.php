@props([
    'stepNumber',
    'title',
    'description',
    'badge' => null,
    'iconBg' => 'bg-orange-50',
    'iconColor' => 'text-orange-600',
    'icon' => null
])

<div class="bg-white rounded-2xl p-7 lg:p-9 border border-slate-200/80 shadow-xs hover:shadow-md hover:border-orange-200 transition-all duration-300 flex flex-col justify-between relative group">
    <div>
        <div class="flex items-center justify-between mb-6">
            <div class="w-12 h-12 rounded-xl {{ $iconBg }} {{ $iconColor }} flex items-center justify-center group-hover:scale-105 transition-transform">
                {!! $icon !!}
            </div>
            <span class="font-mono text-3xl font-black text-slate-200 group-hover:text-orange-200 transition-colors">
                0{{ $stepNumber }}
            </span>
        </div>

        <h3 class="font-bold text-slate-900 text-lg lg:text-xl tracking-tight mb-3 leading-snug">
            {{ $title }}
        </h3>

        <p class="text-slate-600 text-sm lg:text-base leading-relaxed">
            {{ $description }}
        </p>
    </div>

    @if(!empty($badge))
        <div class="mt-8 pt-5 border-t border-slate-100 flex items-center text-xs font-semibold text-slate-500">
            <span class="text-orange-600 font-medium">{{ $badge }}</span>
        </div>
    @endif
</div>


