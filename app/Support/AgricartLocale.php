<?php

namespace App\Support;

use Illuminate\Http\Request;

final class AgricartLocale
{
    public const STORE_COOKIE = 'agricart-locale';

    public const ADMIN_COOKIE = 'agricart-admin-locale';

    public const SCOPES = ['store', 'admin'];

    public static function normalize(?string $locale): string
    {
        return in_array($locale, ['en', 'ur'], true) ? $locale : 'en';
    }

    public static function normalizeScope(?string $scope): string
    {
        return in_array($scope, self::SCOPES, true) ? $scope : 'store';
    }

    public static function cookieName(string $scope): string
    {
        return self::normalizeScope($scope) === 'admin'
            ? self::ADMIN_COOKIE
            : self::STORE_COOKIE;
    }

    public static function resolveScope(Request $request, ?string $redirect = null): string
    {
        $queryScope = $request->query('scope');

        if (is_string($queryScope) && in_array($queryScope, self::SCOPES, true)) {
            return $queryScope;
        }

        $path = is_string($redirect) ? $redirect : $request->getPathInfo();

        if (str_starts_with($path, '/admin')) {
            return 'admin';
        }

        $referer = $request->headers->get('referer');

        if (is_string($referer)) {
            $refererPath = parse_url($referer, PHP_URL_PATH);

            if (is_string($refererPath) && str_starts_with($refererPath, '/admin')) {
                return 'admin';
            }
        }

        return 'store';
    }
}
