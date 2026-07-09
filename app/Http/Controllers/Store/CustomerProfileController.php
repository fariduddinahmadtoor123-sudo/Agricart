<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Services\Store\CustomerService;
use App\Services\Store\StoreLayoutService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerProfileController extends Controller
{
    public function show(StoreLayoutService $layout): View
    {
        return view('store.auth.profile', [
            ...$layout->layoutData(),
            'customer' => auth('customer')->user(),
        ]);
    }

    public function updateProfile(Request $request, CustomerService $customers): RedirectResponse
    {
        $customers->updateAuthenticatedProfile($request->user('customer'), $request->all());

        return redirect()
            ->route('store.customer.account')
            ->with('customer_auth_status', __('agricart.customer.auth.profile_updated'))
            ->with('customer_auth_status_type', 'success');
    }

    public function updatePassword(Request $request, CustomerService $customers): RedirectResponse
    {
        $customers->updateAuthenticatedPassword($request->user('customer'), $request->all());

        return redirect()
            ->route('store.customer.account')
            ->with('customer_auth_status', __('agricart.customer.auth.password_updated'))
            ->with('customer_auth_status_type', 'success');
    }

    public function updateAddress(Request $request, CustomerService $customers): RedirectResponse
    {
        $customers->updateAuthenticatedAddress($request->user('customer'), $request->all());

        return redirect()
            ->route('store.customer.account')
            ->with('customer_auth_status', __('agricart.customer.auth.address_updated'))
            ->with('customer_auth_status_type', 'success');
    }
}
