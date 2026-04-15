<?php

use App\Livewire\Admin\Plans;
use App\Models\GuestSession;
use App\Models\Plan;
use App\Models\User;
use Livewire\Livewire;

test('plans page shows existing plans', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['name' => 'Test WiFi Plan']);

    Livewire::actingAs($user)
        ->test(Plans::class)
        ->assertSee('Test WiFi Plan');
});

test('can create a new time-based plan', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Plans::class)
        ->call('create')
        ->set('name', '30 Minutes WiFi')
        ->set('type', 'time')
        ->set('value', 30)
        ->set('price', '500')
        ->set('validity_days', 1)
        ->call('save');

    $this->assertDatabaseHas('plans', [
        'name' => '30 Minutes WiFi',
        'type' => 'time',
        'value' => 30,
        'price' => 500.00,
    ]);
});

test('can create a new data-based plan', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Plans::class)
        ->call('create')
        ->set('name', '500MB Bundle')
        ->set('type', 'data')
        ->set('value', 500)
        ->set('price', '2000')
        ->set('validity_days', 1)
        ->call('save');

    $this->assertDatabaseHas('plans', [
        'name' => '500MB Bundle',
        'type' => 'data',
        'value' => 500,
    ]);
});

test('can edit an existing plan', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['name' => 'Old Name']);

    Livewire::actingAs($user)
        ->test(Plans::class)
        ->call('edit', $plan->id)
        ->set('name', 'New Name')
        ->call('save');

    expect($plan->fresh()->name)->toBe('New Name');
});

test('can toggle plan active status', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['is_active' => true]);

    Livewire::actingAs($user)
        ->test(Plans::class)
        ->call('toggleActive', $plan->id);

    expect($plan->fresh()->is_active)->toBeFalse();
});

test('can delete a plan without sessions or payments', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();

    Livewire::actingAs($user)
        ->test(Plans::class)
        ->call('delete', $plan->id);

    $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
});

test('cannot delete a plan with existing sessions', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    GuestSession::factory()->create(['plan_id' => $plan->id]);

    Livewire::actingAs($user)
        ->test(Plans::class)
        ->call('delete', $plan->id);

    $this->assertDatabaseHas('plans', ['id' => $plan->id]);
});

test('create form validation requires name and price', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Plans::class)
        ->call('create')
        ->set('name', '')
        ->set('price', '')
        ->call('save')
        ->assertHasErrors(['name', 'price']);
});
