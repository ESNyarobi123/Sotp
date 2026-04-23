<?php

namespace App\Livewire\Platform;

use App\Models\User;
use App\Models\Workspace;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Platform Users')]
class Users extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $roleFilter = '';

    public bool $showForm = false;

    public ?int $editingUserId = null;

    public ?int $viewingUserId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('nullable|string|min:8|max:255')]
    public string $password = '';

    public string $role = 'user';

    #[Validate('nullable|string|max:255')]
    public string $brandName = '';

    public function mount(): void
    {
        abort_unless((bool) auth()->user()?->isAdmin(), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRoleFilter(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $userId): void
    {
        $user = User::findOrFail($userId);
        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->role = $user->hasRole('admin') ? 'admin' : 'user';
        $this->brandName = $user->workspace?->brand_name ?? '';
        $this->showForm = true;
    }

    public function save(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email'.($this->editingUserId ? ','.$this->editingUserId : ''),
            'role' => 'required|in:admin,user',
            'brandName' => 'nullable|string|max:255',
        ];

        if (! $this->editingUserId) {
            $rules['password'] = 'required|string|min:8|max:255';
        } else {
            $rules['password'] = 'nullable|string|min:8|max:255';
        }

        $this->validate($rules);

        if ($this->editingUserId) {
            $user = User::findOrFail($this->editingUserId);
            $user->update([
                'name' => $this->name,
                'email' => $this->email,
            ]);

            if ($this->password !== '') {
                $user->update(['password' => Hash::make($this->password)]);
            }

            if ($this->role === 'admin') {
                $user->syncRoles(['admin']);
            } else {
                $user->syncRoles([]);
            }

            if ($user->workspace && $this->brandName !== '') {
                $user->workspace->update(['brand_name' => $this->brandName]);
            }

            Flux::toast(variant: 'success', text: 'User updated.');
        } else {
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'email_verified_at' => now(),
            ]);

            if ($this->role === 'admin') {
                $user->assignRole('admin');
            }

            if ($this->brandName !== '') {
                Workspace::create([
                    'user_id' => $user->id,
                    'brand_name' => $this->brandName,
                    'public_slug' => Workspace::uniquePublicSlugFromBrand($this->brandName),
                    'provisioning_status' => 'pending',
                ]);
            }

            Flux::toast(variant: 'success', text: 'User created.');
        }

        $this->closeForm();
    }

    public function viewUser(int $userId): void
    {
        $this->viewingUserId = $userId;
    }

    public function closeDetail(): void
    {
        $this->viewingUserId = null;
    }

    public function suspendWorkspace(int $workspaceId, string $reason = ''): void
    {
        $workspace = Workspace::findOrFail($workspaceId);
        $workspace->update([
            'is_suspended' => true,
            'suspension_reason' => $reason ?: 'Suspended by admin',
            'suspended_at' => now(),
        ]);
        unset($this->viewingUser);
        Flux::toast(variant: 'warning', text: 'Workspace suspended.');
    }

    public function unsuspendWorkspace(int $workspaceId): void
    {
        $workspace = Workspace::findOrFail($workspaceId);
        $workspace->update([
            'is_suspended' => false,
            'suspension_reason' => null,
            'suspended_at' => null,
        ]);
        unset($this->viewingUser);
        Flux::toast(variant: 'success', text: 'Workspace unsuspended.');
    }

    public function deleteUser(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->id === auth()->id()) {
            Flux::toast(variant: 'danger', text: 'You cannot delete yourself.');

            return;
        }

        $user->workspace?->delete();
        $user->delete();

        $this->viewingUserId = null;
        Flux::toast(variant: 'success', text: 'User deleted.');
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingUserId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->role = 'user';
        $this->brandName = '';
        $this->resetValidation();
    }

    #[Computed]
    public function users(): LengthAwarePaginator
    {
        return User::with(['workspace', 'roles'])
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->when($this->roleFilter === 'admin', fn ($q) => $q->role('admin'))
            ->when($this->roleFilter === 'user', fn ($q) => $q->withoutRole('admin'))
            ->latest()
            ->paginate(25);
    }

    #[Computed]
    public function viewingUser(): ?User
    {
        return $this->viewingUserId
            ? User::with(['workspace.wallet', 'roles'])->find($this->viewingUserId)
            : null;
    }

    #[Computed]
    public function totalUsers(): int
    {
        return User::count();
    }

    #[Computed]
    public function adminCount(): int
    {
        return User::role('admin')->count();
    }

    #[Computed]
    public function suspendedCount(): int
    {
        return Workspace::where('is_suspended', true)->count();
    }
}
