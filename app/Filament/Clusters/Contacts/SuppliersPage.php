<?php

namespace App\Filament\Clusters\Contacts;

use App\Filament\Clusters\ContactsCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class SuppliersPage extends BasePlaceholderPage
{
    protected static ?string $cluster = ContactsCluster::class;

    protected static ?string $navigationLabel = 'Suppliers';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'suppliers';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;
}
