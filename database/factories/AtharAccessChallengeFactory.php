<?php

namespace Database\Factories;

use App\Models\AtharAccessChallenge;
use App\Models\AtharInvitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AtharAccessChallenge>
 */
class AtharAccessChallengeFactory extends Factory
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
            'code_hash' => hash('sha256', '123456'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'requested_at' => now(),
            'ip_hash' => hash('sha256', fake()->ipv4()),
        ];
    }
}
