<?php

return [
    'overview' => [
        'title' => 'Docs — جائزہ',
        'summary' => 'پورے Agricart کی دو سطحی دستاویزات: آسان ہینڈ آف بریف اور تفصیلی ڈیولپر گائیڈ۔',
        'meta' => [
            'routes' => ['/admin/docs/overview'],
            'permissions' => ['docs.view'],
            'related' => ['تمام ماڈیولز'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'Docs Overview سسٹم کے تمام 15 ماڈیولز کی دستاویزات تک مرکزی راستہ ہے۔ نیلا نشان آسان بریف، سبز نشان تفصیلی گائیڈ، اور دوسرے نشان موجودہ تکمیل کی حالت بتاتے ہیں۔',
                    'آسان بریف مالک، نئے ملازم اور AI کے لیے ہے؛ تفصیلی گائیڈ ڈیولپر کے لیے route، permission، field، service اور اصل code behaviour محفوظ کرتی ہے۔',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'استعمال',
                'steps' => [
                    'User Guide کھولیں، ماڈیول اور پھر صفحہ منتخب کریں۔',
                    'آسان بریف میں مضمون، فیلڈز، بہتریاں اور تیار AI پیغام پڑھیں۔',
                    'موجودہ صفحہ، پورا ماڈیول یا تمام بریفز PDF میں ڈاؤن لوڈ کریں۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'نیا فیچر مکمل ہوتے ہی اسی تبدیلی میں اس کی تفصیلی گائیڈ اور آسان بریف بھی اپ ڈیٹ کریں۔',
                    'Code-verified موجودہ رویہ اور مستقبل کی تجویز ہمیشہ الگ رکھیں۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'page' => 'App\\Filament\\Clusters\\Docs\\OverviewPage',
                    'registry' => 'App\\Support\\Documentation\\DocumentationRegistry',
                ],
            ],
        ],
    ],

    'guide' => [
        'title' => 'User Guide اور آسان بریف',
        'summary' => 'ایک ہی جگہ پڑھنے، AI کو پیغام دینے، نئے ملازم کو تربیت دینے اور PDF کتاب بنانے کا نظام۔',
        'meta' => [
            'routes' => ['/admin/docs/guide'],
            'permissions' => ['docs.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'User Guide میں بائیں طرف صفحات، اوپر ماڈیول، زبان اور document mode منتخب ہوتے ہیں۔ آسان بریف عام فہم handoff ہے؛ تفصیلی گائیڈ مکمل تکنیکی حوالہ ہے۔',
                    'تیار پیغام کاپی کرکے نئے پراجیکٹ کے AI یا ڈیولپر کو دینے سے اسے page purpose، fields، verified behaviour اور زیرِ غور improvements ایک دفعہ میں مل جاتی ہیں۔',
                ],
            ],
            [
                'id' => 'fields',
                'type' => 'fields',
                'heading' => 'کنٹرولز',
                'fields' => [
                    ['name' => 'module', 'label' => 'ماڈیول', 'type' => 'tabs', 'required' => true, 'rules' => '15 sidebar modules'],
                    ['name' => 'page', 'label' => 'صفحہ', 'type' => 'sidebar', 'required' => true, 'rules' => 'منتخب ماڈیول کے صفحات'],
                    ['name' => 'mode', 'label' => 'دستاویز کی قسم', 'type' => 'enum', 'required' => true, 'rules' => 'brief|guide'],
                    ['name' => 'lang', 'label' => 'زبان', 'type' => 'enum', 'required' => true, 'rules' => 'ur|en'],
                    ['name' => 'pdf', 'label' => 'PDF دائرہ', 'type' => 'actions', 'required' => false, 'rules' => 'page|module|book'],
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'عمل',
                'steps' => [
                    'صفحہ منتخب کریں اور آسان بریف پڑھیں۔',
                    'Copy Prompt سے تیار ہینڈ آف پیغام لیں۔',
                    'PDF: یہ بریف، ماڈیول بریف یا تمام بریف سے مطلوبہ فائل محفوظ کریں۔',
                    'تکنیکی implementation جانچنے کے لیے تفصیلی گائیڈ کھولیں۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'ہر PDF پر generation date اور scope واضح رکھیں۔',
                    'Urdu PDF فونٹ rendering کو deployment پر لازماً smoke-test کریں۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'page' => 'App\\Filament\\Clusters\\Docs\\GuidePage',
                    'livewire' => 'App\\Livewire\\Docs\\DocumentationShell',
                    'pdf' => 'App\\Services\\Documentation\\DocumentationPdfExporter',
                ],
            ],
        ],
    ],
];
