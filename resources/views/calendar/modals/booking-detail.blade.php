{{-- MODAL DETAIL PEMINJAMAN (Z-INDEX 50) --}}
<div x-show="showDetailModal" class="fixed inset-0 bg-black/70 backdrop-blur-xs flex items-center justify-center p-4 z-[50]" x-cloak>
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden" @click.away="showDetailModal = false" @click.stop>
        <div class="bg-gray-900 px-6 py-4 text-white flex justify-between items-center">
            <h3 class="font-bold text-base flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-300 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="16" x2="12" y2="12"/>
                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                </svg>
                <span>Detail Penugasan Driver</span>
            </h3>
            <button type="button" @click="showDetailModal = false" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">&times;</button>
        </div>

        <template x-if="selectedDetailItem">
            <div class="p-6 space-y-3 text-xs">
                <div class="p-3 rounded-xl border bg-gray-50 flex justify-between items-center">
                    <div>
                        <span class="text-[10px] font-bold uppercase block text-gray-400">Driver Bertugas</span>
                        <p class="font-bold text-base text-gray-900" x-text="selectedDetailItem.driver_name"></p>
                    </div>
                    <span class="px-2 py-1 text-[10px] font-bold uppercase text-white rounded-lg" :class="getBookingStatusBg(selectedDetailItem.status)" x-text="selectedDetailItem.status"></span>
                </div>

                <div class="grid grid-cols-2 gap-2 border-b pb-2">
                    <div>
                        <span class="text-gray-400 block">Pemohon</span>
                        <span class="font-bold text-gray-800" x-text="selectedDetailItem.user_name"></span>
                    </div>
                    <div>
                        <span class="text-gray-400 block">Seksi / Unit</span>
                        <span class="font-bold text-gray-800" x-text="selectedDetailItem.department"></span>
                    </div>
                </div>

                <div class="border-b pb-2">
                    <span class="text-gray-400 block">Armada Diberikan</span>
                    <span class="font-bold text-indigo-700" x-text="selectedDetailItem.vehicle_name + ' (' + selectedDetailItem.plate_number + ')'"></span>
                </div>

                <div class="border-b pb-2">
                    <span class="text-gray-400 block">Tujuan Perjalanan</span>
                    <span class="font-semibold text-gray-900" x-text="selectedDetailItem.destination"></span>
                </div>

                <div class="border-b pb-2">
                    <span class="text-gray-400 block">Keperluan / Tugas</span>
                    <p class="text-gray-700 italic mt-0.5" x-text="selectedDetailItem.purpose"></p>
                </div>

                <div class="pt-2">
                    <button type="button" @click="showDetailModal = false" class="w-full py-2 bg-gray-100 text-gray-700 font-bold rounded-xl hover:bg-gray-200 transition">
                        Tutup
                    </button>
                </div>
            </div>
        </template>
    </div>
</div>