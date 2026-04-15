<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout>
        {{-- Profile Hero Card --}}
        <div class="mb-6 overflow-hidden rounded-2xl border border-smoke/10 bg-white shadow-sm dark:border-white/10 dark:bg-smoke-light">
            {{-- Banner --}}
            <div class="h-24 bg-gradient-to-r from-terra/80 via-terra to-terra-dark"></div>

            {{-- Avatar + info --}}
            <div class="flex flex-col gap-4 px-6 pb-6 sm:flex-row sm:items-end">
                <div class="-mt-10 flex size-20 items-center justify-center rounded-2xl bg-terra text-2xl font-bold text-white shadow-lg ring-4 ring-white dark:ring-smoke-light">
                    {{ auth()->user()->initials() }}
                </div>
                <div class="flex-1 min-w-0 pb-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-bold text-smoke dark:text-ivory truncate">{{ auth()->user()->name }}</h2>
                        <span class="inline-flex items-center gap-1 rounded-full bg-terra/10 px-2.5 py-0.5 text-xs font-semibold text-terra dark:bg-terra/20 dark:text-terra-light">
                            <flux:icon name="shield-check" class="size-3" />
                            Admin
                        </span>
                        @if(auth()->user()->hasVerifiedEmail())
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                <flux:icon name="check-badge" class="size-3" />
                                Verified
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                <flux:icon name="exclamation-triangle" class="size-3" />
                                Unverified
                            </span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-smoke/50 dark:text-ivory/50">{{ auth()->user()->email }}</p>
                </div>
                <div class="flex gap-3 text-center pb-1">
                    <div class="rounded-xl bg-smoke/5 px-4 py-2 dark:bg-white/5">
                        <p class="text-lg font-bold text-smoke dark:text-ivory">{{ auth()->user()->created_at->format('M Y') }}</p>
                        <p class="text-xs text-smoke/50 dark:text-ivory/40">Joined</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Edit Form Card --}}
        <div class="rounded-2xl border border-smoke/10 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-smoke-light">
            <div class="mb-5 flex items-center gap-2">
                <flux:icon name="pencil-square" class="size-5 text-terra" />
                <div>
                    <h3 class="font-semibold text-smoke dark:text-ivory">{{ __('Edit Profile') }}</h3>
                    <p class="text-xs text-smoke/50 dark:text-ivory/40">{{ __('Update your name and email address') }}</p>
                </div>
            </div>

            <form wire:submit="updateProfileInformation" class="space-y-5">
                {{-- Name Field --}}
                <div>
                    <label class="mb-1.5 flex items-center gap-1.5 text-sm font-medium text-smoke dark:text-ivory">
                        <flux:icon name="user" class="size-4 text-terra" />
                        {{ __('Full Name') }}
                    </label>
                    <flux:input wire:model="name" type="text" required autofocus autocomplete="name" placeholder="Your full name" />
                </div>

                {{-- Email Field --}}
                <div>
                    <label class="mb-1.5 flex items-center gap-1.5 text-sm font-medium text-smoke dark:text-ivory">
                        <flux:icon name="envelope" class="size-4 text-terra" />
                        {{ __('Email Address') }}
                    </label>
                    <flux:input wire:model="email" type="email" required autocomplete="email" placeholder="your@email.com" />

                    @if ($this->hasUnverifiedEmail)
                        <div class="mt-3 flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:border-amber-800/40 dark:bg-amber-900/20 dark:text-amber-400">
                            <flux:icon name="exclamation-triangle" class="size-4 shrink-0" />
                            <span>
                                {{ __('Email not verified.') }}
                                <button type="button" wire:click.prevent="resendVerificationNotification" class="ml-1 underline underline-offset-2 hover:no-underline">
                                    {{ __('Resend verification email') }}
                                </button>
                            </span>
                        </div>
                    @endif
                </div>

                <div class="flex items-center justify-end pt-2">
                    <flux:button variant="primary" type="submit" class="!bg-terra !text-white hover:!opacity-90"
                        wire:loading.attr="disabled" wire:target="updateProfileInformation">
                        <span wire:loading.remove wire:target="updateProfileInformation" class="flex items-center gap-1">
                            <flux:icon name="check" class="size-4" /> {{ __('Save Changes') }}
                        </span>
                        <span wire:loading wire:target="updateProfileInformation" class="flex items-center gap-1.5">
                            <flux:icon name="arrow-path" class="size-4 animate-spin" /> {{ __('Saving…') }}
                        </span>
                    </flux:button>
                </div>
            </form>
        </div>

        {{-- Danger Zone --}}
        @if ($this->showDeleteUser)
            <div class="mt-6 rounded-2xl border border-red-200/60 bg-white p-6 shadow-sm dark:border-red-900/40 dark:bg-smoke-light">
                <div class="mb-4 flex items-center gap-2">
                    <flux:icon name="trash" class="size-5 text-red-500" />
                    <div>
                        <h3 class="font-semibold text-red-600 dark:text-red-400">{{ __('Danger Zone') }}</h3>
                        <p class="text-xs text-smoke/50 dark:text-ivory/40">{{ __('Permanent and irreversible actions') }}</p>
                    </div>
                </div>
                <livewire:settings.delete-user-form />
            </div>
        @endif
    </x-settings.layout>
</section>
