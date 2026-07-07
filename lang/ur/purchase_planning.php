<?php

return [
    'actions' => [
        'add' => 'خریداری منصوبہ بندی شیٹ شامل کریں',
        'add_product' => 'شامل کریں',
        'edit_short' => 'ترمیم',
        'view_short' => 'دیکھیں',
        'delete_short' => 'حذف',
        'print_short' => 'پرنٹ',
        'print' => 'محفوظ اور پرنٹ',
        'remove' => 'ہٹائیں',
        'delete_confirm' => 'کیا یہ منصوبہ بندی شیٹ حذف کریں؟',
    ],

    'fields' => [
        'planning_number' => 'منصوبہ نمبر',
        'paper_size' => 'کاغذ کا سائز',
        'locale' => 'شیٹ کی زبان',
        'notes' => 'نوٹ',
        'product_search' => 'پروڈکٹ شامل کریں',
    ],

    'locales' => [
        'en' => 'انگلش',
        'ur' => 'اردو',
    ],

    'hints' => [
        'auto_on_save' => 'محفوظ کرنے پر خود بخود بنے گا',
        'notes' => 'اس شیٹ کے لیے اختیاری نوٹ',
        'product_search' => 'بارکوڈ، کوڈ، SKU، یا پروڈکٹ نام سے تلاش کریں…',
    ],

    'paper_sizes' => [
        'a4' => 'A4',
        'a5' => 'A5',
        'a3' => 'A3',
    ],

    'columns' => [
        'sr' => 'نمبر',
        'thumb' => 'تصویر',
        'product_name' => 'پروڈکٹ کا نام',
        'stock' => 'اسٹاک',
        'required_qty' => 'درکار مقدار',
        'low_stock' => 'کم اسٹاک',
        'purchase_qty' => 'خریداری مقدار',
        'landing_price' => 'لینڈنگ پرائس',
        'sale_price' => 'سیل پرائس',
    ],

    'form' => [
        'empty' => 'خریداری منصوبہ بندی شیٹ بنانے کے لیے پروڈکٹس تلاش کر کے شامل کریں۔',
    ],

    'list' => [
        'empty' => 'ابھی کوئی منصوبہ بندی شیٹ نہیں۔ نئی بنانے کے لیے Add دبائیں۔',
        'col_number' => 'منصوبہ نمبر',
        'col_items' => 'اشیاء',
        'col_paper' => 'کاغذ',
        'col_locale' => 'زبان',
        'col_created_by' => 'بنانے والا',
        'col_created' => 'تاریخ',
        'col_actions' => 'عمل',
    ],

    'messages' => [
        'created' => 'منصوبہ بندی شیٹ :number محفوظ ہو گئی۔',
        'updated' => 'منصوبہ بندی شیٹ :number اپ ڈیٹ ہو گئی۔',
        'save_failed' => 'منصوبہ بندی شیٹ محفوظ نہیں ہو سکی۔ فارم چیک کر کے دوبارہ کوشش کریں۔',
    ],

    'validation' => [
        'items_required' => 'منصوبہ بندی شیٹ میں کم از کم ایک پروڈکٹ شامل کریں۔',
    ],

    'print' => [
        'title' => 'خریداری منصوبہ بندی',
        'heading' => 'خریداری منصوبہ بندی شیٹ',
        'print_button' => 'پرنٹ',
        'close_button' => 'بند کریں',
    ],
];
