<?php

namespace App\Livewire\Store\Auth;

use App\Models\Customer;
use App\Services\Store\CustomerService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class CustomerAuth extends Component
{
    public string $tab = 'login';

    public string $loginEmail = '';

    public string $loginPassword = '';

    public bool $remember = false;

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $address_line1 = '';

    public string $address_line2 = '';

    public string $city = '';

    public string $state = '';

    public string $postal_code = '';

    public string $country = 'Pakistan';

    public bool $accepts_marketing = false;

    public bool $terms_accepted = false;

    public bool $busy = false;

    public ?string $statusMessage = null;

    public string $statusType = 'danger';

    public function mount(): void
    {
        $tab = request()->query('tab');

        if (is_string($tab) && in_array($tab, ['login', 'register'], true)) {
            $this->tab = $tab;
        }

        if (is_string($flash = session('customer_auth_status'))) {
            $this->statusMessage = $flash;
            $this->statusType = 'success';
        }
    }

    public function switchTab(string $tab): void
    {
        if (! in_array($tab, ['login', 'register'], true)) {
            return;
        }

        $this->tab = $tab;
        $this->statusMessage = null;
        $this->resetValidation();
    }

    public function login(): void
    {
        $this->busy = true;
        $this->statusMessage = null;

        try {
            $this->validate([
                'loginEmail' => ['required', 'email'],
                'loginPassword' => ['required', 'string'],
            ]);

            $customer = Customer::query()->where('email', $this->loginEmail)->first();

            if (! $customer instanceof Customer || ! $customer->is_active) {
                throw ValidationException::withMessages([
                    'loginEmail' => __('agricart.customer.auth.login_failed'),
                ]);
            }

            if (! Auth::guard('customer')->attempt([
                'email' => $this->loginEmail,
                'password' => $this->loginPassword,
                'is_active' => true,
            ], $this->remember)) {
                throw ValidationException::withMessages([
                    'loginEmail' => __('agricart.customer.auth.login_failed'),
                ]);
            }

            session()->regenerate();

            $this->redirectIntended(route('store.home'), navigate: false);
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first();
            $this->statusMessage = $message ?: __('agricart.customer.auth.login_failed');
            $this->statusType = 'danger';
        } finally {
            $this->busy = false;
        }
    }

    public function register(): void
    {
        $this->busy = true;
        $this->statusMessage = null;

        try {
            app(CustomerService::class)->register([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'phone' => $this->phone,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'address_line1' => $this->address_line1,
                'address_line2' => $this->address_line2 ?: null,
                'city' => $this->city,
                'state' => $this->state,
                'postal_code' => $this->postal_code ?: null,
                'country' => $this->country,
                'accepts_marketing' => $this->accepts_marketing,
                'terms_accepted' => $this->terms_accepted,
            ]);

            if (! Auth::guard('customer')->attempt([
                'email' => $this->email,
                'password' => $this->password,
                'is_active' => true,
            ], true)) {
                session()->flash('customer_auth_status', __('agricart.customer.auth.register_success_login'));
                $this->redirect(route('store.customer.login'), navigate: false);

                return;
            }

            session()->regenerate();

            $this->redirect(route('store.home'), navigate: false);
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());
            $message = collect($exception->errors())->flatten()->first();
            $this->statusMessage = $message ?: __('agricart.customer.auth.register_failed');
            $this->statusType = 'danger';
        } finally {
            $this->busy = false;
        }
    }

    public function render(): View
    {
        return view('livewire.store.auth.customer-auth', [
            'provinces' => config('pakistani_provinces', []),
        ]);
    }
}
