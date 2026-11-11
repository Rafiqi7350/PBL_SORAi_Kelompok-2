@extends('layouts.umkm')

@section('content')
<script>
    // Redirect langsung ke tab Pesanan & Pengiriman di portal UMKM
    window.location.href = "{{ route('umkm.katalog.index', ['tab' => 'pemesanan']) }}";
</script>
<div class="py-20 text-center space-y-3">
    <div class="w-10 h-10 border-2 border-[#ea580c] border-t-transparent rounded-full animate-spin mx-auto"></div>
    <p class="text-xs font-medium text-slate-500">Memuat halaman Pesanan &amp; Pengiriman...</p>
    <a href="{{ route('umkm.katalog.index', ['tab' => 'pemesanan']) }}" class="text-xs font-semibold text-[#ea580c] hover:text-[#c2410c] underline inline-block">
        Klik di sini jika halaman tidak berpindah otomatis
    </a>
</div>
@endsection
