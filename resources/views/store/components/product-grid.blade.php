@php
    $columns = min(6, max(3, (int) ($settings->category_grid_columns ?? 4)));
    $products = $products ?? collect();
    $sectionTitle = $sectionTitle ?? __('agricart.storefront.products.title');
@endphp

<section class="ag-store-product-section">
    @if (filled($sectionTitle))
        <h2 class="ag-store-section-title">{{ $sectionTitle }}</h2>
    @endif

    @if ($products->isEmpty())
        <div class="ag-store-empty">{{ $emptyMessage ?? __('agricart.storefront.products.empty') }}</div>
    @else
        <div @class(['ag-store-product-grid', 'ag-store-product-grid--cols-' . $columns])>
            @foreach ($products as $product)
                @php
                    $imageUrls = $productService->allImageUrls($product);
                    $productUrl = route('store.product', $product->slug);
                @endphp
                <article class="ag-store-product-card">
                    <div class="ag-store-product-card__media" data-store-product-card>
                        <a href="{{ $productUrl }}" class="ag-store-product-card__image-link">
                            <div class="ag-store-product-card__image-wrap">
                                @if ($imageUrls !== [])
                                    <img
                                        src="{{ $imageUrls[0] }}"
                                        alt=""
                                        class="ag-store-product-card__image"
                                        data-store-product-card-main
                                        loading="lazy"
                                    />
                                @else
                                    <span class="ag-store-product-card__placeholder">{{ strtoupper(substr($productService->localizedName($product), 0, 1)) }}</span>
                                @endif

                                @if (count($imageUrls) > 1)
                                    <span class="ag-store-product-card__photo-count">
                                        {{ __('agricart.storefront.products.photo_count', ['count' => count($imageUrls)]) }}
                                    </span>
                                @endif
                            </div>
                        </a>

                        @if (count($imageUrls) > 1)
                            <div class="ag-store-product-card__thumbs" role="list" aria-label="{{ __('agricart.storefront.product.gallery') }}">
                                @foreach ($imageUrls as $index => $url)
                                    <button
                                        type="button"
                                        class="ag-store-product-card__thumb @if ($index === 0) is-active @endif"
                                        role="listitem"
                                        data-store-product-card-thumb
                                        data-image-url="{{ $url }}"
                                        aria-label="{{ __('agricart.storefront.product.image_number', ['number' => $index + 1]) }}"
                                        aria-pressed="{{ $index === 0 ? 'true' : 'false' }}"
                                    >
                                        <img src="{{ $url }}" alt="" loading="lazy" />
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <a href="{{ $productUrl }}" class="ag-store-product-card__body">
                        <h3 class="ag-store-product-card__name">{{ $productService->localizedName($product) }}</h3>
                        @if ($product->brand)
                            <p class="ag-store-product-card__brand">{{ $product->brand->name_en }}</p>
                        @endif
                        @if ($price = $productService->formattedRetailPrice($product))
                            <p class="ag-store-product-card__price">{{ __('agricart.storefront.products.price', ['amount' => $price]) }}</p>
                        @endif
                    </a>
                </article>
            @endforeach
        </div>
    @endif
</section>
