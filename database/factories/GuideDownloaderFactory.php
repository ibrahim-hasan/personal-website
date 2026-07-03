<?php

namespace Database\Factories;

use App\Models\Guide;
use App\Models\GuideDownloader;
use Illuminate\Database\Eloquent\Factories\Factory;

class GuideDownloaderFactory extends Factory
{
    protected $model = GuideDownloader::class;

    public function definition(): array
    {
        return [
            'guide_id' => Guide::factory(),
            'email' => fake()->unique()->safeEmail(),
            'is_mail_sent' => true,
            'download_token' => null,
            'token_expires_at' => null,
            'token_used_at' => null,
        ];
    }

    public function unsent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_mail_sent' => false,
        ]);
    }
}
