<!-- MODAL COMPLETE TRIP (POST-TRIP CHECKLIST & CUCI MOBIL) -->
<div x-show="showCompleteModal" 
    class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50" 
    x-transition
    x-cloak>
    
    <div class="bg-white rounded-2xl shadow-xl max-w-xl w-full max-h-[90vh] flex flex-col overflow-hidden border border-gray-100" @click.away="showCompleteModal = false">
        
        <!-- HEADER MODAL PURPLE -->
        <div class="bg-purple-600 px-6 py-4 text-white flex justify-between items-center shrink-0">
            <h3 class="font-bold text-base flex items-center gap-2">
                <!-- Icon Flag Finish Purple Light Tint -->
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-purple-200 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6h-5.6z"/>
                </svg>
                <span>Selesaikan Perjalanan & Pengembalian</span>
            </h3>
            <button type="button" @click="showCompleteModal = false" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <form :action="`/bookings/${completeTripForm.letter_slug}/complete-trip`" method="POST" enctype="multipart/form-data" class="p-6 overflow-y-auto space-y-4 text-xs">
            @csrf
            @method('PATCH')

            {{-- INDIKATOR BBM & FOTO PENGEMBALIAN --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3.5 bg-purple-50/50 rounded-xl border border-purple-100">
                <div class="space-y-1">
                    <label class="font-bold text-purple-950 flex items-center gap-1.5">
                        <!-- Icon Fuel Pump Purple -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-purple-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="3" y1="22" x2="15" y2="22"/>
                            <path d="M4 9h10"/>
                            <path d="M14 22V4a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v18"/>
                            <path d="M14 13h2a2 2 0 0 1 2 2v2a2 2 0 0 0 2 2 2 2 0 0 0 2-2V9.83a2 2 0 0 0-.59-1.42L18 5.5"/>
                        </svg>
                        <span>Kapasitas BBM Akhir</span> <span class="text-rose-500">*</span>
                    </label>
                    <select name="end_fuel_level" x-model="completeTripForm.end_fuel_level" required
                            class="w-full px-3 py-2 bg-white border border-purple-200 rounded-xl text-xs font-medium focus:ring-purple-500 focus:border-purple-500">
                        <option value="Full">Full (Penuh)</option>
                        <option value="3/4">3/4 Tank</option>
                        <option value="1/2">1/2 Tank</option>
                        <option value="1/4">1/4 Tank</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="font-bold text-purple-950 flex items-center gap-1.5">
                        <!-- Icon Camera Purple -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-purple-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/>
                            <circle cx="12" cy="13" r="3"/>
                        </svg>
                        <span>Foto BBM Pengembalian</span> <span class="text-rose-500">*</span>
                    </label>
                    <input type="file" name="end_photo" accept="image/*" required
                        class="w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-purple-100 file:text-purple-800 hover:file:bg-purple-200 transition cursor-pointer">
                </div>
            </div>

            {{-- KHUSUS MULTI-DAY: SYARAT CUCI MOBIL --}}
            <template x-if="completeTripForm.is_multi_day">
                <div class="p-3.5 bg-amber-50 border border-amber-200 rounded-xl space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_washed" value="1" required x-model="completeTripForm.is_washed"
                                   class="rounded border-amber-400 text-amber-600 focus:ring-amber-500">
                            <span class="text-xs font-bold text-amber-950 flex items-center gap-1.5">
                                <!-- Icon Sparkles Amber -->
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-amber-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                                </svg>
                                <span>Kendaraan Sudah Dicuci Bersih</span> <span class="text-rose-500">*</span>
                            </span>
                        </label>
                    </div>
                    <p class="text-[11px] text-amber-800 leading-relaxed">
                        Perjalanan ini berlangsung lebih dari 1 hari. Pengemudi/Pemohon wajib mengembalikan kendaraan dalam kondisi bersih (luar & dalam).
                    </p>
                    <div class="space-y-1">
                        <label class="block text-[11px] font-bold text-amber-900">Foto Nota / Bukti Cuci Mobil <span class="text-rose-500">*</span></label>
                        <input type="file" name="wash_receipt_photo" accept="image/*" required
                               class="w-full text-xs text-amber-700 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-amber-100 file:text-amber-800 hover:file:bg-amber-200 transition cursor-pointer">
                    </div>
                </div>
            </template>

            {{-- SEKSI 1: CHECKLIST FISIK PENGEMBALIAN --}}
            <div class="space-y-2">
                <h4 class="font-bold text-purple-900 text-xs border-b border-purple-100 pb-1 flex items-center gap-1.5">
                    <!-- Icon Search Purple -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-purple-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <span>Checklist Fisik Kepulangan (Centang jika kondisi baik)</span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-gray-700">
                    <label class="flex items-center gap-2 p-2 rounded-lg border border-gray-100 hover:bg-purple-50/50 cursor-pointer transition">
                        <input type="checkbox" name="body_condition" value="1" x-model="completeTripForm.body_condition" @change="checkCompleteIncidentStatus()" class="rounded text-purple-600 focus:ring-purple-500">
                        <span>Bodi & Cat Mulus</span>
                    </label>
                    <label class="flex items-center gap-2 p-2 rounded-lg border border-gray-100 hover:bg-purple-50/50 cursor-pointer transition">
                        <input type="checkbox" name="tires_condition" value="1" x-model="completeTripForm.tires_condition" @change="checkCompleteIncidentStatus()" class="rounded text-purple-600 focus:ring-purple-500">
                        <span>Kondisi Ban Baik</span>
                    </label>
                    <label class="flex items-center gap-2 p-2 rounded-lg border border-gray-100 hover:bg-purple-50/50 cursor-pointer transition">
                        <input type="checkbox" name="lights_condition" value="1" x-model="completeTripForm.lights_condition" @change="checkCompleteIncidentStatus()" class="rounded text-purple-600 focus:ring-purple-500">
                        <span>Lampu-Lampu Nyala</span>
                    </label>
                    <label class="flex items-center gap-2 p-2 rounded-lg border border-gray-100 hover:bg-purple-50/50 cursor-pointer transition">
                        <input type="checkbox" name="glass_mirrors" value="1" x-model="completeTripForm.glass_mirrors" @change="checkCompleteIncidentStatus()" class="rounded text-purple-600 focus:ring-purple-500">
                        <span>Kaca & Spion Utuh</span>
                    </label>
                    <label class="flex items-center gap-2 p-2 rounded-lg border border-gray-100 hover:bg-purple-50/50 cursor-pointer transition">
                        <input type="checkbox" name="brakes_condition" value="1" x-model="completeTripForm.brakes_condition" @change="checkCompleteIncidentStatus()" class="rounded text-purple-600 focus:ring-purple-500">
                        <span>Fungsi Rem Normal</span>
                    </label>
                    <label class="flex items-center gap-2 p-2 rounded-lg border border-gray-100 hover:bg-purple-50/50 cursor-pointer transition">
                        <input type="checkbox" name="interior_cleanliness" value="1" x-model="completeTripForm.interior_cleanliness" @change="checkCompleteIncidentStatus()" class="rounded text-purple-600 focus:ring-purple-500">
                        <span>Kabin Bersih</span>
                    </label>
                </div>
            </div>

            {{-- SEKSI 2: PERLENGKAPAN DINAS PENGEMBALIAN --}}
            <div class="space-y-2">
                <h4 class="font-bold text-purple-900 text-xs border-b border-purple-100 pb-1 flex items-center gap-1.5">
                    <!-- Icon Tools Purple -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-purple-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                    </svg>
                    <span>Kelengkapan Kendaraan Dinas (Centang jika ada)</span>
                </h4>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-gray-700">
                    <label class="flex items-center gap-1.5 p-1.5 rounded-lg border border-gray-100 hover:bg-purple-50/50 cursor-pointer transition">
                        <input type="checkbox" name="has_stnk" value="1" x-model="completeTripForm.has_stnk" @change="checkCompleteIncidentStatus()" class="rounded text-purple-600 focus:ring-purple-500">
                        <span>STNK</span>
                    </label>
                    <label class="flex items-center gap-1.5 p-1.5 rounded-lg border border-gray-100 hover:bg-purple-50/50 cursor-pointer transition">
                        <input type="checkbox" name="has_spare_tire" value="1" x-model="completeTripForm.has_spare_tire" @change="checkCompleteIncidentStatus()" class="rounded text-purple-600 focus:ring-purple-500">
                        <span>Ban Serep</span>
                    </label>
                    <label class="flex items-center gap-1.5 p-1.5 rounded-lg border border-gray-100 hover:bg-purple-50/50 cursor-pointer transition">
                        <input type="checkbox" name="has_jack_tools" value="1" x-model="completeTripForm.has_jack_tools" @change="checkCompleteIncidentStatus()" class="rounded text-purple-600 focus:ring-purple-500">
                        <span>Dongkrak</span>
                    </label>
                    <label class="flex items-center gap-1.5 p-1.5 rounded-lg border border-gray-100 hover:bg-purple-50/50 cursor-pointer transition">
                        <input type="checkbox" name="has_first_aid" value="1" x-model="completeTripForm.has_first_aid" @change="checkCompleteIncidentStatus()" class="rounded text-purple-600 focus:ring-purple-500">
                        <span>Kotak P3K</span>
                    </label>
                </div>
            </div>

            {{-- AREA KENDALA / KERUSAKAN PENGEMBALIAN --}}
            <div class="pt-2 border-t border-gray-100 space-y-3">
                <div x-show="completeTripForm.hasIncident" x-transition class="space-y-3 bg-rose-50/70 p-3.5 rounded-xl border border-rose-200">
                    <div class="flex items-center gap-1.5 text-rose-800 font-bold text-xs">
                        <!-- Icon Alert Rose -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-rose-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                            <line x1="12" y1="9" x2="12" y2="13"/>
                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                        <span>Terdapat indikasi kendala / kerusakan fisik / ketidaklengkapan kendaraan.</span>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-rose-900">
                            Catatan Kerusakan / Insiden <span class="text-rose-600">*</span>
                        </label>
                        <textarea name="condition_notes" x-model="completeTripForm.condition_notes" rows="2"
                                :required="completeTripForm.hasIncident"
                                placeholder="Jelaskan detail kendala (misal: bodi kanan tergores saat parkir)..."
                                class="w-full px-3 py-2 bg-white border border-rose-300 rounded-xl text-xs font-medium focus:ring-rose-500 focus:border-rose-500"></textarea>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-rose-900">
                            Foto Bukti Kerusakan / Insiden <span class="text-rose-600">*</span>
                        </label>
                        <input type="file" name="condition_photos[]" accept="image/*" multiple
                            :required="completeTripForm.hasIncident"
                            class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-rose-100 file:text-rose-700 hover:file:bg-rose-200 transition cursor-pointer">
                    </div>
                </div>
            </div>

            <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2 shrink-0">
                <button type="button" @click="showCompleteModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl shadow-xs transition flex items-center gap-1.5">
                    <!-- Check Circle White Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    <span>Selesaikan & Simpan Log</span>
                </button>
            </div>
        </form>

    </div>
</div>