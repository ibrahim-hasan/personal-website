<?php

namespace Database\Factories;

use App\Enums\ArticleAudioStatus;
use App\Models\ArticleAudio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArticleAudio>
 */
class ArticleAudioFactory extends Factory
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
            'status' => ArticleAudioStatus::Ready,
            'disk' => 'public',
            'path' => 'article-audio/ar/ai-value-example.mp3',
            'mime_type' => 'audio/mpeg',
            'file_size' => fake()->numberBetween(100_000, 5_000_000),
            'character_count' => fake()->numberBetween(1_000, 10_000),
            'segment_count' => 1,
            'content_hash' => hash('sha256', fake()->sentence()),
            'voice_id' => 'test-voice',
            'model_id' => 'eleven_multilingual_v2',
            'output_format' => 'mp3_44100_128',
            'voice_settings' => [
                'stability' => 0.72,
                'similarity_boost' => 0.78,
                'style' => 0.08,
                'speed' => 0.96,
                'use_speaker_boost' => true,
            ],
            'request_ids' => [fake()->uuid()],
            'queued_at' => now()->subMinutes(2),
            'generation_started_at' => now()->subMinute(),
            'generated_at' => now(),
            'failed_at' => null,
            'last_error' => null,
        ];
    }

    public function queued(): static
    {
        return $this->state(fn (): array => [
            'status' => ArticleAudioStatus::Queued,
            'path' => null,
            'generated_at' => null,
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn (): array => [
            'status' => ArticleAudioStatus::Processing,
            'path' => null,
            'generated_at' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => ArticleAudioStatus::Failed,
            'path' => null,
            'generated_at' => null,
            'failed_at' => now(),
            'last_error' => 'Audio generation failed.',
        ]);
    }
}
