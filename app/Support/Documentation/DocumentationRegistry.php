<?php

namespace App\Support\Documentation;

use App\Filament\Clusters\BaseAgricartCluster;
use App\Filament\Layout\ModuleChrome;
use App\Filament\Pages\BaseModulePage;
use App\Filament\Pages\BasePlaceholderPage;
use App\Support\AgricartTranslator;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Lang;

class DocumentationRegistry
{
    /**
     * @return list<array{
     *     key: string,
     *     label: string,
     *     comingSoon: bool,
     * }>
     */
    public static function modules(): array
    {
        $modules = [];

        foreach (ModuleChrome::modules() as $module) {
            if ($module['key'] === 'docs') {
                continue;
            }

            $modules[] = [
                'key' => $module['key'],
                'label' => AgricartTranslator::module($module['key']),
                'comingSoon' => (bool) ($module['comingSoon'] ?? false),
            ];
        }

        return $modules;
    }

    /**
     * @return list<array{
     *     key: string,
     *     label: string,
     *     implemented: bool,
     *     documented: bool,
     *     sort: int,
     * }>
     */
    public static function pagesForModule(string $moduleKey): array
    {
        $module = collect(ModuleChrome::modules())->firstWhere('key', $moduleKey);

        if ($module === null) {
            return [];
        }

        /** @var class-string<\App\Filament\Layout\ClusterSubNavigation> $subNavigationClass */
        $subNavigationClass = $module['subNavigation'];
        $clusterClass = $subNavigationClass::clusterClass();
        $comingSoon = (bool) ($module['comingSoon'] ?? false);

        $pages = [];

        foreach ($clusterClass::getClusteredComponents() as $component) {
            // Docs TOC lists all module pages (not filtered by the reader's module permissions).
            if (! is_subclass_of($component, Page::class)) {
                continue;
            }

            if (
                is_subclass_of($clusterClass, BaseAgricartCluster::class)
                && $comingSoon
                && ! str_ends_with(class_basename($component), 'OverviewPage')
            ) {
                continue;
            }

            $slug = $component::getSlug() ?? 'overview';

            $pages[] = [
                'key' => $slug,
                'label' => $component::getNavigationLabel(),
                'implemented' => static::pageIsImplemented($component),
                'documented' => static::hasArticle($moduleKey, $slug, 'en') || static::hasArticle($moduleKey, $slug, 'ur'),
                'sort' => $component::getNavigationSort() ?? 0,
            ];
        }

        usort($pages, fn (array $a, array $b): int => $a['sort'] <=> $b['sort']);

        return $pages;
    }

    protected static function translationPrefix(string $moduleKey): string
    {
        return 'docs/modules/' . $moduleKey;
    }

    public static function hasArticle(string $moduleKey, string $pageKey, ?string $locale = null): bool
    {
        return Lang::has(static::translationPrefix($moduleKey) . '.' . $pageKey . '.sections', $locale);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function article(string $moduleKey, string $pageKey, ?string $locale = null): ?array
    {
        $key = static::translationPrefix($moduleKey) . '.' . $pageKey;

        if (! Lang::has($key, $locale)) {
            return null;
        }

        $data = __($key, [], $locale);

        return is_array($data) ? $data : null;
    }

    public static function pageLabel(string $moduleKey, string $pageKey): string
    {
        foreach (static::pagesForModule($moduleKey) as $page) {
            if ($page['key'] === $pageKey) {
                return $page['label'];
            }
        }

        return str($pageKey)->headline()->toString();
    }

    public static function moduleExists(string $moduleKey): bool
    {
        return collect(static::modules())->contains(fn (array $module): bool => $module['key'] === $moduleKey);
    }

    public static function pageExists(string $moduleKey, string $pageKey): bool
    {
        return collect(static::pagesForModule($moduleKey))->contains(fn (array $page): bool => $page['key'] === $pageKey);
    }

    public static function defaultModuleKey(): string
    {
        return static::modules()[0]['key'] ?? 'dashboard';
    }

    public static function defaultPageKey(string $moduleKey): string
    {
        $pages = static::pagesForModule($moduleKey);

        return $pages[0]['key'] ?? 'overview';
    }

    /**
     * @param  class-string<Page>  $pageClass
     */
    protected static function pageIsImplemented(string $pageClass): bool
    {
        if (is_subclass_of($pageClass, BasePlaceholderPage::class)) {
            return false;
        }

        return is_subclass_of($pageClass, BaseModulePage::class);
    }
}
