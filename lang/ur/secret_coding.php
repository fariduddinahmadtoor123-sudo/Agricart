<?php

return [
    'actions' => [
        'add' => 'خفیہ کوڈنگ شامل کریں',
        'edit' => 'ترمیم: :name',
        'edit_short' => 'ترمیم',
        'delete_short' => 'حذف',
    ],

    'fields' => [
        'number' => 'پروفائل نمبر',
        'name_en' => 'نام (انگریزی)',
        'name_ur' => 'نام (اردو)',
        'status' => 'حالت',
        'notes' => 'نوٹس',
        'digit' => 'ہندسہ :digit',
    ],

    'sections' => [
        'digit_mapping' => 'ہندسوں کا نقشہ (0–9)',
    ],

    'hints' => [
        'scaffold' => 'یہ ابتدائی فارم ہے۔ مکمل خفیہ کوڈنگ منطق اگلے مرحلے میں ترتیب دی جائے گی۔',
        'digit_mapping' => 'ہر ہندسے کے لیے خفیہ علامت یا حرف مقرر کریں۔ مثال: 1 → A، 2 → B۔',
    ],

    'list' => [
        'empty' => 'ابھی کوئی خفیہ کوڈنگ پروفائل نہیں۔ پہلا پروفائل بنانے کے لیے Add استعمال کریں۔',
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
        'created' => 'خفیہ کوڈنگ پروفائل محفوظ ہو گئی۔',
        'updated' => 'خفیہ کوڈنگ پروفائل اپ ڈیٹ ہو گئی۔',
        'save_failed' => 'خفیہ کوڈنگ پروفائل محفوظ نہیں ہو سکی۔',
        'delete_confirm' => 'خفیہ کوڈنگ پروفائل ":name" حذف کریں؟',
    ],
];
