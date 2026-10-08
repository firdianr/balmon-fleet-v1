<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tambah Akun User / Pegawai Baru') }}
        </h2>
    </x-slot>

    <div class="pt-2 pb-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                @if($errors->any())
                    <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                        <ul class="list-disc pl-5 text-sm">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-4">
                    @csrf

                    {{-- NAMA LENGKAP --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                        <input type="text" name="name" value="{{ old('name') }}" 
                            placeholder="Contoh: Budi Santoso, S.T." 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" required>
                    </div>

                    {{-- NIP --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">NIP (Nomor Induk Pegawai)</label>
                        <input type="text" name="nip" value="{{ old('nip') }}" 
                            placeholder="Contoh: 198804122014021001" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <p class="mt-1 text-xs text-gray-500">* Masukkan 18 digit NIP tanpa spasi.</p>
                    </div>

                    {{-- EMAIL --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Alamat Email Dinas</label>
                        <input type="email" name="email" value="{{ old('email') }}" 
                            placeholder="Contoh: budi.santoso@balmon.go.id" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" required>
                    </div>

                    {{-- PASSWORD --}}
                    <div x-data="{ showPassword: false }">
                        <label class="block text-sm font-medium text-gray-700">
                            Password <span class="text-xs text-gray-500 font-normal"></span>
                        </label>
                        
                        <div class="relative mt-1">
                            <input :type="showPassword ? 'text' : 'password'" 
                                name="password" 
                                class="block w-full rounded-md border-gray-300 pr-10 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                placeholder="••••••••">

                            <!-- Tombol Toggle Show/Hide -->
                            <button type="button" 
                                    @click="showPassword = !showPassword" 
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 focus:outline-none"
                                    tabindex="-1">
                                <!-- Ikon Mata Terbuka (Show) -->
                                <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <!-- Ikon Mata Dicoret (Hide) -->
                                <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" x-cloak>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.02 10.02 0 014.122-.963c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m-4.692-4.692a3 3 0 00-4.243-4.243"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- ROLE --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Role / Hak Akses</label>
                        <select name="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" required>
                            <option value="" disabled selected>-- Pilih Hak Akses --</option>
                            <option value="pegawai" {{ old('role') == 'pegawai' ? 'selected' : '' }}>Pegawai (Peminjam)</option>
                            <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin (Pengelola & Approver)</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        {{-- TELEPON --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nomor Telepon/HP (WhatsApp)</label>
                            <input type="text" name="phone" value="{{ old('phone') }}" 
                                placeholder="Contoh: 081234567890" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                        
                        {{-- DEPARTEMEN --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Seksi / Subbagian</label>
                            <input type="text" name="department" value="{{ old('department') }}" 
                                placeholder="Contoh: Seksi Sarana dan Pelayanan" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                    </div>

                    <div class="flex justify-end space-x-2 pt-4 border-t">
                        <a href="{{ route('admin.users.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-300 transition">Batal</a>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700 transition">Simpan User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>