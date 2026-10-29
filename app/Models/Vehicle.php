<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'plate_number',
        'slug',
        'brand',
        'model',
        'code',
        'type',
        'capacity',
        'fuel_type',
        'status',
        'photo',
        'notes',
    ];

    protected static function booted(): void
    {
        static::creating(function ($vehicle) {
            $vehicle->slug = Str::slug($vehicle->brand . '-' . $vehicle->model . '-' . $vehicle->plate_number);
        });

        static::updating(function ($vehicle) {
            if ($vehicle->isDirty(['brand', 'model', 'plate_number'])) {
                $vehicle->slug = Str::slug($vehicle->brand . '-' . $vehicle->model . '-' . $vehicle->plate_number);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function checklists()
    {
        return $this->hasMany(TripChecklist::class, 'vehicle_id');
    }

    public function lastChecklist()
    {
        return $this->hasOne(TripChecklist::class)->where('type', 'end')->latestOfMany();
    }
}
