<x-app-layout>
    <x-slot name="title">Kelola Peminjaman</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Daftar Peminjaman Kendaraan') }}
        </h2>
    </x-slot>

    <div class="pt-2 pb-6" 
        x-data="{ 
            search: '', 
            sort: 'upcoming',
            bookings: {{ Illuminate\Support\Js::from($bookings->items()) }}, 
            selectedBooking: null,
            activeBooking: null,
            isLoading: false,

            {{-- MODAL REVIEW (ADMIN LAPANGAN: ALOKASI ARMADA & DRIVER) --}}
            vehicles: {{ Illuminate\Support\Js::from($availableVehicles) }},
            drivers: {{ Illuminate\Support\Js::from($availableDrivers) }},
            activeBookings: {{ Illuminate\Support\Js::from($activeBookings) }},
            showReviewModal: false,
            reviewForm: {
                action: 'approve',
                vehicle_id: '',
                driver_id: '',
                admin_note: ''
            },

            // 💡 HELPER: Filter Armada yang Tidak Bentrok
            getAvailableVehicles() {
                if (!this.selectedBooking) return this.vehicles;

                const reqStart = new Date(this.selectedBooking.start_date);
                const reqEnd   = new Date(this.selectedBooking.end_date);
                const currentBookingId = this.selectedBooking.id;

                return this.vehicles.filter(vehicle => {
                    const isConflict = this.activeBookings.some(active => {
                        if (active.id === currentBookingId) return false;
                        if (active.vehicle_id !== vehicle.id) return false;

                        const actStart = new Date(active.start_date);
                        const actEnd   = new Date(active.end_date);

                        return (reqStart <= actEnd && reqEnd >= actStart);
                    });

                    return !isConflict;
                });
            },

            // 💡 HELPER: Filter Driver yang Tidak Bentrok
            getAvailableDrivers() {
                if (!this.selectedBooking) return this.drivers;

                const reqStart = new Date(this.selectedBooking.start_date);
                const reqEnd   = new Date(this.selectedBooking.end_date);
                const currentBookingId = this.selectedBooking.id;

                const freeDrivers = this.drivers.filter(driver => {
                    if (driver.role !== 'driver') return false;

                    return !this.activeBookings.some(active => {
                        if (active.id === currentBookingId) return false;
                        if (active.driver_id !== driver.id) return false;

                        const actStart = new Date(active.start_date);
                        const actEnd   = new Date(active.end_date);

                        return (reqStart <= actEnd && reqEnd >= actStart);
                    });
                });
                const participantsList = this.selectedBooking.participants_users || [];
                return {
                    primaryDrivers: freeDrivers,
                    participants: participantsList
                };
            },
            openReviewModal(booking) {
                this.selectedBooking = booking;
                if (booking.participants && Array.isArray(booking.participants)) {
                    this.selectedBooking.participants_users = this.drivers.filter(d => 
                        booking.participants.includes(d.id) || booking.participants.includes(String(d.id))
                    );
                } else {
                    this.selectedBooking.participants_users = [];
                }
                this.reviewForm.action = 'approve';
                this.reviewForm.vehicle_id = booking.vehicle_id || '';
                this.reviewForm.driver_id = booking.driver_id || '';
                this.reviewForm.admin_note = booking.admin_note || '';
                this.showReviewModal = true;
            },

            {{-- STATE MODAL APPROVER (STATUS: WAITING & CHECKED) --}}
            showApproverModal: false,
            approverActionType: '', // 'waiting' atau 'checked'
            approverForm: {
                action: 'approve',
                admin_note: ''
            },

            openApproverModal(booking, type) {
                this.selectedBooking = booking;
                this.approverActionType = type;
                this.approverForm.action = 'approve';
                this.approverForm.admin_note = '';
                this.showApproverModal = true;
            },

            {{-- STATE MODAL VERIFY RETURN (ADMIN LAPANGAN: TAHAP 4B) --}}
            showVerifyReturnModal: false,
            openVerifyReturnModal(booking) {
                this.selectedBooking = booking;
                this.showVerifyReturnModal = true;
            },

            {{-- MODAL DETAIL ROUTE --}}
            showRouteModal: false,
            routeBookingDetail: null,
            openRouteDetailModal(booking) {
                this.routeBookingDetail = booking;
                this.showRouteModal = true;
            },
            getGoogleMapsUrl(destination) {
                if (!destination) return '#';
                return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(destination)}`;
            },
            getWaUrl(phone) {
                if (!phone) return '#';
                let cleanPhone = phone.replace(/[^0-9]/g, '');
                if (cleanPhone.startsWith('0')) {
                    cleanPhone = '62' + cleanPhone.slice(1);
                }
                return `https://wa.me/${cleanPhone}`;
            },

            {{-- STATE MODAL START TRIP --}}
            showStartTripModal: false,
            startTripForm: {
                letter_slug: '',
                start_km: '',
                last_km_reference: 0,
                start_fuel_level: 'Full'
            },
            openStartTripModal(booking) {
                this.startTripForm.letter_slug = booking.letter_slug;
                this.startTripForm.last_km_reference = booking.vehicle?.last_km || 0;
                this.startTripForm.start_km = booking.vehicle?.last_km || '';
                this.startTripForm.start_fuel_level = 'Full';
                this.showStartTripModal = true;
            },

            {{-- STATE MODAL COMPLETE TRIP (DRIVER) --}}
            showCompleteModal: false,
            completeTripForm: {
                letter_slug: '',
                start_km: 0,
                end_km: '',
                end_fuel_level: 'Full',
                hasIncident: false
            },
            openCompleteModal(booking) {
                this.completeTripForm.letter_slug = booking.letter_slug;
                this.completeTripForm.start_km = booking.log?.start_km || booking.vehicle?.last_km || 0;
                this.completeTripForm.end_km = '';
                this.completeTripForm.end_fuel_level = 'Full';
                this.completeTripForm.hasIncident = false;
                this.showCompleteModal = true;
            },

            {{-- STATE MODAL BATAL PENGAJUAN (PEMOHON) --}}
            showCancelBookingModal: false,
            cancelBookingForm: {
                letter_slug: '',
                letter_number: ''
            },
            openCancelBookingModal(booking) {
                this.cancelBookingForm.letter_slug = booking.letter_slug;
                this.cancelBookingForm.letter_number = booking.letter_number;
                this.showCancelBookingModal = true;
            },

            {{-- STATE MODAL BATAL PERSETUJUAN (ADMIN) --}}
            showCancelApprovalModal: false,
            cancelApprovalForm: {
                letter_slug: '',
                letter_number: '',
                vehicle_name: '',
                cancel_reason: ''
            },
            openCancelApprovalModal(booking) {
                this.cancelApprovalForm.letter_slug = booking.letter_slug;
                this.cancelApprovalForm.letter_number = booking.letter_number;
                this.cancelApprovalForm.vehicle_name = booking.vehicle ? `${booking.vehicle.brand} ${booking.vehicle.model} (${booking.vehicle.plate_number})` : 'Armada Operasional';
                this.cancelApprovalForm.cancel_reason = '';
                this.showCancelApprovalModal = true;
            },

            formatDate(dateString) {
                if (!dateString) return '-';
                const date = new Date(dateString);
                return date.toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            },

            fetchBookings() {
                this.isLoading = true;
                fetch(`/api/search/bookings?q=${encodeURIComponent(this.search)}&sort=${this.sort}`)
                    .then(res => res.json())
                    .then(data => {
                        this.bookings = data.data;
                        this.isLoading = false;
                    })
                    .catch(() => { this.isLoading = false; });
            }
        }"
        x-init="$watch('search', value => fetchBookings());$watch('sort', value => fetchBookings())">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                {{-- AREA SEARCH, DROPDOWN SORTING & TOMBOL AJUKAN --}}
                <div class="mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
                    
                    <div class="flex items-center gap-3 w-full max-w-xl">
                        {{-- KOTAK PENCARIAN REAL-TIME --}}
                        <div class="relative w-full">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                🔍
                            </div>
                            <input type="text" 
                                   x-model="search" 
                                   @input.debounce.300ms="fetchBookings()" 
                                   placeholder="Cari No Surat, Nama Pemohon, NIP, Plat Mobil, atau Tujuan..." 
                                   class="pl-10 pr-10 py-2 w-full text-sm border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm">
                            
                            {{-- Spinner Loader --}}
                            <div x-show="isLoading" class="absolute inset-y-0 right-0 pr-3 flex items-center" x-cloak>
                                <svg class="animate-spin h-4 w-4 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>

                        {{-- DROPDOWN MODE PENGURUTAN JADWAL --}}
                        <select x-model="sort" 
                                @change="fetchBookings()" 
                                class="py-2 px-6 text-sm border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm bg-white cursor-pointer whitespace-nowrap">
                            <option value="created_latest">🆕 Pengajuan Terbaru</option>
                            <option value="upcoming">📅 Jadwal Terdekat</option>
                            <option value="start_desc">🗓️ Jadwal Terjauh</option>
                        </select>
                    </div>

                    <a href="{{ route('bookings.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold hover:bg-indigo-700 transition whitespace-nowrap">
                        + Ajukan Peminjaman
                    </a>
                </div>

                {{-- TABEL PEMINJAMAN KENDARAAN --}}
                <div class="text-gray-900 overflow-x-auto pb-24 pt-2 overflow-y-visible">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b bg-gray-50 text-xs font-semibold text-gray-600 uppercase">
                                <th class="p-3">No Surat Tugas</th>
                                <th class="p-3">Peminjam</th>
                                <th class="p-3">Mobil</th>
                                <th class="p-3">Jadwal</th>
                                <th class="p-3">Status</th>
                                <th class="p-3">Jarak Tempuh</th>
                                <th class="p-3 text-center">Aksi Operasional</th>
                                <th class="p-3 w-10 text-center"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 text-sm">
                            <template x-for="booking in bookings" :key="booking.id">
                                <tr>
                                    {{-- NOMOR SURAT TUGAS --}}
                                    <td class="p-3 font-mono font-bold">
                                        <a :href="`/bookings/${booking.letter_slug}`" class="text-indigo-600 hover:text-indigo-900 underline" x-text="booking.letter_number"></a>
                                    </td>
                                    
                                    {{-- PEMINJAM --}}
                                    <td class="p-3" x-text="booking.user ? booking.user.name : '-'"></td>
                                    
                                    {{-- MOBIL --}}
                                    <td class="p-3 font-medium">
                                        <div x-text="booking.vehicle ? `${booking.vehicle.brand} ${booking.vehicle.model} (${booking.vehicle.plate_number})` : '-'"></div>

                                        <template x-if="booking.needs_reassign">
                                            <div class="mt-1">
                                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-md bg-amber-100 text-amber-800 border border-amber-300 animate-pulse">
                                                    <svg class="w-3.5 h-3.5 mr-1 text-amber-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1,0 11-2 0 1 1 0 012 0zm-1-8a1 1,0 00-1 1v3a1 1,0 002 0V6a1 1,0 00-1-1z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    Armada Maintenance (Perlu Ganti)
                                                </span>
                                            </div>
                                        </template>
                                    </td>
                                    
                                    {{-- JADWAL --}}
                                    <td class="p-3 text-xs whitespace-nowrap">
                                        <div><strong>Mulai:</strong> <span x-text="formatDate(booking.start_date)"></span></div>
                                        <div><strong>Selesai:</strong> <span x-text="formatDate(booking.end_date)"></span></div>
                                    </td>
                                    
                                    {{-- STATUS BADGE --}}
                                    <td class="p-3">
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-full whitespace-nowrap"
                                            :class="{
                                                'bg-yellow-100 text-yellow-800': booking.status === 'pending',
                                                'bg-amber-100 text-amber-800': booking.status === 'waiting',
                                                'bg-blue-100 text-blue-800': booking.status === 'approved',
                                                'bg-purple-100 text-purple-800': booking.status === 'on_trip',
                                                'bg-indigo-100 text-indigo-800': booking.status === 'returned',
                                                'bg-cyan-100 text-cyan-800': booking.status === 'checked',
                                                'bg-emerald-100 text-emerald-800': booking.status === 'completed',
                                                'bg-gray-200 text-gray-700': booking.status === 'canceled',
                                                'bg-rose-100 text-rose-800': booking.status === 'rejected'
                                            }"
                                            x-text="booking.status.replace('_', ' ').toUpperCase()">
                                        </span>
                                    </td>
                                    
                                    {{-- JARAK TEMPUH --}}
                                    <td class="p-3">
                                        <template x-if="booking.log && booking.log.distance_traveled !== null">
                                            <div>
                                                <span class="font-bold text-indigo-600" x-text="`${booking.log.distance_traveled} KM`"></span>
                                                <span class="block text-xs text-gray-500" x-text="`(${booking.log.start_km} - ${booking.log.end_km} KM)`"></span>
                                            </div>
                                        </template>
                                        <template x-if="!booking.log || booking.log.distance_traveled === null">
                                            <span class="text-gray-400 text-xs">-</span>
                                        </template>
                                    </td>

                                    {{-- 1. KOLOM AKSI OPERASIONAL (UTAMA) --}}
                                    <td class="p-3 text-center whitespace-nowrap">
                                        <div class="inline-flex items-center justify-center gap-1.5">

                                            {{-- A. AKSI KHUSUS DRIVER --}}
                                            @if(Auth::user()->role === 'driver')
                                                <template x-if="booking.status === 'approved' && booking.driver_id === {{ Auth::id() }}">
                                                    <button type="button" 
                                                            @click="openStartTripModal(booking)"
                                                            class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs shadow-xs transition flex items-center gap-1">
                                                        <span>🚩 Mulai Tugas (Input KM)</span>
                                                    </button>
                                                </template>

                                                <template x-if="booking.status === 'on_trip' && booking.driver_id === {{ Auth::id() }}">
                                                    <button type="button" 
                                                            @click="openCompleteModal(booking)"
                                                            class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-lg text-xs shadow-xs transition flex items-center gap-1">
                                                        <span>🏁 Selesaikan Tugas</span>
                                                    </button>
                                                </template>

                                                <template x-if="['pending', 'waiting', 'returned', 'checked', 'rejected', 'canceled', 'completed'].includes(booking.status)">
                                                    <span class="text-xs text-gray-400 font-medium italic">Tidak Ada Aksi</span>
                                                </template>
                                            @endif

                                            {{-- B. AKSI KHUSUS ADMIN LAPANGAN & ADMIN APLIKASI --}}
                                            @if(in_array(Auth::user()->role, ['admin_lap', 'admin_app']))
                                                
                                                {{-- STATUS PENDING: Tinjau & Plot Armada --}}
                                                <template x-if="booking.status === 'pending'">
                                                    <button type="button" 
                                                            @click="openReviewModal(booking)"
                                                            class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg text-xs shadow-xs transition flex items-center gap-1">
                                                        <span>📋 Tinjau & Plot Armada</span>
                                                    </button>
                                                </template>

                                                {{-- STATUS WAITING & APPROVED --}}
                                                <template x-if="['waiting', 'approved'].includes(booking.status)">
                                                    <span class="text-xs text-gray-400 font-medium italic">(Menunggu Keberangkatan)</span>
                                                </template>

                                                {{-- STATUS ON_TRIP --}}
                                                <template x-if="booking.status === 'on_trip'">
                                                    <span class="text-xs text-purple-600 font-medium italic">🚗 Dalam Perjalanan</span>
                                                </template>

                                                {{-- TAHAP 4B: STATUS RETURNED -> Admin Lapangan Meninjau Kepulangan --}}
                                                <template x-if="booking.status === 'returned'">
                                                    <button type="button" 
                                                            @click="openVerifyReturnModal(booking)"
                                                            class="px-3 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white font-bold rounded-lg text-xs shadow-xs transition flex items-center gap-1">
                                                        <span>🔍 Tinjau Kepulangan Fisik</span>
                                                    </button>
                                                </template>

                                                <template x-if="booking.status === 'checked'">
                                                    <span class="text-xs text-cyan-700 font-medium italic">(Menunggu Pengesahan Approver)</span>
                                                </template>

                                                <template x-if="['rejected', 'completed', 'canceled'].includes(booking.status)">
                                                    <span class="text-xs text-gray-400 font-medium italic">Selesai / Terarsip</span>
                                                </template>

                                            {{-- C. TAMPILAN LABEL STATUS UNTUK PEGAWAI / NON-ADMIN --}}
                                            @elseif(!in_array(Auth::user()->role, ['driver', 'approver']))
                                                <template x-if="booking.status === 'pending'">
                                                    <span class="text-xs text-amber-600 font-semibold flex items-center justify-center gap-1">
                                                        <span>⏳</span> Menunggu Alokasi
                                                    </span>
                                                </template>

                                                <template x-if="booking.status === 'waiting'">
                                                    <span class="text-xs text-indigo-600 font-bold flex items-center justify-center gap-1">
                                                        <span>📋</span> Menunggu Persetujuan Atasan
                                                    </span>
                                                </template>

                                                <template x-if="booking.status === 'approved'">
                                                    <span class="text-xs text-blue-600 font-bold flex items-center justify-center gap-1">
                                                        <span>✅</span> Disetujui (Siap Berangkat)
                                                    </span>
                                                </template>

                                                <template x-if="booking.status === 'on_trip'">
                                                    <span class="text-xs text-purple-600 font-bold flex items-center justify-center gap-1">
                                                        <span>🚗</span> Dalam Perjalanan
                                                    </span>
                                                </template>

                                                <template x-if="booking.status === 'returned'">
                                                    <span class="text-xs text-indigo-600 font-bold flex items-center justify-center gap-1">
                                                        <span>🔄</span> Menunggu Pemeriksaan Admin
                                                    </span>
                                                </template>

                                                <template x-if="booking.status === 'checked'">
                                                    <span class="text-xs text-cyan-600 font-bold flex items-center justify-center gap-1">
                                                        <span>🏁</span> Menunggu Pengesahan BAST
                                                    </span>
                                                </template>

                                                <template x-if="booking.status === 'rejected'">
                                                    <div class="inline-flex items-center gap-2">
                                                        <span class="text-xs text-red-600 font-medium px-2 py-0.5 bg-red-50 rounded border border-red-200">
                                                            Ditolak
                                                        </span>
                                                        <a :href="`/bookings/${booking.letter_slug}/edit`" 
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-300 hover:bg-amber-100 rounded-md transition-colors"
                                                        title="Perbaiki Permohonan">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                            </svg>
                                                            <span>Perbaiki</span>
                                                        </a>
                                                    </div>
                                                </template>

                                                <template x-if="['completed', 'canceled'].includes(booking.status)">
                                                    <span class="text-xs text-gray-400 font-medium">- Tidak ada aksi-</span>
                                                </template>
                                            @endif

                                            {{-- D. AKSI KHUSUS APPROVER (ATASAN / KEPALA BAGIAN) --}}
                                            @if(Auth::user()->role === 'approver')

                                                {{-- STATUS WAITING: Menunggu Persetujuan Permohonan & Serah Terima --}}
                                                <template x-if="booking.status === 'waiting'">
                                                    <button type="button" 
                                                            @click="openApproverModal(booking, 'waiting')"
                                                            class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg text-xs shadow-xs transition flex items-center gap-1">
                                                        <span>📋 Tinjau & Setujui Permohonan</span>
                                                    </button>
                                                </template>

                                                {{-- STATUS CHECKED: Menunggu Persetujuan Akhir Pengembalian --}}
                                                <template x-if="booking.status === 'checked'">
                                                    <button type="button" 
                                                            @click="openApproverModal(booking, 'checked')"
                                                            class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs shadow-xs transition flex items-center gap-1">
                                                        <span>📋 Tinjau & Setujui Pengembalian</span>
                                                    </button>
                                                </template>

                                                <template x-if="!['waiting', 'checked'].includes(booking.status)">
                                                    <span class="text-xs text-gray-400 font-medium italic">Tidak Ada Aksi</span>
                                                </template>

                                            @endif

                                        </div>
                                    </td>

                                    {{-- 2. KOLOM MENU TITIK TIGA (DROPDOWN OPSI TAMBAHAN) --}}
                                    <td class="p-3 w-10 text-center relative" x-data="{ open: false }">
                                        <button @click="open = !open" 
                                                @click.away="open = false"
                                                class="p-1.5 text-gray-400 hover:text-gray-700 rounded-lg hover:bg-gray-100 transition focus:outline-none">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                                            </svg>
                                        </button>

                                        {{-- CONTENT DROPDOWN MENU --}}
                                        <div x-show="open" 
                                            x-transition:enter="transition ease-out duration-100"
                                            x-transition:enter-start="transform opacity-0 scale-95"
                                            x-transition:enter-end="transform opacity-100 scale-100"
                                            x-transition:leave="transition ease-in duration-75"
                                            x-transition:leave-start="transform opacity-100 scale-100"
                                            x-transition:leave-end="transform opacity-0 scale-95"
                                            class="absolute right-0 mt-1 w-52 bg-white rounded-xl shadow-xl border border-gray-100 py-2 z-30 text-left text-xs space-y-0.5"
                                            x-cloak>

                                            @php
                                                $userRole = Auth::user()->role;
                                                $userId   = Auth::id();
                                            @endphp

                                            {{-- 1. AKSI UNTUK DRIVER --}}
                                            @if($userRole === 'driver')
                                                <template x-if="booking.driver_id === {{ $userId }} && ['approved', 'on_trip'].includes(booking.status)">
                                                    <button type="button" @click="openRouteDetailModal(booking); open = false;" 
                                                            class="w-full flex items-center gap-2 px-4 py-2 text-indigo-600 hover:bg-indigo-50 font-medium transition text-left">
                                                        <span>🗺️</span> Detail Rute & Tim
                                                    </button>
                                                </template>

                                                <template x-if="booking.driver_id !== {{ $userId }} || !['approved', 'on_trip'].includes(booking.status)">
                                                    <div class="px-4 py-2 text-center text-gray-400 font-medium italic text-[11px] flex items-center justify-center gap-1.5">
                                                        <span>🚫</span> Tidak Ada Aksi
                                                    </div>
                                                </template>
                                            @endif

                                            {{-- 2. AKSI UNTUK PEGAWAI / PEMOHON UTAMA --}}
                                            @if(!in_array($userRole, ['admin_lap', 'admin_app', 'driver', 'approver']))
                                                <template x-if="booking.user_id === {{ $userId }} && booking.status === 'pending'">
                                                    <div class="space-y-0.5">
                                                        <a :href="`/bookings/${booking.letter_slug}/edit`" 
                                                        class="flex items-center gap-2 px-4 py-2 text-gray-700 hover:bg-amber-50 hover:text-amber-600 font-medium transition">
                                                            <span>✏️</span> Edit Permohonan
                                                        </a>
                                                        <button type="button" @click="openCancelBookingModal(booking); open = false;" 
                                                                class="w-full flex items-center gap-2 px-4 py-2 text-amber-600 hover:bg-amber-50 font-bold transition text-left">
                                                            <span>🚫</span> Batalkan Pengajuan
                                                        </button>
                                                    </div>
                                                </template>

                                                <template x-if="booking.user_id === {{ $userId }} && ['waiting', 'approved'].includes(booking.status)">
                                                    <button type="button" @click="openCancelBookingModal(booking); open = false;" 
                                                            class="w-full flex items-center gap-2 px-4 py-2 text-amber-600 hover:bg-amber-50 font-bold transition text-left">
                                                        <span>🚫</span> Batalkan Pengajuan
                                                    </button>
                                                </template>

                                                <template x-if="booking.user_id !== {{ $userId }} || !['pending', 'waiting', 'approved'].includes(booking.status)">
                                                    <div class="px-4 py-2 text-center text-gray-400 font-medium italic text-[11px] flex items-center justify-center gap-1.5">
                                                        <span>🚫</span> Tidak Ada Aksi
                                                    </div>
                                                </template>
                                            @endif

                                            {{-- 3. AKSI UNTUK ADMIN (ADMIN LAPANGAN & ADMIN APP) --}}
                                            @if(in_array($userRole, ['admin_lap', 'admin_app']))
                                                <template x-if="booking.status === 'pending'">
                                                    <div class="space-y-0.5">
                                                        <a :href="`/bookings/${booking.letter_slug}/edit`" 
                                                        class="flex items-center gap-2 px-4 py-2 text-gray-700 hover:bg-amber-50 hover:text-amber-600 font-medium transition">
                                                            <span>✏️</span> Edit Permohonan
                                                        </a>
                                                        <button type="button" @click="openReviewModal(booking); reviewForm.action = 'reject'; open = false;" 
                                                                class="w-full flex items-center gap-2 px-4 py-2 text-rose-600 hover:bg-rose-50 font-medium transition text-left">
                                                            <span>❌</span> Tolak Permohonan
                                                        </button>
                                                    </div>
                                                </template>

                                                <template x-if="['waiting'].includes(booking.status)">
                                                    <div class="space-y-0.5">
                                                        <button type="button" @click="openReviewModal(booking); open = false;" 
                                                                class="w-full flex items-center gap-2 px-4 py-2 text-indigo-600 hover:bg-indigo-50 font-medium transition text-left">
                                                            <span>🔄</span> Re-assign Armada
                                                        </button>
                                                        <button type="button" @click="openCancelApprovalModal(booking); open = false;" 
                                                                class="w-full flex items-center gap-2 px-4 py-2 text-rose-600 hover:bg-rose-50 font-bold transition text-left">
                                                            <span>❌</span> Batalkan Persetujuan
                                                        </button>
                                                    </div>
                                                </template>

                                                <template x-if="['approved', 'on_trip', 'returned', 'checked', 'completed', 'rejected', 'canceled'].includes(booking.status)">
                                                    <div class="px-4 py-2 text-center text-gray-400 font-medium italic text-[11px] flex items-center justify-center gap-1.5">
                                                        <span>🚫</span> Tidak Ada Aksi
                                                    </div>
                                                </template>
                                            @endif

                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4" x-show="search === ''">
                    {{ $bookings->links('vendor.pagination.tailwind') }}
                </div>

            </div>
        </div>

        {{-- MODAL PENINJAUAN & PLOTTING ARMADA (ADMIN LAPANGAN: TAHAP 1) --}}
        <div x-show="showReviewModal" 
            class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            x-cloak>

            <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full overflow-hidden border border-gray-100" @click.away="showReviewModal = false">
                
                <div class="bg-indigo-600 px-6 py-4 text-white flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-base flex items-center gap-2">
                            <span x-text="['waiting', 'approved'].includes(selectedBooking?.status) ? '🔄 Re-Assign Armada & Driver' : '📋 Alokasi Armada & Driver'"></span>
                        </h3>
                        <p class="text-xs text-indigo-100 font-mono mt-0.5" x-text="selectedBooking ? selectedBooking.letter_number : ''"></p>
                    </div>
                    <button type="button" @click="showReviewModal = false" class="text-white/80 hover:text-white text-2xl font-bold">&times;</button>
                </div>

                <form :action="selectedBooking ? `/bookings/${selectedBooking.letter_slug}/process-review` : '#'" method="POST" class="p-6 space-y-4">
                    @csrf
                    @method('PATCH')

                    <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-200 text-xs space-y-1.5">
                        <div class="flex justify-between">
                            <span class="text-gray-500 font-medium">Pemohon:</span>
                            <span class="font-bold text-gray-900" x-text="selectedBooking?.user?.name || '-'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 font-medium">Tipe Peminjaman:</span>
                            <span class="font-bold uppercase text-indigo-600" x-text="selectedBooking?.vehicle_type || '-'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 font-medium">Tujuan:</span>
                            <span class="font-bold text-gray-900" x-text="selectedBooking?.destination || '-'"></span>
                        </div>
                        <template x-if="selectedBooking?.vehicle_requirements">
                            <div class="pt-1 border-t border-gray-200 text-amber-800">
                                <span class="font-bold">Kebutuhan Khusus:</span>
                                <p class="italic text-[11px]" x-text="selectedBooking.vehicle_requirements"></p>
                            </div>
                        </template>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5">Keputusan Alokasi <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="cursor-pointer p-2.5 rounded-xl border text-center text-xs font-bold transition flex items-center justify-center gap-1.5"
                                :class="reviewForm.action === 'approve' ? 'bg-emerald-50 border-emerald-500 text-emerald-700 shadow-xs' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
                                <input type="radio" name="action" value="approve" x-model="reviewForm.action" class="hidden">
                                <span>✅ Setujui & Alokasi</span>
                            </label>

                            <label class="cursor-pointer p-2.5 rounded-xl border text-center text-xs font-bold transition flex items-center justify-center gap-1.5"
                                :class="reviewForm.action === 'reject' ? 'bg-rose-50 border-rose-500 text-rose-700 shadow-xs' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
                                <input type="radio" name="action" value="reject" x-model="reviewForm.action" class="hidden">
                                <span>❌ Tolak Permohonan</span>
                            </label>
                        </div>
                    </div>

                    <div x-show="reviewForm.action === 'approve'" class="space-y-3.5 pt-1">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                Pilih Unit Armada Kendaraan <span class="text-red-500">*</span>
                            </label>
                            <select name="vehicle_id" x-model="reviewForm.vehicle_id" :required="reviewForm.action === 'approve'"
                                    class="block w-full rounded-xl border-gray-300 text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">-- Pilih Kendaraan Ready --</option>
                                <template x-for="v in getAvailableVehicles()" :key="v.id">
                                    <option :value="v.id" x-text="`[${v.code}] ${v.brand} ${v.model} (${v.plate_number}) - [${(v.type || '').toUpperCase()}]`"></option>
                                </template>
                            </select>
                            
                            <template x-if="getAvailableVehicles().length === 0">
                                <p class="text-[11px] text-rose-600 font-bold mt-1.5 flex items-center gap-1">
                                    <span>⚠️</span> Seluruh armada telah terpakai / bentrok pada rentang jadwal ini.
                                </p>
                            </template>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                Penunjukan Driver / Pengemudi <span class="text-rose-500">*</span>
                            </label>
                            <select name="driver_id" x-model="reviewForm.driver_id" :required="reviewForm.action === 'approve'"
                                    class="block w-full rounded-xl border-gray-300 text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">-- Pilih Pengemudi --</option>
                                
                                <optgroup label="Driver Utama / Resmi">
                                    <template x-for="d in getAvailableDrivers().primaryDrivers" :key="d.id">
                                        <option :value="d.id" x-text="`👤 ${d.name} ${d.phone ? '(' + d.phone + ')' : ''}`"></option>
                                    </template>
                                </optgroup>

                                <template x-if="getAvailableDrivers().participants && getAvailableDrivers().participants.length > 0">
                                    <optgroup label="Anggota Tim Permohonan Ini">
                                        <template x-for="p in getAvailableDrivers().participants" :key="p.id">
                                            <option :value="p.id" x-text="`👥 ${p.name} (Anggota Tim)`"></option>
                                        </template>
                                    </optgroup>
                                </template>
                            </select>

                            <template x-if="getAvailableDrivers().primaryDrivers.length === 0">
                                <p class="text-[11px] text-amber-600 font-medium mt-1.5">
                                    ℹ️ Seluruh driver resmi sedang bertugas. Anda dapat menunjuk Anggota Tim di atas sebagai pengemudi.
                                </p>
                            </template>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">
                            <span x-text="reviewForm.action === 'reject' ? 'Alasan Penolakan' : 'Catatan Admin Lapangan'"></span>
                            <span x-show="reviewForm.action === 'reject'" class="text-red-500">*</span>
                        </label>
                        <textarea name="admin_note" x-model="reviewForm.admin_note" rows="2" 
                                :required="reviewForm.action === 'reject'"
                                :placeholder="reviewForm.action === 'reject' ? 'Tuliskan alasan penolakan permohonan...' : 'Tuliskan catatan tambahan jika ada...'"
                                class="block w-full rounded-xl border-gray-300 text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                    </div>

                    <div class="flex items-center gap-3 pt-3 border-t border-gray-100">
                        <button type="button" @click="showReviewModal = false" class="w-1/2 py-2.5 bg-gray-100 text-gray-700 text-xs font-bold rounded-xl hover:bg-gray-200 transition">
                            Batal
                        </button>
                        
                        <button type="submit" 
                                class="w-1/2 py-2.5 text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center justify-center gap-1.5"
                                :class="reviewForm.action === 'approve' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700'">
                            <span x-text="reviewForm.action === 'approve' ? 'Simpan & Alokasikan' : 'Konfirmasi Penolakan'"></span>
                        </button>
                    </div>
                </form>

            </div>
        </div>

        {{-- MODAL PENINJAUAN & PERSETUJUAN KHUSUS APPROVER (TAHAP 2 & 5) --}}
        <div x-show="showApproverModal" 
            class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            x-cloak>

            <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full overflow-hidden border border-gray-100" @click.away="showApproverModal = false">
                
                <div class="px-6 py-4 text-white flex justify-between items-center"
                    :class="approverActionType === 'waiting' ? 'bg-indigo-600' : 'bg-emerald-600'">
                    <div>
                        <h3 class="font-bold text-base flex items-center gap-2">
                            <span x-text="approverActionType === 'waiting' ? '📋 Persetujuan Permohonan Peminjaman' : '🏁 Persetujuan Akhir BAST Pengembalian'"></span>
                        </h3>
                        <p class="text-xs opacity-90 font-mono mt-0.5" x-text="selectedBooking ? 'No. Surat: ' + selectedBooking.letter_number : ''"></p>
                    </div>
                    <button type="button" @click="showApproverModal = false" class="text-white/80 hover:text-white text-2xl font-bold">&times;</button>
                </div>

                <form :action="selectedBooking ? `/bookings/${selectedBooking.letter_slug}/approver-review` : '#'" method="POST" class="p-6 space-y-4 text-xs">
                    @csrf
                    @method('PATCH')

                    <input type="hidden" name="action" :value="approverForm.action">

                    <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-200 space-y-1.5">
                        <div class="flex justify-between">
                            <span class="text-gray-500 font-medium">Pemohon Utama:</span>
                            <span class="font-bold text-gray-900" x-text="selectedBooking?.user?.name || '-'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 font-medium">Tujuan Dinas:</span>
                            <span class="font-bold text-gray-900" x-text="selectedBooking?.destination || '-'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 font-medium">Unit Armada Alokasi:</span>
                            <span class="font-bold text-indigo-700" x-text="selectedBooking?.vehicle ? `${selectedBooking.vehicle.brand} ${selectedBooking.vehicle.model} (${selectedBooking.vehicle.plate_number})` : '-'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 font-medium">Pengemudi Ditunjuk:</span>
                            <span class="font-bold text-gray-900" x-text="selectedBooking?.driver ? selectedBooking.driver.name : '-'"></span>
                        </div>
                    </div>

                    <div class="p-3.5 bg-blue-50/70 border border-blue-200 rounded-xl space-y-2">
                        <span class="text-[10px] font-bold text-blue-800 uppercase block tracking-wider">📄 Pratinjau Dokumen Cetak</span>
                        
                        <template x-if="approverActionType === 'waiting'">
                            <div class="flex flex-wrap gap-2">
                                <a :href="selectedBooking ? `/bookings/${selectedBooking.letter_slug}/pdf/formulir-permohonan` : '#'" target="_blank"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-blue-300 text-blue-700 hover:bg-blue-100 font-bold rounded-lg text-xs transition shadow-xs">
                                    <span>📄 Lihat Formulir Permohonan</span>
                                </a>
                                <a :href="selectedBooking ? `/bookings/${selectedBooking.letter_slug}/pdf/bast-peminjaman` : '#'" target="_blank"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg text-xs transition shadow-xs">
                                    <span>📜 Lihat BAST Peminjaman</span>
                                </a>
                            </div>
                        </template>

                        <template x-if="approverActionType === 'checked'">
                            <div class="flex flex-wrap gap-2">
                                <a :href="selectedBooking ? `/bookings/${selectedBooking.letter_slug}/pdf/formulir-pengembalian` : '#'" target="_blank"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-blue-300 text-blue-700 hover:bg-blue-100 font-bold rounded-lg text-xs transition shadow-xs">
                                    <span>📄 Lihat Formulir Pengembalian</span>
                                </a>
                                <a :href="selectedBooking ? `/bookings/${selectedBooking.letter_slug}/pdf/bast-pengembalian` : '#'" target="_blank"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs transition shadow-xs">
                                    <span>📜 Lihat BAST Pengembalian</span>
                                </a>
                            </div>
                        </template>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5">Keputusan Persetujuan <span class="text-rose-500">*</span></label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="cursor-pointer p-2.5 rounded-xl border text-center text-xs font-bold transition flex items-center justify-center gap-1.5"
                                :class="approverForm.action === 'approve' ? 'bg-emerald-50 border-emerald-500 text-emerald-700 shadow-xs' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
                                <input type="radio" name="action_option" value="approve" x-model="approverForm.action" class="hidden">
                                <span>✅ Setujui Dokumen</span>
                            </label>

                            <label class="cursor-pointer p-2.5 rounded-xl border text-center text-xs font-bold transition flex items-center justify-center gap-1.5"
                                :class="approverForm.action === 'reject' ? 'bg-rose-50 border-rose-500 text-rose-700 shadow-xs' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
                                <input type="radio" name="action_option" value="reject" x-model="approverForm.action" class="hidden">
                                <span>❌ Tolak / Kembalikan</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">
                            <span x-text="approverForm.action === 'reject' ? 'Alasan Penolakan' : 'Catatan Persetujuan (Opsional)'"></span>
                            <span x-show="approverForm.action === 'reject'" class="text-rose-500">*</span>
                        </label>
                        <textarea name="admin_note" x-model="approverForm.admin_note" rows="2"
                                :required="approverForm.action === 'reject'"
                                :placeholder="approverForm.action === 'reject' ? 'Tuliskan alasan penolakan permohonan...' : 'Tuliskan catatan persetujuan jika ada...'"
                                class="block w-full rounded-xl border-gray-300 text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                    </div>

                    <div class="flex items-center gap-3 pt-3 border-t border-gray-100">
                        <button type="button" @click="showApproverModal = false" class="w-1/2 py-2.5 bg-gray-100 text-gray-700 text-xs font-bold rounded-xl hover:bg-gray-200 transition">
                            Batal
                        </button>
                        
                        <button type="submit" 
                                class="w-1/2 py-2.5 text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center justify-center gap-1.5"
                                :class="approverForm.action === 'approve' ? (approverActionType === 'waiting' ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-emerald-600 hover:bg-emerald-700') : 'bg-rose-600 hover:bg-rose-700'">
                            <span x-text="approverForm.action === 'approve' ? 'Konfirmasi & Setujui' : 'Konfirmasi Penolakan'"></span>
                        </button>
                    </div>
                </form>

            </div>
        </div>

        {{-- MODAL PENINJAUAN FISIK PENGEMBALIAN OLEH ADMIN LAPANGAN (TAHAP 4B) --}}
        <div x-show="showVerifyReturnModal" 
            class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            x-cloak>

            <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full overflow-hidden border border-gray-100" @click.away="showVerifyReturnModal = false">
                
                <div class="bg-cyan-600 px-6 py-4 text-white flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-base flex items-center gap-2">
                            <span>🔍</span> Pemeriksaan Fisik Kepulangan Kendaraan
                        </h3>
                        <p class="text-xs text-cyan-100 font-mono mt-0.5" x-text="selectedBooking ? 'No. Surat: ' + selectedBooking.letter_number : ''"></p>
                    </div>
                    <button type="button" @click="showVerifyReturnModal = false" class="text-white/80 hover:text-white text-2xl font-bold">&times;</button>
                </div>

                <form :action="selectedBooking ? `/bookings/${selectedBooking.letter_slug}/verify-return` : '#'" method="POST" class="p-6 space-y-4 text-xs">
                    @csrf
                    @method('PATCH')

                    <div class="bg-cyan-50/60 p-4 rounded-xl border border-cyan-200 space-y-2">
                        <span class="text-[10px] font-bold text-cyan-800 uppercase tracking-wider block">Ringkasan Log Pengembalian Driver</span>
                        
                        <div class="grid grid-cols-2 gap-3 pt-1 text-gray-800">
                            <div>
                                <span class="text-[10px] text-gray-500 block">Odometer Awal:</span>
                                <p class="font-bold" x-text="(selectedBooking?.log?.start_km || 0) + ' KM'"></p>
                            </div>
                            <div>
                                <span class="text-[10px] text-gray-500 block">Odometer Akhir:</span>
                                <p class="font-bold text-indigo-700" x-text="(selectedBooking?.log?.end_km || 0) + ' KM'"></p>
                            </div>
                            <div>
                                <span class="text-[10px] text-gray-500 block">BBM Akhir:</span>
                                <p class="font-bold" x-text="selectedBooking?.log?.end_fuel_level || '-'"></p>
                            </div>
                            <div>
                                <span class="text-[10px] text-gray-500 block">Total Jarak Tempuh:</span>
                                <p class="font-bold text-emerald-600" x-text="(selectedBooking?.log?.distance_traveled || 0) + ' KM'"></p>
                            </div>
                        </div>

                        <template x-if="selectedBooking?.log?.condition_notes">
                            <div class="pt-2 border-t border-cyan-200">
                                <span class="text-[10px] text-rose-700 font-bold block">⚠️ Catatan Kondisi / Insiden:</span>
                                <p class="text-rose-950 italic" x-text="selectedBooking.log.condition_notes"></p>
                            </div>
                        </template>
                    </div>

                    <p class="text-gray-600 leading-relaxed">
                        Apakah Anda telah selesai memeriksa kondisi fisik kendaraan dan memastikan odometer serta BBM sudah sesuai?
                    </p>

                    <div class="flex items-center gap-3 pt-3 border-t border-gray-100">
                        <button type="button" @click="showVerifyReturnModal = false" class="w-1/2 py-2.5 bg-gray-100 text-gray-700 text-xs font-bold rounded-xl hover:bg-gray-200 transition">
                            Batal
                        </button>
                        
                        <button type="submit" class="w-1/2 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center justify-center gap-1.5">
                            <span>✅ Konfirmasi Pemeriksaan</span>
                        </button>
                    </div>
                </form>

            </div>
        </div>

        {{-- MODAL DETAIL RUTE & TIM UNTUK DRIVER --}}
        <div x-show="showRouteModal" 
            class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50" 
            x-transition
            x-cloak>
            
            <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full overflow-hidden border border-gray-100" @click.away="showRouteModal = false">
                
                <div class="bg-indigo-600 px-6 py-4 text-white flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-base flex items-center gap-2">
                            <span>🗺️</span> Detail Rute & Tim Penumpang
                        </h3>
                        <p class="text-xs text-indigo-100 mt-0.5" x-text="routeBookingDetail ? 'No. Surat: ' + routeBookingDetail.letter_number : ''"></p>
                    </div>
                    <button type="button" @click="showRouteModal = false" class="text-white/80 hover:text-white text-2xl font-bold">&times;</button>
                </div>

                <div class="p-6 space-y-5 text-xs text-gray-700" x-if="routeBookingDetail">
                    
                    <div class="p-4 bg-indigo-50/70 rounded-xl border border-indigo-100 space-y-2">
                        <span class="text-[10px] font-bold text-indigo-700 uppercase tracking-wider block">Lokasi Tujuan Dinas</span>
                        <p class="text-sm font-bold text-gray-900" x-text="routeBookingDetail.destination || '-'"></p>
                        
                        <a :href="getGoogleMapsUrl(routeBookingDetail.destination)" target="_blank" 
                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg text-xs transition shadow-xs mt-1">
                            <span>📍 Buka Navigasi Google Maps</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>
                    </div>

                    <div class="grid grid-cols-2 gap-3 bg-gray-50 p-3.5 rounded-xl border border-gray-200/80">
                        <div>
                            <span class="text-[10px] text-gray-400 font-bold block">Keberangkatan:</span>
                            <p class="font-bold text-gray-800" x-text="formatDate(routeBookingDetail.start_date)"></p>
                        </div>
                        <div>
                            <span class="text-[10px] text-gray-400 font-bold block">Estimasi Kembali:</span>
                            <p class="font-bold text-gray-800" x-text="formatDate(routeBookingDetail.end_date)"></p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Kontak Tim / Penumpang</span>
                        
                        <div class="p-3 bg-white border border-gray-200 rounded-xl flex items-center justify-between">
                            <div>
                                <p class="font-bold text-gray-900" x-text="routeBookingDetail.user?.name || '-'"></p>
                                <p class="text-[11px] text-gray-500">Pemohon Utama (Penanggung Jawab)</p>
                            </div>
                            <template x-if="routeBookingDetail.user?.phone">
                                <a :href="getWaUrl(routeBookingDetail.user.phone)" target="_blank" 
                                class="px-2.5 py-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-bold rounded-lg transition flex items-center gap-1 text-[11px]">
                                    <span>💬 Chat WA</span>
                                </a>
                            </template>
                        </div>

                        <div class="pt-2">
                            <span class="text-[10px] font-bold text-gray-400 block">Maksud Perjalanan:</span>
                            <p class="text-gray-600 bg-gray-50 p-2.5 rounded-lg border border-gray-100 text-[11px] italic" x-text="routeBookingDetail.purpose || '-'"></p>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-gray-100 flex justify-end">
                        <button type="button" @click="showRouteModal = false" class="px-5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition">
                            Tutup
                        </button>
                    </div>

                </div>
            </div>
        </div>

        {{-- MODAL KEBERANGKATAN (START TRIP) --}}
        <div x-show="showStartTripModal" 
            class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50" 
            x-transition
            x-cloak>
            
            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full overflow-hidden border border-gray-100" @click.away="showStartTripModal = false">
                
                <div class="bg-emerald-600 px-6 py-4 text-white flex justify-between items-center">
                    <h3 class="font-bold text-base flex items-center gap-2">
                        <span>🚀</span> Mulai Perjalanan Dinas
                    </h3>
                    <button type="button" @click="showStartTripModal = false" class="text-white/80 hover:text-white text-2xl font-bold">&times;</button>
                </div>

                <form :action="`/bookings/${startTripForm.letter_slug}/start-trip`" method="POST" enctype="multipart/form-data" class="p-6 space-y-4 text-xs">
                    @csrf
                    @method('PATCH')

                    <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-emerald-700 font-bold uppercase block">KM Terakhir Tercatat:</span>
                            <p class="text-sm font-bold text-emerald-950" x-text="startTripForm.last_km_reference + ' KM'"></p>
                        </div>
                        <span class="text-[10px] bg-emerald-200 text-emerald-800 px-2 py-1 rounded-lg font-bold">Acuan Sistem</span>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-gray-700">
                            Odometer / KM Awal Keberangkatan <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" name="start_km" x-model="startTripForm.start_km" required :min="startTripForm.last_km_reference"
                            placeholder="Contoh: 45200"
                            class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold text-gray-900 focus:bg-white focus:ring-emerald-500 focus:border-emerald-500">
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-gray-700">
                            Kapasitas BBM Awal <span class="text-rose-500">*</span>
                        </label>
                        <select name="start_fuel_level" x-model="startTripForm.start_fuel_level" required
                                class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium focus:bg-white focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="Full">Full (Penuh)</option>
                            <option value="3/4">3/4 Tank</option>
                            <option value="1/2">1/2 Tank</option>
                            <option value="1/4">1/4 Tank</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-gray-700">
                            Foto Odometer / Fisik Awal <span class="text-gray-400 font-normal">(Opsional)</span>
                        </label>
                        <input type="file" name="start_photo" accept="image/*"
                            class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 transition cursor-pointer">
                    </div>

                    <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2">
                        <button type="button" @click="showStartTripModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-xs transition flex items-center gap-1.5">
                            <span>Mulai Perjalanan</span>
                        </button>
                    </div>
                </form>

            </div>
        </div>

        {{-- MODAL PENGEMBALIAN (COMPLETE TRIP OLEH DRIVER) --}}
        <div x-show="showCompleteModal" 
            class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50" 
            x-transition
            x-cloak>
            
            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full overflow-hidden border border-gray-100" @click.away="showCompleteModal = false">
                
                <div class="bg-indigo-600 px-6 py-4 text-white flex justify-between items-center">
                    <h3 class="font-bold text-base flex items-center gap-2">
                        <span>🏁</span> Selesaikan Perjalanan & Pengembalian
                    </h3>
                    <button type="button" @click="showCompleteModal = false" class="text-white/80 hover:text-white text-2xl font-bold">&times;</button>
                </div>

                <form :action="`/bookings/${completeTripForm.letter_slug}/complete-trip`" method="POST" enctype="multipart/form-data" class="p-6 space-y-4 text-xs">
                    @csrf
                    @method('PATCH')

                    <div class="p-3 bg-indigo-50 rounded-xl border border-indigo-200 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-indigo-700 font-bold uppercase block">KM Awal Keberangkatan:</span>
                            <p class="text-sm font-bold text-indigo-950" x-text="completeTripForm.start_km + ' KM'"></p>
                        </div>
                        <template x-if="completeTripForm.end_km && completeTripForm.end_km >= completeTripForm.start_km">
                            <div class="text-right">
                                <span class="text-[10px] text-indigo-700 font-bold uppercase block">Total Tempuh:</span>
                                <p class="text-xs font-bold text-emerald-600" x-text="(completeTripForm.end_km - completeTripForm.start_km) + ' KM'"></p>
                            </div>
                        </template>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-gray-700">
                            Odometer / KM Akhir Pengembalian <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" name="end_km" x-model="completeTripForm.end_km" required :min="completeTripForm.start_km"
                            placeholder="Contoh: 45350"
                            class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold text-gray-900 focus:bg-white focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-gray-700">
                            Kapasitas BBM Akhir <span class="text-rose-500">*</span>
                        </label>
                        <select name="end_fuel_level" x-model="completeTripForm.end_fuel_level" required
                                class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium focus:bg-white focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="Full">Full (Penuh)</option>
                            <option value="3/4">3/4 Tank</option>
                            <option value="1/2">1/2 Tank</option>
                            <option value="1/4">1/4 Tank</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-gray-700">
                            Foto Odometer / Pengembalian <span class="text-gray-400 font-normal">(Opsional)</span>
                        </label>
                        <input type="file" name="end_photo" accept="image/*"
                            class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition cursor-pointer">
                    </div>

                    <div class="pt-2 border-t border-gray-100 space-y-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="completeTripForm.hasIncident" class="rounded border-gray-300 text-rose-600 focus:ring-rose-500">
                            <span class="font-bold text-rose-600 text-xs">⚠️ Ada kendala / insiden kondisi kendaraan selama perjalanan?</span>
                        </label>

                        <div x-show="completeTripForm.hasIncident" x-transition class="space-y-3 bg-rose-50/60 p-3.5 rounded-xl border border-rose-200">
                            <div class="space-y-1">
                                <label class="block font-bold text-rose-900">
                                    Catatan Kerusakan / Insiden
                                </label>
                                <textarea name="condition_notes" rows="2" placeholder="Jelaskan detail kendala (misal: bodi kanan tergores, lampu kiri mati...)"
                                        class="w-full px-3 py-2 bg-white border border-rose-200 rounded-xl text-xs font-medium focus:ring-rose-500 focus:border-rose-500"></textarea>
                            </div>

                            <div class="space-y-1">
                                <label class="block font-bold text-rose-900">
                                    Foto Bukti Kerusakan / Insiden <span class="text-gray-400 font-normal">(Bisa banyak foto)</span>
                                </label>
                                <input type="file" name="condition_photos[]" accept="image/*" multiple
                                    class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-rose-100 file:text-rose-700 hover:file:bg-rose-200 transition cursor-pointer">
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2">
                        <button type="button" @click="showCompleteModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-xs transition flex items-center gap-1.5">
                            <span>Selesaikan & Simpan Log</span>
                        </button>
                    </div>
                </form>

            </div>
        </div>

        {{-- MODAL KONFIRMASI BATAL PENGAJUAN (OLEH PEMOHON) --}}
        <div x-show="showCancelBookingModal" 
            class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50" 
            x-transition
            x-cloak>
            
            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full overflow-hidden border border-gray-100" @click.away="showCancelBookingModal = false">
                <div class="bg-amber-500 px-6 py-4 text-white flex justify-between items-center">
                    <h3 class="font-bold text-base flex items-center gap-2">
                        <span>⚠️</span> Konfirmasi Pembatalan Pengajuan
                    </h3>
                    <button type="button" @click="showCancelBookingModal = false" class="text-white/80 hover:text-white text-2xl font-bold">&times;</button>
                </div>

                <form :action="`/bookings/${cancelBookingForm.letter_slug}/cancel`" method="POST" class="p-6 space-y-4 text-xs">
                    @csrf
                    @method('PATCH')

                    <div class="p-3 bg-amber-50 rounded-xl border border-amber-200/80 text-amber-900 leading-relaxed">
                        Apakah Anda yakin ingin membatalkan permohonan peminjaman armada dengan nomor surat <strong x-text="cancelBookingForm.letter_number"></strong>?
                    </div>

                    <p class="text-gray-500 text-[11px]">
                        Aksi ini tidak dapat dibatalkan kembali. Status permohonan akan diperbarui menjadi <strong>Canceled</strong>.
                    </p>

                    <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2">
                        <button type="button" @click="showCancelBookingModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition">
                            Kembali
                        </button>
                        <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl shadow-xs transition">
                            Ya, Batalkan Pengajuan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- MODAL KONFIRMASI BATAL PERSETUJUAN (OLEH ADMIN) --}}
        <div x-show="showCancelApprovalModal" 
            class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50" 
            x-transition
            x-cloak>
            
            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full overflow-hidden border border-gray-100" @click.away="showCancelApprovalModal = false">
                <div class="bg-rose-600 px-6 py-4 text-white flex justify-between items-center">
                    <h3 class="font-bold text-base flex items-center gap-2">
                        <span>🚫</span> Pembatalan Persetujuan Peminjaman
                    </h3>
                    <button type="button" @click="showCancelApprovalModal = false" class="text-white/80 hover:text-white text-2xl font-bold">&times;</button>
                </div>

                <form :action="`/bookings/${cancelApprovalForm.letter_slug}/cancel-approval`" method="POST" class="p-6 space-y-4 text-xs">
                    @csrf
                    @method('PATCH')

                    <div class="p-3 bg-rose-50 rounded-xl border border-rose-200 text-rose-900 leading-relaxed space-y-1">
                        <p>Membatalkan persetujuan untuk No. Surat: <strong x-text="cancelApprovalForm.letter_number"></strong></p>
                        <p class="text-[11px] text-rose-700">Unit teralokasi (<strong x-text="cancelApprovalForm.vehicle_name"></strong>) dan Driver akan otomatis dilepaskan kembali menjadi ready.</p>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-gray-700">
                            Alasan Pembatalan Persetujuan <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="cancel_reason" x-model="cancelApprovalForm.cancel_reason" required rows="3"
                                placeholder="Jelaskan alasan pembatalan (misal: Unit mendadak rusak / Penugasan mendesak pimpinan...)"
                                class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium focus:bg-white focus:ring-rose-500 focus:border-rose-500"></textarea>
                    </div>

                    <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2">
                        <button type="button" @click="showCancelApprovalModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition">
                            Kembali
                        </button>
                        <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl shadow-xs transition">
                            Batalkan Persetujuan
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>