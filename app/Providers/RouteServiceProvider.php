<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure rate limiting for the application.
     */
    protected function configureRateLimiting(): void
    {
        /**
         * 🔹 1. API Rate Limit
         * Default: 60 request / menit / IP
         */
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        /**
         * 🔹 2. FORM Rate Limit (anti spam)
         * Biasanya dipakai untuk:
         * - booking form
         * - checkout form
         * - contact form
         *
         * Limit: 5 request / menit / IP
         */
        RateLimiter::for('form', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->ip()),
            ];
        });

        /**
         * 🔹 3. WEB / INERTIA Rate Limit
         * Mencegah spam refresh & ddos ringan.
         * Limit: 60 request / menit / IP
         */
        RateLimiter::for('web', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
