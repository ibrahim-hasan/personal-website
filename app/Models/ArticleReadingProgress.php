<?php

namespace App\Models;

use Database\Factories\ArticleReadingProgressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class ArticleReadingProgress extends Model
{
    /** @use HasFactory<ArticleReadingProgressFactory> */
    use HasFactory;

    protected $fillable = [
        'article_id',
        'user_id',
        'progress_percent',
        'last_read_at',
        'completed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'progress_percent' => 'integer',
            'last_read_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Article, $this> */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(User $user, int $articleId, int $percent): void
    {
        $percent = max(0, min(100, $percent));
        $recordedAt = now();

        DB::transaction(function () use ($articleId, $percent, $recordedAt, $user): void {
            self::query()->upsert(
                [
                    [
                        'article_id' => $articleId,
                        'user_id' => $user->getKey(),
                        'progress_percent' => $percent,
                        'last_read_at' => $recordedAt,
                        'completed_at' => $percent >= 95 ? $recordedAt : null,
                    ],
                ],
                uniqueBy: ['article_id', 'user_id'],
                update: ['updated_at'],
            );

            $progress = self::query()
                ->where('article_id', $articleId)
                ->where('user_id', $user->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $progressPercent = max($progress->progress_percent, $percent);
            $lastReadAt = $progress->last_read_at?->isAfter($recordedAt)
                ? $progress->last_read_at
                : $recordedAt;

            $progress->update([
                'progress_percent' => $progressPercent,
                'last_read_at' => $lastReadAt,
                'completed_at' => $progress->completed_at
                    ?? ($progressPercent >= 95 ? $recordedAt : null),
            ]);
        }, 3);
    }
}
