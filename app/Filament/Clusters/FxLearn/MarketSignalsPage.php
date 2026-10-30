<?php

namespace App\Filament\Clusters\FxLearn;

use App\Filament\Clusters\FxLearnCluster;
use App\Filament\Pages\BaseModulePage;
use App\Services\FxLearn\MarketSignalCatalog;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\On;

class MarketSignalsPage extends BaseModulePage
{
    protected static ?string $cluster = FxLearnCluster::class;

    protected static ?string $navigationLabel = 'Market Signals';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'market-signals';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected string $view = 'filament.pages.fx-learn.market-signals';

    public string $category = 'forex';

    public string $assetId = 'eurusd';

    public string $intervalId = '1h';

    public string $search = '';

    public function mount(): void
    {
        $this->ensureSelection();
    }

    #[On('fx-learn-signals-search')]
    public function onToolbarSearch(mixed $query = null): void
    {
        $value = is_array($query) ? (string) ($query['query'] ?? '') : (string) $query;
        $this->search = trim($value);

        if ($this->search === '') {
            return;
        }

        $this->selectFirstSearchMatch();
    }

    #[On('fx-learn-signals-category')]
    public function onToolbarCategory(mixed $category = null): void
    {
        $value = is_array($category) ? (string) ($category['category'] ?? '') : (string) $category;
        $this->setCategory($value);
    }

    #[On('fx-learn-signals-interval')]
    public function onToolbarInterval(mixed $interval = null): void
    {
        $value = is_array($interval) ? (string) ($interval['interval'] ?? '') : (string) $interval;
        $this->setInterval($value);
    }

    public function setCategory(string $category): void
    {
        $valid = collect(MarketSignalCatalog::categories())->pluck('id')->all();

        if (! in_array($category, $valid, true)) {
            return;
        }

        $this->category = $category;
        $first = MarketSignalCatalog::assetsFor($category)[0] ?? null;
        $this->assetId = (string) ($first['id'] ?? '');
        $this->ensureSelection();
    }

    public function setAsset(string $assetId): void
    {
        $found = MarketSignalCatalog::findAsset($this->category, $assetId);

        if ($found === null) {
            return;
        }

        $this->assetId = $found['id'];
    }

    public function setInterval(string $intervalId): void
    {
        $this->intervalId = MarketSignalCatalog::findInterval($intervalId)['id'];
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    public function categoryTabs(): array
    {
        return collect(MarketSignalCatalog::categories())
            ->map(fn (array $cat): array => [
                'id' => $cat['id'],
                'label' => __('agricart.pages.fx-learn.sig_cat_'.$cat['id']),
            ])
            ->all();
    }

    /**
     * @return list<array{id: string, label: string, symbol: string}>
     */
    public function visibleAssets(): array
    {
        $q = mb_strtolower($this->search);
        $assets = MarketSignalCatalog::assetsFor($this->category);

        if ($q === '') {
            return $assets;
        }

        return array_values(array_filter(
            $assets,
            fn (array $a): bool => str_contains(mb_strtolower($a['label'].' '.$a['symbol']), $q)
        ));
    }

    /**
     * @return array{id: string, label: string, symbol: string}
     */
    public function activeAsset(): array
    {
        return MarketSignalCatalog::findAsset($this->category, $this->assetId)
            ?? MarketSignalCatalog::assetsFor('forex')[0];
    }

    /**
     * @return array{id: string, label: string, interval: string}
     */
    public function activeInterval(): array
    {
        return MarketSignalCatalog::findInterval($this->intervalId);
    }

    /**
     * @return list<array{proName: string, title: string}>
     */
    public function tapeSymbols(): array
    {
        return MarketSignalCatalog::tapeSymbols($this->category);
    }

    public function tradingViewUrl(): string
    {
        $symbol = rawurlencode($this->activeAsset()['symbol']);

        return 'https://www.tradingview.com/chart/?symbol='.$symbol;
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

    private function ensureSelection(): void
    {
        $asset = MarketSignalCatalog::findAsset($this->category, $this->assetId);

        if ($asset === null) {
            $fallback = MarketSignalCatalog::assetsFor($this->category)[0] ?? null;
            $this->assetId = (string) ($fallback['id'] ?? 'eurusd');
            $this->category = $fallback ? $this->category : 'forex';
        }

        $this->intervalId = MarketSignalCatalog::findInterval($this->intervalId)['id'];
    }

    private function selectFirstSearchMatch(): void
    {
        $q = mb_strtolower($this->search);

        foreach (MarketSignalCatalog::assetsByCategory() as $cat => $assets) {
            foreach ($assets as $asset) {
                $hay = mb_strtolower($asset['label'].' '.$asset['symbol']);

                if (str_contains($hay, $q)) {
                    $this->category = $cat;
                    $this->assetId = $asset['id'];

                    return;
                }
            }
        }
    }
}
