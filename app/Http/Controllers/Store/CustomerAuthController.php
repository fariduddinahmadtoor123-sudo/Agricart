<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\Store\CustomerService;
use App\Services\Store\StoreLayoutService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CustomerAuthController extends Controller
{
    public function show(StoreLayoutService $layout): View
    {
        $tab = request()->query('tab');
        $redirect = request()->query('redirect');

        if (is_string($redirect) && str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//')) {
            session()->put('url.intended', $redirect);
        }

        return view('store.auth.login', [
            ...$layout->layoutData(),
            'authTab' => is_string($tab) && $tab === 'register' ? 'register' : 'login',
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'login_email' => ['required', 'email'],
            'login_password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $email = $validated['login_email'];
        $password = $validated['login_password'];
        $remember = (bool) ($validated['remember'] ?? false);

        $customer = Customer::query()->where('email', $email)->first();

        if (! $customer instanceof Customer || ! $customer->is_active) {
            return $this->loginFailed($request, __('agricart.customer.auth.login_failed'));
        }

        if (! Auth::guard('customer')->attempt([
            'email' => $email,
            'password' => $password,
            'is_active' => true,
        ], $remember)) {
            return $this->loginFailed($request, __('agricart.customer.auth.login_failed'));
        }

        $request->session()->regenerate();

        $intended = $request->session()->pull('url.intended', route('store.home'));

        if (! is_string($intended) || ! str_starts_with($intended, '/') || str_starts_with($intended, '//')) {
            $intended = route('store.home');
        }

        return redirect()->to($intended)
            ->with('customer_auth_status', __('agricart.customer.auth.login_success'))
            ->with('customer_auth_status_type', 'success');
    }

    public function register(Request $request, CustomerService $customers): RedirectResponse
    {
        try {
            $customer = $customers->register([
                'first_name' => (string) $request->input('first_name'),
                'last_name' => (string) $request->input('last_name'),
                'email' => (string) $request->input('email'),
                'phone' => (string) $request->input('phone'),
                'password' => (string) $request->input('password'),
                'password_confirmation' => (string) $request->input('password_confirmation'),
                'address_line1' => (string) $request->input('address_line1'),
                'address_line2' => $request->input('address_line2'),
                'city' => (string) $request->input('city'),
                'state' => (string) $request->input('state'),
                'postal_code' => $request->input('postal_code'),
                'country' => $request->input('country', 'Pakistan'),
                'accepts_marketing' => $request->boolean('accepts_marketing'),
                'terms_accepted' => $request->boolean('terms_accepted'),
            ]);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('store.customer.login', ['tab' => 'register'])
                ->withErrors($exception->errors())
                ->withInput()
                ->with('_form', 'register');
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('store.customer.login', ['tab' => 'register'])
                ->withInput()
                ->with('_form', 'register')
                ->with('customer_auth_status', __('agricart.customer.auth.register_failed'))
                ->with('customer_auth_status_type', 'danger');
        }

        if (! Auth::guard('customer')->attempt([
            'email' => $customer->email,
            'password' => (string) $request->input('password'),
            'is_active' => true,
        ], true)) {
            return redirect()
                ->route('store.customer.login', ['tab' => 'login'])
                ->with('customer_auth_status', __('agricart.customer.auth.register_success_login'))
                ->with('customer_auth_status_type', 'success');
        }

        $request->session()->regenerate();

        $intended = $request->session()->pull('url.intended', route('store.home'));

        if (! is_string($intended) || ! str_starts_with($intended, '/') || str_starts_with($intended, '//')) {
            $intended = route('store.home');
        }

        return redirect()->to($intended)
            ->with('customer_auth_status', __('agricart.customer.auth.register_success_signed_in'))
            ->with('customer_auth_status_type', 'success');
    }

    private function loginFailed(Request $request, string $message): RedirectResponse
    {
        $params = [];

        $redirect = $request->input('redirect');

        if (is_string($redirect) && str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//')) {
            $params['redirect'] = $redirect;
        }

        return redirect()
            ->route('store.customer.login', $params)
            ->withInput($request->only('login_email', 'remember'))
            ->with('customer_auth_status', $message)
            ->with('customer_auth_status_type', 'danger');
    }
}
