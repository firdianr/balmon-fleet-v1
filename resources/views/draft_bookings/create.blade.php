<x-app-layout>
    <x-slot name="title">Permohonan Armada</x-slot>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Permohonan Armada Baru
            </h2>
            <a href="{{ route('draft-bookings.index') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl text-sm font-semibold transition">
                Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm p-6 sm:p-8">

                {{-- FORM VALIDASI CLIENT-SIDE ALPINE --}}
                <form action="{{ route('draft-bookings.store') }}" 
                      method="POST" 
                      x-data="{
                          driverId: '{{ old('driver_id') }}',
                          driverName: '{{ old('driver_id') ? ($drivers->firstWhere('id', old('driver_id'))->name ?? '-- Pilih Driver --') : '-- Pilih Driver --' }}',
                          vehicleId: '{{ old('vehicle_id') }}',
                          vehicleName: '{{ old('vehicle_id') ? ($vehicles->firstWhere('id', old('vehicle_id'))->brand . ' ' . $vehicles->firstWhere('id', old('vehicle_id'))->model) : '-- Pilih Armada --' }}',
                          openDriver: false,
                          openVehicle: false,
                          clientError: '',
                          
                          validateAndSubmit(e) {
                              const startVal = document.getElementById('start_date').value;
                              const endVal = document.getElementById('end_date').value;

                              if (!startVal || !endVal) {
                                  e.preventDefault();
                                  this.clientError = 'Waktu tanggal mulai dan selesai peminjaman wajib diisi!';
                                  return;
                              }
                              if (!this.driverId) {
                                  e.preventDefault();
                                  this.clientError = 'Silakan pilih driver / pengemudi terlebih dahulu!';
                                  return;
                              }
                              if (!this.vehicleId) {
                                  e.preventDefault();
                                  this.clientError = 'Silakan pilih unit kendaraan terlebih dahulu!';
                                  return;
                              }
                              this.clientError = '';
                          }
                      }"
                      @submit="validateAndSubmit($event)"
                      class="space-y-6">
                    @csrf

                    {{-- PESAN ERROR SISI CLIENT --}}
                    <div x-show="clientError" x-cloak class="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-xs text-rose-800 font-bold flex items-center gap-2">
                        <span>⚠️</span> <span x-text="clientError"></span>
                    </div>

                    {{-- PESAN ERROR SISI SERVER --}}
                    @if ($errors->any())
                        <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-xs text-rose-800 space-y-2">
                            <div class="flex items-center gap-2 font-bold text-rose-900 text-sm">
                                <span>⚠️</span> Terjadi kesalahan pada inputan kamu:
                            </div>
                            <ul class="list-disc list-inside space-y-1 pl-1 font-medium">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="p-4 bg-indigo-50/70 border border-indigo-100 rounded-xl text-xs text-indigo-900 leading-relaxed flex items-start gap-3">
                        <span class="text-lg">ℹ️</span>
                        <div>
                            <p class="font-bold text-sm">Pengajuan Ketersediaan Cepat</p>
                            <p class="text-indigo-700">Silakan pilih tanggal, driver, dan kendaraan yang diinginkan. Setelah disetujui Admin Lapangan, kamu dapat mengunggah Surat Tugas.</p>
                        </div>
                    </div>

                    {{-- 1. INPUT WAKTU PEMINJAMAN --}}
                    <div class="space-y-2 bg-gray-50/80 p-4 rounded-xl border border-gray-200/60">
                        <label class="block text-xs font-bold text-gray-800 uppercase tracking-wider flex items-center gap-2 mb-2">
                            <span>📅</span> 1. Waktu Peminjaman <span class="text-rose-500">*</span>
                        </label>

                        <div class="flex items-center gap-3">
                            <div class="relative w-full">
                                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none text-gray-400 z-10">
                                    <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10h16m-8-3V4M7 7V4m10 3V4M5 20h14a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Zm3-7h.01v.01H8V13Zm4 0h.01v.01H12V13Zm4 0h.01v.01H16V13Zm-8 4h.01v.01H8V17Zm4 0h.01v.01H12V17Zm4 0h.01v.01H16V17Z"/></svg>
                                </div>
                                <input id="start_date" name="start_date" type="text" value="{{ old('start_date') }}" class="block w-full ps-9 pe-3 py-2.5 bg-white border border-gray-300 text-gray-900 text-xs font-semibold rounded-xl focus:ring-indigo-500 focus:border-indigo-500 shadow-2xs placeholder:text-gray-400" placeholder="Pilih tanggal mulai">
                            </div>

                            <span class="text-xs font-bold text-gray-400 uppercase shrink-0">s.d.</span>

                            <div class="relative w-full">
                                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none text-gray-400 z-10">
                                    <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10h16m-8-3V4M7 7V4m10 3V4M5 20h14a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Zm3-7h.01v.01H8V13Zm4 0h.01v.01H12V13Zm4 0h.01v.01H16V13Zm-8 4h.01v.01H8V17Zm4 0h.01v.01H12V17Zm4 0h.01v.01H16V17Z"/></svg>
                                </div>
                                <input id="end_date" name="end_date" type="text" value="{{ old('end_date') }}" class="block w-full ps-9 pe-3 py-2.5 bg-white border border-gray-300 text-gray-900 text-xs font-semibold rounded-xl focus:ring-indigo-500 focus:border-indigo-500 shadow-2xs placeholder:text-gray-400" placeholder="Pilih tanggal selesai">
                            </div>
                        </div>

                        @error('start_date') <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p> @enderror
                        @error('end_date') <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- 2. DROPDOWN DRIVER --}}
                    <div class="relative space-y-1">
                        <label class="block text-xs font-bold text-gray-800 uppercase tracking-wider flex items-center gap-2">
                            <span>🚖</span> 2. Driver / Pengemudi <span class="text-rose-500">*</span>
                        </label>

                        <input type="hidden" name="driver_id" :value="driverId">

                        <button @click="openDriver = !openDriver" @click.away="openDriver = false" type="button" class="w-full inline-flex items-center justify-between text-gray-800 bg-white border border-gray-300 font-medium rounded-xl text-xs px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-2xs">
                            <span x-text="driverName" class="truncate"></span>
                            <svg class="w-4 h-4 ms-1.5 -me-0.5 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/></svg>
                        </button>

                        <div x-show="openDriver" x-cloak class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                            <ul class="p-2 text-xs text-gray-700 font-medium space-y-1">
                                <li>
                                    <button type="button" @click="driverId = ''; driverName = '-- Pilih Driver --'; openDriver = false" class="inline-flex items-center w-full p-2 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg text-left text-gray-400">
                                        -- Pilih Driver --
                                    </button>
                                </li>
                                @foreach($drivers as $driver)
                                    <li>
                                        <button type="button" @click="driverId = '{{ $driver->id }}'; driverName = '{{ $driver->name }} ({{ $driver->phone ?? 'Tidak ada No. WA' }})'; openDriver = false" class="inline-flex items-center w-full p-2 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg text-left">
                                            {{ $driver->name }} ({{ $driver->phone ?? 'Tidak ada No. WA' }})
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        @error('driver_id') <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- 3. DROPDOWN KENDARAAN --}}
                    <div class="relative space-y-1">
                        <label class="block text-xs font-bold text-gray-800 uppercase tracking-wider flex items-center gap-2">
                            <span>🚗</span> 3. Pilih Unit Kendaraan <span class="text-rose-500">*</span>
                        </label>

                        <input type="hidden" name="vehicle_id" :value="vehicleId">

                        <button @click="openVehicle = !openVehicle" @click.away="openVehicle = false" type="button" class="w-full inline-flex items-center justify-between text-gray-800 bg-white border border-gray-300 font-medium rounded-xl text-xs px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-2xs">
                            <span x-text="vehicleName" class="truncate"></span>
                            <svg class="w-4 h-4 ms-1.5 -me-0.5 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/></svg>
                        </button>

                        <div x-show="openVehicle" x-cloak class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-52 overflow-y-auto">
                            <ul class="p-2 text-xs text-gray-700 font-medium space-y-1">
                                <li>
                                    <button type="button" @click="vehicleId = ''; vehicleName = '-- Pilih Armada --'; openVehicle = false" class="inline-flex items-center w-full p-2 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg text-left text-gray-400">
                                        -- Pilih Armada --
                                    </button>
                                </li>
                                @foreach($vehicles as $vehicle)
                                    <li>
                                        <button type="button" @click="vehicleId = '{{ $vehicle->id }}'; vehicleName = '{{ $vehicle->brand }} {{ $vehicle->model }} - {{ $vehicle->plate_number }} ({{ ucfirst($vehicle->type) }})'; openVehicle = false" class="inline-flex items-center justify-between w-full p-2 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg text-left">
                                            <span>{{ $vehicle->brand }} {{ $vehicle->model }} ({{ $vehicle->plate_number }})</span>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-gray-100 text-gray-600 uppercase">{{ $vehicle->type }}</span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-1">*Keterangan (Operational / Monitoring) tertera di sebelah kanan setiap opsi.</p>
                        @error('vehicle_id') <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- FIELD HIDDEN --}}
                    <input type="hidden" name="destination" value="Dalam / Luar Kota">
                    <input type="hidden" name="purpose" value="Permohonan Armada Sementara">

                    <div class="pt-4 border-t border-gray-100 flex justify-end gap-3">
                        <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md transition">
                            Kirim Permohonan
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>