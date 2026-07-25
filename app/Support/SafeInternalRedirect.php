<?php

namespace App\Support;

/**
 * Validates storefront / auth redirect targets so they stay on-site.
 */
class SafeInternalRedirect
{
    public static function normalize(?string $url, ?string $fallback = null): ?string
    {
        if (! is_string($url) || trim($url) === '') {
            return $fallback;
        }

        $url = trim($url);

        if (str_contains($url, '\\') || preg_match('/[\x00-\x1F\x7F]/', $url)) {
            return $fallback;
        }

        if (str_starts_with($url, '//') || str_starts_with($url, '/\\')) {
            return $fallback;
        }

        if (str_starts_with($url, '/')) {
            if (preg_match('/[\x00-\x1F\x7F]/', rawurldecode($url))) {
                return $fallback;
            }

            if (parse_url($url, PHP_URL_SCHEME) !== null || parse_url($url, PHP_URL_HOST) !== null) {
                return $fallback;
            }

            return $url;
        }

        $parts = parse_url($url);

        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return $fallback;
        }

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $requestHost = request()->getHost();

        $allowedHosts = array_values(array_unique(array_filter([
            is_string($appHost) ? strtolower($appHost) : null,
            is_string($requestHost) ? strtolower($requestHost) : null,
        ])));

        if ($allowedHosts === [] || ! in_array(strtolower((string) $parts['host']), $allowedHosts, true)) {
            return $fallback;
        }

        $path = $parts['path'] ?? '/';

        if ($path === '' || ! str_starts_with($path, '/')) {
            $path = '/'.$path;
        }

        if (isset($parts['query']) && $parts['query'] !== '') {
            $path .= '?'.$parts['query'];
        }

        if (isset($parts['fragment']) && $parts['fragment'] !== '') {
            $path .= '#'.$parts['fragment'];
        }

        return $path;
    }

    public static function putIntended(?string $url): void
    {
        $path = self::normalize($url);

        if ($path !== null) {
            session()->put('url.intended', $path);
        }
    }

    public static function pullIntended(string $fallback): string
    {
        $fallbackPath = self::normalize($fallback, '/') ?? '/';

        return self::normalize(
            is_string($pulled = session()->pull('url.intended')) ? $pulled : null,
            $fallbackPath,
        ) ?? $fallbackPath;
    }
}
