@extends('layouts.supplier')

@section('content')
<div class="space-y-6">

    <!-- Header Section -->
    <div class="flex items-center justify-between">
        <div>
            <span class="text-[11px] font-black uppercase text-[#E07A5F] tracking-widest">INVENTARIS BAHAN BAKU</span>
            <h1 class="text-3xl font-black text-[#1E222D] uppercase tracking-tight mt-0.5">KELOLA STOK KOMODITAS</h1>
        </div>
        <div>
            <a href="{{ route('supplier.katalog.create') }}" class="inline-flex items-center gap-2 bg-[#FFB703] hover:bg-amber-400 text-black font-extrabold text-xs py-3 px-5 rounded-full border border-black shadow-sm transition">
                + Tambah Komoditas Baru
            </a>
        </div>
    </div>

    <!-- Table Section -->
    <div class="bg-white rounded-3xl border border-gray-200/80 shadow-sm overflow-hidden">
        @if($products->isEmpty())
            <div class="p-12 text-center">
                <p class="text-sm font-bold text-gray-500">Belum ada komoditas di katalog.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#FFFDF5] border-b border-gray-200 text-[11px] font-black uppercase text-gray-700 tracking-wider">
                            <th class="py-4 px-6">GAMBAR</th>
                            <th class="py-4 px-6">KOMODITAS & KATEGORI</th>
                            <th class="py-4 px-6">HARGA GROSIR</th>
                            <th class="py-4 px-6">KAPASITAS PASOK</th>
                            <th class="py-4 px-6 text-center">AKSI (CRUD)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm font-medium text-gray-800">
                        @foreach($products as $product)
                            <tr class="hover:bg-amber-50/20 transition">
                                <!-- Gambar -->
                                <td class="py-4 px-6">
                                    <div class="w-12 h-12 rounded-xl overflow-hidden bg-gray-100 border border-gray-200 flex-shrink-0">
                                        @if($product->image_url)
                                            <img src="{{ $product->image_url }}" alt="{{ $product->commodity->name }}" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center font-bold text-gray-400 text-[10px] bg-gray-200">
                                                No Image
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                <!-- Komoditas & Kategori -->
                                <td class="py-4 px-6">
                                    <p class="font-extrabold text-gray-900 text-sm">
                                        {{ $product->commodity->name }}
                                    </p>
                                </td>

                                <!-- Harga Grosir -->
                                <td class="py-4 px-6 font-bold text-orange-600">
                                    Rp {{ number_format($product->price, 0, ',', '.') }}/{{ $product->commodity->unit }}
                                </td>

                                <!-- Kapasitas Pasok -->
                                <td class="py-4 px-6 font-semibold text-gray-700">
                                    {{ $product->stock_capacity ?? '-' }}
                                </td>

                                <!-- Aksi CRUD -->
                                <td class="py-4 px-6 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- Edit -->
                                        <a href="{{ route('supplier.katalog.edit', $product->id) }}" class="px-3 py-1.5 bg-amber-100 hover:bg-amber-200 text-amber-900 rounded-full font-bold text-xs border border-amber-300 flex items-center gap-1 transition">
                                            ✏️ Edit
                                        </a>

                                        <!-- Hapus -->
                                        <form method="POST" action="{{ route('supplier.katalog.destroy', $product->id) }}" onsubmit="return confirm('Yakin ingin menghapus komoditas ini?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3 py-1.5 bg-rose-100 hover:bg-rose-200 text-rose-800 rounded-full font-bold text-xs border border-rose-300 flex items-center gap-1 transition">
                                                🗑️ Hapus
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
            <div class="p-4 border-t border-gray-100">
                {{ $products->links() }}
            </div>
        @endif
    </div>

</div>
@endsection