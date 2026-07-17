<?php

namespace App\Services\Sales;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\Finance\BankAccountService;
use App\Services\PricingControls\QuantityPricingEngine;
use App\Services\Purchasing\PurchaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public const PAYMENT_STATUSES = ['paid', 'partial', 'unpaid'];

    public const PAYMENT_METHODS = ['cash', 'bank_transfer'];

    public const DISCOUNT_TYPES = ['percent', 'fixed'];

    public const PRICE_DISPLAYS = ['plain', 'secret'];

    /** @var list<string> */
    public const LOCALES = ['en', 'ur'];

    public function normalizeLocale(?string $locale): string
    {
        return in_array($locale, self::LOCALES, true) ? $locale : 'en';
    }

    public function __construct(
        private readonly PurchaseService $purchaseService,
        private readonly QuantityPricingEngine $quantityPricing,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function complete(array $data): Sale
    {
        return $this->create($data, Sale::STATUS_COMPLETED);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function hold(array $data): Sale
    {
        return $this->create($data, Sale::STATUS_HELD);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, string $status): Sale
    {
        $validated = $this->validate($data, $status);
        $rows = $validated['rows'];
        unset($validated['rows']);

        return DB::transaction(function () use ($validated, $rows, $status): Sale {
            if (! filled($validated['sale_number'] ?? null)) {
                $validated['sale_number'] = $this->nextSaleNumber();
            }

            $validated['cashier_id'] = $validated['cashier_id'] ?? auth()->id();
            $validated['status'] = $status;

            $sale = Sale::query()->create($validated);
            $this->syncItems($sale, $rows, $status);

            if ($status === Sale::STATUS_COMPLETED) {
                $billable = $this->billableRows($rows);
                $this->adjustStock($billable);
            }

            $sale = $sale->fresh(['items', 'customer', 'cashier']);

            if ($status === Sale::STATUS_COMPLETED) {
                app(BankAccountService::class)->recordSaleReceiptIfApplicable($sale);
                app(\App\Services\Finance\CashBookService::class)->recordSaleReceiptIfApplicable($sale);
            }

            return $sale;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Sale $sale, array $data, string $status): Sale
    {
        if ($sale->isCompleted()) {
            throw ValidationException::withMessages([
                'sale' => [__('pos.validation.completed_locked')],
            ]);
        }

        $wasHeld = $sale->isHeld();
        $validated = $this->validate($data, $status, $sale->id);
        $rows = $validated['rows'];
        unset($validated['rows']);

        return DB::transaction(function () use ($sale, $validated, $rows, $status, $wasHeld): Sale {
            $validated['status'] = $status;
            $sale->update($validated);
            $this->syncItems($sale, $rows, $status);

            if ($status === Sale::STATUS_COMPLETED && $wasHeld) {
                $billable = $this->billableRows($rows);
                $this->adjustStock($billable);
            }

            $sale = $sale->fresh(['items', 'customer', 'cashier']);

            if ($status === Sale::STATUS_COMPLETED) {
                app(BankAccountService::class)->recordSaleReceiptIfApplicable($sale);
                app(\App\Services\Finance\CashBookService::class)->recordSaleReceiptIfApplicable($sale);
            }

            return $sale;
        });
    }

    public function delete(Sale $sale): void
    {
        DB::transaction(function () use ($sale): void {
            if ($sale->isCompleted()) {
                $sale->loadMissing('items');
                $rows = $sale->items->map(fn (SaleItem $item): array => [
                    'product_id' => $item->product_id,
                    'quantity' => (string) $item->quantity,
                ])->all();
                $this->restoreStock($this->billableRows($rows));
            }

            $sale->delete();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function toFormState(Sale $sale): array
    {
        $sale->loadMissing(['items', 'cashier']);

        $rows = [];

        foreach ($sale->items as $item) {
            $rows[] = [
                'product_id' => $item->product_id,
                'product_name_en' => (string) $item->product_name_en,
                'product_name_ur' => (string) ($item->product_name_ur ?? ''),
                'brand_name_en' => (string) ($item->brand_name_en ?? ''),
                'brand_name_ur' => (string) ($item->brand_name_ur ?? ''),
                'unit_label' => (string) ($item->unit_label ?? ''),
                'image_path' => $item->image_path,
                'quantity' => (string) ($item->quantity ?? '0'),
                'unit_price' => (string) ($item->unit_price ?? '0'),
                'price_tier_key' => (string) ($item->price_tier_key ?? 'retail'),
                'line_total' => (string) ($item->line_total ?? '0'),
                'price_manual' => false,
            ];
        }

        $total = (float) $sale->total_amount;
        $paid = (float) $sale->amount_paid;

        return [
            'sale_number' => (string) $sale->sale_number,
            'customer_id' => $sale->customer_id,
            'cashier_id' => $sale->cashier_id,
            'sale_date' => $sale->sale_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'locale' => $sale->displayLocale(),
            'payment_status' => (string) $sale->payment_status,
            'payment_method' => (string) $sale->payment_method,
            'bank_account_id' => $sale->bank_account_id ? (int) $sale->bank_account_id : null,
            'price_display' => (string) $sale->price_display,
            'discount_type' => (string) ($sale->discount_type ?? ''),
            'discount_value' => $sale->discount_value !== null && $sale->discount_value !== '' ? (string) $sale->discount_value : '0',
            'subtotal' => $this->formatMoney((float) $sale->subtotal),
            'discount_amount' => $this->formatMoney((float) $sale->discount_amount),
            'total_amount' => $this->formatMoney($total),
            'amount_paid' => $this->formatMoney($paid),
            'balance_due' => $this->formatMoney(max(0, $total - $paid)),
            'print_controls' => (bool) $sale->print_controls,
            'notes' => (string) ($sale->notes ?? ''),
            'status' => (string) $sale->status,
            'rows' => $rows,
        ];
    }

    public function nextSaleNumber(): string
    {
        $next = (int) Sale::query()->max('id') + 1;

        do {
            $number = 'SAL-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            $next++;
        } while (Sale::query()->where('sale_number', $number)->exists());

        return $number;
    }

    /**
     * @return array<int, array{id: int, label: string, image_url: ?string}>
     */
    public function searchProducts(string $term, string $locale = 'en', int $limit = 20): array
    {
        return $this->purchaseService->searchProducts($term, $locale, $limit);
    }

    public function findProductByScan(string $term): ?Product
    {
        return $this->purchaseService->findProductByScan($term);
    }

    /**
     * @return array<string, mixed>
     */
    public function rowFromProduct(Product $product, string $quantity = '0'): array
    {
        $product->loadMissing(['brand', 'unit']);

        $qty = $this->numeric($quantity);

        if ($qty > 0) {
            $resolved = $this->quantityPricing->resolveForQuantity($product, $qty, forPos: true);
            $unitPrice = $resolved['price'] ?? (string) ($product->retail_price ?? '0');
            $tierKey = $resolved !== null ? (string) ($resolved['tier']->tier_key ?? 'retail') : 'retail';
        } else {
            $unitPrice = filled($product->retail_price) ? (string) $product->retail_price : '0';
            $tierKey = 'retail';
        }

        $brand = $product->brand;
        $unit = $product->unit;

        return [
            'product_id' => $product->id,
            'product_name_en' => (string) $product->name_en,
            'product_name_ur' => (string) ($product->name_ur ?? ''),
            'brand_name_en' => $brand ? (string) $brand->name_en : '',
            'brand_name_ur' => $brand ? (string) ($brand->name_ur ?? '') : '',
            'unit_label' => $unit ? (string) ($unit->name_en ?? $unit->code ?? '') : '',
            'image_path' => $product->image_path,
            'quantity' => $qty > 0 ? $this->formatQty($qty) : '0',
            'unit_price' => $unitPrice,
            'price_tier_key' => $tierKey,
            'line_total' => $this->formatMoney($qty * $this->numeric($unitPrice)),
            'price_manual' => false,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    public function recalculateRows(array $rows): array
    {
        $normalized = [];

        foreach ($rows as $row) {
            $productId = (int) ($row['product_id'] ?? 0);
            $product = $productId > 0 ? Product::query()->find($productId) : null;

            $qty = $this->numeric((string) ($row['quantity'] ?? '0'));

            if ($product instanceof Product && $qty > 0 && empty($row['price_manual'])) {
                $resolved = $this->quantityPricing->resolveForQuantity($product, $qty, forPos: true);

                if ($resolved !== null && filled($resolved['price'] ?? null)) {
                    $row['unit_price'] = (string) $resolved['price'];
                    $row['price_tier_key'] = $resolved['tier']->tier_key ?? ($row['price_tier_key'] ?? 'retail');
                }
            }

            $row['line_total'] = $this->formatMoney($qty * $this->numeric((string) ($row['unit_price'] ?? '0')));
            $normalized[] = $row;
        }

        return $normalized;
    }

    public function subtotalFromRows(array $rows): float
    {
        $total = 0.0;

        foreach ($rows as $row) {
            $total += $this->numeric((string) ($row['line_total'] ?? '0'));
        }

        return round($total, 2);
    }

    public function discountAmount(float $subtotal, ?string $type, ?string $value): float
    {
        $type = (string) ($type ?? '');
        $amount = $this->numeric((string) ($value ?? '0'));

        if ($subtotal <= 0 || $amount <= 0) {
            return 0.0;
        }

        if ($type === 'percent') {
            return round(min($subtotal, $subtotal * ($amount / 100)), 2);
        }

        if ($type === 'fixed') {
            return round(min($subtotal, $amount), 2);
        }

        return 0.0;
    }

    public function resolvePaymentStatus(float $total, float $paid): string
    {
        if ($total <= 0) {
            return 'paid';
        }

        if ($paid >= $total) {
            return 'paid';
        }

        if ($paid > 0) {
            return 'partial';
        }

        return 'unpaid';
    }

    public function numeric(string $value): float
    {
        $value = str_replace([',', ' '], '', trim($value));

        return is_numeric($value) ? (float) $value : 0.0;
    }

    public function formatMoney(float $value): string
    {
        if ($value <= 0) {
            return '0';
        }

        $formatted = number_format($value, 2, '.', '');

        return str_ends_with($formatted, '.00') ? (string) (int) $value : rtrim(rtrim($formatted, '0'), '.');
    }

    public function formatQty(float $value): string
    {
        if ($value <= 0) {
            return '';
        }

        return (string) ((int) $value == $value ? (int) $value : $value);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data, string $targetStatus, ?int $ignoreSaleId = null): array
    {
        $uniqueRule = Rule::unique('sales', 'sale_number');

        if ($ignoreSaleId !== null) {
            $uniqueRule->ignore($ignoreSaleId);
        }

        $validated = validator($data, [
            'sale_number' => ['nullable', 'string', 'max:32', $uniqueRule],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'sale_date' => ['required', 'date'],
            'locale' => ['required', Rule::in(self::LOCALES)],
            'payment_status' => ['required', Rule::in(self::PAYMENT_STATUSES)],
            'payment_method' => ['required', Rule::in(self::PAYMENT_METHODS)],
            'bank_account_id' => ['nullable', 'integer'],
            'price_display' => ['required', Rule::in(self::PRICE_DISPLAYS)],
            'discount_type' => ['nullable', Rule::in([...self::DISCOUNT_TYPES, ''])],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'print_controls' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'rows.*.quantity' => ['required', 'string', 'max:32'],
            'rows.*.unit_price' => ['required', 'string', 'max:32'],
            'rows.*.line_total' => ['nullable', 'string', 'max:32'],
            'rows.*.product_name_en' => ['required', 'string', 'max:255'],
            'rows.*.product_name_ur' => ['nullable', 'string', 'max:255'],
            'rows.*.brand_name_en' => ['nullable', 'string', 'max:255'],
            'rows.*.brand_name_ur' => ['nullable', 'string', 'max:255'],
            'rows.*.unit_label' => ['nullable', 'string', 'max:64'],
            'rows.*.image_path' => ['nullable', 'string', 'max:500'],
            'rows.*.price_tier_key' => ['nullable', 'string', 'max:64'],
        ])->validate();

        if (! in_array($targetStatus, Sale::STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => [__('pos.validation.invalid_status')],
            ]);
        }

        $rows = $this->recalculateRows($validated['rows']);
        $rows = array_values(array_filter(
            $rows,
            fn (array $row): bool => (int) ($row['product_id'] ?? 0) > 0,
        ));

        if ($rows === []) {
            throw ValidationException::withMessages([
                'rows' => [__('pos.validation.products_required')],
            ]);
        }

        if ($targetStatus === Sale::STATUS_COMPLETED) {
            $billable = $this->billableRows($rows);

            if ($billable === []) {
                throw ValidationException::withMessages([
                    'rows' => [__('pos.validation.items_required')],
                ]);
            }

            $rows = $billable;
        }

        $subtotal = $this->subtotalFromRows($rows);
        $discountAmount = $this->discountAmount(
            $subtotal,
            $validated['discount_type'] ?? null,
            isset($validated['discount_value']) ? (string) $validated['discount_value'] : null,
        );
        $total = max(0, round($subtotal - $discountAmount, 2));
        $paid = $this->numeric((string) ($validated['amount_paid'] ?? '0'));

        $validated['subtotal'] = $subtotal;
        $validated['discount_amount'] = $discountAmount;
        $validated['total_amount'] = $total;
        $validated['amount_paid'] = $paid;
        $validated['payment_status'] = $this->resolvePaymentStatus($total, $paid);
        $validated['print_controls'] = (bool) ($validated['print_controls'] ?? false);
        $validated['locale'] = $this->normalizeLocale($validated['locale'] ?? 'en');
        $validated['rows'] = $rows;

        return $validated;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function billableRows(array $rows): array
    {
        return array_values(array_filter(
            $rows,
            fn (array $row): bool => $this->numeric((string) ($row['quantity'] ?? '0')) > 0,
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function syncItems(Sale $sale, array $rows, string $status): void
    {
        $sale->items()->delete();

        $persistRows = $status === Sale::STATUS_COMPLETED ? $this->billableRows($rows) : $rows;

        foreach ($persistRows as $index => $row) {
            SaleItem::query()->create([
                'sale_id' => $sale->id,
                'product_id' => (int) $row['product_id'],
                'sort_order' => $index,
                'product_name_en' => (string) $row['product_name_en'],
                'product_name_ur' => (string) ($row['product_name_ur'] ?? ''),
                'brand_name_en' => (string) ($row['brand_name_en'] ?? ''),
                'brand_name_ur' => (string) ($row['brand_name_ur'] ?? ''),
                'unit_label' => (string) ($row['unit_label'] ?? ''),
                'image_path' => $row['image_path'] ?? null,
                'quantity' => (string) ($row['quantity'] ?? ''),
                'unit_price' => (string) ($row['unit_price'] ?? ''),
                'price_tier_key' => (string) ($row['price_tier_key'] ?? ''),
                'line_total' => (string) ($row['line_total'] ?? ''),
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function adjustStock(array $rows): void
    {
        foreach ($this->aggregateStockDeltas($rows) as $productId => $qty) {
            $product = Product::query()->lockForUpdate()->find($productId);

            if ($product === null) {
                continue;
            }

            if (! $this->allowsOverselling() && $product->stock_qty !== null) {
                $available = (float) $product->stock_qty;

                if ($qty > $available + 0.0001) {
                    throw ValidationException::withMessages([
                        'rows' => [__('pos.validation.insufficient_stock', [
                            'product' => (string) $product->name_en,
                            'available' => $this->formatQty($available),
                            'requested' => $this->formatQty($qty),
                        ])],
                    ]);
                }
            }

            Product::query()->whereKey($productId)->decrement('stock_qty', $qty);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function restoreStock(array $rows): void
    {
        foreach ($this->aggregateStockDeltas($rows) as $productId => $qty) {
            Product::query()->lockForUpdate()->find($productId);
            Product::query()->whereKey($productId)->increment('stock_qty', $qty);
        }
    }

    public function allowsOverselling(): bool
    {
        return (bool) config('agricart.inventory.allow_overselling', true);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<int, float>
     */
    private function aggregateStockDeltas(array $rows): array
    {
        $byProduct = [];

        foreach ($rows as $row) {
            $productId = (int) ($row['product_id'] ?? 0);
            $qty = $this->numeric((string) ($row['quantity'] ?? '0'));

            if ($productId <= 0 || $qty <= 0) {
                continue;
            }

            $byProduct[$productId] = ($byProduct[$productId] ?? 0.0) + $qty;
        }

        ksort($byProduct);

        return $byProduct;
    }
}
