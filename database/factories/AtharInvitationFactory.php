<?php

namespace Database\Factories;

use App\Enums\AtharInvitationDeliveryMode;
use App\Enums\AtharInvitationStatus;
use App\Enums\AtharPlacement;
use App\Enums\AtharRelationship;
use App\Models\AtharInvitation;
use App\Models\User;
use App\Support\AtharAccess;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AtharInvitation>
 */
class AtharInvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $email = fake()->safeEmail();
        $token = Str::random(64);

        return [
            'created_by' => User::factory(),
            'token_hash' => AtharAccess::tokenHash($token),
            'token_ciphertext' => $token,
            'email_hash' => hash_hmac('sha256', $email, (string) config('app.key')),
            'email' => $email,
            'delivery_mode' => AtharInvitationDeliveryMode::Email,
            'recipient_name' => fake()->name(),
            'relationship' => AtharRelationship::FormerClient,
            'preferred_locale' => 'en',
            'personal_reason' => fake()->sentence(),
            'prompt_snapshot' => [],
            'placement' => AtharPlacement::About,
            'status' => AtharInvitationStatus::Sent,
            'expires_at' => now()->addDays(14),
            'sent_at' => now(),
        ];
    }

    public function revoked(): static
    {
        return $this->state(['status' => AtharInvitationStatus::Revoked, 'revoked_at' => now()]);
    }

    public function linkOnly(): static
    {
        return $this->state([
            'delivery_mode' => AtharInvitationDeliveryMode::Link,
            'email_hash' => null,
            'email' => null,
            'sent_at' => null,
        ]);
    }
}
