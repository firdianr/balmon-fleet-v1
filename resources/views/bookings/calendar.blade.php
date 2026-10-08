<x-app-layout>
    <x-slot name="title">Jadwal Peminjaman Armada</x-slot>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Kalender Jadwal Mobil Dinas') }}
            </h2>
            <a href="{{ route('bookings.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                + Ajukan Peminjaman
            </a>
        </div>
    </x-slot>

    {{-- WRAPPER ALPINE JS DENGAN LISTEN EVENT @open-calendar-modal.window --}}
    <div x-data="{ showModal: false, selectedEvent: {} }" 
         @open-calendar-modal.window="selectedEvent = $event.detail; showModal = true" 
         class="pt-2 pb-6">
        
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Legenda Warna Status --}}
            <div class="mb-4 flex flex-wrap gap-4 bg-white p-4 rounded-lg shadow-sm text-sm font-medium">
                <span class="flex items-center"><span class="w-3 h-3 bg-blue-500 rounded-full inline-block mr-2"></span> Approved (Disetujui)</span>
                <span class="flex items-center"><span class="w-3 h-3 bg-purple-500 rounded-full inline-block mr-2"></span> On Trip (Sedang Jalan)</span>
                <span class="flex items-center"><span class="w-3 h-3 bg-yellow-500 rounded-full inline-block mr-2"></span> Unconfirmed (Belum Dikonfirmasi)</span>
                <span class="flex items-center"><span class="w-3 h-3 bg-green-500 rounded-full inline-block mr-2"></span> Completed (Selesai)</span>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div id="calendar"></div>
            </div>
        </div>

        <!-- 🚀 MODAL DETAIL PEMINJAMAN -->
        <div x-show="showModal" 
             class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 overflow-y-auto" 
             x-cloak 
             x-transition>
            <div @click.away="showModal = false" class="bg-white rounded-xl p-6 max-w-lg w-full shadow-2xl relative my-auto">
                
                <!-- HEADER MODAL -->
                <div class="flex justify-between items-center pb-3 border-b mb-4">
                    <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span>🚗</span> Detail Peminjaman Armada
                    </h3>
                    <button type="button" @click="showModal = false" class="text-gray-400 hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 inline-flex items-center">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                    </button>
                </div>

                <!-- BODY MODAL -->
                <div class="space-y-3 text-sm text-gray-700">
                    <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                        <p class="text-xs text-gray-500 font-semibold uppercase">Armada / Peminjam</p>
                        <p class="font-bold text-base text-gray-900" x-text="selectedEvent.title || '-'"></p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-2.5 bg-gray-50 rounded-lg">
                            <p class="text-xs text-gray-500 font-semibold">Tujuan Perjalanan</p>
                            <p class="font-medium text-gray-800" x-text="selectedEvent.destination || '-'"></p>
                        </div>
                        <div class="p-2.5 bg-gray-50 rounded-lg">
                            <p class="text-xs text-gray-500 font-semibold">Status Peminjaman</p>
                            <span class="inline-block mt-0.5 px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider"
                                  :class="{
                                      'bg-blue-100 text-blue-800': selectedEvent.status === 'approved',
                                      'bg-purple-100 text-purple-800': selectedEvent.status === 'on_trip',
                                      'bg-yellow-100 text-yellow-800': selectedEvent.status === 'unconfirmed',
                                      'bg-green-100 text-green-800': selectedEvent.status === 'completed',
                                      'bg-gray-100 text-gray-800': !['approved','on_trip','unconfirmed','completed'].includes(selectedEvent.status)
                                  }"
                                  x-text="selectedEvent.status || '-'">
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-2.5 bg-gray-50 rounded-lg">
                            <p class="text-xs text-gray-500 font-semibold">Waktu Mulai</p>
                            <p class="font-medium text-gray-800" x-text="selectedEvent.start || '-'"></p>
                        </div>
                        <div class="p-2.5 bg-gray-50 rounded-lg">
                            <p class="text-xs text-gray-500 font-semibold">Waktu Selesai</p>
                            <p class="font-medium text-gray-800" x-text="selectedEvent.end || '-'"></p>
                        </div>
                    </div>

                    <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                        <p class="text-xs text-gray-500 font-semibold">Keperluan / Alasan</p>
                        <p class="font-normal text-gray-800 whitespace-pre-line" x-text="selectedEvent.purpose || '-'"></p>
                    </div>
                </div>

                <!-- FOOTER MODAL -->
                <div class="mt-6 pt-3 border-t flex justify-end gap-2">
                    <button type="button" @click="showModal = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-xs font-semibold rounded-lg transition">
                        Tutup
                    </button>
                    <template x-if="selectedEvent.slug">
                        <a :href="`/bookings/${selectedEvent.slug}`" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition shadow-sm inline-flex items-center gap-1">
                            <span>📄</span> Lihat Detail Lengkap
                        </a>
                    </template>
                </div>

            </div>
        </div>
    </div>

    {{-- CDN FullCalendar.js --}}
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'id',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listWeek'
                },
                events: '{{ route("api.calendar.events") }}',
                
                // 🚀 PAKAI WINDOW CUSTOM EVENT AGAR TERHUBUNG KE ALPINE
                eventClick: function(info) {
                    info.jsEvent.preventDefault();

                    let options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
                    let startFormatted = info.event.start ? new Date(info.event.start).toLocaleDateString('id-ID', options) : '-';
                    let endFormatted = info.event.end ? new Date(info.event.end).toLocaleDateString('id-ID', options) : '-';

                    // Dispatch event global yang didengarkan oleh Alpine.js
                    window.dispatchEvent(new CustomEvent('open-calendar-modal', {
                        detail: {
                            title: info.event.title,
                            destination: info.event.extendedProps.destination,
                            purpose: info.event.extendedProps.purpose,
                            status: info.event.extendedProps.status,
                            slug: info.event.extendedProps.letter_slug ?? info.event.extendedProps.slug,
                            start: startFormatted,
                            end: endFormatted
                        }
                    }));
                }
            });
            calendar.render();
        });
    </script>
</x-app-layout>