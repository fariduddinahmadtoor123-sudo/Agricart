<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Dashboard\OverviewPage;

class DashboardPageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return DashboardSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return OverviewPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'Dashboard';
    }

    protected static function defaultResourceSlug(): string
    {
        return 'dashboard';
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

        return match ($config['resourceSlug']) {
            'overview' => [
                ...$config,
                'livewirePrefix' => 'dashboard-overview',
                'interactive' => false,
            ],
            'summary' => [
                ...$config,
                'livewirePrefix' => 'dashboard-summary',
                'interactive' => false,
            ],
            'notes' => [
                ...$config,
                'livewirePrefix' => 'staff-notes',
                'interactive' => true,
                'formKey' => null,
                'addLabel' => __('staff_notes.actions.add_note'),
                'addEvent' => 'staff-notes-open-form',
                'compactAddButton' => false,
                'hideColumns' => true,
                'searchPlaceholder' => __('staff_notes.list.search'),
                'filterGroups' => [
                    [
                        'label' => __('staff_notes.filters.label'),
                        'filters' => [
                            ['label' => __('staff_notes.filters.open'), 'value' => 'status:open'],
                            ['label' => __('staff_notes.filters.mine'), 'value' => 'status:mine'],
                            ['label' => __('staff_notes.filters.created'), 'value' => 'status:created'],
                            ['label' => __('staff_notes.filters.done'), 'value' => 'status:done'],
                            ['label' => __('staff_notes.filters.all'), 'value' => 'status:all'],
                        ],
                    ],
                ],
                'filters' => [],
                'columns' => [],
            ],
            default => $config,
        };
    }
}
