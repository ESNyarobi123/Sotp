<div>
    {{-- Page Header --}}
    <div class="mb-6 flex items-center gap-3">
        <div class="grid size-11 place-items-center rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
            <flux:icon name="plug" class="size-6 text-terra dark:text-terra-light" />
        </div>
        <div>
            <flux:heading size="lg" class="text-smoke dark:text-ivory">Omada Integration</flux:heading>
            <flux:text class="mt-0.5 text-smoke/50 dark:text-ivory/50">Connect SKY Omada to your TP-Link Omada Controller</flux:text>
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
                            <div class="text-xs font-semibold text-smoke dark:text-ivory">Open API (Recommended)</div>
                            <div class="text-xs text-smoke/50 dark:text-ivory/40">Uses Client ID + Client Secret from Omada Open API settings. More secure, token-based.</div>
                        </div>
                    </div>
                    <div class="flex gap-2.5">
                        <div class="mt-0.5 size-2 shrink-0 rounded-full bg-smoke/30 dark:bg-ivory/30"></div>
                        <div>
                            <div class="text-xs font-semibold text-smoke dark:text-ivory">Legacy Login</div>
                            <div class="text-xs text-smoke/50 dark:text-ivory/40">Uses username + password. Fallback when Open API Client Secret is not set.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
