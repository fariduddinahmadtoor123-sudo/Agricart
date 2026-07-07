<?php

return [
    'fields' => [
        'label_size' => 'لیبل سائز',
        'name_language' => 'نام کی زبان',
        'code_type' => 'سکین کوڈ',
        'show_store_name' => 'اسٹور کا نام',
        'show_product_name' => 'پروڈکٹ کا نام',
        'show_cost_price' => 'لاگت قیمت',
        'show_sale_price' => 'فروخت قیمت',
        'cost_price' => 'لاگت',
        'sale_price' => 'فروخت',
        'labels_qty' => 'کتنے لیبل',
        'add_product' => 'پروڈکٹ شامل کریں',
        'add_product_placeholder' => 'نام، کوڈ، SKU تلاش یا سکین…',
        'invoice_number' => 'خریداری انوائس سے لوڈ',
        'invoice_placeholder' => 'خریداری انوائس نمبر…',
    ],

    'locales' => [
        'en' => 'انگریزی',
        'ur' => 'اردو',
        'both' => 'انگریزی + اردو',
    ],

    'code_types' => [
        'barcode' => 'بارکوڈ',
        'qr' => 'QR کوڈ',
        'none' => 'کوڈ نہیں',
    ],

    'actions' => [
        'scan_add' => 'سکین / شامل',
        'load_invoice' => 'انوائس لوڈ',
        'print' => 'لیبل پرنٹ',
        'print_one' => 'پرنٹ',
        'add_to_queue' => 'قطار میں شامل',
        'remove' => 'ہٹائیں',
        'clear_all' => 'سب صاف',
    ],

    'panel' => [
        'title' => 'قیمت ٹیگز پرنٹ',
        'subtitle' => 'اپشنز منتخب کریں، قطار بنائیں، پھر پرنٹ کریں۔',
        'close' => 'بند کریں',
        'print_now' => 'اب پرنٹ کریں',
        'section_label' => 'لیبل آپشنز',
        'section_load' => 'پروڈکٹ شامل کریں',
        'section_queue' => 'پرنٹ قطار',
    ],

    'toolbar' => [
        'search' => 'پروڈکٹس تلاش…',
    ],

    'filters' => [
        'readiness' => 'لیبل کی تیاری',
        'ready_to_label' => 'لیبل کے لیے تیار',
        'missing_sale_price' => 'سیل پرائس نہیں',
        'missing_barcode' => 'بارکوڈ / SKU نہیں',
    ],

    'messages' => [
        'catalog_empty' => 'قیمت ٹیگز کے لیے کوئی پروڈکٹ نہیں ملی۔',
        'catalog_no_results' => 'آپ کی تلاش یا فلٹر سے کوئی پروڈکٹ نہیں ملی۔',
        'empty' => 'پروڈکٹ دستی شامل کریں یا خریداری انوائس لوڈ کریں۔',
        'queue_empty' => 'قطار خالی ہے۔ پروڈکٹ شامل کریں یا انوائس لوڈ کریں۔',
        'empty_queue' => 'پرنٹ سے پہلے کم از کم ایک پروڈکٹ شامل کریں۔',
        'queue_count' => 'قطار میں :count پروڈکٹ',
        'product_added' => 'پروڈکٹ قطار میں شامل ہو گئی۔',
        'product_not_found' => 'اس کوڈ یا تلاش سے کوئی پروڈکٹ نہیں ملا۔',
        'invoice_required' => 'خریداری انوائس نمبر لکھیں۔',
        'invoice_not_found' => 'خریداری انوائس نہیں ملی۔',
        'invoice_empty' => 'اس انوائس میں کوئی پروڈکٹ لائن نہیں۔',
        'invoice_loaded' => 'انوائس سے :count لائن(ز) لوڈ ہو گئیں۔',
    ],

    'print' => [
        'title' => 'قیمت ٹیگز',
        'print_button' => 'پرنٹ',
        'close_button' => 'بند کریں',
    ],
];
