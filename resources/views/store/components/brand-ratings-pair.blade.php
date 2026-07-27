@php
    /** @var array{score: int, max: int, percent: int, stars: float}|null $agricartSummary */
    $agricartSummary = $agricartSummary ?? null;
    /** @var array{average: float|null, count: int}|null $customerSummary */
    $customerSummary = $customerSummary ?? ['average' => null, 'count' => 0];
    $variant = $variant ?? 'full';
    $hasAgricart = is_array($agricartSummary);
    $hasCustomers = is_array($customerSummary)
        && ($customerSummary['count'] ?? 0) > 0
        && $customerSummary['average'] !== null;
@endphp

@if ($hasAgricart || $hasCustomers)
    <div
        @class([
            'ag-store-brand-ratings-pair',
            'ag-store-brand-ratings-pair--'.$variant,
        ])
    >
        @if ($hasAgricart)
            <div class="ag-store-brand-ratings-pair__row">
                <span class="ag-store-brand-ratings-pair__label">
                    {{ __('agricart.storefront.brand_reviews.rating_agricart_label') }}
                </span>
                @include('store.components.brand-rating', [
                    'summary' => $agricartSummary,
                    'variant' => $variant,
                ])
            </div>
        @endif

        @if ($hasCustomers)
            <div class="ag-store-brand-ratings-pair__row">
                <span class="ag-store-brand-ratings-pair__label">
                    {{ __('agricart.storefront.brand_reviews.rating_customers_label') }}
                </span>
                @include('store.components.brand-customer-rating', [
                    'summary' => $customerSummary,
                    'variant' => $variant,
                ])
            </div>
        @endif
    </div>
@endif
