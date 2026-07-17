<?php

namespace App\Filament\Clusters\Docs;

use App\Filament\Clusters\DocsCluster;
use App\Filament\Pages\BaseModulePage;
use App\Support\AgricartTranslator;
use App\Support\Documentation\DocumentationRegistry;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class GuidePage extends BaseModulePage
{
    protected static ?string $cluster = DocsCluster::class;

    protected static ?string $navigationLabel = 'User Guide';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'guide';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected string $view = 'filament.pages.docs.guide';

    public static function guideUrl(string $moduleKey, string $pageKey, array $extra = []): string
    {
        return static::getUrl() . '?' . http_build_query(array_filter([
            'm' => $moduleKey,
            'p' => $pageKey,
            'mode' => $extra['mode'] ?? 'brief',
            'lang' => $extra['lang'] ?? 'ur',
            'kind' => $extra['kind'] ?? null,
        ]));
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
        $moduleKey = request()->string('m')->toString();
        $pageKey = request()->string('p')->toString();

        if ($moduleKey === '' || ! DocumentationRegistry::moduleExists($moduleKey)) {
            return DocsCluster::unshiftClusterBreadcrumbs([
                static::getUrl() => static::getNavigationLabel(),
            ]);
        }

        if ($pageKey === '' || ! DocumentationRegistry::pageExists($moduleKey, $pageKey)) {
            $pageKey = DocumentationRegistry::defaultPageKey($moduleKey);
        }

        $moduleLabel = AgricartTranslator::module($moduleKey);
        $pageLabel = DocumentationRegistry::pageLabel($moduleKey, $pageKey);

        return DocsCluster::unshiftClusterBreadcrumbs([
            static::guideUrl($moduleKey, DocumentationRegistry::defaultPageKey($moduleKey)) => $moduleLabel,
            static::guideUrl($moduleKey, $pageKey) => $pageLabel,
        ]);
    }
}
