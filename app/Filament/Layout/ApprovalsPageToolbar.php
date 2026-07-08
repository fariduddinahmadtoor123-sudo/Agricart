<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Approvals\OverviewPage;

class ApprovalsPageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return ApprovalsSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return OverviewPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'Approvals';
    }

    protected static function defaultResourceSlug(): string
    {
        return 'approvals';
    }

    public static function config(): ?array
    {
        $config = parent::config();

        if ($config === null) {
            return null;
        }

        return match ($config['resourceSlug']) {
            'overview' => [
                ...$config,
                'livewirePrefix' => 'approvals-overview',
                'interactive' => false,
            ],
            'staff' => [
                ...$config,
                'addLabel' => null,
                'formKey' => null,
                'livewirePrefix' => 'staff-approvals',
                'interactive' => true,
                'filters' => [
                    ['label' => __('agricart.toolbar.filter_pending'), 'value' => 'status:pending'],
                    ['label' => __('agricart.toolbar.filter_approved'), 'value' => 'status:active'],
                    ['label' => __('agricart.toolbar.filter_rejected'), 'value' => 'status:rejected'],
                ],
                'columns' => [],
            ],
            default => $config,
        };
    }
}
