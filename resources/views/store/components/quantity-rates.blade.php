@php
    $rows = $rows ?? [];
    $variant = $variant ?? 'card';
@endphp

@if ($rows !== [])
    <p class="ag-store-qty-rates ag-store-qty-rates--{{ $variant }}" aria-label="{{ __('agricart.storefront.product.price_table.qty_rates_lead') }}">
        @foreach ($rows as $row)
            <span class="ag-store-qty-rates__item">{{ __('agricart.storefront.product.price_table.qty_rate', [
                'qty' => $row['min_qty'],
                'price' => __('agricart.storefront.products.price', ['amount' => $row['formatted']]),
            ]) }}</span>
        @endforeach
    </p>
@endif
