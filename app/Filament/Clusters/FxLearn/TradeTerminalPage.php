<?php

namespace App\Filament\Clusters\FxLearn;

use App\Filament\Clusters\FxLearnCluster;
use App\Filament\Pages\BaseModulePage;
use App\Models\FxLearnDemoAccount;
use App\Models\User;
use App\Services\Access\ModulePageAccess;
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

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'trade-terminal';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected string $view = 'filament.pages.fx-learn.trade-terminal';

    public string $symbol = 'eurusd';

    public string $side = 'buy';

    public string $lots = '0.10';

    public string $stopLoss = '';

    public string $takeProfit = '';

    public string $historyTab = 'open';

    public bool $showSettings = false;

    public bool $showAddSymbol = false;

    public string $addCategory = 'forex';

    public string $chartInterval = '60';

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

    /** @var list<string> */
    public array $watchlist = [];

    public function mount(DemoTerminalService $terminal, DemoQuoteService $quoteService): void
    {
        $user = $this->authUser();
        $account = $terminal->accountFor($user);
        $this->syncSettingsFromAccount($account);
        $this->watchlist = $terminal->watchlist($account);
        $this->symbol = in_array((string) $account->selected_symbol, $this->watchlist, true)
            ? (string) $account->selected_symbol
            : ($this->watchlist[0] ?? 'eurusd');
        $this->quotes = $quoteService->snapshot();
        $terminal->autoCloseHits($account->fresh());
        $this->dash = $terminal->dashboard($account->fresh(), $this->quotes);
    }

    public function refreshQuotes(DemoTerminalService $terminal, DemoQuoteService $quoteService): void
    {
        $account = $terminal->accountFor($this->authUser());
        $this->quotes = $quoteService->refresh();
        $terminal->autoCloseHits($account);
        $this->watchlist = $terminal->watchlist($account->fresh());
        $this->dash = $terminal->dashboard($account->fresh(), $this->quotes);
    }

    public function setSymbol(string $symbol, DemoTerminalService $terminal): void
    {
        if (DemoSymbolCatalog::get($symbol) === null) {
            return;
        }

        $account = $terminal->accountFor($this->authUser());
        $list = $terminal->watchlist($account);

        if (! in_array($symbol, $list, true)) {
            $account = $terminal->addToWatchlist($account, $symbol);
            $list = $terminal->watchlist($account);
        }

        $this->symbol = $symbol;
        $this->watchlist = $list;
        $account->forceFill(['selected_symbol' => $symbol])->save();
        $this->stopLoss = '';
        $this->takeProfit = '';
    }

    public function canTrade(): bool
    {
        $user = auth()->user();

        return $user instanceof User && (
            $user->isSuperAdmin()
            || ModulePageAccess::can($user, 'fx-learn', 'trade-terminal', 'edit')
        );
    }

    public function addSymbol(string $symbol, DemoTerminalService $terminal): void
    {
        $this->ensureCanTrade();

        try {
            $account = $terminal->addToWatchlist(
                $terminal->accountFor($this->authUser()),
                $symbol,
            );
            $this->watchlist = $terminal->watchlist($account);
            $this->symbol = $symbol;
            $this->showAddSymbol = false;
            $this->stopLoss = '';
            $this->takeProfit = '';

            Notification::make()
                ->title(__('agricart.pages.fx-learn.term_symbol_added', ['symbol' => strtoupper($symbol)]))
                ->success()
                ->send();
        } catch (InvalidArgumentException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    public function removeSymbol(string $symbol, DemoTerminalService $terminal): void
    {
        $this->ensureCanTrade();

        try {
            $account = $terminal->removeFromWatchlist(
                $terminal->accountFor($this->authUser()),
                $symbol,
            );
            $this->watchlist = $terminal->watchlist($account);
            $this->symbol = (string) $account->selected_symbol;

            Notification::make()
                ->title(__('agricart.pages.fx-learn.term_symbol_removed'))
                ->success()
                ->send();
        } catch (InvalidArgumentException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    public function setSide(string $side): void
    {
        $this->side = $side === 'sell' ? 'sell' : 'buy';
    }

    public function setChartInterval(string $interval): void
    {
        $allowed = ['1', '5', '15', '60', '240', 'D'];

        if (in_array($interval, $allowed, true)) {
            $this->chartInterval = $interval;
        }
    }

    public function openTrade(DemoTerminalService $terminal): void
    {
        $this->ensureCanTrade();

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
            $this->watchlist = $terminal->watchlist($account->fresh());
            $this->dash = $terminal->dashboard($account->fresh(), $this->quotes);
        } catch (InvalidArgumentException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    public function closeTrade(int $id, DemoTerminalService $terminal): void
    {
        $this->ensureCanTrade();

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
        $this->ensureCanTrade();

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
     * @return list<array{id: string, label: string, tv: string, bid: float|null, ask: float|null, digits: int, live: bool}>
     */
    public function watchlistRows(): array
    {
        $rows = [];

        foreach ($this->watchlist as $id) {
            $meta = DemoSymbolCatalog::get($id);

            if ($meta === null) {
                continue;
            }

            $q = $this->quotes[$id] ?? null;
            $rows[] = [
                'id' => $id,
                'label' => $meta['label'],
                'tv' => $meta['tv'],
                'bid' => $q['bid'] ?? null,
                'ask' => $q['ask'] ?? null,
                'digits' => (int) ($q['digits'] ?? $meta['digits']),
                'live' => (bool) ($q['live'] ?? false),
            ];
        }

        return $rows;
    }

    /**
     * Symbols available to add (not already on the watchlist).
     *
     * @return list<array{id: string, label: string, category: string, tv: string}>
     */
    public function addableSymbols(): array
    {
        $out = [];

        foreach (DemoSymbolCatalog::optionsForCategory($this->addCategory) as $row) {
            if (in_array($row['id'], $this->watchlist, true)) {
                continue;
            }

            $out[] = $row;
        }

        return $out;
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    public function categoryOptions(): array
    {
        return DemoSymbolCatalog::categories();
    }

    public function activeTvSymbol(): string
    {
        return (string) (DemoSymbolCatalog::get($this->symbol)['tv'] ?? 'FX:EURUSD');
    }

    public function activeLabel(): string
    {
        return (string) (DemoSymbolCatalog::get($this->symbol)['label'] ?? strtoupper($this->symbol));
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

    private function ensureCanTrade(): void
    {
        if ($this->canTrade()) {
            return;
        }

        Notification::make()
            ->title(__('agricart.pages.fx-learn.term_err_no_edit'))
            ->danger()
            ->send();

        abort(403, __('agricart.pages.fx-learn.term_err_no_edit'));
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
