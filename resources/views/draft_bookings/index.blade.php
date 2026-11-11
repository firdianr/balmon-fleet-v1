<x-app-layout>
    <x-slot name="title">Permohonan Armada</x-slot>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    Permohonan Armada (Draft)
                </h2>
                <p class="text-xs text-gray-500 mt-1">Daftar permohonan ketersediaan armada dan penugasan driver.</p>
            </div>
            <a href="{{ route('draft-bookings.create') }}" class="w-full sm:w-auto px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold shadow-md transition text-center flex items-center justify-center gap-2">
                <svg class="w-4 h-4 text-white stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                <span>Permohonan Baru</span>
            </a>
        </div>
    </x-slot>

    <div class="pt-0 pb-6" x-data="{ 
        // State Modal & Form Review
        reviewModal: false, 
        activeDraft: null,
        selectedVehicleId: '',
        selectedDriverId: '',
        adminNote: '',
        actionType: 'approve',
        errorMessage: '',

        // State Modal Hapus
        deleteModal: false,
        deleteActionUrl: '',

        // Master Data dari Controller
        vehicles: {{ Illuminate\Support\Js::from($vehicles) }},
        drivers: {{ Illuminate\Support\Js::from($drivers) }},
        activeBookings: {{ Illuminate\Support\Js::from($activeBookings ?? []) }},
        drafts: {{ Illuminate\Support\Js::from($drafts->items() ?? $drafts ?? []) }},
        attendances: {{ Illuminate\Support\Js::from($attendances ?? []) }},

        // Variable Penampung Hasil Filter
        availableVehicles: [],
        availableDrivers: [],

        // Helper: Parsing Tanggal yang Aman
        parseDate(dateStr) {
            if (!dateStr) return null;
            const formatted = String(dateStr).trim().replace(' ', 'T');
            const d = new Date(formatted);
            return isNaN(d.getTime()) ? null : d;
        },

        // Helper: Cek Bentrok Waktu (Overlapping)
        isOverlapping(startA, endA, startB, endB) {
            const sA = this.parseDate(startA);
            const eA = this.parseDate(endA);
            const sB = this.parseDate(startB);
            const eB = this.parseDate(endB);

            if (!sA || !eA || !sB || !eB) return false;
            return (sA < eB && eA > sB);
        },

        formatDate(dateString) {
            if (!dateString) return '-';
            const date = this.parseDate(dateString);
            if (!date) return '-';
            return date.toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },
        
        openReviewModal(draftId) {
            // Cari data draft berdasarkan ID
            const draft = (this.drafts || []).find(item => String(item.id) === String(draftId));
            if (!draft) return;

            this.activeDraft = draft;
            this.selectedVehicleId = draft.vehicle_id ? String(draft.vehicle_id) : '';
            this.selectedDriverId = draft.driver_id ? String(draft.driver_id) : '';
            this.adminNote = '';
            this.errorMessage = '';
            this.actionType = 'approve';

            const reqStart = draft.start_date;
            const reqEnd   = draft.end_date;

            // 1. FILTER ARMADA KENDARAAN READY
            this.availableVehicles = (this.vehicles || []).filter(v => {
                // Cek Bentrok di Peminjaman Aktif (Resmi)
                const isBooked = (this.activeBookings || []).some(b => {
                    if (b.draft_booking_id && String(b.draft_booking_id) === String(draft.id)) return false;
                    if (!b.vehicle_id || String(b.vehicle_id) !== String(v.id)) return false;
                    return this.isOverlapping(reqStart, reqEnd, b.start_date, b.end_date);
                });

                if (isBooked) return false;

                // Cek Bentrok di Draft Permohonan Lain
                const isDrafted = (this.drafts || []).some(d => {
                    if (String(d.id) === String(draft.id)) return false;
                    if (!d.vehicle_id || String(d.vehicle_id) !== String(v.id)) return false;
                    return this.isOverlapping(reqStart, reqEnd, d.start_date, d.end_date);
                });

                return !isDrafted;
            });

            // 2. FILTER DRIVER READY (Mencakup Booking Aktif, Draft Lain, dan Ketidakhadiran/Attendance)
            this.availableDrivers = (this.drivers || []).filter(d => {
                if (d.role !== 'driver') return false;

                // A. Cek Bentrok Driver di Peminjaman Aktif
                const isBooked = (this.activeBookings || []).some(b => {
                    if (b.draft_booking_id && String(b.draft_booking_id) === String(draft.id)) return false;
                    if (!b.driver_id || String(b.driver_id) !== String(d.id)) return false;
                    return this.isOverlapping(reqStart, reqEnd, b.start_date, b.end_date);
                });

                if (isBooked) return false;

                // B. Cek Bentrok Driver di Draft Lain
                const isDrafted = (this.drafts || []).some(dr => {
                    if (String(dr.id) === String(draft.id)) return false;
                    if (!dr.driver_id || String(dr.driver_id) !== String(d.id)) return false;
                    return this.isOverlapping(reqStart, reqEnd, dr.start_date, dr.end_date);
                });

                if (isDrafted) return false;

                // C. Cek Bentrok Driver di Ketidakhadiran (Cuti/Izin/Sakit/Service) yang Sudah Disetujui
                const isAbsent = (this.attendances || []).some(att => {
                    const driverIdInAttendance = att.driver_id || att.user_id; // Menyesuaikan nama kolom ID Driver
                    if (!driverIdInAttendance || String(driverIdInAttendance) !== String(d.id)) return false;
                    
                    // Cek apakah rentang tanggal permohonan bentrok dengan masa cuti/izin driver
                    return this.isOverlapping(reqStart, reqEnd, att.start_date, att.end_date);
                });

                return !isAbsent;
            });

            this.reviewModal = true;
        },

        submitReview(e) {
            if (this.actionType === 'reject' && !this.adminNote.trim()) {
                e.preventDefault();
                this.errorMessage = 'Alasan penolakan wajib diisi jika Anda menolak permohonan ini!';
                return;
            }
            if (this.actionType === 'approve' && !this.selectedVehicleId) {
                e.preventDefault();
                this.errorMessage = 'Silakan alokasikan unit kendaraan sebelum menyetujui permohonan!';
                return;
            }
            this.errorMessage = '';
        },

        openDeleteModal(url) {
            this.deleteActionUrl = url;
            this.deleteModal = true;
        }
    }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            {{-- 1. TAMPILAN KARTU (MOBILE) --}}
            <div class="block md:hidden space-y-3">
                @forelse($drafts as $d)
                    @php
                        $start = \Carbon\Carbon::parse($d->start_date)->locale('id');
                        $end = \Carbon\Carbon::parse($d->end_date)->locale('id');
                        $sameDay = $start->isSameDay($end);
                    @endphp
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-4 shadow-xs space-y-3">
                        <div class="flex items-start justify-between gap-2 border-b border-gray-100 pb-2.5">
                            <div>
                                <p class="font-bold text-gray-900 text-base leading-snug">{{ $d->user->name ?? '-' }}</p>
                                <p class="text-xs text-gray-400 font-medium mt-0.5">NIP: {{ $d->user->nip ?? '-' }}</p>
                            </div>
                            <div>
                                @switch($d->status)
                                    @case('approved')
                                        <span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold text-[10px] uppercase tracking-wider shrink-0">Disetujui</span>
                                        @break
                                    @case('rejected')
                                        <span class="px-2.5 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200 font-bold text-[10px] uppercase tracking-wider shrink-0">Ditolak</span>
                                        @break
                                    @case('converted')
                                        <span class="px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200 font-bold text-[10px] uppercase tracking-wider shrink-0">Peminjaman Resmi</span>
                                        @break
                                    @default
                                        <span class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200 font-bold text-[10px] uppercase tracking-wider shrink-0">Menunggu Review</span>
                                @endswitch
                            </div>
                        </div>

                        <div class="space-y-2.5 text-xs">
                            <div>
                                <p class="text-gray-400 font-bold uppercase text-[10px] tracking-wider mb-1">Jadwal & Penggerak Roda</p>
                                <div class="flex items-center gap-1.5 font-bold text-gray-800 text-xs">
                                    <svg class="w-4 h-4 text-gray-400 shrink-0 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                                    </svg>
                                    <span>{{ $start->translatedFormat('l, d M Y') }}</span>
                                </div>
                                <div class="flex items-center gap-2 mt-1.5">
                                    <span class="text-[11px] text-indigo-700 font-semibold bg-indigo-50 px-2.5 py-0.5 rounded-md border border-indigo-100">
                                        @if($sameDay)
                                            Jam {{ $start->format('H:i') }} s.d. {{ $end->format('H:i') }} WIB
                                        @else
                                            {{ $start->format('H:i') }} WIB s.d. {{ $end->translatedFormat('l, d M Y') }} ({{ $end->format('H:i') }} WIB)
                                        @endif
                                    </span>
                                    <span class="text-[10px] font-bold uppercase px-2 py-0.5 bg-gray-100 text-gray-700 rounded-md">
                                        {{ strtoupper($d->drivetrain ?? '2WD') }}
                                    </span>
                                </div>
                            </div>

                            <div class="pt-1 flex flex-col gap-2">
                                <div>
                                    <p class="text-gray-400 font-bold uppercase text-[10px] tracking-wider mb-1">Driver</p>
                                    @if($d->driver)
                                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-amber-50 border border-amber-200 text-amber-900 font-semibold text-xs rounded-lg">
                                            <svg class="w-3.5 h-3.5 text-amber-600 shrink-0 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                                            </svg>
                                            <span>{{ $d->driver->name }}</span>
                                        </div>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gray-50 border border-gray-200 text-gray-600 font-medium text-xs rounded-lg">
                                            <svg class="w-3.5 h-3.5 text-gray-400 shrink-0 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                                            </svg>
                                            <span>Lepas Kunci</span>
                                        </span>
                                    @endif
                                </div>

                                <div>
                                    <p class="text-gray-400 font-bold uppercase text-[10px] tracking-wider mb-0.5">Armada Kendaraan</p>
                                    @if($d->vehicle)
                                        <p class="font-bold text-indigo-950 text-xs">{{ $d->vehicle->brand }} {{ $d->vehicle->model }} ({{ $d->vehicle->plate_number }})</p>
                                    @else
                                        <span class="text-amber-700 font-semibold italic text-xs">Akan Ditentukan Admin Lapangan</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- TOMBOL AKSI MOBILE --}}
                        <div class="pt-3 border-t border-gray-100 flex items-center justify-between gap-2">
                            <div class="flex-1 flex items-center gap-2">
                                @if(in_array(Auth::user()->role, ['admin_app', 'admin_lap']) && $d->status === 'draft')
                                    <button type="button" 
                                            @click="openReviewModal({{ $d->id }})" 
                                            class="w-full py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200/80 font-semibold rounded-lg text-xs shadow-xs focus:outline-hidden focus:ring-2 focus:ring-indigo-500/30 inline-flex items-center justify-center gap-1.5 transition-all duration-150 ease-in-out">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-current text-indigo-600 shrink-0" viewBox="0 0 24 24">
                                            <path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                                        </svg>
                                        <span>Tinjau & Plot Armada</span>
                                    </button>
                                @endif

                                @if($d->status === 'approved' && ($d->user_id === Auth::id() || in_array(Auth::user()->role, ['admin_app', 'admin_lap'])))
                                    <a href="{{ route('bookings.create_from_draft', $d->id) }}" class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs shadow-md transition inline-flex items-center justify-center gap-1.5">
                                        <span>Lanjutkan ke Peminjaman Resmi</span>
                                        <svg class="w-4 h-4 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                                    </a>
                                @endif
                            </div>

                            {{-- DROPDOWN TITIK TIGA MOBILE --}}
                            @if($d->status === 'draft' && ($d->user_id === Auth::id() || in_array(Auth::user()->role, ['admin_app', 'admin_lap'])))
                                <div class="relative shrink-0" x-data="{ open: false }">
                                    <button @click="open = !open" @click.away="open = false" type="button" class="p-2 text-gray-500 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl focus:outline-hidden transition">
                                        <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                                        </svg>
                                    </button>
                                    <div x-show="open" x-cloak class="absolute right-0 bottom-full mb-1 w-40 bg-white rounded-xl shadow-xl border border-gray-100 py-1.5 z-30 text-left text-xs space-y-0.5">
                                        <a href="{{ route('draft-bookings.edit', $d->id) }}" class="flex items-center gap-2 px-3 py-2 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 font-semibold transition">
                                            <svg class="w-3.5 h-3.5 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                            <span>Edit Draft</span>
                                        </a>
                                        <button type="button" @click="openDeleteModal('{{ route('draft-bookings.destroy', $d->id) }}'); open = false;" class="w-full flex items-center gap-2 px-3 py-2 text-rose-600 hover:bg-rose-50 font-bold transition text-left">
                                            <svg class="w-3.5 h-3.5 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                            <span>Hapus Draft</span>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="bg-white rounded-2xl p-8 text-center text-gray-400 font-medium italic border border-gray-200">
                        Belum ada data permohonan armada saat ini.
                    </div>
                @endforelse
            </div>

            {{-- 2. TAMPILAN TABEL DESKTOP --}}
            <div class="hidden md:block bg-white rounded-2xl border border-gray-200/80 shadow-xs">
                <div class="overflow-x-auto pb-28 min-h-[320px]">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b bg-gray-50 text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <th class="py-3.5 px-3 text-center">Pemohon</th>
                                <th class="py-3.5 px-3 text-center">Jadwal Perjalanan</th>
                                <th class="py-3.5 px-3 text-center">Driver Ditugaskan</th>
                                <th class="py-3.5 px-3 text-center">Armada Kendaraan</th>
                                <th class="py-3.5 px-3 text-center">Status</th>
                                <th class="py-3.5 px-3 text-center">Aksi Operasional</th>
                                <th class="py-3.5 px-2 w-10 text-center"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 text-xs">
                            @forelse($drafts as $d)
                                @php
                                    $start = \Carbon\Carbon::parse($d->start_date)->locale('id');
                                    $end = \Carbon\Carbon::parse($d->end_date)->locale('id');
                                    $sameDay = $start->isSameDay($end);
                                @endphp
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    {{-- 1. PEMOHON --}}
                                    <td class="py-3 px-3 align-middle text-center">
                                        <div class="font-bold text-gray-900 text-sm leading-tight">{{ $d->user->name ?? '-' }}</div>
                                        <div class="text-[11px] text-gray-400 font-medium mt-0.5">NIP: {{ $d->user->nip ?? '-' }}</div>
                                    </td>

                                    {{-- 2. JADWAL PERJALANAN --}}
                                    <td class="py-3 px-3 align-middle text-center">
                                        <div class="flex items-center justify-center gap-1.5 font-bold text-gray-800 text-xs">
                                            <svg class="w-4 h-4 text-gray-400 shrink-0 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                                            </svg>
                                            <span>{{ $start->translatedFormat('l, d M Y') }}</span>
                                        </div>
                                        <div class="flex items-center justify-center gap-2 mt-1.5 flex-wrap">
                                            <span class="text-[11px] text-indigo-700 font-semibold bg-indigo-50/80 border border-indigo-100 inline-block px-2.5 py-0.5 rounded-md">
                                                @if($sameDay)
                                                    Jam {{ $start->format('H:i') }} s.d. {{ $end->format('H:i') }} WIB
                                                @else
                                                    {{ $start->format('H:i') }} WIB s.d. {{ $end->translatedFormat('l, d M Y') }} ({{ $end->format('H:i') }} WIB)
                                                @endif
                                            </span>
                                        </div>
                                    </td>

                                    {{-- 3. DRIVER DITUGASKAN --}}
                                    <td class="py-3 px-3 align-middle text-center">
                                        @if($d->driver)
                                            <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-amber-50 border border-amber-200 text-amber-900 rounded-xl">
                                                <svg class="w-4 h-4 text-amber-600 shrink-0 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 1114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                                                </svg>
                                                <div class="text-left">
                                                    <div class="font-bold text-xs leading-tight">{{ $d->driver->name }}</div>
                                                    <div class="text-[10px] text-amber-700 font-normal mt-0.5">{{ $d->driver->phone ?? 'No WA -' }}</div>
                                                </div>
                                            </div>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-gray-50 border border-gray-200 text-gray-600 font-medium text-xs rounded-lg">
                                                <svg class="w-3.5 h-3.5 text-gray-400 shrink-0 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                                                </svg>
                                                <span>Lepas Kunci</span>
                                            </span>
                                        @endif
                                    </td>

                                    {{-- 4. ARMADA KENDARAAN --}}
                                    <td class="py-3 px-3 align-middle text-center">
                                        @if($d->vehicle)
                                            <div class="font-bold text-gray-900 text-xs">{{ $d->vehicle->brand }} {{ $d->vehicle->model }}</div>
                                            <div class="text-[11px] text-gray-500 font-medium mt-0.5 flex items-center justify-center gap-1.5">
                                                <span>{{ $d->vehicle->plate_number }}</span>
                                                <span class="px-1.5 py-0.2 rounded bg-gray-100 text-gray-600 uppercase text-[9px] font-bold">{{ $d->vehicle->type }}</span>
                                            </div>
                                        @else
                                            <span class="text-amber-700 font-semibold italic text-xs">Akan Ditentukan Admin Lapangan</span>
                                        @endif
                                    </td>

                                    {{-- 5. STATUS --}}
                                    <td class="py-3 px-3 align-middle text-center">
                                        @switch($d->status)
                                            @case('approved')
                                                <span class="inline-block px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold text-[11px]">Disetujui</span>
                                                @break
                                            @case('rejected')
                                                <span class="inline-block px-3 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200 font-bold text-[11px]">Ditolak</span>
                                                @break
                                            @case('converted')
                                                <span class="inline-block px-3 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200 font-bold text-[11px]">Peminjaman Resmi</span>
                                                @break
                                            @default
                                                <span class="inline-block px-3 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200 font-bold text-[11px]">Menunggu Review</span>
                                        @endswitch
                                    </td>

                                    {{-- 6. AKSI OPERASIONAL --}}
                                    <td class="py-3 px-3 align-middle text-center">
                                        <div class="inline-flex items-center justify-center gap-1.5">
                                            @if(in_array(Auth::user()->role, ['admin_app', 'admin_lap']) && $d->status === 'draft')
                                                <button type="button" 
                                                        @click="openReviewModal({{ $d->id }})" 
                                                        class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200/80 font-semibold rounded-lg text-xs shadow-2xs focus:outline-hidden focus:ring-2 focus:ring-indigo-500/30 inline-flex items-center justify-center gap-1.5 transition-all duration-150 ease-in-out shrink-0">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-current text-indigo-600 shrink-0" viewBox="0 0 24 24">
                                                        <path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                                                    </svg>
                                                    <span>Tinjau & Plot Armada</span>
                                                </button>
                                            @endif

                                            @if($d->status === 'approved' && ($d->user_id === Auth::id() || in_array(Auth::user()->role, ['admin_app', 'admin_lap'])))
                                                <a href="{{ route('bookings.create_from_draft', $d->id) }}" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs shadow-2xs transition inline-flex items-center gap-1.5 shrink-0">
                                                    <span>Lanjutkan ke Peminjaman Resmi</span>
                                                    <svg class="w-3.5 h-3.5 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                                                </a>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- 7. MENU TITIK TIGA --}}
                                    <td class="py-3 px-2 w-10 text-center relative" x-data="{ open: false }">
                                        @if($d->status === 'draft' && ($d->user_id === Auth::id() || in_array(Auth::user()->role, ['admin_app', 'admin_lap'])))
                                            <button @click="open = !open" @click.away="open = false" type="button" class="p-1.5 text-gray-400 hover:text-gray-700 rounded-lg hover:bg-gray-100 transition focus:outline-hidden" aria-label="Menu Opsi Aksi">
                                                <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20">
                                                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                                                </svg>
                                            </button>

                                            <div x-show="open" x-cloak class="absolute right-0 top-12 w-40 bg-white rounded-xl shadow-xl border border-gray-100 py-1.5 z-50 text-left text-xs space-y-0.5">
                                                <a href="{{ route('draft-bookings.edit', $d->id) }}" class="flex items-center gap-2 px-3 py-2 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 font-semibold transition">
                                                    <svg class="w-3.5 h-3.5 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                                    <span>Edit Draft</span>
                                                </a>
                                                <button type="button" @click="openDeleteModal('{{ route('draft-bookings.destroy', $d->id) }}'); open = false;" class="w-full flex items-center gap-2 px-3 py-2 text-rose-600 hover:bg-rose-50 font-bold transition text-left">
                                                    <svg class="w-3.5 h-3.5 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                                    <span>Hapus Draft</span>
                                                </button>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="p-10 text-center text-gray-400 font-medium italic">Belum ada data permohonan armada saat ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-gray-100">
                    {{ $drafts->links() }}
                </div>
            </div>
        </div>
        
        {{-- MODAL KONFIRMASI HAPUS (DELETE MODAL) --}}
        <div x-show="deleteModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
            <div class="bg-white rounded-2xl max-w-sm w-full p-6 space-y-4 shadow-2xl overflow-hidden text-center" @click.away="deleteModal = false">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-rose-100 text-rose-600 mb-2">
                    <svg class="h-6 w-6 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>

                <h3 class="text-base font-bold text-gray-900">Hapus Draft Permohonan?</h3>
                <p class="text-xs text-gray-500">Tindakan ini tidak dapat dibatalkan. Data draft permohonan armada akan dihapus secara permanen dari sistem.</p>

                <div class="flex items-center justify-center gap-2 pt-2">
                    <button type="button" @click="deleteModal = false" class="w-full px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl text-xs transition">
                        Batal
                    </button>
                    
                    <form :action="deleteActionUrl" method="POST" class="w-full">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs shadow-md transition">
                            Ya, Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- MODAL REVIEW ADMIN LAPANGAN --}}
