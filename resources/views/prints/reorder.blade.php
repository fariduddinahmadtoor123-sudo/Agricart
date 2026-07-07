@php
    $isReceipt = $paper === 'receipt';
    $receiptWidth = $receiptProfile?->width_mm ?? 75;
    $receiptMargin = $receiptProfile?->margin_mm ?? 2;
    $widthCss = rtrim(rtrim(number_format($receiptWidth, 2, '.', ''), '0'), '.');
    $marginCss = rtrim(rtrim(number_format($receiptMargin, 2, '.', ''), '0'), '.');
    $layoutQuery = request()->except(['paper', 'autoprint']);
@endphp
<!DOCTYPE html>
<html lang="{{ $locale === 'ur' ? 'ur' : 'en' }}" @if($locale === 'ur') dir="rtl" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('reorder.print.title') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f3f4f6; color: #111827; }
        .print-shell { min-height: 100vh; display: flex; flex-direction: column; align-items: center; gap: 1rem; padding: 1.5rem; }
        .print-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; justify-content: center; }
        .print-actions button, .print-actions a { border: 1px solid #d1d5db; background: #fff; padding: 0.5rem 0.875rem; font-weight: 700; cursor: pointer; text-decoration: none; color: inherit; font-size: 0.8125rem; }
        .print-actions button.is-primary, .print-actions a.is-primary { background: #83B735; border-color: #83B735; color: #fff; }
        .print-actions a.is-active { border-color: #111827; box-shadow: inset 0 0 0 1px #111827; }
        .sheet { width: 100%; background: #fff; border: 1px solid #d1d5db; padding: 1rem; }
        .sheet--a4 { max-width: 210mm; }
        .sheet--receipt { max-width: {{ $widthCss }}mm; padding: 0.5rem 0.45rem; font-size: 0.68rem; }
        .sheet__header { border-bottom: 2px solid #111827; padding-bottom: 0.5rem; margin-bottom: 0.55rem; }
        .sheet__title { margin: 0; font-size: 1.15rem; }
        .sheet__hint { margin: 0.35rem 0 0; font-size: 0.75rem; color: #4b5563; font-style: italic; }
        .sheet__dual { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; }
        .sheet__col-title { margin: 0 0 0.25rem; font-size: 0.7rem; font-weight: 700; text-align: center; color: #6b7280; }
        .mini-table { width: 100%; border-collapse: collapse; font-size: 0.62rem; }
        .mini-table th, .mini-table td { border: 1px solid #9ca3af; padding: 0.2rem 0.15rem; vertical-align: middle; text-align: center; }
        .mini-table th { background: #f3f4f6; font-weight: 700; }
        .mini-table .name { text-align: start !important; font-weight: 600; }
        .mini-table .thumb { width: 1.35rem; height: 1.35rem; object-fit: cover; }
        .mini-table .blank { min-width: 2.5rem; height: 1.2rem; background: #fafafa; }
        .receipt-items { display: flex; flex-direction: column; gap: 0.45rem; }
        .receipt-item { border: 1px solid #d1d5db; border-radius: 0.2rem; padding: 0.35rem 0.3rem; background: #fafafa; }
        .receipt-item__name { margin: 0 0 0.25rem; font-weight: 800; font-size: 0.7rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.15rem; }
        .receipt-item__grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.15rem; }
        .receipt-item__field { display: flex; justify-content: space-between; gap: 0.15rem; padding: 0.12rem 0.2rem; background: #fff; border: 1px solid #e5e7eb; font-size: 0.58rem; }
        .receipt-item__field--wide { grid-column: 1 / -1; min-height: 1.5rem; align-items: center; }
        .receipt-item__field--wide .val { flex: 1; border-bottom: 1px dashed #9ca3af; min-height: 1rem; }
        .sheet__footer { margin-top: 0.75rem; padding-top: 0.55rem; border-top: 2px solid #111827; text-align: center; }
        .sheet__total-label { margin: 0; font-size: 0.72rem; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: 0.04em; }
        .sheet__total-value { margin: 0.2rem 0 0; font-family: 'Courier New', Courier, monospace; font-size: 1.15rem; font-weight: 800; letter-spacing: 0.05em; }
        .sheet__total-meta { margin: 0.2rem 0 0; font-size: 0.65rem; color: #6b7280; }
        @media print { body { background: #fff; } .print-actions { display: none; } .print-shell { padding: 0; } .sheet { border: none; max-width: none; width: 100%; } }
        @if ($isReceipt)
        @media print { @page { size: {{ $widthCss }}mm auto; margin: {{ $marginCss }}mm; } }
        @else
        @media print { @page { size: A4 portrait; margin: 8mm; } }
        @endif
    </style>
</head>
<body>
    <div class="print-shell">
        <div class="print-actions">
            <a href="{{ route('admin.reorder.print', array_merge($layoutQuery, ['paper' => 'a4', 'autoprint' => 0])) }}" @class(['is-active' => ! $isReceipt])>{{ __('reorder.print.layout_a4') }}</a>
            <a href="{{ route('admin.reorder.print', array_merge($layoutQuery, ['paper' => 'receipt', 'autoprint' => 0])) }}" @class(['is-active' => $isReceipt])>{{ __('reorder.print.layout_receipt') }}</a>
            <button type="button" class="is-primary" onclick="window.print()">{{ __('reorder.print.print_button') }}</button>
            <button type="button" onclick="window.close()">{{ __('reorder.print.close_button') }}</button>
        </div>

        <div @class(['sheet', 'sheet--a4' => ! $isReceipt, 'sheet--receipt' => $isReceipt])>
            <header class="sheet__header">
                <h1 class="sheet__title">{{ __('reorder.print.heading') }}</h1>
                <p class="sheet__hint">{{ __('reorder.print.rate_hint') }}</p>
            </header>

            @if ($isReceipt)
                <div class="receipt-items">
                    @foreach ($rows as $row)
                        <article class="receipt-item">
                            <p class="receipt-item__name">{{ $row['sr'] }}. {{ $row['name'] }}</p>
                            <div class="receipt-item__grid">
                                <div class="receipt-item__field"><span>{{ __('reorder.print.receipt.required_qty') }}</span><strong>{{ $row['required_qty'] ?: '—' }}</strong></div>
                                <div class="receipt-item__field"><span>{{ __('reorder.print.receipt.previous_price') }}</span><strong><x-secret-price :value="$row['previous_price'] ?? ''" /></strong></div>
                                <div class="receipt-item__field receipt-item__field--wide"><span>{{ __('reorder.print.receipt.current_price') }}</span><span class="val"></span></div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="sheet__dual">
                    @foreach ([$leftRows, $rightRows] as $colIndex => $colRows)
                        <div>
                            <p class="sheet__col-title">{{ $colIndex === 0 ? 'A' : 'B' }}</p>
                            <table class="mini-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th></th>
                                        <th>{{ __('reorder.columns.product_name') }}</th>
                                        <th>{{ __('reorder.columns.required_qty') }}</th>
                                        <th>{{ __('reorder.columns.previous_price') }}</th>
                                        <th>{{ __('reorder.columns.current_price') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($colRows as $row)
                                        <tr>
                                            <td>{{ $row['sr'] }}</td>
                                            <td>@if ($row['image_url'])<img src="{{ $row['image_url'] }}" alt="" class="thumb" />@endif</td>
                                            <td class="name">{{ $row['name'] }}</td>
                                            <td>{{ $row['required_qty'] ?: '—' }}</td>
                                            <td><x-secret-price :value="$row['previous_price'] ?? ''" /></td>
                                            <td class="blank"></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                </div>
            @endif

            @if (($orderSummary['line_count'] ?? 0) > 0 && filled($orderSummary['formatted'] ?? null))
                <footer class="sheet__footer">
                    <p class="sheet__total-label">{{ __('reorder.print.estimated_total') }}</p>
                    <p class="sheet__total-value"><x-secret-price :value="$orderSummary['formatted']" /></p>
                    <p class="sheet__total-meta">
                        {{ __('reorder.print.total_meta', [
                            'lines' => $orderSummary['line_count'],
                            'qty' => app(\App\Services\Purchasing\ReorderService::class)->formatQuantity($orderSummary['qty_total']),
                        ]) }}
                    </p>
                </footer>
            @endif
        </div>
    </div>
    @if ($autoprint)<script>window.addEventListener('load', () => window.print());</script>@endif
</body>
</html>
