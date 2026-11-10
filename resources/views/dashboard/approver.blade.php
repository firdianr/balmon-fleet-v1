<x-app-layout>
    <x-slot name="title">Dashboard Approver</x-slot>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="font-bold text-xl text-gray-800 leading-tight">Persetujuan & Tanda Tangan Surat Tugas</h2>
                <p class="text-xs text-gray-500 mt-0.5">Pengesahan dokumen peminjaman keberangkatan (Tahap 1) & penyelesaian perjalanan (Tahap 2).</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        
        {{-- METRIK DUA TAHAP APPROVAL --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-indigo-200 shadow-xs flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-indigo-600 uppercase tracking-wider">Tahap 1: Menunggu Persetujuan Berangkat</p>
                    <p class="text-2xl font-black text-indigo-900">{{ $pendingDepartureCount }} <span class="text-xs font-normal text-gray-400">Dokumen</span></p>
                    <span class="text-[10px] text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded font-semibold block mt-1">Status: WAITING &rarr; APPROVED</span>
                </div>
                <div class="p-3 bg-indigo-100 text-indigo-700 rounded-2xl">
                    <svg class="w-6 h-6 stroke-current stroke-2 fill-none" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-teal-200 shadow-xs flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-teal-600 uppercase tracking-wider">Tahap 2: Menunggu Persetujuan Selesai</p>
                    <p class="text-2xl font-black text-teal-900">{{ $pendingFinalCompletionCount }} <span class="text-xs font-normal text-gray-400">Dokumen</span></p>
                    <span class="text-[10px] text-teal-700 bg-teal-50 px-2 py-0.5 rounded font-semibold block mt-1">Status: CHECKED &rarr; COMPLETED</span>
                </div>
                <div class="p-3 bg-teal-100 text-teal-700 rounded-2xl">
                    <svg class="w-6 h-6 stroke-current stroke-2 fill-none" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
            </div>
        </div>

        {{-- TABEL 1: PENGESAHAN KEBERANGKATAN (waiting) --}}
        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs space-y-4">
            <h3 class="font-bold text-sm text-gray-800 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                <span>Tahap 1: Pengesahan Surat Tugas Keberangkatan (Status WAITING)</span>
            </h3>
            <div class="space-y-3">
                @forelse($pendingDepartureBookings as $b)
                    <div class="p-4 border border-indigo-100 bg-indigo-50/30 rounded-xl flex items-center justify-between text-xs">
                        <div>
                            <span class="font-bold text-gray-900 block text-sm">{{ $b->user?->name }} — {{ $b->destination }}</span>
                            <span class="text-gray-600">Armada: <strong>{{ $b->vehicle?->brand }} ({{ $b->vehicle?->plate_number }})</strong> | Driver: <strong>{{ $b->driver?->name ?? 'Tanpa Driver' }}</strong></span>
                        </div>
                        <a href="{{ route('bookings.show', $b->letter_slug) }}" class="px-4 py-2 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 transition">
                            Buka & TTD Pengesahan
                        </a>
                    </div>
                @empty
                    <p class="text-xs text-gray-400 italic">Tidak ada dokumen keberangkatan yang menunggu TTD.</p>
                @endforelse
            </div>
        </div>

        {{-- TABEL 2: PENGESAHAN PENYELESAIAN (checked) --}}
        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs space-y-4">
            <h3 class="font-bold text-sm text-gray-800 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-teal-600"></span>
                <span>Tahap 2: Pengesahan Penutupan Selesai (Status CHECKED)</span>
            </h3>
            <div class="space-y-3">
                @forelse($pendingCompletionBookings as $b)
                    <div class="p-4 border border-teal-100 bg-teal-50/30 rounded-xl flex items-center justify-between text-xs">
                        <div>
                            <span class="font-bold text-gray-900 block text-sm">{{ $b->user?->name }} — {{ $b->destination }}</span>
                            <span class="text-gray-600">Armada: <strong>{{ $b->vehicle?->brand }} ({{ $b->vehicle?->plate_number }})</strong> | Verifikasi Garasi: <span class="text-teal-700 font-bold">LULUS PHYSICAL CHECK</span></span>
                        </div>
                        <a href="{{ route('bookings.show', $b->letter_slug) }}" class="px-4 py-2 bg-teal-600 text-white font-bold rounded-xl hover:bg-teal-700 transition">
                            Buka & TTD Penutupan
                        </a>
                    </div>
                @empty
                    <p class="text-xs text-gray-400 italic">Tidak ada dokumen penutupan yang menunggu TTD.</p>
                @endforelse
            </div>
        </div>

    </div>
</x-app-layout>