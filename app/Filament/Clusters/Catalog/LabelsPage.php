<?php

namespace App\Filament\Clusters\Catalog;

use App\Filament\Clusters\CatalogCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class LabelsPage extends BasePlaceholderPage
{
    protected static ?string $cluster = CatalogCluster::class;

    protected static ?string $navigationLabel = 'Labels';

    protected static ?int $navigationSort = 9;

    protected static ?string $slug = 'labels';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;
}
