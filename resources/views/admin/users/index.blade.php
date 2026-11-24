<x-app-layout>
    <x-slot name="title">Kelola Data & Akun Pegawai</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 stroke-indigo-600 fill-none stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            <span>{{ __('Kelola Akun Pegawai') }}</span>
        </h2>
    </x-slot>

    <div class="pt-2 pb-6" 
         x-data="{ 
            search: '', 
            sort: 'name_asc',
            initialUsers: @js($users->items()),
            users: [], 
            isFiltered: false,
            showDeleteModal: false,
            selectedUser: null,
            isLoading: false,

            // State Import & Pratinjau Seleksi
            showImportModal: {{ $errors->has('import_file') ? 'true' : 'false' }},
            showPreviewModal: false,
            importMode: 'skip',
            selectedImportName: '',
            previewFileName: '',
            previewError: '',
            isParsingImport: false,
            isSavingImport: false,
            previewRows: [],
            previewSearch: '',
            previewFilter: 'all',

            get filteredPreviewRows() {
                return this.previewRows.filter(row => {
                    const matchesFilter =
                        this.previewFilter === 'all' ||
                        (this.previewFilter === 'selected' && row.selected) ||
                        (this.previewFilter === 'valid' && ['new', 'update'].includes(row.status)) ||
                        (this.previewFilter === 'issue' && ['skip', 'invalid'].includes(row.status));

                    if (!matchesFilter) return false;

                    if (!this.previewSearch.trim()) return true;
                    const q = this.previewSearch.toLowerCase();
                    return (row.name || '').toLowerCase().includes(q) ||
                           (row.nip || '').toLowerCase().includes(q) ||
                           (row.email || '').toLowerCase().includes(q) ||
                           (row.department || '').toLowerCase().includes(q);
                });
            },

            get validPreviewRows() {
                return this.previewRows.filter(r => ['new', 'update'].includes(r.status));
            },

            get selectedPreviewCount() {
                return this.previewRows.filter(r => r.selected).length;
            },

            get allValidSelected() {
                return this.validPreviewRows.length > 0 && this.validPreviewRows.every(r => r.selected);
            },

            toggleAllValid(checked) {
                this.previewRows.forEach(r => {
                    if (['new', 'update'].includes(r.status)) {
                        r.selected = checked;
                    }
                });
            },

            async parseImportFile() {
                const fileInput = this.$refs.importFileInput;
                if (!fileInput || !fileInput.files.length) {
                    this.previewError = 'Silakan pilih berkas Excel (.xlsx) atau CSV terlebih dahulu.';
                    return;
                }

                this.isParsingImport = true;
                this.previewError = '';

                const formData = new FormData();
                formData.append('import_file', fileInput.files[0]);
                formData.append('import_mode', this.importMode);
                formData.append('_token', '{{ csrf_token() }}');

                try {
                    const response = await fetch('{{ route('admin_app.users.import.preview') }}', {
                        method: 'POST',
                        headers: { 'Accept': 'application/json' },
                        body: formData
                    });

                    const result = await response.json();

                    if (response.ok && result.success) {
                        this.previewRows = result.rows;
                        this.previewFileName = result.file_name || this.selectedImportName;
                        this.previewSearch = '';
                        this.previewFilter = 'all';
                        this.showImportModal = false;
                        this.showPreviewModal = true;
                    } else {
                        this.previewError = result.message || 'Gagal membaca berkas spreadsheet.';
                    }
                } catch (e) {
                    this.previewError = 'Terjadi kesalahan jaringan saat membaca berkas.';
                } finally {
                    this.isParsingImport = false;
                }
            },

            fetchUsers() {
                this.isLoading = true;
                fetch(`/api/search/users?q=${encodeURIComponent(this.search)}&sort=${this.sort}`)
                    .then(res => res.json())
                    .then(data => {
                        this.users = data.data;
                        this.isFiltered = true;
                        this.isLoading = false;
                    })
                    .catch(() => { this.isLoading = false; });
            }
         }"
         x-init="$watch('search', () => fetchUsers()); $watch('sort', () => fetchUsers())">
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            {{-- LAPORAN BARIS GAGAL IMPORT (JIKA ADA) --}}
            @if(session('import_errors'))
                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 text-xs text-amber-900 space-y-2 shadow-2xs">
                    <p class="font-bold text-amber-950 flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-600 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                            <line x1="12" y1="9" x2="12" y2="13"/>
                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                        <span>Beberapa baris pada berkas dilewati karena tidak valid ({{ count(session('import_errors')) }} baris):</span>
                    </p>
                    <ul class="list-disc list-inside space-y-1 text-amber-800 max-h-36 overflow-y-auto font-medium">
                        @foreach(session('import_errors') as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            {{-- AREA SEARCH & DROPDOWN SORTING --}}
            <div class="bg-white rounded-2xl p-4 border border-gray-200/80 shadow-xs flex flex-col md:flex-row justify-between items-center gap-3">
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:max-w-xl">
                    {{-- KOTAK PENCARIAN REAL-TIME --}}
                    <div class="relative w-full">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"/>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                        </div>
                        <input type="text" 
                            x-model="search" 
                            @input.debounce.300ms="fetchUsers()" 
                            aria-label="Cari User atau Pegawai"
                            placeholder="Cari Nama, NIP, Email, Seksi..." 
                            class="pl-10 pr-10 py-2.5 w-full text-sm border border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 shadow-2xs">
                        
                        <div x-show="isLoading" class="absolute inset-y-0 right-0 pr-3 flex items-center" x-cloak>
                            <svg class="animate-spin h-4 w-4 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>

                    {{-- DROPDOWN MODE PENGURUTAN --}}
                    <select x-model="sort" 
                            @change="fetchUsers()" 
                            aria-label="Urutkan Daftar User"
                            class="w-full sm:w-auto py-2.5 px-4 text-sm border border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 shadow-2xs bg-white font-semibold text-gray-700 cursor-pointer">
                        <option value="name_asc">Nama (A - Z)</option>
                        <option value="name_desc">Nama (Z - A)</option>
                        <option value="nip_asc">NIP (Terurut)</option>
                        <option value="latest">Terbaru Ditambahkan</option>
                        <option value="oldest">Terlama Ditambahkan</option>
                    </select>
                </div>

                {{-- GRUP TOMBOL AKSI KANAN --}}
                <div class="flex flex-col sm:flex-row items-center gap-2.5 w-full md:w-auto shrink-0">
                    <button type="button"
                            @click="showImportModal = true"
                            class="w-full sm:w-auto px-4 py-2.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-xl text-sm font-bold text-center shadow-2xs transition inline-flex items-center justify-center gap-2 cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="17 8 12 3 7 8"/>
                            <line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                        <span>Import Excel</span>
                    </button>

                    <a href="{{ route('admin_app.users.create') }}" class="w-full sm:w-auto px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold text-center shadow-md transition inline-flex items-center justify-center gap-2">
                        <svg class="w-4 h-4 text-white shrink-0 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"/>
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        <span>Tambah User / Pegawai</span>
                    </a>
                </div>
            </div>

            {{-- 1. TAMPILAN KARTU SMARTPHONE / MOBILE --}}
            <div class="block md:hidden space-y-3">
                {{-- DATA AWAL (SSR BLADE - ZERO CLS) --}}
                <div x-show="!isFiltered" class="space-y-3">
                    @forelse($users as $index => $user)
                        @php
                            $badgeClass = match($user->role) {
                                'admin_app' => 'bg-purple-100 text-purple-800 border-purple-200',
                                'admin_lap' => 'bg-amber-100 text-amber-800 border-amber-200',
                                'approver'  => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                'driver'    => 'bg-red-100 text-red-800 border-red-200',
                                default     => 'bg-sky-100 text-sky-800 border-sky-200',
                            };
                            $roleLabel = match($user->role) {
                                'admin_app' => 'ADMIN APP',
                                'admin_lap' => 'ADMIN LAP',
                                'approver'  => 'APPROVER',
                                'driver'    => 'DRIVER',
                                default     => 'PEGAWAI',
                            };
                        @endphp
                        <div class="bg-white rounded-2xl border border-gray-200/80 p-4 shadow-xs space-y-3">
                            <div class="flex items-start justify-between gap-2 border-b border-gray-100 pb-2.5">
                                <div>
                                    <h3 class="font-bold text-sm text-gray-900">{{ $user->name }}</h3>
                                    <p class="font-mono text-xs text-indigo-600 font-semibold mt-0.5">{{ $user->nip ? 'NIP: ' . $user->nip : 'NIP: -' }}</p>
                                </div>
                                <span class="px-2.5 py-1 text-[11px] font-extrabold rounded-full shrink-0 uppercase tracking-wider border shadow-2xs {{ $badgeClass }}">
                                    {{ $roleLabel }}
                                </span>
                            </div>

                            <div class="space-y-1.5 text-xs text-gray-700">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-gray-400 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                                    </svg>
                                    <div>
                                        <span class="font-bold text-gray-800">{{ $user->jabatan ?? '-' }}</span>
                                        @if($user->pangkat)
                                            <span class="text-gray-500"> ({{ $user->pangkat }})</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 text-gray-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-gray-400 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                        <polyline points="22,6 12,13 2,6"/>
                                    </svg>
                                    <span class="font-medium">{{ $user->email }}</span>
                                </div>

                                <div class="flex items-center gap-2 text-gray-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-gray-400 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="4" y="2" width="16" height="20" rx="2" ry="2"/>
                                        <path d="M9 22v-4h6v4"/>
                                        <path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/>
                                        <path d="M12 10h.01"/><path d="M12 14h.01"/>
                                        <path d="M16 10h.01"/><path d="M16 14h.01"/>
                                        <path d="M8 10h.01"/><path d="M8 14h.01"/>
                                    </svg>
                                    <span class="font-medium">{{ $user->department ?? '-' }}</span>
                                </div>
                            </div>

                            <div class="pt-2.5 border-t border-gray-100 flex items-center justify-end gap-2">
                                <a href="{{ route('admin_app.users.edit', $user->slug) }}" 
                                   class="flex-1 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-xl shadow-xs transition text-center flex items-center justify-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                    <span>Edit</span>
                                </a>
                                
                                @if($user->id !== auth()->id())
                                    <button type="button" 
                                            @click="selectedUser = initialUsers[{{ $index }}]; showDeleteModal = true" 
                                            class="flex-1 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl shadow-xs transition text-center flex items-center justify-center gap-1.5"
                                            aria-label="Hapus Akun User">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"/>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                        </svg>
                                        <span>Hapus</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="bg-white rounded-2xl border border-gray-200/80 p-6 text-center text-gray-500 text-xs">
                            Data user/pegawai tidak ditemukan.
                        </div>
                    @endforelse
                </div>

                {{-- HASIL PENCARIAN / SORTING REAL-TIME (ALPINE) --}}
                <div x-show="isFiltered" x-cloak class="space-y-3">
                    <template x-for="user in users" :key="user.id">
                        <div class="bg-white rounded-2xl border border-gray-200/80 p-4 shadow-xs space-y-3">
                            <div class="flex items-start justify-between gap-2 border-b border-gray-100 pb-2.5">
                                <div>
                                    <h3 class="font-bold text-sm text-gray-900" x-text="user.name"></h3>
                                    <p class="font-mono text-xs text-indigo-600 font-semibold mt-0.5" x-text="user.nip ? 'NIP: ' + user.nip : 'NIP: -'"></p>
                                </div>
                                
                                <span class="px-2.5 py-1 text-[11px] font-extrabold rounded-full shrink-0 uppercase tracking-wider border shadow-2xs"
                                    :class="{
                                        'bg-purple-100 text-purple-800 border-purple-200': user.role === 'admin_app',
                                        'bg-amber-100 text-amber-800 border-amber-200': user.role === 'admin_lap',
                                        'bg-emerald-100 text-emerald-800 border-emerald-200': user.role === 'approver',
                                        'bg-red-100 text-red-800 border-red-200': user.role === 'driver',
                                        'bg-sky-100 text-sky-800 border-sky-200': user.role === 'pegawai'
                                    }">
                                    <span x-text="
                                        user.role === 'admin_app' ? 'ADMIN APP' :
                                        (user.role === 'admin_lap' ? 'ADMIN LAP' :
                                        (user.role === 'approver' ? 'APPROVER' :
                                        (user.role === 'driver' ? 'DRIVER' : 'PEGAWAI')))
                                    "></span>
                                </span>
                            </div>

                            <div class="space-y-1.5 text-xs text-gray-700">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-gray-400 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                                    </svg>
                                    <div>
                                        <span class="font-bold text-gray-800" x-text="user.jabatan ?? '-'"></span>
                                        <span class="text-gray-500" x-text="user.pangkat ? ' (' + user.pangkat + ')' : ''"></span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 text-gray-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-gray-400 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                        <polyline points="22,6 12,13 2,6"/>
                                    </svg>
                                    <span class="font-medium" x-text="user.email"></span>
                                </div>

                                <div class="flex items-center gap-2 text-gray-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-gray-400 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="4" y="2" width="16" height="20" rx="2" ry="2"/>
                                        <path d="M9 22v-4h6v4"/>
                                        <path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/>
                                        <path d="M12 10h.01"/><path d="M12 14h.01"/>
                                        <path d="M16 10h.01"/><path d="M16 14h.01"/>
                                        <path d="M8 10h.01"/><path d="M8 14h.01"/>
                                    </svg>
                                    <span class="font-medium" x-text="user.department ?? '-'"></span>
                                </div>
                            </div>

                            <div class="pt-2.5 border-t border-gray-100 flex items-center justify-end gap-2">
                                <a :href="`/admin-app/users/${user.slug}/edit`" 
                                   class="flex-1 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-xl shadow-xs transition text-center flex items-center justify-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                    <span>Edit</span>
                                </a>
                                
                                <template x-if="user.id !== {{ auth()->id() }}">
                                    <button type="button" 
                                            @click="selectedUser = user; showDeleteModal = true" 
                                            class="flex-1 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl shadow-xs transition text-center flex items-center justify-center gap-1.5"
                                            aria-label="Hapus Akun User">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"/>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                        </svg>
                                        <span>Hapus</span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>

                    <div x-show="users.length === 0" class="bg-white rounded-2xl border border-gray-200/80 p-6 text-center text-gray-500 text-xs">
                        Data user/pegawai tidak ditemukan.
                    </div>
                </div>
            </div>

            {{-- 2. TAMPILAN TABEL DESKTOP --}}
            <div class="hidden md:block bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b bg-gray-50 text-xs font-semibold text-gray-600 uppercase">
                                <th class="p-3">Nama Lengkap</th>
                                <th class="p-3">NIP</th>
                                <th class="p-3">Pangkat / Jabatan</th>
                                <th class="p-3">Email Dinas</th>
                                <th class="p-3">Seksi / Subbagian</th>
                                <th class="p-3">Role</th>
                                <th class="p-3 text-center">Aksi</th>
                            </tr>
                        </thead>

                        {{-- DATA AWAL (SSR BLADE - ZERO CLS) --}}
                        <tbody x-show="!isFiltered" class="divide-y divide-gray-200 text-sm">
                            @forelse($users as $index => $user)
                                @php
                                    $badgeClass = match($user->role) {
                                        'admin_app' => 'bg-purple-100 text-purple-800 border-purple-200',
                                        'admin_lap' => 'bg-amber-100 text-amber-800 border-amber-200',
                                        'approver'  => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                        'driver'    => 'bg-red-100 text-red-800 border-red-200',
                                        default     => 'bg-sky-100 text-sky-800 border-sky-200',
                                    };
                                    $roleLabel = match($user->role) {
                                        'admin_app' => 'ADMIN APP',
                                        'admin_lap' => 'ADMIN LAP',
                                        'approver'  => 'APPROVER',
                                        'driver'    => 'DRIVER',
                                        default     => 'PEGAWAI',
                                    };
                                @endphp
                                <tr class="hover:bg-gray-50/80 transition-colors">
                                    <td class="p-3 font-medium text-gray-900">{{ $user->name }}</td>
                                    <td class="p-3 font-mono text-xs text-indigo-600 font-semibold">{{ $user->nip ?? '-' }}</td>
                                    <td class="p-3 text-gray-600 text-xs">
                                        <div class="font-medium text-gray-800">{{ $user->jabatan ?? '-' }}</div>
                                        <div class="text-gray-500">{{ $user->pangkat ?? '' }}</div>
                                    </td>
                                    <td class="p-3 text-gray-600">{{ $user->email }}</td>
                                    <td class="p-3 text-gray-600">{{ $user->department ?? '-' }}</td>

                                    <td class="p-3 whitespace-nowrap">
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-full border shadow-2xs {{ $badgeClass }}">
                                            {{ $roleLabel }}
                                        </span>
                                    </td>

                                    <td class="p-3 text-center whitespace-nowrap">
                                        <div class="inline-flex items-center justify-center gap-1.5">
                                            <a href="{{ route('admin_app.users.edit', $user->slug) }}" 
                                               class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-lg shadow-2xs transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                                </svg>
                                                <span>Edit</span>
                                            </a>
                                            
                                            @if($user->id !== auth()->id())
                                                <button type="button" 
                                                        @click="selectedUser = initialUsers[{{ $index }}]; showDeleteModal = true" 
                                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-lg shadow-2xs transition"
                                                        aria-label="Hapus Akun User">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="3 6 5 6 21 6"/>
                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                                    </svg>
                                                    <span>Hapus</span>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="p-4 text-center text-gray-500 text-xs">
                                        Data user/pegawai tidak ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                        {{-- HASIL PENCARIAN / SORTING REAL-TIME (ALPINE) --}}
                        <tbody x-show="isFiltered" x-cloak class="divide-y divide-gray-200 text-sm">
                            <template x-for="user in users" :key="user.id">
                                <tr class="hover:bg-gray-50/80 transition-colors">
                                    <td class="p-3 font-medium text-gray-900" x-text="user.name"></td>
                                    <td class="p-3 font-mono text-xs text-indigo-600 font-semibold" x-text="user.nip ?? '-'"></td>
                                    <td class="p-3 text-gray-600 text-xs">
                                        <div class="font-medium text-gray-800" x-text="user.jabatan ?? '-'"></div>
                                        <div class="text-gray-500" x-text="user.pangkat ?? ''"></div>
                                    </td>
                                    <td class="p-3 text-gray-600" x-text="user.email"></td>
                                    <td class="p-3 text-gray-600" x-text="user.department ?? '-'"></td>

                                    <td class="p-3 whitespace-nowrap">
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-full border shadow-2xs"
                                            :class="{
                                                'bg-purple-100 text-purple-800 border-purple-200': user.role === 'admin_app',
                                                'bg-amber-100 text-amber-800 border-amber-200': user.role === 'admin_lap',
                                                'bg-emerald-100 text-emerald-800 border-emerald-200': user.role === 'approver',
                                                'bg-red-100 text-red-800 border-red-200': user.role === 'driver',
                                                'bg-sky-100 text-sky-800 border-sky-200': user.role === 'pegawai'
                                            }">
                                            <span x-text="
                                                user.role === 'admin_app' ? 'ADMIN APP' :
                                                (user.role === 'admin_lap' ? 'ADMIN LAP' :
                                                (user.role === 'approver' ? 'APPROVER' :
                                                (user.role === 'driver' ? 'DRIVER' : 'PEGAWAI')))
                                            "></span>
                                        </span>
                                    </td>

                                    <td class="p-3 text-center whitespace-nowrap">
                                        <div class="inline-flex items-center justify-center gap-1.5">
                                            <a :href="`/admin-app/users/${user.slug}/edit`" 
                                               class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-lg shadow-2xs transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                                </svg>
                                                <span>Edit</span>
                                            </a>
                                            
                                            <template x-if="user.id !== {{ auth()->id() }}">
                                                <button type="button" 
                                                        @click="selectedUser = user; showDeleteModal = true" 
                                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-lg shadow-2xs transition"
                                                        aria-label="Hapus Akun User">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="3 6 5 6 21 6"/>
                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                                    </svg>
                                                    <span>Hapus</span>
                                                </button>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <tr x-show="users.length === 0">
                                <td colspan="7" class="p-4 text-center text-gray-500 text-xs">
                                    Data user/pegawai tidak ditemukan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- PAGINASI --}}
            <div class="mt-4" x-show="search === ''">
                {{ $users->links() }}
            </div>
        </div>

        

            {{-- MODAL KONFIRMASI HAPUS USER --}}
            <div x-show="showDeleteModal" 
                class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50" 
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                x-cloak>
                
                <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full overflow-hidden" @click.away="showDeleteModal = false">
                    <div class="bg-rose-600 px-6 py-4 text-white flex justify-between items-center">
                        <h3 class="font-bold text-base flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-white stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"/>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                            </svg>
                            <span>Konfirmasi Hapus Akun User</span>
                        </h3>
                        <button type="button" @click="showDeleteModal = false" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition" aria-label="Tutup Modal Hapus">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"/>
                                <line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="flex items-start gap-3 bg-rose-50 p-3.5 rounded-xl border border-rose-200 text-rose-800 text-xs">
                            <svg class="w-5 h-5 flex-shrink-0 text-rose-600 stroke-2 shrink-0 mt-0.5 fill-none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/>
                                <line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                            <p>
                                <strong>Tindakan ini tidak dapat dibatalkan!</strong> Seluruh riwayat dan hak akses pengguna ini akan dihapus dari sistem.
                            </p>
                        </div>

                        <p class="text-xs text-gray-500">Berikut adalah ringkasan data pengguna yang akan dihapus:</p>
                        
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 text-sm space-y-2.5">
                            <div class="flex justify-between border-b border-gray-200/60 pb-1.5">
                                <span class="text-gray-500 text-xs">Nama Lengkap:</span>
                                <span class="font-bold text-gray-900" x-text="selectedUser ? selectedUser.name : '-'"></span>
                            </div>
                            <div class="flex justify-between border-b border-gray-200/60 pb-1.5">
                                <span class="text-gray-500 text-xs">NIP:</span>
                                <span class="font-mono text-xs font-semibold text-indigo-600" x-text="selectedUser && selectedUser.nip ? selectedUser.nip : '-'"></span>
                            </div>
                            <div class="flex justify-between border-b border-gray-200/60 pb-1.5">
                                <span class="text-gray-500 text-xs">Pangkat / Jabatan:</span>
                                <span class="font-medium text-gray-800 text-right text-xs" x-text="selectedUser ? ((selectedUser.jabatan || '-') + (selectedUser.pangkat ? ' (' + selectedUser.pangkat + ')' : '')) : '-'"></span>
                            </div>
                            <div class="flex justify-between border-b border-gray-200/60 pb-1.5">
                                <span class="text-gray-500 text-xs">Email Dinas:</span>
                                <span class="font-medium text-gray-800 text-xs" x-text="selectedUser ? selectedUser.email : '-'"></span>
                            </div>
                            <div class="flex justify-between border-b border-gray-200/60 pb-1.5">
                                <span class="text-gray-500 text-xs">Seksi / Subbagian:</span>
                                <span class="font-medium text-gray-800 text-xs" x-text="selectedUser && selectedUser.department ? selectedUser.department : '-'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 text-xs">Role / Hak Akses:</span>
                                <span class="font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded-lg text-xs border border-rose-200" 
                                    x-text="selectedUser ? (
                                        selectedUser.role === 'admin_app' ? 'Admin Aplikasi' :
                                        (selectedUser.role === 'admin_lap' ? 'Admin Lapangan' :
                                        (selectedUser.role === 'approver' ? 'Approver' :
                                        (selectedUser.role === 'driver' ? 'Driver' : 'Pegawai')))
                                    ) : '-'"></span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <button type="button" 
                                    @click="showDeleteModal = false" 
                                    class="w-1/2 py-2.5 bg-gray-100 text-gray-700 text-sm font-bold rounded-xl hover:bg-gray-200 transition">
                                Batal
                            </button>
                            
                            <form :action="'/admin-app/users/' + (selectedUser ? selectedUser.slug : '')" method="POST" class="w-1/2">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="w-full py-2.5 bg-rose-600 text-white text-sm font-bold rounded-xl hover:bg-rose-700 shadow-md transition flex items-center justify-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                    </svg>
                                    <span>Ya, Hapus Akun</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {{-- MODAL 1: PILIH BERKAS EXCEL / CSV UNTUK DIBACA --}}
            <div x-show="showImportModal" 
                class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 z-50" 
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                x-cloak>
                
                <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full overflow-hidden max-h-[90vh] flex flex-col" @click.away="showImportModal = false">
                    <div class="bg-emerald-600 px-4 py-3.5 sm:px-6 sm:py-4 text-white flex justify-between items-center shrink-0">
                        <h3 class="font-bold text-sm sm:text-base flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-white stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <path d="M12 18v-6"/>
                                <path d="m9 15 3-3 3 3"/>
                            </svg>
                            <span>Import Data Pegawai (Excel / CSV)</span>
                        </h3>
                        <button type="button" @click="showImportModal = false" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition" aria-label="Tutup Modal Import">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"/>
                                <line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="parseImportFile()" class="p-4 sm:p-6 space-y-4 overflow-y-auto flex-1">
                        {{-- LANGKAH 1: UNDUH TEMPLATE EXCEL --}}
                        <div class="p-3.5 bg-emerald-50/80 border border-emerald-200 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="text-xs text-emerald-950 space-y-0.5">
                                <p class="font-bold">1. Unduh Template Excel (.xlsx)</p>
                                <p class="text-[11px] text-emerald-800">Isi data pegawai pada template resmi (gunakan <strong>Paste Values</strong> saat menyalin dari file lain).</p>
                            </div>
                            <a href="{{ route('admin_app.users.import.template') }}" 
                               class="inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-white hover:bg-emerald-100 text-emerald-700 border border-emerald-300 rounded-lg text-xs font-bold shadow-2xs transition shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="7 10 12 15 17 10"/>
                                    <line x1="12" y1="15" x2="12" y2="3"/>
                                </svg>
                                <span>Template Excel</span>
                            </a>
                        </div>

                        {{-- LANGKAH 2: MODE PENANGANAN DATA GANDA --}}
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-gray-700">2. Jika Email / NIP Sudah Terdaftar:</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs">
                                <label class="flex items-start gap-2 p-2.5 rounded-xl border cursor-pointer transition"
                                       :class="importMode === 'skip' ? 'border-emerald-500 bg-emerald-50/40' : 'border-gray-200 hover:bg-gray-50'">
                                    <input type="radio" x-model="importMode" value="skip" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                                    <div>
                                        <span class="font-bold text-gray-800 block">Lewati (Skip)</span>
                                        <span class="text-[11px] text-gray-500">Hanya masukkan akun baru.</span>
                                    </div>
                                </label>
                                <label class="flex items-start gap-2 p-2.5 rounded-xl border cursor-pointer transition"
                                       :class="importMode === 'update' ? 'border-emerald-500 bg-emerald-50/40' : 'border-gray-200 hover:bg-gray-50'">
                                    <input type="radio" x-model="importMode" value="update" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                                    <div>
                                        <span class="font-bold text-gray-800 block">Perbarui Data</span>
                                        <span class="text-[11px] text-gray-500">Timpa profil dengan data baru.</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- LANGKAH 3: PILIH FILE EXCEL / CSV --}}
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-gray-700">
                                3. Pilih Berkas Excel (.xlsx) atau CSV <span class="text-rose-500">*</span>
                            </label>
                            <label class="flex flex-col items-center justify-center w-full p-4 border-2 border-dashed border-gray-300 hover:border-emerald-400 rounded-xl bg-gray-50/60 hover:bg-emerald-50/30 cursor-pointer transition text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-gray-400 mb-1.5 fill-none stroke-current stroke-1.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="17 8 12 3 7 8"/>
                                    <line x1="12" y1="3" x2="12" y2="15"/>
                                </svg>
                                <span class="text-xs font-bold text-gray-700 break-all" x-text="selectedImportName || 'Klik untuk memilih berkas .xlsx atau .csv (Maks. 5 MB)'"></span>
                                <span class="text-[11px] text-gray-400 mt-0.5" x-show="!selectedImportName">Data akan ditampilkan terlebih dahulu untuk diseleksi sebelum disimpan</span>
                                <input type="file" 
                                       x-ref="importFileInput"
                                       accept=".xlsx,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv" 
                                       required 
                                       class="hidden"
                                       @change="selectedImportName = $event.target.files[0] ? $event.target.files[0].name : ''; previewError = ''">
                            </label>

                            <template x-if="previewError">
                                <p class="text-[11px] font-bold text-rose-600 mt-1" x-text="previewError"></p>
                            </template>

                            @error('import_file')
                                <p class="text-[11px] font-bold text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- TOMBOL AKSI --}}
                        <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
                            <button type="button" 
                                    @click="showImportModal = false" 
                                    class="w-1/2 py-2.5 bg-gray-100 text-gray-700 text-xs sm:text-sm font-bold rounded-xl hover:bg-gray-200 transition">
                                Batal
                            </button>
                            <button type="submit" 
                                    :disabled="isParsingImport"
                                    :class="{ 'opacity-60 cursor-not-allowed': isParsingImport }"
                                    class="w-1/2 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs sm:text-sm font-bold rounded-xl shadow-md transition flex items-center justify-center gap-1.5 cursor-pointer">
                                <span x-text="isParsingImport ? 'Membaca Berkas...' : 'Baca & Pratinjau'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- MODAL 2: PRATINJAU & SELEKSI DAFTAR PENGGUNA SEBELUM DISIMPAN (RESPONSIVE MOBILE & DESKTOP) --}}
            <div x-show="showPreviewModal" 
                class="fixed inset-0 bg-black/65 backdrop-blur-xs flex items-center justify-center p-2 sm:p-4 z-50" 
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                x-cloak>
                
                <div class="bg-white rounded-2xl shadow-2xl max-w-5xl w-full overflow-hidden max-h-[90vh] flex flex-col">
                    {{-- Header Modal Pratinjau --}}
                    <div class="bg-indigo-600 px-4 py-3 sm:px-6 sm:py-4 text-white flex justify-between items-start sm:items-center gap-2 shrink-0">
                        <div class="min-w-0">
                            <h3 class="font-bold text-sm sm:text-base flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-white stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9 11l3 3L22 4"/>
                                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                                </svg>
                                <span class="truncate">Seleksi Daftar Pengguna Import</span>
                            </h3>
                            <p class="text-[11px] sm:text-xs text-indigo-100 mt-0.5 truncate">
                                Berkas: <span class="font-mono font-bold" x-text="previewFileName"></span> • Hilangkan centang pada pengguna yang batal.
                            </p>
                        </div>
                        <button type="button" @click="showPreviewModal = false" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition shrink-0" aria-label="Tutup Modal Pratinjau">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"/>
                                <line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Ringkasan Statistik & Kontrol Filter --}}
                    <div class="px-4 py-3 sm:px-6 sm:py-3.5 bg-gray-50 border-b border-gray-200 space-y-2.5 sm:space-y-3 shrink-0">
                        <div class="flex flex-wrap items-center justify-between gap-1.5 text-[11px] sm:text-xs">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg bg-white border border-gray-200 font-bold text-gray-700">
                                    Total: <span x-text="previewRows.length"></span>
                                </span>
                                <span class="px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg bg-emerald-50 border border-emerald-200 font-bold text-emerald-700">
                                    Baru: <span x-text="previewRows.filter(r => r.status === 'new').length"></span>
                                </span>
                                <span class="px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg bg-sky-50 border border-sky-200 font-bold text-sky-700">
                                    Update: <span x-text="previewRows.filter(r => r.status === 'update').length"></span>
                                </span>
                                <span class="px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg bg-rose-50 border border-rose-200 font-bold text-rose-700">
                                    Error/Skip: <span x-text="previewRows.filter(r => ['skip', 'invalid'].includes(r.status)).length"></span>
                                </span>
                            </div>

                            <span class="px-2.5 py-0.5 sm:px-3 sm:py-1 rounded-lg bg-indigo-600 text-white font-bold">
                                Terpilih: <span x-text="selectedPreviewCount"></span>
                            </span>
                        </div>

                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2">
                            {{-- Filter Tab --}}
                            <div class="flex items-center gap-1.5 text-[11px] sm:text-xs overflow-x-auto pb-0.5 sm:pb-0">
                                <button type="button" @click="previewFilter = 'all'"
                                        :class="previewFilter === 'all' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-100'"
                                        class="px-2.5 py-1.5 rounded-lg font-bold transition shrink-0 cursor-pointer">
                                    Semua
                                </button>
                                <button type="button" @click="previewFilter = 'selected'"
                                        :class="previewFilter === 'selected' ? 'bg-emerald-600 text-white' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-100'"
                                        class="px-2.5 py-1.5 rounded-lg font-bold transition shrink-0 cursor-pointer">
                                    Terpilih (<span x-text="selectedPreviewCount"></span>)
                                </button>
                                <button type="button" @click="previewFilter = 'issue'"
                                        :class="previewFilter === 'issue' ? 'bg-rose-600 text-white' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-100'"
                                        class="px-2.5 py-1.5 rounded-lg font-bold transition shrink-0 cursor-pointer">
                                    Dilewati / Error
                                </button>
                            </div>

                            {{-- Cari Cepat di Dalam Pratinjau --}}
                            <input type="text"
                                   x-model="previewSearch"
                                   placeholder="Filter nama, NIP, atau email..."
                                   class="py-1.5 px-3 text-xs border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 w-full sm:w-64">
                        </div>
                    </div>

                    {{-- AREA DAFTAR PENGGUNA (SCROLLABLE) --}}
                    <div class="overflow-y-auto flex-1 min-h-0">
                        
                        {{-- A. TAMPILAN KARTU MOBILE (< md) --}}
                        <div class="block md:hidden p-3 space-y-2.5 bg-gray-50/50">
                            {{-- Bar Pilih Semua Mobile --}}
                            <div class="bg-white px-3.5 py-2.5 rounded-xl border border-gray-200 flex items-center justify-between">
                                <label class="inline-flex items-center gap-2 text-xs font-bold text-gray-800 cursor-pointer">
                                    <input type="checkbox"
                                           :checked="allValidSelected"
                                           :disabled="validPreviewRows.length === 0"
                                           @change="toggleAllValid($event.target.checked)"
                                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span>Pilih Semua Data Valid</span>
                                </label>
                                <span class="text-[11px] font-semibold text-gray-500" x-text="`${validPreviewRows.length} valid`"></span>
                            </div>

                            <template x-for="row in filteredPreviewRows" :key="row.row_number">
                                <div @click="if (['new', 'update'].includes(row.status)) row.selected = !row.selected"
                                     :class="{
                                         'border-indigo-500 bg-indigo-50/30 ring-1 ring-indigo-500/20': row.selected,
                                         'border-gray-200 bg-white': !row.selected && ['new', 'update'].includes(row.status),
                                         'border-gray-200 bg-gray-100/80 opacity-80': ['skip', 'invalid'].includes(row.status)
                                     }"
                                     class="rounded-xl border p-3.5 shadow-2xs space-y-2.5 transition">
                                    
                                    {{-- Header Kartu: Checkbox, Baris Excel, & Badge Status --}}
                                    <div class="flex items-center justify-between gap-2 border-b border-gray-100 pb-2">
                                        <div class="flex items-center gap-2.5" @click.stop>
                                            <input type="checkbox"
                                                   x-model="row.selected"
                                                   :disabled="!['new', 'update'].includes(row.status)"
                                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 disabled:opacity-40">
                                            <span class="font-mono text-[11px] font-bold text-gray-500 bg-gray-100 px-2 py-0.5 rounded" x-text="'Baris #' + row.row_number"></span>
                                            <span class="px-2 py-0.5 text-[10px] font-extrabold uppercase rounded-full bg-sky-100 text-sky-800 border border-sky-200" x-text="row.role"></span>
                                        </div>

                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full border shrink-0"
                                              :class="{
                                                  'bg-emerald-50 text-emerald-700 border-emerald-200': row.status === 'new',
                                                  'bg-sky-50 text-sky-700 border-sky-200': row.status === 'update',
                                                  'bg-amber-50 text-amber-700 border-amber-200': row.status === 'skip',
                                                  'bg-rose-50 text-rose-700 border-rose-200': row.status === 'invalid'
                                              }"
                                              x-text="
                                                  row.status === 'new' ? 'BARU' :
                                                  (row.status === 'update' ? 'PERBARUI' :
                                                  (row.status === 'skip' ? 'DILEWATI' : 'TIDAK VALID'))
                                              "></span>
                                    </div>

                                    {{-- Identitas Utama --}}
                                    <div>
                                        <h4 class="font-bold text-xs text-gray-900" x-text="row.name || '(Nama Kosong)'"></h4>
                                        <p class="font-mono text-[11px] text-indigo-600 font-semibold mt-0.5" x-text="row.nip ? 'NIP: ' + row.nip : 'Tanpa NIP'"></p>
                                    </div>

                                    {{-- Rincian Kontak & Unit --}}
                                    <div class="space-y-1 text-[11px] text-gray-600">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-gray-400">Email:</span>
                                            <span class="font-medium text-gray-800 truncate" x-text="row.email || '(Email Kosong)'"></span>
                                        </div>
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-gray-400">No. HP:</span>
                                            <span class="font-mono text-gray-700" x-text="row.phone || '-'"></span>
                                        </div>
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-gray-400">Jabatan:</span>
                                            <span class="font-medium text-gray-800 text-right truncate" x-text="row.jabatan || '-'"></span>
                                        </div>
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-gray-400">Unit Kerja:</span>
                                            <span class="text-gray-700 text-right truncate" x-text="row.department || '-'"></span>
                                        </div>
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-gray-400">Password:</span>
                                            <span class="text-gray-700" x-text="row.password_info"></span>
                                        </div>
                                    </div>

                                    {{-- Keterangan Status / Error --}}
                                    <div class="pt-1.5 border-t border-gray-100 text-[11px] leading-snug"
                                         :class="row.status === 'invalid' ? 'text-rose-600 font-semibold' : (row.status === 'skip' ? 'text-amber-700' : 'text-gray-500')"
                                         x-text="row.status_reason">
                                    </div>
                                </div>
                            </template>

                            <div x-show="filteredPreviewRows.length === 0" class="bg-white rounded-xl border border-gray-200 p-6 text-center text-xs text-gray-500">
                                Tidak ada baris data yang cocok dengan filter saat ini.
                            </div>
                        </div>

                        {{-- B. TAMPILAN TABEL DESKTOP (>= md) --}}
                        <div class="hidden md:block overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead class="bg-gray-100 text-gray-600 uppercase font-bold sticky top-0 z-10 border-b border-gray-200">
                                    <tr>
                                        <th class="p-3 w-10 text-center">
                                            <input type="checkbox"
                                                   :checked="allValidSelected"
                                                   :disabled="validPreviewRows.length === 0"
                                                   @change="toggleAllValid($event.target.checked)"
                                                   title="Pilih / Batalkan Semua yang Valid"
                                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                                        </th>
                                        <th class="p-3 w-20">Baris</th>
                                        <th class="p-3">Nama & NIP</th>
                                        <th class="p-3">Email & No. HP</th>
                                        <th class="p-3">Jabatan & Unit Kerja</th>
                                        <th class="p-3">Role & Password</th>
                                        <th class="p-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <template x-for="row in filteredPreviewRows" :key="row.row_number">
                                        <tr @click="if (['new', 'update'].includes(row.status)) row.selected = !row.selected"
                                            :class="{
                                                'bg-indigo-50/40 hover:bg-indigo-50/70 cursor-pointer': row.selected,
                                                'bg-white hover:bg-gray-50 cursor-pointer': !row.selected && ['new', 'update'].includes(row.status),
                                                'bg-gray-50/80 text-gray-400': ['skip', 'invalid'].includes(row.status)
                                            }"
                                            class="transition-colors">
                                            <td class="p-3 text-center" @click.stop>
                                                <input type="checkbox"
                                                       x-model="row.selected"
                                                       :disabled="!['new', 'update'].includes(row.status)"
                                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 disabled:opacity-40 cursor-pointer">
                                            </td>
                                            <td class="p-3 font-mono text-gray-500" x-text="'#' + row.row_number"></td>
                                            <td class="p-3">
                                                <div class="font-bold text-gray-900" x-text="row.name || '(Nama Kosong)'"></div>
                                                <div class="font-mono text-[11px] text-indigo-600" x-text="row.nip ? 'NIP: ' + row.nip : 'Tanpa NIP'"></div>
                                            </td>
                                            <td class="p-3">
                                                <div class="font-medium text-gray-800" x-text="row.email || '(Email Kosong)'"></div>
                                                <div class="text-[11px] text-gray-500" x-text="row.phone || '-'"></div>
                                            </td>
                                            <td class="p-3">
                                                <div class="font-medium text-gray-800" x-text="row.jabatan || '-'"></div>
                                                <div class="text-[11px] text-gray-500" x-text="row.department || '-'"></div>
                                            </td>
                                            <td class="p-3 whitespace-nowrap">
                                                <span class="px-2 py-0.5 text-[10px] font-extrabold uppercase rounded-full bg-sky-100 text-sky-800 border border-sky-200"
                                                      x-text="row.role"></span>
                                                <div class="text-[10px] text-gray-500 mt-1" x-text="row.password_info"></div>
                                            </td>
                                            <td class="p-3">
                                                <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded-full border"
                                                      :class="{
                                                          'bg-emerald-50 text-emerald-700 border-emerald-200': row.status === 'new',
                                                          'bg-sky-50 text-sky-700 border-sky-200': row.status === 'update',
                                                          'bg-amber-50 text-amber-700 border-amber-200': row.status === 'skip',
                                                          'bg-rose-50 text-rose-700 border-rose-200': row.status === 'invalid'
                                                      }"
                                                      x-text="
                                                          row.status === 'new' ? 'BARU' :
                                                          (row.status === 'update' ? 'PERBARUI' :
                                                          (row.status === 'skip' ? 'DILEWATI' : 'TIDAK VALID'))
                                                      "></span>
                                                <p class="text-[11px] mt-1 leading-snug"
                                                   :class="row.status === 'invalid' ? 'text-rose-600 font-semibold' : 'text-gray-500'"
                                                   x-text="row.status_reason"></p>
                                            </td>
                                        </tr>
                                    </template>

                                    <tr x-show="filteredPreviewRows.length === 0">
                                        <td colspan="7" class="p-6 text-center text-gray-500">
                                            Tidak ada baris data yang cocok dengan filter saat ini.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Footer Modal Pratinjau & Form Submit Akhir --}}
                    <div class="px-4 py-3 sm:px-6 sm:py-4 bg-gray-50 border-t border-gray-200 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-2.5 shrink-0">
                        <button type="button"
                                @click="showPreviewModal = false; showImportModal = true;"
                                class="w-full sm:w-auto px-4 py-2.5 bg-white hover:bg-gray-100 text-gray-700 border border-gray-300 text-xs font-bold rounded-xl transition text-center cursor-pointer">
                            &larr; Ganti Berkas
                        </button>

                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <button type="button"
                                    @click="showPreviewModal = false"
                                    class="w-1/3 sm:w-auto px-4 sm:px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold rounded-xl transition cursor-pointer">
                                Batal
                            </button>

                            <form action="{{ route('admin_app.users.import') }}"
                                  method="POST"
                                  @submit="isSavingImport = true"
                                  class="w-2/3 sm:w-auto">
                                @csrf
                                <input type="hidden" name="import_mode" :value="importMode">
                                <input type="hidden" name="rows_json" :value="JSON.stringify(previewRows.filter(r => r.selected))">

                                <button type="submit"
                                        :disabled="selectedPreviewCount === 0 || isSavingImport"
                                        :class="{ 'opacity-50 cursor-not-allowed': selectedPreviewCount === 0 || isSavingImport }"
                                        class="w-full sm:w-auto px-4 sm:px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md transition flex items-center justify-center gap-1.5 cursor-pointer">
                                    <span x-text="isSavingImport ? 'Menyimpan...' : `Simpan (${selectedPreviewCount}) Terpilih`"></span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

    </div>
</x-app-layout>