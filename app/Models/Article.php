<?php

namespace App\Models;

use App\Filament\ArticleBodyMediaProvider;
use App\Support\Editorial\ArticleBody;
use App\Support\Media\PublicImage;
use App\Traits\SynchronizesTranslatedSlugs;
use Database\Factories\ArticleFactory;
use Filament\Forms\Components\RichEditor\Models\Concerns\InteractsWithRichContent;
use Filament\Forms\Components\RichEditor\Models\Contracts\HasRichContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

class Article extends Model implements HasMedia, HasRichContent, LocalizedUrlRoutable
{
    public const string IMAGE_COLLECTION = 'article_image';

    public const string IMAGE_CONVERSION = 'article_hero';

    public const string THUMBNAIL_CONVERSION = 'article_card';

    public const string OPEN_GRAPH_CONVERSION = 'article_open_graph';

    public const int HERO_WIDTH = 1600;

    public const int HERO_HEIGHT = 900;

    public const int CARD_WIDTH = 720;

    public const int CARD_HEIGHT = 480;

    public const int OPEN_GRAPH_WIDTH = 1200;

    public const int OPEN_GRAPH_HEIGHT = 630;

    public const string BODY_AR_COLLECTION = 'article_body_ar';

    public const string BODY_EN_COLLECTION = 'article_body_en';

