<?php

namespace Tests\Feature\FxLearn;

use App\Models\User;
use App\Services\FxLearn\DemoQuoteService;
use App\Services\FxLearn\DemoTerminalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DemoTradeTerminalTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_account_watchlist_trade_and_history(): void
    {
        Cache::flush();

        $this->mock(DemoQuoteService::class, function ($mock): void {
            $quote = [
                'label' => 'EURUSD',
                'category' => 'forex',
                'digits' => 5,
                'contract' => 100000,
                'tv' => 'FX:EURUSD',
                'mid' => 1.10000,
                'bid' => 1.09990,
                'ask' => 1.10010,
                'live' => true,
            ];
            $xau = [
                'label' => 'XAUUSD',
                'category' => 'commodities',
                'digits' => 2,
                'contract' => 100,
                'tv' => 'OANDA:XAUUSD',
                'mid' => 2650.00,
                'bid' => 2649.85,
                'ask' => 2650.15,
                'live' => true,
            ];
            $snapshot = [
                'eurusd' => $quote,
                'xauusd' => $xau,
                'gbpusd' => $quote,
                'us30' => $quote,
            ];
            $mock->shouldReceive('snapshot')->andReturn($snapshot);
            $mock->shouldReceive('refresh')->andReturn($snapshot);
            $mock->shouldReceive('freshFor')->andReturnUsing(function (string $symbol) use ($snapshot) {
                return $snapshot[$symbol] ?? null;
            });
        });

        $user = User::factory()->create();
        $terminal = app(DemoTerminalService::class);
        $account = $terminal->accountFor($user);

        $this->assertSame(['eurusd', 'xauusd', 'gbpusd', 'us30'], $terminal->watchlist($account));

        $account = $terminal->addToWatchlist($account, 'btcusdt');
        $this->assertContains('btcusdt', $terminal->watchlist($account));

        $opened = $terminal->openTrade($account->fresh(), 'eurusd', 'buy', 0.10, 1.09000, 1.12000);
        $this->assertSame('open', $opened['trade']->status);
        $this->assertSame('buy', $opened['trade']->side);

        $dash = $terminal->dashboard($account->fresh());
        $this->assertCount(1, $dash['open']);

        $closed = $terminal->closeTrade($account->fresh(), (int) $opened['trade']->id, 'manual');
        $this->assertSame('closed', $closed->status);
        $this->assertNotNull($closed->pnl);

        $this->assertCount(1, $terminal->history($account->fresh()));
        $this->assertCount(0, $terminal->dashboard($account->fresh())['open']);
        $this->assertNotEmpty($account->fresh()->account_no);
        $this->assertTrue((bool) $account->fresh()->is_active);
    }

    public function test_user_can_create_and_switch_multiple_demo_accounts(): void
    {
        Cache::flush();

        $this->mock(DemoQuoteService::class, function ($mock): void {
            $quote = [
                'label' => 'EURUSD',
                'category' => 'forex',
                'digits' => 5,
                'contract' => 100000,
                'tv' => 'FX:EURUSD',
                'mid' => 1.10000,
                'bid' => 1.09990,
                'ask' => 1.10010,
                'live' => true,
            ];
            $snapshot = ['eurusd' => $quote, 'xauusd' => $quote, 'gbpusd' => $quote, 'us30' => $quote];
            $mock->shouldReceive('snapshot')->andReturn($snapshot);
            $mock->shouldReceive('refresh')->andReturn($snapshot);
            $mock->shouldReceive('freshFor')->andReturn($quote);
        });

        $user = User::factory()->create();
        $terminal = app(DemoTerminalService::class);
        $first = $terminal->accountFor($user);
        $second = $terminal->createAccount($user);

        $this->assertNotSame($first->id, $second->id);
        $this->assertTrue((bool) $second->fresh()->is_active);
        $this->assertFalse((bool) $first->fresh()->is_active);
        $this->assertCount(2, $terminal->accountsFor($user));

        $terminal->openTrade($second, 'eurusd', 'buy', 0.10, null, null);
        $this->assertCount(1, $terminal->openTrades($second));
        $this->assertCount(0, $terminal->openTrades($first->fresh()));

        $switched = $terminal->activateAccount($user, (int) $first->id);
        $this->assertTrue((bool) $switched->is_active);
        $this->assertFalse((bool) $second->fresh()->is_active);
        $this->assertSame($first->id, $terminal->accountFor($user)->id);
    }

    public function test_candle_range_uses_binance_current_kline(): void
    {
        Cache::flush();

        \Illuminate\Support\Facades\Http::fake([
            'api.binance.com/api/v3/klines*' => \Illuminate\Support\Facades\Http::response([
                [1_700_000_000_000, '64000', '65000.5', '63500.25', '64500', '1', 1_700_000_060_000],
            ], 200),
        ]);

        $range = app(DemoQuoteService::class)->candleRange('btcusdt', '15');

        $this->assertTrue($range['live']);
        $this->assertSame('15m', $range['tf']);
        $this->assertSame(65000.5, $range['high']);
        $this->assertSame(63500.25, $range['low']);
        $this->assertSame(64250.38, $range['center']);
        $this->assertSame(1500.25, $range['range']);
        $this->assertSame('binance', $range['source']);
    }

    public function test_candle_range_supports_week_and_month_binance_intervals(): void
    {
        Cache::flush();

        \Illuminate\Support\Facades\Http::fake([
            'api.binance.com/api/v3/klines*interval=1w*' => \Illuminate\Support\Facades\Http::response([
                [1_700_000_000_000, '60000', '70000', '55000', '65000', '1', 1_700_604_800_000],
            ], 200),
            'api.binance.com/api/v3/klines*interval=1M*' => \Illuminate\Support\Facades\Http::response([
                [1_700_000_000_000, '50000', '80000', '45000', '70000', '1', 1_702_675_200_000],
            ], 200),
        ]);

        $quotes = app(DemoQuoteService::class);
        $week = $quotes->candleRange('btcusdt', 'W');
        $month = $quotes->candleRange('btcusdt', 'M');

        $this->assertSame('1W', $week['tf']);
        $this->assertSame(70000.0, $week['high']);
        $this->assertSame(55000.0, $week['low']);
        $this->assertSame(62500.0, $week['center']);

        $this->assertSame('1M', $month['tf']);
        $this->assertSame(80000.0, $month['high']);
        $this->assertSame(45000.0, $month['low']);
        $this->assertSame(62500.0, $month['center']);
    }
}
