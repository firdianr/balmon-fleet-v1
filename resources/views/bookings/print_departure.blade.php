<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Keberangkatan - {{ $booking->letter_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 12pt; color: #000; background: #fff; }
            .page-border { border: none !important; shadow: none !important; }
        }
    </style>
</head>
<body class="bg-gray-100 p-6 flex justify-center" onload="window.print()">
    <div class="bg-white p-8 rounded shadow-md max-w-2xl w-full page-border">
        
        {{-- TOMBOL KONTROL HALAMAN --}}
        <div class="no-print flex justify-between mb-6">
            <button onclick="window.close()" class="px-3 py-1.5 bg-gray-500 text-white text-xs font-semibold rounded">← Kembali</button>
            <button onclick="window.print()" class="px-4 py-1.5 bg-blue-600 text-white text-xs font-semibold rounded">🖨️ Cetak / Simpan PDF</button>
        </div>

        {{-- HEADER KOP --}}
        <div class="text-center border-b-2 border-gray-800 pb-4 mb-6">
            <h2 class="text-xl font-bold uppercase tracking-wide">BALAI MONITOR SPEKTRUM FREKUENSI RADIO</h2>
            <p class="text-xs text-gray-600">BERITA ACARA SERAH TERIMA & BUKTI KEBERANGKATAN ARMADA</p>
            <p class="text-sm font-semibold mt-1">NO SURAT TUGAS: {{ $booking->letter_number }}</p>
        </div>

        {{-- INFORMASI PEMINJAMAN --}}
        <div class="mb-6 text-sm">
            <h3 class="font-bold text-gray-800 border-b pb-1 mb-2">I. DATA PEMINJAMAN & PERJALANAN</h3>
            <table class="w-full text-left border-collapse">
                <!-- 1. NAMA PEMINJAM -->
                <tr>
                    <td class="py-1 font-semibold align-top" style="width: 170px;">Nama Peminjam</td>
                    <td class="py-1 align-top" style="width: 15px;">:</td>
                    <td class="py-1 align-top">{{ $booking->user->name }}</td>
                </tr>

                <!-- 2. TUJUAN PERJALANAN -->
                <tr>
                    <td class="py-1 font-semibold align-top" style="width: 170px;">Tujuan Perjalanan</td>
                    <td class="py-1 align-top" style="width: 15px;">:</td>
                    <td class="py-1 align-top">{{ $booking->destination }}</td>
                </tr>

                <!-- 3. MAKSUD / KEPERLUAN -->
                <tr>
                    <td class="py-1 font-semibold align-top" style="width: 170px;">Maksud / Keperluan</td>
                    <td class="py-1 align-top" style="width: 15px;">:</td>
                    <td class="py-1 align-top">{{ $booking->purpose }}</td>
                </tr>

                <!-- 4. JADWAL PENGGUNAAN -->
                <tr>
                    <td class="py-1 font-semibold align-top" style="width: 170px;">Jadwal Penggunaan</td>
                    <td class="py-1 align-top" style="width: 15px;">:</td>
                    <td class="py-1 align-top">
                        {{ \Carbon\Carbon::parse($booking->start_date)->locale('id')->translatedFormat('d F Y H:i') }} 
                        s.d. 
                        {{ \Carbon\Carbon::parse($booking->end_date)->locale('id')->translatedFormat('d F Y H:i') }}
                    </td>
                </tr>

                <!-- 5. ANGGOTA TIM -->
                <tr>
                    <td class="py-1 font-semibold align-top" style="width: 170px;">Anggota Tim</td>
                    <td class="py-1 align-top" style="width: 15px;">:</td>
                    <td class="py-1 align-top">
                        @if(!empty($booking->participants) && count($booking->participants) > 0)
                            <ol class="m-0 pl-4 list-decimal">
                                @foreach($booking->participants as $participant)
                                    <li class="mb-0.5">
                                        @if(is_numeric($participant))
                                            @php $user = \App\Models\User::find($participant); @endphp
                                            {{ $user->name ?? $participant }} 
                                            <span class="text-gray-600">(NIP: {{ $user->nip ?? '-' }})</span>
                                        @else
                                            {{ $participant }}
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        @else
                            <span class="text-gray-500 italic">- (Tidak ada anggota tim tambahan)</span>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        {{-- INFORMASI KENDARAAN & KM AWAL --}}
        <div class="mb-6 text-sm">
            <h3 class="font-bold text-gray-800 border-b pb-1 mb-2">II. KONDISI AWAL KEBERANGKATAN (CHECK-OUT)</h3>
            <table class="w-full text-left">
                <tr><td class="py-1 w-1/3 font-semibold">Armada Mobil</td><td>: {{ $booking->vehicle->brand }} {{ $booking->vehicle->model }} ({{ $booking->vehicle->plate_number }})</td></tr>
                <tr><td class="py-1 font-semibold">Odometer Awal (KM)</td><td>: <strong>{{ number_format($booking->log->start_km ?? 0) }} KM</strong></td></tr>
                <tr><td class="py-1 font-semibold">Sisa BBM Awal</td><td>: <strong>{{ $booking->log->start_fuel_level ?? '-' }}</strong></td></tr>
                <tr><td class="py-1 font-semibold">Waktu Check-Out</td><td>: {{ $booking->log->checked_out_at ? $booking->log->checked_out_at->translatedFormat('d F Y H:i') : '-' }} WIB</td></tr>
            </table>
        </div>

        {{-- TANDA TANGAN DIGITAL QR CODE --}}
        <div class="mt-10 pt-4 border-t grid grid-cols-2 text-center text-xs">

            {{-- Tanda Tangan Peminjam --}}
            <div class="flex flex-col items-center">
                <p class="mb-2 font-medium text-gray-700">Peminjam</p>
                <div class="my-1 p-1 bg-white border border-gray-300 rounded shadow-sm inline-block">
                    {!! $qrUser !!}
                </div>
                <p class="mt-2 font-bold underline">{{ $booking->user->name }}</p>
                <p class="text-gray-500">NIP: {{ $booking->user->nip ?? '-' }}</p>
            </div>

            {{-- Tanda Tangan Admin / Offisial --}}
            <div class="flex flex-col items-center">
                <p class="mb-2 font-medium text-gray-700">Petugas Operasional / Admin</p>
                <div class="my-1 p-1 bg-white border border-gray-300 rounded shadow-sm inline-block">
                    {!! $qrAdmin !!}
                </div>
                <p class="mt-2 font-bold underline">{{ $booking->departureConfirmer->name ?? '-' }}</p>
                <p class="text-gray-500">NIP: {{ $booking->departureConfirmer->nip ?? '-' }}</p>
            </div>
            
        </div>

    </div>
</body>
</html>