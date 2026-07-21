<?php

return [
    'actions' => [
        'view_short' => 'دیکھیں',
        'edit_short' => 'ترمیم',
        'print' => 'محفوظ اور انوائس پرنٹ',
        'print_short' => 'انوائس',
        'packing_slip' => 'پیکنگ لسٹ',
        'packing_slip_short' => 'پیکنگ',
        'forward_short' => 'فارورڈ',
        'send_to_queue' => 'کیو میں بھیجیں',
        'convert_to_invoice' => 'انوائس میں تبدیل کریں',
        'view_customer_receipt' => 'گاہک کی رسید دیکھیں',
        'verify_payment' => 'ادائیگی تصدیق کریں',
        'reject_payment' => 'مسترد',
    ],

    'forward' => [
        'eyebrow' => 'آرڈر فارورڈ',
        'close' => 'بند کریں',
        'document_label' => 'دستاویز',
        'document_packing' => 'پیکنگ لسٹ',
        'document_invoice' => 'انوائس',
        'suppliers_heading' => 'سپلائرز (پروڈکٹ کیٹگری)',
        'staff_heading' => 'اسٹاف',
        'suppliers_empty' => 'اس آرڈر کی کیٹگری سے کوئی فعال سپلائر نہیں ملا۔ Contacts میں سپلائر کو کیٹگری سے جوڑیں۔',
        'staff_empty' => 'کوئی فعال اسٹاف ممبر نہیں ملا۔',
        'no_phone' => 'فون نمبر نہیں',
        'no_email' => 'ای میل نہیں',
        'open' => 'کھولیں',
    ],

    'fields' => [
        'order_number' => 'آرڈر نمبر',
        'order_date' => 'آرڈر تاریخ',
        'order_status' => 'آرڈر سٹیٹس',
        'pos_invoice' => 'POS انوائس',
        'customer_payment_method' => 'گاہک نے بتایا',
        'customer_payment_reference' => 'ٹرانزیکشن حوالہ',
        'delivery_method' => 'ڈیلیوری طریقہ',
        'payment_proof_status' => 'رسید کی حیثیت',
        'customer_bank_receipt' => 'گاہک کی اپلوڈ شدہ رسید',
        'customer_notes' => 'گاہک کے نوٹس',
        'admin_notes' => 'ایڈمن نوٹس',
        'subtotal' => 'ذیلی کل',
        'total_amount' => 'کل رقم',
        'balance_due' => 'باقی واجب',
    ],

    'sections' => [
        'shipping' => 'ڈیلیوری',
        'customer_payment' => 'ادائیگی (گاہک نے بتائی)',
        'admin_payment' => 'ادائیگی کی تصدیق (ایڈمن)',
    ],

    'columns' => [
        'product_name' => 'پروڈکٹ',
        'brand' => 'برانڈ',
        'quantity' => 'مقدار',
        'unit_price' => 'یونٹ قیمت',
        'line_total' => 'رقم',
    ],

    'order_statuses' => [
        'pending' => 'ادائیگی کا انتظار',
        'payment_received' => 'ادائیگی موصول',
        'processing' => 'پروسیسنگ',
        'converted' => 'انوائس جاری',
        'cancelled' => 'منسوخ',
    ],

    'filters' => [
        'order' => 'آرڈر سٹیٹس',
        'queue' => 'کیو سٹیٹس',
        'active_order_status' => 'سٹیٹس: :status',
        'active_queue_status' => 'کیو: :status',
    ],

    'queue_statuses' => [
        'pending' => 'کیو کے لیے تیار',
        'queued' => 'کیو میں',
    ],

    'hints' => [
        'admin_notes' => 'اسٹاف کے لیے اندرونی نوٹس…',
        'admin_payment' => 'ادائیگی کی تصدیق کریں (طریقہ، سٹیٹس، رقم)۔ مکمل ہونے پر محفوظ کریں۔',
        'converted_locked' => 'یہ آرڈر مکمل POS سیل سے منسلک ہے — یہاں ترمیم نہیں ہو سکتی۔',
        'reject_payment_note' => 'گاہک کے لیے وجہ (اختیاری)',
        'pending_payment' => 'ادائیگی زیر التوا',
    ],

    'payment_proof_statuses' => [
        'none' => 'ضروری نہیں',
        'awaiting_receipt' => 'رسید کا انتظار',
        'submitted' => 'جمع — جائزے میں',
        'verified' => 'تصدیق شدہ',
        'rejected' => 'مسترد — دوبارہ بھیجیں',
    ],

    'list' => [
        'empty' => 'ابھی کوئی آن لائن آرڈر نہیں۔',
        'empty_hint' => 'آرڈرز سٹور پر چیک آؤٹ سے بنیں گے۔ ٹیسٹ کے لیے: php artisan online-orders:demo',
        'quotations_note' => 'یہ آن لائن کوٹیشنز ہیں (انوائس سے پہلے چیک آؤٹ)۔ ادائیگی تصدیق اور Convert into Invoice کے بعد یہ Online Sale Orders پر چلی جاتی ہیں۔',
        'quotations_empty' => 'ابھی کوئی آن لائن کوٹیشن نہیں۔',
        'quotations_empty_hint' => 'گاہک سٹور پر چیک آؤٹ مکمل کرے تو کوٹیشن یہاں آئے گی۔',
        'invoices_note' => 'ادا شدہ آن لائن انوائسز۔ سپلائر/اسٹاف فارورڈ اور لوجسٹکس کے لیے Send to Queue استعمال کریں۔',
        'invoices_empty' => 'ابھی کوئی آن لائن انوائس نہیں۔',
        'invoices_empty_hint' => 'Online Quotations سے ادائیگی تصدیق کے بعد Convert into Invoice کریں۔',
        'no_results' => 'تلاش یا فلٹر کے مطابق کوئی آرڈر نہیں۔',
        'clear_filters' => 'فلٹر صاف کریں',
        'col_number' => 'آرڈر #',
        'col_customer' => 'گاہک',
        'col_total' => 'کل',
        'col_order_status' => 'آرڈر سٹیٹس',
        'col_receipt_status' => 'گاہک کی رسید',
        'col_payment_status' => 'ادائیگی',
        'col_queue_status' => 'کیو',
        'col_customer_payment' => 'گاہک کی ادائیگی',
        'col_order_date' => 'آرڈر تاریخ',
        'col_created' => 'وقت',
        'col_actions' => 'عمل',
        'receipt_notified' => 'ادائیگی نوٹیفائی',
        'receipt_verified' => 'رسید تصدیق شدہ',
        'receipt_rejected' => 'رسید مسترد',
        'receipt_waiting' => 'رسید کا انتظار',
    ],

    'messages' => [
        'updated' => 'آرڈر :number اپ ڈیٹ ہو گیا۔',
        'converted_to_invoice' => 'کوٹیشن :number انوائس میں تبدیل ہو گئی۔',
        'sent_to_queue' => 'انوائس :number ڈسپیچ کیو میں بھیج دی گئی۔',
        'save_failed' => 'آرڈر محفوظ نہیں ہو سکا۔',
        'order_status_required' => 'آرڈر سٹیٹس منتخب کریں۔',
        'proof_not_submitted' => 'جائزے کے لیے کوئی رسید زیر التوا نہیں۔',
        'proof_verified' => 'گاہک کی ادائیگی تصدیق ہو گئی۔',
        'proof_rejected' => 'گاہک کی رسید مسترد کر دی گئی۔',
    ],

    'validation' => [
        'items_required' => 'کم از کم ایک پروڈکٹ ضروری ہے۔',
        'converted_locked' => 'یہ آرڈر پہلے ہی سیل میں تبدیل ہو چکا ہے۔',
        'fulfillment_requires_invoice' => 'فلфилمنٹ صرف Convert into Invoice کے بعد اپ ڈیٹ ہو سکتی ہے۔',
        'fulfillment_requires_queue' => 'فلфилمنٹ صرف Send to Queue کے بعد اپ ڈیٹ ہو سکتی ہے۔',
        'queue_requires_invoice' => 'صرف کنورٹ شدہ انوائسز ڈسپیچ کیو میں بھیجی جا سکتی ہیں۔',
        'amount_paid_exceeds_total' => 'ادا شدہ رقم کل سے زیادہ نہیں ہو سکتی۔',
    ],

    'print' => [
        'title' => 'آن لائن آرڈر',
        'heading' => 'آن لائن سیل آرڈر',
        'ship_to' => 'شپنگ پتہ',
        'appendix_heading' => 'آرڈر سٹیٹس',
    ],
];
