<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

trait SynchronizesTranslatedSlugs
{
    protected static function bootSynchronizesTranslatedSlugs(): void
    {
        static::whenBooted(function (): void {
            static::creating(fn (Model $model) => $model->synchronizeTranslatedSlugColumns());
            static::updating(fn (Model $model) => $model->synchronizeTranslatedSlugColumns());
        });
    }

    protected function synchronizeTranslatedSlugColumns(): void
    {
        $this->setAttribute('slug_ar', trim((string) $this->getTranslation('slug', 'ar', false)));
        $this->setAttribute('slug_en', trim((string) $this->getTranslation('slug', 'en', false)));
    }
}
