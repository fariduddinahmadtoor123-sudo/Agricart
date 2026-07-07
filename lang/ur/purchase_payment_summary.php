<?php

return [
    'actions' => [
        'add' => 'خریداری ادائیگی سمری شامل کریں',
        'add_manual' => 'دستی فریق شامل کریں',
        'add_source' => 'ادائیگی کا ذریعہ شامل کریں',
        'edit' => 'ترمیم: :number',
        'view' => 'دیکھیں: :number',
        'edit_short' => 'ترمیم',
        'view_short' => 'دیکھیں',
        'delete_short' => 'حذف',
        'print_short' => 'پرنٹ',
        'print' => 'محفوظ اور پرنٹ',
        'remove' => 'ہٹائیں',
        'delete_confirm' => 'یہ ادائیگی سمری شیٹ حذف کریں؟',
    ],

    'fields' => [
        'summary_number' => 'سمری نمبر',
        'summary_date' => 'سمری کی تاریخ',
        'paper_size' => 'کاغذ کا سائز',
        'locale' => 'شیٹ کی زبان',
        'notes' => 'نوٹس',
        'supplier' => 'سپلائر',
        'supplier_search' => 'سپلائر تلاش کریں',
        'phone' => 'فون',
        'address' => 'پتہ',
        'payment_amount' => 'ادائیگی',
        'line_status' => 'حالت',
        'source_label' => 'ادائیگی کا ذریعہ',
        'source_amount' => 'موصول رقم',
        'purchaser' => 'پرچیزر',
        'created_by' => 'بنانے والا',
        'printed_by' => 'پرنٹ کرنے والا',
    ],

    'sections' => [
        'suppliers' => 'سپلائر ادائیگیاں',
        'sources' => 'ادائیگی کہاں سے آئی',
    ],

    'locales' => [
        'en' => 'انگریزی',
        'ur' => 'اردو',
    ],

    'line_statuses' => [
        'ok' => 'ٹھیک',
        'dispute' => 'تنازع',
    ],

    'hints' => [
        'auto_on_save' => 'محفوظ پر خود بنے گا',
        'select_purchaser' => 'پرچیزر منتخب کریں',
        'notes' => 'اس سمری شیٹ کے لیے اختیاری نوٹ',
        'manual_row' => 'غیر رجسٹرڈ فریق یا اخراجات کے لیے پلس بٹن استعمال کریں۔',
        'supplier_search' => 'سپلائر تلاش کریں اور قطار میں شامل کریں',
        'manual_name' => 'فریق / اخراجات کا نام',
        'sources' => 'ادائیگی کے ذرائع اور رقم لکھیں (زیادہ سے زیادہ پانچ)۔',
        'supplier_readonly' => 'سپلائر کی تفصیل یہاں صرف پڑھنے کے لیے ہے۔',
    ],

    'paper_sizes' => [
        'a4' => 'A4',
        'receipt' => 'رول / رسید',
    ],

    'filters' => [
        'locale' => 'شیٹ کی زبان',
    ],

    'columns' => [
        'sr' => 'نمبر',
        'supplier' => 'سپلائر / فریق',
        'payment' => 'ادائیگی',
        'ok' => 'ٹھیک',
        'dispute' => 'تنازع',
    ],

    'form' => [
        'empty' => 'اوپر سپلائر تلاش کریں یا + سے دستی فریق شامل کریں۔',
    ],

    'totals' => [
        'supplier_payments' => 'سپلائرز کو کل ادائیگی',
        'source_payments' => 'کل موصول ادائیگی',
        'balance' => 'بقایا / بچت',
    ],

    'list' => [
        'empty' => 'ابھی کوئی خریداری ادائیگی سمری نہیں۔ بنانے کے لیے Add دبائیں۔',
        'col_number' => 'سمری نمبر',
        'col_date' => 'سمری کی تاریخ',
        'col_paid_lines' => 'ادائیگی والی لائنیں',
        'col_total' => 'کل ادائیگیاں',
        'col_locale' => 'زبان',
        'col_created' => 'محفوظ',
        'col_actions' => 'عمل',
    ],

    'messages' => [
        'created' => 'ادائیگی سمری :number محفوظ ہو گئی۔',
        'updated' => 'ادائیگی سمری :number اپ ڈیٹ ہو گئی۔',
        'save_failed' => 'ادائیگی سمری محفوظ نہیں ہو سکی۔ فارم چیک کریں۔',
        'supplier_exists' => 'یہ سپلائر پہلے سے لسٹ میں ہے۔',
    ],

    'validation' => [
        'items_required' => 'کم از کم ایک سپلائر یا دستی قطار شامل کریں۔',
        'content_required' => 'کم از کم ایک ادائیگی کی رقم یا ذریعہ رقم لکھیں۔',
    ],

    'print' => [
        'title' => 'خریداری ادائیگی سمری',
        'heading' => 'خریداری ادائیگی سمری شیٹ',
        'date' => 'تاریخ',
        'print_button' => 'پرنٹ',
        'close_button' => 'بند کریں',
        'sources_heading' => 'ادائیگی کہاں سے موصول ہوئی',
        'total_payments' => 'سپلائرز کو کل ادائیگی',
        'total_sources' => 'کل موصول ادائیگی',
        'balance' => 'بقایا / بچت',
        'notes' => 'نوٹس',
    ],
];
