<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    {{ __('Edit Permohonan Peminjaman Armada') }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Perbarui detail permohonan kendaraan dinas (Nomor Surat: {{ $booking->letter_number }}).
                </p>
            </div>
            <a href="{{ route('bookings.index') }}" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Kembali ke Riwayat Booking</span>
            </a>
        </div>
    </x-slot>

    <div class="py-8" x-data="{
        vehicleType: '{{ old('vehicle_type', $booking->vehicle_type) }}',
        selectedVehicleId: '{{ old('vehicle_id', $booking->vehicle_id ?? '') }}',
        showConfirmModal: false,
        
        // Master Data
        users: {{ Illuminate\Support\Js::from($users) }},
        monitoringVehicles: {{ Illuminate\Support\Js::from($vehicles) }},
        
        // Form Fields State
        letterNumber: '{{ old('letter_number', $booking->letter_number) }}',
        destination: '{{ old('destination', $booking->destination) }}',
        purpose: '{{ old('purpose', $booking->purpose) }}',
        startDate: '{{ old('start_date', $booking->start_date ? $booking->start_date->format('Y-m-d') : '') }}',
        endDate: '{{ old('end_date', $booking->end_date ? $booking->end_date->format('Y-m-d') : '') }}',

        // Dynamic Selected Team Members (Array User IDs)
        selectedUserIds: {{ Illuminate\Support\Js::from(old('participants', $booking->participants ?? [''])) }}.map(id => String(id)),

        addMember() {
            this.selectedUserIds.push('');
        },

        removeMember(index) {
            if (this.selectedUserIds.length > 1) {
                this.selectedUserIds.splice(index, 1);
            }
        },

        getSelectedVehicleDetail() {
            if (this.vehicleType === 'monitoring' && this.selectedVehicleId) {
                return this.monitoringVehicles.find(v => v.id == this.selectedVehicleId);
            }
            return null;
        },

        submitForm() {
            $refs.editBookingForm.submit();
        }
    }">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- ERROR NOTIFICATION --}}
            @if ($errors->any())
                <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 space-y-1">
                    <p class="font-bold text-sm">⚠️ Terdapat beberapa kesalahan input:</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- FORM EDIT PERMOHONAN --}}
            <div class="bg-white p-6 sm:p-8 rounded-xl border border-gray-200 shadow-sm">
                <form x-ref="editBookingForm" 
                    action="{{ route('bookings.update', $booking) }}" 
                    method="POST" 
                    enctype="multipart/form-data" 
                    @submit.prevent="showConfirmModal = true" 
                    class="space-y-6">
                    @csrf
                    @method('PUT')

                    {{-- INFORMASI BERKAS SURAT TUGAS --}}
                    <div class="space-y-2">
                        <label class="block text-sm font-bold text-gray-700">
                            Berkas Surat Tugas (PDF)
                        </label>

                        @if($booking->letter_file)
                            <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2.5">
                                    <div class="p-2 bg-emerald-600 text-white rounded-lg shadow-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-emerald-900">Berkas Surat Tugas Terpasang</p>
                                        <p class="text-[11px] text-emerald-700 font-mono">{{ $booking->letter_file }}</p>
                                    </div>
                                </div>
                                <a href="{{ asset('storage/' . $booking->letter_file) }}" target="_blank" class="text-xs text-indigo-600 hover:text-indigo-800 font-bold px-2 py-1 rounded hover:bg-indigo-50 transition">
                                    Lihat Berkas
                                </a>
                            </div>
                        @endif

                        <input type="file" name="letter_file" accept="application/pdf"
                            class="block w-full text-xs text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition cursor-pointer">
                        <p class="text-[11px] text-gray-500 mt-1">Kosongkan jika tidak ingin mengganti berkas Surat Tugas yang sudah ada (Format .PDF, Max 5MB).</p>
                    </div>

                    {{-- INFORMASI NOMOR SURAT TUGAS --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700">Nomor Surat Tugas <span class="text-red-500">*</span></label>
                        <input type="text" name="letter_number" x-model="letterNumber" placeholder="Contoh: xxx/Balmon.xx/KP.xx.xx/xx/20xx" required
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 text-sm font-mono font-bold">
                    </div>

                    {{-- 1. PEMILIHAN JENIS KENDARAAN DINAS --}}
                    <div>
                        <h3 class="text-base font-bold text-gray-900 border-b pb-2 mb-4 flex items-center gap-2">
                            <span>🚗</span> Kebutuhan Jenis Armada
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <label class="relative flex flex-col p-4 bg-white border-2 rounded-xl cursor-pointer transition hover:border-indigo-300"
                                   :class="vehicleType === 'operational' ? 'border-indigo-600 bg-indigo-50/40 ring-2 ring-indigo-500/20' : 'border-gray-200'">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2 font-bold text-sm text-gray-900">
                                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                                        </svg>
                                        <span>Kendaraan Operasional</span>
                                    </div>
                                    <input type="radio" name="vehicle_type" value="operational" x-model="vehicleType" class="text-indigo-600 focus:ring-indigo-500">
                                </div>
                                <p class="text-xs text-gray-500 leading-relaxed">
                                    Dinas umum, rapat eksternal, atau kunjungan kerja. Unit mobil akan dialokasikan oleh Admin Lapangan.
                                </p>
                            </label>

                            <label class="relative flex flex-col p-4 bg-white border-2 rounded-xl cursor-pointer transition hover:border-indigo-300"
                                   :class="vehicleType === 'monitoring' ? 'border-indigo-600 bg-indigo-50/40 ring-2 ring-indigo-500/20' : 'border-gray-200'">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2 font-bold text-sm text-gray-900">
                                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                                        </svg>
                                        <span>Kendaraan Monitoring</span>
                                    </div>
                                    <input type="radio" name="vehicle_type" value="monitoring" x-model="vehicleType" class="text-indigo-600 focus:ring-indigo-500">
                                </div>
                                <p class="text-xs text-gray-500 leading-relaxed">
                                    Kegiatan pengukuran, pengujian frekuensi, atau tugas teknis spesifik. Anda dapat memilih unit armada secara langsung.
                                </p>
                            </label>
                        </div>
                    </div>

                    {{-- DROPDOWN MOBIL MONITORING --}}
                    <div x-show="vehicleType === 'monitoring'" x-transition class="bg-indigo-50/60 p-4 rounded-xl border border-indigo-200 space-y-3">
                        <label class="block text-sm font-bold text-indigo-950">
                            Pilih Mobil Monitoring <span class="text-red-500">*</span>
                        </label>

                        <select name="vehicle_id" x-model="selectedVehicleId" :required="vehicleType === 'monitoring'" 
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 text-sm font-medium">
                            <option value="">-- Pilih Unit Armada Monitoring --</option>
                            <template x-for="v in monitoringVehicles" :key="v.id">
                                <option :value="v.id" x-text="v.brand + ' ' + v.model + ' (' + v.plate_number + ')'"></option>
                            </template>
                        </select>
                    </div>

                    {{-- CATATAN SPESIFIKASI KENDARAAN --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700">Catatan Kebutuhan Kendaraan</label>
                        <textarea name="vehicle_requirements" rows="2" 
                            placeholder="Contoh: Rute pegunungan menanjak tinggi (butuh 4WD) atau perjalanan dalam kota." 
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 text-sm font-medium">{{ old('vehicle_requirements', $booking->vehicle_requirements) }}</textarea>
                    </div>

                    {{-- 2. JADWAL & DESTINASI --}}
                    <div>
                        <h3 class="text-base font-bold text-gray-900 border-b pb-2 mb-4 flex items-center gap-2">
                            <span>📅</span> Waktu & Destinasi Perjalanan
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700">Tanggal Mulai <span class="text-red-500">*</span></label>
                                <input type="date" name="start_date" x-model="startDate" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 text-sm font-medium">
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700">Tanggal Selesai <span class="text-red-500">*</span></label>
                                <input type="date" name="end_date" x-model="endDate" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 text-sm font-medium">
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700">Tujuan Perjalanan Dinas <span class="text-red-500">*</span></label>
                                <input type="text" name="destination" x-model="destination" placeholder="Contoh: Kota Semarang" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 text-sm font-medium">
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700">Maksud / Keperluan Perjalanan <span class="text-red-500">*</span></label>
                                <textarea name="purpose" x-model="purpose" rows="3" placeholder="Jelaskan maksud agenda kegiatan..." required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 text-sm font-medium"></textarea>
                            </div>
                        </div>
                    </div>

                    {{-- 3. DROPDOWN ANGGOTA TIM DINAMIS DARI DATABASE --}}
                    <div>
                        <div class="flex items-center justify-between border-b pb-2 mb-4">
                            <div>
                                <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                                    <span>👥</span> Anggota Tim / Pengikut Dinas
                                </h3>
                                <p class="text-xs text-gray-500">Pilih anggota tim dari pegawai, admin lapangan, atau driver terdaftar.</p>
                            </div>
                            <button type="button" @click="addMember()" class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-lg text-xs font-bold transition">
                                <span>+ Tambah Anggota</span>
                            </button>
                        </div>

                        <div class="space-y-3">
                            <template x-for="(selectedId, index) in selectedUserIds" :key="index">
                                <div class="p-3 bg-gray-50 rounded-xl border border-gray-200 flex items-center gap-3">
                                    <span class="text-xs font-bold text-gray-400 w-6" x-text="'#' + (index + 1)"></span>
                                    
                                    <div class="flex-grow">
                                        <select name="participants[]" x-model="selectedUserIds[index]" class="block w-full rounded-lg border-gray-300 text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500">
                                            <option value="">-- Pilih Anggota Tim --</option>
                                            <template x-for="u in users" :key="u.id">
                                                <option :value="u.id" x-text="u.name + (u.nip ? ' (NIP: ' + u.nip + ')' : '') + ' - ' + u.role.toUpperCase()"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <button type="button" @click="removeMember(index)" x-show="selectedUserIds.length > 1" class="text-rose-600 hover:text-rose-800 p-1 font-bold text-lg">&times;</button>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- SUBMIT --}}
                    <div class="flex items-center justify-end gap-3 pt-4 border-t">
                        <a href="{{ route('bookings.index') }}" class="px-5 py-2.5 bg-gray-100 text-gray-700 text-sm font-bold rounded-lg hover:bg-gray-200 transition">
                            Batal
                        </a>
                        <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 shadow transition flex items-center gap-1.5">
                            <span>Simpan Perubahan</span>
                        </button>
                    </div>

                </form>
            </div>

            {{-- MODAL KONFIRMASI PEMBARUAN --}}
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
                    <div class="bg-indigo-600 px-6 py-4 text-white flex justify-between items-center">
                        <h3 class="font-bold text-base flex items-center gap-2">
                            <span>✏️</span> Konfirmasi Pembaruan Permohonan
                        </h3>
                        <button type="button" @click="showConfirmModal = false" class="text-white/80 hover:text-white text-2xl font-bold">&times;</button>
                    </div>

                    <div class="p-6 space-y-4">
                        <p class="text-xs text-gray-500">Pastikan rincian perubahan permohonan peminjaman armada Anda sudah benar sebelum disimpan:</p>

                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 text-sm space-y-2.5">
                            <div class="flex justify-between border-b pb-1.5">
                                <span class="text-gray-500 text-xs font-semibold">Tipe Armada:</span>
                                <span class="font-bold text-indigo-700 uppercase" x-text="vehicleType === 'monitoring' ? '📡 Monitoring' : '🚗 Operasional'"></span>
                            </div>

                            <template x-if="vehicleType === 'monitoring'">
                                <div class="flex justify-between border-b pb-1.5">
                                    <span class="text-gray-500 text-xs font-semibold">Mobil Dipilih:</span>
                                    <span class="font-bold text-gray-900" x-text="getSelectedVehicleDetail() ? (getSelectedVehicleDetail().brand + ' ' + getSelectedVehicleDetail().model + ' (' + getSelectedVehicleDetail().plate_number + ')') : 'Belum dipilih'"></span>
                                </div>
                            </template>

                            <template x-if="vehicleType === 'operational'">
                                <div class="flex justify-between border-b pb-1.5">
                                    <span class="text-gray-500 text-xs font-semibold">Penentuan Unit:</span>
                                    <span class="font-bold text-amber-700 text-xs italic">Akan Ditentukan Admin Lapangan</span>
                                </div>
                            </template>

                            <div class="flex justify-between border-b pb-1.5">
                                <span class="text-gray-500 text-xs font-semibold">Tujuan:</span>
                                <span class="font-bold text-gray-900" x-text="destination || '-'"></span>
                            </div>
                        </div>

                        {{-- TOMBOL AKSI MODAL --}}
                        <div class="flex items-center gap-3 pt-2">
                            <button type="button" @click="showConfirmModal = false" class="w-1/2 py-2.5 bg-gray-100 text-gray-700 text-sm font-bold rounded-lg hover:bg-gray-200 transition">
                                Periksa Kembali
                            </button>
                            <button type="button" @click="submitForm()" class="w-1/2 py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 shadow transition flex items-center justify-center gap-1.5">
                                <span>Ya, Simpan Perubahan</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>