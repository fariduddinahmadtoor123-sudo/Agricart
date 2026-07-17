<?php

namespace App\Filament\Clusters\Finance;

use App\Filament\Clusters\FinanceCluster;
use App\Filament\Pages\BaseModulePage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class CashBookPage extends BaseModulePage
{
    protected static ?string $cluster = FinanceCluster::class;

    protected static ?string $navigationLabel = 'Cash Book';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'cash-book';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected string $view = 'filament.pages.finance.cash-book';

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return FinanceCluster::unshiftClusterBreadcrumbs([
            static::getUrl() => static::getNavigationLabel(),
        ]);
    }
}
