<?php

return [
    'actions' => [
        'add' => 'خریداری کوٹیشن شامل کریں',
        'add_product' => 'شامل کریں',
        'edit_short' => 'ترمیم',
        'view_short' => 'دیکھیں',
        'delete_short' => 'حذف',
        'print_short' => 'پرنٹ',
        'print' => 'محفوظ اور پرنٹ',
        'remove' => 'ہٹائیں',
        'change_supplier' => 'سپلائر بدلیں',
        'delete_confirm' => 'کیا یہ کوٹیشن شیٹ حذف کریں؟',
    ],

    'fields' => [
        'quotation_number' => 'کوٹیشن نمبر',
        'quotation_date' => 'تاریخ',
        'locale' => 'شیٹ کی زبان',
        'supplier' => 'سپلائر',
        'notes' => 'نوٹ',
        'product_search' => 'پروڈکٹ شامل کریں',
    ],

    'hints' => [
        'auto_on_save' => 'محفوظ پر خود',
        'select_supplier' => 'سپلائر منتخب کریں — کوڈ یا نام سے تلاش کریں…',
        'product_search' => 'بارکوڈ، کوڈ، SKU، یا نام سے تلاش کریں…',
        'notes' => 'سپلائر کے لیے اختیاری نوٹ',
    ],

    'locales' => [
        'en' => 'انگلش',
        'ur' => 'اردو',
    ],

    'columns' => [
        'sr' => 'نمبر',
        'thumb' => 'تصویر',
        'product_name' => 'پروڈکٹ کا نام',
        'required_qty' => 'درکار مقدار',
        'previous_price' => 'پچھلی قیمت',
        'quoted_rate' => 'کوٹڈ ریٹ',
    ],

    'form' => [
        'empty' => 'سپلائر منتخب کریں اور کوٹیشن شیٹ بنانے کے لیے پروڈکٹ شامل کریں۔',
    ],

    'list' => [
        'empty' => 'ابھی کوئی خریداری کوٹیشن نہیں۔ شامل کریں پر کلک کریں۔',
        'col_number' => 'کوٹیشن نمبر',
        'col_supplier' => 'سپلائر',
        'col_items' => 'اشیاء',
        'col_locale' => 'زبان',
        'col_created_by' => 'بنانے والا',
        'col_created' => 'تاریخ',
        'col_actions' => 'عمل',
    ],

    'messages' => [
        'created' => 'کوٹیشن :number محفوظ ہو گئی۔',
        'updated' => 'کوٹیشن :number اپڈیٹ ہو گئی۔',
        'save_failed' => 'کوٹیشن محفوظ نہیں ہو سکی۔ فارم چیک کر کے دوبارہ کوشش کریں۔',
    ],

    'validation' => [
        'items_required' => 'کوٹیشن شیٹ میں کم از کم ایک پروڈکٹ شامل کریں۔',
        'supplier_required' => 'محفوظ کرنے سے پہلے سپلائر منتخب کریں۔',
    ],

    'print' => [
        'title' => 'خریداری کوٹیشن',
        'heading' => 'خریداری کوٹیشن شیٹ',
        'print_button' => 'پرنٹ',
        'close_button' => 'بند کریں',
        'buyer' => 'بھیجنے والا (ہمارا سٹور)',
        'supplier' => 'وصول کنندہ (سپلائر)',
        'rate_hint' => 'خالی خانے میں کوٹڈ ریٹ لکھ کر یہ شیٹ واپس کریں۔',
        'receipt' => [
            'required_qty' => 'درکار',
            'previous_price' => 'پچھلی',
            'quoted_rate' => 'کوٹڈ ریٹ',
        ],
        'layout_a4' => 'A4',
        'layout_receipt' => 'رول / رسید',
    ],
];
