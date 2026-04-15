<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-6">
        {{-- Header with icon --}}
        <div class="flex w-full flex-col items-center text-center">
            <div class="mb-3 flex items-center gap-2">
                <div class="grid size-10 place-items-center rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
                    <flux:icon name="shield" class="size-5 text-terra dark:text-terra-light" />
                </div>
                <flux:heading size="xl" class="text-smoke dark:text-ivory">{{ __('Log in to your account') }}</flux:heading>
            </div>
            <flux:subheading class="text-smoke/50 dark:text-ivory/50">{{ __('Enter your email and password below to log in') }}</flux:subheading>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <div>
                <div class="mb-1 flex items-center gap-1.5">
                    <flux:icon name="mail" class="size-4 text-terra dark:text-terra-light" />
                    <label class="text-sm font-medium text-smoke dark:text-ivory">{{ __('Email address') }}</label>
                </div>
                <flux:input
                    name="email"
                    :value="old('email')"
                    type="email"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="email@example.com"
                />
            </div>

            <!-- Password -->
            <div>
                <div class="mb-1 flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <flux:icon name="lock" class="size-4 text-terra dark:text-terra-light" />
                        <label class="text-sm font-medium text-smoke dark:text-ivory">{{ __('Password') }}</label>
                    </div>
                    @if (Route::has('password.request'))
                        <flux:link class="text-xs text-terra hover:text-terra-dark" :href="route('password.request')" wire:navigate>
                            {{ __('Forgot your password?') }}
                        </flux:link>
                    @endif
                </div>
                <flux:input
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Password')"
                    viewable
                />
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full !bg-terra !text-white hover:!opacity-90" data-test="login-button">
                    <flux:icon name="log-in" class="mr-1 size-4 text-white" />
                    {{ __('Log in') }}
                </flux:button>
            </div>
        </form>

        @if (Route::has('register'))
            <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-smoke/60 dark:text-ivory/60">
                <span>{{ __('Don\'t have an account?') }}</span>
                <flux:link class="!text-terra hover:!text-terra-dark" :href="route('register')" wire:navigate>{{ __('Sign up') }}</flux:link>
            </div>
        @endif
    </div>
</x-layouts::auth>
