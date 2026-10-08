<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VehicleController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
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
    // Rute Dashboard & Booking Armada
    // ------------------------------------------------------------------
    Route::get('/dashboard', [BookingController::class, 'index'])->name('dashboard');

    Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');

    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/create', [BookingController::class, 'create'])->name('bookings.create');
    Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
    Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
    Route::get('/bookings/{booking}/edit', [BookingController::class, 'edit'])->name('bookings.edit');
    Route::put('/bookings/{booking}', [BookingController::class, 'update'])->name('bookings.update');

    Route::patch('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::patch('/bookings/{booking}/approve', [BookingController::class, 'approve'])->name('bookings.approve');
    Route::post('/bookings/{booking:letter_slug}/check-out', [BookingController::class, 'checkOut'])->name('bookings.checkOut');
    Route::post('/bookings/{booking:letter_slug}/check-in', [BookingController::class, 'checkIn'])->name('bookings.checkIn');
    Route::patch('/bookings/{booking:letter_slug}/confirm-departure', [BookingController::class, 'confirmDeparture'])->name('bookings.confirm-departure');
    Route::patch('/bookings/{booking:letter_slug}/confirm-return', [BookingController::class, 'confirmReturn'])->name('bookings.confirm-return');

    Route::patch('/bookings/{booking:letter_slug}/update-check-out', [BookingController::class, 'updateCheckOut'])->name('bookings.update-check-out');
    Route::patch('/bookings/{booking:letter_slug}/update-check-in', [BookingController::class, 'updateCheckIn'])->name('bookings.update-check-in');

    Route::get('/bookings/{booking:letter_slug}/print-departure', [BookingController::class, 'printDeparture'])->name('bookings.print-departure');
    Route::get('/bookings/{booking:letter_slug}/print-return', [BookingController::class, 'printReturn'])->name('bookings.print-return');

    // Master Data Vehicles (Admin Only)
    Route::middleware(['can:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::resource('vehicles', VehicleController::class)->except(['show']);
        Route::resource('users', UserController::class)->except(['show']);
    });

    // Rute Kalender Interaktif
    Route::get('/calendar', [BookingController::class, 'calendar'])->name('bookings.calendar');
    Route::get('/api/calendar-events', [BookingController::class, 'getEvents'])->name('api.calendar.events');

    // Rute Pencarian
    Route::get('/api/search/bookings', [SearchController::class, 'bookings'])->name('api.search.bookings');
    Route::get('/api/search/vehicles', [SearchController::class, 'vehicles'])->name('api.search.vehicles');
    Route::get('/api/search/users', [SearchController::class, 'users'])->name('api.search.users');
});

require __DIR__.'/auth.php';
