<?php

namespace App\Models;

use App\Enums\IntellectualLibraryType;
use App\Support\DashboardCache;
use App\Traits\Posted;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\File;
use Spatie\Sluggable\HasTranslatableSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Tags\HasTags;
use Spatie\Translatable\HasTranslations;

class IntellectualLibrary extends Model implements HasMedia
{
    use HasFactory, HasTags, HasTranslatableSlug, HasTranslations, InteractsWithMedia, Posted, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'excert',
        'content',
        'type',
        'author_id',
        'reading_time',
        'video_length',
        'youtube_url',
        'seo_title',
        'seo_description',
        'views',
        'is_draft',
        'is_active',
        'scheduled_at',
    ];

    protected $translatable = ['name', 'slug', 'excert', 'content', 'seo_title', 'seo_description'];

    protected $attributes = [
        'views' => 0,
        'is_draft' => false,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'slug' => 'array',
            'excert' => 'array',
            'content' => 'array',
            'seo_title' => 'array',
            'seo_description' => 'array',
            'type' => IntellectualLibraryType::class,
            'views' => 'integer',
            'is_draft' => 'boolean',
            'is_active' => 'boolean',
            'scheduled_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::createWithLocales(config('translatable.locales', ['ar', 'en']))
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->allowDuplicateSlugs()
            ->preventOverwrite();
    }

    public function getSlugForLocale(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $raw = $this->slug;

        if (is_array($raw)) {
            return (string) ($raw[$locale] ?? $raw['ar'] ?? $raw['en'] ?? '');
        }

        return (string) ($raw ?? '');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured_image')
            ->useDisk('public')
            ->singleFile()
            ->acceptsFile(fn (File $file): bool => in_array($file->mimeType, ['image/jpeg', 'image/png', 'image/webp'], true));

        $this->addMediaCollection('cover_image')
            ->useDisk('public')
            ->singleFile()
            ->acceptsFile(fn (File $file): bool => in_array($file->mimeType, ['image/jpeg', 'image/png', 'image/webp'], true));

        $this->addMediaCollection('og_image')
            ->useDisk('public')
            ->singleFile()
            ->acceptsFile(fn (File $file): bool => in_array($file->mimeType, ['image/jpeg', 'image/png', 'image/webp'], true));

        $this->addMediaCollection('tool_file')
            ->useDisk('public')
            ->singleFile()
            ->acceptsFile(fn (File $file): bool => in_array($file->mimeType, ['application/pdf'], true));
    }

    /**
     * Date shown on the public site: explicit publication time, or record creation time.
     */
    public function displayDate(): Attribute
    {
        return Attribute::get(fn () => $this->scheduled_at?->translatedFormat('d M Y') ?? $this->created_at?->translatedFormat('d M Y'));
    }

    public function scopeOrderByDisplayDateDesc(Builder $query): Builder
    {
        $table = $query->getModel()->getTable();

        return $query->orderByRaw("COALESCE({$table}.scheduled_at, {$table}.created_at) DESC");
    }

    public function scopePosted(Builder $query): Builder
    {
        return $query->where('is_draft', false)
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now()));
    }

    protected static function booted(): void
    {
        static::saved(fn () => DashboardCache::bust());
        static::deleted(fn () => DashboardCache::bust());
        static::restored(fn () => DashboardCache::bust());
        static::forceDeleted(fn () => DashboardCache::bust());
    }

    public function localizedSeoTitle(?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $title = trim((string) $this->getTranslation('seo_title', $locale));

        if ($title !== '') {
            return $title;
        }

        $name = trim((string) $this->getTranslation('name', $locale));

        if ($name !== '') {
            return $name;
        }

        $default = Setting::getValue('default_seo_title', 'seo');
        $decoded = is_string($default) ? json_decode($default, true) : $default;

        if (is_array($decoded)) {
            return (string) ($decoded[$locale] ?? $decoded['ar'] ?? $decoded['en'] ?? '');
        }

        return '';
    }

    public function localizedSeoDescription(?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $description = trim((string) $this->getTranslation('seo_description', $locale));

        if ($description !== '') {
            return $description;
        }

        $excerpt = trim((string) $this->getTranslation('excert', $locale));

        if ($excerpt !== '') {
            return $excerpt;
        }

        $default = Setting::getValue('default_seo_description', 'seo');
        $decoded = is_string($default) ? json_decode($default, true) : $default;

        if (is_array($decoded)) {
            return (string) ($decoded[$locale] ?? $decoded['ar'] ?? $decoded['en'] ?? '');
        }

        return '';
    }
}
