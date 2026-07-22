<?php

namespace App\Livewire\Suppliers;

use App\Enums\CategoryAiStatus;
use App\Filament\Forms\CategoryForm;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Contacts\SupplierEnrichmentService;
use App\Services\Contacts\SupplierService;
use App\Support\LivewireImagePreview;
use App\Support\PakistaniBanks;
use App\Support\PakistaniCities;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Form extends Component
{
    use WithFileUploads;

    public ?int $supplierId = null;

    public string $mode = 'create';

    public string $supplier_code = '';

    public string $supplier_type = '';

    public string $status = 'active';

    public string $country_code = 'PK';

    public string $country_ur = '';

    public string $city_key = '';

    public string $city_manual = '';

    public string $city_en = '';

    public string $city_ur = '';

    public string $address_en = '';

    public string $address_ur = '';

    public string $business_name_en = '';

    public string $business_name_ur = '';

    public string $credit_limit = '';

    public string $opening_balance = '';

    /** @var list<array{contact_name_en: string, contact_name_ur: string, mobile_number: string}> */
    public array $contactRows = [
        ['contact_name_en' => '', 'contact_name_ur' => '', 'mobile_number' => ''],
    ];

    /** @var list<int> */
    public array $category_ids = [];

    public string $category_search = '';

    /** @var list<array{bank_key: string, bank_name_en: string, bank_name_ur: string, account_title: string, account_title_ur: string, account_number: string}> */
    public array $bankRows = [
        ['bank_key' => '', 'bank_name_en' => '', 'bank_name_ur' => '', 'account_title' => '', 'account_title_ur' => '', 'account_number' => ''],
    ];

    /** @var array<int, string> */
    public array $bankSearches = [''];

    public bool $portal_enabled = false;

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public ?TemporaryUploadedFile $profilePhotoUpload = null;

    public ?TemporaryUploadedFile $cardFrontUpload = null;

    public ?TemporaryUploadedFile $cardBackUpload = null;

    public ?string $profilePhotoPreviewUrl = null;

    public ?string $cardFrontPreviewUrl = null;

    public ?string $cardBackPreviewUrl = null;

    public ?string $storedProfilePhotoPath = null;

    public ?string $storedCardFrontPath = null;

    public ?string $storedCardBackPath = null;

    public string $ai_status = 'none';

    public bool $saving = false;

    public bool $enriching = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    #[On('supplier-form-open')]
    public function openForm(?int $supplierId = null, string $mode = 'create'): void
    {
        $this->resetForm();
        $this->mode = $mode;
        $this->supplierId = $supplierId;

        if ($supplierId) {
            $this->loadSupplier($supplierId);
        }
    }

    #[On('supplier-form-action')]
    public function handlePanelAction(string $action): void
    {
        match ($action) {
            'save_close' => $this->saveAndClose(),
            'save_next' => $this->saveAndAddNext(),
            'ai' => $this->enrichWithAi(),
            default => null,
        };
    }

    public function updatedCountryCode(): void
    {
        $this->syncCountryUr();
        $this->city_key = '';
        $this->city_manual = '';
        $this->city_en = '';
        $this->city_ur = '';
    }

    public function updatedCityManual(): void
    {
        if ($this->country_code !== 'PK') {
            $this->city_en = $this->city_manual;
        }
    }

    public function updatedCityKey(): void
    {
        if ($this->country_code !== 'PK' || $this->city_key === '') {
            $this->city_en = '';
            $this->city_ur = '';

            return;
        }

        $this->city_en = PakistaniCities::english($this->city_key);
        $this->city_ur = PakistaniCities::urdu($this->city_key);
    }

    public static function resolveCityUr(string $cityKey): string
    {
        return PakistaniCities::urdu($cityKey);
    }

    public function updatedProfilePhotoUpload(): void
    {
        $this->validateOnly('profilePhotoUpload', [
            'profilePhotoUpload' => ['nullable', 'image', 'max:5120'],
        ]);

        $this->profilePhotoPreviewUrl = $this->profilePhotoUpload instanceof TemporaryUploadedFile
            ? LivewireImagePreview::url($this->profilePhotoUpload)
            : null;
    }

    public function updatedCardFrontUpload(): void
    {
        $this->validateOnly('cardFrontUpload', [
            'cardFrontUpload' => ['nullable', 'image', 'max:5120'],
        ]);

        $this->cardFrontPreviewUrl = $this->cardFrontUpload instanceof TemporaryUploadedFile
            ? LivewireImagePreview::url($this->cardFrontUpload)
            : null;
    }

    public function updatedCardBackUpload(): void
    {
        $this->validateOnly('cardBackUpload', [
            'cardBackUpload' => ['nullable', 'image', 'max:5120'],
        ]);

        $this->cardBackPreviewUrl = $this->cardBackUpload instanceof TemporaryUploadedFile
            ? LivewireImagePreview::url($this->cardBackUpload)
            : null;
    }

    public function updatedEnriching(): void
    {
        $this->dispatchBusyState();
    }

    public function updatedSaving(): void
    {
        $this->dispatchBusyState();
    }

    public function updatedCategorySearch(): void
    {
        $this->category_search = trim($this->category_search);
    }

    public function addContactRow(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->contactRows[] = ['contact_name_en' => '', 'contact_name_ur' => '', 'mobile_number' => ''];
    }

    public function removeContactRow(int $index): void
    {
        if ($this->isReadonly() || count($this->contactRows) <= 1) {
            return;
        }

        unset($this->contactRows[$index]);
        $this->contactRows = array_values($this->contactRows);
    }

    public function addBankRow(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->bankRows[] = ['bank_key' => '', 'bank_name_en' => '', 'bank_name_ur' => '', 'account_title' => '', 'account_title_ur' => '', 'account_number' => ''];
        $this->bankSearches[] = '';
    }

    public function removeBankRow(int $index): void
    {
        if ($this->isReadonly() || count($this->bankRows) <= 1) {
            return;
        }

        unset($this->bankRows[$index]);
        unset($this->bankSearches[$index]);
        $this->bankRows = array_values($this->bankRows);
        $this->bankSearches = array_values($this->bankSearches);
    }

    public function selectBank(int $index, string $bankKey): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->bankRows[$index]['bank_key'] = $bankKey;
        $this->bankRows[$index]['bank_name_en'] = PakistaniBanks::english($bankKey);
        $this->bankRows[$index]['bank_name_ur'] = PakistaniBanks::urdu($bankKey);
        $this->bankSearches[$index] = '';
    }

    public function clearBank(int $index): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->bankRows[$index]['bank_key'] = '';
        $this->bankRows[$index]['bank_name_en'] = '';
        $this->bankRows[$index]['bank_name_ur'] = '';
        $this->bankSearches[$index] = '';
    }

    public function addCategory(int $id): void
    {
        if ($this->isReadonly() || in_array($id, $this->category_ids, true)) {
            $this->category_search = '';

            return;
        }

        $this->category_ids[] = $id;
        $this->category_search = '';
    }

    public function removeCategory(int $id): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->category_ids = array_values(array_filter(
            $this->category_ids,
            fn (int $categoryId): bool => $categoryId !== $id,
        ));
    }

    public function enrichWithAi(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->syncCityFields();
        $this->enriching = true;
        $this->ai_status = CategoryAiStatus::Pending->value;
        $this->statusMessage = null;
        $this->dispatchBusyState();

        try {
            $result = app(SupplierEnrichmentService::class)->enrich($this->enrichmentPayload());

            if (! $result['success']) {
                $this->ai_status = CategoryAiStatus::Failed->value;
                $this->setStatus($result['message'], 'danger');

                return;
            }

            $this->applyEnrichmentData($result['data'] ?? []);
            $this->ai_status = CategoryAiStatus::Enriched->value;
            $this->setStatus($result['message'], 'success');
        } catch (\Throwable $exception) {
            $this->ai_status = CategoryAiStatus::Failed->value;
            $this->setStatus(\App\Support\FriendlyError::message($exception), 'danger');
        } finally {
            $this->enriching = false;
            $this->dispatchBusyState();
        }
    }

    public function saveAndClose(): void
    {
        if ($this->persist()) {
            $this->dispatch('suppliers-saved');
            $this->dispatch('close-form-panel');
        }
    }

    public function saveAndAddNext(): void
    {
        if ($this->persist()) {
            $this->dispatch('suppliers-saved');
            $this->resetForm();
        }
    }

    public function isReadonly(): bool
    {
        return $this->mode === 'view';
    }

    public function render(): View
    {
        return view('livewire.suppliers.form', [
            'supplierTypeOptions' => $this->supplierTypeOptions(),
            'statusOptions' => $this->statusOptions(),
            'countryOptionsEn' => $this->countryOptions('en'),
            'citiesByProvince' => $this->citiesByProvince(),
            'filteredCategoryOptions' => $this->filteredCategoryOptions(),
            'selectedCategories' => CategoryForm::categoryLabelsByIds($this->category_ids),
            'filteredBanksByRow' => $this->filteredBanksByRow(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function supplierTypeOptions(): array
    {
        $locale = app()->getLocale();
        $suffix = $locale === 'ur' ? 'ur' : 'en';

        return collect(config('supplier_types', []))
            ->mapWithKeys(fn (array $labels, string $key): array => [
                $key => $labels[$suffix] ?? $labels['en'] ?? $key,
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function statusOptions(): array
    {
        return collect(['active', 'inactive'])
            ->mapWithKeys(fn (string $status): array => [
                $status => __('suppliers.statuses.' . $status),
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function countryOptions(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        $suffix = $locale === 'ur' ? 'ur' : 'en';
        $countries = config('countries', []);
        $options = [];

        if (isset($countries['PK'])) {
            $options['PK'] = $countries['PK'][$suffix] ?? $countries['PK']['en'];
        }

        foreach ($countries as $code => $labels) {
            if ($code === 'PK') {
                continue;
            }

            $options[$code] = $labels[$suffix] ?? $labels['en'] ?? $code;
        }

        return $options;
    }

    private function syncCountryUr(): void
    {
        $country = config('countries.' . $this->country_code);

        $this->country_ur = is_array($country) ? (string) ($country['ur'] ?? '') : '';
    }

    /**
     * @return array<string, array<string, array{en: string, ur: string, province: string}>>
     */
    private function citiesByProvince(): array
    {
        $grouped = [];

        foreach (config('pakistani_cities', []) as $key => $city) {
            if (! is_array($city)) {
                continue;
            }

            $city['ur'] = PakistaniCities::urdu($key);
            $city['en'] = PakistaniCities::english($key);
            $province = (string) ($city['province'] ?? 'Other');
            $grouped[$province][$key] = $city;
        }

        foreach ($grouped as $province => $cities) {
            uasort($cities, fn (array $a, array $b): int => strcmp($a['en'] ?? '', $b['en'] ?? ''));
            $grouped[$province] = $cities;
        }

        ksort($grouped);

        return $grouped;
    }

    /**
     * @return array<int, string>
     */
    private function filteredCategoryOptions(): array
    {
        $needle = trim($this->category_search);

        if (mb_strlen($needle) < 2) {
            return [];
        }

        return CategoryForm::searchCategoryLabels($needle, excludeIds: $this->category_ids);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function filteredBanksByRow(): array
    {
        $results = [];

        foreach ($this->bankSearches as $index => $search) {
            $results[$index] = PakistaniBanks::search((string) $search);
        }

        return $results;
    }

    private function persist(): bool
    {
        if ($this->isReadonly()) {
            return false;
        }

        $permission = $this->supplierId ? 'edit' : 'create';

        if (! $this->userCan($permission)) {
            $this->setStatus(__('agricart.access.denied'), 'danger');

            return false;
        }

        $this->saving = true;
        $this->statusMessage = null;
        $this->dispatchBusyState();

        try {
            $this->syncCityFields();
            $this->syncCountryUr();
            $this->validate($this->formRules(), $this->formValidationMessages());
            $this->persistUploadedFiles();

            $service = app(SupplierService::class);
            $payload = $this->savePayload();

            if ($this->supplierId) {
                $supplier = Supplier::query()->findOrFail($this->supplierId);
                $supplier = $service->update($supplier, $payload);
                $this->supplier_code = (string) $supplier->supplier_code;
                $this->setStatus(__('suppliers.messages.updated', ['name' => $supplier->displayBusinessName()]), 'success');
            } else {
                $supplier = $service->create($payload);
                $this->supplierId = $supplier->id;
                $this->supplier_code = (string) $supplier->supplier_code;
                $this->mode = 'edit';
                $this->password = '';
                $this->password_confirmation = '';
                $this->setStatus(__('suppliers.messages.created', ['name' => $supplier->displayBusinessName()]), 'success');
            }

            return true;
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first();
            $this->setStatus($message ?: __('suppliers.messages.save_failed'), 'danger');

            return false;
        } catch (\Throwable $exception) {
            $this->setStatus(\App\Support\FriendlyError::message($exception), 'danger');

            return false;
        } finally {
            $this->saving = false;
            $this->dispatchBusyState();
        }
    }

    private function loadSupplier(int $supplierId): void
    {
        $supplier = Supplier::query()->with('categories')->findOrFail($supplierId);
        $state = app(SupplierService::class)->toFormState($supplier);

        $this->supplier_code = $state['supplier_code'];
        $this->supplier_type = $state['supplier_type'];
        $this->status = $state['status'];
        $this->business_name_en = $state['business_name_en'];
        $this->business_name_ur = $state['business_name_ur'];
        $this->country_code = $state['country_code'];
        $this->country_ur = $state['country_ur'];
        $this->city_key = $state['city_key'];
        $this->city_manual = $state['city_manual'];
        $this->city_en = $state['city_en'];
        $this->city_ur = $state['city_ur'];
        $this->address_en = $state['address_en'];
        $this->address_ur = $state['address_ur'];
        $this->credit_limit = $state['credit_limit'];
        $this->opening_balance = $state['opening_balance'];
        $this->contactRows = $state['contactRows'];
        $this->bankRows = $state['bankRows'];
        $this->bankSearches = array_fill(0, count($this->bankRows), '');
        $this->category_ids = $state['category_ids'];
        $this->portal_enabled = $state['portal_enabled'];
        $this->email = $state['email'];
        $this->ai_status = $state['ai_status'];
        $this->storedProfilePhotoPath = $state['profile_photo_path'];
        $this->storedCardFrontPath = $state['card_front_path'];
        $this->storedCardBackPath = $state['card_back_path'];
        $this->profilePhotoPreviewUrl = $this->storageUrl($state['profile_photo_path']);
        $this->cardFrontPreviewUrl = $this->storageUrl($state['card_front_path']);
        $this->cardBackPreviewUrl = $this->storageUrl($state['card_back_path']);
    }

    /**
     * @return array<string, mixed>
     */
    private function savePayload(): array
    {
        $payload = [
            'supplier_code' => filled($this->supplier_code) ? $this->supplier_code : null,
            'supplier_type' => $this->supplier_type ?: null,
            'status' => $this->status,
            'business_name_en' => $this->business_name_en,
            'business_name_ur' => $this->business_name_ur,
            'country_code' => $this->country_code,
            'country_ur' => $this->country_ur,
            'city_key' => $this->country_code === 'PK' ? $this->city_key : null,
            'city_en' => $this->city_en,
            'city_ur' => $this->city_ur,
            'address_en' => $this->address_en,
            'address_ur' => $this->address_ur,
            'credit_limit' => $this->credit_limit !== '' ? $this->credit_limit : null,
            'opening_balance' => $this->opening_balance !== '' ? $this->opening_balance : 0,
            'contacts' => $this->contactRows,
            'bank_accounts' => $this->bankRows,
            'category_ids' => $this->category_ids,
            'portal_enabled' => $this->portal_enabled,
            'email' => $this->email ?: null,
            'profile_photo_path' => $this->storedProfilePhotoPath,
            'card_front_path' => $this->storedCardFrontPath,
            'card_back_path' => $this->storedCardBackPath,
            'ai_status' => $this->ai_status,
        ];

        if ($this->portal_enabled && ($this->mode === 'create' || filled($this->password))) {
            $payload['password'] = $this->password;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function enrichmentPayload(): array
    {
        $this->syncCityFields();
        $this->syncCountryUr();

        return [
            'business_name_en' => $this->business_name_en,
            'business_name_ur' => $this->business_name_ur,
            'country_ur' => $this->country_ur,
            'city_en' => $this->city_en,
            'city_ur' => $this->city_ur,
            'address_en' => $this->address_en,
            'address_ur' => $this->address_ur,
            'contacts' => $this->contactRows,
            'bank_accounts' => $this->bankRows,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyEnrichmentData(array $data): void
    {
        foreach ([
            'business_name_en',
            'business_name_ur',
            'address_en',
            'address_ur',
            'city_en',
            'city_ur',
        ] as $key) {
            if (filled($data[$key] ?? null) && ! filled($this->{$key})) {
                $this->{$key} = (string) $data[$key];
            }
        }

        if (isset($data['contacts']) && is_array($data['contacts'])) {
            foreach ($data['contacts'] as $index => $contact) {
                if (! is_array($contact) || ! isset($this->contactRows[$index])) {
                    continue;
                }

                foreach (['contact_name_en', 'contact_name_ur'] as $key) {
                    if (filled($contact[$key] ?? null) && ! filled($this->contactRows[$index][$key] ?? null)) {
                        $this->contactRows[$index][$key] = (string) $contact[$key];
                    }
                }
            }
        }

        if (isset($data['bank_accounts']) && is_array($data['bank_accounts'])) {
            foreach ($data['bank_accounts'] as $index => $bank) {
                if (! is_array($bank) || ! isset($this->bankRows[$index])) {
                    continue;
                }

                foreach (['account_title', 'account_title_ur'] as $key) {
                    if (filled($bank[$key] ?? null) && ! filled($this->bankRows[$index][$key] ?? null)) {
                        $this->bankRows[$index][$key] = (string) $bank[$key];
                    }
                }
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formRules(): array
    {
        $rules = [
            'business_name_en' => ['required_without:business_name_ur', 'nullable', 'string', 'max:255'],
            'business_name_ur' => ['required_without:business_name_en', 'nullable', 'string', 'max:255'],
            'contactRows.0.contact_name_en' => ['required_without:contactRows.0.contact_name_ur', 'nullable', 'string', 'max:255'],
            'contactRows.0.contact_name_ur' => ['required_without:contactRows.0.contact_name_en', 'nullable', 'string', 'max:255'],
            'contactRows.0.mobile_number' => ['required', 'string', 'max:30'],
            'contactRows.*.contact_name_en' => ['nullable', 'string', 'max:255'],
            'contactRows.*.contact_name_ur' => ['nullable', 'string', 'max:255'],
            'contactRows.*.mobile_number' => ['nullable', 'string', 'max:30'],
            'address_en' => ['required_without:address_ur', 'nullable', 'string', 'max:2000'],
            'address_ur' => ['required_without:address_en', 'nullable', 'string', 'max:2000'],
            'country_code' => ['required', 'string', 'max:8'],
            'supplier_type' => ['nullable', 'string', 'max:64'],
            'status' => ['required', 'in:active,inactive'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('suppliers', 'email')->ignore($this->supplierId),
            ],
            'credit_limit' => ['nullable', 'numeric'],
            'opening_balance' => ['nullable', 'numeric'],
            'profilePhotoUpload' => ['nullable', 'image', 'max:5120'],
            'cardFrontUpload' => ['nullable', 'image', 'max:5120'],
            'cardBackUpload' => ['nullable', 'image', 'max:5120'],
        ];

        if ($this->country_code === 'PK') {
            $rules['city_key'] = ['required', 'string'];
        } else {
            $rules['city_manual'] = ['required', 'string', 'max:255'];
        }

        if ($this->portal_enabled && $this->mode === 'create') {
            $rules['email'] = [
                'required',
                'email',
                'max:255',
                Rule::unique('suppliers', 'email')->ignore($this->supplierId),
            ];
            $rules['password'] = ['required', 'string', 'min:8', 'same:password_confirmation'];
            $rules['password_confirmation'] = ['required', 'string'];
        } elseif ($this->portal_enabled && filled($this->password)) {
            $rules['password'] = ['string', 'min:8', 'same:password_confirmation'];
            $rules['password_confirmation'] = ['required_with:password', 'string'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    private function formValidationMessages(): array
    {
        return [
            'business_name_en.required_without' => __('suppliers.validation.business_name_required'),
            'business_name_ur.required_without' => __('suppliers.validation.business_name_required'),
            'contactRows.0.contact_name_en.required_without' => __('suppliers.validation.contact_name_required'),
            'contactRows.0.contact_name_ur.required_without' => __('suppliers.validation.contact_name_required'),
            'contactRows.0.mobile_number.required' => __('suppliers.validation.mobile_required'),
            'address_en.required_without' => __('suppliers.validation.address_required'),
            'address_ur.required_without' => __('suppliers.validation.address_required'),
        ];
    }

    private function persistUploadedFiles(): void
    {
        $service = app(SupplierService::class);

        if ($this->profilePhotoUpload !== null) {
            $this->storedProfilePhotoPath = $service->storeUpload($this->profilePhotoUpload, 'suppliers/profile-photos');
            $this->profilePhotoPreviewUrl = Storage::disk('public')->url($this->storedProfilePhotoPath);
            $this->profilePhotoUpload = null;
        }

        if ($this->cardFrontUpload !== null) {
            $this->storedCardFrontPath = $service->storeUpload($this->cardFrontUpload, 'suppliers/cards');
            $this->cardFrontPreviewUrl = Storage::disk('public')->url($this->storedCardFrontPath);
            $this->cardFrontUpload = null;
        }

        if ($this->cardBackUpload !== null) {
            $this->storedCardBackPath = $service->storeUpload($this->cardBackUpload, 'suppliers/cards');
            $this->cardBackPreviewUrl = Storage::disk('public')->url($this->storedCardBackPath);
            $this->cardBackUpload = null;
        }
    }

    private function syncCityFields(): void
    {
        if ($this->country_code === 'PK') {
            if ($this->city_key !== '') {
                $this->city_en = PakistaniCities::english($this->city_key);
                $this->city_ur = PakistaniCities::urdu($this->city_key);
            }
        } else {
            $this->city_key = '';
            $this->city_en = $this->city_manual;
        }
    }

    private function storageUrl(?string $path): ?string
    {
        return filled($path) ? Storage::disk('public')->url($path) : null;
    }

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }

    private function userCan(string $action): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission('contacts.' . $action);
    }

    private function resetForm(): void
    {
        $this->reset([
            'supplierId',
            'supplier_code',
            'supplier_type',
            'status',
            'country_code',
            'country_ur',
            'city_key',
            'city_manual',
            'city_en',
            'city_ur',
            'address_en',
            'address_ur',
            'business_name_en',
            'business_name_ur',
            'credit_limit',
            'opening_balance',
            'category_ids',
            'category_search',
            'contactRows',
            'portal_enabled',
            'email',
            'password',
            'password_confirmation',
            'profilePhotoUpload',
            'cardFrontUpload',
            'cardBackUpload',
            'profilePhotoPreviewUrl',
            'cardFrontPreviewUrl',
            'cardBackPreviewUrl',
            'storedProfilePhotoPath',
            'storedCardFrontPath',
            'storedCardBackPath',
            'ai_status',
            'saving',
            'enriching',
            'statusMessage',
        ]);

        $this->mode = 'create';
        $this->status = 'active';
        $this->country_code = 'PK';
        $this->syncCountryUr();
        $this->statusType = 'success';
        $this->ai_status = CategoryAiStatus::None->value;
        $this->bankRows = [
            ['bank_key' => '', 'bank_name_en' => '', 'bank_name_ur' => '', 'account_title' => '', 'account_title_ur' => '', 'account_number' => ''],
        ];
        $this->bankSearches = [''];
        $this->contactRows = [
            ['contact_name_en' => '', 'contact_name_ur' => '', 'mobile_number' => ''],
        ];
    }

    private function dispatchBusyState(): void
    {
        $this->dispatch('supplier-form-busy', enriching: $this->enriching, saving: $this->saving);
    }
}
