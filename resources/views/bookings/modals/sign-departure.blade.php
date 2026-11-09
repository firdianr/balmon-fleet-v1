<!-- MODAL TANDA TANGAN KEBERANGKATAN (KHUSUS PEMOHON) -->
<div x-show="showSignDepartureModal" 
    class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 top-0" 
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    x-cloak>
    
    <div class="bg-white rounded-2xl shadow-xl max-w-xl w-full max-h-[90vh] flex flex-col overflow-hidden border border-gray-100" @click.away="showSignDepartureModal = false">
        
        <!-- HEADER MODAL EMERALD -->
        <div class="bg-emerald-600 px-6 py-4 text-white flex justify-between items-center shrink-0">
            <h3 class="font-bold text-base flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-emerald-200 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 20h9"/>
                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                </svg>
                <span>Tanda Tangan Pengesahan BAST Peminjaman</span>
            </h3>
            <button type="button" @click="showSignDepartureModal = false" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <form x-ref="signDepartureFormElement"
              :action="`/bookings/${signDepartureForm.letter_slug}/sign-departure`" 
              method="POST" 
              @submit.prevent="submitSignDepartureForm()"
              class="p-6 overflow-y-auto space-y-4 text-xs flex-1">
            @csrf
            @method('PATCH')

            <input type="hidden" name="signature" x-model="signDepartureForm.signatureData">

            <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200 text-emerald-900 leading-relaxed">
                Log keberangkatan telah diisi oleh driver/petugas. Mohon periksa hasil pemeriksaan fisik di bawah ini sebelum menandatangani <strong>BAST Peminjaman Armada</strong>.
            </div>

            {{-- RINGKASAN LOG KEBERANGKATAN DARI DRIVER --}}
            <div class="space-y-3 bg-gray-50/80 p-3.5 rounded-xl border border-gray-200" 
                 x-data="{ 
                     getStartItems(category) {
                         if (!selectedBooking) return [];
                         const chk = (selectedBooking.checklists || []).find(c => c.type === 'start');
                         if (!chk || !chk.items) return [];
                         
                         let items = [];
                         if (typeof chk.items === 'string') {
                             try { items = JSON.parse(chk.items); } catch(e) { items = []; }
                         } else if (Array.isArray(chk.items)) {
                             items = chk.items;
                         }

                         return category ? items.filter(i => i.category === category) : items;
                     }
                 }">

                <h4 class="font-bold text-gray-800 text-xs border-b border-gray-200 pb-1.5 flex items-center justify-between">
                    <span class="flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-emerald-600 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 11l3 3L22 4"/>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                        </svg>
                        <span>Hasil Pemeriksaan Keberangkatan</span>
                    </span>
                    <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-100 px-2.5 py-0.5 rounded-full" 
                          x-text="`BBM Awal: ${selectedBooking?.log?.start_fuel_level || '-'}`">
                    </span>
                </h4>

                {{-- SUB-SEKSI 1: KONDISI FISIK KENDARAAN --}}
                <div class="space-y-1.5">
                    <div class="font-bold text-gray-700 text-[11px] flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>A. Kondisi Fisik Armada</span>
                    </div>
                    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
                        <table class="w-full text-left text-[11px]">
                            <thead class="bg-gray-100 text-gray-600 font-semibold border-b border-gray-200">
                                <tr>
                                    <th class="py-1.5 px-2.5">Item Fisik</th>
                                    <th class="py-1.5 px-2.5 text-center w-28">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-gray-700">
                                <template x-if="getStartItems('condition').length > 0">
                                    <template x-for="item in getStartItems('condition')" :key="item.id || item.name">
                                        <tr>
                                            <td class="py-1.5 px-2.5 font-medium" x-text="item.name"></td>
                                            <td class="py-1.5 px-2.5 text-center font-bold" 
                                                :class="item.is_checked ? 'text-emerald-600' : 'text-rose-600'" 
                                                x-text="item.is_checked ? '✓ Normal' : '✗ Bermasalah'">
                                            </td>
                                        </tr>
                                    </template>
                                </template>
                                <template x-if="getStartItems('condition').length === 0">
                                    <tr>
                                        <td colspan="2" class="py-2 px-2.5 text-center text-gray-400 italic">Data kondisi fisik tidak tersedia</td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- SUB-SEKSI 2: KELENGKAPAN SURAT & PERALATAN --}}
                <div class="space-y-1.5 pt-1">
                    <div class="font-bold text-gray-700 text-[11px] flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        <span>B. Kelengkapan Berkas & Peralatan</span>
                    </div>
                    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
                        <table class="w-full text-left text-[11px]">
                            <thead class="bg-gray-100 text-gray-600 font-semibold border-b border-gray-200">
                                <tr>
                                    <th class="py-1.5 px-2.5">Item Kelengkapan</th>
                                    <th class="py-1.5 px-2.5 text-center w-28">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-gray-700">
                                <template x-if="getStartItems('completeness').length > 0">
                                    <template x-for="item in getStartItems('completeness')" :key="item.id || item.name">
                                        <tr>
                                            <td class="py-1.5 px-2.5 font-medium" x-text="item.name"></td>
                                            <td class="py-1.5 px-2.5 text-center font-bold" 
                                                :class="item.is_checked ? 'text-emerald-600' : 'text-rose-600'" 
                                                x-text="item.is_checked ? '✓ Ada' : '✗ Tidak Ada'">
                                            </td>
                                        </tr>
                                    </template>
                                </template>
                                <template x-if="getStartItems('completeness').length === 0">
                                    <tr>
                                        <td colspan="2" class="py-2 px-2.5 text-center text-gray-400 italic">Data kelengkapan tidak tersedia</td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- CATATAN & FOTO KENDALA KEBERANGKATAN DARI DRIVER JIKA ADA --}}
                <template x-if="selectedBooking?.log?.condition_notes?.start || (selectedBooking?.log?.condition_photos?.start && selectedBooking.log.condition_photos.start.length > 0)">
                    <div class="p-3 bg-rose-50/90 rounded-xl border border-rose-200 text-rose-900 text-[11px] space-y-2">
                        <div class="font-bold flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-rose-600 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="8" x2="12" y2="12"/>
                                <line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                            <span>Catatan & Foto Kendala Awal:</span>
                        </div>

                        {{-- TEKS CATATAN KENDALA AWAL --}}
                        <template x-if="selectedBooking?.log?.condition_notes?.start">
                            <p class="pl-5 italic leading-relaxed font-medium text-rose-950" x-text="selectedBooking?.log?.condition_notes?.start"></p>
                        </template>

                        {{-- PREVIEW GALLERY FOTO KENDALA AWAL --}}
                        <template x-if="selectedBooking?.log?.condition_photos?.start && selectedBooking.log.condition_photos.start.length > 0">
                            <div class="pt-1.5">
                                <span class="block text-[10px] font-bold text-rose-800 mb-1">Foto Bukti Kerusakan / Insiden Awal:</span>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="(photoPath, idx) in selectedBooking.log.condition_photos.start" :key="idx">
                                        <a :href="`/storage/${photoPath}`" target="_blank" class="group relative rounded-lg overflow-hidden border border-rose-300 bg-white shadow-xs">
                                            <img :src="`/storage/${photoPath}`" class="h-20 w-20 object-cover group-hover:scale-105 transition duration-150" alt="Foto Insiden Awal">
                                            <span class="absolute bottom-0.5 right-0.5 bg-rose-900/75 text-white text-[8px] px-1 rounded backdrop-blur-xs">↗</span>
                                        </a>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <!-- AREA TANDA TANGAN PEMOHON -->
            <div class="space-y-1.5 pt-1 border-t border-gray-100">
                <div class="flex items-center justify-between gap-2">
                    <label class="font-bold text-gray-800">Tanda Tangan Pemohon <span class="text-rose-500">*</span></label>
                    <button type="button" @click="clearSignDeparture()" class="px-2.5 py-1 bg-rose-50 text-rose-600 border border-rose-200 rounded-lg text-[11px] font-bold hover:bg-rose-100 transition">
                        Bersihkan
                    </button>
                </div>

                <div class="border-2 border-dashed border-gray-300 rounded-xl bg-white p-1">
                    <canvas x-ref="signDepartureCanvas" class="w-full h-36 bg-white rounded-lg cursor-pointer touch-none block"></canvas>
                </div>

                <template x-if="signDepartureForm.signatureError">
                    <p class="text-[11px] font-bold text-rose-600 mt-1" x-text="signDepartureForm.signatureError"></p>
                </template>
            </div>

            <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2 shrink-0">
                <button type="button" @click="showSignDepartureModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-xs transition flex items-center gap-1.5">
                    <span>Sah & Mulai Perjalanan</span>
                </button>
            </div>
        </form>

    </div>
</div>