<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Logistics\OverviewPage;
use App\Models\User;

class LogisticsPageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return LogisticsSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return OverviewPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'Logistics';
    }

    protected static function defaultResourceSlug(): string
    {
        return 'logistics';
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
                'addLabel' => null,
                'formKey' => null,
                'hideSearch' => true,
                'hideFilters' => true,
                'hideColumns' => true,
                'interactive' => false,
            ],
            'delivery-methods' => [
                ...$config,
                'addLabel' => static::userCanManage() ? __('agricart.toolbar.add', ['item' => __('agricart.pages.logistics.delivery-methods')]) : null,
                'formKey' => 'delivery-methods',
                'livewirePrefix' => 'delivery-methods',
                'columnDataAttr' => 'data-delivery-method-column',
                'interactive' => true,
                'searchPlaceholder' => __('agricart.delivery_methods.list.search'),
                'filters' => [
                    ['label' => __('agricart.form_panel.statuses.active'), 'value' => 'status:active'],
                    ['label' => __('agricart.form_panel.statuses.inactive'), 'value' => 'status:inactive'],
                ],
                'columns' => [
                    ['label' => __('agricart.delivery_methods.list.col_sort'), 'value' => 'sort_order', 'visible' => true],
                    ['label' => __('agricart.delivery_methods.list.col_name_en'), 'value' => 'name_en', 'visible' => true],
                    ['label' => __('agricart.delivery_methods.list.col_name_ur'), 'value' => 'name_ur', 'visible' => true],
                    ['label' => __('agricart.delivery_methods.list.col_status'), 'value' => 'status', 'visible' => true],
                    ['label' => __('agricart.delivery_methods.list.col_shipping_mode'), 'value' => 'shipping_charge_mode', 'visible' => true],
                    ['label' => __('agricart.delivery_methods.list.col_cod'), 'value' => 'cod_allowed', 'visible' => true],
                ],
            ],
            default => $config,
        };
    }

    protected static function userCanManage(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->isSuperAdmin() || $user->hasPermission('logistics.create'));
    }
}
