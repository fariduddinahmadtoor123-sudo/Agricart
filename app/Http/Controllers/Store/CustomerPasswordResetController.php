<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\Access\SessionInvalidationService;
use App\Services\Store\StoreLayoutService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class CustomerPasswordResetController extends Controller
{
    public function create(StoreLayoutService $layout): View
    {
        return view('store.auth.forgot-password', $layout->layoutData());
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::broker('customers')->sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()
                ->with('customer_auth_status', __('agricart.customer.auth.reset_link_sent'))
                ->with('customer_auth_status_type', 'success');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __('agricart.customer.auth.reset_link_failed')]);
    }

    public function edit(Request $request, string $token, StoreLayoutService $layout): View
    {
        return view('store.auth.reset-password', [
            ...$layout->layoutData(),
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::default()],
        ]);

        $status = Password::broker('customers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($customer, string $password): void {
                // Plain value — Customer `hashed` cast hashes once (do not Hash::make here).
                $customer->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                if ($customer instanceof Customer) {
                    app(SessionInvalidationService::class)->wipeAllForCustomer($customer);
                }
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route('store.customer.login')
                ->with('customer_auth_status', __('agricart.customer.auth.password_reset_success'))
                ->with('customer_auth_status_type', 'success');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __('agricart.customer.auth.password_reset_failed')]);
    }
}
