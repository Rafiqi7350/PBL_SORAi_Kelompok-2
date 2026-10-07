@extends('layouts.admin')

@section('content')
<div x-data="{ showModal: false, editMode: false, currentCategory: { id: '', name: '', description: '' } }" class="max-w-6xl w-full mx-auto space-y-6 pb-12">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-[#F4511E] text-xs font-black tracking-widest uppercase bg-[#F4511E]/10 px-3 py-1 rounded-full border border-[#F4511E]/20">Administrator Master Data</span>
            </div>
            <h1 class="text-3xl md:text-4xl font-extrabold text-[#1E222D] tracking-tight uppercase">Master Kategori Komoditas</h1>
            <p class="text-sm font-medium text-gray-600 mt-1">Kelola daftar kategori bahan baku (misal: Unggas/Daging, Bumbu Dapur, Sayuran Segar, Telur, Sembako).</p>
        </div>
        <div>
            <button @click="editMode = false; currentCategory = { id: '', name: '', description: '' }; showModal = true" class="inline-flex items-center gap-2 bg-[#FFB703] text-black border-2 border-black font-extrabold text-sm py-3.5 px-6 rounded-full shadow-[4px_4px_0px_0px_rgba(30,34,45,1)] hover:bg-[#FFB703]/90 transition">
                + Tambah Kategori Baru
            </button>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-3xl border-2 border-black shadow-[4px_4px_0px_0px_rgba(30,34,45,1)] overflow-hidden">
        @if($categories->isEmpty())
            <div class="p-12 text-center">
                <p class="text-gray-500 font-bold">Belum ada data kategori master.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#FFFDF5] border-b-2 border-black text-[11px] font-black uppercase text-gray-600 tracking-wider">
                            <th class="py-4 px-6">Nama Kategori Master</th>
                            <th class="py-4 px-6">Slug</th>
                            <th class="py-4 px-6">Deskripsi</th>
                            <th class="py-4 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm font-medium text-gray-800">
                        @foreach($categories as $cat)
                            <tr class="hover:bg-amber-50/40 transition">
                                <td class="py-4 px-6 font-black text-[#1E222D] uppercase text-base">
                                    🏷️ {{ $cat->name }}
                                </td>
                                <td class="py-4 px-6 font-mono text-xs text-gray-500">
                                    {{ $cat->slug }}
                                </td>
                                <td class="py-4 px-6 text-xs text-gray-600">
                                    {{ $cat->description ?? '-' }}
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="editMode = true; currentCategory = {{ json_encode(['id' => $cat->id, 'name' => $cat->name, 'description' => $cat->description]) }}; showModal = true" class="p-2 bg-gray-100 hover:bg-amber-200 text-gray-900 rounded-xl transition font-bold text-xs">
                                            ✏️ Edit
                                        </button>
                                        <form method="POST" action="{{ route('admin.categories.destroy', $cat->id) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 bg-rose-50 hover:bg-rose-200 text-rose-700 rounded-xl transition font-bold text-xs">
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

            <div class="p-6 border-t-2 border-gray-100">
                {{ $categories->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Form (Alpine.js) -->
    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div x-show="showModal" x-transition.opacity class="fixed inset-0 bg-gray-900/70 backdrop-blur-sm"></div>

        <div class="flex min-h-screen items-center justify-center p-4">
            <div x-show="showModal" @click.away="showModal = false" x-transition class="relative w-full max-w-md rounded-3xl bg-[#FFFDF5] p-8 shadow-2xl border-2 border-black">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-2xl font-extrabold text-[#1E222D] uppercase" x-text="editMode ? 'Edit Kategori' : 'Tambah Kategori Baru'"></h3>
                    <button @click="showModal = false" class="text-gray-400 hover:text-black font-extrabold text-xl">&times;</button>
                </div>

                <form :action="editMode ? '{{ url('/admin/categories') }}/' + currentCategory.id : '{{ route('admin.categories.store') }}'" method="POST" class="space-y-4">
                    @csrf
                    <template x-if="editMode">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nama Kategori (*)</label>
                        <input type="text" name="name" x-model="currentCategory.name" required placeholder="Contoh: Daging & Unggas" class="w-full px-4 py-3 bg-white border-2 border-black rounded-xl font-bold text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Deskripsi</label>
                        <textarea name="description" x-model="currentCategory.description" rows="3" placeholder="Rincian kategori..." class="w-full px-4 py-3 bg-white border-2 border-black rounded-xl text-xs font-medium"></textarea>
                    </div>

                    <div class="flex gap-3 pt-3">
                        <button type="button" @click="showModal = false" class="w-1/2 py-3 bg-gray-100 rounded-full font-bold text-xs hover:bg-gray-200">Batal</button>
                        <button type="submit" class="w-1/2 py-3 bg-[#FFB703] border-2 border-black rounded-full font-extrabold text-xs hover:bg-[#FFB703]/90">Simpan &rarr;</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
