<?php

return [
    'page_title' => 'ٹرانسپورٹ / گڈز کمپنیاں',
    'nav' => 'ٹرانسپورٹ / گڈز',
    'intro' => 'کوریئر کمپنیاں، گڈز کمپنیاں (اڈے) اور فریٹ والوں کو محفوظ کریں۔ یہ نہ سپلائر ہیں نہ کسٹمر۔',

    'fields' => [
        'code' => 'نمبر',
        'type' => 'کمپنی کی قسم',
        'status' => 'اسٹیٹس',
        'credit_limit' => 'کریڈٹ حد',
        'opening_balance' => 'ابتدائی بیلنس',
        'business_name_en' => 'بزنس کا نام (انگریزی)',
        'business_name_ur' => 'بزنس کا نام (اردو)',
        'country' => 'ملک',
        'country_ur' => 'ملک (اردو)',
        'city' => 'شہر',
        'city_ur' => 'شہر (اردو)',
        'address_en' => 'پتہ (انگریزی)',
        'address_ur' => 'پتہ (اردو)',
        'destination_business_name_en' => 'بزنس کا نام (انگریزی)',
        'destination_business_name_ur' => 'بزنس کا نام (اردو)',
        'destination_address_en' => 'پتہ (انگریزی)',
        'destination_address_ur' => 'پتہ (اردو)',
        'branch_number' => 'برانچ :number',
        'branch_address_en' => 'برانچ کا پتہ (انگریزی)',
        'branch_address_ur' => 'برانچ کا پتہ (اردو)',
        'route_from' => 'کہاں سے (شہر)',
        'route_to' => 'کہاں تک (شہر)',
        'contact_name_en' => 'رابطہ فرد (انگریزی)',
        'contact_name_ur' => 'رابطہ فرد (اردو)',
        'mobile_number' => 'موبائل نمبر',
        'bank_name_en' => 'بینک (انگریزی)',
        'bank_name_ur' => 'بینک (اردو)',
        'account_title' => 'اکاؤنٹ ٹائٹل',
        'account_title_ur' => 'اکاؤنٹ ٹائٹل (اردو)',
        'profile_photo' => 'پروفائل تصویر',
        'card_front' => 'کارڈ / بلٹی (سامنے)',
        'card_back' => 'کارڈ / بلٹی (پیچھے)',
    ],

    'sections' => [
        'destination' => 'ڈیسٹینیشن بزنس ایڈریس',
    ],

    'hints' => [
        'auto_on_save' => 'محفوظ کرنے پر خودکار بنے گا',
        'select_option' => 'منتخب کریں…',
        'city_manual' => 'شہر کا نام لکھیں',
        'opening_balance' => 'اگر آپ نے دینا ہے تو مثبت، اگر انہوں نے دینا ہے تو منفی۔',
        'branches' => 'مزید برانچز ایک لائن میں: پتہ (انگریزی/اردو)، رابطہ (انگریزی/اردو)، فون۔ + سے دوسرا شخص/فون، دوسرے + سے نئی برانچ۔',
        'destination_branches' => 'ڈیسٹینیشن برانچز ایک لائن میں: پتہ (انگریزی/اردو)، رابطہ (انگریزی/اردو)، فون۔ + سے دوسرا شخص/فون، دوسرے + سے نئی برانچ۔',
        'routes' => 'یہ کیریئر جن روٹس پر چلتا ہے (شہر سے شہر)۔ ایک یا زیادہ شامل کریں۔',
        'primary_contact_required' => 'ہیڈ آفس کے لیے کم از کم ایک رابطہ فرد موبائل نمبر کے ساتھ ضروری ہے۔',
        'bank_search' => 'بینک تلاش کریں…',
        'iban_or_account' => 'آئی بین یا اکاؤنٹ نمبر',
        'choose_file' => 'فائل منتخب کریں',
    ],

    'statuses' => [
        'active' => 'فعال',
        'inactive' => 'غیر فعال',
    ],

    'filters' => [
        'active' => 'فعال',
        'inactive' => 'غیر فعال',
    ],

    'actions' => [
        'add' => 'ٹرانسپورٹ / گڈز شامل کریں',
        'remove' => 'ہٹائیں',
        'add_branch' => 'برانچ شامل کریں',
        'remove_branch' => 'برانچ ہٹائیں',
        'add_route' => 'روٹ شامل کریں',
        'add_contact' => 'رابطہ شامل کریں',
        'add_bank' => 'بینک شامل کریں',
        'view_short' => 'دیکھیں',
        'edit_short' => 'ترمیم',
        'delete_short' => 'حذف',
        'delete_confirm' => 'کیا یہ ٹرانسپورٹ / گڈز کمپنی حذف کریں؟',
    ],

    'list' => [
        'search' => 'ٹرانسپورٹ / گڈز کمپنیاں تلاش کریں',
        'empty' => 'ابھی کوئی ٹرانسپورٹ / گڈز کمپنی نہیں۔',
        'col_code' => 'نمبر',
        'col_business' => 'بزنس کا نام',
        'col_contact' => 'رابطہ',
        'col_routes' => 'روٹس',
        'col_type' => 'قسم',
        'col_status' => 'اسٹیٹس',
        'col_created' => 'تاریخ',
        'col_actions' => 'اعمال',
    ],

    'validation' => [
        'business_name_required' => 'بزنس کا نام ضروری ہے (انگریزی یا اردو)۔',
        'contact_name_required' => 'رابطہ فرد کا نام ضروری ہے (انگریزی یا اردو)۔',
        'mobile_required' => 'موبائل نمبر ضروری ہے۔',
        'address_required' => 'پتہ ضروری ہے (انگریزی یا اردو)۔',
    ],

    'messages' => [
        'created' => ':name شامل کر دیا گیا ہے۔',
        'updated' => ':name اپ ڈیٹ ہو گیا ہے۔',
        'save_failed' => 'محفوظ نہیں ہو سکا۔ فارم چیک کر کے دوبارہ کوشش کریں۔',
        'ai_enriched' => 'AI نے خالی انگریزی/اردو خانے بھر دیے ہیں۔',
        'ai_failed' => 'AI درخواست مکمل نہ کر سکا۔ دوبارہ کوشش کریں۔',
    ],
];
