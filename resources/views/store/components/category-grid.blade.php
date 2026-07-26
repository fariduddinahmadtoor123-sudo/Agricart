@php
    $columns = min(6, max(3, (int) $settings->category_grid_columns));
    $categories = $categories ?? collect();
    $sectionTitle = $sectionTitle ?? null;
    $productCounts = $productCounts
        ?? ($categories->isNotEmpty() ? $categoryService->storefrontProductCounts($categories) : []);
@endphp

<section class="ag-store-category-section">
    @if (filled($sectionTitle))
        <h2 class="ag-store-section-title">{{ $sectionTitle }}</h2>
    @endif

    @if ($categories->isEmpty())
        <div class="ag-store-empty">{{ $emptyMessage ?? __('agricart.storefront.categories.empty') }}</div>
    @else
        <div @class(['ag-store-category-grid', 'ag-store-category-grid--cols-' . $columns])>
            @foreach ($categories as $item)
                @php
                    $imageUrl = $categoryService->imageUrl($item);
                    $categoryName = $categoryService->localizedName($item);
                    $letter = mb_strtoupper(mb_substr($categoryName, 0, 1));
                    // First row (up to 6) eager — rest lazy. Typical home grids stay under ~100 images.
                    $priority = $loop->index < 6;
                    $comingSoon = $categoryService->isComingSoon($item);
                    $productCount = $comingSoon ? 0 : (int) ($productCounts[$item->id] ?? 0);
                @endphp
                <a
                    href="{{ route('store.category', $item->slug) }}"
                    @class(['ag-store-category-card', 'ag-store-category-card--coming-soon' => $comingSoon])
                >
                    <div class="ag-store-category-card__image-wrap">
                        <span class="ag-store-category-card__placeholder" aria-hidden="true">{{ $letter }}</span>
                        @if ($imageUrl)
                            <img
                                src="{{ $imageUrl }}"
                                alt="{{ $categoryName }}"
                                class="ag-store-category-card__image"
                                width="320"
                                height="320"
                                decoding="async"
                                @if ($priority) loading="eager" fetchpriority="high" @else loading="lazy" @endif
                                onerror="this.classList.add('is-failed')"
                            />
                        @endif
                        @if ($comingSoon)
                            <span class="ag-store-category-card__badge">{{ __('agricart.storefront.categories.coming_soon') }}</span>
                        @endif
                    </div>
                    <div class="ag-store-category-card__body">
                        <span class="ag-store-category-card__name">{{ $categoryName }}</span>
                        <span class="ag-store-category-card__meta">
                            @if ($comingSoon)
                                {{ __('agricart.storefront.categories.coming_soon') }}
                            @else
                                {{ trans_choice('agricart.storefront.categories.product_count', $productCount, ['count' => number_format($productCount)]) }}
                            @endif
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</section>
