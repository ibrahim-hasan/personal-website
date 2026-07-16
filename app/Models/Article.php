<?php

namespace App\Models;

use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Article extends Model implements HasMedia
{
    public const string IMAGE_COLLECTION = 'article_image';

    public const string IMAGE_CONVERSION = 'article_hero';

    public const string THUMBNAIL_CONVERSION = 'article_card';

    /** @use HasFactory<ArticleFactory> */
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'key',
        'slugs',
        'title',
        'summary',
        'seo_title',
        'seo_description',
        'type',
        'lead',
        'sections',
        'closing',
        'published_at',
        'modified_at',
        'image',
        'read_minutes',
        'topic_keys',
        'featured',
        'source_url',
        'is_published',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'slugs' => 'array',
            'title' => 'array',
            'summary' => 'array',
            'seo_title' => 'array',
            'seo_description' => 'array',
            'type' => 'array',
            'lead' => 'array',
            'sections' => 'array',
            'closing' => 'array',
            'published_at' => 'immutable_date',
            'modified_at' => 'immutable_date',
            'read_minutes' => 'array',
            'topic_keys' => 'array',
            'featured' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    /** @param Builder<Article> $query */
    public function scopePublished(Builder $query): void
    {
        $query
            ->where('is_published', true)
            ->whereDate('published_at', '<=', today());
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::IMAGE_COLLECTION)
            ->useDisk('public')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif'])
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion(self::IMAGE_CONVERSION)
            ->performOnCollections(self::IMAGE_COLLECTION)
            ->fit(Fit::Crop, 1600, 900)
            ->format('webp')
            ->quality(86)
            ->nonQueued();

        $this->addMediaConversion(self::THUMBNAIL_CONVERSION)
            ->performOnCollections(self::IMAGE_COLLECTION)
            ->fit(Fit::Crop, 720, 480)
            ->format('webp')
            ->quality(82)
            ->nonQueued();
    }

    public function imageUrl(string $conversion = self::IMAGE_CONVERSION): string
    {
        if ($this->hasMedia(self::IMAGE_COLLECTION)) {
            return $this->getFirstMediaUrl(self::IMAGE_COLLECTION, $conversion);
        }

        return $this->image ?? '';
    }

    /** @return HasMany<Comment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /** @return HasMany<ArticleAppreciation, $this> */
    public function appreciations(): HasMany
    {
        return $this->hasMany(ArticleAppreciation::class);
    }

    /** @return HasMany<ArticleBookmark, $this> */
    public function bookmarks(): HasMany
    {
        return $this->hasMany(ArticleBookmark::class);
    }

    /** @return HasMany<ArticleReadingProgress, $this> */
    public function readingProgress(): HasMany
    {
        return $this->hasMany(ArticleReadingProgress::class);
    }

    /** @return HasMany<ArticleAudio, $this> */
    public function audioTracks(): HasMany
    {
        return $this->hasMany(ArticleAudio::class);
    }

    /** @return HasMany<ArticleNarration, $this> */
    public function narrations(): HasMany
    {
        return $this->hasMany(ArticleNarration::class);
    }

    protected static function booted(): void
    {
        static::saving(function (Article $article): void {
            $slugs = (array) $article->slugs;

            $article->setAttribute('slug_ar', trim((string) ($slugs['ar'] ?? '')));
            $article->setAttribute('slug_en', trim((string) ($slugs['en'] ?? '')));
        });

        static::created(function (Article $article): void {
            if (Schema::hasColumn((new ArticleAudio)->getTable(), 'article_id')) {
                ArticleAudio::query()
                    ->whereNull('article_id')
                    ->where('article_key', $article->key)
                    ->update(['article_id' => $article->getKey()]);
            }

            if (Schema::hasColumn((new ArticleNarration)->getTable(), 'article_id')) {
                ArticleNarration::query()
                    ->whereNull('article_id')
                    ->where('article_key', $article->key)
                    ->update(['article_id' => $article->getKey()]);
            }
        });

        static::updated(function (Article $article): void {
            if (! $article->wasChanged('key')) {
                return;
            }

            if (Schema::hasColumn((new ArticleAudio)->getTable(), 'article_id')) {
                ArticleAudio::query()
                    ->where('article_id', $article->getKey())
                    ->update(['article_key' => $article->key]);
            }

            if (Schema::hasColumn((new ArticleNarration)->getTable(), 'article_id')) {
                ArticleNarration::query()
                    ->where('article_id', $article->getKey())
                    ->update(['article_key' => $article->key]);
            }
        });

        static::forceDeleting(function (Article $article): void {
            $audioQuery = ArticleAudio::query()->where('article_key', $article->key);

            if (Schema::hasColumn((new ArticleAudio)->getTable(), 'article_id')) {
                $audioQuery->orWhere('article_id', $article->getKey());
            }

            $audioQuery->get()->each(function (ArticleAudio $audio): void {
                $audio->delete();
            });

            $narrationQuery = ArticleNarration::query()->where('article_key', $article->key);

            if (Schema::hasColumn((new ArticleNarration)->getTable(), 'article_id')) {
                $narrationQuery->orWhere('article_id', $article->getKey());
            }

            $narrationQuery->get()->each(function (ArticleNarration $narration): void {
                $narration->delete();
            });
        });
    }
}
