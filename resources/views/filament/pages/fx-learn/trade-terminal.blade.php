@php
    $q = $quotes[$symbol] ?? null;
    $digits = (int) ($q['digits'] ?? 5);
    $risk = $this->riskPreview;
    $history = $this->historyRows(app(\App\Services\FxLearn\DemoTerminalService::class));
    $tvSymbol = $this->activeTvSymbol();
    $label = $this->activeLabel();
    $taInterval = $this->taInterval();
    $deskRows = $this->categorySymbolRows();
    $tfRange = $this->activeTfRange();
    $tfFmt = static fn ($v, int $digits) => $v !== null ? number_format((float) $v, $digits, '.', '') : '—';
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
                        'custom' => __('agricart.pages.fx-learn.term_cat_custom'),
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

                <div class="fxt__desk-range" wire:key="fxt-desk-range-{{ $symbol }}-{{ $chartInterval }}">
                    <div class="fxt__tf fxt__tf--desk" role="group" aria-label="{{ __('agricart.pages.fx-learn.term_tf_label') }}">
                        @foreach ($this->chartIntervalOptions() as $id => $tfLabel)
                            <button
                                type="button"
                                wire:click="setChartInterval(@js($id))"
                                @class(['fxt__tf-btn', 'is-on' => $chartInterval === $id])
                            >{{ $tfLabel }}</button>
                        @endforeach
                    </div>
                    <div class="fxt__hl" aria-label="{{ __('agricart.pages.fx-learn.term_tf_range_label', ['tf' => $tfRange['tf'], 'symbol' => $tfRange['label']]) }}">
                        <div class="fxt__hl-box fxt__hl-box--low">
                            <span>{{ __('agricart.pages.fx-learn.term_tf_range_low') }}</span>
                            <strong dir="ltr">{{ $tfFmt($tfRange['low'], (int) $tfRange['digits']) }}</strong>
                        </div>
                        <div class="fxt__hl-box fxt__hl-box--center">
                            <span>{{ __('agricart.pages.fx-learn.term_tf_range_center') }}</span>
                            <strong dir="ltr">{{ $tfFmt($tfRange['center'] ?? null, (int) $tfRange['digits']) }}</strong>
                        </div>
                        <div class="fxt__hl-box fxt__hl-box--high">
                            <span>{{ __('agricart.pages.fx-learn.term_tf_range_high') }}</span>
                            <strong dir="ltr">{{ $tfFmt($tfRange['high'], (int) $tfRange['digits']) }}</strong>
                        </div>
                    </div>
                </div>
            </header>

            @if ($category === 'custom')
                @php
                    $pickerOpts = $this->customPickerOptions();
                    $pickerGroups = collect($pickerOpts)->groupBy('group');
                @endphp
                <div class="fxt__custom-panel" wire:click.stop>
                    {{-- ignore.self keeps the dropdown open while checkbox rows morph after toggle --}}
                    <details class="fxt__custom-picker" wire:ignore.self>
                        <summary>{{ __('agricart.pages.fx-learn.term_custom_pick') }}</summary>
                        <div class="fxt__custom-picker-menu" role="group" aria-label="{{ __('agricart.pages.fx-learn.term_custom_pick') }}">
                            @foreach ($pickerGroups as $groupLabel => $groupRows)
                                <p class="fxt__custom-picker-group">{{ $groupLabel }}</p>
                                @foreach ($groupRows as $opt)
                                    <label class="fxt__custom-picker-row" wire:key="fxt-pick-{{ $opt['id'] }}">
                                        <input
                                            type="checkbox"
                                            @checked($opt['on'])
                                            wire:click.stop="toggleCustomSymbol(@js($opt['id']))"
                                        >
                                        <span dir="ltr">{{ $opt['label'] }}</span>
                                    </label>
                                @endforeach
                            @endforeach
                        </div>
                    </details>
                </div>
            @endif

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
                            @forelse ($deskRows as $row)
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
                            @empty
                                <tr>
                                    <td colspan="6" class="fxt__empty">{{ __('agricart.pages.fx-learn.term_custom_empty') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Center: lots / SL / TP for selected symbol --}}
                <aside class="fxt__ticket fxt__ticket--rail" aria-label="{{ __('agricart.pages.fx-learn.term_ticket_label') }}">
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
                </aside>

                <aside class="fxt__panel fxt__panel--gauge" aria-label="{{ __('agricart.pages.fx-learn.term_ta_title') }}">
                    <div class="fxt__panel-head">
                        <div>
                            <p class="fxt__kicker">{{ __('agricart.pages.fx-learn.term_ta_kicker') }}</p>
                            <h2 class="fxt__panel-title" dir="ltr">{{ $label }}</h2>
                        </div>
                        <div class="fxt__tf" role="group" aria-label="{{ __('agricart.pages.fx-learn.term_ta_tf_label') }}">
                            @foreach ($this->chartIntervalOptions() as $id => $tfLabel)
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
                            height: 280,
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
        @php
            $openRows = $dash['open'] ?? [];
            $openCount = count($openRows);
            $floatPnl = (float) ($dash['floating'] ?? 0);
            $histNet = collect($history)->sum(fn ($r) => (float) ($r['pnl'] ?? 0));
            $histWins = collect($history)->filter(fn ($r) => (float) ($r['pnl'] ?? 0) > 0)->count();
            $histLosses = collect($history)->filter(fn ($r) => (float) ($r['pnl'] ?? 0) < 0)->count();
        @endphp
        <section class="fxt__book" aria-label="{{ __('agricart.pages.fx-learn.term_book_label') }}">
            <div class="fxt__book-head">
                <div class="fxt__tabs">
                    <button type="button" wire:click="$set('historyTab', 'open')" @class(['is-active' => $historyTab === 'open'])>
                        {{ __('agricart.pages.fx-learn.term_tab_open') }} ({{ $openCount }})
                    </button>
                    <button type="button" wire:click="$set('historyTab', 'history')" @class(['is-active' => $historyTab === 'history'])>
                        {{ __('agricart.pages.fx-learn.term_tab_history') }} ({{ count($history) }})
                    </button>
                </div>
                <div class="fxt__book-summary" role="status">
                    @if ($historyTab === 'open')
                        <span class="fxt__book-chip">{{ __('agricart.pages.fx-learn.term_book_open_count', ['n' => $openCount]) }}</span>
                        <span @class(['fxt__book-chip', 'is-pos' => $floatPnl > 0, 'is-neg' => $floatPnl < 0])>
                            {{ __('agricart.pages.fx-learn.term_book_float') }}
                            <strong dir="ltr">${{ number_format($floatPnl, 2) }}</strong>
                        </span>
                    @else
                        <span class="fxt__book-chip">{{ __('agricart.pages.fx-learn.term_book_closed_count', ['n' => count($history)]) }}</span>
                        <span class="fxt__book-chip">{{ __('agricart.pages.fx-learn.term_book_wins') }} <strong>{{ $histWins }}</strong></span>
                        <span class="fxt__book-chip">{{ __('agricart.pages.fx-learn.term_book_losses') }} <strong>{{ $histLosses }}</strong></span>
                        <span @class(['fxt__book-chip', 'is-pos' => $histNet > 0, 'is-neg' => $histNet < 0])>
                            {{ __('agricart.pages.fx-learn.term_book_net') }}
                            <strong dir="ltr">${{ number_format($histNet, 2) }}</strong>
                        </span>
                    @endif
                </div>
            </div>

            @if ($historyTab === 'open')
                <div class="fxt__table-wrap">
                    <table class="fxt__table fxt__table--book">
                        <thead>
                            <tr>
                                <th>{{ __('agricart.pages.fx-learn.term_col_ticket') }}</th>
                                <th>{{ __('agricart.pages.fx-learn.term_col_symbol') }}</th>
                                <th>{{ __('agricart.pages.fx-learn.term_col_side') }}</th>
                                <th>{{ __('agricart.pages.fx-learn.term_lots') }}</th>
                                <th>{{ __('agricart.pages.fx-learn.term_col_entry') }}</th>
                                <th>{{ __('agricart.pages.fx-learn.term_col_current') }}</th>
                                <th>SL</th>
                                <th>TP</th>
                                <th>{{ __('agricart.pages.fx-learn.term_col_pnl') }}</th>
                                <th>{{ __('agricart.pages.fx-learn.term_col_opened') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($openRows as $row)
                                @php $d = (int) ($row['digits'] ?? 5); @endphp
                                <tr wire:key="fxt-open-{{ $row['id'] }}">
                                    <td class="fxt__mono" dir="ltr">{{ (int) $row['id'] }}</td>
                                    <td class="fxt__sym" dir="ltr">{{ $row['label'] }}</td>
                                    <td><span @class(['fxt__side', 'is-'.$row['side']])>{{ strtoupper((string) $row['side']) }}</span></td>
                                    <td dir="ltr">{{ number_format((float) $row['lots'], 2) }}</td>
                                    <td dir="ltr">{{ number_format((float) $row['entry'], $d, '.', '') }}</td>
                                    <td dir="ltr">{{ isset($row['current']) && $row['current'] !== null ? number_format((float) $row['current'], $d, '.', '') : '—' }}</td>
                                    <td dir="ltr">{{ $row['sl'] !== null ? number_format((float) $row['sl'], $d, '.', '') : '—' }}</td>
                                    <td dir="ltr">{{ $row['tp'] !== null ? number_format((float) $row['tp'], $d, '.', '') : '—' }}</td>
                                    <td @class(['is-pos' => $row['pnl'] > 0, 'is-neg' => $row['pnl'] < 0, 'fxt__pnl']) dir="ltr">${{ number_format((float) $row['pnl'], 2) }}</td>
                                    <td class="fxt__when" dir="ltr">{{ $row['opened_at'] }}</td>
                                    <td class="fxt__act">
                                        <button type="button" class="fxt__mini" wire:click="closeTrade({{ (int) $row['id'] }})">
                                            {{ __('agricart.pages.fx-learn.term_close') }}
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="11" class="fxt__empty">{{ __('agricart.pages.fx-learn.term_no_open') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="fxt__table-wrap">
                    <table class="fxt__table fxt__table--book">
                        <thead>
                            <tr>
                                <th>{{ __('agricart.pages.fx-learn.term_col_ticket') }}</th>
                                <th>{{ __('agricart.pages.fx-learn.term_col_symbol') }}</th>
                                <th>{{ __('agricart.pages.fx-learn.term_col_side') }}</th>
                                <th>{{ __('agricart.pages.fx-learn.term_lots') }}</th>
                                <th>{{ __('agricart.pages.fx-learn.term_col_entry') }}</th>
                                <th>{{ __('agricart.pages.fx-learn.term_col_exit') }}</th>
                                <th>SL</th>
                                <th>TP</th>
                                <th>{{ __('agricart.pages.fx-learn.term_col_pnl') }}</th>
                                <th>{{ __('agricart.pages.fx-learn.term_col_reason') }}</th>
                                <th>{{ __('agricart.pages.fx-learn.term_col_duration') }}</th>
                                <th>{{ __('agricart.pages.fx-learn.term_col_when') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($history as $row)
                                @php $d = (int) ($row['digits'] ?? 5); @endphp
                                <tr wire:key="fxt-hist-{{ $row['id'] }}">
                                    <td class="fxt__mono" dir="ltr">{{ (int) $row['id'] }}</td>
                                    <td class="fxt__sym" dir="ltr">{{ $row['label'] }}</td>
                                    <td><span @class(['fxt__side', 'is-'.$row['side']])>{{ strtoupper((string) $row['side']) }}</span></td>
                                    <td dir="ltr">{{ number_format((float) $row['lots'], 2) }}</td>
                                    <td dir="ltr">{{ number_format((float) $row['entry'], $d, '.', '') }}</td>
                                    <td dir="ltr">{{ number_format((float) $row['exit'], $d, '.', '') }}</td>
                                    <td dir="ltr">{{ $row['sl'] !== null ? number_format((float) $row['sl'], $d, '.', '') : '—' }}</td>
                                    <td dir="ltr">{{ $row['tp'] !== null ? number_format((float) $row['tp'], $d, '.', '') : '—' }}</td>
                                    <td @class(['is-pos' => $row['pnl'] > 0, 'is-neg' => $row['pnl'] < 0, 'fxt__pnl']) dir="ltr">${{ number_format((float) $row['pnl'], 2) }}</td>
                                    <td><span @class(['fxt__reason', 'is-'.($row['reason'] ?? 'manual')])>{{ $row['reason_label'] ?? '—' }}</span></td>
                                    <td dir="ltr">{{ $row['duration'] ?? '—' }}</td>
                                    <td class="fxt__when" dir="ltr">{{ $row['closed_at'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="12" class="fxt__empty">{{ __('agricart.pages.fx-learn.term_no_history') }}</td></tr>
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
                        @foreach ($this->chartIntervalOptions() as $id => $tfLabel)
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
                        height: 540,
                        width: '100%',
                        hide_top_toolbar: false,
                        hide_legend: false,
                        save_image: false
                    })"
                ></div>
            </div>
        </section>
        </section>{{-- /.fxt__desk --}}
    </div>
</x-filament-panels::page>
