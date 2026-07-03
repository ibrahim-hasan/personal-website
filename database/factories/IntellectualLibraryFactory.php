<?php

namespace Database\Factories;

use App\Enums\IntellectualLibraryType;
use App\Models\Author;
use App\Models\IntellectualLibrary;
use Illuminate\Database\Eloquent\Factories\Factory;

class IntellectualLibraryFactory extends Factory
{
    protected $model = IntellectualLibrary::class;

    public function definition(): array
    {
        return [
            'name' => [
                'ar' => fake()->sentence(3),
                'en' => fake()->sentence(3),
            ],
            'slug' => [
                'ar' => fake()->slug(),
                'en' => fake()->slug(),
            ],
            'excert' => [
                'ar' => fake()->paragraph(),
                'en' => fake()->paragraph(),
            ],
            'content' => [
                'ar' => fake()->randomHtml(),
                'en' => fake()->randomHtml(),
            ],
            'type' => IntellectualLibraryType::Article,
            'author_id' => Author::factory(),
            'reading_time' => fake()->numberBetween(5, 30),
            'video_length' => '00:00',
            'seo_title' => [
                'ar' => fake()->sentence(),
                'en' => fake()->sentence(),
            ],
            'seo_description' => [
                'ar' => fake()->paragraph(),
                'en' => fake()->paragraph(),
            ],
            'views' => 0,
            'is_draft' => false,
            'is_active' => true,
            'scheduled_at' => null,
        ];
    }

    public function article(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => IntellectualLibraryType::Article,
            'video_length' => '00:00',
        ]);
    }

    public function video(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => IntellectualLibraryType::Video,
            'video_length' => fake()->time(),
        ]);
    }

    public function podcast(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => IntellectualLibraryType::Podcast,
            'video_length' => fake()->time(),
        ]);
    }

    public function tool(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => IntellectualLibraryType::Tool,
            'reading_time' => null,
            'video_length' => null,
        ]);
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
