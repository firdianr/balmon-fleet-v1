<!-- MODAL APPROVER DENGAN PRATINJAU CHECKLIST DETIL & FOTO BUKTI -->
<div x-show="showApproverModal" 
    class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    x-cloak>

    <div class="bg-white rounded-2xl shadow-xl max-w-2xl w-full max-h-[92vh] flex flex-col overflow-hidden border border-gray-100" @click.away="showApproverModal = false">
        
        <!-- HEADER MODAL -->
        <div class="px-6 py-4 text-white flex justify-between items-center shrink-0"
            :class="approverActionType === 'waiting' ? 'bg-indigo-600' : 'bg-emerald-600'">
            <div>
                <h3 class="font-bold text-base flex items-center gap-2">
                    <template x-if="approverActionType === 'waiting'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                            <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                            <path d="m9 14 2 2 4-4"/>
                        </svg>
                    </template>
                    <template x-if="approverActionType !== 'waiting'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.78 4.78 4 4 0 0 1-6.74 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76z"/>
                            <path d="m9 12 2 2 4-4"/>
                        </svg>
                    </template>
                    <span x-text="approverActionType === 'waiting' ? 'Persetujuan Permohonan Peminjaman' : 'Persetujuan Akhir BAST Pengembalian'"></span>
                </h3>
                <p class="text-xs opacity-90 font-mono mt-0.5" x-text="selectedBooking ? 'No. Surat: ' + selectedBooking.letter_number : ''"></p>
            </div>
            <button type="button" @click="showApproverModal = false" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <form :action="selectedBooking ? `/bookings/${selectedBooking.letter_slug}/approver-review` : '#'" method="POST" class="p-6 overflow-y-auto space-y-4 text-xs">
            @csrf
            @method('PATCH')

            <input type="hidden" name="action" :value="approverForm.action">

            <!-- 1. INFORMASI UTAMA PEMINJAMAN -->
            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200/80 grid grid-cols-2 gap-3 text-gray-800">
                <div>
                    <span class="text-[10px] text-gray-400 block uppercase font-bold">Pemohon Utama</span>
                    <p class="font-bold text-gray-900" x-text="selectedBooking?.user?.name || '-'"></p>
                </div>
                <div>
                    <span class="text-[10px] text-gray-400 block uppercase font-bold">Tujuan Perjalanan</span>
                    <p class="font-bold text-gray-900" x-text="selectedBooking?.destination || '-'"></p>
                </div>
                <div>
                    <span class="text-[10px] text-gray-400 block uppercase font-bold">Armada Operasional</span>
                    <p class="font-bold text-indigo-700" x-text="selectedBooking?.vehicle ? `${selectedBooking.vehicle.brand} ${selectedBooking.vehicle.model} (${selectedBooking.vehicle.plate_number})` : '-'"></p>
                </div>
                <div>
                    <span class="text-[10px] text-gray-400 block uppercase font-bold">Pengemudi Ditunjuk</span>
                    <p class="font-bold text-gray-900" x-text="selectedBooking?.driver ? selectedBooking.driver.name : '-'"></p>
                </div>
            </div>

            <!-- 2. PRATINJAU BUKTI FOTO (SPEEDOMETER, CUCI MOBIL, KERUSAKAN) -->
            <template x-if="selectedBooking?.log">
                <div class="p-3.5 bg-cyan-50/60 border border-cyan-200 rounded-xl space-y-3">
                    <span class="text-[10px] font-bold text-cyan-800 uppercase tracking-wider block border-b border-cyan-200/80 pb-1 flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/>
                            <circle cx="12" cy="13" r="3"/>
                        </svg>
                        <span>Pratinjau Lampiran Foto Operational Log</span>
                    </span>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <!-- FOTO SPEEDOMETER AWAL -->
                        <template x-if="selectedBooking.log.start_photo">
                            <div class="space-y-1">
                                <span class="text-[10px] font-bold text-gray-600 block">Speedometer Start:</span>
                                <a :href="`/storage/${selectedBooking.log.start_photo}`" target="_blank" class="block">
                                    <img :src="`/storage/${selectedBooking.log.start_photo}`" class="h-20 w-full object-cover rounded-lg border border-cyan-300 hover:opacity-90 transition">
                                </a>
                                <span class="text-[10px] text-gray-500 font-mono" x-text="'BBM: ' + (selectedBooking.log.start_fuel_level || '-')"></span>
                            </div>
                        </template>

                        <!-- FOTO SPEEDOMETER AKHIR -->
                        <template x-if="selectedBooking.log.end_photo">
                            <div class="space-y-1">
                                <span class="text-[10px] font-bold text-gray-600 block">Speedometer End:</span>
                                <a :href="`/storage/${selectedBooking.log.end_photo}`" target="_blank" class="block">
                                    <img :src="`/storage/${selectedBooking.log.end_photo}`" class="h-20 w-full object-cover rounded-lg border border-cyan-300 hover:opacity-90 transition">
                                </a>
                                <span class="text-[10px] text-gray-500 font-mono" x-text="'BBM: ' + (selectedBooking.log.end_fuel_level || '-')"></span>
                            </div>
                        </template>

                        <!-- FOTO NOTA CUCI MOBIL -->
                        <template x-if="selectedBooking.displayChecklists?.some(c => c.wash_receipt_photo)">
                            <div class="space-y-1">
                                <span class="text-[10px] font-bold text-amber-800 block flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                                    </svg>
                                    <span>Nota Cuci Mobil:</span>
                                </span>
                                <template x-for="chk in selectedBooking.displayChecklists.filter(c => c.wash_receipt_photo)" :key="chk.id">
                                    <a :href="`/storage/${chk.wash_receipt_photo}`" target="_blank" class="block">
                                        <img :src="`/storage/${chk.wash_receipt_photo}`" class="h-20 w-full object-cover rounded-lg border border-amber-300 hover:opacity-90 transition">
                                    </a>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- FOTO BUKTI KERUSAKAN / INSIDEN -->
                    <template x-if="selectedBooking.log.condition_photos && JSON.parse(selectedBooking.log.condition_photos).length > 0">
                        <div class="pt-2 border-t border-cyan-200/80">
                            <span class="text-[10px] font-bold text-rose-800 block mb-1.5 flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                                    <line x1="12" y1="9" x2="12" y2="13"/>
                                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                                </svg>
                                <span>Bukti Foto Insiden / Kerusakan:</span>
                            </span>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="(photo, index) in JSON.parse(selectedBooking.log.condition_photos)" :key="index">
                                    <a :href="`/storage/${photo}`" target="_blank">
                                        <img :src="`/storage/${photo}`" class="h-16 w-16 object-cover rounded-lg border border-rose-300 hover:scale-105 transition shadow-2xs">
                                    </a>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <!-- 3. RINCIAN COMPLETE ITEM CHECKLIST KENDARAAN -->
            <template x-if="selectedBooking && selectedBooking.displayChecklists && selectedBooking.displayChecklists.length > 0">
                <div class="space-y-3 p-3.5 bg-slate-50 border border-slate-200 rounded-xl">
                    <h4 class="font-bold text-slate-800 text-xs flex items-center justify-between border-b border-slate-200 pb-1.5">
                        <span class="flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 text-slate-700 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                                <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                                <path d="m9 14 2 2 4-4"/>
                            </svg>
                            <span x-text="approverActionType === 'waiting' ? 'Detail Kondisi Fisik Terakhir Armada' : 'Rincian Kondisi Armada'"></span>
                        </span>
                    </h4>

                    <div class="space-y-3">
                        <template x-for="chk in selectedBooking.displayChecklists" :key="chk.id">
                            <div class="p-3 rounded-xl border bg-white space-y-3 shadow-2xs"
                                :class="chk.type === 'start' ? 'border-emerald-200' : 'border-indigo-200'">
                                
                                <div class="flex justify-between items-center border-b border-gray-100 pb-1.5">
                                    <span class="font-bold text-[10px] uppercase px-2 py-0.5 rounded-md tracking-wide flex items-center gap-1"
                                        :class="chk.type === 'start' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-indigo-100 text-indigo-800 border border-indigo-200'">
                                        <template x-if="chk.type === 'start'">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.71 1.1-1.63 1.1-2.58 0-1.88-1.52-3.42-3.4-3.42-.95 0-1.87.39-2.58 1.1z"/>
                                                <path d="M12 15l-3-3 8.5-8.5a2.12 2.12 0 0 1 3 3L12 15z"/>
                                            </svg>
                                        </template>
                                        <template x-if="chk.type !== 'start'">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6h-5.6z"/>
                                            </svg>
                                        </template>
                                        <span x-text="chk.type === 'start' ? 'Keberangkatan (Pre-Trip)' : 'Pengembalian (Post-Trip)'"></span>
                                    </span>
                                    
                                    <template x-if="chk.is_washed">
                                        <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200 flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                                            </svg>
                                            <span>Dikembalikan Bersih</span>
                                        </span>
                                    </template>
                                </div>

                                <!-- KELOMPOK 1: KONDISI FISIK KENDARAAN -->
                                <div class="space-y-1.5">
                                    <span class="font-bold text-gray-800 text-[11px] block border-b border-gray-100 pb-0.5 flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2 text-gray-600 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="11" cy="11" r="8"/>
                                            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                        </svg>
                                        <span>Kondisi Fisik Kendaraan:</span>
                                    </span>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 text-gray-700 text-[11px]">
                                        <div class="flex justify-between py-0.5 border-b border-gray-50">
                                            <span>Bodi & Cat Mulus:</span>
                                            <span class="font-bold flex items-center gap-1" :class="chk.body_condition ? 'text-emerald-600' : 'text-rose-600'">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" x-show="chk.body_condition"><polyline points="20 6 9 17 4 12"/></svg>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" x-show="!chk.body_condition"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                <span x-text="chk.body_condition ? 'Mulus' : 'Bermasalah'"></span>
                                            </span>
                                        </div>
                                        <div class="flex justify-between py-0.5 border-b border-gray-50">
                                            <span>Kondisi Ban Baik:</span>
                                            <span class="font-bold flex items-center gap-1" :class="chk.tires_condition ? 'text-emerald-600' : 'text-rose-600'">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" x-show="chk.tires_condition"><polyline points="20 6 9 17 4 12"/></svg>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" x-show="!chk.tires_condition"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                <span x-text="chk.tires_condition ? 'Baik' : 'Bermasalah'"></span>
                                            </span>
                                        </div>
                                        <div class="flex justify-between py-0.5 border-b border-gray-50">
                                            <span>Lampu-Lampu Nyala:</span>
                                            <span class="font-bold flex items-center gap-1" :class="chk.lights_condition ? 'text-emerald-600' : 'text-rose-600'">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" x-show="chk.lights_condition"><polyline points="20 6 9 17 4 12"/></svg>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" x-show="!chk.lights_condition"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                <span x-text="chk.lights_condition ? 'Nyala' : 'Mati/Meredup'"></span>
                                            </span>
                                        </div>
                                        <div class="flex justify-between py-0.5 border-b border-gray-50">
                                            <span>Kaca & Spion Utuh:</span>
                                            <span class="font-bold flex items-center gap-1" :class="chk.glass_mirrors ? 'text-emerald-600' : 'text-rose-600'">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" x-show="chk.glass_mirrors"><polyline points="20 6 9 17 4 12"/></svg>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" x-show="!chk.glass_mirrors"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                <span x-text="chk.glass_mirrors ? 'Utuh' : 'Retak/Pecah'"></span>
                                            </span>
                                        </div>
                                        <div class="flex justify-between py-0.5 border-b border-gray-50">
                                            <span>Fungsi Rem Normal:</span>
                                            <span class="font-bold flex items-center gap-1" :class="chk.brakes_condition ? 'text-emerald-600' : 'text-rose-600'">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" x-show="chk.brakes_condition"><polyline points="20 6 9 17 4 12"/></svg>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" x-show="!chk.brakes_condition"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                <span x-text="chk.brakes_condition ? 'Normal' : 'Bermasalah'"></span>
                                            </span>
                                        </div>
                                        <div class="flex justify-between py-0.5 border-b border-gray-50">
                                            <span>Kebersihan Kabin:</span>
                                            <span class="font-bold flex items-center gap-1" :class="chk.interior_cleanliness ? 'text-emerald-600' : 'text-rose-600'">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" x-show="chk.interior_cleanliness"><polyline points="20 6 9 17 4 12"/></svg>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" x-show="!chk.interior_cleanliness"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                <span x-text="chk.interior_cleanliness ? 'Bersih' : 'Kotor'"></span>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- KELOMPOK 2: KELENGKAPAN SURAT & PERALATAN -->
                                <div class="space-y-1.5 pt-1">
                                    <span class="font-bold text-gray-800 text-[11px] block border-b border-gray-100 pb-0.5 flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2 text-gray-600 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                                        </svg>
                                        <span>Kelengkapan Surat & Peralatan Dinas:</span>
                                    </span>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 text-gray-700 text-[11px]">
                                        <div class="flex justify-between py-0.5 border-b border-gray-50">
                                            <span>STNK Fisik:</span>
                                            <span class="font-bold flex items-center gap-1" :class="chk.has_stnk ? 'text-emerald-600' : 'text-rose-600'">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" x-show="chk.has_stnk"><polyline points="20 6 9 17 4 12"/></svg>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" x-show="!chk.has_stnk"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                <span x-text="chk.has_stnk ? 'Ada' : 'Tidak Ada'"></span>
                                            </span>
                                        </div>
                                        <div class="flex justify-between py-0.5 border-b border-gray-50">
                                            <span>Ban Serep:</span>
                                            <span class="font-bold flex items-center gap-1" :class="chk.has_spare_tire ? 'text-emerald-600' : 'text-rose-600'">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" x-show="chk.has_spare_tire"><polyline points="20 6 9 17 4 12"/></svg>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" x-show="!chk.has_spare_tire"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                <span x-text="chk.has_spare_tire ? 'Ada' : 'Tidak Ada'"></span>
                                            </span>
                                        </div>
                                        <div class="flex justify-between py-0.5 border-b border-gray-50">
                                            <span>Dongkrak & Tools:</span>
                                            <span class="font-bold flex items-center gap-1" :class="chk.has_jack_tools ? 'text-emerald-600' : 'text-rose-600'">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" x-show="chk.has_jack_tools"><polyline points="20 6 9 17 4 12"/></svg>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" x-show="!chk.has_jack_tools"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                <span x-text="chk.has_jack_tools ? 'Ada' : 'Tidak Ada'"></span>
                                            </span>
                                        </div>
                                        <div class="flex justify-between py-0.5 border-b border-gray-50">
                                            <span>Kotak P3K:</span>
                                            <span class="font-bold flex items-center gap-1" :class="chk.has_first_aid ? 'text-emerald-600' : 'text-rose-600'">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" x-show="chk.has_first_aid"><polyline points="20 6 9 17 4 12"/></svg>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" x-show="!chk.has_first_aid"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                <span x-text="chk.has_first_aid ? 'Ada' : 'Tidak Ada'"></span>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- CATATAN KERUSAKAN / KENDALA JIKA ADA -->
                                <template x-if="chk.notes">
                                    <div class="p-2.5 bg-rose-50 text-rose-800 rounded-lg border border-rose-200 text-[11px] space-y-0.5 mt-2">
                                        <strong class="font-bold flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2 shrink-0 text-rose-600" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                                                <line x1="12" y1="9" x2="12" y2="13"/>
                                                <line x1="12" y1="17" x2="12.01" y2="17"/>
                                            </svg>
                                            <span>Catatan Kerusakan / Kendala:</span>
                                        </strong>
                                        <p x-text="chk.notes" class="leading-relaxed"></p>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <!-- CATATAN KHUSUS DARI VERIFIKASI ADMIN LAPANGAN -->
            <template x-if="selectedBooking?.log?.admin_verification_notes">
                <div class="p-3 bg-cyan-50 border border-cyan-200 rounded-xl space-y-1">
                    <strong class="font-bold text-cyan-900 text-[11px] flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2 text-cyan-700 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                        <span>Catatan Verifikasi Fisik Admin Lapangan:</span>
                    </strong>
                    <p x-text="selectedBooking.log.admin_verification_notes" class="text-cyan-950 text-xs italic leading-relaxed"></p>
                </div>
            </template>

            <!-- 4. PRATINJAU DOKUMEN CETAK -->
            <div class="p-3.5 bg-blue-50/70 border border-blue-200 rounded-xl space-y-2">
                <span class="text-[10px] font-bold text-blue-800 uppercase block tracking-wider flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                    </svg>
                    <span>Pratinjau Dokumen Cetak</span>
                </span>
                
                <template x-if="approverActionType === 'waiting'">
                    <div class="flex flex-wrap gap-2">
                        <a :href="selectedBooking ? `/bookings/${selectedBooking.letter_slug}/pdf/formulir-permohonan` : '#'" target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-blue-300 text-blue-700 hover:bg-blue-100 font-bold rounded-lg text-xs transition shadow-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                            </svg>
                            <span>Lihat Formulir Permohonan</span>
                        </a>
                        <a :href="selectedBooking ? `/bookings/${selectedBooking.letter_slug}/pdf/bast-peminjaman` : '#'" target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg text-xs transition shadow-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                            </svg>
                            <span>Lihat BAST Peminjaman</span>
                        </a>
                    </div>
                </template>

                <template x-if="approverActionType === 'checked'">
                    <div class="flex flex-wrap gap-2">
                        <a :href="selectedBooking ? `/bookings/${selectedBooking.letter_slug}/pdf/formulir-pengembalian` : '#'" target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-blue-300 text-blue-700 hover:bg-blue-100 font-bold rounded-lg text-xs transition shadow-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                            </svg>
                            <span>Lihat Formulir Pengembalian</span>
                        </a>
                        <a :href="selectedBooking ? `/bookings/${selectedBooking.letter_slug}/pdf/bast-pengembalian` : '#'" target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs transition shadow-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                            </svg>
                            <span>Lihat BAST Pengembalian</span>
                        </a>
                    </div>
                </template>
            </div>

            <!-- 5. KEPUTUSAN APPROVER -->
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1.5">Keputusan Persetujuan <span class="text-rose-500">*</span></label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="cursor-pointer p-2.5 rounded-xl border text-center text-xs font-bold transition flex items-center justify-center gap-1.5"
                        :class="approverForm.action === 'approve' ? 'bg-emerald-50 border-emerald-500 text-emerald-700 shadow-xs' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
                        <input type="radio" name="action_option" value="approve" x-model="approverForm.action" class="hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                        <span>Setujui Dokumen</span>
                    </label>

                    <label class="cursor-pointer p-2.5 rounded-xl border text-center text-xs font-bold transition flex items-center justify-center gap-1.5"
                        :class="approverForm.action === 'reject' ? 'bg-rose-50 border-rose-500 text-rose-700 shadow-xs' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
                        <input type="radio" name="action_option" value="reject" x-model="approverForm.action" class="hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="15" y1="9" x2="9" y2="15"/>
                            <line x1="9" y1="9" x2="15" y2="15"/>
                        </svg>
                        <span>Tolak / Kembalikan</span>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">
                    <span x-text="approverForm.action === 'reject' ? 'Alasan Penolakan' : 'Catatan Persetujuan (Opsional)'"></span>
                    <span x-show="approverForm.action === 'reject'" class="text-rose-500">*</span>
                </label>
                <textarea name="admin_note" x-model="approverForm.admin_note" rows="2"
                        :required="approverForm.action === 'reject'"
                        :placeholder="approverForm.action === 'reject' ? 'Tuliskan alasan penolakan permohonan...' : 'Tuliskan catatan persetujuan jika ada...'"
                        class="block w-full rounded-xl border-gray-300 text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500"></textarea>
            </div>

            <div class="flex items-center gap-3 pt-3 border-t border-gray-100 shrink-0">
                <button type="button" @click="showApproverModal = false" class="w-1/2 py-2.5 bg-gray-100 text-gray-700 text-xs font-bold rounded-xl hover:bg-gray-200 transition">
                    Batal
                </button>
                
                <button type="submit" 
                        class="w-1/2 py-2.5 text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center justify-center gap-1.5"
                        :class="approverForm.action === 'approve' ? (approverActionType === 'waiting' ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-emerald-600 hover:bg-emerald-700') : 'bg-rose-600 hover:bg-rose-700'">
                    <template x-if="approverForm.action === 'approve'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                            <polyline points="17 21 17 13 7 13 7 21"/>
                            <polyline points="7 3 7 8 15 8"/>
                        </svg>
                    </template>
                    <template x-if="approverForm.action === 'reject'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="15" y1="9" x2="9" y2="15"/>
                            <line x1="9" y1="9" x2="15" y2="15"/>
                        </svg>
                    </template>
                    <span x-text="approverForm.action === 'approve' ? 'Konfirmasi & Setujui' : 'Konfirmasi Penolakan'"></span>
                </button>
            </div>
        </form>

    </div>
</div>