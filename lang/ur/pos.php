<?php

return [
    'fields' => [
        'product_search' => 'پروڈکٹ تلاش',
        'product_name' => 'پروڈکٹ',
        'customer' => 'گاہک',
        'customer_search' => 'نام یا فون سے گاہک تلاش',
        'quantity' => 'مقدار',
        'brand' => 'برانڈ',
        'unit' => 'یونٹ',
        'unit_price' => 'قیمت',
        'tier' => 'ریٹ',
        'line_total' => 'کل',
        'payment_method' => 'ادائیگی کا طریقہ',
        'payment_status' => 'ادائیگی کی حیثیت',
        'amount_paid' => 'وصول رقم',
        'discount' => 'ڈسکاؤنٹ',
        'discount_type' => 'ڈسکاؤنٹ کی قسم',
        'discount_value' => 'ڈسکاؤنٹ',
        'price_display' => 'قیمت کی شکل',
        'print_controls' => 'انوائس پر کنٹرولز',
        'bank_account' => 'بینک اکاؤنٹ',
        'notes' => 'نوٹ',
        'subtotal' => 'ذیلی کل',
        'total' => 'کل رقم',
        'balance_due' => 'بقایا',
        'sale_date' => 'تاریخ',
        'sale_number' => 'انوائس #',
        'invoice_status' => 'انوائس کی حیثیت',
        'cashier' => 'کیشیئر',
        'locale' => 'انوائس کی زبان',
    ],

    'locales' => [
        'en' => 'انگلش',
        'ur' => 'اردو',
    ],

    'payment_methods' => [
        'cash' => 'کیش',
        'bank_transfer' => 'بینک ٹرانسفر',
    ],

    'payment_statuses' => [
        'paid' => 'مکمل ادائیگی',
        'partial' => 'جزوی ادائیگی',
        'unpaid' => 'ادائیگی باقی',
    ],

    'invoice_statuses' => [
        'held' => 'ہولڈ',
        'completed' => 'مکمل',
    ],

    'print_controls_options' => [
        'no' => 'کنٹرولز پرنٹ نہ کریں',
        'yes' => 'انوائس پر کنٹرولز پرنٹ کریں',
    ],

    'discount_types' => [
        '' => 'کوئی ڈسکاؤنٹ نہیں',
        'percent' => 'فیصد %',
        'fixed' => 'مقررہ رقم',
    ],

    'price_displays' => [
        'plain' => 'عام نمبر',
        'secret' => 'خفیہ کوڈ والی قیمت',
    ],

    'actions' => [
        'add' => 'نئی سیل انوائس',
        'clear_customer' => 'گاہک بدلیں',
        'complete_sale' => 'سیل مکمل کریں',
        'hold' => 'انوائس ہولڈ',
        'add_product' => 'شامل',
        'remove' => 'ہٹائیں',
        'print' => 'محفوظ اور پرنٹ',
        'view_short' => 'دیکھیں',
        'edit_short' => 'جاری رکھیں',
        'print_short' => 'پرنٹ',
        'delete_short' => 'حذف',
        'delete_confirm' => 'یہ سیل انوائس حذف کریں؟',
    ],

    'hints' => [
        'product_search' => 'نام، کوڈ، SKU، بارکوڈ — فہرست سے منتخب کریں',
        'customer_search' => 'نام یا فون سے گاہک تلاش کریں',
        'empty_grid' => 'پروڈکٹ تلاش کر کے شامل کریں — مقدار پہلے 0',
        'auto_on_save' => 'محفوظ پر خود',
        'select_status' => 'منتخب کریں…',
        'select_method' => 'ادائیگی کا طریقہ منتخب کریں…',
        'notes' => 'اس انوائس کے نوٹ',
        'select_bank' => 'بینک منتخب کریں…',
        'no_banks' => 'ابھی کوئی بینک اکاؤنٹ نہیں — Finance → Banks سے شامل کریں',
    ],

    'filters' => [
        'channel' => 'چینل',
        'invoice' => 'انوائس کی حیثیت',
        'payment' => 'ادائیگی',
        'method' => 'ادائیگی کا طریقہ',
        'active_channel' => 'چینل: :channel',
        'active_invoice' => 'انوائس: :status',
        'active_payment' => 'ادائیگی: :status',
        'active_method' => 'طریقہ: :method',
    ],

    'channels' => [
        'online' => 'آن لائن سٹور',
        'offline' => 'آف لائن / POS',
    ],

    'list' => [
        'empty' => 'ابھی کوئی سیل انوائس نہیں۔ نئی انوائس کے لیے Add استعمال کریں۔',
        'no_results' => 'تلاش یا فلٹر سے کوئی انوائس نہیں ملی۔',
        'clear_filters' => 'فلٹر صاف',
        'col_number' => 'انوائس #',
        'col_channel' => 'چینل',
        'col_customer' => 'گاہک',
        'col_cashier' => 'کیشیئر',
        'col_total' => 'کل',
        'col_invoice_status' => 'انوائس',
        'col_payment_status' => 'ادائیگی',
        'col_payment_method' => 'طریقہ',
        'col_sale_date' => 'تاریخ',
        'col_created' => 'بنائی گئی',
        'col_actions' => 'عمل',
    ],

    'messages' => [
        'completed' => 'سیل :number مکمل ہو گئی۔',
        'updated' => 'سیل :number اپڈیٹ ہو گئی۔',
        'held' => 'سیل :number ہولڈ — سٹاک نہیں گھٹا۔',
        'held_updated' => 'ہولڈ سیل :number اپڈیٹ ہو گئی۔',
        'save_failed' => 'سیل محفوظ نہیں ہو سکی۔',
        'payment_status_required' => 'محفوظ کرنے سے پہلے ادائیگی کی حیثیت منتخب کریں۔',
        'payment_method_required' => 'محفوظ کرنے سے پہلے ادائیگی کا طریقہ (کیش یا بینک) منتخب کریں۔',
        'bank_account_required' => 'وصولی والا بینک اکاؤنٹ منتخب کریں۔',
        'bank_transfer_unavailable' => 'بینک ٹرانسفر کے لیے Finance → Banks میں فعال بینک اکاؤنٹ درکار ہے۔',
    ],

    'validation' => [
        'items_required' => 'کم از کم ایک پروڈکٹ کی مقدار 0 سے زیادہ لکھیں۔',
        'products_required' => 'انوائس میں کم از کم ایک پروڈکٹ شامل کریں۔',
        'completed_locked' => 'مکمل سیل میں ترمیم نہیں ہو سکتی۔',
        'invalid_status' => 'انوائس کی حیثیت درست نہیں۔',
        'insufficient_stock' => ':product کے لیے سٹاک ناکافی ہے (دستیاب :available، درخواست :requested)۔',
    ],

    'print' => [
        'title' => 'سیلز انوائس',
        'heading' => 'سیلز انوائس',
        'customer' => 'گاہک',
        'cashier' => 'کیشیئر',
        'print_button' => 'پرنٹ',
        'close_button' => 'بند',
        'controls_heading' => 'پروڈکٹ کنٹرولز',
        'no_controls' => 'ان پروڈکٹس پر کوئی کنٹرول لنک نہیں۔',
        'layout_a4' => 'A4',
        'layout_receipt' => 'رسید (3 انچ)',
    ],
];
