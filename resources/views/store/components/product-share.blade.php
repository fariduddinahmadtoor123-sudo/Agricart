@php
    $shareUrl = route('store.product', $product->slug);
    $shareTitle = $productService->localizedName($product);
    $whatsappText = trim($shareTitle.' '.$shareUrl);
    $whatsappUrl = 'https://wa.me/?text='.rawurlencode($whatsappText);
    $facebookUrl = 'https://www.facebook.com/sharer/sharer.php?u='.rawurlencode($shareUrl);
@endphp

<section class="ag-store-product-share" aria-label="{{ __('agricart.storefront.product.share_label') }}">
    <h2 class="ag-store-product-share__title">{{ __('agricart.storefront.product.share_label') }}</h2>
    <div class="ag-store-product-share__actions">
        <a
            href="{{ $whatsappUrl }}"
            class="ag-store-btn ag-store-btn--secondary ag-store-product-share__btn"
            target="_blank"
            rel="noopener noreferrer"
        >{{ __('agricart.storefront.product.share_whatsapp') }}</a>
        <a
            href="{{ $facebookUrl }}"
            class="ag-store-btn ag-store-btn--secondary ag-store-product-share__btn"
            target="_blank"
            rel="noopener noreferrer"
        >{{ __('agricart.storefront.product.share_facebook') }}</a>
        <button
            type="button"
            class="ag-store-btn ag-store-btn--secondary ag-store-product-share__btn"
            data-store-copy-link
            data-copy-url="{{ $shareUrl }}"
            data-copied-label="{{ __('agricart.storefront.product.share_copied') }}"
        >{{ __('agricart.storefront.product.share_copy') }}</button>
    </div>
</section>
