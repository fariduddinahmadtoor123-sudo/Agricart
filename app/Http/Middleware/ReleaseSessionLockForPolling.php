<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Write the session early so concurrent poll requests are not blocked on the session lock.
 */
class ReleaseSessionLockForPolling
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasSession() && $request->session()->isStarted()) {
            $request->session()->save();
        }

        return $next($request);
    }
}
