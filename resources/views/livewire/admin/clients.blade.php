<div class="space-y-5 p-4 sm:p-6 lg:p-8" wire:poll.60s>
    {{-- Page Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="grid size-9 place-items-center rounded-xl bg-gradient-to-br from-terra/20 to-terra/5 dark:from-terra/25 dark:to-terra/10">
                    <flux:icon name="users" class="size-5 text-terra dark:text-terra-light" />
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-smoke dark:text-ivory">Clients</h1>
            </div>
            <p class="mt-1 text-xs text-smoke/50 dark:text-ivory/40">Guest devices that have connected to your network</p>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
        <button wire:click="$set('statusFilter', 'active')" class="cursor-pointer rounded-2xl border p-3.5 text-left transition-all duration-200 {{ $statusFilter === 'active' ? 'border-emerald-500/30 bg-emerald-500/5 shadow-sm shadow-emerald-500/10 dark:border-emerald-500/20' : 'border-ivory-darker/50 bg-white/70 hover:border-emerald-500/20 hover:shadow-sm dark:border-smoke-light/50 dark:bg-smoke-light/30 dark:hover:border-emerald-500/15' }}">
            <div class="flex items-center gap-2">
                <span class="size-2 rounded-full bg-emerald-500 shadow-sm shadow-emerald-500/40 {{ $statusFilter === 'active' ? 'animate-pulse' : '' }}"></span>
                <span class="text-[10px] font-semibold uppercase tracking-wider text-emerald-600/70 dark:text-emerald-400/70">Online</span>
            </div>
            <div class="mt-1.5 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->activeClients }}</div>
        </button>
        <button wire:click="$set('statusFilter', '')" class="cursor-pointer rounded-2xl border p-3.5 text-left transition-all duration-200 {{ $statusFilter === '' ? 'border-terra/30 bg-terra/5 shadow-sm shadow-terra/10 dark:border-terra/20' : 'border-ivory-darker/50 bg-white/70 hover:border-terra/20 hover:shadow-sm dark:border-smoke-light/50 dark:bg-smoke-light/30 dark:hover:border-terra/15' }}">
            <div class="flex items-center gap-2">
                <span class="size-2 rounded-full bg-terra"></span>
                <span class="text-[10px] font-semibold uppercase tracking-wider text-terra/70 dark:text-terra-light/70">Total</span>
            </div>
            <div class="mt-1.5 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->totalClients }}</div>
        </button>
        <div class="rounded-2xl border border-ivory-darker/50 bg-white/70 p-3.5 dark:border-smoke-light/50 dark:bg-smoke-light/30">
            <div class="text-[10px] font-semibold uppercase tracking-wider text-terra/70 dark:text-terra-light/70">Revenue</div>
            <div class="mt-1.5 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->totalRevenueFromClients }} <span class="text-xs font-normal text-smoke/35">TZS</span></div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col gap-2 sm:flex-row">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search MAC..." icon="magnifying-glass" class="sm:w-56" />
        <flux:select wire:model.live="statusFilter" class="sm:w-36">
            <option value="">All</option>
            <option value="active">Online</option>
            <option value="inactive">Offline</option>
        </flux:select>
    </div>

    {{-- Mobile Card View --}}
    <div class="space-y-2 sm:hidden">
        @forelse ($this->clients as $client)
            <div wire:click="viewClient('{{ $client->client_mac }}')" class="cursor-pointer rounded-2xl border border-ivory-darker/50 bg-white/80 p-3.5 shadow-sm transition-all duration-200 hover:shadow-md hover:border-terra/20 dark:border-smoke-light/50 dark:bg-smoke-light/30 dark:hover:border-terra/15">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            @if($client->has_active)
                                <span class="size-2 shrink-0 rounded-full bg-emerald-500 shadow-sm shadow-emerald-500/40 animate-pulse"></span>
                            @endif
                            <span class="font-mono text-xs font-semibold text-smoke dark:text-ivory truncate">{{ $client->client_mac }}</span>
                        </div>
                        <div class="mt-1 flex items-center gap-2 text-[11px] text-smoke/45 dark:text-ivory/35">
                            @if(!$client->has_active)
                                <span class="inline-flex items-center gap-1 text-smoke/40 dark:text-ivory/40"><span class="size-1.5 rounded-full bg-smoke/30"></span> Offline</span>
                            @endif
                            <span>{{ $client->total_sessions }} sessions</span>
                        </div>
                    </div>
                    @if($client->total_spent)
                        <span class="text-xs font-bold text-smoke dark:text-ivory">{{ number_format($client->total_spent, 0) }} <span class="font-normal text-smoke/35">TZS</span></span>
                    @endif
                </div>
                <div class="mt-2.5 flex items-center justify-between text-[11px] text-smoke/40 dark:text-ivory/35">
                    @php $mb = (float) $client->total_data_mb; @endphp
                    <span class="font-medium">{{ $mb >= 1024 ? round($mb / 1024, 1) . ' GB' : round($mb, 1) . ' MB' }} used</span>
                    <span>{{ $client->last_seen ? \Carbon\Carbon::parse($client->last_seen)->diffForHumans() : '—' }}</span>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border-2 border-dashed border-ivory-darker/40 py-12 text-center dark:border-smoke-light/30">
                <div class="mx-auto grid size-14 place-items-center rounded-2xl bg-ivory/70 dark:bg-smoke-light/40">
                    <flux:icon name="users" class="size-7 text-smoke/25 dark:text-ivory/20" />
                </div>
                <p class="mt-3 text-sm font-medium text-smoke/40 dark:text-ivory/35">No clients found</p>
                <p class="mt-1 text-xs text-smoke/30 dark:text-ivory/25">Clients appear after their first connection</p>
            </div>
        @endforelse
        <div class="pt-2">{{ $this->clients->links() }}</div>
    </div>

    {{-- Desktop Table --}}
    <div class="hidden sm:block overflow-hidden rounded-2xl border border-ivory-darker/60 bg-white/80 shadow-sm backdrop-blur-sm dark:border-smoke-light/60 dark:bg-smoke-light/30">
        <flux:table :paginate="$this->clients">
            <flux:table.columns>
                <flux:table.column>MAC Address</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Sessions</flux:table.column>
                <flux:table.column>Data Used</flux:table.column>
                <flux:table.column>Total Spent</flux:table.column>
                <flux:table.column>Last Seen</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->clients as $client)
                    <flux:table.row class="transition-colors duration-150 hover:bg-ivory/40 dark:hover:bg-smoke-light/20 cursor-pointer" wire:click="viewClient('{{ $client->client_mac }}')">
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                @if($client->has_active)
                                    <span class="size-1.5 shrink-0 rounded-full bg-emerald-500 animate-pulse"></span>
                                @endif
                                <span class="font-mono text-xs font-medium text-smoke dark:text-ivory">{{ $client->client_mac }}</span>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($client->has_active)
                                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                    <span class="size-2 rounded-full bg-emerald-500 animate-pulse"></span> Online
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 text-xs text-smoke/40 dark:text-ivory/40">
                                    <span class="size-2 rounded-full bg-smoke/30"></span> Offline
                                </span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="inline-flex items-center rounded-lg bg-terra/8 px-2 py-0.5 text-[11px] font-medium text-terra dark:bg-terra/15 dark:text-terra-light">{{ $client->total_sessions }}</span>
                        </flux:table.cell>
                        <flux:table.cell class="text-sm font-medium text-smoke/80 dark:text-ivory/70">
                            @php $mb = (float) $client->total_data_mb; @endphp
                            {{ $mb >= 1024 ? round($mb / 1024, 1) . ' GB' : round($mb, 1) . ' MB' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($client->total_spent)
                                <span class="font-bold text-smoke dark:text-ivory">{{ number_format($client->total_spent, 0) }}</span>
                                <span class="text-xs text-smoke/40 dark:text-ivory/35">TZS</span>
                            @else
                                <span class="text-smoke/30 dark:text-ivory/25">—</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-xs text-smoke/45 dark:text-ivory/40">
                            {{ $client->last_seen ? \Carbon\Carbon::parse($client->last_seen)->diffForHumans() : '—' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:button variant="ghost" size="sm" icon="eye" wire:click="viewClient('{{ $client->client_mac }}')" class="cursor-pointer" />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center">
                            <div class="py-12">
                                <div class="mx-auto grid size-14 place-items-center rounded-2xl bg-ivory/70 dark:bg-smoke-light/40">
                                    <flux:icon name="users" class="size-7 text-smoke/20 dark:text-ivory/20" />
                                </div>
                                <p class="mt-3 text-sm font-medium text-smoke/40 dark:text-ivory/35">No clients found</p>
                                <p class="mt-1 text-xs text-smoke/30 dark:text-ivory/25">Clients appear after their first connection</p>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    {{-- Client Detail Modal --}}
    <flux:modal name="client-detail" class="max-w-2xl" wire:model.live="viewingMac">
        @if($this->viewingMac)
            <flux:heading size="lg">Client Profile</flux:heading>
            <flux:text class="mt-1 font-mono text-sm">{{ $this->viewingMac }}</flux:text>

            {{-- Sessions --}}
            <div class="mt-6">
                <flux:heading size="sm" class="mb-3 text-smoke dark:text-ivory">Recent Sessions</flux:heading>
                @if($this->clientSessions?->isNotEmpty())
                    <div class="space-y-2">
                        @foreach($this->clientSessions as $s)
                            <div class="flex items-center justify-between rounded-xl border border-ivory-darker/70 px-3 py-2 dark:border-smoke-light/70">
                                <div>
                                    <div class="text-xs font-medium text-smoke dark:text-ivory">{{ $s->plan?->name ?? 'No plan' }}</div>
                                    <div class="text-xs text-smoke/50 dark:text-ivory/50">
                                        {{ $s->time_started?->format('M d, H:i') ?? '—' }}
                                        @if($s->time_expires) · expires {{ $s->time_expires->format('H:i') }}@endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-smoke/50">{{ round($s->data_used_mb, 1) }} MB</span>
                                    <flux:badge size="sm" :color="match($s->status) { 'active' => 'emerald', 'expired' => 'amber', default => 'zinc' }">
                                        {{ ucfirst($s->status) }}
                                    </flux:badge>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <flux:text class="text-sm text-smoke/40 dark:text-ivory/40">No sessions recorded.</flux:text>
                @endif
            </div>

            {{-- Payments --}}
            <div class="mt-6">
                <flux:heading size="sm" class="mb-3 text-smoke dark:text-ivory">Payment History</flux:heading>
                @if($this->clientPayments?->isNotEmpty())
                    <div class="space-y-2">
                        @foreach($this->clientPayments as $p)
                            <div class="flex items-center justify-between rounded-xl border border-ivory-darker/70 px-3 py-2 dark:border-smoke-light/70">
                                <div>
                                    <div class="text-sm font-semibold text-smoke dark:text-ivory">{{ number_format($p->amount, 0) }} TZS</div>
                                    <div class="text-xs text-smoke/50 dark:text-ivory/50">{{ ucfirst($p->payment_method) }} · {{ $p->created_at->diffForHumans() }}</div>
                                </div>
                                <flux:badge size="sm" :color="match($p->status) { 'completed' => 'emerald', 'pending' => 'amber', 'failed' => 'red', default => 'zinc' }">
                                    {{ ucfirst($p->status) }}
                                </flux:badge>
                            </div>
                        @endforeach
                    </div>
                @else
                    <flux:text class="text-sm text-smoke/40 dark:text-ivory/40">No payments recorded.</flux:text>
                @endif
            </div>

            <div class="mt-6 flex justify-end">
                <flux:button wire:click="closeDetail">Close</flux:button>
            </div>
        @endif
    </flux:modal>
</div>
