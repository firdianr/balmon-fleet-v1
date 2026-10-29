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
            users: {{ json_encode($users->items()) }}, 
            showDeleteModal: false,
            selectedUser: null,
            isLoading: false,
            fetchUsers() {
                this.isLoading = true;
                fetch(`/api/search/users?q=${encodeURIComponent(this.search)}&sort=${this.sort}`)
                    .then(res => res.json())
                    .then(data => {
                        this.users = data.data;
                        this.isLoading = false;
                    })
                    .catch(() => { this.isLoading = false; });
            }
         }"
         x-init="$watch('search', value => fetchUsers());$watch('sort', () => fetchUsers())">
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            
            {{-- AREA SEARCH & DROPDOWN SORTING (STACK MOBILE, INLINE DESKTOP) --}}
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
                            class="w-full sm:w-auto py-2.5 px-4 text-sm border border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 shadow-2xs bg-white font-semibold text-gray-700 cursor-pointer">
                        <option value="name_asc">Nama (A - Z)</option>
                        <option value="name_desc">Nama (Z - A)</option>
                        <option value="nip_asc">NIP (Terurut)</option>
                        <option value="latest">Terbaru Ditambahkan</option>
                        <option value="oldest">Terlama Ditambahkan</option>
                    </select>
                </div>

                <a href="{{ route('admin_app.users.create') }}" class="w-full md:w-auto px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold text-center shadow-md transition shrink-0 inline-flex items-center justify-center gap-2">
                    <svg class="w-4 h-4 text-white shrink-0 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    <span>Tambah User / Pegawai</span>
                </a>
            </div>

            {{-- 1. TAMPILAN KARTU SMARTPHONE / MOBILE (block md:hidden) --}}
            <div class="block md:hidden space-y-3">
                <template x-for="user in users" :key="user.id">
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-4 shadow-xs space-y-3">
                        <div class="flex items-start justify-between gap-2 border-b border-gray-100 pb-2.5">
                            <div>
                                <h3 class="font-bold text-sm text-gray-900" x-text="user.name"></h3>
                                <p class="font-mono text-xs text-indigo-600 font-semibold mt-0.5" x-text="user.nip ? 'NIP: ' + user.nip : 'NIP: -'"></p>
                            </div>
                            
                            {{-- BADGE ROLE WARNA-WARNI MOBILE --}}
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

                        {{-- AKSI MOBILE --}}
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
                                        class="flex-1 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl shadow-xs transition text-center flex items-center justify-center gap-1.5">
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

            {{-- 2. TAMPILAN TABEL DESKTOP (hidden md:block) --}}
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
                        <tbody class="divide-y divide-gray-200 text-sm">
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
                                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-lg shadow-2xs transition">
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
                        <button type="button" @click="showDeleteModal = false" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">
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

        </div>
    </div>
</x-app-layout>