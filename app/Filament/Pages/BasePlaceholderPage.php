<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\BaseAgricartCluster;
use App\Filament\Concerns\ChecksAgricartPermissions;
use App\Filament\Concerns\HasTranslatedNavigationLabel;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

abstract class BasePlaceholderPage extends Page
{
    use ChecksAgricartPermissions;
    use HasTranslatedNavigationLabel;

    protected string $view = 'filament.pages.placeholder';

    public function mount(): void
    {
        if (! static::allowsAccessForComingSoonCluster()) {
            $this->redirectToComingSoonOverview();
        }
    }

    protected function redirectToComingSoonOverview(): void
    {
        $cluster = static::getCluster();

        if (! is_string($cluster)) {
            return;
        }

        $module = str(class_basename($cluster))->beforeLast('Cluster')->toString();
        $overviewClass = "App\\Filament\\Clusters\\{$module}\\OverviewPage";

        if (class_exists($overviewClass)) {
            redirect($overviewClass::getUrl());
        }
    }

    protected static function allowsAccessForComingSoonCluster(): bool
    {
        $cluster = static::getCluster();

        if (! is_string($cluster) || ! is_subclass_of($cluster, BaseAgricartCluster::class)) {
            return true;
        }

        if (! $cluster::isComingSoon()) {
            return true;
        }

        return str_ends_with(class_basename(static::class), 'OverviewPage');
    }

    public function isComingSoonModulePage(): bool
    {
        $cluster = static::getCluster();

        return is_string($cluster)
            && is_subclass_of($cluster, BaseAgricartCluster::class)
            && $cluster::isComingSoon();
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        $current = [
            static::getUrl() => static::getNavigationLabel(),
        ];

        if (filled($cluster = static::getCluster())) {
            return $cluster::unshiftClusterBreadcrumbs($current);
        }

        return $current;
    }

    public function getPlaceholderMessage(): string
    {
        if ($this->isComingSoonModulePage()) {
            return __('agricart.module_coming_soon.message');
        }

        return __('agricart.placeholder.message');
    }

    public function getPlaceholderTitle(): ?string
    {
        if ($this->isComingSoonModulePage()) {
            return __('agricart.module_coming_soon.title');
        }

        return null;
    }
}
