<div class="p-4 sm:p-6 lg:p-8 space-y-5">
    {{-- Page Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="grid size-9 place-items-center rounded-xl bg-gradient-to-br from-terra/20 to-terra/5 dark:from-terra/25 dark:to-terra/10">
                    <flux:icon name="building-office" class="size-5 text-terra dark:text-terra-light" />
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-smoke dark:text-ivory">Platform Workspaces</h1>
            </div>
            <p class="mt-1 text-xs text-smoke/50 dark:text-ivory/40">Manage all workspaces, limits, and suspension</p>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-ivory-darker/50 bg-white/70 p-3.5 dark:border-smoke-light/50 dark:bg-smoke-light/30">
            <div class="text-[10px] font-semibold uppercase tracking-wider text-smoke/50 dark:text-ivory/40">Total</div>
            <div class="mt-1.5 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->totalWorkspaces }}</div>
        </div>
        <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/5 p-3.5 dark:border-emerald-500/15">
            <div class="flex items-center gap-2">
                <span class="size-2 rounded-full bg-emerald-500"></span>
                <span class="text-[10px] font-semibold uppercase tracking-wider text-emerald-600/70 dark:text-emerald-400/70">Active</span>
            </div>
            <div class="mt-1.5 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->activeWorkspaces }}</div>
        </div>
        <div class="rounded-2xl border border-ivory-darker/50 bg-white/70 p-3.5 dark:border-smoke-light/50 dark:bg-smoke-light/30">
            <div class="text-[10px] font-semibold uppercase tracking-wider text-terra/70 dark:text-terra-light/70">Revenue</div>
            <div class="mt-1.5 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->totalRevenue }} <span class="text-xs font-normal text-smoke/35">TZS</span></div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col gap-2 sm:flex-row">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search brand, slug, owner..." icon="magnifying-glass" class="sm:w-64" />
        <flux:select wire:model.live="statusFilter" class="sm:w-36">
            <option value="">All</option>
            <option value="active">Active</option>
            <option value="suspended">Suspended</option>
            <option value="pending">Pending</option>
        </flux:select>
    </div>

    {{-- Mobile Card View --}}
    <div class="space-y-2 sm:hidden">
        @forelse ($this->workspaces as $ws)
            <div wire:click="viewWorkspace({{ $ws->id }})" class="cursor-pointer rounded-2xl border border-ivory-darker/50 bg-white/80 p-3.5 shadow-sm transition-all duration-200 hover:shadow-md hover:border-terra/20 dark:border-smoke-light/50 dark:bg-smoke-light/30 dark:hover:border-terra/15">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-semibold text-smoke dark:text-ivory truncate">{{ $ws->brand_name }}</div>
                        <div class="mt-0.5 text-[11px] text-smoke/45 dark:text-ivory/35 truncate">{{ $ws->user?->name }} &middot; {{ $ws->user?->email }}</div>
                    </div>
                    @if($ws->is_suspended)
                        <flux:badge size="sm" color="red">Suspended</flux:badge>
                    @elseif($ws->provisioning_status === 'ready')
                        <flux:badge size="sm" color="emerald">Active</flux:badge>
                    @else
                        <flux:badge size="sm" color="amber">{{ ucfirst($ws->provisioning_status ?? 'pending') }}</flux:badge>
                    @endif
                </div>
                <div class="mt-2.5 flex items-center justify-between text-[11px] text-smoke/40 dark:text-ivory/35">
                    <span class="font-medium">{{ $ws->devices()->count() }}/{{ $ws->max_devices }} devices</span>
                    <span class="font-bold text-smoke/60 dark:text-ivory/50">{{ number_format((float) ($ws->wallet?->available_balance ?? 0), 0) }} TZS</span>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border-2 border-dashed border-ivory-darker/40 py-12 text-center dark:border-smoke-light/30">
                <div class="mx-auto grid size-14 place-items-center rounded-2xl bg-ivory/70 dark:bg-smoke-light/40">
                    <flux:icon name="building-office" class="size-7 text-smoke/25 dark:text-ivory/20" />
                </div>
                <p class="mt-3 text-sm font-medium text-smoke/40 dark:text-ivory/35">No workspaces found</p>
            </div>
        @endforelse
        <div class="pt-2">{{ $this->workspaces->links() }}</div>
    </div>

    {{-- Desktop Table --}}
    <div class="hidden sm:block overflow-hidden rounded-2xl border border-ivory-darker/60 bg-white/80 shadow-sm backdrop-blur-sm dark:border-smoke-light/60 dark:bg-smoke-light/30">
        <flux:table :paginate="$this->workspaces">
            <flux:table.columns>
                <flux:table.column>Brand</flux:table.column>
                <flux:table.column>Owner</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Devices</flux:table.column>
                <flux:table.column>Wallet</flux:table.column>
                <flux:table.column>Created</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->workspaces as $ws)
                    <flux:table.row class="transition-colors duration-150 hover:bg-ivory/40 dark:hover:bg-smoke-light/20">
                        <flux:table.cell>
                            <div class="font-semibold text-smoke dark:text-ivory">{{ $ws->brand_name }}</div>
                            <div class="text-[11px] text-smoke/40 dark:text-ivory/35">{{ $ws->public_slug }}</div>
                        </flux:table.cell>
                        <flux:table.cell class="text-sm text-smoke/80 dark:text-ivory/70">
                            {{ $ws->user?->name ?? '—' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($ws->is_suspended)
                                <span class="inline-flex items-center gap-1.5 text-xs text-red-600 dark:text-red-400">
                                    <span class="size-2 rounded-full bg-red-500"></span> Suspended
                                </span>
                            @elseif($ws->provisioning_status === 'ready')
                                <span class="inline-flex items-center gap-1.5 text-xs text-emerald-600 dark:text-emerald-400">
                                    <span class="size-2 rounded-full bg-emerald-500"></span> Active
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 text-xs text-amber-600 dark:text-amber-400">
                                    <span class="size-2 rounded-full bg-amber-400"></span> {{ ucfirst($ws->provisioning_status ?? 'pending') }}
                                </span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="font-bold text-smoke dark:text-ivory">{{ $ws->devices()->count() }}</span>
                            <span class="text-[11px] text-smoke/35">/ {{ $ws->max_devices }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="font-bold text-smoke dark:text-ivory">{{ number_format((float) ($ws->wallet?->available_balance ?? 0), 0) }}</span>
                            <span class="text-[11px] text-smoke/35">TZS</span>
                        </flux:table.cell>
                        <flux:table.cell class="text-xs text-smoke/50 dark:text-ivory/40">
                            {{ $ws->created_at->format('M d, Y') }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-1">
                                <flux:button variant="ghost" size="sm" icon="eye" wire:click="viewWorkspace({{ $ws->id }})" />
                                <flux:button variant="ghost" size="sm" icon="adjustments-horizontal" wire:click="editLimits({{ $ws->id }})" title="Edit limits" />
                                @if($ws->is_suspended)
                                    <flux:button variant="ghost" size="sm" icon="check-circle" wire:click="unsuspend({{ $ws->id }})" class="text-emerald-600" title="Unsuspend" />
                                @else
                                    <flux:button variant="ghost" size="sm" icon="no-symbol" wire:click="suspend({{ $ws->id }})" wire:confirm="Suspend '{{ $ws->brand_name }}'?" class="text-red-500" title="Suspend" />
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center">
                            <div class="py-12">
                                <div class="mx-auto grid size-14 place-items-center rounded-2xl bg-ivory/70 dark:bg-smoke-light/40">
                                    <flux:icon name="building-office" class="size-7 text-smoke/20 dark:text-ivory/20" />
                                </div>
                                <p class="mt-3 text-sm font-medium text-smoke/40 dark:text-ivory/35">No workspaces found</p>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    {{-- Edit Limits Modal --}}
    <flux:modal wire:model="showForm" class="max-w-sm">
        <div class="space-y-4">
            <flux:heading size="lg">Edit Workspace Limits</flux:heading>
            <flux:input wire:model="maxDevices" label="Max Devices" type="number" min="1" max="1000" />
            <flux:input wire:model="maxPlans" label="Max Plans" type="number" min="1" max="500" />
            <flux:input wire:model="maxSessions" label="Max Concurrent Sessions (0 = unlimited)" type="number" min="0" max="100000" />
            <div class="flex justify-end gap-2 pt-2">
                <flux:button variant="ghost" wire:click="closeForm">Cancel</flux:button>
                <flux:button variant="primary" wire:click="saveLimits">Save</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Detail Modal --}}
    @if($this->viewingWorkspace)
        @php $ws = $this->viewingWorkspace; @endphp
        <flux:modal wire:model.live="viewingId" class="max-w-lg">
            <div class="space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-sm font-bold text-smoke dark:text-ivory">{{ $ws->brand_name }}</div>
                        <div class="text-xs text-smoke/50 dark:text-ivory/40">{{ $ws->public_slug }} &middot; {{ $ws->user?->email }}</div>
                    </div>
                    @if($ws->is_suspended)
                        <flux:badge color="red">Suspended</flux:badge>
                    @else
                        <flux:badge color="emerald">Active</flux:badge>
                    @endif
                </div>

                <div class="grid grid-cols-3 gap-3">
                    @php
                        $devCount = $ws->devices()->count();
                        $planCount = $ws->plans()->count();
                        $sessionCount = $ws->guestSessions()->where('status', 'active')->count();
                        $revenue = $ws->payments()->where('status', 'completed')->sum('amount');
                    @endphp
                    <div class="rounded-xl bg-ivory/50 p-3 text-center dark:bg-smoke/50">
                        <div class="text-lg font-bold text-smoke dark:text-ivory">{{ $devCount }}</div>
                        <div class="text-[10px] text-smoke/40 dark:text-ivory/35">Devices ({{ $ws->max_devices }} max)</div>
                    </div>
                    <div class="rounded-xl bg-ivory/50 p-3 text-center dark:bg-smoke/50">
                        <div class="text-lg font-bold text-smoke dark:text-ivory">{{ $planCount }}</div>
                        <div class="text-[10px] text-smoke/40 dark:text-ivory/35">Plans ({{ $ws->max_plans }} max)</div>
                    </div>
                    <div class="rounded-xl bg-ivory/50 p-3 text-center dark:bg-smoke/50">
                        <div class="text-lg font-bold text-terra">{{ number_format($revenue, 0) }}</div>
                        <div class="text-[10px] text-smoke/40 dark:text-ivory/35">Revenue TZS</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div>
                        <span class="text-smoke/40 dark:text-ivory/35">Provisioning</span>
                        <div class="font-medium text-smoke dark:text-ivory capitalize">{{ $ws->provisioning_status ?? 'pending' }}</div>
                    </div>
                    <div>
                        <span class="text-smoke/40 dark:text-ivory/35">Omada Site</span>
                        <div class="font-medium text-smoke dark:text-ivory">{{ $ws->omada_site_id ?? '—' }}</div>
                    </div>
                    <div>
                        <span class="text-smoke/40 dark:text-ivory/35">Active Sessions</span>
                        <div class="font-medium text-smoke dark:text-ivory">{{ $sessionCount }}</div>
                    </div>
                    <div>
                        <span class="text-smoke/40 dark:text-ivory/35">Wallet</span>
                        <div class="font-medium text-smoke dark:text-ivory">{{ number_format((float) ($ws->wallet?->available_balance ?? 0), 0) }} TZS</div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 border-t border-ivory-darker/30 pt-3 dark:border-smoke-light/30">
                    <flux:button size="sm" variant="ghost" icon="adjustments-horizontal" wire:click="editLimits({{ $ws->id }})">Edit Limits</flux:button>
                    @if($ws->is_suspended)
                        <flux:button size="sm" variant="primary" icon="check-circle" wire:click="unsuspend({{ $ws->id }})">Unsuspend</flux:button>
                    @else
                        <flux:button size="sm" variant="danger" icon="no-symbol" wire:click="suspend({{ $ws->id }})" wire:confirm="Suspend '{{ $ws->brand_name }}'?">Suspend</flux:button>
                    @endif
                </div>
            </div>
        </flux:modal>
    @endif
</div>
