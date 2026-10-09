@extends('layouts.admin')

@section('content')
<div x-data="{ showModal: false, editMode: false, currentUnit: { id: '', name: '', symbol: '', description: '' } }" class="max-w-6xl w-full mx-auto space-y-6 pb-12">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-orange-600 text-[10px] font-bold tracking-widest uppercase bg-orange-50 px-3 py-1 rounded-full border border-orange-100">Administrator Master Data</span>
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 tracking-tight">Master Satuan Baku Komoditas</h1>
            <p class="text-xs sm:text-sm font-medium text-slate-500 mt-1">Kelola daftar satuan baku pengukuran pasokan komoditas (kg, liter, pcs, dus, gram, ikat, karung).</p>
        </div>
        <div>
            <button @click="editMode = false; currentUnit = { id: '', name: '', symbol: '', description: '' }; showModal = true" class="inline-flex items-center gap-2 bg-[#ea580c] hover:bg-orange-600 text-white font-bold text-xs py-3 px-5 rounded-xl shadow-sm transition cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                <span>Tambah Satuan Baku</span>
            </button>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-[1.75rem] border border-gray-100 shadow-[0_10px_35px_rgba(0,0,0,0.03)] overflow-hidden">
        @if($units->isEmpty())
            <div class="p-12 text-center">
                <p class="text-slate-400 font-medium text-xs">Belum ada data satuan baku master.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100 text-[10px] font-bold uppercase text-slate-400 tracking-wider">
                            <th class="py-4 px-6">Nama Satuan Baku</th>
                            <th class="py-4 px-6">Simbol / Singkatan</th>
                            <th class="py-4 px-6">Deskripsi</th>
                            <th class="py-4 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                        @foreach($units as $u)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-4 px-6 font-bold text-slate-900 text-sm">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 18h12l3-18H3zM9 6v18M15 6v18" />
                                        </svg>
                                        <span>{{ $u->name }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-6 font-mono text-[11px] font-extrabold text-orange-600">
                                    {{ $u->symbol ?? $u->name }}
                                </td>
                                <td class="py-4 px-6 text-xs text-slate-500">
                                    {{ $u->description ?? '-' }}
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="editMode = true; currentUnit = {{ json_encode(['id' => $u->id, 'name' => $u->name, 'symbol' => $u->symbol, 'description' => $u->description]) }}; showModal = true" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl transition font-bold text-xs cursor-pointer">
                                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                            <span>Edit</span>
                                        </button>

                                        <form method="POST" action="{{ route('admin.units.destroy', $u->id) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-xl transition font-bold text-xs cursor-pointer">
                                                <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                <span>Hapus</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-6 border-t border-slate-100">
                {{ $units->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Form (Alpine.js) -->
    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div x-show="showModal" x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

        <div class="flex min-h-screen items-center justify-center p-4">
            <div x-show="showModal" @click.away="showModal = false" x-transition class="relative w-full max-w-md rounded-[1.75rem] bg-white p-6 shadow-2xl border border-gray-100 space-y-4">
                <div class="flex justify-between items-center pb-3 border-b border-slate-100">
                    <h3 class="text-lg font-extrabold text-slate-900" x-text="editMode ? 'Edit Satuan Baku' : 'Tambah Satuan Baku Baru'"></h3>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-700 font-bold text-xl leading-none cursor-pointer">&times;</button>
                </div>

                <form :action="editMode ? '{{ url('/admin/units') }}/' + currentUnit.id : '{{ route('admin.units.store') }}'" method="POST" class="space-y-4 text-xs font-bold">
                    @csrf
                    <template x-if="editMode">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div>
                        <label class="block text-slate-700 mb-1">Nama Satuan Baku (*)</label>
                        <input type="text" name="name" x-model="currentUnit.name" required placeholder="Contoh: Kilogram / Liter / Dus" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-bold text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>

                    <div>
                        <label class="block text-slate-700 mb-1">Simbol / Singkatan</label>
                        <input type="text" name="symbol" x-model="currentUnit.symbol" placeholder="Contoh: kg, l, pcs" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-bold text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>

                    <div>
                        <label class="block text-slate-700 mb-1">Deskripsi</label>
                        <textarea name="description" x-model="currentUnit.description" rows="3" placeholder="Rincian penggunaan satuan..." class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-orange-500"></textarea>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="showModal = false" class="w-1/2 py-2.5 bg-slate-100 rounded-xl font-bold text-xs text-slate-700 hover:bg-slate-200 transition cursor-pointer">Batal</button>
                        <button type="submit" class="w-1/2 py-2.5 bg-[#ea580c] hover:bg-orange-600 text-white rounded-xl font-bold text-xs transition shadow-sm cursor-pointer">Simpan &rarr;</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
