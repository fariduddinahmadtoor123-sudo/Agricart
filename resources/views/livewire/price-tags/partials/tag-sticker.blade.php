@php
    $options = $this->displayOptions();
    $tag = $tag ?? [];
@endphp

<article
    @class([
        'agricart-price-tags__sticker',
        'agricart-price-tags__sticker--preview' => $preview ?? true,
    ])
    @if (($options['locale'] ?? 'en') === 'ur') dir="rtl" @endif
>
    @if ($options['show_store_name'] && filled($tag['store_name'] ?? null))
        <p class="agricart-price-tags__store">{{ $tag['store_name'] }}</p>
    @endif

    @if ($options['show_product_name'])
        @if ($options['locale'] === 'both')
            @if (filled($tag['product_name_en'] ?? null))
                <h4 class="agricart-price-tags__product-name">{{ $tag['product_name_en'] }}</h4>
            @endif
            @if (filled($tag['product_name_ur'] ?? null))
                <p class="agricart-price-tags__product-name-ur" dir="rtl">{{ $tag['product_name_ur'] }}</p>
            @endif
        @else
            <h4 class="agricart-price-tags__product-name">{{ $tag['product_name'] ?? '—' }}</h4>
        @endif
    @endif

    @if ($options['show_cost_price'] || $options['show_sale_price'])
        <div class="agricart-price-tags__prices">
            @if ($options['show_cost_price'])
                <div class="agricart-price-tags__price-line">
                    <span>{{ __('price_tags.fields.cost_price') }}</span>
                    <strong>{{ $tag['cost_price'] ?: '—' }}</strong>
                </div>
            @endif
            @if ($options['show_sale_price'])
                <div class="agricart-price-tags__price-line is-sale">
                    <span>{{ __('price_tags.fields.sale_price') }}</span>
                    <strong>{{ $tag['sale_price'] ?: '—' }}</strong>
                </div>
            @endif
        </div>
    @endif

    @if (($options['code_type'] ?? 'barcode') !== 'none')
        <div class="agricart-price-tags__code-wrap">
            @if (($options['code_type'] ?? 'barcode') === 'qr')
                {!! $tag['qr_svg'] ?? '' !!}
            @else
                {!! $tag['barcode_svg'] ?? '' !!}
            @endif
        </div>
        <p class="agricart-price-tags__code-foot">{{ $tag['scan_code'] ?? '' }}</p>
    @endif
</article>
