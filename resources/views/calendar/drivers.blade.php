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
                <a href="{{ route('calendar.index') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5 border border-gray-200">
                    <svg class="w-4 h-4 text-gray-500 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    <span>Jadwal Armada</span>
                </a>

                @if(in_array($currentUser->role, ['admin_app', 'admin_lap', 'driver']))
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

    <div class="pt-2 pb-6" 
        x-data="driverCalendarApp()" 
        @open-attendance-modal.window="openCreateAttendance()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- NAVIGASI BULAN & FILTER DRIVER --}}
            <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <button type="button" @click="previousMonth()" class="p-2 bg-gray-100 hover:bg-gray-200 rounded-xl text-gray-700 transition" title="Bulan Sebelumnya">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 18 9 12 15 6"/>
                        </svg>
                    </button>
                    <h3 class="text-xl font-black text-gray-900 font-mono" x-text="monthNames[currentMonth] + ' ' + currentYear"></h3>
                    <button type="button" @click="nextMonth()" class="p-2 bg-gray-100 hover:bg-gray-200 rounded-xl text-gray-700 transition" title="Bulan Selanjutnya">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </button>
                </div>

                <div class="flex items-center gap-3 w-full md:w-auto">
                    @if($currentUser->role === 'driver')
                        <span class="px-3 py-2 bg-indigo-50 border border-indigo-200 text-indigo-700 text-xs font-bold rounded-xl flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-indigo-600 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                            <span>Driver: {{ $currentUser->name }}</span>
                        </span>
                    @else
                        <select id="driver_filter" 
                            name="driver_filter"
                            aria-label="Filter Driver"
                            x-model="selectedDriverFilter" 
                            class="bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500 py-2 px-3 w-full md:w-56">
                        <option value="">-- Seluruh Driver --</option>
                        @foreach($drivers as $drv)
                            <option value="{{ $drv->id }}">{{ $drv->name }}</option>
                        @endforeach
                    </select>
                    @endif

                    <button type="button" @click="goToToday()" class="px-4 py-2 bg-indigo-50 border border-indigo-200 text-indigo-700 rounded-xl text-xs font-bold hover:bg-indigo-100 transition flex items-center gap-1.5 shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        <span>Hari Ini</span>
                    </button>
                </div>
            </div>

            {{-- LEGENDA STATUS --}}
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

            {{-- GRID KALENDER --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
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
                                      :class="day.isToday ? 'bg-indigo-600 text-white shadow-xs' : 'text-gray-800'"
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

        {{-- INKLUDI 5 MODAL TERPISAH --}}
        @include('calendar.modals.date-detail')
        @include('calendar.modals.booking-detail')
        @include('calendar.modals.attendance')
        @include('calendar.modals.approval')
        @include('calendar.modals.delete')

    </div>

    {{-- SCRIPT ALPINE.JS INTEGRASI --}}
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
                showApprovalModal: false,
                showDeleteModal: false,
                deleteAttendanceId: null,
                isEditAttendance: false,
                approvalStatus: 'approved',
                selectedDateFormatted: '',
                selectedDetailItem: null,
                selectedAttendance: null,
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
                        const statusPrefix = item.process_status === 'pending' ? 'WAIT: ' : '';
                        return statusPrefix + item.status.toUpperCase() + ': ' + item.driver_name;
                    }
                },

                get calendarDays() {
                    const days = [];
                    const year = parseInt(this.currentYear, 10);
                    const month = parseInt(this.currentMonth, 10);

                    const firstDay = new Date(year, month, 1).getDay();
                    const totalDays = new Date(year, month + 1, 0).getDate();
                    const prevMonthTotalDays = new Date(year, month, 0).getDate();

                    const now = new Date();
                    const todayStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;

                    const prevMonth = month === 0 ? 11 : month - 1;
                    const prevYear = month === 0 ? year - 1 : year;
                    const prevMonthStr = String(prevMonth + 1).padStart(2, '0');

                    for (let i = firstDay - 1; i >= 0; i--) {
                        const dayNum = prevMonthTotalDays - i;
                        const dayStr = String(dayNum).padStart(2, '0');
                        const paddingDateString = `${prevYear}-${prevMonthStr}-${dayStr}`;

                        days.push({ 
                            dayNumber: dayNum, 
                            dateString: paddingDateString, 
                            isCurrentMonth: false, 
                            isToday: false, 
                            items: [] 
                        });
                    }

                    for (let d = 1; d <= totalDays; d++) {
                        const monthStr = String(month + 1).padStart(2, '0');
                        const dayStr = String(d).padStart(2, '0');
                        const dateString = `${year}-${monthStr}-${dayStr}`;

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
                    this.currentMonth = parseInt(this.currentMonth, 10);
                    this.currentYear = parseInt(this.currentYear, 10);
                    if (this.currentMonth === 0) {
                        this.currentMonth = 11;
                        this.currentYear--;
                    } else {
                        this.currentMonth--;
                    }
                },

                nextMonth() {
                    this.currentMonth = parseInt(this.currentMonth, 10);
                    this.currentYear = parseInt(this.currentYear, 10);
                    if (this.currentMonth === 11) {
                        this.currentMonth = 0;
                        this.currentYear++;
                    } else {
                        this.currentMonth++;
                    }
                },

                goToToday() {
                    const now = new Date();
                    this.currentMonth = now.getMonth();
                    this.currentYear = now.getFullYear();
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
                        ...this.absentDrivers.filter(a => a.process_status === 'approved').map(a => a.driver_id)
                    ].filter(Boolean);

                    this.availableDrivers = this.allDrivers.filter(d => !busyDriverIds.includes(d.id));

                    this.showDateModal = true;
                }
            }
        }
    </script>
</x-app-layout>