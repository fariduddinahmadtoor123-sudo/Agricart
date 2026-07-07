<?php

namespace App\Services\Purchasing;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Services\Catalog\ProductSearchQuery;
use App\Services\PricingControls\ChannelPricingEngine;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class PurchaseService
{
    /** @var list<string> */
    public const LOCALES = ['en', 'ur'];

    /** @var list<string> */
    public const DELIVERY_STATUSES = [
        Purchase::DELIVERY_PENDING,
        Purchase::DELIVERY_PARTIAL,
        Purchase::DELIVERY_RECEIVED,
    ];

    /** @var list<string> */
    public const PAYMENT_STATUSES = [
        Purchase::PAYMENT_PENDING,
        Purchase::PAYMENT_PARTIAL,
        Purchase::PAYMENT_PAID,
    ];

    /** @var list<string> */
    public const DISPUTE_STATUSES = [
        Purchase::DISPUTE_OK,
        Purchase::DISPUTE_DISPUTED,
    ];

    public function create(array $data, TemporaryUploadedFile|UploadedFile|null $vendorBill = null): Purchase
    {
        $validated = $this->validate($data);

        return DB::transaction(function () use ($validated, $vendorBill): Purchase {
            $items = $validated['items'];
            unset($validated['items']);

            if (! filled($validated['purchase_number'] ?? null)) {
                $validated['purchase_number'] = $this->nextPurchaseNumber();
            }

            $validated['created_by'] = $validated['created_by'] ?? auth()->id();
            $validated = $this->applyTotals($validated, $items);

            if ($vendorBill !== null) {
                $validated['vendor_bill_path'] = $this->storeVendorBill($vendorBill);
            }

            $purchase = Purchase::query()->create($validated);
            $this->syncItems($purchase, $items);
            $this->applySupplierLedger($purchase);
            $this->applyChannelPricing($purchase->fresh(['items']));
            app(ReorderService::class)->applyPurchaseStock($purchase->fresh(['items']));

            return $purchase->fresh(['items', 'supplier', 'purchaser', 'creator']);
        });
    }

    public function update(Purchase $purchase, array $data, TemporaryUploadedFile|UploadedFile|null $vendorBill = null, bool $removeVendorBill = false): Purchase
    {
        $validated = $this->validate($data, $purchase->id);
        $items = $validated['items'];
        unset($validated['items']);

        return DB::transaction(function () use ($purchase, $validated, $items, $vendorBill, $removeVendorBill): Purchase {
            $before = Purchase::query()->with('items')->findOrFail($purchase->id);

            $validated = $this->applyTotals($validated, $items);

            if ($removeVendorBill && filled($purchase->vendor_bill_path)) {
                Storage::disk('public')->delete($purchase->vendor_bill_path);
                $validated['vendor_bill_path'] = null;
            }

            if ($vendorBill !== null) {
                if (filled($purchase->vendor_bill_path)) {
                    Storage::disk('public')->delete($purchase->vendor_bill_path);
                }

                $validated['vendor_bill_path'] = $this->storeVendorBill($vendorBill);
            }

            $previousPurchase = clone $purchase;

            $purchase->update($validated);
            $this->syncItems($purchase, $items);
            $this->applySupplierLedger($purchase->fresh(), $previousPurchase);

            $fresh = $purchase->fresh(['items']);
            $this->applyChannelPricing($fresh);

            app(ReorderService::class)->refreshPurchaseStock($before, $fresh);

            if (in_array($fresh->delivery_status, [Purchase::DELIVERY_RECEIVED, Purchase::DELIVERY_PARTIAL], true)) {
                $productIds = $fresh->items->pluck('product_id')->filter()->map(fn ($id) => (int) $id)->all();
                app(ReorderService::class)->fulfillQueueForProducts($productIds);
            }

            return $purchase->fresh(['items', 'supplier', 'purchaser', 'creator']);
        });
    }

    public function delete(Purchase $purchase): void
    {
        DB::transaction(function () use ($purchase): void {
            $purchase->load('items');
            app(ReorderService::class)->reversePurchaseStock($purchase);
            $this->reverseSupplierLedger($purchase);

            if (filled($purchase->vendor_bill_path)) {
                Storage::disk('public')->delete($purchase->vendor_bill_path);
            }

            $purchase->delete();
        });
    }

    public function nextPurchaseNumber(): string
    {
        $next = (int) Purchase::query()->max('id') + 1;

        do {
            $number = 'PUR-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            $next++;
        } while (Purchase::query()->where('purchase_number', $number)->exists());

        return $number;
    }

    public function supplierBalance(Supplier $supplier): float
    {
        return (float) ($supplier->opening_balance ?? 0);
    }

    /**
     * Post purchase to supplier opening balance: invoice increases balance, payment reduces it.
     * Extra payment (amount paid above invoice total) reduces balance further.
     */
    public function applySupplierLedger(Purchase $purchase, ?Purchase $previousPurchase = null): void
    {
        if ($previousPurchase !== null) {
            $this->adjustSupplierBalance(
                $previousPurchase->supplier_id,
                -((float) $previousPurchase->total_amount - (float) $previousPurchase->amount_paid),
            );
        }

        $this->adjustSupplierBalance(
            $purchase->supplier_id,
            (float) $purchase->total_amount - (float) $purchase->amount_paid,
        );
    }

    public function reverseSupplierLedger(Purchase $purchase): void
    {
        $this->adjustSupplierBalance(
            $purchase->supplier_id,
            -((float) $purchase->total_amount - (float) $purchase->amount_paid),
        );
    }

    private function adjustSupplierBalance(?int $supplierId, float $delta): void
    {
        if ($supplierId === null || $delta === 0.0) {
            return;
        }

        $supplier = Supplier::query()->lockForUpdate()->find($supplierId);

        if ($supplier === null) {
            return;
        }

        $supplier->update([
            'opening_balance' => round((float) $supplier->opening_balance + $delta, 2),
        ]);
    }

    /**
     * @return array<int, array{id: int, label: string}>
     */
    public function searchSuppliers(string $term, int $limit = 20): array
    {
        $term = trim($term);

        if ($term === '') {
            return [];
        }

        $needle = '%' . $term . '%';

        return Supplier::query()
            ->where('status', 'active')
            ->where(function ($query) use ($needle): void {
                $query->where('supplier_code', 'like', $needle)
                    ->orWhere('business_name_en', 'like', $needle)
                    ->orWhere('business_name_ur', 'like', $needle)
                    ->orWhere('city_en', 'like', $needle);
            })
            ->orderBy('business_name_en')
            ->limit($limit)
            ->get(['id', 'business_name_en', 'business_name_ur', 'supplier_code'])
            ->map(fn (Supplier $supplier): array => [
                'id' => $supplier->id,
                'label' => $this->supplierLabel($supplier),
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, label: string, image_url: ?string}>
     */
    public function searchProducts(string $term, string $locale = 'en', int $limit = 20): array
    {
        $term = trim($term);

        if ($term === '' || mb_strlen($term) < 2) {
            return [];
        }

        $products = Product::query()
            ->where('status', 'active')
            ->tap(fn ($query) => ProductSearchQuery::apply($query, $term))
            ->orderBy('name_en')
            ->limit($limit)
            ->get(['id', 'name_en', 'name_ur', 'image_path']);

        return $products->map(fn (Product $product): array => [
            'id' => $product->id,
            'label' => $this->productLabel($product, $locale),
            'image_url' => $product->image_path ? Storage::disk('public')->url($product->image_path) : null,
        ])->all();
    }

    public function findProductByScan(string $term): ?Product
    {
        $term = trim($term);

        if ($term === '') {
            return null;
        }

        return Product::query()
            ->where('status', 'active')
            ->where(function ($query) use ($term): void {
                $query->where('label_code', $term)
                    ->orWhere('sku', $term)
                    ->orWhere('code', $term);

                if (ctype_digit($term)) {
                    $query->orWhere('id', (int) $term);
                }
            })
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function rowFromProduct(Product $product): array
    {
        $required = max(0, (int) ($product->required_qty ?? 0) - (int) ($product->alert_qty ?? 0));

        return [
            'product_id' => $product->id,
            'product_name_en' => $product->name_en,
            'product_name_ur' => $product->name_ur,
            'image_path' => $product->image_path,
            'required_qty' => $required > 0 ? (string) $required : '',
            'purchase_qty' => '',
            'previous_price' => $this->previousPurchasePrice($product->id),
            'current_rate' => '',
            'landing_cost' => '',
            'cost_price' => '',
            'sale_price' => (string) ($product->retail_price ?? ''),
            'line_total' => '',
        ];
    }

    public function previousPurchasePrice(int $productId): string
    {
        $item = PurchaseItem::query()
            ->where('product_id', $productId)
            ->whereNotNull('current_rate')
            ->where('current_rate', '!=', '')
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->orderByDesc('purchases.purchase_date')
            ->orderByDesc('purchases.id')
            ->select('purchase_items.current_rate')
            ->first();

        return $item ? (string) $item->current_rate : '';
    }

    /**
     * @return array<string, mixed>
     */
    public function toFormState(Purchase $purchase): array
    {
        return [
            'purchase_number' => $purchase->purchase_number,
            'supplier_id' => $purchase->supplier_id,
            'purchaser_id' => $purchase->purchaser_id,
            'locale' => $purchase->displayLocale(),
            'purchase_date' => $purchase->purchase_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'delivery_status' => $purchase->delivery_status,
            'payment_status' => $purchase->payment_status,
            'dispute_status' => $purchase->dispute_status ?? Purchase::DISPUTE_OK,
            'supplier_balance_snapshot' => (string) $purchase->supplier_balance_snapshot,
            'subtotal' => (string) $purchase->subtotal,
            'amount_paid' => (string) $purchase->amount_paid,
            'total_amount' => (string) $purchase->total_amount,
            'balance_due' => (string) $purchase->balance_due,
            'vendor_bill_path' => $purchase->vendor_bill_path,
            'notes' => (string) ($purchase->notes ?? ''),
            'rows' => $purchase->items->map(fn (PurchaseItem $item): array => [
                'product_id' => $item->product_id,
                'product_name_en' => $item->product_name_en,
                'product_name_ur' => (string) ($item->product_name_ur ?? ''),
                'image_path' => $item->image_path,
                'required_qty' => (string) ($item->required_qty ?? ''),
                'purchase_qty' => (string) ($item->purchase_qty ?? ''),
                'previous_price' => (string) ($item->previous_price ?? ''),
                'current_rate' => (string) ($item->current_rate ?? ''),
                'landing_cost' => (string) ($item->landing_cost ?? ''),
                'cost_price' => (string) ($item->cost_price ?? ''),
                'sale_price' => (string) ($item->sale_price ?? ''),
                'line_total' => (string) ($item->line_total ?? ''),
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validate(array $data, ?int $exceptId = null): array
    {
        $rules = [
            'purchase_number' => ['nullable', 'string', 'max:32', \Illuminate\Validation\Rule::unique('purchases', 'purchase_number')->ignore($exceptId)],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'purchaser_id' => ['required', 'integer', 'exists:users,id'],
            'locale' => ['required', 'string', 'in:' . implode(',', self::LOCALES)],
            'purchase_date' => ['required', 'date'],
            'delivery_status' => ['required', 'string', 'in:' . implode(',', self::DELIVERY_STATUSES)],
            'payment_status' => ['required', 'string', 'in:' . implode(',', self::PAYMENT_STATUSES)],
            'dispute_status' => ['required', 'string', 'in:' . implode(',', self::DISPUTE_STATUSES)],
            'supplier_balance_snapshot' => ['nullable', 'numeric'],
            'subtotal' => ['nullable', 'numeric'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'total_amount' => ['nullable', 'numeric'],
            'balance_due' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.product_name_en' => ['required', 'string', 'max:255'],
            'items.*.product_name_ur' => ['nullable', 'string', 'max:255'],
            'items.*.image_path' => ['nullable', 'string', 'max:500'],
            'items.*.required_qty' => ['nullable', 'string', 'max:32'],
            'items.*.purchase_qty' => ['nullable', 'string', 'max:32'],
            'items.*.previous_price' => ['nullable', 'string', 'max:32'],
            'items.*.current_rate' => ['nullable', 'string', 'max:32'],
            'items.*.landing_cost' => ['nullable', 'string', 'max:32'],
            'items.*.cost_price' => ['nullable', 'string', 'max:32'],
            'items.*.sale_price' => ['nullable', 'string', 'max:32'],
            'items.*.line_total' => ['nullable', 'string', 'max:32'],
        ];

        $validator = validator($data, $rules);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $validated = Arr::only($validator->validated(), array_keys($rules));
        $validated['items'] = $this->normalizeItems($validated['items']);

        return $validated;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    public function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $index => $item) {
            if (! is_array($item) || ! filled($item['product_name_en'] ?? null)) {
                continue;
            }

            $row = [
                'product_id' => filled($item['product_id'] ?? null) ? (int) $item['product_id'] : null,
                'sort_order' => $index,
                'product_name_en' => trim((string) $item['product_name_en']),
                'product_name_ur' => trim((string) ($item['product_name_ur'] ?? '')),
                'image_path' => filled($item['image_path'] ?? null) ? (string) $item['image_path'] : null,
                'required_qty' => trim((string) ($item['required_qty'] ?? '')),
                'purchase_qty' => trim((string) ($item['purchase_qty'] ?? '')),
                'previous_price' => trim((string) ($item['previous_price'] ?? '')),
                'current_rate' => trim((string) ($item['current_rate'] ?? '')),
                'landing_cost' => trim((string) ($item['landing_cost'] ?? '')),
                'cost_price' => trim((string) ($item['cost_price'] ?? '')),
                'sale_price' => trim((string) ($item['sale_price'] ?? '')),
                'line_total' => trim((string) ($item['line_total'] ?? '')),
            ];

            $row['line_total'] = $this->calculateLineTotal($row);
            $row['cost_price'] = $this->calculateCostPrice($row);

            $normalized[] = $row;
        }

        if ($normalized === []) {
            throw ValidationException::withMessages([
                'rows' => [__('purchases.validation.items_required')],
            ]);
        }

        return $normalized;
    }

    public function normalizeLocale(?string $locale): string
    {
        return in_array($locale, self::LOCALES, true) ? $locale : 'en';
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function rowDisplayName(array $row, string $locale): string
    {
        $locale = $this->normalizeLocale($locale);

        if ($locale === 'ur' && filled($row['product_name_ur'] ?? null)) {
            return (string) $row['product_name_ur'];
        }

        return (string) ($row['product_name_en'] ?? '');
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function calculateLineTotal(array $row): string
    {
        $qty = $this->numericValue($row['purchase_qty'] ?? null);
        $rate = $this->numericValue($row['current_rate'] ?? null);

        if ($qty === null || $rate === null) {
            return '';
        }

        return $this->formatMoney($qty * $rate);
    }

    /**
     * Cost price = current price + landing cost (per unit).
     *
     * @param  array<string, mixed>  $row
     */
    public function calculateCostPrice(array $row): string
    {
        $rate = $this->numericValue($row['current_rate'] ?? null);
        $landing = $this->numericValue($row['landing_cost'] ?? null);

        if ($rate === null) {
            return '';
        }

        if ($landing === null) {
            return $this->formatMoney($rate);
        }

        return $this->formatMoney($rate + $landing);
    }

    private function numericValue(mixed $value): ?float
    {
        if (! filled($value)) {
            return null;
        }

        $normalized = str_replace(',', '', (string) $value);

        if (! is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    private function formatMoney(float $amount): string
    {
        $formatted = number_format($amount, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyTotals(array $data, array $items): array
    {
        $subtotal = 0.0;

        foreach ($items as $item) {
            $line = $this->numericValue($item['line_total'] ?? null);
            $subtotal += $line ?? 0;
        }

        $data['subtotal'] = round($subtotal, 2);
        $data['total_amount'] = round($subtotal, 2);
        $amountPaid = $this->numericValue($data['amount_paid'] ?? 0) ?? 0.0;
        $data['amount_paid'] = round($amountPaid, 2);
        $data['balance_due'] = round(max(0, $data['total_amount'] - $amountPaid), 2);

        return $data;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function syncItems(Purchase $purchase, array $items): void
    {
        $purchase->items()->delete();

        foreach ($items as $item) {
            $purchase->items()->create($item);
        }
    }

    private function storeVendorBill(TemporaryUploadedFile|UploadedFile $file): string
    {
        return $file->store('purchases/vendor-bills', 'public');
    }

    private function supplierLabel(Supplier $supplier, ?string $locale = null): string
    {
        $locale = $this->normalizeLocale($locale ?? app()->getLocale());

        $name = $locale === 'ur' && filled($supplier->business_name_ur)
            ? $supplier->business_name_ur
            : $supplier->business_name_en;

        return trim($supplier->supplier_code . ' — ' . $name);
    }

    private function productLabel(Product $product, ?string $locale = null): string
    {
        $locale = $this->normalizeLocale($locale ?? app()->getLocale());

        if ($locale === 'ur' && filled($product->name_ur)) {
            return (string) $product->name_ur;
        }

        return (string) $product->name_en;
    }

    private function applyChannelPricing(Purchase $purchase): void
    {
        $purchase->loadMissing('items');
        $engine = app(ChannelPricingEngine::class);

        if ($engine->markupTiers()->isEmpty()) {
            return;
        }

        foreach ($purchase->items as $item) {
            if ($item->product_id === null) {
                continue;
            }

            $product = Product::query()->find($item->product_id);

            if ($product === null) {
                continue;
            }

            $purchasePrice = $this->numericValue($item->current_rate);

            if ($purchasePrice === null || $purchasePrice <= 0) {
                continue;
            }

            $retail = filled($item->sale_price) ? trim((string) $item->sale_price) : null;
            $engine->applyToProduct($product, $purchasePrice, $retail);
        }
    }
}
