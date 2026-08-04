<?php

return [
    'defaults' => [
        'offline_message' => 'Wait for some time',
        'offline_message_ur' => 'تھوڑی دیر انتظار کریں',
        'banner_off_en' => 'Online ordering is temporarily unavailable. You can still browse our catalog.',
        'banner_off_ur' => 'آن لائن آرڈرنگ عارضی بند ہے۔ آپ مصنوعات دیکھ سکتے ہیں۔',
        'maintenance_message' => 'We are performing scheduled maintenance. Please check back soon.',
        'maintenance_message_ur' => 'سائٹ کی دیکھ بھال جاری ہے۔ تھوڑی دیر بعد دوبارہ آئیں۔',
    ],

    'status' => [
        'on' => 'ON',
        'off' => 'OFF',
        'badge_on' => 'سٹور ON',
        'badge_off' => 'سٹور OFF',
    ],

    'actions' => [
        'add' => 'سیلز چینل شامل کریں',
        'toggle_store' => 'چینل آن یا آف',
        'toggle_maintenance' => 'مینٹیننس موڈ آن یا آف',
        'open_store' => 'سٹور کھولیں',
        'edit_messages' => 'چینل پیغامات',
        'edit_messages_short' => 'پیغامات',
        'save' => 'محفوظ',
    ],

    'list' => [
        'col_name' => 'چینل',
        'col_status' => 'سٹور اسٹیٹس',
        'col_maintenance' => 'مینٹیننس',
        'col_actions' => 'عمل',
        'empty' => 'ابھی کوئی سیلز چینل نہیں۔',
    ],

    'filters' => [
        'on' => 'On',
        'off' => 'Off',
    ],

    'fields' => [
        'channel' => 'سیلز چینل',
        'messages' => 'بند ہونے پر پیغامات',
        'messages_off_section' => 'سٹور OFF — صرف براؤز (بغیر ریٹ)',
        'offline_message_en' => 'پروڈکٹ لائن پیغام (انگریزی)',
        'offline_message_ur' => 'پروڈکٹ لائن پیغام (اردو)',
        'banner_message_en' => 'سائٹ بینر (انگریزی)',
        'banner_message_ur' => 'سائٹ بینر (اردو)',
        'maintenance_messages' => 'مینٹیننس — پوری سائٹ بند',
        'maintenance_message_en' => 'مینٹیننس پیغام (انگریزی)',
        'maintenance_message_ur' => 'مینٹیننس پیغام (اردو)',
    ],

    'maintenance' => [
        'page_title' => 'دیکھ بھال جاری ہے',
        'badge' => 'مینٹیننس',
        'badge_on' => 'مینٹیننس ON',
        'badge_off' => 'Site open',
        'switch_on' => 'ON',
        'switch_off' => 'OFF',
    ],

    'messages' => [
        'saved' => 'سیلز چینل ترتیبات محفوظ ہو گئیں۔',
        'store_enabled' => 'آن لائن اسٹور live ہے۔ کسٹمر دوبارہ آرڈر کر سکتے ہیں۔',
        'store_disabled' => 'آن لائن اسٹور بند ہے۔ قیمتیں اور چیک آؤٹ چھپ گئے ہیں۔',
        'facebook_enabled' => 'فیس بک کیٹلاگ فیڈ ON ہے۔ Meta آپ کا پروڈکٹ فیڈ URL کھینچ سکتا ہے۔',
        'facebook_disabled' => 'فیس بک کیٹلاگ فیڈ OFF ہے۔ فیڈ URL نہیں ملے گی۔',
        'maintenance_enabled' => 'مینٹیننس آن ہے۔ عوامی سٹور لنک بند ہے۔',
        'maintenance_disabled' => 'مینٹیننس بند۔ زائر دوبارہ سٹور کھول سکتے ہیں۔',
    ],

    'online_store' => [
        'title' => 'آن لائن اسٹور (اپنی ویب سائٹ)',
        'lead' => 'ایک سوئچ سے سٹور پر کارٹ، چیک آؤٹ اور ریٹس کنٹرول ہوتے ہیں۔',
        'lead_short' => 'نیچے سوئچ یا بٹن استعمال کریں — محفوظ کے بعد فوراً سٹور پر اثر ہوگا۔',
        'behavior_hint' => 'بند ہونے پر: زائر کیٹگری اور پروڈکٹ دیکھ سکتے ہیں؛ ریٹ نہیں؛ ہر پروڈکٹ کے نیچے آپ کا پیغام۔ کارٹ/چیک آؤٹ بند۔ ایڈمن سیلز اور ڈسپیچ متاثر نہیں۔',
    ],

    'facebook' => [
        'section_title' => 'فیس بک اور انسٹاگرام (میٹا کیٹلاگ)',
        'edit_title' => 'فیس بک اور انسٹاگرام کیٹلاگ سیٹ اپ',
        'setup_short' => 'سیٹ اپ',
        'open_page' => 'فیس بک پیج کھولیں',
        'lead' => 'ایک ہی Meta Catalog فیڈ فیس بک اور انسٹاگرام دونوں چلاتی ہے۔ فیڈ URL Commerce Manager میں چسپاں کریں۔ کلک پر Agricart سائٹ کھلتی ہے — خریداری آپ کے سٹور پر۔',
        'page_url' => 'فیس بک پیج URL (اختیاری نوٹ)',
        'page_url_hint' => 'صرف آپ کے حوالے کے لیے۔ صرف یہ ایڈریس لکھنے سے پروڈکٹس sync نہیں ہوتیں — Meta کو نیچے والا فیڈ URL کھینچنا ہوگا۔',
        'price_field' => 'کون سی قیمت بھیجیں',
        'price_wholesale' => 'ہول سیل (آن لائن اسٹور جیسی)',
        'price_retail' => 'ریٹیل',
        'feed_url' => 'کیٹلاگ فیڈ URL (Meta میں کاپی کریں)',
        'feed_url_hint' => 'Meta Commerce Manager → Catalog → Data sources → Scheduled feed → یہ URL چسپاں کریں۔ ٹوکن خفیہ رکھیں۔',
        'feed_token' => 'فیڈ سیکیورٹی ٹوکن',
        'regenerate_token' => 'نیا ٹوکن',
        'token_regenerated' => 'نیا فیڈ ٹوکن بن گیا۔ محفوظ کریں، پھر Meta میں URL اپڈیٹ کریں۔',
        'feed_status_title' => 'فیڈ سٹیٹس (پروڈکٹس کیوں غائب ہو سکتی ہیں)',
        'feed_status' => 'چینل :enabled · :active میں سے :eligible پروڈکٹس فیڈ میں ہیں۔ :missing_price کی تصویر ہے مگر wholesale/retail قیمت نہیں (وہ چھوٹ جاتی ہیں)۔',
        'setup_title' => 'فیس بک — Sync کیسے کریں (ایک بار)',
        'step_1' => 'Sales Channels فہرست میں اس چینل کو ON کریں۔',
        'step_2' => 'اس فارم کو محفوظ کریں تاکہ فیڈ URL تیار ہو۔',
        'step_3' => 'Meta Business Suite → Commerce Manager → Catalog بنائیں یا کھولیں۔',
        'step_4' => 'Scheduled feed ڈیٹا سورس شامل کریں اور اوپر والا Catalog feed URL چسپاں کریں (گھنٹہ وار یا روزانہ)۔',
        'step_5' => 'Catalog کو اپنی Facebook Page سے جوڑیں؛ ویب سائٹ چیک آؤٹ / شاپ لنکس agricart.pk پروڈکٹ صفحات پر رہیں۔',
        'instagram_title' => 'انسٹاگرام — وہی پروڈکٹس (دوسری فیڈ نہیں)',
        'instagram_lead' => 'Instagram Shopping اسی Meta Catalog سے چلتا ہے۔ Agricart میں الگ Instagram sync URL نہیں لگانی۔',
        'instagram_step_1' => 'اپنا Instagram professional اکاؤنٹ اسی Meta Business سے جوڑیں جس میں Catalog ہے۔',
        'instagram_step_2' => 'Commerce Manager میں یہ Catalog Instagram سے connect کریں (Shopping / website checkout)۔',
        'instagram_step_3' => 'جب Catalog میں پروڈکٹس آ جائیں تو Instagram پر دکھ سکتی ہیں؛ ٹیپ پر Agricart پروڈکٹ پیج کھلے گی۔',
        'eligibility_hint' => 'صرف active پروڈکٹس جن کی تصویر اور قیمت (wholesale یا retail) ہو شامل ہوتی ہیں۔ قیمت Price Update یا پروڈکٹ ایڈٹ سے لگائیں، پھر Meta کی اگلی feed pull کا انتظار کریں (یا Commerce Manager میں refresh)۔',
    ],

    'control' => [
        'what_happens' => 'بند ہونے پر کیا ہوتا ہے؟',
        'saved_state' => 'سرور پر محفوظ',
        'preview_hint' => 'OFF کے بعد سٹور کھولیں اور refresh کریں — بینر، بغیر ریٹ، بغیر کارٹ۔',
    ],

    'hints' => [
        'offline_message' => 'قیمت کی جگہ ہر پروڈکٹ کے نیچے دکھایا جائے گا۔',
        'banner_message' => 'جب سٹور بند ہو تو ہر صفحے کے اوپر بینر۔',
        'maintenance_message' => 'مینٹیننس آن ہونے پر مکمل صفحہ۔ انگریزی/اردو زائر کو اپنی زبان کا پیغام۔ خالی چھوڑیں تو ڈیفالٹ۔',
        'no_toggle_permission' => 'آپ دیکھ سکتے ہیں لیکن on/off نہیں بدل سکتے۔ Channels Hub edit اجازت چاہیے۔',
        'no_edit_permission' => 'پیغامات نہیں بدل سکتے۔ Channels Hub edit اجازت چاہیے۔',
    ],

    'placeholders' => [
        'offline_message' => 'تھوڑی دیر انتظار کریں',
        'maintenance_message' => 'We are performing scheduled maintenance. Please check back soon.',
        'maintenance_message_ur' => 'سائٹ کی دیکھ بھال جاری ہے۔ تھوڑی دیر بعد دوبارہ آئیں۔',
    ],

    'future' => [
        'title' => 'مزید چینلز (بعد میں)',
        'lead' => 'Daraz، Alibaba وغیرہ یہاں الگ on/off اور sync کے ساتھ آئیں گے۔',
        'coming_soon' => 'بعد میں',
    ],
];
