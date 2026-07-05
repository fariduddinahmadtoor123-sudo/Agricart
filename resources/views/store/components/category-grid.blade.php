@php
    $columns = min(6, max(3, (int) $settings->category_grid_columns));
    $categories = $categories ?? collect();
    $sectionTitle = $sectionTitle ?? null;
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
                <a href="{{ route('store.category', $item->slug) }}" class="ag-store-category-card">
                    <div class="ag-store-category-card__image-wrap">
                        @if ($imageUrl = $categoryService->imageUrl($item))
                            <img src="{{ $imageUrl }}" alt="" class="ag-store-category-card__image" loading="lazy" />
                        @else
                            <span class="ag-store-category-card__placeholder">{{ strtoupper(substr($categoryService->localizedName($item), 0, 1)) }}</span>
                        @endif
                    </div>
                    <span class="ag-store-category-card__name">{{ $categoryService->localizedName($item) }}</span>
                </a>
            @endforeach
        </div>
    @endif
</section>
