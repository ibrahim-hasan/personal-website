<?php

namespace App\Http\Controllers\Website;

use App\Enums\ArticleAudioStatus;
use App\Http\Controllers\Controller;
use App\Models\Article as ArticleRecord;
use App\Models\ArticleAudio;
use App\Services\ArticleAudio\ArticleAudioScript;
use App\Support\Editorial\ArticleCatalog;
use App\Support\Seo\SeoMetadata;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function __construct(
        private readonly ArticleCatalog $articles,
        private readonly ArticleAudioScript $scripts,
    ) {}

    public function __invoke(ArticleRecord $article): View
    {
        $locale = app()->getLocale();
        $resolvedArticle = $this->articles->findByKey($article->key);

        abort_if($resolvedArticle === null, 404);

        $localizedArticle = $resolvedArticle->localized($locale);
        $canonicalUrl = $this->articles->url($resolvedArticle, $locale);
        $audio = ArticleAudio::query()
            ->where('article_key', $resolvedArticle->key)
            ->where('locale', $locale)
            ->where('status', ArticleAudioStatus::Ready)
            ->whereNotNull('path')
            ->first();
        $articleAudio = null;

        if ($audio !== null) {
            $modelId = $audio->model_id ?: (string) config('services.elevenlabs.model_id');
            $contentHash = $this->scripts->publicFingerprint($resolvedArticle, $locale, $modelId);

            if ($audio->isStale($contentHash)) {
                $audio = null;
            }
        }

        if ($audio !== null && $audio->path !== null && Storage::disk($audio->disk)->exists($audio->path)) {
            $articleAudio = [
                'url' => $audio->publicUrl(),
                'mime_type' => $audio->mime_type ?? 'audio/mpeg',
                'file_size' => $audio->file_size,
                'duration_seconds' => $this->durationSeconds($audio),
                'generated_at' => $audio->generated_at?->toIso8601String(),
                'article_key' => $resolvedArticle->key,
                'locale' => $locale,
            ];
        }

        $articleNodeId = $canonicalUrl.'#article';
        $breadcrumbNodeId = $canonicalUrl.'#breadcrumb';
        $structuredData = [
            [
                '@type' => 'BlogPosting',
                '@id' => $articleNodeId,
                'url' => $canonicalUrl,
                'headline' => $localizedArticle['title'],
                'description' => $localizedArticle['seo_description'],
                'image' => asset($localizedArticle['image']),
                'datePublished' => $localizedArticle['published_at'],
                'dateModified' => $localizedArticle['modified_at'],
                'author' => ['@id' => SeoMetadata::personId()],
                'inLanguage' => $locale,
                'articleSection' => $localizedArticle['type'],
                'keywords' => $localizedArticle['topics'],
                'isAccessibleForFree' => true,
                'isPartOf' => ['@id' => SeoMetadata::websiteId()],
                'mainEntityOfPage' => ['@id' => SeoMetadata::pageId($canonicalUrl)],
            ],
            [
                '@type' => 'BreadcrumbList',
                '@id' => $breadcrumbNodeId,
                'itemListElement' => [
                    [
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => __('site.home.title'),
                        'item' => localized_route('home', locale: $locale),
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => __('site.writing.title'),
                        'item' => localized_route('writing', locale: $locale),
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 3,
                        'name' => $localizedArticle['title'],
                        'item' => $canonicalUrl,
                    ],
                ],
            ],
        ];

        if ($articleAudio !== null) {
            $structuredData[0]['audio'] = [
                '@type' => 'AudioObject',
                'contentUrl' => $articleAudio['url'],
                'encodingFormat' => $articleAudio['mime_type'],
                'inLanguage' => $locale,
                'name' => $localizedArticle['title'],
            ];
        }

        return view('website.article', [
            'article' => $localizedArticle,
            'canonicalUrl' => $canonicalUrl,
            'alternateUrls' => $this->articles->alternateUrls($resolvedArticle),
            'relatedArticles' => $this->articles->related($resolvedArticle, locale: $locale),
            'articleAudio' => $articleAudio,
            'structuredData' => $structuredData,
        ]);
    }

    private function durationSeconds(ArticleAudio $audio): ?int
    {
        if ($audio->duration_seconds !== null && $audio->duration_seconds > 0) {
            return $audio->duration_seconds;
        }

        $fileSize = $audio->file_size;

        if ($fileSize === null && $audio->path !== null && Storage::disk($audio->disk)->exists($audio->path)) {
            $fileSize = Storage::disk($audio->disk)->size($audio->path);
        }

        if ($fileSize === null || $fileSize <= 0) {
            return null;
        }

        preg_match('/_(\d+)(?:$|\?)/', (string) $audio->output_format, $matches);
        $bitrate = (int) ($matches[1] ?? 0);

        return $bitrate > 0 ? (int) round(($fileSize * 8) / ($bitrate * 1000)) : null;
    }
}
