@php
    $meta = $auditMeta ?? [
        'uuid'             => null,
        'is_final'         => false,
        'finalized_at'     => null,
        'verification_url' => null,
        'signers'          => collect(),
    ];

    $qrBase64 = null;
    if (!empty($meta['verification_url'])) {
        try {
            $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(90)
                ->margin(1)
                ->generate($meta['verification_url']);
            $qrBase64 = 'data:image/svg+xml;base64,' . base64_encode($qrSvg);
        } catch (\Throwable $e) {
            $qrBase64 = null;
        }
    }
@endphp

<div style="margin-top: 22px; page-break-inside: avoid; border: 1px solid #94a3b8; background-color: #f8fafc; padding: 8px 10px; font-family: Arial, Helvetica, sans-serif; font-size: 7.5pt; color: #1e293b;">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="vertical-align: top; {{ $qrBase64 ? 'width: 82%; padding-right: 8px;' : 'width: 100%;' }}">
                <div style="font-weight: bold; font-size: 8pt; color: #0f172a; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; padding-bottom: 3px; margin-bottom: 5px;">
                    @if(!empty($meta['is_final']))
                        JEJAK AUDIT TANDA TANGAN ELEKTRONIK &amp; INTEGRITAS DOKUMEN
                    @else
                        PRATINJAU DRAF DOKUMEN (BELUM FINAL / MENUNGGU KELENGKAPAN TANDA TANGAN)
                    @endif
                </div>

                @if(!empty($meta['uuid']))
                    <div style="margin-bottom: 4px; line-height: 1.35;">
                        <strong>ID Dokumen (UUID):</strong> <span style="font-family: 'Courier New', monospace;">{{ $meta['uuid'] }}</span><br>
                        <strong>Waktu Pengesahan Arsip:</strong> {{ $meta['finalized_at']->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') }} WIB &bull;
                        <strong>Proteksi:</strong> AES-256-CBC &amp; SHA-256 Checksum<br>
                        <strong>URL Verifikasi:</strong> <span style="color: #1d4ed8;">{{ $meta['verification_url'] }}</span>
                    </div>
                @endif

                <table style="width: 100%; border-collapse: collapse; margin-top: 4px; font-size: 7pt;">
                    <thead>
                        <tr style="background-color: #e2e8f0; text-align: left;">
                            <th style="border: 1px solid #cbd5e1; padding: 3px 5px;">Peran</th>
                            <th style="border: 1px solid #cbd5e1; padding: 3px 5px;">Nama &amp; NIP</th>
                            <th style="border: 1px solid #cbd5e1; padding: 3px 5px;">Waktu TTD</th>
                            <th style="border: 1px solid #cbd5e1; padding: 3px 5px;">Alamat IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($meta['signers'] as $sig)
                            <tr>
                                <td style="border: 1px solid #cbd5e1; padding: 2.5px 5px; text-transform: uppercase; font-weight: bold;">
                                    {{ str_replace('_', ' ', $sig->signer_role) }}
                                </td>
                                <td style="border: 1px solid #cbd5e1; padding: 2.5px 5px;">
                                    {{ $sig->user->name ?? '-' }} (NIP. {{ $sig->user->nip ?? '-' }})
                                </td>
                                <td style="border: 1px solid #cbd5e1; padding: 2.5px 5px;">
                                    {{ $sig->signed_at ? $sig->signed_at->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') . ' WIB' : '-' }}
                                </td>
                                <td style="border: 1px solid #cbd5e1; padding: 2.5px 5px; font-family: 'Courier New', monospace;">
                                    {{ $sig->ip_address ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="border: 1px solid #cbd5e1; padding: 4px; text-align: center; color: #64748b;">
                                    Belum ada rekaman tanda tangan pada dokumen ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div style="margin-top: 4px; font-size: 6.5pt; color: #475569; line-height: 1.3;">
                    Dokumen ini ditandatangani secara elektronik melalui sistem Balmon-Fleet sesuai ketentuan penyelenggaraan sistem elektronik internal instansi. Keaslian berkas PDF dapat diverifikasi dengan mencocokkan nilai hash SHA-256 pada tautan verifikasi di atas.
                </div>
            </td>

            @if($qrBase64)
                <td style="width: 18%; text-align: center; vertical-align: middle; border-left: 1px solid #cbd5e1; padding-left: 6px;">
                    <img src="{{ $qrBase64 }}" style="width: 78px; height: 78px; display: block; margin: 0 auto;">
                    <div style="font-size: 6pt; color: #475569; margin-top: 3px;">Pindai untuk Verifikasi</div>
                </td>
            @endif
        </tr>
    </table>
</div>