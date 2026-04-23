<?php

namespace Database\Factories;

use App\Models\Workspace;
use App\Models\WorkspaceWallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkspaceWallet>
 */
class WorkspaceWalletFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'currency' => 'TZS',
            'available_balance' => 0,
            'pending_withdrawal_balance' => 0,
            'lifetime_credited' => 0,
            'lifetime_withdrawn' => 0,
        ];
    }
}
