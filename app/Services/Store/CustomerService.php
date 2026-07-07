<?php

namespace App\Services\Store;

use App\Models\Customer;
use App\Services\Contacts\CustomerService;
use Illuminate\Support\Arr;
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

        $existing = app(CustomerService::class)->findByPhone((string) $validated['phone']);

        if ($existing !== null) {
            throw ValidationException::withMessages([
                'phone' => [__('customers.validation.phone_duplicate', ['name' => $existing->displayLabel()])],
            ]);
        }

        return $validated;
    }
}
