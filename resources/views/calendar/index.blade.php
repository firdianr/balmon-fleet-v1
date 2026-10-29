<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    <span>{{ __('Kalender Jadwal Armada') }}</span>
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Klik pada tanggal manapun di kalender untuk melihat daftar ketersediaan armada dan detail peminjaman.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('drivers.calendar') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-bold transition flex items-center gap-1.5 border border-gray-200">
                    <svg class="w-5 h-5 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-width="2" d="M7 17v1a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1a3 3 0 0 0-3-3h-4a3 3 0 0 0-3 3Zm8-9a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                    </svg>
                    <span>Jadwal Driver</span>
                </a>
            </div>
    </x-slot>

    <div class="pt-2 pb-6" x-data="calendarApp()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- KONTROL KALENDER BULAN/TAHUN --}}
            <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <button type="button" @click="previousMonth()" class="p-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-gray-700 transition" title="Bulan Sebelumnya">
                        <!-- Icon Chevron Left -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 18 9 12 15 6"/>
                        </svg>
                    </button>
                    <h3 class="text-xl font-black text-gray-900 font-mono" x-text="monthNames[currentMonth] + ' ' + currentYear"></h3>
                    <button type="button" @click="nextMonth()" class="p-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-gray-700 transition" title="Bulan Selanjutnya">
                        <!-- Icon Chevron Right -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </button>
                </div>

                <button type="button" @click="goToToday()" class="px-4 py-2 bg-indigo-50 border border-indigo-200 text-indigo-700 rounded-lg text-xs font-bold hover:bg-indigo-100 transition flex items-center gap-1.5">
                    <!-- Icon Calendar Today -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                        <circle cx="12" cy="15" r="1.5" fill="currentColor"/>
                    </svg>
                    <span>Hari Ini</span>
                </button>
            </div>

            {{-- LEGENDA STATUS KALENDER --}}
            <div class="bg-white p-4 sm:px-6 sm:py-4 rounded-2xl border border-gray-200/80 shadow-xs space-y-3">
                <div class="flex items-center gap-2 font-bold text-gray-500 uppercase tracking-wider text-[11px]">
                    <!-- Icon Info Circle -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="16" x2="12" y2="12"/>
                        <line x1="12" y1="8" x2="12.01" y2="8"/>
                    </svg>
                    <span>Legenda Status Perjalanan:</span>
                </div>

                <div class="flex flex-wrap items-center gap-x-4 gap-y-2.5 text-xs font-semibold">
                    {{-- 1. PENDING (MENUNGGU ALOKASI) --}}
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-yellow-500 shadow-2xs"></span>
                        <span class="text-gray-700">Pending</span>
                    </div>

                    {{-- 2. WAITING (MENUNGGU APPROVER) --}}
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shadow-2xs"></span>
                        <span class="text-gray-700">Menunggu Persetujuan</span>
                    </div>

                    {{-- 3. APPROVED (DISETUJUI / SIAP BERANGKAT) --}}
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-600 shadow-2xs"></span>
                        <span class="text-gray-700">Disetujui</span>
                    </div>

                    {{-- 4. ON TRIP (DALAM PERJALANAN) --}}
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-purple-600 shadow-2xs"></span>
                        <span class="text-gray-700">Dalam Perjalanan</span>
                    </div>

                    {{-- 5. RETURNED (SUDAH KEMBALI / MENUNGGU CEK FISIK) --}}
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-indigo-600 shadow-2xs"></span>
                        <span class="text-gray-700">Kembali (Cek Fisik)</span>
                    </div>

                    {{-- 6. CHECKED (CEK FISIK OK / MENUNGGU BAST) --}}
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-cyan-600 shadow-2xs"></span>
                        <span class="text-gray-700">Terverifikasi</span>
                    </div>

                    {{-- 7. COMPLETED (SELESAI OPERASIONAL) --}}
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 shadow-2xs"></span>
                        <span class="text-gray-700">Selesai</span>
                    </div>

                    {{-- 8. REJECTED (DITOLAK) --}}
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-600 shadow-2xs"></span>
                        <span class="text-gray-700">Ditolak</span>
                    </div>

                    {{-- 9. CANCELED (DIBATALKAN) --}}
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-gray-400 shadow-2xs"></span>
                        <span class="text-gray-700">Dibatalkan</span>
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
            class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50" 
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
                        <h3 class="font-bold text-lg flex items-center gap-2">
                            <!-- Icon Car Front White -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-indigo-100 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A2 2 0 0 0 2 12v4c0 .6.4 1 1 1h2"/>
                                <circle cx="7" cy="17" r="2"/>
                                <path d="M9 17h6"/>
                                <circle cx="17" cy="17" r="2"/>
                            </svg>
                            <span>Status Armada Kendaraan</span>
                        </h3>
                        <p class="text-xs text-indigo-100 mt-0.5" x-text="selectedDateFormatted"></p>
                    </div>
                    <button type="button" @click="showDateModal = false" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">
                        <!-- Icon Close X -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>

                {{-- BODY MODAL --}}
                <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
                    
                    {{-- SEKSI MOBIL TERSEDIA --}}
                    <div>
                        <h4 class="text-xs font-black uppercase tracking-wider text-emerald-800 bg-emerald-50 px-3.5 py-2 rounded-lg border border-emerald-200 mb-3 flex items-center gap-2">
                            <!-- Icon Check Circle Emerald -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-emerald-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                            <span>Armada Tersedia (<span x-text="availableVehicles.length"></span>)</span>
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
                                                <!-- Icon Image Off -->
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-gray-300 stroke-1.5 fill-none" viewBox="0 0 24 24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                                    <polyline points="21 15 16 10 5 21"/>
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
                                            <span class="px-2 py-1 text-[10px] font-black uppercase tracking-wider text-white bg-emerald-600 rounded shadow-2xs border border-white/20">
                                                AVAILABLE
                                            </span>
                                        </div>
                                    </div>

                                    {{-- INFORMASI KENDARAAN --}}
                                    <div class="p-3">
                                        <div class="flex items-center justify-between gap-2 mb-1">
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold uppercase text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-200">
                                                <template x-if="vehicle.type === 'monitoring'">
                                                    <!-- Icon Antenna/Signal -->
                                                    <svg class="w-3 h-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                                                    </svg>
                                                </template>
                                                <template x-if="vehicle.type !== 'monitoring'">
                                                    <!-- Icon Van/Truck -->
                                                    <svg class="w-3 h-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                                                    </svg>
                                                </template>
                                                <span x-text="vehicle.type === 'monitoring' ? 'Monitoring' : 'Operasional'"></span>
                                            </span>

                                            <span class="px-2 py-0.5 bg-gray-900 text-white font-mono font-bold text-xs rounded" x-text="vehicle.plate_number"></span>
                                        </div>

                                        <h5 class="font-bold text-sm text-gray-900 truncate" x-text="vehicle.brand + ' ' + vehicle.model"></h5>
                                        <p class="text-[11px] text-gray-500 mt-0.5 flex items-center gap-1">
                                            <!-- Icon Users Gray -->
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-gray-400 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                                <circle cx="9" cy="7" r="4"/>
                                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                            </svg>
                                            <span x-text="'Kapasitas: ' + vehicle.capacity + ' orang'"></span>
                                        </p>
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
                            <!-- Icon Calendar Event Amber -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-amber-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            <span>Agenda Peminjaman Armada (<span x-text="bookedVehicles.length"></span>)</span>
                        </h4>

                        <div class="space-y-3">
                            <template x-for="item in bookedVehicles" :key="item.booking.id">
                                <div class="p-3.5 bg-gray-50 border border-gray-200 rounded-xl flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
                                    
                                    {{-- GAMBAR DENGAN DETAILS ARMADA --}}
                                    <div class="flex items-center gap-3 w-full sm:w-auto">
                                        <div class="relative h-16 w-24 bg-gray-100 rounded-lg overflow-hidden shrink-0 border border-gray-200">
                                            <template x-if="item.vehicle && item.vehicle.photo">
                                                <img :src="'/storage/' + item.vehicle.photo" :alt="item.vehicle.brand" class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="!item.vehicle || !item.vehicle.photo">
                                                <div class="w-full h-full flex items-center justify-center text-gray-400 bg-gray-50 text-[10px]">No Photo</div>
                                            </template>
                                            <div class="absolute top-0 left-0 w-7 h-7 bg-gray-900/70 backdrop-blur-2xs flex items-center justify-center text-white text-xs font-mono font-bold">
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
                                            class="w-full sm:w-auto px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-lg shadow-2xs transition flex items-center justify-center gap-1.5 shrink-0">
                                        <!-- Icon Eye White -->
                                        <svg class="w-3.5 h-3.5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
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
            class="fixed inset-0 bg-black/70 backdrop-blur-xs flex items-center justify-center p-4 z-50" 
            x-cloak>
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden" 
                @click.away.stop="showDetailModal = false">
                
                <div class="bg-gray-900 px-6 py-4 text-white flex justify-between items-center">
                    <h3 class="font-bold text-base flex items-center gap-2">
                        <!-- Icon File Text White -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-gray-300 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                        </svg>
                        <span>Detail Peminjaman Armada</span>
                    </h3>
                    <button type="button" @click.stop="showDetailModal = false" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">
                        <!-- Icon Close X -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
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
                            <button type="button" @click.stop="showDetailModal = false" class="w-full py-2 bg-gray-100 text-gray-700 font-bold text-xs rounded-lg hover:bg-gray-200">
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