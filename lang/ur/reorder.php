<?php

return [
    'tabs' => [
        'reorder' => 'ری آرڈر لسٹ',
        'queue' => 'آرڈر کیو',
    ],

    'filters' => [
        'available' => 'بھیجنے کے لیے تیار',
        'in_queue' => 'کیو میں موجود',
        'repeated' => 'بار بار بھیجی (نہ آئی)',
        'all' => 'تمام کم اسٹاک',
        'pending' => 'کیو میں زیر التوا',
        'stale' => 'پرانی (7+ دن)',
    ],

    'actions' => [
        'send_to_queue' => 'کیو میں بھیجیں',
        'print' => 'شیٹ پرنٹ',
        'print_short' => 'پرنٹ',
        'select_all' => 'سب منتخب',
    ],

    'toolbar' => [
        'search' => 'پروڈکٹس تلاش کریں…',
    ],

    'columns' => [
        'sr' => 'نمبر',
        'thumb' => 'لوگو',
        'product_name' => 'پروڈکٹ کا نام',
        'required_qty' => 'درکار مقدار',
        'previous_price' => 'پچھلی قیمت',
        'current_price' => 'موجودہ قیمت',
        'status' => 'حالت',
        'batch' => 'بیچ',
        'queued_at' => 'کیو میں',
        'send_count' => 'کتنی بار بھیجی',
    ],

    'status' => [
        'available' => 'تیار',
        'in_queue' => 'کیو میں',
        'repeated' => ':count بار بھیجی',
        'pending' => 'زیر التوا',
        'stale' => 'پرانا',
    ],

    'messages' => [
        'empty_reorder' => 'ابھی کسی پروڈکٹ کو ری آرڈر کی ضرورت نہیں۔',
        'empty_queue' => 'آرڈر کیو خالی ہے۔',
        'sent_to_queue' => ':count آئٹم کیو میں بھیجے گئے (بیچ :number)۔',
        'nothing_to_send' => 'کوئی نیا آئٹم نہیں — منتخب پروڈکٹس پہلے سے کیو میں ہیں۔',
        'select_items' => 'کیو میں بھیجنے کے لیے کم از کم ایک پروڈکٹ منتخب کریں۔',
    ],

    'print' => [
        'title' => 'ری آرڈر شیٹ',
        'heading' => 'ری آرڈر / ریٹ جمع شیٹ',
        'print_button' => 'پرنٹ',
        'close_button' => 'بند کریں',
        'layout_a4' => 'A4',
        'layout_receipt' => 'رول / رسید',
        'rate_hint' => 'خالی کالم میں موجودہ ریٹ لکھیں۔ یہ شیٹ صرف پرچیزر کے لیے ہے۔',
        'receipt' => [
            'required_qty' => 'درکار',
            'previous_price' => 'پچھلی',
            'current_price' => 'موجودہ',
        ],
    ],
];
