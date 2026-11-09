<x-app-layout>
    <x-slot name="title">Dashboard Operasional Armada</x-slot>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="font-bold text-xl text-gray-800 leading-tight">
                    Dashboard Operasional Armada
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">
                    Selamat datang kembali, <span class="font-bold text-gray-700">{{ Auth::user()->name }}</span> ({{ strtoupper(str_replace('_', ' ', Auth::user()->role)) }})
                </p>
            </div>
            <div class="text-xs text-gray-500 bg-white px-3 py-1.5 rounded-xl border border-gray-200 shadow-2xs font-mono flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <span>{{ now()->translatedFormat('l, d F Y') }}</span>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- 1. SEKSI ALERTS & ACTION NEEDED BASED ON ROLE --}}

            {{-- ALERT UNTUK ADMIN LAPANGAN (Peninjauan Draft Ketersediaan Unit/Driver) --}}
            @if(in_array(Auth::user()->role, ['admin_app', 'admin_lap']) && ($pendingDraftsCount ?? 0) > 0)
                <div class="p-4 bg-amber-50/90 border border-amber-200 rounded-2xl flex items-center justify-between gap-4 shadow-xs">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center font-bold shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-amber-950">Ada {{ $pendingDraftsCount }} Permohonan Armada Menunggu Peninjauan</p>
                            <p class="text-[11px] text-amber-800">Tinjau ketersediaan unit dan pengemudi agar pemohon dapat melanjutkan ke pengajuan Surat Tugas.</p>
                        </div>
                    </div>
                    <a href="{{ route('draft-bookings.index') }}" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-xl transition shadow-xs shrink-0 flex items-center gap-1.5">
                        <span>Tinjau Permohonan</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </a>
                </div>
            @endif

            {{-- ALERT UNTUK ADMIN LAPANGAN (Verifikasi Physical Check Kepulangan) --}}
            @if(in_array(Auth::user()->role, ['admin_app', 'admin_lap']) && ($pendingReturnVerificationsCount ?? 0) > 0)
                <div class="p-4 bg-cyan-50/90 border border-cyan-200 rounded-2xl flex items-center justify-between gap-4 shadow-xs">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-cyan-100 text-cyan-800 flex items-center justify-center font-bold shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><path d="m9 11 2 2 4-4"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-cyan-950">Ada {{ $pendingReturnVerificationsCount }} Armada Kembali Menunggu Verifikasi Garasi</p>
                            <p class="text-[11px] text-cyan-800">Lakukan pengecekan fisik kepulangan, checklist garasi, dan validasi kebersihan armada.</p>
                        </div>
                    </div>
                    <a href="{{ route('bookings.index', ['status' => 'returned']) }}" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-bold rounded-xl transition shadow-xs shrink-0 flex items-center gap-1.5">
                        <span>Verifikasi Garasi</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </a>
                </div>
            @endif

            {{-- ALERT UNTUK APPROVER (Pengesahan TTD Persetujuan Surat Tugas) --}}
            @if(in_array(Auth::user()->role, ['admin_app', 'approver']) && ($pendingApprovalsCount ?? 0) > 0)
                <div class="p-4 bg-indigo-50/90 border border-indigo-200 rounded-2xl flex items-center justify-between gap-4 shadow-xs">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-800 flex items-center justify-center font-bold shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-indigo-950">Ada {{ $pendingApprovalsCount }} Peminjaman Dinas Menunggu Persetujuan Anda</p>
                            <p class="text-[11px] text-indigo-800">Dokumen Surat Tugas telah diunggah dan memerlukan persetujuan tanda tangan elektronik.</p>
                        </div>
                    </div>
                    <a href="{{ route('bookings.index', ['status' => 'pending']) }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition shadow-xs shrink-0 flex items-center gap-1.5">
                        <span>Proses Persetujuan</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </a>
                </div>
            @endif

            {{-- ALERT UNTUK DRIVER (Penugasan Aktif Hari Ini) --}}
            @if(Auth::user()->role === 'driver' && isset($driverActiveTrip))
                <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center justify-between gap-4 shadow-xs">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.5 2.8C2 11.2 2 11.6 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-emerald-950">Penugasan Perjalanan Aktif</p>
                            <p class="text-[11px] text-emerald-800">
                                Unit: <span class="font-bold">{{ $driverActiveTrip->vehicle->brand }} {{ $driverActiveTrip->vehicle->model }} ({{ $driverActiveTrip->vehicle->plate_number }})</span> 
                                | No Surat: <span class="font-mono font-bold">{{ $driverActiveTrip->letter_number }}</span>
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('bookings.show', $driverActiveTrip->letter_slug) }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition shadow-xs shrink-0 flex items-center gap-1.5">
                        <span>Isi Log & Checklist</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </a>
                </div>
            @endif

            {{-- ALERT UNTUK PEMOHON (Pengajuan Siap Sahkan / TTD Kepulangan) --}}
            @if(Auth::user()->role === 'pemohon' && isset($pemohonPendingReturnSign))
                <div class="p-4 bg-purple-50 border border-purple-200 rounded-2xl flex items-center justify-between gap-4 shadow-xs">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-800 flex items-center justify-center font-bold shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-purple-950">Pengembalian Armada Membutuhkan Tanda Tangan Pengesahan</p>
                            <p class="text-[11px] text-purple-800">
                                Perjalanan No. Surat <span class="font-mono font-bold">{{ $pemohonPendingReturnSign->letter_number }}</span> telah selesai diisi oleh driver. Mohon sahkan Formulir & BAST Pengembalian.
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('bookings.index') }}" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl transition shadow-xs shrink-0 flex items-center gap-1.5">
                        <span>Sahkan Pengembalian</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </a>
                </div>
            @endif


            {{-- 2. KARTU METRIK RINGKASAN RINGKAS --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- CARD 1: ARMADA READY --}}
                <div class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs flex items-center justify-between">
                    <div class="space-y-1">
                        <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Armada Siap Pakai</p>
                        <p class="text-2xl font-black text-gray-900">
                            {{ $availableVehiclesCount ?? 0 }} 
                            <span class="text-xs font-medium text-gray-400">/ {{ $totalVehiclesCount ?? 0 }} Unit</span>
                        </p>
                        <span class="inline-block text-[10px] text-blue-700 bg-blue-50 px-2 py-0.5 rounded-md font-semibold">
                            Garasi Balmon
                        </span>
                    </div>
                    <div class="p-3 bg-blue-50 text-blue-600 rounded-2xl text-2xl font-bold shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.5 2.8C2 11.2 2 11.6 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/></svg>
                    </div>
                </div>

                {{-- CARD 2: SEDANG BEROPERASI --}}
                <div class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs flex items-center justify-between">
                    <div class="space-y-1">
                        <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Sedang Beroperasi</p>
                        <p class="text-2xl font-black text-emerald-600">
                            {{ $activeTripsCount ?? 0 }} 
                            <span class="text-xs font-medium text-gray-400">Perjalanan</span>
                        </p>
                        <span class="inline-block text-[10px] text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md font-semibold">
                            Di Luar Garasi
                        </span>
                    </div>
                    <div class="p-3 bg-emerald-50 text-emerald-600 rounded-2xl text-2xl font-bold shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 19 21 12 17 5 21 12 2"/></svg>
                    </div>
                </div>

                {{-- CARD 3: PERMOHONAN DRAFT (PENINJAUAN) --}}
                <div class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs flex items-center justify-between">
                    <div class="space-y-1">
                        <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Permohonan Draft</p>
                        <p class="text-2xl font-black text-amber-600">
                            {{ $pendingDraftsCount ?? 0 }} 
                            <span class="text-xs font-medium text-gray-400">Request</span>
                        </p>
                        <span class="inline-block text-[10px] text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md font-semibold">
                            Tinjau Unit & Driver
                        </span>
                    </div>
                    <div class="p-3 bg-amber-50 text-amber-600 rounded-2xl text-2xl font-bold shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                </div>

                {{-- CARD 4: PEMINJAMAN RESMI --}}
                <div class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs flex items-center justify-between">
                    <div class="space-y-1">
                        <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Peminjaman Dinas</p>
                        <p class="text-2xl font-black text-indigo-600">
                            {{ $pendingApprovalsCount ?? 0 }} 
                            <span class="text-xs font-medium text-gray-400">Dokumen</span>
                        </p>
                        <span class="inline-block text-[10px] text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md font-semibold">
                            Surat Tugas Resmi
                        </span>
                    </div>
                    <div class="p-3 bg-purple-50 text-purple-600 rounded-2xl text-2xl font-bold shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    </div>
                </div>
            </div>


            {{-- 3. AKSES CEPAT LAYANAN (QUICK ACTIONS) --}}
            <div class="bg-white p-6 rounded-2xl border border-gray-200/80 shadow-xs space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-indigo-600 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                        <span>Akses Cepat Layanan Operasional</span>
                    </h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    {{-- TOMBOL 1: PERMOHONAN DRAFT ARMADA --}}
                    <a href="{{ route('draft-bookings.create') }}" class="p-4 rounded-xl border border-indigo-100 bg-gradient-to-br from-indigo-50/40 to-indigo-50/10 hover:border-indigo-300 transition flex items-center justify-between group shadow-2xs">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-indigo-950 text-sm">Permohonan Armada</span>
                                <span class="text-[9px] bg-indigo-100 text-indigo-800 font-bold px-1.5 py-0.5 rounded">Langkah 1</span>
                            </div>
                            <p class="text-gray-500 leading-relaxed text-[11px]">Cek ketersediaan mobil & driver sebelum menerbitkan Surat Tugas resmi.</p>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-indigo-600 stroke-current stroke-2 fill-none group-hover:translate-x-1 transition ml-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </a>

                    {{-- TOMBOL 2: PEMINJAMAN DINAS RESMI --}}
                    <a href="{{ route('bookings.create') }}" class="p-4 rounded-xl border border-emerald-100 bg-gradient-to-br from-emerald-50/40 to-emerald-50/10 hover:border-emerald-300 transition flex items-center justify-between group shadow-2xs">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-emerald-950 text-sm">Peminjaman Dinas (SOP)</span>
                                <span class="text-[9px] bg-emerald-100 text-emerald-800 font-bold px-1.5 py-0.5 rounded">Langkah 2</span>
                            </div>
                            <p class="text-gray-500 leading-relaxed text-[11px]">Sudah memegang Surat Tugas resmi? Langsung ajukan peminjaman armada.</p>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-600 stroke-current stroke-2 fill-none group-hover:translate-x-1 transition ml-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </a>

                    {{-- TOMBOL 3: KATALOG MONITORING DAFTAR ARMADA --}}
                    <a href="{{ route('vehicles.index') }}" class="p-4 rounded-xl border border-blue-100 bg-gradient-to-br from-blue-50/40 to-blue-50/10 hover:border-blue-300 transition flex items-center justify-between group shadow-2xs">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-blue-950 text-sm">Katalog & Status Garasi</span>
                            </div>
                            <p class="text-gray-500 leading-relaxed text-[11px]">Pantau ketersediaan unit kendaraan, STNK, dan jadwal perawatan berkala.</p>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-blue-600 stroke-current stroke-2 fill-none group-hover:translate-x-1 transition ml-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </a>
                </div>
            </div>


            {{-- 4. TABEL MONITORING UTAMA (PERJALANAN SEDANG BERLANGSUNG & PERMOHONAN TERBARU) --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                {{-- TABEL KIRI (2 KOLOM): AKTIVITAS PERJALANAN OPERASIONAL SEDANG BERLANGSUNG / PEMINJAMAN DINAS --}}
                <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200/80 shadow-xs flex flex-col overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-sm text-gray-800 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>Perjalanan & Peminjaman Dinas</span>
                            </h3>
                            <p class="text-[11px] text-gray-500 mt-0.5">Daftar peminjaman resmi armada Balmon Fleet</p>
                        </div>
                        <a href="{{ route('bookings.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition flex items-center gap-1">
                            <span>Lihat Semua</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </a>
                    </div>

                    <div class="overflow-x-auto flex-1">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-gray-50/80 text-gray-500 font-bold border-b border-gray-100 uppercase tracking-wider text-[10px]">
                                    <th class="py-3 px-4">Armada & Plat</th>
                                    <th class="py-3 px-4">Pemohon & Tujuan</th>
                                    <th class="py-3 px-4">Pengemudi</th>
                                    <th class="py-3 px-4">Status & Waktu</th>
                                    <th class="py-3 px-4 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-gray-700">
                                @forelse($recentBookings ?? [] as $booking)
                                    <tr class="hover:bg-slate-50/60 transition">
                                        <td class="py-3 px-4 font-medium">
                                            <div class="flex items-center gap-2.5">
                                                <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-600 shrink-0">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.5 2.8C2 11.2 2 11.6 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/></svg>
                                                </div>
                                                <div>
                                                    <span class="font-bold text-gray-900 block">{{ $booking->vehicle?->brand }} {{ $booking->vehicle?->model }}</span>
                                                    <span class="text-[10px] font-mono text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded">{{ $booking->vehicle?->plate_number }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="font-bold text-gray-800 block">{{ $booking->user?->name }}</span>
                                            <span class="text-[11px] text-gray-500 flex items-center gap-1 truncate max-w-[160px]" title="{{ $booking->destination }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-gray-400 stroke-current stroke-2 fill-none shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 12 8 12s8-6.75 8-12a8 8 0 0 0-8-8z"/><circle cx="12" cy="10" r="3"/></svg>
                                                <span>{{ $booking->destination }}</span>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="font-semibold text-gray-700 block">{{ $booking->driver?->name ?? 'Tanpa Driver' }}</span>
                                        </td>
                                        <td class="py-3 px-4">
                                            {{-- 1. WARNA BADGE STATUS BOOKING RESMI (TABEL KIRI) --}}
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full font-bold text-[10px] uppercase whitespace-nowrap
                                                @if($booking->status === 'pending') bg-yellow-100 text-yellow-800
                                                @elseif($booking->status === 'waiting') bg-amber-100 text-amber-800
                                                @elseif($booking->status === 'approved') bg-blue-100 text-blue-800
                                                @elseif($booking->status === 'trip_started') bg-emerald-100 text-emerald-800
                                                @elseif($booking->status === 'on_trip') bg-purple-100 text-purple-800
                                                @elseif($booking->status === 'trip_completed') bg-violet-100 text-violet-800
                                                @elseif($booking->status === 'returned') bg-indigo-100 text-indigo-800
                                                @elseif($booking->status === 'checked') bg-cyan-100 text-cyan-800
                                                @elseif($booking->status === 'completed') bg-teal-100 text-teal-800
                                                @elseif($booking->status === 'canceled') bg-gray-200 text-gray-700
                                                @elseif($booking->status === 'rejected') bg-rose-100 text-rose-800
                                                @else bg-gray-100 text-gray-700 @endif">
                                                <span>{{ strtoupper(str_replace('_', ' ', $booking->status)) }}</span>
                                            </span>
                                            <span class="text-[10px] text-gray-400 block mt-0.5">
                                                Tgl: {{ \Carbon\Carbon::parse($booking->start_date)->format('d/m/Y') }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <a href="{{ route('bookings.show', $booking->letter_slug) }}" class="p-1.5 text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 rounded-lg transition inline-flex items-center gap-0.5 font-bold">
                                                <span>Detail</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-gray-400 italic">
                                            <div class="space-y-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-gray-300 stroke-current stroke-1 fill-none mx-auto" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.5 2.8C2 11.2 2 11.6 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/></svg>
                                                <p class="text-xs">Tidak ada peminjaman armada saat ini.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- TABEL KANAN (1 KOLOM): PERMOHONAN DRAFT TERBARU (BUTUH PENINJAUAN) --}}
                <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs flex flex-col overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-sm text-gray-800">Permohonan Draft Masuk</h3>
                            <p class="text-[11px] text-gray-500 mt-0.5">Request ketersediaan unit terbaru</p>
                        </div>
                        <a href="{{ route('draft-bookings.index') }}" class="text-xs font-bold text-amber-600 hover:text-amber-800 transition flex items-center gap-1">
                            <span>Semua</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </a>
                    </div>

                    <div class="divide-y divide-gray-100 overflow-y-auto flex-1 max-h-[380px]">
                        @forelse($recentDrafts ?? [] as $draft)
                            <div class="p-4 hover:bg-amber-50/30 transition space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-gray-900 text-xs">{{ $draft->user?->name }}</span>
                                    
                                    {{-- 2. WARNA BADGE STATUS DRAFT BOOKING (TABEL KANAN) --}}
                                    <span class="text-[10px] px-2.5 py-0.5 rounded-full font-bold uppercase whitespace-nowrap
                                        @if($draft->status === 'converted') bg-blue-100 text-blue-800
                                        @elseif($draft->status === 'approved') bg-emerald-100 text-emerald-800
                                        @elseif($draft->status === 'draft' || $draft->status === 'pending') bg-amber-100 text-amber-800
                                        @elseif($draft->status === 'rejected') bg-rose-100 text-rose-800
                                        @else bg-gray-100 text-gray-700 @endif">
                                        {{ strtoupper(str_replace('_', ' ', $draft->status)) }}
                                    </span>
                                </div>
                                <div class="text-[11px] text-gray-600 space-y-0.5">
                                    <p class="truncate font-medium flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-gray-400 stroke-current stroke-2 fill-none shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 12 8 12s8-6.75 8-12a8 8 0 0 0-8-8z"/><circle cx="12" cy="10" r="3"/></svg>
                                        <span>{{ $draft->destination }}</span>
                                    </p>
                                    <p class="text-gray-400 flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-gray-400 stroke-current stroke-2 fill-none shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                        <span>{{ \Carbon\Carbon::parse($draft->start_date)->format('d M Y') }} - {{ \Carbon\Carbon::parse($draft->end_date)->format('d M Y') }}</span>
                                    </p>
                                </div>
                                <div class="pt-1 flex items-center justify-between text-[11px]">
                                    <span class="text-gray-500 font-mono text-[10px]">No: {{ $draft->reference_number ?? '-' }}</span>
                                    <a href="{{ route('draft-bookings.index', $draft->id) }}" class="font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-0.5">
                                        <span>Tinjau</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                    </a>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-gray-400 italic space-y-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-gray-300 stroke-current stroke-1 fill-none mx-auto" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                                <p class="text-xs">Belum ada permohonan draft terbaru.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>