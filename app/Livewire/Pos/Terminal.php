<?php

namespace App\Livewire\Pos;

use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Services\Contacts\CustomerService;
use App\Services\PricingControls\QuantityPricingEngine;
use App\Services\PricingControls\SecretCodingEngine;
use App\Services\Sales\SaleService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Terminal extends Component
{
    public ?int $customer_id = null;

    public string $customer_search = '';

    public string $quick_customer_name = '';

    public string $quick_customer_phone = '';

    public string $sale_date = '';

    public string $product_search = '';

    /** @var list<array<string, mixed>> */
    public array $rows = [];

    public string $payment_method = 'cash';

    public string $amount_paid = '';

    public string $discount_type = '';

    public string $discount_value = '';

    public string $price_display = 'plain';

    public bool $print_controls = false;

    public string $notes = '';

    public bool $saving = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    public function mount(): void
    {
        $this->sale_date = now()->format('Y-m-d');
    }

    public function updatedProductSearch(): void
    {
        // Live search only in view via computed property.
    }

    public function updatedRows(): void
    {
        $this->syncRowsAndTotals();
    }

    private function syncRowsAndTotals(): void
    {
        $this->rows = app(SaleService::class)->recalculateRows($this->rows);
        $this->syncAmountPaidDefault();
    }

    public function updatedDiscountType(): void
    {
        $this->syncAmountPaidDefault();
    }

    public function updatedDiscountValue(): void
    {
        $this->syncAmountPaidDefault();
    }

    public function scanProduct(): void
    {
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
        if (! $this->userCan('create')) {
            return;
        }

        $product = Product::query()->where('status', 'active')->find($productId);

        if ($product === null) {
            return;
        }

        foreach ($this->rows as $index => $row) {
            if ((int) ($row['product_id'] ?? 0) === $product->id) {
                return;
            }
        }

        $this->rows[] = app(SaleService::class)->rowFromProduct($product, '0');
        $this->syncAmountPaidDefault();
    }

    public function removeRow(int $index): void
    {
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
        $this->syncAmountPaidDefault();
    }

    public function markPriceManual(int $index): void
    {
        if (! isset($this->rows[$index])) {
            return;
        }

        $this->rows[$index]['price_manual'] = true;
        $this->syncRowsAndTotals();
    }

    public function selectCustomer(int $customerId): void
    {
        $customer = Customer::query()->find($customerId);

        if ($customer === null) {
            return;
        }

        $this->customer_id = $customer->id;
        $this->customer_search = '';
    }

    public function clearCustomer(): void
    {
        $this->customer_id = null;
        $this->customer_search = '';
    }

    public function quickAddCustomer(): void
    {
        if (! $this->userCan('create')) {
            return;
        }

        try {
            $customer = app(CustomerService::class)->createOffline([
                'display_name' => trim($this->quick_customer_name),
                'phoneRows' => [['mobile_number' => trim($this->quick_customer_phone)]],
            ]);

            $this->customer_id = $customer->id;
            $this->quick_customer_name = '';
            $this->quick_customer_phone = '';
            $this->setStatus(__('pos.messages.customer_added', ['name' => $customer->displayLabel()]), 'success');
        } catch (ValidationException $exception) {
            $this->setStatus($exception->validator->errors()->first() ?: __('pos.messages.customer_failed'), 'danger');
        }
    }

    public function completeSale(): void
    {
        if (! $this->userCan('create')) {
            return;
        }

        $this->saving = true;
        $this->statusMessage = null;

        try {
            $service = app(SaleService::class);
            $rows = $service->recalculateRows($this->rows);
            $subtotal = $service->subtotalFromRows($rows);
            $discount = $service->discountAmount($subtotal, $this->discount_type ?: null, $this->discount_value ?: null);
            $total = max(0, round($subtotal - $discount, 2));
            $paid = $service->numeric($this->amount_paid !== '' ? $this->amount_paid : (string) $total);

            $sale = $service->complete([
                'customer_id' => $this->customer_id,
                'sale_date' => $this->sale_date,
                'locale' => $service->normalizeLocale(app()->getLocale()),
                'payment_method' => $this->payment_method,
                'payment_status' => $service->resolvePaymentStatus($total, $paid),
                'price_display' => $this->price_display,
                'discount_type' => $this->discount_type ?: null,
                'discount_value' => $this->discount_value ?: null,
                'amount_paid' => $paid,
                'print_controls' => $this->print_controls,
                'notes' => $this->notes,
                'rows' => $rows,
            ]);

            $this->dispatch('pos-sale-completed', saleId: $sale->id);
            $this->setStatus(__('pos.messages.completed', ['number' => $sale->sale_number]), 'success');
            $this->resetCart();

            $this->dispatch('open-pos-print', url: route('admin.sales.print', ['sale' => $sale->id]));
        } catch (ValidationException $exception) {
            $this->setStatus($exception->validator->errors()->first() ?: __('pos.messages.save_failed'), 'danger');
        } catch (\Throwable) {
            $this->setStatus(__('pos.messages.save_failed'), 'danger');
        } finally {
            $this->saving = false;
        }
    }

    public function displayPrice(string $amount): string
    {
        $amount = trim($amount);

        if ($amount === '' || $this->price_display !== 'secret') {
            return $amount;
        }

        $numeric = app(SaleService::class)->numeric($amount);

        if ($numeric <= 0) {
            return $amount;
        }

        return app(SecretCodingEngine::class)->encodePrice((string) (int) round($numeric));
    }

    public function tierLabel(string $tierKey): string
    {
        return app(QuantityPricingEngine::class)->tierTypeLabel($tierKey);
    }

    public function rowImageUrl(?string $path): ?string
    {
        return filled($path) ? Storage::disk('public')->url($path) : null;
    }

    public function render(): View
    {
        $service = app(SaleService::class);
        $rows = $service->recalculateRows($this->rows);
        $subtotal = $service->subtotalFromRows($rows);
        $discount = $service->discountAmount($subtotal, $this->discount_type ?: null, $this->discount_value ?: null);
        $total = max(0, round($subtotal - $discount, 2));
        $hasBillableRows = collect($rows)->contains(
            fn (array $row): bool => $service->numeric((string) ($row['quantity'] ?? '0')) > 0,
        );

        return view('livewire.pos.terminal', [
            'productResults' => $service->searchProducts($this->product_search, app()->getLocale()),
            'customerResults' => app(CustomerService::class)->searchCustomers($this->customer_search),
            'selectedCustomer' => $this->customer_id ? Customer::query()->find($this->customer_id) : null,
            'subtotal' => $subtotal,
            'discountAmount' => $discount,
            'total' => $total,
            'rows' => $rows,
            'hasBillableRows' => $hasBillableRows,
        ]);
    }

    private function resetCart(): void
    {
        $this->rows = [];
        $this->customer_id = null;
        $this->product_search = '';
        $this->discount_type = '';
        $this->discount_value = '';
        $this->amount_paid = '';
        $this->notes = '';
        $this->print_controls = false;
    }

    private function syncAmountPaidDefault(): void
    {
        if ($this->amount_paid !== '') {
            return;
        }

        $service = app(SaleService::class);
        $subtotal = $service->subtotalFromRows($this->rows);
        $discount = $service->discountAmount($subtotal, $this->discount_type ?: null, $this->discount_value ?: null);
        $total = max(0, round($subtotal - $discount, 2));

        if ($total > 0) {
            $this->amount_paid = $service->formatMoney($total);
        }
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

        return $user->hasPermission('sales.' . $action);
    }
}
