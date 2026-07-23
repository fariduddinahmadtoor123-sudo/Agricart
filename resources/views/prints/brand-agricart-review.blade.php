<!DOCTYPE html>
<html lang="{{ $locale === 'ur' ? 'ur' : 'en' }}" @if ($locale === 'ur') dir="rtl" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $brandName }} — {{ __('agricart.brands.print.title') }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f5f7;
            color: #0f172a;
        }
        .print-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.85rem;
            padding: 1.25rem;
        }
        .print-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
            justify-content: center;
        }
        .print-actions a,
        .print-actions button {
            border: 1px solid #ebedf0;
            background: #fff;
            padding: 0.45rem 0.85rem;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 0.8125rem;
            font-weight: 700;
            color: #0f172a;
            text-decoration: none;
            cursor: pointer;
            border-radius: 0;
        }
        .print-actions button.is-primary,
        .print-actions a.is-primary {
            background: #83b735;
            border-color: #83b735;
            color: #fff;
        }
        .print-actions a.is-active {
            border-color: #83b735;
            color: #83b735;
            background: color-mix(in srgb, #83b735 12%, #fff);
        }
        .sheet {
            width: 100%;
            max-width: 210mm;
            min-height: 297mm;
            background: #fff;
            border: 1px solid #ebedf0;
            padding: 12mm 12mm 10mm;
        }
        .sheet__header {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 0.75rem 1rem;
            align-items: start;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #83b735;
            margin-bottom: 0.85rem;
        }
        .sheet__store {
            margin: 0 0 0.2rem;
            font-size: 1.05rem;
            font-weight: 800;
        }
        .sheet__doc {
            margin: 0;
            font-size: 0.8rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .sheet__meta {
            margin: 0.35rem 0 0;
            font-size: 0.75rem;
            color: #64748b;
        }
        .sheet__brand {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            margin-bottom: 0.85rem;
        }
        .sheet__logo {
            width: 56px;
            height: 56px;
            object-fit: contain;
            border: 1px solid #ebedf0;
            background: #f4f5f7;
        }
        .sheet__brand-name {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 800;
        }
        .sheet__code {
            margin: 0.15rem 0 0;
            font-size: 0.78rem;
            color: #64748b;
        }
        .sheet__section {
            margin: 0 0 0.85rem;
        }
        .sheet__section-title {
            margin: 0 0 0.35rem;
            font-size: 0.9rem;
            font-weight: 800;
            color: #83b735;
        }
        .sheet__body {
            margin: 0;
            font-size: 0.84rem;
            line-height: 1.45;
            white-space: pre-wrap;
        }
        .sheet__score {
            display: inline-flex;
            align-items: baseline;
            gap: 0.25rem;
            margin: 0 0 0.55rem;
            padding: 0.35rem 0.55rem;
            border: 1px solid #ebedf0;
            background: #f4f5f7;
            font-weight: 800;
        }
        .sheet__score-value {
            font-size: 1.4rem;
            color: #83b735;
        }
        .sheet__score-of {
            font-size: 0.85rem;
            color: #64748b;
        }
        .sheet__fairness {
            margin: 0 0 0.55rem;
            font-size: 0.78rem;
            color: #64748b;
        }
        .criteria {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.76rem;
        }
        .criteria th,
        .criteria td {
            border: 1px solid #d1d5db;
            padding: 0.35rem 0.4rem;
            vertical-align: top;
            text-align: start;
        }
        .criteria th {
            background: #f4f5f7;
            font-weight: 700;
        }
        .criteria__result {
            font-weight: 800;
            white-space: nowrap;
        }
        .criteria__result.is-pass { color: #3f6212; }
        .criteria__result.is-fail { color: #991b1b; }
        .sheet__empty {
            margin: 0;
            font-size: 0.84rem;
            color: #64748b;
        }
        .sheet__notice {
            margin-top: 1rem;
            padding-top: 0.65rem;
            border-top: 1px solid #ebedf0;
            font-size: 0.72rem;
            line-height: 1.4;
            color: #64748b;
            white-space: pre-wrap;
        }
        .sheet__footer {
            margin-top: 0.75rem;
            font-size: 0.7rem;
            color: #94a3b8;
        }
        @media print {
            body { background: #fff; }
            .print-actions { display: none !important; }
            .print-shell { padding: 0; }
            .sheet {
                border: none;
                max-width: none;
                min-height: auto;
                padding: 0;
            }
            @page { size: A4 portrait; margin: 10mm; }
        }
    </style>
</head>
<body>
    <div class="print-shell">
        <div class="print-actions">
            <button type="button" class="is-primary" onclick="window.print()">{{ __('agricart.brands.print.print_button') }}</button>
            <a href="{{ $printUrlEn }}" @class(['is-active' => $locale === 'en'])>{{ __('agricart.brands.print.locale_en') }}</a>
            <a href="{{ $printUrlUr }}" @class(['is-active' => $locale === 'ur'])>{{ __('agricart.brands.print.locale_ur') }}</a>
            <button type="button" onclick="window.close()">{{ __('agricart.brands.print.close_button') }}</button>
        </div>

        <div class="sheet">
            <header class="sheet__header">
                <div>
                    @if (filled($storeName))
                        <p class="sheet__store">{{ $storeName }}</p>
                    @endif
                    <p class="sheet__doc">{{ __('agricart.brands.print.doc_label') }}</p>
                    <p class="sheet__meta">
                        {{ __('agricart.brands.print.printed_on', ['date' => now()->format('d M Y')]) }}
                        @if (filled($storePhone))
                            · {{ $storePhone }}
                        @endif
                    </p>
                </div>
            </header>

            <div class="sheet__brand">
                @if (filled($logoUrl))
                    <img src="{{ $logoUrl }}" alt="" class="sheet__logo">
                @endif
                <div>
                    <h1 class="sheet__brand-name">{{ $brandName }}</h1>
                    <p class="sheet__code">
                        {{ __('agricart.brands.fields.code') }}: {{ $brand->code }}
                        @if (filled($brand->website_url))
                            · {{ $brand->website_url }}
                        @endif
                    </p>
                </div>
            </div>

            @if (filled($description))
                <section class="sheet__section">
                    <h2 class="sheet__section-title">{{ __('agricart.brands.print.section_description') }}</h2>
                    <p class="sheet__body">{{ $description }}</p>
                </section>
            @endif

            <section class="sheet__section">
                <h2 class="sheet__section-title">{{ __('agricart.brands.print.section_review') }}</h2>

                @if ($reviewComplete && $score !== null)
                    <div class="sheet__score" aria-label="{{ __('agricart.brands.print.score_aria', ['score' => $score, 'total' => $totalChecks]) }}">
                        <span class="sheet__score-value">{{ $score }}</span>
                        <span class="sheet__score-of">/ {{ $totalChecks }}</span>
                    </div>
                    <p class="sheet__fairness">{{ __('agricart.brands.print.fairness') }}</p>

                    <table class="criteria">
                        <thead>
                            <tr>
                                <th>{{ __('agricart.brands.print.col_check') }}</th>
                                <th>{{ __('agricart.brands.print.col_result') }}</th>
                                <th>{{ __('agricart.brands.print.col_reason') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (\App\Support\Catalog\BrandAgricartReviewCriteria::keys() as $key)
                                @php
                                    $detail = $criteria[$key];
                                    $passed = $detail['pass'] === true;
                                    $label = $locale === 'ur'
                                        ? (string) __('agricart.brands.review_criteria.'.$key.'.label_ur', [], 'ur')
                                        : (string) __('agricart.brands.review_criteria.'.$key.'.label', [], 'en');
                                    $reason = $locale === 'ur' && filled($detail['reason_ur'])
                                        ? $detail['reason_ur']
                                        : $detail['reason_en'];
                                    if ($reason === '' && filled($detail['reason_ur'])) {
                                        $reason = $detail['reason_ur'];
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $label }}</td>
                                    <td class="criteria__result {{ $passed ? 'is-pass' : 'is-fail' }}">
                                        {{ $passed
                                            ? __('agricart.brands.review_actions.pass')
                                            : __('agricart.brands.review_actions.fail') }}
                                    </td>
                                    <td>{{ $reason }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="sheet__empty">{{ __('agricart.brands.print.review_incomplete') }}</p>
                @endif
            </section>

            @if (filled($fullReview))
                <section class="sheet__section">
                    <h2 class="sheet__section-title">{{ __('agricart.brands.print.section_summary') }}</h2>
                    <p class="sheet__body">{{ $fullReview }}</p>
                </section>
            @endif

            @if (filled($revision))
                <section class="sheet__section">
                    <h2 class="sheet__section-title">{{ __('agricart.brands.print.section_revision') }}</h2>
                    <p class="sheet__body">{{ $revision }}</p>
                </section>
            @endif

            @if (filled($notice))
                <div class="sheet__notice">{{ $notice }}</div>
            @endif

            <p class="sheet__footer">{{ __('agricart.brands.print.footer') }}</p>
        </div>
    </div>

    @if ($autoprint)
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
