<?php

use App\Http\Controllers\RentalController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Public Web Routes (with rate limiter)
|--------------------------------------------------------------------------
|
| Semua GET route memakai throttle:web untuk mencegah spam refresh,
| scraping agresif, dan DDoS ringan.
|
*/

Route::middleware(['throttle:web'])->group(function () {

    // Home & pages
    Route::get('/', [RentalController::class, 'index'])->name('rental.index');

    Route::get('/cart', fn () => Inertia::render('Cart/Index'));
    Route::get('/about', fn () => Inertia::render('About'));
    Route::get('/contact', fn () => Inertia::render('Contact'));
    Route::get('/rental/success', [RentalController::class, 'success'])
        ->name('rental.success');
    Route::get('/product', [RentalController::class, 'index'])->name('rental.index');
    // Route::get('/product/{product}', [RentalController::class, 'show'])->name('rental.show');
    Route::get('/product/{product:slug}', [RentalController::class, 'show'])->name('rental.show');
    Route::get('/sitemap.xml', [RentalController::class, 'sitemap']);
});


/*
|--------------------------------------------------------------------------
| Form / POST Routes (with anti-spam protection)
|--------------------------------------------------------------------------
|
| Semua POST route harus memakai throttle:form
| untuk mencegah submit spam.
|
*/

// Checkout
Route::post('/cart/checkout', [RentalController::class, 'cartCheckout'])
    ->name('rental.cartCheckout')
    ->middleware('throttle:form');
Route::post('/product/{product}/booking', [RentalController::class, 'bookingstore'])
    ->name('rental.bookingstore')
    ->middleware('throttle:form');
