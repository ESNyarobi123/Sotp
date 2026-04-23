@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body
        class="min-h-screen bg-ivory motion-safe:transition-colors dark:bg-smoke"
        x-data="{
            expanded: JSON.parse(localStorage.getItem('sb-expanded') ?? 'true'),
            mobileOpen: false,
            toggle() { this.expanded = !this.expanded; localStorage.setItem('sb-expanded', JSON.stringify(this.expanded)); }
        }"
    >
        <div class="flex min-h-screen">

            {{-- ========== DESKTOP SIDEBAR ========== --}}
            <aside
                class="fixed inset-y-0 left-0 z-40 hidden flex-col border-r border-white/5 bg-gradient-to-b from-smoke via-smoke to-smoke-light/95 shadow-[4px_0_24px_-4px_rgba(0,0,0,0.45)] transition-[width] duration-300 ease-out motion-reduce:transition-none lg:flex"
                :style="expanded ? 'width:240px' : 'width:56px'"
            >
                {{-- Logo / Brand --}}
                <div class="flex h-14 shrink-0 items-center border-b border-white/10 bg-black/10 overflow-hidden backdrop-blur-sm" :class="expanded ? 'gap-2 px-3' : 'justify-center px-1'">
                    <a href="{{ route('dashboard') }}" wire:navigate class="group/brand flex min-w-0 items-center gap-2.5 rounded-xl py-1 outline-none focus-visible:ring-2 focus-visible:ring-terra/60 focus-visible:ring-offset-2 focus-visible:ring-offset-smoke" :class="expanded ? 'flex-1' : ''">
                        <div class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-terra to-terra-dark text-white shadow-md shadow-terra/25 ring-1 ring-white/10 transition group-hover/brand:shadow-terra/40">
                            <flux:icon name="router" class="size-4" />
                        </div>
                        <div class="min-w-0 overflow-hidden transition-all duration-200" :class="expanded ? 'opacity-100 max-w-full' : 'opacity-0 max-w-0'">
                            <div class="truncate text-[13px] font-bold tracking-tight text-ivory leading-tight">SKY Omada</div>
                        </div>
                    </a>
                    <button @click="toggle()" type="button" class="shrink-0 rounded-lg p-1.5 text-ivory/45 transition hover:bg-white/10 hover:text-ivory focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-terra/40 cursor-pointer" :class="expanded ? '' : 'hidden'" x-bind:title="expanded ? @js(__('Collapse sidebar')) : @js(__('Expand sidebar'))">
                        <flux:icon name="panel-left-close" class="size-4" x-show="expanded" x-cloak />
                        <flux:icon name="panel-left-open" class="size-4" x-show="! expanded" x-cloak />
                    </button>
                </div>

                {{-- Navigation --}}
                <nav class="flex-1 overflow-y-auto overflow-x-hidden py-2 space-y-px" style="scrollbar-width:none;-ms-overflow-style:none" x-bind:style="'scrollbar-width:none'">

                    @php
                        $isAdmin = auth()->user()->hasRole('admin');
                        $workspace = auth()->user()->workspace;

                        $overview = [
                            ['route' => 'dashboard', 'label' => __('Dashboard'), 'icon' => 'chart-bar-square', 'params' => []],
                        ];
                        if ($workspace) {
                            $overview[] = [
                                'route' => 'portal.workspace',
                                'params' => ['workspace' => $workspace->public_slug],
                                'label' => __('Guest portal'),
                                'icon' => 'wifi',
                            ];
                        }

                        $management = [
                            ['route' => 'admin.clients', 'label' => __('Clients'), 'icon' => 'user-group', 'params' => []],
                        ];

                        $navItems = [
                            'overview' => $overview,
                            'network' => [
                                ['route' => 'admin.sessions', 'label' => __('Sessions'), 'icon' => 'users', 'params' => []],
                                ['route' => 'admin.devices', 'label' => __('Devices (APs)'), 'icon' => 'server-stack', 'params' => []],
                            ],
                            'billing' => [
                                ['route' => 'admin.payments', 'label' => __('Payments'), 'icon' => 'banknotes', 'params' => []],
                                ['route' => 'admin.plans', 'label' => __('Plans & Packages'), 'icon' => 'tag', 'params' => []],
                            ],
                            'management' => $management,
                        ];

                        if ($isAdmin) {
                            $navItems['platform'] = [
                                ['route' => 'platform.users', 'label' => __('Users'), 'icon' => 'users', 'params' => []],
                                ['route' => 'platform.workspaces', 'label' => __('Workspaces'), 'icon' => 'building-office', 'params' => []],
                                ['route' => 'platform.payments', 'label' => __('All Payments'), 'icon' => 'currency-dollar', 'params' => []],
                                ['route' => 'platform.sessions', 'label' => __('All Sessions'), 'icon' => 'signal', 'params' => []],
                                ['route' => 'platform.devices', 'label' => __('All Devices'), 'icon' => 'cpu-chip', 'params' => []],
                                ['route' => 'admin.omada', 'label' => __('Omada'), 'icon' => 'cloud', 'params' => []],
                                ['route' => 'admin.gateways', 'label' => __('Gateways'), 'icon' => 'credit-card', 'params' => []],
                            ];
                        }

                        $navItems['system'] = [
                            ['route' => 'profile.edit', 'label' => __('Settings'), 'icon' => 'cog-6-tooth', 'params' => []],
                        ];

                        $sectionLabels = [
                            'overview' => __('Overview'),
                            'network' => __('Network'),
                            'billing' => __('Billing'),
                            'management' => __('Management'),
                            'platform' => __('Platform Admin'),
                            'system' => __('Account'),
                        ];
                    @endphp

                    @foreach($navItems as $section => $items)
                        {{-- Section label (expanded) / spacer (collapsed) --}}
                        <div
                            class="pb-0.5 text-[10px] font-semibold uppercase tracking-[0.18em] text-ivory/30 transition-all duration-200 overflow-hidden whitespace-nowrap"
                            :class="expanded ? 'opacity-100 max-h-7 pt-3 px-4' : 'opacity-0 max-h-0 pt-0 px-0'"
                        >{{ $sectionLabels[$section] ?? \Illuminate\Support\Str::headline($section) }}</div>

                        @foreach($items as $item)
                            @php $isActive = request()->routeIs($item['route']); @endphp
                            <a
                                href="{{ route($item['route'], $item['params'] ?? []) }}"
                                wire:navigate
                                title="{{ $item['label'] }}"
                                class="group relative flex items-center gap-2.5 rounded-lg text-[13px] transition-all duration-200 ease-out outline-none motion-reduce:transition-none focus-visible:ring-2 focus-visible:ring-terra/50 cursor-pointer {{ $isActive ? 'bg-terra/90 text-white shadow-sm shadow-black/15 ring-1 ring-white/10' : 'text-ivory/60 hover:bg-white/[0.07] hover:text-ivory' }}"
                                :class="expanded ? 'mx-2 px-2.5 py-2' : 'mx-auto size-9 justify-center'"
                            >
                                {{-- Active indicator --}}
                                @if($isActive)
                                    <span class="absolute inset-y-1.5 left-0 w-0.5 rounded-r-full bg-white/80" x-show="expanded"></span>
                                @endif

                                {{-- Icon --}}
                                <span class="shrink-0 {{ $isActive ? 'text-white' : 'text-ivory/50 group-hover:text-ivory' }}">
                                    <flux:icon name="{{ $item['icon'] }}" class="size-[18px]" />
                                </span>

                                {{-- Label --}}
                                <span
                                    class="flex-1 truncate font-medium transition-all duration-200 overflow-hidden whitespace-nowrap"
                                    :class="expanded ? 'opacity-100 max-w-full' : 'opacity-0 max-w-0'"
                                >{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    @endforeach
                </nav>

                {{-- Profile card — only visible when expanded --}}
                <div class="shrink-0 border-t border-white/10 overflow-hidden" :class="expanded ? 'p-2.5' : 'p-1.5 flex justify-center'">
                    <template x-if="expanded">
                        <div class="flex items-center gap-2.5">
                            <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-terra/20 text-xs font-bold text-terra ring-1 ring-terra/25">
                                {{ auth()->user()->initials() }}
                            </div>
                            <div class="min-w-0 flex-1 overflow-hidden">
                                <div class="truncate text-[13px] font-medium text-ivory leading-tight">{{ auth()->user()->name }}</div>
                                <div class="truncate text-[11px] text-ivory/35">{{ auth()->user()->email }}</div>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="rounded-lg p-1 text-ivory/35 transition hover:bg-white/10 hover:text-red-400 cursor-pointer" title="Log out">
                                    <flux:icon name="arrow-right-start-on-rectangle" class="size-3.5" />
                                </button>
                            </form>
                        </div>
                    </template>
                    <template x-if="!expanded">
                        <button @click="toggle()" type="button" class="flex size-8 items-center justify-center rounded-lg bg-terra/15 text-xs font-bold text-terra ring-1 ring-terra/20 transition hover:bg-terra/25 cursor-pointer" title="{{ auth()->user()->name }}">
                            {{ auth()->user()->initials() }}
                        </button>
                    </template>
                </div>
            </aside>

            {{-- ========== MOBILE OVERLAY SIDEBAR ========== --}}
            <div
                x-show="mobileOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm lg:hidden"
                @click="mobileOpen = false"
            ></div>

            <aside
                x-show="mobileOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="-translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="-translate-x-full"
                class="fixed inset-y-0 left-0 z-50 flex w-[min(100vw-2rem,280px)] flex-col border-r border-white/5 bg-gradient-to-b from-smoke via-smoke to-smoke-light/95 shadow-2xl lg:hidden"
            >
                <div class="flex h-16 items-center border-b border-white/10 px-4 gap-3">
                    <div class="flex size-9 items-center justify-center rounded-xl bg-terra text-white shadow-sm">
                        <flux:icon name="router" class="size-5" />
                    </div>
                    <div>
                        <div class="text-sm font-bold text-ivory">SKY Omada</div>
                        <div class="text-xs text-ivory/40">{{ __('Wi‑Fi console') }}</div>
                    </div>
                    <button @click="mobileOpen = false" class="ml-auto rounded-lg p-1.5 text-ivory/40 hover:text-ivory hover:bg-white/10">
                        <flux:icon name="x-mark" class="size-5" />
                    </button>
                </div>
                <nav class="flex-1 overflow-y-auto py-3 space-y-0.5">
                    @foreach($navItems as $section => $items)
                        <div class="px-4 pt-4 pb-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-ivory/35">{{ $sectionLabels[$section] ?? \Illuminate\Support\Str::headline($section) }}</div>
                        @foreach($items as $item)
                            @php $isActive = request()->routeIs($item['route']); @endphp
                            <a href="{{ route($item['route'], $item['params'] ?? []) }}" wire:navigate @click="mobileOpen = false"
                               class="group relative mx-2 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition {{ $isActive ? 'bg-terra text-white' : 'text-ivory/60 hover:bg-white/8 hover:text-ivory' }}">
                                @if($isActive)<span class="absolute inset-y-2 left-0 w-0.5 rounded-r-full bg-white/60"></span>@endif
                                <flux:icon name="{{ $item['icon'] }}" class="size-5 {{ $isActive ? 'text-white' : 'text-ivory/60 group-hover:text-ivory' }}" />
                                <span class="flex-1 truncate">{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    @endforeach
                </nav>
                <div class="border-t border-white/10 p-3">
                    <div class="flex items-center gap-3">
                        <div class="flex size-9 items-center justify-center rounded-xl bg-terra/20 text-sm font-bold text-terra">{{ auth()->user()->initials() }}</div>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-medium text-ivory">{{ auth()->user()->name }}</div>
                            <div class="truncate text-xs text-ivory/40">{{ auth()->user()->email }}</div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded-lg p-1.5 text-ivory/40 hover:text-red-400 hover:bg-white/10" title="Log out">
                                <flux:icon name="arrow-right-start-on-rectangle" class="size-4" />
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            {{-- ========== MAIN CONTENT ========== --}}
            <div
                class="flex min-h-screen flex-1 flex-col transition-[margin] duration-300 ease-out motion-reduce:transition-none"
                :style="'margin-left:' + (window.innerWidth >= 1024 ? (expanded ? '240px' : '56px') : '0px')"
                x-init="window.addEventListener('resize', () => { $el.style.marginLeft = window.innerWidth >= 1024 ? (expanded ? '240px' : '56px') : '0px' })"
            >
                {{-- Top bar --}}
                <header class="sticky top-0 z-30 flex h-12 items-center gap-3 border-b border-smoke/[0.06] bg-ivory/90 px-4 backdrop-blur-md dark:border-white/[0.06] dark:bg-smoke/90">
                    {{-- Mobile: drawer + brand + user avatar --}}
                    <button @click="mobileOpen = true" type="button" class="flex rounded-xl p-2 text-smoke/55 hover:bg-smoke/10 hover:text-smoke focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-terra/40 dark:text-ivory/50 dark:hover:bg-white/10 dark:hover:text-ivory lg:hidden">
                        <flux:icon name="bars-3" class="size-5" />
                    </button>
                    <div class="min-w-0 flex-1 lg:flex lg:items-center lg:gap-3">
                        <span class="truncate text-sm font-semibold text-smoke dark:text-ivory lg:hidden">SKY Omada</span>
                    </div>
                    <div class="flex shrink-0 items-center gap-2 lg:hidden">
                        <flux:dropdown position="bottom" align="end">
                            <button type="button" class="flex size-9 items-center justify-center rounded-full bg-terra/20 text-xs font-bold text-terra ring-1 ring-terra/25 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-terra">
                                {{ auth()->user()->initials() }}
                            </button>
                            <flux:menu>
                                <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>Settings</flux:menu.item>
                                <flux:menu.separator />
                                <form method="POST" action="{{ route('logout') }}" class="w-full">
                                    @csrf
                                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full" data-test="logout-button">Log out</flux:menu.item>
                                </form>
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </header>

                {{-- Page content --}}
                @if (session('error'))
                    <div
                        class="border-b border-red-200/80 bg-red-50/95 px-4 py-3 text-sm text-red-900 dark:border-red-900/50 dark:bg-red-950/60 dark:text-red-100"
                        role="alert"
                    >
                        <div class="flex items-start gap-2">
                            <flux:icon name="exclamation-triangle" class="mt-0.5 size-5 shrink-0 text-red-600 dark:text-red-300" />
                            <p class="leading-snug">{{ session('error') }}</p>
                        </div>
                    </div>
                @endif
                {{ $slot }}
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
