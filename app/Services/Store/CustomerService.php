<?php

namespace App\Services\Store;

use App\Models\Customer;
use App\Services\Access\SessionInvalidationService;
use App\Services\Identifiers\DocumentCounterService;
use App\Services\Contacts\CustomerService as ContactCustomerService;
use App\Services\Media\OptimizedImageStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Storefront customer accounts (register, login, profile, password).
 *
 * For shared contact/phone domain logic, {@see \App\Services\Contacts\CustomerService} is the owner;
 * this class delegates where appropriate via {@see ContactCustomerService}.
 */
class CustomerService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function register(array $data): Customer
    {
        $validated = $this->validateRegistration($data);
        $validated['customer_number'] = $this->nextCustomerNumber();
        $validated['country'] = filled($validated['country'] ?? null) ? $validated['country'] : 'Pakistan';
        $validated['address_line1'] = (string) ($validated['address_line1'] ?? '');
        $validated['address_line2'] = $validated['address_line2'] ?? null;
        $validated['city'] = (string) ($validated['city'] ?? '');
        $validated['state'] = (string) ($validated['state'] ?? '');
        $validated['is_active'] = true;
        $validated['customer_channel'] = Customer::CHANNEL_ONLINE;
        $validated['display_name'] = trim($validated['first_name'] . ' ' . $validated['last_name']);
        $validated['name_en'] = $validated['display_name'];
        $validated['contacts'] = [['mobile_number' => $validated['phone']]];
        $validated['city_en'] = $validated['city'] !== '' ? $validated['city'] : null;

        return DB::transaction(function () use ($validated): Customer {
            $customer = Customer::query()->create($validated);
            app(ContactCustomerService::class)->syncNormalizedPhones($customer, $validated['contacts']);

            return $customer;
        });
    }

    public function nextCustomerNumber(): string
    {
        return app(DocumentCounterService::class)->next(
            'customers.customer_number',
            'CUS-',
            5,
            fn (): int => DocumentCounterService::maximumSuffix('customers', 'customer_number', 'CUS-'),
        );
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
            'password' => ['required', 'string', Password::default(), 'same:password_confirmation'],
            'password_confirmation' => ['required', 'string'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
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
            'profile_photo' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:2048',
                'dimensions:min_width=64,min_height=64,max_width=6000,max_height=6000',
            ],
            'remove_profile_photo' => ['nullable', 'boolean'],
        ], [
            'profile_photo.image' => __('agricart.customer.auth.profile_photo_invalid'),
            'profile_photo.mimes' => __('agricart.customer.auth.profile_photo_invalid'),
            'profile_photo.max' => __('agricart.customer.auth.profile_photo_max'),
            'profile_photo.dimensions' => __('agricart.customer.auth.profile_photo_dimensions'),
        ])->validate();

        $existing = app(ContactCustomerService::class)->findByPhone((string) $validated['phone'], $customer->id);

        if ($existing !== null) {
            throw ValidationException::withMessages([
                'phone' => [__('agricart.customer.auth.phone_exists')],
            ]);
        }

        $displayName = trim($validated['first_name'] . ' ' . $validated['last_name']);
        $uploadedPhoto = ($data['profile_photo'] ?? null) instanceof UploadedFile
            ? $data['profile_photo']
            : null;
        $removePhoto = (bool) ($validated['remove_profile_photo'] ?? false);

        return DB::transaction(function () use ($customer, $validated, $displayName, $uploadedPhoto, $removePhoto): Customer {
            $customer = Customer::query()->lockForUpdate()->findOrFail($customer->id);
            $contacts = [['mobile_number' => $validated['phone']]];
            $photoPath = $customer->profile_photo_path;

            if ($uploadedPhoto instanceof UploadedFile) {
                $newPath = app(OptimizedImageStorage::class)->store($uploadedPhoto, 'customers/photos');
                $this->deleteProfilePhotoFile($photoPath);
                $photoPath = $newPath;
            } elseif ($removePhoto && filled($photoPath)) {
                $this->deleteProfilePhotoFile($photoPath);
                $photoPath = null;
            }

            $customer->fill([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'display_name' => $displayName,
                'name_en' => $displayName,
                'contacts' => $contacts,
                'profile_photo_path' => $photoPath,
            ]);
            $customer->save();
            app(ContactCustomerService::class)->syncNormalizedPhones($customer, $contacts);

            return $customer;
        });
    }

    private function deleteProfilePhotoFile(?string $path): void
    {
        if (! filled($path)) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateAuthenticatedPassword(Customer $customer, array $data): void
    {
        $validated = validator($data, [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', Password::default(), 'same:password_confirmation'],
            'password_confirmation' => ['required', 'string'],
        ])->validate();

        // Always verify against a fresh DB hash — never a stale in-memory/session copy.
        $fresh = Customer::query()->whereKey($customer->id)->firstOrFail();
        $currentHash = (string) ($fresh->getAuthPassword() ?? '');

        if ($currentHash === '' || ! Hash::check($validated['current_password'], $currentHash)) {
            throw ValidationException::withMessages([
                'current_password' => [__('agricart.customer.auth.current_password_invalid')],
            ]);
        }

        $fresh->password = $validated['password'];
        $fresh->save();

        app(SessionInvalidationService::class)
            ->invalidateOtherCustomerDevices($fresh, $validated['password']);

        if (Auth::guard('customer')->check() && request()->hasSession()) {
            request()->session()->put(
                'password_hash_'.Auth::getDefaultDriver(),
                (string) $fresh->fresh()?->getAuthPassword(),
            );
        }
    }

    /**
     * @return array<string, list<string|\Illuminate\Validation\Rules\Unique>>
     */
    public function addressValidationRules(): array
    {
        return [
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateAuthenticatedAddress(Customer $customer, array $data): Customer
    {
        $validated = validator($data, $this->addressValidationRules())->validate();

        $customer->fill([
            'address_line1' => $validated['address_line1'],
            'address_line2' => $validated['address_line2'] ?? null,
            'city' => $validated['city'],
            'city_en' => $validated['city'],
            'state' => $validated['state'],
            'postal_code' => $validated['postal_code'] ?? null,
            'country' => $validated['country'] ?? 'Pakistan',
        ]);
        $customer->save();

        return $customer;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{
     *     ship_contact_name: string,
     *     ship_phone: string,
     *     ship_address: string|null,
     *     ship_city: string|null,
     *     address: array<string, mixed>
     * }
     */
    public function validateCheckoutShipping(array $data): array
    {
        $rules = array_merge([
            'ship_contact_name' => ['required', 'string', 'max:200'],
            'ship_phone' => ['required', 'string', 'max:30'],
        ], $this->addressValidationRules());

        $validated = validator($data, $rules)->validate();

        $addressBlock = Arr::only($validated, [
            'address_line1',
            'address_line2',
            'city',
            'state',
            'postal_code',
            'country',
        ]);

        return [
            'ship_contact_name' => $validated['ship_contact_name'],
            'ship_phone' => $validated['ship_phone'],
            'ship_address' => $this->formatShipAddress($addressBlock),
            'ship_city' => $validated['city'],
            'address' => $addressBlock,
        ];
    }

    /**
     * @param  array<string, mixed>  $address
     */
    public function syncProfileAddressFromCheckout(Customer $customer, array $address): void
    {
        $validated = validator($address, $this->addressValidationRules())->validate();

        $customer->fill([
            'address_line1' => $validated['address_line1'],
            'address_line2' => $validated['address_line2'] ?? null,
            'city' => $validated['city'],
            'city_en' => $validated['city'],
            'state' => $validated['state'],
            'postal_code' => $validated['postal_code'] ?? null,
            'country' => $validated['country'] ?? 'Pakistan',
        ]);
        $customer->save();
    }

    /**
     * @param  array<string, mixed>  $address
     */
    public function formatShipAddress(array $address): ?string
    {
        $lines = collect([
            $address['address_line1'] ?? null,
            $address['address_line2'] ?? null,
            filled($address['state'] ?? null) ? ($address['state'] . (filled($address['postal_code'] ?? null) ? ' ' . $address['postal_code'] : '')) : ($address['postal_code'] ?? null),
            $address['country'] ?? null,
        ])->filter(fn ($line) => filled($line))->implode(', ');

        return $lines !== '' ? $lines : null;
    }
}
