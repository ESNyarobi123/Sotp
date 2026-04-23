<?php

use App\Models\User;

test('dashboard requires authentication', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('dashboard loads for authenticated admin', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Online Users')
        ->assertSee('Revenue Today')
        ->assertSee('Devices Online')
        ->assertSee('Sessions Today');
});

test('all admin routes require authentication', function () {
    $routes = [
        '/sessions',
        '/payments',
        '/plans',
        '/clients',
        '/devices',
        '/admin/omada',
        '/admin/gateways',
    ];

    foreach ($routes as $route) {
        $this->get($route)->assertRedirect('/login');
    }
});

test('non-admin cannot open omada settings page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get('/admin/omada')
        ->assertRedirect(route('dashboard'));
});

test('non-admin cannot open payment gateways page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get('/admin/gateways')
        ->assertRedirect(route('dashboard'));
});

test('workspace routes load for any authenticated user', function () {
    $user = User::factory()->create();

    $routes = [
        '/sessions' => 'Sessions',
        '/payments' => 'Payments',
        '/plans' => 'Plans',
        '/clients' => 'Clients',
        '/devices' => 'Devices',
    ];

    foreach ($routes as $route => $expectedText) {
        $this->actingAs($user)
            ->get($route)
            ->assertOk()
            ->assertSee($expectedText);
    }
});

test('omada settings page loads only for admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/omada')
        ->assertOk()
        ->assertSee('Omada Integration');
});

test('payment gateways page loads only for admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/gateways')
        ->assertOk()
        ->assertSee('Payment Gateways');
});
