@php
    $rows = $this->filteredEvents();
    $radar = $this->radarEvent(app(\App\Services\FxLearn\EconomicCalendarService::class));
    $clocks = $this->sessionClocks();
    $leftClocks = collect($clocks)->where('side', 'left')->values();
    $rightClocks = collect($clocks)->where('side', 'right')->values();
    $radarAt = (int) ($radar['at'] ?? 0);
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

        {{-- Left clocks | radar | right clocks --}}
        <section class="fxr__deck" aria-label="{{ __('agricart.pages.fx-learn.deck_label') }}">
            <div class="fxr__clocks fxr__clocks--left" aria-label="{{ __('agricart.pages.fx-learn.sessions_label') }}">
                @foreach ($leftClocks as $clock)
                    @include('filament.pages.fx-learn.partials.econ-clock', ['clock' => $clock])
                @endforeach
            </div>

            <div class="fxr__radar" aria-label="{{ __('agricart.pages.fx-learn.radar_label') }}">
                @if ($radar)
                    <div
                        class="fxr__radar-inner"
                        x-data="fxrGauge(@js($radarAt))"
                        x-init="boot()"
                    >
                        <div class="fxr__gauge" aria-hidden="true">
                            <svg viewBox="0 0 120 120" class="fxr__gauge-svg">
                                <circle cx="60" cy="60" r="48" class="fxr__gauge-track" />
                                <circle
                                    cx="60" cy="60" r="48"
                                    class="fxr__gauge-arc"
                                    :stroke-dasharray="arc"
                                    stroke-dashoffset="0"
                                />
                                <line
                                    x1="60" y1="60" x2="60" y2="22"
                                    class="fxr__gauge-needle"
                                    :transform="`rotate(${angle} 60 60)`"
                                />
                                <circle cx="60" cy="60" r="4" class="fxr__gauge-hub" />
                            </svg>
                            <div class="fxr__gauge-count" dir="ltr">
                                <template x-if="withinFive">
                                    <span class="fxr__gauge-live" x-text="countdown"></span>
                                </template>
                                <template x-if="!withinFive">
                                    <span x-text="eta"></span>
                                </template>
                            </div>
                        </div>

                        <div class="fxr__radar-copy">
                            <p class="fxr__radar-kicker">{{ __('agricart.pages.fx-learn.radar_next') }}</p>
                            <h2 class="fxr__radar-title">
                                <span class="fxr__flag fxr__flag--inline">{{ $this->flagForCcy((string) ($radar['ccy'] ?? '')) }}</span>
                                {{ $radar['title'] ?? '' }}
                            </h2>
                            <dl class="fxr__radar-metrics" dir="ltr">
                                <div>
                                    <dt>{{ __('agricart.pages.fx-learn.col_previous') }}</dt>
                                    <dd>{{ $radar['previous'] ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt>{{ __('agricart.pages.fx-learn.col_forecast') }}</dt>
                                    <dd>{{ $radar['forecast'] ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt>{{ __('agricart.pages.fx-learn.col_ccy') }}</dt>
                                    <dd>{{ $radar['ccy'] ?? '' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                @else
                    <div class="fxr__radar-empty">
                        <p>{{ __('agricart.pages.fx-learn.radar_empty') }}</p>
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
                            @php $tone = $this->actualTone($ev); @endphp
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
                    style="height:420px;width:100%"
                    x-data
                    x-init="
                        if ($el.dataset.booted) return;
                        $el.dataset.booted = '1';
                        const conf = document.createElement('script');
                        conf.type = 'text/javascript';
                        conf.text = JSON.stringify({
                            width: '100%',
                            height: '100%',
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
                    <div class="tradingview-widget-container__widget" style="height:100%;width:100%"></div>
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
            const c = (cfg && typeof cfg === 'object') ? cfg : { tz: cfg, open: arguments[1] };
            return {
                hm: '--:--',
                sec: '--',
                open: !!c.open,
                untilLabel: c.untilLabel || '',
                hoursLabel: c.hoursLabel || '',
                labels: c.labels || {},
                tz: c.tz,
                openStart: Number(c.openStart) || 9,
                openEnd: Number(c.openEnd) || 17,
                boundaryMs: Number(c.boundaryMs) || 0,
                boundaryKind: c.boundaryKind || 'open',
                timer: null,
                boot() {
                    this.tick();
                    this.timer = setInterval(() => this.tick(), 1000);
                },
                destroy() {
                    if (this.timer) clearInterval(this.timer);
                },
                localHourMinute() {
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
                        minute: get('minute'),
                        second: get('second'),
                        hm: `${get('hour')}:${get('minute')}`,
                    };
                },
                isWeekday(wd) {
                    return !['Sat', 'Sun'].includes(wd);
                },
                tick() {
                    try {
                        const loc = this.localHourMinute();
                        this.hm = loc.hm;
                        this.sec = loc.second;
                        this.open = this.isWeekday(loc.weekday) && loc.hour >= this.openStart && loc.hour < this.openEnd;

                        const now = Date.now();
                        const pad = (n) => String(n).padStart(2, '0');
                        this.boundaryKind = this.open ? 'close' : 'open';
                        this.hoursLabel = this.open
                            ? (this.labels.closesAt || 'Closes __TIME__').replace('__TIME__', `${pad(this.openEnd)}:00`)
                            : (this.labels.opensAt || 'Opens __TIME__').replace('__TIME__', `${pad(this.openStart)}:00`);

                        let target = this.boundaryMs;
                        if (!target || target <= now) {
                            target = now;
                        }
                        const diffSec = Math.max(0, Math.floor((target - now) / 1000));
                        const h = Math.floor(diffSec / 3600);
                        const m = Math.floor((diffSec % 3600) / 60);
                        let span;
                        if (h > 0) {
                            span = (this.labels.inHm || '__H__h __M__m').replace('__H__', String(h)).replace('__M__', String(m));
                        } else {
                            span = (this.labels.inM || '__M__m').replace('__M__', String(Math.max(1, m)));
                        }
                        const tpl = this.boundaryKind === 'close'
                            ? (this.labels.closesIn || 'Closes in __SPAN__')
                            : (this.labels.opensIn || 'Opens in __SPAN__');
                        this.untilLabel = tpl.replace('__SPAN__', span);
                    } catch (e) {}
                }
            };
        }

        function fxrGauge(atMs) {
            return {
                atMs: Number(atMs) || 0,
                countdown: '--:--',
                eta: '—',
                withinFive: false,
                angle: -90,
                arc: '0 302',
                timer: null,
                boot() {
                    this.tick();
                    this.timer = setInterval(() => this.tick(), 250);
                },
                tick() {
                    const now = Date.now();
                    const diff = Math.floor((this.atMs - now) / 1000);
                    const abs = Math.abs(diff);
                    const h = Math.floor(abs / 3600);
                    const m = Math.floor((abs % 3600) / 60);
                    const s = abs % 60;
                    const pad = (n) => String(n).padStart(2, '0');

                    this.withinFive = diff >= 0 && diff <= 300;

                    if (diff >= 0) {
                        this.countdown = h > 0 ? `${pad(h)}:${pad(m)}:${pad(s)}` : `${pad(m)}:${pad(s)}`;
                        this.eta = h > 0 ? `in ${h}h ${m}m` : (m > 0 ? `in ${m}m ${s}s` : `in ${s}s`);
                        const windowSec = 6 * 3600;
                        const progress = Math.max(0, Math.min(1, 1 - (diff / windowSec)));
                        this.angle = -90 + (progress * 180);
                        const len = 301.6;
                        this.arc = `${(progress * len * 0.5).toFixed(1)} ${len}`;
                    } else {
                        this.withinFive = false;
                        this.eta = `${m}m ago`;
                        this.angle = 90;
                        this.arc = `150.8 301.6`;
                    }
                }
            };
        }
    </script>
</x-filament-panels::page>
