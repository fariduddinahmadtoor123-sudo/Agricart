<?php

return [
    'economic-radar' => [
        'title' => 'FX Learn — Economic Radar',
        'summary' => 'Live economic calendar from free Forex Factory + Finance Calendar feeds, session clocks, TradingView calendar widget, and optional AI bias analysis.',
        'meta' => [
            'routes' => ['/admin/fx-learn/economic-radar'],
            'permissions' => ['fx-learn.economic-radar.view', 'fx-learn.economic-radar.edit'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Working calendar sheet for traders: PKT times, USD/all filters, impact filters, Previous / Forecast / Actual, and surprise tone.',
                    'Feed source is free FF+FC (merged). Finnhub API settings were removed from the UI because they do not drive this feed.',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'Use',
                'steps' => [
                    'Open FX Learn → Economic Radar.',
                    'Filter by day / USD / impact; Refresh if the banner shows a feed issue.',
                    'AI Analyze needs Economic Radar Edit permission plus an active OpenRouter key in Settings → AI.',
                ],
            ],
            [
                'id' => 'permissions',
                'type' => 'permissions',
                'heading' => 'Permissions',
                'list' => [
                    'fx-learn.economic-radar.view — open the page and read the calendar.',
                    'fx-learn.economic-radar.edit — run AI analyze on released Actuals.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
                'classes' => [
                    'page' => 'App\\Filament\\Clusters\\FxLearn\\EconomicRadarPage',
                    'calendar' => 'App\\Services\\FxLearn\\EconomicCalendarService',
                    'schedule' => 'php artisan fx-learn:refresh-calendar (every 5 minutes)',
                ],
            ],
        ],
    ],

    'market-signals' => [
        'title' => 'FX Learn — Market Signals',
        'summary' => 'TradingView tape, quote, technical analysis, and category-aware heatmap / quotes panels.',
        'meta' => [
            'routes' => ['/admin/fx-learn/market-signals'],
            'permissions' => ['fx-learn.market-signals.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Educational market panel for forex, commodities, indices, and crypto using TradingView embeds.',
                    'Toolbar category/timeframe stays in sync with Livewire; lower heatmap/quotes remount when the category changes.',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'Use',
                'steps' => [
                    'Pick category and timeframe in the toolbar.',
                    'Select an asset chip to refresh quote + technical analysis.',
                    'Heatmap / quotes panels follow the same category.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
                'classes' => [
                    'page' => 'App\\Filament\\Clusters\\FxLearn\\MarketSignalsPage',
                    'catalog' => 'App\\Services\\FxLearn\\MarketSignalCatalog',
                ],
            ],
        ],
    ],
];
