@php
    $imageUrls = $imageUrls ?? [];
    $activeUrl = $imageUrls[0] ?? null;
@endphp

<div class="ag-store-product-gallery" data-store-product-gallery>
    <div class="ag-store-product-gallery__main">
        @if ($activeUrl)
            <img
                src="{{ $activeUrl }}"
                alt=""
                class="ag-store-product-gallery__main-image"
                data-store-product-gallery-main
            />
        @else
            <span class="ag-store-product-gallery__placeholder">{{ strtoupper(substr($productService->localizedName($product), 0, 1)) }}</span>
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
                    <img src="{{ $url }}" alt="" loading="lazy" />
                </button>
            @endforeach
        </div>
    @endif
</div>
