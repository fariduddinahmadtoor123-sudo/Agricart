<?php

namespace App\Filament\Layout;

class ModuleChrome
{
    /**
     * @return list<array{
     *     key: string,
     *     path: string,
     *     subNavigation: class-string,
     *     toolbar: class-string,
     *     navLabel: string,
     * }>
     */
    public static function modules(): array
    {
        $comingSoon = config('agricart.modules_coming_soon', []);

        $modules = [
            [
                'key' => 'dashboard',
                'path' => 'admin/dashboard',
                'subNavigation' => DashboardSubNavigation::class,
                'toolbar' => DashboardPageToolbar::class,
                'navLabel' => 'Dashboard',
            ],
            [
                'key' => 'approvals',
                'path' => 'admin/approvals',
                'subNavigation' => ApprovalsSubNavigation::class,
                'toolbar' => ApprovalsPageToolbar::class,
                'navLabel' => 'Approvals',
            ],
            [
                'key' => 'contacts',
                'path' => 'admin/contacts',
                'subNavigation' => ContactsSubNavigation::class,
                'toolbar' => ContactsPageToolbar::class,
                'navLabel' => 'Contacts',
            ],
            [
                'key' => 'catalog',
                'path' => 'admin/catalog',
                'subNavigation' => CatalogSubNavigation::class,
                'toolbar' => CatalogPageToolbar::class,
                'navLabel' => 'Catalog',
            ],
            [
                'key' => 'purchasing',
                'path' => 'admin/purchasing',
                'subNavigation' => PurchasingSubNavigation::class,
                'toolbar' => PurchasingPageToolbar::class,
                'navLabel' => 'Purchasing',
            ],
            [
                'key' => 'sales',
                'path' => 'admin/sales',
                'subNavigation' => SalesSubNavigation::class,
                'toolbar' => SalesPageToolbar::class,
                'navLabel' => 'Sales',
            ],
            [
                'key' => 'store',
                'path' => 'admin/store',
                'subNavigation' => StoreSubNavigation::class,
                'toolbar' => StorePageToolbar::class,
                'navLabel' => 'Store',
            ],
            [
                'key' => 'logistics',
                'path' => 'admin/logistics',
                'subNavigation' => LogisticsSubNavigation::class,
                'toolbar' => LogisticsPageToolbar::class,
                'navLabel' => 'Logistics',
            ],
            [
                'key' => 'service',
                'path' => 'admin/service',
                'subNavigation' => ServiceSubNavigation::class,
                'toolbar' => ServicePageToolbar::class,
                'navLabel' => 'Service',
            ],
            [
                'key' => 'reports',
                'path' => 'admin/reports',
                'subNavigation' => ReportsSubNavigation::class,
                'toolbar' => ReportsPageToolbar::class,
                'navLabel' => 'Reports',
            ],
            [
                'key' => 'staff',
                'path' => 'admin/staff',
                'subNavigation' => StaffSubNavigation::class,
                'toolbar' => StaffPageToolbar::class,
                'navLabel' => 'Staff',
            ],
            [
                'key' => 'finance',
                'path' => 'admin/finance',
                'subNavigation' => FinanceSubNavigation::class,
                'toolbar' => FinancePageToolbar::class,
                'navLabel' => 'Finance',
            ],
            [
                'key' => 'marketplace',
                'path' => 'admin/marketplace',
                'subNavigation' => MarketplaceSubNavigation::class,
                'toolbar' => MarketplacePageToolbar::class,
                'navLabel' => 'Marketplace',
            ],
            [
                'key' => 'settings',
                'path' => 'admin/settings',
                'subNavigation' => SettingsSubNavigation::class,
                'toolbar' => SettingsPageToolbar::class,
                'navLabel' => 'Settings',
            ],
            [
                'key' => 'docs',
                'path' => 'admin/docs',
                'subNavigation' => DocsSubNavigation::class,
                'toolbar' => DocsPageToolbar::class,
                'navLabel' => 'Docs',
            ],
            [
                'key' => 'tools',
                'path' => 'admin/tools',
                'subNavigation' => ToolsSubNavigation::class,
                'toolbar' => ToolsPageToolbar::class,
                'navLabel' => 'External Apps',
            ],
        ];

        return array_map(
            static function (array $module) use ($comingSoon): array {
                $module['comingSoon'] = in_array($module['key'], $comingSoon, true);

                return $module;
            },
            $modules,
        );
    }

    /**
     * @return array{
     *     key: string,
     *     path: string,
     *     subNavigation: class-string,
     *     toolbar: class-string,
     *     navLabel: string,
     * }|null
     */
    public static function active(): ?array
    {
        $path = request()->path();

        foreach (static::modules() as $module) {
            if (str_starts_with($path, $module['path'])) {
                return $module;
            }
        }

        return null;
    }

    public static function isModulePath(?string $path = null): bool
    {
        $path ??= request()->path();

        foreach (static::modules() as $module) {
            if (str_starts_with($path, $module['path'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public static function pathPrefixes(): array
    {
        return array_column(static::modules(), 'path');
    }
}
