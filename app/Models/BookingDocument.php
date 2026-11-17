<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'booking_id',
        'document_type',
        'file_path',
        'sha256_hash',
        'encryption_cipher',
        'file_size',
        'signers_snapshot',
        'finalized_at',
    ];

    protected $casts = [
        'signers_snapshot' => 'array',
        'finalized_at'     => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function getDocumentLabelAttribute(): string
    {
        return match ($this->document_type) {
            'formulir_peminjaman'   => 'Formulir Permohonan Peminjaman Kendaraan Dinas',
            'bast_peminjaman'       => 'BAST Peminjaman Kendaraan Dinas',
            'formulir_pengembalian' => 'Formulir Pengembalian Kendaraan Dinas',
            'bast_pengembalian'     => 'BAST Pengembalian Kendaraan Dinas',
            default                 => ucwords(str_replace('_', ' ', $this->document_type)),
        };
    }
}