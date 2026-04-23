<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'SKY Omada WiFi') }} — Smart Wi-Fi Management Platform</title>
    <meta name="description" content="Turn your TP-Link Omada access points into a revenue-generating Wi-Fi business. Captive portal, mobile payments, real-time analytics — all in one platform.">

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800,900" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, system-ui, sans-serif; -webkit-font-smoothing: antialiased; }
        html { scroll-behavior: smooth; }

        /* Hero gradient — warm subtle radial */
        .hero-gradient {
            background:
                radial-gradient(ellipse 60% 40% at 70% 20%, rgba(188,108,37,0.08), transparent),
                radial-gradient(ellipse 50% 60% at 20% 80%, rgba(188,108,37,0.04), transparent);
        }
        .dark .hero-gradient {
            background:
                radial-gradient(ellipse 60% 40% at 70% 20%, rgba(212,137,63,0.06), transparent),
                radial-gradient(ellipse 50% 60% at 20% 80%, rgba(212,137,63,0.03), transparent);
        }

        /* Glass effect for navbar */
        .glass-nav {
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
        }

        /* Scroll-triggered fade-in (intersection observer driven) */
        .reveal {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity 0.7s cubic-bezier(0.16, 1, 0.3, 1), transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }
        .reveal-delay-1 { transition-delay: 0.1s; }
        .reveal-delay-2 { transition-delay: 0.2s; }
        .reveal-delay-3 { transition-delay: 0.3s; }
        .reveal-delay-4 { transition-delay: 0.4s; }

        /* Gentle float for hero device */
        .device-float {
            animation: deviceFloat 5s ease-in-out infinite;
        }
        @keyframes deviceFloat {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-10px) rotate(0.5deg); }
        }

        /* Dashboard glow */
        .dashboard-glow {
            box-shadow:
                0 32px 64px -12px rgba(188,108,37,0.15),
                0 0 0 1px rgba(188,108,37,0.05);
        }
        .dark .dashboard-glow {
            box-shadow:
                0 32px 64px -12px rgba(212,137,63,0.1),
                0 0 0 1px rgba(212,137,63,0.08);
        }

        /* Respect reduced motion */
        @media (prefers-reduced-motion: reduce) {
            .reveal { transition: none; opacity: 1; transform: none; }
            .device-float { animation: none; }
            html { scroll-behavior: auto; }
        }
    </style>
</head>

