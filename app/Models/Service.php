<?php

namespace App\Models;

use App\Support\DashboardCache;
use App\Traits\Posted;
use App\Traits\SynchronizesTranslatedSlugs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
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
        resolveRouteBindingQuery as private resolveTranslatableRouteBindingQuery;
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
        'fit_signals',
        'engagement_note',
        'seo_title',
        'seo_description',
        'order',
        'is_draft',
        'is_active',
    ];

    protected $translatable = [
        'slug',
        'name',
        'summary',
        'problem',
        'approach',
        'result',
        'fit_signals',
        'engagement_note',
        'seo_title',
        'seo_description',
    ];

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

    public function resolveRouteBindingQuery($query, $value, $field = null): Builder|Relation
    {
        $bindingQuery = $this->resolveTranslatableRouteBindingQuery($query, $value, $field);
        $bindingField = $field ?? $this->getRouteKeyName();

        if ($bindingField !== 'slug' && ! str_ends_with($bindingField, '.slug')) {
            return $bindingQuery;
        }

        return $bindingQuery
            ->where('is_draft', false)
            ->where('is_active', true);
    }

    /** @return BelongsToMany<Project, $this> */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('project_service.sort_order')
            ->orderBy('projects.id');
    }

    /** @return BelongsToMany<Article, $this> */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_service')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('article_service.sort_order')
            ->orderBy('articles.id');
    }

    /**
     * @return array{key: string, id: string, name: string, summary: string, problem: string, approach: string, deliverables: list<string>, result: string, fit_signals: list<string>, engagement_note: string, seo_title: string, seo_description: string}
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
                ->map(fn (array $deliverable): string => (string) ($deliverable[$locale] ?? ''))
                ->filter()
                ->values()
                ->all(),
            'result' => $this->translation('result', $locale),
            'fit_signals' => $this->translationList('fit_signals', $locale),
            'engagement_note' => $this->translation('engagement_note', $locale),
            'seo_title' => $this->translation('seo_title', $locale),
            'seo_description' => $this->translation('seo_description', $locale),
        ];
    }

    private function translation(string $attribute, string $locale): string
    {
        return (string) $this->getTranslation($attribute, $locale, false);
    }

    /** @return list<string> */
    private function translationList(string $attribute, string $locale): array
    {
        $value = $this->getTranslation($attribute, $locale, false);

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(fn (mixed $item): bool => is_string($item) && trim($item) !== '')
            ->map(fn (string $item): string => trim($item))
            ->values()
            ->all();
    }
}
