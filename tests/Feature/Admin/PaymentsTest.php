<?php

use App\Livewire\Admin\Payments;
use App\Models\Payment;
use App\Models\User;
use Livewire\Livewire;

test('payments page shows payment data', function () {
    $user = User::factory()->create();
    $payment = Payment::factory()->completed()->create();

    Livewire::actingAs($user)
        ->test(Payments::class)
        ->assertSee($payment->transaction_id)
        ->assertSee('Payments');
});

test('payments page can filter by status', function () {
    $user = User::factory()->create();
    $completed = Payment::factory()->completed()->create();
    $pending = Payment::factory()->create(['status' => 'pending', 'paid_at' => null]);

    Livewire::actingAs($user)
        ->test(Payments::class)
        ->set('statusFilter', 'completed')
        ->assertSee($completed->transaction_id)
        ->assertDontSee($pending->transaction_id);
});

test('payments page can filter by method', function () {
    $user = User::factory()->create();
    $mpesa = Payment::factory()->completed()->create(['payment_method' => 'mpesa']);
    $airtel = Payment::factory()->completed()->create(['payment_method' => 'airtel']);

    Livewire::actingAs($user)
        ->test(Payments::class)
        ->set('methodFilter', 'mpesa')
        ->assertSee($mpesa->transaction_id)
        ->assertDontSee($airtel->transaction_id);
});

test('payments page can search by phone number', function () {
    $user = User::factory()->create();
    $target = Payment::factory()->completed()->create(['phone_number' => '255712345678']);
    $other = Payment::factory()->completed()->create(['phone_number' => '255798765432']);

    Livewire::actingAs($user)
        ->test(Payments::class)
        ->set('search', '712345')
        ->assertSee('255712345678')
        ->assertDontSee('255798765432');
});

test('payments page shows correct status counts', function () {
    $user = User::factory()->create();
    Payment::factory()->completed()->count(5)->create();
    Payment::factory()->count(3)->create(['status' => 'pending', 'paid_at' => null]);

    $component = Livewire::actingAs($user)->test(Payments::class);

    expect($component->get('completedCount'))->toBe(5);
    expect($component->get('pendingCount'))->toBe(3);
});
