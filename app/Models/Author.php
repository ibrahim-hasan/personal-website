<?php

namespace App\Models;

use App\Traits\Posted;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\File;
use Spatie\Translatable\HasTranslations;

class Author extends Model implements HasMedia
{
    use HasFactory, HasTranslations, InteractsWithMedia, Posted, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'position',
        'is_draft',
        'is_active',
    ];

    protected $translatable = ['name', 'description', 'position'];

    protected $attributes = [
        'is_draft' => false,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'position' => 'array',
            'is_draft' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function intellectualLibraries(): HasMany
    {
        return $this->hasMany(IntellectualLibrary::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->useDisk('public')
            ->singleFile()
            ->acceptsFile(fn (File $file): bool => in_array($file->mimeType, ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'], true));
    }
}
