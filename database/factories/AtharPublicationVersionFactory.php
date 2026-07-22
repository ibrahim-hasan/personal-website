<?php

namespace Database\Factories;

use App\Enums\AtharIdentityDisplay;
use App\Enums\AtharPlacement;
use App\Enums\AtharPublicationOrigin;
use App\Enums\AtharPublicationStatus;
use App\Models\AtharContribution;
use App\Models\AtharPublicationVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AtharPublicationVersion>
 */
class AtharPublicationVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contribution_id' => AtharContribution::factory()->submitted(),
            'version' => 1,
            'status' => AtharPublicationStatus::Draft,
            'origin' => AtharPublicationOrigin::ContributorSelected,
            'public_payload' => ['en' => ['text' => fake()->paragraph(), 'context' => fake()->sentence()]],
            'snapshot_hash' => hash('sha256', fake()->sentence()),
            'placement' => AtharPlacement::About,
            'identity_display' => AtharIdentityDisplay::Anonymous,
            'approved_locales' => ['en'],
        ];
    }
}
