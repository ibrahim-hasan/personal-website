<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Support\Editorial\ArticleCatalog;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    private const string PREVIOUS_AI_PRODUCT_MOAT_DESCRIPTION = 'A practical guide to durable AI product advantage through proprietary context, compounding data, workflow integration, trust, distribution, and fast learning.';

    private const string REFINED_AI_PRODUCT_MOAT_DESCRIPTION = 'A practical guide to durable AI product advantage through proprietary context, compounding data, workflow integration, trust, and fast learning.';

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

            if ($record['key'] === 'ai-product-moat'
                && ($record['seo_description']['en'] ?? null) === self::PREVIOUS_AI_PRODUCT_MOAT_DESCRIPTION) {
                $record['seo_description']['en'] = self::REFINED_AI_PRODUCT_MOAT_DESCRIPTION;
            }

            Article::query()->create($record);
        }
    }
}
