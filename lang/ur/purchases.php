<?php

return [
    'actions' => [
        'add' => 'خریداری شامل کریں',
        'add_product' => 'شامل کریں',
        'edit_short' => 'ترمیم',
        'view_short' => 'دیکھیں',
        'delete_short' => 'حذف',
        'print_short' => 'پرنٹ',
        'print' => 'محفوظ اور پرنٹ',
        'remove' => 'ہٹائیں',
        'change_supplier' => 'سپلائر بدلیں',
        'view_bill' => 'بل دیکھیں',
        'bill_upload' => 'بل کی تصویر',
        'bill_uploading' => 'اپ لوڈ…',
        'delete_confirm' => 'کیا یہ خریداری حذف کریں؟',
    ],

    'sections' => [
        'buyer' => 'خریدار (ہمارا سٹور)',
        'supplier' => 'سپلائر',
    ],

    'fields' => [
        'purchase_number' => 'خریداری نمبر',
        'purchase_date' => 'تاریخ',
        'locale' => 'انوائس کی زبان',
        'purchaser' => 'خریدار (پرچیزر)',
        'supplier_balance' => 'سپلائر بیلنس',
        'delivery_status' => 'مال کی حیثیت',
        'payment_status' => 'ادائیگی کی حیثیت',
        'dispute_status' => 'انوائس کی حیثیت',
        'amount_paid' => 'ادا شدہ رقم',
        'subtotal' => 'ذیلی کل',
        'total_amount' => 'انوائس کل',
        'balance_due' => 'باقی رقم',
        'notes' => 'نوٹ',
        'vendor_bill' => 'سپلائر بل کی تصویر',
        'product_search' => 'پروڈکٹ شامل کریں',
        'created_by' => 'بنانے والا',
    ],

    'hints' => [
        'auto_on_save' => 'محفوظ پر خود',
        'select_purchaser' => 'پرچیزر منتخب کریں',
        'select_status' => 'منتخب کریں…',
        'select_delivery_status' => 'مال کی حیثیت منتخب کریں…',
        'select_payment_status' => 'ادائیگی کی حیثیت منتخب کریں…',
        'select_invoice_status' => 'انوائس کی حیثیت منتخب کریں…',
        'select_supplier' => 'سپلائر منتخب کریں — کوڈ یا نام سے تلاش کریں…',
        'supplier_search' => 'سپلائر کوڈ یا نام سے تلاش کریں…',
        'product_search' => 'بارکوڈ، کوڈ، SKU، یا نام سے تلاش کریں…',
        'notes' => 'اختیاری نوٹ',
        'notes_dispute' => 'اختیاری نوٹ — تنازعہ والی انوائس کی تفصیل یہاں لکھیں',
    ],

    'locales' => [
        'en' => 'انگلش',
        'ur' => 'اردو',
    ],

    'statuses' => [
        'delivery' => [
            'pending' => 'مال باقی',
            'partial' => 'جزوی موصول',
            'received' => 'مال موصول',
        ],
        'payment' => [
            'pending' => 'ادائیگی باقی',
            'partial' => 'جزوی ادائیگی',
            'paid' => 'ادا شدہ',
        ],
        'dispute' => [
            'ok' => 'انوائس ٹھیک',
            'disputed' => 'تنازعہ',
        ],
    ],

    'statuses_short' => [
        'delivery' => [
            'pending' => 'باقی',
            'partial' => 'جزوی',
            'received' => 'موصول',
        ],
        'payment' => [
            'pending' => 'باقی',
            'partial' => 'جزوی',
            'paid' => 'ادا',
        ],
        'dispute' => [
            'ok' => 'ٹھیک',
            'disputed' => 'تنازع',
        ],
    ],

    'columns' => [
        'sr' => 'نمبر',
        'thumb' => 'تصویر',
        'product_name' => 'پروڈکٹ کا نام',
        'required_qty' => 'درکار مقدار',
        'purchase_qty' => 'خریداری مقدار',
        'previous_price' => 'پچھلی قیمت',
        'current_rate' => 'موجودہ قیمت',
        'landing_cost' => 'لینڈنگ کاسٹ',
        'cost_price' => 'لاگت قیمت',
        'sale_price' => 'سیل پرائس',
        'line_total' => 'لائن کل',
    ],

    'form' => [
        'empty' => 'سپلائر منتخب کریں اور خریداری انوائس بنانے کے لیے پروڈکٹس شامل کریں۔',
    ],

    'list' => [
        'empty' => 'ابھی کوئی خریداری نہیں۔ نئی بنانے کے لیے Add Purchase دبائیں۔',
        'col_number' => 'خریداری نمبر',
        'col_supplier' => 'سپلائر',
        'col_purchaser' => 'پرچیزر',
        'col_total' => 'کل',
        'col_delivery' => 'مال',
        'col_payment' => 'ادائیگی',
        'col_dispute' => 'تنازعہ',
        'col_created' => 'تاریخ',
        'col_actions' => 'عمل',
    ],

    'messages' => [
        'created' => 'خریداری :number محفوظ ہو گئی۔',
        'updated' => 'خریداری :number اپ ڈیٹ ہو گئی۔',
        'save_failed' => 'خریداری محفوظ نہیں ہو سکی۔ فارم چیک کر کے دوبارہ کوشش کریں۔',
        'status_required' => 'محفوظ کرنے سے پہلے یہ منتخب کریں: :fields',
    ],

    'validation' => [
        'items_required' => 'خریداری میں کم از کم ایک پروڈکٹ شامل کریں۔',
    ],

    'print' => [
        'title' => 'خریداری انوائس',
        'heading' => 'خریداری انوائس',
        'print_button' => 'پرنٹ',
        'close_button' => 'بند کریں',
        'layout_a4' => 'A4',
        'layout_receipt' => 'رول / رسید',
        'buyer' => 'خریدار',
        'supplier' => 'سپلائر',
        'purchaser' => 'پرچیزر',
        'supplier_balance' => 'سپلائر سابقہ بیلنس',
    ],
];
