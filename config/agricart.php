<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Hijri icon catalog (visual mapping slots 1–4)
    |--------------------------------------------------------------------------
    |
    | Expand this list as real icon assets are added. Search matches English,
    | Urdu, and keyword fields.
    |
    */
    'hijri_icons' => [
        ['id' => 'muharram', 'name_en' => 'Muharram', 'name_ur' => 'محرم', 'keywords' => ['muharram', 'محرم', 'hijri', 'islamic month']],
        ['id' => 'safar', 'name_en' => 'Safar', 'name_ur' => 'صفر', 'keywords' => ['safar', 'صفر']],
        ['id' => 'rabi_al_awwal', 'name_en' => 'Rabi al-Awwal', 'name_ur' => 'ربیع الاول', 'keywords' => ['rabi', 'ربيع', 'awwal', 'اول']],
        ['id' => 'rabi_al_thani', 'name_en' => 'Rabi al-Thani', 'name_ur' => 'ربیع الثانی', 'keywords' => ['rabi', 'ربيع', 'thani', 'ثانی']],
        ['id' => 'jumada_al_awwal', 'name_en' => 'Jumada al-Awwal', 'name_ur' => 'جمادی الاول', 'keywords' => ['jumada', 'جمادی', 'awwal']],
        ['id' => 'jumada_al_thani', 'name_en' => 'Jumada al-Thani', 'name_ur' => 'جمادی الثانی', 'keywords' => ['jumada', 'جمادی', 'thani']],
        ['id' => 'rajab', 'name_en' => 'Rajab', 'name_ur' => 'رجب', 'keywords' => ['rajab', 'رجب']],
        ['id' => 'shaban', 'name_en' => 'Shaaban', 'name_ur' => 'شعبان', 'keywords' => ['shaban', 'shaaban', 'شعبان']],
        ['id' => 'ramadan', 'name_en' => 'Ramadan', 'name_ur' => 'رمضان', 'keywords' => ['ramadan', 'ramzan', 'رمضان', 'رمzan']],
        ['id' => 'shawwal', 'name_en' => 'Shawwal', 'name_ur' => 'شوال', 'keywords' => ['shawwal', 'شوال', 'eid']],
        ['id' => 'dhul_qadah', 'name_en' => 'Dhul Qadah', 'name_ur' => 'ذوالقعدہ', 'keywords' => ['dhul', 'qadah', 'ذوالقعدہ', 'zulqadah']],
        ['id' => 'dhul_hijjah', 'name_en' => 'Dhul Hijjah', 'name_ur' => 'ذوالحجہ', 'keywords' => ['dhul', 'hijjah', 'hajj', 'ذوالحجہ', 'حج']],
        ['id' => 'new_moon', 'name_en' => 'New Moon', 'name_ur' => 'نیا چاند', 'keywords' => ['new moon', 'crescent', 'چاند', 'hilal']],
        ['id' => 'full_moon', 'name_en' => 'Full Moon', 'name_ur' => 'بدر', 'keywords' => ['full moon', 'badr', 'بدر']],
        ['id' => 'eid_ul_fitr', 'name_en' => 'Eid ul-Fitr', 'name_ur' => 'عید الفطر', 'keywords' => ['eid', 'fitr', 'عید', 'فطر']],
        ['id' => 'eid_ul_adha', 'name_en' => 'Eid ul-Adha', 'name_ur' => 'عید الاضحی', 'keywords' => ['eid', 'adha', 'azha', 'قربانی', 'عید']],
    ],
    /*
    |--------------------------------------------------------------------------
    | Inventory
    |--------------------------------------------------------------------------
    |
    | allow_overselling: when true, sales and online cart may reduce stock below
    | zero (typical retail when purchase is recorded later). When false, POS and
    | store checkout block lines that exceed on-hand quantity.
    |
    */
    'inventory' => [
        'allow_overselling' => filter_var(env('AGRICART_ALLOW_OVERSELLING', true), FILTER_VALIDATE_BOOL),
    ],
];
