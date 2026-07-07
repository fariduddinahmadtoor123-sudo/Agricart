<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\ContactsCluster;

class ContactsSubNavigation extends ClusterSubNavigation
{
    public static function isContactsPath(?string $path = null): bool
    {
        return static::isModulePath($path);
    }

    public static function clusterClass(): string
    {
        return ContactsCluster::class;
    }

    protected static function pathPrefix(): string
    {
        return 'admin/contacts';
    }
}
