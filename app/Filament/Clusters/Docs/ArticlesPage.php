<?php

namespace App\Filament\Clusters\Docs;

use App\Filament\Clusters\DocsCluster;
use App\Filament\Pages\BasePlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class ArticlesPage extends BasePlaceholderPage
{
    protected static ?string $cluster = DocsCluster::class;

    protected static ?string $navigationLabel = 'Articles';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'articles';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;
}
