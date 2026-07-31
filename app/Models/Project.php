<?php

namespace App\Models;

use App\Enums\ProjectAssetPermissionStatus;
use App\Enums\ProjectDeliveryEntity;
use App\Enums\ProjectDisclosureLevel;
use App\Enums\ProjectEvidenceLevel;
use App\Enums\ProjectPermissionStatus;
use App\Support\Media\PublicImage;
use App\Traits\SynchronizesTranslatedSlugs;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mcamara\LaravelLocalization\Interfaces\LocalizedUrlRoutable;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasTranslatableSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

class Project extends Model implements HasMedia, LocalizedUrlRoutable
{
    public const string IMAGE_COLLECTION = 'project_image';

    public const string LOGO_COLLECTION = 'project_logo';

    public const string IMAGE_CONVERSION = 'portfolio';

    public const string THUMBNAIL_CONVERSION = 'thumbnail';

    public const string LOGO_CONVERSION = 'logo_display';

    public const int HERO_WIDTH = 1400;

    public const int HERO_HEIGHT = 900;

    public const int CARD_WIDTH = 480;

    public const int CARD_HEIGHT = 320;

    public const int LOGO_WIDTH = 640;

    public const int LOGO_HEIGHT = 320;

    /** @use HasFactory<ProjectFactory> */
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
        'sector',
        'summary',
        'challenge',
        'response',
        'outcome',
        'image_alt',
        'logo_alt',
        'ibrahim_role',
        'delivery_period',
        'confidentiality_note',
        'case_study_sections',
    ];

    protected $fillable = [
        'key',
        'slug',
        'title',
        'sector',
        'summary',
        'challenge',
        'response',
        'outcome',
        'lens',
        'image',
        'image_alt',
        'logo',
        'logo_alt',
        'tags',
        'sort_order',
        'featured',
        'is_active',
        'ibrahim_role',
        'delivery_entity',
        'delivery_period',
        'disclosure_level',
        'evidence_level',
        'permission_status',
        'permission_reference',
        'confidentiality_note',
        'case_study_sections',
        'case_study_reviewed_at',
        'is_detailed_case_study',
        'image_permission_status',
        'image_permission_reference',
        'logo_permission_status',
        'logo_permission_reference',
    ];

    protected $attributes = [
        'sort_order' => 0,
        'featured' => false,
        'is_active' => true,
        'permission_status' => ProjectPermissionStatus::Unreviewed->value,
        'is_detailed_case_study' => false,
        'image_permission_status' => ProjectAssetPermissionStatus::Unreviewed->value,
        'logo_permission_status' => ProjectAssetPermissionStatus::Unreviewed->value,
    ];

    protected $hidden = [
        'permission_reference',
        'image_permission_reference',
        'logo_permission_reference',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'sort_order' => 'integer',
            'featured' => 'boolean',
            'is_active' => 'boolean',
            'delivery_entity' => ProjectDeliveryEntity::class,
            'disclosure_level' => ProjectDisclosureLevel::class,
            'evidence_level' => ProjectEvidenceLevel::class,
            'permission_status' => ProjectPermissionStatus::class,
            'case_study_reviewed_at' => 'immutable_datetime',
            'is_detailed_case_study' => 'boolean',
            'image_permission_status' => ProjectAssetPermissionStatus::class,
            'logo_permission_status' => ProjectAssetPermissionStatus::class,
            'permission_reference' => 'encrypted',
            'image_permission_reference' => 'encrypted',
            'logo_permission_reference' => 'encrypted',
        ];
    }

    /** @param Builder<Project> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_active', true);
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

        return $bindingQuery->where('is_active', true);
    }

    /** @return BelongsToMany<Service, $this> */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('project_service.sort_order')
            ->orderBy('services.id');
    }

    /** @return BelongsToMany<Article, $this> */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_project')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('article_project.sort_order')
            ->orderBy('articles.id');
    }

    /** @return HasMany<ProjectEvidence, $this> */
    public function evidence(): HasMany
    {
        return $this->hasMany(ProjectEvidence::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function registerMediaCollections(): void
    {
        $acceptedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/avif'];

        $this->addMediaCollection(self::IMAGE_COLLECTION)
            ->useDisk((string) config('media-library.disk_name'))
            ->acceptsMimeTypes($acceptedMimeTypes)
            ->singleFile();

        $this->addMediaCollection(self::LOGO_COLLECTION)
            ->useDisk((string) config('media-library.disk_name'))
            ->acceptsMimeTypes($acceptedMimeTypes)
            ->singleFile();
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

        $this->addMediaConversion(self::LOGO_CONVERSION)
            ->performOnCollections(self::LOGO_COLLECTION)
            ->fit(Fit::Contain, self::LOGO_WIDTH, self::LOGO_HEIGHT)
            ->format('webp')
            ->quality(90)
            ->withResponsiveImages()
            ->nonQueued();
    }

    /**
     * @return array{key: string, id: string, title: string, sector: string, summary: string, challenge: string, response: string, outcome: string, lens: string, image: string, image_media: array{src: string, srcset: string, width: int, height: int}, alt: string, logo: string, logo_media: array{src: string, srcset: string, width: int, height: int}, logo_alt: string, tags: list<string>}
     */
    public function toPortfolioArray(string $locale): array
    {
        $mayRenderImage = $this->mayRenderImage();
        $mayRenderLogo = $this->mayRenderLogo();

        return [
            'key' => $this->key,
            'id' => $this->getTranslation('slug', $locale),
            'title' => $this->translation('title', $locale),
            'sector' => $this->translation('sector', $locale),
            'summary' => $this->translation('summary', $locale),
            'challenge' => $this->translation('challenge', $locale),
            'response' => $this->translation('response', $locale),
            'outcome' => $this->translation('outcome', $locale),
            'lens' => $this->lens,
            'image' => $mayRenderImage ? $this->imageUrl() : '',
            'image_media' => $mayRenderImage
                ? $this->responsiveImage(self::THUMBNAIL_CONVERSION, self::CARD_WIDTH, self::CARD_HEIGHT)
                : PublicImage::hidden(self::CARD_WIDTH, self::CARD_HEIGHT),
            'alt' => $mayRenderImage ? $this->translation('image_alt', $locale) : '',
            'logo' => $mayRenderLogo ? $this->logoUrl() : '',
            'logo_media' => $mayRenderLogo
                ? $this->responsiveLogo()
                : PublicImage::hidden(self::LOGO_WIDTH, self::LOGO_HEIGHT),
            'logo_alt' => $mayRenderLogo ? $this->translation('logo_alt', $locale) : '',
            'tags' => collect($this->tags ?? [])
                ->map(fn (array $tag): string => $this->tagTranslation($tag, $locale))
                ->filter()
                ->values()
                ->all(),
        ];
    }

    public function imageUrl(string $conversion = self::IMAGE_CONVERSION): string
    {
        return $this->mediaUrl(self::IMAGE_COLLECTION, $conversion, $this->image);
    }

    public function logoUrl(string $conversion = self::LOGO_CONVERSION): string
    {
        return $this->mediaUrl(self::LOGO_COLLECTION, $conversion, $this->logo);
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

    /**
     * @return array{src: string, srcset: string, width: int, height: int}
     */
    public function responsiveLogo(): array
    {
        return PublicImage::fromMedia(
            $this->getFirstMedia(self::LOGO_COLLECTION),
            $this->logo,
            self::LOGO_CONVERSION,
            self::LOGO_WIDTH,
            self::LOGO_HEIGHT,
        );
    }

    public function mayRenderImage(): bool
    {
        return $this->hasApprovedAssetPermission('image');
    }

    public function mayRenderLogo(): bool
    {
        return $this->hasApprovedAssetPermission('logo');
    }

    public function isAnonymizedForPublic(): bool
    {
        return $this->disclosure_level === ProjectDisclosureLevel::Anonymized;
    }

    private function hasApprovedAssetPermission(string $asset): bool
    {
        if ($this->isAnonymizedForPublic()) {
            return false;
        }

        $status = $this->{"{$asset}_permission_status"};
        $reference = $this->{"{$asset}_permission_reference"};

        return $status === ProjectAssetPermissionStatus::Approved
            && is_string($reference)
            && trim($reference) !== '';
    }

    private function mediaUrl(string $collection, string $conversion, ?string $legacyPath): string
    {
        if ($this->hasMedia($collection)) {
            return $this->getFirstMediaUrl($collection, $conversion);
        }

        return $legacyPath ?? '';
    }

    private function translation(string $attribute, string $locale): string
    {
        return (string) $this->getTranslation($attribute, $locale);
    }

    /** @param array<string, mixed> $tag */
    private function tagTranslation(array $tag, string $locale): string
    {
        $fallbackLocale = (string) config('app.fallback_locale', 'en');

        return (string) ($tag[$locale] ?? $tag[$fallbackLocale] ?? '');
    }
}
