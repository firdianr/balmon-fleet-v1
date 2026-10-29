<div x-show="showRouteModal" 
    class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50" 
    x-transition
    x-cloak>
    
    <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full overflow-hidden border border-gray-100" @click.away="showRouteModal = false">
        
        <!-- HEADER MODAL INDIGO -->
        <div class="bg-indigo-600 px-6 py-4 text-white flex justify-between items-center shrink-0">
            <div>
                <h3 class="font-bold text-base flex items-center gap-2">
                    <!-- Icon Map Pin Indigo Light Tint -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-indigo-200 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="1 6 1 22 8 18 15 22 23 18 23 2 15 6 8 2 1 6"/>
                        <line x1="8" y1="2" x2="8" y2="18"/>
                        <line x1="15" y1="6" x2="15" y2="22"/>
                    </svg>
                    <span>Detail Rute & Tim Penumpang</span>
                </h3>
                <p class="text-xs text-indigo-100 mt-0.5" x-text="routeBookingDetail ? 'No. Surat: ' + routeBookingDetail.letter_number : ''"></p>
            </div>
            <button type="button" @click="showRouteModal = false" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <div class="p-6 space-y-5 text-xs text-gray-700" x-if="routeBookingDetail">
            
            <div class="p-4 bg-indigo-50/70 rounded-xl border border-indigo-100 space-y-2">
                <span class="text-[10px] font-bold text-indigo-700 uppercase tracking-wider block flex items-center gap-1">
                    <!-- Icon Map Pin Indigo -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    <span>Lokasi Tujuan Dinas</span>
                </span>
                <p class="text-sm font-bold text-gray-900" x-text="routeBookingDetail.destination || '-'"></p>
                
                <a :href="getGoogleMapsUrl(routeBookingDetail.destination)" target="_blank" 
                class="inline-flex items-center gap-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg text-xs transition shadow-xs mt-1">
                    <!-- Icon External Navigation White -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="3 11 22 2 13 21 11 13 3 11"/>
                    </svg>
                    <span>Buka Navigasi Google Maps</span>
                </a>
            </div>

            <div class="grid grid-cols-2 gap-3 bg-gray-50 p-3.5 rounded-xl border border-gray-200/80">
                <div>
                    <span class="text-[10px] text-gray-400 font-bold block flex items-center gap-1">
                        <!-- Icon Calendar Start Indigo -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        <span>Keberangkatan:</span>
                    </span>
                    <p class="font-bold text-gray-800 mt-0.5" x-text="formatDate(routeBookingDetail.start_date)"></p>
                </div>
                <div>
                    <span class="text-[10px] text-gray-400 font-bold block flex items-center gap-1">
                        <!-- Icon Calendar End Indigo -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                            <path d="m9 16 2 2 4-4"/>
                        </svg>
                        <span>Estimasi Kembali:</span>
                    </span>
                    <p class="font-bold text-gray-800 mt-0.5" x-text="formatDate(routeBookingDetail.end_date)"></p>
                </div>
            </div>

            <div class="space-y-2">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block flex items-center gap-1">
                    <!-- Icon Users Indigo -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    <span>Kontak Tim / Penumpang</span>
                </span>
                
                <div class="p-3 bg-white border border-gray-200 rounded-xl flex items-center justify-between">
                    <div>
                        <p class="font-bold text-gray-900" x-text="routeBookingDetail.user?.name || '-'"></p>
                        <p class="text-[11px] text-gray-500">Pemohon Utama (Penanggung Jawab)</p>
                    </div>
                    <template x-if="routeBookingDetail.user?.phone">
                        <a :href="getWaUrl(routeBookingDetail.user.phone)" target="_blank" 
                        class="px-2.5 py-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-bold rounded-lg transition flex items-center gap-1 text-[11px]">
                            <!-- Icon Message/Chat Emerald -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-emerald-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                            </svg>
                            <span>Chat WA</span>
                        </a>
                    </template>
                </div>

                <div class="pt-2">
                    <span class="text-[10px] font-bold text-gray-400 block flex items-center gap-1">
                        <!-- Icon File Text Indigo -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                        </svg>
                        <span>Maksud Perjalanan:</span>
                    </span>
                    <p class="text-gray-600 bg-gray-50 p-2.5 rounded-lg border border-gray-100 text-[11px] italic mt-1" x-text="routeBookingDetail.purpose || '-'"></p>
                </div>
            </div>

            <div class="pt-2 border-t border-gray-100 flex justify-end">
                <button type="button" @click="showRouteModal = false" class="px-5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition">
                    Tutup
                </button>
            </div>

        </div>
    </div>
</div>