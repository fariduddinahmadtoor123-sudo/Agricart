<?php

namespace App\Filament\Clusters\FxLearn;

use App\Filament\Clusters\FxLearnCluster;
use App\Filament\Pages\BaseModulePage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class PropChallengeRulesPage extends BaseModulePage
{
    protected static ?string $cluster = FxLearnCluster::class;

    protected static ?string $navigationLabel = 'Prop Challenge & Rules';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'prop-challenge-rules';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected string $view = 'filament.pages.fx-learn.prop-challenge-rules';

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

    /**
     * Default challenge profile from the firm rules you described.
     *
     * @return array<string, mixed>
     */
    public function bootData(): array
    {
        return [
            'defaults' => [
                'balance' => 100000,
                'phase1' => 8,
                'phase2' => 6,
                'phase3' => 3,
                'maxDayLoss' => 5,
                'maxTotalLoss' => 10,
                'maxTradeLoss' => 3,
            ],
            'labels' => [
                'balance' => __('agricart.pages.fx-learn.prop_balance'),
                'phase1' => __('agricart.pages.fx-learn.prop_phase1'),
                'phase2' => __('agricart.pages.fx-learn.prop_phase2'),
                'phase3' => __('agricart.pages.fx-learn.prop_phase3'),
                'maxDay' => __('agricart.pages.fx-learn.prop_max_day'),
                'maxTotal' => __('agricart.pages.fx-learn.prop_max_total'),
                'maxTrade' => __('agricart.pages.fx-learn.prop_max_trade'),
                'reset' => __('agricart.pages.fx-learn.prop_reset'),
                'currentEquity' => __('agricart.pages.fx-learn.prop_current_equity'),
                'activePhase' => __('agricart.pages.fx-learn.prop_active_phase'),
            ],
        ];
    }
}
