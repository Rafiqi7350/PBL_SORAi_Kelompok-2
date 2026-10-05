@props([
    'emoji',
    'title',
    'description',
    'emojiBg' => 'bg-amber-400',
    'emojiTextColor' => 'text-black'
])

<div class="bg-[#FAF2DE]/60 rounded-2xl p-6 hover:bg-[#FAF2DE] transition-all hover:scale-[1.02] cursor-pointer group">
    <div class="w-12 h-12 rounded-xl {{ $emojiBg }} {{ $emojiTextColor }} flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition-transform">
        {{ $emoji }}
    </div>
    <h4 class="font-extrabold text-lg text-gray-900 mb-1">{{ $title }}</h4>
    <p class="text-xs text-gray-600">{{ $description }}</p>
</div>
