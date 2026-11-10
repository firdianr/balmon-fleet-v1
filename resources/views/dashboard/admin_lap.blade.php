<x-app-layout>
    <x-slot name="title">Dashboard Admin Lapangan</x-slot>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="font-bold text-xl text-gray-800 leading-tight">Dashboard Pengelolaan Pengajuan dan Peminjaman</h2>
                <p class="text-xs text-gray-500 mt-0.5">Pusat alokasi armada/driver, verifikasi fisik garasi, dan persetujuan izin driver.</p>
            </div>
            <div class="text-xs text-gray-500 bg-white px-3 py-1.5 rounded-xl border border-gray-200 shadow-2xs font-mono flex items-center gap-1.5">
                <svg class="w-4 h-4 text-gray-400 stroke-current stroke-2 fill-none" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <span>{{ now()->translatedFormat('l, d F Y') }}</span>
            </div>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        {{-- 1. ACTION ALERTS FOR ADMIN LAPANGAN --}}
        
        {{-- ALERT 1A: ALOKASI DRAFT (draft -> waiting / converted) --}}
        @if(($pendingDraftsCount ?? 0) > 0)
            <div class="p-4 bg-amber-50 border border-amber-200 rounded-2xl flex items-center justify-between gap-4 shadow-xs">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center font-bold shrink-0">
                        <svg class="w-5 h-5 stroke-current stroke-2 fill-none" viewBox="0 0 24 24"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-amber-950">Ada {{ $pendingDraftsCount }} Permohonan Draft Menunggu Alokasi Ketersediaan</p>
                        <p class="text-[11px] text-amber-800">Tentukan ketersediaan armada dan pengemudi untuk permohonan draft pegawai.</p>
                    </div>
                </div>
                <a href="{{ route('draft-bookings.index') }}" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-xl transition shrink-0">
                    Alokasikan Draft &rarr;
                </a>
            </div>
        @endif

        {{-- ALERT 1B: ALOKASI PEMINJAMAN DINAS RESMI (pending -> waiting) --}}
        @if(($pendingBookingsCount ?? 0) > 0)
            <div class="p-4 bg-orange-50 border border-orange-200 rounded-2xl flex items-center justify-between gap-4 shadow-xs">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-orange-100 text-orange-800 flex items-center justify-center font-bold shrink-0">
                        <svg class="w-5 h-5 stroke-current stroke-2 fill-none" viewBox="0 0 24 24"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.5 2.8C2 11.2 2 11.6 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-orange-950">Ada {{ $pendingBookingsCount }} Peminjaman Dinas (SOP) Menunggu Alokasi Unit & Driver</p>
                        <p class="text-[11px] text-orange-800">Alokasikan unit kendaraan dan driver agar dokumen peminjaman resmi dapat dikirim ke Approver (TTD).</p>
                    </div>
                </div>
                <a href="{{ route('bookings.index', ['status' => 'pending']) }}" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold rounded-xl transition shrink-0">
                    Alokasikan Booking &rarr;
                </a>
            </div>
        @endif

        {{-- ALERT 2: VERIFIKASI GARASI KEPULANGAN (returned -> checked) --}}
        @if(($pendingReturnVerificationsCount ?? 0) > 0)
            <div class="p-4 bg-cyan-50 border border-cyan-200 rounded-2xl flex items-center justify-between gap-4 shadow-xs">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-cyan-100 text-cyan-800 flex items-center justify-center font-bold shrink-0">
                        <svg class="w-5 h-5 stroke-current stroke-2 fill-none" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><path d="m9 11 2 2 4-4"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-cyan-950">Ada {{ $pendingReturnVerificationsCount }} Armada Kembali Menunggu Physical Check Garasi</p>
                        <p class="text-[11px] text-cyan-800">Lakukan pengecekan fisik kepulangan, kebersihan garasi, dan validasi kondisi armada.</p>
                    </div>
                </div>
                <a href="{{ route('bookings.index', ['status' => 'returned']) }}" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-bold rounded-xl transition shrink-0">
                    Tinjau Kepulangan &rarr;
                </a>
            </div>
        @endif

        {{-- ALERT 3: CUTI / IZIN DRIVER --}}
        @if(count($pendingAttendances ?? []) > 0)
            <div class="bg-white p-5 rounded-2xl border border-amber-200 shadow-xs space-y-3">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="font-bold text-sm text-gray-800 flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-600 stroke-current stroke-2 fill-none" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <span>Pengajuan Cuti / Izin Driver Menunggu Approval ({{ count($pendingAttendances) }})</span>
                    </h3>
                    <a href="{{ route('drivers.calendar') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">Buka Kalender Driver &rarr;</a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($pendingAttendances as $att)
                        <div class="p-3 bg-amber-50/60 border border-amber-200 rounded-xl flex items-center justify-between text-xs">
                            <div>
                                <span class="font-bold text-gray-900 block">{{ $att->driver?->name }}</span>
                                <span class="text-amber-800 font-bold uppercase text-[10px]">{{ $att->status }}</span>
                                <span class="text-gray-500 text-[10px] block mt-0.5"> {{ \Carbon\Carbon::parse($att->start_date)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($att->end_date)->format('d/m/Y') }}</span>
                            </div>
                            <a href="{{ route('drivers.calendar') }}" class="px-3 py-1.5 bg-indigo-600 text-white font-bold text-[11px] rounded-lg hover:bg-indigo-700 transition">Tinjau</a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif


        {{-- 2. KARTU METRIK UTAMA OPERASIONAL --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Armada Siap Pakai</p>
                    <p class="text-2xl font-black text-gray-900">{{ $availableVehiclesCount }} <span class="text-xs font-medium text-gray-400">/ {{ $totalVehiclesCount }} Unit</span></p>
                </div>
                <div class="p-3 bg-blue-50 text-blue-600 rounded-2xl">
                    <svg class="w-6 h-6 stroke-current stroke-2 fill-none" viewBox="0 0 24 24"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.5 2.8C2 11.2 2 11.6 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/></svg>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Sedang Beroperasi</p>
                    <p class="text-2xl font-black text-emerald-600">{{ $activeTripsCount }}</p>
                </div>
                <div class="p-3 bg-emerald-50 text-emerald-600 rounded-2xl">
                    <svg class="w-6 h-6 stroke-current stroke-2 fill-none" viewBox="0 0 24 24"><polygon points="12 2 19 21 12 17 5 21 12 2"/></svg>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Total Butuh Alokasi</p>
                    <p class="text-2xl font-black text-amber-600">
                        {{ ($pendingDraftsCount ?? 0) + ($pendingBookingsCount ?? 0) }}
                        <span class="text-xs font-normal text-gray-400">({{ $pendingBookingsCount }} Booking / {{ $pendingDraftsCount }} Draft)</span>
                    </p>
                </div>
                <div class="p-3 bg-amber-50 text-amber-600 rounded-2xl">
                    <svg class="w-6 h-6 stroke-current stroke-2 fill-none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Physical Check Garasi</p>
                    <p class="text-2xl font-black text-cyan-600">{{ $pendingReturnVerificationsCount }}</p>
                </div>
                <div class="p-3 bg-cyan-50 text-cyan-600 rounded-2xl">
                    <svg class="w-6 h-6 stroke-current stroke-2 fill-none" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><path d="m9 11 2 2 4-4"/></svg>
                </div>
            </div>
        </div>


        {{-- 3. TABEL MONITORING UTAMA --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- TABEL KIRI (2 KOLOM): PERJALANAN & PEMINJAMAN RESMI --}}
            <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 p-6 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <div>
                        <h3 class="font-bold text-sm text-gray-800">Aktivitas Peminjaman & Perjalanan Dinas</h3>
                        <p class="text-[11px] text-gray-500">Daftar peminjaman armada yang sedang berjalan / diproses</p>
                    </div>
                    <a href="{{ route('bookings.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">Lihat Semua &rarr;</a>
                </div>
                <div class="space-y-3">
                    @forelse($recentBookings as $b)
                        <div class="p-3 bg-gray-50 rounded-xl flex items-center justify-between text-xs border border-gray-100">
                            <div>
                                <span class="font-bold text-gray-900 block">
                                    {{ $b->vehicle ? $b->vehicle->brand . ' ' . $b->vehicle->model . ' (' . $b->vehicle->plate_number . ')' : '(Belum Dialokasi)' }}
                                </span>
                                <span class="text-gray-500">Pemohon: {{ $b->user?->name }} | Tujuan: {{ $b->destination }}</span>
                            </div>
                            <span class="px-2.5 py-1 rounded-full font-bold uppercase text-[10px] 
                                @if($b->status === 'pending') bg-orange-100 text-orange-800
                                @elseif($b->status === 'waiting') bg-amber-100 text-amber-800
                                @elseif($b->status === 'approved') bg-blue-100 text-blue-800
                                @elseif($b->status === 'on_trip') bg-purple-100 text-purple-800
                                @elseif($b->status === 'returned') bg-indigo-100 text-indigo-800
                                @elseif($b->status === 'checked') bg-cyan-100 text-cyan-800
                                @elseif($b->status === 'completed') bg-teal-100 text-teal-800
                                @else bg-gray-100 text-gray-700 @endif">
                                {{ $b->status }}
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 italic">Belum ada aktivitas peminjaman.</p>
                    @endforelse
                </div>
            </div>

            {{-- TABEL KANAN (1 KOLOM): PERMOHONAN DRAFT TERBARU --}}
            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <div>
                        <h3 class="font-bold text-sm text-gray-800">Draft Masuk</h3>
                        <p class="text-[11px] text-gray-500">Permohonan ketersediaan armada</p>
                    </div>
                    <a href="{{ route('draft-bookings.index') }}" class="text-xs font-bold text-amber-600 hover:text-amber-800">Lihat Semua &rarr;</a>
                </div>
                <div class="space-y-3">
                    @forelse($recentDrafts as $d)
                        <div class="p-3 bg-amber-50/50 rounded-xl border border-amber-100 text-xs space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-gray-900">{{ $d->user?->name }}</span>
                                <span class="text-[10px] text-amber-700 bg-amber-100 px-2 py-0.5 rounded font-bold uppercase">{{ $d->status }}</span>
                            </div>
                            <span class="text-gray-500 block truncate">{{ $d->destination }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 italic">Tidak ada draft baru.</p>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</x-app-layout>