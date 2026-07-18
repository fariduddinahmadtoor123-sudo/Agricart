<!DOCTYPE html>
<html lang="{{ $locale === 'ur' ? 'ur' : 'en' }}" @if($locale === 'ur') dir="rtl" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $sticker['title'] ?? $product->name_en }} — {{ __('agricart.labels.print.title') }}</title>
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
            justify-content: center;
            gap: 1rem;
            padding: 1.5rem;
        }

        .print-actions {
            display: flex;
            gap: 0.5rem;
        }

        .print-actions button,
        .print-actions a {
            border: 1px solid #d1d5db;
            background: #fff;
            color: #111827;
            padding: 0.5rem 0.875rem;
            font-size: 0.875rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .print-actions button.is-primary {
            background: #83B735;
            border-color: #83B735;
            color: #fff;
        }

        .print-label {
            width: {{ rtrim(rtrim(number_format($profile->width_mm, 2, '.', ''), '0'), '.') }}mm;
            @if ($profile->height_mm !== null)
                height: {{ rtrim(rtrim(number_format($profile->height_mm, 2, '.', ''), '0'), '.') }}mm;
            @endif
            padding: {{ rtrim(rtrim(number_format($profile->margin_mm, 2, '.', ''), '0'), '.') }}mm;
            background: #fff;
            border: 1px solid #d1d5db;
            overflow: hidden;
        }

        .print-label__title {
            margin: 0 0 0.35rem;
            font-size: 9pt;
            font-weight: 700;
            line-height: 1.2;
        }

        .print-label__lines {
            margin: 0 0 0.35rem;
        }

        .print-label__line {
            display: flex;
            justify-content: space-between;
            gap: 0.25rem;
            font-size: 6.5pt;
            line-height: 1.25;
            padding: 0.05rem 0;
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

        .print-label[dir='rtl'] .print-label__line dd {
            text-align: start;
        }

        .print-label__code-wrap {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            margin: 0.2rem 0;
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
            gap: 0.8mm;
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
            margin: 0.15rem 0 0;
            font-family: 'Courier New', Courier, monospace;
            font-size: 6.5pt;
            text-align: center;
            letter-spacing: 0.04em;
        }

        @page {
            size: {{ $profile->pageSizeCss() }};
            margin: 0;
        }

        @media print {
            body {
                background: #fff;
            }

            .print-shell {
                width: {{ rtrim(rtrim(number_format($profile->width_mm, 2, '.', ''), '0'), '.') }}mm;
                @if ($profile->height_mm !== null)
                    height: {{ rtrim(rtrim(number_format($profile->height_mm, 2, '.', ''), '0'), '.') }}mm;
                @endif
                min-height: 0;
                padding: 0;
            }

            .print-actions {
                display: none !important;
            }

            .print-label {
                border: none;
                width: 100%;
                @if ($profile->height_mm !== null)
                    height: 100%;
                @endif
                padding: {{ rtrim(rtrim(number_format($profile->margin_mm, 2, '.', ''), '0'), '.') }}mm;
                margin-inline: auto;
            }
        }
    </style>
</head>
<body>
    <div class="print-shell">
        <article class="print-label" @if($locale === 'ur') dir="rtl" @endif>
            <h1 class="print-label__title">{{ $sticker['title'] ?? '—' }}</h1>

            @if ($type === 'qr')
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
                        <p class="print-label__foot">{{ $sticker['qr_payload'] ?? $sticker['label_code'] ?? '' }}</p>
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
                <p class="print-label__foot">{{ $sticker['barcode_value'] ?? $sticker['label_code'] ?? '' }}</p>
            @endif
        </article>

        <div class="print-actions">
            <button type="button" class="is-primary" onclick="window.print()">{{ __('agricart.labels.print.print_button') }}</button>
            <button type="button" onclick="window.close()">{{ __('agricart.labels.print.close_button') }}</button>
        </div>
    </div>

    @if ($autoprint)
        <script>
            window.addEventListener('load', function () {
                window.setTimeout(function () {
                    window.print();
                }, 250);
            });
        </script>
    @endif
</body>
</html>
