<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'letter_number',
        'letter_slug',
        'letter_file',
        'bast_peminjaman',
        'bast_pengembalian',
        'user_id',
        'vehicle_id',
        'vehicle_type',
        'vehicle_requirements',
        'driver_id',
        'start_date',
        'end_date',
        'destination',
        'purpose',
        'participants',
        'status',
        'admin_note',
        // Audit trail / Tahapan Workflow
        'allocated_by',
        'allocated_at',
        'approved_by',
        'approved_at',
        'returned_checked_by',
        'returned_checked_at',
        'returned_approved_by',
        'returned_approved_at',
    ];

    protected static function booted(): void
    {
        static::creating(function ($booking) {
            if (empty($booking->letter_slug)) {
                $booking->letter_slug = Str::slug(str_replace('/', '-', $booking->letter_number));
            }
        });

        static::updating(function ($booking) {
            if ($booking->isDirty('letter_number') && !$booking->isDirty('letter_slug')) {
                $booking->letter_slug = Str::slug(str_replace('/', '-', $booking->letter_number));
            }
        });
    }

    protected function casts(): array
    {
        return [
            'start_date'           => 'datetime',
            'end_date'             => 'datetime',
            'participants'         => 'array',
            'allocated_at'         => 'datetime',
            'approved_at'          => 'datetime',
            'returned_checked_at'  => 'datetime',
            'returned_approved_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'letter_slug';
    }

    // --- RELASI UTAMA ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function log(): HasOne
    {
        return $this->hasOne(VehicleLog::class, 'booking_id');
    }

    // --- RELASI AUDIT TRAIL / WAKTU PERSETUJUAN ---

    /**
     * Admin Sarpras / Pengelola yang melakukan alokasi armada & driver
     */
    public function allocator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }

    /**
     * Atasan / Pejabat yang menyetujui permohonan peminjaman
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Admin yang melakukan pengecekan fisik kendaraan saat dikembalikan
     */
    public function returnChecker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_checked_by');
    }

    /**
     * Atasan / Pejabat yang menyetujui akhir BAST pengembalian
     */
    public function returnApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_approved_by');
    }

    public function checklists()
    {
        return $this->hasMany(TripChecklist::class);
    }

    public function startChecklist()
    {
        return $this->hasOne(TripChecklist::class)->where('type', 'start');
    }

    public function endChecklist()
    {
        return $this->hasOne(TripChecklist::class)->where('type', 'end');
    }
}