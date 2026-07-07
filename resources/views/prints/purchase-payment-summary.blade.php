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
        .sheet--a5 { max-width: 148mm; }
        .sheet__header { border-bottom: 2px solid #111827; padding-bottom: 0.5rem; margin-bottom: 0.75rem; }
        .sheet__title { margin: 0; font-size: 1.15rem; }
        .sheet__meta { margin: 0.25rem 0 0; font-size: 0.8rem; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; font-size: 0.78rem; }
        th, td { border: 1px solid #9ca3af; padding: 0.35rem 0.4rem; vertical-align: top; }
        th { background: #f3f4f6; font-weight: 700; }
        .name { font-weight: 700; }
        .address { font-size: 0.72rem; color: #374151; }
        .sources { margin-top: 0.85rem; }
        .sources h2 { margin: 0 0 0.35rem; font-size: 0.9rem; }
        .sources-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.35rem; }
        .source-box { border: 1px solid #d1d5db; padding: 0.35rem 0.45rem; background: #fafafa; }
        .source-box strong { display: block; font-size: 0.95rem; margin-top: 0.15rem; }
        .notes { margin-top: 0.85rem; border-top: 1px solid #d1d5db; padding-top: 0.5rem; }
        .notes h2 { margin: 0 0 0.25rem; font-size: 0.85rem; }
        .total { margin-top: 0.65rem; text-align: {{ $locale === 'ur' ? 'left' : 'right' }}; font-size: 0.95rem; font-weight: 800; }
        @media print { body { background: #fff; } .print-actions { display: none; } .print-shell { padding: 0; } .sheet { border: none; max-width: none; } }
        @media print { @page { size: {{ $summary->paper_size === 'a5' ? 'A5' : 'A4' }} portrait; margin: 8mm; } }
    </style>
</head>
<body>
    <div class="print-shell">
        <div class="print-actions">
            <button type="button" class="is-primary" onclick="window.print()">{{ __('purchase_payment_summary.print.print_button') }}</button>
            <button type="button" onclick="window.close()">{{ __('purchase_payment_summary.print.close_button') }}</button>
        </div>

        <div @class(['sheet', 'sheet--a4' => $summary->paper_size !== 'a5', 'sheet--a5' => $summary->paper_size === 'a5'])>
            <header class="sheet__header">
                <h1 class="sheet__title">{{ __('purchase_payment_summary.print.heading') }}</h1>
                <p class="sheet__meta">
                    <code>{{ $summary->summary_number }}</code>
                    · {{ __('purchase_payment_summary.print.date') }}: {{ $summary->summary_date?->format('d M Y') }}
                </p>
            </header>

            @php
                $printRows = $summary->items->filter(fn ($item) => filled($item->payment_amount) || filled($item->line_status));
            @endphp

            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('purchase_payment_summary.fields.supplier') }}</th>
                        <th>{{ __('purchase_payment_summary.fields.phone') }}</th>
                        <th>{{ __('purchase_payment_summary.fields.address') }}</th>
                        <th>{{ __('purchase_payment_summary.fields.payment_amount') }}</th>
                        <th>{{ __('purchase_payment_summary.fields.line_status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($printRows as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="name">{{ $item->displayName($locale) }}</td>
                            <td>{{ $item->phone ?: '—' }}</td>
                            <td class="address">{{ $item->address ?: '—' }}</td>
                            <td>{{ $item->payment_amount ?: '—' }}</td>
                            <td>{{ $item->lineStatusLabel() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">—</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <p class="total">{{ __('purchase_payment_summary.print.total_payments') }}: {{ number_format($summary->totalPayments(), 0) }}</p>

            @php
                $sources = collect($summary->payment_sources ?? [])->filter(fn ($s) => filled($s['label'] ?? null) || filled($s['amount'] ?? null));
            @endphp

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
