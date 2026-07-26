<?php

namespace Database\Factories;

use App\Enums\ContactInquiryStatus;
use App\Models\ContactInquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactInquiry>
 */
class ContactInquiryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'company' => fake()->company(),
            'role' => fake()->jobTitle(),
            'service_key' => 'ai-adoption',
            'service_label' => 'AI Adoption Engineering',
            'challenge' => fake()->paragraphs(2, true),
            'timing' => fake()->randomElement(['This month', 'Next quarter']),
            'locale' => fake()->randomElement(['ar', 'en']),
            'status' => ContactInquiryStatus::New,
            'received_at' => now(),
        ];
    }
}
