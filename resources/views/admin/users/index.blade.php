<x-app-layout>
    <x-slot name="title">Kelola Data & Akun Pegawai</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Kelola Akun Pegawai') }}
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
        
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                {{-- AREA SEARCH & DROPDOWN SORTING --}}
                <div class="mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
                    
                    <div class="flex items-center gap-3 w-full max-w-xl">
                        {{-- KOTAK PENCARIAN REAL-TIME --}}
                        <div class="relative w-full">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                🔍
                            </div>
                            <input type="text" 
                                x-model="search" 
                                @input.debounce.300ms="fetchUsers()" 
                                placeholder="Cari Nama, NIP, Email, Seksi..." 
                                class="pl-10 pr-10 py-2 w-full text-sm border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm">
                        </div>

                        {{-- DROPDOWN MODE PENGURUTAN --}}
                        <select x-model="sort" 
                                @change="fetchUsers()" 
                                class="py-2 px-3 text-sm border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm bg-white cursor-pointer">
                            <option value="name_asc">🔤 Nama (A - Z)</option>
                            <option value="name_desc">🔤 Nama (Z - A)</option>
                            <option value="nip_asc">🔢 NIP (Terurut)</option>
                            <option value="latest">🆕 Terbaru Ditambahkan</option>
                            <option value="oldest">⌛ Terlama Ditambahkan</option>
                        </select>
                    </div>

                    <a href="{{ route('admin_app.users.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold hover:bg-indigo-700 transition whitespace-nowrap">
                        + Tambah User / Pegawai
                    </a>
                </div>

                {{-- TABEL USERS --}}
                <div class="text-gray-900 overflow-x-auto">
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
                                    {{-- Nama Lengkap --}}
                                    <td class="p-3 font-medium text-gray-900" x-text="user.name"></td>
                                    
                                    {{-- NIP --}}
                                    <td class="p-3 font-mono text-xs text-indigo-600" x-text="user.nip ?? '-'"></td>

                                    {{-- Pangkat / Jabatan (Data Skema Baru) --}}
                                    <td class="p-3 text-gray-600 text-xs">
                                        <div class="font-medium text-gray-800" x-text="user.jabatan ?? '-'"></div>
                                        <div class="text-gray-500" x-text="user.pangkat ?? ''"></div>
                                    </td>

                                    {{-- Email --}}
                                    <td class="p-3 text-gray-600" x-text="user.email"></td>

                                    {{-- Seksi / Department --}}
                                    <td class="p-3 text-gray-600" x-text="user.department ?? '-'"></td>

                                    {{-- BADGE ROLE WARNA-WARNI --}}
                                    <td class="p-3 whitespace-nowrap">
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-full border shadow-sm"
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

                                    {{-- AKSI --}}
                                    <td class="p-3 text-center whitespace-nowrap">
                                        <div class="inline-flex items-center justify-center gap-1.5">
                                            {{-- Route diarahkan ke admin-app --}}
                                            <a :href="`/admin-app/users/${user.slug}/edit`" 
                                            class="inline-flex items-center px-2.5 py-1.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold rounded shadow-sm transition">
                                                ✏️ Edit
                                            </a>
                                            
                                            {{-- Mencegah menghapus akun sendiri --}}
                                            <template x-if="user.id !== {{ auth()->id() }}">
                                                <button type="button" 
                                                        @click="selectedUser = user; showDeleteModal = true" 
                                                        class="inline-flex items-center px-2.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded shadow-sm transition">
                                                    🗑 Hapus
                                                </button>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <!-- MODAL KONFIRMASI HAPUS USER -->
                            <div x-show="showDeleteModal" 
                                class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50" 
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                x-cloak>
                                
                                <div class="bg-white rounded-xl shadow-xl max-w-lg w-full overflow-hidden" @click.away="showDeleteModal = false">
                                    
                                    {{-- HEADER MODAL --}}
                                    <div class="bg-rose-600 px-6 py-4 text-white flex justify-between items-center">
                                        <h3 class="font-bold text-base flex items-center gap-2">
                                            <span>🗑️</span> Konfirmasi Hapus Akun User
                                        </h3>
                                        <button type="button" @click="showDeleteModal = false" class="text-white/80 hover:text-white text-lg font-bold">&times;</button>
                                    </div>

                                    {{-- BODY MODAL --}}
                                    <div class="p-6 space-y-4">
                                        <div class="flex items-start gap-3 bg-rose-50 p-3 rounded-lg border border-rose-200 text-rose-800 text-xs">
                                            <svg class="w-5 h-5 flex-shrink-0 text-rose-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                            </svg>
                                            <p>
                                                <strong>Tindakan ini tidak dapat dibatalkan!</strong> Seluruh riwayat dan hak akses pengguna ini akan dihapus dari sistem.
                                            </p>
                                        </div>

                                        <p class="text-xs text-gray-500">Berikut adalah ringkasan data pengguna yang akan dihapus:</p>
                                        
                                        {{-- RINGKASAN DATA USER YANG DIHAPUS --}}
                                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 text-sm space-y-2.5">
                                            <div class="flex justify-between border-b pb-1.5">
                                                <span class="text-gray-500 text-xs">Nama Lengkap:</span>
                                                <span class="font-bold text-gray-900" x-text="selectedUser ? selectedUser.name : '-'"></span>
                                            </div>
                                            <div class="flex justify-between border-b pb-1.5">
                                                <span class="text-gray-500 text-xs">NIP:</span>
                                                <span class="font-mono text-xs font-semibold text-indigo-600" x-text="selectedUser && selectedUser.nip ? selectedUser.nip : '-'"></span>
                                            </div>
                                            <div class="flex justify-between border-b pb-1.5">
                                                <span class="text-gray-500 text-xs">Pangkat / Jabatan:</span>
                                                <span class="font-medium text-gray-800 text-right" x-text="selectedUser ? ((selectedUser.jabatan || '-') + (selectedUser.pangkat ? ' (' + selectedUser.pangkat + ')' : '')) : '-'"></span>
                                            </div>
                                            <div class="flex justify-between border-b pb-1.5">
                                                <span class="text-gray-500 text-xs">Email Dinas:</span>
                                                <span class="font-medium text-gray-800" x-text="selectedUser ? selectedUser.email : '-'"></span>
                                            </div>
                                            <div class="flex justify-between border-b pb-1.5">
                                                <span class="text-gray-500 text-xs">Seksi / Subbagian:</span>
                                                <span class="font-medium text-gray-800" x-text="selectedUser && selectedUser.department ? selectedUser.department : '-'"></span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-500 text-xs">Role / Hak Akses:</span>
                                                <span class="font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded text-xs border border-rose-200" 
                                                    x-text="selectedUser ? (
                                                        selectedUser.role === 'admin_app' ? 'Admin Aplikasi' :
                                                        (selectedUser.role === 'admin_lap' ? 'Admin Lapangan' :
                                                        (selectedUser.role === 'approver' ? 'Approver' :
                                                        (selectedUser.role === 'driver' ? 'Driver' : 'Pegawai')))
                                                    ) : '-'"></span>
                                            </div>
                                        </div>

                                        {{-- TOMBOL AKSI MODAL --}}
                                        <div class="flex items-center gap-2 pt-2">
                                            <button type="button" 
                                                    @click="showDeleteModal = false" 
                                                    class="w-1/2 py-2.5 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition">
                                                Batal
                                            </button>
                                            
                                            <form :action="'/admin-app/users/' + (selectedUser ? selectedUser.slug : '')" method="POST" class="w-1/2">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="w-full py-2.5 bg-rose-600 text-white text-sm font-semibold rounded-lg hover:bg-rose-700 shadow transition flex items-center justify-center gap-1.5">
                                                    <span>⚠️</span> Ya, Hapus Akun
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- JIKA HASIL TIDAK DITEMUKAN --}}
                            <tr x-show="users.length === 0">
                                <td colspan="7" class="p-4 text-center text-gray-500">
                                    Data user/pegawai tidak ditemukan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4" x-show="search === ''">
                    {{ $users->links() }}
                </div>

            </div>
        </div>
    </div>
</x-app-layout>