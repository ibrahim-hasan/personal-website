<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Support\Editorial\ArticleCatalog;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (ArticleCatalog::bootstrapRecords() as $record) {
            $keyMatch = Article::withTrashed()->where('key', $record['key'])->first();
            $slugMatch = Article::withTrashed()->where('slug_en', $record['slug']['en'])->first();

            if ($keyMatch !== null && $slugMatch !== null && $keyMatch->isNot($slugMatch)) {
                throw new \RuntimeException("Article identity collision for [{$record['key']}].");
            }

            if ($keyMatch !== null || $slugMatch !== null) {
                continue;
            }

            $record['slug'] = $record['slug'] ?? $record['slugs'] ?? [];
            unset($record['slugs']);

            Article::query()->create($record);
        }
    }
}
