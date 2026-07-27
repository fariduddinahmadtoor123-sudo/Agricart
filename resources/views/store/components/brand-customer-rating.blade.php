@php
    /** @var array{average: float|null, count: int}|null $summary */
    $summary = $summary ?? null;
    $variant = $variant ?? 'full';
@endphp

@if (is_array($summary) && ($summary['count'] ?? 0) > 0 && $summary['average'] !== null)
    @php
        $average = (float) $summary['average'];
        $count = (int) $summary['count'];
        $aria = __('agricart.storefront.brand_reviews.rating_customers_aria', [
            'average' => number_format($average, 1),
            'count' => $count,
        ]);
    @endphp
    <span
        @class([
            'ag-store-brand-customer-rating',
            'ag-store-brand-customer-rating--'.$variant,
        ])
        title="{{ $aria }}"
        aria-label="{{ $aria }}"
    >
        <span class="ag-store-brand-customer-rating__stars" aria-hidden="true">
            @for ($i = 1; $i <= 5; $i++)
                @php
                    $fill = $average - ($i - 1);
                    $starClass = $fill >= 0.75
                        ? 'is-full'
                        : ($fill >= 0.25 ? 'is-half' : 'is-empty');
                @endphp
                <span class="ag-store-brand-customer-rating__star {{ $starClass }}">★</span>
            @endfor
        </span>
        <span class="ag-store-brand-customer-rating__score">
            {{ __('agricart.storefront.brand_reviews.rating_customers_score', [
                'average' => number_format($average, 1),
            ]) }}
        </span>
        @if ($variant === 'full')
            <span class="ag-store-brand-customer-rating__count">
                {{ __('agricart.storefront.brand_reviews.count', ['count' => $count]) }}
            </span>
        @endif
    </span>
@endif
