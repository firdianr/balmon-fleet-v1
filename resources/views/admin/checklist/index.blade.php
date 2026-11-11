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

    <!-- INCLUDE CDN SORTABLEJS UNTUK DESKTOP -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

    <div class="pt-4 pb-8" x-data="{
        showCreateModal: false,
        showEditModal: false,
        showDeleteModal: false,
        isReordering: false,
        editForm: { id: null, name: '', category: 'condition', is_active: true },
        deleteForm: { id: null, name: '' },
        
        initSortables() {
            // Hanya inisialisasi SortableJS untuk tampilan Desktop
            const containers = [
                this.$refs.conditionContainerDesktop,
                this.$refs.completenessContainerDesktop
            ];

            containers.forEach(container => {
                if (!container) return;
                new Sortable(container, {
                    handle: '.drag-handle',
                    animation: 150,
                    ghostClass: 'bg-indigo-50/80',
                    onEnd: () => {
                        this.saveOrder();
                    }
                });
            });
        },

        // Fungsi Naikkan Urutan Khusus Mobile
        moveItemUp(refName, index) {
            const container = this.$refs[refName];
            if (!container || index <= 0) return;

            const items = Array.from(container.children);
            const targetIndex = index - 1;

            // Tukar posisi elemen DOM ke atas
            container.insertBefore(items[index], items[targetIndex]);

            // Simpan urutan baru ke database
            this.saveOrder();
        },

        saveOrder() {
            this.isReordering = true;
            
            let orders = [];
            const isMobile = window.innerWidth < 768;
            
            const containers = isMobile ? [
                this.$refs.conditionContainerMobile,
                this.$refs.completenessContainerMobile
            ] : [
                this.$refs.conditionContainerDesktop,
                this.$refs.completenessContainerDesktop
            ];

            containers.forEach(container => {
                if (container) {
                    const items = Array.from(container.children);
                    items.forEach(el => {
                        const id = el.getAttribute('data-id');
                        if (id) orders.push(id);
                    });
                }
            });

            fetch('{{ route('master-checklists.reorder') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ orders: orders })
            })
            .then(res => res.json())
            .then(data => {
                this.isReordering = false;
            })
            .catch(() => {
                this.isReordering = false;
                alert('Gagal menyimpan urutan baru.');
            });
        },

        openEditModal(item) {
            this.editForm.id = item.id;
            this.editForm.name = item.name;
            this.editForm.category = item.category;
            this.editForm.is_active = Boolean(item.is_active);
            this.showEditModal = true;
        },

        openDeleteModal(item) {
            this.deleteForm.id = item.id;
            this.deleteForm.name = item.name;
            this.showDeleteModal = true;
        }
    }" x-init="initSortables()">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- HEADER ACTIONS & DESKRIPSI -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 sm:p-6 rounded-2xl shadow-xs border border-gray-100">
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span>Daftar Pemeriksaan Kendaraan</span>
                        <span x-show="isReordering" class="text-xs font-semibold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-md flex items-center gap-1" x-cloak>
                            <svg class="animate-spin h-3 w-3 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Menyimpan urutan...
                        </span>
                    </h3>
                    <p class="text-xs sm:text-sm text-gray-500 mt-1">Item dipisah berdasarkan kategori. Tekan tombol panah di HP atau geser di PC untuk mengatur urutan.</p>
                </div>
                <button type="button" @click="showCreateModal = true" class="w-full sm:w-auto px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs sm:text-sm shadow-xs transition flex items-center justify-center gap-2 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 sm:w-5 sm:h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Tambah Item Checklist</span>
                </button>
            </div>

            <!-- KATEGORI 1: FISIK KENDARAAN (CONDITION) -->
            <div class="space-y-3">
                <div class="flex items-center gap-2 border-b border-amber-200 pb-2">
                    <span class="p-1.5 bg-amber-100 text-amber-700 rounded-lg">
                        <svg class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A2 2 0 0 0 2 12v4c0 .6.4 1 1 1h2"/>
                            <circle cx="7" cy="17" r="2"/>
                            <path d="M9 17h6"/>
                            <circle cx="17" cy="17" r="2"/>
                        </svg>
                    </span>
                    <h4 class="font-bold text-gray-900 text-base">Fisik Kendaraan (Kondisi)</h4>
                    <span class="text-xs bg-amber-50 text-amber-800 border border-amber-200 font-bold px-2 py-0.5 rounded-full">
                        {{ isset($checklists['condition']) ? $checklists['condition']->count() : 0 }} Item
                    </span>
                </div>

                <!-- TAMPILAN MOBILE FISIK KENDARAAN (1 TOMBOL PANAH NAIKKAN) -->
                <div class="block md:hidden space-y-3" x-ref="conditionContainerMobile">
                    @forelse($checklists['condition'] ?? [] as $index => $item)
                        <div data-id="{{ $item->id }}" class="bg-white rounded-2xl border border-gray-100 p-4 shadow-xs space-y-3 transition">
                            <div class="flex items-center justify-between border-b border-gray-100 pb-2.5">
                                {{-- TOMBOL NAIKKAN URUTAN MOBILE --}}
                                <div class="flex items-center gap-2">
                                    <button type="button" 
                                            @click="moveItemUp('conditionContainerMobile', {{ $index }})"
                                            class="px-2.5 py-1 bg-gray-50 hover:bg-indigo-50 text-gray-600 hover:text-indigo-600 rounded-lg border border-gray-200 transition active:scale-95 disabled:opacity-30 flex items-center gap-1.5 text-xs font-bold"
                                            {{ $loop->first ? 'disabled' : '' }}
                                            title="Naikkan Urutan">
                                        <svg class="w-3.5 h-3.5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="18 15 12 9 6 15"/>
                                        </svg>
                                        <span>Naikkan</span>
                                    </button>
                                </div>

                                @if($item->is_active)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-emerald-50 text-emerald-700 rounded-full text-[11px] font-bold border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-gray-100 text-gray-500 rounded-full text-[11px] font-bold border border-gray-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Nonaktif
                                    </span>
                                @endif
                            </div>

                            <h4 class="font-bold text-sm text-gray-900 leading-snug">{{ $item->name }}</h4>

                            <div class="pt-2.5 border-t border-gray-100 flex items-center justify-end gap-2">
                                <button type="button" @click="openEditModal({{ json_encode($item) }})" class="flex-1 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-xl transition text-center flex items-center justify-center gap-1.5">
                                    <span>Edit</span>
                                </button>
                                <button type="button" @click="openDeleteModal({{ json_encode($item) }})" class="flex-1 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl transition text-center flex items-center justify-center gap-1.5">
                                    <span>Hapus</span>
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="bg-white rounded-2xl border border-gray-100 p-6 text-center text-gray-400 text-xs">
                            Belum ada item kondisi fisik kendaraan.
                        </div>
                    @endforelse
                </div>

                <!-- TAMPILAN DESKTOP FISIK KENDARAAN (SORTABLEJS DRAG & DROP) -->
                <div class="hidden md:block bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-gray-50/50 border-b border-gray-100 text-gray-500 uppercase tracking-wider font-bold text-xs">
                                <th class="py-3.5 px-5 w-16 text-center">Geser</th>
                                <th class="py-3.5 px-5">Nama Item Checklist</th>
                                <th class="py-3.5 px-5 text-center w-36">Status</th>
                                <th class="py-3.5 px-5 text-right w-32">Aksi</th>
                            </tr>
                        </thead>
                        <tbody x-ref="conditionContainerDesktop" class="divide-y divide-gray-100 font-medium text-gray-700">
                            @forelse($checklists['condition'] ?? [] as $item)
                                <tr data-id="{{ $item->id }}" class="hover:bg-gray-50/50 transition">
                                    <td class="py-3.5 px-5 text-center">
                                        <span class="drag-handle cursor-grab active:cursor-grabbing inline-flex p-1.5 bg-gray-50 hover:bg-gray-100 rounded-lg text-gray-400 hover:text-gray-600 border border-gray-200 transition">
                                            <svg class="w-4 h-4 stroke-current stroke-2 fill-none" viewBox="0 0 24 24">
                                                <circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/>
                                                <circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/>
                                            </svg>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-5 font-bold text-gray-900 text-sm">
                                        {{ $item->name }}
                                    </td>
                                    <td class="py-3.5 px-5 text-center">
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
                                    <td class="py-3.5 px-5 text-right space-x-1">
                                        <button type="button" @click="openEditModal({{ json_encode($item) }})" class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="Edit Item">
                                            <svg class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </button>
                                        <button type="button" @click="openDeleteModal({{ json_encode($item) }})" class="p-2 text-gray-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus Item">
                                            <svg class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-gray-400 text-sm">
                                        Belum ada item kondisi fisik kendaraan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- KATEGORI 2: KELENGKAPAN DINAS (COMPLETENESS) -->
            <div class="space-y-3 pt-4">
                <div class="flex items-center gap-2 border-b border-blue-200 pb-2">
                    <span class="p-1.5 bg-blue-100 text-blue-700 rounded-lg">
                        <svg class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                        </svg>
                    </span>
                    <h4 class="font-bold text-gray-900 text-base">Kelengkapan Dinas (Peralatan & Surat)</h4>
                    <span class="text-xs bg-blue-50 text-blue-800 border border-blue-200 font-bold px-2 py-0.5 rounded-full">
                        {{ isset($checklists['completeness']) ? $checklists['completeness']->count() : 0 }} Item
                    </span>
                </div>

                <!-- TAMPILAN MOBILE KELENGKAPAN DINAS (1 TOMBOL PANAH NAIKKAN) -->
                <div class="block md:hidden space-y-3" x-ref="completenessContainerMobile">
                    @forelse($checklists['completeness'] ?? [] as $index => $item)
                        <div data-id="{{ $item->id }}" class="bg-white rounded-2xl border border-gray-100 p-4 shadow-xs space-y-3 transition">
                            <div class="flex items-center justify-between border-b border-gray-100 pb-2.5">
                                {{-- TOMBOL NAIKKAN URUTAN MOBILE --}}
                                <div class="flex items-center gap-2">
                                    <button type="button" 
                                            @click="moveItemUp('completenessContainerMobile', {{ $index }})"
                                            class="px-2.5 py-1 bg-gray-50 hover:bg-indigo-50 text-gray-600 hover:text-indigo-600 rounded-lg border border-gray-200 transition active:scale-95 disabled:opacity-30 flex items-center gap-1.5 text-xs font-bold"
                                            {{ $loop->first ? 'disabled' : '' }}
                                            title="Naikkan Urutan">
                                        <svg class="w-3.5 h-3.5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="18 15 12 9 6 15"/>
                                        </svg>
                                        <span>Naikkan</span>
                                    </button>
                                </div>

                                @if($item->is_active)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-emerald-50 text-emerald-700 rounded-full text-[11px] font-bold border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-gray-100 text-gray-500 rounded-full text-[11px] font-bold border border-gray-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Nonaktif
                                    </span>
                                @endif
                            </div>

                            <h4 class="font-bold text-sm text-gray-900 leading-snug">{{ $item->name }}</h4>

                            <div class="pt-2.5 border-t border-gray-100 flex items-center justify-end gap-2">
                                <button type="button" @click="openEditModal({{ json_encode($item) }})" class="flex-1 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-xl transition text-center flex items-center justify-center gap-1.5">
                                    <span>Edit</span>
                                </button>
                                <button type="button" @click="openDeleteModal({{ json_encode($item) }})" class="flex-1 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl transition text-center flex items-center justify-center gap-1.5">
                                    <span>Hapus</span>
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="bg-white rounded-2xl border border-gray-100 p-6 text-center text-gray-400 text-xs">
                            Belum ada item kelengkapan dinas.
                        </div>
                    @endforelse
                </div>

                <!-- TAMPILAN DESKTOP KELENGKAPAN DINAS (SORTABLEJS DRAG & DROP) -->
                <div class="hidden md:block bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-gray-50/50 border-b border-gray-100 text-gray-500 uppercase tracking-wider font-bold text-xs">
                                <th class="py-3.5 px-5 w-16 text-center">Geser</th>
                                <th class="py-3.5 px-5">Nama Item Checklist</th>
                                <th class="py-3.5 px-5 text-center w-36">Status</th>
                                <th class="py-3.5 px-5 text-right w-32">Aksi</th>
                            </tr>
                        </thead>
                        <tbody x-ref="completenessContainerDesktop" class="divide-y divide-gray-100 font-medium text-gray-700">
                            @forelse($checklists['completeness'] ?? [] as $item)
                                <tr data-id="{{ $item->id }}" class="hover:bg-gray-50/50 transition">
                                    <td class="py-3.5 px-5 text-center">
                                        <span class="drag-handle cursor-grab active:cursor-grabbing inline-flex p-1.5 bg-gray-50 hover:bg-gray-100 rounded-lg text-gray-400 hover:text-gray-600 border border-gray-200 transition">
                                            <svg class="w-4 h-4 stroke-current stroke-2 fill-none" viewBox="0 0 24 24">
                                                <circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/>
                                                <circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/>
                                            </svg>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-5 font-bold text-gray-900 text-sm">
                                        {{ $item->name }}
                                    </td>
                                    <td class="py-3.5 px-5 text-center">
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
                                    <td class="py-3.5 px-5 text-right space-x-1">
                                        <button type="button" @click="openEditModal({{ json_encode($item) }})" class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="Edit Item">
                                            <svg class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </button>
                                        <button type="button" @click="openDeleteModal({{ json_encode($item) }})" class="p-2 text-gray-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus Item">
                                            <svg class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-gray-400 text-sm">
                                        Belum ada item kelengkapan dinas.
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
            <div class="bg-white rounded-2xl max-w-md w-full p-5 sm:p-6 shadow-xl space-y-4" @click.away="showCreateModal = false">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="font-bold text-base sm:text-lg text-gray-900">Tambah Item Master Checklist</h3>
                    <button type="button" @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100 transition">
                        <svg class="w-5 h-5 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                <form action="{{ route('master-checklists.store') }}" method="POST" class="space-y-4 text-xs sm:text-sm">
                    @csrf
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Nama Item Checklist <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required placeholder="Contoh: Bodi & Cat Mulus / Dongkrak" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-xs sm:text-sm focus:ring-indigo-500 focus:border-indigo-500 font-medium">
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Kategori <span class="text-rose-500">*</span></label>
                        <select name="category" required class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-xs sm:text-sm focus:ring-indigo-500 focus:border-indigo-500 font-medium">
                            <option value="condition">Fisik Kendaraan (Kondisi)</option>
                            <option value="completeness">Kelengkapan Dinas (Peralatan)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Status Ketersediaan</label>
                        <label class="flex items-center gap-2 mt-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500">
                            <span class="font-bold text-gray-700 text-xs sm:text-sm">Aktifkan Item Ini</span>
                        </label>
                    </div>
                    <div class="pt-4 border-t border-gray-100 flex justify-end gap-2">
                        <button type="button" @click="showCreateModal = false" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition">Batal</button>
                        <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition shadow-xs">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL EDIT -->
        <div x-show="showEditModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4" x-transition x-cloak>
            <div class="bg-white rounded-2xl max-w-md w-full p-5 sm:p-6 shadow-xl space-y-4" @click.away="showEditModal = false">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="font-bold text-base sm:text-lg text-gray-900">Edit Item Master Checklist</h3>
                    <button type="button" @click="showEditModal = false" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100 transition">
                        <svg class="w-5 h-5 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                <form :action="`/master-checklists/${editForm.id}`" method="POST" class="space-y-4 text-xs sm:text-sm">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Nama Item Checklist <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" x-model="editForm.name" required class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-xs sm:text-sm focus:ring-indigo-500 focus:border-indigo-500 font-medium">
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Kategori <span class="text-rose-500">*</span></label>
                        <select name="category" x-model="editForm.category" required class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-xs sm:text-sm focus:ring-indigo-500 focus:border-indigo-500 font-medium">
                            <option value="condition">Fisik Kendaraan (Kondisi)</option>
                            <option value="completeness">Kelengkapan Dinas (Peralatan)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Status Ketersediaan</label>
                        <label class="flex items-center gap-2 mt-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" x-model="editForm.is_active" class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500">
                            <span class="font-bold text-gray-700 text-xs sm:text-sm">Aktifkan Item Ini</span>
                        </label>
                    </div>
                    <div class="pt-4 border-t border-gray-100 flex justify-end gap-2">
                        <button type="button" @click="showEditModal = false" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition">Batal</button>
                        <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition shadow-xs">Update</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL DELETE -->
        <div x-show="showDeleteModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4" x-transition x-cloak>
            <div class="bg-white rounded-2xl max-w-sm w-full p-5 sm:p-6 shadow-xl space-y-4" @click.away="showDeleteModal = false">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="font-bold text-base sm:text-lg text-gray-900">Konfirmasi Hapus</h3>
                    <button type="button" @click="showDeleteModal = false" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100 transition">
                        <svg class="w-5 h-5 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                <p class="text-xs sm:text-sm text-gray-600">
                    Apakah Anda yakin ingin menghapus item checklist <strong x-text="deleteForm.name" class="text-gray-900"></strong>? Tindakan ini tidak dapat dibatalkan.
                </p>
                <form :action="`/master-checklists/${deleteForm.id}`" method="POST" class="pt-2 flex justify-end gap-2 text-xs sm:text-sm">
                    @csrf
                    @method('DELETE')
                    <button type="button" @click="showDeleteModal = false" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition">Batal</button>
                    <button type="submit" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl transition shadow-xs">Hapus</button>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>