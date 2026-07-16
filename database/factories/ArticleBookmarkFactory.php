<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\ArticleBookmark;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArticleBookmark>
 */
class ArticleBookmarkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'user_id' => User::factory(),
        ];
    }
}
