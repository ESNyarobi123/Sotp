<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    protected $model = Workspace::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            // UserFactory auto-provisions a workspace; this factory also persists one row,
            // so drop the auto row to avoid duplicate workspaces_user_id_unique.
            'user_id' => User::factory()->afterCreating(function (User $user): void {
                $user->workspace()->delete();
            }),
            'brand_name' => $name,
            'public_slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'omada_site_id' => null,
            'provisioning_status' => 'ready',
            'provisioning_error' => null,
            'provisioning_attempts' => 0,
            'provisioning_last_attempted_at' => null,
            'provisioning_next_retry_at' => null,
        ];
    }

    public function omadaSite(string $siteId): static
    {
        return $this->state(fn (array $attributes) => [
            'omada_site_id' => $siteId,
            'provisioning_status' => 'ready',
            'provisioning_error' => null,
            'provisioning_attempts' => 0,
            'provisioning_last_attempted_at' => null,
            'provisioning_next_retry_at' => null,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'omada_site_id' => null,
            'provisioning_status' => 'pending',
            'provisioning_error' => null,
            'provisioning_attempts' => 0,
            'provisioning_last_attempted_at' => null,
            'provisioning_next_retry_at' => null,
        ]);
    }
}
