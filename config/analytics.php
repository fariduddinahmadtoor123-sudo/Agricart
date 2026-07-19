<?php

return [
    'enabled' => env('ANALYTICS_ENABLED', true),

    'hash_key' => env('ANALYTICS_HASH_KEY', env('APP_KEY')),

    'visitor_cookie' => 'agricart_visitor',
    'session_cookie' => 'agricart_visit',
    'opt_out_cookie' => 'agricart_analytics_opt_out',
    'visitor_cookie_minutes' => 60 * 24 * 395,
    'session_cookie_minutes' => 30,
    'live_window_minutes' => 5,

    'raw_retention_days' => (int) env('ANALYTICS_RAW_RETENTION_DAYS', 90),
    'visitor_retention_days' => (int) env('ANALYTICS_VISITOR_RETENTION_DAYS', 760),

    'geoip_database' => env(
        'ANALYTICS_GEOIP_DATABASE',
        storage_path('app/private/geoip/GeoLite2-City.mmdb'),
    ),
    'maxmind_account_id' => env('MAXMIND_ACCOUNT_ID'),
    'maxmind_license_key' => env('MAXMIND_LICENSE_KEY'),
];
