<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\Contacts\CustomerService as ContactCustomerService;
use App\Services\Store\CustomerService;
use App\Services\Store\StoreCartService;
use App\Services\Store\StoreLayoutService;
use App\Support\SafeInternalRedirect;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CustomerAuthController extends Controller
{
    public function show(StoreLayoutService $layout): View
    {
        $tab = request()->query('tab');
        SafeInternalRedirect::putIntended(request()->query('redirect'));

        return view('store.auth.login', [
            ...$layout->layoutData(),
            'authTab' => is_string($tab) && $tab === 'register' ? 'register' : 'login',
        ]);
    }

    public function login(Request $request, StoreCartService $cart, ContactCustomerService $contacts): RedirectResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'login_password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $login = trim($validated['login']);
        $password = $validated['login_password'];
        $remember = (bool) ($validated['remember'] ?? false);

        $sessionCart = $request->session()->get(StoreCartService::SESSION_KEY, []);
        $sessionCart = is_array($sessionCart) ? $sessionCart : [];

        $customer = $this->resolveCustomerByLogin($login, $contacts);

        if (! $customer instanceof Customer) {
            return $this->loginFailed($request, __('agricart.customer.auth.login_failed'));
        }

        if (! $customer->is_active) {
            return $this->loginFailed($request, __('agricart.customer.auth.login_inactive'));
        }

        $passwordHash = (string) ($customer->getAuthPassword() ?? '');

        if ($passwordHash === '' || ! Hash::check($password, $passwordHash)) {
            return $this->loginFailed($request, __('agricart.customer.auth.login_failed'));
        }

        Auth::guard('customer')->login($customer, $remember);
        $request->session()->regenerate();

        $cart->mergeAfterCustomerLogin($customer->id, $sessionCart);

        return redirect()->to(SafeInternalRedirect::pullIntended(route('store.home', absolute: false)))
            ->with('customer_auth_status', __('agricart.customer.auth.login_success'))
            ->with('customer_auth_status_type', 'success');
    }

    public function register(Request $request, CustomerService $customers, StoreCartService $cart): RedirectResponse
    {
        $sessionCart = $request->session()->get(StoreCartService::SESSION_KEY, []);
        $sessionCart = is_array($sessionCart) ? $sessionCart : [];

        try {
            $customer = $customers->register([
                'first_name' => (string) $request->input('first_name'),
                'last_name' => (string) $request->input('last_name'),
                'email' => $request->input('email'),
                'phone' => (string) $request->input('phone'),
                'password' => (string) $request->input('password'),
                'password_confirmation' => (string) $request->input('password_confirmation'),
                'address_line1' => $request->input('address_line1'),
                'address_line2' => $request->input('address_line2'),
                'city' => $request->input('city'),
                'state' => $request->input('state'),
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

        Auth::guard('customer')->login($customer, true);
        $request->session()->regenerate();

        $cart->mergeAfterCustomerLogin($customer->id, $sessionCart);

        return redirect()->to(SafeInternalRedirect::pullIntended(route('store.home', absolute: false)))
            ->with('customer_auth_status', __('agricart.customer.auth.register_success_signed_in'))
            ->with('customer_auth_status_type', 'success');
    }

    private function resolveCustomerByLogin(string $login, ContactCustomerService $contacts): ?Customer
    {
        if (str_contains($login, '@')) {
            return Customer::query()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($login)])
                ->first();
        }

        return $contacts->findByPhone($login);
    }

    private function loginFailed(Request $request, string $message): RedirectResponse
    {
        $params = [];

        $redirect = SafeInternalRedirect::normalize($request->input('redirect'));

        if ($redirect !== null) {
            $params['redirect'] = $redirect;
        }

        return redirect()
            ->route('store.customer.login', $params)
            ->withInput($request->only('login', 'remember'))
            ->with('customer_auth_status', $message)
            ->with('customer_auth_status_type', 'danger');
    }
}
