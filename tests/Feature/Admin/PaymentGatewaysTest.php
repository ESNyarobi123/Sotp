<?php

use App\Livewire\Admin\PaymentGateways;
use App\Models\PaymentGatewaySetting;
use App\Models\User;
use Livewire\Livewire;

test('payment gateways page renders', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(PaymentGateways::class)
        ->assertSee('Payment Gateways')
        ->assertSee('ClickPesa')
        ->assertSee('Setup');
});

test('can save clickpesa settings', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(PaymentGateways::class)
        ->call('editClickPesa')
        ->set('client_id', 'TEST_CLIENT_ID')
        ->set('api_key', 'test_api_key_123')
        ->set('webhook_url', 'https://example.com/api/clickpesa/webhook')
        ->call('saveClickPesa');

    $settings = PaymentGatewaySetting::where('gateway', 'clickpesa')->first();
    expect($settings)->not->toBeNull();
    expect($settings->configValue('client_id'))->toBe('TEST_CLIENT_ID');
    expect($settings->configValue('api_key'))->toBe('test_api_key_123');
    expect($settings->is_active)->toBeTrue();
});

test('clickpesa settings require client_id', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(PaymentGateways::class)
        ->call('editClickPesa')
        ->set('client_id', '')
        ->call('saveClickPesa')
        ->assertHasErrors(['client_id']);
});

test('existing clickpesa settings are loaded on mount', function () {
    $user = User::factory()->create();
    PaymentGatewaySetting::create([
        'gateway' => 'clickpesa',
        'display_name' => 'ClickPesa',
        'is_active' => true,
        'config' => ['client_id' => 'LOADED_ID', 'api_key' => 'secret'],
    ]);

    $component = Livewire::actingAs($user)->test(PaymentGateways::class);

    expect($component->get('client_id'))->toBe('LOADED_ID');
    expect($component->get('api_key'))->toBe(''); // Secret fields not pre-filled
});

test('shows not configured badge when no settings', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(PaymentGateways::class)
        ->assertSee('Not configured');
});

test('shows active badge when configured', function () {
    $user = User::factory()->create();
    PaymentGatewaySetting::create([
        'gateway' => 'clickpesa',
        'display_name' => 'ClickPesa',
        'is_active' => true,
        'config' => ['client_id' => 'TEST', 'api_key' => 'key'],
    ]);

    Livewire::actingAs($user)
        ->test(PaymentGateways::class)
        ->assertSee('Active');
});
