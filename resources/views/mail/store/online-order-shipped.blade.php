<x-mail::message>
# {{ __('store_notifications.order_shipped.heading', ['number' => $order->order_number]) }}

{{ __('store_notifications.order_shipped.intro') }}

@if ($parcels !== [])
<x-mail::table>
| {{ __('store_notifications.order_shipped.col_carrier') }} | {{ __('store_notifications.order_shipped.col_tracking') }} | {{ __('store_notifications.order_shipped.col_bilty') }} |
| :-- | :-- | :-- |
@foreach ($parcels as $parcel)
| {{ $parcel['carrier'] ?: '—' }} | {{ $parcel['tracking'] ?: '—' }} | {{ $parcel['bilty'] ?: '—' }} |
@endforeach
</x-mail::table>
@endif

{{ __('store_notifications.order_shipped.hint') }}

<x-mail::button :url="$orderUrl">
{{ __('store_notifications.order_shipped.view_order') }}
</x-mail::button>

{{ __('store_notifications.order_shipped.footer') }}

{{ config('app.name') }}
</x-mail::message>
