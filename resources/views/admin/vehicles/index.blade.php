<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Kelola Armada Kendaraan') }}
        </h2>
    </x-slot>

    <div class="pt-2 pb-6" 
         x-data="{ 
             search: '', 
             vehicles: {{ json_encode($vehicles->items()) }}, 
             showDeleteModal: false,
             selectedVehicle: null,
             isLoading: false,
             fetchVehicles() {
                 this.isLoading = true;
                 fetch(`/api/search/vehicles?q=${encodeURIComponent(this.search)}`)
                     .then(res => res.json())
                     .then(data => {
                         this.vehicles = data.data;
                         this.isLoading = false;
                     })
                     .catch(() => { this.isLoading = false; });
             }
         }"
         x-init="$watch('search', value => fetchVehicles())">
        
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                {{-- KOTAK PENCARIAN REAL-TIME & TOMBOL TAMBAH MOBIL --}}
                <div class="mb-6 flex justify-between items-center gap-4">
                    <div class="relative w-full max-w-md">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            🔍
                        </div>
                        <input type="text" 
                               x-model="search" 
                               @input.debounce.300ms="fetchVehicles()" 
                               placeholder="Cari Plat Nomor, Merk, Model, BBM, atau Status..." 
                               class="pl-10 pr-10 py-2 w-full text-sm border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm">
                        
                        {{-- Spinner Loader Saat Mengetik --}}
                        <div x-show="isLoading" class="absolute inset-y-0 right-0 pr-3 flex items-center" x-cloak>
                            <svg class="animate-spin h-4 w-4 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>

                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.vehicles.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold hover:bg-indigo-700 transition">
                            + Tambah Armada Mobil
                        </a>
                    @endif
                </div>

                {{-- TABEL ARMADA MOBIL --}}
                <div class="text-gray-900 overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b bg-gray-50 text-xs font-semibold text-gray-600 uppercase">
                                <th class="p-3">Plat Nomor</th>
                                <th class="p-3">Merk & Model</th>
                                <th class="p-3">Kapasitas</th>
                                <th class="p-3">Bahan Bakar</th>
                                <th class="p-3">Status Armada</th>
                                @if(auth()->user()->isAdmin())
                                    <th class="p-3 text-center">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 text-sm">
                            <template x-for="vehicle in vehicles" :key="vehicle.id">
                                <tr>
                                    <td class="p-3 font-mono font-bold text-indigo-600" x-text="vehicle.plate_number"></td>
                                    <td class="p-3 font-medium" x-text="`${vehicle.brand} ${vehicle.model}`"></td>
                                    <td class="p-3" x-text="`${vehicle.capacity} Orang`"></td>
                                    <td class="p-3" x-text="vehicle.fuel_type"></td>
                                    <td class="p-3">
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-full"
                                              :class="{
                                                  'bg-green-100 text-green-800': vehicle.status === 'available',
                                                  'bg-purple-100 text-purple-800': vehicle.status === 'borrowed',
                                                  'bg-red-100 text-red-800': vehicle.status === 'maintenance'
                                              }">
                                            <span x-text="vehicle.status === 'available' ? 'TERSEDIA' : (vehicle.status === 'borrowed' ? 'DIPAKAI' : 'MAINTENANCE')"></span>
                                        </span>
                                    </td>
                                    @if(auth()->user()->isAdmin())
                                        <td class="p-3 text-center whitespace-nowrap">
                                            <div class="inline-flex items-center justify-center gap-1.5">
                                                <a :href="`/admin/vehicles/${vehicle.slug}/edit`" class="inline-flex items-center px-2.5 py-1.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold rounded shadow-sm transition">
                                                    ✏️ Edit
                                                </a>
                                                <button type="button" 
                                                    @click="selectedVehicle = vehicle; showDeleteModal = true" 
                                                    class="inline-flex items-center px-2.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded shadow-sm transition">
                                                    🗑 Hapus
                                                 </button>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            </template>
                            
                            <!-- MODAL KONFIRMASI HAPUS MOBIL -->
                            <div x-show="showDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50" x-cloak>
                                <div class="bg-white rounded-lg shadow relative max-w-sm w-full">
                                    <div class="flex justify-end p-2">
                                        <button type="button" @click="showDeleteModal = false" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                                        </button>
                                    </div>

                                    <div class="p-6 pt-0 text-center">
                                        <svg class="w-20 h-20 text-red-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <h3 class="text-lg font-normal text-gray-500 mt-5 mb-6">
                                            Apakah Anda yakin ingin menghapus armada <strong x-text="selectedVehicle ? selectedVehicle.brand + ' ' + selectedVehicle.model + ' (' + selectedVehicle.plate_number + ')' : ''"></strong>?
                                        </h3>
                                        
                                        <form :action="'/admin/vehicles/' + (selectedVehicle ? selectedVehicle.slug : '')" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm inline-flex items-center px-4 py-2.5 text-center mr-2">
                                                Ya, Hapus
                                            </button>
                                        </form>
                                        
                                        <button type="button" @click="showDeleteModal = false" class="text-gray-900 bg-white hover:bg-gray-100 focus:ring-4 focus:ring-cyan-200 border border-gray-200 font-medium inline-flex items-center rounded-lg text-sm px-4 py-2.5 text-center">
                                            Batal
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- JIKA HASIL TIDAK DITEMUKAN --}}
                            <tr x-show="vehicles.length === 0">
                                <td colspan="6" class="p-4 text-center text-gray-500">
                                    Data kendaraan tidak ditemukan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4" x-show="search === ''">
                    {{ $vehicles->links() }}
                </div>

            </div>
        </div>
    </div>
</x-app-layout>