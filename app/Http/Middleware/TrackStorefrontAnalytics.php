<?php

namespace App\Http\Middleware;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsTrafficFilter;
use App\Services\Analytics\VisitorAnalyticsTracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackStorefrontAnalytics
{
    public function __construct(
        private readonly VisitorAnalyticsTracker $tracker,
        private readonly AnalyticsContext $context,
        private readonly AnalyticsTrafficFilter $trafficFilter,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = hrtime(true);
        $response = $next($request);

        if (! $this->shouldTrack($request, $response)) {
            return $response;
        }

        $identity = $this->tracker->identity($request);
        $routeName = (string) $request->route()?->getName();
        $context = $this->context->values();

        if ($context['page_type'] === null) {
            $context['page_type'] = $this->pageType($routeName);
        }

        $payload = $this->tracker->payload(
            $request,
            $identity,
            $context,
            $routeName,
            $response->getStatusCode(),
            (int) round((hrtime(true) - $startedAt) / 1_000_000),
        );

        if ($payload['is_bot'] ?? false) {
            return $response;
        }

        $secure = $request->isSecure();
        $response->headers->setCookie(cookie(
            (string) config('analytics.visitor_cookie'),
            $identity['visitor_token'],
            (int) config('analytics.visitor_cookie_minutes'),
            '/',
            null,
            $secure,
            true,
            false,
            'lax',
        ));
        $response->headers->setCookie(cookie(
            (string) config('analytics.session_cookie'),
            $identity['session_token'],
            (int) config('analytics.session_cookie_minutes'),
            '/',
            null,
            $secure,
            true,
            false,
            'lax',
        ));

        defer(fn () => $this->tracker->record($payload));

        return $response;
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        if (! config('analytics.enabled') || ! $request->isMethod('GET')) {
            return false;
        }

        if ($this->trafficFilter->shouldSkip($request)) {
            return false;
        }

        if (
            str_contains(mb_strtolower((string) $request->headers->get('Purpose')), 'prefetch')
            || str_contains(mb_strtolower((string) $request->headers->get('Sec-Purpose')), 'prefetch')
        ) {
            return false;
        }

        $routeName = (string) $request->route()?->getName();

        if (
            $routeName === ''
            || in_array($routeName, ['store.search.fragment', 'store.search.suggest'], true)
            || (! str_starts_with($routeName, 'store.') && ! str_starts_with($routeName, 'auth.'))
        ) {
            return false;
        }

        $contentType = mb_strtolower((string) $response->headers->get('Content-Type'));

        return $response->isSuccessful() && str_contains($contentType, 'text/html');
    }

    private function pageType(string $routeName): string
    {
        return match (true) {
            $routeName === 'store.home' => 'home',
            $routeName === 'store.search' => 'search',
            $routeName === 'store.cart' => 'cart',
            $routeName === 'store.checkout.success' => 'checkout_success',
            str_starts_with($routeName, 'store.checkout') => 'checkout',
            str_starts_with($routeName, 'store.customer.account') => 'account',
            str_starts_with($routeName, 'auth.') => 'registration',
            default => 'other',
        };
    }
}
