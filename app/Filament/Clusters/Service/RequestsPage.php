<?php

namespace App\Filament\Clusters\Service;

use App\Filament\Clusters\ServiceCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class RequestsPage extends BasePlaceholderPage
{
    protected static ?string $cluster = ServiceCluster::class;

    protected static ?string $navigationLabel = 'Requests';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'requests';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;
}
