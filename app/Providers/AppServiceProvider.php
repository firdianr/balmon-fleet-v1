<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 1. Gate untuk Admin Aplikasi
        Gate::define('admin_app', function (User $user) {
            return $user->role === 'admin_app';
        });

        // 2. Gate untuk Admin Lapangan
        Gate::define('admin_lap', function (User $user) {
            return $user->role === 'admin_lap';
        });

        // 3. Gate Gabungan Seluruh Admin (admin_app & admin_lap)
        Gate::define('admin', function (User $user) {
            return in_array($user->role, ['admin_app', 'admin_lap']);
        });

        // 4. Gate Otorisasi Pengelolaan Kendaraan (Termasuk Sakelar Toggle Driver)
        Gate::define('manage-vehicles', function (User $user) {
            // admin_app dan admin_lap selalu memiliki hak akses
            if (in_array($user->role, ['admin_app', 'admin_lap'])) {
                return true;
            }

            // driver hanya memiliki akses jika fitur sakelar bernilai true
            if ($user->role === 'driver') {
                return config('permissions.driver_can_manage_vehicles', false);
            }

            return false;
        });
    }
}
