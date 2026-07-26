<?php

namespace App\Http\Middleware;

use App\Support\AgricartLocale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetAgricartLocale
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, ?string $scope = null): Response
    {
        $scope = $scope === null
            ? AgricartLocale::resolveScope($request)
            : AgricartLocale::normalizeScope($scope);

        $cookie = AgricartLocale::cookieName($scope);
        $locale = AgricartLocale::normalize(
            $request->cookie($cookie, config('app.locale', 'en')),
        );

        app()->setLocale($locale);

        return $next($request);
    }
}
