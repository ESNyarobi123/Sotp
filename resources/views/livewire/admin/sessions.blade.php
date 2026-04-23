<div class="space-y-5 p-4 sm:p-6 lg:p-8" wire:poll.10s>
    {{-- Page Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="grid size-9 place-items-center rounded-xl bg-gradient-to-br from-terra/20 to-terra/5 dark:from-terra/25 dark:to-terra/10">
                    <flux:icon name="signal" class="size-5 text-terra dark:text-terra-light" />
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-smoke dark:text-ivory">Sessions</h1>
            </div>
            <p class="mt-1 text-xs text-smoke/50 dark:text-ivory/40">Real-time guest WiFi connections across your network</p>
        </div>
        <div class="flex items-center gap-2 rounded-xl border border-smoke/8 bg-white/60 px-3 py-1.5 text-[11px] text-smoke/45 shadow-sm backdrop-blur dark:border-white/8 dark:bg-smoke-light/40 dark:text-ivory/40">
            <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>Live &middot; {{ now()->format('H:i') }}</span>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-3 gap-3">
        <button wire:click="$set('statusFilter', 'active')" class="group cursor-pointer rounded-2xl border p-3.5 text-left transition-all duration-200 {{ $statusFilter === 'active' ? 'border-emerald-500/30 bg-emerald-500/5 shadow-sm shadow-emerald-500/10 dark:border-emerald-500/20' : 'border-ivory-darker/50 bg-white/70 hover:border-emerald-500/20 hover:shadow-sm dark:border-smoke-light/50 dark:bg-smoke-light/30 dark:hover:border-emerald-500/15' }}">
            <div class="flex items-center gap-2">
                <span class="size-2 rounded-full bg-emerald-500 shadow-sm shadow-emerald-500/40 {{ $statusFilter === 'active' ? 'animate-pulse' : '' }}"></span>
                <span class="text-[10px] font-semibold uppercase tracking-wider text-emerald-600/70 dark:text-emerald-400/70">Active</span>
            </div>
            <div class="mt-1.5 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->activeCount }}</div>
        </button>
        <button wire:click="$set('statusFilter', 'expired')" class="group cursor-pointer rounded-2xl border p-3.5 text-left transition-all duration-200 {{ $statusFilter === 'expired' ? 'border-amber-500/30 bg-amber-500/5 shadow-sm shadow-amber-500/10 dark:border-amber-500/20' : 'border-ivory-darker/50 bg-white/70 hover:border-amber-500/20 hover:shadow-sm dark:border-smoke-light/50 dark:bg-smoke-light/30 dark:hover:border-amber-500/15' }}">
            <div class="flex items-center gap-2">
                <span class="size-2 rounded-full bg-amber-400"></span>
                <span class="text-[10px] font-semibold uppercase tracking-wider text-amber-600/70 dark:text-amber-400/70">Expired</span>
            </div>
            <div class="mt-1.5 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->expiredCount }}</div>
        </button>
        <button wire:click="$set('statusFilter', '')" class="group cursor-pointer rounded-2xl border p-3.5 text-left transition-all duration-200 {{ $statusFilter === '' ? 'border-terra/30 bg-terra/5 shadow-sm shadow-terra/10 dark:border-terra/20' : 'border-ivory-darker/50 bg-white/70 hover:border-terra/20 hover:shadow-sm dark:border-smoke-light/50 dark:bg-smoke-light/30 dark:hover:border-terra/15' }}">
            <div class="flex items-center gap-2">
                <span class="size-2 rounded-full bg-terra"></span>
                <span class="text-[10px] font-semibold uppercase tracking-wider text-terra/70 dark:text-terra-light/70">Total</span>
            </div>
            <div class="mt-1.5 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->totalCount }}</div>
        </button>
    </div>

    {{-- Search & Filters --}}
    <div class="flex flex-col gap-2 sm:flex-row">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search MAC, IP, SSID..." icon="magnifying-glass" class="sm:w-72" />
        <flux:select wire:model.live="statusFilter" class="sm:w-40">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="expired">Expired</option>
            <option value="disconnected">Disconnected</option>
        </flux:select>
    </div>

    {{-- Mobile Card View --}}
    <div class="space-y-2 sm:hidden">
        @forelse ($this->sessions as $session)
            <div wire:click="viewSession({{ $session->id }})" class="cursor-pointer rounded-2xl border border-ivory-darker/50 bg-white/80 p-3.5 shadow-sm transition-all duration-200 hover:shadow-md hover:border-terra/20 dark:border-smoke-light/50 dark:bg-smoke-light/30 dark:hover:border-terra/15">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            @if($session->status === 'active')
                                <span class="size-2 shrink-0 rounded-full bg-emerald-500 shadow-sm shadow-emerald-500/40 animate-pulse"></span>
                            @endif
                            <span class="font-mono text-xs font-semibold text-smoke dark:text-ivory truncate">{{ $session->client_mac }}</span>
                        </div>
                        <div class="mt-1 flex items-center gap-1.5 text-[11px] text-smoke/45 dark:text-ivory/35">
                            <flux:icon name="wifi" class="size-3 text-smoke/30 dark:text-ivory/25" />
                            {{ $session->ssid ?? 'No SSID' }}
                            @if($session->plan) &middot; {{ $session->plan->name }} @endif
                        </div>
                    </div>
                    <flux:badge size="sm" :color="$session->status === 'active' ? 'emerald' : ($session->status === 'expired' ? 'amber' : 'red')">
                        {{ ucfirst($session->status) }}
                    </flux:badge>
                </div>
                <div class="mt-2.5 flex items-center gap-3 text-[11px] text-smoke/50 dark:text-ivory/40">
                    <span class="font-medium">{{ number_format($session->data_used_mb, 1) }} MB</span>
                    @if($session->data_limit_mb)
                        @php $pct = min(100, ($session->data_used_mb / $session->data_limit_mb) * 100); @endphp
                        <div class="h-1.5 w-14 overflow-hidden rounded-full bg-zinc-200/80 dark:bg-zinc-700/80">
                            <div class="h-full rounded-full transition-all duration-500 {{ $pct > 90 ? 'bg-red-500' : ($pct > 70 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $pct }}%"></div>
                        </div>
                    @endif
                    <span class="ml-auto font-medium">
                        @if($session->status === 'active' && $session->time_expires)
                            {{ $session->timeRemaining() }}
                        @else
                            {{ $session->time_started?->diffForHumans() ?? '—' }}
                        @endif
                    </span>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border-2 border-dashed border-ivory-darker/40 py-12 text-center dark:border-smoke-light/30">
                <div class="mx-auto grid size-14 place-items-center rounded-2xl bg-ivory/70 dark:bg-smoke-light/40">
                    <flux:icon name="signal-slash" class="size-7 text-smoke/25 dark:text-ivory/20" />
                </div>
                <p class="mt-3 text-sm font-medium text-smoke/40 dark:text-ivory/35">No sessions found</p>
                <p class="mt-1 text-xs text-smoke/30 dark:text-ivory/25">Connections will appear here once guests connect</p>
            </div>
        @endforelse
        <div class="pt-2">{{ $this->sessions->links() }}</div>
    </div>

    {{-- Desktop Table --}}
    <div class="hidden sm:block overflow-hidden rounded-2xl border border-ivory-darker/60 bg-white/80 shadow-sm backdrop-blur-sm dark:border-smoke-light/60 dark:bg-smoke-light/30">
        <flux:table :paginate="$this->sessions">
            <flux:table.columns>
                <flux:table.column wire:click="sort('client_mac')" class="cursor-pointer select-none">
                    Client MAC
                    @if($sortBy === 'client_mac')<flux:icon name="chevron-{{ $sortDir === 'asc' ? 'up' : 'down' }}" class="inline size-3" />@endif
                </flux:table.column>
                <flux:table.column>IP Address</flux:table.column>
                <flux:table.column>SSID</flux:table.column>
                <flux:table.column>Plan</flux:table.column>
                <flux:table.column>Data Used</flux:table.column>
                <flux:table.column wire:click="sort('time_started')" class="cursor-pointer select-none">
                    Started
                    @if($sortBy === 'time_started')<flux:icon name="chevron-{{ $sortDir === 'asc' ? 'up' : 'down' }}" class="inline size-3" />@endif
                </flux:table.column>
                <flux:table.column>Time Left</flux:table.column>
                <flux:table.column wire:click="sort('status')" class="cursor-pointer select-none">
                    Status
                    @if($sortBy === 'status')<flux:icon name="chevron-{{ $sortDir === 'asc' ? 'up' : 'down' }}" class="inline size-3" />@endif
                </flux:table.column>
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->sessions as $session)
                    <flux:table.row class="transition-colors duration-150 hover:bg-ivory/40 dark:hover:bg-smoke-light/20 cursor-pointer" wire:click="viewSession({{ $session->id }})">
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                @if($session->status === 'active')
                                    <span class="size-1.5 shrink-0 rounded-full bg-emerald-500 animate-pulse"></span>
                                @endif
                                <span class="font-mono text-xs font-medium text-smoke dark:text-ivory">{{ $session->client_mac }}</span>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell class="font-mono text-xs text-smoke/60 dark:text-ivory/50">{{ $session->ip_address ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="text-sm text-smoke/70 dark:text-ivory/60">{{ $session->ssid ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            @if($session->plan)
                                <span class="inline-flex items-center rounded-lg bg-terra/8 px-2 py-0.5 text-[11px] font-medium text-terra dark:bg-terra/15 dark:text-terra-light">{{ $session->plan->name }}</span>
                            @else
                                <span class="text-smoke/30 dark:text-ivory/25">—</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="text-sm font-medium text-smoke/80 dark:text-ivory/70">{{ number_format($session->data_used_mb, 1) }} MB</div>
                            @if($session->data_limit_mb)
                                <div class="mt-1 h-1.5 w-16 overflow-hidden rounded-full bg-zinc-200/80 dark:bg-zinc-700/80">
                                    @php $pct = min(100, ($session->data_used_mb / $session->data_limit_mb) * 100); @endphp
                                    <div class="h-full rounded-full transition-all duration-500 {{ $pct > 90 ? 'bg-red-500' : ($pct > 70 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $pct }}%"></div>
                                </div>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-xs text-smoke/55 dark:text-ivory/45">{{ $session->time_started?->diffForHumans() ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="text-xs">
                            @if($session->status === 'active' && $session->time_expires)
                                <span class="font-medium {{ $session->time_expires->diffInMinutes(now()) < 30 ? 'text-amber-600 dark:text-amber-400' : 'text-smoke/70 dark:text-ivory/60' }}">
                                    {{ $session->timeRemaining() }}
                                </span>
                            @elseif($session->status !== 'active')
                                <span class="text-smoke/30 dark:text-ivory/25">—</span>
                            @else
                                <span class="text-smoke/40 dark:text-ivory/35">Unlimited</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$session->status === 'active' ? 'emerald' : ($session->status === 'expired' ? 'amber' : 'red')">
                                {{ ucfirst($session->status) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" class="cursor-pointer" />
                                <flux:menu>
                                    <flux:menu.item wire:click="viewSession({{ $session->id }})" icon="eye">
                                        View Details
                                    </flux:menu.item>
                                    @if($session->status === 'active')
                                        <flux:menu.separator />
                                        <flux:menu.item wire:click="disconnect({{ $session->id }})" wire:confirm="Disconnect client {{ $session->client_mac }}?" icon="x-mark" class="text-red-600 dark:text-red-400">
                                            Disconnect
                                        </flux:menu.item>
                                    @endif
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="9" class="text-center">
                            <div class="py-12">
                                <div class="mx-auto grid size-14 place-items-center rounded-2xl bg-ivory/70 dark:bg-smoke-light/40">
                                    <flux:icon name="signal-slash" class="size-7 text-smoke/20 dark:text-ivory/20" />
                                </div>
                                <p class="mt-3 text-sm font-medium text-smoke/40 dark:text-ivory/35">No sessions found</p>
                                <p class="mt-1 text-xs text-smoke/30 dark:text-ivory/25">Try adjusting your search or filters</p>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    {{-- Session Detail Modal --}}
    <flux:modal name="session-detail" class="max-w-lg" wire:model.live="viewingSessionId">
        @if($this->viewingSession)
            @php $s = $this->viewingSession; @endphp
            <div class="flex items-center gap-3">
                <div class="grid size-10 place-items-center rounded-xl {{ $s->status === 'active' ? 'bg-emerald-500/10' : 'bg-smoke/10 dark:bg-ivory/10' }}">
                    <flux:icon name="signal" class="size-5 {{ $s->status === 'active' ? 'text-emerald-600 dark:text-emerald-400' : 'text-smoke/40 dark:text-ivory/40' }}" />
                </div>
                <div>
                    <flux:heading size="lg">Session Details</flux:heading>
                    <span class="font-mono text-xs text-smoke/50 dark:text-ivory/40">{{ $s->client_mac }}</span>
                </div>
            </div>

            <div class="mt-5 space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-ivory/50 px-3.5 py-2.5 dark:bg-smoke/40">
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-smoke/40 dark:text-ivory/35">Status</div>
                        <flux:badge size="sm" :color="$s->status === 'active' ? 'emerald' : ($s->status === 'expired' ? 'amber' : 'red')" class="mt-1">
                            {{ ucfirst($s->status) }}
                        </flux:badge>
                    </div>
                    <div class="rounded-xl bg-ivory/50 px-3.5 py-2.5 dark:bg-smoke/40">
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-smoke/40 dark:text-ivory/35">Plan</div>
                        <div class="mt-1 text-sm font-semibold text-smoke dark:text-ivory">{{ $s->plan?->name ?? '—' }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-ivory/50 px-3.5 py-2.5 dark:bg-smoke/40">
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-smoke/40 dark:text-ivory/35">IP Address</div>
                        <div class="mt-1 font-mono text-sm text-smoke dark:text-ivory">{{ $s->ip_address ?? '—' }}</div>
                    </div>
                    <div class="rounded-xl bg-ivory/50 px-3.5 py-2.5 dark:bg-smoke/40">
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-smoke/40 dark:text-ivory/35">AP MAC</div>
                        <div class="mt-1 font-mono text-sm text-smoke dark:text-ivory">{{ $s->ap_mac }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-ivory/50 px-3.5 py-2.5 dark:bg-smoke/40">
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-smoke/40 dark:text-ivory/35">SSID</div>
                        <div class="mt-1 text-sm text-smoke dark:text-ivory">{{ $s->ssid ?? '—' }}</div>
                    </div>
                    <div class="rounded-xl bg-ivory/50 px-3.5 py-2.5 dark:bg-smoke/40">
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-smoke/40 dark:text-ivory/35">Username</div>
                        <div class="mt-1 text-sm text-smoke dark:text-ivory">{{ $s->username ?? '—' }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-ivory/50 px-3.5 py-2.5 dark:bg-smoke/40">
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-smoke/40 dark:text-ivory/35">Data Used</div>
                        <div class="mt-1 text-sm font-semibold text-smoke dark:text-ivory">
                            {{ number_format($s->data_used_mb, 1) }} MB
                            @if($s->data_limit_mb)
                                <span class="font-normal text-smoke/40 dark:text-ivory/35">/ {{ number_format($s->data_limit_mb, 0) }} MB</span>
                            @endif
                        </div>
                        @if($s->data_limit_mb)
                            @php $pct = min(100, ($s->data_used_mb / $s->data_limit_mb) * 100); @endphp
                            <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-zinc-200/80 dark:bg-zinc-700/80">
                                <div class="h-full rounded-full transition-all duration-500 {{ $pct > 90 ? 'bg-red-500' : ($pct > 70 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $pct }}%"></div>
                            </div>
                        @endif
                    </div>
                    <div class="rounded-xl bg-ivory/50 px-3.5 py-2.5 dark:bg-smoke/40">
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-smoke/40 dark:text-ivory/35">Time Remaining</div>
                        <div class="mt-1 text-sm font-semibold text-smoke dark:text-ivory">{{ $s->timeRemaining() ?? 'Unlimited' }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-ivory/50 px-3.5 py-2.5 dark:bg-smoke/40">
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-smoke/40 dark:text-ivory/35">Started</div>
                        <div class="mt-1 text-sm text-smoke dark:text-ivory">{{ $s->time_started?->format('M d, Y H:i') ?? '—' }}</div>
                    </div>
                    <div class="rounded-xl bg-ivory/50 px-3.5 py-2.5 dark:bg-smoke/40">
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-smoke/40 dark:text-ivory/35">Expires</div>
                        <div class="mt-1 text-sm text-smoke dark:text-ivory">{{ $s->time_expires?->format('M d, Y H:i') ?? '—' }}</div>
                    </div>
                </div>

                @if($s->payments->isNotEmpty())
                    <div>
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-smoke/40 dark:text-ivory/35 mb-2">Payments</div>
                        <div class="space-y-2">
                            @foreach($s->payments as $payment)
                                <div class="flex items-center justify-between rounded-xl border border-ivory-darker/50 bg-white/60 px-3.5 py-2.5 dark:border-smoke-light/50 dark:bg-smoke-light/30">
                                    <div>
                                        <div class="text-sm font-bold text-smoke dark:text-ivory">{{ number_format($payment->amount, 0) }} <span class="text-xs font-normal text-smoke/40">TZS</span></div>
                                        <div class="text-[11px] text-smoke/45 dark:text-ivory/35">{{ ucfirst($payment->payment_method) }} &middot; {{ $payment->created_at->diffForHumans() }}</div>
                                    </div>
                                    <flux:badge size="sm" :color="$payment->status === 'completed' ? 'emerald' : ($payment->status === 'pending' ? 'amber' : 'red')">
                                        {{ ucfirst($payment->status) }}
                                    </flux:badge>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="mt-6 flex justify-end gap-2">
                @if($s->status === 'active')
                    <flux:button variant="danger" wire:click="disconnect({{ $s->id }})" wire:confirm="Disconnect this client?">
                        Disconnect
                    </flux:button>
                @endif
                <flux:button wire:click="closeSessionDetail">Close</flux:button>
            </div>
        @endif
    </flux:modal>
</div>
