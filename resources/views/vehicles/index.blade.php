<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    {{ __('Daftar Armada Kendaraan Dinas') }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Pilih kendaraan dinas operasional atau monitoring yang tersedia untuk kebutuhan perjalanan dinas.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="pt-2 pb-6" x-data="{ filterType: 'all' }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- BAR FILTER JENIS KENDARAAN --}}
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2 text-sm font-bold text-gray-700">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.447.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                    </svg>
                    <span>Filter Jenis Armada:</span>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    {{-- Tombol Semua --}}
                    <button type="button" 
                            @click="filterType = 'all'"
                            :class="filterType === 'all' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-4 py-2 rounded-lg text-xs font-bold transition-all">
                        Semua Armada
                    </button>

                    {{-- Tombol Operasional --}}
                    <button type="button" 
                            @click="filterType = 'operational'"
                            :class="filterType === 'operational' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-4 py-2 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                        </svg>
                        <span>Operasional</span>
                    </button>

                    {{-- Tombol Monitoring --}}
                    <button type="button" 
                            @click="filterType = 'monitoring'"
                            :class="filterType === 'monitoring' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-4 py-2 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                        </svg>
                        <span>Monitoring</span>
                    </button>
                </div>
            </div>

            {{-- GRID CARD ARMADA KENDARAAN --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($vehicles as $vehicle)
                    <div x-show="filterType === 'all' || filterType === '{{ $vehicle->type }}'"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="bg-white p-6 border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-all duration-200 flex flex-col justify-between relative overflow-hidden">
                        
                        <div>
                            {{-- CONTAINER GAMBAR KENDARAAN --}}
                            <div class="relative h-56 w-full bg-gray-100 rounded-xl overflow-hidden mb-5 border border-gray-200">
                                @if($vehicle->photo)
                                    <img src="{{ asset('storage/' . $vehicle->photo) }}" 
                                         alt="{{ $vehicle->brand }} {{ $vehicle->model }}" 
                                         class="w-full h-full object-cover transform hover:scale-105 transition-transform duration-300">
                                @else
                                    <div class="w-full h-full flex flex-col items-center justify-center text-gray-400 bg-gray-50">
                                        <svg class="w-16 h-16 mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                        </svg>
                                        <span class="text-sm font-semibold">Foto Belum Tersedia</span>
                                    </div>
                                @endif

                                {{-- KODE ARMADA PERSEGI TRANSPARAN --}}
                                <div class="absolute top-0 left-0 w-20 h-20 bg-gray-900/60 backdrop-blur-md border-r border-b border-white/20 flex items-center justify-center text-white z-10 shadow-md rounded-tl-xl">
                                    <span class="text-3xl font-black font-mono text-white tracking-widest">{{ $vehicle->code }}</span>
                                </div>

                                {{-- BADGE STATUS KETERSEDIAAN --}}
                                <div class="absolute top-3 right-3 z-10">
                                    <span class="px-3 py-1.5 text-xs font-black uppercase tracking-wider text-white rounded-lg shadow-md border border-white/20"
                                          :class="{
                                              'bg-emerald-600': '{{ $vehicle->status }}' === 'available',
                                              'bg-amber-500': '{{ $vehicle->status }}' === 'borrowed',
                                              'bg-rose-600': '{{ $vehicle->status }}' === 'maintenance'
                                          }">
                                        @if($vehicle->status === 'available')
                                            AVAILABLE
                                        @elseif($vehicle->status === 'borrowed')
                                            DIPINJAM
                                        @else
                                            MAINTENANCE
                                        @endif
                                    </span>
                                </div>
                            </div>

                            {{-- TIPE (SVG) & PLAT NOMOR --}}
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-bold uppercase tracking-wider text-indigo-700 bg-indigo-50 rounded border border-indigo-200">
                                    @if($vehicle->type === 'monitoring')
                                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                                        </svg>
                                        <span>Monitoring</span>
                                    @else
                                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                                        </svg>
                                        <span>Operasional</span>
                                    @endif
                                </span>

                                <span class="px-3 py-1 bg-gray-900 text-white font-mono font-bold text-sm rounded shadow-sm">
                                    {{ $vehicle->plate_number }}
                                </span>
                            </div>

                            <h5 class="text-2xl font-bold tracking-tight text-gray-900 my-2">
                                {{ $vehicle->brand }} {{ $vehicle->model }}
                            </h5>

                            {{-- DETAIL ATRIBUT --}}
                            <div class="grid grid-cols-2 gap-3 my-4 p-3 bg-gray-50 rounded-xl border border-gray-200 text-sm text-gray-700">
                                <div>
                                    <span class="block text-[10px] uppercase font-bold text-gray-400">Kapasitas</span>
                                    <span class="font-bold text-gray-900">{{ $vehicle->capacity }} Orang</span>
                                </div>
                                <div>
                                    <span class="block text-[10px] uppercase font-bold text-gray-400">BBM</span>
                                    <span class="font-bold text-gray-900">{{ $vehicle->fuel_type }}</span>
                                </div>
                            </div>

                            {{-- CATATAN KENDARAAN --}}
                            <p class="mb-2 text-sm text-gray-600 line-clamp-2">
                                {{ $vehicle->notes ?? 'Tidak ada catatan tambahan untuk armada ini.' }}
                            </p>
                        </div>

                    </div>
                @empty
                    <div class="col-span-full bg-white p-12 text-center rounded-xl border-2 border-dashed border-gray-300">
                        <svg class="w-16 h-16 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                        </svg>
                        <h3 class="text-xl font-bold text-gray-800 mb-1">Belum Ada Armada Kendaraan</h3>
                        <p class="text-base text-gray-500">Tidak ada data armada kendaraan yang tersedia saat ini.</p>
                    </div>
                @endforelse
            </div>

        </div>
    </div>
</x-app-layout>