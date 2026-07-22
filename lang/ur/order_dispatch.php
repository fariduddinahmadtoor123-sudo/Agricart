<?php

return [
    'actions' => [
        'open' => 'کھولیں',
        'clear_staff' => 'اسٹاف فلٹر صاف',
        'clear_supplier' => 'سپلائر فلٹر صاف',
        'whatsapp' => 'واٹس ایپ',
        'email' => 'ای میل',
        'view_proof' => 'بلٹی / رسید دیکھیں',
        'packing_slip' => 'پیکنگ لسٹ',
    ],

    'sections' => [
        'fulfillment' => 'تکمیل اور تفویض',
        'suppliers' => 'سپلائر (پروڈکٹ کیٹگری کے مطابق)',
    ],

    'fields' => [
        'assigned_staff' => 'ذمہ دار اسٹاف',
        'fulfillment_status' => 'تکمیل کی صورتحال',
        'supplier_status' => 'سپلائر سٹیٹس',
        'carrier_name' => 'کورئیر / کیریئر',
        'tracking_number' => 'ٹریکنگ نمبر',
        'shipping_reference' => 'بلٹی / رسید نمبر',
        'shipping_proof' => 'بلٹی یا رسید (تصویر/PDF)',
        'supplier_notes' => 'سپلائر کے نوٹس',
        'acknowledged_at' => 'تسلیم',
        'shipped_at' => 'روانگی',
    ],

    'filters' => [
        'staff' => 'اسٹاف ممبر',
        'supplier' => 'سپلائر',
        'fulfillment' => 'تکمیل',
        'active_staff' => 'اسٹاف: :name',
        'active_supplier' => 'سپلائر: :name',
        'active_fulfillment' => 'سٹیٹس: :status',
    ],

    'hints' => [
        'all_staff' => 'تمام اسٹاف',
        'all_suppliers' => 'تمام سپلائر',
        'all_fulfillment' => 'تمام سٹیٹس',
        'supplier_manual' => 'محفوظ پر تفویض کے لیے سپلائر ٹک کریں۔ اسٹاف/سپلائر کو صرف پیکنگ لسٹ دیں (کسٹمر نام یا ریٹ نہیں)۔',
        'supplier_flow' => 'اسٹاف/سپلائر تفویض کریں، گودام کے لیے پیکنگ لسٹ پرنٹ کریں، پھر یہاں ٹریکنگ اور بلٹی/رسید درج کریں۔',
        'carrier_name' => 'مثلاً Leopards، TCS',
        'shipping_reference' => 'بلٹی / رسید نمبر',
        'supplier_notes' => 'سپلائر نے جو بتایا (اختیاری)',
        'no_suppliers' => 'اس آرڈر کی کیٹگری سے کوئی فعال سپلائر نہیں۔ Contacts میں سپلائر–کیٹگری لنک کریں۔',
    ],

    'validation' => [
        'shipped_proof_required' => 'روانگی کے لیے ٹریکنگ، بلٹی/رسید نمبر، یا فائل اپ لوڈ ضروری ہے۔',
        'select_items_to_forward' => 'فارورڈ کے لیے کم از کم ایک پروڈکٹ لائن منتخب کریں۔',
        'invalid_forward_items' => 'منتخب لائنز اس آرڈر سے تعلق نہیں رکھتیں۔',
        'items_already_forwarded' => 'منتخب لائنز پہلے ہی کسی سپلائر کو بھیجی جا چکی ہیں۔',
        'supplier_required' => 'فعال سپلائر منتخب کریں۔',
        'packing_for_suppliers' => 'سپلائرز کو بھیجنے کے لیے پیکنگ لسٹ منتخب کریں۔',
    ],

    'list' => [
        'queue_note' => 'Ready for Queue سے آنے والی انوائسز / پیکنگ۔ یہاں صرف وہ آرڈرز رہتے ہیں جنہیں ابھی فارورڈ کرنا باقی ہے۔ فارورڈ کے بعد Forwarded صفحے پر جائیں گے۔',
        'empty' => 'کیو میں کوئی آرڈر باقی نہیں (سب فارورڈ ہو چکے یا ابھی بھیجے نہیں گئے)۔',
        'empty_hint' => 'Ready for Queue سے بھیجیں، پھر یہاں سے سپلائر/اسٹاف کو فارورڈ کریں۔',
        'no_results' => 'تلاش یا فلٹر کے مطابق کوئی آرڈر نہیں۔',
        'col_fulfillment' => 'تکمیل',
        'col_staff' => 'اسٹاف',
        'col_suppliers' => 'سپلائر',
        'col_packing_progress' => 'پیکنگ',
        'packing_progress' => ':sent / :total لائنز',
    ],

    'fulfillment_statuses' => [
        'awaiting_assignment' => 'تفویض کا انتظار',
        'assigned' => 'اسٹاف کو تفویض',
        'sourcing' => 'سورسنگ / سپلائر',
        'picking_packing' => 'پیکنگ',
        'ready_to_ship' => 'شپ کے لیے تیار',
        'in_transit' => 'ٹرانزٹ میں',
        'out_for_delivery' => 'ڈیلیوری پر',
        'delivered' => 'پہنچ گیا',
        'on_hold' => 'ہولڈ',
    ],

    'supplier_statuses' => [
        'pending' => 'زیر التوا',
        'sent' => 'بھیج دیا',
        'acknowledged' => 'تسلیم',
        'shipped' => 'روانہ',
        'declined' => 'مسترد',
    ],

    'email' => [
        'subject' => 'آن لائن آرڈر :number',
    ],

    'messages' => [
        'packing_partial' => 'پیکنگ پیشرفت: :sent از :total لائنز بھیجیں · :remaining بقایا — آرڈر کیو میں رہے گا۔',
        'packing_complete' => 'تمام پروڈکٹ لائنز پیکنگ کے لیے بھیج دی گئیں۔ تکمیل کے لیے آرڈر کیو میں رہتا ہے۔',
        'supplier_intro' => 'السلام علیکم :supplier، براہ کرم آرڈر :number پورا کریں:',
        'invoice_intro' => 'السلام علیکم :name، آرڈر :number کی انوائس:',
        'invoice_total' => 'کل: :amount',
        'invoice_link' => 'انوائس کھولیں:',
        'ship_to' => 'شپنگ',
        'notes' => 'نوٹس',
    ],

    'packing_slip' => [
        'title' => 'پیکنگ لسٹ',
        'heading' => 'پیکنگ لسٹ — گودام / سپلائر',
        'to' => 'برائے: :name',
        'notice' => 'اوپر والے آرڈر نمبر کے لیے یہ اشیاء پیک کریں۔ کسٹمر کو یہ سلپ نہ دیں۔ کسٹمر کا نام، ریٹ اور ڈیلیوری پتہ نہیں دکھایا گیا۔',
        'footer' => 'پیک شدہ مال Agricart اسٹاف کو واپس دیں۔ ٹریکنگ اور بلٹی صرف آفس اسٹاف درج کرے گا۔',
        'packed_by' => 'ذمہ دار اسٹاف',
        'plain_intro' => 'براہ کرم آرڈر :number پیک کریں (صرف پروڈکٹ اور مقدار):',
        'plain_footer' => 'پیک شدہ آرڈر Agricart کو دیں۔ اس فہرست میں کسٹمر یا قیمت نہیں۔',
    ],
];
