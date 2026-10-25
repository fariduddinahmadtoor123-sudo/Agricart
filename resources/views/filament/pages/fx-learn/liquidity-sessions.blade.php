@php
    $sessions = app(\App\Services\FxLearn\SessionLiquidityService::class)->sessions();
    $boot = [
        'sessions' => $sessions,
        'zone' => __('agricart.datetime.zone_abbr'),
        'labels' => [
            'open' => __('agricart.pages.fx-learn.liq_open'),
            'closed' => __('agricart.pages.fx-learn.liq_closed_pill'),
            'peak' => __('agricart.pages.fx-learn.liq_level_peak'),
            'mod' => __('agricart.pages.fx-learn.liq_level_mod'),
            'low' => __('agricart.pages.fx-learn.liq_level_low'),
            'mktClosed' => __('agricart.pages.fx-learn.liq_level_closed'),
            'peakDetail' => __('agricart.pages.fx-learn.liq_detail_peak'),
            'modDetail' => __('agricart.pages.fx-learn.liq_detail_mod'),
            'lowDetail' => __('agricart.pages.fx-learn.liq_detail_low'),
            'closedDetail' => __('agricart.pages.fx-learn.liq_detail_closed'),
            'overlapActive' => __('agricart.pages.fx-learn.liq_overlap_active'),
            'overlapSoon' => __('agricart.pages.fx-learn.liq_overlap_soon'),
            'overlapNone' => __('agricart.pages.fx-learn.liq_overlap_none'),
            'window' => __('agricart.pages.fx-learn.liq_window_pkt'),
        ],
    ];
@endphp

