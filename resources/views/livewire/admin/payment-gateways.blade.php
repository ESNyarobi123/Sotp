<div>
    {{-- Header --}}
    <div class="mb-6 flex items-center gap-3">
        <div class="grid size-11 place-items-center rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
            <flux:icon name="credit-card" class="size-6 text-terra dark:text-terra-light" />
        </div>
        <div>
            <flux:heading size="lg" class="text-smoke dark:text-ivory">Payment Gateways</flux:heading>
            <flux:text class="mt-1 text-smoke/50 dark:text-ivory/50">Configure ClickPesa for M-Pesa, Airtel Money, Tigo Pesa, HaloPesa</flux:text>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Main Content --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- ClickPesa Gateway Card --}}
            <flux:card class="rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex size-12 items-center justify-center rounded-xl bg-terra/10 text-terra dark:bg-terra/20 dark:text-terra-light">
                            <flux:icon name="credit-card" class="size-6" />
                        </div>
                        <div>
                            <div class="text-lg font-bold text-zinc-900 dark:text-white">ClickPesa</div>
                            <flux:text class="text-sm">Unified mobile money & card payments</flux:text>
                        </div>
                    </div>
                    @if($this->clickPesaSettings)
                        <flux:badge size="sm" :color="$this->clickPesaSettings->is_active ? 'emerald' : 'zinc'">
                            {{ $this->clickPesaSettings->is_active ? 'Active' : 'Inactive' }}
                        </flux:badge>
                    @else
                        <flux:badge size="sm" color="amber">Not configured</flux:badge>
                    @endif
                </div>

                {{-- Supported channels --}}
                <div class="mt-4 flex flex-wrap gap-2">
                    <flux:badge size="sm" color="green">M-Pesa</flux:badge>
                    <flux:badge size="sm" color="blue">Tigo Pesa</flux:badge>
                    <flux:badge size="sm" color="red">Airtel Money</flux:badge>
                    <flux:badge size="sm" color="violet">HaloPesa</flux:badge>
                    <flux:badge size="sm" color="sky">Card Payments</flux:badge>
                </div>

                {{-- Config summary --}}
                @if($this->clickPesaSettings)
                    <div class="mt-4 space-y-2 rounded-xl border border-ivory-darker/70 bg-ivory/50 p-3 dark:border-smoke-light/50 dark:bg-smoke/40">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium text-smoke/50 dark:text-ivory/40">Client ID</span>
                            <span class="font-mono text-xs font-medium text-smoke dark:text-ivory">{{ $this->clickPesaSettings->configValue('client_id') }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium text-smoke/50 dark:text-ivory/40">API Key</span>
                            <span class="text-xs text-smoke/40 dark:text-ivory/40">••••••••  (saved)</span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-medium text-smoke/50 dark:text-ivory/40">Webhook URL</span>
                            <span class="max-w-xs truncate font-mono text-xs text-smoke/80 dark:text-ivory/70">{{ $this->clickPesaSettings->configValue('webhook_url', url('/api/clickpesa/webhook')) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium text-smoke/50 dark:text-ivory/40">HMAC Checksum</span>
                            @if($this->clickPesaSettings->configValue('checksum_key'))
                                <flux:badge size="sm" color="emerald">Enabled</flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc">Disabled</flux:badge>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Actions --}}
                <div class="mt-4 flex gap-2">
                    <flux:button wire:click="editClickPesa" icon="pencil-square" size="sm" class="!bg-terra !text-white hover:!opacity-90">
                        {{ $this->clickPesaSettings ? 'Edit Settings' : 'Configure' }}
                    </flux:button>
                    @if($this->clickPesaSettings)
                        <flux:button
                            wire:click="testClickPesa"
                            wire:loading.attr="disabled"
                            wire:target="testClickPesa"
                            variant="ghost"
                            size="sm"
                            icon="signal"
                        >
                            <span wire:loading.remove wire:target="testClickPesa">Test Connection</span>
                            <span wire:loading wire:target="testClickPesa" class="flex items-center gap-1">
                                <flux:icon name="arrow-path" class="size-3.5 animate-spin" /> Testing…
                            </span>
                        </flux:button>
                    @endif
                </div>
            </flux:card>

            {{-- API Flow --}}
            <flux:card class="rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
                <flux:heading size="md" class="mb-4">Payment Flow</flux:heading>
                <div class="space-y-3">
                    <div class="flex items-start gap-3">
                        <div class="flex size-7 shrink-0 items-center justify-center rounded-full bg-terra/10 text-xs font-bold text-terra dark:bg-terra/20 dark:text-terra-light">1</div>
                        <div>
                            <div class="text-sm font-medium text-zinc-900 dark:text-white">Customer selects plan on captive portal</div>
                            <flux:text class="text-xs">Enters phone number and selects WiFi plan</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="flex size-7 shrink-0 items-center justify-center rounded-full bg-terra/10 text-xs font-bold text-terra dark:bg-terra/20 dark:text-terra-light">2</div>
                        <div>
                            <div class="text-sm font-medium text-zinc-900 dark:text-white">USSD-PUSH sent via ClickPesa</div>
                            <flux:text class="text-xs">Payment prompt appears on customer's phone (M-Pesa, Airtel, Tigo, etc.)</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="flex size-7 shrink-0 items-center justify-center rounded-full bg-terra/10 text-xs font-bold text-terra dark:bg-terra/20 dark:text-terra-light">3</div>
                        <div>
                            <div class="text-sm font-medium text-zinc-900 dark:text-white">Customer confirms payment</div>
                            <flux:text class="text-xs">Enters PIN on their phone to authorize</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="flex size-7 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">4</div>
                        <div>
                            <div class="text-sm font-medium text-zinc-900 dark:text-white">Webhook received → WiFi access granted</div>
                            <flux:text class="text-xs">ClickPesa sends PAYMENT RECEIVED → system authorizes Omada session</flux:text>
                        </div>
                    </div>
                </div>
            </flux:card>
        </div>

        {{-- Info Sidebar --}}
        <div class="space-y-4">
            {{-- Setup Guide --}}
            <flux:card class="rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
                <flux:heading size="md" class="mb-3">ClickPesa Setup</flux:heading>
                <div class="space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                    <div class="flex gap-2">
                        <div class="flex size-6 shrink-0 items-center justify-center rounded-full bg-terra/10 text-xs font-bold text-terra dark:bg-terra/20 dark:text-terra-light">1</div>
                        <div>Register at <a href="https://merchant.clickpesa.com/account-registration" target="_blank" class="text-terra underline dark:text-terra-light">merchant.clickpesa.com</a></div>
                    </div>
                    <div class="flex gap-2">
                        <div class="flex size-6 shrink-0 items-center justify-center rounded-full bg-terra/10 text-xs font-bold text-terra dark:bg-terra/20 dark:text-terra-light">2</div>
                        <div>Go to Settings → Developers → Create Application</div>
                    </div>
                    <div class="flex gap-2">
                        <div class="flex size-6 shrink-0 items-center justify-center rounded-full bg-terra/10 text-xs font-bold text-terra dark:bg-terra/20 dark:text-terra-light">3</div>
                        <div>Copy Client ID and API Key into the form</div>
                    </div>
                    <div class="flex gap-2">
                        <div class="flex size-6 shrink-0 items-center justify-center rounded-full bg-terra/10 text-xs font-bold text-terra dark:bg-terra/20 dark:text-terra-light">4</div>
                        <div>Set webhook URL in ClickPesa dashboard for PAYMENT RECEIVED & PAYMENT FAILED events</div>
                    </div>
                    <div class="flex gap-2">
                        <div class="flex size-6 shrink-0 items-center justify-center rounded-full bg-terra/10 text-xs font-bold text-terra dark:bg-terra/20 dark:text-terra-light">5</div>
                        <div>Click "Test Connection" to verify</div>
                    </div>
                </div>
            </flux:card>

            {{-- Webhook Info --}}
            <flux:card class="rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
                <flux:heading size="md" class="mb-3">Webhook Endpoint</flux:heading>
                <div x-data="{ copied: false }" class="rounded-xl border border-ivory-darker/70 bg-ivory/50 p-3 dark:border-smoke-light/50 dark:bg-smoke/40">
                    <code class="break-all text-xs text-smoke dark:text-ivory">{{ url('/api/clickpesa/webhook') }}</code>
                    <button type="button"
                        @click="navigator.clipboard.writeText('{{ url('/api/clickpesa/webhook') }}'); copied = true; setTimeout(() => copied = false, 2000)"
                        class="mt-2 flex items-center gap-1 text-xs text-terra transition hover:text-terra-dark dark:text-terra-light">
                        <flux:icon x-show="!copied" name="clipboard-document" class="size-3.5" />
                        <flux:icon x-show="copied" name="clipboard-document-check" class="size-3.5 text-emerald-600" />
                        <span x-text="copied ? 'Copied!' : 'Copy URL'"></span>
                    </button>
                </div>
                <flux:text class="mt-2 text-xs text-smoke/60 dark:text-ivory/50">Set this URL in ClickPesa dashboard for <strong>PAYMENT RECEIVED</strong> and <strong>PAYMENT FAILED</strong> events.</flux:text>
            </flux:card>

            {{-- Supported MNOs --}}
            <flux:card class="rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
                <flux:heading size="md" class="mb-3">Supported Channels</flux:heading>
                <div class="space-y-2">
                    <div class="flex items-center gap-2 text-sm">
                        <div class="size-2 rounded-full bg-emerald-500"></div>
                        <span>M-Pesa (Vodacom)</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <div class="size-2 rounded-full bg-emerald-500"></div>
                        <span>Tigo Pesa / Mixx by Yas</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <div class="size-2 rounded-full bg-emerald-500"></div>
                        <span>Airtel Money</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <div class="size-2 rounded-full bg-emerald-500"></div>
                        <span>HaloPesa</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <div class="size-2 rounded-full bg-emerald-500"></div>
                        <span>Card Payments (Visa/Mastercard)</span>
                    </div>
                </div>
            </flux:card>
        </div>
    </div>

    {{-- ClickPesa Settings Modal --}}
    <flux:modal name="clickpesa-settings" class="max-w-lg" wire:model.live="showClickPesaForm">
        <form wire:submit="saveClickPesa">
            <flux:heading size="lg">ClickPesa Configuration</flux:heading>
            <flux:text class="mt-1">API credentials from your ClickPesa dashboard</flux:text>

            <div class="mt-6 space-y-4">
                <flux:input wire:model="client_id" label="Client ID" placeholder="ID1234XHYAJK" class="font-mono" description="Found in Application details" />

                <flux:input wire:model="api_key" type="password" label="API Key" placeholder="{{ $this->clickPesaSettings ? '••••••••  (unchanged)' : 'Enter API key' }}" description="Leave blank to keep current value" />

                <flux:input wire:model="checksum_key" type="password" label="Checksum Secret Key (optional)" placeholder="{{ $this->clickPesaSettings?->configValue('checksum_key') ? '••••••••  (unchanged)' : 'Enter checksum key' }}" description="For HMAC-SHA256 payload verification" />

                <flux:input wire:model="webhook_url" label="Webhook URL" placeholder="{{ url('/api/clickpesa/webhook') }}" class="font-mono text-xs" description="Auto-generated if left blank" />

                <flux:checkbox wire:model="is_active" label="Active" description="Enable ClickPesa for payment processing" />
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <flux:button wire:click="closeClickPesaForm" variant="ghost">Cancel</flux:button>
                <flux:button type="submit">Save Configuration</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
