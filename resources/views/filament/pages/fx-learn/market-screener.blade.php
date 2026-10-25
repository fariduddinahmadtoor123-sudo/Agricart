@php
    $boot = $this->bootData();
@endphp

<x-filament-panels::page>
    @include('filament.pages.fx-learn.partials.tv-inject')

    <div
        class="fx-scr"
        x-data="window.agricartFxScreener(@js($boot))"
        x-init="start()"
    >
        <header class="fx-scr__head">
            <div>
                <h1 class="fx-scr__title">{{ __('agricart.pages.fx-learn.market-screener') }}</h1>
                <p class="fx-scr__intro">{{ __('agricart.pages.fx-learn.scr_page_intro') }}</p>
            </div>
            <span class="fx-scr__powered" x-text="labels.powered"></span>
        </header>

        <div class="fx-scr__filters">
            <div class="fx-scr__filter-row">
                <span class="fx-scr__filter-label" x-text="labels.screen"></span>
                <div class="fx-scr__chips">
                    <template x-for="item in screens" :key="item.id">
                        <button
                            type="button"
                            class="fx-scr__chip"
                            :class="{ 'is-active': screen === item.id }"
                            x-text="item.label"
                            @click="setScreen(item.id)"
                        ></button>
                    </template>
                </div>
            </div>
            <div class="fx-scr__filter-row">
                <span class="fx-scr__filter-label" x-text="labels.columns"></span>
                <div class="fx-scr__chips">
                    <template x-for="item in columns" :key="item.id">
                        <button
                            type="button"
                            class="fx-scr__chip"
                            :class="{ 'is-active': column === item.id }"
                            x-text="item.label"
                            @click="setColumn(item.id)"
                        ></button>
                    </template>
                </div>
            </div>
        </div>

        <section class="fx-scr__card">
            <div class="fx-scr__widget" wire:ignore>
                <div class="fx-scr__host" x-ref="screenerHost"></div>
            </div>
        </section>
    </div>

    <script>
        window.agricartFxScreener = function (boot) {
            return {
                locale: boot.locale || 'en',
                screens: boot.screens || [],
                columns: boot.columns || [],
                labels: boot.labels || {},
                screen: 'general',
                column: 'overview',
                _themeObs: null,

                start() {
                    this.mount();
                    this._themeObs = window.agricartWatchTvTheme(() => this.mount());
                },

                setScreen(id) {
                    if (this.screen === id) return;
                    this.screen = id;
                    this.mount();
                },

                setColumn(id) {
                    if (this.column === id) return;
                    this.column = id;
                    this.mount();
                },

                mount() {
                    window.agricartInjectTv(
                        this.$refs.screenerHost,
                        'https://s3.tradingview.com/external-embedding/embed-widget-screener.js',
                        {
                            width: '100%',
                            height: '100%',
                            defaultColumn: this.column,
                            defaultScreen: this.screen,
                            showToolbar: true,
                            locale: this.locale,
                            market: 'forex',
                            isTransparent: true,
                            colorTheme: window.agricartTvTheme(),
                        }
                    );
                },
            };
        };
    </script>
</x-filament-panels::page>
