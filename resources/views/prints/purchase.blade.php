@php
    $isReceipt = $paper === 'receipt';
    $receiptWidth = $receiptProfile?->width_mm ?? 75;
    $receiptMargin = $receiptProfile?->margin_mm ?? 2;
    $widthCss = rtrim(rtrim(number_format($receiptWidth, 2, '.', ''), '0'), '.');
    $marginCss = rtrim(rtrim(number_format($receiptMargin, 2, '.', ''), '0'), '.');
    $supplier = $purchase->supplier;
    $supplierPhone = $supplier?->primaryContact()['mobile_number'] ?? null;
    $supplierAddress = $supplier
        ? trim(implode(', ', array_filter([
            $locale === 'ur' && filled($supplier->address_ur)
                ? trim((string) $supplier->address_ur)
                : trim((string) ($supplier->address_en ?: $supplier->address_ur)),
            $supplier->displayCity(),
        ])))
        : '';
    $layoutQuery = request()->except(['paper', 'autoprint']);
@endphp
<!DOCTYPE html>
<html lang="{{ $locale === 'ur' ? 'ur' : 'en' }}" @if($locale === 'ur') dir="rtl" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $purchase->purchase_number }} — {{ __('purchases.print.title') }}</title>
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
            gap: 1rem;
            padding: 1.5rem;
        }
        .print-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: center;
        }
        .print-actions button,
        .print-actions a {
            border: 1px solid #d1d5db;
            background: #fff;
            padding: 0.5rem 0.875rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            font-size: 0.8125rem;
        }
        .print-actions button.is-primary,
        .print-actions a.is-primary {
            background: #83B735;
            border-color: #83B735;
            color: #fff;
        }
        .print-actions a.is-active {
            border-color: #111827;
            box-shadow: inset 0 0 0 1px #111827;
        }
        .invoice {
            width: 100%;
            background: #fff;
            border: 1px solid #d1d5db;
            padding: 1rem;
        }
        .invoice--a4 {
            max-width: 210mm;
        }
        .invoice--receipt {
            max-width: {{ $widthCss }}mm;
            padding: 0.5rem 0.45rem;
            font-size: 0.68rem;
            line-height: 1.35;
        }
        .invoice__header {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            border-bottom: 2px solid #111827;
            padding-bottom: 0.55rem;
            margin-bottom: 0.55rem;
        }
        .invoice__title {
            margin: 0;
            font-size: 1.25rem;
        }
        .invoice--receipt .invoice__title {
            font-size: 0.95rem;
        }
        .invoice__meta {
            font-size: 0.8125rem;
            text-align: end;
        }
        .invoice--receipt .invoice__meta {
            font-size: 0.68rem;
        }
        .invoice__parties {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 0.75rem;
            font-size: 0.8125rem;
        }
        .invoice--receipt .invoice__parties {
            grid-template-columns: 1fr;
            gap: 0.35rem;
            margin-bottom: 0.45rem;
            font-size: 0.66rem;
        }
        .invoice__party h2 {
            margin: 0 0 0.25rem;
            font-size: 0.875rem;
        }
        .invoice--receipt .invoice__party h2 {
            display: none;
        }
        .invoice__party p {
            margin: 0.15rem 0;
        }
        .invoice__party-line {
            margin: 0;
            word-break: break-word;
        }
        .invoice__party-line strong {
            font-weight: 800;
        }
        .invoice__status {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem 1rem;
            font-size: 0.75rem;
            margin-bottom: 0.75rem;
        }
        .invoice--receipt .invoice__status {
            gap: 0.2rem 0.45rem;
            font-size: 0.62rem;
            margin-bottom: 0.45rem;
        }
        .invoice__notes {
            font-size: 0.8125rem;
            margin-bottom: 0.75rem;
            padding: 0.5rem 0.65rem;
            border: 1px solid #f59e0b;
            background: #fffbeb;
        }
        .invoice--receipt .invoice__notes {
            font-size: 0.64rem;
            margin-bottom: 0.45rem;
            padding: 0.35rem 0.45rem;
        }
        .invoice__table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.7rem;
        }
        .invoice__table th,
        .invoice__table td {
            border: 1px solid #9ca3af;
            padding: 0.3rem 0.2rem;
            vertical-align: middle;
            text-align: center;
        }
        .invoice__table th {
            background: #f3f4f6;
        }
        .invoice__table .name {
            text-align: start !important;
            min-width: 8rem;
            font-weight: 600;
        }
        .invoice__table .thumb {
            width: 2rem;
            height: 2rem;
            object-fit: cover;
        }
        .receipt-items {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .receipt-item {
            border: 1px solid #d1d5db;
            border-radius: 0.2rem;
            padding: 0.35rem 0.3rem 0.3rem;
            background: #fafafa;
        }
        .receipt-item:last-child {
            margin-bottom: 0;
        }
        .receipt-item__name {
            margin: 0 0 0.28rem;
            font-weight: 800;
            font-size: 0.7rem;
            line-height: 1.3;
            word-break: break-word;
            padding-bottom: 0.2rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .receipt-item__grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.18rem;
        }
        .receipt-item__field {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 0.2rem;
            min-width: 0;
            padding: 0.14rem 0.22rem;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 0.15rem;
            font-size: 0.6rem;
            line-height: 1.25;
        }
        .receipt-item__label {
            color: #6b7280;
            flex: 1 1 auto;
            min-width: 0;
            word-break: break-word;
        }
        .receipt-item__value {
            font-weight: 700;
            color: #111827;
            text-align: end;
            white-space: nowrap;
            flex: 0 0 auto;
        }
        .receipt-item__field--total {
            grid-column: 1 / -1;
            margin-top: 0.08rem;
            padding: 0.2rem 0.28rem;
            background: #f3f4f6;
            border-color: #9ca3af;
            font-size: 0.64rem;
        }
        .receipt-item__field--total .receipt-item__label {
            color: #111827;
            font-weight: 700;
        }
        .receipt-item__field--total .receipt-item__value {
            font-size: 0.68rem;
        }
        .receipt-item__line {
            margin: 0;
            word-break: break-word;
        }
        .receipt-item__sep {
            color: #9ca3af;
            padding-inline: 0.12rem;
        }
        .invoice__totals {
            margin-top: 0.75rem;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.35rem 1rem;
            font-size: 0.8125rem;
            max-width: 20rem;
            margin-inline-start: auto;
        }
        .invoice__totals div {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
        }
        .invoice--receipt .invoice__totals {
            grid-template-columns: 1fr;
            max-width: none;
            margin-inline-start: 0;
            margin-top: 0.5rem;
            font-size: 0.66rem;
            border-top: 1px solid #111827;
            padding-top: 0.35rem;
        }
        @media print {
            body { background: #fff; }
            .print-actions { display: none; }
            .print-shell { padding: 0; }
            .invoice { border: none; max-width: none; width: 100%; }
            .invoice--receipt { padding: 0; }
        }
        @if ($isReceipt)
        @media print {
            @page {
                size: {{ $widthCss }}mm auto;
                margin: {{ $marginCss }}mm;
            }
        }
        @else
        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
        }
        @endif
    </style>
</head>
<body class="{{ $isReceipt ? 'is-receipt' : 'is-a4' }}">
    <div class="print-shell">
        <div class="print-actions">
            <a
                href="{{ route('admin.purchases.print', array_merge([$purchase], $layoutQuery, ['paper' => 'a4', 'autoprint' => 0])) }}"
                @class(['is-active' => ! $isReceipt])
            >{{ __('purchases.print.layout_a4') }}</a>
            <a
                href="{{ route('admin.purchases.print', array_merge([$purchase], $layoutQuery, ['paper' => 'receipt', 'autoprint' => 0])) }}"
                @class(['is-active' => $isReceipt])
            >{{ __('purchases.print.layout_receipt') }}</a>
            <button type="button" class="is-primary" onclick="window.print()">{{ __('purchases.print.print_button') }}</button>
            <button type="button" onclick="window.close()">{{ __('purchases.print.close_button') }}</button>
        </div>

        <div @class(['invoice', 'invoice--a4' => ! $isReceipt, 'invoice--receipt' => $isReceipt])>
            <header class="invoice__header">
                <h1 class="invoice__title">{{ __('purchases.print.heading') }}</h1>
                <div class="invoice__meta">
                    <strong>{{ $purchase->purchase_number }}</strong><br>
                    {{ $purchase->purchase_date?->format('d M Y') }}
                </div>
            </header>

            <div class="invoice__parties">
                <div class="invoice__party">
                    @unless ($isReceipt)
                        <h2>{{ __('purchases.print.supplier') }}</h2>
                    @endunless
                    <p class="invoice__party-line">
                        <strong>{{ $supplier?->displayBusinessName() ?? '—' }}</strong>
                        @if (filled($supplierAddress))
                            <span class="receipt-item__sep">·</span>{{ $supplierAddress }}
                        @endif
                        @if (filled($supplierPhone))
                            <span class="receipt-item__sep">·</span>{{ $supplierPhone }}
                        @endif
                        @if ((float) $purchase->supplier_balance_snapshot !== 0.0)
                            <span class="receipt-item__sep">·</span>{{ __('purchases.print.supplier_balance') }}: <strong>{{ number_format((float) $purchase->supplier_balance_snapshot, 2) }}</strong>
                        @endif
                    </p>
                </div>
                <div class="invoice__party">
                    @unless ($isReceipt)
                        <h2>{{ __('purchases.print.buyer') }}</h2>
                    @endunless
                    <p class="invoice__party-line">
                        <strong>{{ $buyerName ?: '—' }}</strong>
                        @if (filled($buyerAddress))
                            <span class="receipt-item__sep">·</span>{{ $buyerAddress }}
                        @endif
                        @if (filled($buyerPhone))
                            <span class="receipt-item__sep">·</span>{{ $buyerPhone }}
                        @endif
                    </p>
                </div>
            </div>

            <div class="invoice__status">
                <span>{{ __('purchases.fields.purchaser') }}: <strong>{{ $purchase->purchaser?->name ?? '—' }}</strong></span>
                <span>{{ __('purchases.fields.delivery_status') }}: <strong>{{ __('purchases.statuses.delivery.' . $purchase->delivery_status) }}</strong></span>
                <span>{{ __('purchases.fields.payment_status') }}: <strong>{{ __('purchases.statuses.payment.' . $purchase->payment_status) }}</strong></span>
                <span>{{ __('purchases.fields.dispute_status') }}: <strong>{{ __('purchases.statuses.dispute.' . ($purchase->dispute_status ?? 'ok')) }}</strong></span>
            </div>

            @if (filled($purchase->notes))
                <div class="invoice__notes">
                    <strong>{{ __('purchases.fields.notes') }}:</strong> {{ $purchase->notes }}
                </div>
            @endif

            @if ($isReceipt)
                <div class="receipt-items">
                    @foreach ($purchase->items as $index => $item)
                        <article class="receipt-item">
                            <p class="receipt-item__name">
                                {{ $index + 1 }}. {{ $item->displayName($locale) }}
                            </p>
                            <div class="receipt-item__grid">
                                <div class="receipt-item__field">
                                    <span class="receipt-item__label">{{ __('purchases.print.receipt.required_qty') }}</span>
                                    <span class="receipt-item__value">{{ $item->required_qty ?: '—' }}</span>
                                </div>
                                <div class="receipt-item__field">
                                    <span class="receipt-item__label">{{ __('purchases.print.receipt.purchase_qty') }}</span>
                                    <span class="receipt-item__value">{{ $item->purchase_qty ?: '—' }}</span>
                                </div>
                                <div class="receipt-item__field">
                                    <span class="receipt-item__label">{{ __('purchases.print.receipt.previous_price') }}</span>
                                    <span class="receipt-item__value">{{ $item->previous_price ?: '—' }}</span>
                                </div>
                                <div class="receipt-item__field">
                                    <span class="receipt-item__label">{{ __('purchases.print.receipt.current_rate') }}</span>
                                    <span class="receipt-item__value">{{ $item->current_rate ?: '—' }}</span>
                                </div>
                                <div class="receipt-item__field">
                                    <span class="receipt-item__label">{{ __('purchases.print.receipt.landing_cost') }}</span>
                                    <span class="receipt-item__value">{{ $item->landing_cost ?: '—' }}</span>
                                </div>
                                <div class="receipt-item__field">
                                    <span class="receipt-item__label">{{ __('purchases.print.receipt.cost_price') }}</span>
                                    <span class="receipt-item__value">{{ $item->cost_price ?: '—' }}</span>
                                </div>
                                <div class="receipt-item__field">
                                    <span class="receipt-item__label">{{ __('purchases.print.receipt.sale_price') }}</span>
                                    <span class="receipt-item__value">{{ $item->sale_price ?: '—' }}</span>
                                </div>
                                <div class="receipt-item__field receipt-item__field--total">
                                    <span class="receipt-item__label">{{ __('purchases.print.receipt.line_total') }}</span>
                                    <span class="receipt-item__value">{{ $item->line_total ?: '—' }}</span>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <table class="invoice__table">
                    <thead>
                        <tr>
                            <th>{{ __('purchases.columns.sr') }}</th>
                            <th>{{ __('purchases.columns.thumb') }}</th>
                            <th>{{ __('purchases.columns.product_name') }}</th>
                            <th>{{ __('purchases.columns.required_qty') }}</th>
                            <th>{{ __('purchases.columns.purchase_qty') }}</th>
                            <th>{{ __('purchases.columns.previous_price') }}</th>
                            <th>{{ __('purchases.columns.current_rate') }}</th>
                            <th>{{ __('purchases.columns.landing_cost') }}</th>
                            <th>{{ __('purchases.columns.cost_price') }}</th>
                            <th>{{ __('purchases.columns.sale_price') }}</th>
                            <th>{{ __('purchases.columns.line_total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purchase->items as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>@if ($item->imageUrl())<img src="{{ $item->imageUrl() }}" alt="" class="thumb" />@endif</td>
                                <td class="name">{{ $item->displayName($locale) }}</td>
                                <td>{{ $item->required_qty ?: '—' }}</td>
                                <td>{{ $item->purchase_qty ?: '—' }}</td>
                                <td>{{ $item->previous_price ?: '—' }}</td>
                                <td>{{ $item->current_rate ?: '—' }}</td>
                                <td>{{ $item->landing_cost ?: '—' }}</td>
                                <td>{{ $item->cost_price ?: '—' }}</td>
                                <td>{{ $item->sale_price ?: '—' }}</td>
                                <td>{{ $item->line_total ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            <div class="invoice__totals">
                <div><span>{{ __('purchases.fields.subtotal') }}</span><strong>{{ number_format((float) $purchase->subtotal, 2) }}</strong></div>
                <div><span>{{ __('purchases.fields.total_amount') }}</span><strong>{{ number_format((float) $purchase->total_amount, 2) }}</strong></div>
                <div><span>{{ __('purchases.fields.amount_paid') }}</span><strong>{{ number_format((float) $purchase->amount_paid, 2) }}</strong></div>
                <div><span>{{ __('purchases.fields.balance_due') }}</span><strong>{{ number_format((float) $purchase->balance_due, 2) }}</strong></div>
            </div>
        </div>
    </div>

    @if ($autoprint)
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
