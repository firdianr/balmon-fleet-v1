<x-app-layout>
    <x-slot name="title">Kelola Master Checklist Armada</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-2">
            <!-- Icon Clipboard-Check -->
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 stroke-indigo-600 fill-none stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/>
                <rect x="9" y="3" width="6" height="4" rx="1"/>
                <path d="m9 14 2 2 4-4"/>
            </svg>
            <span>{{ __('Kelola Master Checklist Armada') }}</span>
        </h2>
    </x-slot>

    <div class="pt-4 pb-8" x-data="{
        showCreateModal: false,
        showEditModal: false,
        showDeleteModal: false,
        editForm: {
            id: null,
            name: '',
            category: 'condition',
            sort_order: 0,
            is_active: true
        },
        deleteForm: {
            id: null,
            name: ''
        },
        openEditModal(item) {
            this.editForm.id = item.id;
            this.editForm.name = item.name;
            this.editForm.category = item.category;
            this.editForm.sort_order = item.sort_order;
            this.editForm.is_active = Boolean(item.is_active);
            this.showEditModal = true;
        },
        openDeleteModal(item) {
            this.deleteForm.id = item.id;
            this.deleteForm.name = item.name;
            this.showDeleteModal = true;
        }
    }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- HEADER ACTIONS & DESKRIPSI -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl shadow-xs border border-gray-100">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Daftar Pemeriksaan Kendaraan</h3>
                    <p class="text-sm text-gray-500 mt-1">Kelola poin kelengkapan dinas dan kondisi fisik yang akan dikonfirmasi driver & admin.</p>
                </div>
                <button type="button" @click="showCreateModal = true" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-sm shadow-xs transition flex items-center justify-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Tambah Item Checklist</span>
                </button>
            </div>

            <!-- TABEL DATA MASTER CHECKLIST -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-gray-50/50 border-b border-gray-100 text-gray-500 uppercase tracking-wider font-bold text-xs">
                                <th class="py-4 px-5 w-20 text-center">Urutan</th>
                                <th class="py-4 px-5">Nama Item Checklist</th>
                                <th class="py-4 px-5">Kategori</th>
                                <th class="py-4 px-5 text-center">Status</th>
                                <th class="py-4 px-5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 font-medium text-gray-700">
                            @forelse($checklists as $item)
                                <tr class="hover:bg-gray-50/50 transition">
                                    <td class="py-4 px-5 text-center font-bold text-gray-500">
                                        {{ $item->sort_order }}
                                    </td>
                                    <td class="py-4 px-5 font-bold text-gray-900 text-sm">
                                        {{ $item->name }}
                                    </td>
                                    <td class="py-4 px-5">
                                        @if($item->category === 'condition')
                                            <span class="px-3 py-1 bg-amber-50 text-amber-700 rounded-lg text-xs font-bold border border-amber-200">
                                                Fisik Kendaraan
                                            </span>
                                        @else
                                            <span class="px-3 py-1 bg-blue-50 text-blue-700 rounded-lg text-xs font-bold border border-blue-200">
                                                Kelengkapan Dinas
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-5 text-center">
                                        @if($item->is_active)
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-700 rounded-lg text-xs font-bold border border-emerald-200">
                                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Aktif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-gray-100 text-gray-500 rounded-lg text-xs font-bold border border-gray-200">
                                                <span class="w-2 h-2 rounded-full bg-gray-400"></span> Nonaktif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-5 text-right space-x-1">
                                        <button type="button" @click="openEditModal({{ json_encode($item) }})" class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="Edit Item">
                                            <svg class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </button>
                                        <button type="button" @click="openDeleteModal({{ json_encode($item) }})" class="p-2 text-gray-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus Item">
                                            <svg class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-10 text-center text-gray-400 text-sm">
                                        Belum ada item master checklist yang terdaftar.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- MODAL CREATE -->
        <div x-show="showCreateModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4" x-transition x-cloak>
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4" @click.away="showCreateModal = false">
                <h3 class="font-bold text-lg text-gray-900">Tambah Item Master Checklist</h3>
                <form action="{{ route('master-checklists.store') }}" method="POST" class="space-y-4 text-sm">
                    @csrf
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Nama Item Checklist <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required placeholder="Contoh: Bodi & Cat Mulus / Dongkrak" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Kategori <span class="text-rose-500">*</span></label>
                        <select name="category" required class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="condition">Fisik Kendaraan (Kondisi)</option>
                            <option value="completeness">Kelengkapan Dinas (Peralatan)</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Urutan Tampil</label>
                            <input type="number" name="sort_order" value="0" min="0" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Status</label>
                            <label class="flex items-center gap-2 mt-2.5 cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500">
                                <span class="font-bold text-gray-700 text-sm">Aktifkan</span>
                            </label>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-gray-100 flex justify-end gap-2">
                        <button type="button" @click="showCreateModal = false" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition">Batal</button>
                        <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL EDIT -->
        <div x-show="showEditModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4" x-transition x-cloak>
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4" @click.away="showEditModal = false">
                <h3 class="font-bold text-lg text-gray-900">Edit Item Master Checklist</h3>
                <form :action="`/master-checklists/${editForm.id}`" method="POST" class="space-y-4 text-sm">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Nama Item Checklist <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" x-model="editForm.name" required class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Kategori <span class="text-rose-500">*</span></label>
                        <select name="category" x-model="editForm.category" required class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="condition">Fisik Kendaraan (Kondisi)</option>
                            <option value="completeness">Kelengkapan Dinas (Peralatan)</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Urutan Tampil</label>
                            <input type="number" name="sort_order" x-model="editForm.sort_order" min="0" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Status</label>
                            <label class="flex items-center gap-2 mt-2.5 cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" x-model="editForm.is_active" class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500">
                                <span class="font-bold text-gray-700 text-sm">Aktifkan</span>
                            </label>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-gray-100 flex justify-end gap-2">
                        <button type="button" @click="showEditModal = false" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition">Batal</button>
                        <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition">Update</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL DELETE -->
        <div x-show="showDeleteModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4" x-transition x-cloak>
            <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-xl space-y-4" @click.away="showDeleteModal = false">
                <h3 class="font-bold text-lg text-gray-900">Konfirmasi Hapus</h3>
                <p class="text-sm text-gray-600">
                    Apakah Anda yakin ingin menghapus item checklist <strong x-text="deleteForm.name" class="text-gray-900"></strong>? Tindakan ini tidak dapat dibatalkan.
                </p>
                <form :action="`/master-checklists/${deleteForm.id}`" method="POST" class="pt-2 flex justify-end gap-2 text-sm">
                    @csrf
                    @method('DELETE')
                    <button type="button" @click="showDeleteModal = false" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition">Batal</button>
                    <button type="submit" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl transition">Hapus</button>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>