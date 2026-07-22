@php
    use App\Models\PrintProfile;
    use App\Services\Printing\PrintProfileService;

    $paper = $paper ?? 'a4';
    $isReceipt = $paper === 'receipt';
    $receiptProfile = $receiptProfile ?? app(PrintProfileService::class)->defaultForCategory(PrintProfile::CATEGORY_RECEIPT);
    $receiptWidth = $receiptProfile?->width_mm ?? 75;
    $receiptMargin = $receiptProfile?->margin_mm ?? 2;
    $widthCss = rtrim(rtrim(number_format((float) $receiptWidth, 2, '.', ''), '0'), '.');
    $marginCss = rtrim(rtrim(number_format((float) $receiptMargin, 2, '.', ''), '0'), '.');
    $layoutQuery = request()->except(['autoprint', 'locale', 'paper']);
@endphp
<!DOCTYPE html>
<html lang="{{ $locale === 'ur' ? 'ur' : 'en' }}" @if($locale === 'ur') dir="rtl" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $order->order_number }} — {{ __('order_dispatch.packing_slip.title') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f3f4f6; color: #111827; }
        .print-shell { min-height: 100vh; display: flex; flex-direction: column; align-items: center; gap: 1rem; padding: 1.5rem; }
        .print-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; justify-content: center; }
        .print-actions button, .print-actions a {
            border: 1px solid #d1d5db; background: #fff; padding: 0.5rem 0.875rem; font-weight: 700;
            cursor: pointer; text-decoration: none; color: inherit; font-size: 0.8125rem;
        }
        .print-actions button.is-primary, .print-actions a.is-primary { background: #83B735; border-color: #83B735; color: #fff; }
        .print-actions a.is-active { border-color: #111827; box-shadow: inset 0 0 0 1px #111827; }
        .slip { width: 100%; background: #fff; border: 1px solid #d1d5db; padding: 1rem; font-size: 0.875rem; line-height: 1.45; }
        .slip--a4 { max-width: 210mm; }
        .slip--receipt { max-width: {{ $widthCss }}mm; padding: 0.5rem 0.45rem; font-size: 0.68rem; line-height: 1.35; }
        .slip__head { text-align: center; border-bottom: 2px solid #111827; padding-bottom: 0.55rem; margin-bottom: 0.65rem; }
        .slip--receipt .slip__head { border-bottom-style: dashed; border-bottom-width: 1px; padding-bottom: 0.35rem; margin-bottom: 0.35rem; }
        .slip__logo { max-height: 3rem; margin: 0 auto 0.35rem; display: block; }
        .slip--receipt .slip__logo { max-height: 2.5rem; }
        .slip__store { margin: 0; font-size: 1.2rem; font-weight: 800; }
        .slip--receipt .slip__store { font-size: 0.85rem; }
        .slip__title { margin: 0.35rem 0 0; font-size: 1rem; font-weight: 700; color: #374151; }
        .slip--receipt .slip__title { font-size: 0.72rem; }
        .slip__order { margin: 0.5rem 0 0; font-size: 1.35rem; font-weight: 800; letter-spacing: 0.02em; }
        .slip--receipt .slip__order { font-size: 0.9rem; margin-top: 0.35rem; }
        .slip__meta { margin: 0.25rem 0 0; font-size: 0.8125rem; color: #4b5563; }
        .slip--receipt .slip__meta { font-size: 0.62rem; }
        .slip__table { width: 100%; border-collapse: collapse; font-size: 0.875rem; margin-top: 0.35rem; }
        .slip--receipt .slip__table { font-size: 0.62rem; }
        .slip__table th, .slip__table td { border: 1px solid #9ca3af; padding: 0.4rem 0.35rem; vertical-align: top; text-align: center; }
        .slip--receipt .slip__table th, .slip--receipt .slip__table td { border-bottom: 1px solid #e5e7eb; border-left: none; border-right: none; padding: 0.2rem 0.1rem; }
        .slip__table th { background: #f3f4f6; font-weight: 700; }
        .slip__table .name { text-align: start !important; font-weight: 600; min-width: 12rem; }
        .slip--receipt .slip__table .name { min-width: 0; }
        @media print { body { background: #fff; } .print-actions { display: none; } .print-shell { padding: 0; } .slip { border: none; max-width: none; width: 100%; } .slip--receipt { padding: 0; } }
        @if ($isReceipt)
        @media print { @page { size: {{ $widthCss }}mm auto; margin: {{ $marginCss }}mm; } }
        @else
        @media print { @page { size: A4 portrait; margin: 10mm; } }
        @endif
    </style>
</head>
<body class="{{ $isReceipt ? 'is-receipt' : 'is-a4' }}">
    <div class="print-shell">
        <div class="print-actions">
            <a href="{{ route('admin.online-sale-orders.packing-slip', array_merge(['order' => $order], $layoutQuery, ['paper' => 'a4', 'locale' => $locale, 'autoprint' => 0])) }}" @class(['is-active' => ! $isReceipt])>{{ __('pos.print.layout_a4') }}</a>
            <a href="{{ route('admin.online-sale-orders.packing-slip', array_merge(['order' => $order], $layoutQuery, ['paper' => 'receipt', 'locale' => $locale, 'autoprint' => 0])) }}" @class(['is-active' => $isReceipt])>{{ __('pos.print.layout_receipt') }}</a>
            <a href="{{ route('admin.online-sale-orders.packing-slip', array_merge(['order' => $order], $layoutQuery, ['paper' => $paper, 'locale' => 'en', 'autoprint' => 0])) }}" @class(['is-active' => $locale === 'en'])>{{ __('pos.locales.en') }}</a>
            <a href="{{ route('admin.online-sale-orders.packing-slip', array_merge(['order' => $order], $layoutQuery, ['paper' => $paper, 'locale' => 'ur', 'autoprint' => 0])) }}" @class(['is-active' => $locale === 'ur'])>{{ __('pos.locales.ur') }}</a>
            <button type="button" class="is-primary" onclick="window.print()">{{ __('pos.print.print_button') }}</button>
            <button type="button" onclick="window.close()">{{ __('pos.print.close_button') }}</button>
        </div>

        <article @class(['slip', 'slip--a4' => ! $isReceipt, 'slip--receipt' => $isReceipt])>
            <header class="slip__head">
                @if ($storeLogoUrl)
                    <img src="{{ $storeLogoUrl }}" alt="" class="slip__logo" />
                @endif
                @if (filled($storeName))
                    <h1 class="slip__store">{{ $storeName }}</h1>
                @endif
                <p class="slip__title">{{ __('order_dispatch.packing_slip.heading') }}</p>
                <p class="slip__order">{{ $order->order_number }}</p>
                <p class="slip__meta">{{ $order->order_date?->format('d M Y') }}</p>
            </header>

            <table class="slip__table">
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
        </article>
    </div>

    @if ($autoprint)
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
