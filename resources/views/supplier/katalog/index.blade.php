@extends('layouts.supplier')

@section('content')
<div class="max-w-6xl w-full mx-auto space-y-8 pb-12">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <p class="text-[#E07A5F] text-xs font-bold font-mono tracking-widest uppercase mb-1">INVENTARIS BAHAN BAKU</p>
            <h1 class="text-3xl md:text-4xl font-black text-[#1E222D] tracking-tight uppercase">KELOLA STOK KOMODITAS</h1>
        </div>
        <div>
            <a href="{{ route('supplier.katalog.create') }}" class="inline-flex items-center gap-2 bg-[#FFB703] text-black border-2 border-black font-extrabold text-xs md:text-sm py-3 px-6 rounded-full shadow-[3px_3px_0px_0px_rgba(30,34,45,1)] hover:bg-[#FFB703]/90 transition transform hover:-translate-y-0.5">
                + Tambah Komoditas Baru
            </a>
        </div>
    </div>

    <!-- Main Table Container -->
    <div class="bg-white rounded-3xl border-2 border-black shadow-[4px_4px_0px_0px_rgba(30,34,45,1)] overflow-hidden">
        @if($products->isEmpty())
            <div class="p-12 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl">🌾</div>
                <h3 class="text-lg font-extrabold text-[#1E222D]">Belum ada komoditas di katalog Anda</h3>
                <p class="text-sm font-medium text-gray-500 mt-1">Klik tombol "+ Tambah Komoditas Baru" untuk mulai memasukkan stok komoditas Anda.</p>
                <a href="{{ route('supplier.katalog.create') }}" class="inline-block mt-4 bg-[#FFB703] text-black border-2 border-black font-extrabold px-6 py-2.5 rounded-full text-xs hover:bg-[#FFB703]/90">
                    + Tambah Komoditas Baru
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#FFFDF5] border-b-2 border-black text-xs font-bold font-mono text-gray-700 uppercase tracking-wider">
                            <th class="py-4 px-6 w-24">GAMBAR</th>
                            <th class="py-4 px-6">KOMODITAS & KATEGORI</th>
                            <th class="py-4 px-6">HARGA GROSIR</th>
                            <th class="py-4 px-6">KAPASITAS PASOK</th>
                            <th class="py-4 px-6 text-right w-56">AKSI (CRUD)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm font-medium text-gray-800">
                        @foreach($products as $product)
                            <tr class="hover:bg-amber-50/40 transition">
                                <!-- GAMBAR -->
                                <td class="py-5 px-6">
                                    <div class="w-14 h-14 rounded-2xl overflow-hidden bg-gray-100 border-2 border-black flex-shrink-0 shadow-sm">
                                        @if($product->image_url)
                                            <img src="{{ $product->image_url }}" alt="{{ $product->commodity->name }}" class="w-full h-full object-cover" onerror="this.src='https://placehold.co/100?text=Komoditas'">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center font-bold text-gray-400 text-xs bg-gray-200">
                                                No Img
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                <!-- KOMODITAS & KATEGORI -->
                                <td class="py-5 px-6">
                                    <h3 class="font-extrabold text-[#1E222D] text-base leading-snug">
                                        {{ $product->commodity->name }}
                                    </h3>
                                </td>

                                <!-- HARGA GROSIR -->
                                <td class="py-5 px-6">
                                    <span class="font-black text-[#F4511E] text-base">
                                        Rp {{ number_format($product->price, 0, ',', '.') }}/{{ $product->effective_unit }}
                                    </span>
                                </td>

                                <!-- KAPASITAS PASOK -->
                                <td class="py-5 px-6 font-mono font-bold text-gray-800 text-sm">
                                    {{ $product->weekly_capacity ? number_format($product->weekly_capacity, 0, ',', '.') . ' ' . $product->effective_unit . '/minggu' : ($product->stock_capacity ?? '-') }}
                                </td>

                                <!-- AKSI (CRUD) -->
                                <td class="py-5 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Edit -->
                                        <a href="{{ route('supplier.katalog.edit', $product->id) }}" class="inline-flex items-center gap-1 bg-[#FFF8D6] hover:bg-[#FEF3C7] text-black border-2 border-black font-extrabold text-xs px-4 py-1.5 rounded-full transition shadow-sm">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            Edit
                                        </a>

                                        <!-- Hapus -->
                                        <form method="POST" action="{{ route('supplier.katalog.destroy', $product->id) }}" onsubmit="return confirm('Yakin ingin menghapus {{ $product->commodity->name }}?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center gap-1 bg-[#FEE2E2] hover:bg-[#FCD5D5] text-[#991B1B] border-2 border-black font-extrabold text-xs px-4 py-1.5 rounded-full transition shadow-sm">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-6 border-t-2 border-gray-100">
                {{ $products->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
