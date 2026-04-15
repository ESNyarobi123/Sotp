<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configureDefaults();
        $this->configureRateLimiting();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Configure rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Portal: 60 req/min per IP + 10/min per clientMac
        RateLimiter::for('portal', function (Request $request) {
            $clientMac = $request->query('clientMac', $request->input('clientMac', ''));

            return [
                Limit::perMinute(60)->by($request->ip()),
                Limit::perMinute(10)->by($clientMac ?: $request->ip())->response(function () {
                    return response()->json(['error' => 'Too many requests for this device.'], 429);
                }),
            ];
        });

        // Admin routes: 300/min per authenticated user
        RateLimiter::for('admin', function (Request $request) {
            return Limit::perMinute(300)->by($request->user()?->id ?: $request->ip());
        });

        // Login: 5 attempts/15 minutes with exponential backoff
        RateLimiter::for('login', function (Request $request) {
            $key = strtolower($request->input('email', '')) . '|' . $request->ip();

            return Limit::perMinutes(15, 5)->by($key)->response(function (Request $request, array $headers) {
                $seconds = $headers['Retry-After'] ?? 60;

                return back()
                    ->withErrors(['email' => "Too many login attempts. Please try again in {$seconds} seconds."])
                    ->withInput(['email' => $request->input('email')]);
            });
        });

        // API: 120 req/min per IP
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        // Omada API calls: 30/min (applied in OmadaService)
        RateLimiter::for('omada-api', function () {
            return Limit::perMinute(30)->by('omada-controller');
        });
    }
}
