<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $locale === 'ur' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 28px 32px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #0F172A;
            line-height: 1.45;
        }

        h1, h2, h3 {
            margin: 0 0 8px;
            page-break-after: avoid;
        }

        h1 { font-size: 20px; }
        h2 { font-size: 15px; margin-top: 4px; }
        h3 { font-size: 12px; }

        .cover {
            text-align: center;
            padding-top: 180px;
            page-break-after: always;
        }

        .cover__brand {
            font-size: 28px;
            font-weight: 700;
            color: #83B735;
            margin-bottom: 12px;
        }

        .cover__title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .cover__meta {
            color: #64748B;
            font-size: 11px;
        }

        .toc {
            page-break-after: always;
        }

        .toc__item {
            margin: 4px 0;
            border-bottom: 1px dotted #EBEDF0;
            padding-bottom: 3px;
        }

        .chapter {
            page-break-before: always;
        }

        .chapter:first-of-type {
            page-break-before: auto;
        }

        .chapter__eyebrow {
            color: #64748B;
            font-size: 10px;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .chapter__summary {
            color: #64748B;
            margin: 0 0 12px;
        }

        .meta-box {
            border: 1px solid #EBEDF0;
            background: #F4F5F7;
            padding: 8px 10px;
            margin-bottom: 12px;
        }

        .meta-box p {
            margin: 0 0 4px;
        }

        .section {
            margin-bottom: 14px;
        }

        .section--rebuild {
            border: 1px solid #FBBC34;
            background: #FFFBEB;
            padding: 8px 10px;
        }

        p {
            margin: 0 0 7px;
        }

        ul, ol {
            margin: 0 0 8px;
            padding-{{ $locale === 'ur' ? 'right' : 'left' }}: 16px;
        }

        li {
            margin-bottom: 3px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0 10px;
            font-size: 9.5px;
        }

        th, td {
            border: 1px solid #EBEDF0;
            padding: 4px 5px;
            text-align: {{ $locale === 'ur' ? 'right' : 'left' }};
            vertical-align: top;
        }

        th {
            background: #F4F5F7;
            font-weight: 700;
        }

        code {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
        }

        .fallback {
            color: #64748B;
            font-style: italic;
        }

        .footer-note {
            margin-top: 24px;
            color: #94A3B8;
            font-size: 9px;
            border-top: 1px solid #EBEDF0;
            padding-top: 6px;
        }
    </style>
</head>
<body>
    <div class="cover">
        <div class="cover__brand">Agricart</div>
        <div class="cover__title">{{ $title }}</div>
        @if (! empty($moduleLabel) && $scope === 'module')
            <p class="cover__meta">{{ __('documentation.pdf.module_subtitle', ['module' => $moduleLabel], $locale) }}</p>
        @endif
        <p class="cover__meta">
            {{ __('documentation.pdf.generated_at', ['date' => $generatedAt], $locale) }}
            · {{ $locale === 'ur' ? __('documentation.locale.urdu', [], $locale) : __('documentation.locale.english', [], $locale) }}
        </p>
        <p class="cover__meta">{{ __('documentation.pdf.scope_' . $scope, [], $locale) }}</p>
    </div>

    @if (count($chapters) > 1)
        <div class="toc">
            <h1>{{ __('documentation.pdf.toc', [], $locale) }}</h1>
            @foreach ($chapters as $index => $chapter)
                <div class="toc__item">
                    {{ $index + 1 }}.
                    @if ($scope === 'book')
                        {{ $chapter['module_label'] }} —
                    @endif
                    {{ $chapter['title'] }}
                </div>
            @endforeach
        </div>
    @endif

    @foreach ($chapters as $chapter)
        <div @class(['chapter' => count($chapters) > 1])>
            <div class="chapter__eyebrow">
                {{ $chapter['module_label'] }} / {{ $chapter['page_label'] }}
            </div>
            <h1>{{ $chapter['title'] }}</h1>

            @if (is_array($chapter['article']))
                @include('documentation.pdf.partials.article', [
                    'article' => $chapter['article'],
                    'locale' => $locale,
                ])
            @else
                <p class="fallback">
                    @if ($chapter['implemented'])
                        {{ __('documentation.fallback.implemented_pending', [], $locale) }}
                    @else
                        {{ __('documentation.fallback.not_implemented', [], $locale) }}
                    @endif
                </p>
            @endif
        </div>
    @endforeach

    <div class="footer-note">
        {{ __('documentation.pdf.footer', [], $locale) }}
    </div>
</body>
</html>
