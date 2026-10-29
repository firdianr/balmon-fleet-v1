<x-app-layout>
    <x-slot name="title">Dashboard</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard Operasional Armada
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- BANNER PERHATIAN ADMIN LAPANGAN --}}
            @if(in_array(Auth::user()->role, ['admin_app', 'admin_lap']) && ($pendingDraftsCount ?? 0) > 0)
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-2xl flex items-center justify-between gap-4 shadow-xs">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">⚡</span>
                        <div>
                            <p class="text-xs font-bold text-amber-900">Ada {{ $pendingDraftsCount }} Permohonan Armada Menunggu Peninjauan</p>
                            <p class="text-[11px] text-amber-700">Tinjau ketersediaan unit dan driver agar pemohon bisa segera menerbitkan Surat Tugas.</p>
                        </div>
                    </div>
                    <a href="{{ route('draft-bookings.index') }}" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-xl transition flex-shrink-0">
                        Tinjau Permohonan
                    </a>
                </div>
            @endif

            {{-- BANNER PERHATIAN APPROVER --}}
            @if(in_array(Auth::user()->role, ['admin_app', 'approver']) && ($pendingApprovalsCount ?? 0) > 0)
                <div class="p-4 bg-indigo-50 border border-indigo-200 rounded-2xl flex items-center justify-between gap-4 shadow-xs">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">📝</span>
                        <div>
                            <p class="text-xs font-bold text-indigo-900">Ada {{ $pendingApprovalsCount }} Peminjaman Dinas Menunggu Persetujuan</p>
                            <p class="text-[11px] text-indigo-700">Surat Tugas telah diunggah dan membutuhkan pengesahan Anda.</p>
                        </div>
                    </div>
                    <a href="{{ route('bookings.index') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition flex-shrink-0">
                        Proses Persetujuan
                    </a>
                </div>
            @endif

            {{-- KARTU METRIK STATISTIK --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs flex items-center gap-4">
                    <div class="p-3 bg-blue-50 text-blue-600 rounded-xl font-bold text-xl">🚘</div>
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase">Armada Siap Pakai</p>
                        <p class="text-xl font-black text-gray-900">{{ $availableVehiclesCount ?? 0 }} <span class="text-xs font-normal text-gray-400">/ {{ $totalVehiclesCount ?? 0 }} Unit</span></p>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs flex items-center gap-4">
                    <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl font-bold text-xl">🛣️</div>
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase">Sedang Beroperasi</p>
                        <p class="text-xl font-black text-gray-900">{{ $activeTripsCount ?? 0 }} <span class="text-xs font-normal text-gray-400">Perjalanan</span></p>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs flex items-center gap-4">
                    <div class="p-3 bg-amber-50 text-amber-600 rounded-xl font-bold text-xl">⏳</div>
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase">Permohonan Awal</p>
                        <p class="text-xl font-black text-gray-900">{{ $pendingDraftsCount ?? 0 }} <span class="text-xs font-normal text-gray-400">Request</span></p>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs flex items-center gap-4">
                    <div class="p-3 bg-purple-50 text-purple-600 rounded-xl font-bold text-xl">📋</div>
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase">Peminjaman Resmi</p>
                        <p class="text-xl font-black text-gray-900">{{ $pendingApprovalsCount ?? 0 }} <span class="text-xs font-normal text-gray-400">Dokumen</span></p>
                    </div>
                </div>
            </div>

            {{-- AKSES CEPAT --}}
            <div class="bg-white p-6 rounded-2xl border border-gray-200/80 shadow-xs space-y-3">
                <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider">Akses Cepat Layanan</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <a href="{{ route('draft-bookings.create') }}" class="p-4 rounded-xl border border-indigo-100 bg-indigo-50/40 hover:bg-indigo-50 transition flex items-center justify-between group">
                        <div>
                            <p class="font-bold text-indigo-900 text-sm">Permohonan Armada</p>
                            <p class="text-gray-500 mt-0.5">Ajukan permohonan ketersediaan armada sebelum Surat Tugas diterbitkan.</p>
                        </div>
                        <span class="text-indigo-600 group-hover:translate-x-1 transition">➔</span>
                    </a>

                    <a href="{{ route('bookings.create') }}" class="p-4 rounded-xl border border-emerald-100 bg-emerald-50/40 hover:bg-emerald-50 transition flex items-center justify-between group">
                        <div>
                            <p class="font-bold text-emerald-900 text-sm">Peminjaman Dinas (SOP)</p>
                            <p class="text-gray-500 mt-0.5">Sudah memegang Surat Tugas resmi? Langsung ajukan peminjaman.</p>
                        </div>
                        <span class="text-emerald-600 group-hover:translate-x-1 transition">➔</span>
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>