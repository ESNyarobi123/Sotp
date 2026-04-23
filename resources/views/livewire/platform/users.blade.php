<div class="p-4 sm:p-6 lg:p-8 space-y-5">
    {{-- Page Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="grid size-9 place-items-center rounded-xl bg-gradient-to-br from-terra/20 to-terra/5 dark:from-terra/25 dark:to-terra/10">
                    <flux:icon name="users" class="size-5 text-terra dark:text-terra-light" />
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-smoke dark:text-ivory">Platform Users</h1>
            </div>
            <p class="mt-1 text-xs text-smoke/50 dark:text-ivory/40">Manage all users across the platform</p>
        </div>
        <flux:button wire:click="create" icon="plus" size="sm" class="cursor-pointer !bg-terra !text-white hover:!opacity-90">New User</flux:button>
    </div>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-ivory-darker/50 bg-white/70 p-3.5 dark:border-smoke-light/50 dark:bg-smoke-light/30">
            <div class="text-[10px] font-semibold uppercase tracking-wider text-smoke/50 dark:text-ivory/40">Total Users</div>
            <div class="mt-1.5 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->totalUsers }}</div>
        </div>
        <div class="rounded-2xl border border-ivory-darker/50 bg-white/70 p-3.5 dark:border-smoke-light/50 dark:bg-smoke-light/30">
            <div class="flex items-center gap-2">
                <span class="size-2 rounded-full bg-terra"></span>
                <span class="text-[10px] font-semibold uppercase tracking-wider text-terra/70 dark:text-terra-light/70">Admins</span>
            </div>
            <div class="mt-1.5 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->adminCount }}</div>
        </div>
        @if($this->suspendedCount > 0)
            <div class="rounded-2xl border border-red-500/20 bg-red-500/5 p-3.5 dark:border-red-500/15">
                <div class="flex items-center gap-2">
                    <span class="size-2 rounded-full bg-red-500"></span>
                    <span class="text-[10px] font-semibold uppercase tracking-wider text-red-600/70 dark:text-red-400/70">Suspended</span>
                </div>
                <div class="mt-1.5 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->suspendedCount }}</div>
            </div>
        @endif
    </div>

    {{-- Filters --}}
    <div class="flex flex-col gap-2 sm:flex-row">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search name or email..." icon="magnifying-glass" class="sm:w-64" />
        <flux:select wire:model.live="roleFilter" class="sm:w-36">
            <option value="">All Roles</option>
            <option value="admin">Admin</option>
            <option value="user">Customer</option>
        </flux:select>
    </div>

    {{-- Mobile Card View --}}
    <div class="space-y-2 sm:hidden">
        @forelse ($this->users as $user)
            <div wire:click="viewUser({{ $user->id }})" class="cursor-pointer rounded-2xl border border-ivory-darker/50 bg-white/80 p-3.5 shadow-sm transition-all duration-200 hover:shadow-md hover:border-terra/20 dark:border-smoke-light/50 dark:bg-smoke-light/30 dark:hover:border-terra/15">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-semibold text-smoke dark:text-ivory truncate">{{ $user->name }}</div>
                        <div class="mt-0.5 text-[11px] text-smoke/45 dark:text-ivory/35 truncate">{{ $user->email }}</div>
                    </div>
                    <div class="flex items-center gap-1.5">
                        @if($user->hasRole('admin'))
                            <flux:badge size="sm" color="amber">Admin</flux:badge>
                        @else
                            <flux:badge size="sm" color="zinc">Customer</flux:badge>
                        @endif
                        @if($user->workspace?->is_suspended)
                            <span class="size-2 rounded-full bg-red-500" title="Suspended"></span>
                        @endif
                    </div>
                </div>
                <div class="mt-2.5 flex items-center justify-between text-[11px] text-smoke/40 dark:text-ivory/35">
                    <span class="font-medium">{{ $user->workspace?->brand_name ?? 'No workspace' }}</span>
                    <span>{{ $user->created_at->diffForHumans() }}</span>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border-2 border-dashed border-ivory-darker/40 py-12 text-center dark:border-smoke-light/30">
                <div class="mx-auto grid size-14 place-items-center rounded-2xl bg-ivory/70 dark:bg-smoke-light/40">
                    <flux:icon name="users" class="size-7 text-smoke/25 dark:text-ivory/20" />
                </div>
                <p class="mt-3 text-sm font-medium text-smoke/40 dark:text-ivory/35">No users found</p>
            </div>
        @endforelse
        <div class="pt-2">{{ $this->users->links() }}</div>
    </div>

    {{-- Desktop Table --}}
    <div class="hidden sm:block overflow-hidden rounded-2xl border border-ivory-darker/60 bg-white/80 shadow-sm backdrop-blur-sm dark:border-smoke-light/60 dark:bg-smoke-light/30">
        <flux:table :paginate="$this->users">
            <flux:table.columns>
                <flux:table.column>User</flux:table.column>
                <flux:table.column>Workspace</flux:table.column>
                <flux:table.column>Role</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Joined</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->users as $user)
                    <flux:table.row class="transition-colors duration-150 hover:bg-ivory/40 dark:hover:bg-smoke-light/20">
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-terra/15 text-xs font-bold text-terra">
                                    {{ $user->initials() }}
                                </div>
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-smoke dark:text-ivory truncate">{{ $user->name }}</div>
                                    <div class="text-[11px] text-smoke/45 dark:text-ivory/35 truncate">{{ $user->email }}</div>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell class="text-sm">
                            {{ $user->workspace?->brand_name ?? '—' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($user->hasRole('admin'))
                                <flux:badge size="sm" color="amber">Admin</flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc">Customer</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($user->workspace?->is_suspended)
                                <span class="inline-flex items-center gap-1.5 text-xs text-red-600 dark:text-red-400">
                                    <span class="size-2 rounded-full bg-red-500"></span> Suspended
                                </span>
                            @elseif($user->email_verified_at)
                                <span class="inline-flex items-center gap-1.5 text-xs text-emerald-600 dark:text-emerald-400">
                                    <span class="size-2 rounded-full bg-emerald-500"></span> Active
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 text-xs text-amber-600 dark:text-amber-400">
                                    <span class="size-2 rounded-full bg-amber-400"></span> Unverified
                                </span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-xs text-smoke/50 dark:text-ivory/40">
                            {{ $user->created_at->format('M d, Y') }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-1">
                                <flux:button variant="ghost" size="sm" icon="eye" wire:click="viewUser({{ $user->id }})" />
                                <flux:button variant="ghost" size="sm" icon="pencil-square" wire:click="edit({{ $user->id }})" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center">
                            <div class="py-12">
                                <div class="mx-auto grid size-14 place-items-center rounded-2xl bg-ivory/70 dark:bg-smoke-light/40">
                                    <flux:icon name="users" class="size-7 text-smoke/20 dark:text-ivory/20" />
                                </div>
                                <p class="mt-3 text-sm font-medium text-smoke/40 dark:text-ivory/35">No users found</p>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    {{-- Create/Edit Modal --}}
    <flux:modal wire:model="showForm" class="max-w-lg">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $editingUserId ? 'Edit User' : 'New User' }}</flux:heading>
            <flux:input wire:model="name" label="Full Name" placeholder="John Doe" />
            <flux:input wire:model="email" label="Email" type="email" placeholder="john@example.com" />
            <flux:input wire:model="password" label="{{ $editingUserId ? 'New Password (leave blank to keep)' : 'Password' }}" type="password" />
            <flux:select wire:model="role" label="Role">
                <option value="user">Customer</option>
                <option value="admin">Admin</option>
            </flux:select>
            <flux:input wire:model="brandName" label="Workspace / Brand Name" placeholder="My WiFi Business" />
            <div class="flex justify-end gap-2 pt-2">
                <flux:button variant="ghost" wire:click="closeForm">Cancel</flux:button>
                <flux:button variant="primary" wire:click="save">{{ $editingUserId ? 'Update' : 'Create' }}</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- User Detail Modal --}}
    @if($this->viewingUser)
        <flux:modal wire:model.live="viewingUserId" class="max-w-lg">
            <div class="space-y-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-xl bg-terra/15 text-sm font-bold text-terra">
                            {{ $this->viewingUser->initials() }}
                        </div>
                        <div>
                            <div class="text-sm font-bold text-smoke dark:text-ivory">{{ $this->viewingUser->name }}</div>
                            <div class="text-xs text-smoke/50 dark:text-ivory/40">{{ $this->viewingUser->email }}</div>
                        </div>
                    </div>
                    @if($this->viewingUser->hasRole('admin'))
                        <flux:badge color="amber">Admin</flux:badge>
                    @else
                        <flux:badge color="zinc">Customer</flux:badge>
                    @endif
                </div>

                @if($ws = $this->viewingUser->workspace)
                    <div class="rounded-xl border border-ivory-darker/40 bg-ivory/30 p-3 dark:border-smoke-light/40 dark:bg-smoke/40">
                        <div class="text-xs font-semibold text-smoke/60 dark:text-ivory/50 mb-2">Workspace</div>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <span class="text-smoke/40 dark:text-ivory/35">Brand</span>
                                <div class="font-medium text-smoke dark:text-ivory">{{ $ws->brand_name }}</div>
                            </div>
                            <div>
                                <span class="text-smoke/40 dark:text-ivory/35">Status</span>
                                <div class="font-medium {{ $ws->is_suspended ? 'text-red-600' : 'text-emerald-600' }}">
                                    {{ $ws->is_suspended ? 'Suspended' : 'Active' }}
                                </div>
                            </div>
                            <div>
                                <span class="text-smoke/40 dark:text-ivory/35">Devices</span>
                                <div class="font-medium text-smoke dark:text-ivory">{{ $ws->devices()->count() }} / {{ $ws->max_devices }}</div>
                            </div>
                            <div>
                                <span class="text-smoke/40 dark:text-ivory/35">Plans</span>
                                <div class="font-medium text-smoke dark:text-ivory">{{ $ws->plans()->count() }} / {{ $ws->max_plans }}</div>
                            </div>
                            <div>
                                <span class="text-smoke/40 dark:text-ivory/35">Wallet</span>
                                <div class="font-medium text-smoke dark:text-ivory">{{ number_format((float) ($ws->wallet?->available_balance ?? 0), 0) }} TZS</div>
                            </div>
                            <div>
                                <span class="text-smoke/40 dark:text-ivory/35">Provisioning</span>
                                <div class="font-medium text-smoke dark:text-ivory capitalize">{{ $ws->provisioning_status ?? 'pending' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @if($ws->is_suspended)
                            <flux:button size="sm" variant="primary" icon="check-circle" wire:click="unsuspendWorkspace({{ $ws->id }})">Unsuspend</flux:button>
                        @else
                            <flux:button size="sm" variant="danger" icon="no-symbol" wire:click="suspendWorkspace({{ $ws->id }})" wire:confirm="Suspend this workspace?">Suspend</flux:button>
                        @endif
                        <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $this->viewingUser->id }})">Edit User</flux:button>
                    </div>
                @else
                    <div class="rounded-xl border border-dashed border-ivory-darker/40 py-4 text-center text-xs text-smoke/40 dark:border-smoke-light/40 dark:text-ivory/35">
                        No workspace attached
                    </div>
                @endif

                @if($this->viewingUser->id !== auth()->id())
                    <div class="border-t border-ivory-darker/30 pt-3 dark:border-smoke-light/30">
                        <flux:button size="sm" variant="danger" icon="trash" wire:click="deleteUser({{ $this->viewingUser->id }})" wire:confirm="Permanently delete this user and their workspace? This cannot be undone.">
                            Delete User
                        </flux:button>
                    </div>
                @endif
            </div>
        </flux:modal>
    @endif
</div>
