<?php

return [
    'intro' => 'بغیر پرچیز بنائے بائے پرائس اپڈیٹ کریں۔ خالی چینل پرائسز Price Channel کے پرسنٹ سے بھرتی ہیں؛ آپ ہر پروڈکٹ کی کوئی بھی پرائس یا مقدار خود بدل سکتے ہیں۔ سب پرائسز دوبارہ فارمولے سے بھرنے کے لیے “Markups” دبائیں۔',

    'toolbar' => [
        'search' => 'اپڈیٹ شدہ پروڈکٹس تلاش کریں…',
    ],

    'fields' => [
        'add_product' => 'پروڈکٹ شامل کریں',
        'add_product_placeholder' => 'نام، کوڈ، SKU، یا اسکین…',
        'load_category' => 'کیٹگری لوڈ کریں',
        'load_category_placeholder' => 'کسی بھی لیول کی کیٹگری تلاش کریں (اندرونی سب شامل)…',
    ],

    'list' => [
        'empty' => 'ابھی کوئی پرائس اپڈیٹ نہیں۔ Add دبا کر پرائسز اپڈیٹ کریں۔',
        'no_results' => 'تلاش سے کوئی اپڈیٹ شدہ پروڈکٹ نہیں ملی۔',
        'col_number' => '#',
        'col_thumb' => 'تصویر',
        'col_name' => 'پروڈکٹ',
        'col_code' => 'کوڈ',
        'col_buy' => 'بائے',
        'col_retail' => 'ریٹیل',
        'col_wholesale' => 'ہول سیل',
        'col_bulk' => 'بلک',
        'col_distributor' => 'ڈسٹ',
        'col_updated' => 'اپڈیٹ',
        'col_actions' => 'عمل',
    ],

    'headers' => [
        'sr' => '#',
        'thumb' => 'تصویر',
        'name' => 'پروڈکٹ',
        'buy' => 'بائے',
        'retail' => 'ریٹیل',
        'retail_qty' => 'ر.مقدار',
        'wholesale' => 'ہول',
        'wholesale_qty' => 'ہ.مقدار',
        'super_wholesale' => 'بلک',
        'super_wholesale_qty' => 'ب.مقدار',
        'distributor' => 'ڈسٹ',
        'distributor_qty' => 'ڈ.مقدار',
        'qty' => 'مقدار',
    ],

    'actions' => [
        'add' => 'پرائسز اپڈیٹ',
        'add_product' => 'شامل کریں',
        'update' => 'اپڈیٹ',
        'edit' => 'اپڈیٹ: :name',
        'remove' => 'ہٹائیں',
        'apply_markups' => 'مارک اپ',
        'load_category' => 'سب لوڈ',
        'save' => 'پرائسز محفوظ کریں',
        'saving' => 'محفوظ ہو رہا ہے…',
        'clear' => 'شیٹ صاف کریں',
    ],

    'history' => [
        'title' => 'حالیہ پرائس اپڈیٹس',
        'view_all' => 'ایکٹیویٹی لاگ',
        'line' => ':user نے :product اپڈیٹ کیا',
        'unknown_user' => 'نامعلوم صارف',
    ],

    'messages' => [
        'product_added' => 'پروڈکٹ شیٹ میں شامل ہو گئی۔',
        'already_on_sheet' => 'یہ پروڈکٹ پہلے سے شیٹ میں ہے۔',
        'product_not_found' => 'کوئی مماثل پروڈکٹ نہیں ملی۔',
        'category_not_found' => 'کوئی مماثل کیٹگری نہیں ملی۔',
        'category_empty' => '“:category” کے اندر (سب کیٹگریز سمیت) کوئی پروڈکٹ نہیں ملی۔',
        'category_loaded' => '“:category” سے :added شامل (:skipped پہلے سے شیٹ پر تھیں)۔',
        'category_truncated' => 'حد :limit پروڈکٹس — باقی کے لیے گہری سب کیٹگری لوڈ کریں۔',
        'cleared' => 'شیٹ صاف کر دی گئی۔',
        'empty_sheet' => 'پروڈکٹس شامل کریں، بائے پرائس لکھیں، پھر محفوظ کریں۔',
        'saved' => ':count پروڈکٹ(س) کی پرائسز محفوظ ہو گئیں۔ سٹاک نہیں بدلا۔',
        'save_failed' => 'پرائس اپڈیٹ محفوظ نہیں ہو سکیں۔',
        'buy_price_required' => 'صفر سے بڑی بائے پرائس درج کریں۔',
        'invalid_price' => ':field کی قیمت درست نہیں۔',
        'invalid_quantity' => 'ٹائر کوانٹٹی کم از کم 1 عدد ہونی چاہیے۔',
        'permission_denied' => 'آپ کو پرائس اپڈیٹ کی اجازت نہیں۔',
    ],

    'hints' => [
        'quantity_editable' => 'اس پروڈکٹ کی الگ مقدار۔ تبدیل کر کے محفوظ کریں — دوسری پروڈکٹس نہیں بدلیں گی۔',
        'price_editable' => 'اس پروڈکٹ کی پرائس آزادانہ بدلیں۔ جیسی محفوظ کریں گے ویسی رہے گی — مارک اپ سے بند نہیں۔',
        'buy_fills_empty' => 'بائے بدلنے سے صرف خالی پرائس سیلز بھرتی ہیں۔ موجودہ پرائسز رہتی ہیں جب تک آپ خود نہ بدلیں یا مارک اپ نہ دبائیں۔',
        'apply_markups' => 'اس قطار کی سب پرائسز بائے × Price Channel مارک اپ سے دوبارہ بھریں۔',
    ],
];
