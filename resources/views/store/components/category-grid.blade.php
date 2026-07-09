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
                @php
                    $imageUrl = $categoryService->imageUrl($item);
                    $letter = strtoupper(substr($categoryService->localizedName($item), 0, 1));
                    $priority = $loop->index < 4;
                @endphp
                <a href="{{ route('store.category', $item->slug) }}" class="ag-store-category-card">
                    <div class="ag-store-category-card__image-wrap">
                        <span class="ag-store-category-card__placeholder" aria-hidden="true">{{ $letter }}</span>
                        @if ($imageUrl)
                            <img
                                src="{{ $imageUrl }}"
                                alt=""
                                class="ag-store-category-card__image"
                                width="320"
                                height="320"
                                decoding="async"
                                @if ($priority) loading="eager" fetchpriority="high" @else loading="lazy" @endif
                                onerror="this.classList.add('is-failed')"
                            />
                        @endif
                    </div>
                    <span class="ag-store-category-card__name">{{ $categoryService->localizedName($item) }}</span>
                </a>
            @endforeach
        </div>
    @endif
</section>
