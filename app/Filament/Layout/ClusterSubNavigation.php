<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\BaseAgricartCluster;
use App\Filament\Pages\BasePlaceholderPage;
use Filament\Pages\Page;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

abstract class ClusterSubNavigation
{
    /**
     * @return class-string<\Filament\Clusters\Cluster>
     */
    abstract public static function clusterClass(): string;

    public static function moduleKey(): string
    {
        $cluster = static::clusterClass();

        if (is_subclass_of($cluster, BaseAgricartCluster::class)) {
            return $cluster::moduleSlug();
        }

        return '';
    }

    protected static function pathPrefix(): string
    {
        $slug = static::clusterClass()::getSlug() ?? static::moduleKey();

        return 'admin/'.$slug;
    }

    public static function isModulePath(?string $path = null): bool
    {
        $path ??= request()->path();

        return str_starts_with($path, static::pathPrefix());
    }

    /**
     * Chrome page labels for a module, without gating on the current user's permissions.
     * Used by the Access / roles form so new pages appear under their module.
     *
     * @return list<string>
     */
    public static function chromePageLabels(): array
    {
        $clusterClass = static::clusterClass();
        $comingSoon = is_subclass_of($clusterClass, BaseAgricartCluster::class)
            && $clusterClass::isComingSoon();
        $labels = [];

        foreach ($clusterClass::getClusteredComponents() as $component) {
            if (! is_subclass_of($component, Page::class)) {
                continue;
            }

            if (
                method_exists($component, 'registersInModuleChrome')
                && ! $component::registersInModuleChrome()
            ) {
                continue;
            }

            if ($comingSoon && ! str_ends_with(class_basename($component), 'OverviewPage')) {
                continue;
            }

            $label = trim((string) $component::getNavigationLabel());

            if ($label === '') {
                continue;
            }

            $labels[] = $label;
        }

        return array_values(array_unique($labels));
    }

    /**
     * @return list<array{label: string, url: string, icon: string|\BackedEnum|\Illuminate\Contracts\Support\Htmlable|null, active: bool}>
     */
    public static function items(): array
    {
        if (! static::isModulePath()) {
            return [];
        }

        $cacheKey = 'agricart.subnav.items.'.static::class;
        $cached = request()->attributes->get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        $items = [];

        foreach (static::clusterClass()::getClusteredComponents() as $component) {
            if (! is_subclass_of($component, Page::class) || ! $component::canAccess()) {
                continue;
            }

            // Agricart chrome only — never gate on Filament shouldRegisterNavigation().
            if (
                method_exists($component, 'registersInModuleChrome')
                && ! $component::registersInModuleChrome()
            ) {
                continue;
            }

            $clusterClass = static::clusterClass();

            if (
                is_subclass_of($clusterClass, BaseAgricartCluster::class)
                && $clusterClass::isComingSoon()
                && ! str_ends_with(class_basename($component), 'OverviewPage')
            ) {
                continue;
            }

            $isPlaceholder = is_subclass_of($component, BasePlaceholderPage::class);

            try {
                $url = $component::getUrl();
            } catch (RouteNotFoundException) {
                continue;
            }

            $items[] = [
                'label' => $component::getNavigationLabel(),
                'url' => $url,
                'icon' => $component::getNavigationIcon(),
                'active' => static::isActivePage($component),
                'sort' => $component::getNavigationSort() ?? 0,
                'comingSoon' => $isPlaceholder,
                'pageClass' => $component,
            ];
        }

        usort($items, fn (array $a, array $b): int => $a['sort'] <=> $b['sort']);

        $comingSoonBadge = __('agricart.placeholder.sub_nav_badge');
        $notifications = app(\App\Services\Notifications\ModuleNotificationService::class);

        $result = array_map(
            fn (array $item): array => [
                'label' => $item['label'],
                'url' => $item['url'],
                'icon' => $item['icon'],
                'active' => $item['active'],
                'comingSoon' => $item['comingSoon'] ?? false,
                'comingSoonBadge' => ($item['comingSoon'] ?? false) ? $comingSoonBadge : null,
                'badgeCount' => $notifications->countForPage($item['pageClass']),
            ],
            $items,
        );

        request()->attributes->set($cacheKey, $result);

        return $result;
    }

    /**
     * @return class-string<Page>|null
     */
    public static function resolveActivePageClass(): ?string
    {
        $cacheKey = 'agricart.subnav.active.'.static::class;
        $cached = request()->attributes->get($cacheKey);

        if (is_string($cached) || $cached === false) {
            return $cached === false ? null : $cached;
        }

        foreach (static::clusterClass()::getClusteredComponents() as $component) {
            if (is_subclass_of($component, Page::class) && static::isActivePage($component)) {
                request()->attributes->set($cacheKey, $component);

                return $component;
            }
        }

        request()->attributes->set($cacheKey, false);

        return null;
    }

    /**
     * @param  class-string<Page>  $pageClass
     */
    public static function isActivePage(string $pageClass): bool
    {
        $currentPath = '/' . trim(request()->path(), '/');
        $pagePath = parse_url($pageClass::getUrl(), PHP_URL_PATH) ?? '';

        if (! is_string($pagePath) || $pagePath === '') {
            return false;
        }

        $pagePath = '/' . trim($pagePath, '/');

        return $currentPath === $pagePath;
    }
}
