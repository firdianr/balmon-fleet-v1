<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="9"/>
                        <circle cx="12" cy="12" r="2"/>
                        <path d="M12 14v7"/>
                        <path d="M10 12L3.5 9.5"/>
                        <path d="M14 12l6.5-2.5"/>
                    </svg>
                    <span>{{ __('Kalender Jadwal & Ketersediaan Driver') }}</span>
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Klik pada tanggal manapun di kalender untuk melihat status ketersediaan driver dan detail penugasan.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('calendar.index') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-bold transition flex items-center gap-1.5 border border-gray-200">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h6l2 4m-8-4v8m0-8V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v9h2m8 0H9m4 0h2m4 0h2v-4m0 0h-5m3.5 5.5a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0Zm-10 0a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0Z"/>
                    </svg>
                    <span>Kalender Armada</span>
                </a>

                @if(in_array($currentUser->role, ['admin_app', 'admin_lap', 'approver']))
                    <button type="button" 
                            onclick="window.dispatchEvent(new CustomEvent('open-attendance-modal'))" 
                            class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        <span>Catat Cuti / Ketidakhadiran</span>
                    </button>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="pt-2 pb-6" x-data="driverCalendarApp()" @open-attendance-modal.window="openCreateAttendance()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <button type="button" @click="previousMonth()" class="p-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-gray-700 transition" title="Bulan Sebelumnya">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 18 9 12 15 6"/>
                        </svg>
                    </button>
                    <h3 class="text-xl font-black text-gray-900 font-mono" x-text="monthNames[currentMonth] + ' ' + currentYear"></h3>
                    <button type="button" @click="nextMonth()" class="p-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-gray-700 transition" title="Bulan Selanjutnya">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </button>
                </div>

                <div class="flex items-center gap-3 w-full md:w-auto">
                    @if($currentUser->role === 'driver')
                        <span class="px-3 py-2 bg-indigo-50 border border-indigo-200 text-indigo-700 text-xs font-bold rounded-lg flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span>Driver: {{ $currentUser->name }}</span>
                        </span>
                    @else
                        <select x-model="selectedDriverFilter" class="bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500 py-2 px-3 w-full md:w-56">
                            <option value="">-- Seluruh Driver --</option>
                            @foreach($drivers as $drv)
                                <option value="{{ $drv->id }}">{{ $drv->name }}</option>
                            @endforeach
                        </select>
                    @endif

                    <button type="button" @click="goToToday()" class="px-4 py-2 bg-indigo-50 border border-indigo-200 text-indigo-700 rounded-lg text-xs font-bold hover:bg-indigo-100 transition flex items-center gap-1.5 shrink-0">
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
            </div>

            <div class="bg-white p-4 sm:px-6 sm:py-4 rounded-2xl border border-gray-200/80 shadow-xs space-y-3">
                <div class="flex items-center gap-2 font-bold text-gray-500 uppercase tracking-wider text-[11px]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="16" x2="12" y2="12"/>
                        <line x1="12" y1="8" x2="12.01" y2="8"/>
                    </svg>
                    <span>Legenda Status Driver:</span>
                </div>

                <div class="flex flex-wrap items-center gap-x-4 gap-y-2.5 text-xs font-semibold">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-purple-600 shadow-2xs"></span>
                        <span class="text-gray-700">Dalam Perjalanan (On Trip)</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 shadow-2xs"></span>
                        <span class="text-gray-700">Tugas Disetujui</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shadow-2xs"></span>
                        <span class="text-gray-700">Cuti</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-600 shadow-2xs"></span>
                        <span class="text-gray-700">Sakit</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-purple-500 shadow-2xs"></span>
                        <span class="text-gray-700">Izin</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-orange-600 shadow-2xs"></span>
                        <span class="text-gray-700">Service</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="grid grid-cols-7 bg-gray-50 border-b text-center text-xs font-bold text-gray-600 py-3 uppercase tracking-wider">
                    <div>Minggu</div>
                    <div>Senin</div>
                    <div>Selasa</div>
                    <div>Rabu</div>
                    <div>Kamis</div>
                    <div>Jumat</div>
                    <div>Sabtu</div>
                </div>

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

                                <template x-if="day.totalEventsCount > 0">
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 bg-indigo-100 text-indigo-800 rounded border border-indigo-200">
                                        <span x-text="day.totalEventsCount"></span> Agd
                                    </span>
                                </template>
                            </div>

                            <div class="mt-2 space-y-1">
                                <template x-for="item in day.items.slice(0, 2)" :key="item.type + '_' + item.id">
                                    <div class="text-[10px] font-bold px-1.5 py-0.5 text-white rounded truncate" 
                                         :class="getItemBgClass(item)"
                                         x-text="getItemLabel(item)"></div>
                                </template>
                                <template x-if="day.items.length > 2">
                                    <div class="text-[10px] font-bold text-indigo-600 pl-1" x-text="'+' + (day.items.length - 2) + ' agenda'"></div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

        </div>

        {{-- MODAL STATUS KETERSEDIAAN DRIVER (Z-INDEX 40) --}}
        <div x-show="showDateModal" 
            class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-40" 
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            x-cloak>
            
            <div class="bg-white rounded-xl shadow-xl max-w-3xl w-full overflow-hidden" 
                 @click.away="if(!showAttendanceModal && !showDeleteModal && !showDetailModal) showDateModal = false"
                 @click.stop>
                <div class="bg-indigo-600 px-6 py-4 text-white flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-lg flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-indigo-100 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                            <span>Status Ketersediaan Driver</span>
                        </h3>
                        <p class="text-xs text-indigo-100 mt-0.5" x-text="selectedDateFormatted"></p>
                    </div>
                    <button type="button" @click="showDateModal = false" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto text-xs">
                    <div>
                        <h4 class="text-xs font-black uppercase tracking-wider text-emerald-800 bg-emerald-50 px-3.5 py-2 rounded-lg border border-emerald-200 mb-3 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-emerald-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                            <span>Driver Standby / Tersedia (<span x-text="availableDrivers.length"></span>)</span>
                        </h4>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <template x-for="drv in availableDrivers" :key="drv.id">
                                <div class="p-3 bg-white border border-gray-200 rounded-xl flex items-center justify-between shadow-2xs">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center text-xs">
                                            <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-900" x-text="drv.name"></p>
                                            <p class="text-[10px] text-gray-500" x-text="drv.phone || 'No WA (-)'"></p>
                                        </div>
                                    </div>
                                    <span class="px-2 py-0.5 text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded">READY</span>
                                </div>
                            </template>
                            <template x-if="availableDrivers.length === 0">
                                <p class="col-span-full text-xs text-gray-500 italic p-3 bg-gray-50 rounded-lg border text-center">Tidak ada driver yang standby pada tanggal ini.</p>
                            </template>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-xs font-black uppercase tracking-wider text-indigo-800 bg-indigo-50 px-3.5 py-2 rounded-lg border border-indigo-200 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-800" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                            <span>Driver Bertugas Dinas (<span x-text="assignedBookings.length"></span>)</span>
                        </h4>

                        <div class="space-y-2">
                            <template x-for="item in assignedBookings" :key="'bk_' + item.id">
                                <div class="p-3 bg-gray-50 border border-gray-200 rounded-xl flex items-center justify-between gap-3">
                                    <div class="space-y-0.5">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-gray-900" x-text="item.driver_name"></span>
                                            <span class="px-2 py-0.5 text-[9px] font-bold text-white rounded" :class="getBookingStatusBg(item.status)" x-text="item.status.toUpperCase()"></span>
                                        </div>
                                        <p class="text-[11px] text-gray-600">
                                            Pemohon: <strong x-text="item.user_name"></strong> • Armada: <span class="font-mono" x-text="item.vehicle_name + ' (' + item.plate_number + ')'"></span>
                                        </p>
                                        <p class="text-[10px] text-gray-500">Tujuan: <span x-text="item.destination"></span></p>
                                    </div>
                                    <button type="button" @click="selectedDetailItem = item; showDetailModal = true" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-[11px] rounded-lg transition shrink-0">
                                        Detail
                                    </button>
                                </div>
                            </template>
                            <template x-if="assignedBookings.length === 0">
                                <p class="text-xs text-gray-500 italic p-3 bg-gray-50 rounded-lg border text-center">Tidak ada penugasan driver pada tanggal ini.</p>
                            </template>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-xs font-black uppercase tracking-wider text-amber-800 bg-amber-50 px-3.5 py-2 rounded-lg border border-amber-200 mb-3 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-amber-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            <span>Ketidakhadiran Driver / Absen (<span x-text="absentDrivers.length"></span>)</span>
                        </h4>

                        <div class="space-y-2">
                            <template x-for="item in absentDrivers" :key="'att_' + item.id">
                                <div class="p-3 bg-amber-50/60 border border-amber-200 rounded-xl flex items-center justify-between gap-3">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-amber-950" x-text="item.driver_name"></span>
                                            <span class="px-2 py-0.5 text-[9px] font-bold text-white rounded uppercase" :class="getAttendanceStatusBg(item.status)" x-text="item.status"></span>
                                        </div>
                                        <p class="text-[11px] text-amber-900 mt-0.5" x-text="'Catatan: ' + item.notes"></p>
                                    </div>
                                    
                                    @if(in_array($currentUser->role, ['admin_app', 'admin_lap', 'approver']))
                                        <div class="flex items-center gap-1 shrink-0">
                                            <button type="button" @click="editAttendance(item)" class="p-1.5 bg-amber-100 hover:bg-amber-200 text-amber-800 rounded-lg transition" title="Edit Status">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </button>

                                            <button type="button" @click="confirmDeleteAttendance(item.id)" class="p-1.5 bg-rose-100 hover:bg-rose-200 text-rose-800 rounded-lg transition" title="Batalkan Ketidakhadiran">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </template>
                            <template x-if="absentDrivers.length === 0">
                                <p class="text-xs text-gray-500 italic p-3 bg-gray-50 rounded-lg border text-center">Tidak ada catatan ketidakhadiran driver pada tanggal ini.</p>
                            </template>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- MODAL DETAIL PEMINJAMAN (Z-INDEX 50) --}}
        <div x-show="showDetailModal" class="fixed inset-0 bg-black/70 backdrop-blur-xs flex items-center justify-center p-4 z-[50]" x-cloak>
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden" @click.away="showDetailModal = false" @click.stop>
                <div class="bg-gray-900 px-6 py-4 text-white flex justify-between items-center">
                    <h3 class="font-bold text-base flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Detail Penugasan Driver</span>
                    </h3>
                    <button type="button" @click="showDetailModal = false" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">&times;</button>
                </div>

                <template x-if="selectedDetailItem">
                    <div class="p-6 space-y-3 text-sm">
                        <div class="p-3 rounded-lg border bg-gray-50 flex justify-between items-center">
                            <div>
                                <span class="text-[10px] font-bold uppercase block text-gray-400">Driver</span>
                                <p class="font-bold text-base text-gray-900" x-text="selectedDetailItem.driver_name"></p>
                            </div>
                            <span class="px-2 py-1 text-[10px] font-bold uppercase text-white rounded" :class="getBookingStatusBg(selectedDetailItem.status)" x-text="selectedDetailItem.status"></span>
                        </div>

                        <div class="grid grid-cols-2 gap-2 border-b pb-2 text-xs">
                            <div>
                                <span class="text-gray-400 block">Pemohon</span>
                                <span class="font-bold text-gray-800" x-text="selectedDetailItem.user_name"></span>
                            </div>
                            <div>
                                <span class="text-gray-400 block">Seksi / Unit</span>
                                <span class="font-bold text-gray-800" x-text="selectedDetailItem.department"></span>
                            </div>
                        </div>

                        <div class="border-b pb-2 text-xs">
                            <span class="text-gray-400 block">Armada Diberikan</span>
                            <span class="font-bold text-indigo-700" x-text="selectedDetailItem.vehicle_name + ' (' + selectedDetailItem.plate_number + ')'"></span>
                        </div>

                        <div class="border-b pb-2 text-xs">
                            <span class="text-gray-400 block">Tujuan Perjalanan</span>
                            <span class="font-semibold text-gray-900" x-text="selectedDetailItem.destination"></span>
                        </div>

                        <div class="border-b pb-2 text-xs">
                            <span class="text-gray-400 block">Keperluan / Tugas</span>
                            <p class="text-gray-700 italic mt-0.5" x-text="selectedDetailItem.purpose"></p>
                        </div>

                        <div class="pt-2">
                            <button type="button" @click="showDetailModal = false" class="w-full py-2 bg-gray-100 text-gray-700 font-bold text-xs rounded-lg hover:bg-gray-200">
                                Tutup
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- MODAL INPUT / EDIT KETIDAKHADIRAN (Z-INDEX 60) --}}
        <div x-show="showAttendanceModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-[60]" x-cloak>
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full overflow-hidden border border-gray-100" 
                 @click.away="if(!showDeleteModal) showAttendanceModal = false" 
                 @click.stop>
                <div class="bg-amber-600 px-6 py-4 text-white flex justify-between items-center">
                    <h3 class="font-bold text-base flex items-center gap-2">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span x-text="isEditAttendance ? 'Edit Ketidakhadiran Driver' : 'Catat Ketidakhadiran Driver'"></span>
                    </h3>
                    <button type="button" @click="showAttendanceModal = false" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">&times;</button>
                </div>

                <form :action="isEditAttendance ? '/drivers/attendance/' + attendanceForm.id : '{{ route('drivers.attendance.store') }}'" method="POST" class="p-6 space-y-3 text-xs">
                    @csrf
                    <template x-if="isEditAttendance">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Pilih Driver <span class="text-rose-500">*</span></label>
                        <select name="user_id" x-model="attendanceForm.user_id" required class="w-full rounded-xl border-gray-300 text-xs font-medium focus:ring-amber-500 focus:border-amber-500">
                            <option value="">-- Pilih Driver --</option>
                            @foreach($drivers as $drv)
                                <option value="{{ $drv->id }}">{{ $drv->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Status Keterangan <span class="text-rose-500">*</span></label>
                        <select name="status" x-model="attendanceForm.status" required class="w-full rounded-xl border-gray-300 text-xs font-medium focus:ring-amber-500 focus:border-amber-500">
                            <option value="cuti">Cuti Tahunan / Dinas</option>
                            <option value="sakit">Sakit</option>
                            <option value="izin">Izin / Keperluan Pribadi</option>
                            <option value="service">Pendampingan Service Bengkel</option>
                            <option value="non_active">Non-Aktif / Resign</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Tanggal Mulai <span class="text-rose-500">*</span></label>
                            <input type="date" name="start_date" x-model="attendanceForm.start_date" required class="w-full rounded-xl border-gray-300 text-xs font-medium focus:ring-amber-500 focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Tanggal Selesai <span class="text-rose-500">*</span></label>
                            <input type="date" name="end_date" x-model="attendanceForm.end_date" required class="w-full rounded-xl border-gray-300 text-xs font-medium focus:ring-amber-500 focus:border-amber-500">
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Catatan / Keterangan (Opsional)</label>
                        <textarea name="notes" x-model="attendanceForm.notes" rows="2" placeholder="Tuliskan keterangan tambahan jika ada..." class="w-full rounded-xl border-gray-300 text-xs font-medium focus:ring-amber-500 focus:border-amber-500"></textarea>
                    </div>

                    <div class="flex items-center gap-3 pt-3 border-t border-gray-100">
                        <button type="button" @click="showAttendanceModal = false" class="w-1/2 py-2 bg-gray-100 text-gray-700 font-bold rounded-xl hover:bg-gray-200 transition">
                            Batal
                        </button>
                        <button type="submit" class="w-1/2 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl shadow-xs transition flex items-center justify-center gap-1.5">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span x-text="isEditAttendance ? 'Update Status' : 'Simpan Status'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- MODAL KONFIRMASI HAPUS KETIDAKHADIRAN (Z-INDEX 70) --}}
        <div x-show="showDeleteModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-[70]" x-cloak>
            <div class="bg-white rounded-xl shadow-xl max-w-sm w-full overflow-hidden border border-gray-100" 
                 @click.away="showDeleteModal = false" 
                 @click.stop>
                <div class="p-6 text-center space-y-4">
                    <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Batalkan Ketidakhadiran?</h3>
                        <p class="text-xs text-gray-500 mt-1">Data ketidakhadiran driver ini akan dihapus dari sistem dan status driver akan kembali standby.</p>
                    </div>
                    <form :action="'/drivers/attendance/' + deleteAttendanceId" method="POST" class="flex items-center gap-3 pt-2">
                        @csrf
                        @method('DELETE')
                        <button type="button" @click="showDeleteModal = false" class="w-1/2 py-2 bg-gray-100 text-gray-700 font-bold text-xs rounded-xl hover:bg-gray-200 transition">
                            Batal
                        </button>
                        <button type="submit" class="w-1/2 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow-xs transition">
                            Ya, Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <script>
        function driverCalendarApp() {
            return {
                allDrivers: @json($drivers),
                allBookings: @json($events),
                allAttendances: @json($attendances),
                selectedDriverFilter: '',
                currentMonth: new Date().getMonth(),
                currentYear: new Date().getFullYear(),
                monthNames: ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"],
                showDateModal: false,
                showDetailModal: false,
                showAttendanceModal: false,
                showDeleteModal: false,
                deleteAttendanceId: null,
                isEditAttendance: false,
                selectedDateFormatted: '',
                selectedDetailItem: null,
                availableDrivers: [],
                assignedBookings: [],
                absentDrivers: [],
                attendanceForm: {
                    id: null,
                    user_id: '',
                    status: 'cuti',
                    start_date: '',
                    end_date: '',
                    notes: ''
                },

                openCreateAttendance() {
                    this.isEditAttendance = false;
                    this.attendanceForm = {
                        id: null,
                        user_id: '',
                        status: 'cuti',
                        start_date: '',
                        end_date: '',
                        notes: ''
                    };
                    this.showAttendanceModal = true;
                },

                editAttendance(item) {
                    this.isEditAttendance = true;
                    this.attendanceForm = {
                        id: item.id,
                        user_id: item.driver_id,
                        status: item.status,
                        start_date: this.cleanDate(item.start_date),
                        end_date: this.cleanDate(item.end_date),
                        notes: item.notes !== '-' ? item.notes : ''
                    };
                    this.showAttendanceModal = true;
                },

                confirmDeleteAttendance(id) {
                    this.deleteAttendanceId = id;
                    this.showDeleteModal = true;
                },

                cleanDate(dateStr) {
                    if (!dateStr) return '';
                    return String(dateStr).substring(0, 10);
                },

                getBookingStatusBg(status) {
                    switch (status) {
                        case 'on_trip':   return 'bg-purple-600';
                        case 'completed': return 'bg-blue-600';
                        case 'approved':  return 'bg-emerald-600';
                        default:          return 'bg-indigo-600';
                    }
                },

                getAttendanceStatusBg(status) {
                    switch (status) {
                        case 'cuti':    return 'bg-amber-500';
                        case 'sakit':   return 'bg-rose-600';
                        case 'izin':    return 'bg-purple-500';
                        case 'service': return 'bg-orange-600';
                        default:        return 'bg-gray-500';
                    }
                },

                getItemBgClass(item) {
                    if (item.type === 'booking') {
                        return this.getBookingStatusBg(item.status);
                    } else {
                        return this.getAttendanceStatusBg(item.status);
                    }
                },

                getItemLabel(item) {
                    if (item.type === 'booking') {
                        return item.driver_name + ' - ' + item.destination;
                    } else {
                        return item.status.toUpperCase() + ': ' + item.driver_name;
                    }
                },

                get calendarDays() {
                    const days = [];
                    const firstDay = new Date(this.currentYear, this.currentMonth, 1).getDay();
                    const totalDays = new Date(this.currentYear, this.currentMonth + 1, 0).getDate();
                    const prevMonthTotalDays = new Date(this.currentYear, this.currentMonth, 0).getDate();
                    const todayStr = new Date().toISOString().split('T')[0];

                    for (let i = firstDay - 1; i >= 0; i--) {
                        const dayNum = prevMonthTotalDays - i;
                        days.push({ dayNumber: dayNum, isCurrentMonth: false, isToday: false, items: [] });
                    }

                    for (let d = 1; d <= totalDays; d++) {
                        const monthStr = String(this.currentMonth + 1).padStart(2, '0');
                        const dayStr = String(d).padStart(2, '0');
                        const dateString = `${this.currentYear}-${monthStr}-${dayStr}`;

                        let bookingsOnDay = this.allBookings.filter(b => {
                            const start = this.cleanDate(b.start_date);
                            const end = this.cleanDate(b.end_date);
                            return dateString >= start && dateString <= end;
                        });

                        let attendancesOnDay = this.allAttendances.filter(a => {
                            const start = this.cleanDate(a.start_date);
                            const end = this.cleanDate(a.end_date);
                            return dateString >= start && dateString <= end;
                        });

                        if (this.selectedDriverFilter) {
                            bookingsOnDay = bookingsOnDay.filter(b => String(b.driver_id) === String(this.selectedDriverFilter));
                            attendancesOnDay = attendancesOnDay.filter(a => String(a.driver_id) === String(this.selectedDriverFilter));
                        }

                        const combinedItems = [...bookingsOnDay, ...attendancesOnDay];

                        days.push({
                            dayNumber: d,
                            dateString: dateString,
                            isCurrentMonth: true,
                            isToday: dateString === todayStr,
                            totalEventsCount: combinedItems.length,
                            items: combinedItems
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

                    this.assignedBookings = this.allBookings.filter(b => {
                        const start = this.cleanDate(b.start_date);
                        const end = this.cleanDate(b.end_date);
                        return targetDate >= start && targetDate <= end;
                    });

                    this.absentDrivers = this.allAttendances.filter(a => {
                        const start = this.cleanDate(a.start_date);
                        const end = this.cleanDate(a.end_date);
                        return targetDate >= start && targetDate <= end;
                    });

                    const busyDriverIds = [
                        ...this.assignedBookings.map(b => b.driver_id),
                        ...this.absentDrivers.map(a => a.driver_id)
                    ].filter(Boolean);

                    this.availableDrivers = this.allDrivers.filter(d => !busyDriverIds.includes(d.id));

                    this.showDateModal = true;
                }
            }
        }
    </script>
</x-app-layout>