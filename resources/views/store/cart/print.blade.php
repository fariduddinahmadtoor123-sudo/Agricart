@php
    $locale = app()->getLocale();
    $isUr = $locale === 'ur';
    $storeName = $settings->localized('site_name') ?? config('app.name');
    $storePhone = filled($settings->top_bar_phone ?? null) ? (string) $settings->top_bar_phone : '';
    $logoUrl = $settings->displayLogoUrl();
    $printedLabel = $printedAt->format('d M Y, h:i A');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" @if ($isUr) dir="rtl" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('agricart.storefront.cart.print_title') }} — {{ $storeName }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f3f4f6;
            color: #111827;
        }
        .print-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.25rem 1rem 2rem;
            gap: 0;
        }
        .invoice {
            width: 100%;
            max-width: 210mm;
            background: #fff;
            border: 1px solid #d1d5db;
            padding: 1rem 1.1rem;
            font-size: 0.875rem;
            line-height: 1.45;
        }
        .invoice__head {
            text-align: center;
            border-bottom: 2px solid #111827;
            padding-bottom: 0.55rem;
            margin-bottom: 0.65rem;
        }
        .invoice__logo {
            max-height: 3.25rem;
            max-width: 14rem;
            margin: 0 auto 0.35rem;
            display: block;
            object-fit: contain;
        }
        .invoice__store {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: 0.02em;
        }
        .invoice__title {
            margin: 0.35rem 0 0;
            font-size: 1rem;
            font-weight: 700;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .invoice__meta {
            margin: 0.35rem 0 0;
            font-size: 0.8125rem;
            color: #4b5563;
        }
        .invoice__meta strong { color: #111827; }
        .invoice__table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8125rem;
            margin-top: 0.25rem;
        }
        .invoice__table th,
        .invoice__table td {
            border: 1px solid #9ca3af;
            padding: 0.4rem 0.35rem;
            vertical-align: middle;
            text-align: center;
        }
        .invoice__table th {
            background: #f3f4f6;
            font-weight: 700;
        }
        .invoice__table .name {
            text-align: start !important;
            min-width: 8rem;
            font-weight: 600;
        }
        .invoice__table .brand {
            display: block;
            font-size: 0.72rem;
            font-weight: 500;
            color: #6b7280;
            margin-top: 0.15rem;
        }
        .invoice__thumb {
            width: 2.75rem;
            height: 2.75rem;
            object-fit: cover;
            border-radius: 0.2rem;
            border: 1px solid #e5e7eb;
            display: block;
            margin: 0 auto;
        }
        .invoice__thumb-ph {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.75rem;
            height: 2.75rem;
            margin: 0 auto;
            border-radius: 0.2rem;
            border: 1px solid #e5e7eb;
            background: #f3f4f6;
            font-weight: 700;
            font-size: 0.85rem;
            color: #6b7280;
        }
        .invoice__payment {
            margin-top: 0.75rem;
            padding-top: 0.55rem;
            border-top: 2px solid #111827;
            font-size: 0.875rem;
        }
        .invoice__payment-row {
            display: flex;
            justify-content: space-between;
            gap: 0.5rem;
            margin: 0.15rem 0;
        }
        .invoice__payment-row.is-grand {
            font-weight: 800;
            font-size: 1.05rem;
            margin-top: 0.25rem;
        }
        .invoice__message {
            margin-top: 0.55rem;
            text-align: center;
            font-size: 0.75rem;
            color: #4b5563;
            border-top: 1px dashed #d1d5db;
            padding-top: 0.4rem;
            line-height: 1.45;
        }
        .print-actions {
            width: 100%;
            max-width: 210mm;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1.25rem;
            padding-top: 0.25rem;
        }
        .print-actions button,
        .print-actions a {
            border: 1px solid #d1d5db;
            background: #fff;
            padding: 0.55rem 1rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            font-size: 0.8125rem;
            border-radius: 0.25rem;
        }
        .print-actions button.is-primary,
        .print-actions a.is-primary {
            background: #83B735;
            border-color: #83B735;
            color: #fff;
        }
        @media print {
            body { background: #fff; }
            .print-shell { padding: 0; min-height: auto; }
            .print-actions { display: none !important; }
            .invoice {
                border: none;
                max-width: none;
                width: 100%;
                padding: 0;
            }
            .invoice__thumb {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            @page { size: A4 portrait; margin: 10mm; }
        }
    </style>
</head>
<body>
    <div class="print-shell">
        <article class="invoice">
            <header class="invoice__head">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="" class="invoice__logo" />
                @endif
                <h1 class="invoice__store">{{ $storeName }}</h1>
                <p class="invoice__title">{{ __('agricart.storefront.cart.print_heading') }}</p>
                <p class="invoice__meta">
                    <strong>{{ __('agricart.storefront.cart.print_title') }}</strong>
                    · {{ $printedLabel }}
                    @if ($storePhone !== '')
                        · {{ $storePhone }}
                    @endif
                </p>
            </header>

            <table class="invoice__table">
                <thead>
                    <tr>
                        <th>{{ __('agricart.storefront.cart.col_sn') }}</th>
                        <th>{{ __('agricart.storefront.cart.col_image') }}</th>
                        <th class="name">{{ __('agricart.storefront.cart.col_product') }}</th>
                        <th>{{ __('agricart.storefront.cart.col_price') }}</th>
                        <th>{{ __('agricart.storefront.cart.col_quantity') }}</th>
                        <th>{{ __('agricart.storefront.cart.col_total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lines as $index => $line)
                        @php
                            $p = $line['product'];
                            $thumbUrl = $productService->imageUrl($p);
                            $productName = $productService->localizedName($p);
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                @if ($thumbUrl)
                                    <img src="{{ $thumbUrl }}" alt="" class="invoice__thumb" width="44" height="44" />
                                @else
                                    <span class="invoice__thumb-ph">{{ strtoupper(substr($productName, 0, 1)) }}</span>
                                @endif
                            </td>
                            <td class="name">
                                {{ $productName }}
                                @if ($p->brand)
                                    <span class="brand">{{ $p->brand->name_en }}</span>
                                @endif
                            </td>
                            <td>{{ __('agricart.storefront.products.price', ['amount' => $line['unit_price_formatted']]) }}</td>
                            <td>{{ $line['quantity'] }}</td>
                            <td>{{ __('agricart.storefront.products.price', ['amount' => $line['line_total_formatted']]) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="invoice__payment">
                <div class="invoice__payment-row is-grand">
                    <span>{{ __('agricart.storefront.cart.subtotal') }}</span>
                    <span>{{ __('agricart.storefront.products.price', ['amount' => $subtotal]) }}</span>
                </div>
            </div>

            <p class="invoice__message">{{ __('agricart.storefront.cart.print_note') }}</p>
        </article>

        <div class="print-actions">
            <button type="button" class="is-primary" onclick="window.print()">{{ __('agricart.storefront.cart.print') }}</button>
            <a href="{{ route('store.cart') }}">{{ __('agricart.storefront.cart.back_to_cart') }}</a>
        </div>
    </div>
</body>
</html>
