<?php

use App\Http\Controllers\Api\ClickPesaWebhookController;
use App\Http\Controllers\Api\WorkspaceStatusController;
use App\Http\Controllers\HealthCheckController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/v1/workspace', WorkspaceStatusController::class)
    ->middleware('auth:sanctum')
    ->name('api.v1.workspace');

Route::get('/health', HealthCheckController::class)->name('api.health');

Route::post('/clickpesa/webhook', ClickPesaWebhookController::class)
    ->middleware('throttle:api')
    ->name('api.clickpesa.webhook');
