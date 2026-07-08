@php
    $query = $query ?? '';
    $products = $products ?? null;
    $columns = min(5, max(4, (int) (($settings ?? null)?->category_grid_columns ?? 5)));
@endphp

@if (! filled($query))
    <div class="ag-store-empty">{{ __('agricart.storefront.search.empty_query') }}</div>
@elseif ($products === null || $products->isEmpty())
    <div class="ag-store-empty">{{ __('agricart.storefront.search.no_results', ['query' => $query]) }}</div>
@else
    <div @class(['ag-store-product-grid', 'ag-store-product-grid--cols-' . $columns, 'ag-store-search-results-grid'])>
        @foreach ($products as $product)
            @php
                $imageUrl = $productService->imageUrl($product);
                $productUrl = route('store.product', $product->slug);
                $name = $productService->localizedName($product);
            @endphp
            <article class="ag-store-product-card">
                <a href="{{ $productUrl }}" class="ag-store-product-card__image-link">
                    <div class="ag-store-product-card__image-wrap">
                        @if ($imageUrl)
                            <img src="{{ $imageUrl }}" alt="" class="ag-store-product-card__image" loading="lazy" />
                        @else
                            <span class="ag-store-product-card__placeholder">{{ strtoupper(substr($name, 0, 1)) }}</span>
                        @endif
                    </div>
                </a>
                <a href="{{ $productUrl }}" class="ag-store-product-card__body">
                    <h3 class="ag-store-product-card__name">{{ $name }}</h3>
                    @if ($brand = $productService->localizedBrandName($product))
                        <p class="ag-store-product-card__brand">{{ $brand }}</p>
                    @endif
                    @if ($price = $productService->formattedRetailPrice($product))
                        <p class="ag-store-product-card__price">{{ __('agricart.storefront.products.price', ['amount' => $price]) }}</p>
                    @elseif (! ($onlineStoreChannel['show_prices'] ?? true))
                        <p class="ag-store-product-card__offline-msg">{{ $onlineStoreChannel['offline_message'] ?? __('sales_channels.defaults.offline_message') }}</p>
                    @endif
                </a>
            </article>
        @endforeach
    </div>

    @if ($products->hasPages())
        <div class="ag-store-pagination">
            {{ $products->links() }}
        </div>
    @endif
@endif
