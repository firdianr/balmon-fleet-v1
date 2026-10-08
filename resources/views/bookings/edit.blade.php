<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Permohonan Peminjaman') }}
        </h2>
    </x-slot>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <div class="pt-2 pb-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                @if($errors->any())
                    <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-r-lg">
                        <ul class="list-disc pl-5 text-sm space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('bookings.update', $booking) }}" method="POST" enctype="multipart/form-data" class="space-y-5"
                      x-data="{ participants: {{ json_encode(old('participants', $booking->participants ?? [''])) }} }">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nomor Surat Tugas</label>
                        <input type="text" name="letter_number" value="{{ old('letter_number', $booking->letter_number) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Berkas Surat Tugas (PDF, Kosongkan jika tidak diubah)</label>
                        <input type="file" name="letter_file" accept="application/pdf" class="mt-1 block w-full text-xs">
                        <div class="mt-1 text-xs text-gray-500">Berkas saat ini: <a href="{{ asset('storage/' . $booking->letter_file) }}" target="_blank" class="text-indigo-600 underline">Lihat PDF</a></div>
                    </div>

                    {{-- Pilih Kendaraan --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Pilih Armada Mobil</label>
                        <select name="vehicle_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" required>
                            <option value="">-- Pilih Kendaraan --</option>
                            @foreach($vehicles as $vehicle)
                                @php
                                    // Cek status maintenance
                                    $isMaintenance = $vehicle->status === 'maintenance';
                                    
                                    // Ambil jadwal peminjaman terdekat dari booking LAIN yang approved / on_trip
                                    $activeBooking = $vehicle->bookings->first();
                                    
                                    // Label keterangan jadwal
                                    $scheduleNote = '';
                                    if ($isMaintenance) {
                                        $scheduleNote = '🔴 [MAINTENANCE / BENGKEL]';
                                    } elseif ($activeBooking) {
                                        $scheduleNote = '🟡 [DIPAKAI: ' . $activeBooking->start_date->format('d M H:i') . ' s.d. ' . $activeBooking->end_date->format('d M H:i') . ']';
                                    } else {
                                        $scheduleNote = '🟢 [TERSEDIA]';
                                    }
                                @endphp

                                <option value="{{ $vehicle->id }}" 
                                    {{ old('vehicle_id', $booking->vehicle_id) == $vehicle->id ? 'selected' : '' }}
                                    {{ $isMaintenance ? 'disabled' : '' }}>
                                    {{ $scheduleNote }} {{ $vehicle->brand }} {{ $vehicle->model }} ({{ $vehicle->plate_number }}) - Kapasitas: {{ $vehicle->capacity }} Orang
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                            * Unit berlabel 🟡 [DIPAKAI] tetap dapat dipilih selama rentang jam/tanggal pengajuan Anda tidak bertabrakan dengan jadwal tersebut.
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tanggal & Jam Mulai</label>
                            <input type="text" id="start_date" name="start_date" value="{{ old('start_date', $booking->start_date->format('Y-m-d H:i')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-white" required readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tanggal & Jam Selesai</label>
                            <input type="text" id="end_date" name="end_date" value="{{ old('end_date', $booking->end_date->format('Y-m-d H:i')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-white" required readonly>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tujuan Lokasi</label>
                        <input type="text" name="destination" value="{{ old('destination', $booking->destination) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Keperluan Dinas</label>
                        <textarea name="purpose" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>{{ old('purpose', $booking->purpose) }}</textarea>
                    </div>

                    {{-- ANGGOTA TIM DINAMIS (SELECT DARI TABEL PEGAWAI) --}}
                    <div class="border-t pt-4" 
                        x-data="{ 
                            participants: {{ json_encode(old('participants', $booking->participants ?? [''])) }},
                            allEmployees: {{ json_encode($employees) }},
                            getAvailableEmployees(currentIndex) {
                                return this.allEmployees.filter(emp => {
                                    const empValue = `${emp.name} (NIP: ${emp.nip ?? '-'})`;
                                    return !this.participants.includes(empValue) || this.participants[currentIndex] === empValue;
                                });
                            }
                        }">
                        
                        <label class="block text-sm font-medium text-gray-700 mb-2">Anggota Tim yang Ikut</label>
                        
                        <template x-for="(participant, index) in participants" :key="index">
                            <div class="flex space-x-2 mb-2">
                                <select :name="'participants[' + index + ']'" 
                                        x-model="participants[index]" 
                                        class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- Pilih Pegawai / Anggota --</option>
                                    
                                    <template x-for="employee in getAvailableEmployees(index)" :key="employee.id">
                                        <option :value="`${employee.name} (NIP: ${employee.nip ?? '-'})`"
                                                x-text="`${employee.name} — ${employee.department ?? 'Pegawai'} (NIP: ${employee.nip ?? '-'})`"
                                                :selected="participants[index] === `${employee.name} (NIP: ${employee.nip ?? '-'})`">
                                        </option>
                                    </template>
                                </select>

                                <button type="button" 
                                        @click="participants.splice(index, 1)" 
                                        x-show="participants.length > 1" 
                                        class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white rounded-md text-xs transition">
                                    Hapus
                                </button>
                            </div>
                        </template>

                        <button type="button" 
                                @click="participants.push('')" 
                                class="mt-1 text-xs bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium px-3 py-2 rounded-md transition">
                            + Tambah Anggota
                        </button>
                    </div>

                    <div class="flex justify-end space-x-2 pt-4 border-t">
                        <a href="{{ route('bookings.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm">Batal</a>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const config = {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                time_24hr: true,
                locale: "id"
            };
            flatpickr("#start_date", config);
            flatpickr("#end_date", config);
        });
    </script>
</x-app-layout>