    public const string BODY_IMAGE_CONVERSION = 'article_body';

    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    use HasTranslatableSlug {
        getLocalizedRouteKey as private getSpatieLocalizedRouteKey;
        resolveRouteBindingQuery as private resolveTranslatableRouteBindingQuery;
    }
    use HasTranslations;
    use InteractsWithMedia;
    use InteractsWithRichContent;
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
        'body',
        'read_minutes',
        'image_alt',
        'image_caption',
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
        'body',
        'body_ar',
        'body_en',
        'published_at',
        'modified_at',
        'image',
        'image_alt',
        'image_caption',
        'read_minutes',
        'topic_keys',
        'featured',
        'source_url',
        'is_published',
        'editorial_revision',
    ];

    protected $attributes = [
        'featured' => false,
        'is_published' => false,
        'editorial_revision' => 1,
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
            'editorial_revision' => 'integer',
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
            ->useDisk((string) config('media-library.disk_name'))
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif'])
            ->singleFile();

        foreach ([self::BODY_AR_COLLECTION, self::BODY_EN_COLLECTION] as $collection) {
            $this->addMediaCollection($collection)
                ->useDisk((string) config('media-library.disk_name'))
                ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif'])
                ->withResponsiveImages();
        }
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion(self::IMAGE_CONVERSION)
            ->performOnCollections(self::IMAGE_COLLECTION)
            ->fit(Fit::Crop, self::HERO_WIDTH, self::HERO_HEIGHT)
            ->format('webp')
            ->quality(86)
            ->withResponsiveImages()
            ->nonQueued();

        $this->addMediaConversion(self::THUMBNAIL_CONVERSION)
            ->performOnCollections(self::IMAGE_COLLECTION)
            ->fit(Fit::Crop, self::CARD_WIDTH, self::CARD_HEIGHT)
            ->format('webp')
            ->quality(82)
            ->withResponsiveImages()
            ->nonQueued();

        $this->addMediaConversion(self::OPEN_GRAPH_CONVERSION)
            ->performOnCollections(self::IMAGE_COLLECTION)
            ->fit(Fit::Crop, self::OPEN_GRAPH_WIDTH, self::OPEN_GRAPH_HEIGHT)
            ->format('jpg')
            ->quality(85)
            ->nonQueued();

        $this->addMediaConversion(self::BODY_IMAGE_CONVERSION)
            ->performOnCollections(self::BODY_AR_COLLECTION, self::BODY_EN_COLLECTION)
            ->fit(Fit::Max, 1600, 1600)
            ->format('webp')
            ->quality(84)
            ->withResponsiveImages()
            ->nonQueued();
    }

    public function imageUrl(string $conversion = self::IMAGE_CONVERSION): string
    {
        if ($this->hasMedia(self::IMAGE_COLLECTION)) {
            return $this->getFirstMediaUrl(self::IMAGE_COLLECTION, $conversion);
        }

        return $this->image ?? '';
    }

    /**
     * @return array{src: string, srcset: string, width: int, height: int}
     */
    public function responsiveImage(
        string $conversion = self::IMAGE_CONVERSION,
        int $fallbackWidth = self::HERO_WIDTH,
        int $fallbackHeight = self::HERO_HEIGHT,
    ): array {
        return PublicImage::fromMedia(
            $this->getFirstMedia(self::IMAGE_COLLECTION),
            $this->image,
            $conversion,
            $fallbackWidth,
            $fallbackHeight,
        );
    }

    public function imageAlt(string $locale): string
    {
        return (string) ($this->getTranslation('image_alt', $locale, false)
            ?: $this->getTranslation('title', $locale, false));
    }

    /**
     * @return array{src: string, width: int|null, height: int|null, type: string|null}
     */
    public function openGraphImage(): array
    {
        $media = $this->getFirstMedia(self::IMAGE_COLLECTION);

        if ($media instanceof Media && $media->hasGeneratedConversion(self::OPEN_GRAPH_CONVERSION)) {
            return [
                'src' => $media->getUrl(self::OPEN_GRAPH_CONVERSION),
                'width' => self::OPEN_GRAPH_WIDTH,
                'height' => self::OPEN_GRAPH_HEIGHT,
                'type' => 'image/jpeg',
            ];
        }

        $source = $media instanceof Media ? $media->getUrl() : (string) $this->image;

        return [
            'src' => PublicImage::fromUrl($source, 0, 0)['src'],
            'width' => null,
            'height' => null,
            'type' => null,
        ];
    }

    public function imageCaption(string $locale): string
    {
        return (string) $this->getTranslation('image_caption', $locale, false);
    }

    public static function bodyAttribute(string $locale): string
    {
        return $locale === 'en' ? 'body_en' : 'body_ar';
    }

    public static function bodyCollection(string $locale): string
    {
        return $locale === 'en' ? self::BODY_EN_COLLECTION : self::BODY_AR_COLLECTION;
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

    /** @return BelongsToMany<Service, $this> */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'article_service')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('article_service.sort_order')
            ->orderBy('services.id');
    }

    /** @return BelongsToMany<Project, $this> */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'article_project')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('article_project.sort_order')
            ->orderBy('projects.id');
    }

    /** @return HasMany<EditorialArticleRevisionSnapshot, $this> */
    public function revisionSnapshots(): HasMany
    {
        return $this->hasMany(EditorialArticleRevisionSnapshot::class)
            ->orderByDesc('revision');
    }

    /** @return list<string> */
    public function relatedServiceKeys(): array
    {
        $services = $this->relationLoaded('services')
            ? $this->getRelation('services')
            : $this->services()->get(['services.id', 'services.key']);

        return $services
            ->pluck('key')
            ->map(fn (mixed $key): string => (string) $key)
            ->values()
            ->all();
    }

    /** @return list<string> */
    public function relatedProjectKeys(): array
    {
        $projects = $this->relationLoaded('projects')
            ? $this->getRelation('projects')
            : $this->projects()->get(['projects.id', 'projects.key']);

        return $projects
            ->pluck('key')
            ->map(fn (mixed $key): string => (string) $key)
            ->values()
            ->all();
    }

    protected static function booted(): void
    {
        static::saving(function (Article $article): void {
            $readingMinutes = $article->getTranslations('read_minutes');
            $calculator = app(ArticleBody::class);

            foreach (['ar', 'en'] as $locale) {
                $body = $article->getTranslation('body', $locale, false);

                if (filled($body)) {
                    $readingMinutes[$locale] = $calculator->readingMinutes($body, $locale);
                }
            }

            if ($readingMinutes !== []) {
                $article->setAttribute('read_minutes', $readingMinutes);
            }
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

    protected function setUpRichContent(): void
    {
        foreach (['ar', 'en'] as $locale) {
            $this->registerRichContent(self::bodyAttribute($locale))
                ->json()
                ->fileAttachmentsDisk((string) config('media-library.disk_name'))
                ->fileAttachmentsVisibility('public')
                ->fileAttachmentProvider(
                    ArticleBodyMediaProvider::make()->collection(self::bodyCollection($locale)),
                );
        }
    }

    /**
     * Filament's rich-content attachment provider requires a concrete model
     * attribute. This adapter stores its state in the translatable `body` JSON.
     */
    protected function bodyAr(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): mixed => $this->bodyTranslationFromAttributes($attributes, 'ar'),
            set: fn (mixed $value, array $attributes): array => [
                'body' => $this->bodyTranslationsJson($attributes, 'ar', $value),
            ],
        );
    }

    /**
     * Filament's rich-content attachment provider requires a concrete model
     * attribute. This adapter stores its state in the translatable `body` JSON.
     */
    protected function bodyEn(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): mixed => $this->bodyTranslationFromAttributes($attributes, 'en'),
            set: fn (mixed $value, array $attributes): array => [
                'body' => $this->bodyTranslationsJson($attributes, 'en', $value),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function bodyTranslationFromAttributes(array $attributes, string $locale): mixed
    {
        $translations = json_decode((string) ($attributes['body'] ?? '{}'), true);

        return is_array($translations) ? ($translations[$locale] ?? null) : null;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function bodyTranslationsJson(array $attributes, string $locale, mixed $value): string
    {
        $translations = json_decode((string) ($attributes['body'] ?? '{}'), true);
        $translations = is_array($translations) ? $translations : [];
        $translations[$locale] = $value;

        return json_encode($translations, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
