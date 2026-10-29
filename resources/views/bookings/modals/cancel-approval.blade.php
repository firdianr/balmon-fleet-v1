<div x-show="showCancelApprovalModal" 
    class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50" 
    x-transition
    x-cloak>
    
    <div class="bg-white rounded-2xl shadow-xl max-w-md w-full overflow-hidden border border-gray-100" @click.away="showCancelApprovalModal = false">
        
        <!-- HEADER MODAL ROSE -->
        <div class="bg-rose-600 px-6 py-4 text-white flex justify-between items-center shrink-0">
            <h3 class="font-bold text-base flex items-center gap-2">
                <!-- Icon X Circle Rose Light Tint -->
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-rose-200 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="15" y1="9" x2="9" y2="15"/>
                    <line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
                <span>Pembatalan Persetujuan Peminjaman</span>
            </h3>
            <button type="button" @click="showCancelApprovalModal = false" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <form :action="`/bookings/${cancelApprovalForm.letter_slug}/cancel-approval`" method="POST" class="p-6 space-y-4 text-xs">
            @csrf
            @method('PATCH')

            <div class="p-3 bg-rose-50 rounded-xl border border-rose-200 text-rose-900 leading-relaxed space-y-1 flex gap-2.5">
                <!-- Icon Info Alert Rose -->
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-rose-600 stroke-2 shrink-0 mt-0.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                <div>
                    <p>Membatalkan persetujuan untuk No. Surat: <strong x-text="cancelApprovalForm.letter_number"></strong></p>
                    <p class="text-[11px] text-rose-700 mt-1">Unit teralokasi (<strong x-text="cancelApprovalForm.vehicle_name"></strong>) dan Driver akan otomatis dilepaskan kembali menjadi ready.</p>
                </div>
            </div>

            <div class="space-y-1">
                <label class="font-bold text-gray-700 flex items-center gap-1">
                    <!-- Icon Edit Note Rose -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-rose-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                    <span>Alasan Pembatalan Persetujuan</span> <span class="text-rose-500">*</span>
                </label>
                <textarea name="cancel_reason" x-model="cancelApprovalForm.cancel_reason" required rows="3"
                        placeholder="Jelaskan alasan pembatalan (misal: Unit mendadak rusak / Penugasan mendesak pimpinan...)"
                        class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium focus:bg-white focus:ring-rose-500 focus:border-rose-500"></textarea>
            </div>

            <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2">
                <button type="button" @click="showCancelApprovalModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition">
                    Kembali
                </button>
                <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl shadow-xs transition flex items-center gap-1.5">
                    <!-- Icon Remove/Delete White -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="15" y1="9" x2="9" y2="15"/>
                        <line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                    <span>Batalkan Persetujuan</span>
                </button>
            </div>
        </form>
    </div>
</div>