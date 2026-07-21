@php
    $layoutQuery = request()->except(['autoprint', 'locale']);
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
        .slip { width: 100%; max-width: 210mm; background: #fff; border: 1px solid #d1d5db; padding: 1rem; font-size: 0.875rem; line-height: 1.45; }
        .slip__head { text-align: center; border-bottom: 2px solid #111827; padding-bottom: 0.55rem; margin-bottom: 0.65rem; }
        .slip__logo { max-height: 3rem; margin: 0 auto 0.35rem; display: block; }
        .slip__store { margin: 0; font-size: 1.2rem; font-weight: 800; }
        .slip__title { margin: 0.35rem 0 0; font-size: 1rem; font-weight: 700; color: #374151; }
        .slip__to { margin: 0.5rem 0 0; font-size: 1.05rem; font-weight: 800; color: #111827; }
        .slip__order { margin: 0.5rem 0 0; font-size: 1.35rem; font-weight: 800; letter-spacing: 0.02em; }
        .slip__meta { margin: 0.25rem 0 0; font-size: 0.8125rem; color: #4b5563; }
        .slip__notice {
            margin: 0.65rem 0; padding: 0.55rem 0.65rem; border: 2px solid #2563eb; background: #eff6ff;
            font-size: 0.8125rem; line-height: 1.45;
        }
        .slip__table { width: 100%; border-collapse: collapse; font-size: 0.875rem; margin-top: 0.35rem; }
        .slip__table th, .slip__table td { border: 1px solid #9ca3af; padding: 0.4rem 0.35rem; vertical-align: top; text-align: center; }
        .slip__table th { background: #f3f4f6; font-weight: 700; }
        .slip__table .name { text-align: start !important; font-weight: 600; min-width: 12rem; }
        .slip__footer { margin-top: 0.75rem; padding-top: 0.55rem; border-top: 1px dashed #9ca3af; font-size: 0.8125rem; color: #374151; }
        @media print { body { background: #fff; } .print-actions { display: none; } .print-shell { padding: 0; } .slip { border: none; max-width: none; width: 100%; } @page { size: A4 portrait; margin: 10mm; } }
    </style>
</head>
<body>
    <div class="print-shell">
        <div class="print-actions">
            <a href="{{ route('admin.online-sale-orders.packing-slip', array_merge(['order' => $order], $layoutQuery, ['locale' => 'en', 'autoprint' => 0])) }}" @class(['is-active' => $locale === 'en'])>{{ __('pos.locales.en') }}</a>
            <a href="{{ route('admin.online-sale-orders.packing-slip', array_merge(['order' => $order], $layoutQuery, ['locale' => 'ur', 'autoprint' => 0])) }}" @class(['is-active' => $locale === 'ur'])>{{ __('pos.locales.ur') }}</a>
            <a href="{{ route('admin.online-sale-orders.print', array_merge(['order' => $order], $layoutQuery, ['locale' => $locale, 'autoprint' => 0])) }}">{{ __('online_sale_orders.actions.print_short') }}</a>
            <button type="button" class="is-primary" onclick="window.print()">{{ __('pos.print.print_button') }}</button>
            <button type="button" onclick="window.close()">{{ __('pos.print.close_button') }}</button>
        </div>

        <article class="slip">
            <header class="slip__head">
                @if ($storeLogoUrl)
                    <img src="{{ $storeLogoUrl }}" alt="" class="slip__logo" />
                @endif
                @if (filled($storeName))
                    <h1 class="slip__store">{{ $storeName }}</h1>
                @endif
                <p class="slip__title">{{ __('order_dispatch.packing_slip.heading') }}</p>
                @if (filled($recipientName ?? null))
                    <p class="slip__to">{{ __('order_dispatch.packing_slip.to', ['name' => $recipientName]) }}</p>
                @endif
                <p class="slip__order">{{ $order->order_number }}</p>
                <p class="slip__meta">{{ $order->order_date?->format('d M Y') }}</p>
                @if ($order->assignedUser)
                    <p class="slip__meta">{{ __('order_dispatch.packing_slip.packed_by') }}: {{ $order->assignedUser->name }}</p>
                @endif
            </header>

            <div class="slip__notice">{{ __('order_dispatch.packing_slip.notice') }}</div>

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

            <footer class="slip__footer">
                {{ __('order_dispatch.packing_slip.footer') }}
            </footer>
        </article>
    </div>

    @if ($autoprint)
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
