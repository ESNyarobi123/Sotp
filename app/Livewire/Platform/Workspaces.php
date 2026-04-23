<?php

namespace App\Livewire\Platform;

use App\Models\Payment;
use App\Models\Workspace;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Platform Workspaces')]
class Workspaces extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    public ?int $editingId = null;

    public bool $showForm = false;

    public int $maxDevices = 10;

    public int $maxPlans = 20;

    public int $maxSessions = 0;

    public ?int $viewingId = null;

    public function mount(): void
    {
        abort_unless((bool) auth()->user()?->isAdmin(), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function editLimits(int $workspaceId): void
    {
        $ws = Workspace::findOrFail($workspaceId);
        $this->editingId = $ws->id;
        $this->maxDevices = $ws->max_devices;
        $this->maxPlans = $ws->max_plans;
        $this->maxSessions = $ws->max_sessions;
        $this->showForm = true;
    }

    public function saveLimits(): void
    {
        $this->validate([
            'maxDevices' => 'required|integer|min:1|max:1000',
            'maxPlans' => 'required|integer|min:1|max:500',
            'maxSessions' => 'required|integer|min:0|max:100000',
        ]);

        $ws = Workspace::findOrFail($this->editingId);
        $ws->update([
            'max_devices' => $this->maxDevices,
            'max_plans' => $this->maxPlans,
            'max_sessions' => $this->maxSessions,
        ]);

        $this->showForm = false;
        $this->editingId = null;
        Flux::toast(variant: 'success', text: 'Workspace limits updated.');
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->resetValidation();
    }

    public function viewWorkspace(int $id): void
    {
        $this->viewingId = $id;
    }

    public function closeDetail(): void
    {
        $this->viewingId = null;
    }

    public function suspend(int $id): void
    {
        $ws = Workspace::findOrFail($id);
        $ws->update(['is_suspended' => true, 'suspension_reason' => 'Suspended by admin', 'suspended_at' => now()]);
        unset($this->viewingWorkspace);
        Flux::toast(variant: 'warning', text: "'{$ws->brand_name}' suspended.");
    }

    public function unsuspend(int $id): void
    {
        $ws = Workspace::findOrFail($id);
        $ws->update(['is_suspended' => false, 'suspension_reason' => null, 'suspended_at' => null]);
        unset($this->viewingWorkspace);
        Flux::toast(variant: 'success', text: "'{$ws->brand_name}' unsuspended.");
    }

    #[Computed]
    public function workspaces(): LengthAwarePaginator
    {
        return Workspace::with(['user', 'wallet'])
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('brand_name', 'like', "%{$this->search}%")
                    ->orWhere('public_slug', 'like', "%{$this->search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%"));
            }))
            ->when($this->statusFilter === 'suspended', fn ($q) => $q->where('is_suspended', true))
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_suspended', false)->where('provisioning_status', 'ready'))
            ->when($this->statusFilter === 'pending', fn ($q) => $q->whereIn('provisioning_status', ['pending', 'provisioning']))
            ->latest()
            ->paginate(25);
    }

    #[Computed]
    public function viewingWorkspace(): ?Workspace
    {
        return $this->viewingId ? Workspace::with(['user', 'wallet'])->find($this->viewingId) : null;
    }

    #[Computed]
    public function totalWorkspaces(): int
    {
        return Workspace::count();
    }

    #[Computed]
    public function activeWorkspaces(): int
    {
        return Workspace::where('is_suspended', false)->where('provisioning_status', 'ready')->count();
    }

    #[Computed]
    public function totalRevenue(): string
    {
        return number_format(Payment::completed()->sum('amount'), 0);
    }
}
