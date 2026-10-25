<?php

namespace App\Filament\Clusters\FxLearn;

use App\Filament\Clusters\FxLearnCluster;
use App\Filament\Pages\BaseModulePage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class MarketScreenerPage extends BaseModulePage
{
    protected static ?string $cluster = FxLearnCluster::class;

    protected static ?string $navigationLabel = 'Market Screener';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'market-screener';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlassCircle;

    protected string $view = 'filament.pages.fx-learn.market-screener';

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
     * @return array<string, mixed>
     */
    public function bootData(): array
    {
        return [
            'locale' => 'en',
            'screens' => [
                ['id' => 'general', 'label' => __('agricart.pages.fx-learn.scr_screen_general')],
                ['id' => 'top_gainers', 'label' => __('agricart.pages.fx-learn.scr_screen_gainers')],
                ['id' => 'top_losers', 'label' => __('agricart.pages.fx-learn.scr_screen_losers')],
                ['id' => 'most_volatile', 'label' => __('agricart.pages.fx-learn.scr_screen_volatile')],
                ['id' => 'overbought', 'label' => __('agricart.pages.fx-learn.scr_screen_overbought')],
                ['id' => 'oversold', 'label' => __('agricart.pages.fx-learn.scr_screen_oversold')],
            ],
            'columns' => [
                ['id' => 'overview', 'label' => __('agricart.pages.fx-learn.scr_col_overview')],
                ['id' => 'performance', 'label' => __('agricart.pages.fx-learn.scr_col_performance')],
                ['id' => 'oscillators', 'label' => __('agricart.pages.fx-learn.scr_col_oscillators')],
                ['id' => 'moving_averages', 'label' => __('agricart.pages.fx-learn.scr_col_ma')],
            ],
            'labels' => [
                'powered' => __('agricart.pages.fx-learn.sent_powered'),
                'screen' => __('agricart.pages.fx-learn.scr_screen'),
                'columns' => __('agricart.pages.fx-learn.scr_columns'),
            ],
        ];
    }
}
