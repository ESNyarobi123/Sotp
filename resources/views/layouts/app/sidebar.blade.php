<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body
        class="min-h-screen bg-ivory dark:bg-smoke"
        x-data="{
            expanded: JSON.parse(localStorage.getItem('sb-expanded') ?? 'true'),
            mobileOpen: false,
            toggle() { this.expanded = !this.expanded; localStorage.setItem('sb-expanded', JSON.stringify(this.expanded)); }
        }"
    >
        <div class="flex min-h-screen">

            {{-- ========== DESKTOP SIDEBAR ========== --}}
            <aside
                class="fixed inset-y-0 left-0 z-40 hidden flex-col bg-smoke shadow-xl transition-all duration-300 lg:flex"
                :style="expanded ? 'width:260px' : 'width:72px'"
            >
                {{-- Logo / Brand --}}
                <div class="flex h-16 shrink-0 items-center border-b border-white/10 px-3 overflow-hidden">
                    <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3 min-w-0">
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-terra text-white shadow-sm">
                            <flux:icon name="router" class="size-5" />
                        </div>
                        <div class="min-w-0 transition-all duration-300 overflow-hidden" :class="expanded ? 'opacity-100 max-w-full' : 'opacity-0 max-w-0'">
                            <div class="truncate text-sm font-bold text-ivory">SKY Omada</div>
                            <div class="truncate text-xs text-ivory/40">Admin Console</div>
                        </div>
                    </a>
                </div>

                {{-- Navigation --}}
                <nav class="flex-1 overflow-y-auto overflow-x-hidden py-3 space-y-0.5" style="scrollbar-width:none;-ms-overflow-style:none" x-bind:style="'scrollbar-width:none'">

                    @php
                        $navItems = [
                            'overview' => [
                                ['route' => 'dashboard',      'label' => 'Dashboard',         'icon' => 'chart-bar-square'],
                            ],
                            'network' => [
                                ['route' => 'admin.sessions', 'label' => 'Sessions',           'icon' => 'users'],
                                ['route' => 'admin.devices',  'label' => 'Devices (APs)',       'icon' => 'server-stack'],
                            ],
                            'billing' => [
                                ['route' => 'admin.payments', 'label' => 'Payments',            'icon' => 'banknotes'],
                                ['route' => 'admin.plans',    'label' => 'Plans & Packages',    'icon' => 'tag'],
                            ],
                            'management' => [
                                ['route' => 'admin.clients',  'label' => 'Clients',             'icon' => 'user-group'],
                                ['route' => 'admin.omada',    'label' => 'Omada Integration',   'icon' => 'cloud'],
                                ['route' => 'admin.gateways', 'label' => 'Payment Gateways',    'icon' => 'credit-card'],
                            ],
                            'system' => [
                                ['route' => 'profile.edit',   'label' => 'Settings',            'icon' => 'cog-6-tooth'],
                            ],
                        ];
                    @endphp

                    @foreach($navItems as $section => $items)
                        {{-- Section label (expanded) / spacer (collapsed) --}}
                        <div
                            class="px-4 pb-1 text-[10px] font-semibold uppercase tracking-widest text-ivory/30 transition-all duration-300 overflow-hidden whitespace-nowrap"
                            :class="expanded ? 'opacity-100 max-h-8 pt-4' : 'opacity-0 max-h-3 pt-3'"
                        >{{ $section }}</div>

                        @foreach($items as $item)
                            @php $isActive = request()->routeIs($item['route']); @endphp
                            <a
                                href="{{ route($item['route']) }}"
                                wire:navigate
                                title="{{ $item['label'] }}"
                                class="group relative mx-2 flex items-center gap-3 rounded-xl py-2.5 text-sm transition-all duration-200 focus:outline-none {{ $isActive ? 'bg-terra text-white shadow-sm' : 'text-ivory/60 hover:bg-white/8 hover:text-ivory' }}"
                                :class="expanded ? 'px-3' : 'justify-center px-0'"
                            >
                                {{-- Active left bar --}}
                                @if($isActive)
                                    <span class="absolute inset-y-2 left-0 w-0.5 rounded-r-full bg-white/60"></span>
                                @endif

                                {{-- Icon --}}
                                <span class="shrink-0 transition-all duration-200 {{ $isActive ? 'text-white' : 'text-ivory/60 group-hover:text-ivory' }}">
                                    <flux:icon name="{{ $item['icon'] }}" class="size-5" />
                                </span>

                                {{-- Label --}}
                                <span
                                    class="flex-1 truncate transition-all duration-300 overflow-hidden whitespace-nowrap"
                                    :class="expanded ? 'opacity-100 max-w-full' : 'opacity-0 max-w-0'"
                                >{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    @endforeach
                </nav>

                {{-- Profile card — only visible when expanded --}}
                <div class="shrink-0 border-t border-white/10 p-3 overflow-hidden" x-show="expanded" x-transition.opacity>
                    <div class="flex items-center gap-3">
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-terra/20 text-sm font-bold text-terra ring-1 ring-terra/30">
                            {{ auth()->user()->initials() }}
                        </div>
                        <div class="min-w-0 flex-1 overflow-hidden">
                            <div class="truncate text-sm font-medium text-ivory">{{ auth()->user()->name }}</div>
                            <div class="truncate text-xs text-ivory/40">{{ auth()->user()->email }}</div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded-lg p-1.5 text-ivory/40 transition hover:bg-white/10 hover:text-red-400" title="Log out">
                                <flux:icon name="arrow-right-start-on-rectangle" class="size-4" />
                            </button>
                        </form>
                    </div>
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
                class="fixed inset-y-0 left-0 z-50 flex w-[260px] flex-col bg-smoke shadow-xl lg:hidden"
            >
                <div class="flex h-16 items-center border-b border-white/10 px-4 gap-3">
                    <div class="flex size-9 items-center justify-center rounded-xl bg-terra text-white shadow-sm">
                        <flux:icon name="router" class="size-5" />
                    </div>
                    <div>
                        <div class="text-sm font-bold text-ivory">SKY Omada</div>
                        <div class="text-xs text-ivory/40">Admin Console</div>
                    </div>
                    <button @click="mobileOpen = false" class="ml-auto rounded-lg p-1.5 text-ivory/40 hover:text-ivory hover:bg-white/10">
                        <flux:icon name="x-mark" class="size-5" />
                    </button>
                </div>
                <nav class="flex-1 overflow-y-auto py-3 space-y-0.5">
                    @foreach($navItems as $section => $items)
                        <div class="px-4 pt-4 pb-1 text-[10px] font-semibold uppercase tracking-widest text-ivory/30">{{ $section }}</div>
                        @foreach($items as $item)
                            @php $isActive = request()->routeIs($item['route']); @endphp
                            <a href="{{ route($item['route']) }}" wire:navigate @click="mobileOpen = false"
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
                class="flex min-h-screen flex-1 flex-col transition-all duration-300"
                :style="'margin-left:' + (window.innerWidth >= 1024 ? (expanded ? '260px' : '72px') : '0px')"
                x-init="window.addEventListener('resize', () => { $el.style.marginLeft = window.innerWidth >= 1024 ? (expanded ? '260px' : '72px') : '0px' })"
            >
                {{-- Top bar --}}
                <div class="flex h-14 items-center border-b border-smoke/10 bg-ivory px-4 dark:border-white/10 dark:bg-smoke-light">
                    {{-- Desktop: sidebar collapse toggle only --}}
                    <button @click="toggle()" class="hidden lg:flex rounded-lg p-2 text-smoke/50 transition hover:bg-smoke/8 hover:text-smoke dark:text-ivory/50 dark:hover:bg-white/8 dark:hover:text-ivory" title="Toggle sidebar">
                        <flux:icon name="bars-3" class="size-5" />
                    </button>

                    {{-- Mobile: drawer + brand + user avatar --}}
                    <button @click="mobileOpen = true" class="flex lg:hidden rounded-lg p-2 text-smoke/50 hover:text-smoke dark:text-ivory/50 dark:hover:text-ivory">
                        <flux:icon name="bars-3" class="size-5" />
                    </button>
                    <span class="ml-2 flex-1 text-sm font-semibold text-smoke dark:text-ivory lg:hidden">SKY Omada</span>
                    <div class="flex lg:hidden items-center">
                        <flux:dropdown position="bottom" align="end">
                            <button class="flex size-8 items-center justify-center rounded-full bg-terra/20 text-xs font-bold text-terra">
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
                </div>

                {{-- Page content --}}
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
