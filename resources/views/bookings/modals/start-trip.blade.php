<!-- MODAL START TRIP (PRE-TRIP CHECKLIST DINAMIS) -->
<div x-show="showStartTripModal" 
    class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 top-0" 
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    x-cloak>
    
    <div class="bg-white rounded-2xl shadow-xl max-w-xl w-full max-h-[90vh] flex flex-col overflow-hidden border border-gray-100" @click.away="showStartTripModal = false">
        
        <!-- HEADER MODAL EMERALD -->
        <div class="bg-emerald-600 px-6 py-4 text-white flex justify-between items-center shrink-0">
            <h3 class="font-bold text-base flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-emerald-200 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.71 1.1-1.63 1.1-2.58 0-1.88-1.52-3.42-3.4-3.42-.95 0-1.87.39-2.58 1.1z"/>
                    <path d="M12 15l-3-3 8.5-8.5a2.12 2.12 0 0 1 3 3L12 15z"/>
                </svg>
                <span>Mulai Perjalanan & Checklist Keberangkatan</span>
            </h3>
            <button type="button" @click="showStartTripModal = false" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <form :action="`/bookings/${startTripForm.letter_slug}/start-trip`" 
              method="POST" 
              enctype="multipart/form-data" 
              class="p-6 overflow-y-auto space-y-4 text-xs flex-1">
            @csrf
            @method('PATCH')

            <!-- INPUT HIDDEN -->
            <input type="hidden" name="items" :value="JSON.stringify(startTripForm.items)">
            <input type="hidden" name="has_incident" :value="startTripForm.hasIncident ? 'true' : 'false'">

            {{-- INDIKATOR BBM & FOTO DASHBOARD --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3.5 bg-emerald-50/50 rounded-xl border border-emerald-100">
                <div class="space-y-1">
                    <label class="font-bold text-emerald-950 flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-emerald-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="3" y1="22" x2="15" y2="22"/>
                            <path d="M4 9h10"/>
                            <path d="M14 22V4a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v18"/>
                            <path d="M14 13h2a2 2 0 0 1 2 2v2a2 2 0 0 0 2 2 2 2 0 0 0 2-2V9.83a2 2 0 0 0-.59-1.42L18 5.5"/>
                        </svg>
                        <span>Kapasitas BBM Awal</span> <span class="text-rose-500">*</span>
                    </label>
                    <select name="start_fuel_level" x-model="startTripForm.start_fuel_level" required
                            class="w-full px-3 py-2 bg-white border border-emerald-200 rounded-xl text-xs font-medium focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="Full">Full (Penuh)</option>
                        <option value="3/4">3/4 Tank</option>
                        <option value="1/2">1/2 Tank</option>
                        <option value="1/4">1/4 Tank</option>
                    </select>
                </div>

                <div class="space-y-2" x-data="{ photoPreview: null }">
                    <label class="font-bold text-emerald-950 flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-emerald-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/>
                            <circle cx="12" cy="13" r="3"/>
                        </svg>
                        <span>Foto BBM Awal Keberangkatan</span> <span class="text-rose-500">*</span>
                    </label>
                    
                    <input type="file" name="start_photo" accept="image/*" required
                        @change="
                            const file = $event.target.files[0];
                            if (file) {
                                const reader = new FileReader();
                                reader.onload = (e) => { photoPreview = e.target.result; };
                                reader.readAsDataURL(file);
                            }
                        "
                        class="w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-100 file:text-emerald-800 hover:file:bg-emerald-200 transition cursor-pointer">
                    
                    <p class="text-[11px] text-gray-400">
                        * Format: JPG, PNG, WEBP (Maksimal 2 MB)
                    </p>

                    <div x-show="photoPreview" class="mt-2 relative w-32 h-32 rounded-lg overflow-hidden border border-emerald-200 shadow-sm">
                        <img :src="photoPreview" alt="Preview Foto BBM" class="w-full h-full object-cover">
                        <button type="button" @click="photoPreview = null; $el.closest('.space-y-2').querySelector('input[type=file]').value = ''" 
                            class="absolute top-1 right-1 bg-rose-500 text-white rounded-full p-1 hover:bg-rose-600 transition shadow">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- SEKSI 1: DINAMIS CHECKLIST KONDISI FISIK --}}
            <div class="space-y-2">
                <h4 class="font-bold text-emerald-900 text-xs border-b border-emerald-100 pb-1 flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-emerald-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <span>Checklist Fisik Keberangkatan (Centang jika kondisi baik)</span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-gray-700">
                    <template x-for="item in startTripForm.items.filter(i => i.category === 'condition')" :key="item.id">
                        <label class="flex items-center gap-2 p-2 rounded-lg border border-gray-100 hover:bg-emerald-50/50 cursor-pointer transition">
                            <input type="checkbox" x-model="item.is_checked" @change="checkDynamicIncidentStatus(startTripForm)" class="rounded text-emerald-600 focus:ring-emerald-500">
                            <span x-text="item.name"></span>
                        </label>
                    </template>
                </div>
            </div>

            {{-- SEKSI 2: DINAMIS PERLENGKAPAN DINAS --}}
            <div class="space-y-2">
                <h4 class="font-bold text-emerald-900 text-xs border-b border-emerald-100 pb-1 flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-emerald-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                    </svg>
                    <span>Kelengkapan Kendaraan Dinas (Centang jika ada)</span>
                </h4>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-gray-700">
                    <template x-for="item in startTripForm.items.filter(i => i.category === 'completeness')" :key="item.id">
                        <label class="flex items-center gap-1.5 p-1.5 rounded-lg border border-gray-100 hover:bg-emerald-50/50 cursor-pointer transition">
                            <input type="checkbox" 
                                x-model="item.is_checked" 
                                @change="checkDynamicIncidentStatus(startTripForm)"
                                class="rounded text-emerald-600 focus:ring-emerald-500">
                            <span x-text="item.name"></span>
                        </label>
                    </template>
                </div>
            </div>

            {{-- AREA KENDALA / CATATAN KERUSAKAN AWAL --}}
            <div x-show="startTripForm.hasIncident" x-transition class="space-y-3 bg-rose-50/70 p-3.5 rounded-xl border border-rose-200">
                <div class="flex items-center gap-1.5 text-rose-800 font-bold text-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-rose-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                    <span>Terdapat indikasi kendala / ketidaklengkapan awal fisik kendaraan.</span>
                </div>

                <div class="space-y-1">
                    <label class="block font-bold text-rose-900">
                        Catatan Kerusakan / Kendala Awal <span class="text-rose-600">*</span>
                    </label>
                    <textarea name="notes" x-model="startTripForm.notes" rows="2"
                            :required="startTripForm.hasIncident"
                            placeholder="Jelaskan detail bagian fisik yang bermasalah/rusak sebelum berangkat..."
                            class="w-full px-3 py-2 bg-white border border-rose-300 rounded-xl text-xs font-medium focus:ring-rose-500 focus:border-rose-500"></textarea>
                </div>

                <div class="space-y-2" x-data="{
                    photos: [],
                    handleFileChange(event) {
                        const files = Array.from(event.target.files);
                        this.photos = [];
                        files.forEach(file => {
                            const reader = new FileReader();
                            reader.onload = (e) => {
                                this.photos.push({ file: file, url: e.target.result });
                            };
                            reader.readAsDataURL(file);
                        });
                    },
                    removePhoto(index) {
                        this.photos.splice(index, 1);
                        if (this.photos.length === 0) {
                            const input = document.getElementById('start_condition_photos_input');
                            if (input) input.value = '';
                        }
                    }
                }">
                    <label class="block font-bold text-rose-900">
                        Foto Bukti Kendala / Kerusakan Awal <span class="text-rose-600">*</span>
                    </label>
                    
                    <input type="file" 
                        id="start_condition_photos_input"
                        name="condition_photos[]" 
                        accept="image/jpeg,image/png,image/jpg" 
                        multiple
                        :required="startTripForm.hasIncident && photos.length === 0"
                        @change="handleFileChange($event)"
                        class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-rose-100 file:text-rose-700 hover:file:bg-rose-200 transition cursor-pointer">

                    <p class="text-[11px] text-rose-700/70">
                        * Format: JPG, JPEG, PNG (Maksimal 2 MB per foto, max 5 foto)
                    </p>

                    <template x-if="photos.length > 0">
                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-2.5 pt-2">
                            <template x-for="(photo, index) in photos" :key="index">
                                <div class="relative group aspect-square rounded-xl overflow-hidden border border-rose-200 shadow-sm bg-gray-50">
                                    <img :src="photo.url" alt="Preview kendala" class="w-full h-full object-cover">
                                    
                                    <button type="button" @click="removePhoto(index)" 
                                        class="absolute top-1 right-1 bg-rose-600/90 text-white rounded-full p-1 hover:bg-rose-700 transition shadow-md">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            <!-- TOMBOL AKSI MODAL -->
            <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2 shrink-0">
                <button type="button" @click="showStartTripModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-xs transition flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"/>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                    </svg>
                    <span>Simpan Log Keberangkatan</span>
                </button>
            </div>
        </form>

    </div>
</div>