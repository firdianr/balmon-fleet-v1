<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 stroke-indigo-600 fill-none stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                    <span>{{ __('Edit Akun User / Pegawai') }}</span>
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Perbarui informasi identitas dan hak akses pegawai: <span class="font-bold text-indigo-600">{{ $user->name }}</span>
                </p>
            </div>
            <a href="{{ route('admin_app.users.index') }}" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold rounded-lg transition">
                <svg class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"/>
                    <polyline points="12 19 5 12 12 5"/>
                </svg>
                <span>Kembali ke Daftar User</span>
            </a>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ 
        showConfirmModal: false,
        formData: {
            name: '{{ old('name', $user->name) }}',
            nip: '{{ old('nip', $user->nip) }}',
            pangkat: '{{ old('pangkat', $user->pangkat) }}',
            jabatan: '{{ old('jabatan', $user->jabatan) }}',
            email: '{{ old('email', $user->email) }}',
            role: '{{ old('role', $user->role) }}',
            phone: '{{ old('phone', $user->phone) }}',
            department: '{{ old('department', $user->department) }}',
            passwordChanged: false
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
            $refs.editUserForm.submit();
        }
    }">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            {{-- RINGKASAN ERROR VALIDASI --}}
            @if($errors->any())
                <div class="p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-lg shadow-sm">
                    <div class="flex items-center mb-1">
                        <svg class="w-5 h-5 mr-2 text-red-600 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        <strong class="font-bold text-sm">Terjadi kesalahan pada inputan Anda:</strong>
                    </div>
                    <ul class="list-disc pl-9 text-xs space-y-1 font-medium">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- KARTU FORMULIR EDIT USER --}}
            <div class="bg-white p-6 sm:p-8 rounded-xl border border-gray-200 shadow-sm">
                <form x-ref="editUserForm" action="{{ route('admin_app.users.update', $user->slug) }}" method="POST" @submit.prevent="showConfirmModal = true" class="space-y-6">
                    @csrf
                    @method('PUT')

                    {{-- INFORMASI UTAMA PEGAWAI --}}
                    <div>
                        <h3 class="text-base font-bold text-gray-900 border-b pb-2 mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 stroke-indigo-600 fill-none stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                            <span>Identitas Pegawai</span>
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- NAMA LENGKAP --}}
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-bold text-gray-700">Nama Lengkap & Gelar <span class="text-red-500">*</span></label>
                                <input type="text" name="name" x-model="formData.name" 
                                    placeholder="Contoh: Pegawai, S.Kom." 
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-medium" required>
                                @error('name')
                                    <p class="mt-1 text-xs text-red-600 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- NIP --}}
                            <div>
                                <label class="block text-sm font-bold text-gray-700">NIP (Nomor Induk Pegawai)</label>
                                <input type="text" name="nip" x-model="formData.nip" 
                                    placeholder="Contoh: 198804122014021001" 
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-mono font-medium">
                                @error('nip')
                                    <p class="mt-1 text-xs text-red-600 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- TELEPON / WA --}}
                            <div>
                                <label class="block text-sm font-bold text-gray-700">Nomor WhatsApp Dinas</label>
                                <input type="text" name="phone" x-model="formData.phone" 
                                    placeholder="Contoh: 081234567890" 
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-medium">
                                @error('phone')
                                    <p class="mt-1 text-xs text-red-600 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- PANGKAT / GOLONGAN --}}
                            <div>
                                <label class="block text-sm font-bold text-gray-700">Pangkat / Golongan</label>
                                <select name="pangkat" 
                                        x-model="formData.pangkat" 
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-medium">
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
                                @error('pangkat')
                                    <p class="mt-1 text-xs text-red-600 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- JABATAN --}}
                            <div>
                                <label class="block text-sm font-bold text-gray-700">Jabatan</label>
                                <input type="text" name="jabatan" x-model="formData.jabatan" 
                                    placeholder="Contoh: PFR Ahli Muda" 
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-medium">
                                @error('jabatan')
                                    <p class="mt-1 text-xs text-red-600 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- SEKSI / DEPARTEMEN --}}
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-bold text-gray-700">Seksi / Subbagian / Tim Kerja</label>
                                <input type="text" name="department" x-model="formData.department" 
                                    placeholder="Contoh: Tim Sarana dan Prasarana" 
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-medium">
                                @error('department')
                                    <p class="mt-1 text-xs text-red-600 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- KONTAK & HAK AKSES SISTEM --}}
                    <div>
                        <h3 class="text-base font-bold text-gray-900 border-b pb-2 mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 stroke-indigo-600 fill-none stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="4" width="20" height="16" rx="2"/>
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                            </svg>
                            <span>Kontak & Hak Akses Sistem</span>
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- EMAIL DINAS --}}
                            <div>
                                <label class="block text-sm font-bold text-gray-700">Alamat Email Dinas <span class="text-red-500">*</span></label>
                                <input type="email" name="email" x-model="formData.email" 
                                    placeholder="Contoh: pegawai@balmon.go.id" 
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-medium" required>
                                @error('email')
                                    <p class="mt-1 text-xs text-red-600 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- ROLE / HAK AKSES --}}
                            <div>
                                <label class="block text-sm font-bold text-gray-700">Role / Hak Akses Sistem <span class="text-red-500">*</span></label>
                                <select name="role" x-model="formData.role" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-medium" required>
                                    <option value="pegawai">Pegawai</option>
                                    <option value="driver">Driver / Pengemudi Armada</option>
                                    <option value="approver">Approver (Kepala Kantor)</option>
                                    <option value="admin_lap">Admin Lapangan (Pengatur Armada)</option>
                                    <option value="admin_app">Admin Aplikasi (Pengelola Sistem)</option>
                                </select>
                                @error('role')
                                    <p class="mt-1 text-xs text-red-600 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- KEAMANAN / PASSWORD --}}
                    <div>
                        <h3 class="text-base font-bold text-gray-900 border-b pb-2 mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 stroke-indigo-600 fill-none stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                            <span>Keamanan Akun</span>
                        </h3>
                        <div x-data="{ showPassword: false }">
                            <label class="block text-sm font-bold text-gray-700">
                                Password Akses Baru <span class="text-xs text-gray-400 font-normal">(Kosongkan jika tidak ingin mengganti password)</span>
                            </label>
                            
                            <div class="relative mt-1">
                                <input :type="showPassword ? 'text' : 'password'" 
                                    name="password" 
                                    @input="formData.passwordChanged = $event.target.value.length > 0"
                                    class="block w-full rounded-lg border-gray-300 pr-10 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-medium"
                                    placeholder="••••••••">

                                <button type="button" 
                                        @click="showPassword = !showPassword" 
                                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 focus:outline-none"
                                        tabindex="-1">
                                    <svg x-show="!showPassword" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    <svg x-show="showPassword" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" x-cloak>
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                        <line x1="1" y1="1" x2="23" y2="23"/>
                                    </svg>
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-1 text-xs text-red-600 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- TOMBOL SUBMIT FORM --}}
                    <div class="flex items-center justify-end gap-3 pt-4 border-t">
                        <a href="{{ route('admin_app.users.index') }}" class="px-5 py-2.5 bg-gray-100 text-gray-700 text-sm font-bold rounded-lg hover:bg-gray-200 transition">
                            Batal
                        </a>
                        <button type="submit" class="px-6 py-2.5 bg-amber-500 text-white text-sm font-bold rounded-lg hover:bg-amber-600 shadow transition flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                                <polyline points="17 21 17 13 7 13 7 21"/>
                                <polyline points="7 3 7 8 15 8"/>
                            </svg>
                            <span>Simpan Perubahan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- MODAL KONFIRMASI PERUBAHAN DATA --}}
        <div x-show="showConfirmModal" 
             class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             x-cloak>
            
            <div class="bg-white rounded-xl shadow-xl max-w-lg w-full overflow-hidden" @click.away="showConfirmModal = false">
                
                {{-- HEADER MODAL --}}
                <div class="bg-amber-600 px-6 py-4 text-white flex justify-between items-center">
                    <h3 class="font-bold text-base flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-white stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                        <span>Konfirmasi Perubahan Data User</span>
                    </h3>
                    <button type="button" @click="showConfirmModal = false" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>

                {{-- BODY MODAL --}}
                <div class="p-6 space-y-4">
                    <p class="text-xs text-gray-500">Periksa kembali data user yang akan diperbarui:</p>
                    
                    {{-- RINGKASAN DATA --}}
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 text-sm space-y-2.5">
                        <div class="flex justify-between border-b border-gray-200 pb-1.5">
                            <span class="text-gray-500 text-xs font-semibold">Nama Lengkap:</span>
                            <span class="font-bold text-gray-900" x-text="formData.name || '-'"></span>
                        </div>
                        <div class="flex justify-between border-b border-gray-200 pb-1.5">
                            <span class="text-gray-500 text-xs font-semibold">NIP:</span>
                            <span class="font-mono text-xs font-bold text-indigo-600" x-text="formData.nip || '-'"></span>
                        </div>
                        <div class="flex justify-between border-b border-gray-200 pb-1.5">
                            <span class="text-gray-500 text-xs font-semibold">Pangkat / Jabatan:</span>
                            <span class="font-medium text-gray-800 text-right text-xs" x-text="(formData.jabatan || '-') + (formData.pangkat ? ' (' + formData.pangkat + ')' : '')"></span>
                        </div>
                        <div class="flex justify-between border-b border-gray-200 pb-1.5">
                            <span class="text-gray-500 text-xs font-semibold">Email Dinas:</span>
                            <span class="font-medium text-gray-800 text-xs" x-text="formData.email || '-'"></span>
                        </div>
                        <div class="flex justify-between border-b border-gray-200 pb-1.5">
                            <span class="text-gray-500 text-xs font-semibold">WhatsApp Dinas:</span>
                            <span class="font-medium text-gray-800 text-xs" x-text="formData.phone || '-'"></span>
                        </div>
                        <div class="flex justify-between border-b border-gray-200 pb-1.5">
                            <span class="text-gray-500 text-xs font-semibold">Role / Hak Akses:</span>
                            <span class="font-bold text-amber-800 bg-amber-50 px-2 py-0.5 rounded text-xs border border-amber-200" x-text="getRoleLabel(formData.role)"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 text-xs font-semibold">Status Password:</span>
                            <span class="font-semibold text-xs" :class="formData.passwordChanged ? 'text-rose-600' : 'text-gray-500'" x-text="formData.passwordChanged ? 'Password diperbarui' : 'Tidak Mengubah Password'"></span>
                        </div>
                    </div>

                    {{-- TOMBOL AKSI MODAL --}}
                    <div class="flex items-center gap-3 pt-2">
                        <button type="button" 
                                @click="showConfirmModal = false" 
                                class="w-1/2 py-2.5 bg-gray-100 text-gray-700 text-sm font-bold rounded-lg hover:bg-gray-200 transition">
                            Periksa Kembali
                        </button>
                        <button type="button" 
                                @click="submitForm()" 
                                class="w-1/2 py-2.5 bg-amber-600 text-white text-sm font-bold rounded-lg hover:bg-amber-700 shadow transition flex items-center justify-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            <span>Ya, Simpan Perubahan</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>