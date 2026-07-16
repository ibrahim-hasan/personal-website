<?php

namespace Database\Factories;

use App\Enums\CommentReportStatus;
use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CommentReport> */
class CommentReportFactory extends Factory
{
    protected $model = CommentReport::class;

    public function definition(): array
    {
        return [
            'comment_id' => Comment::factory()->approved(),
            'reporter_user_id' => User::factory(),
            'reason' => fake()->randomElement(['spam', 'abuse', 'misinformation', 'other']),
            'details' => fake()->optional()->sentence(),
            'status' => CommentReportStatus::Pending,
        ];
    }
}
