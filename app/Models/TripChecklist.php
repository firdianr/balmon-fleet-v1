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
        'body_condition',
        'tires_condition',
        'lights_condition',
        'glass_mirrors',
        'brakes_condition',
        'interior_cleanliness',
        'has_stnk',
        'has_spare_tire',
        'has_jack_tools',
        'has_first_aid',
        'is_washed',
        'wash_receipt_photo',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'body_condition'       => 'boolean',
        'tires_condition'      => 'boolean',
        'lights_condition'     => 'boolean',
        'glass_mirrors'        => 'boolean',
        'brakes_condition'     => 'boolean',
        'interior_cleanliness' => 'boolean',
        'has_stnk'             => 'boolean',
        'has_spare_tire'       => 'boolean',
        'has_jack_tools'       => 'boolean',
        'has_first_aid'        => 'boolean',
        'is_washed'            => 'boolean',
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