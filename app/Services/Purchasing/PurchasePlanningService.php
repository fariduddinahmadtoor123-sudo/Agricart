<?php

namespace App\Services\Purchasing;

use App\Models\Product;
use App\Models\PurchasePlanningItem;
use App\Models\PurchasePlanningSheet;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchasePlanningService
{
    public const PAPER_SIZES = ['a4', 'a5', 'a3'];

    /** @var list<string> */
    public const LOCALES = ['en', 'ur'];

    public function create(array $data): PurchasePlanningSheet
    {
        $validated = $this->validate($data);

        return DB::transaction(function () use ($validated): PurchasePlanningSheet {
            $items = $validated['items'];
            unset($validated['items']);

            if (! filled($validated['planning_number'] ?? null)) {
                $validated['planning_number'] = $this->nextPlanningNumber();
            }

            $validated['created_by'] = $validated['created_by'] ?? auth()->id();

            $sheet = PurchasePlanningSheet::query()->create($validated);
            $this->syncItems($sheet, $items);

            return $sheet->fresh(['items', 'creator']);
        });
    }

    public function update(PurchasePlanningSheet $sheet, array $data): PurchasePlanningSheet
    {
        $validated = $this->validate($data, $sheet->id);
        $items = $validated['items'];
        unset($validated['items']);

        return DB::transaction(function () use ($sheet, $validated, $items): PurchasePlanningSheet {
            $sheet->update($validated);
            $this->syncItems($sheet, $items);

            return $sheet->fresh(['items', 'creator']);
        });
    }

    public function delete(PurchasePlanningSheet $sheet): void
    {
        $sheet->delete();
    }

    public function nextPlanningNumber(): string
    {
        $next = (int) PurchasePlanningSheet::query()->max('id') + 1;

        do {
            $number = 'PLN-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            $next++;
        } while (PurchasePlanningSheet::query()->where('planning_number', $number)->exists());

        return $number;
    }

    /**
     * @return array<int, array{id: int, label: string, image_url: ?string}>
     */
    public function searchProducts(string $term, string $locale = 'en', int $limit = 20): array
    {
        $term = trim($term);

        if ($term === '') {
            return [];
        }

        $needle = '%' . $term . '%';

        $products = Product::query()
            ->where('status', 'active')
            ->where(function ($query) use ($term, $needle): void {
                $query->where('name_en', 'like', $needle)
                    ->orWhere('name_ur', 'like', $needle)
                    ->orWhere('code', 'like', $needle)
                    ->orWhere('sku', $term)
                    ->orWhere('label_code', $term)
                    ->orWhere('label_code', 'like', $needle);

                if (ctype_digit($term)) {
                    $query->orWhere('id', (int) $term);
                }
            })
            ->orderBy('name_en')
            ->limit($limit)
            ->get(['id', 'name_en', 'name_ur', 'image_path']);

        return $products->map(fn (Product $product): array => [
            'id' => $product->id,
            'label' => $this->productLabel($product, $locale),
            'image_url' => $product->image_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($product->image_path) : null,
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
        return [
            'product_id' => $product->id,
            'product_name_en' => $product->name_en,
            'product_name_ur' => $product->name_ur,
            'image_path' => $product->image_path,
            'stock_qty' => '0',
            'required_qty' => (string) ($product->required_qty ?? ''),
            'low_qty' => (string) ($product->alert_qty ?? ''),
            'purchase_qty' => '',
            'landing_price' => '',
            'sale_price' => (string) ($product->retail_price ?? ''),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toFormState(PurchasePlanningSheet $sheet): array
    {
        return [
            'planning_number' => $sheet->planning_number,
            'paper_size' => $sheet->paper_size,
            'locale' => $sheet->displayLocale(),
            'notes' => (string) ($sheet->notes ?? ''),
            'rows' => $sheet->items->map(fn (PurchasePlanningItem $item): array => [
                'product_id' => $item->product_id,
                'product_name_en' => $item->product_name_en,
                'product_name_ur' => (string) ($item->product_name_ur ?? ''),
                'image_path' => $item->image_path,
                'stock_qty' => (string) ($item->stock_qty ?? ''),
                'required_qty' => (string) ($item->required_qty ?? ''),
                'low_qty' => (string) ($item->low_qty ?? ''),
                'purchase_qty' => (string) ($item->purchase_qty ?? ''),
                'landing_price' => (string) ($item->landing_price ?? ''),
                'sale_price' => (string) ($item->sale_price ?? ''),
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
            'planning_number' => ['nullable', 'string', 'max:32', \Illuminate\Validation\Rule::unique('purchase_planning_sheets', 'planning_number')->ignore($exceptId)],
            'paper_size' => ['required', 'string', 'in:' . implode(',', self::PAPER_SIZES)],
            'locale' => ['required', 'string', 'in:' . implode(',', self::LOCALES)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.product_name_en' => ['required', 'string', 'max:255'],
            'items.*.product_name_ur' => ['nullable', 'string', 'max:255'],
            'items.*.image_path' => ['nullable', 'string', 'max:500'],
            'items.*.stock_qty' => ['nullable', 'string', 'max:32'],
            'items.*.required_qty' => ['nullable', 'string', 'max:32'],
            'items.*.low_qty' => ['nullable', 'string', 'max:32'],
            'items.*.purchase_qty' => ['nullable', 'string', 'max:32'],
            'items.*.landing_price' => ['nullable', 'string', 'max:32'],
            'items.*.sale_price' => ['nullable', 'string', 'max:32'],
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

            $normalized[] = [
                'product_id' => filled($item['product_id'] ?? null) ? (int) $item['product_id'] : null,
                'sort_order' => $index,
                'product_name_en' => trim((string) $item['product_name_en']),
                'product_name_ur' => trim((string) ($item['product_name_ur'] ?? '')),
                'image_path' => filled($item['image_path'] ?? null) ? (string) $item['image_path'] : null,
                'stock_qty' => trim((string) ($item['stock_qty'] ?? '')),
                'required_qty' => trim((string) ($item['required_qty'] ?? '')),
                'low_qty' => trim((string) ($item['low_qty'] ?? '')),
                'purchase_qty' => trim((string) ($item['purchase_qty'] ?? '')),
                'landing_price' => trim((string) ($item['landing_price'] ?? '')),
                'sale_price' => trim((string) ($item['sale_price'] ?? '')),
            ];
        }

        if ($normalized === []) {
            throw ValidationException::withMessages([
                'rows' => [__('purchase_planning.validation.items_required')],
            ]);
        }

        return $normalized;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function syncItems(PurchasePlanningSheet $sheet, array $items): void
    {
        $sheet->items()->delete();

        foreach ($items as $item) {
            $sheet->items()->create($item);
        }
    }

    private function productLabel(Product $product, ?string $locale = null): string
    {
        $locale = $this->normalizeLocale($locale ?? app()->getLocale());

        if ($locale === 'ur' && filled($product->name_ur)) {
            return (string) $product->name_ur;
        }

        return (string) $product->name_en;
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
