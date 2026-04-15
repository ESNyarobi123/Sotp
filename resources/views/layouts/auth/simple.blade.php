<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-gradient-to-br from-ivory via-white to-ivory antialiased dark:from-smoke dark:via-smoke-light dark:to-smoke">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-sm flex-col gap-2">
                {{-- Logo --}}
                <a href="{{ route('login') }}" class="flex flex-col items-center gap-3 font-medium" wire:navigate>
                    <div class="flex size-14 items-center justify-center rounded-2xl bg-terra text-white shadow-lg">
                        <flux:icon name="wifi" class="size-8 text-white" />
                    </div>
                    <span class="text-xl font-bold text-smoke dark:text-ivory">{{ config('app.name', 'SKY Omada') }}</span>
                </a>
                <div class="mt-2 flex flex-col gap-6">
                    {{ $slot }}
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="fixed bottom-4 left-0 right-0 text-center text-xs text-smoke/40 dark:text-ivory/40">
            Powered by SKY Omada &middot; Secure WiFi Billing
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
