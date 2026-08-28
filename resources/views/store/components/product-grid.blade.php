@php
    $columns = min(6, max(3, (int) ($settings->category_grid_columns ?? 4)));
    $products = $products ?? collect();
    $sectionTitle = $sectionTitle ?? __('agricart.storefront.products.title');
    $eagerImages = (bool) ($eagerImages ?? false);
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
                    // Grid cards only need the main image URL — gallery thumbs were loading every
                    // full-size WebP (up to 1600px) for 64px buttons and crushed mobile bandwidth.
                    $mainImage = $productService->imageData($product);
                    $photoCount = count(array_values(array_unique(array_filter(
                        [$product->image_path, ...($product->gallery_images ?? [])],
                        static fn (mixed $path): bool => is_string($path) && filled($path),
                    ))));
                    $productUrl = route('store.product', $product->slug);
                    $priority = $loop->index < 4;
                    $comingSoon = $productService->isComingSoon($product);
                @endphp
                <article @class(['ag-store-product-card', 'ag-store-product-card--coming-soon' => $comingSoon])>
                    <div class="ag-store-product-card__media" data-store-product-card>
                        <a href="{{ $productUrl }}" class="ag-store-product-card__image-link">
                            <div class="ag-store-product-card__image-wrap">
                                <span class="ag-store-product-card__placeholder" aria-hidden="true">{{ mb_strtoupper(mb_substr($productService->localizedName($product), 0, 1)) }}</span>
                                @if ($mainImage !== null)
                                    <img
                                        src="{{ $mainImage['url'] }}"
                                        alt="{{ $productService->localizedName($product) }}"
                                        class="ag-store-product-card__image"
                                        data-store-product-card-main
                                        width="{{ $mainImage['width'] ?? 320 }}"
                                        height="{{ $mainImage['height'] ?? 320 }}"
                                        decoding="async"
                                        @if ($eagerImages)
                                            loading="eager"
                                            @if ($priority) fetchpriority="high" @endif
                                        @elseif ($priority)
                                            loading="eager" fetchpriority="high"
                                        @else
                                            loading="lazy"
                                        @endif
                                        onerror="this.classList.add('is-failed')"
                                    />
                                @endif

                                @if ($comingSoon)
                                    <span class="ag-store-product-card__badge">{{ __('agricart.storefront.products.coming_soon') }}</span>
                                @elseif ($photoCount > 1)
                                    <span class="ag-store-product-card__photo-count">
                                        {{ __('agricart.storefront.products.photo_count', ['count' => $photoCount]) }}
                                    </span>
                                @endif
                            </div>
                        </a>
                    </div>

                    <a href="{{ $productUrl }}" class="ag-store-product-card__body">
                        <h3 class="ag-store-product-card__name">{{ $productService->localizedName($product) }}</h3>
                        @php
                            $cardPresenter = app(\App\Services\Store\StorefrontProductPresenter::class);
                            $cardProductRating = $cardPresenter->agricartProductRatingSummary($product);
                            $cardBrandRating = $product->brand
                                ? $cardPresenter->agricartBrandRatingSummary($product)
                                : null;
                        @endphp
                        @if ($product->brand)
                            <p class="ag-store-product-card__brand">
                                <span class="ag-store-product-card__brand-name">{{ $productService->localizedBrandName($product) }}</span>
                                @include('store.components.brand-rating', [
                                    'summary' => $cardBrandRating,
                                    'variant' => 'compact',
                                ])
                            </p>
                        @endif
                        @if ($cardProductRating !== null)
                            <p class="ag-store-product-card__product-rating">
                                @include('store.components.brand-rating', [
                                    'summary' => $cardProductRating,
                                    'variant' => 'compact',
                                ])
                            </p>
                        @endif
                        @if ($comingSoon)
                            <p class="ag-store-product-card__coming-soon">{{ __('agricart.storefront.products.coming_soon') }}</p>
                        @elseif ($price = $productService->formattedRetailPrice($product))
                            <p class="ag-store-product-card__price">{{ __('agricart.storefront.products.price', ['amount' => $price]) }}</p>
                            @include('store.components.quantity-rates', [
                                'rows' => $cardPresenter->compactQuantityRates($product),
                                'variant' => 'card',
                            ])
                        @elseif (! ($onlineStoreChannel['show_prices'] ?? true))
                            <p class="ag-store-product-card__offline-msg">{{ $onlineStoreChannel['offline_message'] ?? __('sales_channels.defaults.offline_message') }}</p>
                        @elseif (! $product->isPriced())
                            <p class="ag-store-product-card__price-pending">{{ __('agricart.storefront.product.price_pending_short') }}</p>
                        @endif
                    </a>
                </article>
            @endforeach
        </div>
    @endif
</section>
