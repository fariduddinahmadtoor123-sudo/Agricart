<?php

return [

    'title' => 'سائن ان',

    'heading' => 'سائن ان',

    'actions' => [

        'register' => [
            'before' => 'یا',
            'label' => 'اکاؤنٹ بنائیں',
        ],

        'request_password_reset' => [
            'label' => 'پاس ورڈ بھول گئے؟',
        ],

    ],

    'form' => [

        'email' => [
            'label' => 'ای میل',
        ],

        'password' => [
            'label' => 'پاس ورڈ',
        ],

        'remember' => [
            'label' => 'مجھے یاد رکھیں',
        ],

        'actions' => [

            'authenticate' => [
                'label' => 'سائن ان',
            ],

        ],

    ],

    'messages' => [

        'failed' => 'یہ credentials درست نہیں۔',

    ],

    'notifications' => [

        'throttled' => [
            'title' => 'بہت زیادہ کوششیں',
            'body' => 'براہ کرم :seconds سیکنڈ بعد دوبارہ کوشش کریں۔',
        ],

    ],

];
