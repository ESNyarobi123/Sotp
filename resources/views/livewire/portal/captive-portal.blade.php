<div>
    {{-- Step 1: Select Plan --}}
    @if($step === 'select_plan')
        <div class="space-y-3">
            <div class="text-center">
                <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Choose Your Plan</h2>
                <p class="text-sm text-zinc-500">Select a WiFi access package</p>
            </div>

            @forelse ($this->plans as $plan)
                <button
                    wire:click="selectPlan({{ $plan->id }})"
                    class="w-full rounded-xl border border-zinc-200 bg-white p-4 text-left shadow-sm transition hover:border-terra hover:shadow-md active:scale-[0.98] dark:border-zinc-700 dark:bg-zinc-800"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-bold text-zinc-900 dark:text-white">{{ $plan->name }}</div>
                            <div class="mt-0.5 text-sm text-zinc-500">{{ $plan->formattedValue() }}</div>
                            @if($plan->description)
                                <div class="mt-1 text-xs text-zinc-400">{{ $plan->description }}</div>
                            @endif
                        </div>
                        <div class="text-right">
                            @if((float) $plan->price > 0)
                                <div class="text-xl font-bold text-terra dark:text-terra-light">{{ number_format($plan->price, 0) }}</div>
                                <div class="text-xs text-zinc-500">TZS</div>
                            @else
                                <div class="text-lg font-bold text-emerald-600 dark:text-emerald-400">Free</div>
                            @endif
                        </div>
                    </div>
                </button>
            @empty
                <div class="rounded-xl border border-zinc-200 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-800">
                    <p class="text-zinc-500">No plans available at this time.</p>
                </div>
            @endforelse
        </div>

    {{-- Step 2: Enter Phone Number --}}
    @elseif($step === 'enter_phone')
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            {{-- Selected plan summary --}}
            @if($this->selectedPlan)
                <div class="mb-5 flex items-center justify-between rounded-lg bg-terra/5 dark:bg-terra/10">
                    <div>
                        <div class="font-semibold text-zinc-900 dark:text-white">{{ $this->selectedPlan->name }}</div>
                        <div class="text-xs text-zinc-500">{{ $this->selectedPlan->formattedValue() }}</div>
                    </div>
                    <div class="text-lg font-bold text-terra dark:text-terra-light">{{ number_format($this->selectedPlan->price, 0) }} TZS</div>
                </div>
            @endif

            <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Enter Phone Number</h2>
            <p class="mt-1 text-sm text-zinc-500">You'll receive a payment prompt on your phone</p>

            <form wire:submit="initiatePayment" class="mt-4 space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Phone Number</label>
                    <div class="flex items-center gap-2">
                        <span class="rounded-lg bg-zinc-100 px-3 py-2.5 text-sm font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400">+255</span>
                        <input
                            wire:model="phoneNumber"
                            type="tel"
                            placeholder="7XXXXXXXX"
                            maxlength="12"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2.5 text-sm focus:border-terra focus:ring-terra dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                            inputmode="numeric"
                        >
                    </div>
                    @error('phoneNumber')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-zinc-400">e.g. 712345678</p>
                </div>

                @if($errorMessage)
                    <div class="rounded-lg bg-red-50 p-3 text-sm text-red-600 dark:bg-red-900/20 dark:text-red-400">
                        {{ $errorMessage }}
                    </div>
                @endif

                <button
                    type="submit"
                    class="w-full rounded-lg bg-terra px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-terra-dark active:scale-[0.98]"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50"
                >
                    <span wire:loading.remove wire:target="initiatePayment">Pay {{ number_format($this->selectedPlan?->price ?? 0, 0) }} TZS</span>
                    <span wire:loading wire:target="initiatePayment">Sending payment request...</span>
                </button>

                <button
                    wire:click="backToPlans"
                    type="button"
                    class="w-full rounded-lg border border-zinc-200 px-4 py-2.5 text-sm text-zinc-600 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800"
                >
                    ← Back to plans
                </button>
            </form>
        </div>

    {{-- Step 3: Processing Payment --}}
    @elseif($step === 'processing')
        <div wire:poll.3s="checkPaymentStatus" class="rounded-xl border border-zinc-200 bg-white p-8 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            {{-- Animated spinner --}}
            <div class="mx-auto flex size-16 items-center justify-center">
                <svg class="size-12 animate-spin text-terra" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>

            <h2 class="mt-4 text-lg font-bold text-zinc-900 dark:text-white">Waiting for Payment</h2>
            <p class="mt-2 text-sm text-zinc-500">Check your phone for the payment prompt</p>
            <p class="mt-1 text-xs text-zinc-400">Enter your PIN to confirm payment</p>

            @if($this->selectedPlan)
                <div class="mt-4 inline-flex items-center gap-2 rounded-full bg-terra/5 px-4 py-2 text-sm dark:bg-terra/10">
                    <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $this->selectedPlan->name }}</span>
                    <span class="font-bold text-terra dark:text-terra-light">{{ number_format($this->selectedPlan->price, 0) }} TZS</span>
                </div>
            @endif

            <p class="mt-4 text-xs text-zinc-400">Ref: {{ $transactionId }}</p>
        </div>

    {{-- Step 4: Success --}}
    @elseif($step === 'success')
        <div class="rounded-xl border border-emerald-200 bg-white p-8 text-center shadow-sm dark:border-emerald-800 dark:bg-zinc-800">
            <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-8 text-emerald-600 dark:text-emerald-400">
                    <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
                </svg>
            </div>

            <h2 class="mt-4 text-lg font-bold text-zinc-900 dark:text-white">You're Connected!</h2>
            <p class="mt-2 text-sm text-zinc-500">Enjoy your WiFi access</p>

            @if($this->selectedPlan)
                <div class="mt-4 space-y-1">
                    <div class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $this->selectedPlan->name }}</div>
                    <div class="text-xs text-zinc-500">{{ $this->selectedPlan->formattedValue() }}</div>
                </div>
            @endif

            <div class="mt-6 rounded-lg bg-emerald-50 p-4 dark:bg-emerald-900/20">
                <p class="text-sm font-medium text-emerald-700 dark:text-emerald-400">Your internet access is now active.</p>
                <p class="mt-1 text-xs text-emerald-600 dark:text-emerald-500">You can close this page and start browsing.</p>
            </div>
        </div>

    {{-- Step 5: Error --}}
    @elseif($step === 'error')
        <div class="rounded-xl border border-red-200 bg-white p-8 text-center shadow-sm dark:border-red-800 dark:bg-zinc-800">
            <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-8 text-red-600 dark:text-red-400">
                    <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-1.72 6.97a.75.75 0 1 0-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 1 0 1.06 1.06L12 13.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L13.06 12l1.72-1.72a.75.75 0 1 0-1.06-1.06L12 10.94l-1.72-1.72Z" clip-rule="evenodd" />
                </svg>
            </div>

            <h2 class="mt-4 text-lg font-bold text-zinc-900 dark:text-white">Payment Failed</h2>
            <p class="mt-2 text-sm text-zinc-500">{{ $errorMessage ?? 'Something went wrong. Please try again.' }}</p>

            <div class="mt-6 space-y-2">
                <button
                    wire:click="retry"
                    class="w-full rounded-lg bg-terra px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-terra-dark"
                >
                    Try Again
                </button>
                <button
                    wire:click="backToPlans"
                    class="w-full rounded-lg border border-zinc-200 px-4 py-2.5 text-sm text-zinc-600 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-400"
                >
                    Choose Different Plan
                </button>
            </div>
        </div>
    @endif
</div>
