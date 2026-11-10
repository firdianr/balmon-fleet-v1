{{-- MODAL INPUT KETIDAKHADIRAN DRIVER --}}
<div x-show="showAttendanceModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-[60]" x-cloak>
    <div class="bg-white rounded-2xl shadow-xl max-w-md w-full overflow-hidden border border-gray-100" 
         @click.away="showAttendanceModal = false" 
         @click.stop>
        <div class="bg-amber-600 px-6 py-4 text-white flex justify-between items-center">
            <h3 class="font-bold text-base flex items-center gap-2">
                <svg class="w-5 h-5 text-white stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <span x-text="isEditAttendance ? 'Edit Ketidakhadiran Driver' : 'Ajukan / Catat Ketidakhadiran'"></span>
            </h3>
            <button type="button" @click="showAttendanceModal = false" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">&times;</button>
        </div>

        <form :action="isEditAttendance ? '/drivers/attendance/' + attendanceForm.id : '{{ route('drivers.attendance.store') }}'" method="POST" enctype="multipart/form-data" class="p-6 space-y-4 text-xs">
            @csrf
            <template x-if="isEditAttendance">
                <input type="hidden" name="_method" value="PUT">
            </template>

            @if($currentUser->role === 'driver')
                <input type="hidden" name="user_id" value="{{ $currentUser->id }}">
                <div class="p-3 bg-indigo-50 border border-indigo-200 rounded-xl flex items-center gap-2 text-indigo-900 font-bold">
                    <svg class="w-4 h-4 text-indigo-600 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    <span>Pengaju: {{ $currentUser->name }} (Driver)</span>
                </div>
            @else
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Pilih Driver <span class="text-rose-500">*</span></label>
                    <select name="user_id" x-model="attendanceForm.user_id" required class="w-full rounded-xl border-gray-300 text-xs font-medium focus:ring-amber-500 focus:border-amber-500">
                        <option value="">-- Pilih Driver --</option>
                        @foreach($drivers as $drv)
                            <option value="{{ $drv->id }}">{{ $drv->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

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

            <div>
                <label class="block font-bold text-gray-700 mb-1">Unggah Bukti / Surat Ket. (PDF/Gambar, Max 5MB)</label>
                <input type="file" name="attachment_file" accept=".pdf,.png,.jpg,.jpeg" class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
            </div>

            <div>
                <label class="block font-bold text-gray-700 mb-1">Catatan / Keterangan (Opsional)</label>
                <textarea name="notes" x-model="attendanceForm.notes" rows="2" placeholder="Tuliskan alasan izin/cuti..." class="w-full rounded-xl border-gray-300 text-xs font-medium focus:ring-amber-500 focus:border-amber-500"></textarea>
            </div>

            <div class="flex items-center gap-3 pt-3 border-t border-gray-100">
                <button type="button" @click="showAttendanceModal = false" class="w-1/2 py-2.5 bg-gray-100 text-gray-700 font-bold rounded-xl hover:bg-gray-200 transition">
                    Batal
                </button>
                <button type="submit" class="w-1/2 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl shadow-xs transition flex items-center justify-center gap-1.5">
                    <span x-text="isEditAttendance ? 'Update Pengajuan' : 'Kirim Pengajuan'"></span>
                </button>
            </div>
        </form>
    </div>
</div>