<?php

namespace App\Filament\Clusters\FxLearn;

use App\Filament\Clusters\FxLearnCluster;
use App\Filament\Pages\BaseModulePage;
use App\Services\FxLearn\SessionLiquidityService;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class LiquiditySessionsPage extends BaseModulePage
{
    protected static ?string $cluster = FxLearnCluster::class;

    protected static ?string $navigationLabel = 'Liquidity & Sessions';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'liquidity-sessions';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected string $view = 'filament.pages.fx-learn.liquidity-sessions';

    /** @var array<string, mixed> */
    public array $snapshot = [];

    public function mount(SessionLiquidityService $sessions): void
    {
        $this->snapshot = $sessions->snapshot();
    }

    public function refreshSnapshot(SessionLiquidityService $sessions): void
    {
        $this->snapshot = $sessions->snapshot();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function sessionDefs(SessionLiquidityService $sessions): array
    {
        return $sessions->sessions();
    }

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
        return FxLearnCluster::unshiftClusterBreadcrumbs([
            static::getUrl() => static::getNavigationLabel(),
        ]);
    }
}
