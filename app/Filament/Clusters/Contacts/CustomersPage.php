<?php

namespace App\Filament\Clusters\Contacts;

use App\Filament\Clusters\ContactsCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class CustomersPage extends BasePlaceholderPage
{
    protected static ?string $cluster = ContactsCluster::class;

    protected static ?string $navigationLabel = 'Customers';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'customers';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;
}
