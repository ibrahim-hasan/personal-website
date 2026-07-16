<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class Project extends Model implements HasMedia
{
    public const string IMAGE_COLLECTION = 'project_image';

    public const string LOGO_COLLECTION = 'project_logo';

    public const string IMAGE_CONVERSION = 'portfolio';

    public const string THUMBNAIL_CONVERSION = 'thumbnail';

    public const string LOGO_CONVERSION = 'logo_display';

    /** @use HasFactory<ProjectFactory> */
    use HasFactory, HasTranslations, InteractsWithMedia, SoftDeletes;

    protected $translatable = [
        'title',
        'sector',
        'summary',
        'challenge',
        'response',
        'outcome',
        'image_alt',
        'logo_alt',
    ];

    protected $fillable = [
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
    ];

    protected $attributes = [
        'sort_order' => 0,
        'featured' => false,
        'is_active' => true,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'title' => 'array',
            'sector' => 'array',
            'summary' => 'array',
            'challenge' => 'array',
            'response' => 'array',
            'outcome' => 'array',
            'image_alt' => 'array',
            'logo_alt' => 'array',
            'tags' => 'array',
            'sort_order' => 'integer',
            'featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @param Builder<Project> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function registerMediaCollections(): void
    {
        $acceptedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/avif'];

        $this->addMediaCollection(self::IMAGE_COLLECTION)
            ->useDisk('public')
            ->acceptsMimeTypes($acceptedMimeTypes)
            ->singleFile();

        $this->addMediaCollection(self::LOGO_COLLECTION)
            ->useDisk('public')
            ->acceptsMimeTypes($acceptedMimeTypes)
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion(self::IMAGE_CONVERSION)
            ->performOnCollections(self::IMAGE_COLLECTION)
            ->fit(Fit::Crop, 1400, 900)
            ->format('webp')
            ->quality(86)
            ->nonQueued();

        $this->addMediaConversion(self::THUMBNAIL_CONVERSION)
            ->performOnCollections(self::IMAGE_COLLECTION)
            ->fit(Fit::Crop, 480, 320)
            ->format('webp')
            ->quality(82)
            ->nonQueued();

        $this->addMediaConversion(self::LOGO_CONVERSION)
            ->performOnCollections(self::LOGO_COLLECTION)
            ->fit(Fit::Contain, 640, 320)
            ->format('webp')
            ->quality(90)
            ->nonQueued();
    }

    /**
     * @return array{id: string, title: string, sector: string, summary: string, challenge: string, response: string, outcome: string, lens: string, image: string, alt: string, logo: string, logo_alt: string, tags: list<string>}
     */
    public function toPortfolioArray(string $locale): array
    {
        return [
            'id' => $this->slug,
            'title' => $this->translation('title', $locale),
            'sector' => $this->translation('sector', $locale),
            'summary' => $this->translation('summary', $locale),
            'challenge' => $this->translation('challenge', $locale),
            'response' => $this->translation('response', $locale),
            'outcome' => $this->translation('outcome', $locale),
            'lens' => $this->lens,
            'image' => $this->mediaUrl(self::IMAGE_COLLECTION, self::IMAGE_CONVERSION, $this->image),
            'alt' => $this->translation('image_alt', $locale),
            'logo' => $this->mediaUrl(self::LOGO_COLLECTION, self::LOGO_CONVERSION, $this->logo),
            'logo_alt' => $this->translation('logo_alt', $locale),
            'tags' => collect($this->tags ?? [])
                ->map(fn (array $tag): string => (string) ($tag[$locale] ?? $tag['en'] ?? $tag['ar'] ?? ''))
                ->filter()
                ->values()
                ->all(),
        ];
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
        $translations = $this->getAttribute($attribute);

        if (! is_array($translations)) {
            return is_string($translations) ? $translations : '';
        }

        return (string) ($translations[$locale] ?? $translations['en'] ?? $translations['ar'] ?? '');
    }
}
