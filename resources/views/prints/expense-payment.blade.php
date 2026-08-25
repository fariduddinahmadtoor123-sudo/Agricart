@php
    $isReceipt = $paper === 'receipt';
    $receiptWidth = $receiptProfile?->width_mm ?? 75;
    $receiptMargin = $receiptProfile?->margin_mm ?? 2;
    $widthCss = rtrim(rtrim(number_format((float) $receiptWidth, 2, '.', ''), '0'), '.');
    $marginCss = rtrim(rtrim(number_format((float) $receiptMargin, 2, '.', ''), '0'), '.');
    $printRoute = fn (array $overrides = []): string => route('admin.expense-payments.print', array_merge([
        'payment' => $payment,
        'paper' => $paper,
        'locale' => $locale,
        'autoprint' => 0,
    ], $overrides));
@endphp
<!DOCTYPE html>
<html lang="{{ $locale === 'ur' ? 'ur' : 'en' }}" @if ($locale === 'ur') dir="rtl" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $payment->payment_number }} — {{ __('agricart.finance.expense_payments.print.title') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f4f5f7; color: #0f172a; }
        .print-shell { min-height: 100vh; display: flex; flex-direction: column; align-items: center; gap: 1rem; padding: 1.5rem; }
        .print-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; justify-content: center; }
        .print-actions button, .print-actions a {
            border: 1px solid #ebedf0; background: #fff; padding: 0.5rem 0.875rem; font-weight: 700;
            cursor: pointer; text-decoration: none; color: inherit; font-size: 0.8125rem; border-radius: 0;
        }
        .print-actions button.is-primary, .print-actions a.is-primary { background: #83B735; border-color: #83B735; color: #fff; }
        .print-actions a.is-active { border-color: #0f172a; box-shadow: inset 0 0 0 1px #0f172a; }
        .note-sheet { width: 100%; background: #fff; border: 1px solid #ebedf0; padding: 1rem; }
        .note-sheet--a4 { max-width: 210mm; font-size: 0.9rem; line-height: 1.5; }
        .note-sheet--receipt { max-width: {{ $widthCss }}mm; padding: 0.55rem 0.45rem; font-size: 0.7rem; line-height: 1.35; }
        .note-sheet__head { text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 0.55rem; margin-bottom: 0.75rem; }
        .note-sheet--receipt .note-sheet__head { border-bottom-style: dashed; border-bottom-width: 1px; margin-bottom: 0.45rem; padding-bottom: 0.35rem; }
        .note-sheet__logo { max-height: 3rem; margin: 0 auto 0.35rem; display: block; }
        .note-sheet--receipt .note-sheet__logo { max-height: 2.25rem; }
        .note-sheet__store { margin: 0; font-size: 1.25rem; font-weight: 800; }
        .note-sheet--receipt .note-sheet__store { font-size: 0.85rem; }
        .note-sheet__doc { margin: 0.25rem 0 0; font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }
        .note-sheet__title { margin: 0 0 0.65rem; font-size: 1.2rem; font-weight: 800; }
        .note-sheet--receipt .note-sheet__title { font-size: 0.9rem; margin-bottom: 0.4rem; }
        .note-sheet__facts { display: grid; gap: 0.35rem; border-top: 1px solid #ebedf0; padding-top: 0.65rem; }
        .note-sheet__fact { display: flex; justify-content: space-between; gap: 0.75rem; font-size: 0.8125rem; }
        .note-sheet--receipt .note-sheet__fact { font-size: 0.62rem; }
        .note-sheet__fact span:first-child { color: #64748b; font-weight: 600; }
        .note-sheet__fact span:last-child { font-weight: 700; text-align: end; }
        .note-sheet__receipt-img { margin-top: 0.75rem; max-width: 100%; border: 1px solid #ebedf0; }
        @media print {
            body { background: #fff; }
            .print-actions { display: none; }
            .print-shell { padding: 0; }
            .note-sheet { border: none; max-width: none; width: 100%; }
            .note-sheet--receipt { padding: 0; }
        }
        @if ($isReceipt)
        @media print { @page { size: {{ $widthCss }}mm auto; margin: {{ $marginCss }}mm; } }
        @else
        @media print { @page { size: A4 portrait; margin: 12mm; } }
        @endif
    </style>
</head>
<body class="{{ $isReceipt ? 'is-receipt' : 'is-a4' }}">
    <div class="print-shell">
        <div class="print-actions">
            <a href="{{ $printRoute(['paper' => 'a4']) }}" @class(['is-active' => ! $isReceipt])>{{ __('pos.print.layout_a4') }}</a>
            <a href="{{ $printRoute(['paper' => 'receipt']) }}" @class(['is-active' => $isReceipt])>{{ __('pos.print.layout_receipt') }}</a>
            <a href="{{ $printRoute(['locale' => 'en']) }}" @class(['is-active' => $locale === 'en'])>{{ __('pos.locales.en') }}</a>
            <a href="{{ $printRoute(['locale' => 'ur']) }}" @class(['is-active' => $locale === 'ur'])>{{ __('pos.locales.ur') }}</a>
            <button type="button" class="is-primary" onclick="window.print()">{{ __('pos.print.print_button') }}</button>
            <button type="button" onclick="window.close()">{{ __('pos.print.close_button') }}</button>
        </div>

        <article @class(['note-sheet', $isReceipt ? 'note-sheet--receipt' : 'note-sheet--a4'])>
            <header class="note-sheet__head">
                @if ($storeLogoUrl)
                    <img src="{{ $storeLogoUrl }}" alt="" class="note-sheet__logo" />
                @endif
                <p class="note-sheet__store">{{ $storeName }}</p>
                <p class="note-sheet__doc">{{ __('agricart.finance.expense_payments.print.title') }}</p>
            </header>

            <h1 class="note-sheet__title">{{ $payment->payment_number }}</h1>

            <div class="note-sheet__facts">
                <div class="note-sheet__fact"><span>{{ __('agricart.finance.expense_payments.fields.payment_date') }}</span><span>{{ $payment->payment_date?->format('d M Y') }} {{ $payment->payment_time ? substr((string) $payment->payment_time, 0, 5) : '' }}</span></div>
                <div class="note-sheet__fact"><span>{{ __('agricart.finance.expense_payments.fields.expense') }}</span><span>{{ $expenseLabel }}</span></div>
                <div class="note-sheet__fact"><span>{{ __('agricart.finance.expense_payments.fields.source_wallet') }}</span><span>{{ $walletLabel }}@if ($payment->source_wallet === 'bank') — {{ $bankLabel }}@endif</span></div>
                <div class="note-sheet__fact"><span>{{ __('agricart.finance.expense_payments.fields.amount') }}</span><span>{{ $money((float) $payment->amount) }}</span></div>
                @if (filled($payment->notes))
                    <div class="note-sheet__fact"><span>{{ __('agricart.finance.expense_payments.fields.notes') }}</span><span>{{ $payment->notes }}</span></div>
                @endif
                @if ($payment->createdBy)
                    <div class="note-sheet__fact"><span>{{ __('agricart.finance.expense_payments.print.handled_by') }}</span><span>{{ $payment->createdBy->name }}</span></div>
                @endif
            </div>

            @if ($receiptUrl)
                <img src="{{ $receiptUrl }}" alt="" class="note-sheet__receipt-img" />
            @endif
        </article>
    </div>
    @if ($autoprint)
        <script>window.addEventListener('load', () => setTimeout(() => window.print(), 250));</script>
    @endif
</body>
</html>
