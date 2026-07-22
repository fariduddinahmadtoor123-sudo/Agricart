<?php

return [
    'trusted_proxies' => env('TRUSTED_PROXIES', ''),
    'allow_wildcard_trusted_proxies' => filter_var(
        env('ALLOW_WILDCARD_TRUSTED_PROXIES', false),
        FILTER_VALIDATE_BOOLEAN,
    ),
    'scheduler_shared_locks' => filter_var(
        env('SCHEDULER_SHARED_LOCKS', false),
        FILTER_VALIDATE_BOOLEAN,
    ),
    'scheduler_configured' => filter_var(
        env('SCHEDULER_CONFIGURED', false),
        FILTER_VALIDATE_BOOLEAN,
    ),
    /*
    | Shared hosting (Hostinger) often has no SSH crontab. When true, web
    | requests run `schedule:run` at most once per minute after the response.
    */
    'scheduler_web_tick' => filter_var(
        env('SCHEDULER_WEB_TICK', true),
        FILTER_VALIDATE_BOOLEAN,
    ),
    'scheduler_token' => env('SCHEDULER_TOKEN', ''),
    'queue_worker_configured' => filter_var(
        env('QUEUE_WORKER_CONFIGURED', false),
        FILTER_VALIDATE_BOOLEAN,
    ),
    'healthcheck_url' => env('HEALTHCHECK_URL'),
];
