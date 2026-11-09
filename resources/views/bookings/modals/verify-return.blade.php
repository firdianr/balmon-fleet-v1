<!-- MODAL VERIFIKASI PEMERIKSAAN FISIK DINAMIS OLEH ADMIN LAPANGAN -->
<div x-show="showVerifyReturnModal" 
    class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 top-0"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    x-cloak>

    <div class="bg-white rounded-2xl shadow-xl max-w-xl w-full max-h-[90vh] flex flex-col overflow-hidden border border-gray-100" @click.away="showVerifyReturnModal = false">
        
        <!-- HEADER MODAL CYAN -->
        <div class="bg-cyan-600 px-6 py-4 text-white flex justify-between items-center shrink-0">
            <div>
                <h3 class="font-bold text-base flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-cyan-200 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        <path d="m9 11 2 2 4-4"/>
                    </svg>
                    <span>Verifikasi Physical Check Kepulangan</span>
                </h3>
                <p class="text-xs text-cyan-100 font-mono mt-0.5" x-text="selectedBooking ? 'No. Surat: ' + selectedBooking.letter_number : ''"></p>
            </div>

            <button type="button" @click="showVerifyReturnModal = false" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <form x-ref="verifyReturnFormElement"
              :action="selectedBooking ? `/bookings/${selectedBooking.letter_slug}/verify-return` : '#'" 
              method="POST" 
              @submit.prevent="submitVerifyReturnForm()"
              class="p-6 overflow-y-auto space-y-4 text-xs flex-1">
            @csrf
            @method('PATCH')

            <!-- INPUT HIDDEN: SNAPSHOT ITEMS CHECKLIST (JSON) & TANDA TANGAN -->
            <input type="hidden" name="items" :value="JSON.stringify(verifyReturnForm.items)">
            <input type="hidden" name="signature" x-model="verifyReturnForm.signatureData">

            {{-- 0. CARD LAYANAN AKSES DOKUMEN FORMULIR PENGEMBALIAN (PDF) --}}
            <template x-if="selectedBooking?.letter_slug">
                <div class="p-3 bg-gradient-to-r from-slate-50 to-cyan-50/40 rounded-xl border border-cyan-100 flex items-center justify-between gap-3 shadow-2xs">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-cyan-100 text-cyan-700 flex items-center justify-center shrink-0">
                            {{-- Icon Dokumen / Berkas PDF --}}
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                                <polyline points="10 9 9 9 8 9"/>
                            </svg>
                        </div>
                        <div>
                            <span class="font-bold text-gray-800 text-xs block">Formulir Pengembalian</span>
                            <span class="text-[10px] text-gray-500 block">Dokumen pengesahan fisik pengembalian armada</span>
                        </div>
                    </div>

                    <a :href="`/bookings/${selectedBooking.letter_slug}/pdf/formulir-pengembalian`" 
                       target="_blank" 
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-bold transition shadow-xs shrink-0">
                        {{-- Icon Mata / View PDF --}}
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <span>Lihat Dokumen</span>
                    </a>
                </div>
            </template>

            {{-- 1. PRATINJAU BBM & SPEEDOMETER DRIVER --}}
            <div class="bg-cyan-50/60 p-3.5 rounded-xl border border-cyan-200 space-y-2">
                <span class="text-[10px] font-bold text-cyan-800 uppercase tracking-wider block flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-cyan-700 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="22" x2="15" y2="22"/>
                        <path d="M4 9h10"/>
                        <path d="M14 22V4a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v18"/>
                        <path d="M14 13h2a2 2 0 0 1 2 2v2a2 2 0 0 0 2 2 2 2 0 0 0 2-2V9.83a2 2 0 0 0-.59-1.42L18 5.5"/>
                    </svg>
                    <span>1. Pembacaan Log BBM & Foto Speedometer Driver</span>
                </span>
                <div class="grid grid-cols-2 gap-2 text-gray-800">
                    <div>
                        <span class="text-[10px] text-gray-500 block">BBM Awal:</span>
                        <p class="font-bold" x-text="selectedBooking?.log?.start_fuel_level || '-'"></p>
                    </div>
                    <div>
                        <span class="text-[10px] text-gray-500 block">BBM Akhir:</span>
                        <p class="font-bold text-indigo-700" x-text="selectedBooking?.log?.end_fuel_level || '-'"></p>
                    </div>
                </div>
                <template x-if="selectedBooking?.log?.end_photo">
                    <div class="pt-1.5 border-t border-cyan-200/60">
                        <span class="text-[10px] text-gray-500 font-bold block mb-1 flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-cyan-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/>
                                <circle cx="12" cy="13" r="3"/>
                            </svg>
                            <span>Foto Speedometer:</span>
                        </span>
                        <a :href="`/storage/${selectedBooking.log.end_photo}`" target="_blank">
                            <img :src="`/storage/${selectedBooking.log.end_photo}`" class="h-20 w-auto object-cover rounded-lg border border-cyan-300 hover:opacity-90 transition">
                        </a>
                    </div>
                </template>
            </div>

            {{-- 1.5 PRATINJAU CATATAN & FOTO KENDALA/KERUSAKAN DRIVER JIKA ADA --}}
            <template x-if="selectedBooking?.log?.condition_notes?.end || (selectedBooking?.log?.condition_photos?.end && selectedBooking.log.condition_photos.end.length > 0)">
                <div class="p-3 bg-rose-50/90 rounded-xl border border-rose-200 text-rose-900 text-xs space-y-2">
                    <div class="font-bold flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-rose-600 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        <span>Catatan & Foto Kendala Kepulangan (Dari Driver/Pemohon):</span>
                    </div>

                    {{-- Teks Catatan Insiden --}}
                    <template x-if="selectedBooking?.log?.condition_notes?.end">
                        <p class="pl-5 italic leading-relaxed font-medium text-rose-950" x-text="selectedBooking?.log?.condition_notes?.end"></p>
                    </template>

                    {{-- Preview Gallery Foto Kendala Kepulangan --}}
                    <template x-if="selectedBooking?.log?.condition_photos?.end && selectedBooking.log.condition_photos.end.length > 0">
                        <div class="pt-1">
                            <span class="block text-[10px] font-bold text-rose-800 mb-1">Foto Kerusakan / Insiden:</span>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="(photoPath, idx) in selectedBooking.log.condition_photos.end" :key="idx">
                                    <a :href="`/storage/${photoPath}`" target="_blank" class="group relative rounded-lg overflow-hidden border border-rose-300 bg-white shadow-xs">
                                        <img :src="`/storage/${photoPath}`" class="h-20 w-20 object-cover group-hover:scale-105 transition duration-150" alt="Foto Insiden Kepulangan">
                                        <span class="absolute bottom-0.5 right-0.5 bg-rose-900/75 text-white text-[8px] px-1 rounded backdrop-blur-xs">↗</span>
                                    </a>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            {{-- 2. EDITABLE CHECKLIST DINAMIS OLEH ADMIN LAPANGAN --}}
            <div class="space-y-3 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-200 pb-1.5">
                    <span class="text-[10px] font-bold text-slate-700 uppercase tracking-wider block flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-slate-700 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                            <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                            <path d="m9 14 2 2 4-4"/>
                        </svg>
                        <span>2. Verifikasi Checklist Fisik Garasi (Sesuaikan jika ada perbedaan)</span>
                    </span>
                    <span class="text-[10px] bg-cyan-100 text-cyan-800 font-bold px-2 py-0.5 rounded">Admin Mode</span>
                </div>

                {{-- DINAMIS KONDISI FISIK --}}
                <div class="space-y-1">
                    <span class="font-bold text-gray-700 text-[11px] block flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-cyan-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                        <span>Kondisi Fisik Kendaraan:</span>
                    </span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-gray-700">
                        <template x-for="item in verifyReturnForm.items.filter(i => i.category === 'condition')" :key="item.id">
                            <label class="flex items-center gap-2 p-2 rounded-lg border bg-white hover:bg-cyan-50/40 cursor-pointer transition">
                                <input type="checkbox" x-model="item.is_checked" class="rounded text-cyan-600 focus:ring-cyan-500">
                                <span x-text="item.name"></span>
                            </label>
                        </template>
                    </div>
                </div>

                {{-- DINAMIS KELENGKAPAN DINAS --}}
                <div class="space-y-1 pt-2 border-t border-slate-200">
                    <span class="font-bold text-gray-700 text-[11px] block flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-cyan-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                        </svg>
                        <span>Kelengkapan Kendaraan Dinas:</span>
                    </span>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-gray-700">
                        <template x-for="item in verifyReturnForm.items.filter(i => i.category === 'completeness')" :key="item.id">
                            <label class="flex items-center gap-1.5 p-1.5 rounded-lg border bg-white hover:bg-cyan-50/40 cursor-pointer transition">
                                <input type="checkbox" x-model="item.is_checked" class="rounded text-cyan-600 focus:ring-cyan-500">
                                <span x-text="item.name"></span>
                            </label>
                        </template>
                    </div>
                </div>
            </div>

            {{-- SEKSI VERIFIKASI KEBERSIHAN & CUCI MOBIL --}}
            <div class="space-y-2 pt-2 border-t border-slate-200">
                <span class="font-bold text-gray-700 text-[11px] block flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-amber-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                    </svg>
                    <span>Verifikasi Kebersihan Armada:</span>
                </span>
                
                <div class="p-3 bg-amber-50/70 border border-amber-200 rounded-xl space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_washed" value="1" x-model="verifyReturnForm.is_washed" class="rounded text-amber-600 focus:ring-amber-500">
                        <span class="font-bold text-amber-950 text-xs">Kendaraan Dikembalikan Dalam Kondisi Bersih / Sudah Dicuci</span>
                    </label>

                    {{-- PRATINJAU BUKTI FOTO / NOTA CUCI MOBIL --}}
                    <template x-if="verifyReturnForm.wash_receipt_photo">
                        <div class="pt-1 border-t border-amber-200/80">
                            <span class="text-[10px] text-amber-800 font-bold block mb-1 flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-amber-700 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1z"/>
                                    <line x1="8" y1="6" x2="16" y2="6"/>
                                    <line x1="8" y1="10" x2="16" y2="10"/>
                                    <line x1="8" y1="14" x2="12" y2="14"/>
                                </svg>
                                <span>Bukti Foto / Nota Cuci Mobil Pengemudi:</span>
                            </span>
                            <a :href="`/storage/${verifyReturnForm.wash_receipt_photo}`" target="_blank" class="inline-block">
                                <img :src="`/storage/${verifyReturnForm.wash_receipt_photo}`" class="h-20 w-auto object-cover rounded-lg border border-amber-300 hover:opacity-90 transition">
                            </a>
                        </div>
                    </template>
                </div>
            </div>

            {{-- 3. CATATAN TEMUAN PERBEDAAN ADMIN --}}
            <div class="space-y-1.5">
                <label class="block font-bold text-gray-800 flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-cyan-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                    <span>Catatan Temuan Admin Lapangan</span> 
                    <span class="text-gray-400 font-normal">(Opsional jika ada centang yang disesuaikan)</span>
                </label>
                <textarea name="admin_verification_notes" x-model="verifyReturnForm.admin_verification_notes" rows="2" 
                          placeholder="Misal: STNK ternyata tidak ada di laci mobil, bodi samping kanan ada goresan baru yang belum dicentang driver..."
                          class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium focus:bg-white focus:ring-cyan-500 focus:border-cyan-500"></textarea>
            </div>

            <!-- SEKSI TANDA TANGAN ADMIN LAPANGAN -->
            <div class="space-y-1.5 pt-2 border-t border-cyan-100 shrink-0">
                <div class="flex items-center justify-between gap-2 mb-2">
                    <label class="text-xs font-bold text-cyan-950 flex items-center gap-1 leading-snug">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-cyan-600 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 20h9"/>
                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                        </svg>
                        <span x-text="verifyReturnForm.existingSignatureUrl ? 'Tanda Tangan Verifikasi (Sudah Tersedia)' : 'Tanda Tangan Verifikasi Admin Lapangan'"></span> 
                        <span class="text-rose-500">*</span>
                    </label>

                    <button type="button" 
                            @click="clearVerifyReturnSignature()" 
                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-600 hover:text-rose-700 border border-rose-200 rounded-lg text-[11px] font-bold transition shrink-0 shadow-2xs cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 6h18"/>
                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                        </svg>
                        <span>Bersihkan</span>
                    </button>
                </div>

                {{-- Preview TTD Lama jika Admin sudah pernah TTD --}}
                <template x-if="verifyReturnForm.existingSignatureUrl">
                    <div class="mb-2 p-2 bg-cyan-50/50 border border-cyan-100 rounded-xl flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <img :src="verifyReturnForm.existingSignatureUrl" class="h-10 border bg-white rounded p-1 object-contain">
                            <span class="text-[11px] font-semibold text-cyan-900">TTD Admin tersimpan. Coret canvas di bawah jika ingin memperbarui TTD.</span>
                        </div>
                    </div>
                </template>

                <div class="border-2 border-dashed border-gray-300 rounded-xl bg-white p-1 relative">
                    <canvas x-ref="verifyReturnSignatureCanvas" 
                            style="mix-blend-mode: normal;"
                            class="w-full h-36 bg-white rounded-lg cursor-pointer touch-none block"></canvas>
                </div>

                <template x-if="verifyReturnForm.signatureError">
                    <p class="text-[11px] font-bold text-rose-600 mt-1" x-text="verifyReturnForm.signatureError"></p>
                </template>
            </div>

            <!-- TOMBOL AKSI MODAL -->
            <div class="flex items-center gap-3 pt-3 border-t border-gray-100 shrink-0">
                <button type="button" @click="showVerifyReturnModal = false" class="w-1/2 py-2.5 bg-gray-100 text-gray-700 text-xs font-bold rounded-xl hover:bg-gray-200 transition">
                    Batal
                </button>
                
                <button type="submit" class="w-1/2 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center justify-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                        <polyline points="17 21 17 13 7 13 7 21"/>
                        <polyline points="7 3 7 8 15 8"/>
                    </svg>
                    <span>Simpan Hasil Verifikasi</span>
                </button>
            </div>
        </form>

    </div>
</div>