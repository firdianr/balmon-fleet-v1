<x-app-layout>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .autofill-highlight {
            animation: highlightGreen 2s ease-out;
        }
        @keyframes highlightGreen {
            0% {
                border-color: #22c55e;
                box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.25);
            }
            100% {
                border-color: #d1d5db;
                box-shadow: none;
            }
        }
    </style>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    Lanjutkan ke Peminjaman Resmi
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Konversi draft permohonan ke pengajuan peminjaman kendaraan dinas resmi.
                </p>
            </div>
            <a href="{{ route('draft-bookings.index') }}" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold rounded-lg transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-gray-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"/>
                    <polyline points="12 19 5 12 12 5"/>
                </svg>
                <span>Kembali ke Draft</span>
            </a>
        </div>
    </x-slot>

    @php
        $draftStart = \Carbon\Carbon::parse($draftBooking->start_date);
        $draftEnd   = \Carbon\Carbon::parse($draftBooking->end_date);
        $isSameDay  = $draftStart->isSameDay($draftEnd);
        $bookingType = $isSameDay ? 'single_day' : 'multi_day';
    @endphp

    <div class="pt-2 pb-6" x-data="{
        showConfirmModal: false,
        isParsing: false,
        
        // Pilihan Jenis Dokumen (Surat Tugas / Nota Dinas)
        documentType: 'surat_tugas',

        // Data Master Users
        users: {{ Illuminate\Support\Js::from($users) }},

        // Autofill Data awal diambil dari Draft
        letterNumber: '{{ old('letter_number', '') }}',
        letterFile: '{{ old('letter_file_temp', '') }}',
        destination: '{{ old('destination', '') }}',
        purpose: '{{ old('purpose', '') }}',

        // Mode Jadwal & Prefill dari Draft
        bookingType: '{{ $bookingType }}',
        singleDate: '{{ $draftStart->format('Y-m-d') }}',
        startTime: '{{ $draftStart->format('H:i') }}',
        endTime: '{{ $draftEnd->format('H:i') }}',

        startDateOnlyMulti: '{{ $draftStart->format('Y-m-d') }}',
        startTimeMulti: '{{ $draftStart->format('H:i') }}',
        endDateOnlyMulti: '{{ $draftEnd->format('Y-m-d') }}',
        endTimeMulti: '{{ $draftEnd->format('H:i') }}',

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
            
            // Mengubah string tanggal menjadi objek Date
            const date = new Date(dateTimeString.replace(' ', 'T'));
            if (isNaN(date.getTime())) return dateTimeString; // Fallback jika format tidak valid

            // Format tanggal ke Bahasa Indonesia
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

        get vehicleType() {
            return '{{ $draftBooking->vehicle ? $draftBooking->vehicle->type : 'operational' }}';
        },

        selectedUserIds: {{ Illuminate\Support\Js::from(old('participants', [''])) }}.map(id => String(id)),

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

        showToast(icon, title, text = '') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: icon,
                title: title,
                text: text,
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true
            });
        },

        triggerHighlight() {
            this.$nextTick(() => {
                const fields = ['letter_number', 'destination', 'purpose', 'single_date', 'start_date_multi', 'end_date_multi'];
                fields.forEach(fieldId => {
                    const el = document.getElementById(fieldId);
                    if (el) {
                        el.classList.remove('autofill-highlight');
                        void el.offsetWidth;
                        el.classList.add('autofill-highlight');
                    }
                });
            });
        },

        async handleFileUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('document', file);
            formData.append('document_type', this.documentType);
            formData.append('_token', '{{ csrf_token() }}');

            this.isParsing = true;
            const labelDoc = this.documentType === 'nota_dinas' ? 'Nota Dinas' : 'Surat Tugas';
            this.showToast('info', `Mengekstrak ${labelDoc}...`);

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

                    if (data.start_date && data.end_date) {
                        const sParts = data.start_date.replace('T', ' ').split(' ');
                        const eParts = data.end_date.replace('T', ' ').split(' ');

                        const sDate = sParts[0];
                        const eDate = eParts[0];
                        const sTime = (sParts[1] && sParts[1].length >= 5) ? sParts[1].substring(0, 5) : '08:00';
                        const eTime = (eParts[1] && eParts[1].length >= 5) ? eParts[1].substring(0, 5) : '17:00';

                        if (sDate === eDate) {
                            this.bookingType = 'single_day';
                            this.singleDate = sDate;
                            this.startTime = sTime;
                            this.endTime = eTime;
                        } else {
                            this.bookingType = 'multi_day';
                            this.startDateOnlyMulti = sDate;
                            this.startTimeMulti = sTime;
                            this.endDateOnlyMulti = eDate;
                            this.endTimeMulti = eTime;
                        }
                    }

                    if (data.user_ids && data.user_ids.length > 0) {
                        this.selectedUserIds = data.user_ids.map(id => String(id));
                    }

                    this.showToast('success', 'Autofill Berhasil!', `Data ${labelDoc} berhasil diekstrak.`);
                    this.triggerHighlight();
                } else {
                    this.showToast('error', 'Gagal Membaca Dokumen', result.message || 'Terjadi kesalahan pada parsing file.');
                }
            } catch (error) {
                this.showToast('error', 'Kesalahan Server', 'Gagal memproses berkas dokumen.');
            } finally {
                this.isParsing = false;
            }
        },

        signaturePad: null,
        signatureData: '',
        signatureError: '',

        initSignaturePad() {
            this.$nextTick(() => {
                setTimeout(() => {
                    const canvas = this.$refs.signatureCanvas;
                    if (!canvas) return;

                    // Dapatkan lebar real dari kontainer canvas
                    const width = canvas.offsetWidth || canvas.parentElement.clientWidth || 400;
                    const height = 160; // Tinggi fixed canvas (px)

                    const ratio = Math.max(window.devicePixelRatio || 1, 1);
                    canvas.width = width * ratio;
                    canvas.height = height * ratio;
                    
                    const ctx = canvas.getContext('2d');
                    ctx.scale(ratio, ratio);

                    // Inisialisasi SignaturePad jika belum ada, atau clear jika sudah ada
                    const SignaturePadClass = window.SignaturePad || SignaturePad;
                    
                    if (!this.signaturePad) {
                        this.signaturePad = new SignaturePadClass(canvas, {
                            backgroundColor: 'rgba(255, 255, 255)', // Transparan
                            penColor: 'rgb(0, 0, 128)',                // Biru Navy
                            minWidth: 1.5,
                            maxWidth: 3.5
                        });
                    } else {
                        this.signaturePad.clear();
                    }
                }, 150); // Delay 150ms memberi waktu CSS modal tampil utuh
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
                this.signatureError = 'Tanda tangan wajib diisi sebelum mengirimkan pengajuan!';
                return;
            }

            this.signatureError = '';
            // Ambil string Base64 PNG dan set ke x-model signatureData
            this.signatureData = this.signaturePad.toDataURL('image/png');

            this.$nextTick(() => {
                this.$refs.bookingForm.submit();
            });
        }
    }">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- 1. INFORMASI ALOKASI KENDARAAN & DRIVER TERKUNCI (DARI DRAFT) --}}
            <div class="bg-indigo-900 text-white p-5 rounded-2xl shadow-md space-y-3">
                <div class="flex items-center justify-between border-b border-indigo-700/80 pb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-300">Alokasi Armada & Driver (Terkunci dari Draft)</span>
                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 border border-emerald-400 text-emerald-300 text-[10px] font-bold uppercase">Disetujui Admin Lapangan</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <p class="text-indigo-300 font-semibold mb-0.5">Unit Armada Kendaraan:</p>
                        <p class="text-sm font-bold text-white">
                            {{ $draftBooking->vehicle ? $draftBooking->vehicle->brand . ' ' . $draftBooking->vehicle->model . ' (' . $draftBooking->vehicle->plate_number . ')' : 'Belum Ditentukan' }}
                        </p>
                        <p class="text-[11px] text-indigo-200 mt-0.5">
                            Penggerak Roda: <span class="font-bold uppercase text-amber-300">{{ $draftBooking->drivetrain ?? '2WD' }}</span>
                        </p>
                    </div>
                    <div>
                        <p class="text-indigo-300 font-semibold mb-0.5">Driver Ditugaskan:</p>
                        <p class="text-sm font-bold text-white">
                            {{ $draftBooking->driver ? $draftBooking->driver->name : 'Lepas Kunci (Tanpa Driver)' }}
                        </p>
                        @if($draftBooking->driver && $draftBooking->driver->phone)
                            <p class="text-[11px] text-indigo-200 mt-0.5">No WA: {{ $draftBooking->driver->phone }}</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- 2. UPLOAD & AUTO-FILL DOKUMEN --}}
            <div class="bg-indigo-50/80 p-5 rounded-xl border border-indigo-200 shadow-xs space-y-4">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <div class="p-2.5 bg-indigo-600 text-white rounded-lg shadow-xs shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 fill-none stroke-current stroke-2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-indigo-950 text-sm">Unggah Dokumen (Auto-Fill)</h4>
                            <p class="text-xs text-indigo-700 mt-0.5">Pilih jenis surat lalu unggah PDF untuk mengisi otomatis form di bawah ini.</p>
                        </div>
                    </div>

                    <label class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow-xs transition shrink-0" :class="{ 'opacity-50 cursor-not-allowed': isParsing }">
                        <template x-if="!isParsing">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        </template>
                        <span x-text="isParsing ? 'Membaca Dokumen...' : 'Unggah PDF Dokumen'"></span>
                        <input type="file" accept="application/pdf" class="hidden" @change="handleFileUpload" :disabled="isParsing">
                    </label>
                </div>

                {{-- PILIHAN JENIS SURAT --}}
                <div class="pt-3 border-t border-indigo-200/60 flex items-center gap-6">
                    <span class="text-xs font-bold text-indigo-950">Jenis Dokumen:</span>
                    <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-bold text-gray-800">
                        <input type="radio" x-model="documentType" value="surat_tugas" class="text-indigo-600 focus:ring-indigo-500">
                        <span>Surat Tugas</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-bold text-gray-800">
                        <input type="radio" x-model="documentType" value="nota_dinas" class="text-indigo-600 focus:ring-indigo-500">
                        <span>Nota Dinas</span>
                    </label>
                </div>
            </div>

            {{-- FORM UTAMA --}}
            <div class="bg-white p-6 sm:p-8 rounded-2xl border border-gray-200 shadow-xs">
                <form x-ref="bookingForm" 
                      action="{{ route('bookings.store_from_draft', $draftBooking->id) }}" 
                      method="POST" 
                      enctype="multipart/form-data" 
                      @submit.prevent="showConfirmModal = true; initSignaturePad();" 
                      class="space-y-6">
                    @csrf

                    <input type="hidden" name="letter_type" x-model="documentType">
                    <input type="hidden" name="letter_file_temp" x-model="letterFile">
                    <input type="hidden" name="booking_type" x-model="bookingType">
                    <input type="hidden" name="vehicle_type" :value="vehicleType">
                    <input type="hidden" name="start_date" :value="finalStartDate">
                    <input type="hidden" name="end_date" :value="finalEndDate">
                    <input type="hidden" name="signature" x-model="signatureData">

                    {{-- BERKAS SURAT --}}
                    <div class="space-y-2">
                        <label class="block text-sm font-bold text-gray-700">
                            Upload Dokumen <span x-text="documentType === 'nota_dinas' ? 'Nota Dinas' : 'Surat Tugas'"></span> (PDF) <span class="text-rose-500">*</span>
                        </label>

                        <div x-show="letterFile" x-transition class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="p-2 bg-emerald-600 text-white rounded-lg shadow-xs">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-emerald-900">Dokumen Berhasil Terhubung</p>
                                    <p class="text-[11px] text-emerald-700 font-mono" x-text="letterFile"></p>
                                </div>
                            </div>
                            <button type="button" @click="letterFile = ''" class="text-xs text-rose-600 hover:text-rose-800 font-bold px-2 py-1 rounded hover:bg-rose-50 transition">
                                Ganti File
                            </button>
                        </div>

                        <div x-show="!letterFile" x-transition>
                            <input type="file" name="letter_file" accept="application/pdf"
                                class="block w-full text-xs text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition cursor-pointer">
                        </div>
                    </div>

                    {{-- NOMOR SURAT --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700">
                            Nomor <span x-text="documentType === 'nota_dinas' ? 'Nota Dinas' : 'Surat Tugas'"></span> <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="letter_number" name="letter_number" x-model="letterNumber" placeholder="Masukkan nomor dokumen lengkap..." required
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-2xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 text-sm font-mono font-bold">
                    </div>

                    {{-- JADWAL & DESTINASI PERJALANAN --}}
                    <div>
                        <h3 class="text-base font-bold text-gray-900 border-b pb-2 mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            <span>Waktu & Destinasi Perjalanan</span>
                        </h3>

                        {{-- TOGGLE MODE WAKTU (DURASI PERJALANAN) --}}
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-200/80 mb-4 flex items-center gap-4">
                            <span class="text-xs font-bold text-gray-600 uppercase flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 fill-none stroke-gray-500 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
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

                        {{-- DESTINASI & MAKSUD PERJALANAN --}}
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <span>Tujuan Perjalanan Dinas</span> <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" id="destination" name="destination" x-model="destination" placeholder="Contoh: Kota Semarang" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-2xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 text-sm font-medium">
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                    </svg>
                                    <span>Maksud / Keperluan Perjalanan</span> <span class="text-rose-500">*</span>
                                </label>
                                <textarea id="purpose" name="purpose" x-model="purpose" rows="3" placeholder="Jelaskan maksud agenda kegiatan..." required class="mt-1 block w-full rounded-lg border-gray-300 shadow-2xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 text-sm font-medium"></textarea>
                            </div>
                        </div>
                    </div>

                    {{-- ANGGOTA TIM --}}
                    <div>
                        <div class="flex items-center justify-between border-b pb-2 mb-4">
                            <h3 class="text-base font-bold text-gray-900">Anggota Tim / Pengikut Dinas</h3>
                            <button type="button" @click="addMember()" class="px-3 py-1.5 bg-indigo-50 text-indigo-700 font-bold rounded-lg text-xs hover:bg-indigo-100 transition">
                                + Tambah Anggota
                            </button>
                        </div>

                        <div class="space-y-3">
                            <template x-for="(selectedId, index) in selectedUserIds" :key="index">
                                <div class="p-3 bg-gray-50 rounded-xl border border-gray-200 flex items-center gap-3">
                                    <span class="text-xs font-bold text-gray-400 w-6" x-text="'#' + (index + 1)"></span>
                                    <div class="flex-grow">
                                        <select name="participants[]" x-model="selectedUserIds[index]" class="block w-full rounded-lg border-gray-300 text-xs font-medium">
                                            <option value="">-- Pilih Anggota Tim --</option>
                                            <template x-for="u in getAvailableUsers(index)" :key="u.id">
                                                <option :value="u.id" x-text="u.name + (u.nip ? ' (NIP: ' + u.nip + ')' : '')"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <button type="button" @click="removeMember(index)" x-show="selectedUserIds.length > 1" class="text-rose-600 p-1 font-bold">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- SUBMIT --}}
                    <div class="flex items-center justify-end gap-3 pt-4 border-t">
                        <a href="{{ route('draft-bookings.index') }}" class="px-5 py-2.5 bg-gray-100 text-gray-700 text-sm font-bold rounded-lg hover:bg-gray-200 transition">
                            Batal
                        </a>
                        <button type="submit" class="px-6 py-2.5 bg-emerald-600 text-white text-sm font-bold rounded-lg hover:bg-emerald-700 shadow-md transition">
                            Konfirmasi & Kirim Peminjaman Resmi
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- MODAL KONFIRMASI PEMINJAMAN RESMI DARI DRAFT --}}
        <div x-show="showConfirmModal" 
            class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 top-0" 
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            x-cloak>
            
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden max-h-[90vh] flex flex-col" @click.away="showConfirmModal = false">
                {{-- Header Modal --}}
                <div class="bg-indigo-600 px-6 py-4 text-white flex justify-between items-center shrink-0">
                    <h3 class="font-bold text-base flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-white stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                            <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                            <path d="m9 14 2 2 4-4"/>
                        </svg>
                        <span>Konfirmasi Permohonan Resmi & Tanda Tangan</span>
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
                        Mohon periksa kembali ringkasan permohonan peminjaman armada resmi di bawah ini dan bubuhkan tanda tangan Anda sebelum dikirimkan:
                    </p>

                    {{-- Ringkasan Data --}}
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-200/90 text-xs space-y-2.5">
                        {{-- BARIS 1: JENIS DOKUMEN --}}
                        <div class="flex justify-between items-center border-b border-gray-200/60 pb-2">
                            <span class="text-gray-500 font-medium text-xs">Jenis Dokumen:</span>
                            <span class="font-bold text-indigo-900 text-xs text-right" 
                                x-text="documentType === 'nota_dinas' ? 'Nota Dinas' : (documentType === 'surat_tugas' ? 'Surat Tugas' : (documentType || '-'))">
                            </span>
                        </div>

                        {{-- BARIS 2: NOMOR DOKUMEN / SURAT --}}
                        <div class="flex justify-between items-center border-b border-gray-200/60 pb-2">
                            <span class="text-gray-500 font-medium text-xs">Nomor Dokumen:</span>
                            <span class="font-mono text-xs font-bold text-gray-700 text-right" 
                                x-text="letterNumber || '-'">
                            </span>
                        </div>

                        <div class="flex justify-between border-b border-gray-200/60 pb-2">
                            <span class="text-gray-500 font-medium">Unit Armada (Alokasi Draft):</span>
                            <span class="font-bold text-gray-900 text-right">
                                {{ $draftBooking->vehicle ? $draftBooking->vehicle->brand . ' ' . $draftBooking->vehicle->model . ' (' . $draftBooking->vehicle->plate_number . ')' : 'Belum Ditentukan' }}
                            </span>
                        </div>

                        <div class="flex justify-between border-b border-gray-200/60 pb-2">
                            <span class="text-gray-500 font-medium">Driver Ditugaskan:</span>
                            <span class="font-bold text-gray-900 text-right">
                                {{ $draftBooking->driver ? $draftBooking->driver->name : 'Lepas Kunci (Tanpa Driver)' }}
                            </span>
                        </div>

                        <div class="flex justify-between border-b border-gray-200 pb-1.5">
                            <span class="text-gray-500 text-xs font-semibold">Waktu Mulai:</span>
                            <span class="font-bold text-gray-900 text-xs" x-text="formatHumanDate(finalStartDate)"></span>
                        </div>

                        <div class="flex justify-between border-b border-gray-200 pb-1.5">
                            <span class="text-gray-500 text-xs font-semibold">Waktu Selesai:</span>
                            <span class="font-bold text-gray-900 text-xs" x-text="formatHumanDate(finalEndDate)"></span>
                        </div>

                        <div class="flex justify-between border-b border-gray-200/60 pb-2">
                            <span class="text-gray-500 font-medium">Tujuan Perjalanan:</span>
                            <span class="font-bold text-gray-900" x-text="destination || '-'"></span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-500 font-medium">Jumlah Anggota Tim:</span>
                            <span class="font-bold text-indigo-700" x-text="selectedUserIds.filter(id => id !== '').length + ' Orang'"></span>
                        </div>
                    </div>

                    {{-- ARENA TANDA TANGAN (SIGNATURE PAD) --}}
                    <div class="space-y-1.5 pt-1">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <label class="text-xs font-bold text-gray-700 flex items-center gap-1 leading-snug">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-indigo-600 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 20h9"/>
                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                                </svg>
                                <span>Dengan ini Anda menandatangani Formulir Peminjaman</span> 
                                <span class="text-rose-500">*</span>
                            </label>

                            {{-- TOMBOL BERSIHKAN CANVAS --}}
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

                        <div class="border-2 border-dashed border-gray-300 rounded-xl bg-white p-1 relative">
                            <canvas x-ref="signatureCanvas" 
                                    style="mix-blend-mode: normal;"
                                    class="w-full h-40 bg-white rounded-lg cursor-pointer touch-none block"></canvas>
                        </div>

                        {{-- Pesan Error Jika Belum TTD --}}
                        <template x-if="signatureError">
                            <p class="text-[11px] font-bold text-rose-600 mt-1" x-text="signatureError"></p>
                        </template>
                    </div>

                    {{-- Pesan Peringatan --}}
                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-[11px] text-amber-900 font-medium flex items-start gap-2">
                        <svg class="w-4 h-4 text-amber-600 shrink-0 stroke-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span>Dengan melanjutkan, status draft permohonan akan secara resmi dikonversi menjadi pengajuan peminjaman aktif dan diteruskan ke tahap persetujuan Pejabat.</span>
                    </div>

                    {{-- Tombol Aksi --}}
                    <div class="flex items-center gap-3 pt-2">
                        <button type="button" @click="showConfirmModal = false" class="w-1/2 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition">
                            Periksa Kembali
                        </button>
                        <button type="button" @click="submitForm()" class="w-1/2 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md transition flex items-center justify-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2" viewBox="0 0 24 24">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            <span>Ya, Kirim Peminjaman</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>