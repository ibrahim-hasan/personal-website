<?php

namespace App\Models;

use App\Support\DashboardCache;
use App\Traits\Posted;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

class Service extends Model implements HasMedia
{
    use HasFactory;
    use HasTranslations;
    use InteractsWithMedia;
    use Posted;
    use SoftDeletes;

    protected $fillable = [
        'key',
        'name',
        'summary',
        'problem',
        'approach',
        'deliverables',
        'result',
        'fit_signals',
        'engagement_note',
        'order',
        'is_draft',
        'is_active',
    ];

    protected $translatable = [
        'name',
        'summary',
        'problem',
        'approach',
        'result',
        'fit_signals',
        'engagement_note',
    ];

    protected $attributes = [
        'order' => 0,
        'is_draft' => true,
        'is_active' => false,
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
     * @return array{key: string, id: string, name: string, summary: string, problem: string, approach: string, deliverables: list<string>, result: string, fit_signals: list<string>, engagement_note: string}
     */
    public function toPublicArray(string $locale): array
    {
        return [
            'key' => $this->key,
            'id' => 'service-'.$this->key,
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
