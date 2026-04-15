<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'WiFi Access' }} - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="min-h-screen bg-gradient-to-br from-ivory via-white to-ivory dark:from-smoke dark:via-smoke-light dark:to-smoke">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-8">
        {{-- Logo --}}
        <div class="mb-6 text-center">
            <div class="inline-flex size-14 items-center justify-center rounded-2xl bg-terra text-white shadow-lg">
                <flux:icon name="wifi" class="size-8 text-white" />
            </div>
            <h1 class="mt-3 text-xl font-bold text-smoke dark:text-ivory">{{ config('app.name', 'SKY WiFi') }}</h1>
            <p class="text-sm text-smoke/50 dark:text-ivory/50">High-speed WiFi access</p>
        </div>

        {{-- Content --}}
        <div class="w-full max-w-md">
            {{ $slot }}
        </div>

        {{-- Footer --}}
        <div class="mt-8 text-center text-xs text-smoke/40 dark:text-ivory/40">
            <p>Powered by SKY Omada &middot; Secure WiFi Billing</p>
        </div>
    </div>

    @fluxScripts
</body>
</html>
