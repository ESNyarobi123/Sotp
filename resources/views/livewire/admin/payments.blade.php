<div>
    {{-- Header --}}
    <div class="mb-6 flex items-center gap-3">
        <div class="grid size-11 place-items-center rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
            <flux:icon name="wallet" class="size-6 text-terra dark:text-terra-light" />
        </div>
        <div>
            <flux:heading size="lg" class="text-smoke dark:text-ivory">Payments</flux:heading>
            <flux:text class="mt-1 text-smoke/50 dark:text-ivory/50">Transaction history and revenue tracking</flux:text>
        </div>
    </div>

    {{-- Stats --}}
    @php
        $methodLabels = ['mpesa' => 'M-Pesa', 'airtel' => 'Airtel', 'tigo' => 'Tigo', 'card' => 'Card'];
    @endphp
    <div class="mb-4 grid gap-3 sm:grid-cols-4">
        <div class="rounded-2xl border border-ivory-darker/70 bg-white/70 px-4 py-3 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
            <div class="text-xs font-medium uppercase text-smoke/50 dark:text-ivory/50">Revenue Today</div>
            <div class="mt-1 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->totalRevenueToday }} <span class="text-sm font-normal text-smoke/50">TZS</span></div>
        </div>
        <button wire:click="$set('statusFilter', 'completed')" class="rounded-2xl border border-ivory-darker/70 bg-white/70 px-4 py-3 text-left shadow-sm backdrop-blur transition hover:bg-white dark:border-smoke-light/70 dark:bg-smoke-light/40 dark:hover:bg-smoke-light/55 {{ $statusFilter === 'completed' ? 'ring-2 ring-terra/25 dark:ring-terra/30' : '' }}">
            <div class="text-xs font-medium uppercase text-smoke/50 dark:text-ivory/50">Completed</div>
            <div class="mt-1 text-2xl font-bold text-terra">{{ $this->completedCount }}</div>
        </button>
        <button wire:click="$set('statusFilter', 'pending')" class="rounded-2xl border border-ivory-darker/70 bg-white/70 px-4 py-3 text-left shadow-sm backdrop-blur transition hover:bg-white dark:border-smoke-light/70 dark:bg-smoke-light/40 dark:hover:bg-smoke-light/55 {{ $statusFilter === 'pending' ? 'ring-2 ring-terra/25 dark:ring-terra/30' : '' }}">
            <div class="text-xs font-medium uppercase text-smoke/50 dark:text-ivory/50">Pending</div>
            <div class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $this->pendingCount }}</div>
        </button>
        <button wire:click="$set('statusFilter', 'failed')" class="rounded-2xl border border-ivory-darker/70 bg-white/70 px-4 py-3 text-left shadow-sm backdrop-blur transition hover:bg-white dark:border-smoke-light/70 dark:bg-smoke-light/40 dark:hover:bg-smoke-light/55 {{ $statusFilter === 'failed' ? 'ring-2 ring-terra/25 dark:ring-terra/30' : '' }}">
            <div class="text-xs font-medium uppercase text-smoke/50 dark:text-ivory/50">Failed</div>
            <div class="mt-1 text-2xl font-bold text-red-600 dark:text-red-400">{{ $this->failedCount }}</div>
        </button>
    </div>

    {{-- Filters --}}
    <div class="mb-4 flex flex-col gap-2 sm:flex-row">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search phone, txn ID, receipt..." icon="magnifying-glass" class="sm:w-72" />
        <flux:select wire:model.live="statusFilter" class="sm:w-40">
            <option value="">All Status</option>
            <option value="completed">Completed</option>
            <option value="pending">Pending</option>
            <option value="failed">Failed</option>
            <option value="cancelled">Cancelled</option>
        </flux:select>
        <flux:select wire:model.live="methodFilter" class="sm:w-40">
            <option value="">All Methods</option>
            <option value="mpesa">M-Pesa</option>
            <option value="airtel">Airtel Money</option>
            <option value="tigo">Tigo Pesa</option>
            <option value="card">Card</option>
        </flux:select>
        <flux:input wire:model.live="dateFrom" type="date" class="sm:w-40" />
        <flux:input wire:model.live="dateTo" type="date" class="sm:w-40" />
    </div>

    {{-- Revenue by Method --}}
    @if($this->revenueByMethod->isNotEmpty())
        <div class="mb-4 flex flex-wrap gap-2">
            @foreach($this->revenueByMethod as $method => $total)
                <div class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 px-3 py-1 text-xs dark:border-zinc-700">
                    <span class="font-medium text-zinc-900 dark:text-white">{{ $methodLabels[$method] ?? $method }}</span>
                    <span class="text-zinc-500">{{ number_format($total, 0) }} TZS</span>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Payments Table --}}
    <flux:card class="rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
        <flux:table :paginate="$this->payments">
            <flux:table.columns>
                <flux:table.column>Transaction</flux:table.column>
                <flux:table.column>Phone</flux:table.column>
                <flux:table.column>Amount</flux:table.column>
                <flux:table.column>Method</flux:table.column>
                <flux:table.column>Plan</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Date</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->payments as $payment)
                    <flux:table.row>
                        <flux:table.cell class="font-mono text-xs">{{ $payment->transaction_id }}</flux:table.cell>
                        <flux:table.cell class="text-sm">{{ $payment->phone_number }}</flux:table.cell>
                        <flux:table.cell>
                            <span class="font-semibold text-zinc-900 dark:text-white">{{ number_format($payment->amount, 0) }}</span>
                            <span class="text-xs text-zinc-500">{{ $payment->currency }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="match($payment->payment_method) { 'mpesa' => 'green', 'airtel' => 'red', 'tigo' => 'blue', default => 'zinc' }">
                                {{ $methodLabels[$payment->payment_method] ?? ucfirst($payment->payment_method) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-sm">{{ $payment->plan?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="match($payment->status) { 'completed' => 'emerald', 'pending' => 'amber', 'failed' => 'red', default => 'zinc' }">
                                {{ ucfirst($payment->status) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-xs text-zinc-500">{{ $payment->created_at->format('M d, H:i') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:button variant="ghost" size="sm" icon="eye" wire:click="viewPayment({{ $payment->id }})" />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8" class="text-center">
                            <div class="py-8">
                                <flux:icon name="credit-card" class="mx-auto size-8 text-zinc-300" />
                                <flux:text class="mt-2">No payments found</flux:text>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- Payment Detail Modal --}}
    <flux:modal name="payment-detail" class="max-w-lg" wire:model.live="viewingPaymentId">
        @if($this->viewingPayment)
            @php $p = $this->viewingPayment; @endphp
            <flux:heading size="lg">Payment Details</flux:heading>
            <flux:text class="mt-1 font-mono">{{ $p->transaction_id }}</flux:text>

            <div class="mt-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:text class="text-xs font-medium uppercase text-zinc-500">Amount</flux:text>
                        <div class="mt-1 text-xl font-bold text-zinc-900 dark:text-white">{{ number_format($p->amount, 0) }} {{ $p->currency }}</div>
                    </div>
                    <div>
                        <flux:text class="text-xs font-medium uppercase text-zinc-500">Status</flux:text>
                        <flux:badge size="sm" :color="match($p->status) { 'completed' => 'emerald', 'pending' => 'amber', 'failed' => 'red', default => 'zinc' }" class="mt-1">
                            {{ ucfirst($p->status) }}
                        </flux:badge>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:text class="text-xs font-medium uppercase text-zinc-500">Phone Number</flux:text>
                        <div class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $p->phone_number }}</div>
                    </div>
                    <div>
                        <flux:text class="text-xs font-medium uppercase text-zinc-500">Payment Method</flux:text>
                        <div class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $methodLabels[$p->payment_method] ?? ucfirst($p->payment_method) }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:text class="text-xs font-medium uppercase text-zinc-500">Plan</flux:text>
                        <div class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $p->plan?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <flux:text class="text-xs font-medium uppercase text-zinc-500">Paid At</flux:text>
                        <div class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $p->paid_at?->format('M d, Y H:i:s') ?? '—' }}</div>
                    </div>
                </div>

                @if($p->payment_method === 'mpesa' && ($p->mpesa_checkout_request_id || $p->mpesa_receipt_number))
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <flux:text class="text-xs font-medium uppercase text-zinc-500">M-Pesa Checkout ID</flux:text>
                            <div class="mt-1 font-mono text-xs text-zinc-900 dark:text-white">{{ $p->mpesa_checkout_request_id ?? '—' }}</div>
                        </div>
                        <div>
                            <flux:text class="text-xs font-medium uppercase text-zinc-500">M-Pesa Receipt</flux:text>
                            <div class="mt-1 font-mono text-xs text-zinc-900 dark:text-white">{{ $p->mpesa_receipt_number ?? '—' }}</div>
                        </div>
                    </div>
                @endif

                @if($p->clickpesa_order_id || $p->clickpesa_payment_reference)
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <flux:text class="text-xs font-medium uppercase text-zinc-500">ClickPesa Order ID</flux:text>
                            <div class="mt-1 font-mono text-xs text-zinc-900 dark:text-white">{{ $p->clickpesa_order_id ?? '—' }}</div>
                        </div>
                        <div>
                            <flux:text class="text-xs font-medium uppercase text-zinc-500">ClickPesa Reference</flux:text>
                            <div class="mt-1 font-mono text-xs text-zinc-900 dark:text-white">{{ $p->clickpesa_payment_reference ?? '—' }}</div>
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:text class="text-xs font-medium uppercase text-zinc-500">Client MAC</flux:text>
                        <div class="mt-1 font-mono text-xs text-zinc-900 dark:text-white">{{ $p->client_mac ?? '—' }}</div>
                    </div>
                    <div>
                        <flux:text class="text-xs font-medium uppercase text-zinc-500">AP MAC</flux:text>
                        <div class="mt-1 font-mono text-xs text-zinc-900 dark:text-white">{{ $p->ap_mac ?? '—' }}</div>
                    </div>
                </div>

                <div>
                    <flux:text class="text-xs font-medium uppercase text-zinc-500">Created</flux:text>
                    <div class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $p->created_at->format('M d, Y H:i:s') }}</div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <flux:button wire:click="closePaymentDetail">Close</flux:button>
            </div>
        @endif
    </flux:modal>
</div>
