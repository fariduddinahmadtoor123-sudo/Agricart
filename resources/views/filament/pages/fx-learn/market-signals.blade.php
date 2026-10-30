@php
    $asset = $this->activeAsset();
    $tf = $this->activeInterval();
    $tape = $this->tapeSymbols();
    $quoteKey = str_replace([':', '/'], '-', $asset['symbol']);
@endphp

<x-filament-panels::page>
    <div
        class="fxs"
        x-data="{
            mountTv(el, src, config) {
                if (! el) return;
                el.replaceChildren();
                const wrap = document.createElement('div');
                wrap.className = 'tradingview-widget-container';
                wrap.style.width = '100%';
                if (config.height) {
                    wrap.style.height = config.height + 'px';
                }
                const widget = document.createElement('div');
                widget.className = 'tradingview-widget-container__widget';
                widget.style.width = '100%';
                if (config.height) {
                    widget.style.height = config.height + 'px';
                }
                wrap.appendChild(widget);
                const script = document.createElement('script');
                script.type = 'text/javascript';
                script.src = src;
                script.async = true;
                // TradingView reads JSON from THIS script tag's body (same tag as src).
                script.text = JSON.stringify(config);
                wrap.appendChild(script);
                el.appendChild(wrap);
            }
        }"
    >
        {{-- Live rates tape (follows selected category) --}}
        <section class="fxs__tape" aria-label="{{ __('agricart.pages.fx-learn.sig_tape_label') }}">
            <div
                class="fxs__tape-frame"
                dir="ltr"
                wire:key="fxs-tape-{{ $this->category }}"
                wire:replace
                x-init="mountTv($el, 'https://s3.tradingview.com/external-embedding/embed-widget-ticker-tape.js', {
                    symbols: @js($tape),
                    showSymbolLogo: true,
                    isTransparent: true,
                    displayMode: 'adaptive',
                    colorTheme: 'light',
                    locale: 'en'
                })"
            ></div>
        </section>

        {{-- Signal stage: asset chips above quote + TA --}}
        <section class="fxs__stage" aria-label="{{ __('agricart.pages.fx-learn.sig_stage_label') }}">
            <header class="fxs__stage-head">
                <div>
                    <p class="fxs__kicker">{{ __('agricart.pages.fx-learn.sig_stage_kicker') }}</p>
                    <h2 class="fxs__stage-title" dir="ltr">{{ $asset['label'] }} · {{ $tf['label'] }}</h2>
                    <p class="fxs__stage-sub">{{ __('agricart.pages.fx-learn.sig_stage_hint') }}</p>
                </div>
                <a
                    class="fxs__btn"
                    href="{{ $this->tradingViewUrl() }}"
                    target="_blank"
                    rel="noopener noreferrer"
                >{{ __('agricart.pages.fx-learn.sig_open_chart') }}</a>
            </header>

            <div class="fxs__assets fxs__assets--stage" role="listbox" aria-label="{{ __('agricart.pages.fx-learn.sig_assets_label') }}">
                @forelse ($this->visibleAssets() as $row)
                    <button
                        type="button"
                        role="option"
                        class="fxs__chip fxs__chip--asset"
                        wire:key="fxs-asset-{{ $row['id'] }}"
                        wire:click="setAsset(@js($row['id']))"
                        aria-selected="{{ $this->assetId === $row['id'] ? 'true' : 'false' }}"
                        @class(['is-on' => $this->assetId === $row['id']])
                        title="{{ $row['symbol'] }}"
                    >{{ $row['label'] }}</button>
                @empty
                    <span class="fxs__empty-hint">{{ __('agricart.pages.fx-learn.sig_assets_empty') }}</span>
                @endforelse
            </div>

            <div class="fxs__stage-grid">
                <div
                    class="fxs__quote"
                    dir="ltr"
                    wire:key="fxs-quote-{{ $this->assetId }}-{{ $quoteKey }}"
                    wire:replace
                    x-init="mountTv($el, 'https://s3.tradingview.com/external-embedding/embed-widget-single-quote.js', {
                        symbol: @js($asset['symbol']),
                        width: '100%',
                        isTransparent: true,
                        colorTheme: 'light',
                        locale: 'en'
                    })"
                ></div>

                <div
                    class="fxs__ta"
                    dir="ltr"
                    wire:key="fxs-ta-{{ $this->assetId }}-{{ $quoteKey }}-{{ $tf['interval'] }}"
                    wire:replace
                    x-init="mountTv($el, 'https://s3.tradingview.com/external-embedding/embed-widget-technical-analysis.js', {
                        interval: @js($tf['interval']),
                        width: '100%',
                        isTransparent: true,
                        height: 400,
                        symbol: @js($asset['symbol']),
                        showIntervalTabs: false,
                        displayMode: 'single',
                        locale: 'en',
                        colorTheme: 'light'
                    })"
                ></div>
            </div>
        </section>

        {{-- Heatmap + cross rates (static — no symbol sync) --}}
        <section class="fxs__markets" aria-label="{{ __('agricart.pages.fx-learn.sig_markets_label') }}">
            <div class="fxs__panel">
                <header class="fxs__panel-head">
                    <h3 class="fxs__panel-title">{{ __('agricart.pages.fx-learn.sig_heatmap_title') }}</h3>
                    <p class="fxs__panel-sub">{{ __('agricart.pages.fx-learn.sig_heatmap_hint') }}</p>
                </header>
                <div
                    class="fxs__panel-body"
                    dir="ltr"
                    wire:ignore
                    x-init="mountTv($el, 'https://s3.tradingview.com/external-embedding/embed-widget-forex-heat-map.js', {
                        width: '100%',
                        height: 380,
                        currencies: ['EUR','USD','JPY','GBP','CHF','AUD','CAD','NZD','CNY'],
                        isTransparent: true,
                        colorTheme: 'light',
                        locale: 'en'
                    })"
                ></div>
            </div>

            <div class="fxs__panel">
                <header class="fxs__panel-head">
                    <h3 class="fxs__panel-title">{{ __('agricart.pages.fx-learn.sig_cross_title') }}</h3>
                    <p class="fxs__panel-sub">{{ __('agricart.pages.fx-learn.sig_cross_hint') }}</p>
                </header>
                <div
                    class="fxs__panel-body"
                    dir="ltr"
                    wire:ignore
                    x-init="mountTv($el, 'https://s3.tradingview.com/external-embedding/embed-widget-forex-cross-rates.js', {
                        width: '100%',
                        height: 380,
                        currencies: ['EUR','USD','JPY','GBP','CHF','AUD','CAD','NZD'],
                        isTransparent: true,
                        colorTheme: 'light',
                        locale: 'en'
                    })"
                ></div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
