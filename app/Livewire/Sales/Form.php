<?php

namespace App\Livewire\Sales;

use App\Models\CatalogControl;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\Contacts\CustomerService;
use App\Services\PricingControls\ChannelPricingEngine;
use App\Services\PricingControls\QuantityPricingEngine;
use App\Services\Sales\SaleService;
use App\Services\Sales\SalesBankAccountService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $saleId = null;

    public string $mode = 'create';

    public string $sale_number = '';

    public ?int $customer_id = null;

    public string $customer_search = '';

    public string $sale_date = '';

    public string $locale = 'en';

    public string $invoice_status = Sale::STATUS_HELD;

    public string $payment_method = '';

    public ?int $bank_account_id = null;

    public string $payment_status = '';

    public string $amount_paid = '0';

    public string $discount_type = '';

    public string $discount_value = '0';

    public string $price_display = 'plain';

    /** @var 'no'|'yes' */
    public string $print_controls_mode = 'no';

    public string $notes = '';

    public string $subtotal = '0';

    public string $discount_amount = '0';

    public string $total_amount = '0';

    public string $balance_due = '0';

    /** @var list<array<string, mixed>> */
    public array $rows = [];

    public string $product_search = '';

    public bool $saving = false;

    private bool $recalculatingTotals = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    #[On('sale-form-open')]
    public function openForm(?int $saleId = null, string $mode = 'create'): void
    {
        $this->resetForm();
        $this->mode = $mode;
        $this->saleId = $saleId;

        if ($saleId) {
            $this->loadSale($saleId);
        } else {
            $this->sale_date = now()->format('Y-m-d');
            $this->invoice_status = '';
        }

        $this->recalculateTotals();
    }

    #[On('sale-form-action')]
    public function handlePanelAction(string $action): void
    {
        match ($action) {
            'save_close' => $this->saveAndClose(),
            'hold' => $this->saveOnHold(),
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

    public function updatedAmountPaid(): void
    {
        $this->recalculateTotals();
    }

    public function updatedPaymentMethod(): void
    {
        if ($this->payment_method !== 'bank_transfer') {
            $this->bank_account_id = null;
        }
    }

    public function updatedPaymentStatus(): void
    {
        $service = app(SaleService::class);

        if ($this->payment_status === 'paid') {
            $this->amount_paid = $this->total_amount !== '0' ? $this->total_amount : $service->formatMoney(0);
        } elseif ($this->payment_status === 'unpaid') {
            $this->amount_paid = $service->formatMoney(0);
        }

        $this->recalculateTotals();
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

    public function formattedSaleDate(): string
    {
        try {
            return \Carbon\Carbon::parse($this->sale_date)->format('M j, Y');
        } catch (\Throwable) {
            return $this->sale_date;
        }
    }

    public function isAmountPaidEditable(): bool
    {
        return $this->payment_status === 'partial' && ! $this->isReadonly();
    }

    public function isReadonly(): bool
    {
        return $this->mode === 'view' || $this->invoice_status === Sale::STATUS_COMPLETED;
    }

    public function saveAndClose(): void
    {
        $this->persist(Sale::STATUS_COMPLETED, close: true);
    }

    public function saveOnHold(): void
    {
        $this->persist(Sale::STATUS_HELD, close: true);
    }

    public function saveAndPrint(): void
    {
        if ($this->isReadonly() && $this->saleId) {
            $this->dispatch('sales-open-print', url: route('admin.sales.print', ['sale' => $this->saleId]));

            return;
        }

        $sale = $this->persist(Sale::STATUS_COMPLETED, close: true);

        if ($sale instanceof Sale) {
            $this->dispatch('sales-open-print', url: route('admin.sales.print', ['sale' => $sale->id]));
        }
    }

    public function render(): View
    {
        $service = app(SaleService::class);

        return view('livewire.sales.form', [
            'productResults' => $service->searchProducts($this->product_search, app()->getLocale()),
            'customerResults' => app(CustomerService::class)->searchCustomers($this->customer_search),
            'selectedCustomer' => $this->customer_id ? Customer::query()->find($this->customer_id) : null,
            'paymentOptions' => collect(SaleService::PAYMENT_STATUSES)
                ->mapWithKeys(fn (string $status): array => [$status => __('pos.payment_statuses.' . $status)])
                ->all(),
            'paymentMethodOptions' => collect(SaleService::PAYMENT_METHODS)
                ->mapWithKeys(fn (string $method): array => [$method => __('pos.payment_methods.' . $method)])
                ->all(),
            'bankAccountOptions' => app(SalesBankAccountService::class)->options(),
            'discountOptions' => __('pos.discount_types'),
            'printControlsOptions' => __('pos.print_controls_options'),
            'localeOptions' => collect(SaleService::LOCALES)
                ->mapWithKeys(fn (string $locale): array => [$locale => __('pos.locales.' . $locale)])
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

    private function persist(string $targetStatus, bool $close): ?Sale
    {
        if ($this->isReadonly()) {
            return null;
        }

        if (! $this->userCan($this->saleId ? 'edit' : 'create')) {
            return null;
        }

        if (! $this->validateStatuses()) {
            return null;
        }

        $this->saving = true;
        $this->dispatchBusyState();

        try {
            $service = app(SaleService::class);
            $this->rows = $service->recalculateRows($this->rows);
            $payload = $this->buildPayload();

            if ($this->saleId) {
                $sale = Sale::query()->findOrFail($this->saleId);
                $sale = $service->update($sale, $payload, $targetStatus);
                $messageKey = $targetStatus === Sale::STATUS_HELD
                    ? 'pos.messages.held_updated'
                    : 'pos.messages.updated';
                $this->setStatus(__($messageKey, ['number' => $sale->sale_number]), 'success');
            } else {
                $sale = $targetStatus === Sale::STATUS_HELD
                    ? $service->hold($payload)
                    : $service->complete($payload);
                $this->saleId = $sale->id;
                $this->sale_number = $sale->sale_number;
                $this->invoice_status = $sale->status;
                $messageKey = $targetStatus === Sale::STATUS_HELD
                    ? 'pos.messages.held'
                    : 'pos.messages.completed';
                $this->setStatus(__($messageKey, ['number' => $sale->sale_number]), 'success');
            }

            $this->loadSale($sale->id);
            $this->dispatch('sales-saved');

            if ($close) {
                $this->dispatch('close-form-panel');
            }

            return $sale;
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first()
                ?? __('pos.messages.save_failed');
            $this->setStatus((string) $message, 'error');
        } catch (\Throwable) {
            $this->setStatus(__('pos.messages.save_failed'), 'error');
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
            'sale_number' => filled($this->sale_number) ? $this->sale_number : null,
            'customer_id' => $this->customer_id,
            'sale_date' => $this->sale_date,
            'locale' => app(SaleService::class)->normalizeLocale($this->locale),
            'payment_method' => $this->payment_method,
            'bank_account_id' => $this->payment_method === 'bank_transfer' ? $this->bank_account_id : null,
            'payment_status' => $this->payment_status,
            'price_display' => 'plain',
            'discount_type' => $this->discount_type ?: null,
            'discount_value' => $this->discount_value !== '' ? $this->discount_value : '0',
            'amount_paid' => $this->amount_paid,
            'print_controls' => $this->print_controls_mode === 'yes',
            'notes' => $this->notes,
            'rows' => $this->rows,
        ];
    }

    private function loadSale(int $saleId): void
    {
        $sale = Sale::query()->find($saleId);

        if ($sale === null) {
            return;
        }

        $state = app(SaleService::class)->toFormState($sale);
        $this->sale_number = (string) $state['sale_number'];
        $this->customer_id = $state['customer_id'] ? (int) $state['customer_id'] : null;
        $this->sale_date = (string) $state['sale_date'];
        $this->locale = (string) ($state['locale'] ?? 'en');
        $this->invoice_status = (string) $state['status'];
        $this->payment_method = (string) $state['payment_method'];
        $this->bank_account_id = isset($state['bank_account_id']) ? (int) $state['bank_account_id'] : null;
        $this->payment_status = (string) $state['payment_status'];
        $this->price_display = (string) $state['price_display'];
        $this->discount_type = (string) $state['discount_type'];
        $this->discount_value = filled($state['discount_value'] ?? null) ? (string) $state['discount_value'] : '0';
        $this->subtotal = (string) $state['subtotal'];
        $this->discount_amount = (string) $state['discount_amount'];
        $this->total_amount = (string) $state['total_amount'];
        $this->amount_paid = (string) $state['amount_paid'];
        $this->balance_due = (string) $state['balance_due'];
        $this->print_controls_mode = ($state['print_controls'] ?? false) ? 'yes' : 'no';
        $this->notes = (string) $state['notes'];
        $this->rows = $state['rows'];

        if ($this->mode === 'edit' && $sale->isCompleted()) {
            $this->mode = 'view';
        }
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
        $paid = $service->numeric($this->amount_paid !== '' ? $this->amount_paid : '0');

        $this->subtotal = $service->formatMoney($subtotal);
        $this->discount_amount = $service->formatMoney($discount);
        $this->total_amount = $service->formatMoney($total);

        if ($this->payment_status === 'paid') {
            $this->amount_paid = $this->total_amount;
            $paid = $total;
        } elseif ($this->payment_status === 'unpaid') {
            $this->amount_paid = $service->formatMoney(0);
            $paid = 0.0;
        }

        $this->balance_due = $service->formatMoney(max(0, $total - $paid));

        $this->recalculatingTotals = false;
    }

    private function validateStatuses(): bool
    {
        if ($this->payment_method === '') {
            $this->setStatus(__('pos.messages.payment_method_required'), 'warning');

            return false;
        }

        if ($this->payment_status === '') {
            $this->setStatus(__('pos.messages.payment_status_required'), 'warning');

            return false;
        }

        if ($this->payment_method === 'bank_transfer') {
            $banks = app(SalesBankAccountService::class)->options();

            if ($banks !== [] && $this->bank_account_id === null) {
                $this->setStatus(__('pos.messages.bank_account_required'), 'warning');

                return false;
            }
        }

        return true;
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
        $this->dispatch('sale-form-busy', saving: $this->saving);
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

        return $user->hasPermission('sales.' . $action);
    }

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }

    private function resetForm(): void
    {
        $this->reset([
            'saleId',
            'sale_number',
            'customer_id',
            'customer_search',
            'sale_date',
            'locale',
            'invoice_status',
            'payment_method',
            'bank_account_id',
            'payment_status',
            'amount_paid',
            'discount_type',
            'discount_value',
            'price_display',
            'print_controls_mode',
            'notes',
            'subtotal',
            'discount_amount',
            'total_amount',
            'balance_due',
            'rows',
            'product_search',
            'statusMessage',
        ]);
        $this->statusType = 'success';
        $this->amount_paid = '0';
        $this->discount_value = '0';
        $this->print_controls_mode = 'no';
        $this->locale = app(SaleService::class)->normalizeLocale(app()->getLocale());
        $this->subtotal = '0';
        $this->discount_amount = '0';
        $this->total_amount = '0';
        $this->balance_due = '0';
    }
}
