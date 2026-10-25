<?php

namespace App\Filament\Clusters\FxLearn;

use App\Filament\Clusters\FxLearnCluster;
use App\Filament\Pages\BaseModulePage;
use App\Models\FxLearnDemoAccount;
use App\Models\User;
use App\Services\FxLearn\DemoQuoteService;
use App\Services\FxLearn\DemoSymbolCatalog;
use App\Services\FxLearn\DemoTerminalService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use InvalidArgumentException;
use Livewire\Attributes\Computed;

class TradeTerminalPage extends BaseModulePage
{
    protected static ?string $cluster = FxLearnCluster::class;

    protected static ?string $navigationLabel = 'Trade Terminal';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'trade-terminal';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected string $view = 'filament.pages.fx-learn.trade-terminal';

    public string $category = 'forex';

    public string $symbol = 'eurusd';

    public string $side = 'buy';

    public string $lots = '0.10';

    public string $stopLoss = '';

    public string $takeProfit = '';

    public string $historyTab = 'open';

    public bool $showSettings = false;

    public string $startingBalance = '100000';

    public string $phase1Pct = '8';

    public string $phase2Pct = '6';

    public string $phase3Pct = '3';

    public string $maxDayLossPct = '5';

    public string $maxTotalLossPct = '10';

    public string $maxTradeLossPct = '3';

    public string $activePhase = '1';

    /** @var array<string, array<string, mixed>> */
    public array $quotes = [];

    /** @var array<string, mixed> */
    public array $dash = [];

    public function mount(DemoTerminalService $terminal, DemoQuoteService $quoteService): void
    {
        $user = $this->authUser();
        $account = $terminal->accountFor($user);
        $this->syncSettingsFromAccount($account);
        $this->symbol = $account->selected_symbol ?: 'eurusd';
        $meta = DemoSymbolCatalog::get($this->symbol);
        $this->category = $meta['category'] ?? 'forex';
        $this->quotes = $quoteService->snapshot();
        $terminal->autoCloseHits($account->fresh());
        $this->dash = $terminal->dashboard($account->fresh(), $this->quotes);
    }

    public function refreshQuotes(DemoTerminalService $terminal, DemoQuoteService $quoteService): void
    {
        $account = $terminal->accountFor($this->authUser());
        $this->quotes = $quoteService->refresh();
        $terminal->autoCloseHits($account);
        $this->dash = $terminal->dashboard($account->fresh(), $this->quotes);
    }

    public function setCategory(string $category): void
    {
        $allowed = collect(DemoSymbolCatalog::categories())->pluck('id')->all();

        if (! in_array($category, $allowed, true)) {
            return;
        }

        $this->category = $category;
        $first = collect(DemoSymbolCatalog::all())
            ->filter(fn (array $m): bool => $m['category'] === $category)
            ->keys()
            ->first();

        if (is_string($first)) {
            $this->symbol = $first;
        }
    }

    public function setSymbol(string $symbol): void
    {
        if (DemoSymbolCatalog::get($symbol) === null) {
            return;
        }

        $this->symbol = $symbol;
        $this->category = DemoSymbolCatalog::get($symbol)['category'];
    }

    public function setSide(string $side): void
    {
        $this->side = $side === 'sell' ? 'sell' : 'buy';
    }

