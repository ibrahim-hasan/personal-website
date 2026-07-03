<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait CacheFlushable
{
    public static function bootCacheFlushable(): void
    {
        static::saved(fn () => static::flushModelCache());
        static::deleted(fn () => static::flushModelCache());
    }

    protected static function flushModelCache(): void
    {
        if (Cache::supportsTags()) {
            Cache::tags([static::class])->flush();

            return;
        }

        // Fallback for stores like "database" that do not implement tags.
        Cache::flush();
    }
}
