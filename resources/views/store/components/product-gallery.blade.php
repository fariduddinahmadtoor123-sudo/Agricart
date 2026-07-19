@php
    $imageUrls = $imageUrls ?? [];
    $activeUrl = $imageUrls[0] ?? null;
@endphp

<div class="ag-store-product-gallery" data-store-product-gallery>
    <div class="ag-store-product-gallery__main">
        @if ($activeUrl)
            <img
                src="{{ $activeUrl }}"
                alt="{{ $productService->localizedName($product) }}"
                class="ag-store-product-gallery__main-image"
                data-store-product-gallery-main
                width="720"
                height="720"
                decoding="async"
                fetchpriority="high"
            />
        @else
            <span class="ag-store-product-gallery__placeholder">{{ mb_strtoupper(mb_substr($productService->localizedName($product), 0, 1)) }}</span>
        @endif
    </div>

    @if (count($imageUrls) > 1)
        <div class="ag-store-product-gallery__thumbs" role="list" aria-label="{{ __('agricart.storefront.product.gallery') }}">
            @foreach ($imageUrls as $index => $url)
                <button
                    type="button"
                    class="ag-store-product-gallery__thumb @if ($index === 0) is-active @endif"
                    role="listitem"
                    data-store-product-gallery-thumb
                    data-image-url="{{ $url }}"
                    aria-label="{{ __('agricart.storefront.product.image_number', ['number' => $index + 1]) }}"
                    aria-pressed="{{ $index === 0 ? 'true' : 'false' }}"
                >
                    <img src="{{ $url }}" alt="{{ __('agricart.storefront.product.image_number', ['number' => $index + 1]) }}" loading="lazy" decoding="async" width="72" height="72" />
                </button>
            @endforeach
        </div>
    @endif
</div>
