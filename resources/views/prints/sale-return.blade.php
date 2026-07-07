@php
    $locale = app()->getLocale();
    $isReceipt = $paper === 'receipt';
    $receiptWidth = $receiptProfile?->width_mm ?? 75;
    $receiptMargin = $receiptProfile?->margin_mm ?? 2;
    $widthCss = rtrim(rtrim(number_format($receiptWidth, 2, '.', ''), '0'), '.');
    $marginCss = rtrim(rtrim(number_format($receiptMargin, 2, '.', ''), '0'), '.');
    $saleService = app(\App\Services\Sales\SaleService::class);
    $priceFormat = app(\App\Services\PricingControls\ChannelPricingEngine::class);
    $formatAmount = function (string $amount) use ($saleService, $priceFormat): string {
        $numeric = $saleService->numeric($amount);

        return $priceFormat->formatPrice($numeric);
    };
    $layoutQuery = request()->except(['paper', 'autoprint', 'locale']);
@endphp
<!DOCTYPE html>
<html lang="{{ $locale === 'ur' ? 'ur' : 'en' }}" @if($locale === 'ur') dir="rtl" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $return->return_number }} — {{ __('sale_returns.print.title') }}</title>
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
        .invoice { width: 100%; background: #fff; border: 1px solid #d1d5db; padding: 1rem; }
        .invoice--a4 { max-width: 210mm; font-size: 0.875rem; line-height: 1.45; }
        .invoice--receipt {
            max-width: {{ $widthCss }}mm; padding: 0.5rem 0.45rem; font-size: 0.68rem; line-height: 1.35;
        }
        .invoice__head { text-align: center; border-bottom: 2px solid #111827; padding-bottom: 0.55rem; margin-bottom: 0.55rem; }
        .invoice--receipt .invoice__head { border-bottom-style: dashed; border-bottom-width: 1px; padding-bottom: 0.35rem; margin-bottom: 0.35rem; }
        .invoice__logo { max-height: 3.25rem; margin: 0 auto 0.35rem; display: block; }
        .invoice--receipt .invoice__logo { max-height: 2.5rem; }
        .invoice__store { margin: 0; font-size: 1.35rem; font-weight: 800; }
        .invoice--receipt .invoice__store { font-size: 0.85rem; }
        .invoice__title { margin: 0.25rem 0 0; font-size: 1rem; font-weight: 700; color: #374151; }
        .invoice--receipt .invoice__title { font-size: 0.72rem; }
        .invoice__meta { margin: 0.2rem 0 0; font-size: 0.8125rem; color: #4b5563; }
        .invoice--receipt .invoice__meta { font-size: 0.62rem; }
        .invoice__table { width: 100%; border-collapse: collapse; font-size: 0.8125rem; margin-top: 0.35rem; }
        .invoice--receipt .invoice__table { font-size: 0.62rem; }
        .invoice__table th, .invoice__table td { border: 1px solid #9ca3af; padding: 0.35rem 0.3rem; vertical-align: top; text-align: center; }
        .invoice--receipt .invoice__table th, .invoice--receipt .invoice__table td { border-bottom: 1px solid #e5e7eb; border-left: none; border-right: none; padding: 0.2rem 0.1rem; }
        .invoice__table th { background: #f3f4f6; font-weight: 700; }
        .invoice__table .name { text-align: start !important; min-width: 10rem; font-weight: 600; font-size: 0.9rem; }
        .invoice--receipt .invoice__table .name { font-size: inherit; min-width: 0; }
        .receipt-items { display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.35rem; }
        .receipt-item { border: 1px solid #d1d5db; border-radius: 0.2rem; padding: 0.35rem 0.3rem; background: #fafafa; }
        .receipt-item__name { margin: 0 0 0.25rem; font-weight: 800; font-size: 0.7rem; line-height: 1.3; word-break: break-word; }
        .receipt-item__grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.18rem; font-size: 0.6rem; }
        .receipt-item__field { display: flex; justify-content: space-between; gap: 0.25rem; padding: 0.12rem 0.2rem; background: #fff; border: 1px solid #e5e7eb; border-radius: 0.15rem; }
        .receipt-item__field--total { grid-column: 1 / -1; font-weight: 700; background: #f3f4f6; }
        .invoice__controls { margin-top: 0.65rem; padding: 0.5rem 0.6rem; border: 1px solid #93c5fd; background: #eff6ff; font-size: 0.8125rem; }
        .invoice--receipt .invoice__controls { font-size: 0.58rem; padding: 0.35rem 0.4rem; margin-top: 0.35rem; }
        .invoice__controls h2 { margin: 0 0 0.35rem; font-size: 0.875rem; }
        .invoice--receipt .invoice__controls h2 { font-size: 0.65rem; margin-bottom: 0.2rem; }
        .invoice__controls p { margin: 0.2rem 0; word-break: break-word; }
        .invoice__notes {
            margin-top: 0.65rem; padding: 0.55rem 0.65rem; border: 2px solid #f59e0b; background: #fffbeb;
            font-size: 0.9rem; line-height: 1.45; word-break: break-word;
        }
        .invoice--receipt .invoice__notes { font-size: 0.64rem; margin-top: 0.35rem; padding: 0.35rem 0.45rem; }
        .invoice__payment {
            margin-top: 0.75rem; padding-top: 0.55rem; border-top: 2px solid #111827;
            font-size: 0.875rem;
        }
        .invoice--receipt .invoice__payment { margin-top: 0.45rem; padding-top: 0.35rem; font-size: 0.66rem; border-top-width: 1px; }
        .invoice__payment-row { display: flex; justify-content: space-between; gap: 0.5rem; margin: 0.15rem 0; }
        .invoice__payment-row.is-grand { font-weight: 800; font-size: 1rem; margin-top: 0.35rem; }
        .invoice--receipt .invoice__payment-row.is-grand { font-size: 0.75rem; }
        .invoice__message { margin-top: 0.55rem; text-align: center; font-size: 0.75rem; color: #374151; border-top: 1px dashed #d1d5db; padding-top: 0.35rem; }
        .invoice--receipt .invoice__message { font-size: 0.6rem; }
        @media print { body { background: #fff; } .print-actions { display: none; } .print-shell { padding: 0; } .invoice { border: none; max-width: none; width: 100%; } .invoice--receipt { padding: 0; } }
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
            <a href="{{ route('admin.sale-returns.print', array_merge(['saleReturn' => $return], $layoutQuery, ['paper' => 'a4', 'autoprint' => 0])) }}" @class(['is-active' => ! $isReceipt])>{{ __('pos.print.layout_a4') }}</a>
            <a href="{{ route('admin.sale-returns.print', array_merge(['saleReturn' => $return], $layoutQuery, ['paper' => 'receipt', 'autoprint' => 0])) }}" @class(['is-active' => $isReceipt])>{{ __('pos.print.layout_receipt') }}</a>
            <button type="button" class="is-primary" onclick="window.print()">{{ __('pos.print.print_button') }}</button>
            <button type="button" onclick="window.close()">{{ __('pos.print.close_button') }}</button>
        </div>

        <article @class(['invoice', 'invoice--a4' => ! $isReceipt, 'invoice--receipt' => $isReceipt])>
            <header class="invoice__head">
                @if ($storeLogoUrl)
                    <img src="{{ $storeLogoUrl }}" alt="" class="invoice__logo" />
                @endif
                @if (filled($storeName))
                    <h1 class="invoice__store">{{ $storeName }}</h1>
                @endif
                <p class="invoice__title">{{ __('sale_returns.print.heading') }}</p>
                <p class="invoice__meta">
                    <strong>{{ $return->return_number }}</strong>
                    · {{ $return->return_date?->format('d M Y') }}
                    @if (filled($storePhone)) · {{ $storePhone }} @endif
                </p>
                @if ($return->sale)
                    <p class="invoice__meta">{{ __('sale_returns.fields.sale_number') }}: {{ $return->sale->sale_number }}</p>
                @endif
                @if ($return->customer)
                    <p class="invoice__meta">{{ __('pos.print.customer') }}: {{ $return->customer->displayLabel() }}</p>
                @endif
                @if ($return->processedBy)
                    <p class="invoice__meta">{{ __('sale_returns.fields.processed_by') }}: {{ $return->processedBy->name }}</p>
                @endif
            </header>

            @if ($isReceipt)
                <div class="receipt-items">
                    @foreach ($return->items as $index => $item)
                        <article class="receipt-item">
                            <p class="receipt-item__name">{{ $index + 1 }}. {{ $item->displayName() }}</p>
                            <div class="receipt-item__grid">
                                <div class="receipt-item__field">
                                    <span>{{ __('pos.fields.brand') }}</span>
                                    <strong>{{ $item->displayBrand() }}</strong>
                                </div>
                                <div class="receipt-item__field">
                                    <span>{{ __('pos.fields.quantity') }}</span>
                                    <strong>{{ $item->return_quantity }}</strong>
                                </div>
                                <div class="receipt-item__field">
                                    <span>{{ __('pos.fields.unit_price') }}</span>
                                    <strong>{{ $formatAmount((string) $item->unit_price) }}</strong>
                                </div>
                                <div class="receipt-item__field receipt-item__field--total">
                                    <span>{{ __('pos.fields.line_total') }}</span>
                                    <strong>{{ $formatAmount((string) $item->return_amount) }}</strong>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <table class="invoice__table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('pos.fields.brand') }}</th>
                            <th class="name">{{ __('pos.fields.product_name') }}</th>
                            <th>{{ __('pos.fields.quantity') }}</th>
                            <th>{{ __('pos.fields.unit_price') }}</th>
                            <th>{{ __('pos.fields.line_total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($return->items as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item->displayBrand() }}</td>
                                <td class="name">{{ $item->displayName() }}</td>
                                <td>{{ $item->return_quantity }}</td>
                                <td>{{ $formatAmount((string) $item->unit_price) }}</td>
                                <td>{{ $formatAmount((string) $item->return_amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if (filled($return->notes))
                <div class="invoice__notes">
                    <strong>{{ __('pos.fields.notes') }}:</strong> {{ $return->notes }}
                </div>
            @endif

            <footer class="invoice__payment">
                <div class="invoice__payment-row is-grand"><span>{{ __('sale_returns.fields.refund_total') }}</span><span>{{ $formatAmount((string) $return->refund_total) }}</span></div>
                <div class="invoice__payment-row"><span>{{ __('pos.fields.payment_method') }}</span><span>{{ __('pos.payment_methods.' . $return->payment_method) }}</span></div>
                <div class="invoice__payment-row"><span>{{ __('pos.fields.payment_status') }}</span><span>{{ __('pos.payment_statuses.' . $return->payment_status) }}</span></div>
                <div class="invoice__payment-row"><span>{{ __('pos.fields.amount_paid') }}</span><span>{{ $formatAmount((string) $return->amount_paid) }}</span></div>
                <div class="invoice__payment-row"><span>{{ __('pos.fields.balance_due') }}</span><span>{{ $formatAmount((string) $balanceDue) }}</span></div>
            </footer>

            @if (filled($storeMessage))
                <p class="invoice__message">{{ $storeMessage }}</p>
            @endif
        </article>
    </div>

    @if ($autoprint)
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
