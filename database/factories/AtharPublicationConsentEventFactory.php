<?php

namespace Database\Factories;

use App\Enums\AtharConsentEventType;
use App\Enums\AtharIdentityDisplay;
use App\Enums\AtharPlacement;
use App\Models\AtharContribution;
use App\Models\AtharPublicationConsentEvent;
use App\Models\AtharPublicationVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AtharPublicationConsentEvent>
 */
class AtharPublicationConsentEventFactory extends Factory
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
            'publication_version_id' => AtharPublicationVersion::factory(),
            'event_type' => AtharConsentEventType::Approved,
            'snapshot_hash' => hash('sha256', fake()->sentence()),
            'approved_locales' => ['en'],
            'placement' => AtharPlacement::About,
            'identity_display' => AtharIdentityDisplay::Anonymous,
            'privacy_notice_version' => '2026-07-22',
            'verification_method' => 'email_code',
            'occurred_at' => now(),
        ];
    }
}
