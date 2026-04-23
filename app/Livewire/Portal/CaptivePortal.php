<?php

namespace App\Livewire\Portal;

use App\Models\GuestSession;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Workspace;
use App\Services\ClickPesaService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Title('WiFi Access')]
#[Layout('layouts.portal')]
class CaptivePortal extends Component
{
    public Workspace $workspace;

    // Omada redirect params
    public string $clientMac = '';

    public string $apMac = '';

    public string $ssid = '';

    public string $ipAddress = '';

    // UI state: select_plan, enter_phone, processing, success, error
    public string $step = 'select_plan';

    public ?int $selectedPlanId = null;

    #[Validate('required|regex:/^0?[67][0-9]{8}$/', message: 'Enter a valid phone number (e.g. 712345678)')]
    public string $phoneNumber = '';

    public ?string $transactionId = null;

    public ?string $errorMessage = null;

    /**
     * Initialize with Omada captive portal redirect parameters.
     */
    public function mount(Workspace $workspace): void
    {
        $this->workspace = $workspace;

        $this->clientMac = request()->query('clientMac', '');
        $this->apMac = request()->query('apMac', '');
        $this->ssid = request()->query('ssid', '');
        $this->ipAddress = request()->ip() ?? '';

        // Check if client already has an active session
        if ($this->clientMac) {
            $existingSession = GuestSession::query()
                ->where('workspace_id', $this->workspace->id)
                ->where('client_mac', $this->clientMac)
                ->where('status', 'active')
                ->where('time_expires', '>', now())
                ->first();

            if ($existingSession) {
                $this->step = 'success';
                $this->transactionId = 'existing';
            }
        }
    }

    /**
     * Select a plan and move to phone entry.
     */
    public function selectPlan(int $planId): void
    {
        $plan = Plan::active()->where('workspace_id', $this->workspace->id)->find($planId);

        if (! $plan) {
            return;
        }

        $this->selectedPlanId = $planId;

        // Free plans skip payment
        if ((float) $plan->price <= 0) {
            $this->processFreePlan($plan);

            return;
        }

        $this->step = 'enter_phone';
    }

    /**
     * Go back to plan selection.
     */
    public function backToPlans(): void
    {
        $this->step = 'select_plan';
        $this->selectedPlanId = null;
        $this->phoneNumber = '';
        $this->errorMessage = null;
        $this->resetValidation();
    }

    /**
     * Initiate payment via ClickPesa USSD-PUSH.
     */
    public function initiatePayment(): void
    {
        $this->validate();

        // Normalize phone: strip leading 0, prepend 255
        $phone = ltrim($this->phoneNumber, '0');
        $this->phoneNumber = '255'.$phone;

        $plan = Plan::active()->where('workspace_id', $this->workspace->id)->find($this->selectedPlanId);

        if (! $plan) {
            $this->errorMessage = 'Selected plan is no longer available.';

            return;
        }

        $clickPesa = app(ClickPesaService::class)->forWorkspace($this->workspace);

        if (! $clickPesa->isConfigured()) {
            $this->errorMessage = 'Payment system is not configured. Please contact support.';

            return;
        }

        // Generate unique order reference
        $orderRef = 'SKY'.strtoupper(Str::random(8));

        // Create pending payment record
        $payment = Payment::create([
            'workspace_id' => $this->workspace->id,
            'transaction_id' => $orderRef,
            'phone_number' => $this->phoneNumber,
            'amount' => $plan->price,
            'currency' => 'TZS',
            'payment_method' => 'mpesa',
            'status' => 'pending',
            'plan_id' => $plan->id,
            'client_mac' => $this->clientMac ?: null,
            'ap_mac' => $this->apMac ?: null,
            'metadata' => [
                'ssid' => $this->ssid,
                'ip_address' => $this->ipAddress,
                'plan_name' => $plan->name,
                'plan_type' => $plan->type,
            ],
        ]);

        // Initiate USSD-PUSH
        $result = $clickPesa->initiateUssdPush([
            'amount' => (string) (int) $plan->price,
            'phoneNumber' => $this->phoneNumber,
            'orderReference' => $orderRef,
        ]);

        if ($result['success']) {
            $this->transactionId = $orderRef;

            if (isset($result['data']['id'])) {
                $payment->update(['clickpesa_order_id' => $result['data']['id']]);
            }

            $this->step = 'processing';
        } else {
            $payment->update(['status' => 'failed', 'metadata' => array_merge($payment->metadata ?? [], ['error' => $result['error']])]);
            $this->errorMessage = 'Failed to send payment request. Please try again.';
        }
    }

    /**
     * Poll for payment status during processing.
     */
    public function checkPaymentStatus(): void
    {
        if (! $this->transactionId || $this->step !== 'processing') {
            return;
        }

        $payment = Payment::query()
            ->where('workspace_id', $this->workspace->id)
            ->where('transaction_id', $this->transactionId)
            ->first();

        if (! $payment) {
            return;
        }

        if ($payment->status === 'completed') {
            $this->step = 'success';
        } elseif ($payment->status === 'failed') {
            $this->errorMessage = 'Payment failed. Please try again.';
            $this->step = 'error';
        }
    }

    /**
     * Process a free (0 TZS) plan immediately.
     */
    private function processFreePlan(Plan $plan): void
    {
        $durationMinutes = match ($plan->type) {
            'time' => $plan->value,
            'unlimited' => $plan->duration_minutes ?? 60,
            default => 60,
        };

        GuestSession::create([
            'workspace_id' => $this->workspace->id,
            'client_mac' => $this->clientMac ?: 'unknown',
            'ap_mac' => $this->apMac ?: 'unknown',
            'ip_address' => $this->ipAddress ?: null,
            'ssid' => $this->ssid ?: null,
            'plan_id' => $plan->id,
            'data_used_mb' => 0,
            'data_limit_mb' => $plan->type === 'data' ? $plan->value : null,
            'time_started' => now(),
            'time_expires' => now()->addMinutes($durationMinutes),
            'status' => 'active',
        ]);

        $this->transactionId = 'free';
        $this->step = 'success';
    }

    /**
     * Retry payment from error state.
     */
    public function retry(): void
    {
        $this->step = 'enter_phone';
        $this->errorMessage = null;
        $this->transactionId = null;
    }

    #[Computed]
    public function plans(): Collection
    {
        return Plan::active()
            ->where('workspace_id', $this->workspace->id)
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();
    }

    #[Computed]
    public function selectedPlan(): ?Plan
    {
        return $this->selectedPlanId
            ? Plan::query()->where('workspace_id', $this->workspace->id)->find($this->selectedPlanId)
            : null;
    }
}
