<x-app-layout>
    <x-slot name="title">Detail Peminjaman {{ $booking->letter_number }}</x-slot>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Detail Peminjaman: {{ $booking->letter_number }}
            </h2>
            <a href="{{ route('bookings.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm font-medium">Kembali</a>
        </div>
    </x-slot>

    <div class="pt-2 pb-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if($booking->status === 'rejected' && $booking->admin_note)
                <div class="mt-4 p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-md">
                    <h4 class="text-sm font-semibold text-rose-800">Alasan Penolakan:</h4>
                    <p class="mt-1 text-sm text-rose-700 whitespace-pre-line">{{ $booking->admin_note }}</p>
                </div>
            @endif
            
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h3 class="text-lg font-bold border-b pb-2 mb-4">Informasi Surat & Peminjam</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div><strong>Nomor Surat Tugas:</strong> {{ $booking->letter_number }}</div>
                    <div><strong>Pemohon:</strong> {{ $booking->user->name }} (NIP: {{ $booking->user->nip ?? '-' }})</div>
                    <div><strong>Seksi / Departemen:</strong> {{ $booking->user->department ?? '-' }}</div>
                    <div><strong>Status Pengajuan:</strong> 
                        <span class="font-bold uppercase">{{ $booking->status }}</span>
                        <span class="text-xs text-gray-500 font-normal ml-1">
                            ({{ $booking->updated_at->format('d M Y H:i') }} WIB)
                        </span>
                    </div>
                    <div><strong>Tujuan:</strong> {{ $booking->destination }}</div>
                    <div><strong>Jadwal:</strong> {{ $booking->start_date->format('d M Y H:i') }} s.d. {{ $booking->end_date->format('d M Y H:i') }}</div>
                    <div class="col-span-2"><strong>Keperluan:</strong> {{ $booking->purpose }}</div>
                    <div class="col-span-2">
                        <strong>Surat Perintah (PDF):</strong>
                        <a href="{{ asset('storage/' . $booking->letter_file) }}" target="_blank" class="text-indigo-600 underline font-semibold ml-2">📄 Lihat Berkas PDF</a>
                    </div>
                    {{-- TOMBOL PRINT (AKSI BERDASARKAN STATUS) --}}
                    <div class="flex flex-wrap gap-2">
                        {{-- 1. Cetak Nota Keberangkatan (Muncul saat ON_TRIP atau COMPLETED) --}}
                        @if(in_array($booking->status, ['on_trip', 'completed']))
                            <a href="{{ route('bookings.print-departure', $booking->letter_slug) }}" 
                            target="_blank" 
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded shadow-sm transition">
                                🖨️ Cetak Nota Keberangkatan
                            </a>
                        @endif

                        {{-- 2. Cetak Nota Pengembalian (Hanya Muncul saat COMPLETED) --}}
                        @if($booking->status === 'completed')
                            <a href="{{ route('bookings.print-return', $booking->letter_slug) }}" 
                            target="_blank" 
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded shadow-sm transition">
                                🖨️ Cetak Nota Pengembalian
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- DAFTAR ANGGOTA --}}
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h3 class="text-lg font-bold border-b pb-2 mb-4">Anggota Tim yang Ikut</h3>
                @if(!empty($booking->participants) && count($booking->participants) > 0)
                    <ul class="list-disc pl-5 space-y-1 text-sm">
                        @foreach($booking->participants as $person)
                            <li>{{ $person }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-gray-500">Tidak ada anggota tambahan yang didaftarkan.</p>
                @endif
            </div>

            {{-- INFORMASI MOBIL & LOG ODOMETER --}}
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h3 class="text-lg font-bold border-b pb-2 mb-4">Armada Kendaraan & Log Operasional</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-6">
                    <div><strong>Mobil:</strong> {{ $booking->vehicle->brand }} {{ $booking->vehicle->model }}</div>
                    <div><strong>Plat Nomor:</strong> {{ $booking->vehicle->plate_number }}</div>
                    
                    @if($booking->log)
                        <div><strong>KM Awal (Check-Out):</strong> {{ number_format($booking->log->start_km) }} KM</div>
                        <div><strong>BBM Awal:</strong> {{ $booking->log->start_fuel_level }}</div>
                        <div><strong>KM Akhir (Check-In):</strong> {{ $booking->log->end_km ? number_format($booking->log->end_km) . ' KM' : '-' }}</div>
                        <div><strong>BBM Akhir:</strong> {{ $booking->log->end_fuel_level ?? '-' }}</div>
                        <div class="md:col-span-2">
                            <strong>Jarak Tempuh:</strong> 
                            {{ $booking->log->distance_traveled ? number_format($booking->log->distance_traveled) . ' KM' : '-' }}
                        </div>
                    @endif
                </div>

                @if($booking->log)
                    <hr class="my-4 border-gray-200">

                    {{-- SECTION FOTO ODOMETER (CHECK-OUT & CHECK-IN) --}}
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Dokumentasi Odometer & BBM</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                        {{-- Foto Check-Out --}}
                        <div class="border rounded-lg p-3 bg-gray-50">
                            <span class="block text-xs font-medium text-gray-500 mb-2">📸 Odometer Saat Check-Out</span>
                            @if($booking->log->start_photo)
                                <a href="{{ asset('storage/' . $booking->log->start_photo) }}" target="_blank" class="block group overflow-hidden rounded">
                                    <img src="{{ asset('storage/' . $booking->log->start_photo) }}" 
                                        alt="Odometer Check-Out" 
                                        class="w-full h-40 object-cover rounded group-hover:scale-105 transition duration-200">
                                </a>
                            @else
                                <div class="w-full h-40 bg-gray-200 rounded flex items-center justify-center text-xs text-gray-500">Tidak ada foto</div>
                            @endif
                        </div>

                        {{-- Foto Check-In --}}
                        <div class="border rounded-lg p-3 bg-gray-50">
                            <span class="block text-xs font-medium text-gray-500 mb-2">📸 Odometer Saat Check-In</span>
                            @if($booking->log->end_photo)
                                <a href="{{ asset('storage/' . $booking->log->end_photo) }}" target="_blank" class="block group overflow-hidden rounded">
                                    <img src="{{ asset('storage/' . $booking->log->end_photo) }}" 
                                        alt="Odometer Check-In" 
                                        class="w-full h-40 object-cover rounded group-hover:scale-105 transition duration-200">
                                </a>
                            @else
                                <div class="w-full h-40 bg-gray-200 rounded flex items-center justify-center text-xs text-gray-500">Belum / Tidak ada foto</div>
                            @endif
                        </div>
                    </div>

                    {{-- SECTION CATATAN & FOTO KONDISI MOBIL --}}
                    @if($booking->log->condition_notes || !empty($booking->log->condition_photos))
                        <hr class="my-4 border-gray-200">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Laporan Kondisi & Catatan Fisik Mobil</h4>

                        {{-- Catatan Kondisi --}}
                        @if($booking->log->condition_notes)
                            <div class="mb-4 bg-amber-50 border-l-4 border-amber-400 p-3 rounded text-sm text-amber-900">
                                <strong>Catatan:</strong>
                                <p class="mt-1 whitespace-pre-line">{{ $booking->log->condition_notes }}</p>
                            </div>
                        @endif

                        {{-- Gallery Foto Kerusakan/Kondisi --}}
                        @if(!empty($booking->log->condition_photos))
                            <div>
                                <span class="block text-xs font-medium text-gray-500 mb-2">📸 Foto Bukti Kondisi / Kerusakan Tambahan:</span>
                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                                    @foreach($booking->log->condition_photos as $photo)
                                        <a href="{{ asset('storage/' . $photo) }}" target="_blank" class="block group overflow-hidden rounded border border-gray-200">
                                            <img src="{{ asset('storage/' . $photo) }}" 
                                                alt="Foto Kondisi Mobil" 
                                                class="w-full h-24 object-cover group-hover:scale-105 transition duration-200">
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-app-layout>