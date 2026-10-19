<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    {{ __('Pengajuan Permohonan Peminjaman Armada') }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Lengkapi formulir permohonan untuk pengajuan kendaraan dinas operasional maupun monitoring.
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
        vehicleType: '{{ old('vehicle_type', 'operational') }}',
        selectedVehicleId: '{{ old('vehicle_id', '') }}',
        showConfirmModal: false,
        isParsing: false,
        
        // Master Data
        users: {{ Illuminate\Support\Js::from($users) }},
        monitoringVehicles: {{ Illuminate\Support\Js::from($monitoringVehicles) }},
        
        // Form Fields State (Auto-Fill Supported)
        letterNumber: '{{ old('letter_number', '') }}',
        letterFile: '{{ old('letter_file_temp', '') }}',
        destination: '{{ old('destination', '') }}',
        purpose: '{{ old('purpose', '') }}',
        startDate: '{{ old('start_date', '') }}',
        endDate: '{{ old('end_date', '') }}',

        // Dynamic Selected Team Members (Array User IDs)
        selectedUserIds: {{ Illuminate\Support\Js::from(old('participants', [''])) }}.map(id => String(id)),

        addMember() {
            this.selectedUserIds.push('');
        },

        removeMember(index) {
            if (this.selectedUserIds.length > 1) {
                this.selectedUserIds.splice(index, 1);
            }
        },

        // Auto-Fill Handler via Backend Parsing
        async handleFileUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('document', file);
            formData.append('_token', '{{ csrf_token() }}');

            this.isParsing = true;

            try {
                const response = await fetch('{{ route('bookings.parse_document') }}', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    const data = result.data;
                    if (data.letter_number) this.letterNumber = data.letter_number;
                    if (data.letter_file) this.letterFile = data.letter_file;
                    if (data.destination) this.destination = data.destination;
                    if (data.purpose) this.purpose = data.purpose;
                    if (data.start_date) this.startDate = data.start_date;
                    if (data.end_date) this.endDate = data.end_date;

                    if (data.user_ids && data.user_ids.length > 0) {
                        this.selectedUserIds = data.user_ids.map(id => String(id));
                    }

                    alert('✨ Auto-Fill Berhasil! Nomor Surat Tugas, Jadwal, Maksud, dan Anggota Tim berhasil diisi otomatis.');
                } else {
                    alert('⚠️ Gagal membaca berkas: ' + result.message);
                }
            } catch (error) {
                alert('⚠️ Terjadi kesalahan server saat memproses Surat Tugas.');
            } finally {
                this.isParsing = false;
            }
        },

        getSelectedVehicleDetail() {
            if (this.vehicleType === 'monitoring' && this.selectedVehicleId) {
                return this.monitoringVehicles.find(v => v.id == this.selectedVehicleId);
            }
            return null;
        },

        submitForm() {
            $refs.bookingForm.submit();
        }
    }">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- AUTO-FILL UPLOAD CARD --}}
            <div class="bg-indigo-50/80 p-5 rounded-xl border border-indigo-200 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="p-2.5 bg-indigo-600 text-white rounded-lg shadow-sm">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a1 1 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-indigo-950 text-sm">Unggah Surat Tugas (Auto-Fill Form)</h4>
                        <p class="text-xs text-indigo-700 mt-0.5">Unggah berkas PDF Surat Tugas untuk mengisi otomatis Nomor Surat, Jadwal, Maksud, dan Anggota Tim.</p>
                    </div>
                </div>

                <label class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow transition flex-shrink-0" :class="{ 'opacity-50 cursor-not-allowed': isParsing }">
                    <svg x-show="!isParsing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    <span x-text="isParsing ? 'Membaca Surat Tugas...' : 'Unggah PDF Surat Tugas'"></span>
                    <input type="file" accept="application/pdf" class="hidden" @change="handleFileUpload" :disabled="isParsing">
                </label>
            </div>

            {{-- FORM PERMOHONAN --}}
            <div class="bg-white p-6 sm:p-8 rounded-xl border border-gray-200 shadow-sm">
                <form x-ref="bookingForm" 
                    action="{{ route('bookings.store') }}" 
                    method="POST" 
                    enctype="multipart/form-data" 
                    @submit.prevent="showConfirmModal = true" 
                    class="space-y-6">
                    @csrf

                    {{-- HIDDEN INPUT UNTUK MEMPERTAHANKAN PATH FILE SAAT GAGAL VALIDASI --}}
                    <input type="hidden" name="letter_file_temp" x-model="letterFile">

                    <div class="space-y-2">
                        <label class="block text-sm font-bold text-gray-700">
                            Berkas Surat Tugas (PDF) <span class="text-red-500">*</span>
                        </label>

                        {{-- KONDISI 1: JIKA FILE SUDAH TERISI (LEWAT AUTO-FILL ATAU DARI PERCOBAAN SEBELUMNYA) --}}
                        <div x-show="letterFile" x-transition class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="p-2 bg-emerald-600 text-white rounded-lg shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-emerald-900">Berkas Surat Tugas Terhubung</p>
                                    <p class="text-[11px] text-emerald-700 font-mono" x-text="letterFile"></p>
                                </div>
                            </div>
                            <button type="button" @click="letterFile = ''" class="text-xs text-rose-600 hover:text-rose-800 font-bold px-2 py-1 rounded hover:bg-rose-50 transition">
                                Ganti File
                            </button>
                        </div>

                        {{-- KONDISI 2: JIKA BELUM ADA FILE TERDAPAT/INGIN UPLOAD MANUAL --}}
                        <div x-show="!letterFile" x-transition>
                            <input type="file" name="letter_file" accept="application/pdf"
                                class="block w-full text-xs text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition cursor-pointer">
                            <p class="text-[11px] text-gray-500 mt-1">Format dokumen harus .PDF (Maksimal 5MB)</p>
                        </div>
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
                                <option :value="v.id" x-text="'[' + v.code + '] ' + v.brand + ' ' + v.model + ' - ' + v.plate_number"></option>
                            </template>
                        </select>
                    </div>

                    {{-- CATATAN SPESIFIKASI KENDARAAN --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700">Catatan Kebutuhan Kendaraan</label>
                        <textarea name="vehicle_requirements" rows="2" 
                            placeholder="Contoh: Rute pegunungan menanjak tinggi (butuh 4WD) atau perjalanan dalam kota." 
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 text-sm font-medium">{{ old('vehicle_requirements') }}</textarea>
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
                                        <select name="participants[]" x-model="selectedUserIds[index]" required class="block w-full rounded-lg border-gray-300 text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500">
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
                            <span>Lanjutkan Permohonan</span>
                        </button>
                    </div>

                </form>
            </div>

            {{-- MODAL KONFIRMASI PEMINJAMAN --}}
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
                    <div class="bg-indigo-600 px-6 py-4 text-white flex justify-between items-center">
                        <h3 class="font-bold text-base flex items-center gap-2">
                            <span>📝</span> Konfirmasi Pengajuan Permohonan
                        </h3>
                        <button type="button" @click="showConfirmModal = false" class="text-white/80 hover:text-white text-2xl font-bold">&times;</button>
                    </div>

                    <div class="p-6 space-y-4">
                        <p class="text-xs text-gray-500">Pastikan rincian kebutuhan permohonan peminjaman armada Anda sudah benar sebelum dikirimkan:</p>

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
                                <span>Ya, Kirim Pengajuan</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>