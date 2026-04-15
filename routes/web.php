<?php

use App\Livewire\Portal\CaptivePortal;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

// Captive Portal (public, no auth, rate-limited)
Route::get('/portal', CaptivePortal::class)
    ->middleware('throttle:portal')
    ->name('portal');

// Admin panel (authenticated + verified + rate-limited)
Route::middleware(['auth', 'verified', 'throttle:admin'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::view('sessions', 'admin.sessions')->name('admin.sessions');
    Route::view('payments', 'admin.payments')->name('admin.payments');
    Route::view('plans', 'admin.plans')->name('admin.plans');
    Route::view('clients', 'admin.clients')->name('admin.clients');
    Route::view('devices', 'admin.devices')->name('admin.devices');
    Route::view('omada', 'admin.omada')->name('admin.omada');
    Route::view('gateways', 'admin.gateways')->name('admin.gateways');
});

require __DIR__.'/settings.php';
