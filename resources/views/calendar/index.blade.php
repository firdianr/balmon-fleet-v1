<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 leading-tight">
            {{ __('Kalender Jadwal Armada') }}
        </h2>
        <p class="text-sm text-gray-600 mt-1">
            Klik pada tanggal manapun di kalender untuk melihat daftar ketersediaan armada dan detail peminjaman.
        </p>
    </x-slot>

    <div class="py-8" x-data="calendarApp()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- KONTROL KALENDER BULAN/TAHUN --}}
            <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <button type="button" @click="previousMonth()" class="p-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-gray-700 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <h3 class="text-xl font-black text-gray-900 font-mono" x-text="monthNames[currentMonth] + ' ' + currentYear"></h3>
                    <button type="button" @click="nextMonth()" class="p-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-gray-700 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>

                <button type="button" @click="goToToday()" class="px-4 py-2 bg-indigo-50 border border-indigo-200 text-indigo-700 rounded-lg text-xs font-bold hover:bg-indigo-100 transition">
                    Hari Ini
                </button>
            </div>

            {{-- LEGENDA STATUS KALENDER --}}
            <div class="bg-white px-6 py-3.5 rounded-xl border border-gray-200 shadow-sm flex flex-wrap items-center justify-between gap-4 text-xs">
                <span class="font-bold text-gray-500 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                    <span>💡</span> Legenda Status:
                </span>

                <div class="flex flex-wrap items-center gap-3 sm:gap-5 font-semibold">
                    {{-- 1. PENDING --}}
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-yellow-500 shadow-xs"></span>
                        <span class="text-gray-700">Pending</span>
                    </div>

                    {{-- 2. APPROVED --}}
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-amber-500 shadow-xs"></span>
                        <span class="text-gray-700">Disetujui</span>
                    </div>

                    {{-- 3. ON TRIP --}}
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-purple-600 shadow-xs"></span>
                        <span class="text-gray-700">Perjalanan</span>
                    </div>

                    {{-- 4. COMPLETED --}}
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-emerald-600 shadow-xs"></span>
                        <span class="text-gray-700">Selesai</span>
                    </div>

                    {{-- 5. REJECTED --}}
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-rose-600 shadow-xs"></span>
                        <span class="text-gray-700">Ditolak</span>
                    </div>

                    {{-- 6. CANCELED --}}
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-gray-500 shadow-xs"></span>
                        <span class="text-gray-700">Dibatalkan</span>
                    </div>

                    {{-- 7. HARI INI --}}
                    <div class="flex items-center gap-1.5 border-l border-gray-200 pl-3">
                        <span class="w-3 h-3 rounded-full bg-indigo-600 shadow-xs"></span>
                        <span class="text-gray-700 font-bold">Hari Ini</span>
                    </div>
                </div>
            </div>

            {{-- GRID KALENDER --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                {{-- HEADER HARI --}}
                <div class="grid grid-cols-7 bg-gray-50 border-b text-center text-xs font-bold text-gray-600 py-3 uppercase tracking-wider">
                    <div>Minggu</div>
                    <div>Senin</div>
                    <div>Selasa</div>
                    <div>Rabu</div>
                    <div>Kamis</div>
                    <div>Jumat</div>
                    <div>Sabtu</div>
                </div>

                {{-- TANGGAL KALENDER --}}
                <div class="grid grid-cols-7 divide-x divide-y divide-gray-200">
                    <template x-for="day in calendarDays" :key="day.dateString">
                        <div @click="selectDate(day)" 
                             :class="{
                                 'bg-gray-50/50 text-gray-400': !day.isCurrentMonth,
                                 'bg-indigo-50/30': day.isToday,
                                 'hover:bg-indigo-50/60 cursor-pointer': day.isCurrentMonth
                             }"
                             class="min-h-[110px] p-2 transition flex flex-col justify-between group">
                            
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-bold p-1 rounded-full w-7 h-7 flex items-center justify-center"
                                      :class="day.isToday ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-800'"
                                      x-text="day.dayNumber"></span>

                                {{-- INDIKATOR JUMLAH PEMINJAMAN --}}
                                <template x-if="day.bookedVehiclesCount > 0">
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 bg-indigo-100 text-indigo-800 rounded border border-indigo-200">
                                        <span x-text="day.bookedVehiclesCount"></span> Agd
                                    </span>
                                </template>
                            </div>

                            {{-- BADGE BOOKING DENGAN WARNA SESUAI STATUS --}}
                            <div class="mt-2 space-y-1">
                                <template x-for="booking in day.bookings.slice(0, 2)" :key="booking.id">
                                    <div class="text-[10px] font-bold px-1.5 py-0.5 text-white rounded truncate" 
                                         :class="getStatusBgClass(booking.status)"
                                         x-text="(booking.vehicle_code ? booking.vehicle_code + ' - ' : '') + (booking.vehicle_name || 'Armada')"></div>
                                </template>
                                <template x-if="day.bookings.length > 2">
                                    <div class="text-[10px] font-bold text-indigo-600 pl-1" x-text="'+' + (day.bookings.length - 2) + ' peminjaman'"></div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

        </div>

        {{-- MODAL DAFTAR KETERSEDIAAN MOBIL PADA TANGGAL TERPILIH --}}
        <div x-show="showDateModal" 
            class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50" 
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            x-cloak>
            
            <div class="bg-white rounded-xl shadow-xl max-w-3xl w-full overflow-hidden" @click.away="showDateModal = false">
                
                {{-- HEADER MODAL --}}
                <div class="bg-indigo-600 px-6 py-4 text-white flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-lg">Status Armada Kendaraan</h3>
                        <p class="text-xs text-indigo-100" x-text="selectedDateFormatted"></p>
                    </div>
                    <button type="button" @click="showDateModal = false" class="text-white/80 hover:text-white text-2xl font-bold">&times;</button>
                </div>

                {{-- BODY MODAL --}}
                <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
                    
                    {{-- SEKSI MOBIL TERSEDIA --}}
                    <div>
                        <h4 class="text-xs font-black uppercase tracking-wider text-emerald-800 bg-emerald-50 px-3.5 py-2 rounded-lg border border-emerald-200 mb-3 flex items-center gap-2">
                            <span>🚗</span> Armada Tersedia (<span x-text="availableVehicles.length"></span>)
                        </h4>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <template x-for="vehicle in availableVehicles" :key="vehicle.id">
                                <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-xs hover:shadow-sm transition flex flex-col justify-between">
                                    
                                    {{-- GAMBAR KENDARAAN TERSEDIA --}}
                                    <div class="relative h-32 w-full bg-gray-100">
                                        <template x-if="vehicle.photo">
                                            <img :src="'/storage/' + vehicle.photo" :alt="vehicle.brand + ' ' + vehicle.model" class="w-full h-full object-cover">
                                        </template>
                                        <template x-if="!vehicle.photo">
                                            <div class="w-full h-full flex flex-col items-center justify-center text-gray-400 bg-gray-50">
                                                <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                                </svg>
                                                <span class="text-[10px] font-semibold mt-1">Tidak Ada Foto</span>
                                            </div>
                                        </template>

                                        {{-- KODE ARMADA PERSEGI TRANSPARAN --}}
                                        <div class="absolute top-0 left-0 w-12 h-12 bg-gray-900/60 backdrop-blur-md border-r border-b border-white/20 flex items-center justify-center text-white z-10 shadow-md rounded-tl-xl">
                                            <span class="text-xl font-black font-mono text-white tracking-widest" x-text="vehicle.code"></span>
                                        </div>

                                        {{-- BADGE STATUS TERSEDIA --}}
                                        <div class="absolute top-2 right-2">
                                            <span class="px-2 py-1 text-[10px] font-black uppercase tracking-wider text-white bg-emerald-600 rounded shadow border border-white/20">
                                                AVAILABLE
                                            </span>
                                        </div>
                                    </div>

                                    {{-- INFORMASI KENDARAAN --}}
                                    <div class="p-3">
                                        <div class="flex items-center justify-between gap-2 mb-1">
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold uppercase text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-200">
                                                <template x-if="vehicle.type === 'monitoring'">
                                                    <svg class="w-3 h-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                                                    </svg>
                                                </template>
                                                <template x-if="vehicle.type !== 'monitoring'">
                                                    <svg class="w-3 h-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                                                    </svg>
                                                </template>
                                                <span x-text="vehicle.type === 'monitoring' ? 'Monitoring' : 'Operasional'"></span>
                                            </span>

                                            <span class="px-2 py-0.5 bg-gray-900 text-white font-mono font-bold text-xs rounded" x-text="vehicle.plate_number"></span>
                                        </div>

                                        <h5 class="font-bold text-sm text-gray-900 truncate" x-text="vehicle.brand + ' ' + vehicle.model"></h5>
                                        <p class="text-[11px] text-gray-500 mt-0.5" x-text="'👥 Kapasitas: ' + vehicle.capacity + ' orang'"></p>
                                    </div>

                                </div>
                            </template>
                            <template x-if="availableVehicles.length === 0">
                                <p class="col-span-full text-xs text-gray-500 italic p-3 bg-gray-50 rounded-lg border text-center">Tidak ada armada yang tersedia pada tanggal ini.</p>
                            </template>
                        </div>
                    </div>

                    {{-- SEKSI MOBIL DIPINJAM / TERIKAT AGENDA --}}
                    <div>
                        <h4 class="text-xs font-black uppercase tracking-wider text-amber-800 bg-amber-50 px-3.5 py-2 rounded-lg border border-amber-200 mb-3 flex items-center gap-2">
                            <span>🚘</span> Agenda Peminjaman Armada (<span x-text="bookedVehicles.length"></span>)
                        </h4>

                        <div class="space-y-3">
                            <template x-for="item in bookedVehicles" :key="item.booking.id">
                                <div class="p-3.5 bg-gray-50 border border-gray-200 rounded-xl flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
                                    
                                    {{-- GAMBAR DENGAN DETAILS ARMADA --}}
                                    <div class="flex items-center gap-3 w-full sm:w-auto">
                                        <div class="relative h-16 w-24 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0 border border-gray-200">
                                            <template x-if="item.vehicle && item.vehicle.photo">
                                                <img :src="'/storage/' + item.vehicle.photo" :alt="item.vehicle.brand" class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="!item.vehicle || !item.vehicle.photo">
                                                <div class="w-full h-full flex items-center justify-center text-gray-400 bg-gray-50 text-[10px]">No Photo</div>
                                            </template>
                                            <div class="absolute top-0 left-0 w-7 h-7 bg-gray-900/70 backdrop-blur-xs flex items-center justify-center text-white text-xs font-mono font-bold">
                                                <span x-text="item.vehicle ? item.vehicle.code : '?'"></span>
                                            </div>
                                        </div>

                                        <div class="space-y-0.5">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <h5 class="font-bold text-sm text-gray-900" x-text="item.vehicle ? (item.vehicle.brand + ' ' + item.vehicle.model) : (item.booking.vehicle_name || 'Armada')"></h5>
                                                <span class="px-1.5 py-0.5 bg-gray-900 text-white font-mono font-bold text-[10px] rounded" x-text="item.vehicle ? item.vehicle.plate_number : (item.booking.plate_number || '-')"></span>
                                                <span class="px-1.5 py-0.5 text-[9px] font-bold uppercase rounded text-white" 
                                                      :class="getStatusBgClass(item.booking.status)" 
                                                      x-text="item.booking.status_label || item.booking.status"></span>
                                            </div>
                                            <p class="text-xs text-gray-600">
                                                Pemohon: <strong class="text-gray-900" x-text="item.booking.user_name || '-'"></strong>
                                            </p>
                                            <p class="text-xs text-gray-500 truncate max-w-xs">
                                                Tujuan: <span x-text="item.booking.destination || '-'"></span>
                                            </p>
                                        </div>
                                    </div>

                                    {{-- TOMBOL LIHAT DETAIL BOOKING --}}
                                    <button type="button" 
                                            @click="selectedBookingDetail = item.booking; showDetailModal = true" 
                                            class="w-full sm:w-auto px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 flex-shrink-0">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <span>Detail Peminjaman</span>
                                    </button>

                                </div>
                            </template>
                            <template x-if="bookedVehicles.length === 0">
                                <p class="text-xs text-gray-500 italic p-3 bg-gray-50 rounded-lg border text-center">Seluruh armada tersedia pada tanggal ini.</p>
                            </template>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- MODAL DETAIL PEMINJAMAN --}}
        <div x-show="showDetailModal" 
             class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-50" 
             x-cloak>
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden" @click.away="showDetailModal = false">
                <div class="bg-gray-900 px-6 py-4 text-white flex justify-between items-center">
                    <h3 class="font-bold text-base flex items-center gap-2">
                        <span>📋</span> Detail Peminjaman Armada
                    </h3>
                    <button type="button" @click="showDetailModal = false" class="text-white/80 hover:text-white text-2xl font-bold">&times;</button>
                </div>

                <template x-if="selectedBookingDetail">
                    <div class="p-6 space-y-3 text-sm">
                        <div class="p-3 rounded-lg border flex justify-between items-center"
                             :class="{
                                 'bg-yellow-50 border-yellow-200 text-yellow-900': selectedBookingDetail.status === 'pending',
                                 'bg-amber-50 border-amber-200 text-amber-900': selectedBookingDetail.status === 'approved',
                                 'bg-purple-50 border-purple-200 text-purple-900': selectedBookingDetail.status === 'on_trip',
                                 'bg-emerald-50 border-emerald-200 text-emerald-900': selectedBookingDetail.status === 'completed',
                                 'bg-rose-50 border-rose-200 text-rose-900': selectedBookingDetail.status === 'rejected',
                                 'bg-gray-50 border-gray-200 text-gray-900': selectedBookingDetail.status === 'canceled'
                             }">
                            <div>
                                <span class="text-[10px] font-bold uppercase block opacity-75">Armada</span>
                                <p class="font-bold text-base" x-text="(selectedBookingDetail.vehicle_name || 'Armada') + ' (' + (selectedBookingDetail.plate_number || '-') + ')'"></p>
                            </div>
                            <span class="px-2 py-1 text-[10px] font-bold uppercase text-white rounded" 
                                  :class="getStatusBgClass(selectedBookingDetail.status)" 
                                  x-text="selectedBookingDetail.status"></span>
                        </div>

                        <div class="grid grid-cols-2 gap-2 border-b pb-2">
                            <div>
                                <span class="text-xs text-gray-400 block">Pemohon</span>
                                <span class="font-bold text-gray-800" x-text="selectedBookingDetail.user_name || '-'"></span>
                            </div>
                            <div>
                                <span class="text-xs text-gray-400 block">Seksi / Subbagian</span>
                                <span class="font-bold text-gray-800" x-text="selectedBookingDetail.department || '-'"></span>
                            </div>
                        </div>

                        <div class="border-b pb-2">
                            <span class="text-xs text-gray-400 block">Tujuan Perjalanan</span>
                            <span class="font-semibold text-gray-900" x-text="selectedBookingDetail.destination || '-'"></span>
                        </div>

                        <div class="border-b pb-2">
                            <span class="text-xs text-gray-400 block">Maksud / Keperluan</span>
                            <p class="text-xs text-gray-700 mt-0.5" x-text="selectedBookingDetail.purpose || '-'"></p>
                        </div>

                        <div class="grid grid-cols-2 gap-2 pt-1">
                            <div>
                                <span class="text-xs text-gray-400 block">Tanggal Mulai</span>
                                <span class="font-mono text-xs font-bold text-gray-800" x-text="selectedBookingDetail.start_date || '-'"></span>
                            </div>
                            <div>
                                <span class="text-xs text-gray-400 block">Tanggal Selesai</span>
                                <span class="font-mono text-xs font-bold text-gray-800" x-text="selectedBookingDetail.end_date || '-'"></span>
                            </div>
                        </div>

                        <div class="pt-4">
                            <button type="button" @click="showDetailModal = false" class="w-full py-2 bg-gray-100 text-gray-700 font-bold text-xs rounded-lg hover:bg-gray-200">
                                Tutup
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

    </div>

    {{-- SCRIPT ALPINE JS UNTUK DUKUNGAN KALENDER & LOGIKA TANGGAL --}}
    <script>
        function calendarApp() {
            return {
                allEvents: @json($events),
                allVehicles: @json($vehicles),
                currentMonth: new Date().getMonth(),
                currentYear: new Date().getFullYear(),
                monthNames: ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"],
                showDateModal: false,
                showDetailModal: false,
                selectedDateFormatted: '',
                selectedBookingDetail: null,
                availableVehicles: [],
                bookedVehicles: [],

                // HELPER: Kelas Warna Tailwind berdasarkan Status
                getStatusBgClass(status) {
                    switch (status) {
                        case 'pending':   return 'bg-yellow-500';
                        case 'approved':  return 'bg-amber-500';
                        case 'on_trip':   return 'bg-purple-600';
                        case 'completed': return 'bg-emerald-600';
                        case 'rejected':  return 'bg-rose-600';
                        case 'canceled':  return 'bg-gray-500';
                        default:          return 'bg-indigo-600';
                    }
                },

                // HELPER: Normalisasi Tanggal ke YYYY-MM-DD
                cleanDate(dateStr) {
                    if (!dateStr) return '';
                    return String(dateStr).substring(0, 10);
                },

                get calendarDays() {
                    const days = [];
                    const firstDay = new Date(this.currentYear, this.currentMonth, 1).getDay();
                    const totalDays = new Date(this.currentYear, this.currentMonth + 1, 0).getDate();
                    const prevMonthTotalDays = new Date(this.currentYear, this.currentMonth, 0).getDate();
                    const todayStr = new Date().toISOString().split('T')[0];

                    // Hari bulan sebelumnya
                    for (let i = firstDay - 1; i >= 0; i--) {
                        const dayNum = prevMonthTotalDays - i;
                        days.push({ dayNumber: dayNum, isCurrentMonth: false, isToday: false, bookings: [] });
                    }

                    // Hari bulan ini
                    for (let d = 1; d <= totalDays; d++) {
                        const monthStr = String(this.currentMonth + 1).padStart(2, '0');
                        const dayStr = String(d).padStart(2, '0');
                        const dateString = `${this.currentYear}-${monthStr}-${dayStr}`;

                        // Normalisasi start_date dan end_date sebelum membandingkan
                        const bookingsOnDay = this.allEvents.filter(e => {
                            const startDate = this.cleanDate(e.start_date);
                            const endDate = this.cleanDate(e.end_date);
                            return dateString >= startDate && dateString <= endDate;
                        });

                        days.push({
                            dayNumber: d,
                            dateString: dateString,
                            isCurrentMonth: true,
                            isToday: dateString === todayStr,
                            bookedVehiclesCount: bookingsOnDay.length,
                            bookings: bookingsOnDay
                        });
                    }

                    return days;
                },

                previousMonth() {
                    if (this.currentMonth === 0) {
                        this.currentMonth = 11;
                        this.currentYear--;
                    } else {
                        this.currentMonth--;
                    }
                },

                nextMonth() {
                    if (this.currentMonth === 11) {
                        this.currentMonth = 0;
                        this.currentYear++;
                    } else {
                        this.currentMonth++;
                    }
                },

                goToToday() {
                    this.currentMonth = new Date().getMonth();
                    this.currentYear = new Date().getFullYear();
                },

                selectDate(day) {
                    if (!day.isCurrentMonth) return;

                    this.selectedDateFormatted = `${day.dayNumber} ${this.monthNames[this.currentMonth]} ${this.currentYear}`;
                    const targetDate = day.dateString;

                    // Filter booking aktif (approved dan on_trip) untuk menandai mobil yang sedang TIDAK BISA dipinjam
                    const activeBookings = this.allEvents.filter(e => {
                        const startDate = this.cleanDate(e.start_date);
                        const endDate = this.cleanDate(e.end_date);
                        return targetDate >= startDate && targetDate <= endDate;
                    });
                    
                    // Mobil hanya dianggap tidak tersedia jika statusnya approved atau on_trip pada tanggal tersebut
                    const busyVehicleIds = activeBookings
                        .filter(b => ['approved', 'on_trip'].includes(b.status))
                        .map(b => b.vehicle_id)
                        .filter(Boolean);

                    // Pisahkan armada tersedia & dipinjam/terikat agenda
                    this.availableVehicles = this.allVehicles.filter(v => !busyVehicleIds.includes(v.id) && v.status !== 'maintenance');
                    this.bookedVehicles = activeBookings.map(b => {
                        return {
                            booking: b,
                            vehicle: this.allVehicles.find(v => v.id === b.vehicle_id) || null
                        };
                    });

                    this.showDateModal = true;
                }
            }
        }
    </script>
</x-app-layout>