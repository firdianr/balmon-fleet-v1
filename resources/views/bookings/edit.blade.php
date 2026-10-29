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
                <!-- Icon Arrow Left Gray -->
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
    @endphp

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

        // Mode Perjalanan
        bookingType: '{{ old('booking_type', $isSameDay ? 'single_day' : 'multi_day') }}',
        singleDate: '{{ old('single_date', $booking->start_date ? $booking->start_date->format('Y-m-d') : '') }}',
        startTime: '{{ old('start_time', $booking->start_date ? $booking->start_date->format('H:i') : '08:00') }}',
        endTime: '{{ old('end_time', $booking->end_date ? $booking->end_date->format('H:i') : '17:00') }}',

        // State Multi Hari (Tanggal & Jam Terpisah)
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
                return this.monitoringVehicles.find(v => v.id == this.selectedVehicleId);
            }
            return null;
        },

        formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            });
        },

        submitForm() {
            $refs.editBookingForm.submit();
        }
    }">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- ERROR NOTIFICATION --}}
            @if ($errors->any())
                <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 space-y-1 flex items-start gap-2.5">
                    <!-- Icon Alert Triangle Rose -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-rose-600 stroke-2 shrink-0 mt-0.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                    <div>
                        <p class="font-bold text-sm">Terdapat beberapa kesalahan input:</p>
                        <ul class="list-disc list-inside space-y-0.5 mt-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{-- FORM EDIT PERMOHONAN --}}
            <div class="bg-white p-6 sm:p-8 rounded-xl border border-gray-200 shadow-xs">
                <form x-ref="editBookingForm" 
                    action="{{ route('bookings.update', $booking) }}" 
                    method="POST" 
                    enctype="multipart/form-data" 
                    @submit.prevent="showConfirmModal = true" 
                    class="space-y-6">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="booking_type" x-model="bookingType">
                    <input type="hidden" name="start_date" :value="finalStartDate">
                    <input type="hidden" name="end_date" :value="finalEndDate">

                    {{-- INFORMASI BERKAS SURAT TUGAS --}}
                    <div class="space-y-2">
                        <label class="block text-sm font-bold text-gray-700">
                            Berkas Surat Tugas (PDF)
                        </label>

                        @if($booking->letter_file)
                            <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2.5">
                                    <div class="p-2 bg-emerald-600 text-white rounded-lg shadow-xs">
                                        <!-- Icon Check White -->
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20 6 9 17 4 12"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-emerald-900">Berkas Surat Tugas Terpasang</p>
                                        <p class="text-[11px] text-emerald-700 font-mono">{{ $booking->letter_file }}</p>
                                    </div>
                                </div>
                                <a href="{{ asset('storage/' . $booking->letter_file) }}" target="_blank" class="text-xs text-indigo-600 hover:text-indigo-800 font-bold px-2 py-1 rounded hover:bg-indigo-50 transition flex items-center gap-1">
                                    <!-- Icon External Link Indigo -->
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
                            class="block w-full text-xs text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition cursor-pointer">
                        <p class="text-[11px] text-gray-500 mt-1">Kosongkan jika tidak ingin mengganti berkas Surat Tugas yang sudah ada (Format .PDF, Max 5MB).</p>
                    </div>

                    {{-- INFORMASI NOMOR SURAT TUGAS --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700">Nomor Surat Tugas <span class="text-rose-500">*</span></label>
                        <input type="text" name="letter_number" x-model="letterNumber" placeholder="Contoh: xxx/Balmon.xx/KP.xx.xx/xx/20xx" required
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-2xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 text-sm font-mono font-bold">
                    </div>

                    {{-- 1. PEMILIHAN JENIS KENDARAAN DINAS --}}
                    <div>
                        <h3 class="text-base font-bold text-gray-900 border-b pb-2 mb-4 flex items-center gap-2">
                            <!-- Icon Car Indigo -->
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
                                        <!-- Icon Truck/Van Indigo -->
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
                                        <!-- Icon Signal/Antenna Indigo -->
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
                            <!-- Icon Search Indigo -->
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
                                <option :value="v.id" x-text="v.brand + ' ' + v.model + ' (' + v.plate_number + ')'"></option>
                            </template>
                        </select>
                    </div>

                    {{-- CATATAN SPESIFIKASI KENDARAAN --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 flex items-center gap-1.5">
                            <!-- Icon Pencil Indigo -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                            <span>Catatan Kebutuhan Kendaraan</span>
                        </label>
                        <textarea name="vehicle_requirements" rows="2" 
                            placeholder="Contoh: Rute pegunungan menanjak tinggi (butuh 4WD) atau perjalanan dalam kota." 
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-2xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 text-sm font-medium">{{ old('vehicle_requirements', $booking->vehicle_requirements) }}</textarea>
                    </div>

                    {{-- 2. JADWAL & DESTINASI --}}
                    <div>
                        <h3 class="text-base font-bold text-gray-900 border-b pb-2 mb-4 flex items-center gap-2">
                            <!-- Icon Calendar Indigo -->
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
                                <!-- Icon Clock Gray -->
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
                                    <!-- Icon Map Pin Indigo -->
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
                                    <!-- Icon Clipboard/File Indigo -->
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

                    {{-- 3. DROPDOWN ANGGOTA TIM DINAMIS DARI DATABASE --}}
                    <div>
                        <div class="flex items-center justify-between border-b pb-2 mb-4">
                            <div>
                                <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                                    <!-- Icon Users Indigo -->
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
                                <!-- Icon Plus Indigo -->
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
                                                <option :value="u.id" x-text="u.name + (u.nip ? ' (NIP: ' + u.nip + ')' : '') + ' - ' + u.role.toUpperCase()"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <button type="button" @click="removeMember(index)" x-show="selectedUserIds.length > 1" class="text-rose-600 hover:text-rose-800 p-1 font-bold rounded hover:bg-rose-50 transition">
                                        <!-- Icon Trash Rose -->
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
                        <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 shadow-xs transition flex items-center gap-2">
                            <!-- Icon Save/Disk White -->
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

            {{-- MODAL KONFIRMASI PEMARUAN --}}
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
                            <!-- Icon Pencil White -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-indigo-100 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                            <span>Konfirmasi Pembaruan Permohonan</span>
                        </h3>
                        <button type="button" @click="showConfirmModal = false" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"/>
                                <line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <p class="text-xs text-gray-500">Pastikan rincian perubahan permohonan peminjaman armada Anda sudah benar sebelum disimpan:</p>

                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 text-sm space-y-2.5">
                            <div class="flex justify-between border-b pb-1.5">
                                <span class="text-gray-500 text-xs font-semibold">Tipe Armada:</span>
                                <span class="font-bold text-indigo-700 uppercase" x-text="vehicleType === 'monitoring' ? 'Monitoring' : 'Operasional'"></span>
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
                                <span class="text-gray-500 text-xs font-semibold">Waktu Mulai:</span>
                                <span class="font-bold text-gray-900 text-xs" x-text="finalStartDate || '-'"></span>
                            </div>

                            <div class="flex justify-between border-b pb-1.5">
                                <span class="text-gray-500 text-xs font-semibold">Waktu Selesai:</span>
                                <span class="font-bold text-gray-900 text-xs" x-text="finalEndDate || '-'"></span>
                            </div>

                            <div class="flex justify-between border-b pb-1.5">
                                <span class="text-gray-500 text-xs font-semibold">Tujuan:</span>
                                <span class="font-bold text-gray-900" x-text="destination || '-'"></span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 pt-2">
                            <button type="button" @click="showConfirmModal = false" class="w-1/2 py-2.5 bg-gray-100 text-gray-700 text-sm font-bold rounded-lg hover:bg-gray-200 transition">
                                Periksa Kembali
                            </button>
                            <button type="button" @click="submitForm()" class="w-1/2 py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 shadow-xs transition flex items-center justify-center gap-1.5">
                                <!-- Icon Check White -->
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
    </div>
</x-app-layout>