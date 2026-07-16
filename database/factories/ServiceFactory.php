<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        $key = fake()->unique()->slug(2);

        return [
            'key' => $key,
            'slug' => ['ar' => 'خدمة-'.$key, 'en' => $key],
            'name' => [
                'ar' => fake()->sentence(3),
                'en' => fake()->sentence(3),
            ],
            'summary' => [
                'ar' => fake()->paragraph(),
                'en' => fake()->paragraph(),
            ],
            'problem' => [
                'ar' => fake()->paragraph(),
                'en' => fake()->paragraph(),
            ],
            'approach' => [
                'ar' => fake()->paragraph(),
                'en' => fake()->paragraph(),
            ],
            'deliverables' => [
                ['ar' => 'خارطة طريق', 'en' => 'Roadmap'],
                ['ar' => 'نظام قابل للتسليم', 'en' => 'Handoff-ready system'],
            ],
            'result' => [
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
