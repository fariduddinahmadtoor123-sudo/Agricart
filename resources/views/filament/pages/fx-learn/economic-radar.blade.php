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
        <div
            class="fx-radar__modal"
            role="dialog"
            aria-modal="true"
            aria-label="{{ __('agricart.pages.fx-learn.econ_view_all') }}"
            wire:keydown.escape.window="closeAllView"
            wire:key="econ-modal-{{ $this->tf }}-{{ $this->impact }}-{{ $this->ccy }}-{{ $total }}"
        >
            <button type="button" class="fx-radar__modal-backdrop" wire:click="closeAllView" aria-label="{{ __('agricart.pages.fx-learn.econ_view_close') }}"></button>
            <div class="fx-radar__modal-card">
                <header class="fx-radar__modal-head">
                    <div>
                        <h2 class="fx-radar__modal-title">{{ __('agricart.pages.fx-learn.econ_view_all') }}</h2>
                        <p class="fx-radar__modal-sub" dir="ltr">{{ __('agricart.pages.fx-learn.econ_count', ['count' => $total]) }}</p>
                    </div>
                    <button type="button" class="fx-radar__btn fx-radar__btn--refresh" wire:click="closeAllView">{{ __('agricart.pages.fx-learn.econ_view_close') }}</button>
                </header>
                <div class="fx-radar__modal-body">
                    <div class="fx-radar__table-wrap">
                        @include('filament.pages.fx-learn.partials.econ-events-table', [
                            'rows' => $this->filteredEvents(),
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
