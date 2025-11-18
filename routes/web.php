<?php

use App\Http\Controllers\RentalController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public routes
Route::get('/', [RentalController::class, 'index'])->name('rental.index');
Route::get('/cart', function () {
    return Inertia::render('Cart/Index');
});
Route::get('/about', function () {
    return Inertia::render('About');
});
Route::get('/contact', function () {
    return Inertia::render('Contact');
});
Route::post('/cart/checkout', [RentalController::class, 'cartCheckout'])->name('rental.cartCheckout');
Route::get('/booking/check', function() {
    return inertia('Booking/Check');
})->name('booking.check');
Route::get('/rental/success', [RentalController::class, 'success'])
    ->name('rental.success');
Route::post('/booking/check-status', [RentalController::class, 'checkStatus'])
    ->name('booking.checkStatus');
Route::get('/product', [RentalController::class, 'index'])->name('rental.index');
Route::get('/product/{product}', [RentalController::class, 'show'])->name('rental.show');
Route::post('/product/{product}/check', [RentalController::class, 'checkAvailability']);
Route::post('/product/{product}/booking', [RentalController::class, 'bookingstore'])->name('rental.bookingstore');
Route::get('/orders', [RentalController::class, 'orders'])->name('rental.orders');