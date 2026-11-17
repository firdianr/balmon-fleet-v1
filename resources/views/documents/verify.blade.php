<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Dokumen Elektronik - Balmon Fleet</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 min-h-screen py-10 px-4 text-slate-800">
    <div class="max-w-2xl mx-auto space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-5 {{ $isAuthentic ? 'bg-emerald-600' : 'bg-rose-600' }} text-white">
                <span class="text-[11px] font-bold uppercase tracking-wider opacity-90">Sistem Verifikasi Keaslian Dokumen</span>
                <h1 class="text-lg font-extrabold mt-0.5">{{ $statusMessage }}</h1>
            </div>

            <div class="p-6 space-y-5 text-xs">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Jenis Dokumen</span>
                        <p class="font-bold text-slate-900 mt-0.5">{{ $document->document_label }}</p>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Nomor Surat Rujukan</span>
                        <p class="font-bold text-indigo-700 font-mono mt-0.5">{{ $document->booking->letter_number ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Waktu Finalisasi Arsip</span>
                        <p class="font-bold text-slate-900 mt-0.5">{{ $document->finalized_at->timezone('Asia/Jakarta')->format('d M Y, H:i:s') }} WIB</p>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Standar Enkripsi Penyimpanan</span>
                        <p class="font-bold text-slate-900 font-mono mt-0.5">{{ $document->encryption_cipher }} + HMAC</p>
                    </div>
                </div>

                <div>
                    <span class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Checksum SHA-256 Terdaftar (Arsip Sistem)</span>
                    <div class="p-3 bg-slate-900 text-emerald-400 font-mono text-[11px] rounded-xl break-all">
                        {{ $document->sha256_hash }}
                    </div>
                </div>

                <div>
                    <h2 class="font-bold text-slate-800 mb-2">Daftar Penanda Tangan Sah</h2>
                    <div class="space-y-2">
                        @foreach($document->signers_snapshot ?? [] as $signer)
                            <div class="p-3 rounded-xl border border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                <div>
                                    <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        {{ str_replace('_', ' ', $signer['role']) }}
                                    </span>
                                    <p class="font-bold text-slate-900 mt-1">{{ $signer['name'] }} <span class="font-normal text-slate-500">(NIP. {{ $signer['nip'] }})</span></p>
                                </div>
                                <div class="text-[11px] text-slate-500 sm:text-right font-mono">
                                    <div>{{ $signer['signed_wib'] }}</div>
                                    <div>IP: {{ $signer['ip_address'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Form Uji Cocok File PDF Mandiri --}}
                <div class="pt-4 border-t border-slate-200">
                    <h3 class="font-bold text-slate-800 mb-1">Uji Keaslian Berkas PDF Anda</h3>
                    <p class="text-slate-500 mb-3">Unggah berkas PDF yang Anda miliki untuk mencocokkan nilai hash SHA-256-nya secara matematis dengan arsip asli di server.</p>

                    <form action="{{ route('documents.verify.check_file', $document->uuid) }}" method="POST" enctype="multipart/form-data" class="flex flex-col sm:flex-row gap-2">
                        @csrf
                        <input type="file" name="pdf_file" accept="application/pdf" required class="block w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-300 rounded-xl">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shrink-0 cursor-pointer">
                            Cocokkan Hash
                        </button>
                    </form>

                    @if(session('file_check_result'))
                        @php $res = session('file_check_result'); @endphp
                        <div class="mt-3 p-3.5 rounded-xl border {{ $res['is_match'] ? 'bg-emerald-50 border-emerald-300 text-emerald-900' : 'bg-rose-50 border-rose-300 text-rose-900' }}">
                            <p class="font-bold">{{ $res['is_match'] ? 'COCOK 100%: File PDF ini asli dan tidak pernah dimodifikasi.' : 'TIDAK COCOK: File PDF ini berbeda atau telah mengalami perubahan!' }}</p>
                            <p class="font-mono text-[10px] mt-1 break-all">Hash File Unggahan: {{ $res['uploaded_hash'] }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>
</html>