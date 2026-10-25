@php
    $boot = $this->bootData();
@endphp

<x-filament-panels::page>
    <div
        class="fx-prop"
        x-data="window.agricartFxProp(@js($boot))"
        x-init="start()"
    >
        <header class="fx-prop__head">
            <div>
                <h1 class="fx-prop__title">{{ __('agricart.pages.fx-learn.prop-challenge-rules') }}</h1>
                <p class="fx-prop__intro">{{ __('agricart.pages.fx-learn.prop_page_intro') }}</p>
            </div>
            <button type="button" class="fx-prop__reset" @click="resetDefaults()">
                {{ __('agricart.pages.fx-learn.prop_reset') }}
            </button>
        </header>

        <section class="fx-prop__rules">
            <h2 class="fx-prop__section-title">{{ __('agricart.pages.fx-learn.prop_rules_title') }}</h2>
            <ol class="fx-prop__rules-list">
                <li>{{ __('agricart.pages.fx-learn.prop_rule_1') }}</li>
                <li>{{ __('agricart.pages.fx-learn.prop_rule_2') }}</li>
                <li>{{ __('agricart.pages.fx-learn.prop_rule_3') }}</li>
                <li>{{ __('agricart.pages.fx-learn.prop_rule_4') }}</li>
                <li>{{ __('agricart.pages.fx-learn.prop_rule_5') }}</li>
                <li>{{ __('agricart.pages.fx-learn.prop_rule_6') }}</li>
            </ol>
        </section>

        <section class="fx-prop__card">
            <h2 class="fx-prop__section-title">{{ __('agricart.pages.fx-learn.prop_calc_title') }}</h2>
            <div class="fx-prop__form">
                <label class="fx-prop__field">
                    <span x-text="labels.balance"></span>
                    <input type="number" min="1000" step="1000" x-model.number="balance" />
                </label>
                <label class="fx-prop__field">
                    <span x-text="labels.phase1"></span>
                    <input type="number" min="0" step="0.1" x-model.number="phase1" />
                </label>
                <label class="fx-prop__field">
                    <span x-text="labels.phase2"></span>
                    <input type="number" min="0" step="0.1" x-model.number="phase2" />
                </label>
                <label class="fx-prop__field">
                    <span x-text="labels.phase3"></span>
                    <input type="number" min="0" step="0.1" x-model.number="phase3" />
                </label>
                <label class="fx-prop__field">
                    <span x-text="labels.maxDay"></span>
                    <input type="number" min="0" step="0.1" x-model.number="maxDayLoss" />
                </label>
                <label class="fx-prop__field">
                    <span x-text="labels.maxTotal"></span>
                    <input type="number" min="0" step="0.1" x-model.number="maxTotalLoss" />
                </label>
                <label class="fx-prop__field">
                    <span x-text="labels.maxTrade"></span>
                    <input type="number" min="0" step="0.1" x-model.number="maxTradeLoss" />
                </label>
            </div>
        </section>

        <div class="fx-prop__grid">
            <article class="fx-prop__metric">
                <span class="fx-prop__metric-k">{{ __('agricart.pages.fx-learn.prop_out_p1') }}</span>
                <strong class="fx-prop__metric-v" x-text="money(phase1Usd)"></strong>
                <span class="fx-prop__metric-s" x-text="pct(phase1) + ' · ' + money(balance + phase1Usd)"></span>
            </article>
            <article class="fx-prop__metric">
                <span class="fx-prop__metric-k">{{ __('agricart.pages.fx-learn.prop_out_p2') }}</span>
                <strong class="fx-prop__metric-v" x-text="money(phase2Usd)"></strong>
                <span class="fx-prop__metric-s" x-text="pct(phase2) + ' · ' + money(balance + phase2Usd)"></span>
            </article>
            <article class="fx-prop__metric">
                <span class="fx-prop__metric-k">{{ __('agricart.pages.fx-learn.prop_out_p3') }}</span>
                <strong class="fx-prop__metric-v" x-text="money(phase3Usd)"></strong>
                <span class="fx-prop__metric-s" x-text="pct(phase3) + ' · ' + money(balance + phase3Usd)"></span>
            </article>
            <article class="fx-prop__metric fx-prop__metric--danger">
                <span class="fx-prop__metric-k">{{ __('agricart.pages.fx-learn.prop_out_day') }}</span>
                <strong class="fx-prop__metric-v" x-text="money(maxDayUsd)"></strong>
                <span class="fx-prop__metric-s" x-text="pct(maxDayLoss) + ' · floor ' + money(balance - maxDayUsd)"></span>
            </article>
            <article class="fx-prop__metric fx-prop__metric--danger">
                <span class="fx-prop__metric-k">{{ __('agricart.pages.fx-learn.prop_out_total') }}</span>
                <strong class="fx-prop__metric-v" x-text="money(maxTotalUsd)"></strong>
                <span class="fx-prop__metric-s" x-text="pct(maxTotalLoss) + ' · floor ' + money(balance - maxTotalUsd)"></span>
            </article>
            <article class="fx-prop__metric fx-prop__metric--warn">
                <span class="fx-prop__metric-k">{{ __('agricart.pages.fx-learn.prop_out_trade') }}</span>
                <strong class="fx-prop__metric-v" x-text="money(maxTradeUsd)"></strong>
                <span class="fx-prop__metric-s" x-text="pct(maxTradeLoss) + ' · ' + '{{ __('agricart.pages.fx-learn.prop_out_trade_hint') }}'"></span>
            </article>
        </div>

        <section class="fx-prop__card">
            <h2 class="fx-prop__section-title">{{ __('agricart.pages.fx-learn.prop_progress_title') }}</h2>
            <p class="fx-prop__hint">{{ __('agricart.pages.fx-learn.prop_progress_hint') }}</p>
            <div class="fx-prop__form fx-prop__form--2">
                <label class="fx-prop__field">
                    <span x-text="labels.activePhase"></span>
                    <select x-model.number="activePhase">
                        <option value="1">{{ __('agricart.pages.fx-learn.prop_phase_opt_1') }}</option>
                        <option value="2">{{ __('agricart.pages.fx-learn.prop_phase_opt_2') }}</option>
                        <option value="3">{{ __('agricart.pages.fx-learn.prop_phase_opt_3') }}</option>
                    </select>
                </label>
                <label class="fx-prop__field">
                    <span x-text="labels.currentEquity"></span>
                    <input type="number" min="0" step="100" x-model.number="equity" />
                </label>
            </div>
            <div class="fx-prop__progress">
                <div class="fx-prop__progress-row">
                    <span>{{ __('agricart.pages.fx-learn.prop_need_more') }}</span>
                    <strong x-text="money(needMore)" :class="{ 'is-done': needMore <= 0 }"></strong>
                </div>
                <div class="fx-prop__progress-row">
                    <span>{{ __('agricart.pages.fx-learn.prop_room_day') }}</span>
                    <strong x-text="money(roomDay)"></strong>
                </div>
                <div class="fx-prop__progress-row">
                    <span>{{ __('agricart.pages.fx-learn.prop_room_total') }}</span>
                    <strong x-text="money(roomTotal)"></strong>
                </div>
                <div class="fx-prop__bar" aria-hidden="true">
                    <span class="fx-prop__bar-fill" :style="'width:' + progressPct + '%'"></span>
                </div>
                <p class="fx-prop__progress-note" x-show="needMore <= 0">{{ __('agricart.pages.fx-learn.prop_note_pass') }}</p>
                <p class="fx-prop__progress-note" x-show="needMore > 0" x-cloak>{{ __('agricart.pages.fx-learn.prop_note_need') }}</p>
            </div>
        </section>
    </div>

    <script>
        window.agricartFxProp = function (boot) {
            const d = boot.defaults || {};
            return {
                labels: boot.labels || {},
                balance: Number(d.balance) || 100000,
                phase1: Number(d.phase1) || 8,
                phase2: Number(d.phase2) || 6,
                phase3: Number(d.phase3) || 3,
                maxDayLoss: Number(d.maxDayLoss) || 5,
                maxTotalLoss: Number(d.maxTotalLoss) || 10,
                maxTradeLoss: Number(d.maxTradeLoss) || 3,
                activePhase: 1,
                equity: Number(d.balance) || 100000,
                _defaults: d,

                start() {
                    this.equity = this.balance;
                },

                resetDefaults() {
                    const x = this._defaults;
                    this.balance = Number(x.balance) || 100000;
                    this.phase1 = Number(x.phase1) || 8;
                    this.phase2 = Number(x.phase2) || 6;
                    this.phase3 = Number(x.phase3) || 3;
                    this.maxDayLoss = Number(x.maxDayLoss) || 5;
                    this.maxTotalLoss = Number(x.maxTotalLoss) || 10;
                    this.maxTradeLoss = Number(x.maxTradeLoss) || 3;
                    this.activePhase = 1;
                    this.equity = this.balance;
                },

                pctOf(p) {
                    const bal = Math.max(0, Number(this.balance) || 0);
                    const pct = Math.max(0, Number(p) || 0);
                    return bal * (pct / 100);
                },

                get phase1Usd() { return this.pctOf(this.phase1); },
                get phase2Usd() { return this.pctOf(this.phase2); },
                get phase3Usd() { return this.pctOf(this.phase3); },
                get maxDayUsd() { return this.pctOf(this.maxDayLoss); },
                get maxTotalUsd() { return this.pctOf(this.maxTotalLoss); },
                get maxTradeUsd() { return this.pctOf(this.maxTradeLoss); },

                phaseTargetUsd() {
                    if (this.activePhase === 2) return this.phase2Usd;
                    if (this.activePhase === 3) return this.phase3Usd;
                    return this.phase1Usd;
                },

                phaseTargetEquity() {
                    return (Number(this.balance) || 0) + this.phaseTargetUsd();
                },

                get needMore() {
                    return Math.max(0, this.phaseTargetEquity() - (Number(this.equity) || 0));
                },

                get roomDay() {
                    const floor = (Number(this.balance) || 0) - this.maxDayUsd;
                    return Math.max(0, (Number(this.equity) || 0) - floor);
                },

                get roomTotal() {
                    const floor = (Number(this.balance) || 0) - this.maxTotalUsd;
                    return Math.max(0, (Number(this.equity) || 0) - floor);
                },

                get progressPct() {
                    const target = this.phaseTargetUsd();
                    if (target <= 0) return 0;
                    const gained = (Number(this.equity) || 0) - (Number(this.balance) || 0);
                    return Math.max(0, Math.min(100, (gained / target) * 100));
                },

                money(n) {
                    const v = Number(n);
                    if (!isFinite(v)) return '$0';
                    return new Intl.NumberFormat('en-US', {
                        style: 'currency',
                        currency: 'USD',
                        maximumFractionDigits: 0,
                    }).format(v);
                },

                pct(n) {
                    const v = Number(n);
                    if (!isFinite(v)) return '0%';
                    return `${v}%`;
                },
            };
        };
    </script>
</x-filament-panels::page>
