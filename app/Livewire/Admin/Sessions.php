<?php

namespace App\Livewire\Admin;

use App\Events\SessionDisconnected;
use App\Livewire\Admin\Concerns\UsesAuthWorkspace;
use App\Models\GuestSession;
use App\Services\OmadaService;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Sessions')]
class Sessions extends Component
{
    use UsesAuthWorkspace;
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    public string $sortBy = 'time_started';

    public string $sortDir = 'desc';

    public ?int $viewingSessionId = null;

    /**
     * Sort by the given column.
     */
    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'desc';
        }
    }

    /**
     * Reset pagination when filters change.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Mark a session as disconnected and unauthorize on Omada.
     */
    public function disconnect(int $sessionId): void
    {
        $session = GuestSession::query()
            ->where('workspace_id', $this->authWorkspace()->id)
            ->whereKey($sessionId)
            ->firstOrFail();

        if ($session->status !== 'active') {
            Flux::toast(variant: 'danger', text: 'Session is not active.');

            return;
        }

        $omada = app(OmadaService::class);
        $result = $omada->unauthorizeClient($session->client_mac, $this->authWorkspace());

        $session->update([
            'status' => 'disconnected',
            'time_ended' => now(),
        ]);

        // Broadcast disconnection event
        SessionDisconnected::dispatch($session);

        if ($result['success']) {
            Flux::toast(variant: 'success', text: "Client {$session->client_mac} disconnected from WiFi.");
        } else {
            Flux::toast(variant: 'warning', text: 'Marked disconnected locally. Omada: '.($result['error'] ?? 'Could not reach controller'));
        }
    }

    /**
     * Refresh sessions list when a broadcast event fires.
     */
    #[On('echo:admin-dashboard,.SessionStarted')]
    #[On('echo:admin-dashboard,.SessionDisconnected')]
    public function refreshFromBroadcast(): void
    {
        unset($this->sessions, $this->activeCount, $this->expiredCount, $this->disconnectedCount, $this->totalCount);
    }

    /**
     * Open detail modal for a session.
     */
    public function viewSession(int $sessionId): void
    {
        $this->viewingSessionId = $sessionId;
    }

    /**
     * Close detail modal.
     */
    public function closeSessionDetail(): void
    {
        $this->viewingSessionId = null;
    }

    #[Computed]
    public function sessions(): LengthAwarePaginator
    {
        return GuestSession::with('plan')
            ->where('workspace_id', $this->authWorkspace()->id)
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('client_mac', 'like', "%{$this->search}%")
                    ->orWhere('ip_address', 'like', "%{$this->search}%")
                    ->orWhere('ssid', 'like', "%{$this->search}%")
                    ->orWhere('username', 'like', "%{$this->search}%");
            }))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderBy($this->sortBy, $this->sortDir)
            ->paginate(25);
    }

    #[Computed]
    public function viewingSession(): ?GuestSession
    {
        return $this->viewingSessionId
            ? GuestSession::with('plan', 'payments')
                ->where('workspace_id', $this->authWorkspace()->id)
                ->find($this->viewingSessionId)
            : null;
    }

    #[Computed]
    public function activeCount(): int
    {
        return GuestSession::active()->where('workspace_id', $this->authWorkspace()->id)->count();
    }

    #[Computed]
    public function expiredCount(): int
    {
        return GuestSession::where('workspace_id', $this->authWorkspace()->id)->where('status', 'expired')->count();
    }

    #[Computed]
    public function disconnectedCount(): int
    {
        return GuestSession::where('workspace_id', $this->authWorkspace()->id)->where('status', 'disconnected')->count();
    }

    #[Computed]
    public function totalCount(): int
    {
        return GuestSession::where('workspace_id', $this->authWorkspace()->id)->count();
    }
}
