<div class="space-y-5 p-4 sm:p-6 lg:p-8">
    {{-- Page Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="grid size-9 place-items-center rounded-xl bg-gradient-to-br from-terra/20 to-terra/5 dark:from-terra/25 dark:to-terra/10">
                    <flux:icon name="banknotes" class="size-5 text-terra dark:text-terra-light" />
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-smoke dark:text-ivory">Payments</h1>
            </div>
            <p class="mt-1 text-xs text-smoke/50 dark:text-ivory/40">Revenue collection, wallet management and withdrawals</p>
        </div>
    </div>

    {{-- Wallet + Withdrawals --}}
    <div class="grid gap-4 xl:grid-cols-[minmax(0,1.25fr)_minmax(0,1fr)]">
        <div class="rounded-2xl border border-ivory-darker/60 bg-white/80 p-5 shadow-sm backdrop-blur-sm dark:border-smoke-light/60 dark:bg-smoke-light/30">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <span class="text-[10px] font-semibold uppercase tracking-wider text-smoke/40 dark:text-ivory/40">Available Balance</span>
                    <div class="text-3xl font-bold text-smoke dark:text-ivory">{{ $this->availableWalletBalance }} <span class="text-sm font-normal text-smoke/35">TZS</span></div>
                </div>
                <div class="rounded-xl bg-amber-500/8 px-3 py-1.5 text-center dark:bg-amber-500/12">
                    <div class="text-[10px] font-semibold uppercase tracking-wider text-amber-600/70 dark:text-amber-400/70">On Hold</div>
                    <div class="text-sm font-bold text-amber-700 dark:text-amber-400">{{ $this->pendingWithdrawalBalance }} <span class="text-[10px] font-normal">TZS</span></div>
                </div>
            </div>

            <form wire:submit="submitWithdrawalRequest" class="mt-5 grid gap-3 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]">
                <flux:input wire:model="withdrawalAmount" type="number" min="1" step="1" label="Amount" placeholder="5000" />
                <flux:input wire:model="withdrawalPhoneNumber" label="Phone" placeholder="712345678" />
                <div class="flex items-end">
                    <flux:button type="submit" size="sm" class="w-full cursor-pointer !bg-terra !text-white hover:!opacity-90">Withdraw</flux:button>
                </div>
            </form>
        </div>

        <div class="rounded-2xl border border-ivory-darker/60 bg-white/80 p-5 shadow-sm backdrop-blur-sm dark:border-smoke-light/60 dark:bg-smoke-light/30">
            <div class="flex items-center justify-between gap-2 mb-3">
                <span class="text-sm font-semibold text-smoke dark:text-ivory">Withdrawals</span>
                <flux:badge size="sm" color="zinc">{{ $this->withdrawalRequests->count() }}</flux:badge>
            </div>
            <div class="space-y-2 max-h-48 overflow-y-auto" style="scrollbar-width:thin">
                @forelse($this->withdrawalRequests as $withdrawalRequest)
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-ivory-darker/50 bg-ivory/40 px-3.5 py-2.5 transition-colors duration-150 hover:bg-ivory/60 dark:border-smoke-light/50 dark:bg-smoke/40 dark:hover:bg-smoke/50">
                        <div class="min-w-0">
                            <div class="text-sm font-bold text-smoke dark:text-ivory">{{ number_format($withdrawalRequest->amount, 0) }} {{ $withdrawalRequest->currency }}</div>
                            <div class="truncate text-[11px] text-smoke/45 dark:text-ivory/35">{{ $withdrawalRequest->reference }} &middot; {{ $withdrawalRequest->phone_number }} &middot; {{ $withdrawalRequest->created_at->diffForHumans() }}</div>
                        </div>
                        <flux:badge size="sm" :color="match($withdrawalRequest->status) { 'pending' => 'amber', 'approved' => 'sky', 'processing' => 'blue', 'paid' => 'emerald', 'rejected' => 'red', 'failed' => 'red', default => 'zinc' }">
                            {{ ucfirst($withdrawalRequest->status) }}
                        </flux:badge>
                    </div>
                @empty
                    <p class="py-6 text-center text-xs text-smoke/40 dark:text-ivory/35">No withdrawals yet</p>
                @endforelse
            </div>
        </div>
    </div>

    @if($this->canReviewWithdrawals)
        <flux:card class="mb-6 rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <flux:heading size="md">Pending Withdrawal Review Queue</flux:heading>
                    <flux:text class="mt-1 text-smoke/50 dark:text-ivory/50">Approve requests for manual payout follow-up or reject them to release held balance back to the workspace.</flux:text>
                </div>
                <flux:badge size="sm" color="amber">{{ $this->pendingWithdrawalReviewQueue->count() }} Pending</flux:badge>
            </div>

            <div class="mt-4 space-y-3">
                @forelse($this->pendingWithdrawalReviewQueue as $reviewRequest)
                    <div class="rounded-2xl border border-ivory-darker/70 bg-ivory/55 p-4 dark:border-smoke-light/60 dark:bg-smoke/45">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div class="space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="text-sm font-semibold text-smoke dark:text-ivory">{{ number_format($reviewRequest->amount, 0) }} {{ $reviewRequest->currency }}</div>
                                    <flux:badge size="sm" color="amber">Pending</flux:badge>
                                </div>

                                <div class="font-mono text-[11px] text-smoke/55 dark:text-ivory/45">{{ $reviewRequest->reference }}</div>

                                <div class="grid gap-1 text-xs text-smoke/60 dark:text-ivory/45 sm:grid-cols-2">
                                    <span>Workspace: {{ $reviewRequest->workspace?->brand_name ?? 'Unknown workspace' }}</span>
                                    <span>Requester: {{ $reviewRequest->requester?->name ?? 'Unknown user' }}</span>
                                    <span>Phone: {{ $reviewRequest->phone_number }}</span>
                                    <span>Submitted: {{ $reviewRequest->created_at->diffForHumans() }}</span>
                                </div>
                            </div>

                            <div class="flex flex-col gap-2 sm:flex-row">
                                <flux:button wire:click="approveWithdrawalRequest({{ $reviewRequest->id }})" size="sm" variant="primary">Approve</flux:button>
                                <flux:button wire:click="rejectWithdrawalRequest({{ $reviewRequest->id }})" size="sm" variant="danger">Reject</flux:button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-ivory-darker/80 px-4 py-8 text-center text-sm text-smoke/50 dark:border-smoke-light/70 dark:text-ivory/45">No pending withdrawal requests need review.</div>
                @endforelse
            </div>
        </flux:card>
    @endif

    @if($this->canReviewWithdrawals)
        <flux:card class="mb-6 rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <flux:heading size="md">Withdrawal Payout Queue</flux:heading>
                    <flux:text class="mt-1 text-smoke/50 dark:text-ivory/50">Send approved withdrawals to ClickPesa, retry failed attempts, and refresh processing requests until they settle.</flux:text>
                </div>
                <flux:badge size="sm" color="sky">{{ $this->approvedWithdrawalPayoutQueue->count() }} In Queue</flux:badge>
            </div>

            <div class="mt-4 space-y-3">
                @forelse($this->approvedWithdrawalPayoutQueue as $payoutRequest)
                    <div class="rounded-2xl border border-ivory-darker/70 bg-ivory/55 p-4 dark:border-smoke-light/60 dark:bg-smoke/45">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div class="space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="text-sm font-semibold text-smoke dark:text-ivory">{{ number_format($payoutRequest->amount, 0) }} {{ $payoutRequest->currency }}</div>
                                    <flux:badge size="sm" :color="match($payoutRequest->status) { 'approved' => 'sky', 'processing' => 'blue', 'failed' => 'red', default => 'zinc' }">{{ ucfirst($payoutRequest->status) }}</flux:badge>
                                </div>

                                <div class="font-mono text-[11px] text-smoke/55 dark:text-ivory/45">{{ $payoutRequest->reference }}</div>

                                <div class="grid gap-1 text-xs text-smoke/60 dark:text-ivory/45 sm:grid-cols-2">
                                    <span>Workspace: {{ $payoutRequest->workspace?->brand_name ?? 'Unknown workspace' }}</span>
                                    <span>Requester: {{ $payoutRequest->requester?->name ?? 'Unknown user' }}</span>
                                    <span>Phone: {{ $payoutRequest->phone_number }}</span>
                                    <span>Reviewed: {{ $payoutRequest->approved_at?->diffForHumans() ?? $payoutRequest->updated_at->diffForHumans() }}</span>
                                </div>

                                @if(data_get($payoutRequest->meta, 'payout_status'))
                                    <div class="text-xs text-smoke/55 dark:text-ivory/45">ClickPesa Status: {{ data_get($payoutRequest->meta, 'payout_status') }}</div>
                                @endif

                                @if($payoutRequest->failure_reason)
                                    <div class="text-xs text-red-600 dark:text-red-400">{{ $payoutRequest->failure_reason }}</div>
                                @endif
                            </div>

                            <div class="flex flex-col gap-2 sm:flex-row">
                                @if($payoutRequest->status === 'processing')
                                    <flux:button wire:click="refreshWithdrawalPayoutStatus({{ $payoutRequest->id }})" size="sm" variant="primary">Refresh Status</flux:button>
                                @else
                                    <flux:button wire:click="sendWithdrawalPayout({{ $payoutRequest->id }})" size="sm" variant="primary">{{ $payoutRequest->status === 'failed' ? 'Retry Payout' : 'Send Payout' }}</flux:button>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-ivory-darker/80 px-4 py-8 text-center text-sm text-smoke/50 dark:border-smoke-light/70 dark:text-ivory/45">No withdrawals are waiting for payout or reconciliation.</div>
                @endforelse
            </div>
        </flux:card>
    @endif

    {{-- Stat Cards --}}
    @php
        $methodLabels = ['mpesa' => 'M-Pesa', 'airtel' => 'Airtel', 'tigo' => 'Tigo', 'card' => 'Card'];
    @endphp
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <button wire:click="$set('statusFilter', '')" class="cursor-pointer rounded-2xl border p-3.5 text-left transition-all duration-200 {{ $statusFilter === '' ? 'border-terra/30 bg-terra/5 shadow-sm shadow-terra/10 dark:border-terra/20' : 'border-ivory-darker/50 bg-white/70 hover:border-terra/20 hover:shadow-sm dark:border-smoke-light/50 dark:bg-smoke-light/30' }}">
            <div class="text-[10px] font-semibold uppercase tracking-wider text-terra/70 dark:text-terra-light/70">Today&rsquo;s Revenue</div>
            <div class="mt-1.5 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->totalRevenueToday }} <span class="text-xs font-normal text-smoke/35">TZS</span></div>
        </button>
        <button wire:click="$set('statusFilter', 'completed')" class="cursor-pointer rounded-2xl border p-3.5 text-left transition-all duration-200 {{ $statusFilter === 'completed' ? 'border-emerald-500/30 bg-emerald-500/5 shadow-sm shadow-emerald-500/10 dark:border-emerald-500/20' : 'border-ivory-darker/50 bg-white/70 hover:border-emerald-500/20 hover:shadow-sm dark:border-smoke-light/50 dark:bg-smoke-light/30' }}">
            <div class="flex items-center gap-2">
                <span class="size-2 rounded-full bg-emerald-500"></span>
                <span class="text-[10px] font-semibold uppercase tracking-wider text-emerald-600/70 dark:text-emerald-400/70">Completed</span>
            </div>
            <div class="mt-1.5 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->completedCount }}</div>
        </button>
        <button wire:click="$set('statusFilter', 'pending')" class="cursor-pointer rounded-2xl border p-3.5 text-left transition-all duration-200 {{ $statusFilter === 'pending' ? 'border-amber-500/30 bg-amber-500/5 shadow-sm shadow-amber-500/10 dark:border-amber-500/20' : 'border-ivory-darker/50 bg-white/70 hover:border-amber-500/20 hover:shadow-sm dark:border-smoke-light/50 dark:bg-smoke-light/30' }}">
            <div class="flex items-center gap-2">
                <span class="size-2 rounded-full bg-amber-400"></span>
                <span class="text-[10px] font-semibold uppercase tracking-wider text-amber-600/70 dark:text-amber-400/70">Pending</span>
            </div>
            <div class="mt-1.5 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->pendingCount }}</div>
        </button>
        <button wire:click="$set('statusFilter', 'failed')" class="cursor-pointer rounded-2xl border p-3.5 text-left transition-all duration-200 {{ $statusFilter === 'failed' ? 'border-red-500/30 bg-red-500/5 shadow-sm shadow-red-500/10 dark:border-red-500/20' : 'border-ivory-darker/50 bg-white/70 hover:border-red-500/20 hover:shadow-sm dark:border-smoke-light/50 dark:bg-smoke-light/30' }}">
            <div class="flex items-center gap-2">
                <span class="size-2 rounded-full bg-red-500"></span>
                <span class="text-[10px] font-semibold uppercase tracking-wider text-red-600/70 dark:text-red-400/70">Failed</span>
            </div>
            <div class="mt-1.5 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->failedCount }}</div>
        </button>
    </div>

    {{-- Revenue by Method --}}
    @if($this->revenueByMethod->isNotEmpty())
        <div class="flex flex-wrap gap-2">
            @foreach($this->revenueByMethod as $method => $total)
                <span class="inline-flex items-center gap-1.5 rounded-xl border border-ivory-darker/40 bg-white/60 px-3 py-1.5 text-[11px] shadow-sm dark:border-smoke-light/40 dark:bg-smoke-light/30">
                    <span class="font-semibold text-smoke dark:text-ivory">{{ $methodLabels[$method] ?? $method }}</span>
                    <span class="text-smoke/40 dark:text-ivory/35">{{ number_format($total, 0) }} TZS</span>
                </span>
            @endforeach
        </div>
    @endif

    {{-- Search & Filters --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search phone, txn..." icon="magnifying-glass" class="sm:w-56" />
        <flux:select wire:model.live="statusFilter" class="sm:w-36">
            <option value="">All Status</option>
            <option value="completed">Completed</option>
            <option value="pending">Pending</option>
            <option value="failed">Failed</option>
            <option value="cancelled">Cancelled</option>
        </flux:select>
        <flux:select wire:model.live="methodFilter" class="sm:w-36">
            <option value="">All Methods</option>
            <option value="mpesa">M-Pesa</option>
            <option value="airtel">Airtel</option>
            <option value="tigo">Tigo</option>
            <option value="card">Card</option>
        </flux:select>
        <flux:input wire:model.live="dateFrom" type="date" class="sm:w-36" />
        <flux:input wire:model.live="dateTo" type="date" class="sm:w-36" />
    </div>

    {{-- Mobile Card View --}}
    <div class="space-y-2 sm:hidden">
        @forelse ($this->payments as $payment)
            <div wire:click="viewPayment({{ $payment->id }})" class="cursor-pointer rounded-2xl border border-ivory-darker/50 bg-white/80 p-3.5 shadow-sm transition-all duration-200 hover:shadow-md hover:border-terra/20 dark:border-smoke-light/50 dark:bg-smoke-light/30 dark:hover:border-terra/15">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-bold text-smoke dark:text-ivory">{{ number_format($payment->amount, 0) }} <span class="text-xs font-normal text-smoke/35">{{ $payment->currency }}</span></div>
                        <div class="mt-1 text-[11px] text-smoke/45 dark:text-ivory/35 truncate">
                            {{ $payment->phone_number }} &middot; {{ $payment->plan?->name ?? 'No plan' }}
                        </div>
                    </div>
                    <flux:badge size="sm" :color="match($payment->status) { 'completed' => 'emerald', 'pending' => 'amber', 'failed' => 'red', default => 'zinc' }">
                        {{ ucfirst($payment->status) }}
                    </flux:badge>
                </div>
                <div class="mt-2.5 flex items-center justify-between text-[11px] text-smoke/40 dark:text-ivory/35">
                    <flux:badge size="sm" :color="match($payment->payment_method) { 'mpesa' => 'green', 'airtel' => 'red', 'tigo' => 'blue', default => 'zinc' }">
                        {{ $methodLabels[$payment->payment_method] ?? ucfirst($payment->payment_method) }}
                    </flux:badge>
                    <span class="font-medium">{{ $payment->created_at->format('M d, H:i') }}</span>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border-2 border-dashed border-ivory-darker/40 py-12 text-center dark:border-smoke-light/30">
                <div class="mx-auto grid size-14 place-items-center rounded-2xl bg-ivory/70 dark:bg-smoke-light/40">
                    <flux:icon name="banknotes" class="size-7 text-smoke/25 dark:text-ivory/20" />
                </div>
                <p class="mt-3 text-sm font-medium text-smoke/40 dark:text-ivory/35">No payments found</p>
                <p class="mt-1 text-xs text-smoke/30 dark:text-ivory/25">Payments will appear as guests purchase plans</p>
            </div>
        @endforelse
        <div class="pt-2">{{ $this->payments->links() }}</div>
    </div>

    {{-- Desktop Table --}}
    <div class="hidden sm:block overflow-hidden rounded-2xl border border-ivory-darker/60 bg-white/80 shadow-sm backdrop-blur-sm dark:border-smoke-light/60 dark:bg-smoke-light/30">
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
                    <flux:table.row class="transition-colors duration-150 hover:bg-ivory/40 dark:hover:bg-smoke-light/20 cursor-pointer" wire:click="viewPayment({{ $payment->id }})">
                        <flux:table.cell class="font-mono text-xs font-medium text-smoke/70 dark:text-ivory/60">{{ $payment->transaction_id }}</flux:table.cell>
                        <flux:table.cell class="text-sm text-smoke/80 dark:text-ivory/70">{{ $payment->phone_number }}</flux:table.cell>
                        <flux:table.cell>
                            <span class="font-bold text-smoke dark:text-ivory">{{ number_format($payment->amount, 0) }}</span>
                            <span class="text-xs text-smoke/40 dark:text-ivory/35">{{ $payment->currency }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="match($payment->payment_method) { 'mpesa' => 'green', 'airtel' => 'red', 'tigo' => 'blue', default => 'zinc' }">
                                {{ $methodLabels[$payment->payment_method] ?? ucfirst($payment->payment_method) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($payment->plan)
                                <span class="inline-flex items-center rounded-lg bg-terra/8 px-2 py-0.5 text-[11px] font-medium text-terra dark:bg-terra/15 dark:text-terra-light">{{ $payment->plan->name }}</span>
                            @else
                                <span class="text-smoke/30 dark:text-ivory/25">—</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="match($payment->status) { 'completed' => 'emerald', 'pending' => 'amber', 'failed' => 'red', default => 'zinc' }">
                                {{ ucfirst($payment->status) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-xs text-smoke/50 dark:text-ivory/40">{{ $payment->created_at->format('M d, H:i') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:button variant="ghost" size="sm" icon="eye" wire:click="viewPayment({{ $payment->id }})" class="cursor-pointer" />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8" class="text-center">
                            <div class="py-12">
                                <div class="mx-auto grid size-14 place-items-center rounded-2xl bg-ivory/70 dark:bg-smoke-light/40">
                                    <flux:icon name="banknotes" class="size-7 text-smoke/20 dark:text-ivory/20" />
                                </div>
                                <p class="mt-3 text-sm font-medium text-smoke/40 dark:text-ivory/35">No payments found</p>
                                <p class="mt-1 text-xs text-smoke/30 dark:text-ivory/25">Try adjusting your search or filters</p>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    {{-- Payment Detail Modal --}}
    <flux:modal name="payment-detail" class="max-w-lg" wire:model.live="viewingPaymentId">
        @if($this->viewingPayment)
            @php $p = $this->viewingPayment; @endphp
            <div class="flex items-center gap-3">
                <div class="grid size-10 place-items-center rounded-xl {{ $p->status === 'completed' ? 'bg-emerald-500/10' : ($p->status === 'pending' ? 'bg-amber-500/10' : 'bg-red-500/10') }}">
                    <flux:icon name="banknotes" class="size-5 {{ $p->status === 'completed' ? 'text-emerald-600 dark:text-emerald-400' : ($p->status === 'pending' ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}" />
                </div>
                <div>
                    <flux:heading size="lg">Payment Details</flux:heading>
                    <span class="font-mono text-xs text-smoke/50 dark:text-ivory/40">{{ $p->transaction_id }}</span>
                </div>
            </div>

            <div class="mt-5 space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-ivory/50 px-3.5 py-2.5 dark:bg-smoke/40">
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-smoke/40 dark:text-ivory/35">Amount</div>
                        <div class="mt-1 text-xl font-bold text-smoke dark:text-ivory">{{ number_format($p->amount, 0) }} <span class="text-xs font-normal text-smoke/35">{{ $p->currency }}</span></div>
                    </div>
                    <div class="rounded-xl bg-ivory/50 px-3.5 py-2.5 dark:bg-smoke/40">
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-smoke/40 dark:text-ivory/35">Status</div>
                        <flux:badge size="sm" :color="match($p->status) { 'completed' => 'emerald', 'pending' => 'amber', 'failed' => 'red', default => 'zinc' }" class="mt-1">
                            {{ ucfirst($p->status) }}
                        </flux:badge>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-ivory/50 px-3.5 py-2.5 dark:bg-smoke/40">
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-smoke/40 dark:text-ivory/35">Phone Number</div>
                        <div class="mt-1 text-sm font-medium text-smoke dark:text-ivory">{{ $p->phone_number }}</div>
                    </div>
                    <div class="rounded-xl bg-ivory/50 px-3.5 py-2.5 dark:bg-smoke/40">
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-smoke/40 dark:text-ivory/35">Payment Method</div>
                        <div class="mt-1 text-sm font-medium text-smoke dark:text-ivory">{{ $methodLabels[$p->payment_method] ?? ucfirst($p->payment_method) }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-ivory/50 px-3.5 py-2.5 dark:bg-smoke/40">
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-smoke/40 dark:text-ivory/35">Plan</div>
                        <div class="mt-1 text-sm font-medium text-smoke dark:text-ivory">{{ $p->plan?->name ?? '—' }}</div>
                    </div>
                    <div class="rounded-xl bg-ivory/50 px-3.5 py-2.5 dark:bg-smoke/40">
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-smoke/40 dark:text-ivory/35">Paid At</div>
                        <div class="mt-1 text-sm text-smoke dark:text-ivory">{{ $p->paid_at?->format('M d, Y H:i:s') ?? '—' }}</div>
                    </div>
                </div>

                @if($p->payment_method === 'mpesa' && ($p->mpesa_checkout_request_id || $p->mpesa_receipt_number))
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl bg-ivory/50 px-3.5 py-2.5 dark:bg-smoke/40">
                            <div class="text-[10px] font-semibold uppercase tracking-wider text-smoke/40 dark:text-ivory/35">M-Pesa Checkout ID</div>
                            <div class="mt-1 font-mono text-xs text-smoke dark:text-ivory">{{ $p->mpesa_checkout_request_id ?? '—' }}</div>
                        </div>
                        <div class="rounded-xl bg-ivory/50 px-3.5 py-2.5 dark:bg-smoke/40">
                            <div class="text-[10px] font-semibold uppercase tracking-wider text-smoke/40 dark:text-ivory/35">M-Pesa Receipt</div>
                            <div class="mt-1 font-mono text-xs text-smoke dark:text-ivory">{{ $p->mpesa_receipt_number ?? '—' }}</div>
                        </div>
                    </div>
                @endif

                @if($p->clickpesa_order_id || $p->clickpesa_payment_reference)
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl bg-ivory/50 px-3.5 py-2.5 dark:bg-smoke/40">
                            <div class="text-[10px] font-semibold uppercase tracking-wider text-smoke/40 dark:text-ivory/35">ClickPesa Order ID</div>
                            <div class="mt-1 font-mono text-xs text-smoke dark:text-ivory">{{ $p->clickpesa_order_id ?? '—' }}</div>
                        </div>
                        <div class="rounded-xl bg-ivory/50 px-3.5 py-2.5 dark:bg-smoke/40">
                            <div class="text-[10px] font-semibold uppercase tracking-wider text-smoke/40 dark:text-ivory/35">ClickPesa Reference</div>
                            <div class="mt-1 font-mono text-xs text-smoke dark:text-ivory">{{ $p->clickpesa_payment_reference ?? '—' }}</div>
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-ivory/50 px-3.5 py-2.5 dark:bg-smoke/40">
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-smoke/40 dark:text-ivory/35">Client MAC</div>
                        <div class="mt-1 font-mono text-xs text-smoke dark:text-ivory">{{ $p->client_mac ?? '—' }}</div>
                    </div>
                    <div class="rounded-xl bg-ivory/50 px-3.5 py-2.5 dark:bg-smoke/40">
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-smoke/40 dark:text-ivory/35">AP MAC</div>
                        <div class="mt-1 font-mono text-xs text-smoke dark:text-ivory">{{ $p->ap_mac ?? '—' }}</div>
                    </div>
                </div>

                <div class="rounded-xl bg-ivory/50 px-3.5 py-2.5 dark:bg-smoke/40">
                    <div class="text-[10px] font-semibold uppercase tracking-wider text-smoke/40 dark:text-ivory/35">Created</div>
                    <div class="mt-1 text-sm text-smoke dark:text-ivory">{{ $p->created_at->format('M d, Y H:i:s') }}</div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <flux:button wire:click="closePaymentDetail">Close</flux:button>
            </div>
        @endif
    </flux:modal>
</div>