    public function openTrade(DemoTerminalService $terminal): void
    {
        try {
            $account = $terminal->accountFor($this->authUser());
            $result = $terminal->openTrade(
                $account,
                $this->symbol,
                $this->side,
                (float) $this->lots,
                $this->nullableFloat($this->stopLoss),
                $this->nullableFloat($this->takeProfit),
            );

            foreach ($result['warnings'] as $warning) {
                Notification::make()->title($warning)->warning()->send();
            }

            Notification::make()
                ->title(__('agricart.pages.fx-learn.term_opened'))
                ->success()
                ->send();

            $this->quotes = app(DemoQuoteService::class)->snapshot();
            $this->dash = $terminal->dashboard($account->fresh(), $this->quotes);
        } catch (InvalidArgumentException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    public function closeTrade(int $id, DemoTerminalService $terminal): void
    {
        try {
            $account = $terminal->accountFor($this->authUser());
            $terminal->closeTrade($account, $id, 'manual');
            Notification::make()
                ->title(__('agricart.pages.fx-learn.term_closed'))
                ->success()
                ->send();
            $this->quotes = app(DemoQuoteService::class)->snapshot();
            $this->dash = $terminal->dashboard($account->fresh(), $this->quotes);
        } catch (InvalidArgumentException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    public function saveSettings(DemoTerminalService $terminal): void
    {
        $this->persistSettings($terminal, resetBalance: false);
    }

    public function resetAccount(DemoTerminalService $terminal): void
    {
        $this->persistSettings($terminal, resetBalance: true);
    }

    private function persistSettings(DemoTerminalService $terminal, bool $resetBalance): void
    {
        $account = $terminal->accountFor($this->authUser());
        $account = $terminal->updateRules($account, [
            'starting_balance' => (float) $this->startingBalance,
            'phase1_pct' => (float) $this->phase1Pct,
            'phase2_pct' => (float) $this->phase2Pct,
            'phase3_pct' => (float) $this->phase3Pct,
            'max_day_loss_pct' => (float) $this->maxDayLossPct,
            'max_total_loss_pct' => (float) $this->maxTotalLossPct,
            'max_trade_loss_pct' => (float) $this->maxTradeLossPct,
            'active_phase' => (int) $this->activePhase,
            'reset_balance' => $resetBalance,
        ]);

        $this->syncSettingsFromAccount($account);
        $this->showSettings = false;
        $this->quotes = app(DemoQuoteService::class)->snapshot();
        $this->dash = $terminal->dashboard($account, $this->quotes);

        Notification::make()
            ->title(__('agricart.pages.fx-learn.term_settings_saved'))
            ->success()
            ->send();
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function riskPreview(): array
    {
        $terminal = app(DemoTerminalService::class);
        $account = $terminal->accountFor($this->authUser());

        return $terminal->previewRisk(
            $account,
            $this->symbol,
            $this->side,
            (float) $this->lots,
            $this->nullableFloat($this->stopLoss),
            $this->nullableFloat($this->takeProfit),
        );
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    public function categoryOptions(): array
    {
        return DemoSymbolCatalog::categories();
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    public function symbolOptions(): array
    {
        $out = [];

        foreach (DemoSymbolCatalog::all() as $id => $meta) {
            if ($meta['category'] !== $this->category) {
                continue;
            }

            $out[] = ['id' => $id, 'label' => $meta['label']];
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function historyRows(DemoTerminalService $terminal): array
    {
        $account = $terminal->accountFor($this->authUser());

        return $terminal->history($account)->map(function ($trade): array {
            return [
                'id' => $trade->id,
                'label' => $trade->label,
                'side' => $trade->side,
                'lots' => $trade->lots,
                'entry' => $trade->entry_price,
                'exit' => $trade->exit_price,
                'pnl' => $trade->pnl,
                'reason' => $trade->close_reason,
                'digits' => $trade->digits,
                'closed_at' => agricart_datetime($trade->closed_at, 'Y-m-d H:i', true),
            ];
        })->all();
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

    private function authUser(): User
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }

    private function syncSettingsFromAccount(FxLearnDemoAccount $account): void
    {
        $this->startingBalance = (string) $account->starting_balance;
        $this->phase1Pct = (string) $account->phase1_pct;
        $this->phase2Pct = (string) $account->phase2_pct;
        $this->phase3Pct = (string) $account->phase3_pct;
        $this->maxDayLossPct = (string) $account->max_day_loss_pct;
        $this->maxTotalLossPct = (string) $account->max_total_loss_pct;
        $this->maxTradeLossPct = (string) $account->max_trade_loss_pct;
        $this->activePhase = (string) $account->active_phase;
    }

    private function nullableFloat(string $value): ?float
    {
        $value = trim($value);

        if ($value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}
