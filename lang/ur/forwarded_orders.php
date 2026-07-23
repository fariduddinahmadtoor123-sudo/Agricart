<?php

return [
    'list' => [
        'empty' => 'ابھی کچھ فارورڈ نہیں۔',
        'empty_hint' => 'جب آپ کیو سے آرڈر اسٹاف کو فارورڈ کریں گے، وہ یہاں ہسٹری میں نظر آئے گا۔',
        'no_results' => 'تلاش یا فلٹر کے مطابق کوئی فارورڈ نہیں۔',
        'search' => 'آرڈر، انوائس، یا اسٹاف تلاش…',
        'clear_filters' => 'فلٹر صاف کریں',
        'release_hint' => 'پیکنگ کے بعد Mark released دبائیں — آرڈر Logistics Pending میں چلا جائے گا۔',
        'col_order' => 'آرڈر نمبر',
        'col_invoice' => 'انوائس نمبر',
        'col_from' => 'فارورڈ کیا',
        'col_to' => 'فارورڈ کیا گیا',
        'col_status' => 'حالت',
        'col_actions' => 'اقدامات',
        'status_open' => 'زیرِ انتظار',
        'status_released' => 'ریلیز',
    ],

    'actions' => [
        'mark_released' => 'ریلیز کریں',
        'open_packing' => 'پیکنگ کھولیں',
        'release_confirm' => 'اس فارورڈ کو ریلیز کر کے Logistics Pending میں بھیجیں؟',
    ],

    'filters' => [
        'from' => 'فارورڈ کیا',
        'to' => 'فارورڈ کیا گیا',
        'active_from' => 'منجانب: :name',
        'active_to' => 'بطرف: :name',
    ],

    'validation' => [
        'cannot_release_declined' => 'مسترد تفویض کو ریلیز نہیں کیا جا سکتا۔',
    ],

    'messages' => [
        'released' => 'ریلیز ہو گیا — اب لاجسٹکس زیر التوا میں۔',
        'release_denied' => 'ریلیز کی اجازت نہیں۔',
        'release_failed' => 'ریلیز نہیں ہو سکا۔',
    ],
];
