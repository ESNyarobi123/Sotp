<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
    <style>
        .login-gradient {
            background:
                radial-gradient(ellipse 60% 45% at 50% 100%, rgba(188,108,37,0.10), transparent),
                radial-gradient(ellipse 40% 35% at 80% 10%, rgba(188,108,37,0.05), transparent);
        }
        .dark .login-gradient {
            background:
                radial-gradient(ellipse 60% 45% at 50% 100%, rgba(212,137,63,0.07), transparent),
                radial-gradient(ellipse 40% 35% at 80% 10%, rgba(212,137,63,0.03), transparent);
        }
        @media (prefers-reduced-motion: reduce) {
            .fade-in { opacity: 1 !important; transform: none !important; }
        }
    </style>
</head>
<body class="min-h-screen bg-ivory antialiased dark:bg-smoke">

    <div class="flex min-h-dvh">

        {{-- ─── Left Panel: Brand ─── --}}
        <div class="login-gradient relative hidden w-[480px] shrink-0 flex-col justify-between overflow-hidden border-r border-smoke/[0.04] dark:border-white/[0.04] bg-white/30 dark:bg-smoke-light/[0.08] p-10 lg:flex xl:w-[520px]">

            {{-- Logo --}}
            <div>
                <a href="{{ route('home') }}" class="group inline-flex items-center gap-2.5 cursor-pointer" wire:navigate>
                    <div class="flex size-9 items-center justify-center rounded-[10px] bg-terra shadow-sm transition-transform duration-200 group-hover:scale-105">
                        <svg class="size-[18px] text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z" />
                        </svg>
                    </div>
                    <span class="text-[15px] font-bold tracking-tight text-smoke dark:text-ivory">SKY Omada</span>
                </a>
            </div>

            {{-- Center content --}}
            <div class="space-y-8">
                <div class="space-y-4">
                    <h2 class="text-[28px] font-extrabold leading-tight tracking-tight text-smoke dark:text-ivory">
                        Welcome back.
                    </h2>
                    <p class="text-[14px] leading-relaxed text-smoke/55 dark:text-ivory/45 max-w-sm">
                        Sign in to manage your Wi-Fi business — monitor devices, track revenue, and control guest access from one dashboard.
                    </p>
                </div>

                {{-- Device image --}}
                <div class="flex justify-center">
                    <div class="rounded-[24px] bg-gradient-to-br from-ivory via-ivory-dark/30 to-terra/[0.05] dark:from-smoke dark:via-smoke-light/50 dark:to-terra/[0.06] p-6">
                        <img
                            src="/omada.webp"
                            alt="TP-Link Omada access point"
                            class="w-36 xl:w-40 mx-auto mix-blend-multiply dark:mix-blend-luminosity dark:brightness-[1.8] dark:contrast-[0.9] drop-shadow-md"
                            loading="eager"
                            width="320"
                            height="320"
                        >
                    </div>
                </div>

                {{-- Quick stats --}}
                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-2xl border border-smoke/[0.05] dark:border-white/[0.05] bg-white/50 dark:bg-smoke-light/30 p-4 text-center">
                        <div class="text-lg font-extrabold text-terra dark:text-terra-light">24/7</div>
                        <div class="text-[10px] font-medium text-smoke/40 dark:text-ivory/35 uppercase tracking-wider mt-1">Uptime</div>
                    </div>
                    <div class="rounded-2xl border border-smoke/[0.05] dark:border-white/[0.05] bg-white/50 dark:bg-smoke-light/30 p-4 text-center">
                        <div class="text-lg font-extrabold text-terra dark:text-terra-light">M-Pesa</div>
                        <div class="text-[10px] font-medium text-smoke/40 dark:text-ivory/35 uppercase tracking-wider mt-1">Payments</div>
                    </div>
                    <div class="rounded-2xl border border-smoke/[0.05] dark:border-white/[0.05] bg-white/50 dark:bg-smoke-light/30 p-4 text-center">
                        <div class="text-lg font-extrabold text-terra dark:text-terra-light">Live</div>
                        <div class="text-[10px] font-medium text-smoke/40 dark:text-ivory/35 uppercase tracking-wider mt-1">Analytics</div>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <p class="text-[11px] text-smoke/30 dark:text-ivory/25">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>

        {{-- ─── Right Panel: Login Form ─── --}}
        <div class="flex flex-1 flex-col items-center justify-center px-6 py-10 sm:px-10 lg:px-16">
            <div class="w-full max-w-[400px]">

                {{-- Mobile logo (hidden on lg) --}}
                <div class="mb-10 flex flex-col items-center gap-3 lg:hidden">
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5 cursor-pointer" wire:navigate>
                        <div class="flex size-10 items-center justify-center rounded-2xl bg-terra shadow-sm">
                            <svg class="size-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z" />
                            </svg>
                        </div>
                        <span class="text-lg font-bold tracking-tight text-smoke dark:text-ivory">{{ config('app.name', 'SKY Omada') }}</span>
                    </a>
                </div>

                {{-- Header --}}
                <div class="mb-8 flex w-full flex-col items-center text-center lg:items-start lg:text-left">
                    <div class="mb-3 flex items-center gap-2">
                        <div class="grid size-10 place-items-center rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
                            <flux:icon name="shield" class="size-5 text-terra dark:text-terra-light" />
                        </div>
                        <flux:heading size="xl" class="text-smoke dark:text-ivory">{{ __('Log in to your account') }}</flux:heading>
                    </div>
                    <flux:subheading class="text-smoke/50 dark:text-ivory/50">{{ __('Enter your email and password below to log in') }}</flux:subheading>
                </div>

                <!-- Session Status -->
                <x-auth-session-status class="mb-4 text-center" :status="session('status')" />

                <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
                    @csrf

                    {{-- Email --}}
                    <div>
                        <div class="mb-1 flex items-center gap-1.5">
                            <flux:icon name="mail" class="size-4 text-terra dark:text-terra-light" />
                            <label class="text-sm font-medium text-smoke dark:text-ivory">{{ __('Email address') }}</label>
                        </div>
                        <flux:input
                            name="email"
                            :value="old('email')"
                            type="email"
                            required
                            autofocus
                            autocomplete="email"
                            placeholder="email@example.com"
                        />
                    </div>

                    {{-- Password --}}
                    <div>
                        <div class="mb-1 flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <flux:icon name="lock" class="size-4 text-terra dark:text-terra-light" />
                                <label class="text-sm font-medium text-smoke dark:text-ivory">{{ __('Password') }}</label>
                            </div>
                            @if (Route::has('password.request'))
                                <flux:link class="text-xs text-terra hover:text-terra-dark cursor-pointer" :href="route('password.request')" wire:navigate>
                                    {{ __('Forgot your password?') }}
                                </flux:link>
                            @endif
                        </div>
                        <flux:input
                            name="password"
                            type="password"
                            required
                            autocomplete="current-password"
                            :placeholder="__('Password')"
                            viewable
                        />
                    </div>

                    {{-- Remember me --}}
                    <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

                    {{-- Submit --}}
                    <div class="pt-1">
                        <flux:button variant="primary" type="submit" class="w-full !bg-terra !text-white hover:!opacity-90 cursor-pointer" data-test="login-button">
                            <flux:icon name="log-in" class="mr-1 size-4 text-white" />
                            {{ __('Log in') }}
                        </flux:button>
                    </div>
                </form>

                {{-- Register link --}}
                @if (Route::has('register'))
                    <div class="mt-6 space-x-1 text-sm text-center rtl:space-x-reverse text-smoke/60 dark:text-ivory/60">
                        <span>{{ __('Don\'t have an account?') }}</span>
                        <flux:link class="!text-terra hover:!text-terra-dark cursor-pointer" :href="route('register')" wire:navigate>{{ __('Sign up') }}</flux:link>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @fluxScripts
</body>
</html>
