<div x-show="showReviewModal" 
    class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    x-cloak>

    <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full overflow-hidden border border-gray-100" @click.away="showReviewModal = false">
        
        <!-- Header Modal -->
        <div class="bg-indigo-600 px-6 py-4 text-white flex justify-between items-center">
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

        <form :action="selectedBooking ? `/bookings/${selectedBooking.letter_slug}/process-review` : '#'" method="POST" class="p-6 space-y-4">
            @csrf
            @method('PATCH')

            <!-- Informasi Ringkasan -->
            <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-200 text-xs space-y-1.5">
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
                <template x-if="selectedBooking?.vehicle_requirements">
                    <div class="pt-1 border-t border-gray-200 text-amber-800">
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

            <!-- Form Pilihan Kendaraan & Driver -->
            <div x-show="reviewForm.action === 'approve'" class="space-y-3.5 pt-1">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">
                        Pilih Unit Armada Kendaraan <span class="text-red-500">*</span>
                    </label>
                    <select name="vehicle_id" x-model="reviewForm.vehicle_id" :required="reviewForm.action === 'approve'"
                            class="block w-full rounded-xl border-gray-300 text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">-- Pilih Kendaraan Ready --</option>
                        <template x-for="v in getAvailableVehicles()" :key="v.id">
                            <option :value="v.id" x-text="`[${v.code}] ${v.brand} ${v.model} (${v.plate_number}) - [${(v.type || '').toUpperCase()}]`"></option>
                        </template>
                    </select>
                    
                    <template x-if="getAvailableVehicles().length === 0">
                        <p class="text-[11px] text-rose-600 font-bold mt-1.5 flex items-center gap-1.5">
                            <!-- Icon Warning -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0 text-rose-600" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/>
                                <line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                            <span>Seluruh armada telah terpakai / bentrok pada rentang jadwal ini.</span>
                        </p>
                    </template>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">
                        Penunjukan Driver / Pengemudi <span class="text-rose-500">*</span>
                    </label>
                    <select name="driver_id" x-model="reviewForm.driver_id" :required="reviewForm.action === 'approve'"
                            class="block w-full rounded-xl border-gray-300 text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">-- Pilih Pengemudi --</option>
                        
                        <optgroup label="Driver Utama / Resmi">
                            <template x-for="d in getAvailableDrivers().primaryDrivers" :key="d.id">
                                <option :value="d.id" x-text="`${d.name} ${d.phone ? '(' + d.phone + ')' : ''}`"></option>
                            </template>
                        </optgroup>

                        <template x-if="getAvailableDrivers().participants && getAvailableDrivers().participants.length > 0">
                            <optgroup label="Anggota Tim Permohonan Ini">
                                <template x-for="p in getAvailableDrivers().participants" :key="p.id">
                                    <option :value="p.id" x-text="`${p.name} (Anggota Tim)`"></option>
                                </template>
                            </optgroup>
                        </template>
                    </select>

                    <template x-if="getAvailableDrivers().primaryDrivers.length === 0">
                        <p class="text-[11px] text-amber-700 font-medium mt-1.5 flex items-center gap-1.5">
                            <!-- Icon Info -->
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
            <div class="flex items-center gap-3 pt-3 border-t border-gray-100">
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