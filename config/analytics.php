<?php

return [
    'enabled' => env('ANALYTICS_ENABLED', true),

    'hash_key' => env('ANALYTICS_HASH_KEY', env('APP_KEY')),

    'visitor_cookie' => 'agricart_visitor',
    'session_cookie' => 'agricart_visit',
    'visitor_cookie_minutes' => 60 * 24 * 395,
    'session_cookie_minutes' => 30,
    'live_window_minutes' => 5,

    'raw_retention_days' => (int) env('ANALYTICS_RAW_RETENTION_DAYS', 90),
    'visitor_retention_days' => (int) env('ANALYTICS_VISITOR_RETENTION_DAYS', 760),
    'rollup_retention_days' => (int) env('ANALYTICS_ROLLUP_RETENTION_DAYS', 1095),

    /*
    | Comma-separated public IPs or prefixes to never count as storefront visitors
    | (office / warehouse / your own ISP). Example: 203.0.113.10,39.40.
    */
    'exclude_ips' => array_values(array_filter(array_map(
        static fn (string $ip): string => trim($ip),
        explode(',', (string) env('ANALYTICS_EXCLUDE_IPS', '')),
    ))),

    'geoip_database' => env(
        'ANALYTICS_GEOIP_DATABASE',
        storage_path('app/private/geoip/GeoLite2-City.mmdb'),
    ),
    'maxmind_account_id' => env('MAXMIND_ACCOUNT_ID'),
    'maxmind_license_key' => env('MAXMIND_LICENSE_KEY'),
];
