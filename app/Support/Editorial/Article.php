<?php

namespace App\Support\Editorial;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Lang;

final readonly class Article
{
    /**
     * @param  array{ar: string, en: string}  $slugs
     * @param  array{ar: int, en: int}  $readMinutes
     * @param  list<string>  $topicKeys
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
    ) {}

    public function slug(string $locale): string
    {
        return $this->slugs[$locale] ?? $this->slugs['ar'];
    }

    /**
     * @return array<string, mixed>
     */
    public function localized(string $locale): array
    {
        $minutes = $this->readMinutes[$locale] ?? $this->readMinutes['ar'];

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
            'published_at' => $this->publishedAt,
            'modified_at' => $this->modifiedAt,
            'published_label' => Carbon::parse($this->publishedAt)->locale($locale)->translatedFormat('j F Y'),
            'modified_label' => Carbon::parse($this->modifiedAt)->locale($locale)->translatedFormat('j F Y'),
            'image' => $this->image,
            'read_minutes' => $minutes,
            'read_time' => Lang::get('articles.reader.minutes', ['count' => $minutes], $locale),
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
        $value = Lang::get("articles.articles.{$this->key}.{$field}", [], $locale);

        return is_string($value) ? $value : '';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function translatedArray(string $field, string $locale): array
    {
        $value = Lang::get("articles.articles.{$this->key}.{$field}", [], $locale);

        return is_array($value) ? $value : [];
    }
}
