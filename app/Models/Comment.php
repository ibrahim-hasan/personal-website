<?php

namespace App\Models;

use App\Enums\CommentStatus;
use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'article_id',
        'user_id',
        'parent_id',
        'body',
        'status',
        'moderated_by_user_id',
        'moderated_at',
        'moderation_note',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => CommentStatus::class,
            'moderated_at' => 'datetime',
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

    /** @return BelongsTo<Comment, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<Comment, $this> */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** @return HasMany<CommentReport, $this> */
    public function reports(): HasMany
    {
        return $this->hasMany(CommentReport::class);
    }

    /** @param Builder<Comment> $query */
    public function scopeApproved(Builder $query): void
    {
        $query->where('status', CommentStatus::Approved);
    }

    public function approve(User $moderator): void
    {
        $this->update([
            'status' => CommentStatus::Approved,
            'moderated_by_user_id' => $moderator->getKey(),
            'moderated_at' => now(),
            'moderation_note' => null,
        ]);
    }

    public function reject(User $moderator, ?string $note = null): void
    {
        $this->update([
            'status' => CommentStatus::Rejected,
            'moderated_by_user_id' => $moderator->getKey(),
            'moderated_at' => now(),
            'moderation_note' => $note,
        ]);
    }
}
