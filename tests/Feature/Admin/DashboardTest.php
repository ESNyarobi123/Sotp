<?php

use App\Models\User;

test('dashboard requires authentication', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('dashboard loads for authenticated user', function () {
    $user = User::factory()->create();

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
        '/omada',
        '/gateways',
    ];

    foreach ($routes as $route) {
        $this->get($route)->assertRedirect('/login');
    }
});

test('all admin routes load for authenticated user', function () {
    $user = User::factory()->create();

    $routes = [
        '/sessions' => 'Live Sessions',
        '/payments' => 'Payments',
        '/plans' => 'Plans / Packages',
        '/clients' => 'Clients',
        '/devices' => 'Devices (APs)',
        '/omada' => 'Omada Integration',
        '/gateways' => 'Payment Gateways',
    ];

    foreach ($routes as $route => $expectedText) {
        $this->actingAs($user)
            ->get($route)
            ->assertOk()
            ->assertSee($expectedText);
    }
});
