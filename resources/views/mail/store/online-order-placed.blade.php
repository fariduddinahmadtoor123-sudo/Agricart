<x-mail::message>
# {{ __('store_notifications.order_placed.heading', ['number' => $order->order_number]) }}

{{ __('store_notifications.order_placed.intro') }}

**{{ __('store_notifications.order_placed.total') }}:** {{ __('agricart.storefront.products.price', ['amount' => $sales->formatMoney((float) $order->total_amount)]) }}

@if ($order->customer_payment_method === 'bank_transfer')
{{ __('store_notifications.order_placed.bank_hint') }}
@endif

<x-mail::table>
| {{ __('store_notifications.order_placed.col_item') }} | {{ __('store_notifications.order_placed.col_qty') }} |
| :-- | --: |
@foreach ($order->items as $item)
| {{ $item->displayName($order->locale) }} | {{ $item->quantity }} |
@endforeach
</x-mail::table>

<x-mail::button :url="$orderUrl">
{{ __('store_notifications.order_placed.view_order') }}
</x-mail::button>

{{ __('store_notifications.order_placed.footer') }}

{{ config('app.name') }}
</x-mail::message>
