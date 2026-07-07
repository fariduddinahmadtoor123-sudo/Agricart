@php
    $isReceipt = $paper === 'receipt';
    $receiptWidth = $receiptProfile?->width_mm ?? 75;
    $receiptMargin = $receiptProfile?->margin_mm ?? 2;
    $widthCss = rtrim(rtrim(number_format($receiptWidth, 2, '.', ''), '0'), '.');
    $marginCss = rtrim(rtrim(number_format($receiptMargin, 2, '.', ''), '0'), '.');
    $supplier = $quotation->supplier;
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
    <title>{{ $quotation->quotation_number }} — {{ __('purchase_quotations.print.title') }}</title>
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
        .quotation-sheet {
            width: 100%;
            background: #fff;
            border: 1px solid #d1d5db;
            padding: 1rem;
        }
        .quotation-sheet--a4 { max-width: 210mm; }
        .quotation-sheet--receipt {
            max-width: {{ $widthCss }}mm;
            padding: 0.5rem 0.45rem;
            font-size: 0.68rem;
            line-height: 1.35;
        }
        .quotation-sheet__header {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            border-bottom: 2px solid #111827;
            padding-bottom: 0.55rem;
            margin-bottom: 0.55rem;
        }
        .quotation-sheet__title {
            margin: 0;
            font-size: 1.2rem;
        }
        .quotation-sheet--receipt .quotation-sheet__title { font-size: 0.95rem; }
        .quotation-sheet__meta {
            font-size: 0.8125rem;
            text-align: end;
        }
        .quotation-sheet--receipt .quotation-sheet__meta { font-size: 0.68rem; }
        .quotation-sheet__parties {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 0.75rem;
            font-size: 0.8125rem;
        }
        .quotation-sheet--receipt .quotation-sheet__parties {
            grid-template-columns: 1fr;
            gap: 0.35rem;
            margin-bottom: 0.45rem;
            font-size: 0.66rem;
        }
        .quotation-sheet__party h2 {
            margin: 0 0 0.25rem;
            font-size: 0.875rem;
        }
        .quotation-sheet--receipt .quotation-sheet__party h2 { display: none; }
        .quotation-sheet__party p { margin: 0.15rem 0; word-break: break-word; }
        .quotation-sheet__party-line strong { font-weight: 800; }
        .quotation-sheet__hint {
            margin: 0 0 0.55rem;
            font-size: 0.75rem;
            color: #4b5563;
            font-style: italic;
        }
        .quotation-sheet--receipt .quotation-sheet__hint {
            font-size: 0.62rem;
            margin-bottom: 0.4rem;
        }
        .quotation-sheet__notes {
            font-size: 0.8125rem;
            margin-bottom: 0.75rem;
            padding: 0.5rem 0.65rem;
            border: 1px solid #d1d5db;
            background: #f9fafb;
        }
        .quotation-sheet--receipt .quotation-sheet__notes {
            font-size: 0.64rem;
            margin-bottom: 0.45rem;
            padding: 0.35rem 0.45rem;
        }
        .quotation-sheet__table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.75rem;
        }
        .quotation-sheet__table th,
        .quotation-sheet__table td {
            border: 1px solid #9ca3af;
            padding: 0.35rem 0.3rem;
            vertical-align: middle;
            text-align: center;
        }
        .quotation-sheet__table th { background: #f3f4f6; font-weight: 700; }
        .quotation-sheet__table .name {
            text-align: start !important;
            min-width: 8rem;
            font-weight: 600;
        }
        .quotation-sheet__thumb {
            width: 2.25rem;
            height: 2.25rem;
            object-fit: cover;
            display: block;
            margin: 0 auto;
        }
        .quotation-sheet__quoted {
            min-width: 4rem;
            height: 1.75rem;
            background: #fafafa;
        }
        .receipt-items { display: flex; flex-direction: column; gap: 0.5rem; }
        .receipt-item {
            border: 1px solid #d1d5db;
            border-radius: 0.2rem;
            padding: 0.35rem 0.3rem 0.3rem;
            background: #fafafa;
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
        .receipt-item__field--quoted {
            grid-column: 1 / -1;
            min-height: 1.65rem;
            align-items: center;
            background: #fff;
        }
        .receipt-item__field--quoted .receipt-item__value {
            flex: 1 1 auto;
            min-height: 1.2rem;
            border-bottom: 1px dashed #9ca3af;
            white-space: normal;
        }
        .receipt-item__sep { color: #9ca3af; padding-inline: 0.12rem; }
        @media print {
            body { background: #fff; }
            .print-actions { display: none; }
            .print-shell { padding: 0; }
            .quotation-sheet { border: none; max-width: none; width: 100%; }
            .quotation-sheet--receipt { padding: 0; }
        }
        @if ($isReceipt)
        @media print {
            @page { size: {{ $widthCss }}mm auto; margin: {{ $marginCss }}mm; }
        }
        @else
        @media print {
            @page { size: A4 portrait; margin: 10mm; }
        }
        @endif
    </style>
</head>
<body class="{{ $isReceipt ? 'is-receipt' : 'is-a4' }}">
    <div class="print-shell">
        <div class="print-actions">
            <a
                href="{{ route('admin.purchase-quotations.print', array_merge([$quotation], $layoutQuery, ['paper' => 'a4', 'autoprint' => 0])) }}"
                @class(['is-active' => ! $isReceipt])
            >{{ __('purchase_quotations.print.layout_a4') }}</a>
            <a
                href="{{ route('admin.purchase-quotations.print', array_merge([$quotation], $layoutQuery, ['paper' => 'receipt', 'autoprint' => 0])) }}"
                @class(['is-active' => $isReceipt])
            >{{ __('purchase_quotations.print.layout_receipt') }}</a>
            <button type="button" class="is-primary" onclick="window.print()">{{ __('purchase_quotations.print.print_button') }}</button>
            <button type="button" onclick="window.close()">{{ __('purchase_quotations.print.close_button') }}</button>
        </div>

        <div @class(['quotation-sheet', 'quotation-sheet--a4' => ! $isReceipt, 'quotation-sheet--receipt' => $isReceipt])>
            <header class="quotation-sheet__header">
                <h1 class="quotation-sheet__title">{{ __('purchase_quotations.print.heading') }}</h1>
                <div class="quotation-sheet__meta">
                    <strong>{{ $quotation->quotation_number }}</strong><br>
                    {{ $quotation->quotation_date?->format('d M Y') }}
                </div>
            </header>

            <div class="quotation-sheet__parties">
                <div class="quotation-sheet__party">
                    @unless ($isReceipt)
                        <h2>{{ __('purchase_quotations.print.buyer') }}</h2>
                    @endunless
                    <p class="quotation-sheet__party-line">
                        <strong>{{ $buyerName ?: '—' }}</strong>
                        @if (filled($buyerAddress))
                            <span class="receipt-item__sep">·</span>{{ $buyerAddress }}
                        @endif
                        @if (filled($buyerPhone))
                            <span class="receipt-item__sep">·</span>{{ $buyerPhone }}
                        @endif
                    </p>
                </div>
                <div class="quotation-sheet__party">
                    @unless ($isReceipt)
                        <h2>{{ __('purchase_quotations.print.supplier') }}</h2>
                    @endunless
                    <p class="quotation-sheet__party-line">
                        <strong>{{ $supplier?->displayBusinessName() ?? '—' }}</strong>
                        @if (filled($supplierAddress))
                            <span class="receipt-item__sep">·</span>{{ $supplierAddress }}
                        @endif
                        @if (filled($supplierPhone))
                            <span class="receipt-item__sep">·</span>{{ $supplierPhone }}
                        @endif
                    </p>
                </div>
            </div>

            <p class="quotation-sheet__hint">{{ __('purchase_quotations.print.rate_hint') }}</p>

            @if (filled($quotation->notes))
                <div class="quotation-sheet__notes">
                    <strong>{{ __('purchase_quotations.fields.notes') }}:</strong> {{ $quotation->notes }}
                </div>
            @endif

            @if ($isReceipt)
                <div class="receipt-items">
                    @foreach ($quotation->items as $index => $item)
                        <article class="receipt-item">
                            <p class="receipt-item__name">{{ $index + 1 }}. {{ $item->displayName($locale) }}</p>
                            <div class="receipt-item__grid">
                                <div class="receipt-item__field">
                                    <span class="receipt-item__label">{{ __('purchase_quotations.print.receipt.required_qty') }}</span>
                                    <span class="receipt-item__value">{{ $item->required_qty ?: '—' }}</span>
                                </div>
                                <div class="receipt-item__field">
                                    <span class="receipt-item__label">{{ __('purchase_quotations.print.receipt.previous_price') }}</span>
                                    <span class="receipt-item__value"><x-secret-price :value="$item->previous_price" /></span>
                                </div>
                                <div class="receipt-item__field receipt-item__field--quoted">
                                    <span class="receipt-item__label">{{ __('purchase_quotations.print.receipt.quoted_rate') }}</span>
                                    <span class="receipt-item__value"></span>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <table class="quotation-sheet__table">
                    <thead>
                        <tr>
                            <th>{{ __('purchase_quotations.columns.sr') }}</th>
                            <th>{{ __('purchase_quotations.columns.thumb') }}</th>
                            <th>{{ __('purchase_quotations.columns.product_name') }}</th>
                            <th>{{ __('purchase_quotations.columns.required_qty') }}</th>
                            <th>{{ __('purchase_quotations.columns.previous_price') }}</th>
                            <th>{{ __('purchase_quotations.columns.quoted_rate') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($quotation->items as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    @if ($item->imageUrl())
                                        <img src="{{ $item->imageUrl() }}" alt="" class="quotation-sheet__thumb" />
                                    @endif
                                </td>
                                <td class="name">{{ $item->displayName($locale) }}</td>
                                <td>{{ $item->required_qty ?: '—' }}</td>
                                <td><x-secret-price :value="$item->previous_price" /></td>
                                <td class="quotation-sheet__quoted"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    @if ($autoprint)
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
