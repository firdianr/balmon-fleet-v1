<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug', 'email', 'nip', 'password', 'role', 'phone', 'department'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Helper untuk mengecek role admin
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // Relasi ke tabel Bookings (Sebagai peminjam)
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'user_id');
    }

    // Relasi ke tabel Bookings (Sebagai admin approver)
    public function approvedBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'approved_by');
    }

    private static function generateUniqueSlug(string $name, $ignoreId = null): string
    {
        // Hapus tanda titik, koma, dan karakter khusus dari nama/gelar
        $cleanName = str_replace(['.', ','], '', $name);
        $baseSlug = Str::slug($cleanName);
        
        $slug = $baseSlug;
        $count = 1;

        // Pastikan slug unik jika ada nama pegawai yang persis sama
        while (static::where('slug', $slug)->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$baseSlug}-{$count}";
            $count++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::creating(function ($user) {
            $user->slug = static::generateUniqueSlug($user->name);
        });

        static::updating(function ($user) {
            if ($user->isDirty('name')) {
                $user->slug = static::generateUniqueSlug($user->name, $user->id);
            }
        });
    }
}