<div x-show="reviewModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl border border-gray-100 max-h-[90vh] flex flex-col" @click.away="reviewModal = false">
        
        {{-- HEADER MODAL --}}
        <div class="bg-indigo-600 px-6 py-4 text-white flex justify-between items-center shrink-0">
            <div>
                <h3 class="font-bold text-base flex items-center gap-2">
                    <svg class="w-5 h-5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                        <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                        <path d="m9 14 2 2 4-4"/>
                    </svg>
                    <span>Tinjau & Alokasikan Armada</span>
                </h3>
                <p class="text-xs text-indigo-100 font-mono mt-0.5" x-text="activeDraft ? activeDraft.letter_number || 'Draft Permohonan' : ''"></p>
            </div>
            <button type="button" @click="reviewModal = false" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">
                <svg class="w-5 h-5 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form :action="`/draft-bookings/${activeDraft?.id}/review`" 
              method="POST" 
              @submit="submitReview($event)"
              class="p-6 space-y-4 overflow-y-auto flex-1">
            @csrf

            {{-- RINCIAN DRAFT PERMOHONAN --}}
            <div class="p-3.5 bg-gray-50 rounded-xl space-y-2 text-xs border border-gray-200/80">
                <div class="flex justify-between">
                    <span class="text-gray-500 font-medium">Pemohon:</span>
                    <span class="font-bold text-gray-900" x-text="activeDraft?.user?.name || '-'"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 font-medium">Kebutuhan Kendaraan:</span>
                    <span class="font-bold text-indigo-600 uppercase" x-text="activeDraft?.drivetrain || '2WD'"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 font-medium">Waktu Mulai:</span>
                    <span class="font-bold text-gray-900" x-text="formatDate(activeDraft?.start_date)"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 font-medium">Waktu Selesai:</span>
                    <span class="font-bold text-gray-900" x-text="formatDate(activeDraft?.end_date)"></span>
                </div>
            </div>

            {{-- PESAN ERROR MODAL --}}
            <div x-show="errorMessage" x-cloak class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-700 font-bold flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-600 shrink-0 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span x-text="errorMessage"></span>
            </div>

            {{-- RADIO KEPUTUSAN (SETUJUI / TOLAK) --}}
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1.5">Keputusan Alokasi <span class="text-rose-500">*</span></label>
                <div class="grid grid-cols-2 gap-3">
                    {{-- OPSI SETUJUI --}}
                    <label class="cursor-pointer p-2.5 rounded-xl border text-center text-xs font-bold transition flex items-center justify-center gap-1.5"
                        :class="actionType === 'approve' ? 'bg-emerald-50 border-emerald-500 text-emerald-700 shadow-xs' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
                        <input type="radio" name="action" value="approve" x-model="actionType" class="hidden">
                        <svg class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                        <span>Setujui & Alokasi</span>
                    </label>

                    {{-- OPSI TOLAK --}}
                    <label class="cursor-pointer p-2.5 rounded-xl border text-center text-xs font-bold transition flex items-center justify-center gap-1.5"
                        :class="actionType === 'reject' ? 'bg-rose-50 border-rose-500 text-rose-700 shadow-xs' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
                        <input type="radio" name="action" value="reject" x-model="actionType" class="hidden">
                        <svg class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="15" y1="9" x2="9" y2="15"/>
                            <line x1="9" y1="9" x2="15" y2="15"/>
                        </svg>
                        <span>Tolak Permohonan</span>
                    </label>
                </div>
            </div>

            {{-- FORM KARD GRID PILIHAN KENDARAAN & DRIVER (HANYA TAMPIL JIKA DISETUJUI) --}}
            <div x-show="actionType === 'approve'" class="space-y-5 pt-1" x-cloak>
                
                {{-- 1. CARD GRID PILIHAN ARMADA KENDARAAN READY --}}
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-gray-700">
                        Pilih Unit Armada Kendaraan <span class="text-rose-500">*</span>
                    </label>

                    {{-- Hidden input untuk form & validasi required --}}
                    <input type="hidden" name="vehicle_id" x-model="selectedVehicleId" :required="actionType === 'approve'">

                    <div class="space-y-2">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <template x-for="v in availableVehicles" :key="v.id">
                                <div @click="selectedVehicleId = v.id"
                                    :class="{
                                        'border-indigo-600 bg-indigo-50/50 ring-2 ring-indigo-500/20 shadow-2xs': selectedVehicleId == v.id,
                                        'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50/50': selectedVehicleId != v.id
                                    }"
                                    class="relative p-3.5 rounded-xl border cursor-pointer transition flex items-start gap-3 group">
                                    
                                    {{-- CHECKMARK ICON TERPILIH --}}
                                    <div class="absolute top-3 right-3">
                                        <div :class="selectedVehicleId == v.id ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-gray-300 bg-white'"
                                            class="w-5 h-5 rounded-full border flex items-center justify-center transition">
                                            <svg x-show="selectedVehicleId == v.id" class="w-3 h-3 stroke-current stroke-3 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="20 6 9 17 4 12"/>
                                            </svg>
                                        </div>
                                    </div>

                                    {{-- THUMBNAIL FOTO / PLACEHOLDER --}}
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

                                    {{-- DETAIL INFORMASI ARMADA --}}
                                    <div class="space-y-1 pr-6 min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="text-[9px] font-extrabold uppercase px-1.5 py-0.5 rounded border"
                                                :class="v.type === 'monitoring' 
                                                    ? 'bg-amber-50 text-amber-800 border-amber-200' 
                                                    : 'bg-indigo-50 text-indigo-800 border-indigo-200'"
                                                x-text="v.type === 'monitoring' ? 'Monitoring' : 'Operational'">
                                            </span>
                                            <h4 class="font-bold text-xs text-gray-900 truncate" x-text="v.brand + ' ' + v.model"></h4>
                                        </div>

                                        <p class="font-mono text-xs font-bold text-indigo-600 uppercase" x-text="v.plate_number"></p>

                                        <div class="flex items-center gap-1.5 text-[10px] text-gray-500 pt-0.5 flex-wrap">
                                            <span x-text="(v.capacity || '4') + ' Kursi'"></span>
                                            <span>•</span>
                                            <span class="uppercase font-semibold text-gray-700 bg-gray-100 px-1 py-0.2 rounded border border-gray-200 text-[9px]" x-text="v.drivetrain ? v.drivetrain.toUpperCase() : '2WD'"></span>
                                        </div>
                                    </div>

                                </div>
                            </template>
                        </div>

                        {{-- ALERT BENTROK / ARMADA KOSONG --}}
                        <template x-if="availableVehicles.length === 0">
                            <p class="text-[11px] text-rose-600 font-bold p-3 bg-rose-50 border border-rose-200 rounded-xl flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0 text-rose-600" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                                    <line x1="12" y1="9" x2="12" y2="13"/>
                                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                                </svg>
                                <span>Seluruh armada telah terpakai / bentrok pada rentang jadwal ini.</span>
                            </p>
                        </template>
                    </div>
                </div>

                {{-- 2. CARD GRID PILIHAN DRIVER / PENGEMUDI --}}
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-gray-700">
                        Penunjukan Driver / Pengemudi <span class="text-gray-400 font-normal lowercase">(opsional)</span>
                    </label>

                    {{-- Hidden input driver_id --}}
                    <input type="hidden" name="driver_id" x-model="selectedDriverId">

                    <div class="space-y-3">
                        {{-- DRIVER UTAMA / RESMI --}}
                        <template x-if="availableDrivers.length > 0">
                            <div class="space-y-2">
                                <span class="text-[11px] font-extrabold text-indigo-900 uppercase tracking-wider block">Driver Utama / Resmi:</span>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <template x-for="d in availableDrivers" :key="d.id">
                                        <div @click="selectedDriverId = (selectedDriverId == d.id ? '' : d.id)"
                                            :class="{
                                                'border-indigo-600 bg-indigo-50/50 ring-2 ring-indigo-500/20 shadow-2xs': selectedDriverId == d.id,
                                                'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50/50': selectedDriverId != d.id
                                            }"
                                            class="relative p-3 rounded-xl border cursor-pointer transition flex items-center gap-3 group">
                                            
                                            <div class="absolute top-3 right-3">
                                                <div :class="selectedDriverId == d.id ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-gray-300 bg-white'"
                                                    class="w-5 h-5 rounded-full border flex items-center justify-center transition">
                                                    <svg x-show="selectedDriverId == d.id" class="w-3 h-3 stroke-current stroke-3 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="20 6 9 17 4 12"/>
                                                    </svg>
                                                </div>
                                            </div>

                                            <div class="w-10 h-10 rounded-lg bg-indigo-100 border border-indigo-200 text-indigo-700 font-extrabold text-sm flex items-center justify-center shrink-0"
                                                x-text="d.name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase()">
                                            </div>

                                            <div class="space-y-0.5 pr-6 min-w-0">
                                                <h4 class="font-bold text-xs text-gray-900 truncate" x-text="d.name"></h4>
                                                <div class="flex items-center gap-1 text-[11px] font-semibold text-emerald-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                                    </svg>
                                                    <span x-text="d.phone || 'Tidak Ada Kontak'"></span>
                                                </div>
                                            </div>

                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        {{-- ANGGOTA TIM PERMOHONAN INI --}}
                        <template x-if="activeDraft && activeDraft.participants_users && activeDraft.participants_users.length > 0">
                            <div class="space-y-2 pt-1">
                                <span class="text-[11px] font-extrabold text-amber-900 uppercase tracking-wider block">Anggota Tim Permohonan Ini (Pengemudi Cadangan):</span>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <template x-for="p in activeDraft.participants_users" :key="p.id">
                                        <div @click="selectedDriverId = (selectedDriverId == p.id ? '' : p.id)"
                                            :class="{
                                                'border-amber-500 bg-amber-50/60 ring-2 ring-amber-500/20 shadow-2xs': selectedDriverId == p.id,
                                                'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50/50': selectedDriverId != p.id
                                            }"
                                            class="relative p-3 rounded-xl border cursor-pointer transition flex items-center gap-3 group">
                                            
                                            <div class="absolute top-3 right-3">
                                                <div :class="selectedDriverId == p.id ? 'bg-amber-600 border-amber-600 text-white' : 'border-gray-300 bg-white'"
                                                    class="w-5 h-5 rounded-full border flex items-center justify-center transition">
                                                    <svg x-show="selectedDriverId == p.id" class="w-3 h-3 stroke-current stroke-3 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="20 6 9 17 4 12"/>
                                                    </svg>
                                                </div>
                                            </div>

                                            <div class="w-10 h-10 rounded-lg bg-amber-100 border border-amber-200 text-amber-800 font-extrabold text-sm flex items-center justify-center shrink-0"
                                                x-text="p.name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase()">
                                            </div>

                                            <div class="space-y-0.5 pr-6 min-w-0">
                                                <h4 class="font-bold text-xs text-gray-900 truncate" x-text="p.name"></h4>
                                                <span class="inline-block text-[10px] font-bold text-amber-800 bg-amber-100/80 px-1.5 py-0.2 rounded border border-amber-200/80">Anggota Tim</span>
                                            </div>

                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        {{-- ALERT DRIVER KOSONG --}}
                        <template x-if="availableDrivers.length === 0">
                            <p class="text-[11px] text-amber-800 font-medium p-3 bg-amber-50 border border-amber-200 rounded-xl flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0 text-amber-600" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="12" y1="16" x2="12" y2="12"/>
                                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                                </svg>
                                <span>Seluruh driver resmi sedang bertugas. Anda dapat menunjuk Anggota Tim di atas sebagai pengemudi.</span>
                            </p>
                        </template>
                    </div>
                </div>

            </div>

            {{-- CATATAN ADMIN / ALASAN PENOLAKAN --}}
            <div>
                <label class="block text-xs font-bold text-gray-800 uppercase mb-1">
                    <span x-text="actionType === 'reject' ? 'Alasan Penolakan' : 'Catatan Admin Lapangan'"></span>
                    <span x-show="actionType === 'reject'" class="text-rose-500">*</span>
                    <span x-show="actionType === 'approve'" class="text-gray-400 font-normal lowercase">(opsional)</span>
                </label>
                <textarea name="admin_note" 
                          x-model="adminNote" 
                          rows="2" 
                          :required="actionType === 'reject'"
                          class="w-full rounded-xl border-gray-300 text-xs focus:ring-indigo-500 focus:border-indigo-500" 
                          :placeholder="actionType === 'reject' ? 'Tuliskan alasan penolakan permohonan secara jelas...' : 'Tuliskan catatan tambahan jika ada...'"></textarea>
            </div>

            {{-- TOMBOL SUBMIT DYNAMIS --}}
            <div class="flex items-center justify-end gap-2 pt-3 border-t">
                <button type="button" @click="reviewModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl text-xs transition">
                    Batal
                </button>

                <button type="submit" 
                        class="px-4 py-2 text-white font-bold rounded-xl text-xs shadow-md transition flex items-center gap-1.5"
                        :class="actionType === 'approve' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700'">
                    <template x-if="actionType === 'approve'">
                        <svg class="w-4 h-4 stroke-current stroke-2 fill-none" viewBox="0 0 24 24"><path d="m5 12 5 5L20 7"/></svg>
                    </template>
                    <template x-if="actionType === 'reject'">
                        <svg class="w-4 h-4 stroke-current stroke-2 fill-none" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </template>
                    <span x-text="actionType === 'approve' ? 'Setujui & Alokasikan' : 'Konfirmasi Penolakan'"></span>
                </button>
            </div>
        </form>
    </div>
</div>
    </div>
</x-app-layout>