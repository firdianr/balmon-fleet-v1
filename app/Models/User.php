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

#[Fillable(['name', 'slug', 'email', 'nip', 'password', 'role', 'phone', 'pangkat', 'jabatan', 'department'])]
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

    // ==========================================
    // HELPER METHODS UNTUK ROLE-BASED ACCESS CONTROL (RBAC)
    // ==========================================

    /**
     * Cek apakah user adalah Admin Aplikasi (Pengelola User & Kendaraan)
     */
    public function isAdminApp(): bool
    {
        return $this->role === 'admin_app';
    }

    /**
     * Cek apakah user adalah Admin Lapangan (Pengatur Perjalanan & Armada)
     */
    public function isAdminLap(): bool
    {
        return $this->role === 'admin_lap';
    }

    /**
     * Cek apakah user adalah Approver (Kepala Kantor)
     */
    public function isApprover(): bool
    {
        return $this->role === 'approver';
    }

    /**
     * Cek apakah user adalah Driver/Pengemudi Armada
     */
    public function isDriver(): bool
    {
        return $this->role === 'driver';
    }

    /**
     * Cek apakah user adalah Pegawai
     */
    public function isPegawai(): bool
    {
        return $this->role === 'pegawai';
    }

    /**
     * Helper umum jika ingin mengecek apakah user memiliki hak administratif (App / Lap)
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin_app', 'admin_lap']);
    }

    // ==========================================
    // RELASI DATABASE
    // ==========================================

    // Relasi ke tabel Bookings (Sebagai peminjam/pemohon)
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'user_id');
    }

    // Relasi ke tabel Bookings (Sebagai penyetuju/approver)
    public function approvedBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'approved_by');
    }

    // ==========================================
    // SLUG & ROUTE KEY LOGIC
    // ==========================================

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

    public function attendances()
    {
        return $this->hasMany(DriverAttendance::class, 'user_id');
    }
}