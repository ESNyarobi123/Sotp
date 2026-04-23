<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('workspace status api returns provisioning summaries', function () {
    $user = User::factory()->create();
    $user->workspace->update([
        'omada_site_id' => null,
        'provisioning_status' => 'failed',
        'provisioning_error' => 'Controller temporarily unavailable',
        'provisioning_attempts' => 2,
        'provisioning_last_attempted_at' => now()->subMinutes(5),
        'provisioning_next_retry_at' => now()->addMinute(),
    ]);

    Sanctum::actingAs($user);

    $this->getJson(route('api.v1.workspace'))
        ->assertOk()
        ->assertJsonPath('provisioning_status', 'failed')
        ->assertJsonPath('provisioning_summary.status', 'failed')
        ->assertJsonPath('provisioning_summary.title', 'Controller is temporarily unavailable')
        ->assertJsonPath('provisioning_error_summary.category', 'controller_unavailable')
        ->assertJsonPath('provisioning_error_summary.retryable', true)
        ->assertJsonPath('provisioning_lifecycle.attempts', 2);
});
