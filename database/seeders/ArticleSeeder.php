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
            if (Article::withTrashed()->where('key', $record['key'])->exists()) {
                continue;
            }

            $record['slug'] = $record['slug'] ?? $record['slugs'] ?? [];
            unset($record['slugs']);

            Article::query()->create($record);
        }
    }
}
