@php
    $boot = $this->bootData();
@endphp

<x-filament-panels::page>
    @include('filament.pages.fx-learn.partials.tv-inject')

    <div
        class="fx-sent"
        x-data="window.agricartFxSentiment(@js($boot))"
        x-init="start()"
    >
        <header class="fx-sent__head">
            <div>
                <h1 class="fx-sent__title">{{ __('agricart.pages.fx-learn.market-sentiment') }}</h1>
                <p class="fx-sent__intro">{{ __('agricart.pages.fx-learn.sent_page_intro') }}</p>
            </div>
            <span class="fx-sent__powered" x-text="labels.powered"></span>
        </header>

        <section class="fx-sent__card fx-sent__card--tape">
            <div class="fx-sent__card-head">
                <h2 class="fx-sent__card-title" x-text="labels.tapeTitle"></h2>
                <p class="fx-sent__card-hint" x-text="labels.tapeHint"></p>
            </div>
            <div class="fx-sent__widget fx-sent__widget--tape" wire:ignore>
                <div class="fx-sent__host" x-ref="tapeHost"></div>
            </div>
        </section>

        <div class="fx-sent__grid">
            <section class="fx-sent__card">
                <div class="fx-sent__card-head">
                    <h2 class="fx-sent__card-title" x-text="labels.overviewTitle"></h2>
                    <p class="fx-sent__card-hint" x-text="labels.overviewHint"></p>
                </div>
                <div class="fx-sent__widget fx-sent__widget--overview" wire:ignore>
                    <div class="fx-sent__host" x-ref="overviewHost"></div>
                </div>
            </section>

            <section class="fx-sent__card">
                <div class="fx-sent__card-head">
                    <h2 class="fx-sent__card-title" x-text="labels.heatTitle"></h2>
                    <p class="fx-sent__card-hint" x-text="labels.heatHint"></p>
                </div>
                <div class="fx-sent__widget" wire:ignore>
                    <div class="fx-sent__host" x-ref="heatHost"></div>
                </div>
            </section>
        </div>

        <div class="fx-sent__grid">
            <section class="fx-sent__card">
                <div class="fx-sent__card-head">
                    <h2 class="fx-sent__card-title" x-text="labels.crossTitle"></h2>
                    <p class="fx-sent__card-hint" x-text="labels.crossHint"></p>
                </div>
                <div class="fx-sent__widget" wire:ignore>
                    <div class="fx-sent__host" x-ref="crossHost"></div>
                </div>
            </section>

            <section class="fx-sent__card">
                <div class="fx-sent__card-head">
                    <h2 class="fx-sent__card-title" x-text="labels.taTitle"></h2>
                    <p class="fx-sent__card-hint" x-text="labels.taHint"></p>
                </div>

                <div class="fx-sent__filters">
                    <div class="fx-sent__filter-row">
                        <span class="fx-sent__filter-label" x-text="labels.pair"></span>
                        <div class="fx-sent__chips">
                            <template x-for="pair in pairs" :key="pair.id">
                                <button
                                    type="button"
                                    class="fx-sent__chip"
                                    :class="{ 'is-active': pairId === pair.id }"
                                    x-text="pair.label"
                                    @click="setPair(pair.id)"
                                ></button>
                            </template>
                        </div>
                    </div>
                    <div class="fx-sent__filter-row">
                        <span class="fx-sent__filter-label" x-text="labels.tf"></span>
                        <div class="fx-sent__chips">
                            <template x-for="tf in intervals" :key="tf.id">
                                <button
                                    type="button"
                                    class="fx-sent__chip"
                                    :class="{ 'is-active': interval === tf.id }"
                                    x-text="tf.label"
                                    @click="setInterval(tf.id)"
                                ></button>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="fx-sent__widget fx-sent__widget--ta" wire:ignore>
                    <div class="fx-sent__host" x-ref="taHost"></div>
                </div>
            </section>
        </div>
    </div>

    <script>
        window.agricartFxSentiment = function (boot) {
            return {
                locale: boot.locale || 'en',
                currencies: boot.currencies || [],
                pairs: boot.pairs || [],
                intervals: boot.intervals || [],
                tapeSymbols: boot.tapeSymbols || [],
                overviewTabs: boot.overviewTabs || [],
                labels: boot.labels || {},
                pairId: (boot.pairs && boot.pairs[0] && boot.pairs[0].id) || 'eurusd',
                interval: '60',
                _themeObs: null,

                start() {
                    this.mountAll();
                    this._themeObs = window.agricartWatchTvTheme(() => this.mountAll());
                },

                theme() {
                    return window.agricartTvTheme();
                },

                currentPair() {
                    return this.pairs.find((p) => p.id === this.pairId) || this.pairs[0] || null;
                },

                setPair(id) {
                    if (this.pairId === id) return;
                    this.pairId = id;
                    this.mountTa();
                },

                setInterval(id) {
                    if (this.interval === id) return;
                    this.interval = id;
                    this.mountTa();
                },

                mountAll() {
                    this.mountTape();
                    this.mountOverview();
                    this.mountHeat();
                    this.mountCross();
                    this.mountTa();
                },

                mountTape() {
                    window.agricartInjectTv(
                        this.$refs.tapeHost,
                        'https://s3.tradingview.com/external-embedding/embed-widget-ticker-tape.js',
                        {
                            symbols: this.tapeSymbols,
                            showSymbolLogo: true,
                            isTransparent: true,
                            displayMode: 'adaptive',
                            colorTheme: this.theme(),
                            locale: this.locale,
                        }
                    );
                },

                mountOverview() {
                    window.agricartInjectTv(
                        this.$refs.overviewHost,
                        'https://s3.tradingview.com/external-embedding/embed-widget-market-overview.js',
                        {
                            title: 'Currencies',
                            tabs: this.overviewTabs,
                            width: '100%',
                            height: '100%',
                            showChart: true,
                            showFloatingTooltip: false,
                            locale: this.locale,
                            showSymbolLogo: true,
                            isTransparent: true,
                            colorTheme: this.theme(),
                        }
                    );
                },

                mountHeat() {
                    window.agricartInjectTv(
                        this.$refs.heatHost,
                        'https://s3.tradingview.com/external-embedding/embed-widget-forex-heat-map.js',
                        {
                            width: '100%',
                            height: '100%',
                            currencies: this.currencies,
                            isTransparent: true,
                            colorTheme: this.theme(),
                            locale: this.locale,
                        }
                    );
                },

                mountCross() {
                    window.agricartInjectTv(
                        this.$refs.crossHost,
                        'https://s3.tradingview.com/external-embedding/embed-widget-forex-cross-rates.js',
                        {
                            width: '100%',
                            height: '100%',
                            currencies: this.currencies,
                            isTransparent: true,
                            colorTheme: this.theme(),
                            locale: this.locale,
                        }
                    );
                },

                mountTa() {
                    const pair = this.currentPair();
                    if (!pair) return;
                    window.agricartInjectTv(
                        this.$refs.taHost,
                        'https://s3.tradingview.com/external-embedding/embed-widget-technical-analysis.js',
                        {
                            interval: this.interval,
                            width: '100%',
                            isTransparent: true,
                            height: '100%',
                            symbol: pair.symbol,
                            showIntervalTabs: false,
                            displayMode: 'single',
                            locale: this.locale,
                            colorTheme: this.theme(),
                        }
                    );
                },
            };
        };
    </script>
</x-filament-panels::page>
