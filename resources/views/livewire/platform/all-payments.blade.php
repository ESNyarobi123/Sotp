@php $methodLabels = ['mpesa' => 'M-Pesa', 'airtel' => 'Airtel Money', 'tigo' => 'Tigo Pesa', 'clickpesa' => 'ClickPesa']; @endphp
<div class="p-4 sm:p-6 lg:p-8 space-y-5">
    {{-- Page Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="grid size-9 place-items-center rounded-xl bg-gradient-to-br from-terra/20 to-terra/5 dark:from-terra/25 dark:to-terra/10">
                    <flux:icon name="banknotes" class="size-5 text-terra dark:text-terra-light" />
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-smoke dark:text-ivory">All Payments</h1>
            </div>
            <p class="mt-1 text-xs text-smoke/50 dark:text-ivory/40">Cross-workspace payment overview</p>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-2xl border border-ivory-darker/50 bg-white/70 p-3.5 dark:border-smoke-light/50 dark:bg-smoke-light/30">
            <div class="text-[10px] font-semibold uppercase tracking-wider text-smoke/50 dark:text-ivory/40">Total</div>
            <div class="mt-1.5 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->totalCount }}</div>
        </div>
        <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/5 p-3.5 dark:border-emerald-500/15">
            <div class="flex items-center gap-2">
                <span class="size-2 rounded-full bg-emerald-500"></span>
                <span class="text-[10px] font-semibold uppercase tracking-wider text-emerald-600/70 dark:text-emerald-400/70">Completed</span>
            </div>
            <div class="mt-1.5 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->completedCount }}</div>
        </div>
        <div class="rounded-2xl border border-ivory-darker/50 bg-white/70 p-3.5 dark:border-smoke-light/50 dark:bg-smoke-light/30">
            <div class="text-[10px] font-semibold uppercase tracking-wider text-terra/70 dark:text-terra-light/70">Revenue</div>
            <div class="mt-1.5 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->totalRevenue }} <span class="text-xs font-normal text-smoke/35">TZS</span></div>
        </div>
        <div class="rounded-2xl border border-ivory-darker/50 bg-white/70 p-3.5 dark:border-smoke-light/50 dark:bg-smoke-light/30">
            <div class="text-[10px] font-semibold uppercase tracking-wider text-terra/70 dark:text-terra-light/70">Today</div>
            <div class="mt-1.5 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->todayRevenue }} <span class="text-xs font-normal text-smoke/35">TZS</span></div>
        </div>
    </div>

    <div class="flex flex-col gap-2 sm:flex-row">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search phone, transaction, workspace..." icon="magnifying-glass" class="sm:w-64" />
        <flux:select wire:model.live="statusFilter" class="sm:w-36">
            <option value="">All Status</option>
            <option value="completed">Completed</option>
            <option value="pending">Pending</option>
            <option value="failed">Failed</option>
        </flux:select>
        <flux:select wire:model.live="methodFilter" class="sm:w-36">
            <option value="">All Methods</option>
            <option value="mpesa">M-Pesa</option>
            <option value="airtel">Airtel</option>
            <option value="tigo">Tigo</option>
        </flux:select>
    </div>

    {{-- Mobile --}}
    <div class="space-y-2 sm:hidden">
        @forelse ($this->payments as $payment)
            <div class="rounded-2xl border border-ivory-darker/50 bg-white/80 p-3.5 shadow-sm transition-all duration-200 hover:shadow-md dark:border-smoke-light/50 dark:bg-smoke-light/30">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-bold text-smoke dark:text-ivory">{{ number_format($payment->amount, 0) }} <span class="text-xs font-normal text-smoke/35">{{ $payment->currency }}</span></div>
                        <div class="mt-1 text-[11px] text-smoke/45 dark:text-ivory/35 truncate">{{ $payment->workspace?->brand_name ?? '—' }} &middot; {{ $payment->phone_number }}</div>
                    </div>
                    <flux:badge size="sm" :color="match($payment->status) { 'completed' => 'emerald', 'pending' => 'amber', 'failed' => 'red', default => 'zinc' }">
                        {{ ucfirst($payment->status) }}
                    </flux:badge>
                </div>
                <div class="mt-2.5 flex items-center justify-between text-[11px] text-smoke/40 dark:text-ivory/35">
                    <span class="font-medium">{{ $methodLabels[$payment->payment_method] ?? ucfirst($payment->payment_method) }}</span>
                    <span>{{ $payment->created_at->format('M d, H:i') }}</span>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border-2 border-dashed border-ivory-darker/40 py-12 text-center dark:border-smoke-light/30">
                <div class="mx-auto grid size-14 place-items-center rounded-2xl bg-ivory/70 dark:bg-smoke-light/40">
                    <flux:icon name="banknotes" class="size-7 text-smoke/25 dark:text-ivory/20" />
                </div>
                <p class="mt-3 text-sm font-medium text-smoke/40 dark:text-ivory/35">No payments found</p>
            </div>
        @endforelse
        <div class="pt-2">{{ $this->payments->links() }}</div>
    </div>

    {{-- Desktop --}}
    <div class="hidden sm:block overflow-hidden rounded-2xl border border-ivory-darker/60 bg-white/80 shadow-sm backdrop-blur-sm dark:border-smoke-light/60 dark:bg-smoke-light/30">
        <flux:table :paginate="$this->payments">
            <flux:table.columns>
                <flux:table.column>Workspace</flux:table.column>
                <flux:table.column>Phone</flux:table.column>
                <flux:table.column>Amount</flux:table.column>
                <flux:table.column>Method</flux:table.column>
                <flux:table.column>Plan</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Date</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->payments as $payment)
                    <flux:table.row class="transition-colors duration-150 hover:bg-ivory/40 dark:hover:bg-smoke-light/20">
                        <flux:table.cell class="text-sm font-semibold text-smoke dark:text-ivory">{{ $payment->workspace?->brand_name ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="text-sm text-smoke/80 dark:text-ivory/70">{{ $payment->phone_number }}</flux:table.cell>
                        <flux:table.cell>
                            <span class="font-bold text-smoke dark:text-ivory">{{ number_format($payment->amount, 0) }}</span>
                            <span class="text-xs text-smoke/35">{{ $payment->currency }}</span>
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
                        <flux:table.cell class="text-xs text-smoke/45 dark:text-ivory/40">{{ $payment->created_at->format('M d, H:i') }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center">
                            <div class="py-12">
                                <div class="mx-auto grid size-14 place-items-center rounded-2xl bg-ivory/70 dark:bg-smoke-light/40">
                                    <flux:icon name="banknotes" class="size-7 text-smoke/20 dark:text-ivory/20" />
                                </div>
                                <p class="mt-3 text-sm font-medium text-smoke/40 dark:text-ivory/35">No payments found</p>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</div>
