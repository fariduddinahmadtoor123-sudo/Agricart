<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        $customer = $request->user('customer');

        if ($customer instanceof Customer && ! $customer->is_active) {
            Auth::guard('customer')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('store.customer.login')
                ->with('customer_auth_status_type', 'danger')
                ->with('customer_auth_status', __('agricart.customer.auth.session_deactivated'));
        }

        return $next($request);
    }
}
