<?php

use App\Jobs\ProvisionWorkspaceOmadaSiteJob;
use App\Models\Workspace;
use App\Services\OmadaService;
use Mockery\MockInterface;

test('provision workspace job marks workspace ready when omada site is created', function () {
    $workspace = Workspace::factory()->pending()->create([
        'brand_name' => 'Acme Lounge',
    ]);

    $this->mock(OmadaService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('isConfigured')->once()->andReturn(true);
        $mock->shouldReceive('createSiteForBrand')->once()->with('Acme Lounge')->andReturn([
            'success' => true,
            'siteId' => 'site-123',
            'error' => null,
            'error_code' => null,
            'retryable' => false,
        ]);
    });

    $job = new ProvisionWorkspaceOmadaSiteJob($workspace);
    $job->handle(app(OmadaService::class));

    $workspace->refresh();

    expect($workspace->omada_site_id)->toBe('site-123');
    expect($workspace->provisioning_status)->toBe('ready');
    expect($workspace->provisioning_error)->toBeNull();
    expect($workspace->provisioning_attempts)->toBe(1);
    expect($workspace->provisioning_last_attempted_at)->not->toBeNull();
    expect($workspace->provisioning_next_retry_at)->toBeNull();
});

test('provision workspace job marks workspace failed when omada is not configured', function () {
    $workspace = Workspace::factory()->pending()->create();

    $this->mock(OmadaService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('isConfigured')->once()->andReturn(false);
        $mock->shouldNotReceive('createSiteForBrand');
    });

    $job = new ProvisionWorkspaceOmadaSiteJob($workspace);
    $job->handle(app(OmadaService::class));

    $workspace->refresh();

    expect($workspace->provisioning_status)->toBe('failed');
    expect($workspace->provisioning_error)->toContain('Omada Open API is not configured');
    expect($workspace->provisioning_attempts)->toBe(1);
    expect($workspace->provisioning_last_attempted_at)->not->toBeNull();
    expect($workspace->provisioning_next_retry_at)->toBeNull();
});

test('provision workspace job throws retryable failures and failed hook marks workspace failed', function () {
    $workspace = Workspace::factory()->pending()->create([
        'brand_name' => 'Retry Branch',
    ]);

    $this->mock(OmadaService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('isConfigured')->once()->andReturn(true);
        $mock->shouldReceive('createSiteForBrand')->once()->with('Retry Branch')->andReturn([
            'success' => false,
            'siteId' => null,
            'error' => 'Controller temporarily unavailable',
            'error_code' => 'controller_unavailable',
            'retryable' => true,
        ]);
    });

    $job = new ProvisionWorkspaceOmadaSiteJob($workspace);

    expect(fn () => $job->handle(app(OmadaService::class)))->toThrow(RuntimeException::class);

    $workspace->refresh();

    expect($workspace->provisioning_status)->toBe('provisioning');
    expect($workspace->provisioning_error)->toBeNull();
    expect($workspace->provisioning_attempts)->toBe(1);
    expect($workspace->provisioning_last_attempted_at)->not->toBeNull();
    expect($workspace->provisioning_next_retry_at)->not->toBeNull();

    $job->failed(new RuntimeException('Controller temporarily unavailable'));

    $workspace->refresh();

    expect($workspace->provisioning_status)->toBe('failed');
    expect($workspace->provisioning_error)->toBe('Controller temporarily unavailable');
});
