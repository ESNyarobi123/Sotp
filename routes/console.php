<?php

use App\Events\DeviceStatusChanged;
use App\Models\Device;
use App\Models\OmadaSetting;
use App\Services\OmadaService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('omada:sync-devices', function () {
    $result = app(OmadaService::class)->syncDevicesFromOmada();

    if ($result['success']) {
        OmadaSetting::instance()->touch('last_synced_at');
        $this->info("Synced {$result['synced']} device(s) from Omada.");

        // Broadcast device status change
        DeviceStatusChanged::dispatch($result['synced'], Device::count());
    } else {
        $this->error('Sync failed: ' . ($result['error'] ?? 'Unknown error'));
    }
})->purpose('Sync TP-Link AP devices from Omada controller');

Schedule::command('omada:sync-devices')->everyFiveMinutes();
