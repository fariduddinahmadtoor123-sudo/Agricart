<?php

namespace App\Livewire\Customers;

use App\Enums\CategoryAiStatus;
use App\Models\Customer;
use App\Models\User;
use App\Services\Contacts\CustomerEnrichmentService;
use App\Services\Contacts\CustomerService;
use App\Support\LivewireImagePreview;
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

    public ?int $customerId = null;

    public string $mode = 'create';

    public string $customer_number = '';

    public string $customer_channel = Customer::CHANNEL_OFFLINE;

    public string $display_name = '';

    public string $name_en = '';

    public string $name_ur = '';

    public string $address_quick = '';

    public string $address_en = '';

    public string $address_ur = '';

    public string $city_key = '';

    public string $city_en = '';

    public string $city_ur = '';

    public string $opening_balance = '';

    public string $credit_limit = '';

    public string $email = '';

    public bool $accepts_marketing = false;

    public bool $is_active = true;

    /** @var list<array{mobile_number: string}> */
    public array $phoneRows = [
        ['mobile_number' => ''],
    ];

    public string $first_name = '';

    public string $last_name = '';

    public string $address_line1 = '';

    public string $address_line2 = '';

    public string $city = '';

    public string $state = '';

    public string $postal_code = '';

    public string $country = 'Pakistan';

    public ?TemporaryUploadedFile $profilePhotoUpload = null;

    public ?string $profilePhotoPreviewUrl = null;

    public ?string $storedProfilePhotoPath = null;

    public string $ai_status = 'none';

    public bool $saving = false;

    public bool $enriching = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    #[On('customer-form-open')]
    public function openForm(?int $customerId = null, string $mode = 'create'): void
    {
        $this->resetForm();
        $this->mode = $mode;
        $this->customerId = $customerId;

        if ($customerId) {
            $this->loadCustomer($customerId);
        }
    }

    #[On('customer-form-action')]
    public function handlePanelAction(string $action): void
    {
        match ($action) {
            'save_close' => $this->saveAndClose(),
            'save_next' => $this->saveAndAddNext(),
            'ai' => $this->enrichWithAi(),
            default => null,
        };
    }

    public function updatedCityKey(): void
    {
        if ($this->city_key === '') {
            $this->city_en = '';
            $this->city_ur = '';

            return;
        }

        $city = config('pakistani_cities.' . $this->city_key);

        if (! is_array($city)) {
            return;
        }

        $this->city_en = (string) ($city['en'] ?? '');
        $this->city_ur = (string) ($city['ur'] ?? '');
    }

    public function updatedProfilePhotoUpload(): void
    {
        if ($this->profilePhotoUpload === null) {
            $this->profilePhotoPreviewUrl = null;

            return;
        }

        $this->profilePhotoPreviewUrl = LivewireImagePreview::url($this->profilePhotoUpload);
    }

    public function addPhoneRow(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->phoneRows[] = ['mobile_number' => ''];
    }

    public function removePhoneRow(int $index): void
    {
        if ($this->isReadonly() || count($this->phoneRows) <= 1) {
            return;
        }

        unset($this->phoneRows[$index]);
        $this->phoneRows = array_values($this->phoneRows);
    }

    public function enrichWithAi(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->enriching = true;
        $this->ai_status = CategoryAiStatus::Pending->value;
        $this->statusMessage = null;
        $this->dispatchBusyState();

        try {
            $result = app(CustomerEnrichmentService::class)->enrich($this->enrichmentPayload());

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
            $this->setStatus(__('customers.ai.failed'), 'danger');
        } finally {
            $this->enriching = false;
            $this->dispatchBusyState();
        }
    }

    public function saveAndClose(): void
    {
        if ($this->persist()) {
            $this->dispatch('customers-saved');
            $this->dispatch('close-form-panel');
        }
    }

    public function saveAndAddNext(): void
    {
        if ($this->persist()) {
            $this->dispatch('customers-saved');
            $this->resetForm();
        }
    }

    public function isReadonly(): bool
    {
        return $this->mode === 'view';
    }

    public function isOnline(): bool
    {
        return $this->customer_channel === Customer::CHANNEL_ONLINE;
    }

    public function render(): View
    {
        return view('livewire.customers.form', [
            'citiesByProvince' => $this->citiesByProvince(),
        ]);
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

    private function persist(): bool
    {
        if ($this->isReadonly()) {
            return false;
        }

        if (! $this->userCan($this->customerId ? 'edit' : 'create')) {
            return false;
        }

        $this->saving = true;
        $this->statusMessage = null;
        $this->dispatchBusyState();

        try {
            $service = app(CustomerService::class);
            $payload = $this->formPayload();

            if ($this->customerId) {
                $customer = Customer::query()->findOrFail($this->customerId);
                $customer = $service->update($customer, $payload);
                $this->setStatus(__('customers.messages.updated', ['name' => $customer->displayLabel()]), 'success');
            } else {
                $customer = $service->createOffline($payload);
                $this->customerId = $customer->id;
                $this->customer_number = (string) $customer->customer_number;
                $this->setStatus(__('customers.messages.created', ['name' => $customer->displayLabel()]), 'success');
            }

            return true;
        } catch (ValidationException $exception) {
            $this->setStatus($exception->validator->errors()->first() ?: __('customers.messages.save_failed'), 'danger');

            return false;
        } catch (\Throwable) {
            $this->setStatus(__('customers.messages.save_failed'), 'danger');

            return false;
        } finally {
            $this->saving = false;
            $this->dispatchBusyState();
        }
    }

    private function loadCustomer(int $customerId): void
    {
        $customer = Customer::query()->findOrFail($customerId);
        $state = app(CustomerService::class)->toFormState($customer);

        foreach ($state as $key => $value) {
            if (! property_exists($this, $key)) {
                continue;
            }

            if ($key === 'phoneRows' && is_array($value)) {
                $this->phoneRows = $value;

                continue;
            }

            if (is_bool($value)) {
                $this->{$key} = $value;
            } elseif (is_string($value) || is_numeric($value)) {
                $this->{$key} = (string) $value;
            }
        }

        if (filled($state['profile_photo_path'] ?? null)) {
            $this->storedProfilePhotoPath = (string) $state['profile_photo_path'];
            $this->profilePhotoPreviewUrl = Storage::disk('public')->url($this->storedProfilePhotoPath);
        }
    }

    private function resetForm(): void
    {
        $this->reset([
            'customerId',
            'customer_number',
            'display_name',
            'name_en',
            'name_ur',
            'address_quick',
            'address_en',
            'address_ur',
            'city_key',
            'city_en',
            'city_ur',
            'opening_balance',
            'credit_limit',
            'email',
            'first_name',
            'last_name',
            'address_line1',
            'address_line2',
            'city',
            'state',
            'postal_code',
            'profilePhotoUpload',
            'profilePhotoPreviewUrl',
            'storedProfilePhotoPath',
            'statusMessage',
        ]);

        $this->mode = 'create';
        $this->customer_channel = Customer::CHANNEL_OFFLINE;
        $this->accepts_marketing = false;
        $this->is_active = true;
        $this->ai_status = 'none';
        $this->statusType = 'success';
        $this->country = 'Pakistan';
        $this->phoneRows = [['mobile_number' => '']];
    }

    /**
     * @return array<string, mixed>
     */
    private function formPayload(): array
    {
        return [
            'customer_number' => $this->customer_number,
            'display_name' => $this->display_name,
            'name_en' => $this->name_en,
            'name_ur' => $this->name_ur,
            'address_quick' => $this->address_quick,
            'address_en' => $this->address_en,
            'address_ur' => $this->address_ur,
            'city_key' => $this->city_key,
            'city_en' => $this->city_en,
            'city_ur' => $this->city_ur,
            'opening_balance' => $this->opening_balance,
            'credit_limit' => $this->credit_limit,
            'email' => $this->email,
            'accepts_marketing' => $this->accepts_marketing,
            'is_active' => $this->is_active,
            'ai_status' => $this->ai_status,
            'phoneRows' => $this->phoneRows,
            'profile_photo_upload' => $this->profilePhotoUpload,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function enrichmentPayload(): array
    {
        return [
            'display_name' => $this->display_name,
            'name_en' => $this->name_en,
            'name_ur' => $this->name_ur,
            'address_quick' => $this->address_quick,
            'address_en' => $this->address_en,
            'address_ur' => $this->address_ur,
            'city_en' => $this->city_en,
            'city_ur' => $this->city_ur,
            'contacts' => app(CustomerService::class)->normalizePhoneRows($this->phoneRows),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyEnrichmentData(array $data): void
    {
        foreach (['name_en', 'name_ur', 'address_en', 'address_ur', 'city_en', 'city_ur'] as $key) {
            if (filled($data[$key] ?? null)) {
                $this->{$key} = (string) $data[$key];
            }
        }

        if (filled($data['ai_status'] ?? null)) {
            $this->ai_status = (string) $data['ai_status'];
        }
    }

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }

    private function dispatchBusyState(): void
    {
        $this->dispatch('customer-form-busy', enriching: $this->enriching, saving: $this->saving);
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
}
