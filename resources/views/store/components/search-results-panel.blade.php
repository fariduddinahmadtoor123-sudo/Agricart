@php
    $query = $query ?? '';
    $products = $products ?? null;
    $categories = $categories ?? collect();
    $pages = $pages ?? collect();
    $hasProducts = $products !== null && $products->isNotEmpty();
    $hasCategories = $categories->isNotEmpty();
    $hasPages = $pages->isNotEmpty();
    $columns = min(5, max(4, (int) (($settings ?? null)?->category_grid_columns ?? 5)));
@endphp

@if (! filled($query))
    <div class="ag-store-empty">{{ __('agricart.storefront.search.empty_query') }}</div>
@elseif (! $hasProducts && ! $hasCategories && ! $hasPages)
    <div class="ag-store-empty">{{ __('agricart.storefront.search.no_results', ['query' => $query]) }}</div>
@else
    <div class="ag-store-unified-search">
        @if ($hasCategories)
            <section class="ag-store-search-group" aria-labelledby="ag-store-search-categories">
                <div class="ag-store-search-group__head">
                    <h2 id="ag-store-search-categories">{{ __('agricart.storefront.search.categories') }}</h2>
                    <span>{{ $categories->count() }}</span>
                </div>
                <div class="ag-store-search-link-grid">
                    @foreach ($categories as $result)
                        <a href="{{ $result['url'] }}" class="ag-store-search-link-card">
                            <span class="ag-store-search-link-card__visual">
                                @if ($result['image'])
                                    <img src="{{ $result['image'] }}" alt="{{ $result['name'] }}" loading="lazy" decoding="async" width="64" height="64" />
                                @else
                                    <span aria-hidden="true">{{ mb_strtoupper(mb_substr($result['name'], 0, 1)) }}</span>
                                @endif
                            </span>
                            <span class="ag-store-search-link-card__content">
                                <span class="ag-store-search-link-card__type">{{ $result['type_label'] }}</span>
                                <strong>{{ $result['name'] }}</strong>
                                @if ($result['meta'])
                                    <small>{{ $result['meta'] }}</small>
                                @endif
                                @if ($result['excerpt'])
                                    <span class="ag-store-search-link-card__excerpt">{{ $result['excerpt'] }}</span>
                                @endif
                            </span>
                            <span class="ag-store-search-link-card__arrow" aria-hidden="true">→</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($hasPages)
            <section class="ag-store-search-group" aria-labelledby="ag-store-search-pages">
                <div class="ag-store-search-group__head">
                    <h2 id="ag-store-search-pages">{{ __('agricart.storefront.search.pages') }}</h2>
                    <span>{{ $pages->count() }}</span>
                </div>
                <div class="ag-store-search-page-grid">
                    @foreach ($pages as $result)
                        <a href="{{ $result['url'] }}" class="ag-store-search-page-card">
                            <span class="ag-store-search-page-card__icon" aria-hidden="true">§</span>
                            <span>
                                <small>{{ $result['type_label'] }}</small>
                                <strong>{{ $result['name'] }}</strong>
                                @if ($result['excerpt'])
                                    <span>{{ $result['excerpt'] }}</span>
                                @endif
                            </span>
                            <span aria-hidden="true">→</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($hasProducts)
            <section class="ag-store-search-group" aria-labelledby="ag-store-search-products">
                <div class="ag-store-search-group__head">
                    <h2 id="ag-store-search-products">{{ __('agricart.storefront.search.products') }}</h2>
                    <span>{{ $products->total() }}</span>
                </div>
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
                                        <img src="{{ $imageUrl }}" alt="{{ $productService->localizedName($product) }}" class="ag-store-product-card__image" loading="lazy" decoding="async" width="320" height="320" />
                                    @else
                                        <span class="ag-store-product-card__placeholder">{{ mb_strtoupper(mb_substr($name, 0, 1)) }}</span>
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
            </section>
        @endif
    </div>
@endif
