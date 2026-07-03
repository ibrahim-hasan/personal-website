<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

class DashboardCache
{
    public static function version(): int
    {
        return (int) Cache::get('dashboard:version', 1);
    }

    public static function rememberForever(string $key, Closure $callback): mixed
    {
        return Cache::rememberForever(self::key($key), $callback);
    }

    public static function bust(): void
    {
        Cache::increment('dashboard:version');
    }

    private static function key(string $key): string
    {
        return 'dashboard:'.self::version().':'.$key;
    }
}
