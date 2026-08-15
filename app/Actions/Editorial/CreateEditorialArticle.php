<?php

namespace App\Actions\Editorial;

use App\Models\Article;
use App\Services\EditorialApi\EditorialArticleRelations;
use App\Support\Editorial\ArticleBody;
use Illuminate\Support\Facades\DB;

class CreateEditorialArticle
{
    public function __construct(
        private readonly ArticleBody $articleBody,
        private readonly EditorialArticleRelations $relations,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function handle(array $attributes): Article
    {
        $this->relations->validate($attributes);

        return DB::transaction(function () use ($attributes): Article {
            $article = Article::query()->create([
                ...$this->articleBody->normalizeInput($this->relations->withoutRelationKeys($attributes)),
                'published_at' => today(),
                'modified_at' => today(),
                'featured' => (bool) ($attributes['featured'] ?? false),
                'is_published' => false,
                'editorial_revision' => 1,
            ]);

            $this->relations->sync($article, $attributes);
            $article = $article->refresh();
            $this->relations->captureRevisionSnapshot($article, 'article.created');

            return $article->load(['services:id,key', 'projects:id,key']);
        });
    }
}
