@props([
    'stepNumber',
    'title',
    'description',
    'cardBg' => 'bg-white',
    'titleColor' => 'text-gray-900',
    'subTextColor' => 'text-gray-600',
    'iconBg' => 'bg-amber-100',
    'iconColor' => 'text-[#DC4E00]',
    'icon' => null
])

<div class="{{ $cardBg }} rounded-[2.5rem] p-6 sm:p-8 pt-12 sm:pt-14 text-center shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-2 flex flex-col items-center justify-between relative group overflow-visible border border-black/5">
    
    <div class="absolute -top-8 left-1/2 -translate-x-1/2 z-10">
        <div class="w-16 h-16 rounded-full {{ $iconBg }} {{ $iconColor }} flex items-center justify-center shadow-md border-4 border-[#FAF2DE] group-hover:scale-110 transition-transform duration-300">
            {!! $icon !!}
        </div>
    </div>

    <div class="flex flex-col items-center">
        <h3 class="font-extrabold text-xl sm:text-2xl {{ $titleColor }} mb-3 tracking-tight leading-snug text-center">
            {{ $title }}
        </h3>

        <p class="{{ $subTextColor }} text-sm sm:text-base leading-relaxed text-center font-normal">
            {{ $description }}
        </p>
    </div>

</div>
