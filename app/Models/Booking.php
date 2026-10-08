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
        'user_id',
        'vehicle_id',
        'start_date',
        'end_date',
        'destination',
        'purpose',
        'letter_file',
        'participants',
        'status',
        'admin_note',
        'approved_by',
        'approved_at',
        'departed_confirmed_by',
        'departed_at',
        'returned_confirmed_by',
        'returned_at',
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
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'participants' => 'array',
            'approved_at' => 'datetime',
            'departed_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'letter_slug';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function departureConfirmer()
    {
        return $this->belongsTo(User::class, 'departed_confirmed_by');
    }

    public function returnApprover()
    {
        return $this->belongsTo(User::class, 'returned_confirmed_by');
    }

    public function log(): HasOne
    {
        return $this->hasOne(VehicleLog::class, 'booking_id');
    }
}
