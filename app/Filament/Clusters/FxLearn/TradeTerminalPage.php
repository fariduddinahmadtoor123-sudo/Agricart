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
use App\Services\FxLearn\EconomicCalendarService;
use App\Services\FxLearn\MarketSessionClock;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

class TradeTerminalPage extends BaseModulePage
{
    protected static ?string $cluster = FxLearnCluster::class;

    protected static ?string $navigationLabel = 'Trade Terminal';

    protected static ?int $navigationSort = 3;

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

    public string $chartInterval = '60';

    public string $startingBalance = '100000';

    public string $phase1Pct = '8';

    public string $phase2Pct = '6';

    public string $phase3Pct = '3';

    public string $maxDayLossPct = '5';

    public string $maxTotalLossPct = '10';

    public string $maxTradeLossPct = '3';

    public string $activePhase = '1';

    public string $dataCcyFilter = 'all';

    public string $dataImpactFilter = 'high';

    /** @var array<string, array<string, mixed>> */
    public array $quotes = [];

    /** @var array<string, mixed> */
    public array $dash = [];

    public function mount(DemoTerminalService $terminal, DemoQuoteService $quoteService): void
    {
        $user = $this->authUser();
        $account = $terminal->accountFor($user);
        $this->syncSettingsFromAccount($account);
        $selected = (string) ($account->selected_symbol ?: 'eurusd');
        $meta = DemoSymbolCatalog::get($selected);
        $this->symbol = $meta !== null ? $selected : 'eurusd';
        $this->category = (string) (DemoSymbolCatalog::get($this->symbol)['category'] ?? 'forex');
        $this->quotes = $quoteService->snapshot();
        $terminal->autoCloseHits($account->fresh());
        $this->dash = $terminal->dashboard($account->fresh(), $this->quotes);
        $this->dispatch('fx-learn-terminal-filters-sync', category: $this->category);
        $this->dispatchRiskSync();
    }

    /**
     * Soft poll: reuse shared quote cache (no API stampede). UI ticks stay on Binance WebSocket.
     */
    public function syncQuotes(DemoTerminalService $terminal, DemoQuoteService $quoteService): void
    {
        $account = $terminal->accountFor($this->authUser());
        $this->quotes = $quoteService->snapshot();
        $terminal->autoCloseHits($account);
        $this->dash = $terminal->dashboard($account->fresh(), $this->quotes);
        $this->dispatchRiskSync();
    }

    /**
     * Manual refresh only — busts shared cache once.
     */
    public function refreshQuotes(DemoTerminalService $terminal, DemoQuoteService $quoteService): void
    {
        $account = $terminal->accountFor($this->authUser());
        $this->quotes = $quoteService->refresh();
        $terminal->autoCloseHits($account);
        $this->dash = $terminal->dashboard($account->fresh(), $this->quotes);
        $this->dispatchRiskSync();
    }

    #[On('fx-learn-terminal-category')]
    public function onToolbarCategory(mixed $category = null): void
    {
        $value = is_array($category) ? (string) ($category['category'] ?? '') : (string) $category;
        $this->setCategory($value, app(DemoTerminalService::class));
    }

    #[On('fx-learn-terminal-settings')]
    public function onToolbarSettings(): void
    {
        $this->showSettings = true;
    }

    public function setCategory(string $category, DemoTerminalService $terminal): void
    {
        $allowed = collect(DemoSymbolCatalog::categories())->pluck('id')->all();

        if (! in_array($category, $allowed, true)) {
            return;
        }

        $this->category = $category;
        $first = DemoSymbolCatalog::optionsForCategory($category)[0]['id'] ?? null;

        if (is_string($first)) {
            $this->setSymbol($first, $terminal);
        }

        $this->dispatch('fx-learn-terminal-filters-sync', category: $this->category);
    }

