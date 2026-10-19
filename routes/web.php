<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VehicleController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Middleware\RoleAdminApp;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::redirect('/register', '/login');
Route::redirect('/forgot-password', '/login');
Route::redirect('/reset-password', '/login');

Route::middleware(['auth', 'verified'])->group(function () {
    
    // ------------------------------------------------------------------
    // Rute Manajemen Profil User (Sesuai Komponen Navigation Breeze)
    // ------------------------------------------------------------------
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ------------------------------------------------------------------
    // Rute Dashboard | Kendaraan | Kalender
    // ------------------------------------------------------------------
    Route::get('/dashboard', [BookingController::class, 'index'])->name('dashboard');
    Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');

    // ------------------------------------------------------------------
    // Rute Booking Armada
    // ------------------------------------------------------------------
    // LIST BOOKING
    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
    // CREATE BOOKING
    Route::get('/bookings/create', [BookingController::class, 'create'])->name('bookings.create');
    Route::post('/bookings/parse-document', [BookingController::class, 'parseDocument'])->name('bookings.parse_document');
    Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
    // DETAIL BOOKING
    Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
    // TINJAU BOOKING
    Route::patch('/bookings/{booking}/process-review', [BookingController::class, 'processReview'])->name('bookings.process_review');
    // UPDATE BOOKING
    Route::get('/bookings/{booking}/edit', [BookingController::class, 'edit'])->name('bookings.edit');
    Route::put('/bookings/{booking}', [BookingController::class, 'update'])->name('bookings.update');
    // TRIP
    Route::patch('/bookings/{booking:letter_slug}/start-trip', [BookingController::class, 'startTrip'])->name('bookings.start_trip');
    Route::patch('/bookings/{booking:letter_slug}/complete-trip', [BookingController::class, 'completeTrip'])->name('bookings.complete_trip');
    // CANCEL
    Route::patch('/bookings/{booking:letter_slug}/cancel', [BookingController::class, 'cancelBooking'])->name('bookings.cancel');
    Route::patch('/bookings/{booking:letter_slug}/cancel-approval', [BookingController::class, 'cancelApproval'])->name('bookings.cancel_approval');

    // Route::patch('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
    // Route::patch('/bookings/{booking}/approve', [BookingController::class, 'approve'])->name('bookings.approve');
    // Route::post('/bookings/{booking:letter_slug}/check-out', [BookingController::class, 'checkOut'])->name('bookings.checkOut');
    // Route::post('/bookings/{booking:letter_slug}/check-in', [BookingController::class, 'checkIn'])->name('bookings.checkIn');
    // Route::patch('/bookings/{booking:letter_slug}/confirm-departure', [BookingController::class, 'confirmDeparture'])->name('bookings.confirm-departure');
    // Route::patch('/bookings/{booking:letter_slug}/confirm-return', [BookingController::class, 'confirmReturn'])->name('bookings.confirm-return');
    // Route::patch('/bookings/{booking:letter_slug}/update-check-out', [BookingController::class, 'updateCheckOut'])->name('bookings.update-check-out');
    // Route::patch('/bookings/{booking:letter_slug}/update-check-in', [BookingController::class, 'updateCheckIn'])->name('bookings.update-check-in');
    // Route::get('/bookings/{booking:letter_slug}/print-departure', [BookingController::class, 'printDeparture'])->name('bookings.print-departure');
    // Route::get('/bookings/{booking:letter_slug}/print-return', [BookingController::class, 'printReturn'])->name('bookings.print-return');

    // ------------------------------------------------------------------
    // Master Data (Admin App Only)
    // ------------------------------------------------------------------
    Route::middleware([RoleAdminApp::class])->prefix('admin-app')->name('admin_app.')->group(function () {
        // Pengelolaan Armada Kendaraan
        Route::get('/vehicles', [VehicleController::class, 'adminIndex'])->name('vehicles.index');
        Route::get('/vehicles/create', [VehicleController::class, 'create'])->name('vehicles.create');
        Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
        Route::get('/vehicles/{vehicle:slug}/edit', [VehicleController::class, 'edit'])->name('vehicles.edit');
        Route::put('/vehicles/{vehicle:slug}', [VehicleController::class, 'update'])->name('vehicles.update');
        Route::delete('/vehicles/{vehicle:slug}', [VehicleController::class, 'destroy'])->name('vehicles.destroy');
        // Pengelolaan Akun Pengguna
        Route::resource('users', UserController::class);
    });

    // Rute Pencarian
    Route::get('/api/search/bookings', [SearchController::class, 'bookings'])->name('api.search.bookings');
    Route::get('/api/search/vehicles', [SearchController::class, 'vehicles'])->name('api.search.vehicles');
    Route::get('/api/search/users', [SearchController::class, 'users'])->name('api.search.users');

    Route::prefix('bookings/{booking:letter_slug}/pdf')->name('bookings.pdf.')->group(function () {
        Route::get('/formulir-permohonan', [PdfController::class, 'printFormulirPermohonan'])->name('permohonan');
        Route::get('/bast-peminjaman', [PdfController::class, 'printBastPeminjaman'])->name('bast_peminjaman');
        Route::get('/formulir-pengembalian', [PdfController::class, 'printFormulirPengembalian'])->name('formulir_pengembalian');
        Route::get('/bast-pengembalian', [PdfController::class, 'printBastPengembalian'])->name('bast_pengembalian');
    });
});

require __DIR__.'/auth.php';