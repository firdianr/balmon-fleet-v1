<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BookingSignature extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'user_id',
        'document_type',
        'signer_role',
        'signature_path',
        'signed_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mengambil file PNG dari private storage ('local') dalam format Base64 untuk DomPDF
     */
    public function getBase64ImageAttribute(): ?string
    {
        if (!$this->signature_path) {
            return null;
        }

        if (Storage::disk('local')->exists($this->signature_path)) {
            $binary = Storage::disk('local')->get($this->signature_path);
            return 'data:image/png;base64,' . base64_encode($binary);
        }

        // Fallback kompatibilitas jika masih ada file lama di disk public
        if (Storage::disk('public')->exists($this->signature_path)) {
            $binary = Storage::disk('public')->get($this->signature_path);
            return 'data:image/png;base64,' . base64_encode($binary);
        }

        return null;
    }
}