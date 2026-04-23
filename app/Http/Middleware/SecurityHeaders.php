<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Production-grade security headers.
 * Applies CSP, X-Frame-Options, HSTS, referrer policy, and more.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Prevent MIME-type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // XSS protection (legacy browsers)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer policy — send origin only on cross-origin
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions policy — disable powerful APIs the app does not use
        $response->headers->set(
            'Permissions-Policy',
            'accelerometer=(), ambient-light-sensor=(), autoplay=(), battery=(), camera=(), display-capture=(), document-domain=(), encrypted-media=(), execution-while-not-rendered=(), execution-while-out-of-viewport=(), fullscreen=(self), gamepad=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), midi=(), payment=(), picture-in-picture=(), publickey-credentials-get=(self), speaker=(), usb=(), web-share=(), xr-spatial-tracking=()',
        );

        // Block Flash / cross-domain policy files (legacy)
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        // Reduce information leaked to third parties when following links out
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');

        // HSTS — enforce HTTPS in production (1 year, include subdomains)
        if (app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Content Security Policy (only in production/staging — Vite dev server uses IPv6 that breaks CSP matching)
        if (app()->isProduction() || app()->environment('staging')) {
            $response->headers->set('Content-Security-Policy', $this->buildCsp());
        }

        return $response;
    }

    /**
     * Build a Content-Security-Policy header value.
     */
    private function buildCsp(): string
    {
        $directives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://code.iconify.design https://api.iconify.design",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://fonts.bunny.net",
            "font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net https://cdn.jsdelivr.net data:",
            "img-src 'self' data: https: blob:",
            "connect-src 'self' https://api.iconify.design https://api.clickpesa.com wss: ws:",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
            "worker-src 'self'",
            "manifest-src 'self'",
            'upgrade-insecure-requests',
        ];

        return implode('; ', $directives);
    }
}
