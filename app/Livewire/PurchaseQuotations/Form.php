<?php

namespace App\Livewire\PurchaseQuotations;

use App\Models\CompanyProfile;
use App\Models\Product;
use App\Models\PurchaseQuotation;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Purchasing\PurchaseQuotationService;
use App\Services\Purchasing\PurchaseService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $quotationId = null;

    public string $mode = 'create';

    public string $quotation_number = '';

    public ?int $supplier_id = null;

    public string $supplier_search = '';

    public string $locale = 'en';

    public string $quotation_date = '';

    public string $notes = '';

    /** @var list<array<string, mixed>> */
    public array $rows = [];

    public string $product_search = '';

    public bool $saving = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    #[On('purchase-quotation-form-open')]
    public function openForm(?int $quotationId = null, string $mode = 'create'): void
    {
        $this->resetForm();
        $this->mode = $mode;
        $this->quotationId = $quotationId;

        if ($quotationId) {
            $this->loadQuotation($quotationId);
        } else {
            $this->quotation_date = now()->format('Y-m-d');
        }
    }

    #[On('purchase-quotation-form-action')]
    public function handlePanelAction(string $action): void
    {
        match ($action) {
            'save_close' => $this->saveAndClose(),
            'print' => $this->saveAndPrint(),
            default => null,
        };
    }

    public function updatedSaving(): void
    {
        $this->dispatchBusyState();
    }

    public function updatedProductSearch(): void
    {
        $this->product_search = trim($this->product_search);
    }

    public function selectSupplier(int $supplierId): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->supplier_id = $supplierId;
        $this->supplier_search = '';
    }

    public function clearSupplier(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->supplier_id = null;
        $this->supplier_search = '';
    }

    public function addProduct(int $productId): void
    {
        if ($this->isReadonly()) {
            return;
        }

        if ($this->hasProduct($productId)) {
            $this->product_search = '';

            return;
        }

        $product = Product::query()->where('status', 'active')->find($productId);

        if ($product === null) {
            return;
        }

        $this->rows[] = app(PurchaseQuotationService::class)->rowFromProduct($product);
        $this->product_search = '';
    }

    public function scanProduct(): void
    {
        if ($this->isReadonly() || $this->product_search === '') {
            return;
        }

        $product = app(PurchaseQuotationService::class)->findProductByScan($this->product_search);

        if ($product !== null) {
            $this->addProduct($product->id);

            return;
        }

        $results = $this->productSearchResults();

        if (count($results) === 1) {
            $this->addProduct($results[0]['id']);
        }
    }

    public function removeRow(int $index): void
    {
        if ($this->isReadonly()) {
            return;
        }

        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
    }

    public function saveAndClose(): void
    {
        if ($this->persist()) {
            $this->dispatch('purchase-quotations-saved');
            $this->dispatch('close-form-panel');
        }
    }

    public function saveAndPrint(): void
    {
        $quotation = $this->persist();

        if (! $quotation instanceof PurchaseQuotation) {
            return;
        }

        $this->dispatch('purchase-quotations-saved');
        $url = route('admin.purchase-quotations.print', $quotation);
        $this->js('window.open(' . json_encode($url) . ", '_blank')");
    }

    public function isReadonly(): bool
    {
        return $this->mode === 'view';
    }

    public function render(): View
    {
        $company = CompanyProfile::current();
        $locale = app(PurchaseQuotationService::class)->normalizeLocale($this->locale);
        $previousLocale = app()->getLocale();
        app()->setLocale($locale);
        $buyerAddress = $company->formattedPrimaryAddress() ?? '';
        $buyerName = (string) ($company->localized('business_name') ?? '');
        $primaryPhone = $company->primaryPhone();
        $buyerPhone = (string) ($primaryPhone['phone_number'] ?? '');
        app()->setLocale($previousLocale);

        return view('livewire.purchase-quotations.form', [
            'productResults' => $this->productSearchResults(),
            'supplierResults' => $this->supplierSearchResults(),
            'selectedSupplier' => $this->selectedSupplier(),
            'localeOptions' => $this->localeOptions(),
            'formattedQuotationDate' => $this->formattedQuotationDate(),
            'buyerName' => $buyerName,
            'buyerAddress' => $buyerAddress,
            'buyerPhone' => $buyerPhone,
        ]);
    }

    public function supplierDisplayName(?Supplier $supplier): string
    {
        if ($supplier === null) {
            return '—';
        }

        if ($this->locale === 'ur' && filled($supplier->business_name_ur)) {
            return (string) $supplier->business_name_ur;
        }

        return (string) $supplier->business_name_en;
    }

    public function supplierAddressLine(Supplier $supplier): string
    {
        $address = $this->locale === 'ur' && filled($supplier->address_ur)
            ? trim((string) $supplier->address_ur)
            : trim((string) ($supplier->address_en ?: $supplier->address_ur));

        $city = $this->supplierCity($supplier);

        return implode(', ', array_filter([$address, $city]));
    }

    public function supplierCity(Supplier $supplier): string
    {
        if ($this->locale === 'ur' && filled($supplier->city_ur)) {
            return trim((string) $supplier->city_ur);
        }

        return trim((string) ($supplier->city_en ?: $supplier->city_ur));
    }

    public function supplierPhone(Supplier $supplier): ?string
    {
        $contact = $supplier->primaryContact();
        $phone = $contact['mobile_number'] ?? null;

        return filled($phone) ? (string) $phone : null;
    }

    public function rowImageUrl(?string $path): ?string
    {
        return filled($path) ? Storage::disk('public')->url($path) : null;
    }

    public function rowDisplayName(array $row): string
    {
        return app(PurchaseQuotationService::class)->rowDisplayName($row, $this->locale);
    }

    /**
     * @return array<int, array{id: int, label: string, image_url: ?string}>
     */
    private function productSearchResults(): array
    {
        if ($this->product_search === '') {
            return [];
        }

        return app(PurchaseQuotationService::class)->searchProducts($this->product_search, $this->locale);
    }

    /**
     * @return array<string, string>
     */
    private function localeOptions(): array
    {
        return collect(PurchaseQuotationService::LOCALES)
            ->mapWithKeys(fn (string $locale): array => [
                $locale => __('purchase_quotations.locales.' . $locale),
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, label: string}>
     */
    private function supplierSearchResults(): array
    {
        if ($this->supplier_id !== null || $this->supplier_search === '') {
            return [];
        }

        return app(PurchaseService::class)->searchSuppliers($this->supplier_search);
    }

    private function selectedSupplier(): ?Supplier
    {
        if ($this->supplier_id === null) {
            return null;
        }

        return Supplier::query()->find($this->supplier_id);
    }

    private function formattedQuotationDate(): string
    {
        if ($this->quotation_date === '') {
            return now()->format('d M Y');
        }

        try {
            return \Illuminate\Support\Carbon::parse($this->quotation_date)->format('d M Y');
        } catch (\Throwable) {
            return $this->quotation_date;
        }
    }

    private function loadQuotation(int $quotationId): void
    {
        $quotation = PurchaseQuotation::query()->with('items')->find($quotationId);

        if ($quotation === null) {
            return;
        }

        $state = app(PurchaseQuotationService::class)->toFormState($quotation);
        $this->quotation_number = (string) $state['quotation_number'];
        $this->supplier_id = $state['supplier_id'] ? (int) $state['supplier_id'] : null;
        $this->locale = (string) $state['locale'];
        $this->quotation_date = (string) $state['quotation_date'];
        $this->notes = (string) $state['notes'];
        $this->rows = $state['rows'];
    }

    private function persist(): ?PurchaseQuotation
    {
        if ($this->isReadonly()) {
            return null;
        }

        if (! $this->userCan($this->quotationId ? 'edit' : 'create')) {
            return null;
        }

        $this->saving = true;
        $this->dispatchBusyState();

        try {
            $service = app(PurchaseQuotationService::class);
            $payload = [
                'quotation_number' => filled($this->quotation_number) ? $this->quotation_number : null,
                'supplier_id' => $this->supplier_id,
                'locale' => $this->locale,
                'quotation_date' => $this->quotation_date,
                'notes' => $this->notes,
                'items' => $this->rows,
            ];

            if ($this->quotationId) {
                $quotation = PurchaseQuotation::query()->findOrFail($this->quotationId);
                $quotation = $service->update($quotation, $payload);
                $this->setStatus(__('purchase_quotations.messages.updated', ['number' => $quotation->quotation_number]), 'success');
            } else {
                $quotation = $service->create($payload);
                $this->quotationId = $quotation->id;
                $this->quotation_number = $quotation->quotation_number;
                $this->setStatus(__('purchase_quotations.messages.created', ['number' => $quotation->quotation_number]), 'success');
            }

            return $quotation;
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first()
                ?? __('purchase_quotations.messages.save_failed');
            $this->setStatus((string) $message, 'error');
        } catch (\Throwable) {
            $this->setStatus(__('purchase_quotations.messages.save_failed'), 'error');
        } finally {
            $this->saving = false;
            $this->dispatchBusyState();
        }

        return null;
    }

    private function hasProduct(int $productId): bool
    {
        foreach ($this->rows as $row) {
            if ((int) ($row['product_id'] ?? 0) === $productId) {
                return true;
            }
        }

        return false;
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

        return \App\Services\Access\ModulePageAccess::can($user, 'purchasing', 'purchase-quotations', $action);
    }

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }

    private function resetForm(): void
    {
        $this->reset([
            'quotationId',
            'quotation_number',
            'supplier_id',
            'supplier_search',
            'locale',
            'quotation_date',
            'notes',
            'rows',
            'product_search',
            'saving',
            'statusMessage',
        ]);

        $this->mode = 'create';
        $this->locale = app(PurchaseQuotationService::class)->normalizeLocale(app()->getLocale());
        $this->statusType = 'success';
    }

    private function dispatchBusyState(): void
    {
        $this->dispatch('purchase-quotation-form-busy', saving: $this->saving);
    }
}
