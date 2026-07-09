<?php

return [
    'actions' => [
        'view_short' => 'دیکھیں',
        'edit_short' => 'ترمیم',
        'print' => 'محفوظ اور انوائس پرنٹ',
        'print_short' => 'انوائس',
        'packing_slip' => 'پیکنگ لسٹ',
        'packing_slip_short' => 'پیکنگ',
        'view_customer_receipt' => 'گاہک کی رسید دیکھیں',
        'verify_payment' => 'ادائیگی تصدیق کریں',
        'reject_payment' => 'مسترد',
    ],

    'fields' => [
        'order_number' => 'آرڈر نمبر',
        'order_date' => 'آرڈر تاریخ',
        'order_status' => 'آرڈر سٹیٹس',
        'pos_invoice' => 'POS انوائس',
        'customer_payment_method' => 'گاہک نے بتایا',
        'customer_payment_reference' => 'ٹرانزیکشن حوالہ',
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
        'converted' => 'سیل میں تبدیل',
        'cancelled' => 'منسوخ',
    ],

    'filters' => [
        'order' => 'آرڈر سٹیٹس',
        'active_order_status' => 'سٹیٹس: :status',
    ],

    'hints' => [
        'admin_notes' => 'اسٹاف کے لیے اندرونی نوٹس…',
        'admin_payment' => 'ادائیگی کی تصدیق کریں (طریقہ، سٹیٹس، رقم)۔ مکمل ہونے پر محفوظ کریں۔',
        'converted_locked' => 'یہ آرڈر مکمل POS سیل سے منسلک ہے — یہاں ترمیم نہیں ہو سکتی۔',
        'reject_payment_note' => 'گاہک کے لیے وجہ (اختیاری)',
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
        'no_results' => 'تلاش یا فلٹر کے مطابق کوئی آرڈر نہیں۔',
        'clear_filters' => 'فلٹر صاف کریں',
        'col_number' => 'آرڈر #',
        'col_customer' => 'گاہک',
        'col_total' => 'کل',
        'col_order_status' => 'آرڈر سٹیٹس',
        'col_payment_status' => 'ادائیگی (تصدیق شدہ)',
        'col_customer_payment' => 'گاہک کی ادائیگی',
        'col_order_date' => 'آرڈر تاریخ',
        'col_created' => 'وقت',
        'col_actions' => 'عمل',
    ],

    'messages' => [
        'updated' => 'آرڈر :number اپ ڈیٹ ہو گیا۔',
        'save_failed' => 'آرڈر محفوظ نہیں ہو سکا۔',
        'order_status_required' => 'آرڈر سٹیٹس منتخب کریں۔',
        'proof_not_submitted' => 'جائزے کے لیے کوئی رسید زیر التوا نہیں۔',
        'proof_verified' => 'گاہک کی ادائیگی تصدیق ہو گئی۔',
        'proof_rejected' => 'گاہک کی رسید مسترد کر دی گئی۔',
    ],

    'validation' => [
        'items_required' => 'کم از کم ایک پروڈکٹ ضروری ہے۔',
        'converted_locked' => 'یہ آرڈر پہلے ہی سیل میں تبدیل ہو چکا ہے۔',
        'amount_paid_exceeds_total' => 'ادا شدہ رقم کل سے زیادہ نہیں ہو سکتی۔',
    ],

    'print' => [
        'title' => 'آن لائن آرڈر',
        'heading' => 'آن لائن سیل آرڈر',
        'ship_to' => 'شپنگ پتہ',
    ],
];