<x-filament-panels::page>
    <div
        class="fx-liq"
        x-data="window.agricartFxLiquidity(@js($boot))"
        x-init="start()"
    >
        <header class="fx-liq__head">
            <div>
                <h1 class="fx-liq__title">{{ __('agricart.pages.fx-learn.liquidity-sessions') }}</h1>
                <p class="fx-liq__intro">{{ __('agricart.pages.fx-learn.liq_page_intro') }}</p>
            </div>
            <div class="fx-liq__clock" aria-live="polite">
                <span class="fx-liq__clock-label">{{ __('agricart.pages.fx-learn.liq_local_clock') }}</span>
                <strong class="fx-liq__clock-value" x-text="clock"></strong>
            </div>
        </header>

        <div class="fx-liq__grid">
            <section class="fx-liq__radar-card">
                <div class="fx-liq__radar-wrap">
                    <svg class="fx-liq__svg" viewBox="0 0 200 200" role="img" aria-label="{{ __('agricart.pages.fx-learn.liquidity-sessions') }}">
                        <circle cx="100" cy="100" r="42" fill="none" class="fx-liq__ring" />
                        <circle cx="100" cy="100" r="62" fill="none" class="fx-liq__ring" />
                        <circle cx="100" cy="100" r="82" fill="none" class="fx-liq__ring" />
                        <template x-for="(session, idx) in sessions" :key="session.id">
                            <path
                                fill="none"
                                stroke-linecap="round"
                                :stroke="session.color"
                                :stroke-width="session.openNow ? 7 : 5"
                                :opacity="session.openNow ? 0.92 : 0.22"
                                :d="arcPath(48 + idx * 10, session.open, session.close)"
                            ></path>
                        </template>
                        <circle cx="100" cy="100" r="5" class="fx-liq__hub" />
                        <line
                            x1="100" y1="100"
                            :x2="needle.x" :y2="needle.y"
                            class="fx-liq__needle"
                            stroke-linecap="round"
                        />
                        <circle :cx="needle.x" :cy="needle.y" r="3.5" class="fx-liq__tip" />
                    </svg>
                </div>
                <p class="fx-liq__overlap" x-text="overlapLabel"></p>
            </section>

            <section class="fx-liq__side">
                <div class="fx-liq__meter" :data-level="liquidity">
                    <span class="fx-liq__meter-k">{{ __('agricart.pages.fx-learn.liq_meter') }}</span>
                    <strong class="fx-liq__meter-v" x-text="liquidityLabel"></strong>
                    <p class="fx-liq__meter-d" x-text="liquidityDetail"></p>
                </div>

                <ul class="fx-liq__pills" role="list">
                    <template x-for="session in sessions" :key="'pill-'+session.id">
                        <li class="fx-liq__pill" :class="{ 'is-open': session.openNow }" role="listitem">
                            <span class="fx-liq__pill-dot" :style="'background:' + session.color"></span>
                            <div class="fx-liq__pill-body">
                                <div class="fx-liq__pill-top">
                                    <strong x-text="sessionLabel(session)"></strong>
                                    <span class="fx-liq__pill-state" x-text="session.openNow ? labels.open : labels.closed"></span>
                                </div>
                                <span class="fx-liq__pill-window" x-text="labels.window + ': ' + session.windowPkt"></span>
                            </div>
                        </li>
                    </template>
                </ul>
            </section>
        </div>
    </div>

    <script>
        window.agricartFxLiquidity = function (boot) {
            return {
                sessions: (boot.sessions || []).map((s) => ({
                    ...s,
                    openNow: false,
                    windowPkt: '',
                })),
                labels: boot.labels || {},
                zone: boot.zone || 'PKT',
                clock: '—',
                liquidity: 'mod',
                liquidityLabel: '',
                liquidityDetail: '',
                overlapLabel: '',
                needle: { x: 100, y: 30 },
                _timer: null,

                start() {
                    this.tick();
                    this._timer = setInterval(() => this.tick(), 1000);
                },

                sessionLabel(session) {
                    return document.documentElement.lang === 'ur'
                        ? (session.label_ur || session.label_en)
                        : (session.label_en || session.id);
                },

                utcMinuteToPkt(utcMin) {
                    const now = new Date();
                    const utc = Date.UTC(
                        now.getUTCFullYear(),
                        now.getUTCMonth(),
                        now.getUTCDate(),
                        Math.floor(utcMin / 60),
                        utcMin % 60,
                        0
                    );
                    const d = new Date(utc);
                    const parts = new Intl.DateTimeFormat('en-GB', {
                        timeZone: 'Asia/Karachi',
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false,
                    }).formatToParts(d);
                    const h = parts.find((p) => p.type === 'hour')?.value ?? '00';
                    const m = parts.find((p) => p.type === 'minute')?.value ?? '00';
                    return `${h}:${m}`;
                },

                isOpen(utcMin, open, close) {
                    if (open < close) return utcMin >= open && utcMin < close;
                    return utcMin >= open || utcMin < close;
                },

                isWeekendClosed(now) {
                    const dow = now.getUTCDay();
                    const min = now.getUTCHours() * 60 + now.getUTCMinutes();
                    if (dow === 6) return true;
                    if (dow === 5 && min >= 22 * 60) return true;
                    if (dow === 0 && min < 22 * 60) return true;
                    return false;
                },

                sessionOpeningHour(utcMin, openMin) {
                    return ((utcMin - openMin + 24 * 60) % (24 * 60)) < 60;
                },

                minuteToAngle(minute) {
                    return (minute / (24 * 60)) * 360 - 90;
                },

                polar(r, angleDeg) {
                    const a = (angleDeg * Math.PI) / 180;
                    return { x: 100 + r * Math.cos(a), y: 100 + r * Math.sin(a) };
                },

                arcPath(r, startMin, endMin) {
                    const startAngle = this.minuteToAngle(startMin);
                    let endAngle = this.minuteToAngle(endMin);
                    if (endMin <= startMin) endAngle = this.minuteToAngle(endMin + 24 * 60);
                    const large = (endMin - startMin + (endMin <= startMin ? 24 * 60 : 0)) > 12 * 60 ? 1 : 0;
                    const s = this.polar(r, startAngle);
                    const e = this.polar(r, endAngle);
                    return `M ${s.x} ${s.y} A ${r} ${r} 0 ${large} 1 ${e.x} ${e.y}`;
                },

                tick() {
                    const now = new Date();
                    const utcMin = now.getUTCHours() * 60 + now.getUTCMinutes();
                    const closed = this.isWeekendClosed(now);
                    this.needle = this.polar(70, this.minuteToAngle(utcMin));

                    const pkt = new Intl.DateTimeFormat('en-GB', {
                        timeZone: 'Asia/Karachi',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: false,
                    }).format(now);
                    this.clock = `${pkt} ${this.zone}`;

                    const status = {};
                    this.sessions = this.sessions.map((session) => {
                        const openNow = !closed && this.isOpen(utcMin, session.open, session.close);
                        status[session.id] = openNow;
                        return {
                            ...session,
                            openNow,
                            windowPkt: `${this.utcMinuteToPkt(session.open)} – ${this.utcMinuteToPkt(session.close)}`,
                        };
                    });

                    const openingBoost = !closed && (
                        this.sessionOpeningHour(utcMin, 8 * 60) ||
                        this.sessionOpeningHour(utcMin, 13 * 60)
                    );

                    if (closed) this.liquidity = 'closed';
                    else if ((status.london && status.newyork) || openingBoost) this.liquidity = 'peak';
                    else if (status.london || status.newyork) this.liquidity = 'mod';
                    else this.liquidity = 'low';

                    const map = {
                        peak: [this.labels.peak, this.labels.peakDetail],
                        mod: [this.labels.mod, this.labels.modDetail],
                        low: [this.labels.low, this.labels.lowDetail],
                        closed: [this.labels.mktClosed, this.labels.closedDetail],
                    };
                    const pair = map[this.liquidity] || map.mod;
                    this.liquidityLabel = pair[0];
                    this.liquidityDetail = pair[1];

                    if (closed) {
                        this.overlapLabel = this.labels.overlapNone;
                    } else if (status.london && status.newyork) {
                        this.overlapLabel = this.labels.overlapActive;
                    } else {
                        const overlapStart = 13 * 60;
                        const delta = (overlapStart - utcMin + 24 * 60) % (24 * 60);
                        if (delta > 0 && delta <= 6 * 60 && !status.newyork) {
                            const h = Math.floor(delta / 60);
                            const m = delta % 60;
                            this.overlapLabel = `${this.labels.overlapSoon} ${h}h ${m}m`;
                        } else {
                            this.overlapLabel = this.labels.overlapNone;
                        }
                    }
                },
            };
        };
    </script>
</x-filament-panels::page>
