<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripChecklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'vehicle_id',
        'vehicle_log_id',
        'type',
        'items',
        'is_washed',
        'wash_receipt_photo',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'items'     => 'array',
        'is_washed' => 'boolean',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function log(): BelongsTo
    {
        return $this->belongsTo(VehicleLog::class, 'vehicle_log_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}