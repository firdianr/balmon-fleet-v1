<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'vehicle_id',
        'checked_out_at',
        'start_km',
        'start_fuel_level',
        'start_photo',
        'checked_in_at',
        'end_km',
        'end_fuel_level',
        'end_photo',
        'distance_traveled',
        'condition_notes',
        'condition_photos',
        'is_verified_by_admin',
        'admin_verification_notes',
    ];

    protected $casts = [
        'condition_photos' => 'array',
    ];

    protected function casts(): array
    {
        return [
            'checked_out_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'start_km' => 'integer',
            'end_km' => 'integer',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    // Accessor kalkulasi jarak tempuh otomatis
    public function getDistanceTraveledAttribute(): ?int
    {
        if ($this->start_km !== null && $this->end_km !== null) {
            return $this->end_km - $this->start_km;
        }
        return null;
    }

    public function checklists()
    {
        return $this->hasMany(TripChecklist::class, 'vehicle_log_id');
    }

    public function startChecklist()
    {
        return $this->hasOne(TripChecklist::class, 'vehicle_log_id')->where('type', 'start');
    }

    public function endChecklist()
    {
        return $this->hasOne(TripChecklist::class, 'vehicle_log_id')->where('type', 'end');
    }
}
