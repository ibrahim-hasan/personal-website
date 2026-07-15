<?php

namespace Database\Factories;

use App\Enums\ArticleNarrationStatus;
use App\Models\ArticleNarration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArticleNarration>
 */
class ArticleNarrationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'article_key' => 'ai-value',
            'locale' => 'ar',
            'requested_by_user_id' => null,
            'status' => ArticleNarrationStatus::Draft,
            'source_hash' => hash('sha256', fake()->paragraph()),
            'script' => fake()->paragraphs(6, true),
            'preparation_model' => 'gpt-5.6-terra',
            'prompt_version' => 'arabic-editorial-v1',
            'preparation_notes' => ['Improved pacing and sentence boundaries.'],
            'pronunciation_notes' => [],
            'samples' => [],
            'preparation_started_at' => now()->subMinute(),
            'prepared_at' => now(),
            'approved_at' => null,
            'failed_at' => null,
            'last_error' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => ArticleNarrationStatus::Approved,
            'approved_at' => now(),
        ]);
    }

    public function preparing(): static
    {
        return $this->state(fn (): array => [
            'status' => ArticleNarrationStatus::Preparing,
            'script' => null,
            'prepared_at' => null,
        ]);
    }
}
