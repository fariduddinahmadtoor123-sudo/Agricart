@php
    /** @var array{score: int, max: int, percent: int, stars: float}|null $summary */
    $summary = $summary ?? null;
    $variant = $variant ?? 'full';
@endphp

@if (is_array($summary))
    @php
        $starsOutOfFive = (float) $summary['stars'];
        $aria = __('agricart.storefront.product.brand_review.rating_aria', [
            'stars' => number_format($starsOutOfFive, 1),
            'score' => $summary['score'],
            'max' => $summary['max'],
            'percent' => $summary['percent'],
        ]);
    @endphp
    <span
        @class([
            'ag-store-brand-rating',
            'ag-store-brand-rating--'.$variant,
        ])
        title="{{ $aria }}"
        aria-label="{{ $aria }}"
    >
        <span class="ag-store-brand-rating__stars" aria-hidden="true">
            @for ($i = 1; $i <= 5; $i++)
                @php
                    $fill = $starsOutOfFive - ($i - 1);
                    $starClass = $fill >= 0.75
                        ? 'is-full'
                        : ($fill >= 0.25 ? 'is-half' : 'is-empty');
                @endphp
                <span class="ag-store-brand-rating__star {{ $starClass }}">★</span>
            @endfor
        </span>
        @if ($variant === 'full')
            <span class="ag-store-brand-rating__score">
                {{ __('agricart.storefront.product.brand_review.rating_score', [
                    'score' => $summary['score'],
                    'max' => $summary['max'],
                ]) }}
            </span>
        @endif
        <span class="ag-store-brand-rating__percent">
            {{ __('agricart.storefront.product.brand_review.rating_percent', [
                'percent' => $summary['percent'],
            ]) }}
        </span>
    </span>
@endif
