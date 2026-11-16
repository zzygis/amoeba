<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Short-lived cache for responses every visitor gets the same way and that need many
 * database queries: the homepage blocks and the store's filter options.
 *
 * Entries live 60 seconds and are dropped straight away when something they show
 * changes (admin edits, a checkout changing stock), so the cache only saves work and
 * never shows anything seriously out of date.
 */
class CatalogCache
{
    private const TTL_SECONDS = 60;

    private const KEYS = ['catalog:home', 'catalog:filters'];

    public static function remember(string $name, Closure $build): mixed
    {
        return Cache::remember("catalog:{$name}", self::TTL_SECONDS, $build);
    }

    public static function flush(): void
    {
        foreach (self::KEYS as $key) {
            Cache::forget($key);
        }
    }
}
