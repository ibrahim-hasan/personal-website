<?php

namespace Database\Factories;

use App\Models\Author;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuthorFactory extends Factory
{
    protected $model = Author::class;

    public function definition(): array
    {
        return [
            'name' => [
                'ar' => fake()->name(),
                'en' => fake()->name(),
            ],
            'description' => [
                'ar' => fake()->paragraph(),
                'en' => fake()->paragraph(),
            ],
            'position' => [
                'ar' => fake()->jobTitle(),
                'en' => fake()->jobTitle(),
            ],
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
