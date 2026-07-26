<?php

namespace App\Services\Contacts;

use App\Models\FundTransfer;
use App\Models\OnlineSaleOrderSupplierAssignment;
use App\Models\Purchase;
use App\Models\PurchasePaymentSummaryItem;
use App\Models\PurchaseQuotation;
use App\Models\Supplier;
use App\Services\Media\OptimizedImageStorage;
use App\Services\Identifiers\DocumentCounterService;
use App\Support\PakistaniBanks;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class SupplierService
{
    public function create(array $data): Supplier
    {
        $validated = $this->validate($data);
        $categoryIds = $validated['category_ids'] ?? [];
        unset($validated['category_ids']);

        if (! filled($validated['supplier_code'] ?? null)) {
            $validated['supplier_code'] = $this->nextSupplierCode();
        }

        if (empty($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $validated['source'] = $validated['source'] ?? Supplier::SOURCE_ADMIN;

        $supplier = Supplier::query()->create($validated);
        $supplier->categories()->sync($categoryIds);

        return $supplier->fresh(['categories']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function registerPublic(array $data): Supplier
    {
        $validated = $this->validatePublicRegistration($data);
        $categoryIds = $validated['category_ids'] ?? [];
        unset($validated['category_ids']);

        $primaryMobile = $this->primaryMobileFromContacts($validated['contacts'] ?? []);
        $rejected = $this->findRejectedByPrimaryMobile($primaryMobile);

        if ($rejected !== null) {
            return $this->resubmitRejected($rejected, $data);
        }

        $pending = $this->findPendingByPrimaryMobile($primaryMobile);

        if ($pending !== null) {
            throw ValidationException::withMessages([
                'contactRows.0.mobile_number' => [__('agricart.supplier_register.errors.already_pending')],
            ]);
        }

        $validated['supplier_code'] = $this->nextSupplierCode();
        $validated['status'] = Supplier::STATUS_PENDING;
        $validated['source'] = Supplier::SOURCE_PUBLIC;
        $validated['portal_enabled'] = false;
        $validated['opening_balance'] = 0;
        $validated['ai_status'] = 'none';
        $validated['rejection_reason'] = null;
        $validated['rejected_at'] = null;

        $supplier = Supplier::query()->create($validated);
        $supplier->categories()->sync($categoryIds);

        return $supplier->fresh(['categories']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function resubmitRejected(Supplier $supplier, array $data): Supplier
    {
        if ($supplier->status !== Supplier::STATUS_REJECTED) {
            throw ValidationException::withMessages([
                'supplier' => [__('agricart.supplier_register.errors.not_rejected')],
            ]);
        }

        $validated = $this->validatePublicRegistration($data, $supplier->id);
        $categoryIds = $validated['category_ids'] ?? [];
        unset($validated['category_ids']);

        $validated['status'] = Supplier::STATUS_PENDING;
        $validated['source'] = Supplier::SOURCE_PUBLIC;
        $validated['rejection_reason'] = null;
        $validated['rejected_at'] = null;
        $validated['portal_enabled'] = false;
        $validated['ai_status'] = $supplier->ai_status?->value ?? 'none';

        $supplier->update($validated);
        $supplier->categories()->sync($categoryIds);

        return $supplier->fresh(['categories']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validatePublicRegistration(array $data, ?int $exceptId = null): array
    {
        $typeKeys = array_keys(config('supplier_types', []));

        $rules = [
            'business_name_en' => ['required', 'string', 'max:255'],
            'business_name_ur' => ['nullable', 'string', 'max:255'],
            'supplier_type' => ['required', 'string', Rule::in($typeKeys)],
            'country_code' => ['nullable', 'string', 'max:8'],
            'city_en' => ['required', 'string', 'max:255'],
            'address_en' => ['required', 'string', 'max:2000'],
            'address_ur' => ['nullable', 'string', 'max:2000'],
            'contacts' => ['required', 'array', 'min:1'],
            'contacts.*.contact_name_en' => ['required', 'string', 'max:255'],
            'contacts.*.contact_name_ur' => ['nullable', 'string', 'max:255'],
            'contacts.*.mobile_number' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9\s()\-]+$/'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'custom_categories' => ['nullable', 'string', 'max:2000'],
            'application_note' => ['nullable', 'string', 'max:5000'],
            'terms_accepted' => ['accepted'],
        ];

        $validator = validator($data, $rules, [
            'terms_accepted.accepted' => __('agricart.supplier_register.errors.terms_required'),
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $validated = Arr::only($validator->validated(), [
            'business_name_en',
            'business_name_ur',
            'supplier_type',
            'country_code',
            'city_en',
            'address_en',
            'address_ur',
            'contacts',
            'category_ids',
            'custom_categories',
            'application_note',
        ]);

        $validated['country_code'] = filled($validated['country_code'] ?? null) ? $validated['country_code'] : 'PK';
        $validated['contacts'] = $this->normalizeContacts(is_array($validated['contacts'] ?? null) ? $validated['contacts'] : []);
        $validated['category_ids'] = array_values(array_unique(array_map('intval', $validated['category_ids'] ?? [])));
        $validated['custom_categories'] = filled($validated['custom_categories'] ?? null)
            ? trim((string) $validated['custom_categories'])
            : null;
        $validated['application_note'] = filled($validated['application_note'] ?? null)
            ? trim((string) $validated['application_note'])
            : null;
        $validated['bank_accounts'] = [];
        $validated['terms_accepted_at'] = now();

        if ($validated['contacts'] === []) {
            throw ValidationException::withMessages([
                'contactRows.0.mobile_number' => [__('suppliers.validation.contact_required')],
            ]);
        }

        $primaryMobile = $this->primaryMobileFromContacts($validated['contacts']);

        if ($primaryMobile === null) {
            throw ValidationException::withMessages([
                'contactRows.0.mobile_number' => [__('suppliers.validation.mobile_required')],
            ]);
        }

        $conflict = Supplier::query()
            ->whereIn('status', [Supplier::STATUS_ACTIVE, Supplier::STATUS_INACTIVE, Supplier::STATUS_PENDING])
            ->when($exceptId !== null, fn ($query) => $query->whereKeyNot($exceptId))
            ->get(['id', 'contacts', 'status'])
            ->first(function (Supplier $supplier) use ($primaryMobile): bool {
                return $this->primaryMobileFromContacts(
                    is_array($supplier->contacts) ? $supplier->contacts : [],
                ) === $primaryMobile;
            });

        if ($conflict !== null) {
            throw ValidationException::withMessages([
                'contactRows.0.mobile_number' => [__('agricart.supplier_register.errors.mobile_taken')],
            ]);
        }

        return $validated;
    }

    /**
     * @param  list<array<string, mixed>>  $contacts
     */
    public function primaryMobileFromContacts(array $contacts): ?string
    {
        foreach ($contacts as $contact) {
            if (! is_array($contact)) {
                continue;
            }

            $mobile = preg_replace('/\D+/', '', (string) ($contact['mobile_number'] ?? '')) ?? '';

            if ($mobile !== '') {
                return $mobile;
            }
        }

        return null;
    }

    public function findRejectedByPrimaryMobile(?string $mobileDigits): ?Supplier
    {
        if ($mobileDigits === null || $mobileDigits === '') {
            return null;
        }

        return Supplier::query()
            ->where('status', Supplier::STATUS_REJECTED)
            ->latest('id')
            ->get()
            ->first(function (Supplier $supplier) use ($mobileDigits): bool {
                return $this->primaryMobileFromContacts(
                    is_array($supplier->contacts) ? $supplier->contacts : [],
                ) === $mobileDigits;
            });
    }

    public function findPendingByPrimaryMobile(?string $mobileDigits): ?Supplier
    {
        if ($mobileDigits === null || $mobileDigits === '') {
            return null;
        }

        return Supplier::query()
            ->where('status', Supplier::STATUS_PENDING)
            ->latest('id')
            ->get()
            ->first(function (Supplier $supplier) use ($mobileDigits): bool {
                return $this->primaryMobileFromContacts(
                    is_array($supplier->contacts) ? $supplier->contacts : [],
                ) === $mobileDigits;
            });
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        $validated = $this->validate($data, $supplier->id);
        $categoryIds = $validated['category_ids'] ?? [];
        unset($validated['category_ids']);

        if (empty($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        if (! $validated['portal_enabled']) {
            $validated['email'] = null;
        }

        $this->replaceStoredFiles($supplier, $validated);

        $supplier->update($validated);
        $supplier->categories()->sync($categoryIds);

        return $supplier->fresh(['categories']);
    }

    public function delete(Supplier $supplier): void
    {
        $this->assertDeletable($supplier);

        foreach (['profile_photo_path', 'card_front_path', 'card_back_path'] as $field) {
            if (filled($supplier->{$field})) {
                if ($field === 'profile_photo_path') {
                    continue;
                } else {
                    Storage::disk('public')->delete($supplier->{$field});
                }
            }
        }

        $supplier->delete();
    }

    public function assertDeletable(Supplier $supplier): void
    {
        $purchaseCount = Purchase::query()->where('supplier_id', $supplier->id)->count();
        $quotationCount = PurchaseQuotation::query()->where('supplier_id', $supplier->id)->count();
        $paymentCount = FundTransfer::query()
            ->where('destination_supplier_id', $supplier->id)
            ->count();
        $assignmentCount = OnlineSaleOrderSupplierAssignment::query()
            ->where('supplier_id', $supplier->id)
            ->count();
        $summaryCount = PurchasePaymentSummaryItem::query()
            ->where('supplier_id', $supplier->id)
            ->count();

        if ($purchaseCount + $quotationCount + $paymentCount + $assignmentCount + $summaryCount > 0) {
            throw ValidationException::withMessages([
                'supplier' => [__('suppliers.messages.delete_blocked')],
            ]);
        }
    }

    public function nextSupplierCode(): string
    {
        return app(DocumentCounterService::class)->next(
            'suppliers.supplier_code',
            'SUP-',
            5,
            fn (): int => DocumentCounterService::maximumSuffix('suppliers', 'supplier_code', 'SUP-'),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{contact_name_en: string, contact_name_ur: string, mobile_number: string}>
     */
    public function normalizeContacts(array $rows): array
    {
        return array_values(array_filter(
            array_map(static function (array $row): array {
                return [
                    'contact_name_en' => trim((string) ($row['contact_name_en'] ?? '')),
                    'contact_name_ur' => trim((string) ($row['contact_name_ur'] ?? '')),
                    'mobile_number' => trim((string) ($row['mobile_number'] ?? '')),
                ];
            }, $rows),
            static fn (array $row): bool => filled($row['mobile_number'])
                || filled($row['contact_name_en'])
                || filled($row['contact_name_ur']),
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, string>>
     */
    public function normalizeBankRows(array $rows): array
    {
        $normalized = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $bankKey = trim((string) ($row['bank_key'] ?? ''));

            if ($bankKey === '' && ! filled($row['account_title'] ?? null) && ! filled($row['account_number'] ?? null)) {
                continue;
            }

            $normalized[] = [
                'bank_key' => $bankKey,
                'bank_name_en' => $bankKey !== '' ? PakistaniBanks::english($bankKey) : trim((string) ($row['bank_name_en'] ?? '')),
                'bank_name_ur' => $bankKey !== '' ? PakistaniBanks::urdu($bankKey) : trim((string) ($row['bank_name_ur'] ?? '')),
                'account_title' => trim((string) ($row['account_title'] ?? '')),
                'account_title_ur' => trim((string) ($row['account_title_ur'] ?? '')),
                'account_number' => trim((string) ($row['account_number'] ?? '')),
            ];
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    public function toFormState(Supplier $supplier): array
    {
        $contacts = is_array($supplier->contacts) ? $supplier->contacts : [];
        $banks = is_array($supplier->bank_accounts) ? $supplier->bank_accounts : [];

        return [
            'supplier_code' => (string) $supplier->supplier_code,
            'supplier_type' => (string) ($supplier->supplier_type ?? ''),
            'status' => (string) $supplier->status,
            'business_name_en' => (string) $supplier->business_name_en,
            'business_name_ur' => (string) ($supplier->business_name_ur ?? ''),
            'country_code' => (string) $supplier->country_code,
            'country_ur' => (string) ($supplier->country_ur ?? ''),
            'city_key' => (string) ($supplier->city_key ?? ''),
            'city_manual' => $supplier->country_code !== 'PK' ? (string) ($supplier->city_en ?? '') : '',
            'city_en' => (string) ($supplier->city_en ?? ''),
            'city_ur' => (string) ($supplier->city_ur ?? ''),
            'address_en' => (string) $supplier->address_en,
            'address_ur' => (string) ($supplier->address_ur ?? ''),
            'credit_limit' => $supplier->credit_limit !== null ? (string) $supplier->credit_limit : '',
            'opening_balance' => $supplier->opening_balance !== null ? (string) $supplier->opening_balance : '',
            'contactRows' => $contacts !== []
                ? array_values(array_map(static fn (array $row): array => [
                    'contact_name_en' => (string) ($row['contact_name_en'] ?? ''),
                    'contact_name_ur' => (string) ($row['contact_name_ur'] ?? ''),
                    'mobile_number' => (string) ($row['mobile_number'] ?? ''),
                ], $contacts))
                : [['contact_name_en' => '', 'contact_name_ur' => '', 'mobile_number' => '']],
            'bankRows' => $banks !== []
                ? array_values(array_map(static fn (array $row): array => [
                    'bank_key' => (string) ($row['bank_key'] ?? ''),
                    'bank_name_en' => (string) ($row['bank_name_en'] ?? ''),
                    'bank_name_ur' => (string) ($row['bank_name_ur'] ?? ''),
                    'account_title' => (string) ($row['account_title'] ?? ''),
                    'account_title_ur' => (string) ($row['account_title_ur'] ?? ''),
                    'account_number' => (string) ($row['account_number'] ?? ''),
                ], $banks))
                : [['bank_key' => '', 'bank_name_en' => '', 'bank_name_ur' => '', 'account_title' => '', 'account_title_ur' => '', 'account_number' => '']],
            'category_ids' => $supplier->categories->pluck('id')->map(fn ($id): int => (int) $id)->all(),
            'portal_enabled' => (bool) $supplier->portal_enabled,
            'email' => (string) ($supplier->email ?? ''),
            'ai_status' => $supplier->ai_status?->value ?? 'none',
            'profile_photo_path' => $supplier->profile_photo_path,
            'card_front_path' => $supplier->card_front_path,
            'card_back_path' => $supplier->card_back_path,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validate(array $data, ?int $exceptId = null): array
    {
        $portalEnabled = ! empty($data['portal_enabled']);

        $rules = [
            'supplier_code' => ['nullable', 'string', 'max:32', Rule::unique('suppliers', 'supplier_code')->ignore($exceptId)],
            'supplier_type' => ['nullable', 'string', 'max:64'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'business_name_en' => ['required_without:business_name_ur', 'nullable', 'string', 'max:255'],
            'business_name_ur' => ['required_without:business_name_en', 'nullable', 'string', 'max:255'],
            'country_code' => ['required', 'string', 'max:8'],
            'country_ur' => ['nullable', 'string', 'max:255'],
            'city_key' => ['nullable', 'string', 'max:64'],
            'city_en' => ['nullable', 'string', 'max:255'],
            'city_ur' => ['nullable', 'string', 'max:255'],
            'address_en' => ['required_without:address_ur', 'nullable', 'string', 'max:2000'],
            'address_ur' => ['required_without:address_en', 'nullable', 'string', 'max:2000'],
            'credit_limit' => ['nullable', 'numeric'],
            'opening_balance' => ['nullable', 'numeric'],
            'contacts' => ['nullable', 'array'],
            'contacts.*.contact_name_en' => ['nullable', 'string', 'max:255'],
            'contacts.*.contact_name_ur' => ['nullable', 'string', 'max:255'],
            'contacts.*.mobile_number' => ['nullable', 'string', 'max:30'],
            'bank_accounts' => ['nullable', 'array'],
            'bank_accounts.*.bank_key' => ['nullable', 'string', 'max:64'],
            'bank_accounts.*.bank_name_en' => ['nullable', 'string', 'max:255'],
            'bank_accounts.*.bank_name_ur' => ['nullable', 'string', 'max:255'],
            'bank_accounts.*.account_title' => ['nullable', 'string', 'max:255'],
            'bank_accounts.*.account_title_ur' => ['nullable', 'string', 'max:255'],
            'bank_accounts.*.account_number' => ['nullable', 'string', 'max:64'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'portal_enabled' => ['boolean'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('suppliers', 'email')->ignore($exceptId),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'profile_photo_path' => ['nullable', 'string', 'max:500'],
            'card_front_path' => ['nullable', 'string', 'max:500'],
            'card_back_path' => ['nullable', 'string', 'max:500'],
            'ai_status' => ['nullable', 'string', 'in:none,pending,enriched,failed,manual'],
        ];

        if ($portalEnabled) {
            $rules['email'] = [
                'required',
                'email',
                'max:255',
                Rule::unique('suppliers', 'email')->ignore($exceptId),
            ];

            if ($exceptId === null) {
                $rules['password'] = ['required', 'string', 'min:8'];
            }
        }

        if (($data['country_code'] ?? 'PK') === 'PK') {
            $rules['city_key'] = ['required', 'string', 'max:64'];
        } else {
            $rules['city_en'] = ['required', 'string', 'max:255'];
        }

        $validator = validator($data, $rules);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $validated = Arr::only($validator->validated(), array_keys($rules));
        $validated['portal_enabled'] = (bool) ($validated['portal_enabled'] ?? false);
        $validated['ai_status'] = $validated['ai_status'] ?? 'none';
        $validated['opening_balance'] = $validated['opening_balance'] ?? 0;

        $contacts = $this->normalizeContacts(is_array($validated['contacts'] ?? null) ? $validated['contacts'] : []);

        if ($contacts === []) {
            throw ValidationException::withMessages([
                'contactRows.0.mobile_number' => [__('suppliers.validation.contact_required')],
            ]);
        }

        $primary = $contacts[0];

        if (! filled($primary['mobile_number'])) {
            throw ValidationException::withMessages([
                'contactRows.0.mobile_number' => [__('suppliers.validation.mobile_required')],
            ]);
        }

        if (! filled($primary['contact_name_en']) && ! filled($primary['contact_name_ur'])) {
            throw ValidationException::withMessages([
                'contactRows.0.contact_name_en' => [__('suppliers.validation.contact_name_required')],
            ]);
        }

        $validated['contacts'] = $contacts;
        $validated['bank_accounts'] = $this->normalizeBankRows(is_array($validated['bank_accounts'] ?? null) ? $validated['bank_accounts'] : []);
        $validated['category_ids'] = array_values(array_unique(array_map('intval', $validated['category_ids'] ?? [])));

        if (! filled($validated['business_name_en'] ?? null) && filled($validated['business_name_ur'] ?? null)) {
            $validated['business_name_en'] = $validated['business_name_ur'];
        }

        if (! filled($validated['business_name_ur'] ?? null) && filled($validated['business_name_en'] ?? null)) {
            $validated['business_name_ur'] = null;
        }

        if (! filled($validated['address_en'] ?? null) && filled($validated['address_ur'] ?? null)) {
            $validated['address_en'] = $validated['address_ur'];
        }

        return $validated;
    }

    public function storeUpload(TemporaryUploadedFile $upload, string $directory): string
    {
        return app(OptimizedImageStorage::class)->store($upload, $directory);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function replaceStoredFiles(Supplier $supplier, array &$validated): void
    {
        foreach ([
            'profile_photo_path' => $supplier->profile_photo_path,
            'card_front_path' => $supplier->card_front_path,
            'card_back_path' => $supplier->card_back_path,
        ] as $field => $existing) {
            if (! array_key_exists($field, $validated)) {
                continue;
            }

            $newPath = $validated[$field];

            if (filled($newPath) && filled($existing) && $newPath !== $existing) {
                if ($field === 'profile_photo_path') {
                    continue;
                } else {
                    Storage::disk('public')->delete($existing);
                }
            }

            if (empty($newPath)) {
                $validated[$field] = $existing;
            }
        }
    }
}
