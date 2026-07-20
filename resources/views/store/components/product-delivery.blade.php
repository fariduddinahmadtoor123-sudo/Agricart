@php
    /** @var list<array<string, mixed>> $deliveryOptions */
    $deliveryOptions = $deliveryOptions ?? [];
@endphp

@if ($deliveryOptions !== [])
    <div class="ag-store-pdp__delivery">
        <p class="ag-store-pdp__delivery-lead">{{ __('agricart.storefront.product.delivery.lead') }}</p>

        <ul class="ag-store-pdp__delivery-list">
            @foreach ($deliveryOptions as $option)
                <li class="ag-store-pdp__delivery-item">
                    <h3 class="ag-store-pdp__delivery-name">{{ $option['name'] }}</h3>

                    @if (filled($option['description'] ?? null))
                        <p class="ag-store-pdp__delivery-desc">{{ $option['description'] }}</p>
                    @endif

                    @if (filled($option['shipping_mode_label'] ?? null))
                        <p class="ag-store-pdp__delivery-mode">{{ $option['shipping_mode_label'] }}</p>
                    @endif

                    @if (filled($option['notice'] ?? null))
                        <p class="ag-store-pdp__delivery-notice">{{ $option['notice'] }}</p>
                    @endif

                    @if (($option['full_advance_required'] ?? false) === true)
                        <p class="ag-store-pdp__delivery-hint">{{ __('agricart.storefront.checkout.full_advance_hint') }}</p>
                    @elseif ((float) ($option['cod_min_advance_percent'] ?? 0) > 0)
                        <p class="ag-store-pdp__delivery-hint">{{ __('agricart.storefront.checkout.cod_advance_hint', [
                            'percent' => rtrim(rtrim(number_format((float) $option['cod_min_advance_percent'], 2, '.', ''), '0'), '.'),
                        ]) }}</p>
                    @endif

                    @if (($option['payment_labels'] ?? []) !== [])
                        <p class="ag-store-pdp__delivery-payments">
                            <span class="ag-store-pdp__delivery-payments-label">{{ __('agricart.storefront.product.delivery.payments') }}</span>
                            {{ implode(' · ', $option['payment_labels']) }}
                        </p>
                    @endif
                </li>
            @endforeach
        </ul>

        <p class="ag-store-pdp__delivery-foot">{{ __('agricart.storefront.product.delivery.checkout_hint') }}</p>
    </div>
@endif
