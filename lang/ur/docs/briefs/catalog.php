<?php

return [
    'categories' => [
        'list' => [
            'title' => 'کیٹگریز کی فہرست',
            'summary' => 'مصنوعات کے زمرے فلیٹ فہرست میں؛ درخت UI نہیں۔',
            'article' => [
                'Catalog → Categories پر زمرے قطاروں میں نظر آتے ہیں۔ Parent/بچہ تعلق فارم میں ہوتا ہے، فہرست درخت کی شکل میں نہیں۔',
                'Add Category سے فارم پینل کھلتا ہے۔ تصویر، دو لسانی نام، اور visual map code یہاں اہم ہیں۔',
            ],
            'fields' => [
                ['name' => 'toolbar', 'label' => 'سرچ/فلٹر/کالمز/Add', 'note' => 'معیاری'],
            ],
            'improvements' => [
                ['text' => 'فہرست میں map code اور parent نام دکھائیں', 'suggest' => 'add'],
                ['text' => 'اختیاری درخت نظر (بعد میں)', 'suggest' => 'optional'],
            ],
            'technical' => ['/admin/catalog/categories', 'ListCategories'],
            'prompt' => <<<'PROMPT'
Catalog Categories فہرست: فلیٹ ٹیبل، سرچ، Add Category → فارم پینل۔ درخت UI ابھی مت بنائیں۔ کالمز میں نام، slug، status، map code دکھائیں۔
PROMPT,
        ],
        'form' => [
            'title' => 'کیٹگری شامل کریں (Add Category Form)',
            'summary' => 'تصویر لازمی، نام EN/UR، parent، display-in، SEO، AI، map code۔',
            'article' => [
                'کیٹگری فارم سب سے بھرپور فارمز میں سے ہے۔ نیا زمرہ بناتے وقت تصویر لازمی ہے — یہ random WebP نام سے محفوظ ہوتی ہے، فائل پر نمبر نہیں لگتا۔ نمبر والا کوڈ الگ ہوتا ہے: Visual Map Code جیسے R1، R1M1 (ہائیرارکی)۔',
                'اگر ذیلی زمرہ ہو تو parent منتخب کریں۔ اوپر slots 1–4 آباء کی تصاویر صرف دکھاتی ہیں (منتخب نہیں ہوتیں)۔ Slot 5 اس زمرے کی اپنی تصویر ہے۔',
                'Hijri آئیکن کیٹلاگ کوڈ میں موجود ہے مگر فارم میں جڑا نہیں — نئے سسٹم میں یا تو صاف ہٹا دیں یا صحیح picker بنائیں۔',
                '“Also show in categories” محفوظ تو ہوتا ہے مگر اسٹور فرنٹ اسے استعمال نہیں کرتا — بہتری میں فیصلہ کریں۔',
            ],
            'fields' => [
                ['name' => 'image', 'label' => 'تصویر', 'note' => 'Create پر لازمی؛ WebP'],
                ['name' => 'name_en / name_ur', 'label' => 'نام', 'note' => 'لازمی'],
                ['name' => 'parent_id', 'label' => 'Parent زمرہ', 'note' => 'اختیاری'],
                ['name' => 'display_category_ids', 'label' => 'Also show in', 'note' => 'pivot؛ storefront نہیں پڑھتا'],
                ['name' => 'slug / code / status', 'label' => 'سلگ/کوڈ/سٹیٹس', 'note' => 'سلگ خودکار ہو سکتا ہے'],
                ['name' => 'visual_map_code', 'label' => 'Map code', 'note' => 'R/M/S… خودکار'],
                ['name' => 'content/SEO/FAQs', 'label' => 'مواد', 'note' => 'دو لسانی بلاکس'],
                ['name' => 'AI', 'label' => 'AI', 'note' => 'name_en + اختیاری تصویر'],
            ],
            'improvements' => [
                ['text' => 'تصویر لازمی رکھیں یا واضح “بغیر تصویر ڈرافٹ” موڈ', 'suggest' => 'keep'],
                ['text' => 'Map code رکھیں — لیبلز/پرنٹ کے لیے مفید', 'suggest' => 'keep'],
                ['text' => 'Hijri: یا مکمل وائر کریں یا ہٹا دیں (آدھا نہ چھوڑیں)', 'suggest' => 'add'],
                ['text' => 'category_display: یا storefront جوڑیں یا فیچر ہٹائیں', 'suggest' => 'add'],
                ['text' => 'فہرست actions پر edit/delete permission چیک', 'suggest' => 'add'],
            ],
            'technical' => [
                'CategoryService، CategoryVisualMappingService، CategoryEnrichmentService',
                'جداول: categories، category_display',
                'تصویر: categories/{random40}.webp',
            ],
            'prompt' => <<<'PROMPT'
نئے سسٹم میں Add Category فارم بنائیں (Catalog)۔

لازمی موجودہ رویہ نقل کریں جب تک میں نہ بدلوں:
- name_en + name_ur لازمی
- create پر تصویر لازمی؛ WebP آپٹیمائزڈ اسٹوریج؛ فائل نام random (نمبر نہیں)
- اختیاری parent؛ ancestor پیش منظر slots
- slug name_en سے uniquify
- visual map code: R/M/S/C/L ہائیرارکی، save پر regenerate
- AI enrichment (Settings AI)
- Footer: AI → Save & Add Next (parent رکھے) → Save & Close

واضح بہتری (مجھ سے پوچھ کر لاگو کریں):
1) Hijri icons: پورا کام یا بالکل نہ
2) “Also show in” storefront سے جوڑیں یا UI سے ہٹائیں
3) permission چیکس سخت کریں

