<!DOCTYPE html>
<html lang="{{ $locale === 'ur' ? 'ur' : 'en' }}" @if ($locale === 'ur') dir="rtl" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $single ? ($cards[0]['name'] ?? __('suppliers.print.title')) : __('suppliers.print.directory_title') }}</title>
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
        }
        .sheet {
            width: 100%;
            max-width: 210mm;
            background: #fff;
            border: 1px solid #ebedf0;
            padding: 10mm 10mm 8mm;
        }
        .sheet__header {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding-bottom: 0.6rem;
            border-bottom: 2px solid #83b735;
            margin-bottom: 0.75rem;
        }
        .sheet__store { margin: 0 0 0.15rem; font-size: 1.05rem; font-weight: 800; }
        .sheet__doc { margin: 0; font-size: 0.8rem; font-weight: 700; color: #64748b; }
        .sheet__meta { margin: 0; font-size: 0.75rem; color: #64748b; text-align: end; }
        .cards {
            display: grid;
            grid-template-columns: {{ $single ? '1fr' : '1fr 1fr' }};
            gap: 6mm;
        }
        .card {
            border: 1px solid #cbd5e1;
            padding: 4mm;
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .card__head {
            display: flex;
            gap: 8px;
            align-items: flex-start;
            margin-bottom: 6px;
        }
        .card__photo {
            width: {{ $single ? '72px' : '48px' }};
            height: {{ $single ? '72px' : '48px' }};
            object-fit: cover;
            border: 1px solid #e2e8f0;
            flex-shrink: 0;
        }
        .card__name { margin: 0; font-size: {{ $single ? '1.05rem' : '0.88rem' }}; font-weight: 800; }
        .card__code { margin: 0.15rem 0 0; font-size: 0.72rem; color: #64748b; }
        .facts { display: grid; grid-template-columns: 1fr 1fr; gap: 2px 8px; margin: 0 0 6px; }
        .facts div { min-width: 0; }
        .facts dt { margin: 0; font-size: 0.62rem; font-weight: 700; color: #64748b; }
        .facts dd { margin: 0 0 4px; font-size: {{ $single ? '0.8rem' : '0.72rem' }}; word-break: break-word; }
        .block { margin: 0 0 6px; }
        .block h3 { margin: 0 0 3px; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b; }
        .block p, .block li { margin: 0; font-size: {{ $single ? '0.8rem' : '0.72rem' }}; }
        .block ul { margin: 0; padding-inline-start: 1.1rem; }
        .docs { display: flex; gap: 6px; flex-wrap: wrap; }
        .docs img {
            max-width: {{ $single ? '160px' : '90px' }};
            max-height: {{ $single ? '100px' : '60px' }};
            border: 1px solid #e2e8f0;
            object-fit: cover;
        }
        .empty { margin: 2rem 0; text-align: center; color: #64748b; }
        @media print {
            body { background: #fff; }
            .print-actions { display: none !important; }
            .print-shell { padding: 0; }
            .sheet { border: 0; max-width: none; }
        }
    </style>
</head>
<body>
    <div class="print-shell">
        <div class="print-actions">
            <button type="button" class="is-primary" onclick="window.print()">{{ __('suppliers.actions.print') }}</button>
            <a href="{{ $printUrlEn }}" @class(['is-active' => $locale === 'en'])>EN</a>
            <a href="{{ $printUrlUr }}" @class(['is-active' => $locale === 'ur'])>اردو</a>
        </div>

        <article class="sheet">
            <header class="sheet__header">
                <div>
                    <p class="sheet__store">{{ $storeName }}</p>
                    <p class="sheet__doc">
                        {{ $single ? __('suppliers.print.title') : __('suppliers.print.directory_title') }}
                    </p>
                </div>
                <p class="sheet__meta">
                    @if (filled($storePhone)){{ $storePhone }}<br>@endif
                    {{ now()->format('Y-m-d H:i') }}
                    · {{ trans_choice('suppliers.print.count', count($cards), ['count' => count($cards)]) }}
                </p>
            </header>

            @if ($cards === [])
                <p class="empty">{{ __('suppliers.print.empty') }}</p>
            @else
                <div class="cards">
                    @foreach ($cards as $card)
                        <section class="card">
                            <div class="card__head">
                                @if ($card['photoUrl'])
                                    <img class="card__photo" src="{{ $card['photoUrl'] }}" alt="">
                                @endif
                                <div>
                                    <h2 class="card__name">{{ $card['name'] }}</h2>
                                    <p class="card__code">{{ $card['code'] }}</p>
                                </div>
                            </div>

                            @if ($card['facts'] !== [])
                                <dl class="facts">
                                    @foreach ($card['facts'] as $fact)
                                        <div>
                                            <dt>{{ $fact['label'] }}</dt>
                                            <dd>{{ $fact['value'] }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            @endif

                            @if ($card['contacts'] !== [])
                                <div class="block">
                                    <h3>{{ __('suppliers.sections.contacts') }}</h3>
                                    <ul>
                                        @foreach ($card['contacts'] as $contact)
                                            <li>{{ $contact['name'] }}@if (filled($contact['mobile'])) — {{ $contact['mobile'] }}@endif</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if ($card['banks'] !== [])
                                <div class="block">
                                    <h3>{{ __('suppliers.sections.banks') }}</h3>
                                    <ul>
                                        @foreach ($card['banks'] as $bank)
                                            <li>
                                                {{ collect([$bank['bank'], $bank['title'], $bank['number']])->filter()->implode(' · ') }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if ($card['cardFrontUrl'] || $card['cardBackUrl'])
                                <div class="block">
                                    <h3>{{ __('suppliers.sections.documents') }}</h3>
                                    <div class="docs">
                                        @if ($card['cardFrontUrl'])
                                            <img src="{{ $card['cardFrontUrl'] }}" alt="{{ __('suppliers.fields.card_front') }}">
                                        @endif
                                        @if ($card['cardBackUrl'])
                                            <img src="{{ $card['cardBackUrl'] }}" alt="{{ __('suppliers.fields.card_back') }}">
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </section>
                    @endforeach
                </div>
            @endif
        </article>
    </div>
    @if ($autoprint)
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