    public function setSymbol(string $symbol, DemoTerminalService $terminal): void
    {
        $meta = DemoSymbolCatalog::get($symbol);

        if ($meta === null) {
            return;
        }

        $this->symbol = $symbol;
        $this->category = (string) $meta['category'];
        $terminal->accountFor($this->authUser())->forceFill(['selected_symbol' => $symbol])->save();
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

    public function setSide(string $side): void
    {
        $this->side = $side === 'sell' ? 'sell' : 'buy';
    }

    public function openTradeAs(string $side, DemoTerminalService $terminal): void
    {
        $this->side = $side === 'sell' ? 'sell' : 'buy';
        $this->openTrade($terminal);
    }

    public function setChartInterval(string $interval): void
    {
        $allowed = ['1', '5', '15', '60', '240', 'D'];

        if (in_array($interval, $allowed, true)) {
            $this->chartInterval = $interval;
        }
    }

    /**
     * One-click Buy/Sell from a watchlist row (uses current lots + optional SL/TP fields).
     */
    public function quickTrade(string $symbol, string $side, DemoTerminalService $terminal): void
    {
        $this->ensureCanTrade();

        $meta = DemoSymbolCatalog::get($symbol);

        if ($meta === null) {
            return;
        }

        $this->symbol = $symbol;
        $this->category = (string) $meta['category'];
        $this->side = $side === 'sell' ? 'sell' : 'buy';
        $this->openTrade($terminal);
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
            $this->dash = $terminal->dashboard($account->fresh(), $this->quotes);
            $this->dispatchRiskSync();
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
            $this->dispatchRiskSync();
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
        $this->dispatchRiskSync();

        Notification::make()
            ->title(__('agricart.pages.fx-learn.term_settings_saved'))
            ->success()
            ->send();
    }

    /**
     * Thin FundedHigh-style bars for the module toolbar (day loss / overall loss / profit|loss).
     *
     * @return array{day: array<string, mixed>, overall_loss: array<string, mixed>, pnl: array<string, mixed>}
     */
    public function riskToolbar(): array
    {
        $dayPct = (float) ($this->dash['day_loss_pct'] ?? 0);
        $dayCap = max(0.01, (float) $this->maxDayLossPct);
        $totalPct = (float) ($this->dash['total_loss_pct'] ?? 0);
        $totalCap = max(0.01, (float) $this->maxTotalLossPct);
        $totalPnl = (float) ($this->dash['total_pnl'] ?? 0);
        $phaseFill = (float) ($this->dash['phase_progress_pct'] ?? 0);
        $profitMode = $totalPnl >= 0;

        return [
            'day' => [
                'label' => __('agricart.pages.fx-learn.term_tb_day'),
                'value' => number_format($dayPct, 1).'%',
                'fill' => min(100.0, round(($dayPct / $dayCap) * 100, 1)),
                'fail' => ! empty($this->dash['failed_day']),
                'tone' => 'loss',
            ],
            'overall_loss' => [
                'label' => __('agricart.pages.fx-learn.term_tb_dd'),
                'value' => number_format($totalPct, 1).'%',
                'fill' => min(100.0, round(($totalPct / $totalCap) * 100, 1)),
                'fail' => ! empty($this->dash['failed_total']),
                'tone' => 'loss',
            ],
            'pnl' => [
                'label' => $profitMode
                    ? __('agricart.pages.fx-learn.term_tb_profit')
                    : __('agricart.pages.fx-learn.term_tb_overall_loss'),
                'value' => $profitMode
                    ? number_format($phaseFill, 1).'%'
                    : number_format($totalPct, 1).'%',
                'fill' => $profitMode
                    ? min(100.0, round($phaseFill, 1))
                    : min(100.0, round(($totalPct / $totalCap) * 100, 1)),
                'fail' => (! $profitMode) && ! empty($this->dash['failed_total']),
                'tone' => $profitMode ? 'profit' : 'loss',
            ],
        ];
    }

    private function dispatchRiskSync(): void
    {
        $this->dispatch('fx-learn-terminal-risk-sync', risk: $this->riskToolbar());
        $this->dispatch('fx-learn-terminal-sessions-sync', sessions: $this->sessionLiquidity());
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
     * All tradable symbols in the active category (auto — no manual add).
     *
     * @return list<array{id: string, label: string, tv: string, binance: ?string, bid: float|null, ask: float|null, mid: float|null, high: float|null, low: float|null, digits: int, live: bool, time: string, refresh: string, refresh_mode: string}>
     */
    public function categorySymbolRows(): array
    {
        $rows = [];
        $serverSeconds = max(DemoQuoteService::SOFT_POLL_SECONDS, DemoQuoteService::sharedCacheSeconds());

        foreach (DemoSymbolCatalog::optionsForCategory($this->category) as $row) {
            $q = $this->quotes[$row['id']] ?? null;
            $meta = DemoSymbolCatalog::get($row['id']);
            $digits = (int) ($q['digits'] ?? $meta['digits'] ?? 5);
            $quotedAt = $q['quoted_at'] ?? null;
            $time = is_string($quotedAt) && $quotedAt !== ''
                ? agricart_datetime(\Carbon\CarbonImmutable::parse($quotedAt), 'H:i:s', true)
                : '—';
            // Prefer stream-eligible Binance symbol from quote service (null when Finnhub owns the row).
            $binance = $q['binance'] ?? null;
            $hasStream = is_string($binance) && $binance !== '';
            $source = (string) ($q['source'] ?? 'seed');
            $live = (bool) ($q['live'] ?? false);

            if ($hasStream) {
                // Binance bookTicker: updates on every best bid/ask change (typically milliseconds).
                $refreshMode = 'ws';
                $refresh = __('agricart.pages.fx-learn.term_refresh_ms');
            } elseif ($live && $source === 'finnhub') {
                $refreshMode = 'poll';
                $refresh = __('agricart.pages.fx-learn.term_refresh_sec', ['n' => $serverSeconds]);
            } elseif ($live) {
                $refreshMode = 'poll';
                $refresh = __('agricart.pages.fx-learn.term_refresh_sec', ['n' => $serverSeconds]);
            } else {
                $refreshMode = 'fixed';
                $refresh = __('agricart.pages.fx-learn.term_refresh_fixed');
            }

            $rows[] = [
                'id' => $row['id'],
                'label' => $row['label'],
                'tv' => $row['tv'],
                'binance' => $hasStream ? $binance : null,
                'bid' => $q['bid'] ?? null,
                'ask' => $q['ask'] ?? null,
                'mid' => $q['mid'] ?? null,
                'high' => $q['high'] ?? null,
                'low' => $q['low'] ?? null,
                'digits' => $digits,
                'live' => $live,
                'time' => $time,
                'refresh' => $refresh,
                'refresh_mode' => $refreshMode,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    public function categoryOptions(): array
    {
        return DemoSymbolCatalog::categories();
    }

    /**
     * @return array<string, mixed>
     */
    public function sessionLiquidity(): array
    {
        return MarketSessionClock::liquidityStrip();
    }

    public function setDataCcyFilter(string $ccy): void
    {
        $this->dataCcyFilter = strtoupper($ccy) === 'USD' ? 'USD' : 'all';
    }

    public function setDataImpactFilter(string $impact): void
    {
        $this->dataImpactFilter = in_array($impact, ['high', 'med', 'all'], true) ? $impact : 'high';
    }

    /**
     * Next economic print for the desk ticker (uses shared calendar cache).
     *
     * @return array<string, mixed>|null
     */
    public function nextDataEvent(EconomicCalendarService $calendar): ?array
    {
        $bundle = $calendar->snapshot();
        $events = is_array($bundle['events'] ?? null) ? $bundle['events'] : [];

        $filtered = collect($events)
            ->filter(function (array $ev): bool {
                if ($this->dataCcyFilter === 'USD' && strtoupper((string) ($ev['ccy'] ?? '')) !== 'USD') {
                    return false;
                }

                if ($this->dataImpactFilter !== 'all' && (string) ($ev['impact'] ?? '') !== $this->dataImpactFilter) {
                    return false;
                }

                return true;
            })
            ->values()
            ->all();

        return $calendar->nextUpcoming($filtered, highOnly: false);
    }

    public function activeTvSymbol(): string
    {
        return (string) (DemoSymbolCatalog::get($this->symbol)['tv'] ?? 'FX:EURUSD');
    }

    public function activeLabel(): string
    {
        return (string) (DemoSymbolCatalog::get($this->symbol)['label'] ?? strtoupper($this->symbol));
    }

    /** TradingView Technical Analysis interval id. */
    public function taInterval(): string
    {
        return match ($this->chartInterval) {
            '1' => '1m',
            '5' => '5m',
            '15' => '15m',
            '240' => '4h',
            'D' => '1D',
            default => '1h',
        };
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
