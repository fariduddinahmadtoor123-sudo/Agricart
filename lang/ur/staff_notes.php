<?php

return [
    'form' => [
        'create_heading' => 'نیا نوٹ',
        'edit_heading' => 'نوٹ ترمیم',
        'lead' => 'اپنے یا ساتھی کے لیے سادہ یاد دہانی۔ مکمل ہونے پر Mark done کریں۔',
    ],

    'fields' => [
        'title' => 'عنوان',
        'body' => 'تفصیل (اختیاری)',
        'body_placeholder' => 'مثلاً: 25 تاریخ کو بجلی کا بل ادا کریں…',
        'assigned_to' => 'تفویض / فارورڈ',
        'assigned_none' => 'صرف میں (کوئی assignee نہیں)',
        'due_on' => 'آخری تاریخ (اختیاری)',
        'forward_to' => 'اسٹاف کو بھیجیں',
    ],

    'list' => [
        'heading' => 'آپ کے نوٹس',
        'lead' => 'نوٹس جو آپ نے بنائے یا آپ کو بھیجے گئے۔',
        'empty' => 'ابھی کوئی نوٹ نہیں۔ Add Note سے نیا نوٹ بنائیں۔',
        'search' => 'نوٹس تلاش…',
        'created_by' => 'منجانب :name',
        'assigned_to' => 'بطرف :name',
        'due' => 'آخری تاریخ :date',
        'done_by' => 'مکمل کیا :name',
    ],

    'statuses' => [
        'open' => 'کھلا',
        'done' => 'مکمل',
    ],

    'filters' => [
        'label' => 'دکھائیں',
        'open' => 'کھلا',
        'mine' => 'مجھے تفویض',
        'created' => 'میں نے بنائے',
        'done' => 'مکمل',
        'all' => 'سب',
    ],

    'actions' => [
        'add_note' => 'Add Note',
        'add' => 'نوٹ محفوظ کریں',
        'save' => 'تبدیلیاں محفوظ کریں',
        'cancel_edit' => 'ترمیم منسوخ',
        'cancel' => 'منسوخ',
        'view' => 'View',
        'mark_done' => 'مکمل نشان لگائیں',
        'reopen' => 'دوبارہ کھولیں',
        'edit' => 'ترمیم',
        'forward' => 'فارورڈ',
        'send_forward' => 'بھیجیں',
        'delete' => 'حذف',
        'delete_confirm' => 'یہ نوٹ حذف کریں؟',
    ],

    'errors' => [
        'title' => 'عنوان درج کریں (زیادہ سے زیادہ 200 حروف)۔',
        'assignee' => 'درست اسٹاف ممبر منتخب کریں۔',
        'forbidden' => 'آپ یہ نوٹ تبدیل نہیں کر سکتے۔',
    ],
];
