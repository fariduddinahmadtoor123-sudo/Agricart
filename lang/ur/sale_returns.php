<?php

return [
    'actions' => [
        'add' => 'سیل ریٹرن شامل کریں',
        'load_invoice' => 'انوائس لوڈ کریں',
        'edit_short' => 'ترمیم',
        'view_short' => 'دیکھیں',
        'delete_short' => 'حذف',
        'print' => 'محفوظ اور پرنٹ',
        'print_short' => 'پرنٹ',
        'clear_customer' => 'گاہک صاف کریں',
        'clear_invoice' => 'انوائس بدلیں',
        'delete_confirm' => 'یہ سیل ریٹرن حذف کریں؟',
    ],

    'fields' => [
        'return_number' => 'ریٹرن نمبر',
        'return_date' => 'ریٹرن تاریخ',
        'processed_by' => 'پروسیس کنندہ',
        'sale_number' => 'POS انوائس #',
        'customer' => 'گاہک',
        'notes' => 'نوٹس',
        'refund_subtotal' => 'ریفنڈ ذیلی کل',
        'refund_total' => 'ریفنڈ کل',
        'balance_due' => 'باقی واجب',
    ],

    'hints' => [
        'auto_on_save' => 'محفوظ پر خودکار',
        'customer_search' => 'کوڈ یا نام سے گاہک تلاش کریں…',
        'sale_number' => 'مکمل POS انوائس نمبر درج کریں…',
        'notes' => 'اختیاری اندرونی نوٹ',
    ],

    'columns' => [
        'product_name' => 'پروڈکٹ کا نام',
        'brand' => 'برانڈ',
        'sold_quantity' => 'فروخت مقدار',
        'unit_price' => 'یونٹ قیمت',
        'return_quantity' => 'ریٹرن مقدار',
        'return_amount' => 'ریٹرن رقم',
    ],

    'list' => [
        'empty' => 'ابھی کوئی سیل ریٹرن نہیں۔ ریکارڈ کرنے کے لیے Add پر کلک کریں۔',
        'no_results' => 'آپ کی تلاش یا فلٹرز سے کوئی سیل ریٹرن نہیں ملا۔',
        'clear_filters' => 'فلٹرز صاف کریں',
        'col_number' => 'ریٹرن نمبر',
        'col_sale_number' => 'انوائس نمبر',
        'col_customer' => 'گاہک',
        'col_refund_total' => 'ریفنڈ کل',
        'col_payment_status' => 'ادائیگی کی حالت',
        'col_payment_method' => 'ادائیگی کا طریقہ',
        'col_return_date' => 'ریٹرن تاریخ',
        'col_created' => 'تخلیق',
        'col_actions' => 'اعمال',
    ],

    'messages' => [
        'created' => 'ریٹرن :number محفوظ ہو گیا۔',
        'updated' => 'ریٹرن :number اپ ڈیٹ ہو گیا۔',
        'save_failed' => 'ریٹرن محفوظ نہیں ہو سکا۔ فارم چیک کریں اور دوبارہ کوشش کریں۔',
        'load_failed' => 'انوائس لوڈ نہیں ہو سکی۔',
        'customer_required' => 'انوائس لوڈ کرنے سے پہلے گاہک منتخب کریں۔',
        'sale_required' => 'محفوظ کرنے سے پہلے POS انوائس اور ریٹرن لائنز لوڈ کریں۔',
        'invoice_loaded' => 'انوائس :number لوڈ ہو گئی۔',
    ],

    'validation' => [
        'sale_number_required' => 'POS انوائس نمبر درج کریں۔',
        'sale_not_found' => 'اس انوائس نمبر کے ساتھ کوئی مکمل سیل نہیں ملی۔',
        'sale_customer_mismatch' => 'یہ انوائس کسی اور گاہک کی ہے۔',
        'items_required' => 'کم از کم ایک لائن پر ریٹرن مقدار درج کریں۔',
        'qty_exceeds_sold' => 'ریٹرن مقدار فروخت مقدار سے زیادہ نہیں ہو سکتی۔',
        'qty_exceeds_remaining' => 'ریٹرن مقدار باقی واپسی کی حد سے زیادہ ہے۔',
        'refund_posted_locked' => 'اس ریٹرن کا کیش یا بینک ریفنڈ پوسٹ ہو چکا ہے، اب اسے تبدیل یا حذف نہیں کیا جا سکتا۔',
    ],

    'print' => [
        'title' => 'سیل ریٹرن',
        'heading' => 'سیلز ریٹرن / کریڈٹ نوٹ',
    ],
];
