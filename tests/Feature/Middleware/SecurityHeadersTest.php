<?php

test('security headers are present on web responses', function () {
    $response = $this->get('/login');

    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-XSS-Protection', '1; mode=block');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Permissions-Policy');
});

test('CSP header is not set in local environment', function () {
    $response = $this->get('/login');

    expect($response->headers->has('Content-Security-Policy'))->toBeFalse();
});

test('CSP allows required external sources in production', function () {
    app()->detectEnvironment(fn () => 'production');

    $response = $this->get('/login');

    $csp = $response->headers->get('Content-Security-Policy');

    expect($csp)->toContain("script-src 'self'");
    expect($csp)->toContain('cdn.jsdelivr.net');
    expect($csp)->toContain('api.iconify.design');
    expect($csp)->toContain('fonts.bunny.net');
    expect($csp)->toContain("frame-ancestors 'self'");
});

test('HSTS header only set in production', function () {
    // Default test environment is not production
    $response = $this->get('/login');
    expect($response->headers->has('Strict-Transport-Security'))->toBeFalse();
});
