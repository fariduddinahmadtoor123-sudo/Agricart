<?php

return [
    'actions' => [
        'mark_released' => 'مال نکال دیا / ریلیز',
        'release_confirm' => 'کیا یہ مال نکال کر لاجسٹکس کو دے دیا گیا؟',
    ],

    'list' => [
        'note' => 'وہ لوگ/سپلائر جنہیں فارورڈ ہو چکا۔ جب مال نکلے تو Mark released دبائیں — آرڈر لاجسٹکس → زیر التوا میں آئے گا جہاں بلٹی/ٹریکنگ لگے گی۔',
        'empty' => 'ابھی کچھ فارورڈ نہیں۔',
        'empty_hint' => 'آن لائن آرڈرز کیو سے پیکنگ یا انوائس فارورڈ کریں۔ ریلیز تک یہاں رہیں گے۔',
        'no_results' => 'تلاش کے مطابق کوئی وصول کنندہ نہیں۔',
        'search' => 'وصول کنندہ یا آرڈر نمبر تلاش...',
        'recipient_supplier' => 'سپلائر',
        'recipient_staff' => 'اسٹاف',
        'orders_count' => ':count آرڈر',
        'lines_count' => ':count لائن',
        'document_invoice' => 'انوائس',
        'document_packing' => 'پیکنگ لسٹ',
        'all_items' => 'آرڈر لائنز',
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
