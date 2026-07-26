<?php

namespace App\Livewire\Auth;

use App\Filament\Forms\CategoryForm;
use App\Models\StorePage;
use App\Services\Contacts\SupplierService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class SupplierRegisterForm extends Component
{
    public string $website = '';

    public string $business_name_en = '';

    public string $business_name_ur = '';

    public string $supplier_type = '';

    public string $city_en = '';

    public string $address_en = '';

    public string $application_note = '';

    public string $custom_categories = '';

    public string $category_search = '';

    /** @var list<int> */
    public array $category_ids = [];

    /** @var list<array{contact_name_en: string, contact_name_ur: string, mobile_number: string}> */
    public array $contactRows = [
        ['contact_name_en' => '', 'contact_name_ur' => '', 'mobile_number' => ''],
    ];

    public bool $terms_accepted = false;

    public bool $saving = false;

    public bool $submitted = false;

    public bool $isResubmit = false;

    public ?string $submittedReference = null;

    public ?string $statusMessage = null;

    public string $statusType = 'danger';

    public function addContactRow(): void
    {
        if (count($this->contactRows) >= 8) {
            return;
        }

        $this->contactRows[] = ['contact_name_en' => '', 'contact_name_ur' => '', 'mobile_number' => ''];
    }

    public function removeContactRow(int $index): void
    {
        if (count($this->contactRows) <= 1) {
            return;
        }

        unset($this->contactRows[$index]);
        $this->contactRows = array_values($this->contactRows);
    }

    public function addCategory(int $id): void
    {
        if (! in_array($id, $this->category_ids, true)) {
            $this->category_ids[] = $id;
        }

        $this->category_search = '';
    }

    public function removeCategory(int $id): void
    {
        $this->category_ids = array_values(array_filter(
            $this->category_ids,
            static fn (int $existing): bool => $existing !== $id,
        ));
    }

    public function submit(): void
    {
        $this->saving = true;
        $this->statusMessage = null;

        try {
            if (filled($this->website)) {
                $this->submitted = true;

                return;
            }

            $primaryMobile = trim((string) ($this->contactRows[0]['mobile_number'] ?? ''));
            $ipHash = sha1((string) request()->ip());
            $rateLimitKey = 'supplier-registration:'.sha1($ipHash.'|'.$primaryMobile);
            $ipRateLimitKey = 'supplier-registration-ip:'.$ipHash;

            if (
                RateLimiter::tooManyAttempts($rateLimitKey, 10)
                || RateLimiter::tooManyAttempts($ipRateLimitKey, 30)
            ) {
                throw ValidationException::withMessages([
                    'contactRows.0.mobile_number' => [__('agricart.supplier_register.errors.rate_limited')],
                ]);
            }

            RateLimiter::hit($rateLimitKey, 60);
            RateLimiter::hit($ipRateLimitKey, 3600);

            $service = app(SupplierService::class);
            $contacts = $service->normalizeContacts($this->contactRows);
            $mobileDigits = $service->primaryMobileFromContacts($contacts);
            $this->isResubmit = $service->findRejectedByPrimaryMobile($mobileDigits) !== null;

            $supplier = $service->registerPublic([
                'business_name_en' => $this->business_name_en,
                'business_name_ur' => $this->business_name_ur ?: null,
                'supplier_type' => $this->supplier_type,
                'country_code' => 'PK',
                'city_en' => $this->city_en,
                'address_en' => $this->address_en,
                'contacts' => $contacts,
                'category_ids' => $this->category_ids,
                'custom_categories' => $this->custom_categories ?: null,
                'application_note' => $this->application_note ?: null,
                'terms_accepted' => $this->terms_accepted,
            ]);

            RateLimiter::clear($rateLimitKey);
            $this->submittedReference = $supplier->supplier_code;
            $this->submitted = true;
            $this->dispatch('supplier-registration-result', success: true);
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first();
            $this->statusMessage = $message ?: __('agricart.supplier_register.errors.failed');
            $this->statusType = 'danger';
            $this->setErrorBag($exception->errors());
        } catch (\Throwable) {
            $this->statusMessage = __('agricart.supplier_register.errors.failed');
            $this->statusType = 'danger';
        } finally {
            $this->saving = false;
        }
    }

    public function render(): View
    {
        $termsPage = StorePage::query()
            ->where('slug', 'supplier-terms')
            ->where('is_active', true)
            ->first();

        return view('livewire.auth.supplier-register-form', [
            'supplierTypes' => config('supplier_types', []),
            'categoryOptions' => CategoryForm::searchCategoryLabels(
                $this->category_search,
                excludeIds: $this->category_ids,
            ),
            'selectedCategories' => CategoryForm::categoryLabelsByIds($this->category_ids),
            'termsPage' => $termsPage,
            'termsUrl' => $termsPage?->publicUrl(),
        ]);
    }
}
