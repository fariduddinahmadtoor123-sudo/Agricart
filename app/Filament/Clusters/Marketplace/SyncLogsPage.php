<?php

namespace App\Filament\Clusters\Marketplace;

use App\Filament\Clusters\MarketplaceCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class SyncLogsPage extends BasePlaceholderPage
{
    protected static ?string $cluster = MarketplaceCluster::class;

    protected static ?string $navigationLabel = 'Logs';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'sync-logs';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;
}
