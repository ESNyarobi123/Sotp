<?php

use App\Livewire\Admin\Sessions;
use App\Models\GuestSession;
use App\Models\User;
use Livewire\Livewire;

test('sessions page shows session data', function () {
    $user = User::factory()->create();
    $session = GuestSession::factory()->active()->create();

    Livewire::actingAs($user)
        ->test(Sessions::class)
        ->assertSee($session->client_mac)
        ->assertSee('Live Sessions');
});

test('sessions page can filter by status', function () {
    $user = User::factory()->create();
    $active = GuestSession::factory()->active()->create();
    $expired = GuestSession::factory()->expired()->create();

    Livewire::actingAs($user)
        ->test(Sessions::class)
        ->set('statusFilter', 'active')
        ->assertSee($active->client_mac)
        ->assertDontSee($expired->client_mac);
});

test('sessions page can search by MAC', function () {
    $user = User::factory()->create();
    $target = GuestSession::factory()->active()->create(['client_mac' => 'AA:BB:CC:DD:EE:FF']);
    $other = GuestSession::factory()->active()->create(['client_mac' => '11:22:33:44:55:66']);

    Livewire::actingAs($user)
        ->test(Sessions::class)
        ->set('search', 'AA:BB')
        ->assertSee('AA:BB:CC:DD:EE:FF')
        ->assertDontSee('11:22:33:44:55:66');
});

test('sessions page can disconnect an active session', function () {
    $user = User::factory()->create();
    $session = GuestSession::factory()->active()->create();

    Livewire::actingAs($user)
        ->test(Sessions::class)
        ->call('disconnect', $session->id);

    expect($session->fresh()->status)->toBe('disconnected');
    expect($session->fresh()->time_ended)->not->toBeNull();
});

test('sessions page cannot disconnect a non-active session', function () {
    $user = User::factory()->create();
    $session = GuestSession::factory()->expired()->create();

    Livewire::actingAs($user)
        ->test(Sessions::class)
        ->call('disconnect', $session->id);

    expect($session->fresh()->status)->toBe('expired');
});

test('sessions page shows correct status counts', function () {
    $user = User::factory()->create();
    GuestSession::factory()->active()->count(3)->create();
    GuestSession::factory()->expired()->count(2)->create();

    $component = Livewire::actingAs($user)->test(Sessions::class);

    expect($component->get('activeCount'))->toBe(3);
    expect($component->get('expiredCount'))->toBe(2);
});
