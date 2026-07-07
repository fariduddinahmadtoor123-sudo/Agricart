@php
    $isReceipt = $paperSize === 'receipt';
    $receiptWidth = $receiptProfile?->width_mm ?? 75;
    $receiptMargin = $receiptProfile?->margin_mm ?? 2;
    $widthCss = rtrim(rtrim(number_format($receiptWidth, 2, '.', ''), '0'), '.');
    $marginCss = rtrim(rtrim(number_format($receiptMargin, 2, '.', ''), '0'), '.');
    $printRows = $summary->items->filter(fn ($item) => filled($item->payment_amount) || filled($item->line_status));
    $sources = collect($summary->payment_sources ?? [])->filter(fn ($s) => filled($s['label'] ?? null) || filled($s['amount'] ?? null));
@endphp
<!DOCTYPE html>
<html lang="{{ $locale === 'ur' ? 'ur' : 'en' }}" @if($locale === 'ur') dir="rtl" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $summary->summary_number }} — {{ __('purchase_payment_summary.print.title') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f3f4f6; color: #111827; }
        .print-shell { min-height: 100vh; display: flex; flex-direction: column; align-items: center; gap: 1rem; padding: 1.5rem; }
        .print-actions { display: flex; gap: 0.5rem; }
        .print-actions button { border: 1px solid #d1d5db; background: #fff; padding: 0.5rem 0.875rem; font-weight: 700; cursor: pointer; }
        .print-actions button.is-primary { background: #83B735; border-color: #83B735; color: #fff; }
        .sheet { width: 100%; background: #fff; border: 1px solid #d1d5db; padding: 1rem; }
        .sheet--a4 { max-width: 210mm; }
        .sheet--receipt {
            max-width: {{ $widthCss }}mm;
            padding: 0.5rem 0.45rem;
            font-size: 0.68rem;
            line-height: 1.35;
        }
        .sheet__header { border-bottom: 2px solid #111827; padding-bottom: 0.45rem; margin-bottom: 0.65rem; }
        .sheet--receipt .sheet__header { padding-bottom: 0.35rem; margin-bottom: 0.45rem; }
        .sheet__store { margin: 0 0 0.15rem; font-size: 1.05rem; font-weight: 800; }
        .sheet--receipt .sheet__store { font-size: 0.82rem; }
        .sheet__party { margin: 0.1rem 0; font-size: 0.78rem; color: #374151; }
        .sheet--receipt .sheet__party { font-size: 0.64rem; }
        .sheet__party strong { color: #111827; }
        .sheet__title { margin: 0 0 0.15rem; font-size: 0.9rem; font-weight: 700; }
        .sheet--receipt .sheet__title { font-size: 0.78rem; }
        .sheet__meta { margin: 0; font-size: 0.74rem; color: #4b5563; display: flex; flex-wrap: wrap; gap: 0.2rem 0.45rem; align-items: center; }
        .sheet--receipt .sheet__meta { font-size: 0.62rem; gap: 0.15rem 0.3rem; }
        .sheet__meta .sep { color: #9ca3af; }
        table { width: 100%; border-collapse: collapse; font-size: 0.72rem; }
        th, td { border: 1px solid #9ca3af; padding: 0.3rem 0.35rem; vertical-align: top; }
        th { background: #f3f4f6; font-weight: 700; }
        .party-name { font-weight: 700; display: block; }
        .party-meta { font-size: 0.66rem; color: #4b5563; margin-top: 0.1rem; }
        .receipt-items { display: flex; flex-direction: column; gap: 0.45rem; }
        .receipt-item { border: 1px solid #d1d5db; border-radius: 0.2rem; padding: 0.35rem 0.3rem; background: #fafafa; }
        .receipt-item__name { margin: 0 0 0.25rem; font-weight: 800; font-size: 0.7rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.15rem; word-break: break-word; }
        .receipt-item__meta { margin: 0 0 0.2rem; font-size: 0.58rem; color: #4b5563; }
        .receipt-item__grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.15rem; }
        .receipt-item__field { display: flex; justify-content: space-between; gap: 0.15rem; padding: 0.12rem 0.2rem; background: #fff; border: 1px solid #e5e7eb; font-size: 0.58rem; }
        .receipt-item__field--wide { grid-column: 1 / -1; }
        .sources { margin-top: 0.75rem; }
        .sheet--receipt .sources { margin-top: 0.5rem; }
        .sources h2 { margin: 0 0 0.35rem; font-size: 0.82rem; }
        .sheet--receipt .sources h2 { font-size: 0.68rem; margin-bottom: 0.25rem; }
        .sources-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.35rem; }
        .sheet--receipt .sources-grid { grid-template-columns: 1fr; gap: 0.25rem; }
        .source-box { border: 1px solid #d1d5db; padding: 0.35rem 0.45rem; background: #fafafa; font-size: 0.72rem; }
        .sheet--receipt .source-box { font-size: 0.62rem; padding: 0.25rem 0.35rem; display: flex; justify-content: space-between; gap: 0.35rem; }
        .source-box strong { display: block; font-size: 0.85rem; margin-top: 0.15rem; }
        .sheet--receipt .source-box strong { display: inline; font-size: inherit; margin-top: 0; }
        .notes { margin-top: 0.75rem; border-top: 1px solid #d1d5db; padding-top: 0.5rem; }
        .sheet--receipt .notes { margin-top: 0.5rem; padding-top: 0.35rem; font-size: 0.64rem; }
        .notes h2 { margin: 0 0 0.25rem; font-size: 0.82rem; }
        .sheet--receipt .notes h2 { font-size: 0.68rem; }
        .totals { margin-top: 0.65rem; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.35rem; }
        .sheet--receipt .totals { grid-template-columns: 1fr; gap: 0.2rem; margin-top: 0.5rem; border-top: 1px solid #111827; padding-top: 0.35rem; }
        .totals div { border: 1px solid #d1d5db; padding: 0.35rem 0.45rem; background: #fafafa; font-size: 0.72rem; }
        .sheet--receipt .totals div { border: none; padding: 0.15rem 0; background: transparent; font-size: 0.64rem; display: flex; justify-content: space-between; gap: 0.35rem; }
        .totals strong { display: block; font-size: 0.9rem; margin-top: 0.1rem; }
        .sheet--receipt .totals strong { display: inline; font-size: inherit; margin-top: 0; }
        @media print { body { background: #fff; } .print-actions { display: none; } .print-shell { padding: 0; } .sheet { border: none; max-width: none; width: 100%; } }
        @if ($isReceipt)
        @media print { .sheet--receipt { padding: 0; } @page { size: {{ $widthCss }}mm auto; margin: {{ $marginCss }}mm; } }
        @else
        @media print { @page { size: A4 portrait; margin: 8mm; } }
        @endif
    </style>
</head>
<body class="{{ $isReceipt ? 'is-receipt' : 'is-a4' }}">
    <div class="print-shell">
        <div class="print-actions">
            <button type="button" class="is-primary" onclick="window.print()">{{ __('purchase_payment_summary.print.print_button') }}</button>
            <button type="button" onclick="window.close()">{{ __('purchase_payment_summary.print.close_button') }}</button>
        </div>

        <div @class(['sheet', 'sheet--a4' => ! $isReceipt, 'sheet--receipt' => $isReceipt])>
            <header class="sheet__header">
                @if (filled($storeName))
                    <p class="sheet__store">{{ $storeName }}</p>
                @endif
                @if (filled($purchaserName))
                    <p class="sheet__party">{{ __('purchase_payment_summary.fields.purchaser') }}: <strong>{{ $purchaserName }}</strong></p>
                @endif
                @if (filled($printedByName))
                    <p class="sheet__party">{{ __('purchase_payment_summary.fields.printed_by') }}: <strong>{{ $printedByName }}</strong></p>
                @endif
                <h1 class="sheet__title">{{ __('purchase_payment_summary.print.heading') }}</h1>
                <p class="sheet__meta">
                    <code>{{ $summary->summary_number }}</code>
                    <span class="sep">·</span>
                    <span>{{ __('purchase_payment_summary.print.date') }}: {{ $summary->summary_date?->format('d M Y') }}</span>
                    <span class="sep">·</span>
                    <span>{{ __('purchase_payment_summary.fields.paper_size') }}: {{ __('purchase_payment_summary.paper_sizes.' . $paperSize) }}</span>
                    <span class="sep">·</span>
                    <span>{{ __('purchase_payment_summary.fields.locale') }}: {{ $summary->localeLabel() }}</span>
                </p>
            </header>

            @if ($isReceipt)
                <div class="receipt-items">
                    @forelse ($printRows as $index => $item)
                        <article class="receipt-item">
                            <p class="receipt-item__name">{{ $index + 1 }}. {{ $item->displayName($locale) }}</p>
                            @if (filled($item->phone) || filled($item->address))
                                <p class="receipt-item__meta">{{ collect([$item->phone, $item->address])->filter()->implode(' · ') }}</p>
                            @endif
                            <div class="receipt-item__grid">
                                <div class="receipt-item__field receipt-item__field--wide">
                                    <span>{{ __('purchase_payment_summary.fields.payment_amount') }}</span>
                                    <strong>{{ $item->payment_amount ?: '—' }}</strong>
                                </div>
                                <div class="receipt-item__field receipt-item__field--wide">
                                    <span>{{ __('purchase_payment_summary.fields.line_status') }}</span>
                                    <strong>{{ $item->lineStatusLabel() ?: '—' }}</strong>
                                </div>
                            </div>
                        </article>
                    @empty
                        <p>—</p>
                    @endforelse
                </div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('purchase_payment_summary.columns.supplier') }}</th>
                            <th>{{ __('purchase_payment_summary.fields.payment_amount') }}</th>
                            <th>{{ __('purchase_payment_summary.fields.line_status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($printRows as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <span class="party-name">{{ $item->displayName($locale) }}</span>
                                    @if (filled($item->phone) || filled($item->address))
                                        <span class="party-meta">
                                            {{ collect([$item->phone, $item->address])->filter()->implode(' · ') }}
                                        </span>
                                    @endif
                                </td>
                                <td>{{ $item->payment_amount ?: '—' }}</td>
                                <td>{{ $item->lineStatusLabel() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">—</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @endif

            @if ($sources->isNotEmpty())
                <section class="sources">
                    <h2>{{ __('purchase_payment_summary.print.sources_heading') }}</h2>
                    <div class="sources-grid">
                        @foreach ($sources as $source)
                            <div class="source-box">
                                <span>{{ $source['label'] ?: '—' }}</span>
                                <strong>{{ $source['amount'] ?: '—' }}</strong>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <div class="totals">
                <div>
                    <span>{{ __('purchase_payment_summary.print.total_payments') }}</span>
                    <strong><x-secret-price :value="(string) (int) round($summary->totalPayments())" /></strong>
                </div>
                <div>
                    <span>{{ __('purchase_payment_summary.print.total_sources') }}</span>
                    <strong><x-secret-price :value="(string) (int) round($summary->totalSourcePayments())" /></strong>
                </div>
                <div>
                    <span>{{ __('purchase_payment_summary.print.balance') }}</span>
                    <strong><x-secret-price :value="(string) (int) round(abs($summary->balanceRemaining()))" /></strong>
                </div>
            </div>

            @if (filled($summary->notes))
                <section class="notes">
                    <h2>{{ __('purchase_payment_summary.print.notes') }}</h2>
                    <p>{{ $summary->notes }}</p>
                </section>
            @endif
        </div>
    </div>

    @if ($autoprint)
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
