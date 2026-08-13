@php
    $locale = $locale ?? 'en';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale === 'ur' ? 'ur' : 'en' }}" @if ($locale === 'ur') dir="rtl" @endif>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: #111827; font-size: 12pt; line-height: 1.45; }
        .head { text-align: center; border-bottom: 2px solid #111827; padding-bottom: 8pt; margin-bottom: 12pt; }
        .store { margin: 0; font-size: 16pt; font-weight: 800; }
        .title { margin: 6pt 0 0; font-size: 12pt; font-weight: 700; color: #374151; }
        .order { margin: 8pt 0 0; font-size: 18pt; font-weight: 800; }
        .meta { margin: 4pt 0 0; font-size: 10pt; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; font-size: 10pt; }
        th, td { border: 1px solid #9ca3af; padding: 6pt 5pt; text-align: center; vertical-align: top; }
        th { background: #f3f4f6; font-weight: 700; }
        td.name { text-align: {{ $locale === 'ur' ? 'right' : 'left' }}; font-weight: 600; }
    </style>
</head>
<body>
    <header class="head">
        @if (filled($storeName ?? null))
            <h1 class="store">{{ $storeName }}</h1>
        @endif
        <p class="title">{{ __('order_dispatch.packing_slip.heading') }}</p>
        <p class="order">{{ $order->order_number }}</p>
        <p class="meta">{{ $order->order_date?->format('d M Y') }}</p>
    </header>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('pos.fields.brand') }}</th>
                <th class="name">{{ __('pos.fields.product_name') }}</th>
                <th>{{ __('pos.fields.quantity') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->displayBrand($locale) ?: '—' }}</td>
                    <td class="name">{{ $item->displayName($locale) }}</td>
                    <td><strong>{{ $item->quantity }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
