<div class="space-y-5 p-4 sm:p-6 lg:p-8">
    {{-- Page Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="grid size-9 place-items-center rounded-xl bg-gradient-to-br from-terra/20 to-terra/5 dark:from-terra/25 dark:to-terra/10">
                    <flux:icon name="tag" class="size-5 text-terra dark:text-terra-light" />
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-smoke dark:text-ivory">Plans</h1>
            </div>
            <p class="mt-1 text-xs text-smoke/50 dark:text-ivory/40">{{ $this->activePlansCount }} active plans available on your captive portal</p>
        </div>
        <flux:button wire:click="create" icon="plus" size="sm" class="cursor-pointer !bg-terra !text-white hover:!opacity-90">New Plan</flux:button>
    </div>

    {{-- Plans Grid --}}
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($this->plans as $plan)
            <div class="group relative overflow-hidden rounded-2xl border bg-white/80 p-4.5 shadow-sm backdrop-blur-sm transition-all duration-200 hover:shadow-md dark:bg-smoke-light/30 {{ $plan->is_active ? 'border-terra/20 dark:border-terra/15' : 'border-ivory-darker/50 opacity-60 dark:border-smoke-light/50' }}">
                {{-- Top row: badge + toggle --}}
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center rounded-lg px-2.5 py-0.5 text-[11px] font-semibold {{ match($plan->type) { 'time' => 'bg-terra/10 text-terra', 'data' => 'bg-sky-500/10 text-sky-700 dark:text-sky-400', 'unlimited' => 'bg-violet-500/10 text-violet-700 dark:text-violet-400', default => 'bg-smoke/10 text-smoke/60' } }}">
                        {{ ucfirst($plan->type) }}
                    </span>
                    <button wire:click="toggleActive({{ $plan->id }})" class="cursor-pointer group/toggle" title="{{ $plan->is_active ? 'Deactivate' : 'Activate' }}">
                        <span class="size-3 rounded-full block transition-all duration-200 {{ $plan->is_active ? 'bg-emerald-500 shadow-md shadow-emerald-500/40 group-hover/toggle:shadow-lg group-hover/toggle:shadow-emerald-500/50' : 'bg-smoke/25 dark:bg-ivory/25 group-hover/toggle:bg-smoke/40' }}"></span>
                    </button>
                </div>

                {{-- Name + Value --}}
                <div class="mt-3.5">
                    <div class="text-base font-bold text-smoke dark:text-ivory leading-tight">{{ $plan->name }}</div>
                    <div class="mt-1 flex items-center gap-1.5 text-xs text-smoke/50 dark:text-ivory/40">
                        <flux:icon name="clock" class="size-3" />
                        {{ $plan->formattedValue() }} &middot; {{ $plan->validity_days }}d validity
                    </div>
                </div>

                {{-- Price --}}
                <div class="mt-3.5 text-2xl font-bold text-terra dark:text-terra-light">
                    {{ number_format($plan->price, 0) }}
                    <span class="text-xs font-normal text-smoke/35">TZS</span>
                </div>

                @if($plan->description)
                    <p class="mt-2.5 text-[11px] leading-relaxed text-smoke/45 dark:text-ivory/35 line-clamp-2">{{ $plan->description }}</p>
                @endif

                {{-- Actions --}}
                <div class="mt-3.5 flex gap-1.5 border-t border-ivory-darker/40 pt-3 dark:border-smoke-light/30">
                    <flux:button variant="ghost" size="sm" icon="pencil-square" wire:click="edit({{ $plan->id }})" class="cursor-pointer">Edit</flux:button>
                    <flux:button variant="ghost" size="sm" icon="trash" wire:click="delete({{ $plan->id }})" wire:confirm="Delete '{{ $plan->name }}'?" class="cursor-pointer text-red-600 dark:text-red-400">Delete</flux:button>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-2xl border-2 border-dashed border-ivory-darker/40 py-14 text-center dark:border-smoke-light/30">
                <div class="mx-auto grid size-14 place-items-center rounded-2xl bg-ivory/70 dark:bg-smoke-light/40">
                    <flux:icon name="tag" class="size-7 text-smoke/25 dark:text-ivory/20" />
                </div>
                <p class="mt-3 text-sm font-medium text-smoke/40 dark:text-ivory/35">No plans yet</p>
                <p class="mt-1 text-xs text-smoke/30 dark:text-ivory/25">Create your first plan to start selling WiFi</p>
                <flux:button wire:click="create" variant="ghost" class="mt-4 cursor-pointer" icon="plus" size="sm">Create first plan</flux:button>
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
