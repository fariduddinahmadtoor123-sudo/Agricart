@php
    $q = $quotes[$symbol] ?? null;
    $digits = (int) ($q['digits'] ?? 5);
    $risk = $this->riskPreview;
    $history = $this->historyRows(app(\App\Services\FxLearn\DemoTerminalService::class));
@endphp

<x-filament-panels::page>
    <div class="fx-term" wire:poll.8s="refreshQuotes">
        <header class="fx-term__head">
            <div>
                <h1 class="fx-term__title">{{ __('agricart.pages.fx-learn.trade-terminal') }}</h1>
                <p class="fx-term__intro">{{ __('agricart.pages.fx-learn.term_page_intro') }}</p>
            </div>
            <div class="fx-term__head-actions">
                <button type="button" class="fx-term__btn" wire:click="$toggle('showSettings')">
                    {{ __('agricart.pages.fx-learn.term_settings') }}
                </button>
                <button type="button" class="fx-term__btn" wire:click="refreshQuotes" wire:loading.attr="disabled">
                    {{ __('agricart.pages.fx-learn.term_refresh') }}
                </button>
            </div>
        </header>

        <div class="fx-term__stats">
            <div class="fx-term__stat">
                <span>{{ __('agricart.pages.fx-learn.term_balance') }}</span>
                <strong>${{ number_format((float) ($dash['balance'] ?? 0), 2) }}</strong>
            </div>
            <div class="fx-term__stat">
                <span>{{ __('agricart.pages.fx-learn.term_equity') }}</span>
                <strong>${{ number_format((float) ($dash['equity'] ?? 0), 2) }}</strong>
            </div>
            <div class="fx-term__stat">
                <span>{{ __('agricart.pages.fx-learn.term_floating') }}</span>
                <strong @class(['is-pos' => ($dash['floating'] ?? 0) > 0, 'is-neg' => ($dash['floating'] ?? 0) < 0])>
                    ${{ number_format((float) ($dash['floating'] ?? 0), 2) }}
                </strong>
            </div>
            <div class="fx-term__stat">
                <span>{{ __('agricart.pages.fx-learn.term_day_pnl') }}</span>
                <strong @class(['is-pos' => ($dash['day_pnl'] ?? 0) > 0, 'is-neg' => ($dash['day_pnl'] ?? 0) < 0])>
                    ${{ number_format((float) ($dash['day_pnl'] ?? 0), 2) }}
                </strong>
            </div>
            <div @class(['fx-term__stat', 'is-fail' => ! empty($dash['failed_day'])])>
                <span>{{ __('agricart.pages.fx-learn.term_day_loss') }}</span>
                <strong>{{ number_format((float) ($dash['day_loss_pct'] ?? 0), 2) }}% / {{ $maxDayLossPct }}%</strong>
            </div>
            <div @class(['fx-term__stat', 'is-fail' => ! empty($dash['failed_total'])])>
                <span>{{ __('agricart.pages.fx-learn.term_total_dd') }}</span>
                <strong>{{ number_format((float) ($dash['total_loss_pct'] ?? 0), 2) }}% / {{ $maxTotalLossPct }}%</strong>
            </div>
            <div class="fx-term__stat fx-term__stat--wide">
                <span>{{ __('agricart.pages.fx-learn.term_phase_left') }}</span>
                <strong>${{ number_format((float) ($dash['phase_remaining_usd'] ?? 0), 2) }}</strong>
                <div class="fx-term__bar"><span style="width: {{ number_format((float) ($dash['phase_progress_pct'] ?? 0), 1) }}%"></span></div>
            </div>
        </div>

        @if (! empty($dash['failed_day']) || ! empty($dash['failed_total']))
            <div class="fx-term__alert">{{ __('agricart.pages.fx-learn.term_fail_banner') }}</div>
        @endif

        <div class="fx-term__layout">
            <section class="fx-term__card">
                <div class="fx-term__chips">
                    @foreach ($this->categoryOptions() as $cat)
                        <button
                            type="button"
                            wire:click="setCategory('{{ $cat['id'] }}')"
                            @class(['fx-term__chip', 'is-active' => $category === $cat['id']])
                        >{{ __('agricart.pages.fx-learn.term_cat_'.$cat['id']) }}</button>
                    @endforeach
                </div>
                <div class="fx-term__chips">
                    @foreach ($this->symbolOptions() as $opt)
                        <button
                            type="button"
                            wire:click="setSymbol('{{ $opt['id'] }}')"
                            @class(['fx-term__chip', 'is-active' => $symbol === $opt['id']])
                        >{{ $opt['label'] }}</button>
                    @endforeach
                </div>

                <div class="fx-term__price">
                    <div>
                        <span>{{ __('agricart.pages.fx-learn.term_bid') }}</span>
                        <strong>{{ $q ? number_format((float) $q['bid'], $digits, '.', '') : '—' }}</strong>
                    </div>
                    <div>
                        <span>{{ __('agricart.pages.fx-learn.term_ask') }}</span>
                        <strong>{{ $q ? number_format((float) $q['ask'], $digits, '.', '') : '—' }}</strong>
                    </div>
                    <span @class(['fx-term__live', 'is-on' => ! empty($q['live'])])>
                        {{ ! empty($q['live']) ? __('agricart.pages.fx-learn.term_live') : __('agricart.pages.fx-learn.term_seed') }}
                    </span>
                </div>

                <div class="fx-term__sides">
                    <button type="button" wire:click="setSide('buy')" @class(['fx-term__side', 'is-buy', 'is-active' => $side === 'buy'])>BUY</button>
                    <button type="button" wire:click="setSide('sell')" @class(['fx-term__side', 'is-sell', 'is-active' => $side === 'sell'])>SELL</button>
                </div>

                <div class="fx-term__form">
                    <label>
                        <span>{{ __('agricart.pages.fx-learn.term_lots') }}</span>
                        <input type="number" step="0.01" min="0.01" max="10" wire:model.live="lots" />
                    </label>
                    <label>
                        <span>{{ __('agricart.pages.fx-learn.term_sl') }}</span>
                        <input type="number" step="any" wire:model.live="stopLoss" placeholder="—" />
                    </label>
                    <label>
                        <span>{{ __('agricart.pages.fx-learn.term_tp') }}</span>
                        <input type="number" step="any" wire:model.live="takeProfit" placeholder="—" />
                    </label>
                </div>

                <div @class(['fx-term__risk', 'is-fail' => ! empty($risk['fail'])])>
                    <div>
                        <span>{{ __('agricart.pages.fx-learn.term_risk_usd') }}</span>
                        <strong>${{ number_format((float) ($risk['usd'] ?? 0), 2) }}</strong>
                    </div>
                    <div>
                        <span>{{ __('agricart.pages.fx-learn.term_risk_pct') }}</span>
                        <strong>{{ number_format((float) ($risk['pct'] ?? 0), 2) }}% / {{ $maxTradeLossPct }}%</strong>
                    </div>
                    <div>
                        <span>{{ __('agricart.pages.fx-learn.term_rr') }}</span>
                        <strong>{{ isset($risk['rr']) && $risk['rr'] !== null ? number_format((float) $risk['rr'], 2) : '—' }}</strong>
                    </div>
                </div>

                <button
                    type="button"
                    class="fx-term__submit"
                    wire:click="openTrade"
                    @disabled(! empty($dash['failed_day']) || ! empty($dash['failed_total']) || ! empty($risk['fail']))
                >{{ __('agricart.pages.fx-learn.term_place') }}</button>
            </section>

            <section class="fx-term__card fx-term__card--table">
                <div class="fx-term__tabs">
                    <button type="button" wire:click="$set('historyTab', 'open')" @class(['is-active' => $historyTab === 'open'])>
                        {{ __('agricart.pages.fx-learn.term_tab_open') }} ({{ count($dash['open'] ?? []) }})
                    </button>
                    <button type="button" wire:click="$set('historyTab', 'history')" @class(['is-active' => $historyTab === 'history'])>
                        {{ __('agricart.pages.fx-learn.term_tab_history') }}
                    </button>
                </div>

                @if ($historyTab === 'open')
                    <div class="fx-term__table-wrap">
                        <table class="fx-term__table">
                            <thead>
                                <tr>
                                    <th>{{ __('agricart.pages.fx-learn.term_col_symbol') }}</th>
                                    <th>{{ __('agricart.pages.fx-learn.term_col_side') }}</th>
                                    <th>{{ __('agricart.pages.fx-learn.term_lots') }}</th>
                                    <th>{{ __('agricart.pages.fx-learn.term_col_entry') }}</th>
                                    <th>SL</th>
                                    <th>TP</th>
                                    <th>P/L</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (($dash['open'] ?? []) as $row)
                                    <tr>
                                        <td>{{ $row['label'] }}</td>
                                        <td class="is-{{ $row['side'] }}">{{ strtoupper($row['side']) }}</td>
                                        <td>{{ number_format((float) $row['lots'], 2) }}</td>
                                        <td>{{ number_format((float) $row['entry'], (int) $row['digits'], '.', '') }}</td>
                                        <td>{{ $row['sl'] !== null ? number_format((float) $row['sl'], (int) $row['digits'], '.', '') : '—' }}</td>
                                        <td>{{ $row['tp'] !== null ? number_format((float) $row['tp'], (int) $row['digits'], '.', '') : '—' }}</td>
                                        <td @class(['is-pos' => $row['pnl'] > 0, 'is-neg' => $row['pnl'] < 0])>${{ number_format((float) $row['pnl'], 2) }}</td>
                                        <td>
                                            <button type="button" class="fx-term__mini" wire:click="closeTrade({{ (int) $row['id'] }})">
                                                {{ __('agricart.pages.fx-learn.term_close') }}
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="fx-term__empty">{{ __('agricart.pages.fx-learn.term_no_open') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="fx-term__table-wrap">
                        <table class="fx-term__table">
                            <thead>
                                <tr>
                                    <th>{{ __('agricart.pages.fx-learn.term_col_symbol') }}</th>
                                    <th>{{ __('agricart.pages.fx-learn.term_col_side') }}</th>
                                    <th>{{ __('agricart.pages.fx-learn.term_lots') }}</th>
                                    <th>{{ __('agricart.pages.fx-learn.term_col_entry') }}</th>
                                    <th>{{ __('agricart.pages.fx-learn.term_col_exit') }}</th>
                                    <th>P/L</th>
                                    <th>{{ __('agricart.pages.fx-learn.term_col_when') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($history as $row)
                                    <tr>
                                        <td>{{ $row['label'] }}</td>
                                        <td class="is-{{ $row['side'] }}">{{ strtoupper($row['side']) }}</td>
                                        <td>{{ number_format((float) $row['lots'], 2) }}</td>
                                        <td>{{ number_format((float) $row['entry'], (int) $row['digits'], '.', '') }}</td>
                                        <td>{{ number_format((float) $row['exit'], (int) $row['digits'], '.', '') }}</td>
                                        <td @class(['is-pos' => $row['pnl'] > 0, 'is-neg' => $row['pnl'] < 0])>${{ number_format((float) $row['pnl'], 2) }}</td>
                                        <td>{{ $row['closed_at'] }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="fx-term__empty">{{ __('agricart.pages.fx-learn.term_no_history') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

        @if ($showSettings)
            <div class="fx-term__modal">
                <div class="fx-term__modal-backdrop" wire:click="$set('showSettings', false)"></div>
                <div class="fx-term__modal-card">
                    <div class="fx-term__modal-head">
                        <h2>{{ __('agricart.pages.fx-learn.term_settings') }}</h2>
                        <button type="button" wire:click="$set('showSettings', false)">×</button>
                    </div>
                    <p class="fx-term__modal-hint">{{ __('agricart.pages.fx-learn.term_settings_hint') }}</p>
                    <div class="fx-term__settings-grid">
                        <label><span>{{ __('agricart.pages.fx-learn.prop_balance') }}</span><input type="number" wire:model="startingBalance" /></label>
                        <label><span>{{ __('agricart.pages.fx-learn.prop_phase1') }}</span><input type="number" step="0.1" wire:model="phase1Pct" /></label>
                        <label><span>{{ __('agricart.pages.fx-learn.prop_phase2') }}</span><input type="number" step="0.1" wire:model="phase2Pct" /></label>
                        <label><span>{{ __('agricart.pages.fx-learn.prop_phase3') }}</span><input type="number" step="0.1" wire:model="phase3Pct" /></label>
                        <label><span>{{ __('agricart.pages.fx-learn.prop_max_day') }}</span><input type="number" step="0.1" wire:model="maxDayLossPct" /></label>
                        <label><span>{{ __('agricart.pages.fx-learn.prop_max_total') }}</span><input type="number" step="0.1" wire:model="maxTotalLossPct" /></label>
                        <label><span>{{ __('agricart.pages.fx-learn.prop_max_trade') }}</span><input type="number" step="0.1" wire:model="maxTradeLossPct" /></label>
                        <label>
                            <span>{{ __('agricart.pages.fx-learn.prop_active_phase') }}</span>
                            <select wire:model="activePhase">
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                            </select>
                        </label>
                    </div>
                    <div class="fx-term__modal-foot">
                        <button type="button" class="fx-term__btn" wire:click="saveSettings">{{ __('agricart.pages.fx-learn.term_save_rules') }}</button>
                        <button type="button" class="fx-term__btn fx-term__btn--danger" wire:click="resetAccount" wire:confirm="{{ __('agricart.pages.fx-learn.term_reset_confirm') }}">
                            {{ __('agricart.pages.fx-learn.term_reset_balance') }}
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
