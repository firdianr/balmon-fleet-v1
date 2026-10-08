<x-app-layout>
    <x-slot name="title">Daftar Armada Kendaraan</x-slot>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Daftar Armada Kendaraan Operasional') }}
            </h2>
            
            {{-- Tombol Tambah Khusus Admin --}}
            @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.vehicles.create') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow transition">
                    + Tambah Armada Baru
                </a>
            @endif
        </div>
    </x-slot>

    <div class="pt-2 pb-6" 
         x-data="{ 
             showDeleteModal: false, 
             selectedVehicle: null 
         }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            {{-- GRID CARD KENDARAAN --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($vehicles as $vehicle)
                    @php
                        // Ambil log kilometer & bensin terakhir jika ada
                        $lastLog = $vehicle->bookings->first()->log ?? null;
                        $latestKm = $lastLog ? ($lastLog->end_km ?? $lastLog->start_km) : null;
                        $latestFuel = $lastLog ? ($lastLog->end_fuel_level ?? $lastLog->start_fuel_level) : null;
                    @endphp

                    <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100 flex flex-col justify-between hover:shadow-lg transition duration-200">
                        <div>
                            {{-- GAMBAR MOBIL & BADGE STATUS --}}
                            <div class="relative h-48 bg-gray-100 overflow-hidden">
                                @if($vehicle->photo)
                                    <img src="{{ asset('storage/' . $vehicle->photo) }}" alt="{{ $vehicle->brand }} {{ $vehicle->model }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-400 bg-gray-200">
                                        <span class="text-4xl">🚘</span>
                                    </div>
                                @endif

                                {{-- BADGE STATUS AVAILABILITY --}}
                                <div class="absolute top-3 right-3">
                                    @if($vehicle->status === 'available')
                                        <span class="px-3 py-1 bg-emerald-500 text-white text-xs font-bold rounded-full shadow">
                                            TERSEDIA
                                        </span>
                                    @elseif($vehicle->status === 'borrowed')
                                        <span class="px-3 py-1 bg-purple-600 text-white text-xs font-bold rounded-full shadow">
                                            DIPAKAI
                                        </span>
                                    @else
                                        <span class="px-3 py-1 bg-rose-500 text-white text-xs font-bold rounded-full shadow">
                                            MAINTENANCE
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- DETAIL KENDARAAN --}}
                            <div class="p-5">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900">{{ $vehicle->brand }} {{ $vehicle->model }}</h3>
                                        <p class="text-xs font-mono font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded inline-block mt-1">
                                            {{ $vehicle->plate_number }}
                                        </p>
                                    </div>
                                </div>

                                {{-- SPESIFIKASI MOBIL --}}
                                <div class="grid grid-cols-2 gap-3 text-xs mt-4 py-3 border-y border-gray-100">
                                    <div>
                                        <span class="text-gray-400 block">Kapasitas</span>
                                        <span class="font-semibold text-gray-700">👥 {{ $vehicle->capacity }} Orang</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-400 block">Bahan Bakar</span>
                                        <span class="font-semibold text-gray-700">⛽ {{ $vehicle->fuel_type }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-400 block">Odometer Terakhir</span>
                                        <span class="font-semibold text-gray-700">
                                            📟 {{ $latestKm ? number_format($latestKm) . ' KM' : '-' }}
                                        </span>
                                    </div>
                                    <div>
                                        <span class="text-gray-400 block">Sisa BBM Terakhir</span>
                                        <span class="font-semibold text-gray-700">
                                            📊 {{ $latestFuel ?? '-' }}
                                        </span>
                                    </div>
                                </div>

                                @if($vehicle->notes)
                                    <p class="text-xs text-gray-500 mt-3 italic line-clamp-2">
                                        Catatan: "{{ $vehicle->notes }}"
                                    </p>
                                @endif
                            </div>
                        </div>

                        {{-- FOOTER CARD: AKSI DUA ROLE --}}
                        <div class="px-5 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between gap-2">
                            @if(auth()->user()->isAdmin())
                                {{-- Aksi Admin --}}
                                <div class="inline-flex gap-1.5 w-full">
                                    <a href="{{ route('admin.vehicles.edit', $vehicle) }}" class="flex-1 text-center py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold rounded-lg transition">
                                        ✏️️ Edit
                                    </a>
                                    <button type="button" 
                                            @click="selectedVehicle = {{ json_encode($vehicle) }}; showDeleteModal = true" 
                                            class="flex-1 text-center py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-lg transition">
                                        🗑 Hapus
                                    </button>
                                </div>
                            @else
                                {{-- Aksi Pegawai --}}
                                @if($vehicle->status === 'available')
                                    <a href="{{ route('bookings.create', ['vehicle' => $vehicle->slug]) }}" class="w-full text-center py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition">
                                        Gunakan Mobil Ini
                                    </a>
                                @else
                                    <button disabled class="w-full text-center py-2 bg-gray-200 text-gray-400 text-xs font-semibold rounded-lg cursor-not-allowed">
                                        Tidak Tersedia
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-3 bg-white p-8 text-center rounded-xl text-gray-500">
                        Belum ada armada kendaraan yang terdaftar.
                    </div>
                @endforelse
            </div>

        </div>

        {{-- MODAL KONFIRMASI HAPUS UNTUK ADMIN --}}
        <div x-show="showDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50" x-cloak>
            <div class="bg-white rounded-lg shadow relative max-w-sm w-full p-6 text-center">
                <svg class="w-16 h-16 text-rose-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3 class="text-base font-semibold text-gray-800 mb-2">
                    Hapus Armada Kendaraan?
                </h3>
                <p class="text-xs text-gray-500 mb-6">
                    Anda akan menghapus <strong x-text="selectedVehicle ? selectedVehicle.brand + ' ' + selectedVehicle.model : ''"></strong> (<span x-text="selectedVehicle ? selectedVehicle.plate_number : ''"></span>).
                </p>
                
                <form :action="'/admin/vehicles/' + (selectedVehicle ? selectedVehicle.slug : '')" method="POST" class="inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-lg shadow transition mr-2">
                        Ya, Hapus
                    </button>
                </form>
                
                <button type="button" @click="showDeleteModal = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-semibold rounded-lg transition">
                    Batal
                </button>
            </div>
        </div>
    </div>
</x-app-layout>