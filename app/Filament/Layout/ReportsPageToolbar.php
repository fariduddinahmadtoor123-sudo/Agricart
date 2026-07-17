<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Reports\OverviewPage;
use App\Services\Reports\ReportCatalog;
use Filament\Support\Icons\Heroicon;

class ReportsPageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return ReportsSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return OverviewPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'Reports';
    }

    protected static function defaultResourceSlug(): string
    {
        return 'reports';
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function config(): ?array
    {
        $config = parent::config();

        if ($config === null) {
            return null;
        }

        $slug = $config['resourceSlug'] ?? 'reports';

        if ($slug === 'overview') {
            return [
                ...$config,
                'hideSearch' => true,
                'hideFilters' => true,
                'hideColumns' => true,
            ];
        }

        $reportKey = $slug;
        $definition = ReportCatalog::definition($reportKey);

        if ($definition === null) {
            return $config;
        }

        $prefix = ReportCatalog::eventPrefix($reportKey);

        $toolbarActions = static::userCan('view') ? [
            [
                'label' => __('reports.actions.view_report'),
                'value' => 'view_report',
                'color' => 'primary',
                'outlined' => false,
                'icon' => Heroicon::OutlinedDocumentChartBar,
                'compact' => false,
            ],
        ] : [];

        if ($reportKey === 'stock-report' && static::userCan('view')) {
            $toolbarActions[] = [
                'label' => __('reports.actions.export'),
                'value' => 'export_csv',
                'color' => 'gray',
                'outlined' => true,
                'icon' => Heroicon::OutlinedArrowDownTray,
                'compact' => false,
            ];
        }

        return [
            ...$config,
            'livewirePrefix' => $prefix,
            'livewireComponent' => 'reports.report-shell',
            'columnDataAttr' => 'data-report-'.$reportKey.'-column',
            'interactive' => true,
            'filters' => $definition['filters'],
            'filterGroups' => $definition['filterGroups'] ?? [],
            'columns' => $definition['columns'],
            'toolbarActions' => $toolbarActions,
        ];
    }
}
