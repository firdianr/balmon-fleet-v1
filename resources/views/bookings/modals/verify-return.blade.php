<!-- MODAL VERIFIKASI PEMERIKSAAN FISIK OLEH ADMIN LAPANGAN -->
<div x-show="showVerifyReturnModal" 
    class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50"
    x-transition
    x-cloak>

    <div class="bg-white rounded-2xl shadow-xl max-w-xl w-full max-h-[90vh] flex flex-col overflow-hidden border border-gray-100" @click.away="showVerifyReturnModal = false">
        
        <!-- HEADER MODAL CYAN -->
        <div class="bg-cyan-600 px-6 py-4 text-white flex justify-between items-center shrink-0">
            <div>
                <h3 class="font-bold text-base flex items-center gap-2">
                    <!-- Icon Search Check Light Tint -->
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

        <form :action="selectedBooking ? `/bookings/${selectedBooking.letter_slug}/verify-return` : '#'" method="POST" class="p-6 overflow-y-auto space-y-4 text-xs">
            @csrf
            @method('PATCH')

            {{-- 1. PRATINJAU BBM & SPEEDOMETER DRIVER --}}
            <div class="bg-cyan-50/60 p-3.5 rounded-xl border border-cyan-200 space-y-2">
                <span class="text-[10px] font-bold text-cyan-800 uppercase tracking-wider block flex items-center gap-1.5">
                    <!-- Icon Fuel Gauge Cyan -->
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
                            <!-- Icon Camera Cyan -->
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

            {{-- 2. EDITABLE CHECKLIST OLEH ADMIN LAPANGAN --}}
            <div class="space-y-3 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-200 pb-1.5">
                    <span class="text-[10px] font-bold text-slate-700 uppercase tracking-wider block flex items-center gap-1.5">
                        <!-- Icon Checklist Slate -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-slate-700 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                            <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                            <path d="m9 14 2 2 4-4"/>
                        </svg>
                        <span>2. Verifikasi Checklist Fisik Garasi (Sesuaikan jika ada perbedaan)</span>
                    </span>
                    <span class="text-[10px] bg-cyan-100 text-cyan-800 font-bold px-2 py-0.5 rounded">Admin Mode</span>
                </div>

                {{-- KONDISI FISIK --}}
                <div class="space-y-1">
                    <span class="font-bold text-gray-700 text-[11px] block flex items-center gap-1">
                        <!-- Icon Car Search Cyan -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-cyan-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                        <span>Kondisi Fisik Kendaraan:</span>
                    </span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-gray-700">
                        <label class="flex items-center gap-2 p-2 rounded-lg border bg-white hover:bg-cyan-50/40 cursor-pointer transition">
                            <input type="checkbox" name="body_condition" value="1" x-model="verifyReturnForm.body_condition" class="rounded text-cyan-600 focus:ring-cyan-500">
                            <span>Bodi & Cat Mulus</span>
                        </label>
                        <label class="flex items-center gap-2 p-2 rounded-lg border bg-white hover:bg-cyan-50/40 cursor-pointer transition">
                            <input type="checkbox" name="tires_condition" value="1" x-model="verifyReturnForm.tires_condition" class="rounded text-cyan-600 focus:ring-cyan-500">
                            <span>Kondisi Ban Baik</span>
                        </label>
                        <label class="flex items-center gap-2 p-2 rounded-lg border bg-white hover:bg-cyan-50/40 cursor-pointer transition">
                            <input type="checkbox" name="lights_condition" value="1" x-model="verifyReturnForm.lights_condition" class="rounded text-cyan-600 focus:ring-cyan-500">
                            <span>Lampu-Lampu Nyala</span>
                        </label>
                        <label class="flex items-center gap-2 p-2 rounded-lg border bg-white hover:bg-cyan-50/40 cursor-pointer transition">
                            <input type="checkbox" name="glass_mirrors" value="1" x-model="verifyReturnForm.glass_mirrors" class="rounded text-cyan-600 focus:ring-cyan-500">
                            <span>Kaca & Spion Utuh</span>
                        </label>
                        <label class="flex items-center gap-2 p-2 rounded-lg border bg-white hover:bg-cyan-50/40 cursor-pointer transition">
                            <input type="checkbox" name="brakes_condition" value="1" x-model="verifyReturnForm.brakes_condition" class="rounded text-cyan-600 focus:ring-cyan-500">
                            <span>Fungsi Rem Normal</span>
                        </label>
                        <label class="flex items-center gap-2 p-2 rounded-lg border bg-white hover:bg-cyan-50/40 cursor-pointer transition">
                            <input type="checkbox" name="interior_cleanliness" value="1" x-model="verifyReturnForm.interior_cleanliness" class="rounded text-cyan-600 focus:ring-cyan-500">
                            <span>Kabin Bersih</span>
                        </label>
                    </div>
                </div>

                {{-- KELENGKAPAN DINAS --}}
                <div class="space-y-1 pt-2 border-t border-slate-200">
                    <span class="font-bold text-gray-700 text-[11px] block flex items-center gap-1">
                        <!-- Icon Wrench Cyan -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-cyan-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                        </svg>
                        <span>Kelengkapan Kendaraan Dinas:</span>
                    </span>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-gray-700">
                        <label class="flex items-center gap-1.5 p-1.5 rounded-lg border bg-white hover:bg-cyan-50/40 cursor-pointer transition">
                            <input type="checkbox" name="has_stnk" value="1" x-model="verifyReturnForm.has_stnk" class="rounded text-cyan-600 focus:ring-cyan-500">
                            <span>STNK</span>
                        </label>
                        <label class="flex items-center gap-1.5 p-1.5 rounded-lg border bg-white hover:bg-cyan-50/40 cursor-pointer transition">
                            <input type="checkbox" name="has_spare_tire" value="1" x-model="verifyReturnForm.has_spare_tire" class="rounded text-cyan-600 focus:ring-cyan-500">
                            <span>Ban Serep</span>
                        </label>
                        <label class="flex items-center gap-1.5 p-1.5 rounded-lg border bg-white hover:bg-cyan-50/40 cursor-pointer transition">
                            <input type="checkbox" name="has_jack_tools" value="1" x-model="verifyReturnForm.has_jack_tools" class="rounded text-cyan-600 focus:ring-cyan-500">
                            <span>Dongkrak</span>
                        </label>
                        <label class="flex items-center gap-1.5 p-1.5 rounded-lg border bg-white hover:bg-cyan-50/40 cursor-pointer transition">
                            <input type="checkbox" name="has_first_aid" value="1" x-model="verifyReturnForm.has_first_aid" class="rounded text-cyan-600 focus:ring-cyan-500">
                            <span>Kotak P3K</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- SEKSI VERIFIKASI KEBERSIHAN & CUCI MOBIL --}}
            <div class="space-y-2 pt-2 border-t border-slate-200">
                <span class="font-bold text-gray-700 text-[11px] block flex items-center gap-1">
                    <!-- Icon Sparkles Amber -->
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
                                <!-- Icon Receipt Amber -->
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
                    <!-- Icon Edit Note Cyan -->
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

            <div class="flex items-center gap-3 pt-3 border-t border-gray-100 shrink-0">
                <button type="button" @click="showVerifyReturnModal = false" class="w-1/2 py-2.5 bg-gray-100 text-gray-700 text-xs font-bold rounded-xl hover:bg-gray-200 transition">
                    Batal
                </button>
                
                <button type="submit" class="w-1/2 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center justify-center gap-1.5">
                    <!-- Icon Save Check -->
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