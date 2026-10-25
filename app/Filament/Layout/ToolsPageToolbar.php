<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Tools\OverviewPage;
use Filament\Support\Icons\Heroicon;

class ToolsPageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return ToolsSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return OverviewPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'External Apps';
    }

    protected static function defaultResourceSlug(): string
    {
        return 'tools';
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

        $slug = (string) ($config['resourceSlug'] ?? '');

        if ($slug === 're-order') {
            $actions = [];

            if (static::userCan('create')) {
                $actions[] = [
                    'label' => __('agricart.pages.tools.re_order_new_order'),
                    'value' => 'new-order',
                    'color' => 'primary',
                    'outlined' => false,
                    'icon' => Heroicon::OutlinedPlus,
                ];
            }

            if (static::userCan('edit')) {
                $actions[] = [
                    'label' => __('agricart.pages.tools.re_order_settings'),
                    'value' => 'settings',
                    'outlined' => true,
                    'icon' => Heroicon::OutlinedCog6Tooth,
                ];
            }

            return [
                ...$config,
                'livewirePrefix' => 'tools-reorder',
                'interactive' => true,
                'hideSearch' => false,
                'hideFilters' => true,
                'hideColumns' => true,
                'addLabel' => null,
                'searchPlaceholder' => __('agricart.pages.tools.re_order_search'),
                'toolbarActions' => $actions,
            ];
        }

        return [
            ...$config,
            'addLabel' => null,
            'hideSearch' => true,
            'hideFilters' => true,
            'hideColumns' => true,
        ];
    }
}
