<x-app-layout>
    <x-slot name="title">Permohonan Armada</x-slot>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    Permohonan Armada
                </h2>
                <p class="text-xs text-gray-500 mt-1">Daftar permohonan ketersediaan armada dan penugasan driver.</p>
            </div>
            <a href="{{ route('draft-bookings.create') }}" class="w-full sm:w-auto px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold shadow-md transition text-center flex items-center justify-center gap-2">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                <span>Permohonan Baru</span>
            </a>
        </div>
    </x-slot>

    <div class="py-6" x-data="{ reviewModal: false, convertModal: false, activeDraft: null }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            @if(session('success'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-2xl font-bold flex items-center gap-2">
                    <span>✅</span> {{ session('success') }}
                </div>
            @endif

            {{-- 1. TAMPILAN KARTU (SMARTPHONE: block md:hidden) --}}
            <div class="block md:hidden space-y-3">
                @forelse($drafts as $d)
                    @php
                        $start = \Carbon\Carbon::parse($d->start_date)->locale('id');
                        $end = \Carbon\Carbon::parse($d->end_date)->locale('id');
                        $sameDay = $start->isSameDay($end);
                    @endphp
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-4 shadow-xs space-y-3">
                        <div class="flex items-start justify-between gap-2 border-b border-gray-100 pb-2.5">
                            <div>
                                <p class="font-bold text-gray-900 text-base">{{ $d->user->name ?? '-' }}</p>
                                <p class="text-xs text-gray-500">NIP: {{ $d->user->nip ?? '-' }}</p>
                            </div>
                            @switch($d->status)
                                @case('approved')
                                    <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 font-bold text-xs uppercase shrink-0">Tersedia</span>
                                    @break
                                @case('rejected')
                                    <span class="px-3 py-1 rounded-full bg-rose-100 text-rose-800 font-bold text-xs uppercase shrink-0">Ditolak</span>
                                    @break
                                @case('converted')
                                    <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-800 font-bold text-xs uppercase shrink-0">Peminjaman Resmi</span>
                                    @break
                                @default
                                    <span class="px-3 py-1 rounded-full bg-amber-100 text-amber-800 font-bold text-xs uppercase shrink-0">Menunggu Review</span>
                            @endswitch
                        </div>

                        <div class="space-y-2 text-xs">
                            <div>
                                <p class="text-gray-400 font-bold uppercase text-[10px]">Jadwal Perjalanan</p>
                                <p class="font-bold text-gray-800 text-sm mt-0.5">📅 {{ $start->translatedFormat('l, d M Y') }}</p>
                                <p class="text-xs text-indigo-700 font-semibold bg-indigo-50 inline-block px-2.5 py-0.5 rounded-md mt-1">
                                    @if($sameDay)
                                        Jam {{ $start->format('H:i') }} s.d. {{ $end->format('H:i') }} WIB
                                    @else
                                        {{ $start->format('H:i') }} WIB s.d. {{ $end->translatedFormat('l, d M Y') }} ({{ $end->format('H:i') }} WIB)
                                    @endif
                                </p>
                            </div>

                            <div class="pt-1 flex flex-col gap-1.5">
                                <div>
                                    <p class="text-gray-400 font-bold uppercase text-[10px]">Driver Diminta</p>
                                    @if($d->driver)
                                        <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-amber-100/90 border border-amber-300 text-amber-950 font-bold text-sm rounded-xl mt-1">
                                            <span>🚖</span>
                                            <div>
                                                <p class="leading-none">{{ $d->driver->name }}</p>
                                                <p class="text-[10px] text-amber-800 font-normal mt-0.5">{{ $d->driver->phone ?? 'No WA -' }}</p>
                                            </div>
                                        </div>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-gray-100 text-gray-600 font-bold text-xs rounded-lg mt-1">
                                            🔑 Lepas Kunci
                                        </span>
                                    @endif
                                </div>

                                <div>
                                    <p class="text-gray-400 font-bold uppercase text-[10px]">Armada</p>
                                    @if($d->vehicle)
                                        <p class="font-bold text-indigo-900 text-sm mt-0.5">{{ $d->vehicle->brand }} {{ $d->vehicle->model }} ({{ $d->vehicle->plate_number }})</p>
                                    @else
                                        <span class="text-amber-600 font-bold italic text-xs">Belum Ditentukan</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-gray-100 flex items-center justify-end gap-2">
                            @if(in_array(Auth::user()->role, ['admin_app', 'admin_lap']) && $d->status === 'draft')
                                <button @click="activeDraft = {{ json_encode($d) }}; reviewModal = true" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs shadow-md transition text-center">
                                    Tinjau & Konfirmasi
                                </button>
                            @endif

                            @if($d->status === 'approved' && ($d->user_id === Auth::id() || in_array(Auth::user()->role, ['admin_app', 'admin_lap'])))
                                <button @click="activeDraft = {{ json_encode($d) }}; convertModal = true" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-md transition text-center">
                                    Upload Surat Tugas
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="bg-white rounded-2xl p-8 text-center text-gray-400 font-medium italic border border-gray-200">
                        Belum ada data permohonan armada saat ini.
                    </div>
                @endforelse
            </div>

            {{-- 2. TAMPILAN TABEL (DESKTOP: hidden md:block) --}}
            <div class="hidden md:block bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-gray-50/80 border-b border-gray-200 text-gray-700 font-bold uppercase tracking-wider text-xs">
                                <th class="p-4">Pemohon</th>
                                <th class="p-4">Jadwal Perjalanan</th>
                                <th class="p-4">Driver Ditugaskan</th>
                                <th class="p-4">Armada</th>
                                <th class="p-4 text-center">Status</th>
                                <th class="p-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 font-medium">
                            @forelse($drafts as $d)
                                @php
                                    $start = \Carbon\Carbon::parse($d->start_date)->locale('id');
                                    $end = \Carbon\Carbon::parse($d->end_date)->locale('id');
                                    $sameDay = $start->isSameDay($end);
                                @endphp
                                <tr class="hover:bg-gray-50/60 transition">
                                    <td class="p-4">
                                        <p class="font-bold text-gray-900 text-base">{{ $d->user->name ?? '-' }}</p>
                                        <p class="text-xs text-gray-500">NIP: {{ $d->user->nip ?? '-' }}</p>
                                    </td>

                                    <td class="p-4">
                                        <div class="space-y-1">
                                            <p class="font-bold text-gray-800 text-base">
                                                📅 {{ $start->translatedFormat('l, d M Y') }}
                                            </p>
                                            <p class="text-xs text-indigo-700 font-semibold bg-indigo-50 inline-block px-2.5 py-1 rounded-lg">
                                                @if($sameDay)
                                                    Jam {{ $start->format('H:i') }} s.d. {{ $end->format('H:i') }} WIB
                                                @else
                                                    {{ $start->format('H:i') }} WIB s.d. {{ $end->translatedFormat('l, d M Y') }} ({{ $end->format('H:i') }} WIB)
                                                @endif
                                            </p>
                                        </div>
                                    </td>

                                    <td class="p-4">
                                        @if($d->driver)
                                            <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-amber-100/90 border border-amber-300 text-amber-950 font-bold text-sm rounded-xl shadow-2xs">
                                                <span class="text-base">🚖</span>
                                                <div>
                                                    <p class="leading-none text-sm">{{ $d->driver->name }}</p>
                                                    <p class="text-[10px] text-amber-800 font-normal mt-0.5">{{ $d->driver->phone ?? 'No WA -' }}</p>
                                                </div>
                                            </div>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-gray-100 text-gray-600 font-bold text-xs rounded-lg">
                                                🔑 Lepas Kunci
                                            </span>
                                        @endif
                                    </td>

                                    <td class="p-4">
                                        @if($d->vehicle)
                                            <p class="font-bold text-indigo-900 text-base">{{ $d->vehicle->brand }} {{ $d->vehicle->model }}</p>
                                            <p class="text-xs text-gray-500 font-semibold">
                                                {{ $d->vehicle->plate_number }} 
                                                <span class="ml-1 px-1.5 py-0.5 rounded bg-gray-100 text-gray-700 uppercase text-[10px] font-bold">{{ $d->vehicle->type }}</span>
                                            </p>
                                        @else
                                            <span class="text-amber-600 font-bold italic text-xs">Belum Ditentukan</span>
                                        @endif
                                    </td>

                                    <td class="p-4 text-center">
                                        @switch($d->status)
                                            @case('approved')
                                                <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 font-bold text-xs">Tersedia</span>
                                                @break
                                            @case('rejected')
                                                <span class="px-3 py-1 rounded-full bg-rose-100 text-rose-800 font-bold text-xs">Ditolak</span>
                                                @break
                                            @case('converted')
                                                <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-800 font-bold text-xs">Peminjaman Resmi</span>
                                                @break
                                            @default
                                                <span class="px-3 py-1 rounded-full bg-amber-100 text-amber-800 font-bold text-xs">Menunggu Review</span>
                                        @endswitch
                                    </td>

                                    <td class="p-4 text-right space-x-2">
                                        @if(in_array(Auth::user()->role, ['admin_app', 'admin_lap']) && $d->status === 'draft')
                                            <button @click="activeDraft = {{ json_encode($d) }}; reviewModal = true" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs shadow-md transition">
                                                Tinjau & Konfirmasi
                                            </button>
                                        @endif

                                        @if($d->status === 'approved' && ($d->user_id === Auth::id() || in_array(Auth::user()->role, ['admin_app', 'admin_lap'])))
                                            <button @click="activeDraft = {{ json_encode($d) }}; convertModal = true" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-md transition">
                                                Upload Surat Tugas
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-10 text-center text-gray-400 font-medium italic">Belum ada data permohonan armada saat ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-gray-100">
                    {{ $drafts->links() }}
                </div>
            </div>

            {{-- MODAL TINJAUAN ADMIN LAPANGAN (DENGAN VALIDASI CLIENT ALASAN PENOLAKAN) --}}
            <div x-show="reviewModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4">
                <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl" @click.away="reviewModal = false">
                    <div class="flex justify-between items-center border-b pb-3">
                        <h3 class="text-base font-bold text-gray-900">Tinjau Permohonan Armada</h3>
                        <button @click="reviewModal = false" class="text-gray-400 hover:text-gray-600 text-lg font-bold">&times;</button>
                    </div>

                    <form :action="`/draft-bookings/${activeDraft?.id}/review`" 
                          method="POST" 
                          x-data="{ 
                              actionType: 'approve', 
                              note: '', 
                              errorMessage: '',
                              submitReview(e) {
                                  if (this.actionType === 'reject' && !this.note.trim()) {
                                      e.preventDefault();
                                      this.errorMessage = 'Alasan penolakan wajib diisi sebelum menolak permohonan!';
                                      return;
                                  }
                                  this.errorMessage = '';
                              }
                          }" 
                          @submit="submitReview($event)"
                          class="space-y-4">
                        @csrf
                        <input type="hidden" name="action" :value="actionType">

                        <div x-show="errorMessage" x-cloak class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-700 font-bold flex items-center gap-2">
                            <span>⚠️</span> <span x-text="errorMessage"></span>
                        </div>

                        <div class="p-3.5 bg-gray-50 rounded-xl space-y-2 text-xs border border-gray-100">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Pemohon:</span>
                                <span class="font-bold text-gray-900" x-text="activeDraft?.user?.name"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Unit Diminta:</span>
                                <span class="font-bold text-indigo-700" x-text="activeDraft?.vehicle ? `${activeDraft.vehicle.brand} ${activeDraft.vehicle.model} (${activeDraft.vehicle.plate_number})` : '-'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Driver Diminta:</span>
                                <span class="font-bold text-amber-900" x-text="activeDraft?.driver?.name ?? 'Lepas Kunci'"></span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                                Catatan / Alasan Penolakan <span x-show="actionType === 'reject'" class="text-rose-500">* (Wajib diisi)</span>
                            </label>
                            <textarea name="admin_note" 
                                      x-model="note" 
                                      rows="3" 
                                      class="w-full rounded-xl border-gray-300 text-xs focus:ring-indigo-500 focus:border-indigo-500" 
                                      placeholder="Tuliskan alasan penolakan atau catatan tambahan..."></textarea>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-3 border-t">
                            <button type="button" @click="reviewModal = false" class="px-3.5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl text-xs">
                                Batal
                            </button>
                            <button type="submit" @click="actionType = 'reject'" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs shadow-md transition">
                                Tolak
                            </button>
                            <button type="submit" @click="actionType = 'approve'" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-md transition">
                                Setujui
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- MODAL UPLOAD SURAT TUGAS --}}
            <div x-show="convertModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4">
                <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl" @click.away="convertModal = false">
                    <h3 class="text-base font-bold text-gray-900 border-b pb-2">Upload Surat Tugas</h3>
                    
                    <form :action="`/draft-bookings/${activeDraft?.id}/convert`" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Nomor Surat Tugas <span class="text-rose-500">*</span></label>
                            <input type="text" name="letter_number" class="w-full rounded-xl border-gray-300 text-xs" placeholder="Contoh: ST/102/BALMON/2026" required>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Berkas Surat Tugas (PDF) <span class="text-rose-500">*</span></label>
                            <input type="file" name="letter_file" accept=".pdf" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" required>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2 border-t">
                            <button type="button" @click="convertModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl text-xs">
                                Batal
                            </button>
                            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-md">
                                Simpan & Pindah ke Peminjaman Dinas
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>