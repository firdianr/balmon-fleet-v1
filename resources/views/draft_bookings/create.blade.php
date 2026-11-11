<x-app-layout>
    <x-slot name="title">Pengajuan Draft Permohonan Armada</x-slot>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    {{ __('Pengajuan Draft Permohonan Armada') }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Lengkapi jadwal, penggerak roda, driver, dan unit armada (khusus 4WD) sebelum dikonversi ke peminjaman resmi.
                </p>
            </div>
            <a href="{{ route('draft-bookings.index') }}" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold rounded-lg transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-gray-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"/>
                    <polyline points="12 19 5 12 12 5"/>
                </svg>
                <span>Kembali ke Riwayat Draft</span>
            </a>
        </div>
    </x-slot>

    @php
        $oldStart = old('start_date');
        $oldEnd   = old('end_date');
        $oldType  = old('booking_type', 'single_day');

        $oldSingleDate = '';
        $oldStartTime  = '08:00';
        $oldEndTime    = '17:00';

        $oldStartMultiDate = '';
        $oldStartMultiTime = '08:00';
        $oldEndMultiDate   = '';
        $oldEndMultiTime   = '17:00';

        if ($oldStart && $oldEnd) {
            $sParts = explode(' ', str_replace('T', ' ', $oldStart));
            $eParts = explode(' ', str_replace('T', ' ', $oldEnd));

            if ($oldType === 'single_day') {
                $oldSingleDate = $sParts[0] ?? '';
                $oldStartTime  = isset($sParts[1]) ? substr($sParts[1], 0, 5) : '08:00';
                $oldEndTime    = isset($eParts[1]) ? substr($eParts[1], 0, 5) : '17:00';
            } else {
                $oldStartMultiDate = $sParts[0] ?? '';
                $oldStartMultiTime = isset($sParts[1]) ? substr($sParts[1], 0, 5) : '08:00';
                $oldEndMultiDate   = $eParts[0] ?? '';
                $oldEndMultiTime   = isset($eParts[1]) ? substr($eParts[1], 0, 5) : '17:00';
            }
        }
    @endphp

    <div class="pt-2 pb-6" x-data="{
        bookingType: '{{ $oldType }}',
        singleDate: '{{ $oldSingleDate }}',
        startTime: '{{ $oldStartTime }}',
        endTime: '{{ $oldEndTime }}',

        startDateOnlyMulti: '{{ $oldStartMultiDate }}',
        startTimeMulti: '{{ $oldStartMultiTime }}',
        endDateOnlyMulti: '{{ $oldEndMultiDate }}',
        endTimeMulti: '{{ $oldEndMultiTime }}',

        drivetrain: '{{ old('drivetrain', '2wd') }}',
        driverId: '{{ old('driver_id', '') }}',
        vehicleId: '{{ old('vehicle_id', '') }}',
        
        showConfirmModal: false,

        // Master Data dari Controller
        vehicles: {{ Illuminate\Support\Js::from($vehicles) }},
        drivers: {{ Illuminate\Support\Js::from($drivers) }},

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

        onDrivetrainChange() {
            // Reset kendaraan jika berganti ke 2WD
            this.vehicleId = '';
        },

        getFilteredVehicles() {
            return this.vehicles.filter(v => (v.drivetrain || '2wd') === '4wd');
        },

        getSelectedVehicleDetail() {
            if (this.vehicleId) {
                return this.vehicles.find(v => v.id == this.vehicleId);
            }
            return null;
        },

        getSelectedDriverDetail() {
            if (this.driverId) {
                return this.drivers.find(d => d.id == this.driverId);
            }
            return null;
        },

        submitForm() {
            this.$refs.draftForm.submit();
        }
    }">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- BANNER INFORMASI --}}
            <div class="bg-indigo-50/80 p-5 rounded-xl border border-indigo-200 shadow-2xs flex items-start gap-3">
                <div class="p-2.5 bg-indigo-600 text-white rounded-lg shadow-2xs shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="16" x2="12" y2="12"/>
                        <line x1="12" y1="8" x2="12.01" y2="8"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-bold text-indigo-950 text-sm">Pengajuan Ketersediaan Cepat (Pre-Booking)</h4>
                    <p class="text-xs text-indigo-700 mt-0.5 leading-relaxed">
                        Silakan pilih jadwal, sistem penggerak roda (2WD / 4WD), dan driver. Untuk kebutuhan 4WD, Anda dapat memilih unit spesifik. Setelah disetujui Admin Lapangan, Anda dapat mengunggah Surat Tugas.
                    </p>
                </div>
            </div>

            {{-- FORM CONTAINER --}}
            <div class="bg-white p-6 sm:p-8 rounded-xl border border-gray-200 shadow-2xs">
                <form x-ref="draftForm" 
                      action="{{ route('draft-bookings.store') }}" 
                      method="POST" 
                      @submit.prevent="showConfirmModal = true" 
                      class="space-y-6">
                    @csrf

                    <input type="hidden" name="booking_type" x-model="bookingType">
                    <input type="hidden" name="start_date" :value="finalStartDate">
                    <input type="hidden" name="end_date" :value="finalEndDate">

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

                    {{-- 1. WAKTU & JADWAL PERJALANAN --}}
                    <div>
                        <h3 class="text-base font-bold text-gray-900 border-b pb-2 mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            <span>Waktu & Durasi Perjalanan</span>
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
                                <input type="date" x-model="singleDate" :required="bookingType === 'single_day'" class="w-full rounded-lg border-gray-300 text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Jam Berangkat <span class="text-rose-500">*</span></label>
                                <select x-model="startTime" :required="bookingType === 'single_day'" class="w-full rounded-lg border-gray-300 text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer">
                                    @for ($h = 0; $h < 24; $h++)
                                        @for ($m = 0; $m < 60; $m += 30)
                                            @php $timeVal = sprintf('%02d:%02d', $h, $m); @endphp
                                            <option value="{{ $timeVal }}">{{ $timeVal }} WIB</option>
                                        @endfor
                                    @endfor
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Jam Est. Kembali <span class="text-rose-500">*</span></label>
                                <select x-model="endTime" :required="bookingType === 'single_day'" class="w-full rounded-lg border-gray-300 text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer">
                                    @for ($h = 0; $h < 24; $h++)
                                        @for ($m = 0; $m < 60; $m += 30)
                                            @php $timeVal = sprintf('%02d:%02d', $h, $m); @endphp
                                            <option value="{{ $timeVal }}">{{ $timeVal }} WIB</option>
                                        @endfor
                                    @endfor
                                </select>
                            </div>
                        </div>

                        {{-- INPUT MULTI HARI --}}
                        <div x-show="bookingType === 'multi_day'" x-transition class="space-y-4 mb-4" x-cloak>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-3 bg-gray-50 rounded-xl border border-gray-200/80">
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Tanggal Mulai <span class="text-rose-500">*</span></label>
                                    <input type="date" x-model="startDateOnlyMulti" :required="bookingType === 'multi_day'" class="w-full rounded-lg border-gray-300 text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Jam Mulai <span class="text-rose-500">*</span></label>
                                    <select x-model="startTimeMulti" :required="bookingType === 'multi_day'" class="w-full rounded-lg border-gray-300 text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer">
                                        @for ($h = 0; $h < 24; $h++)
                                            @for ($m = 0; $m < 60; $m += 30)
                                                @php $timeVal = sprintf('%02d:%02d', $h, $m); @endphp
                                                <option value="{{ $timeVal }}">{{ $timeVal }} WIB</option>
                                            @endfor
                                        @endfor
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-3 bg-gray-50 rounded-xl border border-gray-200/80">
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Tanggal Selesai <span class="text-rose-500">*</span></label>
                                    <input type="date" x-model="endDateOnlyMulti" :required="bookingType === 'multi_day'" class="w-full rounded-lg border-gray-300 text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Jam Selesai <span class="text-rose-500">*</span></label>
                                    <select x-model="endTimeMulti" :required="bookingType === 'multi_day'" class="w-full rounded-lg border-gray-300 text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer">
                                        @for ($h = 0; $h < 24; $h++)
                                            @for ($m = 0; $m < 60; $m += 30)
                                                @php $timeVal = sprintf('%02d:%02d', $h, $m); @endphp
                                                <option value="{{ $timeVal }}">{{ $timeVal }} WIB</option>
                                            @endfor
                                        @endfor
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 2. KEBUTUHAN PENGGERAK RODA (DRIVETRAIN) --}}
                    <div>
                        <h3 class="text-base font-bold text-gray-900 border-b pb-2 mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="3"/>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                            </svg>
                            <span>Sistem Penggerak Roda (Drivetrain)</span>
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <label class="relative flex flex-col p-4 bg-white border-2 rounded-xl cursor-pointer transition hover:border-indigo-300"
                                   :class="drivetrain === '2wd' ? 'border-indigo-600 bg-indigo-50/40 ring-2 ring-indigo-500/20' : 'border-gray-200'">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2 font-bold text-sm text-gray-900">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A2 2 0 0 0 2 12v4c0 .6.4 1 1 1h2"/>
                                            <circle cx="7" cy="17" r="2"/>
                                            <path d="M9 17h6"/>
                                            <circle cx="17" cy="17" r="2"/>
                                        </svg>
                                        <span>2WD (Two-Wheel Drive)</span>
                                    </div>
                                    <input type="radio" name="drivetrain" value="2wd" x-model="drivetrain" @change="onDrivetrainChange()" class="text-indigo-600 focus:ring-indigo-500">
                                </div>
                                <p class="text-xs text-gray-500 leading-relaxed">
                                    Untuk perjalanan dinas perkotaan/operasional. Unit armada akan dialokasikan oleh Admin Lapangan.
                                </p>
                            </label>

                            <label class="relative flex flex-col p-4 bg-white border-2 rounded-xl cursor-pointer transition hover:border-indigo-300"
                                   :class="drivetrain === '4wd' ? 'border-indigo-600 bg-indigo-50/40 ring-2 ring-indigo-500/20' : 'border-gray-200'">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2 font-bold text-sm text-gray-900">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="1" y="3" width="15" height="13" rx="2"/>
                                            <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                                            <circle cx="5.5" cy="18.5" r="2.5"/>
                                            <circle cx="18.5" cy="18.5" r="2.5"/>
                                        </svg>
                                        <span>4WD / 4x4 (Four-Wheel Drive)</span>
                                    </div>
                                    <input type="radio" name="drivetrain" value="4wd" x-model="drivetrain" @change="onDrivetrainChange()" class="text-indigo-600 focus:ring-indigo-500">
                                </div>
                                <p class="text-xs text-gray-500 leading-relaxed">
                                    Untuk kegiatan monitoring frekuensi / medan berat. Anda dapat memilih unit armada secara langsung.
                                </p>
                            </label>
                        </div>
                    </div>

                    {{-- 3. PILIHAN ARMADA KENDARAAN (HANYA MUNCUL JIKA DRIVETRAIN === '4wd') --}}
                    <div x-show="drivetrain === '4wd'" x-transition class="bg-indigo-50/60 p-4 sm:p-5 rounded-2xl border border-indigo-200/80 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-bold text-indigo-950 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A2 2 0 0 0 2 12v4c0 .6.4 1 1 1h2"/>
                                    <circle cx="7" cy="17" r="2"/>
                                    <path d="M9 17h6"/>
                                    <circle cx="17" cy="17" r="2"/>
                                </svg>
                                <span>Pilih Unit Kendaraan 4WD</span> <span class="text-rose-500">*</span>
                            </h3>
                        </div>

                        {{-- INPUT HIDDEN UNTUK VALUE FORM & VALIDASI REQUIRED --}}
                        <input type="hidden" 
                            name="vehicle_id" 
                            x-model="vehicleId" 
                            :required="drivetrain === '4wd'">

                        {{-- GRID CARD KENDARAAN 4WD --}}
                        <div class="space-y-2">
                            <label class="sr-only">Pilih Unit Kendaraan 4WD</label>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <template x-for="v in getFilteredVehicles()" :key="v.id">
                                    <div @click="vehicleId = v.id"
                                        :class="{
                                            'border-indigo-600 bg-white ring-2 ring-indigo-500/30 shadow-xs': vehicleId == v.id,
                                            'border-gray-200 bg-white/80 hover:border-indigo-300 hover:bg-white': vehicleId != v.id
                                        }"
                                        class="relative p-3.5 rounded-xl border cursor-pointer transition flex items-start gap-3 group">
                                        
                                        {{-- BADGE CHECKMARK JIKA TERPILIH --}}
                                        <div class="absolute top-3 right-3">
                                            <div :class="vehicleId == v.id ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-gray-300 bg-white'"
                                                class="w-5 h-5 rounded-full border flex items-center justify-center transition">
                                                <svg x-show="vehicleId == v.id" class="w-3 h-3 stroke-current stroke-3 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="20 6 9 17 4 12"/>
                                                </svg>
                                            </div>
                                        </div>

                                        {{-- THUMBNAIL FOTO ARMADA --}}
                                        <div class="w-16 h-16 rounded-lg bg-gray-100 border border-gray-200 overflow-hidden shrink-0 relative">
                                            <template x-if="v.photo">
                                                <img :src="'/storage/' + v.photo" :alt="v.brand" class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="!v.photo">
                                                <div class="w-full h-full flex flex-col items-center justify-center text-gray-400 bg-gray-50">
                                                    <svg class="w-6 h-6 stroke-1.5 fill-none stroke-current" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                                        <circle cx="8.5" cy="8.5" r="1.5"/>
                                                        <polyline points="21 15 16 10 5 21"/>
                                                    </svg>
                                                </div>
                                            </template>
                                            <span class="absolute top-0 left-0 bg-gray-900/80 backdrop-blur-xs text-white text-[9px] font-mono font-black px-1.5 py-0.5 rounded-br-md" x-text="v.code || 'N/A'"></span>
                                        </div>

                                        {{-- RINCIAN INFORMASI ARMADA --}}
                                        <div class="space-y-1 pr-6 min-w-0">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                {{-- BADGE TIPE KENDARAAN (OPERATIONAL / MONITORING) --}}
                                                <span class="text-[9px] font-extrabold uppercase px-1.5 py-0.5 rounded border"
                                                    :class="v.type === 'monitoring' 
                                                        ? 'bg-amber-50 text-amber-800 border-amber-200' 
                                                        : 'bg-indigo-50 text-indigo-800 border-indigo-200'"
                                                    x-text="v.type === 'monitoring' ? 'Monitoring' : 'Operational'">
                                                </span>

                                                <h4 class="font-bold text-sm text-gray-900 truncate" x-text="v.brand + ' ' + v.model"></h4>
                                            </div>

                                            <p class="font-mono text-xs font-bold text-indigo-600 uppercase" x-text="v.plate_number"></p>

                                            <div class="flex items-center gap-2 text-[11px] text-gray-500 pt-0.5 flex-wrap">
                                                {{-- KAPASITAS PENUMPANG --}}
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-3.5 h-3.5 stroke-gray-400 fill-none stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                                        <circle cx="9" cy="7" r="4"/>
                                                    </svg>
                                                    <span x-text="v.capacity + ' Kursi'"></span>
                                                </span>

                                                <span>•</span>

                                                {{-- BADGE DRIVETRAIN --}}
                                                <span class="uppercase font-extrabold text-indigo-700 bg-indigo-100 px-1.5 py-0.2 rounded text-[10px] border border-indigo-200" x-text="v.drivetrain ? v.drivetrain.toUpperCase() : '4WD'"></span>

                                                <span>•</span>

                                                {{-- BAHAN BAKAR --}}
                                                <span class="capitalize" x-text="v.fuel_type"></span>
                                            </div>
                                        </div>

                                    </div>
                                </template>
                            </div>

                            {{-- PESAN JIKA TIDAK ADA UNIT 4WD TERSEDIA --}}
                            <template x-if="getFilteredVehicles().length === 0">
                                <div class="p-4 bg-white/80 border border-indigo-200 rounded-xl text-center text-xs text-gray-500 italic">
                                    Tidak ada unit armada 4WD yang tersedia untuk dipilih pada jadwal ini.
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- 4. PILIHAN DRIVER / PENGEMUDI --}}
                    <div class="space-y-4">
                        <div class="flex items-center justify-between border-b pb-2">
                            <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                </svg>
                                <span>Driver / Pengemudi</span> <span class="text-gray-400 text-xs font-normal">(Opsional)</span>
                            </h3>
                        </div>

                        {{-- INPUT HIDDEN UNTUK FORM --}}
                        <input type="hidden" name="driver_id" x-model="driverId">

                        <div class="space-y-2">
                            <label class="sr-only">Pilih Driver / Pengemudi</label>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                
                                {{-- OPSI 1: TANPA DRIVER / LEPAS KUNCI --}}
                                <div @click="driverId = ''"
                                    :class="{
                                        'border-indigo-600 bg-indigo-50/50 ring-2 ring-indigo-500/20 shadow-2xs': driverId === '' || driverId === null,
                                        'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50/50': driverId !== '' && driverId !== null
                                    }"
                                    class="relative p-3.5 rounded-xl border cursor-pointer transition flex items-center gap-3 group">
                                    
                                    {{-- BADGE CHECKMARK --}}
                                    <div class="absolute top-3.5 right-3">
                                        <div :class="(driverId === '' || driverId === null) ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-gray-300 bg-white'"
                                            class="w-5 h-5 rounded-full border flex items-center justify-center transition">
                                            <svg x-show="driverId === '' || driverId === null" class="w-3 h-3 stroke-current stroke-3 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="20 6 9 17 4 12"/>
                                            </svg>
                                        </div>
                                    </div>

                                    {{-- IKON LEPAS KUNCI --}}
                                    <div class="w-12 h-12 rounded-xl bg-gray-100 border border-gray-200 flex items-center justify-center shrink-0 text-gray-500 group-hover:text-indigo-600 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="7" cy="11" r="3"/>
                                            <path d="M10 11h11"/>
                                            <path d="M18 11v2"/>
                                            <path d="M15 11v2"/>
                                        </svg>
                                    </div>

                                    {{-- TEXT INFO --}}
                                    <div class="pr-6">
                                        <h4 class="font-bold text-sm text-gray-900">Tanpa Driver</h4>
                                        <p class="text-xs text-gray-500 mt-0.5">Lepas kunci / Dikenudikan sendiri oleh peminjam</p>
                                    </div>
                                </div>

                                {{-- OPSI LIST DRIVER YANG TERSEDIA --}}
                                <template x-for="d in drivers" :key="d.id">
                                    <div @click="driverId = d.id"
                                        :class="{
                                            'border-indigo-600 bg-indigo-50/50 ring-2 ring-indigo-500/20 shadow-2xs': driverId == d.id,
                                            'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50/50': driverId != d.id
                                        }"
                                        class="relative p-3.5 rounded-xl border cursor-pointer transition flex items-start gap-3 group">
                                        
                                        {{-- BADGE CHECKMARK --}}
                                        <div class="absolute top-3.5 right-3">
                                            <div :class="driverId == d.id ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-gray-300 bg-white'"
                                                class="w-5 h-5 rounded-full border flex items-center justify-center transition">
                                                <svg x-show="driverId == d.id" class="w-3 h-3 stroke-current stroke-3 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="20 6 9 17 4 12"/>
                                                </svg>
                                            </div>
                                        </div>

                                        {{-- AVATAR INSIAL NAMA DRIVER --}}
                                        <div class="w-12 h-12 rounded-xl bg-indigo-100 border border-indigo-200 text-indigo-700 font-extrabold text-base flex items-center justify-center shrink-0"
                                            x-text="d.name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase()">
                                        </div>

                                        {{-- RINCIAN INFORMASI DRIVER --}}
                                        <div class="space-y-1 pr-6 min-w-0">
                                            <h4 class="font-bold text-sm text-gray-900 truncate" x-text="d.name"></h4>

                                            {{-- NIP (JIKA ADA) --}}
                                            <p class="font-mono text-[11px] text-gray-500 font-medium" x-text="d.nip ? 'NIP: ' + d.nip : 'Pengemudi Resmi'"></p>

                                            {{-- NOMOR WHATSAPP --}}
                                            <div class="flex items-center gap-1.5 text-xs font-semibold text-emerald-600 pt-0.5">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                                </svg>
                                                <span x-text="d.phone || 'Tidak Ada Kontak'"></span>
                                            </div>
                                        </div>

                                    </div>
                                </template>
                            </div>

                            {{-- PESAN JIKA TIDAK ADA DRIVER TERSEDIA --}}
                            <template x-if="drivers.length === 0">
                                <div class="p-4 bg-gray-50 border border-gray-200 rounded-xl text-center text-xs text-gray-500 italic">
                                    Tidak ada data driver/pengemudi yang terdaftar.
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- SUBMIT --}}
                    <div class="flex items-center justify-end gap-3 pt-4 border-t">
                        <a href="{{ route('draft-bookings.index') }}" class="px-5 py-2.5 bg-gray-100 text-gray-700 text-sm font-bold rounded-lg hover:bg-gray-200 transition">
                            Batal
                        </a>
                        <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 shadow-2xs transition flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="22" y1="2" x2="11" y2="13"/>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                            </svg>
                            <span>Kirim Permohonan Draft</span>
                        </button>
                    </div>

                </form>
            </div>
        </div>
        
        {{-- MODAL KONFIRMASI --}}
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
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-indigo-100 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                            <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                            <path d="m9 14 2 2 4-4"/>
                        </svg>
                        <span>Konfirmasi Pengajuan Draft Armada</span>
                    </h3>
                    <button type="button" @click="showConfirmModal = false" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <p class="text-xs text-gray-500">Pastikan rincian draft permohonan armada Anda sudah benar sebelum dikirimkan:</p>

                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 text-sm space-y-2.5">
                        <div class="flex justify-between border-b pb-1.5">
                            <span class="text-gray-500 text-xs font-semibold">Penggerak Roda:</span>
                            <span class="font-bold text-indigo-700 uppercase" x-text="drivetrain"></span>
                        </div>

                        <template x-if="drivetrain === '4wd'">
                            <div class="flex justify-between border-b pb-1.5">
                                <span class="text-gray-500 text-xs font-semibold">Mobil Dipilih:</span>
                                <span class="font-bold text-gray-900" x-text="getSelectedVehicleDetail() ? (getSelectedVehicleDetail().brand + ' ' + getSelectedVehicleDetail().model + ' (' + getSelectedVehicleDetail().plate_number + ')') : 'Belum dipilih'"></span>
                            </div>
                        </template>

                        <template x-if="drivetrain === '2wd'">
                            <div class="flex justify-between border-b pb-1.5">
                                <span class="text-gray-500 text-xs font-semibold">Penentuan Unit:</span>
                                <span class="font-bold text-amber-700 text-xs italic">Akan Ditentukan Admin Lapangan</span>
                            </div>
                        </template>

                        <div class="flex justify-between border-b pb-1.5">
                            <span class="text-gray-500 text-xs font-semibold">Driver:</span>
                            <span class="font-bold text-gray-900" x-text="getSelectedDriverDetail() ? getSelectedDriverDetail().name : 'Lepas Kunci (Tanpa Driver)'"></span>
                        </div>

                        <div class="flex justify-between border-b border-gray-200 pb-1.5">
                            <span class="text-gray-500 text-xs font-semibold">Waktu Mulai:</span>
                            <span class="font-bold text-gray-900 text-xs" x-text="formatHumanDate(finalStartDate)"></span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-500 text-xs font-semibold">Waktu Selesai:</span>
                            <span class="font-bold text-gray-900 text-xs" x-text="formatHumanDate(finalEndDate)"></span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="button" @click="showConfirmModal = false" class="w-1/2 py-2.5 bg-gray-100 text-gray-700 text-sm font-bold rounded-lg hover:bg-gray-200 transition">
                            Periksa Kembali
                        </button>
                        <button type="button" @click="submitForm()" class="w-1/2 py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 shadow-2xs transition flex items-center justify-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            <span>Ya, Kirim Pengajuan</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>