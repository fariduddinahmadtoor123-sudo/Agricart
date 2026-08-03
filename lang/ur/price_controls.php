<?php

return [
    'actions' => [
        'add' => 'چینل ٹیئر شامل کریں',
        'edit' => 'ترمیم: :name',
        'edit_short' => 'ترمیم',
        'delete_short' => 'حذف',
    ],

    'fields' => [
        'name_en' => 'چینل کا نام (انگریزی)',
        'name_ur' => 'چینل کا نام (اردو)',
        'markup_percent' => 'خرید قیمت پر مارک اپ (%)',
        'product_field' => 'جنریٹ شدہ قیمت محفوظ کریں',
        'status' => 'حالت',
        'map_retail' => 'ریٹیل قیمت',
        'map_wholesale' => 'ہول سیل قیمت',
        'map_super_wholesale' => 'بلک قیمت',
        'map_distributor' => 'ڈسٹریبیوٹر قیمت',
        'map_custom' => 'اضافی چینل (:key)',
        'map_custom_slot' => 'اضافی چینل سلاٹ (JSON)',
    ],

    'hints' => [
        'form' => 'ہر سیل چینل کے لیے خرید قیمت پر کتنا مارک اپ لگانا ہے مقرر کریں۔ پرچیز انوائس پر ریٹیل اب بھی دستی ہوتی ہے۔',
        'form_retail' => 'ریٹیل مارک اپ Purchasing → Price Update پر استعمال ہوتا ہے (بائے پرائس → ریٹیل)۔ پرچیز میں ریٹیل sale price سے آتی ہے۔',
        'retail_field' => 'ریٹیل ہمیشہ پروڈکٹ کی ریٹیل قیمت فیلڈ پر نقش ہوتی ہے۔',
        'custom_field' => 'اضافی چینلز پروڈکٹ کے JSON فیلڈ میں محفوظ ہوتے ہیں تاکہ بعد میں مارکیٹ پلیس سنک ہو سکے۔',
        'example_preview' => 'مثال: خرید قیمت :base → چینل قیمت :price',
    ],

    'list' => [
        'intro' => 'پرچیز محفوظ کرتے وقت خرید قیمت اور ریٹیل درج کریں۔ دیگر چینلز نیچے والے مارک اپ سے بنتے ہیں۔ Price Update ریٹیل مارک اپ بھی استعمال کرتا ہے۔',
        'retail_note' => 'پرچیز پر ریٹیل دستی ہے۔ Price Update کے لیے ریٹیل مارک اپ % سیٹ کریں۔ دیگر ٹائرز خرید قیمت پر مارک اپ لگاتے ہیں۔',
        'empty' => 'ابھی کوئی چینل ٹیئر نہیں۔ پہلا ٹیئر بنانے کے لیے Add استعمال کریں۔',
        'col_name' => 'چینل',
        'col_markup' => 'مارک اپ %',
        'col_maps_to' => 'پروڈکٹ فیلڈ',
        'col_example' => 'مثال @ :base',
        'col_example_short' => 'مثال',
        'col_status' => 'حالت',
        'col_actions' => 'عمل',
        'manual_markup' => 'دستی',
        'manual_example' => 'پرچیز پر درج کریں (یا Price Update کے لیے % سیٹ کریں)',
        'retail_markup_note' => 'پرچیز: دستی · Price Update: یہ %',
        'retail_example_note' => 'Price Update شیٹ استعمال کرتی ہے',
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
        'created' => 'چینل ٹیئر محفوظ ہو گیا۔',
        'updated' => 'چینل ٹیئر اپ ڈیٹ ہو گیا۔',
        'save_failed' => 'چینل ٹیئر محفوظ نہیں ہو سکا۔',
        'delete_confirm' => 'چینل ٹیئر ":name" حذف کریں؟',
        'field_in_use' => 'یہ پروڈکٹ فیلڈ پہلے سے کسی دوسرے ٹیئر سے منسلک ہے۔',
        'cannot_delete_last_profile' => 'کم از کم ایک قیمت پروفائل ضروری ہے۔',
        'cannot_edit_retail' => 'ریٹیل چینل کے نام اور میپنگ فکس ہیں۔ Price Update کے لیے ریٹیل مارک اپ % ایڈٹ کر سکتے ہیں۔',
        'cannot_delete_retail' => 'ریٹیل قیمت کا ٹیئر حذف نہیں ہو سکتا۔',
        'retail_field_reserved' => 'ریٹیل قیمت پرچیز پر الگ سے منظم ہوتی ہے۔',
    ],
];
