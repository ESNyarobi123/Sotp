<?php

use App\Livewire\Admin\OmadaIntegration;
use App\Models\OmadaSetting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('omada page renders settings form', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(OmadaIntegration::class)
        ->assertSee('Omada Integration')
        ->assertSee('Controller Connection')
        ->assertSee('Hotspot')
        ->assertSee('Setup Guide');
});

test('can save omada settings', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(OmadaIntegration::class)
        ->set('controller_url', 'https://omada.test.com')
        ->set('username', 'admin')
        ->set('password', 'secret123')
        ->set('hotspot_operator_name', 'operator')
        ->set('external_portal_url', 'https://portal.test.com')
        ->call('save');

    $settings = OmadaSetting::first();
    expect($settings->controller_url)->toBe('https://omada.test.com');
    expect($settings->username)->toBe('admin');
    expect($settings->hotspot_operator_name)->toBe('operator');
    expect($settings->external_portal_url)->toBe('https://portal.test.com');
});

test('save validates required fields', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(OmadaIntegration::class)
        ->set('controller_url', '')
        ->set('username', '')
        ->call('save')
        ->assertHasErrors(['controller_url', 'username']);
});

test('save validates url format', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(OmadaIntegration::class)
        ->set('controller_url', 'not-a-url')
        ->set('username', 'admin')
        ->call('save')
        ->assertHasErrors(['controller_url']);
});

test('password fields are not pre-filled with existing values', function () {
    $user = User::factory()->create();
    OmadaSetting::factory()->create();

    $component = Livewire::actingAs($user)->test(OmadaIntegration::class);

    expect($component->get('password'))->toBe('');
    expect($component->get('api_key'))->toBe('');
    expect($component->get('hotspot_operator_password'))->toBe('');
});

test('existing settings are loaded on mount', function () {
    $user = User::factory()->create();
    OmadaSetting::factory()->create([
        'controller_url' => 'https://my-omada.com',
        'username' => 'myadmin',
        'site_id' => 'site-abc',
    ]);

    $component = Livewire::actingAs($user)->test(OmadaIntegration::class);

    expect($component->get('controller_url'))->toBe('https://my-omada.com');
    expect($component->get('username'))->toBe('myadmin');
    expect($component->get('site_id'))->toBe('site-abc');
});

test('test connection succeeds with valid response', function () {
    $user = User::factory()->create();
    OmadaSetting::factory()->create([
        'controller_url' => 'https://omada.test.com',
        'username' => 'admin',
        'password' => 'pass',
    ]);

    Http::fake([
        '*/api/info' => Http::response([
            'errorCode' => 0,
            'result' => ['omadacId' => 'abc123'],
        ]),
    ]);

    Livewire::actingAs($user)
        ->test(OmadaIntegration::class)
        ->call('testConnection');

    $settings = OmadaSetting::first();
    expect($settings->is_connected)->toBeTrue();
    expect($settings->omada_id)->toBe('abc123');
    expect($settings->last_synced_at)->not->toBeNull();
});

test('test connection fails gracefully', function () {
    $user = User::factory()->create();
    OmadaSetting::factory()->create([
        'controller_url' => 'https://omada.test.com',
        'username' => 'admin',
        'password' => 'pass',
    ]);

    Http::fake([
        '*/api/info' => Http::response('Error', 500),
    ]);

    Livewire::actingAs($user)
        ->test(OmadaIntegration::class)
        ->call('testConnection');

    expect(OmadaSetting::first()->is_connected)->toBeFalse();
});
