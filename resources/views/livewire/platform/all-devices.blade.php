<div class="p-4 sm:p-6 lg:p-8 space-y-5">
    {{-- Page Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="grid size-9 place-items-center rounded-xl bg-gradient-to-br from-terra/20 to-terra/5 dark:from-terra/25 dark:to-terra/10">
                    <flux:icon name="cpu-chip" class="size-5 text-terra dark:text-terra-light" />
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-smoke dark:text-ivory">All Devices</h1>
            </div>
            <p class="mt-1 text-xs text-smoke/50 dark:text-ivory/40">Cross-workspace device overview</p>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-ivory-darker/50 bg-white/70 p-3.5 dark:border-smoke-light/50 dark:bg-smoke-light/30">
            <div class="text-[10px] font-semibold uppercase tracking-wider text-smoke/50 dark:text-ivory/40">Total</div>
            <div class="mt-1.5 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->totalCount }}</div>
        </div>
        <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/5 p-3.5 dark:border-emerald-500/15">
            <div class="flex items-center gap-2">
                <span class="size-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-[10px] font-semibold uppercase tracking-wider text-emerald-600/70 dark:text-emerald-400/70">Online</span>
            </div>
            <div class="mt-1.5 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->onlineCount }}</div>
        </div>
        <div class="rounded-2xl border border-ivory-darker/50 bg-white/70 p-3.5 dark:border-smoke-light/50 dark:bg-smoke-light/30">
            <div class="text-[10px] font-semibold uppercase tracking-wider text-terra/70 dark:text-terra-light/70">Clients</div>
            <div class="mt-1.5 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->totalClients }}</div>
        </div>
    </div>

    <div class="flex flex-col gap-2 sm:flex-row">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search name, MAC, workspace..." icon="magnifying-glass" class="sm:w-64" />
        <flux:select wire:model.live="statusFilter" class="sm:w-36">
            <option value="">All</option>
            <option value="online">Online</option>
            <option value="offline">Offline</option>
            <option value="unknown">Unknown</option>
        </flux:select>
    </div>

    {{-- Mobile --}}
    <div class="space-y-2 sm:hidden">
        @forelse ($this->devices as $device)
            <div class="rounded-2xl border border-ivory-darker/50 bg-white/80 p-3.5 shadow-sm transition-all duration-200 hover:shadow-md dark:border-smoke-light/50 dark:bg-smoke-light/30">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="size-2 shrink-0 rounded-full {{ $device->status === 'online' ? 'bg-emerald-500 shadow-sm shadow-emerald-500/40' : ($device->status === 'offline' ? 'bg-red-400' : 'bg-amber-400') }} {{ $device->status === 'online' ? 'animate-pulse' : '' }}"></span>
                            <span class="text-sm font-semibold text-smoke dark:text-ivory truncate">{{ $device->name }}</span>
                        </div>
                        <div class="mt-1 text-[11px] text-smoke/45 dark:text-ivory/35 truncate">
                            {{ $device->workspace?->brand_name ?? '—' }} &middot; {{ $device->ap_mac }}
                        </div>
                    </div>
                    <span class="shrink-0 text-xs font-bold text-smoke/60 dark:text-ivory/50">{{ $device->clients_count ?? 0 }} <span class="font-normal">clients</span></span>
                </div>
                <div class="mt-2.5 flex items-center justify-between text-[11px] text-smoke/40 dark:text-ivory/35">
                    <span class="font-medium">{{ $device->model ?? '—' }}</span>
                    <span>{{ $device->uptimeForHumans() }}</span>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border-2 border-dashed border-ivory-darker/40 py-12 text-center dark:border-smoke-light/30">
                <div class="mx-auto grid size-14 place-items-center rounded-2xl bg-ivory/70 dark:bg-smoke-light/40">
                    <flux:icon name="cpu-chip" class="size-7 text-smoke/25 dark:text-ivory/20" />
                </div>
                <p class="mt-3 text-sm font-medium text-smoke/40 dark:text-ivory/35">No devices found</p>
            </div>
        @endforelse
        <div class="pt-2">{{ $this->devices->links() }}</div>
    </div>

    {{-- Desktop --}}
    <div class="hidden sm:block overflow-hidden rounded-2xl border border-ivory-darker/60 bg-white/80 shadow-sm backdrop-blur-sm dark:border-smoke-light/60 dark:bg-smoke-light/30">
        <flux:table :paginate="$this->devices">
            <flux:table.columns>
                <flux:table.column>Workspace</flux:table.column>
                <flux:table.column>Device</flux:table.column>
                <flux:table.column>MAC</flux:table.column>
                <flux:table.column>Model</flux:table.column>
                <flux:table.column>Clients</flux:table.column>
                <flux:table.column>Uptime</flux:table.column>
                <flux:table.column>Status</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->devices as $device)
                    <flux:table.row class="transition-colors duration-150 hover:bg-ivory/40 dark:hover:bg-smoke-light/20">
                        <flux:table.cell class="text-sm font-semibold text-smoke dark:text-ivory">{{ $device->workspace?->brand_name ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="text-sm font-semibold text-smoke dark:text-ivory">{{ $device->name }}</flux:table.cell>
                        <flux:table.cell class="font-mono text-xs font-medium text-smoke/70 dark:text-ivory/60">{{ $device->ap_mac }}</flux:table.cell>
                        <flux:table.cell class="text-sm text-smoke/70 dark:text-ivory/60">{{ $device->model ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <span class="inline-flex items-center rounded-lg bg-terra/8 px-2 py-0.5 text-[11px] font-medium text-terra dark:bg-terra/15 dark:text-terra-light">{{ $device->clients_count ?? 0 }}</span>
                        </flux:table.cell>
                        <flux:table.cell class="text-xs text-smoke/45 dark:text-ivory/40">{{ $device->uptimeForHumans() }}</flux:table.cell>
                        <flux:table.cell>
                            <span class="inline-flex items-center gap-1.5 text-xs font-medium">
                                <span class="size-2 rounded-full {{ $device->status === 'online' ? 'bg-emerald-500 animate-pulse' : ($device->status === 'offline' ? 'bg-red-400' : 'bg-amber-400') }}"></span>
                                {{ ucfirst($device->status) }}
                            </span>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center">
                            <div class="py-12">
                                <div class="mx-auto grid size-14 place-items-center rounded-2xl bg-ivory/70 dark:bg-smoke-light/40">
                                    <flux:icon name="cpu-chip" class="size-7 text-smoke/20 dark:text-ivory/20" />
                                </div>
                                <p class="mt-3 text-sm font-medium text-smoke/40 dark:text-ivory/35">No devices found</p>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</div>
