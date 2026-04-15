<div>
    {{-- Page Header --}}
    <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-smoke dark:text-ivory">Dashboard</h1>
            <p class="text-sm text-smoke/50 dark:text-ivory/50">Welcome back — here's what's happening with your WiFi network</p>
        </div>
        <div class="flex items-center gap-2 text-xs text-smoke/50 dark:text-ivory/50">
            <flux:icon name="clock" class="size-4" />
            <span>Last updated: {{ now()->format('H:i') }}</span>
        </div>
    </div>

    {{-- Stats Cards (poll only this section) --}}
    <div wire:poll.30s="pollDashboard" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        {{-- Online Users --}}
        <flux:card class="group relative overflow-hidden rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-smoke/50 dark:text-ivory/50">Online Users</p>
                    <div class="mt-1 text-3xl font-bold text-smoke dark:text-ivory">{{ $this->onlineUsers }}</div>
                    <p class="mt-0.5 text-xs text-smoke/50 dark:text-ivory/40">Live connections</p>
                </div>
                <div class="relative">
                    <div class="absolute -inset-2 rounded-2xl bg-terra/10 blur-xl transition group-hover:bg-terra/15 dark:bg-terra/15 dark:group-hover:bg-terra/20"></div>
                    <div class="relative grid size-11 place-items-center rounded-2xl border border-ivory-darker/70 bg-ivory/70 dark:border-smoke-light/70 dark:bg-smoke">
                        <flux:icon name="users" class="size-6 text-terra dark:text-terra-light" />
                    </div>
                </div>
            </div>
        </flux:card>

        {{-- Revenue Today --}}
        <flux:card class="group relative overflow-hidden rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-smoke/50 dark:text-ivory/50">Revenue Today</p>
                    <div class="mt-1 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->revenueToday }} <span class="text-xs font-normal text-smoke/50">TZS</span></div>
                    <p class="mt-0.5 text-xs text-smoke/50 dark:text-ivory/40">{{ $this->totalPaymentsToday }} payments</p>
                </div>
                <div class="relative">
                    <div class="absolute -inset-2 rounded-2xl bg-terra/10 blur-xl transition group-hover:bg-terra/15 dark:bg-terra/15 dark:group-hover:bg-terra/20"></div>
                    <div class="relative grid size-11 place-items-center rounded-2xl border border-ivory-darker/70 bg-ivory/70 dark:border-smoke-light/70 dark:bg-smoke">
                        <flux:icon name="wallet" class="size-6 text-terra dark:text-terra-light" />
                    </div>
                </div>
            </div>
        </flux:card>

        {{-- Sessions Today --}}
        <flux:card class="group relative overflow-hidden rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-smoke/50 dark:text-ivory/50">Sessions Today</p>
                    <div class="mt-1 text-3xl font-bold text-smoke dark:text-ivory">{{ $this->totalSessionsToday }}</div>
                    <p class="mt-0.5 text-xs text-smoke/50 dark:text-ivory/40">Total connections</p>
                </div>
                <div class="relative">
                    <div class="absolute -inset-2 rounded-2xl bg-terra/10 blur-xl transition group-hover:bg-terra/15 dark:bg-terra/15 dark:group-hover:bg-terra/20"></div>
                    <div class="relative grid size-11 place-items-center rounded-2xl border border-ivory-darker/70 bg-ivory/70 dark:border-smoke-light/70 dark:bg-smoke">
                        <flux:icon name="activity" class="size-6 text-terra dark:text-terra-light" />
                    </div>
                </div>
            </div>
        </flux:card>

        {{-- Active Devices --}}
        <flux:card class="group relative overflow-hidden rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-smoke/50 dark:text-ivory/50">Devices Online</p>
                    <div class="mt-1 text-3xl font-bold text-smoke dark:text-ivory">{{ $this->activeDevices }} <span class="text-sm font-normal text-smoke/50">/ {{ $this->totalDevices }}</span></div>
                    <p class="mt-0.5 text-xs text-smoke/50 dark:text-ivory/40">TP-Link online</p>
                </div>
                <div class="relative">
                    <div class="absolute -inset-2 rounded-2xl bg-terra/10 blur-xl transition group-hover:bg-terra/15 dark:bg-terra/15 dark:group-hover:bg-terra/20"></div>
                    <div class="relative grid size-11 place-items-center rounded-2xl border border-ivory-darker/70 bg-ivory/70 dark:border-smoke-light/70 dark:bg-smoke">
                        <flux:icon name="router" class="size-6 text-terra dark:text-terra-light" />
                    </div>
                </div>
            </div>
        </flux:card>

        {{-- Monthly Revenue --}}
        <flux:card class="group relative overflow-hidden rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-smoke/50 dark:text-ivory/50">This Month</p>
                    <div class="mt-1 text-2xl font-bold text-smoke dark:text-ivory">{{ $this->revenueThisMonth }} <span class="text-xs font-normal text-smoke/50">TZS</span></div>
                    <p class="mt-0.5 text-xs text-smoke/50 dark:text-ivory/40">Total revenue</p>
                </div>
                <div class="relative">
                    <div class="absolute -inset-2 rounded-2xl bg-terra/10 blur-xl transition group-hover:bg-terra/15 dark:bg-terra/15 dark:group-hover:bg-terra/20"></div>
                    <div class="relative grid size-11 place-items-center rounded-2xl border border-ivory-darker/70 bg-ivory/70 dark:border-smoke-light/70 dark:bg-smoke">
                        <flux:icon name="banknotes" class="size-6 text-terra dark:text-terra-light" />
                    </div>
                </div>
            </div>
        </flux:card>
    </div>

    {{-- Charts Row --}}
    <div class="mt-6 grid gap-4 lg:grid-cols-5">
        {{-- Revenue Trend (60% width) --}}
        <flux:card class="lg:col-span-3 rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <flux:icon name="activity" class="size-5 text-terra dark:text-terra-light" />
                    <flux:heading size="sm" class="text-smoke dark:text-ivory">Revenue — Last 7 Days</flux:heading>
                </div>
                <flux:badge size="sm" class="bg-terra/10 text-terra">{{ $this->revenueThisWeek }} TZS this week</flux:badge>
            </div>
            <div
                class="mt-4"
                wire:ignore
                x-data="{
                    chart: null,
                    init() {
                        const isDark = document.documentElement.classList.contains('dark');
                        this.chart = new ApexCharts(this.$refs.revenueChart, {
                            chart: { type: 'area', height: 280, toolbar: { show: false }, fontFamily: 'Instrument Sans, sans-serif', background: 'transparent' },
                            series: [{ name: 'Revenue (TZS)', data: {{ json_encode($this->revenueTrendData['series']) }} }],
                            xaxis: { categories: {{ json_encode($this->revenueTrendData['categories']) }}, labels: { style: { colors: isDark ? '#EEEBD9' : '#282427' } } },
                            yaxis: { labels: { style: { colors: isDark ? '#EEEBD9' : '#282427' }, formatter: (v) => v >= 1000000 ? (v/1000000).toFixed(1)+'M' : v >= 1000 ? (v/1000).toFixed(0)+'k' : v } },
                            colors: ['#BC6C25'],
                            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 90, 100] } },
                            stroke: { curve: 'smooth', width: 3 },
                            dataLabels: { enabled: false },
                            grid: { borderColor: isDark ? '#3A363A' : '#D6D2B7', strokeDashArray: 4 },
                            tooltip: { y: { formatter: (val) => val.toLocaleString() + ' TZS' }, theme: isDark ? 'dark' : 'light' },
                            theme: { mode: isDark ? 'dark' : 'light' },
                        });
                        this.chart.render();
                        this.$el.addEventListener('charts-refresh', (e) => {
                            this.chart.updateSeries([{ name: 'Revenue (TZS)', data: e.detail.revenue.series }]);
                            this.chart.updateOptions({ xaxis: { categories: e.detail.revenue.categories } });
                        });
                    },
                    destroy() { this.chart?.destroy(); }
                }"
            >
                <div x-ref="revenueChart"></div>
            </div>
        </flux:card>

        {{-- Online Users — Last 24hrs (40% width) --}}
        <flux:card class="lg:col-span-2 rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
            <div class="flex items-center gap-2">
                <flux:icon name="users" class="size-5 text-terra dark:text-terra-light" />
                <flux:heading size="sm" class="text-smoke dark:text-ivory">Online Users — Last 24hrs</flux:heading>
            </div>
            <div
                class="mt-4"
                wire:ignore
                x-data="{
                    chart: null,
                    init() {
                        const isDark = document.documentElement.classList.contains('dark');
                        this.chart = new ApexCharts(this.$refs.sessionsChart, {
                            chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: 'Instrument Sans, sans-serif', background: 'transparent' },
                            series: [{ name: 'Sessions', data: {{ json_encode($this->sessionsPerHourData['series']) }} }],
                            xaxis: { categories: {{ json_encode($this->sessionsPerHourData['categories']) }}, labels: { rotate: -45, style: { fontSize: '10px', colors: isDark ? '#EEEBD9' : '#282427' } } },
                            yaxis: { labels: { style: { colors: isDark ? '#EEEBD9' : '#282427' } } },
                            colors: [isDark ? '#D4893F' : '#BC6C25'],
                            plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
                            dataLabels: { enabled: false },
                            grid: { borderColor: isDark ? '#3A363A' : '#D6D2B7', strokeDashArray: 4 },
                            theme: { mode: isDark ? 'dark' : 'light' },
                        });
                        this.chart.render();
                        this.$el.addEventListener('charts-refresh', (e) => {
                            this.chart.updateSeries([{ name: 'Sessions', data: e.detail.sessions.series }]);
                            this.chart.updateOptions({ xaxis: { categories: e.detail.sessions.categories } });
                        });
                    },
                    destroy() { this.chart?.destroy(); }
                }"
            >
                <div x-ref="sessionsChart"></div>
            </div>
        </flux:card>
    </div>

    {{-- Recent Sessions & Recent Payments --}}
    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        {{-- Recent Sessions --}}
        <flux:card class="rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <flux:icon name="users" class="size-5 text-terra dark:text-terra-light" />
                    <flux:heading size="sm" class="text-smoke dark:text-ivory">Recent Sessions</flux:heading>
                </div>
                <a href="{{ route('admin.sessions') }}" wire:navigate class="text-xs font-medium text-terra hover:text-terra-dark dark:text-terra-light">View all →</a>
            </div>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-ivory-darker text-left text-xs font-semibold uppercase tracking-wider text-smoke/50 dark:border-smoke-light dark:text-ivory/50">
                            <th class="pb-2">MAC</th>
                            <th class="pb-2">Plan</th>
                            <th class="pb-2">Time Left</th>
                            <th class="pb-2 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ivory-darker dark:divide-smoke-light">
                        @forelse($this->recentSessions as $session)
                            <tr>
                                <td class="py-2 font-mono text-xs text-smoke dark:text-ivory">{{ $session->client_mac }}</td>
                                <td class="py-2 text-xs text-smoke/80 dark:text-ivory/70">{{ $session->plan?->name ?? '—' }}</td>
                                <td class="py-2 text-xs text-smoke/80 dark:text-ivory/70">{{ $session->isActive() ? ($session->timeRemaining() ?? 'Unlimited') : '—' }}</td>
                                <td class="py-2 text-right">
                                    @if($session->isActive())
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-terra">
                                            <flux:icon name="activity" class="size-4 text-terra dark:text-terra-light" /> Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs text-smoke/50">
                                            <flux:icon name="activity" class="size-4 text-smoke/40 dark:text-ivory/40" /> Ended
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-8 text-center text-sm text-smoke/50">No sessions yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </flux:card>

        {{-- Recent Payments --}}
        <flux:card class="rounded-2xl border border-ivory-darker/70 bg-white/70 shadow-sm backdrop-blur dark:border-smoke-light/70 dark:bg-smoke-light/40">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <flux:icon name="wallet" class="size-5 text-terra dark:text-terra-light" />
                    <flux:heading size="sm" class="text-smoke dark:text-ivory">Recent Payments</flux:heading>
                </div>
                <a href="{{ route('admin.payments') }}" wire:navigate class="text-xs font-medium text-terra hover:text-terra-dark dark:text-terra-light">View all →</a>
            </div>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-ivory-darker text-left text-xs font-semibold uppercase tracking-wider text-smoke/50 dark:border-smoke-light dark:text-ivory/50">
                            <th class="pb-2">Phone</th>
                            <th class="pb-2">Amount</th>
                            <th class="pb-2">Plan</th>
                            <th class="pb-2 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ivory-darker dark:divide-smoke-light">
                        @forelse($this->recentPayments as $payment)
                            <tr>
                                <td class="py-2 text-xs text-smoke dark:text-ivory">{{ $payment->phone_number }}</td>
                                <td class="py-2 text-xs font-bold text-smoke dark:text-ivory">{{ number_format($payment->amount, 0) }} <span class="font-normal text-smoke/50">TZS</span></td>
                                <td class="py-2 text-xs text-smoke/80 dark:text-ivory/70">{{ $payment->plan?->name ?? '—' }}</td>
                                <td class="py-2 text-right">
                                    <flux:badge size="sm" :color="$payment->status === 'completed' ? 'emerald' : ($payment->status === 'pending' ? 'amber' : 'red')">
                                        {{ ucfirst($payment->status) }}
                                    </flux:badge>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-8 text-center text-sm text-smoke/50">No payments yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </flux:card>
    </div>
</div>
