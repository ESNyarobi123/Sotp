<?php

namespace App\Livewire\Admin;

use App\Models\Plan;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Title('Plans / Packages')]
class Plans extends Component
{
    public bool $showForm = false;

    public ?int $editingPlanId = null;

    #[Validate('required|string|max:100')]
    public string $name = '';

    #[Validate('required|in:time,data,unlimited')]
    public string $type = 'time';

    #[Validate('nullable|integer|min:1')]
    public ?int $value = null;

    #[Validate('nullable|integer|min:1')]
    public ?int $duration_minutes = null;

    #[Validate('required|numeric|min:0')]
    public string $price = '';

    #[Validate('required|integer|min:1')]
    public int $validity_days = 1;

    #[Validate('nullable|string|max:500')]
    public string $description = '';

    #[Validate('boolean')]
    public bool $is_active = true;

    /**
     * Open the create form.
     */
    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    /**
     * Open the edit form for a plan.
     */
    public function edit(int $planId): void
    {
        $plan = Plan::findOrFail($planId);

        $this->editingPlanId = $plan->id;
        $this->name = $plan->name;
        $this->type = $plan->type;
        $this->value = $plan->value;
        $this->duration_minutes = $plan->duration_minutes;
        $this->price = (string) $plan->price;
        $this->validity_days = $plan->validity_days;
        $this->description = $plan->description ?? '';
        $this->is_active = $plan->is_active;
        $this->showForm = true;
    }

    /**
     * Save or update the plan.
     */
    public function save(): void
    {
        $validated = $this->validate();

        if ($this->type !== 'unlimited' && empty($this->value)) {
            $this->addError('value', $this->type === 'time' ? 'Duration (minutes) is required.' : 'Data limit (MB) is required.');

            return;
        }

        if ($this->type === 'unlimited' && empty($this->duration_minutes)) {
            $this->addError('duration_minutes', 'Duration (minutes) is required for unlimited plans.');

            return;
        }

        $data = [
            'name' => $validated['name'],
            'type' => $validated['type'],
            'value' => $validated['type'] !== 'unlimited' ? $validated['value'] : null,
            'duration_minutes' => $validated['type'] === 'unlimited' ? $validated['duration_minutes'] : null,
            'price' => $validated['price'],
            'validity_days' => $validated['validity_days'],
            'description' => $validated['description'] ?: null,
            'is_active' => $validated['is_active'],
        ];

        if ($this->editingPlanId) {
            Plan::findOrFail($this->editingPlanId)->update($data);
            Flux::toast(variant: 'success', text: 'Plan updated successfully.');
        } else {
            $data['sort_order'] = Plan::max('sort_order') + 1;
            Plan::create($data);
            Flux::toast(variant: 'success', text: 'Plan created successfully.');
        }

        $this->closeForm();
    }

    /**
     * Toggle plan active status.
     */
    public function toggleActive(int $planId): void
    {
        $plan = Plan::findOrFail($planId);
        $plan->update(['is_active' => ! $plan->is_active]);

        $status = $plan->is_active ? 'activated' : 'deactivated';
        Flux::toast(variant: 'success', text: "Plan {$status}.");
    }

    /**
     * Delete a plan.
     */
    public function delete(int $planId): void
    {
        $plan = Plan::findOrFail($planId);

        if ($plan->guestSessions()->exists() || $plan->payments()->exists()) {
            Flux::toast(variant: 'danger', text: 'Cannot delete plan with existing sessions or payments. Deactivate it instead.');

            return;
        }

        $plan->delete();
        Flux::toast(variant: 'success', text: 'Plan deleted.');
    }

    /**
     * Close form and reset.
     */
    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    #[Computed]
    public function plans(): \Illuminate\Database\Eloquent\Collection
    {
        return Plan::orderBy('sort_order')->orderBy('name')->get();
    }

    #[Computed]
    public function activePlansCount(): int
    {
        return Plan::active()->count();
    }

    /**
     * Reset form fields.
     */
    private function resetForm(): void
    {
        $this->editingPlanId = null;
        $this->name = '';
        $this->type = 'time';
        $this->value = null;
        $this->duration_minutes = null;
        $this->price = '';
        $this->validity_days = 1;
        $this->description = '';
        $this->is_active = true;
        $this->resetValidation();
    }
}
