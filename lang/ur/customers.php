<?php

return [
    'actions' => [
        'add' => 'گاہک شامل کریں',
        'edit_short' => 'ترمیم',
        'view_short' => 'دیکھیں',
        'delete_short' => 'حذف',
        'delete_confirm' => 'یہ گاہک حذف کریں؟',
        'remove' => 'ہٹائیں',
        'add_phone' => 'فون شامل کریں',
        'export_archive' => 'آرکائیو ایکسپورٹ',
        'export_all' => 'سب ایکسپورٹ',
        'export_online' => 'آن لائن ایکسپورٹ',
        'export_offline' => 'آف لائن ایکسپورٹ',
        'import_archive' => 'آرکائیو امپورٹ',
    ],

    'portable' => [
        'hint' => 'انکرپٹڈ .ags پیکج۔ فائل نام میں customers + اختیاری online/offline + آج کی تاریخ۔ پاس ورڈ کبھی ایکسپورٹ نہیں؛ آن لائن امپورٹ پر رینڈم پاس ورڈ لگتا ہے — لاگ ان سے پہلے ری سیٹ درکار۔',
        'export_ok' => ':count گاہک ایکسپورٹ (:scope)۔',
        'import_ok' => 'امپورٹ مکمل: :created نئے، :updated اپڈیٹ، :skipped چھوڑے، :media میڈیا۔',
        'forbidden' => 'گاہک امپورٹ/ایکسپورٹ کی اجازت نہیں۔',
        'scope_all' => 'سب',
        'scope_online' => 'آن لائن',
        'scope_offline' => 'آف لائن',
    ],

    'sections' => [
        'online_account' => 'آن لائن رجسٹریشن',
        'account' => 'اکاؤنٹ اور حد',
        'documents' => 'تصویر',
        'bilingual' => 'تلاش کے لیے نام اور پتے',
    ],

    'channels' => [
        'online' => 'آن لائن',
        'offline' => 'آف لائن / ریٹیل',
    ],

    'fields' => [
        'customer_number' => 'گاہک نمبر',
        'display_name' => 'نام',
        'name_en' => 'نام (انگریزی)',
        'name_ur' => 'نام (اردو)',
        'phone' => 'فون',
        'address_quick' => 'پتہ',
        'address_en' => 'پتہ (انگریزی)',
        'address_ur' => 'پتہ (اردو)',
        'city' => 'شہر',
        'city_ur' => 'شہر (اردو)',
        'opening_balance' => 'ابتدائی بیلنس',
        'credit_limit' => 'کریڈٹ حد',
        'email' => 'ای میل',
        'profile_photo' => 'گاہک کی تصویر',
        'accepts_marketing' => 'پروموشن ای میل بھیجیں',
        'is_active' => 'حیثیت',
        'first_name' => 'پہلا نام',
        'last_name' => 'آخری نام',
        'address_line1' => 'پتہ لائن 1',
        'address_line2' => 'پتہ لائن 2',
        'state' => 'صوبہ',
        'postal_code' => 'پوسٹل کوڈ',
        'country' => 'ملک',
    ],

    'hints' => [
        'auto_on_save' => 'محفوظ پر خود بخود',
        'select_option' => 'منتخب کریں',
        'display_name' => 'انگریزی یا اردو — کاؤنٹر پر فوری اندراج',
        'phone' => '03xx xxxxxxx',
        'address_quick' => 'اختیاری مختصر پتہ',
        'email_optional' => 'اختیاری — پروموشن کے لیے',
        'bilingual_ai' => 'صرف نام اور فون سے محفوظ کریں۔ بعد میں AI چلائیں تاکہ انگریزی/اردو نام اور تلاش کے قابل پتے بھر جائیں۔',
        'opening_balance' => 'صرف ابتدائی لیجر رقم — لائیو بیلنس نہیں۔ موجودہ بیلنس Customer Ledger سے آتی ہے (ابتدائی + سیل − رسیدیں)۔',
    ],

    'statuses' => [
        'active' => 'فعال',
        'inactive' => 'غیر فعال',
    ],

    'filters' => [
        'online' => 'آن لائن گاہک',
        'offline' => 'آف لائن / ریٹیل',
        'active' => 'فعال',
        'inactive' => 'غیر فعال',
    ],

    'list' => [
        'empty' => 'ابھی کوئی گاہک نہیں۔ ریٹیل گاہک شامل کریں یا آن لائن رجسٹریشن کا انتظار کریں۔',
        'col_number' => 'گاہک نمبر',
        'col_name' => 'نام',
        'col_phone' => 'فون',
        'col_channel' => 'قسم',
        'col_city' => 'شہر',
        'col_balance' => 'ابتدائی بیلنس',
        'col_created' => 'شامل',
        'col_actions' => 'عمل',
    ],

    'messages' => [
        'created' => 'گاہک :name محفوظ ہو گیا۔',
        'updated' => 'گاہک :name اپ ڈیٹ ہو گیا۔',
        'save_failed' => 'گاہک محفوظ نہیں ہو سکا۔ فارم چیک کریں۔',
        'delete_blocked' => 'اس گاہک پر سیل، آن لائن آرڈر، ریٹرن یا فنڈ رسید موجود ہیں۔ حذف کی بجائے غیر فعال کریں۔',
    ],

    'validation' => [
        'phone_required' => 'کم از کم ایک فون نمبر درج کریں۔',
        'phone_duplicate' => 'یہ فون نمبر پہلے سے گاہک “:name” کے لیے محفوظ ہے۔',
        'phone_duplicate_same_form' => 'ایک ہی فون نمبر دو بار درج ہو گیا ہے۔',
    ],

    'ai' => [
        'no_configuration' => 'AI سیٹ نہیں۔ پہلے Settings میں AI ترتیب دیں۔',
        'input_required' => 'AI سے پہلے نام یا پتہ لکھیں۔',
        'invalid_json' => 'AI کا جواب درست نہیں۔ دوبارہ کوشش کریں۔',
        'enriched' => 'انگریزی اور اردو فیلڈز اپ ڈیٹ ہو گئیں۔',
        'failed' => 'AI ناکام۔ دوبارہ کوشش کریں۔',
        'prompt' => "آپ Agricart (پاکستان میں زرعی ریٹیل) کے گاہک ریکارڈ تلاش کے لیے ترتیب دے رہے ہیں۔\n\nگاہک ڈیٹا:\n:payload",
    ],
];
