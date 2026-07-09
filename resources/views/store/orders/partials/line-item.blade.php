@props(['item', 'index', 'sales'])

@php
    $thumbUrl = $item->thumbnailUrl();
    $initial = strtoupper(mb_substr($item->displayName(), 0, 1));
@endphp

<li class="ag-store-orders__line">
    <span class="ag-store-orders__line-sn" aria-hidden="true">{{ $index + 1 }}</span>
    <div class="ag-store-orders__line-thumb">
        @if ($thumbUrl)
            <img src="{{ $thumbUrl }}" alt="" class="ag-store-orders__line-img" loading="lazy" width="56" height="56" />
        @else
            <span class="ag-store-orders__line-placeholder">{{ $initial }}</span>
        @endif
    </div>
    <div class="ag-store-orders__line-body">
        <span class="ag-store-orders__line-name">{{ $item->displayName() }}</span>
        @if ($item->displayBrand() !== '—')
            <span class="ag-store-orders__line-brand">{{ $item->displayBrand() }}</span>
        @endif
        <p class="ag-store-orders__line-meta">
            <span>{{ __('agricart.storefront.checkout.qty_line', ['qty' => $sales->formatQty((float) $item->quantity)]) }}</span>
            <span aria-hidden="true">·</span>
            <span>{{ __('agricart.storefront.products.price', ['amount' => $sales->formatMoney((float) $item->unit_price)]) }} {{ __('agricart.storefront.checkout.each') }}</span>
        </p>
    </div>
    <div class="ag-store-orders__line-total">
        {{ __('agricart.storefront.products.price', ['amount' => $sales->formatMoney((float) $item->line_total)]) }}
    </div>
</li>
