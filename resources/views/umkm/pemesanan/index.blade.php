@extends('layouts.umkm')

@section('content')
<script>
    // Redirect langsung ke tab Pesanan & Pengiriman di portal UMKM
    window.location.href = "{{ route('umkm.katalog.index', ['tab' => 'pemesanan']) }}";
</script>
<div class="py-12 text-center text-xs text-gray-500">
    <p>Memuat Pesanan & Pengiriman...</p>
    <a href="{{ route('umkm.katalog.index', ['tab' => 'pemesanan']) }}" class="text-[#ea580c] font-bold underline mt-2 inline-block">
        Klik di sini jika halaman tidak berpindah otomatis
    </a>
</div>
@endsection
