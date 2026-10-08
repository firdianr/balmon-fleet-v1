<x-app-layout>
    <x-slot name="title">Kelola Pegawai / User</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Kelola Akun Pegawai & User') }}
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

                    <a href="{{ route('admin.users.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold hover:bg-indigo-700 transition whitespace-nowrap">
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
                                <th class="p-3">Email Dinas</th>
                                <th class="p-3">Seksi / Subbagian</th>
                                <th class="p-3">Role</th>
                                <th class="p-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 text-sm">
                            <template x-for="user in users" :key="user.id">
                                <tr>
                                    <td class="p-3 font-medium text-gray-900" x-text="user.name"></td>
                                    <td class="p-3 font-mono text-xs text-indigo-600" x-text="user.nip ?? '-'"></td>
                                    <td class="p-3 text-gray-600" x-text="user.email"></td>
                                    <td class="p-3 text-gray-600" x-text="user.department ?? '-'"></td>
                                    <td class="p-3">
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-full"
                                              :class="{
                                                  'bg-purple-100 text-purple-800': user.role === 'admin',
                                                  'bg-blue-100 text-blue-800': user.role === 'pegawai'
                                              }">
                                            <span x-text="user.role.toUpperCase()"></span>
                                        </span>
                                    </td>
                                    <td class="p-3 text-center whitespace-nowrap">
                                        <div class="inline-flex items-center justify-center gap-1.5">
                                            <a :href="`/admin/users/${user.slug}/edit`" class="inline-flex items-center px-2.5 py-1.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold rounded shadow-sm transition">
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
                            <div x-show="showDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50" x-cloak>
                                <div class="bg-white rounded-lg shadow relative max-w-sm w-full">
                                    <div class="flex justify-end p-2">
                                        <button type="button" @click="showDeleteModal = false" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                                        </button>
                                    </div>

                                    <div class="p-6 pt-0 text-center">
                                        <svg class="w-20 h-20 text-red-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <h3 class="text-lg font-normal text-gray-500 mt-5 mb-6">
                                            Apakah Anda yakin ingin menghapus akun <strong x-text="selectedUser ? selectedUser.name : ''"></strong>?
                                        </h3>
                                        
                                        <form :action="'/admin/users/' + (selectedUser ? selectedUser.slug : '')" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm inline-flex items-center px-4 py-2.5 text-center mr-2">
                                                Ya, Hapus
                                            </button>
                                        </form>
                                        
                                        <button type="button" @click="showDeleteModal = false" class="text-gray-900 bg-white hover:bg-gray-100 focus:ring-4 focus:ring-cyan-200 border border-gray-200 font-medium inline-flex items-center rounded-lg text-sm px-4 py-2.5 text-center">
                                            Batal
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- JIKA HASIL TIDAK DITEMUKAN --}}
                            <tr x-show="users.length === 0">
                                <td colspan="6" class="p-4 text-center text-gray-500">
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