<?php

namespace App\Models;

use App\Traits\SynchronizesTranslatedSlugs;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Mcamara\LaravelLocalization\Interfaces\LocalizedUrlRoutable;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasTranslatableSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

class Article extends Model implements HasMedia, LocalizedUrlRoutable
{
    public const string IMAGE_COLLECTION = 'article_image';

    public const string IMAGE_CONVERSION = 'article_hero';

    public const string THUMBNAIL_CONVERSION = 'article_card';

    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    use HasTranslatableSlug {
        getLocalizedRouteKey as private getSpatieLocalizedRouteKey;
        resolveRouteBindingQuery as private resolveTranslatableRouteBindingQuery;
    }
    use HasTranslations;
    use InteractsWithMedia;
    use SoftDeletes;
    use SynchronizesTranslatedSlugs;

    protected $translatable = [
        'slug',
        'title',
        'summary',
        'seo_title',
        'seo_description',
        'type',
        'lead',
        'sections',
        'closing',
        'read_minutes',
    ];

    protected $fillable = [
        'key',
        'slug',
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
            'published_at' => 'immutable_date',
            'modified_at' => 'immutable_date',
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

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(180)
            ->preventOverwrite();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getLocalizedRouteKey($locale): mixed
    {
        return $this->getSpatieLocalizedRouteKey((string) $locale);
    }

    public function resolveRouteBindingQuery($query, $value, $field = null): Builder|Relation
    {
        $bindingQuery = $this->resolveTranslatableRouteBindingQuery($query, $value, $field);
        $bindingField = $field ?? $this->getRouteKeyName();

        if ($bindingField !== 'slug' && ! str_ends_with($bindingField, '.slug')) {
            return $bindingQuery;
        }

        return $bindingQuery
            ->where('is_published', true)
            ->whereDate('published_at', '<=', today());
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::IMAGE_COLLECTION)
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
