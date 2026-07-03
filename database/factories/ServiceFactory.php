<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'name' => [
                'ar' => fake()->sentence(3),
                'en' => fake()->sentence(3),
            ],
            'problems_you_are_facing' => [
                'ar' => fake()->paragraph(),
                'en' => fake()->paragraph(),
            ],
            'how_can_we_help' => [
                'ar' => fake()->paragraph(),
                'en' => fake()->paragraph(),
            ],
            'type_of_intervention' => [
                'ar' => fake()->sentence(),
                'en' => fake()->sentence(),
            ],
            'results' => [
                'ar' => fake()->paragraph(),
                'en' => fake()->paragraph(),
            ],
            'order' => fake()->numberBetween(1, 100),
            'is_draft' => false,
            'is_active' => true,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_draft' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
