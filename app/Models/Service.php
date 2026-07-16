<?php

namespace App\Models;

use App\Support\DashboardCache;
use App\Traits\Posted;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

class Service extends Model implements HasMedia
{
    use HasFactory, HasTranslations, InteractsWithMedia, Posted, SoftDeletes;

    protected $fillable = [
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

    protected $translatable = ['name', 'summary', 'problem', 'approach', 'result'];

    protected $attributes = [
        'order' => 0,
        'is_draft' => false,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'summary' => 'array',
            'problem' => 'array',
            'approach' => 'array',
            'deliverables' => 'array',
            'result' => 'array',
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

    /**
     * @return array{id: string, name: string, summary: string, problem: string, approach: string, deliverables: list<string>, result: string}
     */
    public function toPublicArray(string $locale): array
    {
        return [
            'id' => $this->slug,
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
        $translations = $this->getAttribute($attribute);

        if (! is_array($translations)) {
            return is_string($translations) ? $translations : '';
        }

        return (string) ($translations[$locale] ?? $translations['en'] ?? $translations['ar'] ?? '');
    }
}
