<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('agricart.labels.print.batch_title', ['count' => $totalCopies]) }}</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f3f4f6;
            color: #111827;
        }
        .print-actions {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            padding: .75rem;
            border-bottom: 1px solid #d1d5db;
            background: #fff;
        }
        .print-actions button {
            border: 1px solid #d1d5db;
            border-radius: 0;
            background: #fff;
            color: #111827;
            padding: .5rem .875rem;
            font: 700 .875rem Arial, sans-serif;
            cursor: pointer;
        }
        .print-actions .is-primary {
            border-color: #83B735;
            background: #83B735;
            color: #fff;
        }
        .print-sheet {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax({{ max(180, (int) round($profile->width_mm * 3.78)) }}px, 1fr));
            gap: 1rem;
            padding: 1.5rem;
        }
        .print-label {
            width: {{ rtrim(rtrim(number_format($profile->width_mm, 2, '.', ''), '0'), '.') }}mm;
            @if ($profile->height_mm !== null)
                height: {{ rtrim(rtrim(number_format($profile->height_mm, 2, '.', ''), '0'), '.') }}mm;
            @endif
            padding: {{ rtrim(rtrim(number_format($profile->margin_mm, 2, '.', ''), '0'), '.') }}mm;
            border: 1px solid #d1d5db;
            background: #fff;
            overflow: hidden;
            margin-inline: auto;
            break-after: page;
            page-break-after: always;
        }
        .print-label__title {
            margin: 0 0 .35rem;
            font-size: 9pt;
            font-weight: 700;
            line-height: 1.2;
            text-align: center;
        }
        .print-label__store {
            margin: 0 0 .25rem;
            padding-bottom: .2rem;
            border-bottom: .35mm solid #83B735;
            font-size: 7pt;
            font-weight: 700;
            line-height: 1.15;
            text-align: center;
        }
        .print-label__lines { margin: 0 0 .35rem; }
        .print-label__line {
            display: flex;
            justify-content: space-between;
            gap: .25rem;
            padding: .05rem 0;
            font-size: 6.5pt;
            line-height: 1.25;
        }
        .print-label__line dt {
            margin: 0;
            font-weight: 700;
            color: #4b5563;
        }
        .print-label__line dd {
            margin: 0;
            text-align: end;
        }
        .print-label[dir='rtl'] .print-label__line dd { text-align: start; }
        .print-label__code-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            margin: .2rem 0;
            overflow: hidden;
        }
        .print-label__code-wrap svg {
            display: block;
            max-width: 100%;
            height: auto;
            margin-inline: auto;
        }
        .print-label__qr-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(14mm, 19mm);
            align-items: center;
            gap: .8mm;
            min-height: 0;
        }
        .print-label__qr-layout .print-label__lines {
            min-width: 0;
            margin-bottom: 0;
        }
        .print-label__qr-code {
            min-width: 0;
            text-align: center;
        }
        .print-label__qr-code .print-label__code-wrap {
            margin: 0;
        }
        .print-label__qr-code svg {
            width: 100%;
            max-width: 18mm;
            max-height: 18mm;
        }
        .print-label__foot {
            margin: .15rem 0 0;
            font-family: 'Courier New', Courier, monospace;
            font-size: 6.5pt;
            text-align: center;
            letter-spacing: .04em;
        }
        @page {
            size: {{ $profile->pageSizeCss() }};
            margin: 0;
        }
        @media print {
            body { background: #fff; }
            .print-actions { display: none !important; }
            .print-sheet { display: block; padding: 0; }
            .print-label {
                border: 0;
                margin-inline: auto;
            }
            .print-label:last-child {
                break-after: auto;
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>
    <div class="print-actions">
        <strong>{{ trans_choice('agricart.labels.print.batch_count', $totalCopies, ['count' => $totalCopies]) }}</strong>
        <button type="button" class="is-primary" onclick="window.print()">{{ __('agricart.labels.print.print_button') }}</button>
        <button type="button" onclick="window.close()">{{ __('agricart.labels.print.close_button') }}</button>
    </div>

    <main class="print-sheet">
        @foreach ($printJobs as $job)
            @for ($copy = 0; $copy < $job['copies']; $copy++)
                @php($sticker = $job['sticker'])
                <article class="print-label" @if ($job['locale'] === 'ur') dir="rtl" @endif>
                    @if (filled($sticker['store_name'] ?? null))
                        <p class="print-label__store">{{ $sticker['store_name'] }}</p>
                    @endif

                    @if (filled($sticker['title'] ?? null))
                        <h1 class="print-label__title">{{ $sticker['title'] }}</h1>
                    @endif

                    @if ($job['type'] === 'qr')
                        <div class="print-label__qr-layout">
                            <dl class="print-label__lines">
                                @foreach ($sticker['lines'] ?? [] as $line)
                                    <div class="print-label__line">
                                        <dt>{{ $line['label'] ?? '' }}</dt>
                                        <dd>{{ $line['value'] ?? '' }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                            <div class="print-label__qr-code">
                                <div class="print-label__code-wrap">{!! $sticker['qr_svg'] ?? '' !!}</div>
                                @if ($sticker['show_printed_code'] ?? true)
                                    <p class="print-label__foot">{{ $sticker['qr_payload'] ?? $sticker['label_code'] ?? '' }}</p>
                                @endif
                            </div>
                        </div>
                    @else
                        @if (! empty($sticker['lines']))
                            <dl class="print-label__lines">
                                @foreach ($sticker['lines'] as $line)
                                    <div class="print-label__line">
                                        <dt>{{ $line['label'] ?? '' }}</dt>
                                        <dd>{{ $line['value'] ?? '' }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif
                        <div class="print-label__code-wrap">{!! $sticker['barcode_svg'] ?? '' !!}</div>
                        @if ($sticker['show_printed_code'] ?? true)
                            <p class="print-label__foot">{{ $sticker['barcode_value'] ?? $sticker['label_code'] ?? '' }}</p>
                        @endif
                    @endif
                </article>
            @endfor
        @endforeach
    </main>

    @if ($autoprint)
        <script>
            window.addEventListener('load', function () {
                window.setTimeout(function () { window.print(); }, 250);
            });
        </script>
    @endif
</body>
</html>
