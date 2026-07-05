<?php

namespace App\Filament\Clusters\Marketplace;

use App\Filament\Clusters\MarketplaceCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class ChannelConnectionsPage extends BasePlaceholderPage
{
    protected static ?string $cluster = MarketplaceCluster::class;

    protected static ?string $navigationLabel = 'Channels';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'channel-connections';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;
}
