@php
    $calendar = app(\App\Services\FxLearn\EconomicCalendarService::class);
    $rows = $this->filteredEvents();
    $filteredRadar = $this->filteredRadarEvent($calendar);
    $routineRadar = $this->routineRadarEvent($calendar);
    $clocks = $this->sessionClocks();
    $leftClocks = collect($clocks)->where('side', 'left')->values();
    $rightClocks = collect($clocks)->where('side', 'right')->values();
    $pk = $this->pakistanClock();
    $filteredAt = (int) ($filteredRadar['at'] ?? 0);
    $routineAt = (int) ($routineRadar['at'] ?? 0);
    $metaFrom = (string) ($this->feedMeta['from'] ?? '—');
    $metaTo = (string) ($this->feedMeta['to'] ?? '—');
    $metaStatus = $this->feedMeta['http_status'] ?? null;
    $cred = $this->credentialStatus;
@endphp

<x-filament-panels::page>
    <div class="fxr">
        {{-- Status / debug banner --}}
        @if (filled($this->feedError) || $this->usingDemo)
            <div @class(['fxr__banner', 'is-demo' => $this->usingDemo, 'is-error' => filled($this->feedError) && ! $this->usingDemo])>
                <div class="fxr__banner-copy">
                    @if ($this->usingDemo)
                        <strong>{{ __('agricart.pages.fx-learn.feed_demo_title') }}</strong>
                        <span>{{ $this->feedError ?? __('agricart.pages.fx-learn.feed_demo_body') }}</span>
                    @else
                        <strong>{{ __('agricart.pages.fx-learn.feed_error_title') }}</strong>
                        <span>{{ $this->feedError }}</span>
                    @endif
                    <span class="fxr__banner-meta" dir="ltr">
                        {{ __('agricart.pages.fx-learn.feed_range', ['from' => $metaFrom, 'to' => $metaTo]) }}
                        @if ($metaStatus)
                            · HTTP {{ $metaStatus }}
                        @endif
                        · {{ __('agricart.pages.fx-learn.feed_token_source', ['source' => $cred['source'] ?? 'none']) }}
                    </span>
                </div>
                <button type="button" class="fxr__btn fxr__btn--primary" wire:click="openSettings">
                    {{ __('agricart.pages.fx-learn.settings') }}
                </button>
            </div>
        @endif

        {{-- 4 session clocks + center Pakistan clock + next-data line --}}
        <section class="fxr__deck" aria-label="{{ __('agricart.pages.fx-learn.deck_label') }}">
            <div class="fxr__clocks fxr__clocks--left" aria-label="{{ __('agricart.pages.fx-learn.sessions_label') }}">
                @foreach ($leftClocks as $clock)
                    @include('filament.pages.fx-learn.partials.econ-clock', ['clock' => $clock])
                @endforeach
            </div>

            <div class="fxr__hub" aria-label="{{ __('agricart.pages.fx-learn.pk_hub_label') }}">
                {{-- Filtered strip (matches toolbar dropdowns) --}}
                @if ($filteredRadar)
                    <div
                        class="fxr__eta fxr__eta--filter"
                        wire:key="fxr-eta-f-{{ $this->ccyFilter }}-{{ $this->impactFilter }}-{{ md5($this->search.'|'.$filteredAt) }}"
                        x-data="fxrEtaLine(@js([
                            'atMs' => $filteredAt,
                            'title' => (string) ($filteredRadar['title'] ?? ''),
                            'ccy' => (string) ($filteredRadar['ccy'] ?? ''),
                            'impact' => (string) ($filteredRadar['impact'] ?? ''),
                            'flag' => $this->flagForCcy((string) ($filteredRadar['ccy'] ?? '')),
                            'prefix' => __('agricart.pages.fx-learn.eta_filtered_prefix'),
                        ]))"
                        x-init="boot()"
                    >
                        <span class="fxr__eta-flag" x-text="flag" aria-hidden="true"></span>
                        <span class="fxr__eta-text" dir="ltr">
                            <span x-text="prefix"></span>
                            <strong x-text="countdown"></strong>
                            <span class="fxr__eta-sep">·</span>
                            <span x-text="ccy"></span>
                            <span x-text="title"></span>
                            <span class="fxr__eta-impact" x-text="impact"></span>
                        </span>
                    </div>
                @else
                    <div class="fxr__eta fxr__eta--filter fxr__eta--empty">
                        <span>{{ __('agricart.pages.fx-learn.eta_filtered_empty') }}</span>
                    </div>
                @endif

                <div
                    class="fxr__pk"
                    x-data="fxrPakistanClock(@js($pk['tz']))"
                    x-init="boot()"
                    x-on:beforeunload.window="destroy()"
                >
                    <div class="fxr__pk-top">
                        <span class="fxr__pk-flag" aria-hidden="true">{{ $pk['flag'] }}</span>
                        <span class="fxr__pk-label">{{ $pk['label'] }}</span>
                    </div>
                    <p class="fxr__pk-time" dir="ltr">
                        <span class="fxr__pk-hm" x-text="hm">{{ $pk['hm'] }}</span>
                        <span class="fxr__pk-sec" x-text="sec">{{ $pk['sec'] }}</span>
                    </p>
                    <p class="fxr__pk-date" dir="ltr" x-text="date">{{ $pk['date'] }}</p>
                </div>

                {{-- Routine next print (unfiltered) --}}
                @if ($routineRadar)
                    <div
                        class="fxr__eta fxr__eta--routine"
                        wire:key="fxr-eta-r-{{ $routineAt }}"
                        x-data="fxrEtaLine(@js([
                            'atMs' => $routineAt,
                            'title' => (string) ($routineRadar['title'] ?? ''),
                            'ccy' => (string) ($routineRadar['ccy'] ?? ''),
                            'impact' => (string) ($routineRadar['impact'] ?? ''),
                            'flag' => $this->flagForCcy((string) ($routineRadar['ccy'] ?? '')),
                            'prefix' => __('agricart.pages.fx-learn.eta_routine_prefix'),
                        ]))"
                        x-init="boot()"
                    >
                        <span class="fxr__eta-flag" x-text="flag" aria-hidden="true"></span>
                        <span class="fxr__eta-text" dir="ltr">
                            <span x-text="prefix"></span>
                            <strong x-text="countdown"></strong>
                            <span class="fxr__eta-sep">·</span>
                            <span x-text="ccy"></span>
                            <span x-text="title"></span>
                            <span class="fxr__eta-impact" x-text="impact"></span>
                        </span>
                    </div>
                @else
                    <div class="fxr__eta fxr__eta--routine fxr__eta--empty">
                        <span>{{ __('agricart.pages.fx-learn.radar_empty') }}</span>
                    </div>
                @endif
            </div>

            <div class="fxr__clocks fxr__clocks--right" aria-label="{{ __('agricart.pages.fx-learn.sessions_label') }}">
                @foreach ($rightClocks as $clock)
                    @include('filament.pages.fx-learn.partials.econ-clock', ['clock' => $clock])
                @endforeach
            </div>
        </section>

        {{-- Economic sheet --}}
        <section class="fxr__sheet" aria-label="{{ __('agricart.pages.fx-learn.sheet_label') }}">
            <header class="fxr__sheet-head">
                <div>
                    <h2 class="fxr__sheet-title">{{ __('agricart.pages.fx-learn.sheet_title') }}</h2>
                    <p class="fxr__sheet-sub" dir="ltr">
                        {{ __('agricart.pages.fx-learn.sheet_count', ['count' => $rows->count()]) }}
                        ·
                        <span @class([
                            'fxr__feed',
                            'is-live' => in_array($this->feedSource, ['ff+fc', 'ff', 'fc', 'finnhub'], true),
                            'is-demo' => $this->feedSource === 'demo',
                        ])>
                            @if ($this->feedSource === 'ff+fc')
                                {{ __('agricart.pages.fx-learn.feed_live_ff_fc') }}
                            @elseif ($this->feedSource === 'ff')
                                {{ __('agricart.pages.fx-learn.feed_live_ff') }}
                            @elseif ($this->feedSource === 'fc')
                                {{ __('agricart.pages.fx-learn.feed_live_fc') }}
                            @elseif ($this->feedSource === 'finnhub')
                                {{ __('agricart.pages.fx-learn.feed_live') }}
                            @elseif ($this->feedSource === 'demo')
                                {{ __('agricart.pages.fx-learn.feed_demo') }}
                            @else
                                {{ __('agricart.pages.fx-learn.feed_empty') }}
                            @endif
                        </span>
                    </p>
                </div>
                <button type="button" class="fxr__btn" wire:click="refreshFeed" wire:loading.attr="disabled">
                    {{ __('agricart.pages.fx-learn.refresh') }}
                </button>
            </header>

            <div class="fxr__daybar" role="tablist" aria-label="{{ __('agricart.pages.fx-learn.day_tabs_label') }}">
                @foreach ($this->dayTabs() as $tab)
                    <button
                        type="button"
                        role="tab"
                        class="fxr__daychip"
                        wire:key="fxr-day-{{ $tab['key'] }}"
                        wire:click="setDayFilter(@js($tab['key']))"
                        aria-selected="{{ $this->dayFilter === $tab['key'] ? 'true' : 'false' }}"
                        @class(['is-on' => $this->dayFilter === $tab['key']])
                    >
                        <span>{{ $tab['label'] }}</span>
                        <span class="fxr__daychip-n" dir="ltr">{{ $tab['count'] }}</span>
                    </button>
                @endforeach
                <button
                    type="button"
                    class="fxr__daychip fxr__daychip--tool"
                    wire:click="toggleNumbersOnly"
                    @class(['is-on' => $this->numbersOnly])
                    title="{{ __('agricart.pages.fx-learn.filter_numbers_hint') }}"
                >
                    {{ __('agricart.pages.fx-learn.filter_numbers') }}
                </button>
            </div>

            <div class="fxr__table-wrap">
                <table class="fxr__table">
                    <thead>
                        <tr>
                            <th>{{ __('agricart.pages.fx-learn.col_time') }}</th>
                            <th>{{ __('agricart.pages.fx-learn.col_ccy') }}</th>
                            <th>{{ __('agricart.pages.fx-learn.col_event') }}</th>
                            <th>{{ __('agricart.pages.fx-learn.col_previous') }}</th>
                            <th>{{ __('agricart.pages.fx-learn.col_forecast') }}</th>
                            <th>{{ __('agricart.pages.fx-learn.col_actual') }}</th>
                            <th>{{ __('agricart.pages.fx-learn.col_action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $ev)
                            @php
                                $tone = $this->actualTone($ev);
                                $surprise = $this->surpriseLabel($ev);
                            @endphp
                            <tr @class(['fxr__row', 'is-high' => ($ev['impact'] ?? '') === 'high'])>
                                <td class="fxr__td-time" dir="ltr">{{ $this->formatPkt((int) $ev['at']) }}</td>
                                <td class="fxr__td-ccy" dir="ltr">
                                    <span class="fxr__flag fxr__flag--inline">{{ $this->flagForCcy((string) ($ev['ccy'] ?? '')) }}</span>
                                    {{ $ev['ccy'] ?? '' }}
                                    <span @class(['fxr__impact', 'is-'.$ev['impact']])>{{ $ev['impact'] ?? '' }}</span>
                                </td>
                                <td class="fxr__td-event">{{ $ev['title'] ?? '' }}</td>
                                <td class="fxr__td-num" dir="ltr">{{ $ev['previous'] ?? '—' }}</td>
                                <td class="fxr__td-num" dir="ltr">{{ $ev['forecast'] ?? '—' }}</td>
                                <td class="fxr__td-num" dir="ltr">
                                    <span @class(['fxr__actual', 'is-'.$tone])>{{ $ev['actual'] ?? '—' }}</span>
                                    @if ($surprise !== null)
                                        <span @class(['fxr__surprise', 'is-'.$tone]) dir="ltr">{{ $surprise }}</span>
                                    @endif
                                </td>
                                <td>
                                    <button
                                        type="button"
                                        class="fxr__btn fxr__btn--ai"
                                        wire:click="openAnalyze(@js((string) $ev['id']))"
                                    >{{ __('agricart.pages.fx-learn.analyze') }}</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="fxr__empty">{{ __('agricart.pages.fx-learn.sheet_empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- TradingView calendar widget: visual only (cannot scrape Actuals into our sheet) --}}
        <section class="fxr__tv" aria-label="{{ __('agricart.pages.fx-learn.tv_calendar_label') }}">
            <header class="fxr__sheet-head">
                <div>
                    <h2 class="fxr__sheet-title">{{ __('agricart.pages.fx-learn.tv_calendar_title') }}</h2>
                    <p class="fxr__sheet-sub">{{ __('agricart.pages.fx-learn.tv_calendar_hint') }}</p>
                </div>
                <a
                    class="fxr__btn"
                    href="https://www.tradingview.com/economic-calendar/"
                    target="_blank"
                    rel="noopener noreferrer"
                >{{ __('agricart.pages.fx-learn.visit_tv') }}</a>
            </header>
            <div class="fxr__tv-frame" dir="ltr" wire:ignore>
                <div
                    class="tradingview-widget-container"
                    style="height:480px;width:100%"
                    x-data
                    x-init="
                        if ($el.dataset.booted) return;
                        $el.dataset.booted = '1';
                        const conf = document.createElement('script');
                        conf.type = 'text/javascript';
                        conf.text = JSON.stringify({
                            width: '100%',
                            height: 480,
                            colorTheme: 'light',
                            isTransparent: true,
                            locale: 'en',
                            importanceFilter: '0,1',
                            currencyFilter: 'USD,EUR,GBP,JPY,AUD,CAD,CHF,NZD,CNY'
                        });
                        const s = document.createElement('script');
                        s.src = 'https://s3.tradingview.com/external-embedding/embed-widget-events.js';
                        s.async = true;
                        $el.appendChild(conf);
                        $el.appendChild(s);
                    "
                >
                    <div class="tradingview-widget-container__widget" style="height:480px;width:100%"></div>
                </div>
            </div>
        </section>
    </div>

    @if ($this->settingsOpen)
        <div class="fxr__modal fxr__modal--settings" role="dialog" aria-modal="true" wire:keydown.escape.window="closeSettings">
            <button type="button" class="fxr__modal-backdrop" wire:click="closeSettings" aria-label="{{ __('agricart.pages.fx-learn.close') }}"></button>
            <div class="fxr__modal-card fxr__modal-card--settings">
                <header class="fxr__modal-head">
                    <div>
                        <p class="fxr__radar-kicker">{{ __('agricart.pages.fx-learn.settings') }}</p>
                        <h2 class="fxr__modal-title">{{ __('agricart.pages.fx-learn.finnhub_settings_title') }}</h2>
                    </div>
                    <button type="button" class="fxr__btn" wire:click="closeSettings">{{ __('agricart.pages.fx-learn.close') }}</button>
                </header>

                <div class="fxr__settings-status" dir="ltr">
                    <p>
                        <strong>{{ __('agricart.pages.fx-learn.finnhub_status') }}:</strong>
                        @if (($cred['source'] ?? 'none') === 'none')
                            {{ __('agricart.pages.fx-learn.finnhub_status_none') }}
                        @elseif (! empty($cred['limited']))
                            {{ __('agricart.pages.fx-learn.finnhub_status_limited', ['masked' => $cred['masked'] ?? '••••']) }}
                        @elseif (! empty($cred['connected']))
                            {{ __('agricart.pages.fx-learn.finnhub_status_ok', ['masked' => $cred['masked'] ?? '••••']) }}
                        @else
                            {{ __('agricart.pages.fx-learn.finnhub_status_bad', ['masked' => $cred['masked'] ?? '••••']) }}
                        @endif
                    </p>
                    @if (filled($cred['message'] ?? null))
                        <p class="fxr__settings-hint">{{ $cred['message'] }}</p>
                    @endif
                    @if (filled($cred['last_tested_at'] ?? null))
                        <p class="fxr__settings-hint">{{ __('agricart.pages.fx-learn.finnhub_last_tested', ['at' => $cred['last_tested_at']]) }}</p>
                    @endif
                </div>

                <label class="fxr__field">
                    <span>{{ __('agricart.pages.fx-learn.finnhub_label') }}</span>
                    <input type="text" wire:model="settingsLabel" autocomplete="off" />
                </label>

                <label class="fxr__field">
                    <span>{{ __('agricart.pages.fx-learn.finnhub_api_key') }}</span>
                    <input
                        type="password"
                        wire:model="settingsApiKey"
                        autocomplete="new-password"
                        placeholder="{{ __('agricart.pages.fx-learn.finnhub_api_key_ph') }}"
                    />
                </label>

                <div class="fxr__modal-actions fxr__modal-actions--wrap">
                    <button
                        type="button"
                        class="fxr__btn fxr__btn--primary"
                        wire:click="saveAndTestConnection"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="saveAndTestConnection">{{ __('agricart.pages.fx-learn.finnhub_save_test') }}</span>
                        <span wire:loading wire:target="saveAndTestConnection">{{ __('agricart.pages.fx-learn.finnhub_testing') }}</span>
                    </button>

                    @if (($cred['source'] ?? 'none') === 'database')
                        <button type="button" class="fxr__btn" wire:click="testExistingConnection" wire:loading.attr="disabled">
                            {{ __('agricart.pages.fx-learn.finnhub_retest') }}
                        </button>
                        <button type="button" class="fxr__btn fxr__btn--danger" wire:click="deleteApiKey" wire:confirm="{{ __('agricart.pages.fx-learn.finnhub_delete_confirm') }}">
                            {{ __('agricart.pages.fx-learn.finnhub_delete') }}
                        </button>
                    @endif
                </div>

                @if (filled($this->settingsMessage))
                    <p @class(['fxr__msg', 'is-'.$this->settingsMessageTone])>{{ $this->settingsMessage }}</p>
                @endif

                <p class="fxr__disclaimer">{{ __('agricart.pages.fx-learn.finnhub_settings_hint') }}</p>
            </div>
        </div>
    @endif

    @if ($this->analyzeEventId)
        @php $ae = $this->analyzingEvent(); @endphp
        <div class="fxr__modal" role="dialog" aria-modal="true" wire:keydown.escape.window="closeAnalyze">
            <button type="button" class="fxr__modal-backdrop" wire:click="closeAnalyze" aria-label="{{ __('agricart.pages.fx-learn.close') }}"></button>
            <div class="fxr__modal-card">
                <header class="fxr__modal-head">
                    <div>
                        <p class="fxr__radar-kicker">{{ __('agricart.pages.fx-learn.analyze') }}</p>
                        <h2 class="fxr__modal-title">{{ $ae['title'] ?? '' }}</h2>
                    </div>
                    <button type="button" class="fxr__btn" wire:click="closeAnalyze">{{ __('agricart.pages.fx-learn.close') }}</button>
                </header>

                @if ($ae)
                    <dl class="fxr__radar-metrics fxr__radar-metrics--modal" dir="ltr">
                        <div>
                            <dt>{{ __('agricart.pages.fx-learn.col_previous') }}</dt>
                            <dd>{{ $ae['previous'] ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('agricart.pages.fx-learn.col_forecast') }}</dt>
                            <dd>{{ $ae['forecast'] ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('agricart.pages.fx-learn.col_actual') }}</dt>
                            <dd>{{ $ae['actual'] ?? '—' }}</dd>
                        </div>
                    </dl>
                @endif

                <div class="fxr__modal-actions">
                    <button
                        type="button"
                        class="fxr__btn fxr__btn--primary"
                        wire:click="runAnalyze"
                        wire:loading.attr="disabled"
                        @disabled($this->analyzing)
                    >
                        <span wire:loading.remove wire:target="runAnalyze">{{ __('agricart.pages.fx-learn.analyze_run') }}</span>
                        <span wire:loading wire:target="runAnalyze">{{ __('agricart.pages.fx-learn.analyzing') }}</span>
                    </button>
                </div>

                @if (filled($this->aiMessage))
                    <p @class(['fxr__msg', 'is-'.$this->aiMessageTone])>{{ $this->aiMessage }}</p>
                @endif

                @if (filled($this->aiBias) && filled($this->aiVerdict))
                    <div class="fxr__ai-result">
                        <p><strong>{{ __('agricart.pages.fx-learn.ai_bias') }}:</strong>
                            {{ __('agricart.pages.fx-learn.ai_bias_'.$this->aiBias) }}</p>
                        <p><strong>{{ __('agricart.pages.fx-learn.ai_verdict') }}:</strong>
                            {{ __('agricart.pages.fx-learn.ai_verdict_'.$this->aiVerdict) }}</p>
                        @if (filled($this->aiSummaryEn))
                            <p dir="ltr">{{ $this->aiSummaryEn }}</p>
                        @endif
                        @if (filled($this->aiSummaryUr))
                            <p dir="rtl">{{ $this->aiSummaryUr }}</p>
                        @endif
                    </div>
                @endif

                <p class="fxr__disclaimer">{{ __('agricart.pages.fx-learn.ai_disclaimer') }}</p>
            </div>
        </div>
    @endif

    <script>
        function fxrSessionClock(cfg) {
            const c = (cfg && typeof cfg === 'object') ? cfg : { tz: String(cfg || 'UTC') };
            return {
                hm: '--:--',
                sec: '--',
                open: !!c.open,
                openUntilLabel: c.openUntilLabel || '',
                closeUntilLabel: c.closeUntilLabel || '',
                labels: c.labels || {},
                tz: c.tz,
                openStart: Number(c.openStart) || 9,
                openEnd: Number(c.openEnd) || 17,
                openBoundaryMs: Number(c.openBoundaryMs) || 0,
                closeBoundaryMs: Number(c.closeBoundaryMs) || 0,
                timer: null,
                boot() {
                    this.tick();
                    this.timer = setInterval(() => this.tick(), 1000);
                },
                destroy() {
                    if (this.timer) clearInterval(this.timer);
                },
                localParts() {
                    const parts = new Intl.DateTimeFormat('en-GB', {
                        timeZone: this.tz,
                        weekday: 'short',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: false,
                    }).formatToParts(new Date());
                    const get = (t) => parts.find(p => p.type === t)?.value || '00';
                    return {
                        weekday: get('weekday'),
                        hour: Number(get('hour')),
                        hm: `${get('hour')}:${get('minute')}`,
                        second: get('second'),
                    };
                },
                isWeekday(wd) {
                    return !['Sat', 'Sun'].includes(wd);
                },
                spanText(diffSec) {
                    const h = Math.floor(diffSec / 3600);
                    const m = Math.floor((diffSec % 3600) / 60);
                    if (h > 0) return h + 'h ' + m + 'm';
                    return Math.max(1, m) + 'm';
                },
                tick() {
                    try {
                        const loc = this.localParts();
                        this.hm = loc.hm;
                        this.sec = loc.second;
                        this.open = this.isWeekday(loc.weekday) && loc.hour >= this.openStart && loc.hour < this.openEnd;

                        const now = Date.now();
                        const openTarget = this.openBoundaryMs > now ? this.openBoundaryMs : now;
                        const closeTarget = this.closeBoundaryMs > now ? this.closeBoundaryMs : now;

                        if (this.open) {
                            this.openUntilLabel = this.labels.open || 'Open';
                        } else {
                            const openSec = Math.max(0, Math.floor((openTarget - now) / 1000));
                            this.openUntilLabel = (this.labels.opensIn || 'Open __SPAN__')
                                .replace('__SPAN__', this.spanText(openSec));
                        }

                        const closeSec = Math.max(0, Math.floor((closeTarget - now) / 1000));
                        this.closeUntilLabel = (this.labels.closesIn || 'Close __SPAN__')
                            .replace('__SPAN__', this.spanText(closeSec));
                    } catch (e) {}
                }
            };
        }

        function fxrPakistanClock(tz) {
            return {
                hm: '--:--',
                sec: '--',
                date: '—',
                timer: null,
                boot() {
                    this.tick();
                    this.timer = setInterval(() => this.tick(), 1000);
                },
                destroy() {
                    if (this.timer) clearInterval(this.timer);
                },
                tick() {
                    try {
                        const now = new Date();
                        const timeParts = new Intl.DateTimeFormat('en-GB', {
                            timeZone: tz,
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit',
                            hour12: false,
                        }).formatToParts(now);
                        const dateParts = new Intl.DateTimeFormat('en-GB', {
                            timeZone: tz,
                            weekday: 'short',
                            day: '2-digit',
                            month: 'short',
                            year: 'numeric',
                        }).formatToParts(now);
                        const get = (parts, t) => parts.find(p => p.type === t)?.value || '';
                        this.hm = `${get(timeParts, 'hour')}:${get(timeParts, 'minute')}`;
                        this.sec = get(timeParts, 'second');
                        this.date = `${get(dateParts, 'weekday')} ${get(dateParts, 'day')} ${get(dateParts, 'month')} ${get(dateParts, 'year')}`;
                    } catch (e) {}
                }
            };
        }

        function fxrEtaLine(cfg) {
            const c = cfg || {};
            return {
                atMs: Number(c.atMs) || 0,
                title: c.title || '',
                ccy: c.ccy || '',
                impact: c.impact || '',
                flag: c.flag || '',
                prefix: c.prefix || 'Data in',
                countdown: '—',
                timer: null,
                boot() {
                    this.tick();
                    this.timer = setInterval(() => this.tick(), 1000);
                },
                tick() {
                    const diff = Math.floor((this.atMs - Date.now()) / 1000);
                    const abs = Math.abs(diff);
                    const h = Math.floor(abs / 3600);
                    const m = Math.floor((abs % 3600) / 60);
                    const s = abs % 60;
                    const pad = (n) => String(n).padStart(2, '0');
                    if (diff >= 0) {
                        this.countdown = h > 0 ? `${h}h ${m}m` : (m > 0 ? `${m}m ${pad(s)}s` : `${s}s`);
                    } else {
                        this.countdown = h > 0 ? `${h}h ${m}m ago` : `${m}m ago`;
                    }
                }
            };
        }
    </script>
</x-filament-panels::page>
