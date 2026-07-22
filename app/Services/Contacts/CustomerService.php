<?php

namespace App\Services\Contacts;

use App\Models\Customer;
use App\Models\CustomerPhone;
use App\Models\FundTransfer;
use App\Models\OnlineSaleOrder;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Services\Media\OptimizedImageStorage;
use App\Services\Identifiers\DocumentCounterService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CustomerService
{
    /** @var list<string> */
    public const CHANNELS = [Customer::CHANNEL_ONLINE, Customer::CHANNEL_OFFLINE];

    public function createOffline(array $data): Customer
    {
        $validated = $this->validateOffline($data);
        $validated['customer_channel'] = Customer::CHANNEL_OFFLINE;

        if (! filled($validated['customer_number'] ?? null)) {
            $validated['customer_number'] = $this->nextCustomerNumber();
        }

        $validated = $this->applyOfflineLegacyFields($validated);

        return DB::transaction(function () use ($validated): Customer {
            $customer = Customer::query()->create($validated);
            $this->syncNormalizedPhones($customer, $validated['contacts'] ?? []);

            return $customer;
        });
    }

    public function update(Customer $customer, array $data): Customer
    {
        if ($customer->isOnline()) {
            $validated = $this->validateOnlineAdmin($data, $customer->id);
        } else {
            $validated = $this->validateOffline($data, $customer->id);
            $validated = $this->applyOfflineLegacyFields($validated, $customer);
        }

        return DB::transaction(function () use ($customer, $validated): Customer {
            $locked = Customer::query()->lockForUpdate()->findOrFail($customer->id);
            $locked->update($validated);
            $this->syncNormalizedPhones($locked, $validated['contacts'] ?? $locked->contacts ?? []);

            return $locked->fresh();
        });
    }

    public function delete(Customer $customer): void
    {
        $this->assertDeletable($customer);

        $customer->delete();
    }

    public function assertDeletable(Customer $customer): void
    {
        $saleCount = Sale::query()->where('customer_id', $customer->id)->count();
        $orderCount = OnlineSaleOrder::query()->where('customer_id', $customer->id)->count();
        $returnCount = SaleReturn::query()->where('customer_id', $customer->id)->count();
        $receiptCount = FundTransfer::query()
            ->where('destination_customer_id', $customer->id)
            ->count();

        if ($saleCount + $orderCount + $returnCount + $receiptCount > 0) {
            throw ValidationException::withMessages([
                'customer' => [__('customers.messages.delete_blocked')],
            ]);
        }
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
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{mobile_number: string}>
     */
    public function normalizePhoneRows(array $rows): array
    {
        return array_values(array_filter(
            array_map(static function (array $row): array {
                return [
                    'mobile_number' => trim((string) ($row['mobile_number'] ?? '')),
                ];
            }, $rows),
            static fn (array $row): bool => filled($row['mobile_number']),
        ));
    }

    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return '';
        }

        if (strlen($digits) > 10 && str_starts_with($digits, '92')) {
            $digits = substr($digits, -10);
        }

        if (strlen($digits) > 10) {
            $digits = substr($digits, -10);
        }

        return $digits;
    }

    /**
     * @return list<string>
     */
    public function customerPhoneKeys(Customer $customer): array
    {
        $keys = [];

        if (filled($customer->phone)) {
            $keys[] = $this->normalizePhone((string) $customer->phone);
        }

        foreach (is_array($customer->contacts) ? $customer->contacts : [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            if (filled($row['mobile_number'] ?? null)) {
                $keys[] = $this->normalizePhone((string) $row['mobile_number']);
            }
        }

        return array_values(array_unique(array_filter($keys)));
    }

    public function findByPhone(string $phone, ?int $exceptId = null): ?Customer
    {
        $needle = $this->normalizePhone($phone);

        if ($needle === '') {
            return null;
        }

        $customerId = CustomerPhone::query()
            ->where('canonical_phone', $needle)
            ->when($exceptId !== null, fn ($query) => $query->where('customer_id', '!=', $exceptId))
            ->value('customer_id');

        if ($customerId !== null) {
            return Customer::query()->find($customerId);
        }

        // Compatibility fallback while a deployment has legacy rows not yet normalized.
        return Customer::query()
            ->when($exceptId !== null, fn ($query) => $query->where('id', '!=', $exceptId))
            ->where(fn ($query) => $query->where('phone', 'like', '%'.$needle.'%')->orWhere('contacts', 'like', '%'.$needle.'%'))
            ->limit(30)
            ->get()
            ->first(fn (Customer $customer): bool => in_array($needle, $this->customerPhoneKeys($customer), true));
    }

    /**
     * @return array<int, array{id: int, label: string, phone: string}>
     */
    public function searchCustomers(string $term, int $limit = 15): array
    {
        $term = trim($term);

        if ($term === '') {
            return [];
        }

        $needle = '%'.$term.'%';
        $normalized = $this->normalizePhone($term);

        $customers = Customer::query()
            ->where(function ($query) use ($needle, $term, $normalized): void {
                $query->where('display_name', 'like', $needle)
                    ->orWhere('name_en', 'like', $needle)
                    ->orWhere('name_ur', 'like', $needle)
                    ->orWhere('phone', 'like', $needle)
                    ->orWhere('customer_number', 'like', $needle)
                    ->orWhere('first_name', 'like', $needle)
                    ->orWhere('last_name', 'like', $needle);

                if ($normalized !== '') {
                    $query->orWhere('phone', 'like', '%'.$normalized.'%');
                }

                if (ctype_digit($term)) {
                    $query->orWhere('id', (int) $term);
                }
            })
            ->orderBy('display_name')
            ->limit($limit)
            ->get();

        return $customers->map(fn (Customer $customer): array => [
            'id' => $customer->id,
            'label' => $customer->displayLabel(),
            'phone' => $customer->primaryPhone(),
        ])->all();
    }

    /**
     * @param  list<array{mobile_number: string}>  $phones
     */
    public function assertPhonesUnique(array $phones, ?int $exceptCustomerId = null): void
    {
        $seen = [];

        foreach ($phones as $row) {
            $raw = trim((string) ($row['mobile_number'] ?? ''));

            if ($raw === '') {
                continue;
            }

            $key = $this->normalizePhone($raw);

            if ($key === '') {
                continue;
            }

            if (isset($seen[$key])) {
                throw ValidationException::withMessages([
                    'phoneRows' => [__('customers.validation.phone_duplicate_same_form')],
                ]);
            }

            $seen[$key] = true;

            $existing = $this->findByPhone($raw, $exceptCustomerId);

            if ($existing !== null) {
                throw ValidationException::withMessages([
                    'phoneRows' => [__('customers.validation.phone_duplicate', ['name' => $existing->displayLabel()])],
                ]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toFormState(Customer $customer): array
    {
        $phones = is_array($customer->contacts) ? $customer->contacts : [];

        if ($phones === [] && filled($customer->phone)) {
            $phones = [['mobile_number' => (string) $customer->phone]];
        }

        return [
            'customer_number' => (string) ($customer->customer_number ?? ''),
            'customer_channel' => (string) $customer->customer_channel,
            'display_name' => (string) ($customer->display_name ?? ''),
            'name_en' => (string) ($customer->name_en ?? ''),
            'name_ur' => (string) ($customer->name_ur ?? ''),
            'address_quick' => (string) ($customer->address_quick ?? ''),
            'address_en' => (string) ($customer->address_en ?? ''),
            'address_ur' => (string) ($customer->address_ur ?? ''),
            'city_key' => (string) ($customer->city_key ?? ''),
            'city_en' => (string) ($customer->city_en ?? ''),
            'city_ur' => (string) ($customer->city_ur ?? ''),
            'opening_balance' => $customer->opening_balance !== null ? (string) $customer->opening_balance : '',
            'credit_limit' => $customer->credit_limit !== null ? (string) $customer->credit_limit : '',
            'email' => (string) ($customer->email ?? ''),
            'accepts_marketing' => (bool) $customer->accepts_marketing,
            'is_active' => (bool) $customer->is_active,
            'ai_status' => $customer->ai_status?->value ?? 'none',
            'profile_photo_path' => $customer->profile_photo_path,
            'phoneRows' => $phones !== []
                ? array_values(array_map(static fn (array $row): array => [
                    'mobile_number' => (string) ($row['mobile_number'] ?? ''),
                ], $phones))
                : [['mobile_number' => '']],
            'first_name' => (string) $customer->first_name,
            'last_name' => (string) $customer->last_name,
            'address_line1' => (string) $customer->address_line1,
            'address_line2' => (string) ($customer->address_line2 ?? ''),
            'city' => (string) $customer->city,
            'state' => (string) $customer->state,
            'postal_code' => (string) ($customer->postal_code ?? ''),
            'country' => (string) $customer->country,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validateOffline(array $data, ?int $exceptId = null): array
    {
        $validated = validator($data, [
            'customer_number' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('customers', 'customer_number')->ignore($exceptId),
            ],
            'display_name' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'name_ur' => ['nullable', 'string', 'max:255'],
            'address_quick' => ['nullable', 'string', 'max:500'],
            'address_en' => ['nullable', 'string', 'max:2000'],
            'address_ur' => ['nullable', 'string', 'max:2000'],
            'city_key' => ['nullable', 'string', 'max:64'],
            'city_en' => ['nullable', 'string', 'max:255'],
            'city_ur' => ['nullable', 'string', 'max:255'],
            'opening_balance' => ['nullable', 'numeric'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('customers', 'email')->ignore($exceptId),
            ],
            'accepts_marketing' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'ai_status' => ['nullable', 'string', 'max:32'],
            'phoneRows' => ['required', 'array', 'min:1'],
            'phoneRows.*.mobile_number' => ['nullable', 'string', 'max:30'],
            'profile_photo_upload' => ['nullable'],
        ])->validate();

        $phones = $this->normalizePhoneRows($validated['phoneRows'] ?? []);

        if ($phones === []) {
            throw ValidationException::withMessages([
                'phoneRows' => __('customers.validation.phone_required'),
            ]);
        }

        $this->assertPhonesUnique($phones, $exceptId);

        $validated['contacts'] = $phones;
        $validated['phone'] = $phones[0]['mobile_number'];
        unset($validated['phoneRows']);

        if (isset($validated['profile_photo_upload']) && $validated['profile_photo_upload'] instanceof TemporaryUploadedFile) {
            $validated['profile_photo_path'] = $this->storeUploadedPhoto($validated['profile_photo_upload']);
        }

        unset($validated['profile_photo_upload']);

        return $this->normalizeDecimalFields($validated);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validateOnlineAdmin(array $data, ?int $exceptId = null): array
    {
        $validated = validator($data, [
            'name_en' => ['nullable', 'string', 'max:255'],
            'name_ur' => ['nullable', 'string', 'max:255'],
            'address_en' => ['nullable', 'string', 'max:2000'],
            'address_ur' => ['nullable', 'string', 'max:2000'],
            'city_key' => ['nullable', 'string', 'max:64'],
            'city_en' => ['nullable', 'string', 'max:255'],
            'city_ur' => ['nullable', 'string', 'max:255'],
            'opening_balance' => ['nullable', 'numeric'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'accepts_marketing' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'ai_status' => ['nullable', 'string', 'max:32'],
            'profile_photo_upload' => ['nullable'],
        ])->validate();

        if (isset($validated['profile_photo_upload']) && $validated['profile_photo_upload'] instanceof TemporaryUploadedFile) {
            $validated['profile_photo_path'] = $this->storeUploadedPhoto($validated['profile_photo_upload']);
        }

        unset($validated['profile_photo_upload']);

        return $this->normalizeDecimalFields($validated);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizeDecimalFields(array $validated): array
    {
        $opening = $validated['opening_balance'] ?? null;
        $validated['opening_balance'] = ($opening === '' || $opening === null) ? 0 : $opening;

        $credit = $validated['credit_limit'] ?? null;
        $validated['credit_limit'] = ($credit === '' || $credit === null) ? null : $credit;

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function applyOfflineLegacyFields(array $validated, ?Customer $existing = null): array
    {
        $displayName = trim((string) ($validated['display_name'] ?? ''));
        $parts = preg_split('/\s+/', $displayName, 2) ?: [];

        $validated['first_name'] = $parts[0] ?? $displayName;
        $validated['last_name'] = $parts[1] ?? '-';

        if (! filled($validated['email'] ?? null)) {
            $number = (string) ($validated['customer_number'] ?? $existing?->customer_number ?? $this->nextCustomerNumber());
            $validated['email'] = 'offline.'.Str::lower($number).'@contacts.agricart.local';
        }

        if (! filled($validated['password'] ?? null) && $existing === null) {
            $validated['password'] = Hash::make(Str::random(40));
        } elseif ($existing !== null) {
            unset($validated['password']);
        }

        $validated['address_line1'] = filled($validated['address_quick'] ?? null)
            ? (string) $validated['address_quick']
            : (filled($validated['address_en'] ?? null) ? (string) $validated['address_en'] : '-');

        $validated['city'] = (string) ($validated['city_en'] ?? $validated['city_ur'] ?? '-');
        $validated['state'] = $existing?->state ?: 'Punjab';
        $validated['country'] = $existing?->country ?: 'Pakistan';

        return $validated;
    }

    private function storeUploadedPhoto(TemporaryUploadedFile $file): string
    {
        return app(OptimizedImageStorage::class)->store($file, 'customers/photos');
    }

    /**
     * Keep the indexed canonical table authoritative while retaining legacy JSON.
     *
     * @param  list<array{mobile_number?: string}>  $phones
     */
    public function syncNormalizedPhones(Customer $customer, array $phones): void
    {
        CustomerPhone::query()->where('customer_id', $customer->id)->delete();

        foreach ($this->normalizePhoneRows($phones) as $sort => $row) {
            $canonical = $this->normalizePhone($row['mobile_number']);
            if ($canonical === '') {
                continue;
            }

            CustomerPhone::query()->create([
                'customer_id' => $customer->id,
                'phone' => $row['mobile_number'],
                'canonical_phone' => $canonical,
                'sort_order' => $sort,
            ]);
        }
    }
}
