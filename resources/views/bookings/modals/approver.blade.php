<!-- MODAL APPROVER DENGAN PRATINJAU CHECKLIST DETIL (COLLAPSIBLE), FOTO BUKTI & SIGNATURE PAD -->
<div x-show="showApproverModal" 
    class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 top-0"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    x-cloak>

    {{-- DIBERSIHKAN DARI x-data SEHINGGA CANVAS TTD DAPAT TERDETERMINASI KEMBALI --}}
    <div class="bg-white rounded-2xl shadow-xl max-w-xl w-full max-h-[92vh] flex flex-col overflow-hidden border border-gray-100" 
         @click.away="showApproverModal = false">
        
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

        <form x-ref="approverFormElement"
              :action="selectedBooking ? `/bookings/${selectedBooking.letter_slug}/approver-review` : '#'" 
              method="POST" 
              @submit.prevent="submitApproverReviewForm()"
              class="p-6 overflow-y-auto space-y-4 text-xs flex-1">
            @csrf
            @method('PATCH')

            <input type="hidden" name="action" :value="approverForm.action">
            <input type="hidden" name="signature" x-model="approverForm.signatureData">

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

            <!-- 2. PRATINJAU BUKTI FOTO (SPEEDOMETER, CUCI MOBIL) -->
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
                </div>
            </template>

            <!-- 3. RINCIAN COMPLETE ITEM CHECKLIST KENDARAAN (FITUR COLLAPSIBLE TOGGLE) -->
            <template x-if="selectedBooking && selectedBooking.displayChecklists && selectedBooking.displayChecklists.length > 0">
                <div class="space-y-3 p-3.5 bg-slate-50 border border-slate-200 rounded-xl">
                    <!-- HEADER COLLAPSIBLE TOGGLE -->
                    <button type="button" 
                            @click="showChecklistDetail = !showChecklistDetail" 
                            class="w-full flex items-center justify-between font-bold text-slate-800 text-xs border-b border-slate-200 pb-1.5 focus:outline-hidden cursor-pointer hover:text-indigo-600 transition">
                        <span class="flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 text-slate-700 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                                <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                                <path d="m9 14 2 2 4-4"/>
                            </svg>
                            <span x-text="approverActionType === 'waiting' ? 'Detail Kondisi Fisik Terakhir Armada' : 'Rincian Kondisi Armada'"></span>
                        </span>

                        <div class="flex items-center gap-1.5 text-[11px] text-indigo-600">
                            <span x-text="showChecklistDetail ? 'Sembunyikan' : 'Tampilkan Detail'"></span>
                            <svg xmlns="http://www.w3.org/2000/svg" 
                                 class="w-4 h-4 fill-none stroke-current stroke-2 transition-transform duration-200" 
                                 :class="showChecklistDetail ? 'rotate-180' : ''" 
                                 viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </div>
                    </button>

                    <!-- ISI COLLAPSIBLE DETAIL -->
                    <div x-show="showChecklistDetail" x-collapse class="space-y-3 pt-1">
                        <template x-for="chk in selectedBooking.displayChecklists" :key="chk.id">
                            <div class="p-3 rounded-xl border bg-white space-y-3 shadow-2xs"
                                :class="chk.type === 'start' ? 'border-emerald-200' : 'border-indigo-200'">
                                
                                <div class="flex justify-between items-center border-b border-gray-100 pb-1.5">
                                    <span class="font-bold text-[10px] uppercase px-2 py-0.5 rounded-md tracking-wide flex items-center gap-1"
                                        :class="chk.type === 'start' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-indigo-100 text-indigo-800 border border-indigo-200'">
                                        <span x-text="chk.type === 'start' ? 'Keberangkatan (Pre-Trip)' : 'Pengembalian (Post-Trip)'"></span>
                                    </span>
                                    
                                    <template x-if="chk.is_washed">
                                        <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                                            Kondisi Bersih
                                        </span>
                                    </template>
                                </div>

                                <!-- KONDISI FISIK DINAMIS + BADGE 'SUDAH DIPERBAIKI' -->
                                <div class="space-y-1.5" x-show="chk.items && chk.items.some(i => i.category === 'condition')">
                                    <span class="font-bold text-gray-800 text-[11px] block border-b border-gray-100 pb-0.5">Kondisi Fisik Kendaraan:</span>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 text-gray-700 text-[11px]">
                                        <template x-for="item in (chk.items || []).filter(i => i.category === 'condition')" :key="item.id">
                                            <div class="flex justify-between items-center py-1 border-b border-gray-50">
                                                <span x-text="item.name + ':'"></span>
                                                <div class="flex items-center gap-1.5">
                                                    <!-- JIKA FALSE TAPI MOBIL SUDAH AVAILABLE (SUDAH DIPERBAIKI) -->
                                                    <template x-if="!item.is_checked && selectedBooking?.vehicle?.status === 'available'">
                                                        <span class="text-[9px] font-bold px-1.5 py-0.5 bg-emerald-100 text-emerald-800 border border-emerald-300 rounded">
                                                            Sudah Diperbaiki
                                                        </span>
                                                    </template>
                                                    
                                                    <span class="font-bold" :class="item.is_checked ? 'text-emerald-600' : 'text-rose-600'" 
                                                          x-text="item.is_checked ? 'Baik / Normal' : 'Bermasalah'"></span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- KELENGKAPAN DINAS DINAMIS + BADGE 'SUDAH DIPERBAIKI' -->
                                <div class="space-y-1.5 pt-1" x-show="chk.items && chk.items.some(i => i.category === 'completeness')">
                                    <span class="font-bold text-gray-800 text-[11px] block border-b border-gray-100 pb-0.5">Kelengkapan Kendaraan Dinas:</span>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 text-gray-700 text-[11px]">
                                        <template x-for="item in (chk.items || []).filter(i => i.category === 'completeness')" :key="item.id">
                                            <div class="flex justify-between items-center py-1 border-b border-gray-50">
                                                <span x-text="item.name + ':'"></span>
                                                <div class="flex items-center gap-1.5">
                                                    <!-- JIKA FALSE TAPI MOBIL SUDAH AVAILABLE (SUDAH DIPERBAIKI) -->
                                                    <template x-if="!item.is_checked && selectedBooking?.vehicle?.status === 'available'">
                                                        <span class="text-[9px] font-bold px-1.5 py-0.5 bg-emerald-100 text-emerald-800 border border-emerald-300 rounded">
                                                            Sudah Diperbaiki
                                                        </span>
                                                    </template>

                                                    <span class="font-bold" :class="item.is_checked ? 'text-emerald-600' : 'text-rose-600'" 
                                                          x-text="item.is_checked ? 'Ada' : 'Tidak Ada'"></span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- CATATAN KERUSAKAN (CONDITION NOTE) & FOTO INSIDEN -->
                                <template x-if="chk.type === 'start' ? (selectedBooking.startNotes || selectedBooking.parsedStartPhotos.length > 0) : (selectedBooking.endNotes || selectedBooking.parsedEndPhotos.length > 0)">
                                    <div class="p-2.5 bg-rose-50 text-rose-800 rounded-lg border border-rose-200 text-[11px] space-y-2 mt-2">
                                        <div class="flex items-center justify-between">
                                            <strong class="font-bold flex items-center gap-1 text-rose-900">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-rose-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                                                    <line x1="12" y1="9" x2="12" y2="13"/>
                                                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                                                </svg>
                                                <span>Catatan Kerusakan / Kendala:</span>
                                            </strong>

                                            <!-- PEMBERITAHUAN JIKA FLEET SUDAH REPAIRED/AVAILABLE -->
                                            <template x-if="selectedBooking?.vehicle?.status === 'available'">
                                                <span class="text-[9px] font-bold px-2 py-0.5 bg-emerald-600 text-white rounded-full">
                                                    Armada Sudah Diperbaiki
                                                </span>
                                            </template>
                                        </div>

                                        {{-- CONDITION NOTE (TEKS CATATAN DARI JSON CONDITION_NOTES) --}}
                                        <template x-if="chk.type === 'start' ? selectedBooking.startNotes : selectedBooking.endNotes">
                                            <div class="bg-white/70 p-2 rounded-md border border-rose-200/60">
                                                <span class="text-[10px] text-gray-500 font-bold block mb-0.5">Catatan Fisik (Condition Note):</span>
                                                <p x-text="chk.type === 'start' ? selectedBooking.startNotes : selectedBooking.endNotes" class="leading-relaxed font-bold text-rose-950"></p>
                                            </div>
                                        </template>

                                        {{-- CONDITION PHOTOS (BUKTI FOTO KERUSAKAN) --}}
                                        <template x-if="(chk.type === 'start' ? selectedBooking.parsedStartPhotos : selectedBooking.parsedEndPhotos).length > 0">
                                            <div class="pt-1 border-t border-rose-200/60">
                                                <span class="text-[10px] font-bold text-rose-800 block mb-1">Bukti Foto Insiden / Kerusakan (Condition Photos):</span>
                                                <div class="flex flex-wrap gap-2">
                                                    <template x-for="(photo, index) in (chk.type === 'start' ? selectedBooking.parsedStartPhotos : selectedBooking.parsedEndPhotos)" :key="index">
                                                        <a :href="`/storage/${photo}`" target="_blank">
                                                            <img :src="`/storage/${photo}`" class="h-16 w-16 object-cover rounded-lg border border-rose-300 hover:scale-105 transition shadow-2xs">
                                                        </a>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
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

            <!-- ARENA TANDA TANGAN APPROVER -->
            <div x-show="approverForm.action === 'approve'" class="space-y-1.5 pt-1">
                <div class="flex items-center justify-between gap-2 mb-2">
                    <label class="text-xs font-bold text-gray-700 flex items-center gap-1 leading-snug">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-indigo-600 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 20h9"/>
                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                        </svg>
                        <span x-text="approverForm.existingSignatureUrl ? 'Tanda Tangan Persetujuan (Sudah Tersedia)' : 'Tanda Tangan Pengesahan Approver'"></span> 
                        <span class="text-rose-500">*</span>
                    </label>

                    <button type="button" 
                            @click="clearApproverSignature()" 
                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-600 hover:text-rose-700 border border-rose-200 rounded-lg text-[11px] font-bold transition shrink-0 shadow-2xs cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 6h18"/>
                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                        </svg>
                        <span>Bersihkan</span>
                    </button>
                </div>

                <template x-if="approverForm.existingSignatureUrl">
                    <div class="mb-2 p-2 bg-indigo-50/50 border border-indigo-100 rounded-xl flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <img :src="approverForm.existingSignatureUrl" class="h-10 border bg-white rounded p-1 object-contain">
                            <span class="text-[11px] font-semibold text-indigo-900">TTD Approver tersimpan. Coret canvas di bawah jika ingin memperbarui TTD.</span>
                        </div>
                    </div>
                </template>

                <div class="border-2 border-dashed border-gray-300 rounded-xl bg-white p-1 relative">
                    <canvas x-ref="approverSignatureCanvas" 
                            style="mix-blend-mode: normal;"
                            class="w-full h-36 bg-white rounded-lg cursor-pointer touch-none block"></canvas>
                </div>

                <template x-if="approverForm.signatureError">
                    <p class="text-[11px] font-bold text-rose-600 mt-1" x-text="approverForm.signatureError"></p>
                </template>
            </div>

            <!-- CATATAN APPROVER -->
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

            <!-- TOMBOL AKSI MODAL -->
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