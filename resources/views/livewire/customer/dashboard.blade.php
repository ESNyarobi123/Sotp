<div class="space-y-5 p-4 sm:p-6 lg:p-8" wire:poll.30s>
    @php($provisioning = $this->workspace->provisioningSummary())

    {{-- ═══ Header ═══ --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-1.5">
                <flux:badge size="sm" :color="$provisioning['badge_color']">{{ $provisioning['status'] === 'ready' ? __('Portal Ready') : __(ucfirst($provisioning['status'])) }}</flux:badge>
                <flux:badge size="sm" :color="$this->clickPesaSettings?->is_active ? 'emerald' : 'zinc'">{{ $this->clickPesaSettings?->is_active ? __('ClickPesa') : __('Payments Pending') }}</flux:badge>
            </div>
            <h1 class="mt-2 truncate text-2xl font-bold tracking-tight text-smoke dark:text-ivory sm:text-3xl">{{ $this->workspace->brand_name }}</h1>
        </div>
        <div class="flex shrink-0 items-center gap-2 text-xs text-smoke/45 dark:text-ivory/40">
            <flux:icon name="clock" class="size-3.5" />
            <span>{{ now()->format('H:i') }} &middot; {{ $this->devicesLastSyncedAt ? __('Synced :t', ['t' => $this->devicesLastSyncedAt]) : __('Not synced') }}</span>
        </div>
    </div>

    {{-- ═══ Portal Banner (only when ready) ═══ --}}
    @if(! $this->workspace->isOmadaReady())
        <x-workspace.provisioning-status :workspace="$this->workspace" />
    @else
        <div class="flex flex-col gap-3 rounded-2xl border border-terra/15 bg-gradient-to-r from-terra/[0.06] to-transparent p-4 dark:border-terra/20 dark:from-terra/[0.08] sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3 min-w-0">
                <div class="grid size-10 shrink-0 place-items-center rounded-xl bg-terra/10 dark:bg-terra/15">
                    <flux:icon name="wifi" class="size-5 text-terra dark:text-terra-light" />
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-smoke/50 dark:text-ivory/45">{{ __('Guest Portal') }}</p>
                    <p class="truncate font-mono text-xs text-smoke/70 dark:text-ivory/55">{{ $this->workspace->portalUrl() }}</p>
                </div>
            </div>
            <div class="flex shrink-0 gap-2" x-data="{ copied: false }">
                <flux:button size="sm" variant="ghost" icon="clipboard-document" type="button"
                    x-on:click="navigator.clipboard.writeText('{{ $this->workspace->portalUrl() }}'); copied = true; setTimeout(() => copied = false, 1600)">
                    <span x-show="! copied">{{ __('Copy') }}</span>
                    <span x-show="copied">{{ __('Copied!') }}</span>
                </flux:button>
                <flux:button size="sm" icon="arrow-top-right-on-square" href="{{ $this->workspace->portalUrl() }}" target="_blank" class="!bg-terra !text-white hover:!opacity-90 cursor-pointer">
                    {{ __('Open') }}
                </flux:button>
                <flux:button size="sm" variant="ghost" icon="tag" href="{{ route('admin.plans') }}" wire:navigate class="cursor-pointer">
                    {{ __('Plans') }}
                </flux:button>
            </div>
        </div>
    @endif

    {{-- ═══ KPI Grid ═══ --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
        {{-- Online Users --}}
        <div class="group rounded-2xl border border-ivory-darker/60 bg-white/70 p-4 shadow-sm backdrop-blur transition-all duration-200 hover:border-terra/20 hover:shadow-md dark:border-smoke-light/60 dark:bg-smoke-light/30">
            <div class="flex items-center justify-between">
                <div class="grid size-9 place-items-center rounded-xl bg-emerald-500/10 dark:bg-emerald-500/15">
                    <flux:icon name="users" class="size-4.5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div class="h-2 w-2 rounded-full bg-emerald-500 shadow-sm shadow-emerald-500/50"></div>
            </div>
            <div class="mt-3 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->onlineUsers }}</div>
            <p class="text-[11px] text-smoke/45 dark:text-ivory/40">{{ __('Online') }}</p>
        </div>

        {{-- Devices --}}
        <div class="group rounded-2xl border border-ivory-darker/60 bg-white/70 p-4 shadow-sm backdrop-blur transition-all duration-200 hover:border-terra/20 hover:shadow-md dark:border-smoke-light/60 dark:bg-smoke-light/30">
            <div class="grid size-9 place-items-center rounded-xl bg-terra/10 dark:bg-terra/15">
                <flux:icon name="server-stack" class="size-4.5 text-terra dark:text-terra-light" />
            </div>
            <div class="mt-3 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->onlineDevices }}<span class="text-sm font-normal text-smoke/35 dark:text-ivory/30">/{{ $this->totalDevices }}</span></div>
            <p class="text-[11px] text-smoke/45 dark:text-ivory/40">{{ __('APs') }}</p>
        </div>

        {{-- Clients --}}
        <div class="group rounded-2xl border border-ivory-darker/60 bg-white/70 p-4 shadow-sm backdrop-blur transition-all duration-200 hover:border-terra/20 hover:shadow-md dark:border-smoke-light/60 dark:bg-smoke-light/30">
            <div class="grid size-9 place-items-center rounded-xl bg-sky-500/10 dark:bg-sky-500/15">
                <flux:icon name="user-group" class="size-4.5 text-sky-600 dark:text-sky-400" />
            </div>
            <div class="mt-3 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->activeClients }}<span class="text-sm font-normal text-smoke/35 dark:text-ivory/30">/{{ $this->totalClients }}</span></div>
            <p class="text-[11px] text-smoke/45 dark:text-ivory/40">{{ __('Clients') }}</p>
        </div>

        {{-- Sessions Today --}}
        <div class="group rounded-2xl border border-ivory-darker/60 bg-white/70 p-4 shadow-sm backdrop-blur transition-all duration-200 hover:border-terra/20 hover:shadow-md dark:border-smoke-light/60 dark:bg-smoke-light/30">
            <div class="grid size-9 place-items-center rounded-xl bg-violet-500/10 dark:bg-violet-500/15">
                <flux:icon name="activity" class="size-4.5 text-violet-600 dark:text-violet-400" />
            </div>
            <div class="mt-3 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->totalSessionsToday }}</div>
            <p class="text-[11px] text-smoke/45 dark:text-ivory/40">{{ __('Sessions') }}</p>
        </div>

        {{-- Revenue Today --}}
        <div class="group rounded-2xl border border-ivory-darker/60 bg-white/70 p-4 shadow-sm backdrop-blur transition-all duration-200 hover:border-terra/20 hover:shadow-md dark:border-smoke-light/60 dark:bg-smoke-light/30">
            <div class="grid size-9 place-items-center rounded-xl bg-amber-500/10 dark:bg-amber-500/15">
                <flux:icon name="wallet" class="size-4.5 text-amber-600 dark:text-amber-400" />
            </div>
            <div class="mt-3 text-xl font-bold text-smoke dark:text-ivory">{{ $this->revenueToday }}<span class="text-[10px] font-normal text-smoke/40 dark:text-ivory/30 ml-0.5">TZS</span></div>
            <p class="text-[11px] text-smoke/45 dark:text-ivory/40">{{ $this->totalPaymentsToday }} {{ __('today') }}</p>
        </div>

        {{-- Monthly --}}
        <div class="group rounded-2xl border border-ivory-darker/60 bg-white/70 p-4 shadow-sm backdrop-blur transition-all duration-200 hover:border-terra/20 hover:shadow-md dark:border-smoke-light/60 dark:bg-smoke-light/30">
            <div class="grid size-9 place-items-center rounded-xl bg-terra/10 dark:bg-terra/15">
                <flux:icon name="banknotes" class="size-4.5 text-terra dark:text-terra-light" />
            </div>
            <div class="mt-3 text-xl font-bold text-smoke dark:text-ivory">{{ $this->revenueThisMonth }}<span class="text-[10px] font-normal text-smoke/40 dark:text-ivory/30 ml-0.5">TZS</span></div>
            <p class="text-[11px] text-smoke/45 dark:text-ivory/40">{{ __('This month') }}</p>
        </div>
    </div>

    {{-- ═══ Wallet + Quick Actions ═══ --}}
    <div class="grid gap-4 lg:grid-cols-[1fr_auto]">
        {{-- Wallet --}}
        <div class="flex flex-wrap items-center gap-4 rounded-2xl border border-emerald-200/60 bg-emerald-50/50 p-4 dark:border-emerald-800/30 dark:bg-emerald-950/15">
            <div class="grid size-10 shrink-0 place-items-center rounded-xl bg-emerald-500/15 dark:bg-emerald-500/20">
                <flux:icon name="banknotes" class="size-5 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div class="min-w-0">
                <p class="text-[11px] font-medium uppercase tracking-wider text-emerald-700/60 dark:text-emerald-400/60">{{ __('Wallet Balance') }}</p>
                <p class="text-xl font-bold text-emerald-700 dark:text-emerald-300">{{ $this->availableWalletBalance }} <span class="text-sm font-normal">TZS</span></p>
            </div>
            <div class="ml-auto">
                <flux:button size="sm" variant="ghost" icon="arrow-right" href="{{ route('admin.payments') }}" wire:navigate class="cursor-pointer text-emerald-700 dark:text-emerald-400">
                    {{ __('History') }}
                </flux:button>
            </div>
        </div>

        {{-- Quick nav --}}
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.devices') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-xl border border-ivory-darker/60 bg-white/70 px-3 py-2.5 text-xs font-medium text-smoke/70 shadow-sm transition-all duration-200 hover:border-terra/20 hover:text-terra dark:border-smoke-light/60 dark:bg-smoke-light/30 dark:text-ivory/55 dark:hover:text-terra-light cursor-pointer">
                <flux:icon name="server-stack" class="size-3.5" /> {{ __('Devices') }}
            </a>
            <a href="{{ route('admin.payments') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-xl border border-ivory-darker/60 bg-white/70 px-3 py-2.5 text-xs font-medium text-smoke/70 shadow-sm transition-all duration-200 hover:border-terra/20 hover:text-terra dark:border-smoke-light/60 dark:bg-smoke-light/30 dark:text-ivory/55 dark:hover:text-terra-light cursor-pointer">
                <flux:icon name="wallet" class="size-3.5" /> {{ __('Payments') }}
            </a>
            <a href="{{ route('admin.sessions') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-xl border border-ivory-darker/60 bg-white/70 px-3 py-2.5 text-xs font-medium text-smoke/70 shadow-sm transition-all duration-200 hover:border-terra/20 hover:text-terra dark:border-smoke-light/60 dark:bg-smoke-light/30 dark:text-ivory/55 dark:hover:text-terra-light cursor-pointer">
                <flux:icon name="activity" class="size-3.5" /> {{ __('Sessions') }}
            </a>
            <a href="{{ route('admin.clients') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-xl border border-ivory-darker/60 bg-white/70 px-3 py-2.5 text-xs font-medium text-smoke/70 shadow-sm transition-all duration-200 hover:border-terra/20 hover:text-terra dark:border-smoke-light/60 dark:bg-smoke-light/30 dark:text-ivory/55 dark:hover:text-terra-light cursor-pointer">
                <flux:icon name="user-group" class="size-3.5" /> {{ __('Clients') }}
            </a>
            <a href="{{ route('profile.edit') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-xl border border-ivory-darker/60 bg-white/70 px-3 py-2.5 text-xs font-medium text-smoke/70 shadow-sm transition-all duration-200 hover:border-terra/20 hover:text-terra dark:border-smoke-light/60 dark:bg-smoke-light/30 dark:text-ivory/55 dark:hover:text-terra-light cursor-pointer">
                <flux:icon name="cog-6-tooth" class="size-3.5" /> {{ __('Settings') }}
            </a>
        </div>
    </div>

    {{-- ═══ Activity Feed: Devices · Payments · Sessions ═══ --}}
    <div class="grid gap-4 lg:grid-cols-3">

        {{-- Devices --}}
        <div class="rounded-2xl border border-ivory-darker/60 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/60 dark:bg-smoke-light/30">
            <div class="flex items-center justify-between border-b border-ivory-darker/50 px-4 py-3 dark:border-smoke-light/40">
                <div class="flex items-center gap-2">
                    <flux:icon name="server-stack" class="size-4 text-terra dark:text-terra-light" />
                    <span class="text-sm font-semibold text-smoke dark:text-ivory">{{ __('Devices') }}</span>
                </div>
                <a href="{{ route('admin.devices') }}" wire:navigate class="text-[11px] font-medium text-terra hover:text-terra-dark dark:text-terra-light cursor-pointer">{{ __('All') }} &rarr;</a>
            </div>
            <div class="divide-y divide-ivory-darker/40 dark:divide-smoke-light/30">
                @forelse($this->recentDevices as $device)
                    <div class="flex items-center gap-3 px-4 py-3">
                        <div class="h-2 w-2 shrink-0 rounded-full {{ $device->status === 'online' ? 'bg-emerald-500 shadow-sm shadow-emerald-500/50' : ($device->status === 'offline' ? 'bg-zinc-300 dark:bg-zinc-600' : 'bg-amber-400') }}"></div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-smoke dark:text-ivory">{{ $device->name }}</p>
                            <p class="truncate font-mono text-[10px] text-smoke/40 dark:text-ivory/35">{{ $device->ap_mac }}</p>
                        </div>
                        <span class="shrink-0 text-[10px] text-smoke/40 dark:text-ivory/35">{{ $device->model ?: '—' }}</span>
                    </div>
                @empty
                    <p class="px-4 py-8 text-center text-xs text-smoke/40 dark:text-ivory/35">{{ __('No devices yet') }}</p>
                @endforelse
            </div>
        </div>

        {{-- Payments --}}
        <div class="rounded-2xl border border-ivory-darker/60 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/60 dark:bg-smoke-light/30">
            <div class="flex items-center justify-between border-b border-ivory-darker/50 px-4 py-3 dark:border-smoke-light/40">
                <div class="flex items-center gap-2">
                    <flux:icon name="wallet" class="size-4 text-terra dark:text-terra-light" />
                    <span class="text-sm font-semibold text-smoke dark:text-ivory">{{ __('Payments') }}</span>
                </div>
                <a href="{{ route('admin.payments') }}" wire:navigate class="text-[11px] font-medium text-terra hover:text-terra-dark dark:text-terra-light cursor-pointer">{{ __('All') }} &rarr;</a>
            </div>
            <div class="divide-y divide-ivory-darker/40 dark:divide-smoke-light/30">
                @forelse($this->recentPayments as $payment)
                    <div class="flex items-center gap-3 px-4 py-3">
                        <div class="h-2 w-2 shrink-0 rounded-full {{ $payment->status === 'completed' ? 'bg-emerald-500' : ($payment->status === 'pending' ? 'bg-amber-400' : 'bg-red-400') }}"></div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-smoke dark:text-ivory">{{ number_format($payment->amount, 0) }} {{ $payment->currency }}</p>
                            <p class="text-[10px] text-smoke/40 dark:text-ivory/35">{{ $payment->phone_number }} &middot; {{ $payment->plan?->name ?? '—' }}</p>
                        </div>
                        <span class="shrink-0 text-[10px] text-smoke/40 dark:text-ivory/35">{{ optional($payment->paid_at ?? $payment->created_at)->diffForHumans(short: true) }}</span>
                    </div>
                @empty
                    <p class="px-4 py-8 text-center text-xs text-smoke/40 dark:text-ivory/35">{{ __('No payments yet') }}</p>
                @endforelse
            </div>
        </div>

        {{-- Sessions --}}
        <div class="rounded-2xl border border-ivory-darker/60 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/60 dark:bg-smoke-light/30">
            <div class="flex items-center justify-between border-b border-ivory-darker/50 px-4 py-3 dark:border-smoke-light/40">
                <div class="flex items-center gap-2">
                    <flux:icon name="activity" class="size-4 text-terra dark:text-terra-light" />
                    <span class="text-sm font-semibold text-smoke dark:text-ivory">{{ __('Sessions') }}</span>
                </div>
                <a href="{{ route('admin.sessions') }}" wire:navigate class="text-[11px] font-medium text-terra hover:text-terra-dark dark:text-terra-light cursor-pointer">{{ __('All') }} &rarr;</a>
            </div>
            <div class="divide-y divide-ivory-darker/40 dark:divide-smoke-light/30">
                @forelse($this->recentSessions as $session)
                    <div class="flex items-center gap-3 px-4 py-3">
                        <div class="h-2 w-2 shrink-0 rounded-full {{ $session->isActive() ? 'bg-emerald-500 shadow-sm shadow-emerald-500/50' : 'bg-zinc-300 dark:bg-zinc-600' }}"></div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-mono text-xs font-medium text-smoke dark:text-ivory">{{ $session->client_mac }}</p>
                            <p class="text-[10px] text-smoke/40 dark:text-ivory/35">{{ $session->plan?->name ?? '—' }} &middot; {{ $session->isActive() ? ($session->timeRemaining() ?? __('Unlimited')) : __('Ended') }}</p>
                        </div>
                        <flux:badge size="sm" :color="$session->isActive() ? 'emerald' : 'zinc'">{{ $session->isActive() ? __('Live') : __('Done') }}</flux:badge>
                    </div>
                @empty
                    <p class="px-4 py-8 text-center text-xs text-smoke/40 dark:text-ivory/35">{{ __('No sessions yet') }}</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ═══ Clients Table ═══ --}}
    <div class="rounded-2xl border border-ivory-darker/60 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/60 dark:bg-smoke-light/30">
        <div class="flex items-center justify-between border-b border-ivory-darker/50 px-4 py-3 dark:border-smoke-light/40">
            <div class="flex items-center gap-2">
                <flux:icon name="user-group" class="size-4 text-terra dark:text-terra-light" />
                <span class="text-sm font-semibold text-smoke dark:text-ivory">{{ __('Top Clients') }}</span>
            </div>
            <a href="{{ route('admin.clients') }}" wire:navigate class="text-[11px] font-medium text-terra hover:text-terra-dark dark:text-terra-light cursor-pointer">{{ __('All') }} &rarr;</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-ivory-darker/40 text-left text-[10px] font-semibold uppercase tracking-wider text-smoke/40 dark:border-smoke-light/30 dark:text-ivory/35">
                        <th class="px-4 py-2.5">{{ __('MAC') }}</th>
                        <th class="px-4 py-2.5">{{ __('Sessions') }}</th>
                        <th class="px-4 py-2.5">{{ __('Data') }}</th>
                        <th class="px-4 py-2.5 text-right">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ivory-darker/30 dark:divide-smoke-light/20">
                    @forelse($this->topClients as $client)
                        <tr class="transition-colors hover:bg-ivory/30 dark:hover:bg-smoke-light/20">
                            <td class="whitespace-nowrap px-4 py-2.5 font-mono text-xs text-smoke dark:text-ivory">{{ $client->client_mac }}</td>
                            <td class="px-4 py-2.5 text-xs text-smoke/70 dark:text-ivory/55">{{ $client->total_sessions }}</td>
                            <td class="px-4 py-2.5 text-xs text-smoke/70 dark:text-ivory/55">{{ number_format((float) $client->total_data_mb, 1) }} MB</td>
                            <td class="px-4 py-2.5 text-right">
                                <span class="inline-flex h-2 w-2 rounded-full {{ $client->has_active ? 'bg-emerald-500' : 'bg-zinc-300 dark:bg-zinc-600' }}"></span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-xs text-smoke/40 dark:text-ivory/35">{{ __('No client data yet') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
