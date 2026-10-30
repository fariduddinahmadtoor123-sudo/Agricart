@php
    $q = $quotes[$symbol] ?? null;
    $digits = (int) ($q['digits'] ?? 5);
    $risk = $this->riskPreview;
    $history = $this->historyRows(app(\App\Services\FxLearn\DemoTerminalService::class));
    $tvSymbol = $this->activeTvSymbol();
    $label = $this->activeLabel();
    $taInterval = $this->taInterval();
    $deskRows = $this->categorySymbolRows();
    $binanceStreams = collect($deskRows)
        ->filter(fn ($row) => filled($row['binance'] ?? null))
        ->mapWithKeys(fn ($row) => [strtolower((string) $row['binance']) => [
            'id' => $row['id'],
            'digits' => (int) $row['digits'],
        ]])
        ->all();
@endphp

<x-filament-panels::page>
    <div
        class="fxt"
        wire:poll.5s="refreshQuotes"
        x-data="{
            mountTv(el, src, config) {
                if (! el) return;
                el.replaceChildren();
                const wrap = document.createElement('div');
                wrap.className = 'tradingview-widget-container';
                wrap.style.width = '100%';
                wrap.style.height = (config.height || 420) + 'px';
                const widget = document.createElement('div');
                widget.className = 'tradingview-widget-container__widget';
                widget.style.height = '100%';
                wrap.appendChild(widget);
                const script = document.createElement('script');
                script.type = 'text/javascript';
                script.src = src;
                script.async = true;
                script.text = JSON.stringify(config);
                wrap.appendChild(script);
                el.appendChild(wrap);
            }
        }"
    >
        {{-- Stable Binance WebSocket host — remounts only when stream set changes --}}
        <div
            class="fxt__ws"
            wire:ignore
            wire:key="fxt-ws-{{ $category }}-{{ collect(array_keys($binanceStreams))->sort()->implode('-') }}"
            x-data="{
                streams: @js($binanceStreams),
                liveLabel: @js(__('agricart.pages.fx-learn.term_live')),
                ws: null,
                reconnectTimer: null,
                fmt(value, digits) {
                    const n = Number(value);
                    if (! Number.isFinite(n)) return '—';
                    return n.toFixed(digits);
                },
                nowTime() {
                    try {
                        return new Intl.DateTimeFormat(undefined, {
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit',
                            hour12: false,
                            timeZone: 'Asia/Karachi'
                        }).format(new Date());
                    } catch (e) {
                        return new Date().toLocaleTimeString();
                    }
                },
                connectBinance() {
                    if (this.ws) {
                        try { this.ws.close(); } catch (e) {}
                        this.ws = null;
                    }
                    if (this.reconnectTimer) {
                        clearTimeout(this.reconnectTimer);
                        this.reconnectTimer = null;
                    }
                    const keys = Object.keys(this.streams || {});
                    if (! keys.length) return;
                    const streams = keys.map((s) => s + '@ticker').join('/');
                    const url = 'wss://stream.binance.com:9443/stream?streams=' + streams;
                    const socket = new WebSocket(url);
                    this.ws = socket;
                    socket.onmessage = (event) => {
                        let payload;
                        try { payload = JSON.parse(event.data); } catch (e) { return; }
                        const data = payload && payload.data ? payload.data : payload;
                        if (! data || ! data.s) return;
                        const map = this.streams[String(data.s).toLowerCase()];
                        if (! map) return;
                        const digits = map.digits || 2;
                        let bid = Number(data.b);
                        let ask = Number(data.a);
                        const last = Number(data.c);
                        if (! (bid > 0) || ! (ask > 0)) {
                            if (! (last > 0)) return;
                            const half = Math.max(last * 0.00005, 0.00001);
                            bid = last - half;
                            ask = last + half;
                        }
                        const high = Number(data.h);
                        const row = document.querySelector('[data-fxt-id=\"' + map.id + '\"]');
                        if (row) {
                            const askEl = row.querySelector('[data-fxt-ask]');
                            const bidEl = row.querySelector('[data-fxt-bid]');
                            const highEl = row.querySelector('[data-fxt-high]');
                            const timeEl = row.querySelector('[data-fxt-time]');
                            const dotEl = row.querySelector('[data-fxt-dot]');
                            if (askEl) askEl.textContent = this.fmt(ask, digits);
                            if (bidEl) bidEl.textContent = this.fmt(bid, digits);
                            if (highEl && high > 0) highEl.textContent = this.fmt(high, digits);
                            if (timeEl) timeEl.textContent = this.nowTime();
                            if (dotEl) dotEl.classList.add('is-on');
                        }
                        const active = document.querySelector('.fxt__grid tbody tr.is-on');
                        if (active && active.getAttribute('data-fxt-id') === map.id) {
                            const ticketAsk = document.querySelector('[data-fxt-ticket-ask]');
                            const ticketBid = document.querySelector('[data-fxt-ticket-bid]');
                            const ticketLive = document.querySelector('[data-fxt-ticket-live]');
                            if (ticketAsk) ticketAsk.textContent = this.fmt(ask, digits);
                            if (ticketBid) ticketBid.textContent = this.fmt(bid, digits);
                            if (ticketLive) {
                                ticketLive.classList.add('is-on');
                                ticketLive.textContent = this.liveLabel;
                            }
                        }
                    };
                    socket.onclose = () => {
                        if (this.ws !== socket) return;
                        this.reconnectTimer = setTimeout(() => this.connectBinance(), 2500);
                    };
                }
            }"
            x-init="connectBinance()"
            x-on:remove="if (reconnectTimer) clearTimeout(reconnectTimer); if (ws) { try { ws.close(); } catch (e) {} }"
        ></div>
        {{-- Selected market desk (Forex / Commodities / …) --}}
        <section class="fxt__desk" wire:key="fxt-desk-{{ $category }}" aria-label="{{ __('agricart.pages.fx-learn.term_desk_label', ['market' => __('agricart.pages.fx-learn.term_cat_'.$category)]) }}">
            <header class="fxt__desk-head">
                <h2 class="fxt__desk-title">{{ __('agricart.pages.fx-learn.term_desk_title', ['market' => __('agricart.pages.fx-learn.term_cat_'.$category)]) }}</h2>
            </header>

            <div class="fxt__grid-wrap" wire:key="fxt-board-{{ $category }}">
                <table class="fxt__grid" aria-label="{{ __('agricart.pages.fx-learn.term_watch_label') }}">
                    <thead>
                        <tr>
                            <th>{{ __('agricart.pages.fx-learn.term_col_symbol') }}</th>
                            <th>{{ __('agricart.pages.fx-learn.term_ask') }}</th>
                            <th>{{ __('agricart.pages.fx-learn.term_bid') }}</th>
                            <th>{{ __('agricart.pages.fx-learn.term_card_high') }}</th>
                            <th>{{ __('agricart.pages.fx-learn.term_card_time') }}</th>
                            <th>{{ __('agricart.pages.fx-learn.term_col_trade') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($deskRows as $row)
                            @php
                                $fmt = static fn ($v) => $v !== null ? number_format((float) $v, $row['digits'], '.', '') : '—';
                            @endphp
                            <tr
                                wire:key="fxt-row-{{ $row['id'] }}"
                                data-fxt-id="{{ $row['id'] }}"
                                @class(['is-on' => $symbol === $row['id']])
                                wire:click="setSymbol(@js($row['id']))"
                            >
                                <td class="fxt__grid-sym" dir="ltr">
                                    <strong>{{ $row['label'] }}</strong>
                                    <span data-fxt-dot @class(['fxt__dot', 'is-on' => $row['live'] || filled($row['binance'])])></span>
                                </td>
                                <td class="fxt__grid-ask" data-fxt-ask dir="ltr">{{ $fmt($row['ask']) }}</td>
                                <td class="fxt__grid-bid" data-fxt-bid dir="ltr">{{ $fmt($row['bid']) }}</td>
                                <td data-fxt-high dir="ltr">{{ $fmt($row['high']) }}</td>
                                <td class="fxt__grid-time" data-fxt-time dir="ltr">{{ $row['time'] }}</td>
                                <td class="fxt__grid-actions" wire:click.stop>
                                    <button
                                        type="button"
                                        class="fxt__grid-buy"
                                        wire:click="quickTrade(@js($row['id']), 'buy')"
                                        @disabled(! empty($dash['failed_day']) || ! empty($dash['failed_total']) || ! $this->canTrade())
                                    >BUY</button>
                                    <button
                                        type="button"
                                        class="fxt__grid-sell"
                                        wire:click="quickTrade(@js($row['id']), 'sell')"
                                        @disabled(! empty($dash['failed_day']) || ! empty($dash['failed_total']) || ! $this->canTrade())
                                    >SELL</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        <div class="fxt__stats" role="group" aria-label="{{ __('agricart.pages.fx-learn.term_stats_label') }}">
            <div class="fxt__stat">
                <span>{{ __('agricart.pages.fx-learn.term_balance') }}</span>
                <strong>${{ number_format((float) ($dash['balance'] ?? 0), 2) }}</strong>
            </div>
            <div class="fxt__stat">
                <span>{{ __('agricart.pages.fx-learn.term_equity') }}</span>
                <strong>${{ number_format((float) ($dash['equity'] ?? 0), 2) }}</strong>
            </div>
            <div class="fxt__stat">
                <span>{{ __('agricart.pages.fx-learn.term_floating') }}</span>
                <strong @class(['is-pos' => ($dash['floating'] ?? 0) > 0, 'is-neg' => ($dash['floating'] ?? 0) < 0])>
                    ${{ number_format((float) ($dash['floating'] ?? 0), 2) }}
                </strong>
            </div>
            <div class="fxt__stat">
                <span>{{ __('agricart.pages.fx-learn.term_day_pnl') }}</span>
                <strong @class(['is-pos' => ($dash['day_pnl'] ?? 0) > 0, 'is-neg' => ($dash['day_pnl'] ?? 0) < 0])>
                    ${{ number_format((float) ($dash['day_pnl'] ?? 0), 2) }}
                </strong>
            </div>
            <div @class(['fxt__stat', 'is-fail' => ! empty($dash['failed_day'])])>
                <span>{{ __('agricart.pages.fx-learn.term_day_loss') }}</span>
                <strong>{{ number_format((float) ($dash['day_loss_pct'] ?? 0), 2) }}% / {{ $maxDayLossPct }}%</strong>
            </div>
            <div @class(['fxt__stat', 'is-fail' => ! empty($dash['failed_total'])])>
                <span>{{ __('agricart.pages.fx-learn.term_total_dd') }}</span>
                <strong>{{ number_format((float) ($dash['total_loss_pct'] ?? 0), 2) }}% / {{ $maxTotalLossPct }}%</strong>
            </div>
            <div class="fxt__stat fxt__stat--phase">
                <span>{{ __('agricart.pages.fx-learn.term_phase_left') }}</span>
                <strong>${{ number_format((float) ($dash['phase_remaining_usd'] ?? 0), 2) }}</strong>
                <div class="fxt__bar"><span style="width: {{ number_format((float) ($dash['phase_progress_pct'] ?? 0), 1) }}%"></span></div>
            </div>
        </div>

        @if (! empty($dash['failed_day']) || ! empty($dash['failed_total']))
            <div class="fxt__alert">{{ __('agricart.pages.fx-learn.term_fail_banner') }}</div>
        @endif

        {{-- Order ticket: one horizontal strip --}}
        <section class="fxt__ticket fxt__ticket--bar" aria-label="{{ __('agricart.pages.fx-learn.term_ticket_label') }}">
            <div class="fxt__ticket-sym" dir="ltr">
                <span>{{ __('agricart.pages.fx-learn.term_col_symbol') }}</span>
                <strong>{{ $label }}</strong>
            </div>
            <div class="fxt__ticket-px" dir="ltr">
                <div>
                    <span>{{ __('agricart.pages.fx-learn.term_bid') }}</span>
                    <strong class="is-bid" data-fxt-ticket-bid>{{ $q ? number_format((float) $q['bid'], $digits, '.', '') : '—' }}</strong>
                </div>
                <div>
                    <span>{{ __('agricart.pages.fx-learn.term_ask') }}</span>
                    <strong class="is-ask" data-fxt-ticket-ask>{{ $q ? number_format((float) $q['ask'], $digits, '.', '') : '—' }}</strong>
                </div>
                <span data-fxt-ticket-live @class(['fxt__live', 'is-on' => ! empty($q['live']) || filled($q['binance'] ?? null)])>
                    {{ (! empty($q['live']) || filled($q['binance'] ?? null)) ? __('agricart.pages.fx-learn.term_live') : __('agricart.pages.fx-learn.term_seed') }}
                </span>
            </div>
            <label class="fxt__ticket-field">
                <span>{{ __('agricart.pages.fx-learn.term_lots') }}</span>
                <input type="number" step="0.01" min="0.01" max="10" wire:model.live="lots" />
            </label>
            <label class="fxt__ticket-field">
                <span>{{ __('agricart.pages.fx-learn.term_sl') }}</span>
                <input type="number" step="any" wire:model.live="stopLoss" placeholder="—" dir="ltr" />
            </label>
            <label class="fxt__ticket-field">
                <span>{{ __('agricart.pages.fx-learn.term_tp') }}</span>
                <input type="number" step="any" wire:model.live="takeProfit" placeholder="—" dir="ltr" />
            </label>
            <div @class(['fxt__ticket-risk', 'is-fail' => ! empty($risk['fail'])])>
                <span>{{ __('agricart.pages.fx-learn.term_risk_pct') }}</span>
                <strong>{{ number_format((float) ($risk['pct'] ?? 0), 2) }}%</strong>
                <span>R:R {{ isset($risk['rr']) && $risk['rr'] !== null ? number_format((float) $risk['rr'], 2) : '—' }}</span>
            </div>
            <div class="fxt__ticket-actions">
                <button
                    type="button"
                    class="fxt__submit fxt__submit--buy"
                    wire:click="openTradeAs('buy')"
                    @disabled(! empty($dash['failed_day']) || ! empty($dash['failed_total']) || ! empty($risk['fail']) || ! $this->canTrade())
                >{{ __('agricart.pages.fx-learn.term_place_buy') }}</button>
                <button
                    type="button"
                    class="fxt__submit fxt__submit--sell"
                    wire:click="openTradeAs('sell')"
                    @disabled(! empty($dash['failed_day']) || ! empty($dash['failed_total']) || ! empty($risk['fail']) || ! $this->canTrade())
                >{{ __('agricart.pages.fx-learn.term_place_sell') }}</button>
            </div>
        </section>

        {{-- Positions / history under ticket --}}
        <section class="fxt__book" aria-label="{{ __('agricart.pages.fx-learn.term_book_label') }}">
            <div class="fxt__tabs">
                <button type="button" wire:click="$set('historyTab', 'open')" @class(['is-active' => $historyTab === 'open'])>
                    {{ __('agricart.pages.fx-learn.term_tab_open') }} ({{ count($dash['open'] ?? []) }})
                </button>
                <button type="button" wire:click="$set('historyTab', 'history')" @class(['is-active' => $historyTab === 'history'])>
                    {{ __('agricart.pages.fx-learn.term_tab_history') }}
                </button>
            </div>

            @if ($historyTab === 'open')
                <div class="fxt__table-wrap">
                    <table class="fxt__table">
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
                                <tr wire:key="fxt-open-{{ $row['id'] }}">
                                    <td dir="ltr">{{ $row['label'] }}</td>
                                    <td class="is-{{ $row['side'] }}">{{ strtoupper($row['side']) }}</td>
                                    <td>{{ number_format((float) $row['lots'], 2) }}</td>
                                    <td dir="ltr">{{ number_format((float) $row['entry'], (int) $row['digits'], '.', '') }}</td>
                                    <td dir="ltr">{{ $row['sl'] !== null ? number_format((float) $row['sl'], (int) $row['digits'], '.', '') : '—' }}</td>
                                    <td dir="ltr">{{ $row['tp'] !== null ? number_format((float) $row['tp'], (int) $row['digits'], '.', '') : '—' }}</td>
                                    <td @class(['is-pos' => $row['pnl'] > 0, 'is-neg' => $row['pnl'] < 0])>${{ number_format((float) $row['pnl'], 2) }}</td>
                                    <td>
                                        <button type="button" class="fxt__mini" wire:click="closeTrade({{ (int) $row['id'] }})">
                                            {{ __('agricart.pages.fx-learn.term_close') }}
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="fxt__empty">{{ __('agricart.pages.fx-learn.term_no_open') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="fxt__table-wrap">
                    <table class="fxt__table">
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
                                <tr wire:key="fxt-hist-{{ $row['id'] }}">
                                    <td dir="ltr">{{ $row['label'] }}</td>
                                    <td class="is-{{ $row['side'] }}">{{ strtoupper($row['side']) }}</td>
                                    <td>{{ number_format((float) $row['lots'], 2) }}</td>
                                    <td dir="ltr">{{ number_format((float) $row['entry'], (int) $row['digits'], '.', '') }}</td>
                                    <td dir="ltr">{{ number_format((float) $row['exit'], (int) $row['digits'], '.', '') }}</td>
                                    <td @class(['is-pos' => $row['pnl'] > 0, 'is-neg' => $row['pnl'] < 0])>${{ number_format((float) $row['pnl'], 2) }}</td>
                                    <td>{{ $row['closed_at'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="fxt__empty">{{ __('agricart.pages.fx-learn.term_no_history') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        {{-- Chart 50% + Technical Analysis (strong buy/sell) 50% --}}
        <section class="fxt__markets" aria-label="{{ __('agricart.pages.fx-learn.term_markets_label') }}">
            <div class="fxt__panel">
                <div class="fxt__panel-head">
                    <div>
                        <p class="fxt__kicker">{{ __('agricart.pages.fx-learn.term_chart_kicker') }}</p>
                        <h2 class="fxt__panel-title" dir="ltr">{{ $label }}</h2>
                    </div>
                    <div class="fxt__tf" role="group" aria-label="{{ __('agricart.pages.fx-learn.term_tf_label') }}">
                        @foreach (['1' => '1m', '5' => '5m', '15' => '15m', '60' => '1H', '240' => '4H', 'D' => '1D'] as $id => $tfLabel)
                            <button
                                type="button"
                                wire:click="setChartInterval(@js($id))"
                                @class(['fxt__tf-btn', 'is-on' => $chartInterval === $id])
                            >{{ $tfLabel }}</button>
                        @endforeach
                    </div>
                </div>
                <div
                    class="fxt__chart"
                    dir="ltr"
                    wire:key="fxt-chart-{{ $tvSymbol }}-{{ $chartInterval }}"
                    wire:replace
                    x-init="mountTv($el, 'https://s3.tradingview.com/external-embedding/embed-widget-advanced-chart.js', {
                        autosize: true,
                        symbol: @js($tvSymbol),
                        interval: @js($chartInterval),
                        timezone: 'Asia/Karachi',
                        theme: 'light',
                        style: '1',
                        locale: 'en',
                        allow_symbol_change: false,
                        calendar: false,
                        support_host: 'https://www.tradingview.com',
                        height: 420,
                        hide_top_toolbar: false,
                        hide_legend: false,
                        save_image: false
                    })"
                ></div>
            </div>

            <div class="fxt__panel">
                <div class="fxt__panel-head">
                    <div>
                        <p class="fxt__kicker">{{ __('agricart.pages.fx-learn.term_ta_kicker') }}</p>
                        <h2 class="fxt__panel-title">{{ __('agricart.pages.fx-learn.term_ta_title') }}</h2>
                        <p class="fxt__panel-sub">{{ __('agricart.pages.fx-learn.term_ta_hint') }}</p>
                    </div>
                </div>
                <div
                    class="fxt__ta"
                    dir="ltr"
                    wire:key="fxt-ta-{{ $tvSymbol }}-{{ $taInterval }}"
                    wire:replace
                    x-init="mountTv($el, 'https://s3.tradingview.com/external-embedding/embed-widget-technical-analysis.js', {
                        interval: @js($taInterval),
                        width: '100%',
                        isTransparent: true,
                        height: 420,
                        symbol: @js($tvSymbol),
                        showIntervalTabs: true,
                        displayMode: 'single',
                        locale: 'en',
                        colorTheme: 'light'
                    })"
                ></div>
            </div>
        </section>
        </section>{{-- /.fxt__desk --}}

        <div class="fxt__page-tools fxt__page-tools--foot">
            <button type="button" class="fxt__btn" wire:click="$toggle('showSettings')">
                {{ __('agricart.pages.fx-learn.term_settings') }}
            </button>
            <button type="button" class="fxt__btn" wire:click="refreshQuotes" wire:loading.attr="disabled">
                {{ __('agricart.pages.fx-learn.term_refresh') }}
            </button>
        </div>

        @if ($showSettings)
            <div class="fxt__modal" role="dialog" aria-modal="true">
                <div class="fxt__modal-backdrop" wire:click="$set('showSettings', false)"></div>
                <div class="fxt__modal-card">
                    <div class="fxt__modal-head">
                        <h2>{{ __('agricart.pages.fx-learn.term_settings') }}</h2>
                        <button type="button" wire:click="$set('showSettings', false)">×</button>
                    </div>
                    <p class="fxt__modal-hint">{{ __('agricart.pages.fx-learn.term_settings_hint') }}</p>
                    <div class="fxt__settings-grid">
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
                    <div class="fxt__modal-foot">
                        <button type="button" class="fxt__btn" wire:click="saveSettings">{{ __('agricart.pages.fx-learn.term_save_rules') }}</button>
                        <button
                            type="button"
                            class="fxt__btn fxt__btn--danger"
                            wire:click="resetAccount"
                            wire:confirm="{{ __('agricart.pages.fx-learn.term_reset_confirm') }}"
                        >{{ __('agricart.pages.fx-learn.term_reset_balance') }}</button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
