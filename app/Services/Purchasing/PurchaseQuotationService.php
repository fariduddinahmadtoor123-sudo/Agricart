<?php

namespace App\Services\Purchasing;

use App\Models\Product;
use App\Models\PurchaseQuotation;
use App\Models\PurchaseQuotationItem;
use App\Services\Identifiers\DocumentCounterService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseQuotationService
{
    /** @var list<string> */
    public const LOCALES = ['en', 'ur'];

    public function __construct(
        private readonly PurchaseService $purchaseService,
    ) {}

    public function create(array $data): PurchaseQuotation
    {
        $validated = $this->validate($data);

        return DB::transaction(function () use ($validated): PurchaseQuotation {
            $items = $validated['items'];
            unset($validated['items']);

            if (! filled($validated['quotation_number'] ?? null)) {
                $validated['quotation_number'] = $this->nextQuotationNumber();
            }

            $validated['created_by'] = $validated['created_by'] ?? auth()->id();

            $quotation = PurchaseQuotation::query()->create($validated);
            $this->syncItems($quotation, $items);

            return $quotation->fresh(['items', 'supplier', 'creator']);
        });
    }

    public function update(PurchaseQuotation $quotation, array $data): PurchaseQuotation
    {
        $validated = $this->validate($data, $quotation->id);
        $items = $validated['items'];
        unset($validated['items']);

        return DB::transaction(function () use ($quotation, $validated, $items): PurchaseQuotation {
            $quotation->update($validated);
            $this->syncItems($quotation, $items);

            return $quotation->fresh(['items', 'supplier', 'creator']);
        });
    }

    public function delete(PurchaseQuotation $quotation): void
    {
        $quotation->delete();
    }

    public function nextQuotationNumber(): string
    {
        return app(DocumentCounterService::class)->next(
            'purchase_quotations.quotation_number',
            'QUO-',
            5,
            fn (): int => DocumentCounterService::maximumSuffix('purchase_quotations', 'quotation_number', 'QUO-'),
        );
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
    public function rowFromProduct(Product $product): array
    {
        $purchaseRow = $this->purchaseService->rowFromProduct($product);

        return [
            'product_id' => $purchaseRow['product_id'],
            'product_name_en' => $purchaseRow['product_name_en'],
            'product_name_ur' => $purchaseRow['product_name_ur'],
            'image_path' => $purchaseRow['image_path'],
            'required_qty' => $purchaseRow['required_qty'],
            'previous_price' => $purchaseRow['previous_price'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toFormState(PurchaseQuotation $quotation): array
    {
        return [
            'quotation_number' => $quotation->quotation_number,
            'supplier_id' => $quotation->supplier_id,
            'locale' => $quotation->displayLocale(),
            'quotation_date' => $quotation->quotation_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'notes' => (string) ($quotation->notes ?? ''),
            'rows' => $quotation->items->map(fn (PurchaseQuotationItem $item): array => [
                'product_id' => $item->product_id,
                'product_name_en' => $item->product_name_en,
                'product_name_ur' => (string) ($item->product_name_ur ?? ''),
                'image_path' => $item->image_path,
                'required_qty' => (string) ($item->required_qty ?? ''),
                'previous_price' => (string) ($item->previous_price ?? ''),
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
            'quotation_number' => ['nullable', 'string', 'max:32', \Illuminate\Validation\Rule::unique('purchase_quotations', 'quotation_number')->ignore($exceptId)],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'locale' => ['required', 'string', 'in:' . implode(',', self::LOCALES)],
            'quotation_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.product_name_en' => ['required', 'string', 'max:255'],
            'items.*.product_name_ur' => ['nullable', 'string', 'max:255'],
            'items.*.image_path' => ['nullable', 'string', 'max:500'],
            'items.*.required_qty' => ['nullable', 'string', 'max:32'],
            'items.*.previous_price' => ['nullable', 'string', 'max:32'],
        ];

        $validator = validator($data, $rules, [
            'supplier_id.required' => __('purchase_quotations.validation.supplier_required'),
        ]);

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

            $normalized[] = [
                'product_id' => filled($item['product_id'] ?? null) ? (int) $item['product_id'] : null,
                'sort_order' => $index,
                'product_name_en' => trim((string) $item['product_name_en']),
                'product_name_ur' => trim((string) ($item['product_name_ur'] ?? '')),
                'image_path' => filled($item['image_path'] ?? null) ? (string) $item['image_path'] : null,
                'required_qty' => trim((string) ($item['required_qty'] ?? '')),
                'previous_price' => trim((string) ($item['previous_price'] ?? '')),
                'current_price' => null,
            ];
        }

        if ($normalized === []) {
            throw ValidationException::withMessages([
                'rows' => [__('purchase_quotations.validation.items_required')],
            ]);
        }

        return $normalized;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function syncItems(PurchaseQuotation $quotation, array $items): void
    {
        $quotation->items()->delete();

        foreach ($items as $item) {
            $quotation->items()->create($item);
        }
    }

    public function normalizeLocale(?string $locale): string
    {
        return in_array($locale, self::LOCALES, true) ? $locale : 'en';
    }

    /**
     * @param  array{product_name_en?: string, product_name_ur?: string|null}  $row
     */
    public function rowDisplayName(array $row, string $locale): string
    {
        $locale = $this->normalizeLocale($locale);

        if ($locale === 'ur' && filled($row['product_name_ur'] ?? null)) {
            return (string) $row['product_name_ur'];
        }

        return (string) ($row['product_name_en'] ?? '');
    }
}
