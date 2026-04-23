<?php

use App\Http\Controllers\HomeController;
use App\Livewire\Portal\CaptivePortal;
use App\Models\Workspace;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Captive Portal (public, rate-limited) — per-workspace URL for multi-tenant Omada sites
Route::get('/portal/{workspace:public_slug}', CaptivePortal::class)
    ->middleware('throttle:portal')
    ->name('portal.workspace');

Route::get('/portal', function () {
    $workspace = Workspace::query()->orderBy('id')->first();

    if ($workspace) {
        return redirect()->route('portal.workspace', ['workspace' => $workspace->public_slug]);
    }

    abort(503, 'No Wi-Fi portal is configured yet.');
})->middleware('throttle:portal')->name('portal');

// Authenticated app (verified + rate-limited) — each Livewire screen scopes data to the signed-in user's workspace
Route::middleware(['auth', 'verified', 'throttle:admin'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // Workspace-scoped pages (all authenticated users)
    Route::view('sessions', 'admin.sessions')->name('admin.sessions');
    Route::view('payments', 'admin.payments')->name('admin.payments');
    Route::view('plans', 'admin.plans')->name('admin.plans');
    Route::view('clients', 'admin.clients')->name('admin.clients');
    Route::view('devices', 'admin.devices')->name('admin.devices');

    // Platform admin pages — full cross-workspace management
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::view('users', 'platform.users')->name('platform.users');
        Route::view('workspaces', 'platform.workspaces')->name('platform.workspaces');
        Route::view('all-payments', 'platform.payments')->name('platform.payments');
        Route::view('all-sessions', 'platform.sessions')->name('platform.sessions');
        Route::view('all-devices', 'platform.devices')->name('platform.devices');
        Route::view('gateways', 'admin.gateways')->name('admin.gateways');
        Route::view('omada', 'admin.omada')->name('admin.omada');
    });
});

require __DIR__.'/settings.php';
