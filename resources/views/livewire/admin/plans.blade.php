<div>
    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <div class="grid size-11 place-items-center rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
                <flux:icon name="list-checks" class="size-6 text-terra dark:text-terra-light" />
            </div>
            <div>
                <flux:heading size="lg" class="text-smoke dark:text-ivory">Plans / Packages</flux:heading>
                <flux:text class="mt-1 text-smoke/50 dark:text-ivory/50">{{ $this->activePlansCount }} active plans</flux:text>
            </div>
        </div>
        <flux:button wire:click="create" icon="plus" class="!bg-terra !text-white hover:!opacity-90">
            New Plan
        </flux:button>
    </div>

    {{-- Plans Grid --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($this->plans as $plan)
            <flux:card class="relative rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40 {{ $plan->is_active ? 'ring-2 ring-terra/15' : 'opacity-70' }}">
                {{-- Status indicator --}}
                <div class="absolute right-4 top-4">
                    <button wire:click="toggleActive({{ $plan->id }})" title="{{ $plan->is_active ? 'Deactivate' : 'Activate' }}">
                        <div class="size-3 rounded-full {{ $plan->is_active ? 'bg-terra' : 'bg-smoke/30 dark:bg-ivory/30' }}"></div>
                    </button>
                </div>

                {{-- Type badge --}}
                <flux:badge size="sm" class="{{ match($plan->type) { 'time' => 'bg-terra text-white', 'data' => 'bg-smoke/60 text-ivory', 'unlimited' => 'bg-terra/60 text-white', default => 'bg-zinc-400 text-white' } }}">
                    {{ ucfirst($plan->type) }}
                </flux:badge>

                {{-- Plan name --}}
                <div class="mt-3 text-lg font-bold text-smoke dark:text-ivory">{{ $plan->name }}</div>

                {{-- Value --}}
                <div class="mt-1 text-sm text-smoke/80 dark:text-ivory/70">{{ $plan->formattedValue() }}</div>

                {{-- Price --}}
                <div class="mt-3 text-2xl font-bold text-terra dark:text-terra-light">
                    {{ number_format($plan->price, 0) }}
                    <span class="text-sm font-normal text-smoke/50">TZS</span>
                </div>

                {{-- Validity --}}
                <div class="mt-1 text-xs text-smoke/50 dark:text-ivory/50">Valid for {{ $plan->validity_days }} {{ Str::plural('day', $plan->validity_days) }}</div>

                {{-- Description --}}
                @if($plan->description)
                    <flux:text class="mt-2 text-xs">{{ $plan->description }}</flux:text>
                @endif

                {{-- Actions --}}
                <div class="mt-4 flex gap-2 border-t border-ivory-darker pt-3 dark:border-smoke-light">
                    <flux:button variant="ghost" size="sm" icon="pencil-square" wire:click="edit({{ $plan->id }})">Edit</flux:button>
                    <flux:button variant="ghost" size="sm" icon="trash" wire:click="delete({{ $plan->id }})" wire:confirm="Delete '{{ $plan->name }}'? This cannot be undone." class="text-red-600 dark:text-red-400">Delete</flux:button>
                </div>
            </flux:card>
        @empty
            <div class="col-span-full">
                <flux:card>
                    <div class="py-12 text-center">
                        <flux:icon name="tag" class="mx-auto size-8 text-zinc-300" />
                        <flux:text class="mt-2">No plans created yet</flux:text>
                        <flux:button wire:click="create" variant="ghost" class="mt-4" icon="plus">Create your first plan</flux:button>
                    </div>
                </flux:card>
            </div>
        @endforelse
    </div>

    {{-- Create / Edit Modal --}}
    <flux:modal name="plan-form" class="max-w-lg" wire:model.live="showForm">
        <form wire:submit="save">
            <flux:heading size="lg">{{ $editingPlanId ? 'Edit Plan' : 'New Plan' }}</flux:heading>

            <div class="mt-6 space-y-4">
                <flux:input wire:model="name" label="Plan Name" placeholder="e.g. 1 Hour WiFi" />

                <flux:select wire:model.live="type" label="Plan Type">
                    <option value="time">Time-based</option>
                    <option value="data">Data-based</option>
                    <option value="unlimited">Unlimited</option>
                </flux:select>

                @if($type === 'time')
                    <flux:input wire:model="value" label="Duration (minutes)" type="number" min="1" placeholder="60" />
                @elseif($type === 'data')
                    <flux:input wire:model="value" label="Data (MB)" type="number" min="1" placeholder="500" />
                @else
                    <flux:input wire:model="duration_minutes" label="Duration (minutes)" type="number" min="1" placeholder="60" />
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="price" label="Price (TZS)" type="number" min="0" step="100" placeholder="1000" />
                    <flux:input wire:model="validity_days" label="Validity (days)" type="number" min="1" placeholder="1" />
                </div>

                <flux:input wire:model="description" label="Description (optional)" placeholder="Brief description..." />

                <flux:checkbox wire:model="is_active" label="Active" description="Available for purchase on captive portal" />
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <flux:button wire:click="closeForm" variant="ghost">Cancel</flux:button>
                <flux:button type="submit">{{ $editingPlanId ? 'Update Plan' : 'Create Plan' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
