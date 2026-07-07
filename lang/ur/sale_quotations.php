<?php

return [
    'actions' => [
        'add' => 'سیل کوٹیشن شامل کریں',
        'add_product' => 'شامل کریں',
        'edit_short' => 'ترمیم',
        'view_short' => 'دیکھیں',
        'delete_short' => 'حذف',
        'print' => 'محفوظ اور پرنٹ',
        'print_short' => 'پرنٹ',
        'remove' => 'ہٹائیں',
        'clear_customer' => 'گاہک صاف کریں',
        'delete_confirm' => 'یہ سیل کوٹیشن حذف کریں؟',
    ],

    'fields' => [
        'quotation_number' => 'کوٹیشن نمبر',
        'quotation_date' => 'تاریخ',
        'quotation_amount' => 'کوٹیشن کل',
        'prepared_by' => 'تیار کنندہ',
        'locale' => 'شیٹ کی زبان',
        'notes' => 'نوٹس',
        'product_search' => 'پروڈکٹ شامل کریں',
        'discount_type' => 'رعایت کی قسم',
        'discount_value' => 'رعایت کی قیمت',
        'print_controls' => 'کوٹیشن پر کنٹرولز',
        'subtotal' => 'ذیلی کل',
        'discount' => 'رعایت',
    ],

    'hints' => [
        'auto_on_save' => 'محفوظ پر خودکار',
        'customer_search' => 'کوڈ یا نام سے گاہک تلاش کریں…',
        'product_search' => 'بارکوڈ، کوڈ، SKU یا نام سے تلاش…',
        'notes' => 'گاہک کے لیے اختیاری نوٹ',
    ],

    'locales' => [
        'en' => 'انگریزی',
        'ur' => 'اردو',
    ],

    'columns' => [
        'product_name' => 'پروڈکٹ کا نام',
        'brand' => 'برانڈ',
        'quantity' => 'مقدار',
        'unit' => 'یونٹ',
        'tier' => 'ٹیئر',
        'unit_price' => 'یونٹ قیمت',
        'line_total' => 'لائن کل',
    ],

    'list' => [
        'empty' => 'ابھی کوئی سیل کوٹیشن نہیں۔ شامل کرنے کے لیے Add پر کلک کریں۔',
        'col_number' => 'کوٹیشن نمبر',
        'col_customer' => 'گاہک',
        'col_prepared_by' => 'تیار کنندہ',
        'col_amount' => 'رقم',
        'col_locale' => 'زبان',
        'col_quotation_date' => 'کوٹیشن تاریخ',
        'col_created' => 'تخلیق',
        'col_actions' => 'اعمال',
    ],

    'messages' => [
        'created' => 'کوٹیشن :number محفوظ ہو گئی۔',
        'updated' => 'کوٹیشن :number اپ ڈیٹ ہو گئی۔',
        'save_failed' => 'کوٹیشن محفوظ نہیں ہو سکی۔ فارم چیک کریں اور دوبارہ کوشش کریں۔',
    ],

    'validation' => [
        'products_required' => 'کوٹیشن میں کم از کم ایک پروڈکٹ شامل کریں۔',
    ],

    'print' => [
        'title' => 'سیل کوٹیشن',
        'heading' => 'سیلز کوٹیشن',
        'controls_heading' => 'پروڈکٹ کنٹرولز (پیش نظارہ)',
    ],
];
