<?php

return [
    // Deprecated: payment proofs and CNIC documents are served only from the private
    // local disk. Authorized reads auto-migrate any legacy public original, then delete
    // the public copy. Keep this false; use the backfill commands for bulk cleanup.
    'legacy_public_payment_proof_fallback' => filter_var(
        env('SECURITY_LEGACY_PUBLIC_PAYMENT_PROOF_FALLBACK', false),
        FILTER_VALIDATE_BOOLEAN,
    ),

    'allow_wildcard_trusted_proxies' => filter_var(
        env('ALLOW_WILDCARD_TRUSTED_PROXIES', false),
        FILTER_VALIDATE_BOOLEAN,
    ),

    'headers' => [
        'csp_mode_explicit' => env('SECURITY_CSP_MODE') !== null,
        // NOTE: Hostinger injects a platform-level `Content-Security-Policy:
        // upgrade-insecure-requests` header that overrides any app-set
        // `Content-Security-Policy`. Enforce mode is therefore silently dropped
        // on that host, so we default to report-only (which uses the distinct
        // `Content-Security-Policy-Report-Only` header and coexists). Switch to
        // enforce only on infrastructure that does not override the header.
        'csp_mode' => env('SECURITY_CSP_MODE', 'report-only'),
        'csp' => env(
            'SECURITY_CSP',
            "default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'none'; form-action 'self'; img-src 'self' data: blob: https:; font-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://www.googletagmanager.com https://www.google-analytics.com https://connect.facebook.net; connect-src 'self' ws: wss: https:; frame-src 'self' https://www.googletagmanager.com https://www.google.com https://maps.google.com https://maps.google.com.pk https://www.openstreetmap.org https://openstreetmap.org;",
        ),
        'permissions_policy' => env(
            'SECURITY_PERMISSIONS_POLICY',
            'camera=(), microphone=(self), geolocation=(), payment=(), usb=()',
        ),
        'hsts_max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),
        'hsts_include_subdomains' => filter_var(env('SECURITY_HSTS_INCLUDE_SUBDOMAINS', true), FILTER_VALIDATE_BOOLEAN),
    ],
];
