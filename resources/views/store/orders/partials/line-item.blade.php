@props(['item', 'index', 'sales', 'order' => null, 'productReview' => null])

@php
    $thumbUrl = $item->thumbnailUrl();
    $initial = strtoupper(mb_substr($item->displayName(), 0, 1));
    $reviewSlug = $item->product?->slug;
    $canWriteReview = $order !== null
        && $order->fulfillment_status === \App\Models\OnlineSaleOrder::FULFILLMENT_DELIVERED
        && $order->order_status !== \App\Models\OnlineSaleOrder::STATUS_CANCELLED
        && $item->product_id !== null
        && filled($reviewSlug)
        && ($productReview === null || $productReview->status !== \App\Models\ProductReview::STATUS_APPROVED);
@endphp

<li class="ag-store-orders__line">
    <span class="ag-store-orders__line-sn" aria-hidden="true">{{ $index + 1 }}</span>
    <div class="ag-store-orders__line-thumb">
        @if ($thumbUrl)
            <img src="{{ $thumbUrl }}" alt="" class="ag-store-orders__line-img" loading="lazy" width="56" height="56" />
        @else
            <span class="ag-store-orders__line-placeholder">{{ $initial }}</span>
        @endif
    </div>
    <div class="ag-store-orders__line-body">
        <span class="ag-store-orders__line-name">{{ $item->displayName() }}</span>
        @if ($item->displayBrand() !== '—')
            <span class="ag-store-orders__line-brand">{{ $item->displayBrand() }}</span>
        @endif
        <p class="ag-store-orders__line-meta">
            <span>{{ __('agricart.storefront.checkout.qty_line', ['qty' => $sales->formatQty((float) $item->quantity)]) }}</span>
            <span aria-hidden="true">·</span>
            <span>{{ __('agricart.storefront.products.price', ['amount' => $sales->formatMoney((float) $item->unit_price)]) }} {{ __('agricart.storefront.checkout.each') }}</span>
        </p>
        @if ($productReview !== null)
            <p class="ag-store-orders__line-review">
                {{ __('agricart.storefront.reviews.status_'.$productReview->status) }}
                @if ($canWriteReview)
                    <a href="{{ route('store.customer.reviews.create', $reviewSlug) }}" class="ag-store-orders__line-review-link">
                        {{ __('agricart.storefront.reviews.write_short') }}
                    </a>
                @endif
            </p>
        @elseif ($canWriteReview)
            <p class="ag-store-orders__line-review">
                <a href="{{ route('store.customer.reviews.create', $reviewSlug) }}" class="ag-store-orders__line-review-link">
                    {{ __('agricart.storefront.reviews.write_short') }}
                </a>
            </p>
        @endif
    </div>
    <div class="ag-store-orders__line-total">
        {{ __('agricart.storefront.products.price', ['amount' => $sales->formatMoney((float) $item->line_total)]) }}
    </div>
</li>
