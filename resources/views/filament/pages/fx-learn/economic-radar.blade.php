@php
    $visible = $this->filteredEvents();
    $preview = $this->previewEvents();
    $poll = $this->shouldPoll();
    $total = $visible->count();
    $isLive = $this->feedSource === 'ff';
@endphp

<x-filament-panels::page>
    <div
        class="fx-radar"
        @if ($poll)
            wire:poll.30s="softRefresh"
        @endif
    >
        <section
            class="fx-radar__workspace"
            aria-label="{{ __('agricart.pages.fx-learn.econ_workspace') }}"
            x-data="fxRadarClocks()"
            x-init="boot(); return () => destroy()"
        >
            <aside class="fx-radar__clocks fx-radar__clocks--left" aria-label="{{ __('agricart.pages.fx-learn.econ_clocks') }}">
                @include('filament.pages.fx-learn.partials.econ-glass-clock', ['id' => 'tokyo'])
                @include('filament.pages.fx-learn.partials.econ-glass-clock', ['id' => 'london'])
            </aside>

            <div class="fx-radar__workspace-box">
                <p class="fx-radar__workspace-title">{{ __('agricart.pages.fx-learn.econ_workspace_title') }}</p>
                <p class="fx-radar__workspace-text">{{ __('agricart.pages.fx-learn.econ_workspace_hint') }}</p>
            </div>

            <aside class="fx-radar__clocks fx-radar__clocks--right" aria-label="{{ __('agricart.pages.fx-learn.econ_clocks') }}">
                @include('filament.pages.fx-learn.partials.econ-glass-clock', ['id' => 'pk'])
                @include('filament.pages.fx-learn.partials.econ-glass-clock', ['id' => 'ny'])
            </aside>
        </section>

        <section class="fx-radar__calendar" aria-label="{{ __('agricart.pages.fx-learn.economic-radar') }}">
            <div class="fx-radar__filters" role="group" aria-label="{{ __('agricart.pages.fx-learn.econ_filters') }}">
                <label class="fi-sr-only" for="fx-radar-tf">{{ __('agricart.pages.fx-learn.econ_tf') }}</label>
                <select id="fx-radar-tf" class="fx-radar__select" wire:model.live="tf">
                    <option value="today">{{ __('agricart.pages.fx-learn.econ_tf_today') }}</option>
                    <option value="tomorrow">{{ __('agricart.pages.fx-learn.econ_tf_tomorrow') }}</option>
                    <option value="week">{{ __('agricart.pages.fx-learn.econ_tf_week') }}</option>
                </select>

                <label class="fi-sr-only" for="fx-radar-impact">{{ __('agricart.pages.fx-learn.econ_impact') }}</label>
                <select id="fx-radar-impact" class="fx-radar__select" wire:model.live="impact">
                    <option value="all">{{ __('agricart.pages.fx-learn.econ_impact_opt_all') }}</option>
                    <option value="low">{{ __('agricart.pages.fx-learn.econ_impact_opt_low') }}</option>
                    <option value="med">{{ __('agricart.pages.fx-learn.econ_impact_opt_med') }}</option>
                    <option value="high">{{ __('agricart.pages.fx-learn.econ_impact_opt_high') }}</option>
                </select>

                <label class="fi-sr-only" for="fx-radar-ccy">{{ __('agricart.pages.fx-learn.econ_ccy') }}</label>
                <select id="fx-radar-ccy" class="fx-radar__select" wire:model.live="ccy">
                    <option value="ALL">{{ __('agricart.pages.fx-learn.econ_ccy_opt_all') }}</option>
                    <option value="USD">USD</option>
                    <option value="EUR">EUR</option>
                    <option value="GBP">GBP</option>
                    <option value="JPY">JPY</option>
                    <option value="AUD">AUD</option>
                    <option value="CAD">CAD</option>
                    <option value="CHF">CHF</option>
                    <option value="NZD">NZD</option>
                    <option value="CNY">CNY</option>
                </select>

                <span class="fx-radar__filter-count" dir="ltr">{{ __('agricart.pages.fx-learn.econ_count', ['count' => $total]) }}</span>
            </div>

            <div class="fx-radar__table-wrap fx-radar__table-wrap--preview">
                @include('filament.pages.fx-learn.partials.econ-events-table', [
                    'rows' => $preview,
                    'compact' => true,
                    'toolbar' => true,
                    'isLive' => $isLive,
                    'total' => $total,
                ])
            </div>
        </section>
    </div>

    @if ($this->showAllView)
        @php
            $viewRows = $this->filteredEvents();
            $viewCount = $viewRows->count();
        @endphp
        <div
            class="fx-radar__modal"
            role="dialog"
            aria-modal="true"
            aria-label="{{ __('agricart.pages.fx-learn.econ_view_all') }}"
            wire:keydown.escape.window="closeAllView"
            wire:key="econ-modal-{{ $this->tf }}-{{ $this->impact }}-{{ $this->ccy }}-{{ $viewCount }}"
        >
            <button type="button" class="fx-radar__modal-backdrop" wire:click="closeAllView" aria-label="{{ __('agricart.pages.fx-learn.econ_view_close') }}"></button>
            <div class="fx-radar__modal-card">
                <header class="fx-radar__modal-head">
                    <div>
                        <h2 class="fx-radar__modal-title">{{ __('agricart.pages.fx-learn.econ_view_all') }} · {{ $this->rangeLabel() }}</h2>
                        <p class="fx-radar__modal-sub" dir="ltr">{{ __('agricart.pages.fx-learn.econ_count', ['count' => $viewCount]) }}</p>
                    </div>
                    <button type="button" class="fx-radar__btn fx-radar__btn--refresh" wire:click="closeAllView">{{ __('agricart.pages.fx-learn.econ_view_close') }}</button>
                </header>
                <div class="fx-radar__modal-body">
                    <div class="fx-radar__table-wrap fx-radar__table-wrap--modal" wire:key="econ-view-rows-{{ $viewCount }}-{{ $this->tf }}-{{ $this->impact }}-{{ $this->ccy }}">
                        @include('filament.pages.fx-learn.partials.econ-events-table', [
                            'rows' => $viewRows,
                            'compact' => false,
                            'toolbar' => false,
                        ])
                    </div>
                </div>
            </div>
        </div>
    @endif

    <script>
        function fxRadarClocks() {
            const zones = {
                tokyo: 'Asia/Tokyo',
                london: 'Europe/London',
                ny: 'America/New_York',
                pk: 'Asia/Karachi',
            };

            const blank = { h: 0, m: 0, s: 0 };
            const dash = { tokyo: '--', london: '--', ny: '--', pk: '--' };

            return {
                hm: { tokyo: '--:--', london: '--:--', ny: '--:--', pk: '--:--' },
                sec: { ...dash },
                angles: {
                    tokyo: { ...blank },
                    london: { ...blank },
                    ny: { ...blank },
                    pk: { ...blank },
                },
                timer: null,

                boot() {
                    this.tick();
                    this.timer = setInterval(() => this.tick(), 1000);
                },

                destroy() {
                    if (this.timer) clearInterval(this.timer);
                },

                partsFor(tz) {
                    const parts = new Intl.DateTimeFormat('en-GB', {
                        timeZone: tz,
                        hour: 'numeric',
                        minute: 'numeric',
                        second: 'numeric',
                        hour12: false,
                        hourCycle: 'h23',
                    }).formatToParts(new Date());

                    const map = {};
                    for (const p of parts) {
                        if (p.type !== 'literal') map[p.type] = p.value;
                    }

                    let h = Number(map.hour);
                    if (h === 24) h = 0;

                    return {
                        h,
                        m: Number(map.minute),
                        s: Number(map.second),
                    };
                },

                tick() {
                    for (const [key, tz] of Object.entries(zones)) {
                        const { h, m, s } = this.partsFor(tz);
                        this.hm[key] = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
                        this.sec[key] = `:${String(s).padStart(2, '0')}`;
                        this.angles[key] = {
                            h: ((h % 12) + m / 60) * 30,
                            m: (m + s / 60) * 6,
                            s: s * 6,
                        };
                    }
                },
            };
        }
    </script>
</x-filament-panels::page>
