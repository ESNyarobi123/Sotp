<?php

use App\Jobs\ProvisionWorkspaceOmadaSiteJob;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    Queue::fake();

    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'brand_name' => 'John WiFi Lounge',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();

    $user = User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole('guest'))->toBeTrue();
    expect($user->workspace)->not->toBeNull();
    expect($user->workspace->brand_name)->toBe('John WiFi Lounge');

    Queue::assertPushed(ProvisionWorkspaceOmadaSiteJob::class, fn (ProvisionWorkspaceOmadaSiteJob $job) => $job->workspace->is($user->workspace));
});
