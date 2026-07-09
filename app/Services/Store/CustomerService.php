<?php

namespace App\Services\Store;

use App\Models\Customer;
use App\Services\Contacts\CustomerService as ContactCustomerService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function register(array $data): Customer
    {
        $validated = $this->validateRegistration($data);
        $validated['customer_number'] = $this->nextCustomerNumber();
        $validated['country'] = $validated['country'] ?? 'Pakistan';
        $validated['is_active'] = true;
        $validated['customer_channel'] = Customer::CHANNEL_ONLINE;
        $validated['display_name'] = trim($validated['first_name'] . ' ' . $validated['last_name']);
        $validated['name_en'] = $validated['display_name'];
        $validated['contacts'] = [['mobile_number' => $validated['phone']]];
        $validated['city_en'] = $validated['city'];

        return Customer::query()->create($validated);
    }

    public function nextCustomerNumber(): string
    {
        $next = (int) Customer::query()->max('id') + 1;

        do {
            $number = 'CUS-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            $next++;
        } while (Customer::query()->where('customer_number', $number)->exists());

        return $number;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validateRegistration(array $data): array
    {
        $rules = [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('customers', 'email')],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'same:password_confirmation'],
            'password_confirmation' => ['required', 'string'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:120'],
            'accepts_marketing' => ['nullable', 'boolean'],
            'terms_accepted' => ['accepted'],
        ];

        $validator = validator($data, $rules, [
            'terms_accepted.accepted' => __('agricart.customer.auth.terms_required'),
            'email.unique' => __('agricart.customer.auth.email_exists'),
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $validated = Arr::only($validator->validated(), [
            'first_name',
            'last_name',
            'email',
            'phone',
            'password',
            'address_line1',
            'address_line2',
            'city',
            'state',
            'postal_code',
            'country',
            'accepts_marketing',
        ]);

        $validated['accepts_marketing'] = (bool) ($validated['accepts_marketing'] ?? false);

        $existing = app(ContactCustomerService::class)->findByPhone((string) $validated['phone']);

        if ($existing !== null) {
            throw ValidationException::withMessages([
                'phone' => [__('agricart.customer.auth.phone_exists')],
            ]);
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateAuthenticatedProfile(Customer $customer, array $data): Customer
    {
        $validated = validator($data, [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($customer->id)],
            'phone' => ['required', 'string', 'max:30'],
        ])->validate();

        $existing = app(ContactCustomerService::class)->findByPhone((string) $validated['phone'], $customer->id);

        if ($existing !== null) {
            throw ValidationException::withMessages([
                'phone' => [__('agricart.customer.auth.phone_exists')],
            ]);
        }

        $displayName = trim($validated['first_name'] . ' ' . $validated['last_name']);

        $customer->fill([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'display_name' => $displayName,
            'name_en' => $displayName,
            'contacts' => [['mobile_number' => $validated['phone']]],
        ]);
        $customer->save();

        return $customer;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateAuthenticatedPassword(Customer $customer, array $data): void
    {
        $validated = validator($data, [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'same:password_confirmation'],
            'password_confirmation' => ['required', 'string'],
        ])->validate();

        if (! Hash::check($validated['current_password'], $customer->password)) {
            throw ValidationException::withMessages([
                'current_password' => [__('agricart.customer.auth.current_password_invalid')],
            ]);
        }

        $customer->password = $validated['password'];
        $customer->save();
    }
}
