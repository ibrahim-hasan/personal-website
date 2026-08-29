<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Seeder;
use RuntimeException;

class SeoAuthorityEditorialContentSeeder extends Seeder
{
    /** @var list<string> */
    private const UPDATE_FIELDS = [
        'title',
        'summary',
        'seo_title',
        'seo_description',
        'type',
        'body',
        'image_alt',
        'image_caption',
        'read_minutes',
        'topic_keys',
    ];

    /** @var array<string, string> */
    private const RELEASE_IMAGE_HASHES = [
        'ai-adoption-roadmap-saudi-companies-2026' => '9d261eed9164c7c187d102c9ac3a5207687a652eee63886dd9999ef6241e27e3',
        'ai-use-case-register-governance-template' => 'b1213b871d6dc289851b7331bf73220fd30a7b603200c5eb0805afae12be1932',
        'data-governance-before-ai' => '15aece9a08fc1c0f15e8d4522be5350129c88a353072a461d4ebb7bdeccf8f45',
        'digital-transformation-roadmap-process-to-impact' => 'c229f01003b24842e5e3f701f862d9c5b956effcb1282eb002a167865a09f481',
        'workflow-audit-what-should-be-automated' => 'b4a5f77080b916ff8a0574afad47b8c8058f1cb4fd997c2a596184891eda72b3',
    ];

    public function run(): void
    {
        $records = require database_path('migrations/data/seo_authority_editorial_content_v1.php');

        foreach ($records as $record) {
            $article = $this->findExisting($record);

            if ($article === null) {
                if (($record['operation'] ?? null) !== 'create') {
                    throw new RuntimeException(
                        "Required source article [{$record['key']}] is missing from the SEO authority bootstrap.",
                    );
                }

                $article = Article::query()->create($this->createAttributes($record));
            } elseif (($record['operation'] ?? null) === 'update' && $this->isUntouchedBootstrapArticle($article)) {
                $article->update([
                    ...$this->updateAttributes($record),
                    'editorial_revision' => $article->editorial_revision + 1,
                ]);
                $article = $article->refresh();
            }

            if (($record['operation'] ?? null) === 'create' && $this->isReleaseBaseline($article, $record)) {
                $this->attachManagedHero($article, $record);
                $article->update(['is_published' => true]);
            }
        }
    }

    /** @param array<string, mixed> $record */
    private function findExisting(array $record): ?Article
    {
        $keyMatch = Article::withTrashed()->where('key', $record['key'])->first();
        $slugMatches = [
            Article::withTrashed()->where('slug_ar', $record['slug']['ar'])->first(),
            Article::withTrashed()->where('slug_en', $record['slug']['en'])->first(),
        ];

        foreach ($slugMatches as $slugMatch) {
            if ($slugMatch !== null && ($keyMatch === null || $keyMatch->isNot($slugMatch))) {
                throw new RuntimeException("Article identity collision for [{$record['key']}].");
            }
        }

        return $keyMatch;
    }

    private function isUntouchedBootstrapArticle(Article $article): bool
    {
        return ! $article->trashed()
            && $article->editorial_revision === 1
            && $article->modified_at->toDateString() < '2026-08-29';
    }

    /** @param array<string, mixed> $record */
    private function isReleaseBaseline(Article $article, array $record): bool
    {
        return ! $article->trashed()
            && $article->editorial_revision === 1
            && $article->modified_at->toDateString() === '2026-08-29'
            && $article->image === $record['image'];
    }

    /** @param array<string, mixed> $record */
    private function attachManagedHero(Article $article, array $record): void
    {
        if ($article->hasMedia(Article::IMAGE_COLLECTION)) {
            return;
        }

        $source = public_path($record['image']);
        $expectedHash = self::RELEASE_IMAGE_HASHES[$record['key']] ?? null;

        if ($expectedHash === null || ! is_file($source) || ! hash_equals($expectedHash, hash_file('sha256', $source))) {
            throw new RuntimeException("Managed release image is unavailable for [{$record['key']}].");
        }

        $extension = pathinfo($source, PATHINFO_EXTENSION);
        $article
            ->addMedia($source)
            ->preservingOriginal()
            ->usingName($record['title']['en'])
            ->usingFileName($record['key'].'.'.$extension)
            ->toMediaCollection(Article::IMAGE_COLLECTION);
    }

    /** @param array<string, mixed> $record */
    private function createAttributes(array $record): array
    {
        unset($record['operation']);

        return $record;
    }

    /** @param array<string, mixed> $record */
    private function updateAttributes(array $record): array
    {
        $attributes = [];

        foreach (self::UPDATE_FIELDS as $field) {
            $attributes[$field] = $record[$field];
        }

        $attributes['modified_at'] = $record['modified_at'];

        return $attributes;
    }
}
