@php
    $isBoth = $locale === 'both';
    $htmlLang = $locale === 'ur' ? 'ur' : 'en';
    $htmlDir = $locale === 'ur' ? 'rtl' : 'ltr';
@endphp
<!DOCTYPE html>
<html lang="{{ $htmlLang }}" dir="{{ $htmlDir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('price_updates.print.title') }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, "Segoe UI", sans-serif;
            background: #f3f4f6;
            color: #111827;
        }
        .print-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
        }
        .print-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: center;
        }
        .print-actions button {
            border: 1px solid #d1d5db;
            background: #fff;
            padding: 0.45rem 0.8rem;
            font-weight: 700;
            cursor: pointer;
            font-size: 0.8125rem;
        }
        .print-actions button.is-primary {
            background: #83B735;
            border-color: #83B735;
            color: #fff;
        }
        .sheet {
            width: 100%;
            max-width: 210mm;
            background: #fff;
            border: 1px solid #d1d5db;
            padding: 5mm 5mm 4mm;
        }
        .sheet__header {
            border-bottom: 1.5px solid #111827;
            padding-bottom: 0.3rem;
            margin-bottom: 0.35rem;
        }
        .sheet__title {
            margin: 0;
            font-size: 1rem;
            font-weight: 800;
        }
        .sheet__hint {
            margin: 0.18rem 0 0;
            font-size: 0.7rem;
            color: #4b5563;
        }
        .sheet__meta {
            margin: 0.12rem 0 0;
            font-size: 0.65rem;
            color: #6b7280;
        }
        .buy-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 0.78rem;
            border: 2px solid #111827;
        }
        .buy-table th,
        .buy-table td {
            border: 1.5px solid #111827;
            padding: 0.22rem 0.3rem;
            vertical-align: middle;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .buy-table th {
            background: #f3f4f6;
            font-weight: 700;
            text-align: center;
            font-size: 0.72rem;
        }
        .buy-table .col-sr { width: 5%; text-align: center; font-weight: 800; font-size: 0.82rem; }
        .buy-table .col-pic { width: 12%; text-align: center; padding: 0.2rem; }
        .buy-table .col-name { text-align: start; font-weight: 700; font-size: 0.82rem; line-height: 1.25; }
        .buy-table .col-buy { width: 16%; }
        .buy-table--single .col-name { width: 67%; }
        .buy-table--both .col-name-en { width: 30%; text-align: start; font-weight: 700; font-size: 0.8rem; line-height: 1.25; }
        .buy-table--both .col-name-ur {
            width: 37%;
            text-align: center;
            font-weight: 700;
            font-size: 0.92rem;
            line-height: 1.35;
            direction: rtl;
            font-family: "Noto Nastaliq Urdu", "Jameel Noori Nastaleeq", "Al Qalam Taj Nastaleeq", "Segoe UI", Tahoma, Arial, sans-serif;
        }
        .buy-table--both .col-buy { width: 16%; }
        .buy-table .thumb {
            width: 44px;
            height: 44px;
            object-fit: contain;
            object-position: center;
            display: block;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #6b7280;
        }
        .buy-table .thumb-empty {
            display: block;
            width: 44px;
            height: 44px;
            margin: 0 auto;
            border: 1.5px dashed #6b7280;
            background: #fafafa;
        }
        .buy-table .name-ur-only {
            display: block;
            direction: rtl;
            text-align: start;
            font-size: 0.95rem;
            line-height: 1.4;
            font-family: "Noto Nastaliq Urdu", "Jameel Noori Nastaleeq", "Al Qalam Taj Nastaleeq", "Segoe UI", Tahoma, Arial, sans-serif;
        }
        .buy-table .name-en-only {
            display: block;
            font-size: 0.84rem;
            line-height: 1.25;
        }
        .buy-table .code {
            display: block;
            font-size: 0.78rem;
            font-weight: 700;
            color: #111827;
            margin-top: 0.12rem;
            letter-spacing: 0.03em;
            font-family: "Courier New", Courier, Consolas, monospace;
        }
        .buy-table .blank {
            min-height: 1.25rem;
            background: #fafafa;
        }
        .buy-table tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .buy-table thead th.side-en {
            background: #eef2ff;
        }
        .buy-table thead th.side-ur {
            background: #ecfdf5;
        }
        @media print {
            body { background: #fff; }
            .print-actions { display: none !important; }
            .print-shell { padding: 0; }
            .sheet {
                border: none;
                max-width: none;
                width: 100%;
                padding: 0;
            }
            .buy-table {
                border: 2px solid #000;
            }
            .buy-table th,
            .buy-table td {
                border: 1.75px solid #000 !important;
            }
            .buy-table .thumb {
                width: 48px;
                height: 48px;
                border-color: #000;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .buy-table .code {
                font-size: 0.82rem;
                color: #000;
            }
            @page { size: A4 portrait; margin: 7mm; }
        }
    </style>
</head>
<body>
    <div class="print-shell">
        <div class="print-actions">
            <button type="button" class="is-primary" onclick="window.print()">{{ __('price_updates.print.print_button') }}</button>
            <button type="button" onclick="window.close()">{{ __('price_updates.print.close_button') }}</button>
        </div>

        <div class="sheet">
            <header class="sheet__header">
                <h1 class="sheet__title">{{ __('price_updates.print.heading') }}</h1>
                <p class="sheet__hint">
                    {{ $isBoth ? __('price_updates.print.hint_both') : __('price_updates.print.hint') }}
                </p>
                <p class="sheet__meta">
                    {{ __('price_updates.print.count', ['count' => count($rows)]) }}
                    · {{ agricart_datetime($printedAt) }}
                </p>
            </header>

            <table @class(['buy-table', 'buy-table--both' => $isBoth, 'buy-table--single' => ! $isBoth])>
                <thead>
                    <tr>
                        <th class="col-sr {{ $isBoth ? 'side-en' : '' }}">{{ __('price_updates.print.headers.sr') }}</th>
                        <th class="col-pic {{ $isBoth ? 'side-en' : '' }}">{{ __('price_updates.print.headers.pic') }}</th>
                        @if ($isBoth)
                            <th class="col-name-en side-en">{{ __('price_updates.print.headers.name_en') }}</th>
                            <th class="col-name-ur side-ur">{{ __('price_updates.print.headers.name_ur') }}</th>
                            <th class="col-buy side-ur">{{ __('price_updates.print.headers.buy') }}</th>
                        @else
                            <th class="col-name">{{ __('price_updates.print.headers.name') }}</th>
                            <th class="col-buy">{{ __('price_updates.print.headers.buy') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $index => $row)
                        @php
                            $nameEn = trim((string) ($row['name'] ?? ''));
                            $nameUr = trim((string) ($row['name_ur'] ?? ''));
                            $code = trim((string) ($row['code'] ?? ''));
                            $thumb = $row['thumb_url'] ?? null;
                        @endphp
                        <tr>
                            <td class="col-sr">{{ $index + 1 }}</td>
                            <td class="col-pic">
                                @if (filled($thumb))
                                    <img src="{{ $thumb }}" alt="" class="thumb" />
                                @else
                                    <span class="thumb-empty" aria-hidden="true"></span>
                                @endif
                            </td>
                            @if ($isBoth)
                                <td class="col-name-en">
                                    <span class="name-en-only">{{ $nameEn !== '' ? $nameEn : '—' }}</span>
                                    @if ($code !== '')
                                        <span class="code">{{ $code }}</span>
                                    @endif
                                </td>
                                <td class="col-name-ur">
                                    {{ $nameUr !== '' ? $nameUr : ($nameEn !== '' ? $nameEn : '—') }}
                                </td>
                                <td class="col-buy"><div class="blank"></div></td>
                            @else
                                <td class="col-name">
                                    @if ($locale === 'ur')
                                        <span class="name-ur-only">{{ $nameUr !== '' ? $nameUr : $nameEn }}</span>
                                    @else
                                        <span class="name-en-only">{{ $nameEn !== '' ? $nameEn : ($nameUr !== '' ? $nameUr : '—') }}</span>
                                    @endif
                                    @if ($code !== '')
                                        <span class="code">{{ $code }}</span>
                                    @endif
                                </td>
                                <td class="col-buy"><div class="blank"></div></td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isBoth ? 5 : 4 }}" style="text-align:center;padding:0.75rem;">{{ __('price_updates.print.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($autoprint)
        <script>
            window.addEventListener('load', () => {
                setTimeout(() => window.print(), 250);
            });
        </script>
    @endif
</body>
</html>
