<div class="space-y-5 p-4 sm:p-6 lg:p-8" wire:poll.30s>
    {{-- Page Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="grid size-9 place-items-center rounded-xl bg-gradient-to-br from-terra/20 to-terra/5 dark:from-terra/25 dark:to-terra/10">
                    <flux:icon name="server-stack" class="size-5 text-terra dark:text-terra-light" />
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-smoke dark:text-ivory">Devices</h1>
            </div>
            <p class="mt-1 text-xs text-smoke/50 dark:text-ivory/40">
                Manage your network access points and hardware
                @if($this->lastSyncedAt)
                    &middot; <span class="text-smoke/35 dark:text-ivory/30">Synced {{ $this->lastSyncedAt }}</span>
                @endif
            </p>
        </div>
        <div class="flex items-center gap-1.5">
            <flux:button wire:click="$toggle('showGuide')" variant="ghost" icon="question-mark-circle" size="sm" class="cursor-pointer" />
            <flux:button wire:click="syncFromOmada" wire:loading.attr="disabled" wire:target="syncFromOmada" variant="ghost" icon="arrow-path" size="sm" class="cursor-pointer">
                <span wire:loading.remove wire:target="syncFromOmada">Sync</span>
                <span wire:loading wire:target="syncFromOmada">Syncing&hellip;</span>
            </flux:button>
            <flux:button wire:click="create" icon="plus" size="sm" class="cursor-pointer !bg-terra !text-white hover:!opacity-90">Add</flux:button>
        </div>
    </div>

    {{-- Setup Guide (collapsible) --}}
    @if($showGuide)
        @php($deviceAdoptionStatus = $this->deviceAdoptionStatus)
        @php($pendingDeviceInventory = $this->pendingDeviceInventory)

        <div class="mb-4 rounded-2xl border border-sky-200 bg-sky-50/80 p-5 shadow-sm dark:border-sky-900/50 dark:bg-sky-950/30">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-2">
                    <flux:icon name="light-bulb" class="size-5 text-sky-600 dark:text-sky-400" />
                    <span class="text-sm font-semibold text-sky-800 dark:text-sky-300">{{ $deviceAdoptionStatus['title'] }}</span>
                </div>
                <div class="flex items-center gap-2">
                    @if($pendingDeviceInventory['status'] !== 'blocked')
                        <flux:button
                            type="button"
                            wire:click="refreshPendingDeviceInventory"
                            variant="ghost"
                            icon="arrow-path"
                            size="sm"
                        >
                            {{ __('Refresh pending inventory') }}
                        </flux:button>
                    @endif
                    <button wire:click="$set('showGuide', false)" class="text-sky-400 hover:text-sky-600">
                        <flux:icon name="x-mark" class="size-4" />
                    </button>
                </div>
            </div>
            <div class="mt-3 space-y-2.5 text-xs text-sky-700/90 dark:text-sky-400/90">
                <div class="rounded-lg bg-sky-100 p-2.5 text-sky-800 dark:bg-sky-900/30 dark:text-sky-300">
                    {{ $deviceAdoptionStatus['message'] }}
                </div>
                @foreach($deviceAdoptionStatus['steps'] as $index => $step)
                    <div class="flex gap-2.5">
                        <span class="flex size-5 shrink-0 items-center justify-center rounded-full bg-sky-600 text-[10px] font-bold text-white">{{ $index + 1 }}</span>
                        <span>{{ $step }}</span>
                    </div>
                @endforeach
                @foreach($deviceAdoptionStatus['blockers'] as $blocker)
                    <div class="rounded-lg border border-amber-300/60 bg-amber-100/80 p-2.5 text-amber-800 dark:border-amber-700/60 dark:bg-amber-900/30 dark:text-amber-300">
                        <strong>Blocked:</strong> {{ $blocker }}
                    </div>
                @endforeach
                @if($pendingDeviceInventory['status'] === 'ready')
                    <div class="rounded-lg bg-sky-100 p-2.5 text-sky-800 dark:bg-sky-900/30 dark:text-sky-300">
                        <strong>Discovered pending devices:</strong> {{ $pendingDeviceInventory['total'] }}
                    </div>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <div class="rounded-lg border border-sky-200/70 bg-white/80 p-2.5 dark:border-sky-800/60 dark:bg-sky-950/20">
                            <strong>Already in SKY:</strong> {{ $pendingDeviceInventory['correlation']['already_in_sky'] }}
                        </div>
                        <div class="rounded-lg border border-sky-200/70 bg-white/80 p-2.5 dark:border-sky-800/60 dark:bg-sky-950/20">
                            <strong>Not yet in SKY:</strong> {{ $pendingDeviceInventory['correlation']['not_in_sky'] }}
                        </div>
                    </div>
                    @foreach(['isolated' => 'Isolated', 'preconfig' => 'Preconfigured'] as $inventoryKey => $inventoryLabel)
                        @foreach($pendingDeviceInventory[$inventoryKey] as $device)
                            <div class="rounded-lg border border-sky-200/70 bg-white/80 p-2.5 dark:border-sky-800/60 dark:bg-sky-950/20">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="font-semibold">{{ $device['name'] }}</span>
                                    <div class="flex items-center gap-2">
                                        <span>{{ $inventoryLabel }}</span>
                                        <span>{{ $device['in_sky'] ? 'In SKY' : 'Not in SKY' }}</span>
                                    </div>
                                </div>
                                <div class="mt-1 text-[11px] opacity-80">{{ $device['mac'] }}</div>
                                @if($device['local_device_name'])
                                    <div class="mt-1 text-[11px] opacity-80">{{ __('Local device: :name (:status)', ['name' => $device['local_device_name'], 'status' => $device['local_device_status']]) }}</div>
                                @endif
                                @if($deviceAdoptionStatus['endpoint_verified'] && $this->canTriggerDeviceAdoption)
                                    <div class="mt-3 flex justify-end">
                                        <flux:button
                                            type="button"
                                            wire:click="selectPendingDeviceForAdoption('{{ $device['mac'] }}')"
                                            variant="ghost"
                                            size="sm"
                                            icon="wifi"
                                        >
                                            {{ $adoptDeviceMac === $device['mac'] ? __('Selected for adopt') : __('Use for adopt') }}
                                        </flux:button>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @endforeach

                    @if($deviceAdoptionStatus['endpoint_verified'] && $this->canTriggerDeviceAdoption && $pendingDeviceInventory['total'] > 0)
                        <div class="rounded-lg border border-sky-200/70 bg-white/80 p-2.5 dark:border-sky-800/60 dark:bg-sky-950/20">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <strong>Admin adopt trigger</strong>
                                    <div class="mt-1 text-[11px] opacity-80">{{ __('Use the device default username and password from the hardware label or vendor documentation before starting adoption.') }}</div>
                                </div>
                                <span class="rounded-full bg-sky-100 px-2 py-1 text-[10px] font-semibold text-sky-800 dark:bg-sky-900/40 dark:text-sky-300">{{ __('Verified endpoint') }}</span>
                            </div>

                            <div class="mt-3 grid gap-2 md:grid-cols-3">
                                <flux:input wire:model="adoptDeviceMac" label="Device MAC" placeholder="AA:BB:CC:DD:EE:FF" />
                                <flux:input wire:model="adoptDeviceUsername" label="Device username" placeholder="admin" />
                                <flux:input wire:model="adoptDevicePassword" type="password" label="Device password" placeholder="Enter device password" />
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <flux:button type="button" wire:click="startDeviceAdoption" icon="paper-airplane" class="!bg-terra !text-white hover:!opacity-90">
                                    {{ __('Start adopt request') }}
                                </flux:button>
                                <flux:button type="button" wire:click="checkAdoptDeviceResult" variant="ghost" icon="arrow-path">
                                    {{ __('Check adopt result') }}
                                </flux:button>
                            </div>

                            @if($adoptDeviceResult !== [])
                                <div class="mt-3 rounded-lg border p-2.5 text-xs leading-relaxed {{ $adoptDeviceResult['status'] === 'success' ? 'border-emerald-200 bg-emerald-50/80 text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300' : ($adoptDeviceResult['status'] === 'pending' ? 'border-sky-200 bg-sky-50/80 text-sky-800 dark:border-sky-900/50 dark:bg-sky-950/30 dark:text-sky-300' : 'border-amber-300/60 bg-amber-100/80 text-amber-800 dark:border-amber-700/60 dark:bg-amber-900/30 dark:text-amber-300') }}">
                                    <div class="flex items-center justify-between gap-3">
                                        <strong>{{ $adoptDeviceResult['title'] }}</strong>
                                        @if(! empty($adoptDeviceResult['device_mac']))
                                            <span class="rounded-full px-2 py-1 text-[10px] font-semibold {{ $adoptDeviceResult['status'] === 'success' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : ($adoptDeviceResult['status'] === 'pending' ? 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300') }}">{{ $adoptDeviceResult['device_mac'] }}</span>
                                        @endif
                                    </div>
                                    <div class="mt-1">{{ $adoptDeviceResult['message'] }}</div>
                                    @if(isset($adoptDeviceResult['adopt_error_code']) && $adoptDeviceResult['adopt_error_code'] !== null)
                                        <div class="mt-2 text-[11px] opacity-80">
                                            {{ __('Adopt error code: :code', ['code' => $adoptDeviceResult['adopt_error_code']]) }}
                                            @if(isset($adoptDeviceResult['adopt_failed_type']) && $adoptDeviceResult['adopt_failed_type'] !== null)
                                                · {{ __('Failed type: :type', ['type' => $adoptDeviceResult['adopt_failed_type']]) }}
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                @elseif($pendingDeviceInventory['status'] === 'unavailable' && $pendingDeviceInventory['error'])
                    <div class="rounded-lg border border-amber-300/60 bg-amber-100/80 p-2.5 text-amber-800 dark:border-amber-700/60 dark:bg-amber-900/30 dark:text-amber-300">
                        <strong>Discovery error:</strong> {{ $pendingDeviceInventory['error'] }}
                    </div>
                @endif
                <div class="mt-3 rounded-lg bg-sky-100 p-2.5 text-sky-800 dark:bg-sky-900/30 dark:text-sky-300">
                    <strong>Note:</strong> {{ $deviceAdoptionStatus['endpoint_verified'] ? 'Adoption endpoint has been verified for this controller version.' : 'Adoption is still treated as a manual controller step until the public Open API documents a supported adopt or assign-to-site endpoint.' }}
                </div>
            </div>
        </div>
    @endif

    @if(! $this->workspace->isOmadaReady())
        <x-workspace.provisioning-status :workspace="$this->workspace" class="mb-4" />
    @endif

    {{-- Stat Cards --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <button wire:click="$set('statusFilter', 'online')" class="cursor-pointer rounded-2xl border p-3.5 text-left transition-all duration-200 {{ $statusFilter === 'online' ? 'border-emerald-500/30 bg-emerald-500/5 shadow-sm shadow-emerald-500/10 dark:border-emerald-500/20' : 'border-ivory-darker/50 bg-white/70 hover:border-emerald-500/20 hover:shadow-sm dark:border-smoke-light/50 dark:bg-smoke-light/30 dark:hover:border-emerald-500/15' }}">
            <div class="flex items-center gap-2">
                <span class="size-2 rounded-full bg-emerald-500 shadow-sm shadow-emerald-500/40 {{ $statusFilter === 'online' ? 'animate-pulse' : '' }}"></span>
                <span class="text-[10px] font-semibold uppercase tracking-wider text-emerald-600/70 dark:text-emerald-400/70">Online</span>
            </div>
            <div class="mt-1.5 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->onlineCount }}</div>
        </button>
        <button wire:click="$set('statusFilter', 'offline')" class="cursor-pointer rounded-2xl border p-3.5 text-left transition-all duration-200 {{ $statusFilter === 'offline' ? 'border-smoke/20 bg-smoke/5 shadow-sm dark:border-ivory/15' : 'border-ivory-darker/50 bg-white/70 hover:border-smoke/15 hover:shadow-sm dark:border-smoke-light/50 dark:bg-smoke-light/30' }}">
            <div class="flex items-center gap-2">
                <span class="size-2 rounded-full bg-smoke/30 dark:bg-ivory/30"></span>
                <span class="text-[10px] font-semibold uppercase tracking-wider text-smoke/50 dark:text-ivory/40">Offline</span>
            </div>
            <div class="mt-1.5 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->offlineCount }}</div>
        </button>
        <button wire:click="$set('statusFilter', '')" class="cursor-pointer rounded-2xl border p-3.5 text-left transition-all duration-200 {{ $statusFilter === '' ? 'border-terra/30 bg-terra/5 shadow-sm shadow-terra/10 dark:border-terra/20' : 'border-ivory-darker/50 bg-white/70 hover:border-terra/20 hover:shadow-sm dark:border-smoke-light/50 dark:bg-smoke-light/30 dark:hover:border-terra/15' }}">
            <div class="flex items-center gap-2">
                <span class="size-2 rounded-full bg-terra"></span>
                <span class="text-[10px] font-semibold uppercase tracking-wider text-terra/70 dark:text-terra-light/70">Total</span>
            </div>
            <div class="mt-1.5 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->totalCount }}</div>
        </button>
        <div class="rounded-2xl border border-ivory-darker/50 bg-white/70 p-3.5 dark:border-smoke-light/50 dark:bg-smoke-light/30">
            <div class="flex items-center gap-2">
                <flux:icon name="users" class="size-3.5 text-sky-500/70" />
                <span class="text-[10px] font-semibold uppercase tracking-wider text-sky-600/70 dark:text-sky-400/70">Clients</span>
            </div>
            <div class="mt-1.5 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->totalClients }}</div>
        </div>
    </div>

    {{-- Search & Filters --}}
    <div class="flex flex-col gap-2 sm:flex-row">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search name, MAC, IP..." icon="magnifying-glass" class="sm:w-64" />
        <flux:select wire:model.live="statusFilter" class="sm:w-36">
            <option value="">All</option>
            <option value="online">Online</option>
            <option value="offline">Offline</option>
            <option value="unknown">Unknown</option>
        </flux:select>
    </div>

    {{-- Mobile Card View --}}
    <div class="space-y-2 sm:hidden">
        @forelse ($this->devices as $device)
            <div class="cursor-pointer rounded-2xl border border-ivory-darker/50 bg-white/80 p-3.5 shadow-sm transition-all duration-200 hover:shadow-md hover:border-terra/20 dark:border-smoke-light/50 dark:bg-smoke-light/30 dark:hover:border-terra/15">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            @if($device->status === 'online')
                                <span class="size-2 shrink-0 rounded-full bg-emerald-500 shadow-sm shadow-emerald-500/50 animate-pulse"></span>
                            @elseif($device->status === 'offline')
                                <span class="size-2 shrink-0 rounded-full bg-smoke/30 dark:bg-ivory/30"></span>
                            @else
                                <span class="size-2 shrink-0 rounded-full bg-amber-400"></span>
                            @endif
                            <span class="text-sm font-semibold text-smoke dark:text-ivory truncate">{{ $device->name }}</span>
                        </div>
                        <div class="mt-1 flex items-center gap-1.5 text-[11px] text-smoke/45 dark:text-ivory/35 truncate pl-4">
                            <span class="font-mono">{{ $device->ap_mac }}</span>
                            @if($device->model) &middot; {{ $device->model }} @endif
                        </div>
                    </div>
                    <flux:dropdown position="bottom" align="end">
                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" class="cursor-pointer" />
                        <flux:menu>
                            <flux:menu.item wire:click="edit({{ $device->id }})" icon="pencil-square">Edit</flux:menu.item>
                            @if($device->status === 'online')
                                <flux:menu.item wire:click="rebootDevice({{ $device->id }})" wire:confirm="Reboot '{{ $device->name }}'?" icon="arrow-path">Reboot</flux:menu.item>
                            @endif
                            <flux:menu.separator />
                            <flux:menu.item wire:click="delete({{ $device->id }})" wire:confirm="Remove '{{ $device->name }}'?" icon="trash" class="text-red-600 dark:text-red-400">Remove</flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>
                <div class="mt-2.5 flex items-center gap-3 text-[11px] text-smoke/40 dark:text-ivory/35 pl-4">
                    @if($device->status === 'online')
                        <span class="font-semibold text-emerald-600/80 dark:text-emerald-400/80">{{ $device->clients_count }} clients</span>
                    @endif
                    @if($device->uptime_seconds > 0)
                        <span>{{ $device->uptimeForHumans() }}</span>
                    @endif
                    <span class="ml-auto">{{ $device->last_seen_at?->diffForHumans() ?? 'Never' }}</span>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border-2 border-dashed border-ivory-darker/40 py-12 text-center dark:border-smoke-light/30">
                <div class="mx-auto grid size-14 place-items-center rounded-2xl bg-ivory/70 dark:bg-smoke-light/40">
                    <flux:icon name="server-stack" class="size-7 text-smoke/25 dark:text-ivory/20" />
                </div>
                <p class="mt-3 text-sm font-medium text-smoke/40 dark:text-ivory/35">No devices found</p>
                <p class="mt-1 text-xs text-smoke/30 dark:text-ivory/25">Add access points to start serving WiFi</p>
                <div class="mt-4 flex justify-center gap-2">
                    <flux:button wire:click="$set('showGuide', true)" variant="ghost" icon="question-mark-circle" size="sm" class="cursor-pointer">Guide</flux:button>
                    <flux:button wire:click="create" variant="ghost" icon="plus" size="sm" class="cursor-pointer">Add</flux:button>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Desktop Table --}}
    <div class="hidden sm:block overflow-hidden rounded-2xl border border-ivory-darker/60 bg-white/80 shadow-sm backdrop-blur-sm dark:border-smoke-light/60 dark:bg-smoke-light/30">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>MAC / IP</flux:table.column>
                <flux:table.column>Model</flux:table.column>
                <flux:table.column>Clients</flux:table.column>
                <flux:table.column>Uptime</flux:table.column>
                <flux:table.column>Firmware</flux:table.column>
                <flux:table.column>Last Seen</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->devices as $device)
                    <flux:table.row class="transition-colors duration-150 hover:bg-ivory/40 dark:hover:bg-smoke-light/20">
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                @if($device->status === 'online')
                                    <div class="size-2.5 rounded-full bg-emerald-500 shadow-sm shadow-emerald-500/50 animate-pulse"></div>
                                    <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-400">Online</span>
                                @elseif($device->status === 'offline')
                                    <div class="size-2.5 rounded-full bg-smoke/30 dark:bg-ivory/30"></div>
                                    <span class="text-xs text-smoke/50 dark:text-ivory/50">Offline</span>
                                @else
                                    <div class="size-2.5 rounded-full bg-amber-400"></div>
                                    <span class="text-xs text-amber-600 dark:text-amber-400">Unknown</span>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="font-semibold text-smoke dark:text-ivory">{{ $device->name }}</div>
                            @if($device->site_name)
                                <div class="text-[11px] text-smoke/40 dark:text-ivory/40">{{ $device->site_name }}</div>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="font-mono text-xs font-medium text-smoke dark:text-ivory">{{ $device->ap_mac }}</div>
                            <div class="font-mono text-[11px] text-smoke/40 dark:text-ivory/40">{{ $device->ip_address ?? '—' }}</div>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($device->model)
                                <span class="inline-flex items-center rounded-lg bg-smoke/6 px-2 py-0.5 text-[11px] font-medium text-smoke/70 dark:bg-ivory/8 dark:text-ivory/60">{{ $device->model }}</span>
                            @else
                                <span class="text-smoke/25 dark:text-ivory/20">—</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($device->status === 'online')
                                <span class="text-sm font-bold text-smoke dark:text-ivory">{{ $device->clients_count }}</span>
                            @else
                                <span class="text-smoke/25 dark:text-ivory/20">—</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-xs text-smoke/55 dark:text-ivory/45">
                            {{ $device->uptime_seconds > 0 ? $device->uptimeForHumans() : '—' }}
                        </flux:table.cell>
                        <flux:table.cell class="text-xs text-smoke/55 dark:text-ivory/45">
                            {{ $device->firmware_version ?? '—' }}
                        </flux:table.cell>
                        <flux:table.cell class="text-xs text-smoke/45 dark:text-ivory/35">
                            {{ $device->last_seen_at?->diffForHumans() ?? 'Never' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" class="cursor-pointer" />
                                <flux:menu>
                                    <flux:menu.item wire:click="edit({{ $device->id }})" icon="pencil-square">
                                        Edit
                                    </flux:menu.item>
                                    @if($device->status === 'online')
                                        <flux:menu.item wire:click="rebootDevice({{ $device->id }})" wire:confirm="Reboot '{{ $device->name }}'? It will disconnect all clients temporarily." icon="arrow-path">
                                            Reboot
                                        </flux:menu.item>
                                    @endif
                                    <flux:menu.separator />
                                    <flux:menu.item wire:click="delete({{ $device->id }})" wire:confirm="Remove device '{{ $device->name }}'?" icon="trash" class="text-red-600 dark:text-red-400">
                                        Remove
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="9" class="text-center">
                            <div class="py-12">
                                <div class="mx-auto grid size-14 place-items-center rounded-2xl bg-ivory/70 dark:bg-smoke-light/40">
                                    <flux:icon name="server-stack" class="size-7 text-smoke/20 dark:text-ivory/20" />
                                </div>
                                <p class="mt-3 text-sm font-medium text-smoke/40 dark:text-ivory/35">No devices found</p>
                                <p class="mt-1 text-xs text-smoke/30 dark:text-ivory/25">Add access points to start serving WiFi</p>
                                <div class="mt-4 flex justify-center gap-2">
                                    <flux:button wire:click="$set('showGuide', true)" variant="ghost" icon="question-mark-circle" size="sm" class="cursor-pointer">
                                        How to add devices
                                    </flux:button>
                                    <flux:button wire:click="create" variant="ghost" icon="plus" size="sm" class="cursor-pointer">
                                        Add manually
                                    </flux:button>
                                </div>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    {{-- Create / Edit Modal --}}
    <flux:modal name="device-form" class="max-w-lg" wire:model.live="showForm">
        <form wire:submit="save">
            <flux:heading size="lg">{{ $editingDeviceId ? 'Edit Device' : 'Add Device' }}</flux:heading>
            <flux:text class="mt-1 text-smoke/50 dark:text-ivory/50">
                {{ $editingDeviceId ? 'Update device details. Name changes push to Omada automatically.' : 'Add a device record for local tracking. Adopt via Omada Controller to sync it.' }}
            </flux:text>

            <div class="mt-6 space-y-4">
                <flux:input wire:model="name" label="Device Name" placeholder="e.g. Lobby AP" />

                <flux:input wire:model="ap_mac" label="MAC Address" placeholder="AA:BB:CC:DD:EE:FF" class="font-mono" :disabled="(bool) $editingDeviceId" />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="ip_address" label="IP Address (optional)" placeholder="192.168.0.100" class="font-mono" />
                    <flux:input wire:model="model" label="Model (optional)" placeholder="EAP620 HD" />
                </div>

                <flux:input wire:model="site_name" label="Site / Location (optional)" placeholder="e.g. Main Branch" />
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <flux:button wire:click="closeForm" variant="ghost">Cancel</flux:button>
                <flux:button type="submit" class="!bg-terra !text-white hover:!opacity-90">{{ $editingDeviceId ? 'Update' : 'Add Device' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
