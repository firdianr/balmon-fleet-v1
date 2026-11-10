<x-app-layout>
    <x-slot name="title">Dashboard System Administrator</x-slot>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="font-bold text-xl text-gray-800 leading-tight">Dashboard Overview Management</h2>
                <p class="text-xs text-gray-500 mt-0.5">Pantauan sistem dan performa operasional armada secara menyeluruh.</p>
            </div>
            <div class="text-xs text-gray-500 bg-white px-3 py-1.5 rounded-xl border border-gray-200 shadow-2xs font-mono flex items-center gap-1.5">
                <svg class="w-4 h-4 text-gray-400 stroke-current stroke-2 fill-none" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <span>{{ now()->translatedFormat('l, d F Y') }}</span>
            </div>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        {{-- METRIK UTAMA --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Armada Ready</p>
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
                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Draft Butuh Alokasi</p>
                    <p class="text-2xl font-black text-amber-600">{{ $pendingDraftsCount }}</p>
                </div>
                <div class="p-3 bg-amber-50 text-amber-600 rounded-2xl">
                    <svg class="w-6 h-6 stroke-current stroke-2 fill-none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Menunggu Verifikasi Garasi</p>
                    <p class="text-2xl font-black text-cyan-600">{{ $pendingReturnVerificationsCount }}</p>
                </div>
                <div class="p-3 bg-cyan-50 text-cyan-600 rounded-2xl">
                    <svg class="w-6 h-6 stroke-current stroke-2 fill-none" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
            </div>
        </div>

        {{-- TABEL MONITORING UTAMA --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 p-6 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="font-bold text-sm text-gray-800">Aktivitas Peminjaman Terbaru</h3>
                    <a href="{{ route('bookings.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">Lihat Semua &rarr;</a>
                </div>
                <div class="space-y-3">
                    @forelse($recentBookings as $b)
                        <div class="p-3 bg-gray-50 rounded-xl flex items-center justify-between text-xs border border-gray-100">
                            <div>
                                <span class="font-bold text-gray-900 block">{{ $b->vehicle?->brand }} {{ $b->vehicle?->model }} ({{ $b->vehicle?->plate_number }})</span>
                                <span class="text-gray-500">Pemohon: {{ $b->user?->name }} | Tujuan: {{ $b->destination }}</span>
                            </div>
                            <span class="px-2.5 py-1 rounded-full font-bold uppercase text-[10px] bg-indigo-100 text-indigo-800">{{ $b->status }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 italic">Belum ada aktivitas peminjaman.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="font-bold text-sm text-gray-800">Draft Masuk</h3>
                    <a href="{{ route('draft-bookings.index') }}" class="text-xs font-bold text-amber-600 hover:text-amber-800">Lihat Semua &rarr;</a>
                </div>
                <div class="space-y-3">
                    @forelse($recentDrafts as $d)
                        <div class="p-3 bg-amber-50/50 rounded-xl border border-amber-100 text-xs space-y-1">
                            <span class="font-bold text-gray-900 block">{{ $d->user?->name }}</span>
                            <span class="text-gray-500 block truncate">{{ $d->destination }}</span>
                            <span class="text-[10px] text-amber-700 bg-amber-100 px-2 py-0.5 rounded font-bold uppercase">{{ $d->status }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 italic">Tidak ada draft baru.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>