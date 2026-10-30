<?php

namespace App\Services\FxLearn;

use App\Models\FxLearnDemoAccount;
use App\Models\FxLearnDemoTrade;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DemoTerminalService
{
    public function __construct(
        private readonly DemoQuoteService $quotes,
    ) {}

    public function accountFor(User $user): FxLearnDemoAccount
    {
        $account = FxLearnDemoAccount::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'starting_balance' => 100000,
                'balance' => 100000,
                'phase1_pct' => 8,
                'phase2_pct' => 6,
                'phase3_pct' => 3,
                'max_day_loss_pct' => 5,
                'max_total_loss_pct' => 10,
                'max_trade_loss_pct' => 3,
                'active_phase' => 1,
                'selected_symbol' => 'eurusd',
                'watchlist' => DemoSymbolCatalog::defaultWatchlist(),
            ],
        );

        if (! is_array($account->watchlist) || $account->watchlist === []) {
            $account->forceFill(['watchlist' => DemoSymbolCatalog::defaultWatchlist()])->save();
        }

        $today = CarbonImmutable::now(agricart_display_timezone())->toDateString();

        if ($account->day_anchor_date?->toDateString() !== $today) {
            $quotes = $this->quotes->snapshot();
            $equity = $this->equity($account, $quotes);
            $account->forceFill([
                'day_anchor_date' => $today,
                'day_anchor_equity' => $equity,
            ])->save();
        }

        return $account->fresh();
    }

    /**
     * @return list<string>
     */
    public function watchlist(FxLearnDemoAccount $account): array
    {
        $ids = is_array($account->watchlist) ? $account->watchlist : DemoSymbolCatalog::defaultWatchlist();
        $clean = [];

        foreach ($ids as $id) {
            if (! is_string($id) || DemoSymbolCatalog::get($id) === null) {
                continue;
            }

            if (! in_array($id, $clean, true)) {
                $clean[] = $id;
            }
        }

        return $clean !== [] ? $clean : DemoSymbolCatalog::defaultWatchlist();
    }

    public function addToWatchlist(FxLearnDemoAccount $account, string $symbol): FxLearnDemoAccount
    {
        if (DemoSymbolCatalog::get($symbol) === null) {
            throw new InvalidArgumentException(__('agricart.pages.fx-learn.term_err_symbol'));
        }

        $list = $this->watchlist($account);

        if (! in_array($symbol, $list, true)) {
            $list[] = $symbol;
        }

        $account->forceFill([
            'watchlist' => $list,
            'selected_symbol' => $symbol,
        ])->save();

        return $account->fresh();
    }

    public function removeFromWatchlist(FxLearnDemoAccount $account, string $symbol): FxLearnDemoAccount
    {
        $list = array_values(array_filter(
            $this->watchlist($account),
            static fn (string $id): bool => $id !== $symbol,
        ));

        if ($list === []) {
            throw new InvalidArgumentException(__('agricart.pages.fx-learn.term_err_watchlist_empty'));
        }

        $selected = $account->selected_symbol === $symbol ? $list[0] : $account->selected_symbol;

        $account->forceFill([
            'watchlist' => $list,
            'selected_symbol' => $selected,
        ])->save();

        return $account->fresh();
    }

    /**
     * @param  array<string, float|int|string|bool>  $rules
     */
    public function updateRules(FxLearnDemoAccount $account, array $rules): FxLearnDemoAccount
    {
        $starting = max(1000, (float) ($rules['starting_balance'] ?? $account->starting_balance));
        $resetBalance = ! empty($rules['reset_balance']);

        $account->fill([
            'starting_balance' => $starting,
            'phase1_pct' => max(0, (float) ($rules['phase1_pct'] ?? $account->phase1_pct)),
            'phase2_pct' => max(0, (float) ($rules['phase2_pct'] ?? $account->phase2_pct)),
            'phase3_pct' => max(0, (float) ($rules['phase3_pct'] ?? $account->phase3_pct)),
            'max_day_loss_pct' => max(0, (float) ($rules['max_day_loss_pct'] ?? $account->max_day_loss_pct)),
            'max_total_loss_pct' => max(0, (float) ($rules['max_total_loss_pct'] ?? $account->max_total_loss_pct)),
            'max_trade_loss_pct' => max(0, (float) ($rules['max_trade_loss_pct'] ?? $account->max_trade_loss_pct)),
            'active_phase' => max(1, min(3, (int) ($rules['active_phase'] ?? $account->active_phase))),
        ]);

        if ($resetBalance) {
            foreach ($this->openTrades($account) as $trade) {
                $this->closeTrade($account->fresh(), (int) $trade->id, 'reset');
                $account->refresh();
            }

            $account->balance = $starting;
            $account->day_anchor_equity = $starting;
            $account->day_anchor_date = CarbonImmutable::now(agricart_display_timezone())->toDateString();
        }

        $account->save();

        return $account->fresh();
    }

    /**
     * @return array{trade: FxLearnDemoTrade, warnings: list<string>}
     */
    public function openTrade(
        FxLearnDemoAccount $account,
        string $symbol,
        string $side,
        float $lots,
        ?float $stopLoss,
        ?float $takeProfit,
    ): array {
        $meta = DemoSymbolCatalog::get($symbol);

        if ($meta === null) {
            throw new InvalidArgumentException(__('agricart.pages.fx-learn.term_err_symbol'));
        }

        $side = strtolower($side) === 'sell' ? 'sell' : 'buy';
        $lots = round(max(0.01, min(10, $lots)), 2);
        $quote = $this->quotes->freshFor($symbol) ?? ($this->quotes->snapshot()[$symbol] ?? null);

        if ($quote === null) {
            throw new InvalidArgumentException(__('agricart.pages.fx-learn.term_err_quote'));
        }

        $quotes = $this->quotes->snapshot();
        $quotes[$symbol] = $quote;

        $dashboard = $this->dashboard($account, $quotes);

        if ($dashboard['failed_total'] || $dashboard['failed_day']) {
            throw new InvalidArgumentException(__('agricart.pages.fx-learn.term_err_failed'));
        }

        $price = $side === 'buy' ? (float) $quote['ask'] : (float) $quote['bid'];
        $warnings = [];

        if ($stopLoss !== null) {
            if ($side === 'buy' && $stopLoss >= $price) {
                throw new InvalidArgumentException(__('agricart.pages.fx-learn.term_err_sl_buy'));
            }

            if ($side === 'sell' && $stopLoss <= $price) {
                throw new InvalidArgumentException(__('agricart.pages.fx-learn.term_err_sl_sell'));
            }

            $risk = $this->riskAtStop($price, $stopLoss, $lots, (float) $meta['contract'], (float) $account->balance);

            if ($risk['pct'] > (float) $account->max_trade_loss_pct + 0.0001) {
                throw new InvalidArgumentException(__('agricart.pages.fx-learn.term_err_trade_risk', [
                    'pct' => number_format($risk['pct'], 2),
                    'max' => number_format((float) $account->max_trade_loss_pct, 2),
                ]));
            }

            if ($risk['pct'] > (float) $account->max_trade_loss_pct * 0.8) {
                $warnings[] = __('agricart.pages.fx-learn.term_warn_trade_risk', [
                    'pct' => number_format($risk['pct'], 2),
                ]);
            }
        }

        if ($takeProfit !== null) {
            if ($side === 'buy' && $takeProfit <= $price) {
                throw new InvalidArgumentException(__('agricart.pages.fx-learn.term_err_tp_buy'));
            }

            if ($side === 'sell' && $takeProfit >= $price) {
                throw new InvalidArgumentException(__('agricart.pages.fx-learn.term_err_tp_sell'));
            }
        }

        $trade = FxLearnDemoTrade::query()->create([
            'user_id' => $account->user_id,
            'symbol' => $symbol,
            'label' => $meta['label'],
            'category' => $meta['category'],
            'side' => $side,
            'lots' => $lots,
            'entry_price' => $price,
            'stop_loss' => $stopLoss,
            'take_profit' => $takeProfit,
            'contract_size' => (float) $meta['contract'],
            'digits' => (int) $meta['digits'],
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $list = $this->watchlist($account);

        if (! in_array($symbol, $list, true)) {
            $list[] = $symbol;
        }

        $account->forceFill([
            'selected_symbol' => $symbol,
            'watchlist' => $list,
        ])->save();

        return ['trade' => $trade, 'warnings' => $warnings];
    }

    public function closeTrade(FxLearnDemoAccount $account, int $tradeId, string $reason = 'manual'): FxLearnDemoTrade
    {
        $trade = FxLearnDemoTrade::query()
            ->where('user_id', $account->user_id)
            ->where('id', $tradeId)
            ->where('status', 'open')
            ->firstOrFail();

        $quote = $this->quotes->freshFor((string) $trade->symbol)
            ?? ($this->quotes->snapshot()[$trade->symbol] ?? null);
        $exit = $quote
            ? ($trade->side === 'buy' ? (float) $quote['bid'] : (float) $quote['ask'])
            : (float) $trade->entry_price;

        return $this->finalizeClose($account, $trade, $exit, $reason);
    }

    /**
     * @return list<FxLearnDemoTrade>
     */
    public function autoCloseHits(FxLearnDemoAccount $account): array
    {
        $quotes = $this->quotes->snapshot();
        $closed = [];

        foreach ($this->openTrades($account) as $trade) {
            $quote = $quotes[$trade->symbol] ?? null;

            if ($quote === null) {
                continue;
            }

            $bid = (float) $quote['bid'];
            $ask = (float) $quote['ask'];

            if ($trade->side === 'buy') {
                if ($trade->stop_loss !== null && $bid <= (float) $trade->stop_loss) {
                    $closed[] = $this->finalizeClose($account, $trade, (float) $trade->stop_loss, 'sl');
                    $account->refresh();

                    continue;
                }

                if ($trade->take_profit !== null && $bid >= (float) $trade->take_profit) {
                    $closed[] = $this->finalizeClose($account, $trade, (float) $trade->take_profit, 'tp');
                    $account->refresh();
                }
            } else {
                if ($trade->stop_loss !== null && $ask >= (float) $trade->stop_loss) {
                    $closed[] = $this->finalizeClose($account, $trade, (float) $trade->stop_loss, 'sl');
                    $account->refresh();

                    continue;
                }

                if ($trade->take_profit !== null && $ask <= (float) $trade->take_profit) {
                    $closed[] = $this->finalizeClose($account, $trade, (float) $trade->take_profit, 'tp');
                    $account->refresh();
                }
            }
        }

        return $closed;
    }

    /**
     * @return Collection<int, FxLearnDemoTrade>
     */
    public function openTrades(FxLearnDemoAccount $account): Collection
    {
        return FxLearnDemoTrade::query()
            ->where('user_id', $account->user_id)
            ->where('status', 'open')
            ->orderByDesc('opened_at')
            ->get();
    }

    /**
     * @return Collection<int, FxLearnDemoTrade>
     */
    public function history(FxLearnDemoAccount $account, int $limit = 40): Collection
    {
        return FxLearnDemoTrade::query()
            ->where('user_id', $account->user_id)
            ->where('status', 'closed')
            ->orderByDesc('closed_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  array<string, array<string, mixed>>|null  $quotes
     * @return array<string, mixed>
     */
    public function dashboard(FxLearnDemoAccount $account, ?array $quotes = null): array
    {
        $quotes ??= $this->quotes->snapshot();
        $open = $this->openTrades($account);
        $floating = 0.0;
        $rows = [];

        foreach ($open as $trade) {
            $pnl = $this->floatingPnl($trade, $quotes);
            $floating += $pnl;
            $rows[] = [
                'id' => $trade->id,
                'symbol' => $trade->symbol,
                'label' => $trade->label,
                'category' => $trade->category,
                'side' => $trade->side,
                'lots' => $trade->lots,
                'entry' => $trade->entry_price,
                'sl' => $trade->stop_loss,
                'tp' => $trade->take_profit,
                'digits' => $trade->digits,
                'pnl' => $pnl,
                'opened_at' => agricart_datetime($trade->opened_at, 'Y-m-d H:i', true),
            ];
        }

        $equity = (float) $account->balance + $floating;
        $start = (float) $account->starting_balance;
        $dayAnchor = (float) ($account->day_anchor_equity ?? $start);
        $dayPnl = $equity - $dayAnchor;
        $totalPnl = $equity - $start;
        $dayLossPct = $dayAnchor > 0 ? max(0, (-$dayPnl) / $dayAnchor * 100) : 0;
        $totalLossPct = $start > 0 ? max(0, (-$totalPnl) / $start * 100) : 0;
        $phasePct = match ((int) $account->active_phase) {
            2 => (float) $account->phase2_pct,
            3 => (float) $account->phase3_pct,
            default => (float) $account->phase1_pct,
        };
        $phaseTarget = $start * ($phasePct / 100);
        $phaseGain = max(0, $totalPnl);

        return [
            'balance' => (float) $account->balance,
            'equity' => $equity,
            'floating' => $floating,
            'starting_balance' => $start,
            'day_pnl' => $dayPnl,
            'total_pnl' => $totalPnl,
            'day_loss_pct' => $dayLossPct,
            'total_loss_pct' => $totalLossPct,
            'failed_day' => $dayLossPct >= (float) $account->max_day_loss_pct,
            'failed_total' => $totalLossPct >= (float) $account->max_total_loss_pct,
            'phase_target_usd' => $phaseTarget,
            'phase_progress_pct' => $phaseTarget > 0 ? min(100, ($phaseGain / $phaseTarget) * 100) : 0,
            'phase_remaining_usd' => max(0, $phaseTarget - $phaseGain),
            'open' => $rows,
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $quotes
     */
    public function equity(FxLearnDemoAccount $account, array $quotes): float
    {
        $floating = 0.0;

        foreach ($this->openTrades($account) as $trade) {
            $floating += $this->floatingPnl($trade, $quotes);
        }

        return (float) $account->balance + $floating;
    }

    /**
     * @return array{usd: float, pct: float}
     */
    public function riskAtStop(float $entry, float $stop, float $lots, float $contract, float $balance): array
    {
        $usd = abs($entry - $stop) * $lots * $contract;
        $pct = $balance > 0 ? ($usd / $balance) * 100 : 0;

        return ['usd' => $usd, 'pct' => $pct];
    }

    /**
     * @return array{usd: float, pct: float, rr: float|null, fail: bool, entry?: float}
     */
    public function previewRisk(
        FxLearnDemoAccount $account,
        string $symbol,
        string $side,
        float $lots,
        ?float $stopLoss,
        ?float $takeProfit,
    ): array {
        $meta = DemoSymbolCatalog::get($symbol);

        if ($meta === null) {
            return ['usd' => 0, 'pct' => 0, 'rr' => null, 'fail' => false];
        }

        $quotes = $this->quotes->snapshot();
        $quote = $quotes[$symbol] ?? null;

        if ($quote === null) {
            return ['usd' => 0, 'pct' => 0, 'rr' => null, 'fail' => false];
        }

        $side = strtolower($side) === 'sell' ? 'sell' : 'buy';
        $entry = $side === 'buy' ? (float) $quote['ask'] : (float) $quote['bid'];
        $lots = max(0.01, $lots);

        if ($stopLoss === null) {
            return ['usd' => 0, 'pct' => 0, 'rr' => null, 'fail' => false, 'entry' => $entry];
        }

        $risk = $this->riskAtStop($entry, $stopLoss, $lots, (float) $meta['contract'], (float) $account->balance);
        $rr = null;

        if ($takeProfit !== null && $risk['usd'] > 0) {
            $reward = abs($takeProfit - $entry) * $lots * (float) $meta['contract'];
            $rr = $reward / $risk['usd'];
        }

        return [
            'usd' => $risk['usd'],
            'pct' => $risk['pct'],
            'rr' => $rr,
            'fail' => $risk['pct'] > (float) $account->max_trade_loss_pct,
            'entry' => $entry,
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $quotes
     */
    private function floatingPnl(FxLearnDemoTrade $trade, array $quotes): float
    {
        $quote = $quotes[$trade->symbol] ?? null;

        if ($quote === null) {
            return 0.0;
        }

        $mark = $trade->side === 'buy' ? (float) $quote['bid'] : (float) $quote['ask'];

        return $this->pnlAt($trade, $mark);
    }

    private function pnlAt(FxLearnDemoTrade $trade, float $exit): float
    {
        $dir = $trade->side === 'buy' ? 1 : -1;

        return ($exit - (float) $trade->entry_price) * $dir * (float) $trade->lots * (float) $trade->contract_size;
    }

    private function finalizeClose(
        FxLearnDemoAccount $account,
        FxLearnDemoTrade $trade,
        float $exit,
        string $reason,
    ): FxLearnDemoTrade {
        return DB::transaction(function () use ($account, $trade, $exit, $reason): FxLearnDemoTrade {
            $locked = FxLearnDemoTrade::query()
                ->whereKey($trade->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== 'open') {
                return $locked;
            }

            $pnl = $this->pnlAt($locked, $exit);
            $locked->fill([
                'exit_price' => $exit,
                'pnl' => round($pnl, 2),
                'status' => 'closed',
                'close_reason' => $reason,
                'closed_at' => now(),
            ])->save();

            $acc = FxLearnDemoAccount::query()
                ->whereKey($account->id)
                ->lockForUpdate()
                ->firstOrFail();
            $acc->balance = round((float) $acc->balance + $pnl, 2);
            $acc->save();

            return $locked->fresh();
        });
    }
}
