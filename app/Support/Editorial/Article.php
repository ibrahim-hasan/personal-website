<?php

namespace App\Support\Editorial;

use App\Models\Article as ArticleRecord;
use App\Support\Media\PublicImage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Lang;

final readonly class Article
{
    private const int TABLE_OF_CONTENTS_MINIMUM_HEADINGS = 4;

    private const int TABLE_OF_CONTENTS_MINIMUM_MINUTES = 6;

    /**
     * @param  array{ar: string, en: string}  $slugs
     * @param  array{ar: int, en: int}  $readMinutes
     * @param  list<string>  $topicKeys
     * @param  array<string, array<string, mixed>>  $translations
     */
    public function __construct(
        public string $key,
        public array $slugs,
        public string $publishedAt,
        public string $modifiedAt,
        public string $image,
        public array $readMinutes,
        public array $topicKeys,
        public bool $featured = false,
        public ?string $sourceUrl = null,
        public array $translations = [],
        public ?ArticleRecord $record = null,
    ) {}

    public function slug(string $locale): string
    {
        return $this->slugs[$locale] ?? $this->slugs['ar'];
    }

    /**
     * @return array{src: string, srcset: string, width: int, height: int}
     */
    public function imageMedia(
        string $conversion = ArticleRecord::IMAGE_CONVERSION,
        int $fallbackWidth = ArticleRecord::HERO_WIDTH,
        int $fallbackHeight = ArticleRecord::HERO_HEIGHT,
    ): array {
        if ($this->record !== null) {
            return $this->record->responsiveImage($conversion, $fallbackWidth, $fallbackHeight);
        }

        return PublicImage::fromUrl($this->image, $fallbackWidth, $fallbackHeight);
    }

    /**
     * @return array<string, mixed>
     */
    public function localized(string $locale, bool $includeBody = true): array
    {
        $minutes = $this->readMinutes[$locale] ?? $this->readMinutes['ar'];
        $body = $includeBody ? $this->translatedValue('body', $locale) : null;
        $bodyPresentation = ['html' => '', 'headings' => []];

        if ($includeBody) {
            $bodyPresentation = $this->record !== null
                ? app(ArticleBody::class)->presentForArticle($this->record, $locale)
                : app(ArticleBody::class)->present(
                    is_string($body) || is_array($body) ? $body : null,
                );
        }

        $tableOfContents = array_map(
            fn (array $heading): array => [
                'id' => (string) ($heading['id'] ?? ''),
                'label' => $this->tableOfContentsLabel((string) ($heading['label'] ?? '')),
            ],
            $bodyPresentation['headings'],
        );

        return [
            'key' => $this->key,
            'slug' => $this->slug($locale),
            'title' => $this->translatedString('title', $locale),
            'summary' => $this->translatedString('summary', $locale),
            'seo_title' => $this->translatedString('seo_title', $locale),
            'seo_description' => $this->translatedString('seo_description', $locale),
            'type' => $this->translatedString('type', $locale),
            'lead' => $this->translatedString('lead', $locale),
            'sections' => $this->translatedArray('sections', $locale),
            'closing' => $this->translatedString('closing', $locale),
            'body' => is_string($body) || is_array($body) ? $body : [],
            'body_html' => $bodyPresentation['html'],
            'headings' => $bodyPresentation['headings'],
            'table_of_contents' => $tableOfContents,
            'show_table_of_contents' => $includeBody
                && count($tableOfContents) >= self::TABLE_OF_CONTENTS_MINIMUM_HEADINGS
                && (int) $minutes >= self::TABLE_OF_CONTENTS_MINIMUM_MINUTES,
            'published_at' => $this->publishedAt,
            'modified_at' => $this->modifiedAt,
            'published_label' => Carbon::parse($this->publishedAt)->locale($locale)->translatedFormat('j F Y'),
            'modified_label' => Carbon::parse($this->modifiedAt)->locale($locale)->translatedFormat('j F Y'),
            'image' => $this->image,
            'image_media' => $this->imageMedia(),
            'card_image' => $this->imageMedia(
                ArticleRecord::THUMBNAIL_CONVERSION,
                ArticleRecord::CARD_WIDTH,
                ArticleRecord::CARD_HEIGHT,
            ),
            'image_alt' => $this->translatedString('image_alt', $locale) ?: $this->translatedString('title', $locale),
            'image_caption' => $this->translatedString('image_caption', $locale),
            'read_minutes' => $minutes,
            'read_time' => Lang::choice('articles.reader.minutes', $minutes, ['count' => $minutes], $locale),
            'topic_keys' => $this->topicKeys,
            'topics' => array_map(
                fn (string $topic): string => (string) Lang::get("articles.topics.{$topic}", [], $locale),
                $this->topicKeys,
            ),
            'featured' => $this->featured,
            'source_url' => $this->sourceUrl,
        ];
    }

    private function translatedString(string $field, string $locale): string
    {
        $value = $this->translatedValue($field, $locale);

        return is_string($value) ? $value : '';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function translatedArray(string $field, string $locale): array
    {
        $value = $this->translatedValue($field, $locale);

        return is_array($value) ? $value : [];
    }

    private function translatedValue(string $field, string $locale): mixed
    {
        if (array_key_exists($locale, $this->translations[$field] ?? [])) {
            return $this->translations[$field][$locale];
        }

        $rewriteKey = "article_rewrites.articles.{$this->key}.{$field}";

        if (Lang::has($rewriteKey, $locale)) {
            return Lang::get($rewriteKey, [], $locale);
        }

        $legacyKey = "articles.articles.{$this->key}.{$field}";

        return Lang::has($legacyKey, $locale)
            ? Lang::get($legacyKey, [], $locale)
            : null;
    }

    private function tableOfContentsLabel(string $label): string
    {
        return trim((string) preg_replace(
            '/^\s*(?:\([\d٠-٩]+\)\s*|(?:[\d٠-٩]+|[IVXLCDM]+)[.)\-:]\s*)/u',
            '',
            $label,
        ));
    }
}
