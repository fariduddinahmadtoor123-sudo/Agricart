<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Vault\MyBoxPage;

class VaultPageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return VaultSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return MyBoxPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'Personal Vault';
    }

    protected static function defaultResourceSlug(): string
    {
        return 'vault';
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

        return [
            ...$config,
            'addLabel' => null,
            'hideSearch' => true,
            'hideFilters' => true,
            'hideColumns' => true,
        ];
    }
}
