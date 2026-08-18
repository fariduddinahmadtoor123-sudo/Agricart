@php
    $isUrdu = $locale === 'ur';
    $isBoth = $locale === 'both';
    $htmlLang = $isUrdu ? 'ur' : 'en';
    $htmlDir = $isUrdu ? 'rtl' : 'ltr';
    $pageCount = max(1, count($pages));
    $switchQuery = request()->except(['locale', 'autoprint']);
@endphp
<!DOCTYPE html>
<html lang="{{ $htmlLang }}" dir="{{ $htmlDir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('rate_lists.print.title') }}</title>
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
        .print-actions button,
        .print-actions a {
            border: 1px solid #d1d5db;
            background: #fff;
            padding: 0.45rem 0.8rem;
            font-weight: 700;
            cursor: pointer;
            font-size: 0.8125rem;
            text-decoration: none;
            color: inherit;
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
        .sheet {
            width: 100%;
            max-width: 210mm;
            min-height: 297mm;
            background: #fff;
            border: 1px solid #d1d5db;
            padding: 7mm 8mm 6mm;
            page-break-after: always;
        }
        .sheet:last-child { page-break-after: auto; }
        .sheet__header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 0.75rem;
            border-bottom: 2px solid #111827;
            padding-bottom: 0.35rem;
            margin-bottom: 0.4rem;
        }
        .sheet__store {
            margin: 0;
            font-size: 13pt;
            font-weight: 800;
            line-height: 1.2;
        }
        .sheet__title {
            margin: 0.12rem 0 0;
            font-size: 10pt;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .sheet__meta {
            margin: 0;
            font-size: 8pt;
            color: #4b5563;
            text-align: end;
            white-space: nowrap;
        }
        .rate-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 9pt;
            border: 1.5px solid #111827;
        }
        .rate-table th,
        .rate-table td {
            border: 1px solid #6b7280;
            padding: 2.2mm 2.4mm;
            vertical-align: middle;
            height: 7.4mm;
            overflow: hidden;
        }
        .rate-table th {
            background: #f3f4f6;
            font-weight: 800;
            font-size: 8.5pt;
        }
        .rate-table .col-sr {
            width: 12mm;
            text-align: center;
            font-weight: 800;
        }
        .rate-table .col-rate {
            width: 28mm;
            text-align: center;
            font-weight: 800;
            font-size: 10pt;
            white-space: nowrap;
        }
        .rate-table .col-name {
            text-align: start;
            font-weight: 700;
            line-height: 1.2;
        }
        .rate-table .col-name-ur {
            text-align: start;
            direction: rtl;
            font-weight: 700;
            line-height: 1.25;
            font-family: "Noto Nastaliq Urdu", "Jameel Noori Nastaleeq", "Al Qalam Taj Nastaleeq", "Segoe UI", Tahoma, Arial, sans-serif;
        }
        .rate-table--ur .col-name {
            direction: rtl;
            font-family: "Noto Nastaliq Urdu", "Jameel Noori Nastaleeq", "Al Qalam Taj Nastaleeq", "Segoe UI", Tahoma, Arial, sans-serif;
        }
        .sheet__empty {
            margin: 2rem 0;
            text-align: center;
            color: #6b7280;
            font-size: 11pt;
        }
        .sheet__foot {
            margin-top: 0.35rem;
            font-size: 7.5pt;
            color: #6b7280;
            text-align: center;
        }
        @page { size: A4 portrait; margin: 8mm; }
        @media print {
            body { background: #fff; }
            .print-actions { display: none !important; }
            .print-shell { padding: 0; }
            .sheet {
                border: none;
                max-width: none;
                min-height: 0;
                width: 100%;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="print-shell">
        <div class="print-actions">
            <a href="{{ route('admin.rate-list.print', array_merge($switchQuery, ['locale' => 'en', 'autoprint' => 0])) }}" @class(['is-active' => $locale === 'en'])>{{ __('rate_lists.locales.en') }}</a>
            <a href="{{ route('admin.rate-list.print', array_merge($switchQuery, ['locale' => 'ur', 'autoprint' => 0])) }}" @class(['is-active' => $locale === 'ur'])>{{ __('rate_lists.locales.ur') }}</a>
            <a href="{{ route('admin.rate-list.print', array_merge($switchQuery, ['locale' => 'both', 'autoprint' => 0])) }}" @class(['is-active' => $isBoth])>{{ __('rate_lists.locales.both') }}</a>
            <button type="button" class="is-primary" onclick="window.print()">{{ __('rate_lists.print.print_button') }}</button>
            <button type="button" onclick="window.close()">{{ __('rate_lists.print.close_button') }}</button>
        </div>

        @forelse ($pages as $pageIndex => $pageRows)
            <div class="sheet">
                <header class="sheet__header">
                    <div>
                        <p class="sheet__store">{{ $meta['store_name'] !== '' ? $meta['store_name'] : __('rate_lists.print.shop_fallback') }}</p>
                        <h1 class="sheet__title">{{ __('rate_lists.print.heading') }}</h1>
                    </div>
                    <p class="sheet__meta">
                        {{ $meta['printed_at'] }}
                        @if ($pageCount > 1)
                            · {{ __('rate_lists.print.page', ['current' => $pageIndex + 1, 'total' => $pageCount]) }}
                        @endif
                    </p>
                </header>

                <table @class(['rate-table', 'rate-table--ur' => $isUrdu, 'rate-table--both' => $isBoth])>
                    <thead>
                        <tr>
                            <th class="col-sr">{{ __('rate_lists.columns.sr') }}</th>
                            @if ($isBoth)
                                <th class="col-name">{{ __('rate_lists.columns.name_en') }}</th>
                                <th class="col-name-ur">{{ __('rate_lists.columns.name_ur') }}</th>
                            @else
                                <th class="col-name">{{ __('rate_lists.columns.name') }}</th>
                            @endif
                            <th class="col-rate">{{ __('rate_lists.columns.rate') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pageRows as $row)
                            <tr>
                                <td class="col-sr">{{ $row['sr'] }}</td>
                                @if ($isBoth)
                                    <td class="col-name">{{ $row['name_en'] }}</td>
                                    <td class="col-name-ur">{{ $row['name_ur'] }}</td>
                                @else
                                    <td class="col-name">{{ $row['name'] }}</td>
                                @endif
                                <td class="col-rate">{{ $row['rate'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <p class="sheet__foot">{{ __('rate_lists.print.footer') }}</p>
            </div>
        @empty
            <div class="sheet">
                <header class="sheet__header">
                    <div>
                        <p class="sheet__store">{{ $meta['store_name'] !== '' ? $meta['store_name'] : __('rate_lists.print.shop_fallback') }}</p>
                        <h1 class="sheet__title">{{ __('rate_lists.print.heading') }}</h1>
                    </div>
                    <p class="sheet__meta">{{ $meta['printed_at'] }}</p>
                </header>
                <p class="sheet__empty">{{ __('rate_lists.list.empty') }}</p>
            </div>
        @endforelse
    </div>
    @if ($autoprint)<script>window.addEventListener('load', () => window.print());</script>@endif
</body>
</html>
