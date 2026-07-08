@php
    $isReceipt = $paper === 'receipt';
    $receiptWidth = $receiptProfile?->width_mm ?? 76;
    $receiptMargin = $receiptProfile?->margin_mm ?? 2;
    $widthCss = rtrim(rtrim(number_format($receiptWidth, 2, '.', ''), '0'), '.');
    $marginCss = rtrim(rtrim(number_format($receiptMargin, 2, '.', ''), '0'), '.');
    $layoutQuery = request()->except(['paper', 'autoprint', 'locale']);
    $storeName = (string) ($company->localized('business_name') ?? '');
    $printRoute = 'admin.bank-accounts.print';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale === 'ur' ? 'ur' : 'en' }}" @if($locale === 'ur') dir="rtl" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $account->account_code ?: $account->displayName() }} — {{ __('agricart.finance.banks.print.details_title') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f3f4f6; color: #111827; }
        .print-shell { min-height: 100vh; display: flex; flex-direction: column; align-items: center; gap: 1rem; padding: 1.5rem; }
        .print-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; justify-content: center; }
        .print-actions button, .print-actions a {
            border: 1px solid #d1d5db; background: #fff; padding: 0.5rem 0.875rem; font-weight: 700;
            cursor: pointer; text-decoration: none; color: inherit; font-size: 0.8125rem;
        }
        .print-actions button.is-primary, .print-actions a.is-primary { background: #83B735; border-color: #83B735; color: #fff; }
        .print-actions a.is-active { border-color: #111827; box-shadow: inset 0 0 0 1px #111827; }
        .sheet { width: 100%; background: #fff; border: 1px solid #d1d5db; padding: 1.25rem 1.5rem; }
        .sheet--a4 { max-width: 210mm; }
        .sheet--receipt {
            max-width: {{ $widthCss }}mm;
            padding: 0.5rem 0.45rem;
            font-size: 0.68rem;
            line-height: 1.35;
        }
        .sheet__head { border-bottom: 2px solid #111827; padding-bottom: 0.45rem; margin-bottom: 0.65rem; }
        .sheet--receipt .sheet__head { border-bottom-style: dashed; border-bottom-width: 1px; padding-bottom: 0.35rem; margin-bottom: 0.45rem; text-align: center; }
        .sheet__store { margin: 0 0 0.35rem; font-size: 1.1rem; font-weight: 800; }
        .sheet--receipt .sheet__store { font-size: 0.82rem; }
        .sheet__title { margin: 0; font-size: 1rem; font-weight: 700; }
        .sheet--receipt .sheet__title { font-size: 0.75rem; }
        .field-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.65rem 1rem; }
        .field { border: 1px solid #d1d5db; padding: 0.5rem 0.65rem; background: #fafafa; }
        .field--wide { grid-column: 1 / -1; }
        .field__label { display: block; font-size: 0.72rem; color: #6b7280; margin-bottom: 0.2rem; }
        .field__value { font-size: 0.95rem; font-weight: 700; word-break: break-word; }
        .field__value.is-mono { font-family: ui-monospace, monospace; letter-spacing: 0.02em; }
        .sheet--receipt .field-grid { display: flex; flex-direction: column; gap: 0.35rem; }
        .sheet--receipt .field {
            border: 1px solid #e5e7eb; padding: 0.35rem 0.4rem; background: #fafafa; border-radius: 0.15rem;
            display: flex; justify-content: space-between; align-items: flex-start; gap: 0.35rem;
        }
        .sheet--receipt .field--wide { flex-direction: column; align-items: stretch; }
        .sheet--receipt .field__label { font-size: 0.58rem; margin-bottom: 0.1rem; }
        .sheet--receipt .field__value { font-size: 0.68rem; text-align: end; }
        .sheet--receipt .field--wide .field__value { text-align: start; }
        @media print { body { background: #fff; } .print-actions { display: none; } .print-shell { padding: 0; } .sheet { border: none; max-width: none; width: 100%; } .sheet--receipt { padding: 0; } }
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
            <a href="{{ route($printRoute, array_merge(['account' => $account], $layoutQuery, ['paper' => 'a4', 'locale' => $locale, 'autoprint' => 0])) }}" @class(['is-active' => ! $isReceipt])>{{ __('pos.print.layout_a4') }}</a>
            <a href="{{ route($printRoute, array_merge(['account' => $account], $layoutQuery, ['paper' => 'receipt', 'locale' => $locale, 'autoprint' => 0])) }}" @class(['is-active' => $isReceipt])>{{ __('pos.print.layout_receipt') }}</a>
            <a href="{{ route($printRoute, array_merge(['account' => $account], $layoutQuery, ['paper' => $paper, 'locale' => 'en', 'autoprint' => 0])) }}" @class(['is-active' => $locale === 'en'])>{{ __('pos.locales.en') }}</a>
            <a href="{{ route($printRoute, array_merge(['account' => $account], $layoutQuery, ['paper' => $paper, 'locale' => 'ur', 'autoprint' => 0])) }}" @class(['is-active' => $locale === 'ur'])>{{ __('pos.locales.ur') }}</a>
            <button type="button" class="is-primary" onclick="window.print()">{{ __('agricart.finance.banks.print.print_button') }}</button>
            <button type="button" onclick="window.close()">{{ __('agricart.finance.banks.print.close_button') }}</button>
        </div>

        <div @class(['sheet', 'sheet--a4' => ! $isReceipt, 'sheet--receipt' => $isReceipt])>
            <header class="sheet__head">
                @if (filled($storeName))
                    <p class="sheet__store">{{ $storeName }}</p>
                @endif
                <h1 class="sheet__title">{{ __('agricart.finance.banks.print.account_details_heading') }}</h1>
            </header>

            <div class="field-grid">
                @if (filled($account->account_code))
                    <div class="field field--wide">
                        <span class="field__label">{{ __('agricart.finance.banks.list.col_code') }}</span>
                        <span class="field__value is-mono">{{ $account->account_code }}</span>
                    </div>
                @endif
                <div class="field field--wide">
                    <span class="field__label">{{ __('agricart.finance.banks.fields.bank') }}</span>
                    <span class="field__value" @if($locale === 'ur') dir="rtl" @endif>{{ $bankName }}</span>
                </div>
                <div class="field field--wide">
                    <span class="field__label">{{ __('agricart.finance.banks.print.account_title_heading') }}</span>
                    <span class="field__value" @if($locale === 'ur') dir="rtl" @endif>{{ $accountTitle }}</span>
                </div>
                <div class="field">
                    <span class="field__label">{{ __('agricart.finance.banks.fields.identifier_type') }}</span>
                    <span class="field__value">{{ $identifierTypeLabel }}</span>
                </div>
                <div class="field field--wide">
                    <span class="field__label">{{ __('agricart.finance.banks.fields.identifier_value') }}</span>
                    <span class="field__value is-mono">{{ $account->displayAccountIdentifier() ?: '—' }}</span>
                </div>
            </div>
        </div>
    </div>

    @if ($autoprint)
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
