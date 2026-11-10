{{-- MODAL KONFIRMASI HAPUS KETIDAKHADIRAN (Z-INDEX 70) --}}
<div x-show="showDeleteModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-[70]" x-cloak>
    <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full overflow-hidden border border-gray-100" 
         @click.away="showDeleteModal = false" 
         @click.stop>
        <div class="p-6 text-center space-y-4">
            <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto">
                <svg class="w-6 h-6 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 6h18"/>
                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                    <line x1="10" y1="11" x2="10" y2="17"/>
                    <line x1="14" y1="11" x2="14" y2="17"/>
                </svg>
            </div>
            <div>
                <h3 class="text-base font-bold text-gray-900">Batalkan Ketidakhadiran?</h3>
                <p class="text-xs text-gray-500 mt-1">Data ketidakhadiran driver ini akan dihapus dari sistem dan status driver akan kembali standby.</p>
            </div>
            <form :action="'/drivers/attendance/' + deleteAttendanceId" method="POST" class="flex items-center gap-3 pt-2">
                @csrf
                @method('DELETE')
                <button type="button" @click="showDeleteModal = false" class="w-1/2 py-2 bg-gray-100 text-gray-700 font-bold text-xs rounded-xl hover:bg-gray-200 transition">
                    Batal
                </button>
                <button type="submit" class="w-1/2 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow-xs transition">
                    Ya, Hapus
                </button>
            </form>
        </div>
    </div>
</div>