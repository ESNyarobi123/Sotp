<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
    <style>
        .register-gradient {
            background:
                radial-gradient(ellipse 70% 50% at 30% 100%, rgba(188,108,37,0.12), transparent),
                radial-gradient(ellipse 50% 40% at 80% 0%, rgba(188,108,37,0.06), transparent);
        }
        .dark .register-gradient {
            background:
                radial-gradient(ellipse 70% 50% at 30% 100%, rgba(212,137,63,0.08), transparent),
                radial-gradient(ellipse 50% 40% at 80% 0%, rgba(212,137,63,0.04), transparent);
        }
        @media (prefers-reduced-motion: reduce) {
            .fade-in { opacity: 1 !important; transform: none !important; }
        }
    </style>
</head>
<body class="min-h-screen bg-ivory antialiased dark:bg-smoke">

    <div class="flex min-h-dvh">

        {{-- ─── Left Panel: Brand / Selling ─── --}}
        <div class="register-gradient relative hidden w-[480px] shrink-0 flex-col justify-between overflow-hidden border-r border-smoke/[0.04] dark:border-white/[0.04] bg-white/30 dark:bg-smoke-light/[0.08] p-10 lg:flex xl:w-[520px]">

            {{-- Logo & back --}}
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

            {{-- Hero copy --}}
            <div class="space-y-8">
                <div class="space-y-4">
                    <h2 class="text-[28px] font-extrabold leading-tight tracking-tight text-smoke dark:text-ivory">
                        Start monetizing<br>your Wi-Fi today.
                    </h2>
                    <p class="text-[14px] leading-relaxed text-smoke/55 dark:text-ivory/45 max-w-sm">
                        Create your account and we'll set up a dedicated network site on the Omada controller for your access points — automatically.
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

                {{-- Feature bullets --}}
                <div class="space-y-3.5">
                    <div class="flex items-center gap-3">
                        <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-terra/[0.08] dark:bg-terra/[0.12]">
                            <svg class="size-4 text-terra dark:text-terra-light" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.348 14.651a3.75 3.75 0 010-5.303m5.304 0a3.75 3.75 0 010 5.303m-7.425 2.122a6.75 6.75 0 010-9.546m9.546 0a6.75 6.75 0 010 9.546" /></svg>
                        </div>
                        <span class="text-[13px] font-medium text-smoke/70 dark:text-ivory/55">Auto-provisioned Omada network site</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-terra/[0.08] dark:bg-terra/[0.12]">
                            <svg class="size-4 text-terra dark:text-terra-light" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        </div>
                        <span class="text-[13px] font-medium text-smoke/70 dark:text-ivory/55">Mobile money payments via ClickPesa</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-terra/[0.08] dark:bg-terra/[0.12]">
                            <svg class="size-4 text-terra dark:text-terra-light" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.148 2.148A12.061 12.061 0 0116.5 7.605" /></svg>
                        </div>
                        <span class="text-[13px] font-medium text-smoke/70 dark:text-ivory/55">Real-time dashboard & session analytics</span>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <p class="text-[11px] text-smoke/30 dark:text-ivory/25">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>

        {{-- ─── Right Panel: Form ─── --}}
        <div class="flex flex-1 flex-col items-center justify-center px-6 py-10 sm:px-10 lg:px-16">
            <div class="w-full max-w-[420px]">

                {{-- Mobile logo (hidden on lg) --}}
                <div class="mb-8 flex flex-col items-center gap-3 lg:hidden">
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
                            <flux:icon name="user-plus" class="size-5 text-terra dark:text-terra-light" />
                        </div>
                        <flux:heading size="xl" class="text-smoke dark:text-ivory">{{ __('Create your account') }}</flux:heading>
                    </div>
                    <flux:subheading class="text-smoke/50 dark:text-ivory/50">{{ __('Fill in your details to get started with SKY Omada WiFi') }}</flux:subheading>
                </div>

                <!-- Session Status -->
                <x-auth-session-status class="mb-4 text-center" :status="session('status')" />

                <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-5">
                    @csrf

                    {{-- Name --}}
                    <div>
                        <div class="mb-1 flex items-center gap-1.5">
                            <flux:icon name="user" class="size-4 text-terra dark:text-terra-light" />
                            <label class="text-sm font-medium text-smoke dark:text-ivory">{{ __('Full name') }}</label>
                        </div>
                        <flux:input
                            name="name"
                            :value="old('name')"
                            type="text"
                            required
                            autofocus
                            autocomplete="name"
                            :placeholder="__('John Doe')"
                        />
                    </div>

                    {{-- Brand name --}}
                    <div>
                        <div class="mb-1 flex items-center gap-1.5">
                            <flux:icon name="building-storefront" class="size-4 text-terra dark:text-terra-light" />
                            <label class="text-sm font-medium text-smoke dark:text-ivory">{{ __('Business or WiFi brand name') }}</label>
                        </div>
                        <flux:input
                            name="brand_name"
                            :value="old('brand_name')"
                            type="text"
                            required
                            maxlength="100"
                            :placeholder="__('e.g. Sky Lounge Downtown')"
                        />
                        <p class="mt-1.5 text-[11px] leading-relaxed text-smoke/40 dark:text-ivory/35">
                            {{ __('Shown in your dashboard. We create a dedicated Omada network site for your access points.') }}
                        </p>
                    </div>

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
                            autocomplete="email"
                            placeholder="email@example.com"
                        />
                    </div>

                    {{-- Password --}}
                    <div>
                        <div class="mb-1 flex items-center gap-1.5">
                            <flux:icon name="lock" class="size-4 text-terra dark:text-terra-light" />
                            <label class="text-sm font-medium text-smoke dark:text-ivory">{{ __('Password') }}</label>
                        </div>
                        <flux:input
                            name="password"
                            type="password"
                            required
                            autocomplete="new-password"
                            :placeholder="__('Create a strong password')"
                            viewable
                        />
                    </div>

                    {{-- Confirm Password --}}
                    <div>
                        <div class="mb-1 flex items-center gap-1.5">
                            <flux:icon name="shield-check" class="size-4 text-terra dark:text-terra-light" />
                            <label class="text-sm font-medium text-smoke dark:text-ivory">{{ __('Confirm password') }}</label>
                        </div>
                        <flux:input
                            name="password_confirmation"
                            type="password"
                            required
                            autocomplete="new-password"
                            :placeholder="__('Repeat your password')"
                            viewable
                        />
                    </div>

                    {{-- Submit --}}
                    <div class="pt-1">
                        <flux:button variant="primary" type="submit" class="w-full !bg-terra !text-white hover:!opacity-90 cursor-pointer" data-test="register-user-button">
                            <flux:icon name="rocket-launch" class="mr-1 size-4 text-white" />
                            {{ __('Create account') }}
                        </flux:button>
                    </div>
                </form>

                {{-- Login link --}}
                <div class="mt-6 space-x-1 text-sm text-center rtl:space-x-reverse text-smoke/60 dark:text-ivory/60">
                    <span>{{ __('Already have an account?') }}</span>
                    <flux:link class="!text-terra hover:!text-terra-dark cursor-pointer" :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
                </div>
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
