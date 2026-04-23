@props([
    'workspace',
    'linkHref' => null,
    'linkLabel' => null,
])

@if(! $workspace->isOmadaReady())
    @php($provisioning = $workspace->provisioningSummary())
    @php($provisioningLifecycle = $workspace->provisioningLifecycleSummary())

    <flux:callout :variant="$provisioning['callout_variant']" :icon="$provisioning['icon']" {{ $attributes }}>
        <flux:callout.heading>{{ __($provisioning['title']) }}</flux:callout.heading>
        <flux:callout.text>
            {{ __($provisioning['message']) }}
            <span class="mt-2 block text-xs opacity-90">
                {{ __('Attempts: :count', ['count' => $provisioningLifecycle['attempts']]) }}
                @if($provisioningLifecycle['last_attempted_human'])
                    {{ __('· Last attempt :time', ['time' => $provisioningLifecycle['last_attempted_human']]) }}
                @endif
                @if($provisioningLifecycle['next_retry_human'])
                    {{ __('· Retry :time', ['time' => $provisioningLifecycle['next_retry_human']]) }}
                @endif
            </span>
            @if($workspace->provisioning_error)
                <span class="mt-2 block text-xs opacity-90">{{ $workspace->provisioning_error }}</span>
            @endif
            @if($linkHref && $linkLabel)
                <a href="{{ $linkHref }}" wire:navigate class="mt-3 inline-flex text-xs font-medium underline underline-offset-2">
                    {{ __($linkLabel) }}
                </a>
            @endif
        </flux:callout.text>
    </flux:callout>
@endif
