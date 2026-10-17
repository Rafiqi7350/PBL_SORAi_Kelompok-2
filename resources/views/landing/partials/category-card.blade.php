@props([
    'title',
    'description',
    'tag' => null,
    'popular' => null,
    'icon' => null,
    'iconBg' => 'bg-orange-50',
    'iconColor' => 'text-orange-600'
])

<div class="bg-slate-50/70 hover:bg-white rounded-2xl p-6 lg:p-7 border border-slate-200/80 hover:border-orange-300 hover:shadow-md transition-all duration-300 group flex flex-col justify-between">
    <div>
        <div class="flex items-center justify-between mb-5">
            <div class="w-12 h-12 rounded-xl {{ $iconBg }} {{ $iconColor }} flex items-center justify-center group-hover:scale-105 transition-transform">
                {!! $icon !!}
            </div>
            @if(!empty($tag))
                <span class="text-[11px] font-semibold text-slate-600 bg-white border border-slate-200/80 px-2.5 py-1 rounded-md shadow-2xs">
                    {{ $tag }}
                </span>
            @endif
        </div>
        <h4 class="font-bold text-slate-900 text-base lg:text-lg mb-2 group-hover:text-orange-600 transition-colors">
            {{ $title }}
        </h4>
        <p class="text-xs lg:text-sm text-slate-600 leading-relaxed mb-6">
            {{ $description }}
        </p>
    </div>

    @if(!empty($popular))
        <div class="pt-4 border-t border-slate-200/70 flex items-center justify-between text-xs text-slate-500 font-medium">
            <span class="truncate">Item: {{ $popular }}</span>
        </div>
    @endif
</div>



