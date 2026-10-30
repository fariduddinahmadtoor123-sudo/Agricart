@php
    $rows = $this->historyRows();
    $canManage = $this->canManageAccounts();
    $seeAll = $this->canSeeAllUsers();
    $selectedMeta = $this->selectedAccountMeta();
    $tradeRows = $selectedMeta ? $this->selectedTradeRows() : [];
    $priceFmt = static fn (?float $v, int $digits): string => $v !== null
        ? number_format($v, $digits, '.', '')
        : '—';
@endphp

<x-filament-panels::page>
    <div class="fxt fxt--ahist">
        <header class="fxt__ahist-head">
            <div>
                <p class="fxt__kicker">{{ __('agricart.pages.fx-learn.ahist_kicker') }}</p>
                <h1 class="fxt__ahist-title">{{ __('agricart.pages.fx-learn.ahist_title') }}</h1>
                <p class="fxt__ahist-lead">{{ __('agricart.pages.fx-learn.ahist_lead') }}</p>
            </div>
            @if ($canManage)
                <button type="button" class="fxt__btn fxt__btn--primary" wire:click="createAccount">
                    {{ __('agricart.pages.fx-learn.term_account_new') }}
                </button>
            @endif
        </header>

        <div class="fxt__ahist-table-wrap">
            <table class="fxt__ahist-table" aria-label="{{ __('agricart.pages.fx-learn.ahist_title') }}">
                <thead>
                    <tr>
                        <th>{{ __('agricart.pages.fx-learn.ahist_col_no') }}</th>
                        @if ($seeAll)
                            <th>{{ __('agricart.pages.fx-learn.ahist_col_user') }}</th>
                        @endif
                        <th>{{ __('agricart.pages.fx-learn.term_balance') }}</th>
                        <th>{{ __('agricart.pages.fx-learn.ahist_col_starting') }}</th>
                        <th>{{ __('agricart.pages.fx-learn.ahist_col_trades') }}</th>
                        <th>{{ __('agricart.pages.fx-learn.ahist_col_status') }}</th>
                        <th>{{ __('agricart.pages.fx-learn.ahist_col_created') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr
                            wire:key="ahist-{{ $row['id'] }}"
                            wire:click="openAccountTrades({{ $row['id'] }})"
                            @class([
                                'is-on' => $row['is_active'],
                                'is-selected' => $selectedMeta && (int) $this->selectedAccountId === (int) $row['id'],
                                'is-clickable' => true,
                            ])
                        >
                            <td dir="ltr"><strong>{{ $row['account_no'] }}</strong></td>
                            @if ($seeAll)
                                <td>
                                    <span>{{ $row['user_name'] }}</span>
                                    @if (filled($row['user_email']))
                                        <small dir="ltr">{{ $row['user_email'] }}</small>
                                    @endif
                                </td>
                            @endif
                            <td dir="ltr">${{ number_format($row['balance'], 2) }}</td>
                            <td dir="ltr">${{ number_format($row['starting'], 2) }}</td>
                            <td dir="ltr">{{ $row['open_trades'] }} / {{ $row['closed_trades'] }}</td>
                            <td>
                                <span @class(['fxt__ahist-badge', 'is-on' => $row['is_active']])>
                                    {{ $row['is_active'] ? __('agricart.pages.fx-learn.ahist_active') : __('agricart.pages.fx-learn.ahist_idle') }}
                                </span>
                            </td>
                            <td dir="ltr">{{ $row['created_at'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $seeAll ? 7 : 6 }}" class="fxt__empty">{{ __('agricart.pages.fx-learn.ahist_empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($selectedMeta)
            <section class="fxt__ahist-trades" wire:key="ahist-trades-{{ $this->selectedAccountId }}-{{ $tradePeriod }}">
                <header class="fxt__ahist-trades-head">
                    <div>
                        <p class="fxt__kicker">{{ __('agricart.pages.fx-learn.ahist_trades_kicker') }}</p>
                        <h2 class="fxt__ahist-trades-title" dir="ltr">
                            {{ $selectedMeta['account_no'] }}
                            <span>· {{ $selectedMeta['user_name'] }}</span>
                        </h2>
                    </div>
                    <div class="fxt__ahist-trades-tools">
                        <div class="fxt__tf" role="group" aria-label="{{ __('agricart.pages.fx-learn.ahist_period_label') }}">
                            @foreach ([
                                'day' => __('agricart.pages.fx-learn.ahist_period_day'),
                                'week' => __('agricart.pages.fx-learn.ahist_period_week'),
                                'month' => __('agricart.pages.fx-learn.ahist_period_month'),
                                'all' => __('agricart.pages.fx-learn.ahist_period_all'),
                            ] as $periodId => $periodLabel)
                                <button
                                    type="button"
                                    wire:click="setTradePeriod(@js($periodId))"
                                    @class(['fxt__tf-btn', 'is-on' => $tradePeriod === $periodId])
                                >{{ $periodLabel }}</button>
                            @endforeach
                        </div>
                        <button type="button" class="fxt__btn" wire:click="closeAccountTrades">
                            {{ __('agricart.pages.fx-learn.ahist_trades_close') }}
                        </button>
                    </div>
                </header>

                <div class="fxt__ahist-table-wrap">
                    <table class="fxt__ahist-table" aria-label="{{ __('agricart.pages.fx-learn.ahist_trades_kicker') }}">
                        <thead>
                            <tr>
                                <th>{{ __('agricart.pages.fx-learn.term_col_symbol') }}</th>
                                <th>{{ __('agricart.pages.fx-learn.term_col_side') }}</th>
                                <th>{{ __('agricart.pages.fx-learn.term_lots') }}</th>
                                <th>{{ __('agricart.pages.fx-learn.term_col_entry') }}</th>
                                <th>{{ __('agricart.pages.fx-learn.term_col_exit') }}</th>
                                <th>{{ __('agricart.pages.fx-learn.term_col_pnl') }}</th>
                                <th>{{ __('agricart.pages.fx-learn.ahist_col_status') }}</th>
                                <th>{{ __('agricart.pages.fx-learn.term_col_opened') }}</th>
                                <th>{{ __('agricart.pages.fx-learn.term_col_when') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tradeRows as $trade)
                                <tr wire:key="ahist-trade-{{ $trade['id'] }}">
                                    <td dir="ltr"><strong>{{ $trade['symbol'] }}</strong></td>
                                    <td>{{ $trade['side'] }}</td>
                                    <td dir="ltr">{{ number_format($trade['lots'], 2) }}</td>
                                    <td dir="ltr">{{ $priceFmt($trade['entry'], $trade['digits']) }}</td>
                                    <td dir="ltr">{{ $priceFmt($trade['exit'], $trade['digits']) }}</td>
                                    <td
                                        dir="ltr"
                                        @class([
                                            'is-pos' => ($trade['pnl'] ?? 0) > 0,
                                            'is-neg' => ($trade['pnl'] ?? 0) < 0,
                                        ])
                                    >
                                        {{ $trade['pnl'] !== null ? '$'.number_format($trade['pnl'], 2) : '—' }}
                                    </td>
                                    <td>{{ $trade['status'] }}</td>
                                    <td dir="ltr">{{ $trade['opened_at'] }}</td>
                                    <td dir="ltr">{{ $trade['closed_at'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="fxt__empty">{{ __('agricart.pages.fx-learn.ahist_trades_empty') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
