<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', (string) config('security.headers.permissions_policy'));

        $cspHeader = config('security.headers.csp_mode', 'report-only') === 'enforce'
            ? 'Content-Security-Policy'
            : 'Content-Security-Policy-Report-Only';
        $response->headers->set($cspHeader, (string) config('security.headers.csp'));

        if (app()->environment('production') && $request->isSecure()) {
            $hsts = 'max-age='.(int) config('security.headers.hsts_max_age', 31536000);

            if (config('security.headers.hsts_include_subdomains', true)) {
                $hsts .= '; includeSubDomains';
            }

            $response->headers->set('Strict-Transport-Security', $hsts);
        }

        // Laravel's public-disk ServeFile defaults to private no-store. On Hostinger
        // that path is the fallback when the Apache mirror misses — allow long CDN/browser cache.
        if (
            $response->getStatusCode() === 200
            && $request->is('storage/*')
            && str_starts_with((string) $response->headers->get('Content-Type'), 'image/')
        ) {
            $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
        }

        return $response;
    }
}
