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
            bookings: {{ json_encode($bookings->items()) }}, 
            showApproveModal: false,
            showRejectModal: false,
            showCancelModal: false,
            selectedBooking: null,
            showCheckOut: false,
            showEditCheckOut: false,
            showCheckIn: false,
            showEditCheckIn: false,
            showConfirmDepartureModal: false,
            showConfirmReturnModal: false,
            activeBooking: null,
            isLoading: false,
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
        x-init="$watch('search', value => fetchBookings()); $watch('sort', value => fetchBookings())">
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
                                <th class="p-3 w-10 text-center"></th> {{-- Kolom Titik 3 Tanpa Header --}}
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
                                    <td class="p-3 font-medium" x-text="booking.vehicle ? `${booking.vehicle.brand} ${booking.vehicle.model} (${booking.vehicle.plate_number})` : '-'"></td>
                                    
                                    {{-- JADWAL --}}
                                    <td class="p-3 text-xs whitespace-nowrap">
                                        <div><strong>Mulai:</strong> <span x-text="formatDate(booking.start_date)"></span></div>
                                        <div><strong>Selesai:</strong> <span x-text="formatDate(booking.end_date)"></span></div>
                                    </td>
                                    
                                    {{-- STATUS BADGE --}}
                                    <td class="p-3">
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-full"
                                            :class="{
                                                'bg-yellow-100 text-yellow-800': booking.status === 'pending',
                                                'bg-blue-100 text-blue-800': booking.status === 'approved',
                                                'bg-purple-100 text-purple-800': booking.status === 'on_trip',
                                                'bg-amber-100 text-amber-800': booking.status === 'unconfirmed',
                                                'bg-green-100 text-green-800': booking.status === 'completed',
                                                'bg-gray-200 text-gray-700': booking.status === 'canceled',
                                                'bg-red-100 text-red-800': booking.status === 'rejected'
                                            }"
                                            x-text="booking.status.toUpperCase()">
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

                                            {{-- STATUS PENDING --}}
                                            <template x-if="booking.status === 'pending'">
                                                <div class="inline-flex items-center gap-1.5">
                                                    @if(auth()->user()->isAdmin())
                                                        <button type="button" @click="selectedBooking = booking; showApproveModal = true" class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded shadow-sm transition">
                                                            ✓ Approve
                                                        </button>
                                                        <button type="button" @click="selectedBooking = booking; showRejectModal = true" class="px-2.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded shadow-sm transition">
                                                            ✕ Reject
                                                        </button>
                                                    @else
                                                        <span class="text-xs text-amber-600 font-medium italic">⏳ Menunggu Persetujuan</span>
                                                    @endif
                                                </div>
                                            </template>

                                            {{-- STATUS APPROVED --}}
                                            <template x-if="booking.status === 'approved'">
                                                <div>
                                                    <template x-if="{{ auth()->user()->isAdmin() ? 'true' : 'false' }} || booking.user_id === {{ auth()->id() }}">
                                                        <button @click="showCheckOut = true; activeBooking = booking" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded shadow-sm transition">
                                                            Berangkat
                                                        </button>
                                                    </template>
                                                </div>
                                            </template>

                                            {{-- 3. STATUS ON TRIP --}}
                                            <template x-if="booking.status === 'on_trip'">
                                                <div>
                                                    @if(auth()->user()->isAdmin())
                                                        {{-- TAMPILAN UNTUK ADMIN --}}
                                                        <div class="flex flex-col gap-1.5 w-full">
                                                            {{-- 1. Tombol Konfirmasi Keberangkatan (HANYA muncul jika departed_confirmed_by MASIH KOSONG) --}}
                                                            <template x-if="!booking.departed_confirmed_by">
                                                                <button @click="selectedBooking = booking; showConfirmDepartureModal = true" 
                                                                        class="w-full text-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded shadow-sm transition">
                                                                    ✓ Konfirmasi Keberangkatan
                                                                </button>
                                                            </template>

                                                            {{-- Indikator Jika Keberangkatan Sudah Dikonfirmasi --}}
                                                            <template x-if="booking.departed_confirmed_by">
                                                                <span class="w-full text-center px-2 py-1 bg-blue-50 text-blue-700 border border-blue-200 text-xs font-medium rounded-md" title="Keberangkatan telah dikonfirmasi">
                                                                    ✓ Keberangkatan Dikonfirmasi
                                                                </span>
                                                            </template>

                                                            {{-- 2. Tombol Selesai (Check-In Pengembalian) --}}
                                                            <button @click="showCheckIn = true; activeBooking = booking" 
                                                                    class="w-full text-center px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold rounded shadow-sm transition">
                                                                Selesai
                                                            </button>
                                                        </div>
                                                    @else
                                                        {{-- TAMPILAN UNTUK PEGAWAI (PEMILIK BOOKING) --}}
                                                        <template x-if="Number(booking.user_id) === Number({{ auth()->id() }})">
                                                            <button @click="showCheckIn = true; activeBooking = booking" 
                                                                    class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold rounded shadow-sm transition">
                                                                Selesai
                                                            </button>
                                                        </template>
                                                    @endif
                                                </div>
                                            </template>

                                            {{-- STATUS UNCONFIRMED --}}
                                            <template x-if="booking.status === 'unconfirmed'">
                                                <div>
                                                    @if(auth()->user()->isAdmin())
                                                        <div class="flex flex-col gap-1.5 w-full">
                                                            {{-- 1. KONFIRMASI KEBERANGKATAN --}}
                                                            <template x-if="!booking.departed_confirmed_by">
                                                                <button @click="selectedBooking = booking; showConfirmDepartureModal = true" 
                                                                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded shadow-sm transition">
                                                                    ✓ Konfirmasi Keberangkatan
                                                                </button>
                                                            </template>
                                                            <template x-if="booking.departed_confirmed_by">
                                                                <span class="inline-flex items-center px-2 py-1 bg-blue-50 text-blue-700 border border-blue-200 text-xs font-medium rounded-md" title="Keberangkatan telah dikonfirmasi">
                                                                    ✓ Keberangkatan Dikonfirmasi
                                                                </span>
                                                            </template>

                                                            {{-- 2. KONFIRMASI PENGEMBALIAN --}}
                                                            <template x-if="!booking.returned_confirmed_by">
                                                                <button @click="selectedBooking = booking; showConfirmReturnModal = true" 
                                                                        class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded shadow-sm transition">
                                                                    ✓ Konfirmasi Pengembalian
                                                                </button>
                                                            </template>
                                                            <template x-if="booking.returned_confirmed_by">
                                                                <span class="inline-flex items-center px-2 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-medium rounded-md" title="Pengembalian telah dikonfirmasi">
                                                                    ✓ Pengembalian Dikonfirmasi
                                                                </span>
                                                            </template>
                                                        </div>
                                                    @else
                                                        {{-- Tampilan untuk Pegawai Non-Admin --}}
                                                        <span class="inline-flex items-center px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200 text-xs font-medium rounded-full">
                                                            ⏳ Menunggu Verifikasi Admin
                                                        </span>
                                                    @endif
                                                </div>
                                            </template>

                                            {{-- STATUS COMPLETED / REJECTED / CANCELED --}}
                                            <template x-if="['completed', 'canceled', 'rejected'].includes(booking.status)">
                                                <span class="text-xs text-gray-400 italic">Selesai</span>
                                            </template>

                                        </div>
                                    </td>

                                    {{-- 2. KOLOM TITIK 3 (AKSI SEKUNDER & MANAJEMEN) --}}
                                    <td class="p-3 text-center whitespace-nowrap relative">
                                        <div x-data="{ open: false }" 
                                            @click.outside="open = false" 
                                            class="inline-block text-left">
                                            
                                            {{-- Tombol Titik 3 --}}
                                            <button @click="open = !open" 
                                                    type="button"
                                                    class="p-1.5 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition focus:outline-none">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4z"/>
                                                </svg>
                                            </button>

                                            {{-- DROPDOWN MENU --}}
                                            <div x-show="open" 
                                                @click.away="open = false"
                                                x-transition:enter="transition ease-out duration-100"
                                                x-transition:enter-start="transform opacity-0 scale-95"
                                                x-transition:enter-end="transform opacity-100 scale-100"
                                                class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-100" 
                                                style="display: none;">
                                                
                                                {{-- 1. STATUS PENDING --}}
                                                <template x-if="booking.status === 'pending'">
                                                    <div>
                                                        <a :href="`/bookings/${booking.letter_slug}/edit`" class="z-100 block px-4 py-2 text-xs text-gray-700 hover:bg-gray-100">
                                                            ✏️ Edit Permohonan
                                                        </a>
                                                        <button type="button" @click="selectedBooking = booking; showCancelModal = true; open = false" class="w-full text-left px-4 py-2 text-xs text-red-600 hover:bg-gray-100">
                                                            🚫 Batalkan Pengajuan
                                                        </button>
                                                    </div>
                                                </template>

                                                {{-- 2. STATUS APPROVED --}}
                                                <template x-if="booking.status === 'approved'">
                                                    <div>
                                                        @if(auth()->user()->isAdmin())
                                                            <a :href="`/bookings/${booking.letter_slug}/edit`" class="block px-4 py-2 text-xs text-gray-700 hover:bg-gray-100">✏️️ Edit Permohonan</a>
                                                        @endif
                                                        <template x-if="{{ auth()->user()->isAdmin() ? 'true' : 'false' }} || booking.user_id === {{ auth()->id() }}">
                                                            <button type="button" @click="selectedBooking = booking; showCancelModal = true; open = false" class="w-full text-left px-4 py-2 text-xs text-red-600 hover:bg-gray-100">🚫 Batal Peminjaman</button>
                                                        </template>
                                                    </div>
                                                </template>

                                                {{-- 3. STATUS ON TRIP --}}
                                                <template x-if="booking.status === 'on_trip'">
                                                    <div>
                                                        @if(auth()->user()->isAdmin())
                                                            {{-- AKSES ADMIN --}}
                                                            <div>
                                                                {{-- 1. Edit Data Keberangkatan --}}
                                                                <button @click="
                                                                            activeBooking = { 
                                                                                ...booking, 
                                                                                start_km: booking.start_km ?? booking.log?.start_km ?? booking.vehicle?.last_log_end_km ?? 0 
                                                                            }; 
                                                                            showEditCheckOut = true; 
                                                                            open = false;
                                                                        " 
                                                                        class="w-full text-left px-4 py-2 text-xs text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                                    <span>✏️</span> Edit Data Keberangkatan
                                                                </button>

                                                                {{-- 2. Cetak Nota Keberangkatan (Hanya Tampil Jika departed_confirmed_by Sudah Terisi) --}}
                                                                <template x-if="booking.departed_confirmed_by">
                                                                    <a :href="`/bookings/${booking.letter_slug}/print-departure`" 
                                                                    target="_blank" 
                                                                    class="block px-4 py-2 text-xs text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                                        <span>📄</span> Cetak Nota Keberangkatan
                                                                    </a>
                                                                </template>
                                                            </div>
                                                        @else
                                                            {{-- AKSES PEGAWAI (PEMILIK BOOKING) --}}
                                                            <template x-if="Number(booking.user_id) === Number({{ auth()->id() }})">
                                                                <div>
                                                                    {{-- 1. Edit Data Keberangkatan --}}
                                                                    <button @click="
                                                                                activeBooking = { 
                                                                                    ...booking, 
                                                                                    start_km: booking.start_km ?? booking.log?.start_km ?? booking.vehicle?.last_log_end_km ?? 0 
                                                                                }; 
                                                                                showEditCheckOut = true; 
                                                                                open = false;
                                                                            " 
                                                                            class="w-full text-left px-4 py-2 text-xs text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                                        <span>✏️</span> Edit Data Keberangkatan
                                                                    </button>

                                                                    {{-- 2. Cetak Nota Keberangkatan (Hanya Tampil Jika departed_confirmed_by Sudah Terisi) --}}
                                                                    <template x-if="booking.departed_confirmed_by">
                                                                        <a :href="`/bookings/${booking.letter_slug}/print-departure`" 
                                                                        target="_blank" 
                                                                        class="block px-4 py-2 text-xs text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                                            <span>📄</span> Cetak Nota Keberangkatan
                                                                        </a>
                                                                    </template>
                                                                </div>
                                                            </template>
                                                        @endif
                                                    </div>
                                                </template>

                                                {{-- 4. STATUS UNCONFIRMED --}}
                                                <template x-if="booking.status === 'unconfirmed'">
                                                    <div>
                                                        {{-- Pembungkus Akses: Hanya Admin ATAU Pemilik Booking --}}
                                                        @if(auth()->user()->isAdmin())
                                                            {{-- A. EDIT DATA PENGEMBALIAN --}}
                                                            <button @click="activeBooking = { 
                                                                        ...booking, 
                                                                        end_km: booking.end_km ?? booking.log?.end_km ?? booking.vehicle?.last_km ?? 0 
                                                                    };
                                                                    showEditCheckIn = true; 
                                                                    open = false" 
                                                                    class="w-full text-left px-4 py-2 text-xs text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                                <span>✏️</span> Edit Data Pengembalian
                                                            </button>

                                                            {{-- B. CETAK NOTA KEBERANGKATAN (Tampil HANYA jika departed_confirmed_by sudah terisi) --}}
                                                            <template x-if="booking.departed_confirmed_by">
                                                                <a :href="`/bookings/${booking.letter_slug}/print-departure`" 
                                                                target="_blank" 
                                                                class="block px-4 py-2 text-xs text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                                    <span>📄</span> Cetak Nota Keberangkatan
                                                                </a>
                                                            </template>

                                                            {{-- C. CETAK NOTA PENGEMBALIAN (Tampil HANYA jika returned_confirmed_by sudah terisi) --}}
                                                            <template x-if="booking.returned_confirmed_by">
                                                                <a :href="`/bookings/${booking.letter_slug}/print-return`" 
                                                                target="_blank" 
                                                                class="block px-4 py-2 text-xs text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                                    <span>🖨️</span> Cetak Nota Pengembalian
                                                                </a>
                                                            </template>
                                                        @else
                                                            {{-- Untuk Pegawai Pengaju --}}
                                                            <template x-if="Number(booking.user_id) === Number({{ auth()->id() }})">
                                                                <div>
                                                                    {{-- A. EDIT DATA PENGEMBALIAN --}}
                                                                    <button @click="activeBooking = { 
                                                                                ...booking, 
                                                                                end_km: booking.end_km ?? booking.log?.end_km ?? booking.vehicle?.last_km ?? 0 
                                                                            };
                                                                            showEditCheckIn = true; 
                                                                            open = false" 
                                                                            class="w-full text-left px-4 py-2 text-xs text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                                        <span>✏️</span> Edit Data Pengembalian
                                                                    </button>

                                                                    {{-- B. CETAK NOTA KEBERANGKATAN --}}
                                                                    <template x-if="booking.departed_confirmed_by">
                                                                        <a :href="`/bookings/${booking.letter_slug}/print-departure`" 
                                                                        target="_blank" 
                                                                        class="block px-4 py-2 text-xs text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                                            <span>📄</span> Cetak Nota Keberangkatan
                                                                        </a>
                                                                    </template>

                                                                    {{-- C. CETAK NOTA PENGEMBALIAN --}}
                                                                    <template x-if="booking.returned_confirmed_by">
                                                                        <a :href="`/bookings/${booking.letter_slug}/print-return`" 
                                                                        target="_blank" 
                                                                        class="block px-4 py-2 text-xs text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                                            <span>🖨️</span> Cetak Nota Pengembalian
                                                                        </a>
                                                                    </template>
                                                                </div>
                                                            </template>
                                                        @endif
                                                    </div>
                                                </template>

                                                {{-- 5. STATUS COMPLETED --}}
                                                <template x-if="booking.status === 'completed'">
                                                    <div>
                                                        {{-- @if(auth()->user()->isAdmin())
                                                            <button @click="
                                                                activeBooking = { 
                                                                    ...booking, 
                                                                    start_km: booking.start_km ?? booking.log?.start_km ?? booking.vehicle?.last_log_end_km ?? 0 
                                                                }; 
                                                                showEditCheckOut = true; 
                                                                open = false;
                                                            " class="w-full text-left px-4 py-2 text-xs text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                                <span>✏️</span> Edit Data Keberangkatan
                                                            </button>
                                                            <button @click="activeBooking = booking; showEditCheckIn = true; open = false" 
                                                                    class="w-full text-left px-4 py-2 text-xs text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                                <span>✏️</span> Edit Data Pengembalian
                                                            </button>
                                                        @endif --}}
                                                        <a :href="`/bookings/${booking.letter_slug}/print-departure`" target="_blank" class="block px-4 py-2 text-xs text-gray-700 hover:bg-gray-100">📄 Cetak Nota Keberangkatan</a>
                                                        <a :href="`/bookings/${booking.letter_slug}/print-return`" target="_blank" class="block px-4 py-2 text-xs text-gray-700 hover:bg-gray-100">🖨️ Cetak Nota Pengembalian</a>
                                                    </div>
                                                </template>

                                                {{-- 6. STATUS REJECTED / CANCELED --}}
                                                <template x-if="['canceled', 'rejected'].includes(booking.status)">
                                                    <span class="block px-4 py-2 text-xs text-gray-400 italic">Tidak Ada Tindakan</span>
                                                </template>

                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4" x-show="search === ''">
                    {{ $bookings->links() }}
                </div>

            </div>
        </div>

        <!-- MODAL KONFIRMASI APPROVE BOOKING -->
        <div x-show="showApproveModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50" x-cloak>
            <div class="bg-white rounded-lg shadow relative max-w-md w-full">
                <!-- Close Button -->
                <div class="flex justify-end p-2">
                    <button type="button" @click="showApproveModal = false" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                    </button>
                </div>

                <div class="p-6 pt-0 text-center">
                    {{-- IKON CENTANG EMERALD --}}
                    <svg class="w-16 h-16 text-emerald-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    
                    <h3 class="text-lg font-bold text-gray-800 mb-1">
                        Setujui Peminjaman Kendaraan
                    </h3>
                    <p class="font-bold font-mono text-indigo-600 text-sm mb-4" x-text="selectedBooking ? selectedBooking.letter_number : ''"></p>

                    <div class="mb-4 p-3 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-900 rounded-r-md shadow-sm">
                        <div class="flex items-start gap-2.5">
                            <svg class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="text-xs leading-relaxed">
                                <p class="font-bold text-emerald-900 mb-0.5">Persetujuan Permohonan Peminjaman</p>
                                <p class="text-emerald-800">
                                    Dengan menyetujui pengajuan ini, Anda secara resmi menyetujui alokasi kendaraan untuk jadwal dan tujuan yang telah diajukan. Status permohonan akan diperbarui menjadi <strong>Approved (Disetujui)</strong>.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <form :action="'/bookings/' + (selectedBooking ? selectedBooking.letter_slug : '') + '/approve'" method="POST" class="inline">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="approved">
                        
                        <button type="submit" class="text-white bg-emerald-600 hover:bg-emerald-700 focus:ring-4 focus:ring-emerald-300 font-medium rounded-lg text-sm inline-flex items-center px-4 py-2.5 text-center mr-2 shadow-sm transition">
                            ✓ Ya, Setujui & Tandatangani
                        </button>
                    </form>
                    
                    <button type="button" @click="showApproveModal = false" class="text-gray-700 bg-white hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 border border-gray-300 font-medium inline-flex items-center rounded-lg text-sm px-4 py-2.5 text-center transition">
                        Batal
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL KONFIRMASI REJECT BOOKING -->
        <div x-show="showRejectModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50" x-cloak>
            <div class="bg-white rounded-lg shadow relative max-w-md w-full">
                <!-- Close Button -->
                <div class="flex justify-end p-2">
                    <button type="button" @click="showRejectModal = false" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                    </button>
                </div>

                <div class="p-6 pt-0 text-center">
                    <svg class="w-16 h-16 text-rose-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>

                    <h3 class="text-lg font-bold text-gray-800 mb-2">
                        Tolak Pengajuan Peminjaman
                    </h3>
                    <p class="text-sm text-gray-500 mb-4">
                        Surat Tugas: <strong class="text-gray-700" x-text="selectedBooking ? selectedBooking.letter_number : ''"></strong>
                    </p>
                    
                    <form :action="'/bookings/' + (selectedBooking ? selectedBooking.letter_slug : '') + '/approve'" method="POST">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="rejected">

                        <!-- Input Textarea Alasan Penolakan -->
                        <div class="text-left mb-5">
                            <label for="admin_note" class="block text-xs font-semibold text-gray-700 uppercase mb-1">
                                Alasan Penolakan <span class="text-rose-500">*</span>
                            </label>
                            <textarea 
                                name="admin_note" 
                                id="admin_note" 
                                rows="3" 
                                required
                                placeholder="Contoh: Jadwal bertabrakan dengan kegiatan prioritas, armada harus masuk bengkel, dsb."
                                class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 placeholder-gray-400"></textarea>
                        </div>

                        <div class="flex justify-end gap-2">
                            <button type="button" @click="showRejectModal = false" class="text-gray-700 bg-white hover:bg-gray-100 border border-gray-300 font-medium rounded-lg text-sm px-4 py-2 text-center">
                                Batal
                            </button>
                            <button type="submit" class="text-white bg-rose-600 hover:bg-rose-700 font-medium rounded-lg text-sm px-4 py-2 text-center shadow-sm">
                                Kirim & Tolak
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL KONFIRMASI BATAL BOOKING -->
        <div x-show="showCancelModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50" x-cloak>
            <div class="bg-white rounded-lg shadow relative max-w-sm w-full">
                <div class="flex justify-end p-2">
                    <button type="button" @click="showCancelModal = false" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                    </button>
                </div>

                <div class="p-6 pt-0 text-center">
                    <svg class="w-20 h-20 text-amber-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="text-lg font-normal text-gray-600 mt-5 mb-6">
                        Apakah Anda yakin ingin membatalkan pengajuan <strong x-text="selectedBooking ? selectedBooking.letter_number : ''"></strong>?
                    </h3>
                    
                    <form :action="'/bookings/' + (selectedBooking ? selectedBooking.letter_slug : '') + '/cancel'" method="POST" class="inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="text-white bg-amber-600 hover:bg-amber-700 focus:ring-4 focus:ring-amber-300 font-medium rounded-lg text-sm inline-flex items-center px-4 py-2.5 text-center mr-2">
                            Ya, Batalkan
                        </button>
                    </form>
                    
                    <button type="button" @click="showCancelModal = false" class="text-gray-900 bg-white hover:bg-gray-100 border border-gray-200 font-medium inline-flex items-center rounded-lg text-sm px-4 py-2.5 text-center">
                        Tidak
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL CHECK-OUT -->
        <div x-show="showCheckOut" 
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 overflow-y-auto" 
            x-cloak>
            <div class="bg-white rounded-lg shadow relative max-w-md w-full p-6 max-h-[90vh] overflow-y-auto my-auto">
                <div class="flex justify-between items-center mb-4 pb-2 border-b">
                    <h3 class="text-lg font-bold text-gray-900">
                        🚀 Konfirmasi Keberangkatan
                    </h3>
                    <button type="button" @click="showCheckOut = false" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 inline-flex items-center">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                    </button>
                </div>

                <form :action="'/bookings/' + (activeBooking ? activeBooking.letter_slug : '') + '/check-out'" 
                    method="POST" 
                    enctype="multipart/form-data">
                    @csrf

                    <!-- INPUT KILOMETER (KM START) -->
                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Odometer / KM Awal <span class="text-rose-500">*</span>
                        </label>

                        <input type="number" 
                            name="start_km" 
                            required 
                            :min="activeBooking && activeBooking.vehicle && activeBooking.vehicle.last_km ? activeBooking.vehicle.last_km : 0" 
                            :value="activeBooking && activeBooking.vehicle && activeBooking.vehicle.last_km ? activeBooking.vehicle.last_km : ''"
                            class="w-full text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="Masukkan angka KM saat ini">

                        <!-- PETUNJUK TEKS KM TERAKHIR -->
                        <p class="text-xs text-indigo-600 mt-1 font-medium" x-show="activeBooking">
                            💡 KM Terakhir Mobil: 
                            <span class="font-bold" x-text="(activeBooking && activeBooking.vehicle && activeBooking.vehicle.last_km ? activeBooking.vehicle.last_km : 0) + ' KM'"></span>
                        </p>
                    </div>

                    <!-- INPUT BBM -->
                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Sisa Bensin / BBM <span class="text-rose-500">*</span>
                        </label>
                        <select name="start_fuel_level" required class="w-full text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">-- Pilih Level BBM --</option>
                            <option value="Full">Full (Penuh)</option>
                            <option value="3/4">3/4 Tank</option>
                            <option value="1/2">1/2 Tank</option>
                            <option value="1/4">1/4 Tank</option>
                            <option value="E">E (Hampir Habis)</option>
                        </select>
                    </div>

                    <!-- FOTO ODOMETER -->
                    <div class="mb-5">
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Foto Odometer & BBM <span class="text-rose-500">*</span>
                        </label>
                        <input type="file" name="start_photo" accept="image/*" required class="w-full text-xs text-gray-500 border border-gray-300 rounded-lg p-1.5">
                    </div>

                    {{-- ➕ KOTAK IMBAUAN TANDA TANGAN DIGITAL & NOTA KEBERANGKATAN --}}
                    <div class="bg-blue-50 border border-blue-200 text-blue-900 text-xs p-3.5 rounded-lg mb-5 space-y-1">
                        <div class="font-semibold flex items-center gap-1.5 text-blue-800">
                            <svg class="w-4 h-4 text-blue-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Penerbitan Nota Keberangkatan</span>
                        </div>
                        <p class="leading-relaxed text-blue-700">
                            Dengan mengonfirmasi keberangkatan ini, status permohonan akan menjadi <strong>ON TRIP</strong> dan sistem secara otomatis menerbitkan <strong>Nota Keberangkatan Resmi</strong> yang telah dilengkapi dengan <strong>Tanda Tangan Digital (QR Code)</strong>.
                        </p>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showCheckOut = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-semibold rounded-lg transition">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg transition shadow-sm">
                            ✓ Konfirmasi Keberangkatan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL EDIT CHECK-OUT -->
        <div x-show="showEditCheckOut" 
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 overflow-y-auto" 
            x-cloak>
            <div class="bg-white rounded-lg shadow relative max-w-md w-full p-6 max-h-[90vh] overflow-y-auto my-auto">
                <div class="flex justify-between items-center mb-4 pb-2 border-b">
                    <h3 class="text-lg font-bold text-gray-900">
                        ✏️ Koreksi Data Keberangkatan
                    </h3>
                    <button type="button" @click="showEditCheckOut = false" class="text-gray-400 hover:text-gray-900 rounded-lg text-sm p-1.5">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                    </button>
                </div>

                <form :action="'/bookings/' + (activeBooking ? activeBooking.letter_slug : '') + '/update-check-out'" 
                    method="POST" 
                    enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')

                    <!-- KM AWAL -->
                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Odometer / KM Awal Keberangkatan <span class="text-rose-500">*</span>
                        </label>

                        <input type="number" 
                            name="start_km" 
                            required 
                            x-model="activeBooking.start_km"
                            :min="activeBooking?.vehicle?.last_log_end_km ?? 0"
                            class="w-full text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="Masukkan angka KM saat ini">

                        <!-- PETUNJUK TEKS ACUAN KM PENGEMBALIAN TERAKHIR -->
                        <p class="text-xs text-indigo-600 mt-1 font-medium" x-show="activeBooking">
                            💡 KM Pengembalian Terakhir Mobil: 
                            <span class="font-bold" x-text="(activeBooking?.vehicle?.last_log_end_km ?? 0) + ' KM'"></span>
                        </p>
                    </div>

                    <!-- BBM AWAL -->
                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Sisa Bensin / BBM (Koreksi) <span class="text-rose-500">*</span>
                        </label>
                        <select name="start_fuel_level" required class="w-full text-sm border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
                            <option value="Full" :selected="activeBooking && activeBooking.log && activeBooking.log.start_fuel_level === 'Full'">Full (Penuh)</option>
                            <option value="3/4" :selected="activeBooking && activeBooking.log && activeBooking.log.start_fuel_level === '3/4'">3/4 Tank</option>
                            <option value="1/2" :selected="activeBooking && activeBooking.log && activeBooking.log.start_fuel_level === '1/2'">1/2 Tank</option>
                            <option value="1/4" :selected="activeBooking && activeBooking.log && activeBooking.log.start_fuel_level === '1/4'">1/4 Tank</option>
                            <option value="E" :selected="activeBooking && activeBooking.log && activeBooking.log.start_fuel_level === 'E'">E (Hampir Habis)</option>
                        </select>
                    </div>

                    <!-- FOTO ODOMETER AWAL -->
                    <div class="mb-5">
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Ganti Foto Odometer / BBM (Opsional)
                        </label>
                        <input type="file" name="start_photo" accept="image/*" class="w-full text-xs text-gray-500 border border-gray-300 rounded-lg p-1.5">
                        <p class="text-[11px] text-gray-500 mt-1">* Biarkan kosong jika tidak ingin mengubah foto awal.</p>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showEditCheckOut = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-semibold rounded-lg transition">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded-lg transition shadow-sm">
                            💾 Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL CHECK-IN (PENGEMBALIAN MOBIL) -->
        <div x-show="showCheckIn" 
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 overflow-y-auto" 
            x-cloak>
            <div class="bg-white rounded-lg p-6 max-w-md w-full shadow-xl relative max-h-[90vh] overflow-y-auto my-auto">
                <div class="flex justify-between items-center mb-3 pb-2 border-b top-0 bg-white z-10">
                    <h3 class="text-lg font-bold text-gray-900">🏁 Pengembalian Mobil</h3>
                    <button type="button" @click="showCheckIn = false" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 inline-flex items-center">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                    </button>
                </div>

                <div class="mb-4 p-3 bg-amber-50 border-l-4 border-amber-500 text-amber-900 rounded-r-md shadow-sm">
                    <div class="flex items-start gap-2.5">
                        <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div class="text-xs leading-relaxed">
                            <p class="font-bold text-amber-900 mb-0.5">⚠️ Periksa Kembali Data Keberangkatan</p>
                            <p class="text-amber-800">
                                Pastikan data Odometer Awal dan BBM saat berangkat sudah benar. Setelah tombol <strong>Selesaikan Peminjaman</strong> dikirim, data keberangkatan akan <strong>dikunci dan tidak dapat diubah lagi</strong>.
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- FORM CHECK-IN TETAP SAMA PERSIS SEPERTI SEBELUMNYA -->
                <div class="mb-4 p-3 bg-blue-50 border border-blue-200 text-blue-800 rounded-md text-xs flex justify-between items-center">
                    <span><strong>Odometer Saat Berangkat:</strong></span>
                    <span x-text="activeBooking && activeBooking.log ? activeBooking.log.start_km + ' KM' : '0 KM'" class="font-bold text-sm text-blue-900"></span>
                </div>

                <form :action="'/bookings/' + (activeBooking ? activeBooking.letter_slug : '') + '/check-in'" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    
                    {{-- INPUT KM AKHIR --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Odometer / KM Akhir <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" name="end_km" 
                            :min="activeBooking && activeBooking.log ? activeBooking.log.start_km + 1 : 1"
                            placeholder="Masukkan angka kilometer pengembalian..." 
                            class="block w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    </div>

                    {{-- INPUT SISA BBM --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Kondisi Sisa BBM (Pengembalian) <span class="text-rose-500">*</span>
                        </label>
                        <select name="end_fuel_level" class="block w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <option value="">-- Pilih Level BBM --</option>
                            <option value="Full">Full (Penuh)</option>
                            <option value="3/4">3/4 Tank</option>
                            <option value="1/2">1/2 Tank</option>
                            <option value="1/4">1/4 Tank</option>
                            <option value="E">E (Hampir Habis)</option>
                        </select>
                    </div>

                    {{-- FOTO ODOMETER --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Foto Odometer (KM dan Sisa BBM) <span class="text-rose-500">*</span>
                        </label>
                        <input type="file" name="end_photo" accept="image/*" class="block w-full text-xs text-gray-500 border border-gray-300 rounded-lg p-1.5" required>
                    </div>

                    {{-- CATATAN KONDISI --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Catatan Kondisi Mobil (Opsional)</label>
                        <textarea name="condition_notes" rows="2" placeholder="Catat jika ada kendala mesin, goresan, dll..." class="block w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>

                    {{-- MULTIPLE FOTO KERUSAKAN/KONDISI --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Foto Kondisi / Bukti Kerusakan (Opsional)</label>
                        <input type="file" 
                            name="condition_photos[]" 
                            multiple 
                            accept="image/*" 
                            class="block w-full text-xs text-gray-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        <p class="mt-1 text-[11px] text-gray-500">* Anda dapat memilih lebih dari satu foto sekaligus jika ada lecet, penyok, atau kerusakan fisik lainnya.</p>
                    </div>

                    {{-- KOTAK IMBAUAN PERMOHONAN PENGEMBALIAN (PENGAJUAN CHECK-IN) --}}
                    <div class="bg-amber-50 border border-amber-200 text-amber-900 text-xs p-3.5 rounded-lg space-y-1">
                        <div class="font-semibold flex items-center gap-1.5 text-amber-800">
                            <svg class="w-4 h-4 text-amber-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Pengajuan Serah Terima Pengembalian</span>
                        </div>
                        <p class="leading-relaxed text-amber-700">
                            Dengan mengajukan pengembalian ini, data Odometer dan Foto Kondisi akan dikirim ke Admin untuk <strong>Verifikasi Fisik Armada</strong>. Setelah disetujui Admin, status peminjaman menjadi <strong>COMPLETED</strong> dan <strong>Nota Pengembalian Resmi (QR Code)</strong> akan otomatis diterbitkan.
                        </p>
                    </div>

                    <div class="flex justify-end space-x-2 pt-3 border-t">
                        <button type="button" @click="showCheckIn = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md text-xs font-semibold transition">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-md text-xs font-semibold hover:bg-purple-700 shadow-sm transition">
                            ✓ Selesaikan Peminjaman
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL EDIT CHECK-IN -->
        <div x-show="showEditCheckIn" 
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 overflow-y-auto" 
            x-cloak>
            <div class="bg-white rounded-lg shadow relative max-w-md w-full p-6 max-h-[90vh] overflow-y-auto my-auto">
                <div class="flex justify-between items-center mb-3 pb-2 border-b">
                    <h3 class="text-lg font-bold text-gray-900">
                        ✏️ Koreksi Data Pengembalian
                    </h3>
                    <button type="button" @click="showEditCheckIn = false" class="text-gray-400 hover:text-gray-900 rounded-lg text-sm p-1.5">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                    </button>
                </div>

                <div class="mb-4 p-3 bg-blue-50 border border-blue-200 text-blue-800 rounded-md text-xs flex justify-between items-center">
                    <span><strong>Odometer Saat Berangkat:</strong></span>
                    <span x-text="activeBooking && activeBooking.log ? activeBooking.log.start_km + ' KM' : '0 KM'" class="font-bold text-sm text-blue-900"></span>
                </div>

                <form :action="'/bookings/' + (activeBooking ? activeBooking.letter_slug : '') + '/update-check-in'" 
                    method="POST" 
                    enctype="multipart/form-data" 
                    class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <!-- KM AKHIR -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Odometer / KM Akhir (Koreksi) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" 
                            name="end_km" 
                            required 
                            :min="activeBooking && activeBooking.log ? activeBooking.log.start_km + 1 : 1"
                            :value="activeBooking && activeBooking.log ? activeBooking.log.end_km : ''"
                            class="w-full text-sm border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
                    </div>

                    <!-- BBM AKHIR -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Level BBM Pengembalian (Koreksi) <span class="text-rose-500">*</span>
                        </label>
                        <select name="end_fuel_level" required class="w-full text-sm border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
                            <option value="Full" :selected="activeBooking && activeBooking.log && activeBooking.log.end_fuel_level === 'Full'">Full (Penuh)</option>
                            <option value="3/4" :selected="activeBooking && activeBooking.log && activeBooking.log.end_fuel_level === '3/4'">3/4 Tank</option>
                            <option value="1/2" :selected="activeBooking && activeBooking.log && activeBooking.log.end_fuel_level === '1/2'">1/2 Tank</option>
                            <option value="1/4" :selected="activeBooking && activeBooking.log && activeBooking.log.end_fuel_level === '1/4'">1/4 Tank</option>
                            <option value="E" :selected="activeBooking && activeBooking.log && activeBooking.log.end_fuel_level === 'E'">E (Hampir Habis)</option>
                        </select>
                    </div>

                    <!-- FOTO ODOMETER AKHIR -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Ganti Foto Odometer / BBM (Opsional)
                        </label>
                        <input type="file" name="end_photo" accept="image/*" class="w-full text-xs text-gray-500 border border-gray-300 rounded-lg p-1.5">
                        <p class="text-[11px] text-gray-500 mt-0.5">* Biarkan kosong jika tidak ingin mengubah foto odometer pengembalian.</p>
                    </div>

                    <!-- CATATAN KONDISI -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Catatan Kondisi Mobil (Opsional)</label>
                        <textarea name="condition_notes" 
                                rows="2" 
                                :value="activeBooking && activeBooking.log ? activeBooking.log.condition_notes : ''"
                                placeholder="Catat jika ada kendala mesin, goresan, dll..." 
                                class="w-full text-sm border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500"></textarea>
                    </div>

                    <!-- TAMBAH FOTO KERUSAKAN -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Tambah Foto Bukti Kerusakan (Opsional)</label>
                        <input type="file" 
                            name="condition_photos[]" 
                            multiple 
                            accept="image/*" 
                            class="w-full text-xs text-gray-500 border border-gray-300 rounded-lg p-1.5">
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t">
                        <button type="button" @click="showEditCheckIn = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-semibold rounded-lg transition">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded-lg transition shadow-sm">
                            💾 Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL KONFIRMASI KEBERANGKATAN -->
        <div x-show="showConfirmDepartureModal" 
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 overflow-y-auto" 
            x-cloak>
            <div class="bg-white rounded-lg shadow relative max-w-md w-full p-6 max-h-[90vh] overflow-y-auto my-auto text-center">
                <!-- TOMBOL CLOSE -->
                <div class="flex justify-end p-1">
                    <button type="button" @click="showConfirmDepartureModal = false" class="text-gray-400 hover:text-gray-900 rounded-lg text-sm p-1">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                    </button>
                </div>

                <!-- ICON UTAMA (KEBERANGKATAN - BLUE) -->
                <svg class="w-16 h-16 text-blue-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                </svg>

                <!-- JUDUL MODAL & NOMOR SURAT -->
                <h3 class="text-lg font-bold text-gray-800 mb-1">
                    Konfirmasi Keberangkatan Armada
                </h3>
                <p class="font-bold font-mono text-indigo-600 text-sm mb-4" x-text="selectedBooking ? selectedBooking.letter_number : ''"></p>

                <!-- INFORMASI RINGKAS PERJALANAN -->
                <div class="mb-4 p-3 bg-gray-50 border border-gray-200 text-gray-800 rounded-lg text-xs space-y-1.5 text-left">
                    <div class="flex justify-between">
                        <span class="font-semibold text-gray-600">Peminjam:</span>
                        <span x-text="selectedBooking ? selectedBooking.user.name : '-'" class="font-bold text-gray-900"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-semibold text-gray-600">Kendaraan:</span>
                        <span x-text="selectedBooking && selectedBooking.vehicle ? selectedBooking.vehicle.model + ' (' + selectedBooking.vehicle.plate_number + ')' : '-'" class="font-bold text-gray-900"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-semibold text-gray-600">Tujuan:</span>
                        <span x-text="selectedBooking ? selectedBooking.destination : '-'" class="font-bold text-gray-900"></span>
                    </div>
                </div>

                <!-- ALERT PERINGATAN TANDA TANGAN DIGITAL -->
                <div class="bg-blue-50 border border-blue-200 text-blue-900 text-xs text-left p-3.5 rounded-lg mb-5">
                    <div class="flex items-start gap-2.5">
                        <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <div class="space-y-1">
                            <div class="font-semibold text-blue-800">Legitimasikan Tanda Tangan Digital</div>
                            <p class="leading-relaxed text-blue-700">
                                Dengan mengonfirmasi keberangkatan ini, Anda (<strong>{{ auth()->user()->name }}</strong>) akan dicatat sebagai penanggung jawab dan membubuhi <strong>Tanda Tangan Digital (QR Code)</strong> pada Nota Keberangkatan resmi.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- FORM ACTION -->
                <form :action="'/bookings/' + (selectedBooking ? selectedBooking.letter_slug : '') + '/confirm-departure'" method="POST" class="inline">
                    @csrf
                    @method('PATCH')
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showConfirmDepartureModal = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-semibold rounded-lg transition">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg transition shadow">
                            ✓ Konfirmasi Keberangkatan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL KONFIRMASI PENGEMBALIAN -->
        <div x-show="showConfirmReturnModal" 
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 overflow-y-auto" 
            x-cloak>
            <div class="bg-white rounded-lg shadow relative max-w-md w-full p-6 max-h-[90vh] overflow-y-auto my-auto text-center">
                <!-- TOMBOL CLOSE -->
                <div class="flex justify-end p-1">
                    <button type="button" @click="showConfirmReturnModal = false" class="text-gray-400 hover:text-gray-900 rounded-lg text-sm p-1">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                    </button>
                </div>

                <!-- ICON UTAMA (PENGEMBALIAN - EMERALD) -->
                <svg class="w-16 h-16 text-emerald-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>

                <!-- JUDUL MODAL & NOMOR SURAT -->
                <h3 class="text-lg font-bold text-gray-800 mb-1">
                    Verifikasi Pengembalian Fisik
                </h3>
                <p class="font-bold font-mono text-indigo-600 text-sm mb-4" x-text="selectedBooking ? selectedBooking.letter_number : ''"></p>

                <!-- INFORMASI RINGKAS PERJALANAN -->
                <div class="mb-4 p-3 bg-gray-50 border border-gray-200 text-gray-800 rounded-lg text-xs space-y-1.5 text-left">
                    <div class="flex justify-between">
                        <span class="font-semibold text-gray-600">Peminjam:</span>
                        <span x-text="selectedBooking ? selectedBooking.user.name : '-'" class="font-bold text-gray-900"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-semibold text-gray-600">Kendaraan:</span>
                        <span x-text="selectedBooking && selectedBooking.vehicle ? selectedBooking.vehicle.model + ' (' + selectedBooking.vehicle.plate_number + ')' : '-'" class="font-bold text-gray-900"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-semibold text-gray-600">Tujuan:</span>
                        <span x-text="selectedBooking ? selectedBooking.destination : '-'" class="font-bold text-gray-900"></span>
                    </div>
                </div>

                <!-- ALERT PERINGATAN TANDA TANGAN DIGITAL -->
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs text-left p-3.5 rounded-lg mb-5">
                    <div class="flex items-start gap-2.5">
                        <svg class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <div class="space-y-1">
                            <div class="font-semibold text-emerald-800">Verifikasi Fisik & Tanda Tangan Penerima</div>
                            <p class="leading-relaxed text-emerald-700">
                                Pastikan kondisi fisik kendaraan dan odometer telah diperiksa. Dengan menyetujui, Anda (<strong>{{ auth()->user()->name }}</strong>) akan dicatat sebagai penanggung jawab dan membubuhi <strong>Tanda Tangan Digital (QR Code)</strong> pada Nota Pengembalian.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- FORM ACTION -->
                <form :action="'/bookings/' + (selectedBooking ? selectedBooking.letter_slug : '') + '/confirm-return'" method="POST" class="inline">
                    @csrf
                    @method('PATCH')
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showConfirmReturnModal = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-semibold rounded-lg transition">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg transition shadow">
                            ✓ Konfirmasi Pengembalian
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>