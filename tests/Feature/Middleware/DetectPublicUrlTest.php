<?php

use App\Http\Middleware\DetectPublicUrl;
use Illuminate\Support\Facades\Cache;

test('detects ngrok tunnel from forwarded headers', function () {
    $this->withHeaders([
        'X-Forwarded-Host' => 'abc123.ngrok-free.app',
        'X-Forwarded-Proto' => 'https',
    ])->get('/login');

    expect(Cache::get('detected_public_url'))->toBe('https://abc123.ngrok-free.app');
    expect(DetectPublicUrl::tunnelProvider())->toBe('ngrok');
});

test('detects localxpose tunnel', function () {
    $this->withHeaders([
        'X-Forwarded-Host' => 'myapp.loclx.io',
        'X-Forwarded-Proto' => 'https',
    ])->get('/login');

    expect(Cache::get('detected_public_url'))->toBe('https://myapp.loclx.io');
    expect(DetectPublicUrl::tunnelProvider())->toBe('LocalXpose');
});

test('detects cloudflare tunnel', function () {
    $this->withHeaders([
        'CF-Connecting-IP' => '203.0.113.50',
        'X-Forwarded-Host' => 'wifi.example.com',
    ])->get('/login');

    expect(Cache::get('detected_public_url'))->toBe('https://wifi.example.com');
});

test('manual PUBLIC_URL takes priority over headers', function () {
    config(['app.public_url' => 'https://manual.example.com']);

    $this->withHeaders([
        'X-Forwarded-Host' => 'abc123.ngrok-free.app',
        'X-Forwarded-Proto' => 'https',
    ])->get('/login');

    expect(Cache::get('detected_public_url'))->toBe('https://manual.example.com');
    expect(DetectPublicUrl::publicUrl())->toBe('https://manual.example.com');
});

test('falls back to APP_URL when no tunnel detected', function () {
    Cache::forget('detected_public_url');
    config(['app.public_url' => '']);

    expect(DetectPublicUrl::publicUrl())->toBe(config('app.url'));
});

test('portalUrl appends /portal to public URL', function () {
    Cache::put('detected_public_url', 'https://tunnel.ngrok-free.app', now()->addMinutes(5));

    expect(DetectPublicUrl::portalUrl())->toBe('https://tunnel.ngrok-free.app/portal');
});

test('webhookUrl appends /api/clickpesa/webhook', function () {
    Cache::put('detected_public_url', 'https://tunnel.ngrok-free.app', now()->addMinutes(5));

    expect(DetectPublicUrl::webhookUrl())->toBe('https://tunnel.ngrok-free.app/api/clickpesa/webhook');
});

test('ignores localhost forwarded host', function () {
    Cache::forget('detected_public_url');

    $this->withHeaders([
        'X-Forwarded-Host' => 'localhost',
    ])->get('/login');

    expect(Cache::has('detected_public_url'))->toBeFalse();
});
