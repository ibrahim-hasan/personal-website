<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EditorialArticleRevisionSnapshot extends Model
{
    protected $fillable = [
        'article_id',
        'revision',
        'action',
        'service_keys',
        'project_keys',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'revision' => 'integer',
            'service_keys' => 'array',
            'project_keys' => 'array',
        ];
    }

    /** @return BelongsTo<Article, $this> */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