<body class="bg-ivory dark:bg-smoke text-smoke dark:text-ivory overflow-x-hidden">

    {{-- ═══════════════════════ NAVIGATION ═══════════════════════ --}}
    <nav class="fixed top-0 inset-x-0 z-50 glass-nav bg-ivory/80 dark:bg-smoke/80 border-b border-smoke/[0.04] dark:border-white/[0.04]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-[64px]">
                {{-- Logo --}}
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 cursor-pointer group">
                    <div class="w-8 h-8 rounded-[10px] bg-terra flex items-center justify-center shadow-sm transition-transform duration-200 group-hover:scale-105">
                        <svg class="w-[18px] h-[18px] text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z" />
                        </svg>
                    </div>
                    <span class="text-[15px] font-bold tracking-tight text-smoke dark:text-ivory">SKY Omada</span>
                </a>

                {{-- Center links (desktop) --}}
                <div class="hidden md:flex items-center gap-7">
                    <a href="#features" class="text-[13px] font-medium text-smoke/60 dark:text-ivory/55 hover:text-terra dark:hover:text-terra-light transition-colors duration-200 cursor-pointer">Features</a>
                    <a href="#how-it-works" class="text-[13px] font-medium text-smoke/60 dark:text-ivory/55 hover:text-terra dark:hover:text-terra-light transition-colors duration-200 cursor-pointer">How It Works</a>
                    <a href="#dashboard" class="text-[13px] font-medium text-smoke/60 dark:text-ivory/55 hover:text-terra dark:hover:text-terra-light transition-colors duration-200 cursor-pointer">Dashboard</a>
                    <a href="#hardware" class="text-[13px] font-medium text-smoke/60 dark:text-ivory/55 hover:text-terra dark:hover:text-terra-light transition-colors duration-200 cursor-pointer">Hardware</a>
                </div>

                {{-- Auth buttons --}}
                <div class="flex items-center gap-2.5">
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 text-[13px] font-semibold text-white bg-terra rounded-full hover:bg-terra-dark transition-colors duration-200 cursor-pointer shadow-sm">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex items-center px-4 py-2 text-[13px] font-medium text-smoke/70 dark:text-ivory/65 hover:text-terra dark:hover:text-terra-light transition-colors duration-200 cursor-pointer">
                            Sign In
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="inline-flex items-center px-4 py-2 text-[13px] font-semibold text-white bg-terra rounded-full hover:bg-terra-dark transition-colors duration-200 cursor-pointer shadow-sm shadow-terra/20">
                                Get Started
                            </a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    {{-- ═══════════════════════ HERO ═══════════════════════ --}}
    <section class="hero-gradient relative min-h-[100dvh] flex items-center pt-[64px]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20 lg:py-28">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">
                {{-- Left: Copy --}}
                <div class="max-w-xl space-y-7">
                    <div class="reveal">
                        <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-terra/[0.08] dark:bg-terra/[0.12] border border-terra/[0.12] dark:border-terra/[0.15]">
                            <span class="w-[6px] h-[6px] rounded-full bg-terra"></span>
                            <span class="text-[11px] font-semibold tracking-[0.04em] uppercase text-terra dark:text-terra-light">Powered by TP-Link Omada</span>
                        </span>
                    </div>

                    <h1 class="reveal reveal-delay-1 text-[clamp(2.5rem,5vw,4.5rem)] font-extrabold leading-[1.05] tracking-[-0.025em]">
                        Wi-Fi that<br>
                        <span class="text-terra dark:text-terra-light">pays for itself.</span>
                    </h1>

                    <p class="reveal reveal-delay-2 text-base sm:text-lg text-smoke/55 dark:text-ivory/50 leading-[1.7] max-w-md">
                        Transform TP-Link Omada access points into a fully automated revenue machine. Captive portal, mobile payments, real-time analytics — zero complexity.
                    </p>

                    <div class="reveal reveal-delay-3 flex flex-wrap items-center gap-3 pt-1">
                        @auth
                            <a href="{{ route('dashboard') }}" class="group inline-flex items-center gap-2 px-6 py-3.5 text-[14px] font-semibold text-white bg-terra rounded-full hover:bg-terra-dark transition-all duration-200 cursor-pointer shadow-lg shadow-terra/25 hover:shadow-terra/35 focus:outline-none focus:ring-2 focus:ring-terra focus:ring-offset-2 focus:ring-offset-ivory dark:focus:ring-offset-smoke">
                                Open Dashboard
                                <svg class="w-4 h-4 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="group inline-flex items-center gap-2 px-6 py-3.5 text-[14px] font-semibold text-white bg-terra rounded-full hover:bg-terra-dark transition-all duration-200 cursor-pointer shadow-lg shadow-terra/25 hover:shadow-terra/35 focus:outline-none focus:ring-2 focus:ring-terra focus:ring-offset-2 focus:ring-offset-ivory dark:focus:ring-offset-smoke">
                                Start Free
                                <svg class="w-4 h-4 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                            </a>
                            <a href="#features" class="inline-flex items-center px-6 py-3.5 text-[14px] font-medium text-smoke/70 dark:text-ivory/60 border border-smoke/10 dark:border-white/10 rounded-full hover:border-terra/30 hover:text-terra dark:hover:text-terra-light transition-all duration-200 cursor-pointer focus:outline-none focus:ring-2 focus:ring-terra/50">
                                Learn More
                            </a>
                        @endauth
                    </div>

                    <div class="reveal reveal-delay-4 flex flex-wrap items-center gap-5 pt-3 text-[13px] text-smoke/50 dark:text-ivory/40">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                            No hardware changes
                        </span>
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                            5-minute setup
                        </span>
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                            M-Pesa ready
                        </span>
                    </div>
                </div>

                {{-- Right: Device Hero Image --}}
                <div class="relative flex items-center justify-center lg:justify-end">
                    <div class="device-float relative z-10">
                        {{-- Colored backdrop so blend-mode reveals brand colors --}}
                        <div class="rounded-[32px] bg-gradient-to-br from-ivory via-ivory-dark/40 to-terra/[0.06] dark:from-smoke dark:via-smoke-light/60 dark:to-terra/[0.08] p-6 sm:p-8">
                            <img
                                src="/omada.webp"
                                alt="TP-Link Omada outdoor access point — enterprise Wi-Fi 6"
                                class="w-48 sm:w-56 lg:w-64 mx-auto mix-blend-multiply dark:mix-blend-luminosity dark:brightness-[1.8] dark:contrast-[0.9] drop-shadow-lg"
                                loading="eager"
                                width="512"
                                height="512"
                            >
                        </div>
                    </div>
                    {{-- Background glow --}}
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none" aria-hidden="true">
                        <div class="w-64 h-64 lg:w-80 lg:h-80 rounded-full bg-terra/[0.08] dark:bg-terra/[0.06] blur-3xl"></div>
                    </div>
                    {{-- Floating badge --}}
                    <div class="absolute bottom-2 left-1/2 -translate-x-1/2 lg:left-auto lg:right-6 lg:translate-x-0 px-4 py-2.5 rounded-2xl bg-white/90 dark:bg-smoke-light/90 border border-smoke/[0.06] dark:border-white/[0.08] shadow-lg backdrop-blur-sm">
                        <div class="flex items-center gap-2.5">
                            <div class="w-2.5 h-2.5 rounded-full bg-emerald-400 shadow-sm shadow-emerald-400/50"></div>
                            <span class="text-[12px] font-semibold text-smoke dark:text-ivory tracking-tight">Omada AP — Wi-Fi 6</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════ METRICS BAR ═══════════════════════ --}}
    <section class="py-16 sm:py-20 border-y border-smoke/[0.04] dark:border-white/[0.04] bg-white/30 dark:bg-smoke-light/[0.08]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 sm:gap-12">
                <div class="reveal text-center space-y-1.5">
                    <div class="text-3xl sm:text-4xl font-extrabold tracking-tight text-terra dark:text-terra-light">{{ number_format((int) ($publicSnapshot['total_workspaces'] ?? 0)) }}</div>
                    <div class="text-[12px] font-medium text-smoke/45 dark:text-ivory/40 uppercase tracking-wider">Workspaces</div>
                </div>
                <div class="reveal reveal-delay-1 text-center space-y-1.5">
                    <div class="text-3xl sm:text-4xl font-extrabold tracking-tight text-terra dark:text-terra-light">{{ number_format((int) ($publicSnapshot['total_devices'] ?? 0)) }}</div>
                    <div class="text-[12px] font-medium text-smoke/45 dark:text-ivory/40 uppercase tracking-wider">Devices</div>
                </div>
                <div class="reveal reveal-delay-2 text-center space-y-1.5">
                    <div class="text-3xl sm:text-4xl font-extrabold tracking-tight text-terra dark:text-terra-light">{{ number_format((int) ($publicSnapshot['active_sessions'] ?? 0)) }}</div>
                    <div class="text-[12px] font-medium text-smoke/45 dark:text-ivory/40 uppercase tracking-wider">Active Sessions</div>
                </div>
                <div class="reveal reveal-delay-3 text-center space-y-1.5">
                    <div class="text-3xl sm:text-4xl font-extrabold tracking-tight text-terra dark:text-terra-light">{{ $publicSnapshot['revenue_today'] ?? '0' }} <span class="text-sm font-semibold text-smoke/35 dark:text-ivory/30">TZS</span></div>
                    <div class="text-[12px] font-medium text-smoke/45 dark:text-ivory/40 uppercase tracking-wider">Revenue Today</div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════ FEATURES ═══════════════════════ --}}
    <section id="features" class="py-24 lg:py-32">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Section header --}}
            <div class="reveal text-center max-w-2xl mx-auto mb-16 lg:mb-20">
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-terra dark:text-terra-light mb-4">Features</p>
                <h2 class="text-3xl sm:text-4xl lg:text-[2.75rem] font-extrabold tracking-tight leading-tight">Everything you need to monetize Wi-Fi</h2>
                <p class="mt-5 text-base text-smoke/50 dark:text-ivory/45 leading-relaxed">From captive portal to payment collection — a complete ecosystem for your hotspot business.</p>
            </div>

            {{-- Feature grid --}}
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">

                {{-- 1. Captive Portal --}}
                <div class="reveal group p-7 rounded-[20px] bg-white/70 dark:bg-smoke-light/30 border border-smoke/[0.06] dark:border-white/[0.06] hover:border-terra/20 dark:hover:border-terra/20 hover:shadow-lg hover:shadow-terra/[0.04] transition-all duration-250 cursor-default">
                    <div class="w-11 h-11 rounded-[12px] bg-terra/[0.08] dark:bg-terra/[0.12] flex items-center justify-center mb-5">
                        <svg class="w-5 h-5 text-terra dark:text-terra-light" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                        </svg>
                    </div>
                    <h3 class="text-[17px] font-bold mb-2 text-smoke dark:text-ivory">Captive Portal</h3>
                    <p class="text-[13px] leading-[1.7] text-smoke/55 dark:text-ivory/45">Branded login page for guests. Pay via mobile money, get instant internet — no manual intervention required.</p>
                </div>

                {{-- 2. Mobile Payments --}}
                <div class="reveal reveal-delay-1 group p-7 rounded-[20px] bg-white/70 dark:bg-smoke-light/30 border border-smoke/[0.06] dark:border-white/[0.06] hover:border-terra/20 dark:hover:border-terra/20 hover:shadow-lg hover:shadow-terra/[0.04] transition-all duration-250 cursor-default">
                    <div class="w-11 h-11 rounded-[12px] bg-terra/[0.08] dark:bg-terra/[0.12] flex items-center justify-center mb-5">
                        <svg class="w-5 h-5 text-terra dark:text-terra-light" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                        </svg>
                    </div>
                    <h3 class="text-[17px] font-bold mb-2 text-smoke dark:text-ivory">Mobile Money</h3>
                    <p class="text-[13px] leading-[1.7] text-smoke/55 dark:text-ivory/45">ClickPesa gateway with M-Pesa, Tigo Pesa & Airtel Money. Customers pay from their phone — funds settle to your wallet.</p>
                </div>

                {{-- 3. Omada Device Sync --}}
                <div class="reveal reveal-delay-2 group p-7 rounded-[20px] bg-white/70 dark:bg-smoke-light/30 border border-smoke/[0.06] dark:border-white/[0.06] hover:border-terra/20 dark:hover:border-terra/20 hover:shadow-lg hover:shadow-terra/[0.04] transition-all duration-250 cursor-default">
                    <div class="w-11 h-11 rounded-[12px] bg-terra/[0.08] dark:bg-terra/[0.12] flex items-center justify-center mb-5">
                        <svg class="w-5 h-5 text-terra dark:text-terra-light" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.7L5.737 5.1a3.375 3.375 0 012.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 01.9 2.7m0 0a3 3 0 01-3 3m0 3h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008zm-3 6h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008z" />
                        </svg>
                    </div>
                    <h3 class="text-[17px] font-bold mb-2 text-smoke dark:text-ivory">Omada Device Sync</h3>
                    <p class="text-[13px] leading-[1.7] text-smoke/55 dark:text-ivory/45">Auto-discover, adopt & monitor access points. Real-time status, client count, and signal strength for every AP.</p>
                </div>

                {{-- 4. Live Sessions --}}
                <div class="reveal group p-7 rounded-[20px] bg-white/70 dark:bg-smoke-light/30 border border-smoke/[0.06] dark:border-white/[0.06] hover:border-terra/20 dark:hover:border-terra/20 hover:shadow-lg hover:shadow-terra/[0.04] transition-all duration-250 cursor-default">
                    <div class="w-11 h-11 rounded-[12px] bg-terra/[0.08] dark:bg-terra/[0.12] flex items-center justify-center mb-5">
                        <svg class="w-5 h-5 text-terra dark:text-terra-light" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.148 2.148A12.061 12.061 0 0116.5 7.605" />
                        </svg>
                    </div>
                    <h3 class="text-[17px] font-bold mb-2 text-smoke dark:text-ivory">Live Session Tracking</h3>
                    <p class="text-[13px] leading-[1.7] text-smoke/55 dark:text-ivory/45">See who's connected right now, data usage, session duration — automatic disconnection when time expires.</p>
                </div>

                {{-- 5. Client Intelligence --}}
                <div class="reveal reveal-delay-1 group p-7 rounded-[20px] bg-white/70 dark:bg-smoke-light/30 border border-smoke/[0.06] dark:border-white/[0.06] hover:border-terra/20 dark:hover:border-terra/20 hover:shadow-lg hover:shadow-terra/[0.04] transition-all duration-250 cursor-default">
                    <div class="w-11 h-11 rounded-[12px] bg-terra/[0.08] dark:bg-terra/[0.12] flex items-center justify-center mb-5">
                        <svg class="w-5 h-5 text-terra dark:text-terra-light" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                        </svg>
                    </div>
                    <h3 class="text-[17px] font-bold mb-2 text-smoke dark:text-ivory">Client Intelligence</h3>
                    <p class="text-[13px] leading-[1.7] text-smoke/55 dark:text-ivory/45">Track returning customers, device fingerprints, spending patterns — optimize your pricing plans with data.</p>
                </div>

                {{-- 6. Workspace Wallet --}}
                <div class="reveal reveal-delay-2 group p-7 rounded-[20px] bg-white/70 dark:bg-smoke-light/30 border border-smoke/[0.06] dark:border-white/[0.06] hover:border-terra/20 dark:hover:border-terra/20 hover:shadow-lg hover:shadow-terra/[0.04] transition-all duration-250 cursor-default">
                    <div class="w-11 h-11 rounded-[12px] bg-terra/[0.08] dark:bg-terra/[0.12] flex items-center justify-center mb-5">
                        <svg class="w-5 h-5 text-terra dark:text-terra-light" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 110-6h.75a2.25 2.25 0 002.25-2.25V3m-16.5 0v.75A2.25 2.25 0 003.75 6H6a3 3 0 010 6H3.75a2.25 2.25 0 00-2.25 2.25V21m16.5 0v-.75a2.25 2.25 0 00-2.25-2.25H15a3 3 0 010-6h.75a2.25 2.25 0 002.25-2.25V3m-16.5 18h.75a2.25 2.25 0 002.25-2.25V15a3 3 0 016 0v.75a2.25 2.25 0 002.25 2.25H21" />
                        </svg>
                    </div>
                    <h3 class="text-[17px] font-bold mb-2 text-smoke dark:text-ivory">Workspace Wallet</h3>
                    <p class="text-[13px] leading-[1.7] text-smoke/55 dark:text-ivory/45">Automatic revenue settlement. Request withdrawals, real-time balance tracking, and lifetime earnings overview.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════ HOW IT WORKS ═══════════════════════ --}}
    <section id="how-it-works" class="py-24 lg:py-32 bg-white/40 dark:bg-smoke-light/[0.06] border-y border-smoke/[0.04] dark:border-white/[0.04]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="reveal text-center max-w-2xl mx-auto mb-16 lg:mb-20">
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-terra dark:text-terra-light mb-4">How It Works</p>
                <h2 class="text-3xl sm:text-4xl lg:text-[2.75rem] font-extrabold tracking-tight leading-tight">Three steps to revenue</h2>
                <p class="mt-5 text-base text-smoke/50 dark:text-ivory/45 leading-relaxed">Get your Wi-Fi business running in minutes, not days.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8 lg:gap-12">
                <div class="reveal text-center space-y-4">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-terra/[0.08] dark:bg-terra/[0.12] border border-terra/[0.1] flex items-center justify-center">
                        <span class="text-xl font-extrabold text-terra dark:text-terra-light">1</span>
                    </div>
                    <h3 class="text-lg font-bold text-smoke dark:text-ivory">Connect Omada Controller</h3>
                    <p class="text-[13px] text-smoke/50 dark:text-ivory/45 leading-relaxed max-w-xs mx-auto">Link your TP-Link Omada Controller via the Open API. We auto-discover all your access points and sites.</p>
                </div>
                <div class="reveal reveal-delay-1 text-center space-y-4">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-terra/[0.08] dark:bg-terra/[0.12] border border-terra/[0.1] flex items-center justify-center">
                        <span class="text-xl font-extrabold text-terra dark:text-terra-light">2</span>
                    </div>
                    <h3 class="text-lg font-bold text-smoke dark:text-ivory">Set Up Plans & Payment</h3>
                    <p class="text-[13px] text-smoke/50 dark:text-ivory/45 leading-relaxed max-w-xs mx-auto">Create Wi-Fi plans (hourly, daily, weekly) and connect ClickPesa for mobile money collections.</p>
                </div>
                <div class="reveal reveal-delay-2 text-center space-y-4">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-terra/[0.08] dark:bg-terra/[0.12] border border-terra/[0.1] flex items-center justify-center">
                        <span class="text-xl font-extrabold text-terra dark:text-terra-light">3</span>
                    </div>
                    <h3 class="text-lg font-bold text-smoke dark:text-ivory">Share Your Portal Link</h3>
                    <p class="text-[13px] text-smoke/50 dark:text-ivory/45 leading-relaxed max-w-xs mx-auto">Guests connect to Wi-Fi, land on the portal, pay via M-Pesa, and get instant access. You earn automatically.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════ DASHBOARD PREVIEW ═══════════════════════ --}}
    <section id="dashboard" class="py-24 lg:py-32">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="reveal text-center max-w-2xl mx-auto mb-14">
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-terra dark:text-terra-light mb-4">Dashboard</p>
                <h2 class="text-3xl sm:text-4xl lg:text-[2.75rem] font-extrabold tracking-tight leading-tight">Your business at a glance</h2>
                <p class="mt-5 text-base text-smoke/50 dark:text-ivory/45 leading-relaxed">A real-time workspace console — online users, revenue, devices, and payments in one beautiful view.</p>
            </div>

            {{-- Browser mockup --}}
            <div class="reveal rounded-[24px] overflow-hidden dashboard-glow">
                {{-- Titlebar --}}
                <div class="bg-smoke dark:bg-[#1a181a] px-5 py-3.5 flex items-center">
                    <div class="flex gap-[7px]">
                        <div class="w-[11px] h-[11px] rounded-full bg-[#ff5f57]"></div>
                        <div class="w-[11px] h-[11px] rounded-full bg-[#febc2e]"></div>
                        <div class="w-[11px] h-[11px] rounded-full bg-[#28c840]"></div>
                    </div>
                    <div class="flex-1 flex justify-center">
                        <div class="px-4 py-1 rounded-md bg-smoke-light/80 dark:bg-white/[0.06] text-ivory/50 text-[11px] font-mono">skyomadawifi.com/dashboard</div>
                    </div>
                    <div class="w-[62px]"></div>
                </div>

                {{-- Dashboard content --}}
                <div class="bg-ivory dark:bg-smoke p-6 sm:p-10 lg:p-12">
                    {{-- Header --}}
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-8">
                        <div>
                            <div class="flex items-center gap-2 mb-2.5">
                                @if($workspaceSnapshot)
                                    <span class="px-2 py-[3px] rounded-md bg-sky-100 dark:bg-sky-900/30 text-sky-700 dark:text-sky-300 text-[10px] font-bold uppercase tracking-wider">Your Workspace</span>
                                    @php($provisioning = $workspaceSnapshot['workspace']->provisioningSummary())
                                    <span class="px-2 py-[3px] rounded-md bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 text-[10px] font-bold uppercase tracking-wider">
                                        {{ $provisioning['status'] === 'ready' ? 'Portal Ready' : ucfirst($provisioning['status']) }}
                                    </span>
                                @else
                                    <span class="px-2 py-[3px] rounded-md bg-sky-100 dark:bg-sky-900/30 text-sky-700 dark:text-sky-300 text-[10px] font-bold uppercase tracking-wider">Live Snapshot</span>
                                    <span class="px-2 py-[3px] rounded-md bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 text-[10px] font-bold uppercase tracking-wider">Sign in for details</span>
                                @endif
                            </div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.15em] text-terra dark:text-terra-light">
                                {{ $workspaceSnapshot ? $workspaceSnapshot['workspace']->brand_name : config('app.name') }}
                            </p>
                            <h3 class="text-xl sm:text-2xl font-extrabold text-smoke dark:text-ivory mt-1 tracking-tight">
                                {{ $workspaceSnapshot ? $workspaceSnapshot['workspace']->brand_name : 'Platform Overview' }}
                            </h3>
                            <p class="text-[12px] text-smoke/45 dark:text-ivory/40 mt-1.5 max-w-md">Run your Wi-Fi business from one place: devices, customers, sessions, billing history, and wallet balance.</p>
                        </div>
                        <div class="flex gap-3">
                            <div class="px-4 py-3 rounded-2xl bg-white/80 dark:bg-smoke-light/50 border border-smoke/[0.05] dark:border-white/[0.06]">
                                <div class="text-[10px] font-medium text-smoke/40 dark:text-ivory/35 uppercase tracking-wider">Updated</div>
                                <div class="text-lg font-bold text-smoke dark:text-ivory mt-0.5">{{ now()->format('H:i') }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Stats grid --}}
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-8">
                        <div class="p-4 rounded-2xl bg-white/80 dark:bg-smoke-light/40 border border-smoke/[0.04] dark:border-white/[0.05]">
                            <div class="text-[10px] font-semibold text-smoke/40 dark:text-ivory/35 uppercase tracking-wider">Online</div>
                            <div class="text-2xl font-extrabold text-smoke dark:text-ivory mt-1.5 tracking-tight">{{ $workspaceSnapshot['online_users'] ?? ($publicSnapshot['active_sessions'] ?? 0) }}</div>
                            <div class="text-[10px] text-smoke/35 dark:text-ivory/30 mt-0.5">{{ $workspaceSnapshot ? 'Active guests' : 'Active sessions' }}</div>
                        </div>
                        <div class="p-4 rounded-2xl bg-white/80 dark:bg-smoke-light/40 border border-smoke/[0.04] dark:border-white/[0.05]">
                            <div class="text-[10px] font-semibold text-smoke/40 dark:text-ivory/35 uppercase tracking-wider">Devices</div>
                            <div class="text-2xl font-extrabold text-smoke dark:text-ivory mt-1.5 tracking-tight">
                                {{ $workspaceSnapshot['online_devices'] ?? 0 }}
                                @if($workspaceSnapshot)
                                    <span class="text-xs font-normal text-smoke/30 dark:text-ivory/25">/ {{ $workspaceSnapshot['total_devices'] ?? 0 }}</span>
                                @endif
                            </div>
                            <div class="text-[10px] text-smoke/35 dark:text-ivory/30 mt-0.5">{{ $workspaceSnapshot ? 'Online APs' : 'Total devices' }}</div>
                        </div>
                        <div class="p-4 rounded-2xl bg-white/80 dark:bg-smoke-light/40 border border-smoke/[0.04] dark:border-white/[0.05]">
                            <div class="text-[10px] font-semibold text-smoke/40 dark:text-ivory/35 uppercase tracking-wider">Clients</div>
                            <div class="text-2xl font-extrabold text-smoke dark:text-ivory mt-1.5 tracking-tight">
                                {{ $workspaceSnapshot['active_clients'] ?? 0 }}
                                @if($workspaceSnapshot)
                                    <span class="text-xs font-normal text-smoke/30 dark:text-ivory/25">/ {{ $workspaceSnapshot['total_clients'] ?? 0 }}</span>
                                @endif
                            </div>
                            <div class="text-[10px] text-smoke/35 dark:text-ivory/30 mt-0.5">{{ $workspaceSnapshot ? 'Active' : 'Sign in for client metrics' }}</div>
                        </div>
                        <div class="p-4 rounded-2xl bg-white/80 dark:bg-smoke-light/40 border border-smoke/[0.04] dark:border-white/[0.05]">
                            <div class="text-[10px] font-semibold text-smoke/40 dark:text-ivory/35 uppercase tracking-wider">Sessions</div>
                            <div class="text-2xl font-extrabold text-smoke dark:text-ivory mt-1.5 tracking-tight">{{ $workspaceSnapshot['sessions_today'] ?? 0 }}</div>
                            <div class="text-[10px] text-smoke/35 dark:text-ivory/30 mt-0.5">{{ $workspaceSnapshot ? 'Today' : 'Sign in for workspace stats' }}</div>
                        </div>
                        <div class="p-4 rounded-2xl bg-white/80 dark:bg-smoke-light/40 border border-smoke/[0.04] dark:border-white/[0.05]">
                            <div class="text-[10px] font-semibold text-smoke/40 dark:text-ivory/35 uppercase tracking-wider">Revenue</div>
                            <div class="text-2xl font-extrabold text-smoke dark:text-ivory mt-1.5 tracking-tight">
                                {{ $workspaceSnapshot['revenue_today'] ?? ($publicSnapshot['revenue_today'] ?? '0') }}
                                <span class="text-[10px] font-normal text-smoke/30 dark:text-ivory/25">TZS</span>
                            </div>
                            <div class="text-[10px] text-smoke/35 dark:text-ivory/30 mt-0.5">Today</div>
                        </div>
                        <div class="p-4 rounded-2xl bg-white/80 dark:bg-smoke-light/40 border border-smoke/[0.04] dark:border-white/[0.05]">
                            <div class="text-[10px] font-semibold text-smoke/40 dark:text-ivory/35 uppercase tracking-wider">Month</div>
                            <div class="text-2xl font-extrabold text-smoke dark:text-ivory mt-1.5 tracking-tight">
                                {{ $workspaceSnapshot['revenue_month'] ?? '—' }}
                                <span class="text-[10px] font-normal text-smoke/30 dark:text-ivory/25">TZS</span>
                            </div>
                            <div class="text-[10px] text-smoke/35 dark:text-ivory/30 mt-0.5">Revenue</div>
                        </div>
                    </div>

                    {{-- Cards row --}}
                    @if($workspaceSnapshot)
                        <div class="grid sm:grid-cols-3 gap-4">
                        {{-- Devices card --}}
                        <div class="p-5 rounded-2xl bg-white/80 dark:bg-smoke-light/40 border border-smoke/[0.04] dark:border-white/[0.05]">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-[10px] font-bold text-smoke/40 dark:text-ivory/35 uppercase tracking-wider">Devices</span>
                                <span class="text-[11px] text-terra dark:text-terra-light font-medium">View all →</span>
                            </div>
                            <div class="space-y-2.5">
                                @forelse($workspaceSnapshot['recent_devices'] as $device)
                                    <div class="p-3 rounded-xl bg-ivory/80 dark:bg-smoke/50 flex items-center justify-between">
                                        <div>
                                            <div class="text-[13px] font-semibold text-smoke dark:text-ivory">{{ $device->name ?: 'Unnamed device' }}</div>
                                            <div class="text-[10px] text-smoke/35 dark:text-ivory/30 font-mono mt-0.5">{{ $device->model ?: '—' }}</div>
                                        </div>
                                        @php($isOnline = $device->status === 'online')
                                        <span class="px-2 py-[2px] rounded-md {{ $isOnline ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300' : 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300' }} text-[10px] font-bold">
                                            {{ ucfirst($device->status ?: 'unknown') }}
                                        </span>
                                    </div>
                                @empty
                                    <div class="p-3 rounded-xl bg-ivory/80 dark:bg-smoke/50">
                                        <div class="text-[13px] font-semibold text-smoke dark:text-ivory">No devices yet</div>
                                        <div class="text-[10px] text-smoke/35 dark:text-ivory/30 mt-0.5">Connect your Omada controller to sync devices.</div>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        {{-- Payments card --}}
                        <div class="p-5 rounded-2xl bg-white/80 dark:bg-smoke-light/40 border border-smoke/[0.04] dark:border-white/[0.05]">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-[10px] font-bold text-smoke/40 dark:text-ivory/35 uppercase tracking-wider">Payments</span>
                                <span class="text-[11px] text-terra dark:text-terra-light font-medium">View all →</span>
                            </div>
                            <div class="space-y-2.5">
                                @forelse($workspaceSnapshot['recent_payments'] as $payment)
                                    @php($phone = (string) ($payment->phone_number ?? ''))
                                    <div class="p-3 rounded-xl bg-ivory/80 dark:bg-smoke/50 flex items-center justify-between">
                                        <div>
                                            <div class="text-[13px] font-semibold text-smoke dark:text-ivory">{{ number_format((float) $payment->amount, 0) }} TZS</div>
                                            <div class="text-[10px] text-smoke/35 dark:text-ivory/30 mt-0.5">
                                                {{ $phone !== '' ? (substr($phone, 0, 6).'****'.substr($phone, -2)) : '—' }}
                                            </div>
                                        </div>
                                        <span class="px-2 py-[2px] rounded-md bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 text-[10px] font-bold">Paid</span>
                                    </div>
                                @empty
                                    <div class="p-3 rounded-xl bg-ivory/80 dark:bg-smoke/50">
                                        <div class="text-[13px] font-semibold text-smoke dark:text-ivory">No payments yet</div>
                                        <div class="text-[10px] text-smoke/35 dark:text-ivory/30 mt-0.5">Once guests pay, you&rsquo;ll see them here.</div>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        {{-- Wallet card --}}
                        <div class="p-5 rounded-2xl bg-white/80 dark:bg-smoke-light/40 border border-smoke/[0.04] dark:border-white/[0.05]">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-[10px] font-bold text-smoke/40 dark:text-ivory/35 uppercase tracking-wider">Wallet</span>
                                <span class="text-[11px] text-terra dark:text-terra-light font-medium">Open →</span>
                            </div>
                            <div class="space-y-2.5">
                                <div class="p-3 rounded-xl bg-emerald-50/80 dark:bg-emerald-950/20 border border-emerald-200/50 dark:border-emerald-800/30">
                                    <div class="text-[10px] text-smoke/40 dark:text-ivory/35 font-medium">Available balance</div>
                                    <div class="text-lg font-extrabold text-emerald-700 dark:text-emerald-300 mt-0.5 tracking-tight">{{ $workspaceSnapshot['available_wallet_balance'] ?? '0' }} TZS</div>
                                </div>
                                <div class="p-3 rounded-xl bg-ivory/80 dark:bg-smoke/50">
                                    <div class="text-[11px] text-smoke/40 dark:text-ivory/35">Wallet is ready for withdrawals</div>
                                </div>
                            </div>
                        </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════ HARDWARE ═══════════════════════ --}}
    <section id="hardware" class="py-24 lg:py-32 bg-white/40 dark:bg-smoke-light/[0.06] border-y border-smoke/[0.04] dark:border-white/[0.04]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">
                {{-- Left: Info --}}
                <div class="space-y-8 max-w-lg">
                    <div class="reveal">
                        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-terra dark:text-terra-light mb-4">Hardware</p>
                        <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight leading-tight">Built for TP-Link Omada</h2>
                        <p class="mt-5 text-base text-smoke/50 dark:text-ivory/45 leading-relaxed">
                            Integrates natively with the TP-Link Omada SDN ecosystem. Enterprise-grade hardware, cloud-managed simplicity.
                        </p>
                    </div>

                    <div class="space-y-5">
                        <div class="reveal flex items-start gap-4">
                            <div class="mt-0.5 w-9 h-9 rounded-xl bg-terra/[0.08] dark:bg-terra/[0.12] flex items-center justify-center shrink-0">
                                <svg class="w-[18px] h-[18px] text-terra dark:text-terra-light" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.348 14.651a3.75 3.75 0 010-5.303m5.304 0a3.75 3.75 0 010 5.303m-7.425 2.122a6.75 6.75 0 010-9.546m9.546 0a6.75 6.75 0 010 9.546M5.106 18.894c-3.808-3.808-3.808-9.98 0-13.789m13.788 0c3.808 3.808 3.808 9.981 0 13.79M12 12.75h.008v.008H12v-.008z" /></svg>
                            </div>
                            <div>
                                <h4 class="text-[15px] font-bold text-smoke dark:text-ivory">EAP Series Access Points</h4>
                                <p class="text-[13px] text-smoke/50 dark:text-ivory/45 mt-1 leading-relaxed">Wi-Fi 6 & 6E ceiling-mount APs with seamless roaming, band steering, and up to 6000 Mbps.</p>
                            </div>
                        </div>
                        <div class="reveal reveal-delay-1 flex items-start gap-4">
                            <div class="mt-0.5 w-9 h-9 rounded-xl bg-terra/[0.08] dark:bg-terra/[0.12] flex items-center justify-center shrink-0">
                                <svg class="w-[18px] h-[18px] text-terra dark:text-terra-light" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15a4.5 4.5 0 004.5 4.5H18a3.75 3.75 0 001.332-7.257 3 3 0 00-3.758-3.848 5.25 5.25 0 00-10.233 2.33A4.502 4.502 0 002.25 15z" /></svg>
                            </div>
                            <div>
                                <h4 class="text-[15px] font-bold text-smoke dark:text-ivory">Cloud-Managed via Open API</h4>
                                <p class="text-[13px] text-smoke/50 dark:text-ivory/45 mt-1 leading-relaxed">Full integration through Omada's REST API. Auto-discover, adopt remotely, sync client data.</p>
                            </div>
                        </div>
                        <div class="reveal reveal-delay-2 flex items-start gap-4">
                            <div class="mt-0.5 w-9 h-9 rounded-xl bg-terra/[0.08] dark:bg-terra/[0.12] flex items-center justify-center shrink-0">
                                <svg class="w-[18px] h-[18px] text-terra dark:text-terra-light" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                            </div>
                            <div>
                                <h4 class="text-[15px] font-bold text-smoke dark:text-ivory">Enterprise Security</h4>
                                <p class="text-[13px] text-smoke/50 dark:text-ivory/45 mt-1 leading-relaxed">WPA3, VLAN isolation, captive portal auth, per-client rate limiting — configured automatically.</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right: Device image --}}
                <div class="reveal relative flex items-center justify-center">
                    <div class="relative z-10">
                        <div class="rounded-[32px] bg-gradient-to-br from-ivory via-ivory-dark/40 to-terra/[0.06] dark:from-smoke dark:via-smoke-light/60 dark:to-terra/[0.08] p-8 sm:p-10">
                            <img
                                src="/omada.webp"
                                alt="TP-Link Omada outdoor access point — enterprise-grade hardware"
                                class="w-52 sm:w-64 lg:w-72 mx-auto mix-blend-multiply dark:mix-blend-luminosity dark:brightness-[1.8] dark:contrast-[0.9] drop-shadow-lg"
                                loading="lazy"
                                width="576"
                                height="576"
                            >
                        </div>
                    </div>
                    {{-- Background blur --}}
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none" aria-hidden="true">
                        <div class="w-56 h-56 lg:w-72 lg:h-72 rounded-full bg-terra/[0.06] dark:bg-terra/[0.04] blur-3xl"></div>
                    </div>
                    {{-- Floating specs --}}
                    <div class="absolute top-6 right-2 lg:right-0 px-3.5 py-2 rounded-xl bg-white/90 dark:bg-smoke-light/90 border border-smoke/[0.05] dark:border-white/[0.06] shadow-md backdrop-blur-sm">
                        <div class="text-[10px] font-medium text-smoke/40 dark:text-ivory/35">Wi-Fi 6</div>
                        <div class="text-[13px] font-bold text-smoke dark:text-ivory">AX5400</div>
                    </div>
                    <div class="absolute bottom-6 left-2 lg:left-4 px-3.5 py-2 rounded-xl bg-white/90 dark:bg-smoke-light/90 border border-smoke/[0.05] dark:border-white/[0.06] shadow-md backdrop-blur-sm">
                        <div class="text-[10px] font-medium text-smoke/40 dark:text-ivory/35">PoE Powered</div>
                        <div class="text-[13px] font-bold text-smoke dark:text-ivory">802.3at</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════ FINAL CTA ═══════════════════════ --}}
    <section class="py-24 lg:py-32">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="reveal relative p-10 sm:p-14 lg:p-16 rounded-[28px] bg-smoke dark:bg-smoke-light overflow-hidden">
                {{-- Decorative blobs --}}
                <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
                    <div class="absolute -top-20 -right-20 w-72 h-72 rounded-full bg-terra/[0.12] blur-3xl"></div>
                    <div class="absolute -bottom-16 -left-16 w-56 h-56 rounded-full bg-terra-light/[0.08] blur-3xl"></div>
                </div>

                <div class="relative text-center space-y-7">
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-ivory tracking-tight leading-tight">Ready to monetize your Wi-Fi?</h2>
                    <p class="text-[15px] text-ivory/50 max-w-xl mx-auto leading-relaxed">
                        Join venue owners across Tanzania turning TP-Link Omada access points into automated income streams.
                    </p>
                    <div class="flex flex-wrap justify-center gap-3 pt-2">
                        @auth
                            <a href="{{ route('dashboard') }}" class="group inline-flex items-center gap-2 px-7 py-3.5 text-[14px] font-semibold text-smoke bg-ivory rounded-full hover:bg-white transition-all duration-200 cursor-pointer shadow-lg focus:outline-none focus:ring-2 focus:ring-ivory focus:ring-offset-2 focus:ring-offset-smoke">
                                Go to Dashboard
                                <svg class="w-4 h-4 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="group inline-flex items-center gap-2 px-7 py-3.5 text-[14px] font-semibold text-smoke bg-ivory rounded-full hover:bg-white transition-all duration-200 cursor-pointer shadow-lg focus:outline-none focus:ring-2 focus:ring-ivory focus:ring-offset-2 focus:ring-offset-smoke">
                                Create Your Account
                                <svg class="w-4 h-4 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                            </a>
                            <a href="{{ route('login') }}" class="inline-flex items-center px-7 py-3.5 text-[14px] font-medium text-ivory/70 border border-ivory/15 rounded-full hover:border-ivory/30 hover:text-ivory transition-all duration-200 cursor-pointer focus:outline-none focus:ring-2 focus:ring-ivory/30">
                                Sign In
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════ FOOTER ═══════════════════════ --}}
    <footer class="py-10 border-t border-smoke/[0.04] dark:border-white/[0.04]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-5">
                <a href="{{ route('home') }}" class="flex items-center gap-2 cursor-pointer group">
                    <div class="w-7 h-7 rounded-[8px] bg-terra flex items-center justify-center transition-transform duration-200 group-hover:scale-105">
                        <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z" />
                        </svg>
                    </div>
                    <span class="text-[13px] font-bold text-smoke dark:text-ivory tracking-tight">SKY Omada WiFi</span>
                </a>

                <div class="flex items-center gap-6 text-[12px] font-medium text-smoke/40 dark:text-ivory/35">
                    <a href="#features" class="hover:text-terra dark:hover:text-terra-light transition-colors duration-200 cursor-pointer">Features</a>
                    <a href="#how-it-works" class="hover:text-terra dark:hover:text-terra-light transition-colors duration-200 cursor-pointer">How It Works</a>
                    <a href="#hardware" class="hover:text-terra dark:hover:text-terra-light transition-colors duration-200 cursor-pointer">Hardware</a>
                </div>

                <p class="text-[11px] text-smoke/30 dark:text-ivory/25">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </footer>

    {{-- Scroll reveal script --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const reveals = document.querySelectorAll('.reveal');
            if (!reveals.length) return;

            // Respect reduced motion
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                reveals.forEach(el => el.classList.add('visible'));
                return;
            }

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

            reveals.forEach(el => observer.observe(el));
        });
    </script>
</body>
</html>
