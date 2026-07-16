<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\ArticleReadingProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArticleReadingProgress>
 */
class ArticleReadingProgressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'user_id' => User::factory(),
            'progress_percent' => fake()->numberBetween(1, 100),
            'last_read_at' => now(),
            'completed_at' => null,
        ];
    }
}
