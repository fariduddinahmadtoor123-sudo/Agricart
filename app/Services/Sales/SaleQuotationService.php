<?php

namespace App\Services\Sales;

use App\Models\SaleQuotation;
use App\Models\SaleQuotationItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaleQuotationService
{
  /** @var list<string> */
    public const LOCALES = ['en', 'ur'];

    public function __construct(
        private readonly SaleService $saleService,
    ) {}

    public function normalizeLocale(?string $locale): string
    {
        return in_array($locale, self::LOCALES, true) ? $locale : 'en';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SaleQuotation
    {
        $validated = $this->validate($data);
        $rows = $validated['rows'];
        unset($validated['rows']);

        return DB::transaction(function () use ($validated, $rows): SaleQuotation {
            if (! filled($validated['quotation_number'] ?? null)) {
                $validated['quotation_number'] = $this->nextQuotationNumber();
            }

            $validated['prepared_by_id'] = $validated['prepared_by_id'] ?? auth()->id();

            $quotation = SaleQuotation::query()->create($validated);
            $this->syncItems($quotation, $rows);

            return $quotation->fresh(['items', 'customer', 'preparedBy']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SaleQuotation $quotation, array $data): SaleQuotation
    {
        $validated = $this->validate($data, $quotation->id);
        $rows = $validated['rows'];
        unset($validated['rows']);

        return DB::transaction(function () use ($quotation, $validated, $rows): SaleQuotation {
            $quotation->update($validated);
            $this->syncItems($quotation, $rows);

            return $quotation->fresh(['items', 'customer', 'preparedBy']);
        });
    }

    public function delete(SaleQuotation $quotation): void
    {
        $quotation->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function toFormState(SaleQuotation $quotation): array
    {
        $quotation->loadMissing(['items', 'preparedBy']);

        $rows = [];

        foreach ($quotation->items as $item) {
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

        return [
            'quotation_number' => (string) $quotation->quotation_number,
            'customer_id' => $quotation->customer_id,
            'prepared_by_id' => $quotation->prepared_by_id,
            'quotation_date' => $quotation->quotation_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'locale' => $quotation->displayLocale(),
            'discount_type' => (string) ($quotation->discount_type ?? ''),
            'discount_value' => $quotation->discount_value !== null ? (string) $quotation->discount_value : '0',
            'subtotal' => $this->saleService->formatMoney((float) $quotation->subtotal),
            'discount_amount' => $this->saleService->formatMoney((float) $quotation->discount_amount),
            'quotation_amount' => $this->saleService->formatMoney((float) $quotation->quotation_amount),
            'print_controls' => (bool) $quotation->print_controls,
            'notes' => (string) ($quotation->notes ?? ''),
            'rows' => $rows,
        ];
    }

    public function nextQuotationNumber(): string
    {
        $next = (int) SaleQuotation::query()->max('id') + 1;

        do {
            $number = 'SQ-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            $next++;
        } while (SaleQuotation::query()->where('quotation_number', $number)->exists());

        return $number;
    }

    /**
     * @return array<int, array{id: int, label: string, image_url: ?string}>
     */
    public function searchProducts(string $term, string $locale = 'en', int $limit = 20): array
    {
        return $this->saleService->searchProducts($term, $locale, $limit);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data, ?int $ignoreId = null): array
    {
        $uniqueRule = Rule::unique('sale_quotations', 'quotation_number');

        if ($ignoreId !== null) {
            $uniqueRule->ignore($ignoreId);
        }

        $validated = validator($data, [
            'quotation_number' => ['nullable', 'string', 'max:32', $uniqueRule],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'quotation_date' => ['required', 'date'],
            'locale' => ['required', Rule::in(self::LOCALES)],
            'discount_type' => ['nullable', Rule::in([...SaleService::DISCOUNT_TYPES, ''])],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
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

        $rows = $this->saleService->recalculateRows($validated['rows']);
        $rows = array_values(array_filter(
            $rows,
            fn (array $row): bool => (int) ($row['product_id'] ?? 0) > 0,
        ));

        if ($rows === []) {
            throw ValidationException::withMessages([
                'rows' => [__('sale_quotations.validation.products_required')],
            ]);
        }

        $subtotal = $this->saleService->subtotalFromRows($rows);
        $discountAmount = $this->saleService->discountAmount(
            $subtotal,
            $validated['discount_type'] ?? null,
            isset($validated['discount_value']) ? (string) $validated['discount_value'] : null,
        );
        $total = max(0, round($subtotal - $discountAmount, 2));

        $validated['subtotal'] = $subtotal;
        $validated['discount_amount'] = $discountAmount;
        $validated['quotation_amount'] = $total;
        $validated['print_controls'] = (bool) ($validated['print_controls'] ?? false);
        $validated['locale'] = $this->normalizeLocale($validated['locale'] ?? 'en');
        $validated['rows'] = $rows;

        return $validated;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function syncItems(SaleQuotation $quotation, array $rows): void
    {
        $quotation->items()->delete();

        foreach ($rows as $index => $row) {
            SaleQuotationItem::query()->create([
                'sale_quotation_id' => $quotation->id,
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
}
