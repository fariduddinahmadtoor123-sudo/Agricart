<?php

namespace App\Filament\Clusters\Reports;

use App\Filament\Clusters\ReportsCluster;
use App\Filament\Pages\BaseModulePage;
use App\Services\Reports\ReportCatalog;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class OverviewPage extends BaseModulePage
{
    protected static ?string $cluster = ReportsCluster::class;

    protected static ?string $navigationLabel = 'Overview';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'overview';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected string $view = 'filament.pages.reports.overview';

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    /**
     * @return list<array{label: string, url: string, description: string}>
     */
    public function reportLinks(): array
    {
        $links = [];

        foreach (ReportCatalog::reportPages() as $entry) {
            $pageClass = $entry['page'];

            if (! $pageClass::canAccess()) {
                continue;
            }

            $links[] = [
                'label' => $pageClass::getNavigationLabel(),
                'url' => $pageClass::getUrl(),
                'description' => __('reports.overview.descriptions.' . $entry['key']),
            ];
        }

        return $links;
    }

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return ReportsCluster::unshiftClusterBreadcrumbs([
            static::getUrl() => static::getNavigationLabel(),
        ]);
    }
}
