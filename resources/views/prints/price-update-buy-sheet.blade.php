@php
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
            font-family: Arial, Helvetica, sans-serif;
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
            padding: 6mm 6mm 5mm;
        }
        .sheet__header {
            border-bottom: 1.5px solid #111827;
            padding-bottom: 0.35rem;
            margin-bottom: 0.4rem;
        }
        .sheet__title {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 800;
        }
        .sheet__hint {
            margin: 0.2rem 0 0;
            font-size: 0.68rem;
            color: #4b5563;
        }
        .sheet__meta {
            margin: 0.15rem 0 0;
            font-size: 0.62rem;
            color: #6b7280;
        }
        .buy-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 0.62rem;
        }
        .buy-table th,
        .buy-table td {
            border: 1px solid #9ca3af;
            padding: 0.12rem 0.18rem;
            vertical-align: middle;
        }
        .buy-table th {
            background: #f3f4f6;
            font-weight: 700;
            text-align: center;
        }
        .buy-table .col-sr { width: 6%; text-align: center; font-weight: 700; }
        .buy-table .col-pic { width: 9%; text-align: center; }
        .buy-table .col-name { width: 55%; text-align: start; font-weight: 600; }
        .buy-table .col-buy { width: 30%; }
        .buy-table .thumb {
            width: 18px;
            height: 18px;
            object-fit: cover;
            display: inline-block;
            vertical-align: middle;
            border: 1px solid #e5e7eb;
        }
        .buy-table .thumb-empty {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 1px dashed #d1d5db;
            vertical-align: middle;
        }
        .buy-table .name-en { display: block; line-height: 1.2; }
        .buy-table .name-ur {
            display: block;
            line-height: 1.25;
            direction: rtl;
            font-size: 0.6rem;
            color: #374151;
            margin-top: 0.05rem;
        }
        .buy-table .code {
            display: block;
            font-size: 0.52rem;
            font-weight: 500;
            color: #6b7280;
            margin-top: 0.05rem;
        }
        .buy-table .blank {
            min-height: 1.05rem;
            background: #fafafa;
        }
        .buy-table tr {
            page-break-inside: avoid;
            break-inside: avoid;
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
                <p class="sheet__hint">{{ __('price_updates.print.hint') }}</p>
                <p class="sheet__meta">
                    {{ __('price_updates.print.count', ['count' => count($rows)]) }}
                    · {{ $printedAt->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                </p>
            </header>

            <table class="buy-table">
                <thead>
                    <tr>
                        <th class="col-sr">{{ __('price_updates.print.headers.sr') }}</th>
                        <th class="col-pic">{{ __('price_updates.print.headers.pic') }}</th>
                        <th class="col-name">{{ __('price_updates.print.headers.name') }}</th>
                        <th class="col-buy">{{ __('price_updates.print.headers.buy') }}</th>
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
                            <td class="col-name">
                                @if ($locale === 'ur')
                                    <span class="name-ur">{{ $nameUr !== '' ? $nameUr : $nameEn }}</span>
                                @elseif ($locale === 'both')
                                    @if ($nameEn !== '')
                                        <span class="name-en">{{ $nameEn }}</span>
                                    @endif
                                    @if ($nameUr !== '')
                                        <span class="name-ur">{{ $nameUr }}</span>
                                    @endif
                                    @if ($nameEn === '' && $nameUr === '')
                                        <span class="name-en">—</span>
                                    @endif
                                @else
                                    <span class="name-en">{{ $nameEn !== '' ? $nameEn : ($nameUr !== '' ? $nameUr : '—') }}</span>
                                @endif
                                @if ($code !== '')
                                    <span class="code">{{ $code }}</span>
                                @endif
                            </td>
                            <td class="col-buy"><div class="blank"></div></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align:center;padding:0.75rem;">{{ __('price_updates.print.empty') }}</td>
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
