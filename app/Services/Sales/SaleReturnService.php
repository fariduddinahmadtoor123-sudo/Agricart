<?php

namespace App\Services\Sales;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaleReturnService
{
    public function __construct(
        private readonly SaleService $saleService,
    ) {}

    public function findSaleForReturn(?int $customerId, string $saleNumber): Sale
    {
        $saleNumber = trim($saleNumber);

        if ($saleNumber === '') {
            throw ValidationException::withMessages([
                'sale_number' => [__('sale_returns.validation.sale_number_required')],
            ]);
        }

        $sale = Sale::query()
            ->where('sale_number', $saleNumber)
            ->where('status', Sale::STATUS_COMPLETED)
            ->with(['items' => fn ($q) => $q->orderBy('sort_order'), 'customer'])
            ->first();

        if ($sale === null) {
            throw ValidationException::withMessages([
                'sale_number' => [__('sale_returns.validation.sale_not_found')],
            ]);
        }

        if ($customerId !== null && $sale->customer_id !== null && (int) $sale->customer_id !== $customerId) {
            throw ValidationException::withMessages([
                'sale_number' => [__('sale_returns.validation.sale_customer_mismatch')],
            ]);
        }

        return $sale;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function rowsFromSale(Sale $sale): array
    {
        $rows = [];

        foreach ($sale->items as $item) {
            $rows[] = $this->rowFromSaleItem($item);
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    public function rowFromSaleItem(SaleItem $item): array
    {
        return [
            'sale_item_id' => $item->id,
            'product_id' => $item->product_id,
            'product_name_en' => (string) $item->product_name_en,
            'product_name_ur' => (string) ($item->product_name_ur ?? ''),
            'brand_name_en' => (string) ($item->brand_name_en ?? ''),
            'brand_name_ur' => (string) ($item->brand_name_ur ?? ''),
            'unit_label' => (string) ($item->unit_label ?? ''),
            'image_path' => $item->image_path,
            'sold_quantity' => (string) ($item->quantity ?? '0'),
            'unit_price' => (string) ($item->unit_price ?? '0'),
            'return_quantity' => '0',
            'return_amount' => '0',
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
            $returnQty = $this->saleService->numeric((string) ($row['return_quantity'] ?? '0'));
            $unitPrice = $this->saleService->numeric((string) ($row['unit_price'] ?? '0'));
            $row['return_amount'] = $this->saleService->formatMoney($returnQty * $unitPrice);
            $normalized[] = $row;
        }

        return $normalized;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function refundSubtotalFromRows(array $rows): float
    {
        $total = 0.0;

        foreach ($rows as $row) {
            $total += $this->saleService->numeric((string) ($row['return_amount'] ?? '0'));
        }

        return round($total, 2);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SaleReturn
    {
        $validated = $this->validate($data);
        $rows = $validated['rows'];
        unset($validated['rows']);

        return DB::transaction(function () use ($validated, $rows): SaleReturn {
            if (! filled($validated['return_number'] ?? null)) {
                $validated['return_number'] = $this->nextReturnNumber();
            }

            $validated['processed_by_id'] = $validated['processed_by_id'] ?? auth()->id();

            $return = SaleReturn::query()->create($validated);
            $this->syncItems($return, $rows);
            $this->adjustStock($rows, 1);

            return $return->fresh(['items', 'customer', 'sale', 'processedBy']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SaleReturn $return, array $data): SaleReturn
    {
        $validated = $this->validate($data, $return->id);
        $rows = $validated['rows'];
        unset($validated['rows']);

        return DB::transaction(function () use ($return, $validated, $rows): SaleReturn {
            $return->loadMissing('items');
            $oldRows = $return->items->map(fn (SaleReturnItem $item): array => [
                'product_id' => $item->product_id,
                'return_quantity' => (string) $item->return_quantity,
            ])->all();

            $this->adjustStock($oldRows, -1);

            $return->update($validated);
            $this->syncItems($return, $rows);
            $this->adjustStock($rows, 1);

            return $return->fresh(['items', 'customer', 'sale', 'processedBy']);
        });
    }

    public function delete(SaleReturn $return): void
    {
        DB::transaction(function () use ($return): void {
            $return->loadMissing('items');
            $rows = $return->items->map(fn (SaleReturnItem $item): array => [
                'product_id' => $item->product_id,
                'return_quantity' => (string) $item->return_quantity,
            ])->all();

            $this->adjustStock($rows, -1);
            $return->delete();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function toFormState(SaleReturn $return): array
    {
        $return->loadMissing(['items', 'sale', 'processedBy']);

        $rows = [];

        foreach ($return->items as $item) {
            $rows[] = [
                'sale_item_id' => $item->sale_item_id,
                'product_id' => $item->product_id,
                'product_name_en' => (string) $item->product_name_en,
                'product_name_ur' => (string) ($item->product_name_ur ?? ''),
                'brand_name_en' => (string) ($item->brand_name_en ?? ''),
                'brand_name_ur' => (string) ($item->brand_name_ur ?? ''),
                'unit_label' => (string) ($item->unit_label ?? ''),
                'image_path' => $item->image_path,
                'sold_quantity' => (string) ($item->sold_quantity ?? '0'),
                'unit_price' => (string) ($item->unit_price ?? '0'),
                'return_quantity' => (string) ($item->return_quantity ?? '0'),
                'return_amount' => (string) ($item->return_amount ?? '0'),
            ];
        }

        $total = (float) $return->refund_total;
        $paid = (float) $return->amount_paid;

        return [
            'return_number' => (string) $return->return_number,
            'sale_id' => (int) $return->sale_id,
            'sale_number' => (string) ($return->sale?->sale_number ?? ''),
            'customer_id' => $return->customer_id,
            'return_date' => $return->return_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'payment_status' => (string) $return->payment_status,
            'payment_method' => (string) $return->payment_method,
            'bank_account_id' => $return->bank_account_id ? (int) $return->bank_account_id : null,
            'refund_subtotal' => $this->saleService->formatMoney((float) $return->refund_subtotal),
            'refund_total' => $this->saleService->formatMoney($total),
            'amount_paid' => $this->saleService->formatMoney($paid),
            'balance_due' => $this->saleService->formatMoney(max(0, $total - $paid)),
            'notes' => (string) ($return->notes ?? ''),
            'rows' => $rows,
        ];
    }

    public function nextReturnNumber(): string
    {
        $next = (int) SaleReturn::query()->max('id') + 1;

        do {
            $number = 'RET-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            $next++;
        } while (SaleReturn::query()->where('return_number', $number)->exists());

        return $number;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data, ?int $ignoreReturnId = null): array
    {
        $uniqueRule = Rule::unique('sale_returns', 'return_number');

        if ($ignoreReturnId !== null) {
            $uniqueRule->ignore($ignoreReturnId);
        }

        $validated = validator($data, [
            'return_number' => ['nullable', 'string', 'max:32', $uniqueRule],
            'sale_id' => ['required', 'integer', 'exists:sales,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'return_date' => ['required', 'date'],
            'payment_status' => ['required', Rule::in(SaleService::PAYMENT_STATUSES)],
            'payment_method' => ['required', Rule::in(SaleService::PAYMENT_METHODS)],
            'bank_account_id' => ['nullable', 'integer'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.sale_item_id' => ['nullable', 'integer', 'exists:sale_items,id'],
            'rows.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'rows.*.sold_quantity' => ['nullable', 'string', 'max:32'],
            'rows.*.return_quantity' => ['required', 'string', 'max:32'],
            'rows.*.return_amount' => ['nullable', 'string', 'max:32'],
            'rows.*.unit_price' => ['required', 'string', 'max:32'],
            'rows.*.product_name_en' => ['required', 'string', 'max:255'],
            'rows.*.product_name_ur' => ['nullable', 'string', 'max:255'],
            'rows.*.brand_name_en' => ['nullable', 'string', 'max:255'],
            'rows.*.brand_name_ur' => ['nullable', 'string', 'max:255'],
            'rows.*.unit_label' => ['nullable', 'string', 'max:64'],
            'rows.*.image_path' => ['nullable', 'string', 'max:500'],
        ])->validate();

        $rows = $this->recalculateRows($validated['rows']);
        $rows = $this->billableRows($rows);

        if ($rows === []) {
            throw ValidationException::withMessages([
                'rows' => [__('sale_returns.validation.items_required')],
            ]);
        }

        $this->assertReturnQuantities($rows, (int) $validated['sale_id'], $ignoreReturnId);

        $subtotal = $this->refundSubtotalFromRows($rows);
        $paid = $this->saleService->numeric((string) ($validated['amount_paid'] ?? '0'));
        $total = $subtotal;

        $validated['refund_subtotal'] = $subtotal;
        $validated['refund_total'] = $total;
        $validated['amount_paid'] = $paid;
        $validated['payment_status'] = $this->saleService->resolvePaymentStatus($total, $paid);
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
            fn (array $row): bool => $this->saleService->numeric((string) ($row['return_quantity'] ?? '0')) > 0,
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function assertReturnQuantities(array $rows, int $saleId, ?int $ignoreReturnId): void
    {
        foreach ($rows as $index => $row) {
            $returnQty = $this->saleService->numeric((string) ($row['return_quantity'] ?? '0'));
            $soldQty = $this->saleService->numeric((string) ($row['sold_quantity'] ?? '0'));
            $saleItemId = (int) ($row['sale_item_id'] ?? 0);

            if ($returnQty > $soldQty) {
                throw ValidationException::withMessages([
                    "rows.{$index}.return_quantity" => [__('sale_returns.validation.qty_exceeds_sold')],
                ]);
            }

            if ($saleItemId <= 0) {
                continue;
            }

            $alreadyReturned = $this->returnedQuantityForSaleItem($saleItemId, $saleId, $ignoreReturnId);

            if ($returnQty + $alreadyReturned > $soldQty) {
                throw ValidationException::withMessages([
                    "rows.{$index}.return_quantity" => [__('sale_returns.validation.qty_exceeds_remaining')],
                ]);
            }
        }
    }

    private function returnedQuantityForSaleItem(int $saleItemId, int $saleId, ?int $ignoreReturnId): float
    {
        $query = SaleReturnItem::query()
            ->where('sale_item_id', $saleItemId)
            ->whereHas('saleReturn', fn ($q) => $q->where('sale_id', $saleId));

        if ($ignoreReturnId !== null) {
            $query->where('sale_return_id', '!=', $ignoreReturnId);
        }

        $sum = 0.0;

        foreach ($query->get(['return_quantity']) as $item) {
            $sum += $this->saleService->numeric((string) ($item->return_quantity ?? '0'));
        }

        return $sum;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function syncItems(SaleReturn $return, array $rows): void
    {
        $return->items()->delete();

        foreach ($rows as $index => $row) {
            SaleReturnItem::query()->create([
                'sale_return_id' => $return->id,
                'sale_item_id' => isset($row['sale_item_id']) ? (int) $row['sale_item_id'] : null,
                'product_id' => (int) $row['product_id'],
                'sort_order' => $index,
                'product_name_en' => (string) $row['product_name_en'],
                'product_name_ur' => (string) ($row['product_name_ur'] ?? ''),
                'brand_name_en' => (string) ($row['brand_name_en'] ?? ''),
                'brand_name_ur' => (string) ($row['brand_name_ur'] ?? ''),
                'unit_label' => (string) ($row['unit_label'] ?? ''),
                'image_path' => $row['image_path'] ?? null,
                'sold_quantity' => (string) ($row['sold_quantity'] ?? ''),
                'unit_price' => (string) ($row['unit_price'] ?? ''),
                'return_quantity' => (string) ($row['return_quantity'] ?? ''),
                'return_amount' => (string) ($row['return_amount'] ?? ''),
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function adjustStock(array $rows, int $direction): void
    {
        foreach ($rows as $row) {
            $productId = (int) ($row['product_id'] ?? 0);
            $qty = $this->saleService->numeric((string) ($row['return_quantity'] ?? '0'));

            if ($productId <= 0 || $qty <= 0) {
                continue;
            }

            if ($direction > 0) {
                Product::query()->whereKey($productId)->increment('stock_qty', $qty);
            } else {
                Product::query()->whereKey($productId)->decrement('stock_qty', $qty);
            }
        }
    }
}
