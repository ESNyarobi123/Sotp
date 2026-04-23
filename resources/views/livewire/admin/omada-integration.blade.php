<div>
    {{-- Page Header --}}
    <div class="mb-6 flex items-center gap-3">
        <div class="grid size-10 place-items-center rounded-xl bg-gradient-to-br from-terra/20 to-terra/5 dark:from-terra/25 dark:to-terra/10">
            <flux:icon name="plug" class="size-5 text-terra dark:text-terra-light" />
        </div>
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-smoke dark:text-ivory">Omada Integration</h1>
            <p class="mt-0.5 text-xs text-smoke/50 dark:text-ivory/40">Connect SKY Omada to your TP-Link Omada Controller</p>
        </div>
    </div>

    {{-- Connection Status Banner --}}
    @php $connected = $this->settings->exists && $this->settings->is_connected; @endphp
    <div class="mb-6 flex items-center gap-4 rounded-2xl border px-5 py-4 shadow-sm backdrop-blur
        {{ $connected
            ? 'border-emerald-200 bg-emerald-50/80 dark:border-emerald-900/50 dark:bg-emerald-950/30'
            : 'border-amber-200 bg-amber-50/80 dark:border-amber-900/50 dark:bg-amber-950/30' }}">
        <div class="relative shrink-0">
            <div class="size-11 rounded-2xl flex items-center justify-center
                {{ $connected ? 'bg-emerald-100 dark:bg-emerald-900/50' : 'bg-amber-100 dark:bg-amber-900/50' }}">
                <flux:icon name="{{ $connected ? 'check-circle' : 'exclamation-circle' }}"
                    class="size-6 {{ $connected ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}" />
            </div>
            @if($connected)
                <span class="absolute -right-0.5 -top-0.5 size-3 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-smoke animate-pulse"></span>
            @endif
        </div>
        <div class="flex-1 min-w-0">
            <div class="font-semibold {{ $connected ? 'text-emerald-800 dark:text-emerald-300' : 'text-amber-800 dark:text-amber-300' }}">
                {{ $connected ? 'Controller Connected' : 'Not Connected' }}
            </div>
            <div class="mt-0.5 text-xs {{ $connected ? 'text-emerald-700/70 dark:text-emerald-400/70' : 'text-amber-700/70 dark:text-amber-400/70' }}">
                @if($connected)
                    Last synced {{ $this->settings->last_synced_at?->diffForHumans() ?? 'never' }}
                    @if($this->settings->controller_url)
                        &middot; <span class="font-mono">{{ parse_url($this->settings->controller_url, PHP_URL_HOST) }}</span>
                    @endif
                @else
                    Save your controller credentials and click <strong>Test Connection</strong> to verify.
                @endif
            </div>
        </div>
        @if($connected && $this->settings->omada_id)
            <div class="hidden sm:block text-right shrink-0">
                <div class="text-xs text-emerald-700/60 dark:text-emerald-400/60 uppercase tracking-wider font-medium">Controller ID</div>
                <div class="mt-0.5 font-mono text-xs font-semibold text-emerald-800 dark:text-emerald-300">{{ $this->settings->omada_id }}</div>
            </div>
        @endif
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- ═══ LEFT — Settings Form (2/3) ═══ --}}
        <div class="lg:col-span-2">
            <form wire:submit="save" class="space-y-4">

                {{-- ① Controller Connection --}}
                <div class="rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
                    <div class="flex items-center gap-3 border-b border-ivory-darker/60 px-5 py-4 dark:border-smoke-light/40">
                        <div class="grid size-8 place-items-center rounded-xl bg-terra/10 dark:bg-terra/15">
                            <flux:icon name="server" class="size-4 text-terra dark:text-terra-light" />
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-smoke dark:text-ivory">Controller Connection</div>
                            <div class="text-xs text-smoke/50 dark:text-ivory/40">Omada SDN Controller URL and admin credentials</div>
                        </div>
                    </div>
                    <div class="space-y-4 p-5">
                        <flux:input
                            wire:model="controller_url"
                            label="Controller URL"
                            placeholder="https://192.168.1.100:8043"
                            description="Full URL including port (e.g. :8043 for Omada software controller)"
                            icon="globe-alt"
                        />
                        <div class="grid gap-4 sm:grid-cols-2">
                            <flux:input wire:model="username" label="Admin Username" placeholder="admin" icon="user" />
                            <flux:input
                                wire:model="password"
                                type="password"
                                label="Admin Password"
                                placeholder="{{ $this->hasCredentials ? 'Leave blank to keep saved password' : 'Enter password' }}"
                            />
                        </div>
                        <div>
                            <flux:input
                                wire:model="api_key"
                                type="password"
                                label="Open API Client Secret"
                                placeholder="{{ $this->hasCredentials ? 'Leave blank to keep saved value' : 'Enter Client Secret (Open API)' }}"
                                description="Required for Omada Open API (Client Credentials). Leave blank to use legacy login."
                            />
                        </div>
                    </div>
                </div>

                {{-- ② Hotspot / Captive Portal --}}
                <div class="rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
                    <div class="flex items-center gap-3 border-b border-ivory-darker/60 px-5 py-4 dark:border-smoke-light/40">
                        <div class="grid size-8 place-items-center rounded-xl bg-terra/10 dark:bg-terra/15">
                            <flux:icon name="wifi" class="size-4 text-terra dark:text-terra-light" />
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-smoke dark:text-ivory">Hotspot &amp; Captive Portal</div>
                            <div class="text-xs text-smoke/50 dark:text-ivory/40">Operator credentials and external portal URL</div>
                        </div>
                    </div>
                    <div class="space-y-4 p-5">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <flux:input wire:model="hotspot_operator_name" label="Hotspot Operator Username" placeholder="hotspot_operator" icon="user-circle" />
                            <flux:input
                                wire:model="hotspot_operator_password"
                                type="password"
                                label="Hotspot Operator Password"
                                placeholder="{{ $this->hasCredentials ? 'Leave blank to keep saved' : 'Enter password' }}"
                            />
                        </div>
                        {{-- Auto-detected tunnel URL banner --}}
                        @if($this->hasTunnelDetected)
                            <div class="flex items-center gap-3 rounded-xl border border-sky-200 bg-sky-50/80 px-4 py-3 dark:border-sky-900/50 dark:bg-sky-950/30">
                                <flux:icon name="globe-alt" class="size-5 shrink-0 text-sky-600 dark:text-sky-400" />
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-semibold text-sky-800 dark:text-sky-300">
                                        Public URL Detected via {{ $this->tunnelProvider }}
                                    </div>
                                    <div class="mt-0.5 font-mono text-xs text-sky-700/80 dark:text-sky-400/80 truncate">
                                        {{ $this->detectedPublicUrl }}
                                    </div>
                                </div>
                                <button type="button" wire:click="useDetectedUrl"
                                    class="shrink-0 rounded-lg bg-sky-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-sky-700 transition">
                                    Use This URL
                                </button>
                            </div>
                        @endif

                        <div x-data="{ copied: false }">
                            <flux:input
                                wire:model="external_portal_url"
                                label="External Portal URL"
                                placeholder="{{ config('app.url') }}/portal"
                                description="Set this exact URL in Omada Controller → Hotspot → Portal → External Portal"
                                icon="link"
                            />
                            <div class="mt-1.5 flex items-center gap-3">
                                @if($external_portal_url)
                                    <button type="button"
                                        @click="navigator.clipboard.writeText('{{ $external_portal_url }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                        class="inline-flex items-center gap-1 text-xs text-terra hover:text-terra-dark dark:text-terra-light transition">
                                        <flux:icon x-show="!copied" name="clipboard-document" class="size-3.5" />
                                        <flux:icon x-show="copied" name="clipboard-document-check" class="size-3.5 text-emerald-600" />
                                        <span x-text="copied ? 'Copied!' : 'Copy URL'"></span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ③ Site Identifiers --}}
                <div class="rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
                    <div class="flex items-center gap-3 border-b border-ivory-darker/60 px-5 py-4 dark:border-smoke-light/40">
                        <div class="grid size-8 place-items-center rounded-xl bg-terra/10 dark:bg-terra/15">
                            <flux:icon name="identification" class="size-4 text-terra dark:text-terra-light" />
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-smoke dark:text-ivory">Site Identifiers</div>
                            <div class="text-xs text-smoke/50 dark:text-ivory/40">Auto-detected when you test the connection</div>
                        </div>
                        @if($this->settings->omada_id || $this->settings->site_id)
                            <flux:badge size="sm" color="emerald" class="ml-auto">Auto-detected</flux:badge>
                        @else
                            <flux:badge size="sm" color="zinc" class="ml-auto">Not yet detected</flux:badge>
                        @endif
                    </div>
                    <div class="grid gap-4 p-5 sm:grid-cols-2">
                        <flux:input
                            wire:model="site_id"
                            label="Site ID"
                            placeholder="Auto-detected on connection test"
                            class="font-mono"
                            description="Omada site identifier"
                            :readonly="(bool) $this->settings->site_id"
                        />
                        <flux:input
                            wire:model="omada_id"
                            label="Controller ID (omadacId)"
                            placeholder="Auto-detected on connection test"
                            class="font-mono"
                            description="Filled automatically from controller API"
                            :readonly="(bool) $this->settings->omada_id"
                        />
                    </div>
                </div>

                {{-- Save / Test --}}
                <div class="flex flex-wrap items-center gap-3 pt-1">
                    <flux:button type="submit" icon="check" class="!bg-terra !text-white hover:!opacity-90">
                        Save Settings
                    </flux:button>
                    <flux:button
                        wire:click="testConnection"
                        wire:loading.attr="disabled"
                        wire:target="testConnection"
                        variant="ghost"
                        icon="signal"
                        :disabled="$testing || !$this->hasCredentials"
                    >
                        <span wire:loading.remove wire:target="testConnection">Test Connection</span>
                        <span wire:loading wire:target="testConnection" class="flex items-center gap-1.5">
                            <flux:icon name="arrow-path" class="size-4 animate-spin" /> Testing…
                        </span>
                    </flux:button>
                    @if(!$this->hasCredentials)
                        <flux:text class="text-xs text-smoke/40 dark:text-ivory/40">Save settings first to enable connection test</flux:text>
                    @endif
                </div>
            </form>
        </div>

        {{-- ═══ RIGHT — Info Sidebar (1/3) ═══ --}}
        <div class="space-y-4">

            {{-- Quick Stats --}}
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-2xl border border-ivory-darker/70 bg-white/70 px-4 py-3 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
                    <div class="text-xs font-medium uppercase tracking-wider text-smoke/40 dark:text-ivory/40">Last Sync</div>
                    <div class="mt-1 text-sm font-semibold text-smoke dark:text-ivory">
                        {{ $this->settings->last_synced_at?->diffForHumans() ?? '—' }}
                    </div>
                </div>
                <div class="rounded-2xl border border-ivory-darker/70 bg-white/70 px-4 py-3 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
                    <div class="text-xs font-medium uppercase tracking-wider text-smoke/40 dark:text-ivory/40">Auth Mode</div>
                    <div class="mt-1 text-sm font-semibold text-smoke dark:text-ivory">
                        {{ config('services.omada.client_id') || ($this->settings->exists && $this->settings->api_key) ? 'Open API' : 'Not Set' }}
                    </div>
                </div>
            </div>

            {{-- Tunnel / Public URL Status --}}
            @if($this->tunnelProvider)
                <div class="rounded-2xl border border-sky-200 bg-sky-50/80 px-4 py-3 shadow-sm dark:border-sky-900/50 dark:bg-sky-950/30">
                    <div class="text-xs font-medium uppercase tracking-wider text-sky-600/70 dark:text-sky-400/60">Public URL</div>
                    <div class="mt-1 text-xs font-semibold text-sky-800 dark:text-sky-300">{{ $this->tunnelProvider }}</div>
                    <div class="mt-0.5 font-mono text-[11px] text-sky-700/70 dark:text-sky-400/60 truncate">{{ $this->detectedPublicUrl }}</div>
                </div>
            @endif

            @php($workspaceProvisioning = $this->workspace->provisioningSummary())
            @php($workspaceProvisioningError = $this->workspace->provisioningErrorSummary())
            @php($workspaceProvisioningLifecycle = $this->workspace->provisioningLifecycleSummary())
            @php($pendingDeviceInventory = $this->pendingDeviceInventory)

            <div class="rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
                <div class="border-b border-ivory-darker/60 px-5 py-4 dark:border-smoke-light/40">
                    <div class="flex items-center gap-2">
                        <flux:icon name="server-stack" class="size-4 text-terra dark:text-terra-light" />
                        <div class="text-sm font-semibold text-smoke dark:text-ivory">Workspace Provisioning</div>
                    </div>
                    <div class="mt-1 text-xs text-smoke/50 dark:text-ivory/40">Current Omada readiness for your signed-in workspace.</div>
                </div>
                <div class="space-y-4 p-5">
                    <div class="flex items-start justify-between gap-3 rounded-2xl border border-ivory-darker/70 bg-ivory/50 px-4 py-3 dark:border-smoke-light/60 dark:bg-smoke/45">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-smoke/40 dark:text-ivory/40">{{ __('Workspace') }}</div>
                            <div class="mt-1 text-sm font-semibold text-smoke dark:text-ivory">{{ $this->workspace->brand_name }}</div>
                        </div>
                        <flux:badge size="sm" :color="$workspaceProvisioning['badge_color']">
                            {{ ucfirst($workspaceProvisioning['status']) }}
                        </flux:badge>
                    </div>

                    <div class="rounded-2xl border border-ivory-darker/70 bg-ivory/50 px-4 py-3 dark:border-smoke-light/60 dark:bg-smoke/45">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-smoke/40 dark:text-ivory/40">{{ __('Omada site ID') }}</div>
                        <div class="mt-1 break-all font-mono text-xs font-semibold text-smoke dark:text-ivory">{{ $this->workspace->omada_site_id ?: __('Not assigned yet') }}</div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-ivory-darker/70 bg-ivory/50 px-4 py-3 dark:border-smoke-light/60 dark:bg-smoke/45">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-smoke/40 dark:text-ivory/40">{{ __('Attempts') }}</div>
                            <div class="mt-1 text-sm font-semibold text-smoke dark:text-ivory">{{ $workspaceProvisioningLifecycle['attempts'] }}</div>
                        </div>
                        <div class="rounded-2xl border border-ivory-darker/70 bg-ivory/50 px-4 py-3 dark:border-smoke-light/60 dark:bg-smoke/45">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-smoke/40 dark:text-ivory/40">{{ __('Last attempt') }}</div>
                            <div class="mt-1 text-sm font-semibold text-smoke dark:text-ivory">{{ $workspaceProvisioningLifecycle['last_attempted_human'] ?? __('Not yet attempted') }}</div>
                        </div>
                        <div class="rounded-2xl border border-ivory-darker/70 bg-ivory/50 px-4 py-3 dark:border-smoke-light/60 dark:bg-smoke/45">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-smoke/40 dark:text-ivory/40">{{ __('Next retry') }}</div>
                            <div class="mt-1 text-sm font-semibold text-smoke dark:text-ivory">{{ $workspaceProvisioningLifecycle['next_retry_human'] ?? __('No retry scheduled') }}</div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-ivory-darker/70 bg-ivory/50 px-4 py-3 text-xs leading-relaxed text-smoke/60 dark:border-smoke-light/60 dark:bg-smoke/45 dark:text-ivory/50">
                        {{ __($workspaceProvisioning['message']) }}
                    </div>

                    @if($this->workspace->provisioning_error)
                        <div class="rounded-2xl border border-red-200 bg-red-50/80 px-4 py-3 text-xs leading-relaxed text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300">
                            {{ $this->workspace->provisioning_error }}
                        </div>
                    @endif

                    @if($workspaceProvisioningError)
                        <div class="rounded-2xl border border-ivory-darker/70 bg-ivory/50 px-4 py-3 dark:border-smoke-light/60 dark:bg-smoke/45">
                            <div class="flex items-center justify-between gap-3">
                                <div class="text-xs font-semibold text-smoke dark:text-ivory">{{ $workspaceProvisioningError['title'] }}</div>
                                <flux:badge size="sm" :color="$workspaceProvisioningError['retryable'] ? 'amber' : 'zinc'">
                                    {{ $workspaceProvisioningError['retryable'] ? 'Retryable' : 'Needs Fix' }}
                                </flux:badge>
                            </div>
                            <div class="mt-1 text-[11px] leading-relaxed text-smoke/55 dark:text-ivory/45">{{ $workspaceProvisioningError['message'] }}</div>
                        </div>
                    @endif

                    @if(! $this->workspace->isOmadaReady())
                        <flux:button
                            type="button"
                            wire:click="retryProvisioning"
                            variant="ghost"
                            icon="arrow-path"
                            class="w-full justify-center"
                        >
                            {{ __('Retry Provisioning') }}
                        </flux:button>
                    @endif
                </div>
            </div>

            @php($deviceAdoptionStatus = $this->deviceAdoptionStatus)

            <div class="rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
                <div class="border-b border-ivory-darker/60 px-5 py-4 dark:border-smoke-light/40">
                    <div class="flex items-center gap-2">
                        <flux:icon name="clipboard-document-list" class="size-4 text-terra dark:text-terra-light" />
                        <div class="text-sm font-semibold text-smoke dark:text-ivory">Step 1 API Audit</div>
                    </div>
                    <div class="mt-1 text-xs text-smoke/50 dark:text-ivory/40">Current implementation status before we automate adopt and hotspot configuration.</div>
                </div>
                <div class="space-y-3 p-5">
                    @foreach($this->auditCapabilities as $capability)
                        <div class="rounded-2xl border border-ivory-darker/70 bg-ivory/50 p-4 dark:border-smoke-light/60 dark:bg-smoke/45">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-sm font-semibold text-smoke dark:text-ivory">{{ $capability['title'] }}</div>
                                    <div class="mt-1 text-xs leading-relaxed text-smoke/55 dark:text-ivory/45">{{ $capability['description'] }}</div>
                                </div>
                                <flux:badge
                                    size="sm"
                                    :color="$capability['status'] === 'implemented' ? 'emerald' : ($capability['status'] === 'needs_config' ? 'amber' : 'zinc')"
                                >
                                    {{ $capability['status'] === 'implemented' ? 'Implemented' : ($capability['status'] === 'needs_config' ? 'Needs Config' : 'Unverified') }}
                                </flux:badge>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
                <div class="border-b border-ivory-darker/60 px-5 py-4 dark:border-smoke-light/40">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <flux:icon name="wifi" class="size-4 text-terra dark:text-terra-light" />
                            <div class="text-sm font-semibold text-smoke dark:text-ivory">Step 3 Device Adoption</div>
                        </div>
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
                    </div>
                    <div class="mt-1 text-xs text-smoke/50 dark:text-ivory/40">Current status of adopt / assign-to-site automation for this workspace.</div>
                </div>
                <div class="space-y-3 p-5">
                    <div class="flex items-start justify-between gap-3 rounded-2xl border border-ivory-darker/70 bg-ivory/50 px-4 py-3 dark:border-smoke-light/60 dark:bg-smoke/45">
                        <div>
                            <div class="text-sm font-semibold text-smoke dark:text-ivory">{{ $deviceAdoptionStatus['title'] }}</div>
                            <div class="mt-1 text-xs leading-relaxed text-smoke/55 dark:text-ivory/45">{{ $deviceAdoptionStatus['message'] }}</div>
                        </div>
                        <flux:badge size="sm" :color="$deviceAdoptionStatus['badge_color']">
                            {{ $deviceAdoptionStatus['status'] === 'blocked' ? 'Blocked' : 'Manual' }}
                        </flux:badge>
                    </div>

                    @if($deviceAdoptionStatus['blockers'] !== [])
                        <div class="space-y-2">
                            @foreach($deviceAdoptionStatus['blockers'] as $blocker)
                                <div class="rounded-2xl border border-amber-200 bg-amber-50/80 px-4 py-3 text-xs leading-relaxed text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-300">
                                    {{ $blocker }}
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="space-y-2">
                        @foreach($deviceAdoptionStatus['steps'] as $index => $step)
                            <div class="flex gap-3 rounded-2xl border border-ivory-darker/70 bg-ivory/50 px-4 py-3 dark:border-smoke-light/60 dark:bg-smoke/45">
                                <div class="flex size-5 shrink-0 items-center justify-center rounded-full bg-terra/10 text-[10px] font-bold text-terra dark:bg-terra/20 dark:text-terra-light">
                                    {{ $index + 1 }}
                                </div>
                                <div class="text-xs leading-relaxed text-smoke/60 dark:text-ivory/50">{{ $step }}</div>
                            </div>
                        @endforeach
                    </div>

                    @if($pendingDeviceInventory['status'] === 'ready')
                        <div class="rounded-2xl border border-ivory-darker/70 bg-ivory/50 px-4 py-3 dark:border-smoke-light/60 dark:bg-smoke/45">
                            <div class="flex items-center justify-between gap-3">
                                <div class="text-xs font-semibold text-smoke dark:text-ivory">{{ __('Discovered pending devices') }}</div>
                                <flux:badge size="sm" color="sky">{{ $pendingDeviceInventory['total'] }}</flux:badge>
                            </div>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                <div class="rounded-2xl border border-ivory-darker/60 bg-white/70 px-3 py-2 text-[11px] dark:border-smoke-light/50 dark:bg-smoke/40">
                                    <div class="text-smoke/45 dark:text-ivory/40">{{ __('Already in SKY') }}</div>
                                    <div class="mt-1 font-semibold text-smoke dark:text-ivory">{{ $pendingDeviceInventory['correlation']['already_in_sky'] }}</div>
                                </div>
                                <div class="rounded-2xl border border-ivory-darker/60 bg-white/70 px-3 py-2 text-[11px] dark:border-smoke-light/50 dark:bg-smoke/40">
                                    <div class="text-smoke/45 dark:text-ivory/40">{{ __('Not yet in SKY') }}</div>
                                    <div class="mt-1 font-semibold text-smoke dark:text-ivory">{{ $pendingDeviceInventory['correlation']['not_in_sky'] }}</div>
                                </div>
                            </div>
                            @if($pendingDeviceInventory['total'] === 0)
                                <div class="mt-2 text-[11px] leading-relaxed text-smoke/55 dark:text-ivory/45">{{ __('No isolated or preconfigured devices are currently visible for this site.') }}</div>
                            @else
                                <div class="mt-2 space-y-2">
                                    @foreach(['isolated' => 'Isolated', 'preconfig' => 'Preconfigured'] as $inventoryKey => $inventoryLabel)
                                        @foreach($pendingDeviceInventory[$inventoryKey] as $device)
                                            <div class="rounded-2xl border border-ivory-darker/60 bg-white/70 px-3 py-2 text-[11px] dark:border-smoke-light/50 dark:bg-smoke/40">
                                                <div class="flex items-center justify-between gap-3">
                                                    <div class="font-semibold text-smoke dark:text-ivory">{{ $device['name'] }}</div>
                                                    <div class="flex items-center gap-2">
                                                        <flux:badge size="sm" color="zinc">{{ $inventoryLabel }}</flux:badge>
                                                        <flux:badge size="sm" :color="$device['in_sky'] ? 'emerald' : 'amber'">
                                                            {{ $device['in_sky'] ? 'In SKY' : 'Not in SKY' }}
                                                        </flux:badge>
                                                    </div>
                                                </div>
                                                <div class="mt-1 text-smoke/55 dark:text-ivory/45">{{ $device['mac'] }}</div>
                                                @if($device['model'])
                                                    <div class="mt-0.5 text-smoke/45 dark:text-ivory/40">{{ $device['model'] }}</div>
                                                @endif
                                                @if($device['local_device_name'])
                                                    <div class="mt-0.5 text-smoke/45 dark:text-ivory/40">
                                                        {{ __('Local device: :name (:status)', ['name' => $device['local_device_name'], 'status' => $device['local_device_status']]) }}
                                                    </div>
                                                @endif
                                                @if($deviceAdoptionStatus['endpoint_verified'])
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
                                </div>
                            @endif
                        </div>

                        @if($deviceAdoptionStatus['endpoint_verified'] && $pendingDeviceInventory['total'] > 0)
                            <div class="rounded-2xl border border-ivory-darker/70 bg-ivory/50 px-4 py-3 dark:border-smoke-light/60 dark:bg-smoke/45">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-xs font-semibold text-smoke dark:text-ivory">{{ __('Admin adopt trigger') }}</div>
                                        <div class="mt-1 text-[11px] leading-relaxed text-smoke/55 dark:text-ivory/45">{{ __('Use the device default username and password from the hardware label or vendor documentation before starting adoption.') }}</div>
                                    </div>
                                    <flux:badge size="sm" color="sky">{{ __('Verified endpoint') }}</flux:badge>
                                </div>

                                <div class="mt-3 grid gap-3 md:grid-cols-3">
                                    <flux:input wire:model="adoptDeviceMac" label="Device MAC" placeholder="AA:BB:CC:DD:EE:FF" />
                                    <flux:input wire:model="adoptDeviceUsername" label="Device username" placeholder="admin" />
                                    <flux:input wire:model="adoptDevicePassword" type="password" label="Device password" placeholder="Enter device password" />
                                </div>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    <flux:button
                                        type="button"
                                        wire:click="startDeviceAdoption"
                                        icon="paper-airplane"
                                        class="!bg-terra !text-white hover:!opacity-90"
                                    >
                                        {{ __('Start adopt request') }}
                                    </flux:button>
                                    <flux:button
                                        type="button"
                                        wire:click="checkAdoptDeviceResult"
                                        variant="ghost"
                                        icon="arrow-path"
                                    >
                                        {{ __('Check adopt result') }}
                                    </flux:button>
                                </div>

                                @if($adoptDeviceResult !== [])
                                    <div class="mt-3 rounded-2xl border px-4 py-3 text-xs leading-relaxed {{ $adoptDeviceResult['status'] === 'success' ? 'border-emerald-200 bg-emerald-50/80 text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300' : ($adoptDeviceResult['status'] === 'pending' ? 'border-sky-200 bg-sky-50/80 text-sky-800 dark:border-sky-900/50 dark:bg-sky-950/30 dark:text-sky-300' : 'border-amber-200 bg-amber-50/80 text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-300') }}">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="font-semibold">{{ $adoptDeviceResult['title'] }}</div>
                                            @if(! empty($adoptDeviceResult['device_mac']))
                                                <flux:badge size="sm" :color="$adoptDeviceResult['status'] === 'success' ? 'emerald' : ($adoptDeviceResult['status'] === 'pending' ? 'sky' : 'amber')">
                                                    {{ $adoptDeviceResult['device_mac'] }}
                                                </flux:badge>
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
                        <div class="rounded-2xl border border-amber-200 bg-amber-50/80 px-4 py-3 text-xs leading-relaxed text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-300">
                            {{ $pendingDeviceInventory['error'] }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
                <div class="border-b border-ivory-darker/60 px-5 py-4 dark:border-smoke-light/40">
                    <div class="flex items-center gap-2">
                        <flux:icon name="check-badge" class="size-4 text-terra dark:text-terra-light" />
                        <div class="text-sm font-semibold text-smoke dark:text-ivory">Automation Readiness</div>
                    </div>
                    <div class="mt-1 text-xs text-smoke/50 dark:text-ivory/40">Configuration checklist for provisioning, sync, and portal automation.</div>
                </div>
                <div class="space-y-2 p-5">
                    @foreach($this->automationReadiness as $item)
                        <div class="flex items-start justify-between gap-3 rounded-2xl border border-ivory-darker/70 bg-ivory/50 px-4 py-3 dark:border-smoke-light/60 dark:bg-smoke/45">
                            <div class="min-w-0">
                                <div class="text-xs font-semibold text-smoke dark:text-ivory">{{ $item['label'] }}</div>
                                <div class="mt-0.5 text-[11px] text-smoke/50 dark:text-ivory/40">{{ $item['source'] }}</div>
                            </div>
                            <flux:badge size="sm" :color="$item['ready'] ? 'emerald' : 'amber'">
                                {{ $item['ready'] ? 'Ready' : 'Missing' }}
                            </flux:badge>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
                <div class="border-b border-ivory-darker/60 px-5 py-4 dark:border-smoke-light/40">
                    <div class="flex items-center gap-2">
                        <flux:icon name="shield-check" class="size-4 text-terra dark:text-terra-light" />
                        <div class="text-sm font-semibold text-smoke dark:text-ivory">Finalize Site Readiness</div>
                    </div>
                    <div class="mt-1 text-xs text-smoke/50 dark:text-ivory/40">Last-mile checks before this workspace can rely on Omada-powered portal and device actions.</div>
                </div>
                <div class="space-y-2 p-5">
                    @foreach($this->finalizeSiteReadiness as $item)
                        <div class="flex items-start justify-between gap-3 rounded-2xl border border-ivory-darker/70 bg-ivory/50 px-4 py-3 dark:border-smoke-light/60 dark:bg-smoke/45">
                            <div class="min-w-0">
                                <div class="text-xs font-semibold text-smoke dark:text-ivory">{{ $item['label'] }}</div>
                                <div class="mt-0.5 text-[11px] text-smoke/50 dark:text-ivory/40">{{ $item['source'] }}</div>
                            </div>
                            <flux:badge size="sm" :color="$item['ready'] ? 'emerald' : 'amber'">
                                {{ $item['ready'] ? 'Ready' : 'Missing' }}
                            </flux:badge>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Setup Guide --}}
            <div class="rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
                <div class="border-b border-ivory-darker/60 px-5 py-4 dark:border-smoke-light/40">
                    <div class="text-sm font-semibold text-smoke dark:text-ivory">Setup Guide</div>
                </div>
                <div class="space-y-0 divide-y divide-ivory-darker/60 dark:divide-smoke-light/40">
                    @foreach([
                        ['icon' => 'server', 'step' => '1', 'title' => 'Enter Controller URL', 'desc' => 'Full HTTPS URL of your Omada SDN controller including port.'],
                        ['icon' => 'user', 'step' => '2', 'title' => 'Add Admin Credentials', 'desc' => 'Your Omada admin username and password or Open API Client Secret.'],
                        ['icon' => 'wifi', 'step' => '3', 'title' => 'Configure Hotspot Operator', 'desc' => 'Create a Hotspot Operator in Omada and paste credentials here.'],
                        ['icon' => 'link', 'step' => '4', 'title' => 'Set Portal URL in Omada', 'desc' => 'Copy the External Portal URL into Omada → Hotspot → Portal settings.'],
                        ['icon' => 'signal', 'step' => '5', 'title' => 'Test & Auto-detect IDs', 'desc' => 'Save, click Test Connection — Site ID and Controller ID fill automatically.'],
                    ] as $item)
                        <div class="flex gap-3 px-5 py-3.5">
                            <div class="flex size-6 shrink-0 items-center justify-center rounded-full bg-terra/10 text-[11px] font-bold text-terra dark:bg-terra/20 dark:text-terra-light">
                                {{ $item['step'] }}
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-smoke dark:text-ivory">{{ $item['title'] }}</div>
                                <div class="mt-0.5 text-xs text-smoke/50 dark:text-ivory/40">{{ $item['desc'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Auth Mode Info --}}
            <div class="rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40 p-5">
                <div class="text-xs font-semibold uppercase tracking-wider text-smoke/40 dark:text-ivory/40 mb-3">Authentication Modes</div>
                <div class="space-y-3">
                    <div class="flex gap-2.5">
                        <div class="mt-0.5 size-2 shrink-0 rounded-full bg-terra"></div>
                        <div>
                            <div class="text-xs font-semibold text-smoke dark:text-ivory">Open API Automation</div>
                            <div class="text-xs text-smoke/50 dark:text-ivory/40">This is what provisioning, device sync, portal authorization, rename, and reboot rely on today.</div>
                        </div>
                    </div>
                    <div class="flex gap-2.5">
                        <div class="mt-0.5 size-2 shrink-0 rounded-full bg-smoke/30 dark:bg-ivory/30"></div>
                        <div>
                            <div class="text-xs font-semibold text-smoke dark:text-ivory">Controller Reachability Check</div>
                            <div class="text-xs text-smoke/50 dark:text-ivory/40">The admin form can still save controller details and test basic reachability, but adopt and hotspot config remain unverified until the real controller API doc confirms them.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40 p-5">
                <div class="text-xs font-semibold uppercase tracking-wider text-smoke/40 dark:text-ivory/40 mb-3">Audit Notes</div>
                <div class="space-y-2">
                    @foreach($this->auditNotes as $note)
                        <div class="rounded-xl border border-ivory-darker/70 bg-ivory/50 px-3 py-2 text-xs leading-relaxed text-smoke/55 dark:border-smoke-light/60 dark:bg-smoke/45 dark:text-ivory/45">
                            {{ $note }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
