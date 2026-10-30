<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'finnhub' => [
        'key' => env('FINNHUB_API_KEY'),
        'timeout' => (int) env('FINNHUB_TIMEOUT', 25),
        'retry' => (int) env('FINNHUB_RETRY', 2),
        'cache_ttl' => (int) env('FINNHUB_CALENDAR_CACHE_TTL', 3600),
    ],

    'fx_learn' => [
        'calendar_cache_ttl' => (int) env('FX_LEARN_CALENDAR_CACHE_TTL', 900),
        'calendar_near_ttl' => (int) env('FX_LEARN_CALENDAR_NEAR_TTL', 90),
    ],

];
