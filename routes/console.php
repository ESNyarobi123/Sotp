<?php

use App\Events\DeviceStatusChanged;
use App\Models\Device;
use App\Services\OmadaService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('omada:sync-devices', function () {
    $result = app(OmadaService::class)->syncDevicesForAllWorkspaces();

    if ($result['success']) {
        $this->info("Synced {$result['synced']} device(s) across {$result['workspaces']} workspace(s).");

        DeviceStatusChanged::dispatch($result['synced'], Device::count());
    } else {
        $this->error('Sync failed: '.($result['error'] ?? 'Unknown error'));
    }
})->purpose('Sync TP-Link AP devices from Omada controller for every workspace');

Schedule::command('omada:sync-devices')->everyFiveMinutes();
Schedule::command('sessions:expire')->everyMinute();
