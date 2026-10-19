<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    {{ __('Kelola Armada Kendaraan') }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Daftar seluruh kendaraan dinas (operasional & monitoring) Balmon Semarang.
                </p>
            </div>
            
            {{-- Tombol Tambah Armada --}}
            <a href="{{ route('admin_app.vehicles.create') }}" 
               class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-base font-bold rounded-lg shadow transition-all duration-150">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Tambah Armada Baru</span>
            </a>
        </div>
    </x-slot>

    <div class="pt-2 pb-6" x-data="{ 
        showDeleteModal: false, 
        selectedVehicle: null,
        filterType: 'all',
        searchKeyword: ''
    }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- BAR FILTER & SEARCH --}}
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm space-y-3 sm:space-y-0 sm:flex sm:items-center sm:justify-between sm:gap-4">
                
                {{-- FILTER JENIS ARMADA --}}
                <div class="flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-800 dark:text-blue-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M5.05 3C3.291 3 2.352 5.024 3.51 6.317l5.422 6.059v4.874c0 .472.227.917.613 1.2l3.069 2.25c1.01.742 2.454.036 2.454-1.2v-7.124l5.422-6.059C21.647 5.024 20.708 3 18.95 3H5.05Z"/>
                    </svg>
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider hidden md:inline-block">Tipe:</span>
                    <div class="flex items-center gap-1.5 w-full sm:w-auto">
                        <button type="button" 
                                @click="filterType = 'all'"
                                :class="filterType === 'all' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                class="px-3.5 py-2 rounded-lg text-xs font-bold transition-all">
                            Semua
                        </button>

                        <button type="button" 
                                @click="filterType = 'operational'"
                                :class="filterType === 'operational' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                class="px-3.5 py-2 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                            </svg>
                            <span>Operasional</span>
                        </button>

                        <button type="button" 
                                @click="filterType = 'monitoring'"
                                :class="filterType === 'monitoring' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                class="px-3.5 py-2 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                            </svg>
                            <span>Monitoring</span>
                        </button>
                    </div>
                </div>

                {{-- SEARCH BAR --}}
                <div class="relative w-full sm:w-72">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" 
                        x-model="searchKeyword" 
                        placeholder="Cari merk, model, plat, kode..." 
                        class="block w-full pl-9 pr-8 py-2 bg-gray-50 border border-gray-300 rounded-lg text-xs font-medium focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    
                    {{-- Tombol Clear Search --}}
                    <button type="button" 
                            x-show="searchKeyword.length > 0" 
                            @click="searchKeyword = ''" 
                            class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-gray-400 hover:text-gray-600 font-bold text-xs">
                        &times;
                    </button>
                </div>

            </div>

            {{-- GRID CARD ARMADA KENDARAAN --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($vehicles as $vehicle)
                    <div x-show="(filterType === 'all' || filterType === '{{ $vehicle->type }}') && 
                            ('{{ strtolower($vehicle->brand . ' ' . $vehicle->model . ' ' . $vehicle->plate_number . ' ' . $vehicle->code) }}'.includes(searchKeyword.toLowerCase()))"
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

                                {{-- KODE ARMADA PERSEGI DENGAN BACKGROUND TRANSPARAN (POJOK KIRI ATAS) --}}
                                <div class="absolute top-0 left-0 w-20 h-20 bg-gray-900/60 backdrop-blur-md border-r border-b border-white/20 flex items-center justify-center text-white z-10 shadow-md rounded-tl-xl">
                                    <span class="text-3xl font-black font-mono text-white tracking-widest">{{ $vehicle->code }}</span>
                                </div>

                                {{-- BADGE STATUS KETERSEDIAAN (KANAN ATAS) --}}
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

                            {{-- TIPE (PAKAI SVG) & PLAT NOMOR --}}
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-bold uppercase tracking-wider text-indigo-700 bg-indigo-50 rounded border border-indigo-200">
                                    @if($vehicle->type === 'monitoring')
                                        {{-- SVG Monitoring --}}
                                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                                        </svg>
                                        <span>Monitoring</span>
                                    @else
                                        {{-- SVG Operasional --}}
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

                            {{-- DETAIL ATRIBUT (KAPASITAS & BAHAN BAKAR) --}}
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
                            <p class="mb-5 text-sm text-gray-600 line-clamp-2">
                                {{ $vehicle->notes ?? 'Tidak ada catatan tambahan untuk armada ini.' }}
                            </p>
                        </div>

                        {{-- TOMBOL AKSI EDIT & HAPUS --}}
                        <div class="pt-4 border-t border-gray-100 flex items-center justify-between gap-3">
                            <a href="{{ route('admin_app.vehicles.edit', $vehicle->slug) }}" 
                               class="w-1/2 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold rounded-lg shadow-sm transition">
                                <svg class="w-5 h-5 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.779 17.779 4.36 19.918 6.5 13.5m4.279 4.279 8.364-8.643a3.027 3.027 0 0 0-2.14-5.165 3.03 3.03 0 0 0-2.14.886L6.5 13.5m4.279 4.279L6.499 13.5m2.14 2.14 6.213-6.504M12.75 7.04 17 11.28"/>
                                </svg>
                                <span>Edit</span>
                            </a>

                            <button type="button" 
                                    @click="selectedVehicle = {{ json_encode($vehicle) }}; showDeleteModal = true"
                                    class="w-1/2 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-sm font-bold rounded-lg shadow-sm transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                <span>Hapus</span>
                            </button>
                        </div>

                    </div>
                @empty
                    <div class="col-span-full bg-white p-12 text-center rounded-xl border-2 border-dashed border-gray-300">
                        <svg class="w-16 h-16 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                        </svg>
                        <h3 class="text-xl font-bold text-gray-800 mb-1">Belum Ada Armada Kendaraan</h3>
                        <p class="text-base text-gray-500 mb-6">Silakan tambahkan data kendaraan dinas baru ke dalam sistem.</p>
                        <a href="{{ route('admin_app.vehicles.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 shadow">
                            <span>➕ Tambah Armada Sekarang</span>
                        </a>
                    </div>
                @endforelse
            </div>

        </div>

        {{-- MODAL KONFIRMASI HAPUS KENDARAAN --}}
        <div x-show="showDeleteModal" 
             class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             x-cloak>
            
            <div class="bg-white rounded-xl shadow-xl max-w-lg w-full overflow-hidden" @click.away="showDeleteModal = false">
                
                {{-- HEADER MODAL --}}
                <div class="bg-rose-600 px-6 py-4 text-white flex justify-between items-center">
                    <h3 class="font-bold text-lg flex items-center gap-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <span>Konfirmasi Hapus Armada Kendaraan</span>
                    </h3>
                    <button type="button" @click="showDeleteModal = false" class="text-white/80 hover:text-white text-2xl font-bold">&times;</button>
                </div>

                {{-- BODY MODAL --}}
                <div class="p-6 space-y-4">
                    <div class="flex items-start gap-3 bg-rose-50 p-3.5 rounded-lg border border-rose-200 text-rose-800 text-sm">
                        <svg class="w-6 h-6 flex-shrink-0 text-rose-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <p>
                            <strong>Peringatan!</strong> Data kendaraan ini akan dihapus permanen dari sistem beserta foto yang terhubung.
                        </p>
                    </div>

                    <p class="text-sm text-gray-600">Berikut adalah ringkasan data armada yang akan dihapus:</p>
                    
                    {{-- RINGKASAN DATA KENDARAAN YANG DIHAPUS --}}
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 text-sm space-y-3">
                        <div class="flex justify-between border-b pb-2">
                            <span class="text-gray-500 font-medium">Merk & Model:</span>
                            <span class="font-bold text-gray-900" x-text="selectedVehicle ? (selectedVehicle.brand + ' ' + selectedVehicle.model) : '-'"></span>
                        </div>
                        <div class="flex justify-between border-b pb-2">
                            <span class="text-gray-500 font-medium">Nomor Plat:</span>
                            <span class="font-mono text-sm font-bold text-indigo-600" x-text="selectedVehicle ? selectedVehicle.plate_number : '-'"></span>
                        </div>
                        <div class="flex justify-between border-b pb-2">
                            <span class="text-gray-500 font-medium">Kode Kendaraan:</span>
                            <span class="font-mono text-sm font-bold text-gray-800" x-text="selectedVehicle ? selectedVehicle.code : '-'"></span>
                        </div>
                        <div class="flex justify-between border-b pb-2">
                            <span class="text-gray-500 font-medium">Tipe Armada:</span>
                            <span class="font-bold text-gray-800 capitalize" x-text="selectedVehicle ? selectedVehicle.type : '-'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 font-medium">Status Saat Ini:</span>
                            <span class="font-black px-2.5 py-0.5 rounded text-xs uppercase" 
                                  :class="{
                                      'bg-emerald-100 text-emerald-800 border border-emerald-300': selectedVehicle && selectedVehicle.status === 'available',
                                      'bg-amber-100 text-amber-800 border border-amber-300': selectedVehicle && selectedVehicle.status === 'borrowed',
                                      'bg-rose-100 text-rose-800 border border-rose-300': selectedVehicle && selectedVehicle.status === 'maintenance'
                                  }"
                                  x-text="selectedVehicle ? selectedVehicle.status : '-'"></span>
                        </div>
                    </div>

                    {{-- TOMBOL AKSI MODAL --}}
                    <div class="flex items-center gap-3 pt-3">
                        <button type="button" 
                                @click="showDeleteModal = false" 
                                class="w-1/2 py-2.5 bg-gray-100 text-gray-700 text-sm font-bold rounded-lg hover:bg-gray-200 transition">
                            Batal
                        </button>
                        
                        <form :action="'/admin-app/vehicles/' + (selectedVehicle ? selectedVehicle.slug : '')" method="POST" class="w-1/2">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    class="w-full py-2.5 bg-rose-600 text-white text-sm font-bold rounded-lg hover:bg-rose-700 shadow transition flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                <span>Ya, Hapus Armada</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>