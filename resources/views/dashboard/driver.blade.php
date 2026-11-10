<x-app-layout>
    <x-slot name="title">Dashboard Pengemudi</x-slot>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-xl text-gray-800 leading-tight">Dashboard Operational Driver</h2>
                <p class="text-xs text-gray-500 mt-0.5">Kelola penugasan aktif, log perjalanan, dan status pengajuan cuti.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
         x-data="driverDashboardApp()"
         @open-attendance-modal.window="openCreateAttendance()">

        {{-- PENUGASAN PERJALANAN AKTIF --}}
        @if(isset($driverActiveTrip))
            <div class="p-6 bg-emerald-50 border border-emerald-200 rounded-2xl space-y-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="px-3 py-1 bg-emerald-600 text-white font-bold text-[10px] rounded-lg uppercase tracking-wider">Penugasan Perjalanan Aktif</span>
                    <span class="font-mono text-xs font-bold text-emerald-900">No. Surat: {{ $driverActiveTrip->letter_number }}</span>
                </div>
                <div>
                    <h3 class="font-bold text-lg text-gray-900">{{ $driverActiveTrip->vehicle?->brand }} {{ $driverActiveTrip->vehicle?->model }} ({{ $driverActiveTrip->vehicle?->plate_number }})</h3>
                    <p class="text-xs text-gray-600 mt-1">
                        Pemohon: <strong>{{ $driverActiveTrip->user?->name }}</strong> | Tujuan: <strong>{{ $driverActiveTrip->destination }}</strong>
                    </p>
                </div>
                <div class="pt-2 flex items-center gap-3">
                    <a href="{{ route('bookings.show', $driverActiveTrip->letter_slug) }}" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition shadow-xs inline-flex items-center gap-2">
                        <svg class="w-4 h-4 stroke-current stroke-2 fill-none" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        <span>Isi Odometer Log & Checklist Perjalanan</span>
                    </a>
                </div>
            </div>
        @else
            <div class="p-5 bg-white border border-gray-200 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-xs">
                <div class="flex items-center gap-3">
                    <div class="w-3 h-3 rounded-full bg-emerald-500 animate-ping shrink-0"></div>
                    <p class="text-xs text-gray-700">
                        Status Anda saat ini: <strong class="text-emerald-700 font-bold uppercase">Standby / Ready</strong> (Tidak ada tugas jalan aktif)
                    </p>
                </div>
                
                {{-- TOMBOL PENGAJUAN CUTI PADA BANNER STANDBY --}}
                <button type="button" 
                        @click="openCreateAttendance()" 
                        class="px-4 py-2 bg-amber-50 border border-amber-200 text-amber-800 hover:bg-amber-100 font-bold text-xs rounded-xl transition flex items-center justify-center gap-1.5 shrink-0">
                    <svg class="w-4 h-4 text-amber-600 stroke-current stroke-2 fill-none" viewBox="0 0 24 24"><path d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    <span>Ajukan Cuti / Izin</span>
                </button>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
            
            {{-- KOLOM KIRI (2 KOLOM): RIWAYAT PERJALANAN --}}
            <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-gray-200 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="font-bold text-sm text-gray-800">Riwayat Perjalanan Terakhir Saya</h3>
                    <a href="{{ route('bookings.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                        <span>Lihat Semua</span>
                        <svg class="w-3.5 h-3.5 stroke-current stroke-2 fill-none" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                </div>
                <div class="space-y-3">
                    @forelse($myHistory as $h)
                        <div class="p-3 bg-gray-50 rounded-xl flex items-center justify-between text-xs border border-gray-100">
                            <div>
                                <span class="font-bold text-gray-900 block">{{ $h->vehicle?->brand }} ({{ $h->vehicle?->plate_number }})</span>
                                <span class="text-gray-500">Tujuan: {{ $h->destination }}</span>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full font-bold uppercase text-[10px] bg-gray-200 text-gray-700">{{ $h->status }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 italic">Belum ada riwayat penugasan.</p>
                    @endforelse
                </div>
            </div>

            {{-- KOLOM KANAN (1 KOLOM): RIWAYAT CUTI / IZIN SAYA --}}
            <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="font-bold text-sm text-gray-800">Pengajuan Cuti Saya</h3>
                    <button type="button" @click="openCreateAttendance()" class="text-xs font-bold text-amber-600 hover:text-amber-800 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 stroke-current stroke-2 fill-none" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        <span>Tambah</span>
                    </button>
                </div>
                <div class="space-y-3">
                    @forelse($myAttendances as $att)
                        <div class="p-3 bg-amber-50/40 rounded-xl border border-amber-100 text-xs space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-gray-900 uppercase text-[11px]">{{ $att->status }}</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                    @if($att->process_status === 'approved') bg-emerald-100 text-emerald-800
                                    @elseif($att->process_status === 'rejected') bg-rose-100 text-rose-800
                                    @else bg-amber-100 text-amber-800 @endif">
                                    {{ $att->process_status }}
                                </span>
                            </div>
                            <p class="text-[10px] text-gray-500 flex items-center gap-1">
                                <svg class="w-3 h-3 text-gray-400 stroke-current stroke-2 fill-none" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                <span>{{ \Carbon\Carbon::parse($att->start_date)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($att->end_date)->format('d/m/Y') }}</span>
                            </p>
                            @if($att->notes)
                                <p class="text-[10px] text-gray-600 italic truncate">"{{ $att->notes }}"</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 italic">Belum ada pengajuan cuti/izin.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- MODAL INPUT KETIDAKHADIRAN DRIVER --}}
        <div x-show="showAttendanceModal" 
                class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-[60]" 
                x-cloak>
            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full overflow-hidden border border-gray-100" 
                    @click.away="showAttendanceModal = false" 
                    @click.stop>
                
                {{-- HEADER MODAL --}}
                <div class="bg-amber-600 px-6 py-4 text-white flex justify-between items-center">
                    <h3 class="font-bold text-base flex items-center gap-2">
                        <svg class="w-5 h-5 text-white stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        <span>Ajukan / Catat Ketidakhadiran</span>
                    </h3>
                    <button type="button" @click="showAttendanceModal = false" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">&times;</button>
                </div>

                {{-- FORM MODAL --}}
                <form action="{{ route('drivers.attendance.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4 text-xs">
                    @csrf
                    
                    {{-- ID DRIVER DENGAN DISPLAY INFO --}}
                    <input type="hidden" name="user_id" value="{{ Auth::id() }}">
                    <div class="p-3 bg-indigo-50 border border-indigo-200 rounded-xl flex items-center gap-2 text-indigo-900 font-bold">
                        <svg class="w-4 h-4 text-indigo-600 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <span>Pengaju: {{ Auth::user()->name }} (Driver)</span>
                    </div>

                    {{-- PILIHAN STATUS CUTI / IZIN --}}
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Status Keterangan <span class="text-rose-500">*</span></label>
                        <select name="status" x-model="attendanceForm.status" required class="w-full rounded-xl border-gray-300 text-xs font-medium focus:ring-amber-500 focus:border-amber-500">
                            <option value="cuti">Cuti Tahunan / Dinas</option>
                            <option value="sakit">Sakit</option>
                            <option value="izin">Izin / Keperluan Pribadi</option>
                            <option value="service">Pendampingan Service Bengkel</option>
                            <option value="non_active">Non-Aktif / Resign</option>
                        </select>
                    </div>

                    {{-- RENTANG TANGGAL --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Tanggal Mulai <span class="text-rose-500">*</span></label>
                            <input type="date" name="start_date" x-model="attendanceForm.start_date" required class="w-full rounded-xl border-gray-300 text-xs font-medium focus:ring-amber-500 focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Tanggal Selesai <span class="text-rose-500">*</span></label>
                            <input type="date" name="end_date" x-model="attendanceForm.end_date" required class="w-full rounded-xl border-gray-300 text-xs font-medium focus:ring-amber-500 focus:border-amber-500">
                        </div>
                    </div>

                    {{-- FILE BUKTI --}}
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Unggah Bukti / Surat Ket. (PDF/Gambar, Max 5MB)</label>
                        <input type="file" name="attachment_file" accept=".pdf,.png,.jpg,.jpeg" class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
                    </div>

                    {{-- CATATAN / REASON --}}
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Catatan / Keterangan (Opsional)</label>
                        <textarea name="notes" x-model="attendanceForm.notes" rows="2" placeholder="Tuliskan alasan izin/cuti..." class="w-full rounded-xl border-gray-300 text-xs font-medium focus:ring-amber-500 focus:border-amber-500"></textarea>
                    </div>

                    {{-- TOMBOL AKSI --}}
                    <div class="flex items-center gap-3 pt-3 border-t border-gray-100">
                        <button type="button" @click="showAttendanceModal = false" class="w-1/2 py-2.5 bg-gray-100 text-gray-700 font-bold rounded-xl hover:bg-gray-200 transition">
                            Batal
                        </button>
                        <button type="submit" class="w-1/2 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl shadow-xs transition flex items-center justify-center gap-1.5">
                            <span>Kirim Pengajuan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    {{-- SCRIPT ALPINE.JS DASHBOARD DRIVER --}}
    <script>
        function driverDashboardApp() {
            return {
                showAttendanceModal: false,
                attendanceForm: {
                    user_id: '{{ Auth::id() }}',
                    status: 'cuti',
                    start_date: '',
                    end_date: '',
                    notes: ''
                },

                openCreateAttendance() {
                    const today = new Date().toISOString().split('T')[0];
                    this.attendanceForm = {
                        user_id: '{{ Auth::id() }}',
                        status: 'cuti',
                        start_date: today,
                        end_date: today,
                        notes: ''
                    };
                    this.showAttendanceModal = true;
                }
            }
        }
    </script>
</x-app-layout>