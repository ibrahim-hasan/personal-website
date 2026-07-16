<?php

namespace App\Models;

use App\Support\DashboardCache;
use App\Traits\Posted;
use App\Traits\SynchronizesTranslatedSlugs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mcamara\LaravelLocalization\Interfaces\LocalizedUrlRoutable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Sluggable\HasTranslatableSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

class Service extends Model implements HasMedia, LocalizedUrlRoutable
{
    use HasFactory;
    use HasTranslatableSlug {
        getLocalizedRouteKey as private getSpatieLocalizedRouteKey;
    }
    use HasTranslations;
    use InteractsWithMedia;
    use Posted;
    use SoftDeletes;
    use SynchronizesTranslatedSlugs;

    protected $fillable = [
        'key',
        'slug',
        'name',
        'summary',
        'problem',
        'approach',
        'deliverables',
        'result',
        'order',
        'is_draft',
        'is_active',
    ];

    protected $translatable = ['slug', 'name', 'summary', 'problem', 'approach', 'result'];

    protected $attributes = [
        'order' => 0,
        'is_draft' => false,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'deliverables' => 'array',
            'order' => 'integer',
            'is_draft' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => DashboardCache::bust());
        static::deleted(fn () => DashboardCache::bust());
        static::restored(fn () => DashboardCache::bust());
        static::forceDeleted(fn () => DashboardCache::bust());
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
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

    /**
     * @return array{key: string, id: string, name: string, summary: string, problem: string, approach: string, deliverables: list<string>, result: string}
     */
    public function toPublicArray(string $locale): array
    {
        return [
            'key' => $this->key,
            'id' => $this->getTranslation('slug', $locale),
            'name' => $this->translation('name', $locale),
            'summary' => $this->translation('summary', $locale),
            'problem' => $this->translation('problem', $locale),
            'approach' => $this->translation('approach', $locale),
            'deliverables' => collect($this->deliverables ?? [])
                ->map(fn (array $deliverable): string => (string) ($deliverable[$locale] ?? $deliverable['en'] ?? $deliverable['ar'] ?? ''))
                ->filter()
                ->values()
                ->all(),
            'result' => $this->translation('result', $locale),
        ];
    }

    private function translation(string $attribute, string $locale): string
    {
        return (string) $this->getTranslation($attribute, $locale);
    }
}
