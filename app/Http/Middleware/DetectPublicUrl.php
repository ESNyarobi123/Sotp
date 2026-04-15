<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-detect public URLs from tunnels (ngrok, Cloudflare Tunnel, LocalXpose)
 * and cache the result so the Omada Integration page can suggest the correct
 * external portal URL for captive portal redirects.
 */
class DetectPublicUrl
{
    /**
     * Cache key for the detected public URL.
     */
    private const CACHE_KEY = 'detected_public_url';

    public function handle(Request $request, Closure $next): Response
    {
        // Manual .env override is highest priority
        $manualUrl = config('app.public_url');

        if ($manualUrl) {
            Cache::put(self::CACHE_KEY, $manualUrl, now()->addHours(24));

            return $next($request);
        }

        $detected = $this->detectFromHeaders($request);

        if ($detected) {
            Cache::put(self::CACHE_KEY, $detected, now()->addMinutes(10));
        }

        return $next($request);
    }

    /**
     * Inspect request headers for known tunnel signatures.
     */
    private function detectFromHeaders(Request $request): ?string
    {
        $forwardedHost = $request->header('X-Forwarded-Host');
        $forwardedProto = $request->header('X-Forwarded-Proto', 'https');

        // ── ngrok ──────────────────────────────────────────
        if ($forwardedHost && str_contains($forwardedHost, '.ngrok')) {
            return "{$forwardedProto}://{$forwardedHost}";
        }

        // ── LocalXpose ─────────────────────────────────────
        if ($forwardedHost && str_contains($forwardedHost, '.loclx.io')) {
            return "{$forwardedProto}://{$forwardedHost}";
        }

        // ── Cloudflare Tunnel ──────────────────────────────
        if ($request->header('CF-Connecting-IP')) {
            $host = $forwardedHost ?: $request->getHost();

            if (! in_array($host, ['localhost', '127.0.0.1', '0.0.0.0'])) {
                return "https://{$host}";
            }
        }

        // ── Generic forwarded host (any reverse proxy) ─────
        if ($forwardedHost && ! in_array($forwardedHost, ['localhost', '127.0.0.1', '0.0.0.0'])) {
            return "{$forwardedProto}://{$forwardedHost}";
        }

        return null;
    }

    /**
     * Resolve the best public URL available:
     *   1. Cached detection (from tunnel headers)
     *   2. Manual PUBLIC_URL env
     *   3. APP_URL fallback
     */
    public static function publicUrl(): string
    {
        return Cache::get(self::CACHE_KEY)
            ?: config('app.public_url')
            ?: config('app.url');
    }

    /**
     * Resolve the portal URL using the best public base URL.
     */
    public static function portalUrl(): string
    {
        return rtrim(self::publicUrl(), '/') . '/portal';
    }

    /**
     * Resolve the ClickPesa webhook URL using the best public base URL.
     */
    public static function webhookUrl(): string
    {
        return rtrim(self::publicUrl(), '/') . '/api/clickpesa/webhook';
    }

    /**
     * Whether the current public URL was auto-detected from a tunnel.
     */
    public static function isDetectedFromTunnel(): bool
    {
        $cached = Cache::get(self::CACHE_KEY);
        $manual = config('app.public_url');

        return $cached && $cached !== $manual && $cached !== config('app.url');
    }

    /**
     * Get the tunnel provider name, if detected.
     */
    public static function tunnelProvider(): ?string
    {
        $url = Cache::get(self::CACHE_KEY, '');

        return match (true) {
            str_contains($url, '.ngrok') => 'ngrok',
            str_contains($url, '.loclx.io') => 'LocalXpose',
            str_contains($url, '.trycloudflare.com') => 'Cloudflare Tunnel',
            config('app.public_url') !== '' && $url === config('app.public_url') => 'Manual (.env)',
            default => null,
        };
    }
}
