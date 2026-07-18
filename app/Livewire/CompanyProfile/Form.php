<?php

namespace App\Livewire\CompanyProfile;

use App\Enums\CategoryAiStatus;
use App\Filament\Clusters\Store\FooterPage;
use App\Services\Settings\CompanyProfileEnrichmentService;
use App\Services\Settings\CompanyProfileService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Form extends Component
{
    public string $business_name_en = '';

    public string $business_name_ur = '';

    public string $tagline_en = '';

    public string $tagline_ur = '';

    public string $email = '';

    public string $whatsapp_number = '';

    public string $website = '';

    public string $ntn = '';

    public string $strn = '';

    public string $currency_code = 'PKR';

    public bool $price_use_decimals = false;

    public int $price_decimal_places = 2;

    /** @var list<array<string, mixed>> */
    public array $phoneRows = [];

    /** @var list<array<string, mixed>> */
    public array $addressRows = [];

    public ?string $storeLogoPreviewUrl = null;

    public string $ai_status = 'none';

    public bool $saving = false;

    public bool $enriching = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    public bool $panelIsOpen = false;

    public function openForm(): void
    {
        $this->panelIsOpen = true;
        $this->statusMessage = null;
        $this->loadProfile();
    }

    public function handlePanelClose(): void
    {
        $this->panelIsOpen = false;
    }

    public function handlePanelAction(string $action): void
    {
        match ($action) {
            'ai' => $this->enrichWithAi(),
            'save_close' => $this->saveAndClose(),
            default => null,
        };
    }

    public function addPhoneRow(): void
    {
        $this->phoneRows[] = app(CompanyProfileService::class)->emptyPhoneRow();
        $this->phoneRows[array_key_last($this->phoneRows)]['is_primary'] = count($this->phoneRows) === 1;
    }

    public function removePhoneRow(int $index): void
    {
        if (count($this->phoneRows) <= 1) {
            return;
        }

        unset($this->phoneRows[$index]);
        $this->phoneRows = array_values($this->phoneRows);
        $this->phoneRows[0]['is_primary'] = true;
    }

    public function addAddressRow(): void
    {
        $this->addressRows[] = app(CompanyProfileService::class)->emptyAddressRow();
        $this->addressRows[array_key_last($this->addressRows)]['is_primary'] = count($this->addressRows) === 1;
    }

    public function removeAddressRow(int $index): void
    {
        if (count($this->addressRows) <= 1) {
            return;
        }

        unset($this->addressRows[$index]);
        $this->addressRows = array_values($this->addressRows);
        $this->addressRows[0]['is_primary'] = true;
    }

    public function enrichWithAi(): void
    {
        $this->enriching = true;
        $this->ai_status = CategoryAiStatus::Pending->value;
        $this->statusMessage = null;
        $this->dispatchBusyState();

        try {
            $result = app(CompanyProfileEnrichmentService::class)->enrich($this->enrichmentPayload());

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
        $this->saving = true;
        $this->statusMessage = null;
        $this->dispatchBusyState();

        try {
            $validated = $this->validate($this->rules());
            $service = app(CompanyProfileService::class);

            $service->save([
                'business_name_en' => $validated['business_name_en'],
                'business_name_ur' => $validated['business_name_ur'],
                'legal_name_en' => null,
                'legal_name_ur' => null,
                'tagline_en' => $validated['tagline_en'],
                'tagline_ur' => $validated['tagline_ur'],
                'email' => $validated['email'],
                'whatsapp_number' => $validated['whatsapp_number'],
                'website' => $validated['website'],
                'ntn' => $validated['ntn'],
                'strn' => $validated['strn'],
                'currency_code' => $validated['currency_code'],
                'price_use_decimals' => $validated['price_use_decimals'],
                'price_decimal_places' => $validated['price_decimal_places'],
                'invoice_footer_en' => null,
                'invoice_footer_ur' => null,
                'phones' => $this->phoneRows,
                'addresses' => $this->addressRows,
                'ai_status' => $this->ai_status,
            ]);

            $this->dispatch('company-profile-saved');
            $this->panelIsOpen = false;
            $this->dispatch('close-form-panel');
            $this->js("window.dispatchEvent(new CustomEvent('close-form-panel', { bubbles: true }))");
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            $this->setStatus(\App\Support\FriendlyError::message($exception), 'danger');
        } finally {
            $this->saving = false;
            $this->dispatchBusyState();
        }
    }

    public function render(): View
    {
        return view('livewire.company-profile.form', [
            'designations' => app(CompanyProfileService::class)->designationOptions(),
            'storeFooterSettingsUrl' => FooterPage::getUrl(),
        ]);
    }

    private function loadProfile(): void
    {
        $service = app(CompanyProfileService::class);
        $profile = $service->current();

        if (! $profile->isConfigured()) {
            $profile = $service->syncFromStorefront();
        }

        $state = $service->toFormState($profile);

        $this->business_name_en = $state['business_name_en'];
        $this->business_name_ur = $state['business_name_ur'];
        $this->tagline_en = $state['tagline_en'];
        $this->tagline_ur = $state['tagline_ur'];
        $this->email = $state['email'];
        $this->whatsapp_number = $state['whatsapp_number'];
        $this->website = $state['website'];
        $this->ntn = $state['ntn'];
        $this->strn = $state['strn'];
        $this->currency_code = $state['currency_code'];
        $this->price_use_decimals = $state['price_use_decimals'];
        $this->price_decimal_places = $state['price_decimal_places'];
        $this->phoneRows = $state['phones'];
        $this->addressRows = $state['addresses'];
        $this->ai_status = $state['ai_status'];
        $this->storeLogoPreviewUrl = $profile->displayLogoUrl();
    }

    /**
     * @return array<string, mixed>
     */
    private function enrichmentPayload(): array
    {
        return [
            'business_name_en' => $this->business_name_en,
            'business_name_ur' => $this->business_name_ur,
            'tagline_en' => $this->tagline_en,
            'tagline_ur' => $this->tagline_ur,
            'phones' => $this->phoneRows,
            'addresses' => $this->addressRows,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyEnrichmentData(array $data): void
    {
        foreach (['business_name_en', 'business_name_ur', 'tagline_en', 'tagline_ur'] as $key) {
            if (filled($data[$key] ?? null) && ! filled($this->{$key})) {
                $this->{$key} = (string) $data[$key];
            }
        }

        if (isset($data['phones']) && is_array($data['phones'])) {
            foreach ($data['phones'] as $index => $phone) {
                if (! is_array($phone) || ! isset($this->phoneRows[$index])) {
                    continue;
                }

                foreach (['contact_person_en', 'contact_person_ur'] as $key) {
                    if (filled($phone[$key] ?? null) && ! filled($this->phoneRows[$index][$key] ?? null)) {
                        $this->phoneRows[$index][$key] = (string) $phone[$key];
                    }
                }
            }
        }

        if (isset($data['addresses']) && is_array($data['addresses'])) {
            foreach ($data['addresses'] as $index => $address) {
                if (! is_array($address) || ! isset($this->addressRows[$index])) {
                    continue;
                }

                foreach (['address_en', 'address_ur'] as $key) {
                    if (filled($address[$key] ?? null) && ! filled($this->addressRows[$index][$key] ?? null)) {
                        $this->addressRows[$index][$key] = (string) $address[$key];
                    }
                }
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'business_name_en' => ['required', 'string', 'max:255'],
            'business_name_ur' => ['nullable', 'string', 'max:255'],
            'tagline_en' => ['nullable', 'string', 'max:500'],
            'tagline_ur' => ['nullable', 'string', 'max:500'],
            'email' => ['nullable', 'email', 'max:255'],
            'whatsapp_number' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s()\-]+$/'],
            'website' => ['nullable', 'string', 'max:500'],
            'ntn' => ['nullable', 'string', 'max:50'],
            'strn' => ['nullable', 'string', 'max:50'],
            'currency_code' => ['required', 'string', 'size:3'],
            'price_use_decimals' => ['boolean'],
            'price_decimal_places' => ['required', 'integer', 'min:0', 'max:4'],
        ];
    }

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }

    private function dispatchBusyState(): void
    {
        $this->dispatch('company-profile-form-busy', enriching: $this->enriching, saving: $this->saving);
    }
}
