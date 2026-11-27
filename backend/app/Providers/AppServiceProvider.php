<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        RateLimiter::for('public-api', function (Request $request) {
            $expected = (string) config('services.internal_api.key');

            if ($expected !== '' && is_string($request->header('X-API-Key')) && hash_equals($expected, $request->header('X-API-Key'))) {
                return Limit::none();
            }

            return Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('comment-submit', function (Request $request) {
            return Limit::perMinutes(10, 3)->by($request->ip())->response(function (Request $request, array $headers) {
                return response()->json([
                    'message' => 'Terlalu banyak percobaan komentar. Silakan coba lagi nanti.',
                ], 429, $headers);
            });
        });
    }
}
