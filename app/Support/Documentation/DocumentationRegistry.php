<?php

namespace App\Support\Documentation;

use App\Filament\Clusters\BaseAgricartCluster;
use App\Filament\Layout\ClusterSubNavigation;
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
            $modules[] = [
                'key' => $module['key'],
                'label' => AgricartTranslator::module($module['key']),
                'comingSoon' => (bool) ($module['comingSoon'] ?? false),
            ];
        }

        return $modules;
    }

    /**
     * Modules the current user is allowed to read documentation for.
     * A reader only sees docs for modules they can access ("{module}.view"),
     * with Super Admin seeing everything. Outside an authenticated web
     * request (CLI/tests) the full list is returned unchanged.
     *
     * @return list<array{key: string, label: string, comingSoon: bool}>
     */
    public static function accessibleModules(): array
    {
        $user = auth()->user();

        if (! $user instanceof \App\Models\User) {
            return static::modules();
        }

        if ($user->isSuperAdmin()) {
            return static::modules();
        }

        return array_values(array_filter(
            static::modules(),
            static fn (array $module): bool => static::userCanReadModule($module['key']),
        ));
    }

    public static function userCanReadModule(string $moduleKey): bool
    {
        $user = auth()->user();

        if (! $user instanceof \App\Models\User) {
            return true;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->canUseAdmin()) {
            return false;
        }

        return $user->hasPermission($moduleKey.'.view');
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

        /** @var class-string<ClusterSubNavigation> $subNavigationClass */
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
                'has_brief' => static::pageHasAnyBrief($moduleKey, $slug),
                'sort' => $component::getNavigationSort() ?? 0,
            ];
        }

        usort($pages, fn (array $a, array $b): int => $a['sort'] <=> $b['sort']);

        return $pages;
    }

    protected static function translationPrefix(string $moduleKey): string
    {
        return 'docs/modules/'.$moduleKey;
    }

    public static function hasArticle(string $moduleKey, string $pageKey, ?string $locale = null): bool
    {
        return Lang::has(static::translationPrefix($moduleKey).'.'.$pageKey.'.sections', $locale);
    }

    protected static function briefPrefix(string $moduleKey): string
    {
        return 'docs/briefs/'.$moduleKey;
    }

    /**
     * Brief kinds for a Filament page: typically list + form, or a single page brief.
     *
     * @return list<string>
     */
    public static function briefKinds(string $moduleKey, string $pageKey, ?string $locale = null): array
    {
        $kinds = [];

        foreach (['list', 'form', 'page'] as $kind) {
            if (
                static::hasBrief($moduleKey, $pageKey, $kind, $locale)
                || static::hasBrief($moduleKey, $pageKey, $kind, 'ur')
                || static::hasBrief($moduleKey, $pageKey, $kind, 'en')
            ) {
                $kinds[] = $kind;
            }
        }

        if ($kinds === [] && (
            static::hasArticle($moduleKey, $pageKey, $locale)
            || static::hasArticle($moduleKey, $pageKey, 'ur')
            || static::hasArticle($moduleKey, $pageKey, 'en')
        )) {
            $kinds[] = 'page';
        }

        return $kinds;
    }

    public static function hasBrief(string $moduleKey, string $pageKey, string $kind = 'page', ?string $locale = null): bool
    {
        return Lang::has(static::briefPrefix($moduleKey).'.'.$pageKey.'.'.$kind.'.prompt', $locale);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function brief(string $moduleKey, string $pageKey, string $kind = 'page', ?string $locale = null): ?array
    {
        $key = static::briefPrefix($moduleKey).'.'.$pageKey.'.'.$kind;
        $locales = array_values(array_unique(array_filter([$locale, 'ur', 'en'])));

        foreach ($locales as $try) {
            $hand = null;

            if (Lang::has($key, $try)) {
                $data = __($key, [], $try);

                if (is_array($data)) {
                    $hand = $data;
                }
            }

            $guide = static::article($moduleKey, $pageKey, $try);
            $base = is_array($guide)
                ? DocumentationBriefBuilder::fromGuide($moduleKey, $pageKey, $guide, $try)
                : null;

            if ($hand !== null) {
                return $base !== null
                    ? static::ensureSkeleton(static::mergeBrief($base, $hand), $try)
                    : static::ensureSkeleton($hand, $try);
            }

            if ($kind === 'page' && $base !== null) {
                return static::ensureSkeleton($base, $try);
            }
        }

        return null;
    }

    /**
     * Overlay a hand-written brief on top of the guide-derived skeleton so every
     * page shares the same handbook sections. Hand-authored keys always win.
     *
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $hand
     * @return array<string, mixed>
     */
    protected static function mergeBrief(array $base, array $hand): array
    {
        foreach ($hand as $key => $value) {
            if (is_array($value) && $value === [] && ($base[$key] ?? null)) {
                // Hand brief left the section empty; keep the guide-derived content.
                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }

    /**
     * Guarantee the full handbook section skeleton exists on a hand brief that
     * has no backing guide (placeholders where the team has not filled content).
     *
     * @param  array<string, mixed>  $hand
     * @return array<string, mixed>
     */
    protected static function ensureSkeleton(array $hand, string $locale): array
    {
        $defaults = [
            'overview' => [],
            'notes' => [],
            'prompt' => '',
            'ui_flow' => [],
            'functional_requirements' => [],
            'fields' => [],
            'validation' => [],
            'database' => [],
            'api' => [],
            'permissions' => [],
            'edge_cases' => [],
            'business_rules' => [],
            'non_functional_requirements' => [],
            'dependencies' => [],
            'technical' => [],
            'improvements' => [],
        ];

        foreach ($defaults as $key => $value) {
            if (! array_key_exists($key, $hand)) {
                $hand[$key] = $value;
            }
        }

        if (! array_key_exists('article', $hand) && ($hand['overview'] ?? []) !== []) {
            $hand['article'] = $hand['overview'];
        }

        return $hand;
    }

    public static function pageHasAnyBrief(string $moduleKey, string $pageKey): bool
    {
        return static::briefKinds($moduleKey, $pageKey, 'ur') !== []
            || static::briefKinds($moduleKey, $pageKey, 'en') !== [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function article(string $moduleKey, string $pageKey, ?string $locale = null): ?array
    {
        $key = static::translationPrefix($moduleKey).'.'.$pageKey;

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
