<?php

namespace App\Http\Controllers;

use App\Http\Middleware\DetectPublicUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthCheckController extends Controller
{
    /**
     * Application health check endpoint.
     * Returns service status for monitoring and load balancers.
     */
    public function __invoke(): JsonResponse
    {
        $dbOk = $this->checkDatabase();
        $cacheOk = $this->checkCache();

        $healthy = $dbOk && $cacheOk;

        return response()->json([
            'status' => $healthy ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'services' => [
                'database' => $dbOk ? 'ok' : 'error',
                'cache' => $cacheOk ? 'ok' : 'error',
            ],
            'app' => [
                'name' => config('app.name'),
                'env' => app()->environment(),
                'public_url' => DetectPublicUrl::publicUrl(),
            ],
        ], $healthy ? 200 : 503);
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function checkCache(): bool
    {
        try {
            Cache::put('health-check', true, 10);

            return Cache::get('health-check') === true;
        } catch (\Throwable) {
            return false;
        }
    }
}
