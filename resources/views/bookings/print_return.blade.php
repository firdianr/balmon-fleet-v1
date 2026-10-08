<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Pengembalian - {{ $booking->letter_number }}</title>
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
        
        <div class="no-print flex justify-between mb-6">
            <button onclick="window.close()" class="px-3 py-1.5 bg-gray-500 text-white text-xs font-semibold rounded">← Kembali</button>
            <button onclick="window.print()" class="px-4 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded">🖨️ Cetak Nota Pengembalian</button>
        </div>

        {{-- HEADER KOP --}}
        <div class="text-center border-b-2 border-gray-800 pb-4 mb-6">
            <h2 class="text-xl font-bold uppercase tracking-wide">BALAI MONITOR SPEKTRUM FREKUENSI RADIO</h2>
            <p class="text-xs text-gray-600">BERITA ACARA PENGEMBALIAN & SERAH TERIMA AKHIR ARMADA</p>
            <p class="text-sm font-semibold mt-1">NO SURAT TUGAS: {{ $booking->letter_number }}</p>
        </div>

        {{-- INFORMASI UMUM --}}
        <div class="mb-4 text-sm">
            <table class="w-full text-left">
                <tr><td class="py-1 w-1/3 font-semibold">Peminjam Utama</td><td>: {{ $booking->user->name }}</td></tr>
                <tr><td class="py-1 font-semibold">Armada Mobil</td><td>: {{ $booking->vehicle->brand }} {{ $booking->vehicle->model }} ({{ $booking->vehicle->plate_number }})</td></tr>
                <tr><td class="py-1 font-semibold">Tujuan Perjalanan</td><td>: {{ $booking->destination }}</td></tr>
            </table>
        </div>

        {{-- LOG OPERASIONAL LENGKAP --}}
        <div class="mb-6 text-sm">
            <h3 class="font-bold text-gray-800 border-b pb-1 mb-2">RINGKASAN OPERASIONAL (CHECK-OUT & CHECK-IN)</h3>
            <table class="w-full border-collapse border border-gray-300 text-center text-xs mb-3">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border border-gray-300 p-2">Indikator</th>
                        <th class="border border-gray-300 p-2">Keberangkatan (Check-Out)</th>
                        <th class="border border-gray-300 p-2">Pengembalian (Check-In)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="border border-gray-300 p-2 font-semibold text-left">Odometer / KM</td>
                        <td class="border border-gray-300 p-2">{{ number_format($booking->log->start_km ?? 0) }} KM</td>
                        <td class="border border-gray-300 p-2">{{ number_format($booking->log->end_km ?? 0) }} KM</td>
                    </tr>
                    <tr>
                        <td class="border border-gray-300 p-2 font-semibold text-left">Level Sisa BBM</td>
                        <td class="border border-gray-300 p-2">{{ $booking->log->start_fuel_level ?? '-' }}</td>
                        <td class="border border-gray-300 p-2">{{ $booking->log->end_fuel_level ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="border border-gray-300 p-2 font-semibold text-left">Waktu Serah Terima</td>
                        <td class="border border-gray-300 p-2">{{ $booking->log->checked_out_at ? $booking->log->checked_out_at->format('d/m/Y H:i') : '-' }}</td>
                        <td class="border border-gray-300 p-2">{{ $booking->log->checked_in_at ? $booking->log->checked_in_at->format('d/m/Y H:i') : '-' }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="p-3 bg-gray-50 border rounded text-xs space-y-1">
                <div><strong>Total Jarak Tempuh:</strong> {{ number_format($booking->log->distance_traveled ?? 0) }} KM</div>
                <div><strong>Catatan Kondisi Mobil:</strong> {{ $booking->log->condition_notes ?? 'Tidak ada catatan kerusakan/masalah.' }}</div>
            </div>
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

            {{-- Tanda Tangan Penerima / Admin --}}
            <div class="flex flex-col items-center">
                <p class="mb-2 font-medium text-gray-700">Petugas Operasional / Admin</p>
                <div class="my-1 p-1 bg-white border border-gray-300 rounded shadow-sm inline-block">
                    {!! $qrAdmin !!}
                </div>
                <p class="mt-2 font-bold underline">
                    {{ $booking->returnApprover->name ?? ($booking->returnedBy->name ?? '-') }}
                </p>
                <p class="text-gray-500">
                    NIP: {{ $booking->returnApprover->nip ?? ($booking->returnedBy->nip ?? '-') }}
                </p>
            </div>
        </div>

    </div>
</body>
</html>