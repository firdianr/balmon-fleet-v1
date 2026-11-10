{{-- MODAL APPROVAL PENINJAUAN CUTI (ADMIN LAPANGAN) --}}
<div x-show="showApprovalModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-[70]" x-cloak>
    <div class="bg-white rounded-2xl shadow-xl max-w-md w-full overflow-hidden border border-gray-100" @click.away="showApprovalModal = false" @click.stop>
        <div class="bg-indigo-900 px-6 py-4 text-white flex justify-between items-center">
            <h3 class="font-bold text-base flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-300 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <span>Tinjau Pengajuan Ketidakhadiran</span>
            </h3>
            <button type="button" @click="showApprovalModal = false" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">&times;</button>
        </div>

        <template x-if="selectedAttendance">
            <form :action="'/drivers/attendance/' + selectedAttendance.id + '/approve'" method="POST" class="p-6 space-y-4 text-xs">
                @csrf
                @method('PATCH')

                <div class="p-3 bg-gray-50 border border-gray-200 rounded-xl space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-sm text-gray-900" x-text="selectedAttendance.driver_name"></span>
                        <span class="px-2 py-0.5 text-[10px] font-bold text-white rounded uppercase" :class="getAttendanceStatusBg(selectedAttendance.status)" x-text="selectedAttendance.status"></span>
                    </div>
                    <p class="text-gray-600 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-gray-500 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        <span>Periode: <strong x-text="selectedAttendance.start_date + ' s/d ' + selectedAttendance.end_date"></strong></span>
                    </p>
                    <p class="text-gray-600 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-gray-500 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10 9 9 9 8 9"/>
                        </svg>
                        <span>Alasan: <span class="italic" x-text="selectedAttendance.notes"></span></span>
                    </p>
                </div>

                {{-- DOKUMEN BUKTI FILE --}}
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Lampiran Bukti File:</label>
                    <template x-if="selectedAttendance.attachment_url">
                        <a :href="selectedAttendance.attachment_url" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold rounded-xl border border-indigo-200 transition">
                            <svg class="w-4 h-4 text-indigo-600 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                <polyline points="15 3 21 3 21 9"/>
                                <line x1="10" y1="14" x2="21" y2="3"/>
                            </svg>
                            <span>Lihat Bukti Lampiran (PDF/Gambar)</span>
                        </a>
                    </template>
                    <template x-if="!selectedAttendance.attachment_url">
                        <p class="text-gray-400 italic bg-gray-50 p-2 rounded-lg border">Tidak ada lampiran berkas yang diunggah.</p>
                    </template>
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Keputusan Peninjauan <span class="text-rose-500">*</span></label>
                    <select name="process_status" x-model="approvalStatus" required class="w-full rounded-xl border-gray-300 text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="approved">Setujui (Sahkan Cuti/Izin)</option>
                        <option value="rejected">Tolak Pengajuan</option>
                    </select>
                </div>

                <div x-show="approvalStatus === 'rejected'">
                    <label class="block font-bold text-gray-700 mb-1">Alasan Penolakan <span class="text-rose-500">*</span></label>
                    <textarea name="rejection_note" rows="2" placeholder="Tuliskan alasan penolakan agar driver tahu..." class="w-full rounded-xl border-gray-300 text-xs font-medium focus:ring-rose-500 focus:border-rose-500"></textarea>
                </div>

                <div class="flex items-center gap-3 pt-3 border-t border-gray-100">
                    <button type="button" @click="showApprovalModal = false" class="w-1/2 py-2.5 bg-gray-100 text-gray-700 font-bold rounded-xl hover:bg-gray-200 transition">
                        Batal
                    </button>
                    <button type="submit" class="w-1/2 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-xs transition">
                        Simpan Keputusan
                    </button>
                </div>
            </form>
        </template>
    </div>
</div>