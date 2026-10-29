<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                    <span>{{ __('Edit Armada Kendaraan') }}</span>
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Perbarui informasi unit kendaraan dinas: <span class="font-bold text-indigo-600">{{ $vehicle->brand }} {{ $vehicle->model }} ({{ $vehicle->plate_number }})</span>.
                </p>
            </div>
            <a href="{{ route('admin_app.vehicles.index') }}" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold rounded-lg transition">
                <svg class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"/>
                    <polyline points="12 19 5 12 12 5"/>
                </svg>
                <span>Kembali ke Daftar Armada</span>
            </a>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ 
        showConfirmModal: false,
        photoPreview: null,
        initialStatus: '{{ $vehicle->status }}',
        formData: {
            brand: '{{ old('brand', $vehicle->brand) }}',
            model: '{{ old('model', $vehicle->model) }}',
            plate_number: '{{ old('plate_number', $vehicle->plate_number) }}',
            code: '{{ old('code', $vehicle->code) }}',
            type: '{{ old('type', $vehicle->type) }}',
            capacity: '{{ old('capacity', $vehicle->capacity) }}',
            fuel_type: '{{ old('fuel_type', $vehicle->fuel_type) }}',
            status: '{{ old('status', $vehicle->status) }}',
            notes: '{{ old('notes', $vehicle->notes) }}'
        },
        previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                this.photoPreview = URL.createObjectURL(file);
            }
        },
        getTypeLabel(type) {
            return type === 'monitoring' ? 'Monitoring' : 'Operasional';
        },
        getStatusLabel(status) {
            const labels = {
                'available': 'Available (Tersedia)',
                'borrowed': 'Borrowed (Dipinjam)',
                'maintenance': 'Maintenance (Pemeliharaan)'
            };
            return labels[status] || status;
        },
        submitForm() {
            $refs.editVehicleForm.submit();
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

            {{-- KARTU FORMULIR EDIT VEHICLE --}}
            <div class="bg-white p-6 sm:p-8 rounded-xl border border-gray-200 shadow-sm">
                <form x-ref="editVehicleForm" action="{{ route('admin_app.vehicles.update', $vehicle->slug) }}" method="POST" enctype="multipart/form-data" @submit.prevent="showConfirmModal = true" class="space-y-6">
                    @csrf
                    @method('PUT')

                    {{-- INFORMASI UTAMA KENDARAAN --}}
                    <div>
                        <h3 class="text-base font-bold text-gray-900 border-b pb-2 mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 stroke-indigo-600 fill-none stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A2 2 0 0 0 2 12v4c0 .6.4 1 1 1h2"/>
                                <circle cx="7" cy="17" r="2"/>
                                <path d="M9 17h6"/>
                                <circle cx="17" cy="17" r="2"/>
                            </svg>
                            <span>Identitas Kendaraan</span>
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- MERK KENDARAAN --}}
                            <div>
                                <label class="block text-sm font-bold text-gray-700">Merk / Pabrikan <span class="text-red-500">*</span></label>
                                <input type="text" name="brand" x-model="formData.brand" 
                                    placeholder="Contoh: Toyota, Mitsubishi, Isuzu" 
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-medium" required>
                                @error('brand')
                                    <p class="mt-1 text-xs text-red-600 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- MODEL KENDARAAN --}}
                            <div>
                                <label class="block text-sm font-bold text-gray-700">Tipe / Model <span class="text-red-500">*</span></label>
                                <input type="text" name="model" x-model="formData.model" 
                                    placeholder="Contoh: Innova Zenix, Triton, HiAce" 
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-medium" required>
                                @error('model')
                                    <p class="mt-1 text-xs text-red-600 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- NOMOR PLAT --}}
                            <div>
                                <label class="block text-sm font-bold text-gray-700">Nomor Plat Kendaraan <span class="text-red-500">*</span></label>
                                <input type="text" name="plate_number" x-model="formData.plate_number" 
                                    placeholder="Contoh: H 1234 XX" 
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-mono font-bold uppercase" required>
                                @error('plate_number')
                                    <p class="mt-1 text-xs text-red-600 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- KODE ARMADA --}}
                            <div>
                                <label class="block text-sm font-bold text-gray-700">Kode Armada <span class="text-red-500">*</span></label>
                                <input type="text" name="code" x-model="formData.code" 
                                    placeholder="Contoh: A, B, C, D..." 
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-mono font-bold uppercase" required>
                                <p class="mt-1 text-xs text-gray-500">Kode unik pengenal kendaraan (misal: A, B, M1).</p>
                                @error('code')
                                    <p class="mt-1 text-xs text-red-600 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- SPESIFIKASI DAN KLASIFIKASI --}}
                    <div>
                        <h3 class="text-base font-bold text-gray-900 border-b pb-2 mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 stroke-indigo-600 fill-none stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="3"/>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                            </svg>
                            <span>Spesifikasi & Status Operasional</span>
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- TIPE ARMADA --}}
                            <div>
                                <label class="block text-sm font-bold text-gray-700">Tipe Armada <span class="text-red-500">*</span></label>
                                <select name="type" x-model="formData.type" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-medium" required>
                                    <option value="operational">Operasional (Perjalanan Dinas Umum)</option>
                                    <option value="monitoring">Monitoring (Pengukuran / Lapangan)</option>
                                </select>
                                @error('type')
                                    <p class="mt-1 text-xs text-red-600 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- KAPASITAS PENUMPANG --}}
                            <div>
                                <label class="block text-sm font-bold text-gray-700">Kapasitas Penumpang (Orang) <span class="text-red-500">*</span></label>
                                <input type="number" name="capacity" x-model="formData.capacity" min="1" 
                                    placeholder="Contoh: 7" 
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-medium" required>
                                @error('capacity')
                                    <p class="mt-1 text-xs text-red-600 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- BAHAN BAKAR --}}
                            <div>
                                <label class="block text-sm font-bold text-gray-700">Jenis Bahan Bakar <span class="text-red-500">*</span></label>
                                <input type="text" name="fuel_type" x-model="formData.fuel_type" 
                                    placeholder="Contoh: Pertalite, Pertamax, Dexlite, Solar" 
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-medium" required>
                                @error('fuel_type')
                                    <p class="mt-1 text-xs text-red-600 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- STATUS KENDARAAN --}}
                            <div>
                                <label class="block text-sm font-bold text-gray-700">Status Ketersediaan <span class="text-red-500">*</span></label>
                                <select name="status" x-model="formData.status" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-medium" required>
                                    <option value="available">Available (Siap Digunakan)</option>
                                    <option value="borrowed">Borrowed (Sedang Dipinjam)</option>
                                    <option value="maintenance">Maintenance (Dalam Perawatan)</option>
                                </select>
                                <div class="mt-1.5 flex items-start gap-1.5 text-xs text-amber-700 font-medium bg-amber-50 p-2.5 rounded-lg border border-amber-200" x-show="formData.status === 'maintenance' && initialStatus !== 'maintenance'">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-amber-600 stroke-2 shrink-0 mt-0.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                                        <line x1="12" y1="9" x2="12" y2="13"/>
                                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                                    </svg>
                                    <span>Mengubah status ke Maintenance akan mengirim notifikasi peringatan WhatsApp ke pemohon jadwal peminjaman mendatang yang terdampak.</span>
                                </div>
                                @error('status')
                                    <p class="mt-1 text-xs text-red-600 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- FOTO & CATATAN ADDON --}}
                    <div>
                        <h3 class="text-base font-bold text-gray-900 border-b pb-2 mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 stroke-indigo-600 fill-none stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                                <circle cx="12" cy="13" r="4"/>
                            </svg>
                            <span>Foto & Catatan Tambahan</span>
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- UPLOAD FOTO ARMADA --}}
                            <div>
                                <label class="block text-sm font-bold text-gray-700">Ganti Foto Kendaraan</label>
                                <input type="file" name="photo" @change="previewImage" accept="image/*"
                                    class="mt-1 block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                <p class="mt-1 text-xs text-gray-500">Kosongkan jika tidak ingin mengganti foto. Format: JPG, PNG, WEBP (Maksimal 8MB).</p>

                                {{-- PRATINJAU FOTO LAMA vs BARU --}}
                                <div class="mt-3">
                                    <template x-if="photoPreview">
                                        <div class="relative w-full h-36 bg-gray-100 rounded-lg overflow-hidden border border-indigo-300 shadow-2xs">
                                            <span class="absolute top-2 left-2 bg-indigo-600 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-2xs z-10">Foto Baru</span>
                                            <img :src="photoPreview" class="w-full h-full object-cover">
                                        </div>
                                    </template>

                                    <template x-if="!photoPreview && '{{ $vehicle->photo }}'">
                                        <div class="relative w-full h-36 bg-gray-100 rounded-lg overflow-hidden border border-gray-200 shadow-2xs">
                                            <span class="absolute top-2 left-2 bg-gray-900/80 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-2xs z-10">Foto Saat Ini</span>
                                            <img src="{{ asset('storage/' . $vehicle->photo) }}" class="w-full h-full object-cover">
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- CATATAN KENDARAAN --}}
                            <div>
                                <label class="block text-sm font-bold text-gray-700">Catatan / Keterangan Tambahan</label>
                                <textarea name="notes" x-model="formData.notes" rows="4" 
                                    placeholder="Contoh: Kondisi fisik mulus, jadwal servis rutin setiap tanggal 15, dilengkapi GPS tracker..." 
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-medium"></textarea>
                            </div>
                        </div>
                    </div>

                    {{-- TOMBOL SUBMIT FORM --}}
                    <div class="flex items-center justify-end gap-3 pt-4 border-t">
                        <a href="{{ route('admin_app.vehicles.index') }}" class="px-5 py-2.5 bg-gray-100 text-gray-700 text-sm font-bold rounded-lg hover:bg-gray-200 transition">
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
                        <span>Konfirmasi Perubahan Data Armada</span>
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
                    {{-- ALERT PERINGATAN JIKA RUBAH KE MAINTENANCE --}}
                    <template x-if="formData.status === 'maintenance' && initialStatus !== 'maintenance'">
                        <div class="flex items-start gap-2.5 bg-amber-50 p-3 rounded-lg border border-amber-300 text-amber-900 text-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-amber-600 stroke-2 shrink-0 mt-0.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/>
                                <line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                            <p>
                                <strong>Perhatian:</strong> Perubahan status menjadi <strong>Maintenance</strong> akan secara otomatis memicu pengiriman pesan peringatan WhatsApp ke seluruh pemohon yang memiliki peminjaman aktif/disetujui untuk unit armada ini.
                            </p>
                        </div>
                    </template>

                    <p class="text-xs text-gray-500">Periksa kembali ringkasan data kendaraan yang telah diubah:</p>
                    
                    {{-- RINGKASAN DATA KENDARAAN --}}
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 text-sm space-y-2.5">
                        <div class="flex justify-between border-b border-gray-200 pb-1.5">
                            <span class="text-gray-500 text-xs font-semibold">Merk & Model:</span>
                            <span class="font-bold text-gray-900" x-text="(formData.brand || '-') + ' ' + (formData.model || '')"></span>
                        </div>
                        <div class="flex justify-between border-b border-gray-200 pb-1.5">
                            <span class="text-gray-500 text-xs font-semibold">Nomor Plat:</span>
                            <span class="font-mono text-xs font-bold text-indigo-600 uppercase" x-text="formData.plate_number || '-'"></span>
                        </div>
                        <div class="flex justify-between border-b border-gray-200 pb-1.5">
                            <span class="text-gray-500 text-xs font-semibold">Kode Armada:</span>
                            <span class="font-mono text-xs font-bold text-gray-900 uppercase" x-text="formData.code || '-'"></span>
                        </div>
                        <div class="flex justify-between border-b border-gray-200 pb-1.5">
                            <span class="text-gray-500 text-xs font-semibold">Tipe Armada:</span>
                            <span class="font-bold text-gray-800" x-text="getTypeLabel(formData.type)"></span>
                        </div>
                        <div class="flex justify-between border-b border-gray-200 pb-1.5">
                            <span class="text-gray-500 text-xs font-semibold">Kapasitas & BBM:</span>
                            <span class="font-bold text-gray-800" x-text="(formData.capacity ? formData.capacity + ' Orang' : '-') + ' / ' + (formData.fuel_type || '-')"></span>
                        </div>
                        <div class="flex justify-between border-b border-gray-200 pb-1.5">
                            <span class="text-gray-500 text-xs font-semibold">Status Ketersediaan:</span>
                            <span class="font-bold text-xs" x-text="getStatusLabel(formData.status)"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 text-xs font-semibold">Status Foto:</span>
                            <span class="font-semibold text-xs" :class="photoPreview ? 'text-indigo-600' : 'text-gray-500'" x-text="photoPreview ? 'Foto Baru Diunggah' : 'Tetap Memakai Foto Lama'"></span>
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