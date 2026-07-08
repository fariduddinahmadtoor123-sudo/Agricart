<!DOCTYPE html>
<html lang="{{ app()->getLocale() === 'ur' ? 'ur' : 'en' }}" @if(app()->getLocale() === 'ur') dir="rtl" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $account->displayName() }} — {{ __('agricart.finance.banks.print.history_title') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f3f4f6; color: #111827; }
        .print-shell { min-height: 100vh; display: flex; flex-direction: column; align-items: center; gap: 1rem; padding: 1.5rem; }
        .print-actions { display: flex; gap: 0.5rem; }
        .print-actions button { border: 1px solid #d1d5db; background: #fff; padding: 0.5rem 0.875rem; font-weight: 700; cursor: pointer; }
        .print-actions button.is-primary { background: #83B735; border-color: #83B735; color: #fff; }
        .sheet { width: 100%; max-width: 210mm; background: #fff; border: 1px solid #d1d5db; padding: 1rem 1.25rem; }
        .sheet__store { margin: 0 0 0.25rem; font-size: 1.05rem; font-weight: 800; }
        .sheet__title { margin: 0 0 0.35rem; font-size: 0.95rem; font-weight: 700; }
        .sheet__meta { margin: 0 0 0.75rem; font-size: 0.78rem; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; font-size: 0.75rem; }
        th, td { border: 1px solid #9ca3af; padding: 0.35rem 0.4rem; vertical-align: top; }
        th { background: #f3f4f6; font-weight: 700; }
        .amt-debit { color: #b91c1c; font-weight: 700; }
        .amt-credit { color: #15803d; font-weight: 700; }
        @media print { body { background: #fff; } .print-actions { display: none; } .print-shell { padding: 0; } .sheet { border: none; max-width: none; } }
        @media print { @page { size: A4 portrait; margin: 8mm; } }
    </style>
</head>
<body>
    <div class="print-shell">
        <div class="print-actions">
            <button type="button" class="is-primary" onclick="window.print()">{{ __('agricart.finance.banks.print.print_button') }}</button>
            <button type="button" onclick="window.close()">{{ __('agricart.finance.banks.print.close_button') }}</button>
        </div>

        <div class="sheet">
            @php
                $storeName = (string) ($company->localized('business_name') ?? '');
            @endphp
            @if (filled($storeName))
                <p class="sheet__store">{{ $storeName }}</p>
            @endif
            @php
                $bankName = $account->displayBankName();
                $accountLabel = $account->displayName();
            @endphp
            <h1 class="sheet__title">
                @if (filled($bankName))
                    {{ __('agricart.finance.banks.print.history_heading', ['bank' => $bankName, 'account' => $accountLabel]) }}
                @else
                    {{ __('agricart.finance.banks.print.history_heading_account_only', ['account' => $accountLabel]) }}
                @endif
            </h1>
            <p class="sheet__meta">
                @if (filled($account->account_code))
                    <strong>{{ $account->account_code }}</strong>
                    @if (filled($account->displayAccountIdentifier()))
                        · {{ $account->displayAccountIdentifier() }}
                    @endif
                    <br>
                @elseif (filled($account->displayAccountIdentifier()))
                    {{ $account->displayAccountIdentifier() }}<br>
                @endif
                @if ($from || $to)
                    {{ __('agricart.finance.banks.print.period') }}:
                    {{ $from?->format('d M Y') ?? '…' }}
                    —
                    {{ $to?->format('d M Y') ?? '…' }}
                @else
                    {{ __('agricart.finance.banks.print.period_all') }}
                @endif
            </p>

            <table>
                <thead>
                    <tr>
                        <th>{{ __('agricart.finance.banks.ledger.col_date') }}</th>
                        <th>{{ __('agricart.finance.banks.ledger.col_description') }}</th>
                        <th>{{ __('agricart.finance.banks.ledger.col_debit') }}</th>
                        <th>{{ __('agricart.finance.banks.ledger.col_credit') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $tx)
                        <tr>
                            <td>{{ $tx->transaction_date?->format('d M Y') }}</td>
                            <td>{{ $tx->description ?: __('agricart.finance.banks.ledger.types.' . $tx->type) }}</td>
                            <td>
                                @if ($tx->direction === 'debit')
                                    <span class="amt-debit">{{ $money((float) $tx->amount) }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if ($tx->direction === 'credit')
                                    <span class="amt-credit">{{ $money((float) $tx->amount) }}</span>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">{{ __('agricart.finance.banks.ledger.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($autoprint)
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
