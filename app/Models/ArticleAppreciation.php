<?php

namespace App\Models;

use Database\Factories\ArticleAppreciationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleAppreciation extends Model
{
    /** @use HasFactory<ArticleAppreciationFactory> */
    use HasFactory;

    protected $fillable = ['article_id', 'user_id'];

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
}
