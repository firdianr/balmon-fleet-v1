<x-app-layout>
    <x-slot name="title">Dashboard Admin App</x-slot>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="font-bold text-xl text-gray-800 leading-tight">Dashboard Pengelolaan Pengajuan dan Peminjaman</h2>
                <p class="text-xs text-gray-500 mt-0.5">Pusat kontrol integrasi sistem, pemantauan master data, dan pengawasan operasional armada.</p>
            </div>
            <div class="text-xs text-gray-500 bg-white px-3 py-1.5 rounded-xl border border-gray-200 shadow-2xs font-mono flex items-center gap-1.5">
                <svg class="w-4 h-4 text-gray-400 stroke-current stroke-2 fill-none" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <span>{{ now()->translatedFormat('l, d F Y') }}</span>
            </div>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        {{-- 1. WIDGET HEALTH CHECK REAL-TIME (DINAMIS) --}}
        @if(isset($systemHealth))
            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs space-y-3">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="font-bold text-sm text-gray-800 flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-600 stroke-current stroke-2 fill-none" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"/><rect x="2" y="14" width="20" height="8" rx="2" ry="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/></svg>
                        <span>Status Kesehatan Sistem & Integrasi Real-Time</span>
                    </h3>

                    @if($systemHealth['all_good'])
                        <span class="px-2.5 py-1 rounded-full font-bold uppercase text-[10px] bg-emerald-100 text-emerald-800 border border-emerald-200 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-ping"></span>
                            <span>System Normal</span>
                        </span>
                    @else
                        <span class="px-2.5 py-1 rounded-full font-bold uppercase text-[10px] bg-rose-100 text-rose-800 border border-rose-200 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-600 animate-ping"></span>
                            <span>Issue Detected</span>
                        </span>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                    {{-- WA GATEWAY STATUS --}}
                    <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 flex items-center justify-between">
                        <div>
                            <span class="text-gray-500 font-semibold block">Notification Engine (WhatssApp)</span>
                            @if($systemHealth['whatsapp'])
                                <span class="font-bold text-emerald-600 flex items-center gap-1 mt-0.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Terhubung (Connected)
                                </span>
                            @else
                                <span class="font-bold text-rose-600 flex items-center gap-1 mt-0.5">
                                    <span class="w-2 h-2 rounded-full bg-rose-500"></span> Terputus (Disconnected)
                                </span>
                            @endif
                        </div>
                        <div class="p-2 {{ $systemHealth['whatsapp'] ? 'bg-emerald-100/60 text-emerald-700' : 'bg-rose-100/60 text-rose-700' }} rounded-lg">
                            <svg class="w-4 h-4 stroke-current stroke-2 fill-none" viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                        </div>
                    </div>

                    {{-- DATABASE STATUS --}}
                    <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 flex items-center justify-between">
                        <div>
                            <span class="text-gray-500 font-semibold block">Koneksi Database</span>
                            @if($systemHealth['database'])
                                <span class="font-bold text-emerald-600 flex items-center gap-1 mt-0.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Normal (Connected)
                                </span>
                            @else
                                <span class="font-bold text-rose-600 flex items-center gap-1 mt-0.5">
                                    <span class="w-2 h-2 rounded-full bg-rose-500"></span> Connection Error
                                </span>
                            @endif
                        </div>
                        <div class="p-2 {{ $systemHealth['database'] ? 'bg-blue-100/60 text-blue-700' : 'bg-rose-100/60 text-rose-700' }} rounded-lg">
                            <svg class="w-4 h-4 stroke-current stroke-2 fill-none" viewBox="0 0 24 24"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M21 19c0 1.66-4 3-9 3s-9-1.34-9-3"/></svg>
                        </div>
                    </div>

                    {{-- STORAGE PERMISSION STATUS --}}
                    <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 flex items-center justify-between">
                        <div>
                            <span class="text-gray-500 font-semibold block">File Storage & Signature</span>
                            @if($systemHealth['storage'])
                                <span class="font-bold text-indigo-600 flex items-center gap-1 mt-0.5">
                                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span> Writable (Ready)
                                </span>
                            @else
                                <span class="font-bold text-rose-600 flex items-center gap-1 mt-0.5">
                                    <span class="w-2 h-2 rounded-full bg-rose-500"></span> Permission Denied
                                </span>
                            @endif
                        </div>
                        <div class="p-2 {{ $systemHealth['storage'] ? 'bg-indigo-100/60 text-indigo-700' : 'bg-rose-100/60 text-rose-700' }} rounded-lg">
                            <svg class="w-4 h-4 stroke-current stroke-2 fill-none" viewBox="0 0 24 24"><path d="M20 14.66V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h5.34"/><polygon points="18 2 22 6 12 16 8 16 8 12 18 2"/></svg>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- 2. KARTU METRIK UTAMA OPERASIONAL (PERSISI SAMA DENGAN ADMIN LAP) --}}
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

        {{-- 3. TABEL MONITORING UTAMA (PERSISI SAMA DENGAN ADMIN LAP) --}}
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

                            @if($b->status === 'pending')
                                <span class="px-2.5 py-1 rounded-full font-bold uppercase text-[10px] bg-amber-100 text-amber-800 border border-amber-200">Pending</span>
                            @elseif($b->status === 'waiting')
                                <span class="px-2.5 py-1 rounded-full font-bold uppercase text-[10px] bg-yellow-100 text-yellow-800 border border-yellow-200">Waiting</span>
                            @elseif($b->status === 'approved')
                                <span class="px-2.5 py-1 rounded-full font-bold uppercase text-[10px] bg-blue-100 text-blue-800 border border-blue-200">Approved</span>
                            @elseif($b->status === 'trip_started')
                                <span class="px-2.5 py-1 rounded-full font-bold uppercase text-[10px] bg-emerald-100 text-emerald-800 border border-emerald-200">Trip Started</span>
                            @elseif($b->status === 'on_trip')
                                <span class="px-2.5 py-1 rounded-full font-bold uppercase text-[10px] bg-purple-100 text-purple-800 border border-purple-200">On Trip</span>
                            @elseif($b->status === 'trip_completed')
                                <span class="px-2.5 py-1 rounded-full font-bold uppercase text-[10px] bg-violet-100 text-violet-800 border border-violet-200">Trip Completed</span>
                            @elseif($b->status === 'returned')
                                <span class="px-2.5 py-1 rounded-full font-bold uppercase text-[10px] bg-indigo-100 text-indigo-800 border border-indigo-200">Returned</span>
                            @elseif($b->status === 'checked')
                                <span class="px-2.5 py-1 rounded-full font-bold uppercase text-[10px] bg-cyan-100 text-cyan-800 border border-cyan-200">Checked</span>
                            @elseif($b->status === 'completed')
                                <span class="px-2.5 py-1 rounded-full font-bold uppercase text-[10px] bg-teal-100 text-teal-800 border border-teal-200">Completed</span>
                            @else
                                <span class="px-2.5 py-1 rounded-full font-bold uppercase text-[10px] bg-gray-100 text-gray-700 border border-gray-200">{{ $b->status }}</span>
                            @endif
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
                                
                                @if($d->status === 'draft')
                                    <span class="text-[10px] bg-amber-100 text-amber-800 px-2 py-0.5 rounded font-bold uppercase border border-amber-200">Draft</span>
                                @elseif($d->status === 'approved')
                                    <span class="text-[10px] bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded font-bold uppercase border border-emerald-200">Approved</span>
                                @elseif($d->status === 'converted')
                                    <span class="text-[10px] bg-blue-100 text-blue-800 px-2 py-0.5 rounded font-bold uppercase border border-blue-200">Converted</span>
                                @else
                                    <span class="text-[10px] bg-rose-100 text-rose-800 px-2 py-0.5 rounded font-bold uppercase border border-rose-200">{{ $d->status }}</span>
                                @endif
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