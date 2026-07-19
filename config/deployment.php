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
    'queue_worker_configured' => filter_var(
        env('QUEUE_WORKER_CONFIGURED', false),
        FILTER_VALIDATE_BOOLEAN,
    ),
    'healthcheck_url' => env('HEALTHCHECK_URL'),
];
