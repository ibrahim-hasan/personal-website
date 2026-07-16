<?php

namespace App\Models;

use App\Enums\CommentReportStatus;
use Database\Factories\CommentReportFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommentReport extends Model
{
    /** @use HasFactory<CommentReportFactory> */
    use HasFactory;

    protected $fillable = [
        'comment_id',
        'reporter_user_id',
        'reason',
        'details',
        'status',
        'reviewed_by_user_id',
        'reviewed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => CommentReportStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Comment, $this> */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    /** @return BelongsTo<User, $this> */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    /** @param  Builder<CommentReport>  $query */
    public function scopePending(Builder $query): void
    {
        $query->where('status', CommentReportStatus::Pending);
    }
}
