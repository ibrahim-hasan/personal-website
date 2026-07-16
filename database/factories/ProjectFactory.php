<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $key = fake()->unique()->slug(2);

        return [
            'key' => $key,
            'slug' => ['ar' => 'مشروع-'.$key, 'en' => $key],
            'title' => ['ar' => 'مشروع تجريبي', 'en' => fake()->words(3, true)],
            'sector' => ['ar' => 'الأنظمة الرقمية', 'en' => 'Digital systems'],
            'summary' => ['ar' => fake()->sentence(), 'en' => fake()->sentence()],
            'challenge' => ['ar' => fake()->sentence(), 'en' => fake()->sentence()],
            'response' => ['ar' => fake()->sentence(), 'en' => fake()->sentence()],
            'outcome' => ['ar' => fake()->sentence(), 'en' => fake()->sentence()],
            'lens' => fake()->randomElement(['transformation', 'ai-adoption', 'product', 'operations']),
            'image' => 'images/projects/atlas/digi-pedia-ai-learning.webp',
            'image_alt' => ['ar' => 'صورة المشروع', 'en' => 'Project image'],
            'logo' => null,
            'logo_alt' => ['ar' => '', 'en' => ''],
            'tags' => [
                ['ar' => 'منتج رقمي', 'en' => 'Digital product'],
            ],
            'sort_order' => fake()->numberBetween(1, 20),
            'featured' => false,
            'is_active' => true,
        ];
    }
}
