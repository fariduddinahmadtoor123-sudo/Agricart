<?php

return [
    'economic-radar' => [
        'title' => 'FX Learn — اکنامک ریڈار',
        'summary' => 'مفت Forex Factory + Finance Calendar فیڈز سے لائیو اکنامک کیلنڈر، سیشن گھڑیاں، TradingView وجٹ، اور اختیاری AI تجزیہ۔',
        'meta' => [
            'routes' => ['/admin/fx-learn/economic-radar'],
            'permissions' => ['fx-learn.economic-radar.view', 'fx-learn.economic-radar.edit'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'ٹریڈرز کے لیے ورکنگ کیلنڈر شیٹ: PKT اوقات، USD/سب فلٹرز، امپیکٹ، Previous / Forecast / Actual۔',
                    'فیڈ سورس مفت FF+FC ہے۔ Finnhub سیٹنگز UI سے ہٹا دی گئیں کیونکہ وہ اس فیڈ کو نہیں چلاتیں۔',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'استعمال',
                'steps' => [
                    'FX Learn → Economic Radar کھولیں۔',
                    'دن / USD / امپیکٹ سے فلٹر کریں؛ فیڈ مسئلہ ہو تو Refresh دبائیں۔',
                    'AI Analyze کے لیے Economic Radar Edit اور Settings → AI میں فعال OpenRouter کلید درکار ہے۔',
                ],
            ],
            [
                'id' => 'permissions',
                'type' => 'permissions',
                'heading' => 'اجازتیں',
                'list' => [
                    'fx-learn.economic-radar.view — صفحہ کھولنا اور کیلنڈر پڑھنا۔',
                    'fx-learn.economic-radar.edit — ریلیزڈ Actual پر AI تجزیہ۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'page' => 'App\\Filament\\Clusters\\FxLearn\\EconomicRadarPage',
                    'calendar' => 'App\\Services\\FxLearn\\EconomicCalendarService',
                    'schedule' => 'php artisan fx-learn:refresh-calendar (ہر 5 منٹ)',
                ],
            ],
        ],
    ],

    'market-signals' => [
        'title' => 'FX Learn — مارکیٹ سگنلز',
        'summary' => 'TradingView ٹیپ، کوٹ، ٹیکنیکل اینالیسس، اور کیٹیگری کے مطابق ہیٹ میپ / کوٹس۔',
        'meta' => [
            'routes' => ['/admin/fx-learn/market-signals'],
            'permissions' => ['fx-learn.market-signals.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'فاریکس، کموڈیٹیز، انڈیسز اور کرپٹو کے لیے تعلیمی مارکیٹ پینل (TradingView embeds)۔',
                    'ٹول بار کیٹیگری/ٹائم فریم Livewire سے سنک؛ نیچے ہیٹ میپ/کوٹس کیٹیگری بدلنے پر دوبارہ لوڈ ہوتے ہیں۔',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'استعمال',
                'steps' => [
                    'ٹول بار سے کیٹیگری اور ٹائم فریم چنیں۔',
                    'ایسٹ چپ سے کوٹ + ٹیکنیکل اینالیسس ریفریش کریں۔',
                    'ہیٹ میپ / کوٹس وہی کیٹیگری فالو کرتے ہیں۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'page' => 'App\\Filament\\Clusters\\FxLearn\\MarketSignalsPage',
                    'catalog' => 'App\\Services\\FxLearn\\MarketSignalCatalog',
                ],
            ],
        ],
    ],

    'trade-terminal' => [
        'title' => 'FX Learn — ٹریڈ ٹرمینل',
        'summary' => 'ذاتی ڈیمو ٹریڈنگ ڈیسک: واچ لسٹ، TradingView چارٹ، SL/TP، اوپن پوزیشنز، ہسٹری، سیو/ری سیٹ۔',
        'meta' => [
            'routes' => ['/admin/fx-learn/trade-terminal'],
            'permissions' => ['fx-learn.trade-terminal.view', 'fx-learn.trade-terminal.edit'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'ڈیمو منی سے پریکٹس۔ فرنٹ لائن پر صرف وہ سمبلز جو آپ واچ لسٹ میں ایڈ کریں۔',
                    'چارٹ TradingView Advanced Chart ہے (Market Signals جیسا سمبل خاندان)۔ لائیو بِڈ/آسک Binance سرکاری WebSocket/REST اور Finnhub فری quote API سے آتے ہیں — کوئی صفحہ سکریپنگ نہیں۔',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'استعمال',
                'steps' => [
                    '+ Add symbol سے کیٹلاگ میں سے سمبل ایڈ کریں۔',
                    'واچ لسٹ سمبل چنیں — چارٹ اور ٹکٹ فالو کریں گے۔',
                    'لاٹس، اختیاری SL/TP، Buy/Sell۔ اوپن اور ہسٹری آپ کے یوزر کے تحت محفوظ۔',
                    'Account & rules: % محفوظ کریں، یا Reset (اوپن بند، بیلنس واپس؛ ہسٹری رہتی ہے)۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'page' => 'App\\Filament\\Clusters\\FxLearn\\TradeTerminalPage',
                    'terminal' => 'App\\Services\\FxLearn\\DemoTerminalService',
                    'quotes' => 'App\\Services\\FxLearn\\DemoQuoteService',
                    'catalog' => 'App\\Services\\FxLearn\\DemoSymbolCatalog',
                ],
            ],
        ],
    ],
];
