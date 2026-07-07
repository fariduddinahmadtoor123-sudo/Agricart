<!DOCTYPE html>
<html lang="{{ $locale === 'ur' ? 'ur' : 'en' }}" @if($locale === 'ur') dir="rtl" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $sheet->planning_number }} — {{ __('purchase_planning.print.title') }}</title>
    <style>
        :root {
            color-scheme: light;
        }

        * {
            box-sizing: border-box;
        }

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
            gap: 0.5rem;
        }

        .print-actions button {
            border: 1px solid #d1d5db;
            background: #fff;
            color: #111827;
            padding: 0.5rem 0.875rem;
            font-size: 0.875rem;
            font-weight: 700;
            cursor: pointer;
        }

        .print-actions button.is-primary {
            background: #83B735;
            border-color: #83B735;
            color: #fff;
        }

        .planning-sheet {
            width: 100%;
            background: #fff;
            border: 1px solid #d1d5db;
            padding: 1rem;
        }

        .planning-sheet--a4 {
            max-width: 210mm;
        }

        .planning-sheet--a5 {
            max-width: 148mm;
        }

        .planning-sheet--a3 {
            max-width: 297mm;
        }

        .planning-sheet__header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 0.75rem;
            border-bottom: 2px solid #111827;
            padding-bottom: 0.5rem;
        }

        .planning-sheet__title {
            margin: 0;
            font-size: 1.125rem;
            font-weight: 700;
        }

        .planning-sheet__meta {
            margin: 0;
            font-size: 0.8125rem;
            text-align: end;
        }

        .planning-sheet__table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.75rem;
        }

        .planning-sheet__table th,
        .planning-sheet__table td {
            border: 1px solid #9ca3af;
            padding: 0.35rem 0.25rem;
            vertical-align: middle;
        }

        .planning-sheet__table th {
            background: #f3f4f6;
            font-weight: 700;
            text-align: center;
        }

        .planning-sheet__thumb {
            width: 2.25rem;
            height: 2.25rem;
            object-fit: cover;
            display: block;
            margin: 0 auto;
        }

        .planning-sheet__name {
            min-width: 8rem;
            font-weight: 600;
        }

        .planning-sheet__manual {
            min-width: 3.5rem;
            height: 1.75rem;
        }

        .planning-sheet__sr {
            width: 2rem;
            text-align: center;
        }

        @media print {
            body {
                background: #fff;
            }

            .print-actions {
                display: none;
            }

            .print-shell {
                padding: 0;
            }

            .planning-sheet {
                border: none;
                max-width: none;
                width: 100%;
            }

            @page {
                margin: 10mm;
            }
        }
    </style>
</head>
<body>
    <div class="print-shell">
        <div class="print-actions">
            <button type="button" class="is-primary" onclick="window.print()">{{ __('purchase_planning.print.print_button') }}</button>
            <button type="button" onclick="window.close()">{{ __('purchase_planning.print.close_button') }}</button>
        </div>

        <div @class([
            'planning-sheet',
            'planning-sheet--' . $sheet->paper_size,
        ])>
            <header class="planning-sheet__header">
                <h1 class="planning-sheet__title">{{ __('purchase_planning.print.heading') }}</h1>
                <p class="planning-sheet__meta">
                    <strong>{{ $sheet->planning_number }}</strong><br>
                    {{ $sheet->created_at?->format('d M Y') }}
                </p>
            </header>

            <table class="planning-sheet__table">
                <thead>
                    <tr>
                        <th>{{ __('purchase_planning.columns.sr') }}</th>
                        <th>{{ __('purchase_planning.columns.thumb') }}</th>
                        <th>{{ __('purchase_planning.columns.product_name') }}</th>
                        <th>{{ __('purchase_planning.columns.stock') }}</th>
                        <th>{{ __('purchase_planning.columns.required_qty') }}</th>
                        <th>{{ __('purchase_planning.columns.low_stock') }}</th>
                        <th>{{ __('purchase_planning.columns.purchase_qty') }}</th>
                        <th>{{ __('purchase_planning.columns.landing_price') }}</th>
                        <th>{{ __('purchase_planning.columns.sale_price') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sheet->items as $index => $item)
                        <tr>
                            <td class="planning-sheet__sr">{{ $index + 1 }}</td>
                            <td>
                                @if ($item->imageUrl())
                                    <img src="{{ $item->imageUrl() }}" alt="" class="planning-sheet__thumb" />
                                @endif
                            </td>
                            <td class="planning-sheet__name">{{ $item->displayName($locale) }}</td>
                            <td class="planning-sheet__manual"></td>
                            <td class="planning-sheet__manual"></td>
                            <td class="planning-sheet__manual"></td>
                            <td class="planning-sheet__manual"></td>
                            <td class="planning-sheet__manual"></td>
                            <td class="planning-sheet__manual"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if ($autoprint)
        <script>
            window.addEventListener('load', () => window.print());
        </script>
    @endif
</body>
</html>
