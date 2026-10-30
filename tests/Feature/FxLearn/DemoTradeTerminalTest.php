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
    }
}
