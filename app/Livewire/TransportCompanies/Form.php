<?php

namespace App\Livewire\TransportCompanies;

use App\Models\TransportCompany;
use App\Models\User;
use App\Services\Contacts\TransportCompanyService;
use App\Support\LivewireImagePreview;
use App\Support\PakistaniBanks;
use App\Support\PakistaniCities;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Form extends Component
{
    use WithFileUploads;

    public ?int $transportCompanyId = null;

    public string $mode = 'create';

    public string $transport_code = '';

    public string $transport_type = '';

    public string $status = 'active';

    public string $country_code = 'PK';

    public string $country_ur = '';

    public string $city_key = '';

    public string $city_manual = '';

    public string $city_en = '';

    public string $city_ur = '';

    public string $business_name_en = '';

    public string $business_name_ur = '';

    public string $address_en = '';

    public string $address_ur = '';

    public string $destination_business_name_en = '';

    public string $destination_business_name_ur = '';

    public string $destination_country_code = 'PK';

    public string $destination_country_ur = '';

    public string $destination_city_key = '';

    public string $destination_city_manual = '';

    public string $destination_city_en = '';

    public string $destination_city_ur = '';

    public string $destination_address_en = '';

    public string $destination_address_ur = '';

    public string $credit_limit = '';

    public string $opening_balance = '';

    /** @var list<array{from_key: string, from_en: string, from_ur: string, to_key: string, to_en: string, to_ur: string}> */
    public array $routeRows = [
        ['from_key' => '', 'from_en' => '', 'from_ur' => '', 'to_key' => '', 'to_en' => '', 'to_ur' => ''],
    ];

    /**
     * @var list<array{address_en: string, address_ur: string, contacts: list<array{contact_name_en: string, contact_name_ur: string, mobile_number: string}>}>
     */
    public array $branchRows = [
        [
            'address_en' => '',
            'address_ur' => '',
            'contacts' => [
                ['contact_name_en' => '', 'contact_name_ur' => '', 'mobile_number' => ''],
            ],
        ],
    ];

    /**
     * @var list<array{address_en: string, address_ur: string, contacts: list<array{contact_name_en: string, contact_name_ur: string, mobile_number: string}>}>
     */
    public array $destinationBranchRows = [
        [
            'address_en' => '',
            'address_ur' => '',
            'contacts' => [
                ['contact_name_en' => '', 'contact_name_ur' => '', 'mobile_number' => ''],
            ],
        ],
    ];

    /** @var list<array{contact_name_en: string, contact_name_ur: string, mobile_number: string}> */
    public array $contactRows = [
        ['contact_name_en' => '', 'contact_name_ur' => '', 'mobile_number' => ''],
    ];

    /** @var list<array{bank_key: string, bank_name_en: string, bank_name_ur: string, account_title: string, account_title_ur: string, account_number: string}> */
    public array $bankRows = [
        ['bank_key' => '', 'bank_name_en' => '', 'bank_name_ur' => '', 'account_title' => '', 'account_title_ur' => '', 'account_number' => ''],
    ];

    /** @var array<int, string> */
    public array $bankSearches = [''];

    public ?TemporaryUploadedFile $profilePhotoUpload = null;

    public ?TemporaryUploadedFile $cardFrontUpload = null;

    public ?TemporaryUploadedFile $cardBackUpload = null;

    public ?string $profilePhotoPreviewUrl = null;

    public ?string $cardFrontPreviewUrl = null;

    public ?string $cardBackPreviewUrl = null;

    public ?string $storedProfilePhotoPath = null;

    public ?string $storedCardFrontPath = null;

    public ?string $storedCardBackPath = null;

    public bool $saving = false;

    public bool $enriching = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    public function mount(): void
    {
        $this->syncCountryUr();
        $this->syncDestinationCountryUr();
    }

    #[On('transport-company-form-open')]
    public function openForm(?int $transportCompanyId = null, string $mode = 'create'): void
    {
        $this->resetForm();
        $this->mode = $mode;
        $this->transportCompanyId = $transportCompanyId;

        if ($transportCompanyId) {
            $this->loadCompany($transportCompanyId);
        }
    }

    #[On('transport-company-form-action')]
    public function handlePanelAction(string $action): void
    {
        match ($action) {
            'save_close' => $this->saveAndClose(),
            'save_next' => $this->saveAndAddNext(),
            'ai' => $this->enrichWithAi(),
            default => null,
        };
    }

    public function enrichWithAi(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->enriching = true;
        $this->statusMessage = null;
        $this->dispatchBusyState();

        try {
            $result = app(\App\Services\Contacts\SupplierEnrichmentService::class)
                ->enrich($this->enrichmentPayload());

            if (! ($result['success'] ?? false)) {
                $this->setStatus($result['message'] ?? __('transport_companies.messages.ai_failed'), 'danger');

                return;
            }

            $this->applyEnrichmentData($result['data'] ?? []);
            $this->setStatus(__('transport_companies.messages.ai_enriched'), 'success');
        } catch (\Throwable $exception) {
            $this->setStatus(\App\Support\FriendlyError::message($exception), 'danger');
        } finally {
            $this->enriching = false;
            $this->dispatchBusyState();
        }
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

    public function updatedDestinationCountryCode(): void
    {
        $this->syncDestinationCountryUr();
        $this->destination_city_key = '';
        $this->destination_city_manual = '';
        $this->destination_city_en = '';
        $this->destination_city_ur = '';
    }

    public function updatedDestinationCityManual(): void
    {
        if ($this->destination_country_code !== 'PK') {
            $this->destination_city_en = $this->destination_city_manual;
        }
    }

    public function updatedDestinationCityKey(): void
    {
        if ($this->destination_country_code !== 'PK' || $this->destination_city_key === '') {
            $this->destination_city_en = '';
            $this->destination_city_ur = '';

            return;
        }

        $this->destination_city_en = PakistaniCities::english($this->destination_city_key);
        $this->destination_city_ur = PakistaniCities::urdu($this->destination_city_key);
    }

    public function updatedProfilePhotoUpload(): void
    {
        $this->validateOnly('profilePhotoUpload', ['profilePhotoUpload' => ['nullable', 'image', 'max:5120']]);
        $this->profilePhotoPreviewUrl = $this->profilePhotoUpload instanceof TemporaryUploadedFile
            ? LivewireImagePreview::url($this->profilePhotoUpload)
            : null;
    }

    public function updatedCardFrontUpload(): void
    {
        $this->validateOnly('cardFrontUpload', ['cardFrontUpload' => ['nullable', 'image', 'max:5120']]);
        $this->cardFrontPreviewUrl = $this->cardFrontUpload instanceof TemporaryUploadedFile
            ? LivewireImagePreview::url($this->cardFrontUpload)
            : null;
    }

    public function updatedCardBackUpload(): void
    {
        $this->validateOnly('cardBackUpload', ['cardBackUpload' => ['nullable', 'image', 'max:5120']]);
        $this->cardBackPreviewUrl = $this->cardBackUpload instanceof TemporaryUploadedFile
            ? LivewireImagePreview::url($this->cardBackUpload)
            : null;
    }

    public function addRouteRow(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->routeRows[] = ['from_key' => '', 'from_en' => '', 'from_ur' => '', 'to_key' => '', 'to_en' => '', 'to_ur' => ''];
    }

    public function removeRouteRow(int $index): void
    {
        if ($this->isReadonly() || count($this->routeRows) <= 1) {
            return;
        }

        unset($this->routeRows[$index]);
        $this->routeRows = array_values($this->routeRows);
    }

    public function addBranchRow(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->branchRows[] = [
            'address_en' => '',
            'address_ur' => '',
            'contacts' => [
                ['contact_name_en' => '', 'contact_name_ur' => '', 'mobile_number' => ''],
            ],
        ];
    }

    public function removeBranchRow(int $index): void
    {
        if ($this->isReadonly() || count($this->branchRows) <= 1) {
            return;
        }

        unset($this->branchRows[$index]);
        $this->branchRows = array_values($this->branchRows);
    }

    public function addBranchContactRow(int $branchIndex): void
    {
        if ($this->isReadonly() || ! isset($this->branchRows[$branchIndex])) {
            return;
        }

        $this->branchRows[$branchIndex]['contacts'][] = [
            'contact_name_en' => '',
            'contact_name_ur' => '',
            'mobile_number' => '',
        ];
    }

    public function removeBranchContactRow(int $branchIndex, int $contactIndex): void
    {
        if ($this->isReadonly() || ! isset($this->branchRows[$branchIndex]['contacts'])) {
            return;
        }

        $contacts = $this->branchRows[$branchIndex]['contacts'];

        if (count($contacts) <= 1) {
            return;
        }

        unset($contacts[$contactIndex]);
        $this->branchRows[$branchIndex]['contacts'] = array_values($contacts);
    }

    public function addDestinationBranchRow(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->destinationBranchRows[] = [
            'address_en' => '',
            'address_ur' => '',
            'contacts' => [
                ['contact_name_en' => '', 'contact_name_ur' => '', 'mobile_number' => ''],
            ],
        ];
    }

    public function removeDestinationBranchRow(int $index): void
    {
        if ($this->isReadonly() || count($this->destinationBranchRows) <= 1) {
            return;
        }

        unset($this->destinationBranchRows[$index]);
        $this->destinationBranchRows = array_values($this->destinationBranchRows);
    }

    public function addDestinationBranchContactRow(int $branchIndex): void
    {
        if ($this->isReadonly() || ! isset($this->destinationBranchRows[$branchIndex])) {
            return;
        }

        $this->destinationBranchRows[$branchIndex]['contacts'][] = [
            'contact_name_en' => '',
            'contact_name_ur' => '',
            'mobile_number' => '',
        ];
    }

    public function removeDestinationBranchContactRow(int $branchIndex, int $contactIndex): void
    {
        if ($this->isReadonly() || ! isset($this->destinationBranchRows[$branchIndex]['contacts'])) {
            return;
        }

        $contacts = $this->destinationBranchRows[$branchIndex]['contacts'];

        if (count($contacts) <= 1) {
            return;
        }

        unset($contacts[$contactIndex]);
        $this->destinationBranchRows[$branchIndex]['contacts'] = array_values($contacts);
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

        unset($this->bankRows[$index], $this->bankSearches[$index]);
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

    public function saveAndClose(): void
    {
        if ($this->persist()) {
            $this->dispatch('transport-companies-saved');
            $this->dispatch('close-form-panel');
        }
    }

    public function saveAndAddNext(): void
    {
        if ($this->persist()) {
            $this->dispatch('transport-companies-saved');
            $this->resetForm();
        }
    }

    public function isReadonly(): bool
    {
        return $this->mode === 'view';
    }

    public function render(): View
    {
        return view('livewire.transport-companies.form', [
            'transportTypeOptions' => $this->transportTypeOptions(),
            'statusOptions' => $this->statusOptions(),
            'countryOptionsEn' => $this->countryOptions('en'),
            'citiesByProvince' => $this->citiesByProvince(),
            'filteredBanksByRow' => $this->filteredBanksByRow(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function enrichmentPayload(): array
    {
        return [
            'business_name_en' => $this->business_name_en,
            'business_name_ur' => $this->business_name_ur,
            'address_en' => $this->address_en,
            'address_ur' => $this->address_ur,
            'country_ur' => $this->country_ur,
            'city_en' => $this->city_en,
            'city_ur' => $this->city_ur,
            'contacts' => array_map(fn (array $row): array => [
                'contact_name_en' => $row['contact_name_en'] ?? '',
                'contact_name_ur' => $row['contact_name_ur'] ?? '',
                'mobile_number' => $row['mobile_number'] ?? '',
            ], $this->contactRows),
            'bank_accounts' => array_map(fn (array $row): array => [
                'bank_key' => $row['bank_key'] ?? '',
                'bank_name_en' => $row['bank_name_en'] ?? '',
                'bank_name_ur' => $row['bank_name_ur'] ?? '',
                'account_title' => $row['account_title'] ?? '',
                'account_title_ur' => $row['account_title_ur'] ?? '',
                'account_number' => $row['account_number'] ?? '',
            ], $this->bankRows),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyEnrichmentData(array $data): void
    {
        foreach (['business_name_en', 'business_name_ur', 'address_en', 'address_ur', 'city_en', 'city_ur'] as $key) {
            if (blank($this->{$key}) && filled($data[$key] ?? null)) {
                $this->{$key} = (string) $data[$key];
            }
        }

        if (blank($this->city_manual) && $this->country_code !== 'PK' && filled($data['city_en'] ?? null)) {
            $this->city_manual = (string) $data['city_en'];
        }

        $contacts = is_array($data['contacts'] ?? null) ? $data['contacts'] : [];
        foreach ($this->contactRows as $index => $row) {
            $parsed = is_array($contacts[$index] ?? null) ? $contacts[$index] : [];

            foreach (['contact_name_en', 'contact_name_ur'] as $key) {
                if (blank($row[$key] ?? null) && filled($parsed[$key] ?? null)) {
                    $this->contactRows[$index][$key] = (string) $parsed[$key];
                }
            }
        }

        $banks = is_array($data['bank_accounts'] ?? null) ? $data['bank_accounts'] : [];
        foreach ($this->bankRows as $index => $row) {
            $parsed = is_array($banks[$index] ?? null) ? $banks[$index] : [];

            foreach (['account_title', 'account_title_ur'] as $key) {
                if (blank($row[$key] ?? null) && filled($parsed[$key] ?? null)) {
                    $this->bankRows[$index][$key] = (string) $parsed[$key];
                }
            }
        }
    }

    private function dispatchBusyState(): void
    {
        $this->dispatch('transport-company-form-busy', enriching: $this->enriching, saving: $this->saving);
    }

    private function persist(): bool
    {
        if ($this->isReadonly()) {
            return false;
        }

        $permission = $this->transportCompanyId ? 'edit' : 'create';

        if (! $this->userCan($permission)) {
            $this->setStatus(__('agricart.access.denied'), 'danger');

            return false;
        }

        $this->saving = true;
        $this->statusMessage = null;
        $this->dispatchBusyState();

        try {
            $this->syncCityFields();
            $this->syncDestinationCityFields();
            $this->persistUploadedFiles();

            $service = app(TransportCompanyService::class);
            $payload = $this->savePayload();

            if ($this->transportCompanyId) {
                $company = TransportCompany::query()->findOrFail($this->transportCompanyId);
                $company = $service->update($company, $payload);
                $this->transport_code = (string) $company->transport_code;
                $this->setStatus(__('transport_companies.messages.updated', ['name' => $company->displayBusinessName()]), 'success');
            } else {
                $company = $service->create($payload);
                $this->transportCompanyId = $company->id;
                $this->transport_code = (string) $company->transport_code;
                $this->mode = 'edit';
                $this->setStatus(__('transport_companies.messages.created', ['name' => $company->displayBusinessName()]), 'success');
            }

            return true;
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first();
            $this->setStatus($message ?: __('transport_companies.messages.save_failed'), 'danger');

            return false;
        } catch (\Throwable $exception) {
            $this->setStatus(\App\Support\FriendlyError::message($exception), 'danger');

            return false;
        } finally {
            $this->saving = false;
            $this->dispatchBusyState();
        }
    }

    private function loadCompany(int $id): void
    {
        $company = TransportCompany::query()->findOrFail($id);
        $state = app(TransportCompanyService::class)->toFormState($company);

        $this->transport_code = $state['transport_code'];
        $this->transport_type = $state['transport_type'];
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
        $this->destination_business_name_en = $state['destination_business_name_en'];
        $this->destination_business_name_ur = $state['destination_business_name_ur'];
        $this->destination_country_code = $state['destination_country_code'];
        $this->destination_country_ur = $state['destination_country_ur'];
        $this->destination_city_key = $state['destination_city_key'];
        $this->destination_city_manual = $state['destination_city_manual'];
        $this->destination_city_en = $state['destination_city_en'];
        $this->destination_city_ur = $state['destination_city_ur'];
        $this->destination_address_en = $state['destination_address_en'];
        $this->destination_address_ur = $state['destination_address_ur'];
        $this->credit_limit = $state['credit_limit'];
        $this->opening_balance = $state['opening_balance'];
        $this->routeRows = $state['routeRows'];
        $this->branchRows = $state['branchRows'];
        $this->destinationBranchRows = $state['destinationBranchRows'];
        $this->contactRows = $state['contactRows'];
        $this->bankRows = $state['bankRows'];
        $this->bankSearches = array_fill(0, count($this->bankRows), '');
        $this->storedProfilePhotoPath = $state['profile_photo_path'];
        $this->storedCardFrontPath = $state['card_front_path'];
        $this->storedCardBackPath = $state['card_back_path'];
        $this->profilePhotoPreviewUrl = $this->storageUrl($state['profile_photo_path']);
        $this->cardFrontPreviewUrl = $this->storageUrl($state['card_front_path']);
        $this->cardBackPreviewUrl = $this->storageUrl($state['card_back_path']);
        $this->syncCountryUr();
        $this->syncDestinationCountryUr();
    }

    /**
     * @return array<string, mixed>
     */
    private function savePayload(): array
    {
        return [
            'transport_code' => filled($this->transport_code) ? $this->transport_code : null,
            'transport_type' => $this->transport_type ?: null,
            'status' => $this->status,
            'business_name_en' => $this->business_name_en,
            'business_name_ur' => $this->business_name_ur,
            'country_code' => $this->country_code,
            'country_ur' => $this->country_ur,
            'city_key' => $this->country_code === 'PK' ? ($this->city_key ?: null) : null,
            'city_en' => $this->city_en,
            'city_ur' => $this->city_ur,
            'address_en' => $this->address_en,
            'address_ur' => $this->address_ur,
            'destination_business_name_en' => $this->destination_business_name_en,
            'destination_business_name_ur' => $this->destination_business_name_ur,
            'destination_country_code' => $this->destination_country_code ?: 'PK',
            'destination_country_ur' => $this->destination_country_ur,
            'destination_city_key' => $this->destination_country_code === 'PK' ? ($this->destination_city_key ?: null) : null,
            'destination_city_en' => $this->destination_city_en,
            'destination_city_ur' => $this->destination_city_ur,
            'destination_address_en' => $this->destination_address_en,
            'destination_address_ur' => $this->destination_address_ur,
            'credit_limit' => $this->credit_limit !== '' ? $this->credit_limit : null,
            'opening_balance' => $this->opening_balance !== '' ? $this->opening_balance : 0,
            'routes' => [],
            'branches' => $this->branchRows,
            'destination_branches' => $this->destinationBranchRows,
            'contacts' => $this->contactRows,
            'bank_accounts' => $this->bankRows,
            'profile_photo_path' => $this->storedProfilePhotoPath,
            'card_front_path' => $this->storedCardFrontPath,
            'card_back_path' => $this->storedCardBackPath,
        ];
    }

    private function persistUploadedFiles(): void
    {
        $service = app(TransportCompanyService::class);

        if ($this->profilePhotoUpload !== null) {
            $this->storedProfilePhotoPath = $service->storeUpload($this->profilePhotoUpload, 'transport-companies/profile-photos');
            $this->profilePhotoPreviewUrl = Storage::disk(config('media.disk', 'public'))->url($this->storedProfilePhotoPath);
            $this->profilePhotoUpload = null;
        }

        if ($this->cardFrontUpload !== null) {
            $this->storedCardFrontPath = $service->storeUpload($this->cardFrontUpload, 'transport-companies/cards');
            $this->cardFrontPreviewUrl = Storage::disk(config('media.disk', 'public'))->url($this->storedCardFrontPath);
            $this->cardFrontUpload = null;
        }

        if ($this->cardBackUpload !== null) {
            $this->storedCardBackPath = $service->storeUpload($this->cardBackUpload, 'transport-companies/cards');
            $this->cardBackPreviewUrl = Storage::disk(config('media.disk', 'public'))->url($this->storedCardBackPath);
            $this->cardBackUpload = null;
        }
    }

    /**
     * @return array<string, string>
     */
    private function transportTypeOptions(): array
    {
        $suffix = app()->getLocale() === 'ur' ? 'ur' : 'en';

        return collect(config('transport_types', []))
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
                $status => __('transport_companies.statuses.' . $status),
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function countryOptions(?string $locale = null): array
    {
        $suffix = ($locale ?? app()->getLocale()) === 'ur' ? 'ur' : 'en';
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

    private function syncDestinationCountryUr(): void
    {
        $country = config('countries.' . $this->destination_country_code);
        $this->destination_country_ur = is_array($country) ? (string) ($country['ur'] ?? '') : '';
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

    private function syncDestinationCityFields(): void
    {
        if ($this->destination_country_code === 'PK') {
            if ($this->destination_city_key !== '') {
                $this->destination_city_en = PakistaniCities::english($this->destination_city_key);
                $this->destination_city_ur = PakistaniCities::urdu($this->destination_city_key);
            }
        } else {
            $this->destination_city_key = '';
            $this->destination_city_en = $this->destination_city_manual;
        }
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

    private function storageUrl(?string $path): ?string
    {
        return filled($path) ? Storage::disk(config('media.disk', 'public'))->url($path) : null;
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
            'transportCompanyId',
            'transport_code',
            'transport_type',
            'status',
            'country_code',
            'country_ur',
            'city_key',
            'city_manual',
            'city_en',
            'city_ur',
            'business_name_en',
            'business_name_ur',
            'address_en',
            'address_ur',
            'destination_business_name_en',
            'destination_business_name_ur',
            'destination_country_code',
            'destination_country_ur',
            'destination_city_key',
            'destination_city_manual',
            'destination_city_en',
            'destination_city_ur',
            'destination_address_en',
            'destination_address_ur',
            'credit_limit',
            'opening_balance',
            'routeRows',
            'branchRows',
            'destinationBranchRows',
            'contactRows',
            'bankRows',
            'bankSearches',
            'profilePhotoUpload',
            'cardFrontUpload',
            'cardBackUpload',
            'profilePhotoPreviewUrl',
            'cardFrontPreviewUrl',
            'cardBackPreviewUrl',
            'storedProfilePhotoPath',
            'storedCardFrontPath',
            'storedCardBackPath',
            'saving',
            'enriching',
            'statusMessage',
        ]);

        $this->mode = 'create';
        $this->status = 'active';
        $this->country_code = 'PK';
        $this->destination_country_code = 'PK';
        $this->statusType = 'success';
        $this->syncCountryUr();
        $this->syncDestinationCountryUr();
        $this->routeRows = [
            ['from_key' => '', 'from_en' => '', 'from_ur' => '', 'to_key' => '', 'to_en' => '', 'to_ur' => ''],
        ];
        $emptyBranch = [
            'address_en' => '',
            'address_ur' => '',
            'contacts' => [
                ['contact_name_en' => '', 'contact_name_ur' => '', 'mobile_number' => ''],
            ],
        ];
        $this->branchRows = [$emptyBranch];
        $this->destinationBranchRows = [$emptyBranch];
        $this->contactRows = [
            ['contact_name_en' => '', 'contact_name_ur' => '', 'mobile_number' => ''],
        ];
        $this->bankRows = [
            ['bank_key' => '', 'bank_name_en' => '', 'bank_name_ur' => '', 'account_title' => '', 'account_title_ur' => '', 'account_number' => ''],
        ];
        $this->bankSearches = [''];
    }
}
