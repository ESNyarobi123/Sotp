<section class="w-full p-4 sm:p-6 lg:p-8">
    @include('partials.settings-heading')

    <x-settings.layout>
        {{-- Profile banner --}}
        <div class="mb-5 overflow-hidden rounded-2xl border border-smoke/10 bg-gradient-to-r from-terra/90 to-terra-dark/90 shadow-sm dark:border-white/10">
            <div class="flex flex-col gap-4 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-white/15 text-lg font-bold text-white ring-1 ring-white/20 backdrop-blur">
                        {{ auth()->user()->initials() }}
                    </div>
                    <div class="min-w-0">
                        <h2 class="truncate text-lg font-bold text-white">{{ auth()->user()->name }}</h2>
                        <div class="flex flex-wrap items-center gap-2 mt-0.5">
                            <span class="text-xs text-white/65">{{ auth()->user()->email }}</span>
                            <span class="inline-flex items-center gap-1 rounded-full bg-white/15 px-2 py-0.5 text-[10px] font-semibold text-white/85 ring-1 ring-white/15">
                                {{ auth()->user()->hasRole('admin') ? __('Admin') : __('Customer') }}
                            </span>
                            @if(auth()->user()->hasVerifiedEmail())
                                <span class="size-1.5 rounded-full bg-emerald-400"></span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-300/20 px-2 py-0.5 text-[10px] font-semibold text-amber-100">{{ __('Unverified') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex gap-3 text-white text-center">
                    <div class="rounded-xl border border-white/15 bg-white/10 px-4 py-2 backdrop-blur-sm">
                        <p class="text-[10px] uppercase tracking-wider text-white/50">{{ __('Joined') }}</p>
                        <p class="mt-0.5 text-sm font-bold">{{ auth()->user()->created_at->format('M Y') }}</p>
                    </div>
                    <div class="rounded-xl border border-white/15 bg-white/10 px-4 py-2 backdrop-blur-sm">
                        <p class="text-[10px] uppercase tracking-wider text-white/50">{{ __('Workspace') }}</p>
                        <p class="mt-0.5 truncate text-sm font-bold max-w-[8rem]">{{ auth()->user()->workspace?->brand_name ?? __('Pending') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]">
            {{-- Edit Form --}}
            <div class="rounded-2xl border border-smoke/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-smoke-light">
                <h3 class="text-sm font-semibold text-smoke dark:text-ivory mb-4">{{ __('Account profile') }}</h3>

                <form wire:submit="updateProfileInformation" class="space-y-5">
                    <flux:input wire:model="name" :label="__('Full name')" type="text" required autofocus autocomplete="name" :placeholder="__('Your full name')" />
                    <flux:input wire:model="email" :label="__('Email address')" type="email" required autocomplete="email" :placeholder="__('your@email.com')" />

                    @if ($this->hasUnverifiedEmail)
                        <flux:callout variant="warning" icon="exclamation-triangle">
                            <flux:callout.heading>{{ __('Email verification pending') }}</flux:callout.heading>
                            <flux:callout.text>
                                {{ __('Verify your email to keep recovery working.') }}
                                <button type="button" wire:click.prevent="resendVerificationNotification" class="ml-1 font-medium underline underline-offset-2 hover:no-underline">
                                    {{ __('Resend') }}
                                </button>
                            </flux:callout.text>
                        </flux:callout>
                    @endif

                    <div class="flex items-center justify-end border-t border-smoke/10 pt-4 dark:border-white/10">
                        <flux:button variant="primary" type="submit" class="!bg-terra !text-white hover:!opacity-90"
                            wire:loading.attr="disabled" wire:target="updateProfileInformation">
                            <span wire:loading.remove wire:target="updateProfileInformation">{{ __('Save') }}</span>
                            <span wire:loading wire:target="updateProfileInformation">{{ __('Saving…') }}</span>
                        </flux:button>
                    </div>
                </form>
            </div>

            {{-- Summary --}}
            <div class="space-y-4">
                <div class="rounded-2xl border border-smoke/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-smoke-light">
                    <h3 class="text-sm font-semibold text-smoke dark:text-ivory mb-3">{{ __('Overview') }}</h3>
                    <div class="space-y-2">
                        @foreach([
                            [__('Name'), auth()->user()->name],
                            [__('Email'), auth()->user()->email],
                            [__('Workspace'), auth()->user()->workspace?->brand_name ?? __('Pending')],
                        ] as [$label, $value])
                            <div class="flex items-center justify-between rounded-xl bg-ivory/50 px-3 py-2 dark:bg-smoke/40">
                                <span class="text-[11px] font-medium uppercase tracking-wider text-smoke/40 dark:text-ivory/40">{{ $label }}</span>
                                <span class="text-sm font-medium text-smoke dark:text-ivory truncate max-w-[60%] text-right">{{ $value }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-2xl border border-smoke/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-smoke-light">
                    <h3 class="text-sm font-semibold text-smoke dark:text-ivory mb-3">{{ __('Tips') }}</h3>
                    <ul class="space-y-2 text-xs text-smoke/55 dark:text-ivory/45">
                        <li class="rounded-xl bg-ivory/50 px-3 py-2 dark:bg-smoke/40">{{ __('Use a long-term email for recovery.') }}</li>
                        <li class="rounded-xl bg-ivory/50 px-3 py-2 dark:bg-smoke/40">{{ __('Password & 2FA → Security tab.') }}</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Danger Zone --}}
        @if ($this->showDeleteUser)
            <div class="mt-5 rounded-2xl border border-red-200/50 bg-white p-5 shadow-sm dark:border-red-900/40 dark:bg-smoke-light">
                <div class="flex items-center gap-2 mb-3">
                    <flux:icon name="trash" class="size-4 text-red-500" />
                    <h3 class="text-sm font-semibold text-red-600 dark:text-red-400">{{ __('Danger Zone') }}</h3>
                </div>
                <livewire:settings.delete-user-form />
            </div>
        @endif
    </x-settings.layout>
</section>
