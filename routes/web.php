<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VehicleController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DraftBookingController;
use App\Http\Controllers\DriverCalendarController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public & Redirect Routes
|--------------------------------------------------------------------------
*/
Route::get('/', fn () => redirect()->route('login'));
Route::redirect('/register', '/login');
Route::redirect('/forgot-password', '/login');
Route::redirect('/reset-password', '/login');

/*
|--------------------------------------------------------------------------
| Authenticated Routes (Semua Role: Pegawai, Driver, Admin App, Admin Lap, Approver)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    // ------------------------------------------------------------------
    // Profil, Dashboard, & Kalender
    // ------------------------------------------------------------------
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/drivers/calendar', [DriverCalendarController::class, 'index'])->name('drivers.calendar');
    Route::post('/drivers/attendance', [DriverCalendarController::class, 'storeAttendance'])->name('drivers.attendance.store');
    Route::put('/drivers/attendance/{id}', [DriverCalendarController::class, 'updateAttendance'])->name('drivers.attendance.update');
    Route::delete('/drivers/attendance/{id}', [DriverCalendarController::class, 'destroyAttendance'])->name('drivers.attendance.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ------------------------------------------------------------------
    // Daftar Kendaraan (Read-Only untuk Semua User Termasuk Driver & Pegawai)
    // ------------------------------------------------------------------
    Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');

    // ------------------------------------------------------------------
    // Layer 1 - Draft Booking (Permohonan Armada)
    // ------------------------------------------------------------------
    Route::prefix('draft-bookings')->name('draft-bookings.')->group(function () {
        Route::get('/', [DraftBookingController::class, 'index'])->name('index');
        Route::get('/create', [DraftBookingController::class, 'create'])->name('create');
        Route::post('/', [DraftBookingController::class, 'store'])->name('store');
        Route::post('/{draftBooking}/convert', [DraftBookingController::class, 'convertToBooking'])->name('convert');
    });

    // ------------------------------------------------------------------
    // Layer 2 - Formal Booking (Peminjaman Dinas)
    // ------------------------------------------------------------------
    Route::prefix('bookings')->name('bookings.')->group(function () {
        Route::get('/', [BookingController::class, 'index'])->name('index');
        Route::get('/create', [BookingController::class, 'create'])->name('create');
        Route::post('/parse-document', [BookingController::class, 'parseDocument'])->name('parse_document');
        Route::post('/', [BookingController::class, 'store'])->name('store');

        Route::get('/{booking:letter_slug}', [BookingController::class, 'show'])->name('show');
        Route::get('/{booking:letter_slug}/edit', [BookingController::class, 'edit'])->name('edit');
        Route::put('/{booking:letter_slug}', [BookingController::class, 'update'])->name('update');
        Route::patch('/{booking:letter_slug}/cancel', [BookingController::class, 'cancelBooking'])->name('cancel');

        // Cetak Dokumen PDF
        Route::prefix('{booking:letter_slug}/pdf')->name('pdf.')->group(function () {
            Route::get('/formulir-permohonan', [PdfController::class, 'printFormulirPermohonan'])->name('permohonan');
            Route::get('/bast-peminjaman', [PdfController::class, 'printBastPeminjaman'])->name('bast_peminjaman');
            Route::get('/formulir-pengembalian', [PdfController::class, 'printFormulirPengembalian'])->name('formulir_pengembalian');
            Route::get('/bast-pengembalian', [PdfController::class, 'printBastPengembalian'])->name('bast_pengembalian');
        });
    });

    // ------------------------------------------------------------------
    // API Pencarian Autocomplete
    // ------------------------------------------------------------------
    Route::prefix('api/search')->name('api.search.')->group(function () {
        Route::get('/bookings', [SearchController::class, 'bookings'])->name('bookings');
        Route::get('/vehicles', [SearchController::class, 'vehicles'])->name('vehicles');
        Route::get('/users', [SearchController::class, 'users'])->name('users');
    });

    /*
    |--------------------------------------------------------------------------
    | Fitur Spesifik: ADMIN APP ONLY
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:admin_app'])->prefix('admin-app')->name('admin_app.')->group(function () {
        // Kelola Akun Pengguna (CRUD User)
        Route::resource('users', UserController::class);
    });

    /*
    |--------------------------------------------------------------------------
    | Fitur Spesifik: ADMIN LAPANGAN & ADMIN APP
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:admin_app,admin_lap'])->group(function () {
        // Pengelolaan Armada Kendaraan (CRUD Admin)
        Route::prefix('admin-app/vehicles')->name('admin_app.vehicles.')->group(function () {
            Route::get('/', [VehicleController::class, 'adminIndex'])->name('index');
            Route::get('/create', [VehicleController::class, 'create'])->name('create');
            Route::post('/', [VehicleController::class, 'store'])->name('store');
            Route::get('/{vehicle:slug}/edit', [VehicleController::class, 'edit'])->name('edit');
            Route::put('/{vehicle:slug}', [VehicleController::class, 'update'])->name('update');
            Route::delete('/{vehicle:slug}', [VehicleController::class, 'destroy'])->name('destroy');
        });

        // Review Draft Permohonan & Verifikasi Pengembalian Armada
        Route::post('/draft-bookings/{draftBooking}/review', [DraftBookingController::class, 'review'])->name('draft-bookings.review');
        Route::patch('/bookings/{booking:letter_slug}/process-review', [BookingController::class, 'processReview'])->name('bookings.process_review');
        Route::patch('/bookings/{booking:letter_slug}/verify-return', [BookingController::class, 'verifyReturn'])->name('bookings.verify_return');
    });

    /*
    |--------------------------------------------------------------------------
    | Fitur Spesifik: APPROVER (Pejabat Penyetuju) & ADMIN APP
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:approver,admin_app'])->prefix('bookings/{booking:letter_slug}')->name('bookings.')->group(function () {
        Route::patch('/approver-review', [BookingController::class, 'processApproverReview'])->name('approver_review');
        Route::patch('/cancel-approval', [BookingController::class, 'cancelApproval'])->name('cancel_approval');
    });

    /*
    |--------------------------------------------------------------------------
    | Fitur Spesifik: DRIVER, ADMIN LAPANGAN, & ADMIN APP
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:driver,admin_lap,admin_app,pegawai'])->prefix('bookings/{booking:letter_slug}')->name('bookings.')->group(function () {
        Route::patch('/start-trip', [BookingController::class, 'startTrip'])->name('start_trip');
        Route::patch('/complete-trip', [BookingController::class, 'completeTrip'])->name('complete_trip');
    });

});

require __DIR__.'/auth.php';