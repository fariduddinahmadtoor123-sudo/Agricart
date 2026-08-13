<?php

namespace App\Filament\Clusters;

use App\Models\User;
use App\Services\Access\ModulePageAccess;
use App\Services\Access\PermissionRegistry;
use App\Support\AgricartTranslator;
use Filament\Clusters\Cluster;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

abstract class BaseAgricartCluster extends Cluster
{
    protected static bool $shouldRegisterSubNavigation = false;

    public static function isComingSoon(): bool
    {
        $slug = static::moduleSlug();

        if ($slug === '') {
            return false;
        }

        return in_array($slug, config('agricart.modules_coming_soon', []), true);
    }

    /**
     * Public accessor for the cluster module key used by chrome/toolbars.
     * Protected static::$slug cannot be read from outside this class hierarchy.
     */
    public static function moduleSlug(): string
    {
        return static::$slug ?? '';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->canUseAdmin()) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        $slug = static::moduleSlug();

        if ($slug === '') {
            return false;
        }

        if (PermissionRegistry::moduleUsesPagePermissions($slug)) {
            return ModulePageAccess::canAccessModule(
                $user,
                $slug,
                PermissionRegistry::pageSlugs($slug),
            );
        }

        return $user->hasPermission("{$slug}.view");
    }

    public static function viewPermission(): ?string
    {
        $slug = static::moduleSlug();

        return $slug !== '' ? "{$slug}.view" : null;
    }

    public static function getNavigationLabel(): string
    {
        $slug = static::moduleSlug();

        return AgricartTranslator::module($slug !== '' ? $slug : 'app');
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    public function getTitle(): string|Htmlable
    {
        return static::getNavigationLabel();
    }

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            static::getUrl() => static::getNavigationLabel(),
        ];
    }

    public static function getNavigationUrl(): string
    {
        $overviewPage = static::resolveOverviewPageClass();

        if ($overviewPage !== null && $overviewPage::canAccess()) {
            return $overviewPage::getUrl();
        }

        foreach (static::getClusteredComponents() as $component) {
            if (is_subclass_of($component, Page::class) && $component::canAccess()) {
                return $component::getUrl();
            }
        }

        if ($overviewPage !== null) {
            return $overviewPage::getUrl();
        }

        return parent::getNavigationUrl();
    }

    public function mount(): void
    {
        $overviewPage = static::resolveOverviewPageClass();

        if ($overviewPage !== null) {
            if ($overviewPage::canAccess()) {
                redirect($overviewPage::getUrl());

                return;
            }

            foreach (static::getClusteredComponents() as $component) {
                if (
                    is_subclass_of($component, Page::class)
                    && $component !== $overviewPage
                    && $component::canAccess()
                ) {
                    redirect($component::getUrl());

                    return;
                }
            }
        }

        parent::mount();
    }

    /**
     * @return class-string|null
     */
    protected static function resolveOverviewPageClass(): ?string
    {
        $module = str(class_basename(static::class))->beforeLast('Cluster')->toString();
        $pageClass = "App\\Filament\\Clusters\\{$module}\\OverviewPage";

        return class_exists($pageClass) ? $pageClass : null;
    }
}
