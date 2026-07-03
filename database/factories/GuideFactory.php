<?php

namespace Database\Factories;

use App\Models\Guide;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guide>
 */
class GuideFactory extends Factory
{
    protected $model = Guide::class;

    public function definition(): array
    {
        return [
            'title' => [
                'ar' => fake('ar_SA')->sentence(6),
                'en' => fake()->sentence(6),
            ],
            'description' => [
                'ar' => fake('ar_SA')->paragraph(),
                'en' => fake()->paragraph(),
            ],
            'sort_order' => fake()->numberBetween(0, 100),
            'is_draft' => false,
            'is_active' => true,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'is_draft' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
