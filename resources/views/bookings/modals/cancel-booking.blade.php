<div x-show="showCancelBookingModal" 
    class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50" 
    x-transition
    x-cloak>
    
    <div class="bg-white rounded-2xl shadow-xl max-w-md w-full overflow-hidden border border-gray-100" @click.away="showCancelBookingModal = false">
        
        <!-- HEADER MODAL AMBER -->
        <div class="bg-amber-500 px-6 py-4 text-white flex justify-between items-center shrink-0">
            <h3 class="font-bold text-base flex items-center gap-2">
                <!-- Icon Alert Triangle Amber Light Tint -->
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-amber-100 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                <span>Konfirmasi Pembatalan Pengajuan</span>
            </h3>
            <button type="button" @click="showCancelBookingModal = false" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <form :action="`/bookings/${cancelBookingForm.letter_slug}/cancel`" method="POST" class="p-6 space-y-4 text-xs">
            @csrf
            @method('PATCH')

            <div class="p-3 bg-amber-50 rounded-xl border border-amber-200/80 text-amber-900 leading-relaxed flex gap-2">
                <!-- Icon Warning Amber -->
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-amber-600 stroke-2 shrink-0 mt-0.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <div>
                    Apakah Anda yakin ingin membatalkan permohonan peminjaman armada dengan nomor surat <strong x-text="cancelBookingForm.letter_number"></strong>?
                </div>
            </div>

            <p class="text-gray-500 text-[11px]">
                Aksi ini tidak dapat dibatalkan kembali. Status permohonan akan diperbarui menjadi <strong>Canceled</strong>.
            </p>

            <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2">
                <button type="button" @click="showCancelBookingModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition">
                    Kembali
                </button>
                <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl shadow-xs transition flex items-center gap-1.5">
                    <!-- Icon Ban/Cancel White -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                    </svg>
                    <span>Ya, Batalkan Pengajuan</span>
                </button>
            </div>
        </form>
    </div>
</div>