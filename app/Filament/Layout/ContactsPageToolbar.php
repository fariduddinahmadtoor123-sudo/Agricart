<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Contacts\OverviewPage;

class ContactsPageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return ContactsSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return OverviewPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'Contacts';
    }

    protected static function defaultResourceSlug(): string
    {
        return 'contacts';
    }
}
