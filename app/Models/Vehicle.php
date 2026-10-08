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
        'capacity',
        'fuel_type',
        'status',
        'photo',
        'notes',
    ];

    protected static function booted(): void
    {
        static::creating(function ($vehicle) {
            $vehicle->slug = Str::slug($vehicle->plate_number);
        });

        static::updating(function ($vehicle) {
            $vehicle->slug = Str::slug($vehicle->plate_number);
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
}
