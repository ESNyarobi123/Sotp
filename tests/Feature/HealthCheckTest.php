<?php

test('health endpoint returns healthy status', function () {
    $response = $this->getJson('/api/health');

    $response->assertOk()
        ->assertJsonStructure([
            'status',
            'timestamp',
            'services' => ['database', 'cache'],
            'app' => ['name', 'env', 'public_url'],
        ])
        ->assertJson([
            'status' => 'healthy',
            'services' => [
                'database' => 'ok',
                'cache' => 'ok',
            ],
        ]);
});

test('health endpoint includes app name', function () {
    $response = $this->getJson('/api/health');

    $response->assertOk()
        ->assertJsonPath('app.name', config('app.name'));
});
