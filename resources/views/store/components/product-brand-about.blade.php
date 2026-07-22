@php
    $brandName = $presenter->localizedBrandName($product);
    $brandLogoUrl = $presenter->brandImageUrl($product);
    $brandDescription = $presenter->localizedBrandDescription($product);
    $websiteUrl = filled($product->brand?->website_url) ? $product->brand->website_url : null;
@endphp

<div class="ag-store-pdp__brand-about">
    <div class="ag-store-pdp__brand-about-head">
        @if ($brandLogoUrl)
            <img
                src="{{ $brandLogoUrl }}"
                alt=""
                class="ag-store-pdp__brand-about-logo"
                loading="lazy"
                width="56"
                height="56"
            />
        @endif
        <div class="ag-store-pdp__brand-about-meta">
            @if (filled($brandName))
                <p class="ag-store-pdp__brand-about-name">{{ $brandName }}</p>
            @endif
            @if ($websiteUrl)
                <a
                    href="{{ $websiteUrl }}"
                    class="ag-store-pdp__brand-about-link"
                    target="_blank"
                    rel="noopener noreferrer"
                >{{ __('agricart.storefront.product.brand_website') }}</a>
            @endif
        </div>
    </div>

    @if (filled($brandDescription))
        <div class="ag-store-pdp__prose ag-store-pdp__brand-about-body">{!! nl2br(e($brandDescription)) !!}</div>
    @endif
</div>
