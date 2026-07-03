<?php

namespace App\Models;

use App\Support\DashboardCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\File;
use Spatie\Translatable\HasTranslations;

class Guide extends Model implements HasMedia
{
    use HasFactory, HasTranslations, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'sort_order',
        'is_draft',
        'is_active',
    ];

    public array $translatable = ['title', 'description'];

    protected $attributes = [
        'sort_order' => 0,
        'is_draft' => false,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'description' => 'array',
            'sort_order' => 'integer',
            'is_draft' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function downloaders(): HasMany
    {
        return $this->hasMany(GuideDownloader::class);
    }

    public function getCoverImageUrl(): string
    {
        $url = $this->getFirstMediaUrl('cover_image');

        return $url !== '' ? $url : asset('images/bg-cover-2.png');
    }

    public function localizedTitle(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return (string) ($this->getTranslation('title', $locale) ?: $this->getTranslation('title', 'ar') ?: $this->getTranslation('title', 'en') ?: '');
    }

    public function localizedDescription(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return (string) ($this->getTranslation('description', $locale) ?: $this->getTranslation('description', 'ar') ?: $this->getTranslation('description', 'en') ?: '');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover_image')
            ->useDisk('public')
            ->singleFile()
            ->acceptsFile(fn (File $file): bool => in_array($file->mimeType, ['image/jpeg', 'image/png', 'image/webp'], true));

        $this->addMediaCollection('guide_file')
            ->useDisk('local')
            ->singleFile()
            ->acceptsFile(fn (File $file): bool => $file->mimeType === 'application/pdf');
    }

    public function scopePosted(Builder $query): Builder
    {
        return $query->where('is_draft', false)->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    protected static function booted(): void
    {
        static::saved(fn () => DashboardCache::bust());
        static::deleted(fn () => DashboardCache::bust());
        static::restored(fn () => DashboardCache::bust());
        static::forceDeleted(fn () => DashboardCache::bust());
    }
}
