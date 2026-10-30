@php
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
        wire:poll.15s.visible="syncQuotes"
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
                    // bookTicker = best bid/ask on every change (fastest free official stream, browser-only).
                    const streams = keys.map((s) => s + '@bookTicker').join('/');
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
                        const bid = Number(data.b);
                        const ask = Number(data.a);
                        if (! (bid > 0) || ! (ask > 0)) return;
                        const row = document.querySelector('[data-fxt-id=\"' + map.id + '\"]');
                        if (row) {
                            const askEl = row.querySelector('[data-fxt-ask]');
                            const bidEl = row.querySelector('[data-fxt-bid]');
                            const timeEl = row.querySelector('[data-fxt-time]');
                            const dotEl = row.querySelector('[data-fxt-dot]');
                            if (askEl) askEl.textContent = this.fmt(ask, digits);
                            if (bidEl) bidEl.textContent = this.fmt(bid, digits);
                            if (timeEl) timeEl.textContent = this.nowTime();
                            if (dotEl) dotEl.classList.add('is-on');
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
        {{-- Selected market desk — category chips sit with the rates board --}}
        <section class="fxt__desk" wire:key="fxt-desk-{{ $category }}" aria-label="{{ __('agricart.pages.fx-learn.term_desk_label', ['market' => __('agricart.pages.fx-learn.term_cat_'.$category)]) }}">
            <header class="fxt__desk-head">
                <div
                    class="fxt__desk-cats agricart-fxt-cats"
                    role="tablist"
                    aria-label="{{ __('agricart.pages.fx-learn.term_cats_label') }}"
                >
                    @foreach ([
                        'forex' => __('agricart.pages.fx-learn.term_cat_forex'),
                        'commodities' => __('agricart.pages.fx-learn.term_cat_commodities'),
                        'indices' => __('agricart.pages.fx-learn.term_cat_indices'),
                        'crypto' => __('agricart.pages.fx-learn.term_cat_crypto'),
                    ] as $catId => $catLabel)
                        <button
                            type="button"
                            role="tab"
                            wire:click="setCategory('{{ $catId }}')"
                            class="agricart-fxt-cats__btn"
                            aria-selected="{{ $category === $catId ? 'true' : 'false' }}"
                            @class(['is-on' => $category === $catId])
                        >{{ $catLabel }}</button>
                    @endforeach
                </div>
            </header>

            {{-- Rates grid + TradingView TA gauge (official embed — click symbol / pick TF) --}}
            <div class="fxt__board">
                <div class="fxt__grid-wrap" wire:key="fxt-board-grid-{{ $category }}">
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

                <aside class="fxt__panel fxt__panel--gauge" aria-label="{{ __('agricart.pages.fx-learn.term_ta_title') }}">
                    <div class="fxt__panel-head">
                        <div>
                            <p class="fxt__kicker">{{ __('agricart.pages.fx-learn.term_ta_kicker') }}</p>
                            <h2 class="fxt__panel-title" dir="ltr">{{ $label }}</h2>
                            <p class="fxt__panel-sub">{{ __('agricart.pages.fx-learn.term_ta_hint') }}</p>
                        </div>
                        <div class="fxt__tf" role="group" aria-label="{{ __('agricart.pages.fx-learn.term_ta_tf_label') }}">
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
                        class="fxt__ta fxt__ta--desk"
                        dir="ltr"
                        wire:key="fxt-ta-desk-{{ $tvSymbol }}-{{ $taInterval }}"
                        wire:replace
                        x-init="mountTv($el, 'https://s3.tradingview.com/external-embedding/embed-widget-technical-analysis.js', {
                            interval: @js($taInterval),
                            width: '100%',
                            isTransparent: true,
                            height: 380,
                            symbol: @js($tvSymbol),
                            showIntervalTabs: false,
                            displayMode: 'single',
                            locale: 'en',
                            colorTheme: 'light'
                        })"
                    ></div>
                </aside>
            </div>

        @if (! empty($dash['failed_day']) || ! empty($dash['failed_total']))
            <div class="fxt__alert">{{ __('agricart.pages.fx-learn.term_fail_banner') }}</div>
        @endif

        {{-- Positions / history under rates board --}}
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

        {{-- Full-width chart (same symbol + timeframe as the desk gauge) --}}
        <section class="fxt__markets fxt__markets--chart" aria-label="{{ __('agricart.pages.fx-learn.term_chart_label') }}">
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
