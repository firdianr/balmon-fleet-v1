<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tambah Akun User / Pegawai Baru') }}
        </h2>
    </x-slot>

    <div class="pt-2 pb-6" x-data="{ 
        showConfirmModal: false,
        formData: {
            name: '{{ old('name') }}',
            nip: '{{ old('nip') }}',
            pangkat: '{{ old('pangkat') }}',
            jabatan: '{{ old('jabatan') }}',
            email: '{{ old('email') }}',
            role: '{{ old('role') }}',
            phone: '{{ old('phone') }}',
            department: '{{ old('department') }}'
        },
        getRoleLabel(role) {
            const labels = {
                'admin_app': 'Admin Aplikasi',
                'admin_lap': 'Admin Lapangan',
                'approver': 'Approver (Kepala Kantor)',
                'driver': 'Driver / Pengemudi',
                'pegawai': 'Pegawai'
            };
            return labels[role] || role;
        },
        submitForm() {
            $refs.addUserForm.submit();
        }
    }">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            {{-- ERROR VALIDASI --}}
            {{-- @if($errors->any())
                <div class="p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-lg shadow-sm">
                    <div class="flex items-center mb-1">
                        <svg class="w-5 h-5 mr-2 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <strong class="font-semibold text-sm">Terjadi kesalahan pada inputan Anda:</strong>
                    </div>
                    <ul class="list-disc pl-9 text-xs space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif --}}

            {{-- KARTU FORM --}}
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <header class="mb-6 border-b pb-4">
                    <h3 class="text-lg font-medium text-gray-900">Informasi Pengguna Baru</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Pastikan seluruh identitas dan nomor WhatsApp dinas terisi dengan benar.
                    </p>
                </header>

                <form x-ref="addUserForm" action="{{ route('admin_app.users.store') }}" method="POST" @submit.prevent="showConfirmModal = true" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- NAMA LENGKAP --}}
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Nama Lengkap & Gelar <span class="text-red-500">*</span></label>
                            <input type="text" name="name" x-model="formData.name" 
                                placeholder="Contoh: Pegawai, S.Kom." 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" required>
                        </div>

                        {{-- NIP --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">NIP (Nomor Induk Pegawai)</label>
                            <input type="text" name="nip" x-model="formData.nip" 
                                placeholder="Contoh: 198804122014021001" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            @error('nip')
                                <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- TELEPON / WA --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nomor WhatsApp Dinas</label>
                            <input type="text" name="phone" x-model="formData.phone" 
                                placeholder="Contoh: 081234567890" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>

                        {{-- PANGKAT / GOLONGAN (DROPDOWN PNS & PPPK) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Pangkat / Golongan</label>
                            <select name="pangkat" 
                                    x-model="formData.pangkat" 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="">-- Pilih Pangkat / Golongan --</option>
                                
                                {{-- KATEGORI PNS --}}
                                <optgroup label="PNS - Golongan IV (Pembina)">
                                    <option value="Pembina Utama (IV/e)">Pembina Utama (IV/e)</option>
                                    <option value="Pembina Utama Madya (IV/d)">Pembina Utama Madya (IV/d)</option>
                                    <option value="Pembina Utama Muda (IV/c)">Pembina Utama Muda (IV/c)</option>
                                    <option value="Pembina Tk. I (IV/b)">Pembina Tk. I (IV/b)</option>
                                    <option value="Pembina (IV/a)">Pembina (IV/a)</option>
                                </optgroup>

                                <optgroup label="PNS - Golongan III (Penata)">
                                    <option value="Penata Tk. I (III/d)">Penata Tk. I (III/d)</option>
                                    <option value="Penata (III/c)">Penata (III/c)</option>
                                    <option value="Penata Muda Tk. I (III/b)">Penata Muda Tk. I (III/b)</option>
                                    <option value="Penata Muda (III/a)">Penata Muda (III/a)</option>
                                </optgroup>

                                <optgroup label="PNS - Golongan II (Pengatur)">
                                    <option value="Pengatur Tk. I (II/d)">Pengatur Tk. I (II/d)</option>
                                    <option value="Pengatur (II/c)">Pengatur (II/c)</option>
                                    <option value="Pengatur Muda Tk. I (II/b)">Pengatur Muda Tk. I (II/b)</option>
                                    <option value="Pengatur Muda (II/a)">Pengatur Muda (II/a)</option>
                                </optgroup>

                                <optgroup label="PNS - Golongan I (Juru)">
                                    <option value="Juru Tk. I (I/d)">Juru Tk. I (I/d)</option>
                                    <option value="Juru (I/c)">Juru (I/c)</option>
                                    <option value="Juru Muda Tk. I (I/b)">Juru Muda Tk. I (I/b)</option>
                                    <option value="Juru Muda (I/a)">Juru Muda (I/a)</option>
                                </optgroup>

                                {{-- KATEGORI PPPK --}}
                                <optgroup label="PPPK (Pegawai Pemerintah dengan Perjanjian Kerja)">
                                    <option value="PPPK Golongan I">PPPK Golongan I</option>
                                    <option value="PPPK Golongan II">PPPK Golongan II</option>
                                    <option value="PPPK Golongan III">PPPK Golongan III</option>
                                    <option value="PPPK Golongan IV">PPPK Golongan IV</option>
                                    <option value="PPPK Golongan V">PPPK Golongan V</option>
                                    <option value="PPPK Golongan VI">PPPK Golongan VI</option>
                                    <option value="PPPK Golongan VII">PPPK Golongan VII</option>
                                    <option value="PPPK Golongan VIII">PPPK Golongan VIII</option>
                                    <option value="PPPK Golongan IX">PPPK Golongan IX</option>
                                    <option value="PPPK Golongan X">PPPK Golongan X</option>
                                    <option value="PPPK Golongan XI">PPPK Golongan XI</option>
                                    <option value="PPPK Golongan XII">PPPK Golongan XII</option>
                                    <option value="PPPK Golongan XIII">PPPK Golongan XIII</option>
                                    <option value="PPPK Golongan XIV">PPPK Golongan XIV</option>
                                    <option value="PPPK Golongan XV">PPPK Golongan XV</option>
                                    <option value="PPPK Golongan XVI">PPPK Golongan XVI</option>
                                    <option value="PPPK Golongan XVII">PPPK Golongan XVII</option>
                                </optgroup>

                                {{-- KATEGORI NON-ASN / LAINNYA --}}
                                <optgroup label="Lainnya">
                                    <option value="Non-ASN / PPNPN">Non-ASN / PPNPN / Kontrak</option>
                                </optgroup>
                            </select>
                        </div>

                        {{-- JABATAN --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Jabatan</label>
                            <input type="text" name="jabatan" x-model="formData.jabatan" 
                                placeholder="Contoh: PFR Ahli Muda" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>

                        {{-- SEKSI / DEPARTEMEN --}}
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Seksi / Subbagian / Tim Kerja</label>
                            <input type="text" name="department" x-model="formData.department" 
                                placeholder="Contoh: Tim Sarana dan Prasarana" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                    </div>

                    <hr class="border-gray-200 my-4">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- EMAIL DINAS --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Alamat Email Dinas <span class="text-red-500">*</span></label>
                            <input type="email" name="email" x-model="formData.email" 
                                placeholder="Contoh: pegawai@balmon.go.id" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" required>
                            @error('email')
                                <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- ROLE / HAK AKSES --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Role / Hak Akses Sistem <span class="text-red-500">*</span></label>
                            <select name="role" x-model="formData.role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" required>
                                <option value="" disabled selected>-- Pilih Hak Akses --</option>
                                <option value="pegawai">Pegawai</option>
                                <option value="driver">Driver / Pengemudi Armada</option>
                                <option value="approver">Approver (Kepala Kantor)</option>
                                <option value="admin_lap">Admin Lapangan (Pengatur Armada)</option>
                                <option value="admin_app">Admin Aplikasi (Pengelola Sistem)</option>
                            </select>
                            @error('role')
                                <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- PASSWORD --}}
                    <div x-data="{ showPassword: false }">
                        <label class="block text-sm font-medium text-gray-700">
                            Password Akses <span class="text-red-500">*</span>
                        </label>
                        
                        <div class="relative mt-1">
                            <input :type="showPassword ? 'text' : 'password'" 
                                name="password" 
                                class="block w-full rounded-md border-gray-300 pr-10 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                placeholder="••••••••" required>

                            <button type="button" 
                                    @click="showPassword = !showPassword" 
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 focus:outline-none"
                                    tabindex="-1">
                                <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.02 10.02 0 014.122-.963c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m-4.692-4.692a3 3 0 00-4.243-4.243"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- TOMBOL AKSI FORM --}}
                    <div class="flex items-center justify-end gap-3 pt-4 border-t">
                        <a href="{{ route('admin_app.users.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                            Batal
                        </a>
                        <button type="submit" class="px-5 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold hover:bg-indigo-700 shadow-sm transition">
                            Lanjutkan Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- MODAL KONFIRMASI KEBENARAN DATA --}}
        <div x-show="showConfirmModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50" x-cloak>
            <div class="bg-white rounded-xl shadow-xl max-w-lg w-full overflow-hidden" @click.away="showConfirmModal = false">
                <div class="bg-indigo-600 px-6 py-4 text-white flex justify-between items-center">
                    <h3 class="font-bold text-base flex items-center gap-2">
                        <span>🔍</span> Konfirmasi Kebenaran Data User
                    </h3>
                    <button type="button" @click="showConfirmModal = false" class="text-white/80 hover:text-white text-lg font-bold">&times;</button>
                </div>

                <div class="p-6 space-y-4">
                    <p class="text-xs text-gray-500">Periksa kembali ringkasan data sebelum disimpan ke dalam database sistem:</p>
                    
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 text-sm space-y-2.5">
                        <div class="flex justify-between border-b pb-1.5">
                            <span class="text-gray-500 text-xs">Nama Lengkap:</span>
                            <span class="font-semibold text-gray-900" x-text="formData.name || '-'"></span>
                        </div>
                        <div class="flex justify-between border-b pb-1.5">
                            <span class="text-gray-500 text-xs">NIP:</span>
                            <span class="font-mono text-xs font-semibold text-indigo-600" x-text="formData.nip || '-'"></span>
                        </div>
                        <div class="flex justify-between border-b pb-1.5">
                            <span class="text-gray-500 text-xs">Pangkat / Jabatan:</span>
                            <span class="font-medium text-gray-800 text-right" x-text="(formData.jabatan || '-') + (formData.pangkat ? ' (' + formData.pangkat + ')' : '')"></span>
                        </div>
                        <div class="flex justify-between border-b pb-1.5">
                            <span class="text-gray-500 text-xs">Email Dinas:</span>
                            <span class="font-medium text-gray-800" x-text="formData.email || '-'"></span>
                        </div>
                        <div class="flex justify-between border-b pb-1.5">
                            <span class="text-gray-500 text-xs">WhatsApp Dinas:</span>
                            <span class="font-medium text-gray-800" x-text="formData.phone || '-'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 text-xs">Role / Hak Akses:</span>
                            <span class="font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded text-xs border border-indigo-200" x-text="getRoleLabel(formData.role)"></span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <button type="button" @click="showConfirmModal = false" class="w-1/2 py-2.5 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition">
                            Periksa Kembali
                        </button>
                        <button type="button" @click="submitForm()" class="w-1/2 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 shadow transition flex items-center justify-center gap-1.5">
                            <span>✅</span> Ya, Data Sudah Benar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>