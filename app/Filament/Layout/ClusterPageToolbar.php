<?php

namespace App\Filament\Layout;

use App\Models\User;
use Filament\Clusters\Cluster;
use Filament\Pages\Page;

abstract class ClusterPageToolbar
{
    /**
     * @return class-string<Cluster>
     */
    abstract protected static function subNavigationClass(): string;

    /**
     * @return class-string<Page>
     */
    abstract protected static function overviewPageClass(): string;

    abstract protected static function defaultResourceLabel(): string;

    abstract protected static function defaultResourceSlug(): string;

    /**
     * @return array{
     *     addLabel: string|null,
     *     searchPlaceholder: string,
     *     resourceLabel: string,
     *     resourceSlug: string,
     *     formKey: string|null,
     *     filters: list<array{label: string, value: string}>,
     *     columns: list<array{label: string, value: string, visible: bool}>,
     * }|null
     */
    public static function config(): ?array
    {
        $subNavigationClass = static::subNavigationClass();

        if (! $subNavigationClass::isModulePath()) {
            return null;
        }

        $pageClass = $subNavigationClass::resolveActivePageClass();
        $resourceLabel = $pageClass ? $pageClass::getNavigationLabel() : static::defaultResourceLabel();
        $resourceSlug = $pageClass ? ($pageClass::getSlug() ?? static::defaultResourceSlug()) : static::defaultResourceSlug();
        $isOverview = $pageClass === static::overviewPageClass() || $pageClass === null;
        $canCreate = static::userCan('create');

        $filters = static::filtersFor($resourceLabel);
        $columns = static::columnsFor($resourceLabel);

        return [
            'addLabel' => (! $isOverview && $pageClass && $canCreate)
                ? __('agricart.toolbar.add', ['item' => $resourceLabel])
                : null,
            'searchPlaceholder' => $isOverview
                ? __('agricart.toolbar.search')
                : __('agricart.toolbar.search_resource', ['resource' => mb_strtolower($resourceLabel)]),
            'resourceLabel' => $resourceLabel,
            'resourceSlug' => $resourceSlug,
            'formKey' => static::formKeyFor($resourceSlug),
            'filters' => $filters,
            'columns' => $columns,
            'hideFilters' => $filters === [],
            'hideColumns' => $columns === [],
        ];
    }

    protected static function moduleKey(): string
    {
        return static::subNavigationClass()::moduleKey();
    }

    protected static function userCan(string $action): bool
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->canUseAdmin()) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        $moduleKey = static::moduleKey();

        if ($moduleKey === '') {
            return false;
        }

        if (\App\Services\Access\PermissionRegistry::moduleUsesPagePermissions($moduleKey)) {
            $pageClass = static::subNavigationClass()::resolveActivePageClass();
            $pageSlug = $pageClass ? (string) ($pageClass::getSlug() ?? '') : '';

            if ($pageSlug !== '') {
                return \App\Services\Access\ModulePageAccess::can($user, $moduleKey, $pageSlug, $action);
            }
        }

        return $user->hasPermission("{$moduleKey}.{$action}");
    }

    protected static function formKeyFor(string $resourceSlug): ?string
    {
        return match ($resourceSlug) {
            'ai-settings' => 'ai-settings',
            'categories' => 'categories',
            'brands' => 'brands',
            'packages' => 'packages',
            'units' => 'units',
            'packing-units' => 'packing-units',
            'attributes' => 'attributes',
            'controls' => 'controls',
            'products' => 'products',
            'product-reviews' => 'product-reviews',
            'users' => 'users',
            'staff' => 'staff',
            'pay-management' => 'staff-salary',
            'attendance' => 'staff-attendance',
            'leave-management' => 'staff-leave',
            'payroll' => 'staff-payroll',
            'advances' => 'staff-advance',
            'roles' => 'roles',
            'suppliers' => 'suppliers',
            'message-templates' => 'contact-message-templates',
            'purchase-planning' => 'purchase-planning',
            'purchase-payment-summary' => 'purchase-payment-summary',
            'purchase-quotations' => 'purchase-quotations',
            'purchases' => 'purchases',
            'price-update' => 'price-update',
            'pos-sales' => 'sales',
            'sales-quotations' => 'sale-quotations',
            'sales-returns' => 'sale-returns',
            'online-quotations' => 'online-orders',
            'online-orders' => 'online-orders',
            'order-dispatch' => 'online-orders',
            'forwarded-orders' => 'online-orders',
            'shipments' => 'logistics-ship',
            'shipped' => 'logistics-ship',
            'delivery-methods' => 'delivery-methods',
            're-order-center' => 're-order-center',
            'price-control' => 'price-controls',
            'quantity-control' => 'quantity-controls',
            'secret-coding' => 'secret-coding',
            'sales-channels' => 'sales-channel',
            default => null,
        };
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    public static function filtersFor(string $resourceLabel): array
    {
        return [];
    }

    /**
     * @return list<array{label: string, value: string, visible: bool}>
     */
    public static function columnsFor(string $resourceLabel): array
    {
        return [];
    }
}
