<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Models\Workspace;
use App\Models\WorkspaceWallet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WithdrawalRequest>
 */
class WithdrawalRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $workspace = Workspace::factory();
        $wallet = WorkspaceWallet::factory()->state(fn () => [
            'workspace_id' => $workspace,
        ]);

        return [
            'workspace_id' => $workspace,
            'workspace_wallet_id' => $wallet,
            'requested_by' => User::factory(),
            'reviewed_by' => null,
            'reference' => 'WDR'.Str::upper(Str::random(10)),
            'status' => 'pending',
            'amount' => fake()->randomElement([5000, 10000, 15000]),
            'currency' => 'TZS',
            'phone_number' => '2557'.fake()->numerify('#######'),
            'review_notes' => null,
            'failure_reason' => null,
            'approved_at' => null,
            'rejected_at' => null,
            'paid_at' => null,
            'meta' => null,
        ];
    }
}
