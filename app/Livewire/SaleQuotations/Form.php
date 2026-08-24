<?php

namespace App\Livewire\SaleQuotations;

use App\Livewire\Concerns\AuthorizesSensitiveActions;
use App\Models\CatalogControl;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SaleQuotation;
use App\Services\Contacts\CustomerService;
use App\Services\PricingControls\ChannelPricingEngine;
use App\Services\PricingControls\QuantityPricingEngine;
use App\Services\Sales\SaleQuotationService;
use App\Services\Sales\SaleService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesSensitiveActions;

    public ?int $quotationId = null;

    public string $mode = 'create';

    public string $quotation_number = '';

    public ?int $customer_id = null;

    public string $customer_search = '';

    public string $quotation_date = '';

    public string $locale = 'en';

    public string $discount_type = '';

    public string $discount_value = '0';

    /** @var 'no'|'yes' */
    public string $print_controls_mode = 'no';

    public string $notes = '';

    public string $subtotal = '0';

    public string $discount_amount = '0';

    public string $quotation_amount = '0';

    /** @var list<array<string, mixed>> */
    public array $rows = [];

    public string $product_search = '';

    public bool $saving = false;

    private bool $recalculatingTotals = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    #[On('sale-quotation-form-open')]
    public function openForm(?int $quotationId = null, string $mode = 'create'): void
    {
        $this->authorizeAny($mode === 'view' ? ['sales.sales-quotations.view', 'sales.sales-quotations.edit'] : ($quotationId ? 'sales.sales-quotations.edit' : 'sales.sales-quotations.create'));
        $this->resetForm();
        $this->mode = $mode;
        $this->quotationId = $quotationId;

        if ($quotationId) {
            $this->loadQuotation($quotationId);
        } else {
            $this->quotation_date = now()->format('Y-m-d');
        }

        $this->recalculateTotals();
    }

    #[On('sale-quotation-form-action')]
    public function handlePanelAction(string $action): void
    {
        $this->authorizeAny($this->requiredActionPermission());

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

    public function updatedRows(): void
    {
        $this->recalculateTotals();
    }

    public function updated(string $propertyName): void
    {
        if (str_starts_with($propertyName, 'rows')) {
            $this->recalculateTotals();
        }
    }

    public function updatedDiscountType(): void
    {
        $this->recalculateTotals();
    }

    public function updatedDiscountValue(): void
    {
        $this->recalculateTotals();
    }

    public function selectCustomer(int $customerId): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $customer = Customer::query()->find($customerId);

        if ($customer === null) {
            return;
        }

        $this->customer_id = $customer->id;
        $this->customer_search = '';
    }

    public function clearCustomer(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->customer_id = null;
        $this->customer_search = '';
    }

    public function scanProduct(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $term = trim($this->product_search);

        if ($term === '') {
            return;
        }

        $service = app(SaleService::class);
        $product = $service->findProductByScan($term);

        if ($product instanceof Product) {
            $this->addProduct($product->id);
            $this->product_search = '';

            return;
        }

        $results = $service->searchProducts($term, app()->getLocale(), 1);

        if (count($results) === 1) {
            $this->addProduct($results[0]['id']);
            $this->product_search = '';
        }
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

        $this->rows[] = app(SaleService::class)->rowFromProduct($product, '0');
        $this->product_search = '';
        $this->recalculateTotals();
    }

    public function removeRow(int $index): void
    {
        if ($this->isReadonly()) {
            return;
        }

        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
        $this->recalculateTotals();
    }

    public function markPriceManual(int $index): void
    {
        if (! isset($this->rows[$index])) {
            return;
        }

        $this->rows[$index]['price_manual'] = true;
        $this->recalculateTotals();
    }

    public function displayPrice(string $amount): string
    {
        $amount = trim($amount);

        if ($amount === '') {
            return $amount;
        }

        $numeric = app(SaleService::class)->numeric($amount);

        return app(ChannelPricingEngine::class)->formatPrice($numeric);
    }

    public function tierLabel(string $tierKey): string
    {
        return app(QuantityPricingEngine::class)->tierTypeLabel($tierKey);
    }

    public function rowImageUrl(?string $path): ?string
    {
        return filled($path) ? Storage::disk('public')->url($path) : null;
    }

    public function formattedQuotationDate(): string
    {
        try {
            return \Carbon\Carbon::parse($this->quotation_date)->format('M j, Y');
        } catch (\Throwable) {
            return $this->quotation_date;
        }
    }

    public function isReadonly(): bool
    {
        return $this->mode === 'view';
    }

    public function saveAndClose(): void
    {
        $this->authorizeAny($this->quotationId ? 'sales.sales-quotations.edit' : 'sales.sales-quotations.create');

        if ($this->persist(close: true)) {
            return;
        }
    }

    public function saveAndPrint(): void
    {
        $this->authorizeAny($this->requiredActionPermission());

        if ($this->isReadonly()) {
            if ($this->quotationId) {
                $this->openPrint((int) $this->quotationId);
            }

            return;
        }

        $quotation = $this->persist(close: false);

        if (! $quotation instanceof SaleQuotation) {
            return;
        }

        $this->openPrint($quotation->id);
    }

    public function openPrint(int $quotationId): void
    {
        $this->dispatch('sale-quotations-open-print', url: route('admin.sale-quotations.print', ['quotation' => $quotationId]));
    }

    public function render(): View
    {
        $quotationService = app(SaleQuotationService::class);

        return view('livewire.sale-quotations.form', [
            'productResults' => $quotationService->searchProducts($this->product_search, app()->getLocale()),
            'customerResults' => app(CustomerService::class)->searchCustomers($this->customer_search),
            'selectedCustomer' => $this->customer_id ? Customer::query()->find($this->customer_id) : null,
            'discountOptions' => __('pos.discount_types'),
            'printControlsOptions' => __('pos.print_controls_options'),
            'localeOptions' => collect(SaleQuotationService::LOCALES)
                ->mapWithKeys(fn (string $locale): array => [$locale => __('sale_quotations.locales.' . $locale)])
                ->all(),
            'controlsPreview' => $this->controlsPreviewLines(),
            'cashierName' => auth()->user()?->name ?? '—',
        ]);
    }

    /**
     * @return list<array{product: string, controls: string}>
     */
    private function controlsPreviewLines(): array
    {
        if ($this->print_controls_mode !== 'yes') {
            return [];
        }

        $productIds = collect($this->rows)
            ->pluck('product_id')
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return [];
        }

        $locale = app()->getLocale();
        $lines = [];

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->with(['controls' => fn ($q) => $q->orderBy('name_en')])
            ->get()
            ->keyBy('id');

        foreach ($this->rows as $row) {
            $productId = (int) ($row['product_id'] ?? 0);
            $product = $products->get($productId);

            if ($product === null) {
                continue;
            }

            $controlText = $product->controls
                ->map(function (CatalogControl $control) use ($locale): string {
                    $name = $locale === 'ur' && filled($control->name_ur)
                        ? (string) $control->name_ur
                        : (string) $control->name_en;
                    $value = $locale === 'ur' && filled($control->value_ur)
                        ? (string) $control->value_ur
                        : (string) ($control->value_en ?? '');

                    return filled($value) ? trim($name . ': ' . $value) : $name;
                })
                ->filter()
                ->implode(' · ');

            if ($controlText === '') {
                continue;
            }

            $label = $locale === 'ur' && filled($row['product_name_ur'] ?? null)
                ? (string) $row['product_name_ur']
                : (string) ($row['product_name_en'] ?? $product->name_en);

            $lines[] = [
                'product' => $label,
                'controls' => $controlText,
            ];
        }

        return $lines;
    }

    private function persist(bool $close): ?SaleQuotation
    {
        $this->authorizeAny($this->quotationId ? 'sales.sales-quotations.edit' : 'sales.sales-quotations.create');

        if ($this->isReadonly()) {
            return null;
        }

        $this->saving = true;
        $this->dispatchBusyState();

        try {
            $service = app(SaleQuotationService::class);
            $saleService = app(SaleService::class);
            $this->rows = $saleService->recalculateRows($this->rows);
            $payload = $this->buildPayload();

            if ($this->quotationId) {
                $quotation = SaleQuotation::query()->findOrFail($this->quotationId);
                $quotation = $service->update($quotation, $payload);
                $this->setStatus(__('sale_quotations.messages.updated', ['number' => $quotation->quotation_number]), 'success');
            } else {
                $quotation = $service->create($payload);
                $this->quotationId = $quotation->id;
                $this->quotation_number = $quotation->quotation_number;
                $this->setStatus(__('sale_quotations.messages.created', ['number' => $quotation->quotation_number]), 'success');
            }

            $this->loadQuotation($quotation->id);
            $this->dispatch('sale-quotations-saved');

            if ($close) {
                $this->dispatch('close-form-panel');
            }

            return $quotation;
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first()
                ?? __('sale_quotations.messages.save_failed');
            $this->setStatus((string) $message, 'error');
        } catch (\Throwable) {
            $this->setStatus(__('sale_quotations.messages.save_failed'), 'error');
        } finally {
            $this->saving = false;
            $this->dispatchBusyState();
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(): array
    {
        return [
            'quotation_number' => filled($this->quotation_number) ? $this->quotation_number : null,
            'customer_id' => $this->customer_id,
            'quotation_date' => $this->quotation_date,
            'locale' => app(SaleQuotationService::class)->normalizeLocale($this->locale),
            'discount_type' => $this->discount_type ?: null,
            'discount_value' => $this->discount_value !== '' ? $this->discount_value : '0',
            'print_controls' => $this->print_controls_mode === 'yes',
            'notes' => $this->notes,
            'rows' => $this->rows,
        ];
    }

    private function loadQuotation(int $quotationId): void
    {
        $this->authorizeAny($this->mode === 'view' ? ['sales.sales-quotations.view', 'sales.sales-quotations.edit'] : 'sales.sales-quotations.edit');
        $quotation = SaleQuotation::query()->find($quotationId);

        if ($quotation === null) {
            return;
        }

        $state = app(SaleQuotationService::class)->toFormState($quotation);
        $this->quotation_number = (string) $state['quotation_number'];
        $this->customer_id = $state['customer_id'] ? (int) $state['customer_id'] : null;
        $this->quotation_date = (string) $state['quotation_date'];
        $this->locale = (string) ($state['locale'] ?? 'en');
        $this->discount_type = (string) $state['discount_type'];
        $this->discount_value = filled($state['discount_value'] ?? null) ? (string) $state['discount_value'] : '0';
        $this->subtotal = (string) $state['subtotal'];
        $this->discount_amount = (string) $state['discount_amount'];
        $this->quotation_amount = (string) $state['quotation_amount'];
        $this->print_controls_mode = ($state['print_controls'] ?? false) ? 'yes' : 'no';
        $this->notes = (string) $state['notes'];
        $this->rows = $state['rows'];
    }

    private function recalculateTotals(): void
    {
        if ($this->recalculatingTotals) {
            return;
        }

        $this->recalculatingTotals = true;

        $service = app(SaleService::class);
        $this->rows = $service->recalculateRows($this->rows);
        $subtotal = $service->subtotalFromRows($this->rows);
        $discount = $service->discountAmount($subtotal, $this->discount_type ?: null, $this->discount_value ?: null);
        $total = max(0, round($subtotal - $discount, 2));

        $this->subtotal = $service->formatMoney($subtotal);
        $this->discount_amount = $service->formatMoney($discount);
        $this->quotation_amount = $service->formatMoney($total);

        $this->recalculatingTotals = false;
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

    private function dispatchBusyState(): void
    {
        $this->dispatch('sale-quotation-form-busy', saving: $this->saving);
    }

    private function requiredActionPermission(): array|string
    {
        return $this->isReadonly()
            ? ['sales.sales-quotations.view', 'sales.sales-quotations.edit']
            : ($this->quotationId ? 'sales.sales-quotations.edit' : 'sales.sales-quotations.create');
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
            'customer_id',
            'customer_search',
            'quotation_date',
            'locale',
            'discount_type',
            'discount_value',
            'print_controls_mode',
            'notes',
            'subtotal',
            'discount_amount',
            'quotation_amount',
            'rows',
            'product_search',
            'statusMessage',
        ]);
        $this->statusType = 'success';
        $this->discount_value = '0';
        $this->print_controls_mode = 'no';
        $this->locale = app(SaleQuotationService::class)->normalizeLocale(app()->getLocale());
        $this->subtotal = '0';
        $this->discount_amount = '0';
        $this->quotation_amount = '0';
    }
}
