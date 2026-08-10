<?php

return [
    'locale' => [
        'english' => 'English',
        'urdu' => 'اردو',
    ],

    'nav' => [
        'modules' => 'دستاویزی ماڈیولز',
        'pages' => 'اس ماڈیول کے صفحات',
    ],

    'badges' => [
        'documented' => 'مکمل گائیڈ موجود',
        'brief' => 'یوزر گائیڈ موجود',
        'live' => 'فیچر زندہ — گائیڈ توسیع ہو رہی',
        'planned' => 'جلد ایپ میں',
        'coming_soon_module' => 'ماڈیول جلد',
    ],

    'modes' => [
        'brief' => 'یوزر گائیڈ',
        'guide' => 'تفصیلی گائیڈ',
    ],

    'brief' => [
        'howto' => 'تین سادہ حصے: (۱) Overview — یہ صفحہ کیا کرتا ہے اور کیسے استعمال کریں؛ (۲) AI Prompt — صفحہ دوبارہ بنانے کا پیغام؛ (۳) Notes — آئندہ خواہشات (بعد میں بھریں)۔ Overview اور Notes کے لیے English / Urdu بدلیں۔',
        'overview_heading' => '۱ — Overview',
        'overview_empty' => 'اردو Overview ابھی خالی ہے۔ آسان اردو الفاظ میں یہاں (زبان کی فائل میں) پیسٹ کر دیں۔',
        'article_heading' => '۱ — Overview',
        'fields_heading' => 'فیلڈز (آج کے سسٹم میں)',
        'ui_flow_heading' => 'UI Flow',
        'functional_heading' => 'Functional Requirements',
        'validation_heading' => 'Validation',
        'database_heading' => 'Database',
        'api_heading' => 'API / Integration',
        'permissions_heading' => 'Permissions',
        'edge_cases_heading' => 'Edge Cases',
        'business_rules_heading' => 'Business Rules',
        'non_functional_heading' => 'Non-Functional Requirements',
        'dependencies_heading' => 'Dependencies / Related Modules',
        'section_placeholder' => '— بعد میں بھریں —',
        'improvements_heading' => '۳ — Notes',
        'improvements_hint' => 'اس صفحے پر آئندہ تبدیلیاں لکھیں۔',
        'notes_heading' => '۳ — Notes',
        'notes_hint' => 'آئندہ خواہشات (ہارڈ لاک نہیں)۔ جب کچھ نیا چاہیے ہو تو docs کی زبان فائل میں لائنیں شامل کر دیں۔',
        'notes_empty' => 'ابھی کوئی نوٹ نہیں۔ بعد میں اس صفحے کی منصوبہ بند تبدیلیاں یہاں لکھ سکتے ہیں۔',
        'technical_heading' => 'مختصر تکنیکی حوالہ',
        'prompt_heading' => '۲ — AI Prompt',
        'prompt_hint' => 'اس پیغام کو AI ٹول میں کاپی کر کے یہ صفحہ آج جیسے دوبارہ بنایا جا سکتا ہے۔',
        'prompt_empty' => 'اس صفحے کا AI Prompt ابھی نہیں لکھا۔',
        'copy_prompt' => 'پیغام کاپی کریں',
        'copied' => 'کاپی ہو گیا',
        'missing' => 'اس صفحے کی یوزر گائیڈ ابھی نہیں لکھی۔',
        'missing_hint' => 'کہیں کہ اس صفحے کے لیے Overview + AI Prompt (لسٹ اور/یا فارم) لکھ دیں۔',
        'kinds' => [
            'list' => 'فہرست صفحہ',
            'form' => 'ایڈ / ایڈٹ فارم',
            'page' => 'صفحہ',
        ],
        'suggest_keep' => 'رکھیں',
        'suggest_add' => 'شامل / بہتر',
        'suggest_remove' => 'ہٹائیں',
        'suggest_optional' => 'اختیاری',
    ],

    'screenshot_placeholder' => 'اسکرین شاٹ کی جگہ — بعد میں تصویر شامل کریں',

    'meta' => [
        'routes' => 'راستے',
        'permissions' => 'اجازتیں',
        'related' => 'متعلقہ',
    ],

    'fields' => [
        'name' => 'فیلڈ',
        'label' => 'لیبل',
        'type' => 'قسم',
        'required' => 'لازمی',
        'rules' => 'قواعد / نوٹ',
        'yes' => 'ہاں',
        'no' => 'نہیں',
    ],

    'fallback' => [
        'implemented_pending' => 'یہ اسکرین Agricart میں فعال ہے۔ اس صفحے کی تفصیلی دو لسانی گائیڈ شامل کی جا رہی ہے۔ تب تک ایپ کا ٹول بار اور فارم پینل استعمال کریں؛ جلد یہاں واپس دیکھیں۔',
        'not_implemented' => 'یہ اسکرین ابھی ایپ میں دستیاب نہیں۔ ترقی مکمل ہونے تک مینو پر placeholder نظر آ سکتا ہے۔',
        'no_ur_translation' => 'اس صفحے کا اردو متن ابھی شائع نہیں۔ انگریزی ٹیب استعمال کریں یا اگلی اپ ڈیٹ کے بعد دوبارہ دیکھیں۔',
    ],

    'overview' => [
        'intro' => 'Docs ایک یوزر گائیڈ ہے۔ ہر صفحے پر تین حصے ہیں: Overview (کیا اور کیسے)، AI Prompt (دوبارہ بنانے کا پیغام)، اور Notes (آئندہ خواہشات)۔',
        'structure' => 'نیلا نقطہ = یوزر گائیڈ تیار۔ ماڈیول چنیں، صفحہ چنیں، English یا Urdu پڑھیں۔ فہرست / فارم الگ منتخب ہو سکتے ہیں۔',
        'open_guide' => 'یوزر گائیڈ کھولیں',
    ],

    'pdf' => [
        'group_label' => 'دستاویزات PDF ڈاؤن لوڈ',
        'page' => 'PDF: یہ صفحہ',
        'module' => 'PDF: یہ ماڈیول',
        'book' => 'PDF: پوری کتاب',
        'page_hint' => 'منتخب زبان میں موجودہ صفحہ PDF میں ڈاؤن لوڈ کریں۔',
        'module_hint' => 'موجودہ ماڈیول کے تمام صفحات ایک PDF میں۔',
        'book_hint' => 'پورا Agricart دستاویزی کتاب (تمام ماڈیولز)۔',
        'preparing' => 'تیار ہو رہا ہے…',
        'book_title' => 'Agricart یوزر گائیڈ',
        'module_subtitle' => ':module — ماڈیول دستاویزات',
        'generated_at' => 'تیار کردہ :date',
        'toc' => 'فہرستِ مضامین',
        'scope_page' => 'ایک صفحے کی برآمد',
        'scope_module' => 'ماڈیول برآمد',
        'scope_book' => 'مکمل سسٹم دستاویزی کتاب',
        'footer' => 'Agricart دستاویزات — آف لائن محفوظ کرنے کے لیے PDF برآمد۔',
        'brief_page' => 'PDF: یہ گائیڈ',
        'brief_module' => 'PDF: ماڈیول گائیڈ',
        'brief_book' => 'PDF: تمام گائیڈز',
        'brief_page_hint' => 'اس صفحے کی یوزر گائیڈ (لسٹ + فارم اگر لکھی ہو) PDF میں۔',
        'brief_module_hint' => 'اس ماڈیول کی تمام یوزر گائیڈز ایک PDF میں۔',
        'brief_book_hint' => 'پورے Agricart کی یوزر گائیڈز ایک کتاب کی صورت میں۔',
        'brief_book_title' => 'Agricart یوزر گائیڈ',
        'brief_book_subtitle' => 'Overview + AI Prompt + Notes',
    ],
];
