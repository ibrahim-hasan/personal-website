<?php

namespace App\Services\ArticleAudio;

use App\Enums\ArticleAudioStatus;
use App\Models\ArticleAudio;
use App\Support\Editorial\ArticleCatalog;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

final readonly class HomepageAudioSpotlight
{
    public function __construct(
        private ArticleCatalog $articles,
        private ArticleAudioScript $scripts,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $featuredArticles
     * @return array{title: string, url: string}|null
     */
    public function firstAvailable(array $featuredArticles, string $locale): ?array
    {
        if ($featuredArticles === [] || ! Schema::hasTable((new ArticleAudio)->getTable())) {
            return null;
        }

        $articleKeys = collect($featuredArticles)
            ->pluck('key')
            ->filter(fn (mixed $key): bool => is_string($key) && $key !== '')
            ->values();

        if ($articleKeys->isEmpty()) {
            return null;
        }

        $tracks = ArticleAudio::query()
            ->whereIn('article_key', $articleKeys)
            ->where('locale', $locale)
            ->where('status', ArticleAudioStatus::Ready)
            ->whereNotNull('path')
            ->latest('generated_at')
            ->get([
                'id',
                'article_key',
                'locale',
                'status',
                'disk',
                'path',
                'model_id',
                'content_hash',
                'generated_at',
            ])
            ->groupBy('article_key')
            ->map->first();

        foreach ($featuredArticles as $featuredArticle) {
            $articleKey = $featuredArticle['key'] ?? null;

            if (! is_string($articleKey)) {
                continue;
            }

            /** @var ArticleAudio|null $track */
            $track = $tracks->get($articleKey);
            $article = $this->articles->findByKey($articleKey);

            if ($track === null || $article === null || $track->path === null) {
                continue;
            }

            $modelId = $track->model_id ?: (string) config('services.elevenlabs.model_id');
            $contentHash = $this->scripts->publicFingerprint($article, $locale, $modelId);

            if ($track->isStale($contentHash) || ! Storage::disk($track->disk)->exists($track->path)) {
                continue;
            }

            return [
                'title' => (string) ($featuredArticle['title'] ?? ''),
                'url' => (string) ($featuredArticle['url'] ?? '').'#article-audio',
            ];
        }

        return null;
    }
}
