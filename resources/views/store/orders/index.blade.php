@extends('store.layouts.app')

@section('title', __('agricart.customer.orders.page_title') . ' — ' . ($settings->localized('site_name') ?? config('app.name')))

@section('content')
    <div class="ag-store-customer-auth-page">
        <div class="ag-store-account ag-store-account--orders">
            <header class="ag-store-account__head ag-store-orders__head">
                <div class="ag-store-orders__head-text">
                    <h1 class="ag-store-customer-auth__title">{{ __('agricart.customer.orders.heading') }}</h1>
                    <p class="ag-store-customer-auth__lead">{{ __('agricart.customer.orders.lead') }}</p>
                </div>
                <a href="{{ route('store.customer.account') }}" class="ag-store-orders__head-link">{{ __('agricart.customer.auth.my_profile') }}</a>
            </header>

            @if ($orders->isEmpty())
                <div class="ag-store-orders__empty-card">
                    <p class="ag-store-orders__empty">{{ __('agricart.customer.orders.empty') }}</p>
                    <a href="{{ route('store.home') }}" class="ag-store-btn ag-store-btn--primary">{{ __('agricart.storefront.cart.continue_shopping') }}</a>
                </div>
            @else
                <div class="ag-store-orders__list">
                    @foreach ($orders as $order)
                        @php
                            $statusKey = $order->order_status;
                            $statusLabel = __('online_sale_orders.order_statuses.' . $statusKey);
                        @endphp
                        <article class="ag-store-orders__card">
                            <div class="ag-store-orders__card-top">
                                <div class="ag-store-orders__card-id">
                                    <span class="ag-store-orders__card-label">{{ __('agricart.customer.orders.col_number') }}</span>
                                    <code class="ag-store-orders__card-number">{{ $order->order_number }}</code>
                                </div>
                                <span @class([
                                    'ag-store-orders__status',
                                    'is-pending' => $statusKey === 'pending',
                                    'is-paid' => in_array($statusKey, ['payment_received', 'processing'], true),
                                    'is-done' => $statusKey === 'converted',
                                    'is-cancelled' => $statusKey === 'cancelled',
                                ])>{{ $statusLabel }}</span>
                            </div>

                            <div class="ag-store-orders__card-meta">
                                <span>{{ $order->order_date?->format('d M Y') }}</span>
                                <span aria-hidden="true">·</span>
                                <span class="ag-store-orders__card-total">
                                    {{ __('agricart.storefront.products.price', ['amount' => $sales->formatMoney((float) $order->total_amount)]) }}
                                </span>
                            </div>

                            @if ($order->items->isNotEmpty())
                                <ul class="ag-store-orders__preview" aria-label="{{ __('agricart.customer.orders.items_preview') }}">
                                    @foreach ($order->items as $item)
                                        @php $thumbUrl = $item->thumbnailUrl(); @endphp
                                        <li class="ag-store-orders__preview-item">
                                            @if ($thumbUrl)
                                                <img src="{{ $thumbUrl }}" alt="" width="40" height="40" loading="lazy" class="ag-store-orders__preview-img" />
                                            @else
                                                <span class="ag-store-orders__preview-placeholder" aria-hidden="true">{{ strtoupper(mb_substr($item->displayName(), 0, 1)) }}</span>
                                            @endif
                                        </li>
                                    @endforeach
                                    @if ($order->items_count > $order->items->count())
                                        <li class="ag-store-orders__preview-more">+{{ $order->items_count - $order->items->count() }}</li>
                                    @endif
                                </ul>
                            @endif

                            <div class="ag-store-orders__card-actions">
                                <a href="{{ route('store.customer.orders.show', $order->order_number) }}" class="ag-store-btn ag-store-btn--primary ag-store-btn--sm">
                                    {{ __('agricart.customer.orders.view') }}
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="ag-store-orders__pagination">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
