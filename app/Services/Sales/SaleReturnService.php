<?php

namespace App\Services\Sales;

use App\Models\BankAccountTransaction;
use App\Models\CashBookTransaction;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Services\Finance\BankAccountService;
use App\Services\Identifiers\DocumentCounterService;
use App\Services\Finance\CashBookService;
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
            'unit_cost_snapshot' => (string) ($item->unit_cost_snapshot ?? '0'),
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
        return DB::transaction(function () use ($data): SaleReturn {
            $validated = $this->validate($data);
            $rows = $validated['rows'];
            unset($validated['rows']);

            if (! filled($validated['return_number'] ?? null)) {
                $validated['return_number'] = $this->nextReturnNumber();
            }

            $validated['processed_by_id'] = $validated['processed_by_id'] ?? auth()->id();

            $return = SaleReturn::query()->create($validated);
            $this->syncItems($return, $rows);
            $this->adjustStock($rows, 1);

            $return = $return->fresh(['items', 'customer', 'sale', 'processedBy']);
            app(CashBookService::class)->recordSaleReturnRefundIfApplicable($return);
            app(BankAccountService::class)->recordSaleReturnRefundIfApplicable($return);

            return $return;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SaleReturn $return, array $data): SaleReturn
    {
        $this->assertFinanciallyEditable($return);

        return DB::transaction(function () use ($return, $data): SaleReturn {
            $return = SaleReturn::query()->lockForUpdate()->findOrFail($return->id);
            $this->assertFinanciallyEditable($return);
            $validated = $this->validate($data, $return->id);
            $rows = $validated['rows'];
            unset($validated['rows']);
            $return->loadMissing('items');
            $oldRows = $return->items->map(fn (SaleReturnItem $item): array => [
                'product_id' => $item->product_id,
                'return_quantity' => (string) $item->return_quantity,
            ])->all();

            $this->adjustStock($oldRows, -1);

            $return->update($validated);
            $this->syncItems($return, $rows);
            $this->adjustStock($rows, 1);

            $return = $return->fresh(['items', 'customer', 'sale', 'processedBy']);
            app(CashBookService::class)->recordSaleReturnRefundIfApplicable($return);
            app(BankAccountService::class)->recordSaleReturnRefundIfApplicable($return);

            return $return;
        });
    }

    public function delete(SaleReturn $return): void
    {
        $this->assertFinanciallyEditable($return);

        DB::transaction(function () use ($return): void {
            $return = SaleReturn::query()->lockForUpdate()->findOrFail($return->id);
            $this->assertFinanciallyEditable($return);
            $return->loadMissing('items');
            $rows = $return->items->map(fn (SaleReturnItem $item): array => [
                'product_id' => $item->product_id,
                'return_quantity' => (string) $item->return_quantity,
            ])->all();

            $this->adjustStock($rows, -1);
            $return->delete();
        });
    }

    public function assertFinanciallyEditable(SaleReturn $return): void
    {
        $cashPosted = CashBookTransaction::query()->where('sale_return_id', $return->id)->exists();
        $bankPosted = BankAccountTransaction::query()->where('sale_return_id', $return->id)->exists();

        if ($cashPosted || $bankPosted) {
            throw ValidationException::withMessages([
                'return' => [__('sale_returns.validation.refund_posted_locked')],
            ]);
        }
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
                'unit_cost_snapshot' => (string) ($item->unit_cost_snapshot ?? '0'),
                'cogs_reversal_snapshot' => (string) ($item->cogs_reversal_snapshot ?? '0'),
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
        return app(DocumentCounterService::class)->next(
            'sale_returns.return_number',
            'RET-',
            5,
            fn (): int => DocumentCounterService::maximumSuffix('sale_returns', 'return_number', 'RET-'),
        );
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
            'bank_account_id' => [
                'nullable',
                'integer',
                'exists:bank_accounts,id',
                Rule::requiredIf(($data['payment_method'] ?? '') === 'bank_transfer'),
            ],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.sale_item_id' => ['required', 'integer'],
            'rows.*.product_id' => ['nullable', 'integer'],
            'rows.*.sold_quantity' => ['nullable'],
            'rows.*.return_quantity' => ['required', 'numeric', 'min:0'],
            'rows.*.return_amount' => ['nullable', 'string', 'max:32'],
            'rows.*.unit_price' => ['nullable'],
            'rows.*.product_name_en' => ['nullable', 'string', 'max:255'],
            'rows.*.product_name_ur' => ['nullable', 'string', 'max:255'],
            'rows.*.brand_name_en' => ['nullable', 'string', 'max:255'],
            'rows.*.brand_name_ur' => ['nullable', 'string', 'max:255'],
            'rows.*.unit_label' => ['nullable', 'string', 'max:64'],
            'rows.*.image_path' => ['nullable', 'string', 'max:500'],
        ])->validate();

        if ($validated['payment_method'] !== 'bank_transfer') {
            $validated['bank_account_id'] = null;
        }

        $sale = Sale::query()->lockForUpdate()->find((int) $validated['sale_id']);

        if ($sale === null || ! $sale->isCompleted()) {
            throw ValidationException::withMessages([
                'sale_id' => [__('sale_returns.validation.sale_not_found')],
            ]);
        }

        $requested = [];

        foreach ($validated['rows'] as $row) {
            $saleItemId = (int) $row['sale_item_id'];
            $quantity = (float) $row['return_quantity'];

            if ($quantity > 0) {
                $requested[$saleItemId] = ($requested[$saleItemId] ?? 0.0) + $quantity;
            }
        }

        if ($requested === []) {
            throw ValidationException::withMessages([
                'rows' => [__('sale_returns.validation.items_required')],
            ]);
        }

        $saleItems = SaleItem::query()
            ->where('sale_id', $sale->id)
            ->whereIn('id', array_keys($requested))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if ($saleItems->count() !== count($requested)) {
            throw ValidationException::withMessages([
                'rows' => [__('sale_returns.validation.sale_not_found')],
            ]);
        }

        SaleReturn::query()->where('sale_id', $sale->id)->orderBy('id')->lockForUpdate()->get(['id']);
        $existing = SaleReturnItem::query()
            ->whereIn('sale_item_id', array_keys($requested))
            ->when($ignoreReturnId !== null, fn ($query) => $query->where('sale_return_id', '!=', $ignoreReturnId))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->groupBy('sale_item_id');

        $rows = [];

        foreach ($requested as $saleItemId => $returnQty) {
            /** @var SaleItem $item */
            $item = $saleItems->get($saleItemId);
            $soldQty = $this->saleService->numeric((string) $item->quantity);
            $alreadyReturned = $existing->get($saleItemId, collect())
                ->sum(fn (SaleReturnItem $existingItem): float => $this->saleService->numeric((string) $existingItem->return_quantity));

            if ($returnQty > $soldQty + 0.0001 || $returnQty + $alreadyReturned > $soldQty + 0.0001) {
                throw ValidationException::withMessages([
                    'rows' => [__('sale_returns.validation.qty_exceeds_remaining')],
                ]);
            }

            $trusted = $this->rowFromSaleItem($item);
            $trusted['return_quantity'] = $this->saleService->formatQty($returnQty);
            $trusted['return_amount'] = $this->saleService->formatMoney(
                $returnQty * $this->saleService->numeric((string) $item->unit_price),
            );
            $rows[] = $trusted;
        }

        $subtotal = $this->refundSubtotalFromRows($rows);
        $paid = $this->saleService->normalizePaidAgainstTotal(
            $this->saleService->numeric((string) ($validated['amount_paid'] ?? '0')),
            $subtotal,
        );
        $total = $subtotal;

        $validated['customer_id'] = $sale->customer_id;
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
    private function syncItems(SaleReturn $return, array $rows): void
    {
        $return->items()->delete();

        foreach ($rows as $index => $row) {
            SaleReturnItem::query()->create([
                'sale_return_id' => $return->id,
                'sale_item_id' => isset($row['sale_item_id']) ? (int) $row['sale_item_id'] : null,
                'product_id' => filled($row['product_id'] ?? null) ? (int) $row['product_id'] : null,
                'sort_order' => $index,
                'product_name_en' => (string) $row['product_name_en'],
                'product_name_ur' => (string) ($row['product_name_ur'] ?? ''),
                'brand_name_en' => (string) ($row['brand_name_en'] ?? ''),
                'brand_name_ur' => (string) ($row['brand_name_ur'] ?? ''),
                'unit_label' => (string) ($row['unit_label'] ?? ''),
                'image_path' => $row['image_path'] ?? null,
                'sold_quantity' => (string) ($row['sold_quantity'] ?? ''),
                'unit_price' => (string) ($row['unit_price'] ?? ''),
                'unit_cost_snapshot' => (string) ($row['unit_cost_snapshot'] ?? '0'),
                'cogs_reversal_snapshot' => round(
                    $this->saleService->numeric((string) ($row['return_quantity'] ?? '0'))
                    * $this->saleService->numeric((string) ($row['unit_cost_snapshot'] ?? '0')),
                    4,
                ),
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
        $deltas = [];

        foreach ($rows as $row) {
            $productId = (int) ($row['product_id'] ?? 0);
            $qty = $this->saleService->numeric((string) ($row['return_quantity'] ?? '0'));

            if ($productId <= 0 || $qty <= 0) {
                continue;
            }

            $deltas[$productId] = ($deltas[$productId] ?? 0.0) + $qty;
        }

        ksort($deltas);

        foreach ($deltas as $productId => $qty) {
            Product::query()->lockForUpdate()->find($productId);

            if ($direction > 0) {
                Product::query()->whereKey($productId)->increment('stock_qty', $qty);
            } else {
                Product::query()->whereKey($productId)->decrement('stock_qty', $qty);
            }
        }
    }
}
