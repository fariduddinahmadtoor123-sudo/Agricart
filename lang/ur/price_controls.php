<?php

return [
    'actions' => [
        'add' => 'قیمت کنٹرول شامل کریں',
        'edit' => 'ترمیم: :name',
        'edit_short' => 'ترمیم',
        'delete_short' => 'حذف',
    ],

    'fields' => [
        'number' => 'کنٹرول نمبر',
        'name_en' => 'نام (انگریزی)',
        'name_ur' => 'نام (اردو)',
        'status' => 'حالت',
        'notes' => 'نوٹس',
    ],

    'hints' => [
        'scaffold' => 'یہ ابتدائی فارم ہے۔ تفصیلی قیمت کنٹرول قواعد اگلے مرحلے میں شامل ہوں گے۔',
    ],

    'list' => [
        'empty' => 'ابھی کوئی قیمت کنٹرول نہیں۔ پہلا ریکارڈ بنانے کے لیے Add استعمال کریں۔',
        'col_number' => 'نمبر',
        'col_name_en' => 'نام (EN)',
        'col_name_ur' => 'نام (UR)',
        'col_status' => 'حالت',
        'col_created' => 'تاریخ',
        'col_actions' => 'عمل',
    ],

    'statuses' => [
        'active' => 'فعال',
        'inactive' => 'غیر فعال',
    ],

    'filters' => [
        'active' => 'فعال',
        'inactive' => 'غیر فعال',
    ],

    'messages' => [
        'created' => 'قیمت کنٹرول محفوظ ہو گیا۔',
        'updated' => 'قیمت کنٹرول اپ ڈیٹ ہو گیا۔',
        'save_failed' => 'قیمت کنٹرول محفوظ نہیں ہو سکا۔',
        'delete_confirm' => 'قیمت کنٹرول ":name" حذف کریں؟',
    ],
];
