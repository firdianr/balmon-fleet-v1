<x-app-layout>
    <x-slot name="title">Detail Peminjaman {{ $booking->letter_number }}</x-slot>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Detail Peminjaman: {{ $booking->letter_number }}
            </h2>
            <a href="{{ route('bookings.index') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm font-semibold transition">
                Kembali
            </a>
        </div>
    </x-slot>

    <div class="pt-2 pb-6">
        <div class="max-w-7xl mx-auto space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- HEADER BANNER (DESAIN SESUAI HALAMAN PROFIL) --}}
            <div class="bg-gradient-to-r from-blue-700 via-indigo-600 to-blue-600 rounded-2xl p-6 sm:p-8 text-white shadow-lg flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <div class="flex items-center gap-5">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center text-white font-bold text-2xl sm:text-3xl border-2 border-white/30 shadow-inner flex-shrink-0">
                        {{ strtoupper(substr($booking->user->name ?? 'P', 0, 1)) }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-xl sm:text-2xl font-bold tracking-tight">{{ $booking->letter_number }}</h1>
                            <span class="px-3 py-1 rounded-full text-xs font-bold tracking-wider uppercase bg-white/20 backdrop-blur-md text-white border border-white/30">
                                Armada {{ strtoupper($booking->vehicle_type) }}
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm text-blue-100 mt-1 flex items-center gap-2 flex-wrap">
                            <span>Pemohon: <strong>{{ $booking->user->name ?? '-' }}</strong></span>
                            <span>•</span>
                            <span>NIP: {{ $booking->user->nip ?? '-' }}</span>
                            <span>•</span>
                            <span class="bg-white/10 px-2.5 py-0.5 rounded-full text-xs">{{ $booking->user->department ?? 'Balmon Semarang' }}</span>
                        </p>
                    </div>
                </div>

                {{-- STATUS BADGE HEADER --}}
                <div class="flex-shrink-0">
                    @switch($booking->status)
                        @case('approved')
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-500/20 border border-emerald-300/40 text-emerald-100 font-bold text-xs sm:text-sm backdrop-blur-md">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                Disetujui (Approved)
                            </span>
                            @break
                        @case('on_trip')
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-cyan-500/20 border border-cyan-300/40 text-cyan-100 font-bold text-xs sm:text-sm backdrop-blur-md">
                                <span class="w-2.5 h-2.5 rounded-full bg-purple-400 animate-ping"></span>
                                Dalam Perjalanan
                            </span>
                            @break
                        @case('completed')
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-500/20 border border-blue-300/40 text-blue-100 font-bold text-xs sm:text-sm backdrop-blur-md">
                                <svg class="w-4 h-4 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Selesai (Completed)
                            </span>
                            @break
                        @case('rejected')
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-rose-500/20 border border-rose-300/40 text-rose-100 font-bold text-xs sm:text-sm backdrop-blur-md">
                                <span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span>
                                Ditolak
                            </span>
                            @break
                        @case('canceled')
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-rose-500/20 border border-rose-300/40 text-rose-100 font-bold text-xs sm:text-sm backdrop-blur-md">
                                <span class="w-2.5 h-2.5 rounded-full bg-gray-400"></span>
                                Dibatalkan
                            </span>
                            @break
                        @default
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-500/20 border border-amber-300/40 text-amber-100 font-bold text-xs sm:text-sm backdrop-blur-md">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-bounce"></span>
                                Menunggu Persetujuan
                            </span>
                    @endswitch
                </div>
            </div>

            {{-- MAIN CONTENT GRID --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                {{-- KOLOM KIRI: RINCIAN PERMOHONAN, ANGGOTA TIM, & LOG PERJALANAN (7 KOLOM) --}}
                <div class="lg:col-span-7 space-y-6">

                    {{-- CARD 1: INFORMASI RINCIAN PERJALANAN --}}
                    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 space-y-5">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                            <div class="flex items-center gap-2">
                                <div class="p-2 bg-indigo-50 text-indigo-600 rounded-lg">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a1 1 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-gray-900">Rincian Permohonan Dinas</h2>
                                    <p class="text-xs text-gray-500">Informasi utama jadwal dan maksud perjalanan</p>
                                </div>
                            </div>

                            @if($booking->letter_file)
                                <a href="{{ asset('storage/' . $booking->letter_file) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-lg text-xs font-bold transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a1 1 0 01-2 2z"/>
                                    </svg>
                                    <span>Lihat Surat Tugas</span>
                                </a>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- DATES --}}
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-500 block">Waktu Berangkat</label>
                                <div class="p-3 bg-gray-50 rounded-xl border border-gray-200 text-sm font-bold text-gray-900 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-indigo-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span>{{ $booking->start_date ? $booking->start_date->format('d/m/Y H:i') : '-' }} WIB</span>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-500 block">Waktu Kembali</label>
                                <div class="p-3 bg-gray-50 rounded-xl border border-gray-200 text-sm font-bold text-gray-900 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-indigo-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span>{{ $booking->end_date ? $booking->end_date->format('d/m/Y H:i') : '-' }} WIB</span>
                                </div>
                            </div>

                            {{-- DESTINATION --}}
                            <div class="sm:col-span-2 space-y-1">
                                <label class="text-xs font-semibold text-gray-500 block">Tujuan Perjalanan</label>
                                <div class="p-3 bg-gray-50 rounded-xl border border-gray-200 text-sm font-bold text-gray-900">
                                    {{ $booking->destination }}
                                </div>
                            </div>

                            {{-- PURPOSE --}}
                            <div class="sm:col-span-2 space-y-1">
                                <label class="text-xs font-semibold text-gray-500 block">Keperluan / Maksud Tugas</label>
                                <div class="p-3 bg-gray-50 rounded-xl border border-gray-200 text-sm text-gray-800 leading-relaxed whitespace-pre-line">
                                    {{ $booking->purpose }}
                                </div>
                            </div>

                            {{-- VEHICLE REQUIREMENTS (IF ANY) --}}
                            @if($booking->vehicle_requirements)
                                <div class="sm:col-span-2 space-y-1">
                                    <label class="text-xs font-semibold text-gray-500 block">Spesifikasi / Kebutuhan Tambahan Armada</label>
                                    <div class="p-3 bg-amber-50/60 rounded-xl border border-amber-200 text-xs font-medium text-amber-900">
                                        {{ $booking->vehicle_requirements }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- CARD 2: ANGGOTA TIM / PENGIKUT DINAS --}}
                    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 space-y-4">
                        <div class="flex items-center gap-2 border-b border-gray-100 pb-3">
                            <div class="p-2 bg-indigo-50 text-indigo-600 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5-3.562M9 20H4v-2a3 3 0 015-3.562M12 14a4 4 0 100-8 4 4 0 000 8z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-gray-900">Anggota Tim / Pengikut Dinas</h2>
                                <p class="text-xs text-gray-500">Personel terdaftar dalam perjalanan dinas ini</p>
                            </div>
                        </div>

                        @php
                            $participantUsers = [];
                            if (!empty($booking->participants)) {
                                $participantUsers = \App\Models\User::whereIn('id', $booking->participants)->get();
                            }
                        @endphp

                        @if(count($participantUsers) > 0)
                            <div class="divide-y divide-gray-100">
                                @foreach($participantUsers as $idx => $pUser)
                                    <div class="py-3 flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-700 font-bold text-xs flex items-center justify-center border border-indigo-100">
                                                {{ $idx + 1 }}
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-gray-900">{{ $pUser->name }}</p>
                                                <p class="text-[11px] text-gray-500">
                                                    NIP: {{ $pUser->nip ?? '-' }} • {{ $pUser->jabatan ?? '-' }}
                                                </p>
                                            </div>
                                        </div>
                                        <span class="px-2.5 py-1 rounded-md bg-gray-100 text-gray-600 text-[10px] font-bold uppercase">
                                            {{ $pUser->role }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-gray-400 italic py-2">Tidak ada anggota tim tambahan (Hanya Pemohon Utama).</p>
                        @endif
                    </div>

                    {{-- CARD 3: LOG & PERJALANAN FISIK ARMADA (KM, BBM, FOTO, KERUSAKAN) --}}
                    @php $log = $booking->log; @endphp
                    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 space-y-5">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                            <div class="flex items-center gap-2">
                                <div class="p-2 bg-emerald-50 text-emerald-600 rounded-lg">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-gray-900">Data Operasional Perjalanan Armada</h2>
                                    <p class="text-xs text-gray-500">Log kilometer, kondisi fisik, BBM, dan riwayat pengembalian</p>
                                </div>
                            </div>
                            @if($log && $log->distance_traveled)
                                <span class="px-3 py-1 bg-emerald-100 text-emerald-800 font-extrabold text-xs rounded-full border border-emerald-200">
                                    Total Tempuh: {{ number_format($log->distance_traveled, 0, ',', '.') }} KM
                                </span>
                            @endif
                        </div>

                        @if($log)
                            <div class="space-y-6 text-xs">
                                {{-- GRID STATUS BIFURKASI: KEBERANGKATAN & PENGEMBALIAN --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {{-- DATA KEBERANGKATAN --}}
                                    <div class="p-4 bg-emerald-50/50 rounded-xl border border-emerald-100 space-y-3">
                                        <div class="flex items-center justify-between border-b border-emerald-200/60 pb-2">
                                            <span class="font-bold text-emerald-900 text-xs flex items-center gap-1.5">
                                                <span>🚀</span> Keberangkatan (Check-out)
                                            </span>
                                            <span class="text-[10px] text-emerald-700 font-medium">
                                                {{ $log->checked_out_at ? \Carbon\Carbon::parse($log->checked_out_at)->format('d/m/Y H:i') : '-' }} WIB
                                            </span>
                                        </div>

                                        <div class="grid grid-cols-2 gap-2 text-gray-800">
                                            <div>
                                                <span class="text-gray-500 block text-[10px]">KM Awal:</span>
                                                <span class="font-bold text-sm text-gray-900">{{ number_format($log->start_km, 0, ',', '.') }} KM</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500 block text-[10px]">BBM Awal:</span>
                                                <span class="font-bold text-sm text-gray-900">{{ $log->start_fuel_level ?? '-' }}</span>
                                            </div>
                                        </div>

                                        @if($log->start_photo)
                                            <div class="pt-2">
                                                <span class="text-gray-500 block text-[10px] mb-1">Foto Odometer / Kondisi Awal:</span>
                                                <a href="{{ asset('storage/' . $log->start_photo) }}" target="_blank" class="block group relative rounded-lg overflow-hidden border border-emerald-200 bg-gray-100 h-28">
                                                    <img src="{{ asset('storage/' . $log->start_photo) }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-200" alt="Foto Awal">
                                                    <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white font-bold text-[10px]">
                                                        Klik untuk Memperbesar
                                                    </div>
                                                </a>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- DATA PENGEMBALIAN --}}
                                    <div class="p-4 bg-indigo-50/50 rounded-xl border border-indigo-100 space-y-3">
                                        <div class="flex items-center justify-between border-b border-indigo-200/60 pb-2">
                                            <span class="font-bold text-indigo-900 text-xs flex items-center gap-1.5">
                                                <span>🏁</span> Pengembalian (Check-in)
                                            </span>
                                            <span class="text-[10px] text-indigo-700 font-medium">
                                                {{ $log->checked_in_at ? \Carbon\Carbon::parse($log->checked_in_at)->format('d/m/Y H:i') : 'Dalam Perjalanan' }}
                                            </span>
                                        </div>

                                        @if($log->checked_in_at)
                                            <div class="grid grid-cols-2 gap-2 text-gray-800">
                                                <div>
                                                    <span class="text-gray-500 block text-[10px]">KM Akhir:</span>
                                                    <span class="font-bold text-sm text-gray-900">{{ number_format($log->end_km, 0, ',', '.') }} KM</span>
                                                </div>
                                                <div>
                                                    <span class="text-gray-500 block text-[10px]">BBM Akhir:</span>
                                                    <span class="font-bold text-sm text-gray-900">{{ $log->end_fuel_level ?? '-' }}</span>
                                                </div>
                                            </div>

                                            @if($log->end_photo)
                                                <div class="pt-2">
                                                    <span class="text-gray-500 block text-[10px] mb-1">Foto Odometer / Pengembalian:</span>
                                                    <a href="{{ asset('storage/' . $log->end_photo) }}" target="_blank" class="block group relative rounded-lg overflow-hidden border border-indigo-200 bg-gray-100 h-28">
                                                        <img src="{{ asset('storage/' . $log->end_photo) }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-200" alt="Foto Akhir">
                                                        <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white font-bold text-[10px]">
                                                            Klik untuk Memperbesar
                                                        </div>
                                                    </a>
                                                </div>
                                            @endif
                                        @else
                                            <div class="py-6 text-center text-gray-400 italic">
                                                Perjalanan dinas sedang berlangsung. Data pengembalian belum diinput.
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- CATATAN INSIDEN / KERUSAKAN (JIKA ADA) --}}
                                @if($log->condition_notes || !empty($log->condition_photos))
                                    @php
                                        $incidentPhotos = [];
                                        if (!empty($log->condition_photos)) {
                                            $incidentPhotos = is_array($log->condition_photos) ? $log->condition_photos : json_decode($log->condition_photos, true);
                                        }
                                    @endphp
                                    <div class="p-4 bg-rose-50 rounded-xl border border-rose-200 space-y-3">
                                        <span class="font-bold text-rose-900 text-xs flex items-center gap-1.5">
                                            <span>⚠️</span> Catatan Kondisi / Kerusakan Selama Perjalanan
                                        </span>
                                        
                                        @if($log->condition_notes)
                                            <p class="text-xs text-rose-950 bg-white p-3 rounded-lg border border-rose-200 leading-relaxed">
                                                {{ $log->condition_notes }}
                                            </p>
                                        @endif

                                        @if(count($incidentPhotos) > 0)
                                            <div class="space-y-1">
                                                <span class="text-[10px] font-bold text-rose-800">Foto Bukti Kerusakan / Insiden:</span>
                                                <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                                                    @foreach($incidentPhotos as $photoPath)
                                                        <a href="{{ asset('storage/' . $photoPath) }}" target="_blank" class="block rounded-lg overflow-hidden border border-rose-200 h-20 bg-gray-100 hover:opacity-90 transition">
                                                            <img src="{{ asset('storage/' . $photoPath) }}" class="w-full h-full object-cover" alt="Foto Kerusakan">
                                                        </a>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @else
                            <p class="text-xs text-gray-400 italic py-3 text-center bg-gray-50 rounded-xl border border-gray-100">
                                Kendaraan belum melakukan proses keberangkatan (check-out).
                            </p>
                        @endif
                    </div>

                </div>

                {{-- KOLOM KANAN: STATUS ARMADA, DRIVER, CATATAN & TOMBOL CETAK BAST (5 KOLOM) --}}
                <div class="lg:col-span-5 space-y-6">

                    {{-- CARD 4: UNIT KENDARAAN & DRIVER --}}
                    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 space-y-4">
                        <div class="flex items-center gap-2 border-b border-gray-100 pb-3">
                            <div class="p-2 bg-indigo-50 text-indigo-600 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 17a2 2 0 100 4 2 2 0 000-4zm8 0a2 2 0 100 4 2 2 0 000-4zM3 9h18M5 13h14M3 13l2 4h14l2-4"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-gray-900">Unit Kendaraan & Driver</h2>
                                <p class="text-xs text-gray-500">Informasi armada dan penunjukan pengemudi</p>
                            </div>
                        </div>

                        @if($booking->vehicle)
                            <div class="p-4 bg-indigo-50/50 rounded-xl border border-indigo-100 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-indigo-900 uppercase tracking-wider">{{ $booking->vehicle->type ?? 'Armada' }}</span>
                                    <span class="px-2.5 py-1 bg-indigo-600 text-white rounded-lg text-xs font-extrabold tracking-widest font-mono">
                                        {{ $booking->vehicle->plate_number }}
                                    </span>
                                </div>

                                <div>
                                    <p class="text-base font-extrabold text-gray-900">{{ $booking->vehicle->brand }} {{ $booking->vehicle->model }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Tahun {{ $booking->vehicle->year ?? '-' }} • {{ $booking->vehicle->color ?? '-' }}</p>
                                </div>
                            </div>
                        @else
                            <div class="p-4 bg-amber-50 rounded-xl border border-amber-200/80 text-amber-900 text-xs leading-relaxed space-y-1">
                                <p class="font-bold">🚗 Penentuan Unit Belum Di-Plot</p>
                                <p class="text-amber-800">Peminjaman tipe <strong>Operasional</strong> akan ditentukan oleh Admin Lapangan sebelum jadwal keberangkatan.</p>
                            </div>
                        @endif

                        {{-- INFORMASI DRIVER --}}
                        <div class="pt-2 border-t border-gray-100">
                            <span class="text-xs font-semibold text-gray-500 block mb-2">Driver / Pengemudi Ditunjuk:</span>
                            @if($booking->driver)
                                <div class="p-3 bg-gray-50 rounded-xl border border-gray-200 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full bg-indigo-600 text-white font-bold text-xs flex items-center justify-center">
                                            🚖
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-gray-900">{{ $booking->driver->name }}</p>
                                            <p class="text-[10px] text-gray-500">{{ $booking->driver->phone ?? 'Tidak ada No WA' }}</p>
                                        </div>
                                    </div>
                                    @if($booking->driver->phone)
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $booking->driver->phone) }}" target="_blank" class="px-2.5 py-1 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 text-[10px] font-bold rounded-lg transition border border-emerald-200">
                                            Chat WA
                                        </a>
                                    @endif
                                </div>
                            @else
                                <div class="p-3 bg-gray-50 rounded-xl border border-gray-200 text-xs font-bold text-gray-600">
                                    Lepas Kunci (Tanpa Driver)
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- CARD 5: CATATAN ADMIN & DOKUMEN CETAK BAST --}}
                    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 space-y-5">
                        <div class="flex items-center gap-2 border-b border-gray-100 pb-3">
                            <div class="p-2 bg-indigo-50 text-indigo-600 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-gray-900">Catatan Verifikasi & Berkas Cetak</h2>
                                <p class="text-xs text-gray-500">Histori persetujuan dan pengunduhan dokumen cetak</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            {{-- ADMIN NOTE --}}
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-500 block">Catatan Admin / Verifikator</label>
                                <div class="p-3 bg-gray-50 rounded-xl border border-gray-200 text-xs text-gray-700 italic">
                                    {{ $booking->admin_note ?? 'Belum ada catatan administratif.' }}
                                </div>
                            </div>

                            {{-- SEKSI TOMBOL CETAK DOKUMEN BAST --}}
                            <div class="space-y-2 pt-2 border-t border-gray-100">
                                <label class="text-xs font-bold text-gray-800 block">Cetak Dokumen & Berita Acara (BAST):</label>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                    {{-- 1. FORMULIR PERMOHONAN PEMINJAMAN --}}
                                    <a href="{{ route('bookings.pdf.permohonan', $booking->letter_slug) }}" target="_blank"
                                    class="flex items-center gap-2 px-3 py-2 bg-gray-50 hover:bg-indigo-50 hover:text-indigo-700 text-gray-700 font-bold rounded-xl border border-gray-200 transition">
                                        <span>📄</span>
                                        <span>Formulir Peminjaman</span>
                                    </a>

                                    {{-- 2. BAST PEMINJAMAN (AKTIF JIKA APPROVED, ON_TRIP, COMPLETED) --}}
                                    @if(in_array($booking->status, ['approved', 'on_trip', 'completed']))
                                        <a href="{{ route('bookings.pdf.bast_peminjaman', $booking->letter_slug) }}" target="_blank" 
                                        class="flex items-center gap-2 px-3 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold rounded-xl border border-emerald-200 transition">
                                            <span>🔑</span>
                                            <span>BAST Peminjaman</span>
                                        </a>
                                    @else
                                        <button disabled class="flex items-center gap-2 px-3 py-2 bg-gray-100 text-gray-400 font-bold rounded-xl border border-gray-200 cursor-not-allowed w-full text-left">
                                            <span>🔑</span>
                                            <span>BAST Peminjaman</span>
                                        </button>
                                    @endif

                                    {{-- 3. FORMULIR PENGEMBALIAN (AKTIF JIKA COMPLETED) --}}
                                    @if($booking->status === 'completed')
                                        <a href="{{ route('bookings.pdf.formulir_pengembalian', $booking->letter_slug) }}" target="_blank" 
                                        class="flex items-center gap-2 px-3 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-800 font-bold rounded-xl border border-indigo-200 transition">
                                            <span>📝</span>
                                            <span>Formulir Pengembalian</span>
                                        </a>
                                    @else
                                        <button disabled class="flex items-center gap-2 px-3 py-2 bg-gray-100 text-gray-400 font-bold rounded-xl border border-gray-200 cursor-not-allowed w-full text-left">
                                            <span>📝</span>
                                            <span>Formulir Pengembalian</span>
                                        </button>
                                    @endif

                                    {{-- 4. BAST PENGEMBALIAN (AKTIF JIKA COMPLETED) --}}
                                    @if($booking->status === 'completed')
                                        <a href="{{ route('bookings.pdf.bast_pengembalian', $booking->letter_slug) }}" target="_blank" 
                                        class="flex items-center gap-2 px-3 py-2 bg-blue-50 hover:bg-blue-100 text-blue-800 font-bold rounded-xl border border-blue-200 transition">
                                            <span>📋</span>
                                            <span>BAST Pengembalian</span>
                                        </a>
                                    @else
                                        <button disabled class="flex items-center gap-2 px-3 py-2 bg-gray-100 text-gray-400 font-bold rounded-xl border border-gray-200 cursor-not-allowed w-full text-left">
                                            <span>📋</span>
                                            <span>BAST Pengembalian</span>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </div>
</x-app-layout>