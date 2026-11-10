<x-app-layout>
    <x-slot name="title">Dashboard Pemohon</x-slot>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="font-bold text-xl text-gray-800 leading-tight">Dashboard Layanan Permohonan Armada</h2>
                <p class="text-xs text-gray-500 mt-0.5">Kelola request armada dinas dan sahkan pengesahan TTD log perjalanan.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        {{-- DUA TAHAP ACTION ALERTS FOR PEMOHON --}}

        {{-- 1. ALERT TTD KEBERANGKATAN (trip_started -> on_trip) --}}
        @if(isset($pemohonPendingDepartureSign))
            <div class="p-4 bg-purple-50 border border-purple-200 rounded-2xl flex items-center justify-between gap-4 shadow-xs">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-800 flex items-center justify-center font-bold shrink-0">
                        <svg class="w-5 h-5 stroke-current stroke-2 fill-none" viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-purple-950">Driver Telah Mengisi Checklist Keberangkatan (Tahap 1)</p>
                        <p class="text-[11px] text-purple-800">
                            Perjalanan No. Surat <span class="font-mono font-bold">{{ $pemohonPendingDepartureSign->letter_number }}</span> membutuhkan TTD pengesahan Anda agar status resmi berubah menjadi <strong>ON TRIP</strong>.
                        </p>
                    </div>
                </div>
                <a href="{{ route('bookings.show', $pemohonPendingDepartureSign->letter_slug) }}" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl transition shrink-0">
                    Sahkan Keberangkatan &rarr;
                </a>
            </div>
        @endif

        {{-- 2. ALERT TTD KEPULANGAN (trip_completed -> returned) --}}
        @if(isset($pemohonPendingReturnSign))
            <div class="p-4 bg-indigo-50 border border-indigo-200 rounded-2xl flex items-center justify-between gap-4 shadow-xs">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-800 flex items-center justify-center font-bold shrink-0">
                        <svg class="w-5 h-5 stroke-current stroke-2 fill-none" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-indigo-950">Driver Telah Mengisi Checklist Kepulangan (Tahap 2)</p>
                        <p class="text-[11px] text-indigo-800">
                            Perjalanan No. Surat <span class="font-mono font-bold">{{ $pemohonPendingReturnSign->letter_number }}</span> telah selesai. Mohon sahkan TTD Formulir BAST Pengembalian.
                        </p>
                    </div>
                </div>
                <a href="{{ route('bookings.show', $pemohonPendingReturnSign->letter_slug) }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition shrink-0">
                    Sahkan BAST Kepulangan &rarr;
                </a>
            </div>
        @endif

        {{-- KARTU AKSES CEPAT LAYANAN --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <a href="{{ route('draft-bookings.create') }}" class="p-5 bg-white rounded-2xl border border-indigo-100 hover:border-indigo-300 transition shadow-xs flex items-center justify-between">
                <div>
                    <span class="font-bold text-indigo-950 text-sm block">1. Permohonan Draft Armada</span>
                    <span class="text-xs text-gray-500 mt-1 block">Cek ketersediaan mobil & driver sebelum menerbitkan Surat Tugas.</span>
                </div>
                <span class="px-3 py-1.5 bg-indigo-50 text-indigo-700 font-bold text-xs rounded-xl">Buat Request</span>
            </a>
            <a href="{{ route('bookings.create') }}" class="p-5 bg-white rounded-2xl border border-emerald-100 hover:border-emerald-300 transition shadow-xs flex items-center justify-between">
                <div>
                    <span class="font-bold text-emerald-950 text-sm block">2. Peminjaman Dinas Resmi</span>
                    <span class="text-xs text-gray-500 mt-1 block">Sudah memegang Surat Tugas resmi? Unggah dokumen di sini.</span>
                </div>
                <span class="px-3 py-1.5 bg-emerald-50 text-emerald-700 font-bold text-xs rounded-xl">Input Peminjaman</span>
            </a>
        </div>

        {{-- DUA TABEL STATUS PEGAWAI: DRAFT SAYA & PEMINJAMAN RESMI SAYA --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            {{-- TABEL 1: PERMOHONAN DRAFT SAYA --}}
            <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="font-bold text-sm text-gray-800 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        <span>Status Permohonan Draft Saya</span>
                    </h3>
                    <a href="{{ route('draft-bookings.index') }}" class="text-xs font-bold text-amber-600 hover:text-amber-800">Semua Draft &rarr;</a>
                </div>
                <div class="space-y-3">
                    @forelse($myDrafts as $d)
                        <div class="p-3 bg-amber-50/40 rounded-xl flex items-center justify-between text-xs border border-amber-100">
                            <div>
                                <span class="font-bold text-gray-900 block text-sm">{{ $d->destination }}</span>
                                <span class="text-gray-500 text-[11px]">
                                    Unit: <strong>{{ $d->vehicle?->brand ?? 'Menunggu Alokasi' }}</strong> | Driver: <strong>{{ $d->driver?->name ?? 'Menunggu Alokasi' }}</strong>
                                </span>
                            </div>
                            <span class="px-2.5 py-1 rounded-full font-bold uppercase text-[10px] 
                                @if($d->status === 'converted') bg-blue-100 text-blue-800
                                @elseif($d->status === 'approved') bg-emerald-100 text-emerald-800
                                @elseif($d->status === 'draft') bg-amber-100 text-amber-800
                                @elseif($d->status === 'rejected') bg-rose-100 text-rose-800
                                @else bg-gray-100 text-gray-700 @endif">
                                {{ $d->status }}
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 italic">Belum ada permohonan draft.</p>
                    @endforelse
                </div>
            </div>

            {{-- TABEL 2: PEMINJAMAN DINAS RESMI SAYA --}}
            <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="font-bold text-sm text-gray-800 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                        <span>Status Peminjaman Dinas Resmi Saya</span>
                    </h3>
                    <a href="{{ route('bookings.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">Semua Peminjaman &rarr;</a>
                </div>
                <div class="space-y-3">
                    @forelse($myBookings as $b)
                        <div class="p-3 bg-gray-50 rounded-xl flex items-center justify-between text-xs border border-gray-100">
                            <div>
                                <span class="font-bold text-gray-900 block text-sm">{{ $b->destination }}</span>
                                <span class="text-gray-500 text-[11px]">
                                    Unit: <strong>{{ $b->vehicle?->brand ?? '-' }} ({{ $b->vehicle?->plate_number ?? '-' }})</strong> | Driver: <strong>{{ $b->driver?->name ?? 'Tanpa Driver' }}</strong>
                                </span>
                            </div>
                            <span class="px-2.5 py-1 rounded-full font-bold uppercase text-[10px] bg-indigo-100 text-indigo-800">{{ $b->status }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 italic">Belum ada peminjaman dinas resmi.</p>
                    @endforelse
                </div>
            </div>

        </div>

    </div>
</x-app-layout>