<?php

namespace App\Services\Catalog;

use Illuminate\Support\Facades\Cache;

class CatalogCache
{
    public const OVERVIEW_STATS_KEY = 'catalog.overview.stats';

    public static function forgetOverviewStats(): void
    {
        Cache::forget(self::OVERVIEW_STATS_KEY);
    }
}
