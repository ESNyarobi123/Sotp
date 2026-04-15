<div wire:poll.60s>
    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <div class="grid size-11 place-items-center rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
                <flux:icon name="users" class="size-6 text-terra dark:text-terra-light" />
            </div>
            <div>
                <flux:heading size="lg" class="text-smoke dark:text-ivory">Clients</flux:heading>
                <flux:text class="mt-1 text-smoke/50 dark:text-ivory/50">Guest users and MAC address tracking</flux:text>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="mb-4 grid gap-3 sm:grid-cols-3">
        <div class="flex items-center gap-3 rounded-2xl border border-ivory-darker/70 bg-white/70 px-4 py-3 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
            <div class="grid size-11 place-items-center rounded-2xl border border-ivory-darker/70 bg-ivory/70 dark:border-smoke-light/70 dark:bg-smoke">
                <flux:icon name="users" class="size-6 text-terra dark:text-terra-light" />
            </div>
            <div>
                <div class="text-2xl font-bold text-smoke dark:text-ivory">{{ $this->totalClients }}</div>
                <div class="text-xs text-smoke/50 dark:text-ivory/50">Total Clients</div>
            </div>
        </div>
        <button wire:click="$set('statusFilter', 'active')" class="flex items-center gap-3 rounded-2xl border border-ivory-darker/70 bg-white/70 px-4 py-3 shadow-sm backdrop-blur transition hover:bg-white dark:border-smoke-light/70 dark:bg-smoke-light/40 {{ $statusFilter === 'active' ? 'ring-2 ring-terra/25' : '' }}">
            <div class="grid size-11 place-items-center rounded-2xl border border-ivory-darker/70 bg-ivory/70 dark:border-smoke-light/70 dark:bg-smoke">
                <flux:icon name="activity" class="size-6 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div class="text-left">
                <div class="text-2xl font-bold text-smoke dark:text-ivory">{{ $this->activeClients }}</div>
                <div class="text-xs text-smoke/50 dark:text-ivory/50">Online Now</div>
            </div>
        </button>
        <div class="flex items-center gap-3 rounded-2xl border border-ivory-darker/70 bg-white/70 px-4 py-3 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
            <div class="grid size-11 place-items-center rounded-2xl border border-ivory-darker/70 bg-ivory/70 dark:border-smoke-light/70 dark:bg-smoke">
                <flux:icon name="banknotes" class="size-6 text-terra dark:text-terra-light" />
            </div>
            <div>
                <div class="text-xl font-bold text-smoke dark:text-ivory">{{ $this->totalRevenueFromClients }} <span class="text-xs font-normal text-smoke/50">TZS</span></div>
                <div class="text-xs text-smoke/50 dark:text-ivory/50">Total Revenue</div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="mb-4 flex flex-col gap-2 sm:flex-row">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by MAC address..." icon="magnifying-glass" class="sm:w-72" />
        <flux:select wire:model.live="statusFilter" class="sm:w-44">
            <option value="">All Clients</option>
            <option value="active">Online Now</option>
            <option value="inactive">Offline</option>
        </flux:select>
    </div>

    {{-- Table --}}
    <flux:card class="rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
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
                    <flux:table.row>
                        <flux:table.cell class="font-mono text-sm font-medium text-smoke dark:text-ivory">
                            {{ $client->client_mac }}
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($client->has_active)
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                    <span class="size-2 rounded-full bg-emerald-500 animate-pulse"></span> Online
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 text-xs text-smoke/40 dark:text-ivory/40">
                                    <span class="size-2 rounded-full bg-smoke/30"></span> Offline
                                </span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" class="bg-terra/10 text-terra">{{ $client->total_sessions }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-sm text-smoke/80 dark:text-ivory/70">
                            @php $mb = (float) $client->total_data_mb; @endphp
                            {{ $mb >= 1024 ? round($mb / 1024, 1) . ' GB' : round($mb, 1) . ' MB' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($client->total_spent)
                                <span class="font-semibold text-smoke dark:text-ivory">{{ number_format($client->total_spent, 0) }}</span>
                                <span class="text-xs text-smoke/50">TZS</span>
                            @else
                                <span class="text-smoke/40 dark:text-ivory/40">—</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-xs text-smoke/50 dark:text-ivory/50">
                            {{ $client->last_seen ? \Carbon\Carbon::parse($client->last_seen)->diffForHumans() : '—' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:button variant="ghost" size="sm" icon="eye" wire:click="viewClient('{{ $client->client_mac }}')" />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center">
                            <div class="py-8">
                                <flux:icon name="users" class="mx-auto size-8 text-zinc-300" />
                                <flux:text class="mt-2">No clients found</flux:text>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

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
