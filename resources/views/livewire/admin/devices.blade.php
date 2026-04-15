<div wire:poll.30s>
    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <div class="grid size-11 place-items-center rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
                <flux:icon name="router" class="size-6 text-terra dark:text-terra-light" />
            </div>
            <div>
                <flux:heading size="lg" class="text-smoke dark:text-ivory">Devices (APs)</flux:heading>
                <flux:text class="mt-1 text-smoke/50 dark:text-ivory/50">Manage TP-Link access points</flux:text>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <flux:button wire:click="$toggle('showGuide')" variant="ghost" icon="question-mark-circle" size="sm" />
            <flux:button
                wire:click="syncFromOmada"
                wire:loading.attr="disabled"
                wire:target="syncFromOmada"
                variant="ghost"
                icon="arrow-path"
                class="relative"
            >
                <span wire:loading.remove wire:target="syncFromOmada">Sync from Omada</span>
                <span wire:loading wire:target="syncFromOmada">Syncing...</span>
            </flux:button>
            @if($this->lastSyncedAt)
                <span class="hidden text-xs text-smoke/40 dark:text-ivory/40 sm:inline">
                    Last sync: {{ $this->lastSyncedAt }}
                </span>
            @endif
            <flux:button wire:click="create" icon="plus" class="!bg-terra !text-white hover:!opacity-90">
                Add Device
            </flux:button>
        </div>
    </div>

    {{-- Setup Guide (collapsible) --}}
    @if($showGuide)
        <div class="mb-4 rounded-2xl border border-sky-200 bg-sky-50/80 p-5 shadow-sm dark:border-sky-900/50 dark:bg-sky-950/30">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-2">
                    <flux:icon name="light-bulb" class="size-5 text-sky-600 dark:text-sky-400" />
                    <span class="text-sm font-semibold text-sky-800 dark:text-sky-300">How to Add TP-Link Devices</span>
                </div>
                <button wire:click="$set('showGuide', false)" class="text-sky-400 hover:text-sky-600">
                    <flux:icon name="x-mark" class="size-4" />
                </button>
            </div>
            <div class="mt-3 space-y-2.5 text-xs text-sky-700/90 dark:text-sky-400/90">
                <div class="flex gap-2.5">
                    <span class="flex size-5 shrink-0 items-center justify-center rounded-full bg-sky-600 text-[10px] font-bold text-white">1</span>
                    <span><strong>Connect the AP</strong> to the same network as your Omada Controller (via Ethernet).</span>
                </div>
                <div class="flex gap-2.5">
                    <span class="flex size-5 shrink-0 items-center justify-center rounded-full bg-sky-600 text-[10px] font-bold text-white">2</span>
                    <span><strong>Open Omada Controller</strong> web UI or the Omada app. The AP will appear as "Pending".</span>
                </div>
                <div class="flex gap-2.5">
                    <span class="flex size-5 shrink-0 items-center justify-center rounded-full bg-sky-600 text-[10px] font-bold text-white">3</span>
                    <span><strong>Adopt the device</strong> — click "Adopt" in the controller. Wait for it to provision (1-3 minutes).</span>
                </div>
                <div class="flex gap-2.5">
                    <span class="flex size-5 shrink-0 items-center justify-center rounded-full bg-sky-600 text-[10px] font-bold text-white">4</span>
                    <span><strong>Click "Sync from Omada"</strong> above to pull the device into this dashboard automatically.</span>
                </div>
                <div class="mt-3 rounded-lg bg-sky-100 p-2.5 text-sky-800 dark:bg-sky-900/30 dark:text-sky-300">
                    <strong>Note:</strong> Device adoption is a network-level process and cannot be done via the API. The AP must be physically connected and discovered by the Omada Controller first.
                </div>
            </div>
        </div>
    @endif

    {{-- Status counters --}}
    <div class="mb-4 grid gap-3 sm:grid-cols-4">
        <button wire:click="$set('statusFilter', 'online')" class="group flex items-center gap-3 rounded-2xl border border-ivory-darker/70 bg-white/70 px-4 py-3 shadow-sm backdrop-blur transition hover:bg-white dark:border-smoke-light/70 dark:bg-smoke-light/40 dark:hover:bg-smoke-light/55 {{ $statusFilter === 'online' ? 'ring-2 ring-terra/25 dark:ring-terra/30' : '' }}">
            <div class="grid size-11 place-items-center rounded-2xl border border-ivory-darker/70 bg-ivory/70 dark:border-smoke-light/70 dark:bg-smoke">
                <flux:icon name="activity" class="size-6 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div class="text-left">
                <div class="text-2xl font-bold text-smoke dark:text-ivory">{{ $this->onlineCount }}</div>
                <div class="text-xs text-smoke/50 dark:text-ivory/50">Online</div>
            </div>
        </button>
        <button wire:click="$set('statusFilter', 'offline')" class="group flex items-center gap-3 rounded-2xl border border-ivory-darker/70 bg-white/70 px-4 py-3 shadow-sm backdrop-blur transition hover:bg-white dark:border-smoke-light/70 dark:bg-smoke-light/40 dark:hover:bg-smoke-light/55 {{ $statusFilter === 'offline' ? 'ring-2 ring-terra/25 dark:ring-terra/30' : '' }}">
            <div class="grid size-11 place-items-center rounded-2xl border border-ivory-darker/70 bg-ivory/70 dark:border-smoke-light/70 dark:bg-smoke">
                <flux:icon name="activity" class="size-6 text-smoke/40 dark:text-ivory/40" />
            </div>
            <div class="text-left">
                <div class="text-2xl font-bold text-smoke dark:text-ivory">{{ $this->offlineCount }}</div>
                <div class="text-xs text-smoke/50 dark:text-ivory/50">Offline</div>
            </div>
        </button>
        <button wire:click="$set('statusFilter', '')" class="group flex items-center gap-3 rounded-2xl border border-ivory-darker/70 bg-white/70 px-4 py-3 shadow-sm backdrop-blur transition hover:bg-white dark:border-smoke-light/70 dark:bg-smoke-light/40 dark:hover:bg-smoke-light/55 {{ $statusFilter === '' ? 'ring-2 ring-terra/25 dark:ring-terra/30' : '' }}">
            <div class="grid size-11 place-items-center rounded-2xl border border-ivory-darker/70 bg-ivory/70 dark:border-smoke-light/70 dark:bg-smoke">
                <flux:icon name="router" class="size-6 text-terra dark:text-terra-light" />
            </div>
            <div class="text-left">
                <div class="text-2xl font-bold text-smoke dark:text-ivory">{{ $this->totalCount }}</div>
                <div class="text-xs text-smoke/50 dark:text-ivory/50">Total</div>
            </div>
        </button>
        <div class="flex items-center gap-3 rounded-2xl border border-ivory-darker/70 bg-white/70 px-4 py-3 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
            <div class="grid size-11 place-items-center rounded-2xl border border-ivory-darker/70 bg-ivory/70 dark:border-smoke-light/70 dark:bg-smoke">
                <flux:icon name="users" class="size-6 text-sky-600 dark:text-sky-400" />
            </div>
            <div class="text-left">
                <div class="text-2xl font-bold text-smoke dark:text-ivory">{{ $this->totalClients }}</div>
                <div class="text-xs text-smoke/50 dark:text-ivory/50">Connected Clients</div>
            </div>
        </div>
    </div>

    {{-- Search & Filter --}}
    <div class="mb-4 flex flex-col gap-2 sm:flex-row">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search name, MAC, IP, site..." icon="magnifying-glass" class="sm:w-80" />
        <flux:select wire:model.live="statusFilter" class="sm:w-40">
            <option value="">All Status</option>
            <option value="online">Online</option>
            <option value="offline">Offline</option>
            <option value="unknown">Unknown</option>
        </flux:select>
    </div>

    {{-- Devices Table --}}
    <flux:card class="rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
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
                    <flux:table.row>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                @if($device->status === 'online')
                                    <div class="size-2.5 rounded-full bg-emerald-500 shadow-sm shadow-emerald-500/50"></div>
                                    <span class="text-xs font-medium text-emerald-700 dark:text-emerald-400">Online</span>
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
                            <div class="font-medium text-smoke dark:text-ivory">{{ $device->name }}</div>
                            @if($device->site_name)
                                <div class="text-[11px] text-smoke/40 dark:text-ivory/40">{{ $device->site_name }}</div>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="font-mono text-xs text-smoke dark:text-ivory">{{ $device->ap_mac }}</div>
                            <div class="font-mono text-[11px] text-smoke/40 dark:text-ivory/40">{{ $device->ip_address ?? '—' }}</div>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($device->model)
                                <flux:badge size="sm" variant="outline">{{ $device->model }}</flux:badge>
                            @else
                                <span class="text-smoke/30 dark:text-ivory/30">—</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($device->status === 'online')
                                <span class="text-sm font-semibold text-smoke dark:text-ivory">{{ $device->clients_count }}</span>
                            @else
                                <span class="text-smoke/30 dark:text-ivory/30">—</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-xs text-smoke/60 dark:text-ivory/50">
                            {{ $device->uptime_seconds > 0 ? $device->uptimeForHumans() : '—' }}
                        </flux:table.cell>
                        <flux:table.cell class="text-xs text-smoke/60 dark:text-ivory/50">
                            {{ $device->firmware_version ?? '—' }}
                        </flux:table.cell>
                        <flux:table.cell class="text-xs text-smoke/50 dark:text-ivory/40">
                            {{ $device->last_seen_at?->diffForHumans() ?? 'Never' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
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
                            <div class="py-8">
                                <flux:icon name="server-stack" class="mx-auto size-8 text-smoke/20 dark:text-ivory/20" />
                                <flux:text class="mt-2 text-smoke/50 dark:text-ivory/50">No devices found</flux:text>
                                <div class="mt-3 flex justify-center gap-2">
                                    <flux:button wire:click="$set('showGuide', true)" variant="ghost" icon="question-mark-circle" size="sm">
                                        How to add devices
                                    </flux:button>
                                    <flux:button wire:click="create" variant="ghost" icon="plus" size="sm">
                                        Add manually
                                    </flux:button>
                                </div>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

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
