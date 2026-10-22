<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    {{ __('Tambah Armada Kendaraan Baru') }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Isi formulir berikut untuk menambahkan unit kendaraan dinas operasional atau monitoring ke dalam sistem.
                </p>
            </div>
            <a href="{{ route('admin_app.vehicles.index') }}" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Kembali ke Daftar Armada</span>
            </a>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ 
        showConfirmModal: false,
        photoPreview: null,
        formData: {
            brand: '{{ old('brand') }}',
            model: '{{ old('model') }}',
            plate_number: '{{ old('plate_number') }}',
            code: '{{ old('code') }}',
            type: '{{ old('type', 'operational') }}',
            capacity: '{{ old('capacity') }}',
            fuel_type: '{{ old('fuel_type') }}',
            status: '{{ old('status', 'available') }}',
            notes: '{{ old('notes') }}'
        },
        previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                this.photoPreview = URL.createObjectURL(file);
            }
        },
        getTypeLabel(type) {
            return type === 'monitoring' ? '📡 Monitoring' : '🚗 Operasional';
        },
        getStatusLabel(status) {
            const labels = {
                'available': '🟢 Available (Tersedia)',
                'borrowed': '🟡 Dipinjam',
                'maintenance': '🔴 Maintenance'
            };
            return labels[status] || status;
        },
        submitForm() {
            $refs.addVehicleForm.submit();
        }
    }">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            {{-- RINGKASAN ERROR VALIDASI --}}
            @if($errors->any())
                <div class="p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-lg shadow-sm">
                    <div class="flex items-center mb-1">
                        <svg class="w-5 h-5 mr-2 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
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

            {{-- KARTU FORMULIR TAMBAH VEHICLE --}}
            <div class="bg-white p-6 sm:p-8 rounded-xl border border-gray-200 shadow-sm">
                <form x-ref="addVehicleForm" action="{{ route('admin_app.vehicles.store') }}" method="POST" enctype="multipart/form-data" @submit.prevent="showConfirmModal = true" class="space-y-6">
                    @csrf

                    {{-- INFORMASI UTAMA KENDARAAN --}}
                    <div>
                        <h3 class="text-base font-bold text-gray-900 border-b pb-2 mb-4 flex items-center gap-2">
                            <span>🚗</span> Identitas Kendaraan
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
                                <label class="block text-sm font-medium text-gray-700">Tipe / Model <span class="text-red-500">*</span></label>
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
                            <span>⚙️</span> Spesifikasi & Status Operasional
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

                            {{-- STATUS AWAL --}}
                            <div>
                                <label class="block text-sm font-bold text-gray-700">Status Ketersediaan Awal <span class="text-red-500">*</span></label>
                                <select name="status" x-model="formData.status" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-medium" required>
                                    <option value="available">Available (Siap Digunakan)</option>
                                    <option value="borrowed">Borrowed (Sedang Dipinjam)</option>
                                    <option value="maintenance">Maintenance (Dalam Perawatan)</option>
                                </select>
                                @error('status')
                                    <p class="mt-1 text-xs text-red-600 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- FOTO & CATATAN ADDON --}}
                    <div>
                        <h3 class="text-base font-bold text-gray-900 border-b pb-2 mb-4 flex items-center gap-2">
                            <span>📷</span> Foto & Catatan Tambahan
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- UPLOAD FOTO ARMADA --}}
                            <div>
                                <label class="block text-sm font-bold text-gray-700">Foto Kendaraan</label>
                                <input type="file" name="photo" @change="previewImage" accept="image/*"
                                    class="mt-1 block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                <p class="mt-1 text-xs text-gray-500">Format: JPG, PNG, WEBP (Maksimal 8MB).</p>

                                {{-- PRATINJAU FOTO --}}
                                <template x-if="photoPreview">
                                    <div class="mt-3 relative w-full h-36 bg-gray-100 rounded-lg overflow-hidden border border-gray-200">
                                        <img :src="photoPreview" class="w-full h-full object-cover">
                                    </div>
                                </template>
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
                        <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 shadow transition flex items-center gap-1.5">
                            <span>✓ Simpan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- MODAL KONFIRMASI KEBENARAN DATA --}}
        <div x-show="showConfirmModal" 
             class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             x-cloak>
            
            <div class="bg-white rounded-xl shadow-xl max-w-lg w-full overflow-hidden" @click.away="showConfirmModal = false">
                
                {{-- HEADER MODAL --}}
                <div class="bg-indigo-600 px-6 py-4 text-white flex justify-between items-center">
                    <h3 class="font-bold text-base flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Konfirmasi Kebenaran Data Armada</span>
                    </h3>
                    <button type="button" @click="showConfirmModal = false" class="text-white/80 hover:text-white text-2xl font-bold">&times;</button>
                </div>

                {{-- BODY MODAL --}}
                <div class="p-6 space-y-4">
                    <p class="text-xs text-gray-500">Periksa kembali ringkasan data unit kendaraan sebelum ditambahkan ke dalam sistem:</p>
                    
                    {{-- RINGKASAN DATA KENDARAAN --}}
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 text-sm space-y-2.5">
                        <div class="flex justify-between border-b pb-1.5">
                            <span class="text-gray-500 text-xs font-semibold">Merk & Model:</span>
                            <span class="font-bold text-gray-900" x-text="(formData.brand || '-') + ' ' + (formData.model || '')"></span>
                        </div>
                        <div class="flex justify-between border-b pb-1.5">
                            <span class="text-gray-500 text-xs font-semibold">Nomor Plat:</span>
                            <span class="font-mono text-xs font-bold text-indigo-600 uppercase" x-text="formData.plate_number || '-'"></span>
                        </div>
                        <div class="flex justify-between border-b pb-1.5">
                            <span class="text-gray-500 text-xs font-semibold">Kode Armada:</span>
                            <span class="font-mono text-xs font-bold text-gray-900 uppercase" x-text="formData.code || '-'"></span>
                        </div>
                        <div class="flex justify-between border-b pb-1.5">
                            <span class="text-gray-500 text-xs font-semibold">Tipe Armada:</span>
                            <span class="font-bold text-gray-800" x-text="getTypeLabel(formData.type)"></span>
                        </div>
                        <div class="flex justify-between border-b pb-1.5">
                            <span class="text-gray-500 text-xs font-semibold">Kapasitas & BBM:</span>
                            <span class="font-bold text-gray-800" x-text="(formData.capacity ? formData.capacity + ' Orang' : '-') + ' / ' + (formData.fuel_type || '-')"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 text-xs font-semibold">Status Awal:</span>
                            <span class="font-bold text-xs" x-text="getStatusLabel(formData.status)"></span>
                        </div>
                    </div>

                    {{-- PRATINJAU FOTO DI MODAL JIKA ADA --}}
                    <template x-if="photoPreview">
                        <div class="text-xs text-gray-500">
                            <span class="block mb-1 font-semibold">Foto Terlampir:</span>
                            <img :src="photoPreview" class="h-24 w-full object-cover rounded-lg border border-gray-200">
                        </div>
                    </template>

                    {{-- TOMBOL AKSI MODAL --}}
                    <div class="flex items-center gap-3 pt-2">
                        <button type="button" 
                                @click="showConfirmModal = false" 
                                class="w-1/2 py-2.5 bg-gray-100 text-gray-700 text-sm font-bold rounded-lg hover:bg-gray-200 transition">
                            Periksa Kembali
                        </button>
                        <button type="button" 
                                @click="submitForm()" 
                                class="w-1/2 py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 shadow transition flex items-center justify-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Ya, Data Sudah Benar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>