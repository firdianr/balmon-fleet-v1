<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    {{ __('Edit Permohonan Peminjaman Armada') }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Perbarui detail permohonan kendaraan dinas (Nomor Dokumen: {{ $booking->letter_number }}).
                </p>
            </div>
            <a href="{{ route('bookings.index') }}" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold rounded-lg transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-gray-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"/>
                    <polyline points="12 19 5 12 12 5"/>
                </svg>
                <span>Kembali ke Riwayat Booking</span>
            </a>
        </div>
    </x-slot>

    @php
        $isSameDay = $booking->start_date && $booking->end_date ? $booking->start_date->format('Y-m-d') === $booking->end_date->format('Y-m-d') : true;

        $originalSnapshot = [
            'letter_type'          => $booking->letter_type ?? 'surat_tugas',
            'letter_number'        => $booking->letter_number ?? '',
            'letter_file_name'     => $booking->letter_file ? basename($booking->letter_file) : '',
            'vehicle_type'         => $booking->vehicle_type ?? 'operational',
            'vehicle_id'           => $booking->vehicle_id ? (string) $booking->vehicle_id : '',
            'vehicle_label'        => $booking->vehicle
                ? ($booking->vehicle->brand . ' ' . $booking->vehicle->model . ' (' . $booking->vehicle->plate_number . ')')
                : ($booking->vehicle_type === 'operational' ? 'Ditentukan Admin Lapangan' : '-'),
            'vehicle_requirements' => $booking->vehicle_requirements ?? '',
            'start_date'           => $booking->start_date ? $booking->start_date->format('Y-m-d H:i:00') : '',
            'end_date'             => $booking->end_date ? $booking->end_date->format('Y-m-d H:i:00') : '',
            'destination'          => $booking->destination ?? '',
            'purpose'              => $booking->purpose ?? '',
            'participants'         => collect($booking->participants ?? [])->filter()->map(fn($id) => (string) $id)->values()->toArray(),
        ];
    @endphp

    <div class="py-8" x-data="{
        documentType: {{ Illuminate\Support\Js::from(old('letter_type', $booking->letter_type ?? 'surat_tugas')) }},
        vehicleType: {{ Illuminate\Support\Js::from(old('vehicle_type', $booking->vehicle_type)) }},
        selectedVehicleId: {{ Illuminate\Support\Js::from((string) old('vehicle_id', $booking->vehicle_id ?? '')) }},
        showConfirmModal: false,
        
        // Master Data & Snapshot Data Awal (Sebelum Edit)
        users: {{ Illuminate\Support\Js::from($users) }},
        monitoringVehicles: {{ Illuminate\Support\Js::from($vehicles) }},
        originalData: {{ Illuminate\Support\Js::from($originalSnapshot) }},
        
        // Form Fields State
        letterNumber: {{ Illuminate\Support\Js::from(old('letter_number', $booking->letter_number)) }},
        newLetterFileName: '',
        vehicleRequirements: {{ Illuminate\Support\Js::from(old('vehicle_requirements', $booking->vehicle_requirements ?? '')) }},
        destination: {{ Illuminate\Support\Js::from(old('destination', $booking->destination)) }},
        purpose: {{ Illuminate\Support\Js::from(old('purpose', $booking->purpose)) }},

        // Mode Perjalanan
        bookingType: '{{ old('booking_type', $isSameDay ? 'single_day' : 'multi_day') }}',
        singleDate: '{{ old('single_date', $booking->start_date ? $booking->start_date->format('Y-m-d') : '') }}',
        startTime: '{{ old('start_time', $booking->start_date ? $booking->start_date->format('H:i') : '08:00') }}',
        endTime: '{{ old('end_time', $booking->end_date ? $booking->end_date->format('H:i') : '17:00') }}',

        // State Multi Hari
        startDateOnlyMulti: '{{ old('start_date_only_multi', $booking->start_date ? $booking->start_date->format('Y-m-d') : '') }}',
        startTimeMulti: '{{ old('start_time_multi', $booking->start_date ? $booking->start_date->format('H:i') : '08:00') }}',
        endDateOnlyMulti: '{{ old('end_date_only_multi', $booking->end_date ? $booking->end_date->format('Y-m-d') : '') }}',
        endTimeMulti: '{{ old('end_time_multi', $booking->end_date ? $booking->end_date->format('H:i') : '17:00') }}',

        get finalStartDate() {
            if (this.bookingType === 'single_day') {
                return this.singleDate ? `${this.singleDate} ${this.startTime}:00` : '';
            }
            return (this.startDateOnlyMulti && this.startTimeMulti) ? `${this.startDateOnlyMulti} ${this.startTimeMulti}:00` : '';
        },

        get finalEndDate() {
            if (this.bookingType === 'single_day') {
                return this.singleDate ? `${this.singleDate} ${this.endTime}:00` : '';
            }
            return (this.endDateOnlyMulti && this.endTimeMulti) ? `${this.endDateOnlyMulti} ${this.endTimeMulti}:00` : '';
        },

        formatHumanDate(dateTimeString) {
            if (!dateTimeString) return '-';
            const date = new Date(dateTimeString.replace(' ', 'T'));
            if (isNaN(date.getTime())) return dateTimeString;

            const formattedDate = new Intl.DateTimeFormat('id-ID', {
                day: 'numeric',
                month: 'long',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            }).format(date);

            return formattedDate.replace('.', ':') + ' WIB';
        },

        // Dynamic Selected Team Members
        selectedUserIds: {{ Illuminate\Support\Js::from(old('participants', !empty($booking->participants) ? $booking->participants : [''])) }}.map(id => String(id)),

        addMember() {
            this.selectedUserIds.push('');
        },

        removeMember(index) {
            if (this.selectedUserIds.length > 1) {
                this.selectedUserIds.splice(index, 1);
            }
        },

        getAvailableUsers(currentIndex) {
            return this.users.filter(u => {
                const isSelectedElsewhere = this.selectedUserIds.some((selectedId, idx) => {
                    return idx !== currentIndex && String(selectedId) === String(u.id);
                });
                return !isSelectedElsewhere;
            });
        },

        getSelectedVehicleDetail() {
            if (this.vehicleType === 'monitoring' && this.selectedVehicleId) {
                return this.monitoringVehicles.find(v => String(v.id) === String(this.selectedVehicleId));
            }
            return null;
        },

        formatParticipantNames(ids) {
            const validIds = (ids || []).filter(id => id !== '');
            if (validIds.length === 0) return 'Tidak ada anggota';
            return validIds.map(id => {
                const found = this.users.find(u => String(u.id) === String(id));
                return found ? found.name : `User #${id}`;
            }).join(', ');
        },

        // Komputasi Daftar Perubahan (Perbandingan Sebelum vs Sesudah)
        get changedFields() {
            const diffs = [];
            const orig = this.originalData;

            if (orig.letter_type !== this.documentType) {
                diffs.push({
                    label: 'Jenis Dokumen',
                    oldVal: orig.letter_type === 'nota_dinas' ? 'Nota Dinas' : 'Surat Tugas',
                    newVal: this.documentType === 'nota_dinas' ? 'Nota Dinas' : 'Surat Tugas',
                });
            }

            if (this.newLetterFileName) {
                diffs.push({
                    label: 'Berkas Dokumen (PDF)',
                    oldVal: orig.letter_file_name || 'Belum ada file',
                    newVal: this.newLetterFileName,
                });
            }

            if ((orig.letter_number || '').trim() !== (this.letterNumber || '').trim()) {
                diffs.push({
                    label: 'Nomor Dokumen',
                    oldVal: orig.letter_number || '-',
                    newVal: this.letterNumber || '-',
                });
            }

            if (orig.vehicle_type !== this.vehicleType) {
                diffs.push({
                    label: 'Tipe Armada',
                    oldVal: orig.vehicle_type === 'monitoring' ? 'Monitoring' : 'Operasional',
                    newVal: this.vehicleType === 'monitoring' ? 'Monitoring' : 'Operasional',
                });
            }

            const currentVehId = this.vehicleType === 'monitoring' ? String(this.selectedVehicleId || '') : '';
            if (String(orig.vehicle_id || '') !== currentVehId) {
                const newVeh = this.getSelectedVehicleDetail();
                const newVehLabel = this.vehicleType === 'operational'
                    ? 'Ditentukan Admin Lapangan'
                    : (newVeh ? `${newVeh.brand} ${newVeh.model} (${newVeh.plate_number})` : 'Belum dipilih');

                diffs.push({
                    label: 'Unit Armada',
                    oldVal: orig.vehicle_label || '-',
                    newVal: newVehLabel,
                });
            }

            if ((orig.vehicle_requirements || '').trim() !== (this.vehicleRequirements || '').trim()) {
                diffs.push({
                    label: 'Catatan Kebutuhan Kendaraan',
                    oldVal: orig.vehicle_requirements || '(Kosong)',
                    newVal: this.vehicleRequirements || '(Kosong)',
                });
            }

            if (orig.start_date !== this.finalStartDate) {
                diffs.push({
                    label: 'Waktu Mulai',
                    oldVal: this.formatHumanDate(orig.start_date),
                    newVal: this.formatHumanDate(this.finalStartDate),
                });
            }

            if (orig.end_date !== this.finalEndDate) {
                diffs.push({
                    label: 'Waktu Selesai',
                    oldVal: this.formatHumanDate(orig.end_date),
                    newVal: this.formatHumanDate(this.finalEndDate),
                });
            }

            if ((orig.destination || '').trim() !== (this.destination || '').trim()) {
                diffs.push({
                    label: 'Tujuan Perjalanan',
                    oldVal: orig.destination || '-',
                    newVal: this.destination || '-',
                });
            }

            if ((orig.purpose || '').trim() !== (this.purpose || '').trim()) {
                diffs.push({
                    label: 'Maksud / Keperluan',
                    oldVal: orig.purpose || '-',
                    newVal: this.purpose || '-',
                });
            }

            const origParticipants = [...(orig.participants || [])].map(String).filter(Boolean).sort();
            const currParticipants = [...this.selectedUserIds].map(String).filter(Boolean).sort();
            if (JSON.stringify(origParticipants) !== JSON.stringify(currParticipants)) {
                diffs.push({
                    label: 'Anggota Tim / Pengikut',
                    oldVal: this.formatParticipantNames(orig.participants),
                    newVal: this.formatParticipantNames(this.selectedUserIds),
                });
            }

            return diffs;
        },

        // Signature Pad State
        signaturePad: null,
        signatureData: '',
        signatureError: '',

        initSignaturePad() {
            this.$nextTick(() => {
                setTimeout(() => {
                    const canvas = this.$refs.signatureCanvas;
                    if (!canvas) return;

                    const width = canvas.offsetWidth || canvas.parentElement.clientWidth || 400;
                    const height = 160;

                    const ratio = Math.max(window.devicePixelRatio || 1, 1);
                    canvas.width = width * ratio;
                    canvas.height = height * ratio;
                    
                    const ctx = canvas.getContext('2d');
                    ctx.scale(ratio, ratio);

                    const SignaturePadClass = window.SignaturePad || SignaturePad;
                    
                    if (!this.signaturePad) {
                        this.signaturePad = new SignaturePadClass(canvas, {
                            backgroundColor: 'rgba(255, 255, 255)',
                            penColor: 'rgb(0, 0, 128)',
                            minWidth: 1.5,
                            maxWidth: 3.5
                        });
                    } else {
                        this.signaturePad.clear();
                    }
                    this.signatureError = '';
                }, 150);
            });
        },

        clearSignature() {
            if (this.signaturePad) {
                this.signaturePad.clear();
                this.signatureError = '';
            }
        },

        submitForm() {
            if (!this.signaturePad || this.signaturePad.isEmpty()) {
                this.signatureError = 'Tanda tangan wajib diisi sebelum menyimpan perubahan permohonan!';
                return;
            }

            this.signatureError = '';
            this.signatureData = this.signaturePad.toDataURL('image/png');

            this.$nextTick(() => {
                this.$refs.editBookingForm.submit();
            });
        }
    }">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- FORM EDIT PERMOHONAN --}}
            <div class="bg-white pt-2 pb-6 sm:p-8 rounded-xl border border-gray-200 shadow-2xs">
                <form x-ref="editBookingForm" 
                    action="{{ route('bookings.update', $booking) }}" 
                    method="POST" 
                    enctype="multipart/form-data" 
                    @submit.prevent="showConfirmModal = true; initSignaturePad();" 
                    class="space-y-6">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="letter_type" x-model="documentType">
                    <input type="hidden" name="booking_type" x-model="bookingType">
                    <input type="hidden" name="start_date" :value="finalStartDate">
                    <input type="hidden" name="end_date" :value="finalEndDate">
                    <input type="hidden" name="signature" x-model="signatureData">

                    {{-- ERROR SERVER --}}
                    @if ($errors->any())
                        <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 space-y-2">
                            <div class="flex items-center gap-2 font-bold text-rose-900 text-sm">
                                <svg class="w-4 h-4 text-rose-600 shrink-0 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>Terjadi kesalahan pada inputan Anda:</span>
                            </div>
                            <ul class="list-disc list-inside space-y-1 pl-1 font-medium">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- PILIH JENIS DOKUMEN --}}
                    <div>
                        <h3 class="text-base font-bold text-gray-900 border-b pb-2 mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                                <polyline points="10 9 9 9 8 9"/>
                            </svg>
                            <span>Dokumen Dasar Peminjaman</span>
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                            <label class="relative flex flex-col p-4 bg-white border-2 rounded-xl cursor-pointer transition hover:border-indigo-300"
                                   :class="documentType === 'surat_tugas' ? 'border-indigo-600 bg-indigo-50/40 ring-2 ring-indigo-500/20' : 'border-gray-200'">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2 font-bold text-sm text-gray-900">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-indigo-600 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                                            <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                                        </svg>
                                        <span>Surat Tugas</span>
                                    </div>
                                    <input type="radio" name="document_type_radio" value="surat_tugas" x-model="documentType" class="text-indigo-600 focus:ring-indigo-500">
                                </div>
                                <p class="text-xs text-gray-500 leading-relaxed">
                                    Dokumen resmi Surat Tugas yang diterbitkan dan ditandatangani Pejabat.
                                </p>
                            </label>

                            <label class="relative flex flex-col p-4 bg-white border-2 rounded-xl cursor-pointer transition hover:border-indigo-300"
                                   :class="documentType === 'nota_dinas' ? 'border-amber-500 bg-amber-50/40 ring-2 ring-amber-500/20' : 'border-gray-200'">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2 font-bold text-sm text-gray-900">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-600 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14 2 14 8 20 8"/>
                                        </svg>
                                        <span>Nota Dinas</span>
                                    </div>
                                    <input type="radio" name="document_type_radio" value="nota_dinas" x-model="documentType" class="text-amber-600 focus:ring-amber-500">
                                </div>
                                <p class="text-xs text-gray-500 leading-relaxed">
                                    Peminjaman berbasis Nota Dinas internal (Surat Tugas disusulkan saat pengembalian).
                                </p>
                            </label>
                        </div>
                    </div>

                    {{-- INFORMASI BERKAS & NOMOR SURAT --}}
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">
                                <span x-text="documentType === 'nota_dinas' ? 'Berkas Nota Dinas (PDF)' : 'Berkas Surat Tugas (PDF)'"></span>
                            </label>

                            @if($booking->letter_file)
                                <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2.5">
                                        <div class="p-2 bg-emerald-600 text-white rounded-lg shadow-2xs">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="20 6 9 17 4 12"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-emerald-900">Berkas Dokumen Terpasang</p>
                                            <p class="text-[11px] text-emerald-700 font-mono">{{ basename($booking->letter_file) }}</p>
                                        </div>
                                    </div>
                                    <a href="{{ route('bookings.files.letter', $booking->letter_slug) }}" target="_blank" class="text-xs text-indigo-600 hover:text-indigo-800 font-bold px-2 py-1 rounded hover:bg-indigo-50 transition flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                            <polyline points="15 3 21 3 21 9"/>
                                            <line x1="10" y1="14" x2="21" y2="3"/>
                                        </svg>
                                        <span>Lihat Berkas</span>
                                    </a>
                                </div>
                            @endif

                            <input type="file" name="letter_file" accept="application/pdf"
                                @change="newLetterFileName = $event.target.files[0]?.name || ''"
                                class="block w-full text-xs text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition cursor-pointer">
                            <p class="text-[11px] text-gray-500 mt-1">Kosongkan jika tidak ingin mengganti berkas yang sudah ada (Format .PDF, Max 5MB).</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700">
                                <span x-text="documentType === 'nota_dinas' ? 'Nomor Nota Dinas' : 'Nomor Surat Tugas'"></span> <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="letter_number" x-model="letterNumber" placeholder="Contoh: xxx/Balmon.xx/KP.xx.xx/xx/20xx" required
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-2xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 text-sm font-mono font-bold">
                        </div>
                    </div>

                    {{-- 1. PEMILIHAN JENIS KENDARAAN DINAS --}}
                    <div>
                        <h3 class="text-base font-bold text-gray-900 border-b pb-2 mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A2 2 0 0 0 2 12v4c0 .6.4 1 1 1h2"/>
                                <circle cx="7" cy="17" r="2"/>
                                <path d="M9 17h6"/>
                                <circle cx="17" cy="17" r="2"/>
                            </svg>
                            <span>Kebutuhan Jenis Armada</span>
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <label class="relative flex flex-col p-4 bg-white border-2 rounded-xl cursor-pointer transition hover:border-indigo-300"
                                   :class="vehicleType === 'operational' ? 'border-indigo-600 bg-indigo-50/40 ring-2 ring-indigo-500/20' : 'border-gray-200'">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2 font-bold text-sm text-gray-900">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="1" y="3" width="15" height="13" rx="2"/>
                                            <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                                            <circle cx="5.5" cy="18.5" r="2.5"/>
                                            <circle cx="18.5" cy="18.5" r="2.5"/>
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
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M2 12h5l2 5 3-10 3 7 2-2h5"/>
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
                        <label class="block text-sm font-bold text-indigo-950 flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"/>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                            <span>Pilih Mobil Monitoring</span> <span class="text-rose-500">*</span>
                        </label>

                        <select name="vehicle_id" x-model="selectedVehicleId" :required="vehicleType === 'monitoring'" 
                                class="block w-full rounded-lg border-gray-300 shadow-2xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 text-sm font-medium">
                            <option value="">-- Pilih Unit Armada Monitoring --</option>
                            <template x-for="v in monitoringVehicles" :key="v.id">
                                <option :value="v.id" :selected="String(v.id) === String(selectedVehicleId)" x-text="v.brand + ' ' + v.model + ' (' + v.plate_number + ')'"></option>
                            </template>
                        </select>
                    </div>

                    {{-- CATATAN SPESIFIKASI KENDARAAN --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                            <span>Catatan Kebutuhan Kendaraan</span>
                        </label>
                        <textarea name="vehicle_requirements" x-model="vehicleRequirements" rows="2" 
                            placeholder="Contoh: Rute pegunungan menanjak tinggi (butuh 4WD) atau perjalanan dalam kota." 
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-2xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 text-sm font-medium"></textarea>
                    </div>

                    {{-- 2. JADWAL & DESTINASI --}}
                    <div>
                        <h3 class="text-base font-bold text-gray-900 border-b pb-2 mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            <span>Waktu & Destinasi Perjalanan</span>
                        </h3>

                        {{-- TOGGLE MODE WAKTU --}}
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-200/80 mb-4 flex items-center gap-4">
                            <span class="text-xs font-bold text-gray-600 uppercase flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-gray-500 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
                                <span>Durasi Perjalanan:</span>
                            </span>
                            <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-bold text-gray-800">
                                <input type="radio" x-model="bookingType" value="single_day" class="text-indigo-600 focus:ring-indigo-500">
                                <span>1 Hari (PP)</span>
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-bold text-gray-800">
                                <input type="radio" x-model="bookingType" value="multi_day" class="text-indigo-600 focus:ring-indigo-500">
                                <span>Lebih dari 1 Hari</span>
                            </label>
                        </div>

                        {{-- INPUT 1 HARI (PP) --}}
                        <div x-show="bookingType === 'single_day'" x-transition class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Tanggal Perjalanan <span class="text-rose-500">*</span></label>
                                <input type="date" id="single_date" x-model="singleDate" :required="bookingType === 'single_day'" class="w-full rounded-lg border-gray-300 text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Jam Berangkat <span class="text-rose-500">*</span></label>
                                <select x-model="startTime" :required="bookingType === 'single_day'" class="w-full rounded-lg border-gray-300 text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer">
                                    @for ($h = 0; $h < 24; $h++)
                                        @foreach (['00', '30'] as $m)
                                            @php $timeVal = sprintf('%02d:%s', $h, $m); @endphp
                                            <option value="{{ $timeVal }}">{{ $timeVal }} WIB</option>
                                        @endforeach
                                    @endfor
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Jam Est. Kembali <span class="text-rose-500">*</span></label>
                                <select x-model="endTime" :required="bookingType === 'single_day'" class="w-full rounded-lg border-gray-300 text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer">
                                    @for ($h = 0; $h < 24; $h++)
                                        @foreach (['00', '30'] as $m)
                                            @php $timeVal = sprintf('%02d:%s', $h, $m); @endphp
                                            <option value="{{ $timeVal }}">{{ $timeVal }} WIB</option>
                                        @endforeach
                                    @endfor
                                </select>
                            </div>
                        </div>

                        {{-- INPUT MULTI HARI --}}
                        <div x-show="bookingType === 'multi_day'" x-transition class="space-y-4 mb-4" x-cloak>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-3 bg-gray-50 rounded-xl border border-gray-200/80">
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Tanggal Mulai <span class="text-rose-500">*</span></label>
                                    <input type="date" id="start_date_multi" x-model="startDateOnlyMulti" :required="bookingType === 'multi_day'" class="w-full rounded-lg border-gray-300 text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Jam Mulai <span class="text-rose-500">*</span></label>
                                    <select x-model="startTimeMulti" :required="bookingType === 'multi_day'" class="w-full rounded-lg border-gray-300 text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer">
                                        @for ($h = 0; $h < 24; $h++)
                                            @foreach (['00', '30'] as $m)
                                                @php $timeVal = sprintf('%02d:%s', $h, $m); @endphp
                                                <option value="{{ $timeVal }}">{{ $timeVal }} WIB</option>
                                            @endforeach
                                        @endfor
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-3 bg-gray-50 rounded-xl border border-gray-200/80">
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Tanggal Selesai <span class="text-rose-500">*</span></label>
                                    <input type="date" id="end_date_multi" x-model="endDateOnlyMulti" :required="bookingType === 'multi_day'" class="w-full rounded-lg border-gray-300 text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Jam Selesai <span class="text-rose-500">*</span></label>
                                    <select x-model="endTimeMulti" :required="bookingType === 'multi_day'" class="w-full rounded-lg border-gray-300 text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer">
                                        @for ($h = 0; $h < 24; $h++)
                                            @foreach (['00', '30'] as $m)
                                                @php $timeVal = sprintf('%02d:%s', $h, $m); @endphp
                                                <option value="{{ $timeVal }}">{{ $timeVal }} WIB</option>
                                            @endforeach
                                        @endfor
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <span>Tujuan Perjalanan Dinas</span> <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="destination" x-model="destination" placeholder="Contoh: Kota Semarang" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-2xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 text-sm font-medium">
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                    </svg>
                                    <span>Maksud / Keperluan Perjalanan</span> <span class="text-rose-500">*</span>
                                </label>
                                <textarea name="purpose" x-model="purpose" rows="3" placeholder="Jelaskan maksud agenda kegiatan..." required class="mt-1 block w-full rounded-lg border-gray-300 shadow-2xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 text-sm font-medium"></textarea>
                            </div>
                        </div>
                    </div>

                    {{-- 3. DROPDOWN ANGGOTA TIM DINAMIS --}}
                    <div>
                        <div class="flex items-center justify-between border-b pb-2 mb-4">
                            <div>
                                <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                        <circle cx="9" cy="7" r="4"/>
                                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                    </svg>
                                    <span>Anggota Tim / Pengikut Dinas</span>
                                </h3>
                                <p class="text-xs text-gray-500">Pilih anggota tim dari pegawai, admin lapangan, atau driver terdaftar.</p>
                            </div>
                            <button type="button" @click="addMember()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-lg text-xs font-bold transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="5" x2="12" y2="19"/>
                                    <line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                                <span>Tambah Anggota</span>
                            </button>
                        </div>

                        <div class="space-y-3">
                            <template x-for="(selectedId, index) in selectedUserIds" :key="index">
                                <div class="p-3 bg-gray-50 rounded-xl border border-gray-200 flex items-center gap-3">
                                    <span class="text-xs font-bold text-gray-400 w-6" x-text="'#' + (index + 1)"></span>
                                    
                                    <div class="flex-grow">
                                        <select name="participants[]" x-model="selectedUserIds[index]" class="block w-full rounded-lg border-gray-300 text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500">
                                            <option value="">-- Pilih Anggota Tim --</option>
                                            <template x-for="u in getAvailableUsers(index)" :key="u.id">
                                                <option :value="u.id" :selected="String(u.id) === String(selectedUserIds[index])" x-text="u.name + (u.nip ? ' (NIP: ' + u.nip + ')' : '') + ' - ' + u.role.toUpperCase()"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <button type="button" @click="removeMember(index)" x-show="selectedUserIds.length > 1" class="text-rose-600 hover:text-rose-800 p-1 font-bold rounded hover:bg-rose-50 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"/>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- SUBMIT --}}
                    <div class="flex items-center justify-end gap-3 pt-4 border-t">
                        <a href="{{ route('bookings.index') }}" class="px-5 py-2.5 bg-gray-100 text-gray-700 text-sm font-bold rounded-lg hover:bg-gray-200 transition">
                            Batal
                        </a>
                        <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 shadow-2xs transition flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                                <polyline points="17 21 17 13 7 13 7 21"/>
                                <polyline points="7 3 7 8 15 8"/>
                            </svg>
                            <span>Lanjutkan Pembaruan</span>
                        </button>
                    </div>

                </form>
            </div>
        </div>
        

        {{-- MODAL KONFIRMASI PERUBAHAN & TANDA TANGAN --}}
        <div x-show="showConfirmModal" 
            class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50" 
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            x-cloak>
            
            <div class="bg-white rounded-2xl shadow-2xl max-w-xl w-full overflow-hidden max-h-[90vh] flex flex-col" @click.away="showConfirmModal = false">
                {{-- Header Modal --}}
                <div class="bg-indigo-600 px-6 py-4 text-white flex justify-between items-center shrink-0">
                    <h3 class="font-bold text-base flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-indigo-100 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                        <span>Konfirmasi Perubahan & Tanda Tangan</span>
                    </h3>
                    <button type="button" @click="showConfirmModal = false" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>

                {{-- Body Modal (Scrollable) --}}
                <div class="p-6 space-y-4 overflow-y-auto flex-1">
                    <p class="text-xs text-gray-600 leading-relaxed">
                        Periksa perbandingan data yang diubah dan bubuhkan tanda tangan pengesahan terbaru Anda di bawah ini:
                    </p>

                    {{-- 1. RINGKASAN PERBANDINGAN PERUBAHAN (SEBELUM vs SESUDAH) --}}
                    <div class="bg-amber-50/60 border border-amber-200 rounded-xl p-3.5 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold text-amber-950 flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-600 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="16 3 21 3 21 8"/>
                                    <line x1="4" y1="20" x2="21" y2="3"/>
                                    <polyline points="21 16 21 21 16 21"/>
                                    <line x1="15" y1="15" x2="21" y2="21"/>
                                    <line x1="4" y1="4" x2="9" y2="9"/>
                                </svg>
                                <span>Ringkasan Perubahan Data</span>
                            </h4>
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-300"
                                    x-text="changedFields.length + ' Perubahan'"></span>
                        </div>

                        <template x-if="changedFields.length === 0">
                            <p class="text-[11px] text-amber-800 italic bg-white/80 p-2.5 rounded-lg border border-amber-200/70">
                                Tidak ada perubahan pada kolom isian. Menyimpan akan memperbarui tanda tangan pemohon dan meregenerasi arsip dokumen PDF terbaru.
                            </p>
                        </template>

                        <template x-if="changedFields.length > 0">
                            <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                                <template x-for="(diff, idx) in changedFields" :key="idx">
                                    <div class="bg-white p-2.5 rounded-lg border border-amber-200/80 text-[11px] space-y-1">
                                        <div class="font-bold text-gray-800" x-text="diff.label"></div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                                            <div class="bg-rose-50/80 text-rose-800 px-2 py-1 rounded border border-rose-200/70">
                                                <span class="block text-[9px] font-bold uppercase tracking-wider text-rose-500">Sebelum</span>
                                                <span class="line-through break-words" x-text="diff.oldVal"></span>
                                            </div>
                                            <div class="bg-emerald-50/90 text-emerald-900 px-2 py-1 rounded border border-emerald-200/80">
                                                <span class="block text-[9px] font-bold uppercase tracking-wider text-emerald-600">Sesudah</span>
                                                <span class="font-semibold break-words" x-text="diff.newVal"></span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    {{-- 2. RINGKASAN DATA TERKINI --}}
                    <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-200/90 text-xs space-y-2">
                        <div class="flex justify-between gap-4 border-b border-gray-200/60 pb-1.5">
                            <span class="text-gray-500 font-medium shrink-0">Jenis Dokumen:</span>
                            <span class="font-bold text-indigo-900 text-right" x-text="documentType === 'nota_dinas' ? 'Nota Dinas' : 'Surat Tugas'"></span>
                        </div>

                        <div class="flex justify-between gap-4 border-b border-gray-200/60 pb-1.5">
                            <span class="text-gray-500 font-medium shrink-0">Nomor Dokumen:</span>
                            <span class="font-mono font-bold text-gray-800 text-right break-all" x-text="letterNumber || '-'"></span>
                        </div>

                        <div class="flex justify-between gap-4 border-b border-gray-200/60 pb-1.5">
                            <span class="text-gray-500 font-medium shrink-0">Armada:</span>
                            <span class="font-bold text-gray-900 text-right"
                                x-text="vehicleType === 'monitoring'
                                    ? (getSelectedVehicleDetail() ? (getSelectedVehicleDetail().brand + ' ' + getSelectedVehicleDetail().model + ' (' + getSelectedVehicleDetail().plate_number + ')') : 'Belum dipilih')
                                    : 'Operasional (Ditentukan Admin Lapangan)'"></span>
                        </div>

                        <div class="flex justify-between gap-4 border-b border-gray-200/60 pb-1.5">
                            <span class="text-gray-500 font-medium shrink-0">Waktu Mulai:</span>
                            <span class="font-bold text-gray-800 text-right" x-text="formatHumanDate(finalStartDate)"></span>
                        </div>

                        <div class="flex justify-between gap-4 border-b border-gray-200/60 pb-1.5">
                            <span class="text-gray-500 font-medium shrink-0">Waktu Selesai:</span>
                            <span class="font-bold text-gray-800 text-right" x-text="formatHumanDate(finalEndDate)"></span>
                        </div>

                        <div class="flex justify-between gap-4 border-b border-gray-200/60 pb-1.5">
                            <span class="text-gray-500 font-medium shrink-0">Tujuan:</span>
                            <span class="font-bold text-gray-900 text-right break-words" x-text="destination || '-'"></span>
                        </div>

                        <div class="flex justify-between gap-4 border-b border-gray-200/60 pb-1.5">
                            <span class="text-gray-500 font-medium shrink-0">Keperluan:</span>
                            <span class="font-bold text-gray-900 text-right break-words" x-text="purpose || '-'"></span>
                        </div>

                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500 font-medium shrink-0">Anggota Tim:</span>
                            <span class="font-bold text-indigo-700 text-right" x-text="selectedUserIds.filter(id => id !== '').length + ' Orang'"></span>
                        </div>
                    </div>

                    {{-- 3. ARENA TANDA TANGAN (SIGNATURE PAD) --}}
                    <div class="space-y-2 pt-1">
                        <div class="flex items-center justify-between gap-2">
                            <label class="text-xs font-bold text-gray-700 flex items-center gap-1 leading-snug">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-indigo-600 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 20h9"/>
                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                                </svg>
                                <span>Tanda Tangan Pengesahan Perubahan</span> 
                                <span class="text-rose-500">*</span>
                            </label>

                            <button type="button" 
                                    @click="clearSignature()" 
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-600 hover:text-rose-700 border border-rose-200 rounded-lg text-[11px] font-bold transition shrink-0 shadow-2xs cursor-pointer">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 6h18"/>
                                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                </svg>
                                <span>Bersihkan</span>
                            </button>
                        </div>

                        <div class="p-2.5 bg-indigo-50/70 border border-indigo-200/80 rounded-xl text-[11px] text-indigo-950 flex items-start gap-2 leading-relaxed">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-indigo-600 fill-none stroke-current stroke-2 shrink-0 mt-0.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                <path d="m9 12 2 2 4-4"/>
                            </svg>
                            <div>
                                <p><strong>Pembaruan TTD &amp; Arsip Otomatis:</strong> Berkas tanda tangan lama akan digantikan dengan tanda tangan terbaru di <em>private storage</em>, dan dokumen PDF final (<strong>Formulir Permohonan</strong> maupun <strong>BAST</strong>) langsung diperbarui serta dienkripsi ulang dengan <strong>AES-256-CBC</strong> dan <em>checksum</em> <strong>SHA-256</strong>.</p>
                            </div>
                        </div>

                        <div class="border-2 border-dashed border-gray-300 rounded-xl bg-white p-1 relative">
                            <canvas x-ref="signatureCanvas" 
                                style="mix-blend-mode: normal;"
                                class="w-full h-40 bg-white rounded-lg cursor-pointer touch-none block"></canvas>
                        </div>

                        <template x-if="signatureError">
                            <p class="text-[11px] font-bold text-rose-600 mt-1" x-text="signatureError"></p>
                        </template>
                    </div>

                    {{-- Tombol Aksi --}}
                    <div class="flex items-center gap-3 pt-2">
                        <button type="button" @click="showConfirmModal = false" class="w-1/2 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition">
                            Periksa Kembali
                        </button>
                        <button type="button" @click="submitForm()" class="w-1/2 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-2xs transition flex items-center justify-center gap-1.5">
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