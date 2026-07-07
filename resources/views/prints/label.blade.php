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
                min-height: {{ rtrim(rtrim(number_format($profile->height_mm, 2, '.', ''), '0'), '.') }}mm;
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
            margin: 0.2rem 0;
            overflow: hidden;
        }

        .print-label__code-wrap svg {
            display: block;
            max-width: 100%;
            height: auto;
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
            margin: {{ rtrim(rtrim(number_format($profile->margin_mm, 2, '.', ''), '0'), '.') }}mm;
        }

        @media print {
            body {
                background: #fff;
            }

            .print-shell {
                min-height: auto;
                padding: 0;
            }

            .print-actions {
                display: none !important;
            }

            .print-label {
                border: none;
                width: auto;
                min-height: auto;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="print-shell">
        <article class="print-label" @if($locale === 'ur') dir="rtl" @endif>
            <h1 class="print-label__title">{{ $sticker['title'] ?? '—' }}</h1>

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

            <div class="print-label__code-wrap">
                @if ($type === 'qr')
                    {!! $sticker['qr_svg'] ?? '' !!}
                @else
                    {!! $sticker['barcode_svg'] ?? '' !!}
                @endif
            </div>

            <p class="print-label__foot">
                {{ $type === 'qr' ? ($sticker['qr_payload'] ?? $sticker['label_code'] ?? '') : ($sticker['barcode_value'] ?? $sticker['label_code'] ?? '') }}
            </p>
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