پہلے کام کرنے والا فارم + سروس؛ SEO/FAQ دوسرے مرحلے میں بھر سکتے ہو۔
PROMPT,
        ],
    ],

    'products' => [
        'form' => [
            'title' => 'پروڈکٹ شامل کریں (Add Product)',
            'summary' => 'فروخت کا مرکزی ریکارڈ: کیٹگری، قیمت، یونٹ، اسٹاک، AI۔',
            'article' => [
                'پروڈکٹ فارم stepped ہوتا ہے۔ Primary category لازمی۔ قیمتیں، unit/pack، تصاویر، attributes۔ AI متن بھرتا ہے۔',
            ],
            'fields' => [
                ['name' => 'name_en/ur', 'label' => 'نام', 'note' => 'لازمی'],
                ['name' => 'category_id', 'label' => 'کیٹگری', 'note' => 'لازمی'],
                ['name' => 'prices / units / stock', 'label' => 'قیمت و مقدار', 'note' => 'اختیاری مگر POS کے لیے اہم'],
            ],
            'improvements' => [
                ['text' => 'فارم فیلڈز کم کریں — پہلے ضروری، باقی “مزید” میں', 'suggest' => 'add'],
                ['text' => 'بارکوڈ اسکین فیلڈ واضح', 'suggest' => 'keep'],
            ],
            'technical' => ['ProductService', 'products جدول'],
            'prompt' => <<<'PROMPT'
Add Product فارم: لازمی name_en، name_ur، category_id۔ اختیاری brand، قیمتیں، unit/pack، تصویر، AI۔
فارم کو مراحل میں رکھیں مگر پہلے مرحلے میں صرف ضروری فیلڈز۔ Agricart فارم پینل فوٹر استعمال کریں۔
PROMPT,
        ],
        'list' => [
            'title' => 'پروڈکٹس فہرست',
            'summary' => 'مصنوعات کی فہرست اور سرچ۔',
            'article' => ['Products فہرست سے Add/Edit کھلتا ہے۔'],
            'fields' => [],
            'improvements' => [['text' => 'کم اسٹاک ہائی لائٹ', 'suggest' => 'add']],
            'technical' => [],
            'prompt' => 'Products فہرست: سرچ، فلٹر، Add Product۔ کم اسٹاک رنگ سے دکھائیں۔',
        ],
    ],

    'overview' => [
        'page' => [
            'title' => 'Catalog جائزہ',
            'summary' => 'ماسٹر ڈیٹا کا نقشہ۔',
            'article' => ['پہلے Units/Categories/Brands، پھر Products۔'],
            'fields' => [],
            'improvements' => [],
            'technical' => [],
            'prompt' => 'Catalog Overview: کارڈز جو Categories، Products، Brands کی طرف لے جائیں۔',
        ],
    ],
];
