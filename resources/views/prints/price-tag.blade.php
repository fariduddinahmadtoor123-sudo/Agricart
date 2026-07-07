<!DOCTYPE html>
<html lang="{{ $locale === 'ur' ? 'ur' : 'en' }}" @if($locale === 'ur') dir="rtl" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('price_tags.print.title') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f3f4f6; color: #111827; }
        .print-shell { min-height: 100vh; display: flex; flex-direction: column; align-items: center; gap: 1rem; padding: 1.5rem; }
        .print-actions { display: flex; gap: 0.5rem; }
        .print-actions button { border: 1px solid #d1d5db; background: #fff; padding: 0.5rem 0.875rem; font-weight: 700; cursor: pointer; }
        .print-actions button.is-primary { background: #83B735; border-color: #83B735; color: #fff; }
        .print-label {
            width: {{ rtrim(rtrim(number_format($profile->width_mm, 2, '.', ''), '0'), '.') }}mm;
            @if ($profile->height_mm !== null)
                min-height: {{ rtrim(rtrim(number_format($profile->height_mm, 2, '.', ''), '0'), '.') }}mm;
            @endif
            padding: {{ rtrim(rtrim(number_format($profile->margin_mm, 2, '.', ''), '0'), '.') }}mm;
            background: #fff;
            border: 1px solid #d1d5db;
            overflow: hidden;
            page-break-after: always;
        }
        .print-label:last-child { page-break-after: auto; }
        .print-label__store { margin: 0 0 0.25rem; font-size: 6.5pt; font-weight: 700; text-align: center; color: #374151; }
        .print-label__name { margin: 0 0 0.2rem; font-size: 8pt; font-weight: 800; line-height: 1.2; text-align: center; }
        .print-label__name-ur { margin: 0 0 0.25rem; font-size: 7pt; font-weight: 700; line-height: 1.25; text-align: center; }
        .print-label__prices { display: grid; gap: 0.1rem; margin: 0.15rem 0 0.25rem; }
        .print-label__price { display: flex; justify-content: space-between; gap: 0.25rem; font-size: 6.5pt; }
        .print-label__price strong { font-size: 7.5pt; }
        .print-label__price.is-sale strong { font-size: 8.5pt; }
        .print-label__code-wrap { display: flex; justify-content: center; align-items: center; margin: 0.15rem 0; overflow: hidden; }
        .print-label__code-wrap svg { display: block; max-width: 100%; height: auto; }
        .print-label__foot { margin: 0.1rem 0 0; font-family: 'Courier New', Courier, monospace; font-size: 6pt; text-align: center; letter-spacing: 0.04em; }
        @page { size: {{ $profile->pageSizeCss() }}; margin: {{ rtrim(rtrim(number_format($profile->margin_mm, 2, '.', ''), '0'), '.') }}mm; }
        @media print {
            body { background: #fff; }
            .print-actions { display: none !important; }
            .print-shell { padding: 0; }
            .print-label { border: none; width: auto; min-height: auto; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="print-shell">
        @foreach ($jobs as $job)
            @for ($copy = 0; $copy < (int) ($job['qty'] ?? 1); $copy++)
                @php $tag = $job['tag'] ?? []; @endphp
                <article class="print-label" @if(($options['locale'] ?? 'en') === 'ur') dir="rtl" @endif>
                    @if ($options['show_store_name'] && filled($tag['store_name'] ?? null))
                        <p class="print-label__store">{{ $tag['store_name'] }}</p>
                    @endif

                    @if ($options['show_product_name'])
                        @if (($options['locale'] ?? 'en') === 'both')
                            @if (filled($tag['product_name_en'] ?? null))
                                <h1 class="print-label__name">{{ $tag['product_name_en'] }}</h1>
                            @endif
                            @if (filled($tag['product_name_ur'] ?? null))
                                <p class="print-label__name-ur" dir="rtl">{{ $tag['product_name_ur'] }}</p>
                            @endif
                        @else
                            <h1 class="print-label__name">{{ $tag['product_name'] ?? '—' }}</h1>
                        @endif
                    @endif

                    @if ($options['show_cost_price'] || $options['show_sale_price'])
                        <div class="print-label__prices">
                            @if ($options['show_cost_price'])
                                <div class="print-label__price">
                                    <span>{{ __('price_tags.fields.cost_price') }}</span>
                                    <strong><x-secret-price :value="$tag['cost_price'] ?? ''" /></strong>
                                </div>
                            @endif
                            @if ($options['show_sale_price'])
                                <div class="print-label__price is-sale">
                                    <span>{{ __('price_tags.fields.sale_price') }}</span>
                                    <strong>{{ $tag['sale_price'] ?: '—' }}</strong>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if (($options['code_type'] ?? 'barcode') !== 'none')
                        <div class="print-label__code-wrap">
                            @if (($options['code_type'] ?? 'barcode') === 'qr')
                                {!! $tag['qr_svg'] ?? '' !!}
                            @else
                                {!! $tag['barcode_svg'] ?? '' !!}
                            @endif
                        </div>
                        <p class="print-label__foot">{{ $tag['scan_code'] ?? '' }}</p>
                    @endif
                </article>
            @endfor
        @endforeach

        <div class="print-actions">
            <button type="button" class="is-primary" onclick="window.print()">{{ __('price_tags.print.print_button') }}</button>
            <button type="button" onclick="window.close()">{{ __('price_tags.print.close_button') }}</button>
        </div>
    </div>

    @if ($autoprint)
        <script>window.addEventListener('load', () => window.setTimeout(() => window.print(), 250));</script>
    @endif
</body>
</html>
