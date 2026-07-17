<?php

return [
    'actions' => [
        'add' => 'سپلائر شامل کریں',
        'edit_short' => 'ترمیم',
        'view_short' => 'دیکھیں',
        'delete_short' => 'حذف',
        'delete_confirm' => 'یہ سپلائر حذف کریں؟',
        'remove' => 'ہٹائیں',
        'add_contact' => 'رابطہ شامل کریں',
        'add_bank' => 'بینک اکاؤنٹ شامل کریں',
    ],

    'sections' => [
        'business' => 'کاروباری معلومات',
        'categories' => 'سپلائی کیٹگریز',
        'contacts' => 'رابطے',
        'banks' => 'بینک اکاؤنٹس',
        'portal' => 'پورٹل رسائی',
        'documents' => 'دستاویزات',
    ],

    'fields' => [
        'supplier_code' => 'سپلائر کوڈ',
        'supplier_type' => 'سپلائر کی قسم',
        'status' => 'حیثیت',
        'country' => 'ملک',
        'country_ur' => 'ملک (اردو)',
        'city' => 'شہر',
        'city_en' => 'شہر (انگلش)',
        'city_ur' => 'شہر (اردو)',
        'address_en' => 'مکمل پتہ',
        'address_ur' => 'پتہ (اردو)',
        'business_name_en' => 'کاروباری نام',
        'business_name_ur' => 'کاروباری نام (اردو)',
        'contact_name_en' => 'رابطہ نام',
        'contact_name_ur' => 'رابطہ نام (اردو)',
        'mobile_number' => 'موبائل نمبر',
        'credit_limit' => 'کریڈٹ لمٹ',
        'opening_balance' => 'افتتاحی بیلنس',
        'categories' => 'کیٹگریز',
        'bank_name' => 'بینک کا نام',
        'bank_name_en' => 'بینک کا نام (انگلش)',
        'bank_name_ur' => 'بینک کا نام (اردو)',
        'branch_name' => 'برانچ کا نام',
        'account_title' => 'اکاؤنٹ ٹائٹل',
        'account_title_ur' => 'اکاؤنٹ ٹائٹل (اردو)',
        'account_number' => 'آئی بی اے این / اکاؤنٹ نمبر',
        'portal_enabled' => 'سپلائر پورٹل لاگ ان کی اجازت',
        'email' => 'ای میل',
        'password' => 'پاسورڈ',
        'password_confirm' => 'پاسورڈ کی تصدیق',
        'profile_photo' => 'پروفائل فوٹو',
        'card_front' => 'کارڈ سامنے',
        'card_back' => 'کارڈ پیچھے',
    ],

    'hints' => [
        'auto_on_save' => 'محفوظ ہونے پر خود بخود بنے گا',
        'select_option' => 'ایک آپشن منتخب کریں',
        'category_search' => 'ٹیگ کرنے کے لیے کیٹگری تلاش کریں…',
        'no_categories' => 'کوئی مماثل کیٹگری نہیں۔',
        'bank_search' => 'انگلش، اردو یا کوڈ (ایچ بی ایل، یو بی ایل، این بی پی) سے بینک تلاش کریں…',
        'iban_or_account' => 'آئی بی اے این یا اکاؤنٹ نمبر',
        'choose_file' => 'کوئی فائل نہیں',
        'portal_later' => 'ابھی اختیاری۔ فعال ہونے پر سپلائر بعد میں لاگ ان کر کے لیجر وغیرہ دیکھ سکے گا۔',
        'city_manual' => 'شہر کا نام لکھیں',
        'address_ur_panel' => 'اردو پتہ (جہاں ممکن ہو خود بھر جائے گا)',
        'primary_contact_required' => 'پرائمری رابطہ کا نام اور موبائل نمبر ضروری ہیں (* والے فیلڈز)۔',
    ],

    'statuses' => [
        'active' => 'فعال',
        'inactive' => 'غیر فعال',
    ],

    'messages' => [
        'created' => 'سپلائر :name کامیابی سے محفوظ ہو گیا۔',
        'updated' => 'سپلائر :name اپ ڈیٹ ہو گیا۔',
        'save_failed' => 'سپلائر محفوظ نہیں ہو سکا۔ فارم چیک کر کے دوبارہ کوشش کریں۔',
    ],

    'validation' => [
        'contact_required' => 'کم از کم ایک رابطہ موبائل نمبر کے ساتھ شامل کریں۔',
        'mobile_required' => 'پہلے رابطے کا موبائل نمبر ضروری ہے۔',
        'contact_name_required' => 'پہلے رابطے کا نام انگلش یا اردو میں لکھیں۔',
    ],

    'ai' => [
        'no_configuration' => 'فعال AI کنفیگریشن نہیں۔ Settings → AI میں شامل کریں۔',
        'input_required' => 'AI استعمال کرنے سے پہلے کم از کم ایک انگلش یا اردو فیلڈ بھریں۔',
        'invalid_json' => 'AI نے غلط جواب دیا۔ دوبارہ کوشش کریں۔',
        'enriched' => 'خالی انگلش اور اردو فیلڈز بھر دی گئیں۔ دیکھ کر محفوظ کریں۔',
        'prompt' => 'You are completing a supplier profile for an agriculture and hardware retailer in Pakistan. Current form data (JSON):\n:payload',
    ],

    'list' => [
        'empty' => 'ابھی کوئی سپلائر نہیں۔ پہلا سپلائر شامل کرنے کے لیے سپلائر شامل کریں پر کلک کریں۔',
        'col_code' => 'کوڈ',
        'col_business' => 'کاروبار',
        'col_contact' => 'رابطہ',
        'col_city' => 'شہر',
        'col_type' => 'قسم',
        'col_status' => 'حیثیت',
        'col_categories' => 'کیٹگریز',
        'col_created' => 'تاریخ',
        'col_actions' => 'عمل',
    ],

    'filters' => [
        'active' => 'فعال سپلائرز',
        'inactive' => 'غیر فعال سپلائرز',
        'by_category' => 'کیٹگری والے',
        'by_city' => 'شہر کے لحاظ سے',
    ],
];
