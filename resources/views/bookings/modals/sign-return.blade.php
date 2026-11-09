<!-- MODAL TANDA TANGAN KEPULANGAN (KHUSUS PEMOHON) -->
<div x-show="showSignReturnModal" 
    class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 top-0" 
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    x-cloak>
    
    <div class="bg-white rounded-2xl shadow-xl max-w-xl w-full max-h-[90vh] flex flex-col overflow-hidden border border-gray-100" @click.away="showSignReturnModal = false">
        
        <!-- HEADER MODAL PURPLE -->
        <div class="bg-purple-600 px-6 py-4 text-white flex justify-between items-center shrink-0">
            <h3 class="font-bold text-base flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-purple-200 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 20h9"/>
                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                </svg>
                <span>Tanda Tangan Pengesahan Pengembalian</span>
            </h3>
            <button type="button" @click="showSignReturnModal = false" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <form x-ref="signReturnFormElement"
              :action="`/bookings/${signReturnForm.letter_slug}/sign-return`" 
              method="POST" 
              enctype="multipart/form-data"
              @submit.prevent="submitSignReturnForm()"
              class="p-6 overflow-y-auto space-y-4 text-xs flex-1">
            @csrf
            @method('PATCH')

            <!-- INPUT HIDDEN SIGNATURE -->
            <input type="hidden" name="signature" x-model="signReturnForm.signatureData">

            <div class="p-3 bg-purple-50 rounded-xl border border-purple-200 text-purple-900 leading-relaxed">
                Log pengembalian kendaraan telah diisi. Mohon periksa hasil pemeriksaan fisik kepulangan di bawah ini sebelum menandatangani <strong>Formulir & BAST Pengembalian Armada</strong>.
            </div>

            {{-- DOKUMEN SURAT TUGAS SUSULAN (KHUSUS PEMINJAMAN NOTA DINAS) --}}
            <template x-if="selectedBooking?.letter_type === 'nota_dinas'">
                <div class="p-3.5 bg-amber-50/60 rounded-xl border border-amber-200 space-y-3">
                    <div class="flex items-center gap-1.5 text-amber-900 font-bold text-xs">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-amber-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10 9 9 9 8 9"/>
                        </svg>
                        <span>Kelengkapan Surat Tugas (Pengembalian Nota Dinas)</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-gray-700 text-xs">
                                Nomor Surat Tugas <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" 
                                name="return_letter_number" 
                                x-model="signReturnForm.return_letter_number"
                                :required="selectedBooking?.letter_type === 'nota_dinas'"
                                placeholder="Contoh: 510/Balmon.33/KP.01.06/09/2026"
                                class="w-full px-3 py-2 bg-white border border-amber-300 rounded-xl text-xs font-medium focus:ring-amber-500 focus:border-amber-500">
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-gray-700 text-xs">
                                File Surat Tugas (PDF) <span class="text-rose-500">*</span>
                            </label>
                            
                            <input type="file" 
                                name="return_letter_file" 
                                accept="application/pdf"
                                :required="selectedBooking?.letter_type === 'nota_dinas'"
                                class="w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-amber-100 file:text-amber-800 hover:file:bg-amber-200 transition cursor-pointer">
                            
                            <p class="text-[11px] text-gray-400">
                                * Format: PDF saja (Maksimal 5 MB)
                            </p>
                        </div>
                    </div>
                </div>
            </template>

            {{-- RINGKASAN LOG KEPULANGAN DARI DRIVER --}}
            <div class="space-y-3 bg-gray-50/80 p-3.5 rounded-xl border border-gray-200" 
                 x-data="{ 
                     getEndItems(category) {
                         if (!selectedBooking) return [];
                         const chk = (selectedBooking.checklists || []).find(c => c.type === 'end');
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
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-purple-600 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 11l3 3L22 4"/>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                        </svg>
                        <span>Hasil Pemeriksaan Kepulangan</span>
                    </span>
                    <span class="text-[11px] font-semibold text-purple-700 bg-purple-100 px-2.5 py-0.5 rounded-full" 
                          x-text="`BBM Akhir: ${selectedBooking?.log?.end_fuel_level || '-'}`">
                    </span>
                </h4>

                {{-- SUB-SEKSI 1: KONDISI FISIK KENDARAAN --}}
                <div class="space-y-1.5">
                    <div class="font-bold text-gray-700 text-[11px] flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>A. Kondisi Fisik Kepulangan</span>
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
                                <template x-if="getEndItems('condition').length > 0">
                                    <template x-for="item in getEndItems('condition')" :key="item.id || item.name">
                                        <tr>
                                            <td class="py-1.5 px-2.5 font-medium" x-text="item.name"></td>
                                            <td class="py-1.5 px-2.5 text-center font-bold" 
                                                :class="item.is_checked ? 'text-emerald-600' : 'text-rose-600'" 
                                                x-text="item.is_checked ? '✓ Normal' : '✗ Bermasalah'">
                                            </td>
                                        </tr>
                                    </template>
                                </template>
                                <template x-if="getEndItems('condition').length === 0">
                                    <tr>
                                        <td colspan="2" class="py-2 px-2.5 text-center text-gray-400 italic">Data fisik tidak tersedia</td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- SUB-SEKSI 2: KELENGKAPAN SURAT & PERALATAN --}}
                <div class="space-y-1.5 pt-1">
                    <div class="font-bold text-gray-700 text-[11px] flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
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
                                <template x-if="getEndItems('completeness').length > 0">
                                    <template x-for="item in getEndItems('completeness')" :key="item.id || item.name">
                                        <tr>
                                            <td class="py-1.5 px-2.5 font-medium" x-text="item.name"></td>
                                            <td class="py-1.5 px-2.5 text-center font-bold" 
                                                :class="item.is_checked ? 'text-emerald-600' : 'text-rose-600'" 
                                                x-text="item.is_checked ? '✓ Ada' : '✗ Tidak Ada'">
                                            </td>
                                        </tr>
                                    </template>
                                </template>
                                <template x-if="getEndItems('completeness').length === 0">
                                    <tr>
                                        <td colspan="2" class="py-2 px-2.5 text-center text-gray-400 italic">Data kelengkapan tidak tersedia</td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- INFORMASI STATUS KEBERSIHAN CUCI MOBIL + PREVIEW NOTA --}}
                <template x-if="(selectedBooking?.checklists || []).find(c => c.type === 'end')">
                    <div class="space-y-2 p-2.5 bg-white rounded-xl border border-gray-200"
                        x-data="{ 
                            endChecklist() {
                                return (selectedBooking?.checklists || []).find(c => c.type === 'end');
                            }
                        }">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="font-medium text-gray-600">Status Kebersihan Armada:</span>
                            <span class="font-bold" 
                                :class="endChecklist()?.is_washed ? 'text-emerald-600' : 'text-amber-600'" 
                                x-text="endChecklist()?.is_washed ? '✓ Sudah Dicuci Bersih' : '⚠ Belum / Tidak Dicuci'">
                            </span>
                        </div>

                        {{-- PREVIEW FOTO NOTA / BUKTI CUCI MOBIL --}}
                        <template x-if="endChecklist()?.wash_receipt_photo">
                            <div class="pt-2 border-t border-gray-100">
                                <span class="block text-[10px] font-bold text-gray-500 mb-1">Bukti Nota / Cuci Mobil:</span>
                                <a :href="`/storage/${endChecklist().wash_receipt_photo}`" target="_blank" class="inline-block group relative rounded-lg overflow-hidden border border-gray-200">
                                    <img :src="`/storage/${endChecklist().wash_receipt_photo}`" class="h-24 w-auto object-cover rounded-lg group-hover:opacity-90 transition" alt="Bukti Cuci Mobil">
                                    <span class="absolute bottom-1 right-1 bg-black/60 text-white text-[9px] px-1.5 py-0.5 rounded backdrop-blur-xs">Buka Foto ↗</span>
                                </a>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- CATATAN & FOTO KENDALA KEPULANGAN --}}
                <template x-if="selectedBooking?.log?.condition_notes?.end || (selectedBooking?.log?.condition_photos?.end && selectedBooking.log.condition_photos.end.length > 0)">
                    <div class="p-3 bg-rose-50/90 rounded-xl border border-rose-200 text-rose-900 text-[11px] space-y-2">
                        <div class="font-bold flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-rose-600 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="8" x2="12" y2="12"/>
                                <line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                            <span>Catatan & Foto Kendala Kepulangan:</span>
                        </div>

                        {{-- TEKS CATATAN --}}
                        <template x-if="selectedBooking?.log?.condition_notes?.end">
                            <p class="pl-5 italic leading-relaxed font-medium text-rose-950" x-text="selectedBooking?.log?.condition_notes?.end"></p>
                        </template>

                        {{-- PREVIEW GALLERY FOTO KENDALA KEPULANGAN --}}
                        <template x-if="selectedBooking?.log?.condition_photos?.end && selectedBooking.log.condition_photos.end.length > 0">
                            <div class="pt-1.5">
                                <span class="block text-[10px] font-bold text-rose-800 mb-1">Foto Kerusakan / Insiden:</span>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="(photoPath, idx) in selectedBooking.log.condition_photos.end" :key="idx">
                                        <a :href="`/storage/${photoPath}`" target="_blank" class="group relative rounded-lg overflow-hidden border border-rose-300 bg-white shadow-xs">
                                            <img :src="`/storage/${photoPath}`" class="h-20 w-20 object-cover group-hover:scale-105 transition duration-150" alt="Foto Insiden">
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
                    <button type="button" @click="clearSignReturn()" class="px-2.5 py-1 bg-rose-50 text-rose-600 border border-rose-200 rounded-lg text-[11px] font-bold hover:bg-rose-100 transition">
                        Bersihkan
                    </button>
                </div>

                <div class="border-2 border-dashed border-gray-300 rounded-xl bg-white p-1">
                    <canvas x-ref="signReturnCanvas" class="w-full h-36 bg-white rounded-lg cursor-pointer touch-none block"></canvas>
                </div>

                <template x-if="signReturnForm.signatureError">
                    <p class="text-[11px] font-bold text-rose-600 mt-1" x-text="signReturnForm.signatureError"></p>
                </template>
            </div>

            <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2 shrink-0">
                <button type="button" @click="showSignReturnModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl shadow-xs transition flex items-center gap-1.5">
                    <span>Sah & Ajukan Pengembalian</span>
                </button>
            </div>
        </form>

    </div>
</div>