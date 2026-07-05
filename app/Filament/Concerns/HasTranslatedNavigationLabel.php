<?php

namespace App\Filament\Concerns;

use App\Support\AgricartTranslator;
use Filament\Clusters\Cluster;

trait HasTranslatedNavigationLabel
{
    public static function getNavigationLabel(): string
    {
        $pageSlug = static::getSlug() ?? 'overview';
        $moduleSlug = static::resolveModuleSlug();
        $fallback = static::$navigationLabel ?? null;

        return AgricartTranslator::page($moduleSlug, $pageSlug, $fallback);
    }

    protected static function resolveModuleSlug(): string
    {
        $cluster = static::getCluster();

        if (is_string($cluster) && is_subclass_of($cluster, Cluster::class)) {
            return $cluster::getSlug() ?? 'app';
        }

        return 'app';
    }
}
