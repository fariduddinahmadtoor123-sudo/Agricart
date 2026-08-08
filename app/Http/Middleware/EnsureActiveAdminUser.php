<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAdminUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->canUseAdmin()) {
            return $next($request);
        }

        Auth::logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        // Build the redirect via the framework Redirector directly. The global redirect()
        // helper can resolve to Livewire's Redirector on full-page component routes, which is
        // not a Symfony Response and would break this middleware's return type contract.
        $redirector = new Redirector(app('url'));

        if ($request->hasSession()) {
            $redirector->setSession($request->session());
        }

        return $redirector
            ->guest('/admin/login')
            ->with('status', __('agricart.auth.inactive'));
    }
}
