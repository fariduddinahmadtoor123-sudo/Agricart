<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $locale === 'ur' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: arial, sans-serif;
            font-size: 11pt;
            color: #0F172A;
            line-height: 1.55;
        }
        h1 { font-size: 18pt; margin: 0 0 8px; }
        h2 { font-size: 13pt; margin: 0 0 8px; }
        .cover { text-align: center; padding-top: 160px; page-break-after: always; }
        .cover__brand { font-size: 26pt; font-weight: 700; color: #83B735; }
        .cover__title { font-size: 20pt; font-weight: 700; margin: 12px 0; }
        .cover__meta { color: #64748B; font-size: 11pt; }
        .toc { page-break-after: always; }
        .toc__item { margin: 4px 0; border-bottom: 1px dotted #EBEDF0; padding-bottom: 3px; }
        .chapter { page-break-before: always; }
        .chapter:first-child { page-break-before: auto; }
        .eyebrow { color: #64748B; font-size: 9pt; margin-bottom: 4px; }
        .summary { color: #64748B; }
        .brief-section {
            border: 1px solid #CBD5E1;
            border-{{ $locale === 'ur' ? 'right' : 'left' }}: 4px solid #83B735;
            padding: 10px 12px;
            margin: 0 0 12px;
            background: #FFFFFF;
        }
        .brief-section--improvements {
            border-{{ $locale === 'ur' ? 'right' : 'left' }}-color: #FBBC34;
            background: #FFFDF5;
        }
        .brief-section--technical {
            border-{{ $locale === 'ur' ? 'right' : 'left' }}-color: #64748B;
            background: #F8FAFC;
        }
        .brief-section--prompt {
            border-{{ $locale === 'ur' ? 'right' : 'left' }}-color: #0F172A;
            background: #F8FAFC;
        }
        .section-number {
            display: inline-block;
            min-width: 18px;
            color: #FFFFFF;
            background: #83B735;
            padding: 2px 5px;
            margin-{{ $locale === 'ur' ? 'left' : 'right' }}: 6px;
            text-align: center;
            font-weight: 700;
        }
        .brief-section--improvements .section-number { background: #FBBC34; color: #0F172A; }
        .brief-section--prompt .section-number { background: #0F172A; }
        .inner-box {
            margin-top: 8px;
            padding: 8px 10px;
            border: 1px solid #E2E8F0;
            background: #F8FAFC;
        }
        .inner-box h3 { margin: 0 0 6px; font-size: 11pt; }
        .inner-box--tech { background: #FFFFFF; }
        .placeholder { margin: 0; color: #64748B; font-style: italic; }
        .improve-card {
            margin: 0 0 6px;
            padding: 7px 9px;
            border: 1px solid #F0D78A;
            background: #FFFFFF;
        }
        pre.prompt {
            white-space: pre-wrap;
            font-family: arial, sans-serif;
            background: #FFFFFF;
            border: 1px solid #CBD5E1;
            padding: 9px;
            font-size: 10pt;
            line-height: 1.55;
        }
        ul { margin: 0 0 8px; }
        p { margin: 0 0 7px; }
    </style>
</head>
<body>
    <div class="cover">
        <div class="cover__brand">Agricart</div>
        <div class="cover__title">{{ $title }}</div>
        <p class="cover__meta">{{ $labels['subtitle'] }}</p>
        <p class="cover__meta">{{ $labels['generated_at'] }}</p>
        <p class="cover__meta">{{ $labels['scope'] }}</p>
    </div>

    @if (count($chapters) > 1)
        <div class="toc">
            <h1>{{ $labels['toc'] }}</h1>
            @foreach ($chapters as $index => $chapter)
                <div class="toc__item">
                    {{ $index + 1 }}.
                    @if ($scope === 'book')
                        {{ $chapter['module_label'] }} —
                    @endif
                    {{ $chapter['page_label'] }}
                    @if (! empty($chapter['kind_label']))
                        ({{ $chapter['kind_label'] }})
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @foreach ($chapters as $chapter)
        <div @class(['chapter' => count($chapters) > 1])>
            <div class="eyebrow">
                {{ $chapter['module_label'] }} / {{ $chapter['page_label'] }}
                @if (! empty($chapter['kind_label']))
                    — {{ $chapter['kind_label'] }}
                @endif
            </div>
            @if (is_array($chapter['brief'] ?? null))
                @include('documentation.pdf.partials.brief-body', [
                    'brief' => $chapter['brief'],
                    'locale' => $locale,
                    'labels' => $labels,
                ])
            @endif
        </div>
    @endforeach

    <p style="margin-top:24px;color:#94A3B8;font-size:9pt;border-top:1px solid #EBEDF0;padding-top:6px;">
        {{ $labels['footer'] }}
    </p>
</body>
</html>
