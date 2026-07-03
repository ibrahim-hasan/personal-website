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
        'name',
        'problems_you_are_facing',
        'how_can_we_help',
        'type_of_intervention',
        'results',
        'order',
        'is_draft',
        'is_active',
    ];

    protected $translatable = ['name', 'problems_you_are_facing', 'how_can_we_help', 'type_of_intervention', 'results'];

    protected $attributes = [
        'order' => 0,
        'is_draft' => false,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'problems_you_are_facing' => 'array',
            'how_can_we_help' => 'array',
            'type_of_intervention' => 'array',
            'results' => 'array',
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
}
