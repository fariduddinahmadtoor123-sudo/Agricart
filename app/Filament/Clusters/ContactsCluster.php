<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

class ContactsCluster extends BaseAgricartCluster
{
    protected static ?string $navigationLabel = 'Contacts';

    protected static ?string $clusterBreadcrumb = 'Contacts';

    protected static ?string $slug = 'contacts';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;
}
