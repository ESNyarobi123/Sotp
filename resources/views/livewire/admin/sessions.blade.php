<div wire:poll.10s>
    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <div class="grid size-11 place-items-center rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
                <flux:icon name="users" class="size-6 text-terra dark:text-terra-light" />
            </div>
            <div>
                <flux:heading size="lg" class="text-smoke dark:text-ivory">Live Sessions</flux:heading>
                <flux:text class="mt-1 text-smoke/50 dark:text-ivory/50">Currently connected WiFi clients</flux:text>
            </div>
        </div>
    </div>

    {{-- Status counters --}}
    <div class="mb-4 grid gap-3 sm:grid-cols-3">
        <button wire:click="$set('statusFilter', 'active')" class="group flex items-center gap-3 rounded-2xl border border-ivory-darker/70 bg-white/70 px-4 py-3 shadow-sm backdrop-blur transition hover:bg-white dark:border-smoke-light/70 dark:bg-smoke-light/40 dark:hover:bg-smoke-light/55 {{ $statusFilter === 'active' ? 'ring-2 ring-terra/25 dark:ring-terra/30' : '' }}">
            <div class="grid size-11 place-items-center rounded-2xl border border-ivory-darker/70 bg-ivory/70 dark:border-smoke-light/70 dark:bg-smoke">
                <flux:icon name="activity" class="size-6 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div class="text-left">
                <div class="text-2xl font-bold text-smoke dark:text-ivory">{{ $this->activeCount }}</div>
                <div class="text-xs text-smoke/50 dark:text-ivory/50">Active</div>
            </div>
        </button>
        <button wire:click="$set('statusFilter', 'expired')" class="group flex items-center gap-3 rounded-2xl border border-ivory-darker/70 bg-white/70 px-4 py-3 shadow-sm backdrop-blur transition hover:bg-white dark:border-smoke-light/70 dark:bg-smoke-light/40 dark:hover:bg-smoke-light/55 {{ $statusFilter === 'expired' ? 'ring-2 ring-terra/25 dark:ring-terra/30' : '' }}">
            <div class="grid size-11 place-items-center rounded-2xl border border-ivory-darker/70 bg-ivory/70 dark:border-smoke-light/70 dark:bg-smoke">
                <flux:icon name="activity" class="size-6 text-amber-600 dark:text-amber-400" />
            </div>
            <div class="text-left">
                <div class="text-2xl font-bold text-smoke dark:text-ivory">{{ $this->expiredCount }}</div>
                <div class="text-xs text-smoke/50 dark:text-ivory/50">Expired</div>
            </div>
        </button>
        <button wire:click="$set('statusFilter', '')" class="group flex items-center gap-3 rounded-2xl border border-ivory-darker/70 bg-white/70 px-4 py-3 shadow-sm backdrop-blur transition hover:bg-white dark:border-smoke-light/70 dark:bg-smoke-light/40 dark:hover:bg-smoke-light/55 {{ $statusFilter === '' ? 'ring-2 ring-terra/25 dark:ring-terra/30' : '' }}">
            <div class="grid size-11 place-items-center rounded-2xl border border-ivory-darker/70 bg-ivory/70 dark:border-smoke-light/70 dark:bg-smoke">
                <flux:icon name="activity" class="size-6 text-terra dark:text-terra-light" />
            </div>
            <div class="text-left">
                <div class="text-2xl font-bold text-smoke dark:text-ivory">{{ $this->totalCount }}</div>
                <div class="text-xs text-smoke/50 dark:text-ivory/50">Total</div>
            </div>
        </button>
    </div>

    {{-- Search & Filters --}}
    <div class="mb-4 flex flex-col gap-2 sm:flex-row">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search MAC, IP, SSID, username..." icon="magnifying-glass" class="sm:w-80" />
        <flux:select wire:model.live="statusFilter" class="sm:w-44">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="expired">Expired</option>
            <option value="disconnected">Disconnected</option>
        </flux:select>
    </div>

    {{-- Sessions Table --}}
    <flux:card class="rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
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
                    <flux:table.row>
                        <flux:table.cell class="font-mono text-xs">{{ $session->client_mac }}</flux:table.cell>
                        <flux:table.cell class="font-mono text-xs">{{ $session->ip_address ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="text-sm">{{ $session->ssid ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            @if($session->plan)
                                <flux:badge size="sm">{{ $session->plan->name }}</flux:badge>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="text-sm">{{ number_format($session->data_used_mb, 1) }} MB</div>
                            @if($session->data_limit_mb)
                                <div class="mt-0.5 h-1.5 w-16 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                    @php $pct = min(100, ($session->data_used_mb / $session->data_limit_mb) * 100); @endphp
                                    <div class="h-full rounded-full {{ $pct > 90 ? 'bg-red-500' : ($pct > 70 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $pct }}%"></div>
                                </div>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-xs">{{ $session->time_started?->diffForHumans() ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="text-xs">
                            @if($session->status === 'active' && $session->time_expires)
                                <span class="{{ $session->time_expires->diffInMinutes(now()) < 30 ? 'text-amber-600 dark:text-amber-400 font-medium' : '' }}">
                                    {{ $session->timeRemaining() }}
                                </span>
                            @elseif($session->status !== 'active')
                                <span class="text-zinc-400">—</span>
                            @else
                                <span class="text-zinc-500">Unlimited</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$session->status === 'active' ? 'emerald' : ($session->status === 'expired' ? 'amber' : 'red')">
                                {{ ucfirst($session->status) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
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
                            <div class="py-8">
                                <flux:icon name="signal-slash" class="mx-auto size-8 text-zinc-300" />
                                <flux:text class="mt-2">No sessions found</flux:text>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- Session Detail Modal --}}
    <flux:modal name="session-detail" class="max-w-lg" wire:model.live="viewingSessionId">
        @if($this->viewingSession)
            @php $s = $this->viewingSession; @endphp
            <flux:heading size="lg">Session Details</flux:heading>
            <flux:text class="mt-1">{{ $s->client_mac }}</flux:text>

            <div class="mt-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:text class="text-xs font-medium uppercase text-zinc-500">Status</flux:text>
                        <flux:badge size="sm" :color="$s->status === 'active' ? 'emerald' : ($s->status === 'expired' ? 'amber' : 'red')" class="mt-1">
                            {{ ucfirst($s->status) }}
                        </flux:badge>
                    </div>
                    <div>
                        <flux:text class="text-xs font-medium uppercase text-zinc-500">Plan</flux:text>
                        <div class="mt-1 text-sm font-medium text-zinc-900 dark:text-white">{{ $s->plan?->name ?? '—' }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:text class="text-xs font-medium uppercase text-zinc-500">IP Address</flux:text>
                        <div class="mt-1 font-mono text-sm text-zinc-900 dark:text-white">{{ $s->ip_address ?? '—' }}</div>
                    </div>
                    <div>
                        <flux:text class="text-xs font-medium uppercase text-zinc-500">AP MAC</flux:text>
                        <div class="mt-1 font-mono text-sm text-zinc-900 dark:text-white">{{ $s->ap_mac }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:text class="text-xs font-medium uppercase text-zinc-500">SSID</flux:text>
                        <div class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $s->ssid ?? '—' }}</div>
                    </div>
                    <div>
                        <flux:text class="text-xs font-medium uppercase text-zinc-500">Username</flux:text>
                        <div class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $s->username ?? '—' }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:text class="text-xs font-medium uppercase text-zinc-500">Data Used</flux:text>
                        <div class="mt-1 text-sm text-zinc-900 dark:text-white">
                            {{ number_format($s->data_used_mb, 1) }} MB
                            @if($s->data_limit_mb)
                                / {{ number_format($s->data_limit_mb, 0) }} MB
                            @endif
                        </div>
                    </div>
                    <div>
                        <flux:text class="text-xs font-medium uppercase text-zinc-500">Time Remaining</flux:text>
                        <div class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $s->timeRemaining() ?? 'Unlimited' }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:text class="text-xs font-medium uppercase text-zinc-500">Started</flux:text>
                        <div class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $s->time_started?->format('M d, Y H:i') ?? '—' }}</div>
                    </div>
                    <div>
                        <flux:text class="text-xs font-medium uppercase text-zinc-500">Expires</flux:text>
                        <div class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $s->time_expires?->format('M d, Y H:i') ?? '—' }}</div>
                    </div>
                </div>

                @if($s->payments->isNotEmpty())
                    <div>
                        <flux:text class="text-xs font-medium uppercase text-zinc-500">Payments</flux:text>
                        <div class="mt-2 space-y-2">
                            @foreach($s->payments as $payment)
                                <div class="flex items-center justify-between rounded-lg border border-zinc-100 px-3 py-2 dark:border-zinc-700">
                                    <div>
                                        <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ number_format($payment->amount, 0) }} TZS</div>
                                        <div class="text-xs text-zinc-500">{{ ucfirst($payment->payment_method) }} &middot; {{ $payment->created_at->diffForHumans() }}</div>
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
