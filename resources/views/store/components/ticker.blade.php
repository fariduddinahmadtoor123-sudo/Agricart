<div class="ag-store-ticker" data-store-ticker data-speed="{{ $settings->ticker_speed }}">
    <div class="ag-store-ticker__controls" aria-label="{{ __('agricart.storefront.ticker.controls') }}">
        <button type="button" class="ag-store-ticker__btn" data-store-ticker-pause aria-pressed="false" title="{{ __('agricart.storefront.ticker.pause') }}">⏸</button>
        <button type="button" class="ag-store-ticker__btn" data-store-ticker-slower title="{{ __('agricart.storefront.ticker.slower') }}">−</button>
        <button type="button" class="ag-store-ticker__btn" data-store-ticker-faster title="{{ __('agricart.storefront.ticker.faster') }}">+</button>
    </div>
    <div class="ag-store-ticker__viewport">
        <div class="ag-store-ticker__track" data-store-ticker-track>
            @foreach ([1, 2] as $copy)
                <div class="ag-store-ticker__content" @if ($copy === 2) aria-hidden="true" @endif>
                    @foreach ($ticker_messages as $message)
                        <span class="ag-store-ticker__item">
                            @if (filled($message->link))
                                <a href="{{ $message->link }}">{{ $message->localizedText() }}</a>
                            @else
                                {{ $message->localizedText() }}
                            @endif
                        </span>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</div>
