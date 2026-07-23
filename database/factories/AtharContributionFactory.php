<?php

namespace Database\Factories;

use App\Enums\AtharContributionStatus;
use App\Models\AtharContribution;
use App\Models\AtharInvitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AtharContribution>
 */
class AtharContributionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invitation_id' => AtharInvitation::factory(),
            'status' => AtharContributionStatus::Draft,
            'draft_payload' => ['freeform' => fake()->paragraph()],
            'draft_updated_at' => now(),
        ];
    }

    public function submitted(): static
    {
        return $this->state([
            'status' => AtharContributionStatus::Submitted,
            'sealed_payload' => ['freeform' => fake()->paragraph()],
            'source_hash' => hash('sha256', fake()->sentence()),
            'submitted_at' => now(),
        ]);
    }
}
