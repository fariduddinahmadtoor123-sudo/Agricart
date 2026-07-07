<?php

namespace App\Services\Purchasing;

use Illuminate\Support\Facades\Cache;

class PurchasingCache
{
    public const OVERVIEW_STATS_KEY = 'purchasing.overview.stats';

    public static function forgetOverviewStats(): void
    {
        Cache::forget(self::OVERVIEW_STATS_KEY);
    }
}
