<div x-show="showReviewModal" 
    class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 top-0"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    x-cloak>

    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-gray-100 max-h-[90vh] flex flex-col" @click.away="showReviewModal = false">
        
        <!-- Header Modal -->
        <div class="bg-indigo-600 px-6 py-4 text-white flex justify-between items-center shrink-0">
            <div>
                <h3 class="font-bold text-base flex items-center gap-2">
                    <!-- Icon SVG Dynamic berdasarkan Status -->
                    <template x-if="['waiting', 'approved'].includes(selectedBooking?.status)">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                            <path d="M3 3v5h5"/>
                            <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/>
                            <path d="M16 16h5v5"/>
                        </svg>
                    </template>
                    <template x-if="!['waiting', 'approved'].includes(selectedBooking?.status)">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                            <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                            <path d="m9 14 2 2 4-4"/>
                        </svg>
                    </template>
                    <span x-text="['waiting', 'approved'].includes(selectedBooking?.status) ? 'Re-Assign Armada & Driver' : 'Alokasi Armada & Driver'"></span>
                </h3>
                <p class="text-xs text-indigo-100 font-mono mt-0.5" x-text="selectedBooking ? selectedBooking.letter_number : ''"></p>
            </div>
            <button type="button" @click="showReviewModal = false" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <form x-ref="reviewFormElement"
              :action="selectedBooking ? `/bookings/${selectedBooking.letter_slug}/process-review` : '#'" 
              method="POST" 
              @submit.prevent="submitReviewForm()"
              class="p-6 space-y-4 overflow-y-auto flex-1">
            @csrf
            @method('PATCH')

            <!-- Input Hidden Tanda Tangan Admin -->
            <input type="hidden" name="signature" x-model="reviewForm.signatureData">

            <!-- Informasi Ringkasan -->
            <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-200 text-xs space-y-2">
                <div class="flex justify-between items-center border-b border-gray-200/80 pb-2">
                    <div class="flex items-center gap-1.5 font-bold text-gray-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10 9 9 9 8 9"/>
                        </svg>
                        <span>Dokumen Permohonan</span>
                    </div>

                    <!-- Tombol Lihat Formulir Permohonan PDF -->
                    <template x-if="selectedBooking?.letter_slug">
                        <a :href="`/bookings/${selectedBooking.letter_slug}/pdf/formulir-permohonan`" 
                           target="_blank" 
                           class="inline-flex items-center gap-1 px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200/80 rounded-lg text-[11px] font-bold transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <span>Lihat Formulir (PDF)</span>
                        </a>
                    </template>
                </div>

                <div class="space-y-1.5 pt-0.5">
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-medium">Pemohon:</span>
                        <span class="font-bold text-gray-900" x-text="selectedBooking?.user?.name || '-'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-medium">Tipe Peminjaman:</span>
                        <span class="font-bold uppercase text-indigo-600" x-text="selectedBooking?.vehicle_type || '-'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-medium">Tujuan:</span>
                        <span class="font-bold text-gray-900" x-text="selectedBooking?.destination || '-'"></span>
                    </div>
                </div>

                <template x-if="selectedBooking?.vehicle_requirements">
                    <div class="pt-1.5 border-t border-gray-200 text-amber-800">
                        <span class="font-bold">Kebutuhan Khusus:</span>
                        <p class="italic text-[11px]" x-text="selectedBooking.vehicle_requirements"></p>
                    </div>
                </template>
            </div>

            <!-- Radio Keputusan -->
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1.5">Keputusan Alokasi <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="cursor-pointer p-2.5 rounded-xl border text-center text-xs font-bold transition flex items-center justify-center gap-1.5"
                        :class="reviewForm.action === 'approve' ? 'bg-emerald-50 border-emerald-500 text-emerald-700 shadow-xs' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
                        <input type="radio" name="action" value="approve" x-model="reviewForm.action" class="hidden">
                        <!-- Icon Check Circle -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                        <span>Setujui & Alokasi</span>
                    </label>

                    <label class="cursor-pointer p-2.5 rounded-xl border text-center text-xs font-bold transition flex items-center justify-center gap-1.5"
                        :class="reviewForm.action === 'reject' ? 'bg-rose-50 border-rose-500 text-rose-700 shadow-xs' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
                        <input type="radio" name="action" value="reject" x-model="reviewForm.action" class="hidden">
                        <!-- Icon X Circle -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="15" y1="9" x2="9" y2="15"/>
                            <line x1="9" y1="9" x2="15" y2="15"/>
                        </svg>
                        <span>Tolak Permohonan</span>
                    </label>
                </div>
            </div>

            <!-- Form Pilihan Kendaraan & Driver (Hanya tampil jika Disetujui) -->
            <div x-show="reviewForm.action === 'approve'" class="space-y-5 pt-1">
                
                {{-- 1. PILIHAN ARMADA KENDARAAN READY --}}
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-gray-700">
                            Pilih Unit Armada Kendaraan <span class="text-rose-500">*</span>
                        </label>
                    </div>

                    {{-- INPUT HIDDEN UNTUK FORM & VALIDASI REQUIRED --}}
                    <input type="hidden" 
                        name="vehicle_id" 
                        x-model="reviewForm.vehicle_id" 
                        :required="reviewForm.action === 'approve'">

                    {{-- GRID CARD KENDARAAN READY --}}
                    <div class="space-y-2">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <template x-for="v in getAvailableVehicles()" :key="v.id">
                                <div @click="reviewForm.vehicle_id = v.id"
                                    :class="{
                                        'border-indigo-600 bg-indigo-50/50 ring-2 ring-indigo-500/20 shadow-2xs': reviewForm.vehicle_id == v.id,
                                        'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50/50': reviewForm.vehicle_id != v.id
                                    }"
                                    class="relative p-3.5 rounded-xl border cursor-pointer transition flex items-start gap-3 group">
                                    
                                    {{-- BADGE CHECKMARK JIKA TERPILIH --}}
                                    <div class="absolute top-3 right-3">
                                        <div :class="reviewForm.vehicle_id == v.id ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-gray-300 bg-white'"
                                            class="w-5 h-5 rounded-full border flex items-center justify-center transition">
                                            <svg x-show="reviewForm.vehicle_id == v.id" class="w-3 h-3 stroke-current stroke-3 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="20 6 9 17 4 12"/>
                                            </svg>
                                        </div>
                                    </div>

                                    {{-- THUMBNAIL FOTO --}}
                                    <div class="w-16 h-16 rounded-lg bg-gray-100 border border-gray-200 overflow-hidden shrink-0 relative">
                                        <template x-if="v.photo">
                                            <img :src="'/storage/' + v.photo" :alt="v.brand" class="w-full h-full object-cover">
                                        </template>
                                        <template x-if="!v.photo">
                                            <div class="w-full h-full flex flex-col items-center justify-center text-gray-400 bg-gray-50">
                                                <svg class="w-6 h-6 stroke-1.5 fill-none stroke-current" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                                    <polyline points="21 15 16 10 5 21"/>
                                                </svg>
                                            </div>
                                        </template>
                                        <span class="absolute top-0 left-0 bg-gray-900/80 backdrop-blur-xs text-white text-[9px] font-mono font-black px-1.5 py-0.5 rounded-br-md" x-text="v.code || 'N/A'"></span>
                                    </div>

                                    {{-- RINCIAN INFORMASI --}}
                                    <div class="space-y-1 pr-6 min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            {{-- BADGE TIPE --}}
                                            <span class="text-[9px] font-extrabold uppercase px-1.5 py-0.5 rounded border"
                                                :class="v.type === 'monitoring' 
                                                    ? 'bg-amber-50 text-amber-800 border-amber-200' 
                                                    : 'bg-indigo-50 text-indigo-800 border-indigo-200'"
                                                x-text="v.type === 'monitoring' ? 'Monitoring' : 'Operational'">
                                            </span>

                                            <h4 class="font-bold text-sm text-gray-900 truncate" x-text="v.brand + ' ' + v.model"></h4>
                                        </div>

                                        <p class="font-mono text-xs font-bold text-indigo-600 uppercase" x-text="v.plate_number"></p>

                                        <div class="flex items-center gap-2 text-[11px] text-gray-500 pt-0.5 flex-wrap">
                                            {{-- KAPASITAS --}}
                                            <span class="flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5 stroke-gray-400 fill-none stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                                    <circle cx="9" cy="7" r="4"/>
                                                </svg>
                                                <span x-text="v.capacity + ' Kursi'"></span>
                                            </span>

                                            <span>•</span>

                                            {{-- DRIVETRAIN --}}
                                            <span class="uppercase font-semibold text-gray-700 bg-gray-100 px-1.5 py-0.2 rounded border border-gray-200 text-[10px]" x-text="v.drivetrain ? v.drivetrain.toUpperCase() : '2WD'"></span>

                                            <span>•</span>

                                            {{-- BAHAN BAKAR --}}
                                            <span class="capitalize" x-text="v.fuel_type"></span>
                                        </div>
                                    </div>

                                </div>
                            </template>
                        </div>

                        {{-- ALERT BENTROK/KOSONG --}}
                        <template x-if="getAvailableVehicles().length === 0">
                            <p class="text-[11px] text-rose-600 font-bold p-3 bg-rose-50 border border-rose-200 rounded-xl flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0 text-rose-600" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                                    <line x1="12" y1="9" x2="12" y2="13"/>
                                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                                </svg>
                                <span>Seluruh armada telah terpakai / bentrok pada rentang jadwal ini.</span>
                            </p>
                        </template>
                    </div>
                </div>

                {{-- 2. PILIHAN DRIVER / PENGEMUDI --}}
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-gray-700">
                        Penunjukan Driver / Pengemudi <span class="text-rose-500">*</span>
                    </label>

                    {{-- INPUT HIDDEN UNTUK FORM --}}
                    <input type="hidden" 
                        name="driver_id" 
                        x-model="reviewForm.driver_id" 
                        :required="reviewForm.action === 'approve'">

                    <div class="space-y-3">
                        {{-- OPTGROUP: DRIVER UTAMA / RESMI --}}
                        <template x-if="getAvailableDrivers()?.primaryDrivers?.length > 0">
                            <div class="space-y-2">
                                <span class="text-[11px] font-extrabold text-indigo-900 uppercase tracking-wider block">Driver Utama / Resmi:</span>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <template x-for="d in getAvailableDrivers().primaryDrivers" :key="d.id">
                                        <div @click="reviewForm.driver_id = d.id"
                                            :class="{
                                                'border-indigo-600 bg-indigo-50/50 ring-2 ring-indigo-500/20 shadow-2xs': reviewForm.driver_id == d.id,
                                                'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50/50': reviewForm.driver_id != d.id
                                            }"
                                            class="relative p-3 rounded-xl border cursor-pointer transition flex items-center gap-3 group">
                                            
                                            {{-- BADGE CHECKMARK --}}
                                            <div class="absolute top-3 right-3">
                                                <div :class="reviewForm.driver_id == d.id ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-gray-300 bg-white'"
                                                    class="w-5 h-5 rounded-full border flex items-center justify-center transition">
                                                    <svg x-show="reviewForm.driver_id == d.id" class="w-3 h-3 stroke-current stroke-3 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="20 6 9 17 4 12"/>
                                                    </svg>
                                                </div>
                                            </div>

                                            {{-- AVATAR INSIAL NAMA DRIVER --}}
                                            <div class="w-10 h-10 rounded-lg bg-indigo-100 border border-indigo-200 text-indigo-700 font-extrabold text-sm flex items-center justify-center shrink-0"
                                                x-text="d.name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase()">
                                            </div>

                                            {{-- RINCIAN INFORMASI DRIVER --}}
                                            <div class="space-y-0.5 pr-6 min-w-0">
                                                <h4 class="font-bold text-xs text-gray-900 truncate" x-text="d.name"></h4>
                                                <div class="flex items-center gap-1 text-[11px] font-semibold text-emerald-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                                    </svg>
                                                    <span x-text="d.phone || 'Tidak Ada Kontak'"></span>
                                                </div>
                                            </div>

                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        {{-- OPTGROUP: ANGGOTA TIM PERMOHONAN INI --}}
                        <template x-if="getAvailableDrivers()?.participants && getAvailableDrivers().participants.length > 0">
                            <div class="space-y-2 pt-1">
                                <span class="text-[11px] font-extrabold text-amber-900 uppercase tracking-wider block">Anggota Tim Permohonan Ini (Pengemudi Cadangan):</span>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <template x-for="p in getAvailableDrivers().participants" :key="p.id">
                                        <div @click="reviewForm.driver_id = p.id"
                                            :class="{
                                                'border-amber-500 bg-amber-50/60 ring-2 ring-amber-500/20 shadow-2xs': reviewForm.driver_id == p.id,
                                                'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50/50': reviewForm.driver_id != p.id
                                            }"
                                            class="relative p-3 rounded-xl border cursor-pointer transition flex items-center gap-3 group">
                                            
                                            {{-- BADGE CHECKMARK --}}
                                            <div class="absolute top-3 right-3">
                                                <div :class="reviewForm.driver_id == p.id ? 'bg-amber-600 border-amber-600 text-white' : 'border-gray-300 bg-white'"
                                                    class="w-5 h-5 rounded-full border flex items-center justify-center transition">
                                                    <svg x-show="reviewForm.driver_id == p.id" class="w-3 h-3 stroke-current stroke-3 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="20 6 9 17 4 12"/>
                                                    </svg>
                                                </div>
                                            </div>

                                            {{-- AVATAR INSIAL --}}
                                            <div class="w-10 h-10 rounded-lg bg-amber-100 border border-amber-200 text-amber-800 font-extrabold text-sm flex items-center justify-center shrink-0"
                                                x-text="p.name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase()">
                                            </div>

                                            {{-- RINCIAN INFORMASI ANGGOTA TIM --}}
                                            <div class="space-y-0.5 pr-6 min-w-0">
                                                <h4 class="font-bold text-xs text-gray-900 truncate" x-text="p.name"></h4>
                                                <span class="inline-block text-[10px] font-bold text-amber-800 bg-amber-100/80 px-1.5 py-0.2 rounded border border-amber-200/80">Anggota Tim</span>
                                            </div>

                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        {{-- ALERT DRIVER KOSONG / TERSISA ANGGOTA TIM --}}
                        <template x-if="(getAvailableDrivers()?.primaryDrivers?.length || 0) === 0">
                            <p class="text-[11px] text-amber-800 font-medium p-3 bg-amber-50 border border-amber-200 rounded-xl flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0 text-amber-600" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="12" y1="16" x2="12" y2="12"/>
                                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                                </svg>
                                <span>Seluruh driver resmi sedang bertugas. Anda dapat menunjuk Anggota Tim di atas sebagai pengemudi.</span>
                            </p>
                        </template>
                    </div>
                </div>

                {{-- 3. ARENA TANDA TANGAN ADMIN LAPANGAN --}}
                <div x-show="reviewForm.action === 'approve'" class="space-y-1.5 pt-2">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <label class="text-xs font-bold text-gray-700 flex items-center gap-1 leading-snug">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-indigo-600 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 20h9"/>
                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                            </svg>
                            <span x-text="reviewForm.existingSignatureUrl ? 'Tanda Tangan Pengesahan (Sudah Tersedia)' : 'Tanda Tangan Pengesahan Admin Lapangan'"></span> 
                            <span class="text-rose-500">*</span>
                        </label>

                        <button type="button" 
                                @click="clearReviewSignature()" 
                                class="inline-flex items-center gap-1 px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-600 hover:text-rose-700 border border-rose-200 rounded-lg text-[11px] font-bold transition shrink-0 shadow-2xs cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 6h18"/>
                                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                            </svg>
                            <span>Bersihkan Canvas</span>
                        </button>
                    </div>

                    {{-- Tampilan jika TTD lama sudah ada (Saat Re-Assign) --}}
                    <template x-if="reviewForm.existingSignatureUrl">
                        <div class="mb-2 p-2 bg-indigo-50/50 border border-indigo-100 rounded-xl flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <img :src="reviewForm.existingSignatureUrl" class="h-10 border bg-white rounded p-1 object-contain">
                                <span class="text-[11px] font-semibold text-indigo-900">TTD Admin tersimpan. Coret canvas di bawah jika ingin memperbarui TTD.</span>
                            </div>
                        </div>
                    </template>

                    {{-- Canvas Signature Pad --}}
                    <div class="border-2 border-dashed border-gray-300 rounded-xl bg-white p-1 relative">
                        <canvas x-ref="reviewSignatureCanvas" 
                                style="mix-blend-mode: normal;"
                                class="w-full h-36 bg-white rounded-lg cursor-pointer touch-none block"></canvas>
                    </div>

                    <template x-if="reviewForm.signatureError">
                        <p class="text-[11px] font-bold text-rose-600 mt-1" x-text="reviewForm.signatureError"></p>
                    </template>
                </div>
            </div>

            <!-- Catatan Admin -->
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">
                    <span x-text="reviewForm.action === 'reject' ? 'Alasan Penolakan' : 'Catatan Admin Lapangan'"></span>
                    <span x-show="reviewForm.action === 'reject'" class="text-red-500">*</span>
                </label>
                <textarea name="admin_note" x-model="reviewForm.admin_note" rows="2" 
                        :required="reviewForm.action === 'reject'"
                        :placeholder="reviewForm.action === 'reject' ? 'Tuliskan alasan penolakan permohonan...' : 'Tuliskan catatan tambahan jika ada...'"
                        class="block w-full rounded-xl border-gray-300 text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500"></textarea>
            </div>

            <!-- Tombol Aksi -->
            <div class="flex items-center gap-3 pt-3 border-t border-gray-100 shrink-0">
                <button type="button" @click="showReviewModal = false" class="w-1/2 py-2.5 bg-gray-100 text-gray-700 text-xs font-bold rounded-xl hover:bg-gray-200 transition">
                    Batal
                </button>
                
                <button type="submit" 
                        class="w-1/2 py-2.5 text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center justify-center gap-1.5"
                        :class="reviewForm.action === 'approve' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700'">
                    <!-- Icon Submit Dynamic -->
                    <template x-if="reviewForm.action === 'approve'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                            <polyline points="17 21 17 13 7 13 7 21"/>
                            <polyline points="7 3 7 8 15 8"/>
                        </svg>
                    </template>
                    <template x-if="reviewForm.action === 'reject'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="15" y1="9" x2="9" y2="15"/>
                            <line x1="9" y1="9" x2="15" y2="15"/>
                        </svg>
                    </template>
                    <span x-text="reviewForm.action === 'approve' ? 'Simpan & Alokasikan' : 'Konfirmasi Penolakan'"></span>
                </button>
            </div>
        </form>

    </div>
</div>