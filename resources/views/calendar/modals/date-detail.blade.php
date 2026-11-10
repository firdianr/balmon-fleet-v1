{{-- MODAL STATUS KETERSEDIAAN DRIVER (PER TANGGAL) --}}
<div x-show="showDateModal" 
    class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-40" 
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    x-cloak>
    
    <div class="bg-white rounded-2xl shadow-xl max-w-3xl w-full overflow-hidden" 
         @click.away="if(!showAttendanceModal && !showDeleteModal && !showDetailModal && !showApprovalModal) showDateModal = false"
         @click.stop>
        <div class="bg-indigo-600 px-6 py-4 text-white flex justify-between items-center">
            <div>
                <h3 class="font-bold text-lg flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-indigo-100 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                    </svg>
                    <span>Status Ketersediaan Driver</span>
                </h3>
                <p class="text-xs text-indigo-100 mt-0.5" x-text="selectedDateFormatted"></p>
            </div>
            <button type="button" @click="showDateModal = false" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">&times;</button>
        </div>

        <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto text-xs">
            
            {{-- 1. DRIVER STANDBY --}}
            <div>
                <h4 class="text-xs font-black uppercase tracking-wider text-emerald-800 bg-emerald-50 px-3.5 py-2 rounded-xl border border-emerald-200 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    <span>Driver Standby / Tersedia (<span x-text="availableDrivers.length"></span>)</span>
                </h4>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <template x-for="drv in availableDrivers" :key="drv.id">
                        <div class="p-3 bg-white border border-gray-200 rounded-xl flex items-center justify-between shadow-2xs">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center text-xs">
                                    <svg class="w-4 h-4 text-emerald-700 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                        <circle cx="12" cy="7" r="4"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-bold text-gray-900" x-text="drv.name"></p>
                                    <p class="text-[10px] text-gray-500" x-text="drv.phone || 'No WA (-)'"></p>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg">READY</span>
                        </div>
                    </template>
                    <template x-if="availableDrivers.length === 0">
                        <p class="col-span-full text-xs text-gray-500 italic p-3 bg-gray-50 rounded-xl border text-center">Tidak ada driver yang standby pada tanggal ini.</p>
                    </template>
                </div>
            </div>

            {{-- 2. DRIVER BERTUGAS DINAS --}}
            <div>
                <h4 class="text-xs font-black uppercase tracking-wider text-indigo-800 bg-indigo-50 px-3.5 py-2 rounded-xl border border-indigo-200 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-600 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="1" y="3" width="15" height="13"/>
                        <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                        <circle cx="5.5" cy="18.5" r="2.5"/>
                        <circle cx="18.5" cy="18.5" r="2.5"/>
                    </svg>
                    <span>Driver Bertugas Dinas (<span x-text="assignedBookings.length"></span>)</span>
                </h4>

                <div class="space-y-2">
                    <template x-for="item in assignedBookings" :key="'bk_' + item.id">
                        <div class="p-3 bg-gray-50 border border-gray-200 rounded-xl flex items-center justify-between gap-3">
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-gray-900" x-text="item.driver_name"></span>
                                    <span class="px-2 py-0.5 text-[9px] font-bold text-white rounded-lg" :class="getBookingStatusBg(item.status)" x-text="item.status.toUpperCase()"></span>
                                </div>
                                <p class="text-[11px] text-gray-600">
                                    Pemohon: <strong x-text="item.user_name"></strong> • Armada: <span class="font-mono" x-text="item.vehicle_name + ' (' + item.plate_number + ')'"></span>
                                </p>
                            </div>
                            <button type="button" @click="selectedDetailItem = item; showDetailModal = true" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-[11px] rounded-xl transition shrink-0">
                                Detail
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            {{-- 3. KETIDAKHADIRAN / PERMOHONAN CUTI --}}
            <div>
                <h4 class="text-xs font-black uppercase tracking-wider text-amber-800 bg-amber-50 px-3.5 py-2 rounded-xl border border-amber-200 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-600 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    <span>Ketidakhadiran Driver / Cuti (<span x-text="absentDrivers.length"></span>)</span>
                </h4>

                <div class="space-y-2">
                    <template x-for="item in absentDrivers" :key="'att_' + item.id">
                        <div class="p-3 bg-amber-50/60 border border-amber-200 rounded-xl flex items-center justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-amber-950" x-text="item.driver_name"></span>
                                    <span class="px-2 py-0.5 text-[9px] font-bold text-white rounded-lg uppercase" :class="getAttendanceStatusBg(item.status)" x-text="item.status"></span>
                                    
                                    {{-- BADGE PROCESS STATUS --}}
                                    <span class="px-2 py-0.5 text-[9px] font-bold rounded-lg uppercase border flex items-center gap-1"
                                          :class="{
                                              'bg-amber-100 text-amber-800 border-amber-300': item.process_status === 'pending',
                                              'bg-emerald-100 text-emerald-800 border-emerald-300': item.process_status === 'approved',
                                              'bg-rose-100 text-rose-800 border-rose-300': item.process_status === 'rejected'
                                          }">
                                        <template x-if="item.process_status === 'pending'">
                                            <svg class="w-3 h-3 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                        </template>
                                        <template x-if="item.process_status === 'approved'">
                                            <svg class="w-3 h-3 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                        </template>
                                        <template x-if="item.process_status === 'rejected'">
                                            <svg class="w-3 h-3 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        </template>
                                        <span x-text="item.process_status === 'pending' ? 'MENUNGGU APPROVAL' : (item.process_status === 'approved' ? 'SAH / DISETUJUI' : 'DITOLAK')"></span>
                                    </span>
                                </div>
                                <p class="text-[11px] text-amber-900 mt-1" x-text="'Catatan: ' + item.notes"></p>
                            </div>
                            
                            {{-- TOMBOL ACTION --}}
                            <div class="flex items-center gap-1.5 shrink-0">
                                {{-- 1. Tombol Tinjau / Approval (Khusus Admin) --}}
                                @if(in_array($currentUser->role, ['admin_app', 'admin_lap']))
                                    <button type="button" 
                                            @click="selectedAttendance = item; approvalStatus = 'approved'; showApprovalModal = true" 
                                            class="px-2.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-[10px] rounded-xl transition shadow-xs flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M9 11l3 3L22 4"/>
                                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                                        </svg>
                                        <span>Tinjau</span>
                                    </button>
                                @endif

                                {{-- 2. Tombol Edit & Hapus --}}
                                {{-- Syarat: Admin selalu bisa edit/hapus, sedangkan Driver HANYA BISA jika process_status !== 'approved' --}}
                                @if(in_array($currentUser->role, ['admin_app', 'admin_lap']))
                                    {{-- Akses Penuh untuk Admin --}}
                                    <button type="button" @click="editAttendance(item)" class="p-1.5 bg-amber-100 hover:bg-amber-200 text-amber-800 rounded-lg transition" title="Edit Status">
                                        <svg class="w-4 h-4 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </button>
                                    
                                    <button type="button" @click="confirmDeleteAttendance(item.id)" class="p-1.5 bg-rose-100 hover:bg-rose-200 text-rose-800 rounded-lg transition" title="Hapus">
                                        <svg class="w-4 h-4 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"/>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                        </svg>
                                    </button>
                                @else
                                    {{-- Akses Terbatas untuk Driver (Hanya jika belum disetujui / pending atau rejected) --}}
                                    <template x-if="item.process_status !== 'approved'">
                                        <div class="flex items-center gap-1.5">
                                            <button type="button" @click="editAttendance(item)" class="p-1.5 bg-amber-100 hover:bg-amber-200 text-amber-800 rounded-lg transition" title="Edit Status">
                                                <svg class="w-4 h-4 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                                </svg>
                                            </button>
                                            
                                            <button type="button" @click="confirmDeleteAttendance(item.id)" class="p-1.5 bg-rose-100 hover:bg-rose-200 text-rose-800 rounded-lg transition" title="Hapus">
                                                <svg class="w-4 h-4 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="3 6 5 6 21 6"/>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </template>
                                @endif
                            </div>
                        </div>
                    </template>
                </div>
            </div>

        </div>
    </div>
</div>