<x-app-layout>
    <x-slot name="title">Detail Peminjaman {{ $booking->letter_number }}</x-slot>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                Detail Peminjaman: {{ $booking->letter_number }}
            </h2>
            <a href="{{ route('bookings.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-bold transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-gray-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"/>
                    <polyline points="12 19 5 12 12 5"/>
                </svg>
                <span>Kembali</span>
            </a>
        </div>
    </x-slot>

    <div class="pt-2 pb-6">
        <div class="max-w-7xl mx-auto space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- HEADER BANNER --}}
            <div class="bg-gradient-to-r from-blue-700 via-indigo-600 to-blue-600 rounded-2xl p-6 sm:p-8 text-white shadow-lg flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <div class="flex items-center gap-5">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center text-white font-bold text-2xl sm:text-3xl border-2 border-white/30 shadow-inner shrink-0">
                        {{ strtoupper(substr($booking->user->name ?? 'P', 0, 1)) }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">{{ $booking->letter_number }}</h1>
                            <span class="px-3 py-1 rounded-full text-xs sm:text-sm font-bold tracking-wider uppercase bg-white/20 backdrop-blur-md text-white border border-white/30">
                                Armada {{ strtoupper($booking->vehicle_type ?? 'Operasional') }}
                            </span>
                        </div>
                        <p class="text-sm sm:text-base text-blue-100 mt-1 flex items-center gap-2 flex-wrap">
                            <span>Pemohon: <strong>{{ $booking->user->name ?? '-' }}</strong></span>
                            <span>•</span>
                            <span>NIP: {{ $booking->user->nip ?? '-' }}</span>
                            <span>•</span>
                            <span class="bg-white/10 px-2.5 py-0.5 rounded-full text-xs sm:text-sm font-medium">{{ $booking->user->department ?? 'Balmon Semarang' }}</span>
                        </p>
                    </div>
                </div>

                {{-- STATUS BADGE HEADER --}}
                <div class="shrink-0">
                    @switch($booking->status)
                        @case('approved')
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-500/20 border border-emerald-300/40 text-emerald-100 font-bold text-sm sm:text-base backdrop-blur-md">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                Disetujui (Approved)
                            </span>
                            @break
                        @case('on_trip')
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-cyan-500/20 border border-cyan-300/40 text-cyan-100 font-bold text-sm sm:text-base backdrop-blur-md">
                                <span class="w-2.5 h-2.5 rounded-full bg-cyan-300 animate-ping"></span>
                                Dalam Perjalanan (On Trip)
                            </span>
                            @break
                        @case('returned')
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-500/20 border border-amber-300/40 text-amber-100 font-bold text-sm sm:text-base backdrop-blur-md">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-bounce"></span>
                                Dikembalikan (Menunggu Verifikasi Admin)
                            </span>
                            @break
                        @case('checked')
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-500/20 border border-indigo-300/40 text-indigo-100 font-bold text-sm sm:text-base backdrop-blur-md">
                                <span class="w-2.5 h-2.5 rounded-full bg-indigo-400"></span>
                                Diverifikasi Admin
                            </span>
                            @break
                        @case('completed')
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-500/20 border border-blue-300/40 text-blue-100 font-bold text-sm sm:text-base backdrop-blur-md">
                                <svg class="w-5 h-5 text-blue-300 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Selesai (Completed)
                            </span>
                            @break
                        @case('rejected')
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-rose-500/20 border border-rose-300/40 text-rose-100 font-bold text-sm sm:text-base backdrop-blur-md">
                                <span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span>
                                Ditolak
                            </span>
                            @break
                        @case('canceled')
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-rose-500/20 border border-rose-300/40 text-rose-100 font-bold text-sm sm:text-base backdrop-blur-md">
                                <span class="w-2.5 h-2.5 rounded-full bg-gray-400"></span>
                                Dibatalkan
                            </span>
                            @break
                        @default
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-500/20 border border-amber-300/40 text-amber-100 font-bold text-sm sm:text-base backdrop-blur-md">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-bounce"></span>
                                Menunggu Persetujuan
                            </span>
                    @endswitch
                </div>
            </div>

            {{-- MAIN CONTENT GRID --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                {{-- KOLOM KIRI --}}
                <div class="lg:col-span-7 space-y-6">

                    {{-- CARD 1: INFORMASI RINCIAN PERJALANAN --}}
                    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 sm:p-7 space-y-5">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                        <line x1="16" y1="13" x2="8" y2="13"/>
                                        <line x1="16" y1="17" x2="8" y2="17"/>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-lg font-bold text-gray-900">Rincian Permohonan Dinas</h2>
                                    <p class="text-xs sm:text-sm text-gray-500">Informasi utama jadwal dan maksud perjalanan</p>
                                </div>
                            </div>

                            @if($booking->letter_file)
                                <a href="{{ asset('storage/' . $booking->letter_file) }}" target="_blank" class="inline-flex items-center gap-2 px-3.5 py-2 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-xl text-xs sm:text-sm font-bold transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                        <polyline points="7 10 12 15 17 10"/>
                                        <line x1="12" y1="15" x2="12" y2="3"/>
                                    </svg>
                                    <span>Lihat Surat Tugas</span>
                                </a>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="text-xs sm:text-sm font-bold text-gray-500 block">Waktu Berangkat</label>
                                <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-200 text-sm sm:text-base font-bold text-gray-900 flex items-center gap-2.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                        <line x1="16" y1="2" x2="16" y2="6"/>
                                        <line x1="8" y1="2" x2="8" y2="6"/>
                                        <line x1="3" y1="10" x2="21" y2="10"/>
                                    </svg>
                                    <span>{{ $booking->start_date ? \Carbon\Carbon::parse($booking->start_date)->locale('id')->translatedFormat('d M Y, H:i') : '-' }} WIB</span>
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-xs sm:text-sm font-bold text-gray-500 block">Waktu Kembali</label>
                                <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-200 text-sm sm:text-base font-bold text-gray-900 flex items-center gap-2.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                        <line x1="16" y1="2" x2="16" y2="6"/>
                                        <line x1="8" y1="2" x2="8" y2="6"/>
                                        <line x1="3" y1="10" x2="21" y2="10"/>
                                    </svg>
                                    <span>{{ $booking->end_date ? \Carbon\Carbon::parse($booking->end_date)->locale('id')->translatedFormat('d M Y, H:i') : '-' }} WIB</span>
                                </div>
                            </div>

                            <div class="sm:col-span-2 space-y-1.5">
                                <label class="text-xs sm:text-sm font-bold text-gray-500 block">Tujuan Perjalanan</label>
                                <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-200 text-sm sm:text-base font-bold text-gray-900 flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <span>{{ $booking->destination }}</span>
                                </div>
                            </div>

                            <div class="sm:col-span-2 space-y-1.5">
                                <label class="text-xs sm:text-sm font-bold text-gray-500 block">Keperluan / Maksud Tugas</label>
                                <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-200 text-sm sm:text-base font-medium text-gray-800 leading-relaxed">
                                    {{ $booking->purpose }}
                                </div>
                            </div>

                            @if($booking->vehicle_requirements)
                                <div class="sm:col-span-2 space-y-1.5">
                                    <label class="text-xs sm:text-sm font-bold text-gray-500 block">Spesifikasi / Kebutuhan Tambahan Armada</label>
                                    <div class="p-3.5 bg-amber-50/70 rounded-xl border border-amber-200 text-xs sm:text-sm font-semibold text-amber-900">
                                        {{ $booking->vehicle_requirements }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- CARD 2: ANGGOTA TIM / PENGIKUT DINAS --}}
                    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 sm:p-7 space-y-4">
                        <div class="flex items-center gap-3 border-b border-gray-100 pb-4">
                            <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-gray-900">Anggota Tim / Pengikut Dinas</h2>
                                <p class="text-xs sm:text-sm text-gray-500">Personel terdaftar dalam perjalanan dinas ini</p>
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
                                    <div class="py-3.5 flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-3.5">
                                            <div class="w-9 h-9 rounded-full bg-indigo-50 text-indigo-700 font-bold text-sm flex items-center justify-center border border-indigo-100 shrink-0">
                                                {{ $idx + 1 }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-gray-900">{{ $pUser->name }}</p>
                                                <p class="text-xs text-gray-500 mt-0.5">
                                                    NIP: {{ $pUser->nip ?? '-' }} • {{ $pUser->jabatan ?? '-' }}
                                                </p>
                                            </div>
                                        </div>
                                        <span class="px-3 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-bold uppercase tracking-wider">
                                            {{ $pUser->role }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs sm:text-sm text-gray-400 italic py-2">Tidak ada anggota tim tambahan (Hanya Pemohon Utama).</p>
                        @endif
                    </div>

                    {{-- CARD 3: LOG OPERASIONAL BBM & SPEEDOMETER --}}
                    @php $log = $booking->log; @endphp
                    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 sm:p-7 space-y-5">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 fill-none stroke-emerald-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="12" y1="20" x2="12" y2="10"/>
                                        <line x1="18" y1="20" x2="18" y2="4"/>
                                        <line x1="6" y1="20" x2="6" y2="16"/>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-lg font-bold text-gray-900">Data Operational Log & Indikator BBM</h2>
                                    <p class="text-xs sm:text-sm text-gray-500">Waktu check-in/out dan bukti foto speedometer</p>
                                </div>
                            </div>
                        </div>

                        @if($log)
                            <div class="space-y-6 text-xs sm:text-sm">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {{-- DATA KEBERANGKATAN --}}
                                    <div class="p-4 bg-emerald-50/50 rounded-xl border border-emerald-100 space-y-3 flex flex-col justify-between">
                                        <div class="space-y-3">
                                            <div class="flex items-center justify-between border-b border-emerald-200/60 pb-2.5">
                                                <span class="font-bold text-emerald-900 text-xs sm:text-sm flex items-center gap-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-emerald-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/>
                                                        <line x1="4" y1="22" x2="4" y2="15"/>
                                                    </svg>
                                                    <span>Keberangkatan</span>
                                                </span>
                                                <span class="text-xs text-emerald-700 font-bold">
                                                    {{ $log->checked_out_at ? \Carbon\Carbon::parse($log->checked_out_at)->locale('id')->translatedFormat('d M Y, H:i') : '-' }} WIB
                                                </span>
                                            </div>

                                            <div class="text-gray-800">
                                                <span class="text-gray-500 block text-xs font-semibold">BBM Awal:</span>
                                                <span class="font-bold text-base sm:text-lg text-gray-900">{{ $log->start_fuel_level ?? '-' }}</span>
                                            </div>
                                        </div>

                                        @if($log->start_photo)
                                            <div class="pt-2">
                                                <span class="text-gray-500 block text-xs font-semibold mb-1">Foto BBM Awal:</span>
                                                <a href="{{ asset('storage/' . $log->start_photo) }}" target="_blank" class="block group relative rounded-lg overflow-hidden border border-emerald-200 bg-gray-100 h-36">
                                                    <img src="{{ asset('storage/' . $log->start_photo) }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-200" alt="Foto Awal">
                                                    <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white font-bold text-xs">
                                                        Klik untuk Memperbesar
                                                    </div>
                                                </a>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- DATA PENGEMBALIAN --}}
                                    <div class="p-4 bg-indigo-50/50 rounded-xl border border-indigo-100 space-y-3 flex flex-col justify-between">
                                        <div class="space-y-3">
                                            <div class="flex items-center justify-between border-b border-indigo-200/60 pb-2.5">
                                                <span class="font-bold text-indigo-900 text-xs sm:text-sm flex items-center gap-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/>
                                                        <line x1="4" y1="22" x2="4" y2="15"/>
                                                    </svg>
                                                    <span>Pengembalian</span>
                                                </span>
                                                <span class="text-xs text-indigo-700 font-bold">
                                                    {{ $log->checked_in_at ? \Carbon\Carbon::parse($log->checked_in_at)->locale('id')->translatedFormat('d M Y, H:i') . ' WIB' : 'Dalam Perjalanan' }}
                                                </span>
                                            </div>

                                            @if($log->checked_in_at)
                                                <div class="text-gray-800">
                                                    <span class="text-gray-500 block text-xs font-semibold">BBM Akhir:</span>
                                                    <span class="font-bold text-base sm:text-lg text-gray-900">{{ $log->end_fuel_level ?? '-' }}</span>
                                                </div>
                                            @endif
                                        </div>

                                        @if($log->checked_in_at && $log->end_photo)
                                            <div class="pt-2">
                                                <span class="text-gray-500 block text-xs font-semibold mb-1">Foto BBM Pengembalian:</span>
                                                <a href="{{ asset('storage/' . $log->end_photo) }}" target="_blank" class="block group relative rounded-lg overflow-hidden border border-indigo-200 bg-gray-100 h-36">
                                                    <img src="{{ asset('storage/' . $log->end_photo) }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-200" alt="Foto Akhir">
                                                    <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white font-bold text-xs">
                                                        Klik untuk Memperbesar
                                                    </div>
                                                </a>
                                            </div>
                                        @elseif(!$log->checked_in_at)
                                            <div class="py-8 text-center text-gray-400 italic text-xs sm:text-sm">
                                                Perjalanan dinas sedang berlangsung. Data pengembalian belum diinput.
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- CATATAN INSIDEN / KERUSAKAN --}}
                                @if($log->condition_notes || !empty($log->condition_photos))
                                    @php
                                        $incidentPhotos = [];
                                        if (!empty($log->condition_photos)) {
                                            $incidentPhotos = is_array($log->condition_photos) ? $log->condition_photos : json_decode($log->condition_photos, true);
                                        }
                                    @endphp
                                    <div class="p-4 bg-rose-50 rounded-xl border border-rose-200 space-y-3">
                                        <span class="font-bold text-rose-900 text-xs sm:text-sm flex items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-rose-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                                                <line x1="12" y1="9" x2="12" y2="13"/>
                                                <line x1="12" y1="17" x2="12.01" y2="17"/>
                                            </svg>
                                            <span>Catatan Kondisi / Kerusakan Selama Perjalanan</span>
                                        </span>
                                        
                                        @if($log->condition_notes)
                                            <p class="text-xs sm:text-sm text-rose-950 bg-white p-3.5 rounded-lg border border-rose-200 leading-relaxed font-medium">
                                                {{ $log->condition_notes }}
                                            </p>
                                        @endif

                                        @if(count($incidentPhotos) > 0)
                                            <div class="space-y-1.5">
                                                <span class="text-xs font-bold text-rose-800">Foto Bukti Kerusakan / Insiden:</span>
                                                <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                                                    @foreach($incidentPhotos as $photoPath)
                                                        <a href="{{ asset('storage/' . $photoPath) }}" target="_blank" class="block rounded-lg overflow-hidden border border-rose-200 h-24 bg-gray-100 hover:opacity-90 transition">
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
                            <p class="text-xs sm:text-sm text-gray-400 italic py-4 text-center bg-gray-50 rounded-xl border border-gray-100">
                                Kendaraan belum melakukan proses keberangkatan (check-out).
                            </p>
                        @endif
                    </div>

                    {{-- CARD 4: RINCIAN TRIP CHECKLIST (PRE-TRIP & POST-TRIP) --}}
                    @php $checklists = $booking->checklists; @endphp
                    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 sm:p-7 space-y-5">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2.5 bg-slate-100 text-slate-700 rounded-xl">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 fill-none stroke-slate-700 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                                        <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                                        <path d="m9 14 2 2 4-4"/>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-lg font-bold text-gray-900">Hasil Physical Check Armada (Checklist)</h2>
                                    <p class="text-xs sm:text-sm text-gray-500">Kondisi fisik detail dan kelengkapan dinas</p>
                                </div>
                            </div>
                        </div>

                        @if($checklists && $checklists->count() > 0)
                            <div class="space-y-4">
                                @foreach($checklists as $chk)
                                    <div class="px-4 py-4 rounded-xl border bg-white space-y-4 shadow-2xs {{ $chk->type === 'start' ? 'border-emerald-200' : 'border-indigo-200' }}">
                                        
                                        <div class="flex justify-between items-center border-b border-gray-100 pb-2.5">
                                            <span class="font-bold text-xs uppercase px-3 py-1 rounded-lg tracking-wide flex items-center gap-1.5 {{ $chk->type === 'start' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-indigo-100 text-indigo-800 border border-indigo-200' }}">
                                                @if($chk->type === 'start')
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-emerald-700 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/>
                                                        <path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-3.05 11a22.35 22.35 0 0 1-3.95 2z"/>
                                                    </svg>
                                                    <span>Keberangkatan (Pre-Trip)</span>
                                                @else
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-indigo-700 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/>
                                                        <line x1="4" y1="22" x2="4" y2="15"/>
                                                    </svg>
                                                    <span>Pengembalian (Post-Trip)</span>
                                                @endif
                                            </span>
                                            
                                            @if($chk->is_washed && $chk->type === 'end')
                                                <span class="text-xs font-bold text-amber-800 bg-amber-50 px-2.5 py-1 rounded-md border border-amber-200 flex items-center gap-1">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-amber-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3z"/>
                                                    </svg>
                                                    <span>Dikembalikan Bersih</span>
                                                </span>
                                            @endif
                                        </div>

                                        {{-- KONDISI FISIK KENDARAAN --}}
                                        <div class="space-y-2">
                                            <span class="font-bold text-gray-800 text-xs sm:text-sm block border-b border-gray-100 pb-1 flex items-center gap-1.5">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="11" cy="11" r="8"/>
                                                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                                </svg>
                                                <span>Kondisi Fisik Kendaraan:</span>
                                            </span>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-gray-700 text-xs sm:text-sm">
                                                <div class="flex items-center justify-between py-1 border-b border-gray-50">
                                                    <span>Bodi & Cat Mulus</span>
                                                    <span class="font-bold flex items-center gap-1.5 {{ $chk->body_condition ? 'text-emerald-600' : 'text-rose-600' }}">
                                                        @if($chk->body_condition)
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                                            <span>Mulus</span>
                                                        @else
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                            <span>Bermasalah</span>
                                                        @endif
                                                    </span>
                                                </div>
                                                <div class="flex items-center justify-between py-1 border-b border-gray-50">
                                                    <span>Kondisi Ban Baik</span>
                                                    <span class="font-bold flex items-center gap-1.5 {{ $chk->tires_condition ? 'text-emerald-600' : 'text-rose-600' }}">
                                                        @if($chk->tires_condition)
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                                            <span>Baik</span>
                                                        @else
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                            <span>Bermasalah</span>
                                                        @endif
                                                    </span>
                                                </div>
                                                <div class="flex items-center justify-between py-1 border-b border-gray-50">
                                                    <span>Lampu-Lampu Nyala</span>
                                                    <span class="font-bold flex items-center gap-1.5 {{ $chk->lights_condition ? 'text-emerald-600' : 'text-rose-600' }}">
                                                        @if($chk->lights_condition)
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                                            <span>Nyala</span>
                                                        @else
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                            <span>Mati/Meredup</span>
                                                        @endif
                                                    </span>
                                                </div>
                                                <div class="flex items-center justify-between py-1 border-b border-gray-50">
                                                    <span>Kaca & Spion Utuh</span>
                                                    <span class="font-bold flex items-center gap-1.5 {{ $chk->glass_mirrors ? 'text-emerald-600' : 'text-rose-600' }}">
                                                        @if($chk->glass_mirrors)
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                                            <span>Utuh</span>
                                                        @else
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                            <span>Retak/Pecah</span>
                                                        @endif
                                                    </span>
                                                </div>
                                                <div class="flex items-center justify-between py-1 border-b border-gray-50">
                                                    <span>Fungsi Rem Normal</span>
                                                    <span class="font-bold flex items-center gap-1.5 {{ $chk->brakes_condition ? 'text-emerald-600' : 'text-rose-600' }}">
                                                        @if($chk->brakes_condition)
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                                            <span>Normal</span>
                                                        @else
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                            <span>Bermasalah</span>
                                                        @endif
                                                    </span>
                                                </div>
                                                <div class="flex items-center justify-between py-1 border-b border-gray-50">
                                                    <span>Kebersihan Kabin</span>
                                                    <span class="font-bold flex items-center gap-1.5 {{ $chk->interior_cleanliness ? 'text-emerald-600' : 'text-rose-600' }}">
                                                        @if($chk->interior_cleanliness)
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                                            <span>Bersih</span>
                                                        @else
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                            <span>Kotor</span>
                                                        @endif
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- KELENGKAPAN SURAT & PERALATAN --}}
                                        <div class="space-y-2 pt-1">
                                            <span class="font-bold text-gray-800 text-xs sm:text-sm block border-b border-gray-100 pb-1 flex items-center gap-1.5">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                    <polyline points="14 2 14 8 20 8"/>
                                                </svg>
                                                <span>Kelengkapan Surat & Peralatan Dinas:</span>
                                            </span>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-gray-700 text-xs sm:text-sm">
                                                <div class="flex items-center justify-between py-1 border-b border-gray-50">
                                                    <span>STNK Fisik</span>
                                                    <span class="font-bold flex items-center gap-1.5 {{ $chk->has_stnk ? 'text-emerald-600' : 'text-rose-600' }}">
                                                        @if($chk->has_stnk)
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                                            <span>Ada</span>
                                                        @else
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                            <span>Tidak Ada</span>
                                                        @endif
                                                    </span>
                                                </div>
                                                <div class="flex items-center justify-between py-1 border-b border-gray-50">
                                                    <span>Ban Serep</span>
                                                    <span class="font-bold flex items-center gap-1.5 {{ $chk->has_spare_tire ? 'text-emerald-600' : 'text-rose-600' }}">
                                                        @if($chk->has_spare_tire)
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                                            <span>Ada</span>
                                                        @else
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                            <span>Tidak Ada</span>
                                                        @endif
                                                    </span>
                                                </div>
                                                <div class="flex items-center justify-between py-1 border-b border-gray-50">
                                                    <span>Dongkrak & Tools</span>
                                                    <span class="font-bold flex items-center gap-1.5 {{ $chk->has_jack_tools ? 'text-emerald-600' : 'text-rose-600' }}">
                                                        @if($chk->has_jack_tools)
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                                            <span>Ada</span>
                                                        @else
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                            <span>Tidak Ada</span>
                                                        @endif
                                                    </span>
                                                </div>
                                                <div class="flex items-center justify-between py-1 border-b border-gray-50">
                                                    <span>Kotak P3K</span>
                                                    <span class="font-bold flex items-center gap-1.5 {{ $chk->has_first_aid ? 'text-emerald-600' : 'text-rose-600' }}">
                                                        @if($chk->has_first_aid)
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                                            <span>Ada</span>
                                                        @else
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                            <span>Tidak Ada</span>
                                                        @endif
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- LAMPIRAN NOTA CUCI MOBIL --}}
                                        @if($chk->wash_receipt_photo)
                                            <div class="pt-2 border-t border-gray-100 space-y-2">
                                                <span class="text-xs font-bold text-amber-800 flex items-center gap-1.5">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-amber-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                                    <span>Lampiran Bukti Nota Cuci Mobil:</span>
                                                </span>
                                                <a href="{{ asset('storage/' . $chk->wash_receipt_photo) }}" target="_blank" class="inline-block">
                                                    <img src="{{ asset('storage/' . $chk->wash_receipt_photo) }}" class="h-32 w-auto object-cover rounded-xl border border-amber-300 hover:opacity-90 transition shadow-2xs">
                                                </a>
                                            </div>
                                        @endif

                                        {{-- CATATAN KENDALA CHECKLIST --}}
                                        @if($chk->notes)
                                            <div class="p-3.5 bg-rose-50 text-rose-800 rounded-xl border border-rose-200 text-xs sm:text-sm space-y-1">
                                                <strong class="font-bold flex items-center gap-1.5">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-rose-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                                    <span>Catatan Fisik / Temuan:</span>
                                                </strong>
                                                <p class="leading-relaxed font-medium">{{ $chk->notes }}</p>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs sm:text-sm text-gray-400 italic py-4 text-center bg-gray-50 rounded-xl border border-gray-100">
                                Belum ada catatan physical check yang diisi.
                            </p>
                        @endif
                    </div>

                </div>

                {{-- KOLOM KANAN --}}
                <div class="lg:col-span-5 space-y-6">

                    {{-- CARD 5: UNIT KENDARAAN & DRIVER --}}
                    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 sm:p-7 space-y-4">
                        <div class="flex items-center gap-3 border-b border-gray-100 pb-4">
                            <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A2 2 0 0 0 2 12v4c0 .6.4 1 1 1h2"/>
                                    <circle cx="7" cy="17" r="2"/>
                                    <path d="M9 17h6"/>
                                    <circle cx="17" cy="17" r="2"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-gray-900">Unit Kendaraan & Driver</h2>
                                <p class="text-xs sm:text-sm text-gray-500">Informasi armada dan penunjukan pengemudi</p>
                            </div>
                        </div>

                        @if($booking->vehicle)
                            <div class="py-4 px-4 bg-indigo-50/50 rounded-xl border border-indigo-100 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-indigo-900 uppercase tracking-wider">{{ $booking->vehicle->type ?? 'Armada' }}</span>
                                    <span class="px-3 py-1 bg-indigo-600 text-white rounded-lg text-sm font-extrabold tracking-widest font-mono shadow-2xs">
                                        {{ $booking->vehicle->plate_number }}
                                    </span>
                                </div>

                                <div>
                                    <p class="text-lg sm:text-xl font-extrabold text-gray-900">{{ $booking->vehicle->brand }} {{ $booking->vehicle->model }}</p>
                                    @if(!empty($booking->vehicle->color))
                                        <p class="text-xs sm:text-sm text-gray-600 font-medium mt-0.5">Warna: {{ $booking->vehicle->color }}</p>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="px-4 py-4 bg-amber-50 rounded-xl border border-amber-200/80 text-amber-900 text-xs sm:text-sm leading-relaxed space-y-1">
                                <p class="font-bold flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-amber-700 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                    <span>Penentuan Unit Belum Di-Plot</span>
                                </p>
                                <p class="text-amber-800">Peminjaman tipe <strong>Operasional</strong> akan ditentukan oleh Admin Lapangan sebelum jadwal keberangkatan.</p>
                            </div>
                        @endif

                        <div class="pt-3 border-t border-gray-100">
                            <span class="text-xs sm:text-sm font-bold text-gray-600 block mb-2">Driver / Pengemudi Ditunjuk:</span>
                            @if($booking->driver)
                                <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-200 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-indigo-600 text-white font-bold text-xs flex items-center justify-center shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                                                <circle cx="12" cy="7" r="4"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-gray-900">{{ $booking->driver->name }}</p>
                                            <p class="text-xs text-gray-500 mt-0.5">{{ $booking->driver->phone ?? 'Tidak ada No WA' }}</p>
                                        </div>
                                    </div>
                                    @if($booking->driver->phone)
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $booking->driver->phone) }}" target="_blank" class="px-3 py-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 text-xs font-bold rounded-lg transition border border-emerald-200 flex items-center gap-1.5">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-emerald-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                            </svg>
                                            <span>Chat WA</span>
                                        </a>
                                    @endif
                                </div>
                            @else
                                <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-200 text-xs sm:text-sm font-bold text-gray-700">
                                    Lepas Kunci (Tanpa Driver)
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- CARD 6: CATATAN VERIFIKASI & DOKUMEN CETAK BAST --}}
                    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 sm:p-7 space-y-5">
                        <div class="flex items-center gap-3 border-b border-gray-100 pb-4">
                            <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                    <path d="m9 12 2 2 4-4"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-gray-900">Catatan Verifikasi & Berkas Cetak</h2>
                                <p class="text-xs sm:text-sm text-gray-500">Histori persetujuan dan pengunduhan dokumen cetak</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="space-y-1.5">
                                <label class="text-xs sm:text-sm font-bold text-gray-600 block">Catatan Admin / Verifikator</label>
                                <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-200 text-xs sm:text-sm text-gray-700 italic leading-relaxed font-medium">
                                    {{ $booking->admin_note ?? 'Belum ada catatan administratif.' }}
                                </div>
                            </div>

                            <div class="space-y-2.5 pt-3 border-t border-gray-100">
                                <label class="text-xs sm:text-sm font-bold text-gray-800 block">Cetak Dokumen & Berita Acara (BAST):</label>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs sm:text-sm">
                                    <a href="{{ route('bookings.pdf.permohonan', $booking->letter_slug) }}" target="_blank"
                                    class="flex items-center gap-2 px-3.5 py-2.5 bg-gray-50 hover:bg-indigo-50 hover:text-indigo-700 text-gray-700 font-bold rounded-xl border border-gray-200 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                        <span>Formulir Peminjaman</span>
                                    </a>

                                    @if(in_array($booking->status, ['waiting', 'approved', 'on_trip', 'returned', 'checked', 'completed']))
                                        <a href="{{ route('bookings.pdf.bast_peminjaman', $booking->letter_slug) }}" target="_blank" 
                                        class="flex items-center gap-2 px-3.5 py-2.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold rounded-xl border border-emerald-200 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-emerald-700 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
                                            <span>BAST Peminjaman</span>
                                        </a>
                                    @else
                                        <button disabled class="flex items-center gap-2 px-3.5 py-2.5 bg-gray-100 text-gray-400 font-bold rounded-xl border border-gray-200 cursor-not-allowed w-full text-left">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-gray-400 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
                                            <span>BAST Peminjaman</span>
                                        </button>
                                    @endif

                                    @if(in_array($booking->status, ['returned', 'checked', 'completed']))
                                        <a href="{{ route('bookings.pdf.formulir_pengembalian', $booking->letter_slug) }}" target="_blank" 
                                        class="flex items-center gap-2 px-3.5 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-800 font-bold rounded-xl border border-indigo-200 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-indigo-700 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            <span>Formulir Pengembalian</span>
                                        </a>
                                    @else
                                        <button disabled class="flex items-center gap-2 px-3.5 py-2.5 bg-gray-100 text-gray-400 font-bold rounded-xl border border-gray-200 cursor-not-allowed w-full text-left">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-gray-400 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            <span>Formulir Pengembalian</span>
                                        </button>
                                    @endif

                                    @if(in_array($booking->status, ['returned', 'checked', 'completed']))
                                        <a href="{{ route('bookings.pdf.bast_pengembalian', $booking->letter_slug) }}" target="_blank" 
                                        class="flex items-center gap-2 px-3.5 py-2.5 bg-blue-50 hover:bg-blue-100 text-blue-800 font-bold rounded-xl border border-blue-200 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-blue-700 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
                                            <span>BAST Pengembalian</span>
                                        </a>
                                    @else
                                        <button disabled class="flex items-center gap-2 px-3.5 py-2.5 bg-gray-100 text-gray-400 font-bold rounded-xl border border-gray-200 cursor-not-allowed w-full text-left">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-gray-400 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
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