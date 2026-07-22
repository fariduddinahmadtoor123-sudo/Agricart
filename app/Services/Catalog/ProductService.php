<?php

namespace App\Services\Catalog;

use App\Models\CatalogAttribute;
use App\Models\CatalogControl;
use App\Models\Category;
use App\Models\DeliveryMethod;
use App\Models\OnlineSaleOrderItem;
use App\Models\PackingUnit;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\PurchasePlanningItem;
use App\Models\PurchaseQuotationItem;
use App\Models\ReorderQueueItem;
use App\Models\SaleItem;
use App\Models\SaleQuotationItem;
use App\Models\SaleReturnItem;
use App\Services\Media\MediaLibraryService;
use App\Services\Identifiers\DocumentCounterService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductService
{
    public function __construct(
        private readonly ProductLabelService $labelService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $displayCategoryIds
     * @param  list<array{attribute_id: int, value: string}>  $attributes
     * @param  list<int>  $controlIds
     * @param  list<int>  $deliveryMethodIds
     */
    public function create(
        array $data,
        array $displayCategoryIds = [],
        array $attributes = [],
        array $controlIds = [],
        array $deliveryMethodIds = [],
    ): Product {
        $validated = $this->validate($data);
        $this->assertRequiredCatalogLinks($attributes, $controlIds, $deliveryMethodIds);

        return DB::transaction(function () use ($validated, $displayCategoryIds, $attributes, $controlIds, $deliveryMethodIds): Product {
            if (! filled($validated['code'] ?? null)) {
                $validated['code'] = $this->nextCode();
            }

            if (! filled($validated['slug'] ?? null) && filled($validated['name_en'])) {
                $validated['slug'] = $this->uniqueSlug($this->slugFromName($validated['name_en']));
            }

            $category = isset($validated['category_id'])
                ? Category::query()->find($validated['category_id'])
                : null;

            if ($category !== null) {
                $mapCode = $this->labelService->resolveVisualMapCode($category);
                $assignment = $this->labelService->nextLabelAssignment($mapCode);
                $validated['category_visual_map_code'] = $mapCode;
                $validated['label_sequence'] = $assignment['label_sequence'];
                $validated['label_code'] = $assignment['label_code'];
            }

            $product = Product::query()->create($validated);
            $this->syncDisplayCategories($product, $displayCategoryIds);
            $this->syncAttributes($product, $attributes);
            $this->syncControls($product, $controlIds);
            $this->syncDeliveryMethods($product, $deliveryMethodIds);
            $this->labelService->generateForProduct($product->fresh([
                'category',
                'brand',
                'unit',
                'packUnit',
                'attributes',
                'controls',
                'deliveryMethods',
            ]));

            CatalogCache::forgetOverviewStats();

            return $product->fresh([
                'category',
                'brand',
                'unit',
                'packUnit',
                'attributes',
                'controls',
                'deliveryMethods',
                'displayCategories',
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $displayCategoryIds
     * @param  list<array{attribute_id: int, value: string}>  $attributes
     * @param  list<int>  $controlIds
     * @param  list<int>  $deliveryMethodIds
     */
    public function update(
        Product $product,
        array $data,
        array $displayCategoryIds = [],
        array $attributes = [],
        array $controlIds = [],
        array $deliveryMethodIds = [],
    ): Product {
        $validated = $this->validate($data, $product->id);
        $this->assertRequiredCatalogLinks($attributes, $controlIds, $deliveryMethodIds);

        if (empty($validated['image_path']) && filled($product->image_path)) {
            $validated['image_path'] = $product->image_path;
        }

        return DB::transaction(function () use ($product, $validated, $displayCategoryIds, $attributes, $controlIds, $deliveryMethodIds): Product {
            $categoryId = $validated['category_id'] ?? $product->category_id;
            $categoryChanged = (int) $categoryId !== (int) $product->category_id;

            if ($categoryChanged && $categoryId) {
                $category = Category::query()->find($categoryId);

                if ($category !== null) {
                    $mapCode = $this->labelService->resolveVisualMapCode($category);
                    $assignment = $this->labelService->nextLabelAssignment($mapCode);
                    $validated['category_visual_map_code'] = $mapCode;
                    $validated['label_sequence'] = $assignment['label_sequence'];
                    $validated['label_code'] = $assignment['label_code'];
                }
            }

            $product->update($validated);
            $this->syncDisplayCategories($product, $displayCategoryIds);
            $this->syncAttributes($product, $attributes);
            $this->syncControls($product, $controlIds);
            $this->syncDeliveryMethods($product, $deliveryMethodIds);
            $this->labelService->generateForProduct($product->fresh([
                'category',
                'brand',
                'unit',
                'packUnit',
                'attributes',
                'controls',
                'deliveryMethods',
            ]));

            CatalogCache::forgetOverviewStats();

            return $product->fresh([
                'category',
                'brand',
                'unit',
                'packUnit',
                'attributes',
                'controls',
                'deliveryMethods',
                'displayCategories',
            ]);
        });
    }

    public function delete(Product $product): void
    {
        $this->assertDeletable($product);

        $mediaPaths = array_values(array_filter(array_merge(
            [$product->image_path],
            $product->gallery_images ?? [],
        )));

        $product->delete();

        foreach (array_unique($mediaPaths) as $path) {
            app(MediaLibraryService::class)->deleteFileIfUnused($path);
        }

        CatalogCache::forgetOverviewStats();
    }

    public function assertDeletable(Product $product): void
    {
        $referenced = SaleItem::query()->where('product_id', $product->id)->exists()
            || SaleQuotationItem::query()->where('product_id', $product->id)->exists()
            || SaleReturnItem::query()->where('product_id', $product->id)->exists()
            || OnlineSaleOrderItem::query()->where('product_id', $product->id)->exists()
            || PurchaseItem::query()->where('product_id', $product->id)->exists()
            || PurchaseQuotationItem::query()->where('product_id', $product->id)->exists()
            || PurchasePlanningItem::query()->where('product_id', $product->id)->exists()
            || ReorderQueueItem::query()->where('product_id', $product->id)->exists();

        if ($referenced) {
            throw ValidationException::withMessages([
                'product' => [__('agricart.products.messages.delete_blocked')],
            ]);
        }
    }

    public function slugFromName(string $name): string
    {
        return Str::slug($name);
    }

    public function uniqueSlug(string $baseSlug, ?int $exceptId = null): string
    {
        $slug = Str::slug($baseSlug) ?: 'product';
        $original = $slug;
        $counter = 1;

        while ($this->slugExists($slug, $exceptId)) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    public function nextCode(): string
    {
        return app(DocumentCounterService::class)->next(
            'products.code',
            'PRD-',
            6,
            fn (): int => DocumentCounterService::maximumSuffix('products', 'code', 'PRD-'),
        );
    }

    /**
     * @param  list<int>  $displayCategoryIds
     */
    public function syncDisplayCategories(Product $product, array $displayCategoryIds): void
    {
        $ids = collect($displayCategoryIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0 && $id !== (int) $product->category_id)
            ->unique()
            ->values()
            ->all();

        $product->displayCategories()->sync($ids);
    }

    /**
     * @param  list<array{attribute_id: int, value: string}>  $attributes
     * @param  list<int>  $controlIds
     * @param  list<int>  $deliveryMethodIds
     */
    private function assertRequiredCatalogLinks(array $attributes, array $controlIds, array $deliveryMethodIds): void
    {
        $attributeCount = collect($attributes)
            ->filter(function (array $row): bool {
                return (int) ($row['attribute_id'] ?? 0) > 0
                    && filled(trim((string) ($row['value'] ?? '')));
            })
            ->count();

        if ($attributeCount < 1) {
            throw ValidationException::withMessages([
                'attributes' => [__('agricart.products.messages.attribute_required')],
            ]);
        }

        $controlCount = collect($controlIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->count();

        if ($controlCount < 1) {
            throw ValidationException::withMessages([
                'controls' => [__('agricart.products.messages.control_required')],
            ]);
        }

        $deliveryCount = collect($deliveryMethodIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->count();

        if ($deliveryCount < 1) {
            throw ValidationException::withMessages([
                'delivery_methods' => [__('agricart.products.messages.delivery_required')],
            ]);
        }
    }

    /**
     * @param  list<array{attribute_id: int, value: string}>  $attributes
     */
    public function syncAttributes(Product $product, array $attributes): void
    {
        $sync = [];
        $existingIds = $product->attributes()->pluck('attributes.id')->map(fn ($id): int => (int) $id)->all();

        foreach ($attributes as $row) {
            $attributeId = (int) ($row['attribute_id'] ?? 0);
            $value = trim((string) ($row['value'] ?? ''));

            if ($attributeId <= 0 || $value === '') {
                continue;
            }

            if (mb_strlen($value) > 255) {
                throw ValidationException::withMessages([
                    'attributes' => ['Attribute values may not be greater than 255 characters.'],
                ]);
            }

            $attribute = CatalogAttribute::query()->find($attributeId);

            if ($attribute === null) {
                throw ValidationException::withMessages([
                    'attributes' => ['The selected attribute no longer exists.'],
                ]);
            }

            if ($attribute->status !== 'active' && ! in_array($attributeId, $existingIds, true)) {
                throw ValidationException::withMessages([
                    'attributes' => ['The selected attribute is inactive.'],
                ]);
            }

            $sync[$attributeId] = ['value' => $value];
        }

        $product->attributes()->sync($sync);
    }

    /**
     * @param  list<int>  $controlIds
     */
    public function syncControls(Product $product, array $controlIds): void
    {
        $existingIds = $product->controls()->pluck('controls.id')->map(fn ($id): int => (int) $id)->all();
        $ids = collect($controlIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        foreach ($ids as $controlId) {
            $control = CatalogControl::query()->find($controlId);

            if ($control === null) {
                throw ValidationException::withMessages([
                    'controls' => ['The selected control no longer exists.'],
                ]);
            }

            if ($control->status !== 'active' && ! in_array($controlId, $existingIds, true)) {
                throw ValidationException::withMessages([
                    'controls' => ['The selected control is inactive.'],
                ]);
            }
        }

        $product->controls()->sync($ids);
    }

    /**
     * @param  list<int>  $deliveryMethodIds
     */
    public function syncDeliveryMethods(Product $product, array $deliveryMethodIds): void
    {
        $existingIds = $product->deliveryMethods()->pluck('delivery_methods.id')->map(fn ($id): int => (int) $id)->all();
        $ids = collect($deliveryMethodIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        foreach ($ids as $methodId) {
            $method = DeliveryMethod::query()->find($methodId);

            if ($method === null) {
                throw ValidationException::withMessages([
                    'delivery_methods' => [__('agricart.products.messages.delivery_missing')],
                ]);
            }

            if ($method->status !== DeliveryMethod::STATUS_ACTIVE && ! in_array($methodId, $existingIds, true)) {
                throw ValidationException::withMessages([
                    'delivery_methods' => [__('agricart.products.messages.delivery_inactive')],
                ]);
            }
        }

        $product->deliveryMethods()->sync($ids);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validate(array $data, ?int $exceptId = null): array
    {
        $rules = [
            'code' => ['nullable', 'string', 'max:32', Rule::unique('products', 'code')->ignore($exceptId)],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->ignore($exceptId),
            ],
            'status' => ['nullable', 'string', 'in:active,inactive,draft'],
            'name_en' => ['required', 'string', 'max:255'],
            'name_ur' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'image_path' => ['nullable', 'string', 'max:500'],
            'gallery_images' => ['nullable', 'array'],
            'gallery_images.*' => ['string', 'max:500'],
            'sku' => ['nullable', 'string', 'max:128'],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'unit_qty' => ['required', 'string', 'max:64'],
            'pack_unit_id' => ['required', 'integer', 'exists:packing_units,id'],
            'pack_qty' => ['nullable', 'string', 'max:64'],
            'required_qty' => ['nullable', 'string', 'max:64'],
            'alert_qty' => ['nullable', 'string', 'max:64'],
            'retail_price' => ['nullable', 'string', 'max:64'],
            'wholesale_price' => ['nullable', 'string', 'max:64'],
            'super_wholesale_price' => ['nullable', 'string', 'max:64'],
            'distributor_price' => ['nullable', 'string', 'max:64'],
            'price_tier_quantities' => ['nullable', 'array'],
            'price_tier_quantities.*' => ['integer', 'min:1', 'max:9999999'],
            'short_description_en' => ['nullable', 'string'],
            'short_description_ur' => ['nullable', 'string'],
            'full_description_en' => ['nullable', 'string'],
            'full_description_ur' => ['nullable', 'string'],
            'usage_guidelines_en' => ['nullable', 'string'],
            'usage_guidelines_ur' => ['nullable', 'string'],
            'benefits_en' => ['nullable', 'string'],
            'benefits_ur' => ['nullable', 'string'],
            'warnings_en' => ['nullable', 'string'],
            'warnings_ur' => ['nullable', 'string'],
            'buying_guide_en' => ['nullable', 'string'],
            'buying_guide_ur' => ['nullable', 'string'],
            'seo_title_en' => ['nullable', 'string'],
            'seo_title_ur' => ['nullable', 'string'],
            'seo_description_en' => ['nullable', 'string'],
            'seo_description_ur' => ['nullable', 'string'],
            'seo_keywords_en' => ['nullable', 'string'],
            'seo_keywords_ur' => ['nullable', 'string'],
            'seo_focus_keyword_en' => ['nullable', 'string'],
            'seo_focus_keyword_ur' => ['nullable', 'string'],
            'search_terms' => ['nullable', 'array'],
            'hs_code' => ['nullable', 'string', 'max:128'],
            'customs_notes_en' => ['nullable', 'string'],
            'customs_notes_ur' => ['nullable', 'string'],
            'import_notes_en' => ['nullable', 'string'],
            'import_notes_ur' => ['nullable', 'string'],
            'export_notes_en' => ['nullable', 'string'],
            'export_notes_ur' => ['nullable', 'string'],
            'label_code' => ['nullable', 'string', 'max:64', Rule::unique('products', 'label_code')->ignore($exceptId)],
            'category_visual_map_code' => ['nullable', 'string', 'max:64'],
            'label_sequence' => ['nullable', 'integer', 'min:1'],
            'label_qr' => ['nullable', 'array'],
            'label_barcode' => ['nullable', 'array'],
            'ai_status' => ['nullable', 'string', 'in:none,pending,enriched,failed,manual'],
            'ai_generated_at' => ['nullable', 'date'],
            'ai_version' => ['nullable', 'string', 'max:64'],
        ];

        $validator = validator($data, $rules);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $validated = Arr::only($validator->validated(), array_keys($rules));

        if (filled($validated['pack_unit_id'] ?? null)) {
            $packingUnitId = (int) $validated['pack_unit_id'];
            $unchangedInactiveAssignment = $exceptId !== null
                && (int) Product::query()->whereKey($exceptId)->value('pack_unit_id') === $packingUnitId;

            if (! $unchangedInactiveAssignment
                && ! PackingUnit::query()->whereKey($packingUnitId)->where('status', 'active')->exists()) {
                throw ValidationException::withMessages([
                    'pack_unit_id' => ['The selected packing unit is inactive.'],
                ]);
            }
        }

        $validated['status'] = $validated['status'] ?? 'active';
        $validated['ai_status'] = $validated['ai_status'] ?? 'none';

        if ($exceptId && empty($validated['image_path'])) {
            unset($validated['image_path']);
        }

        return $validated;
    }

    private function slugExists(string $slug, ?int $exceptId): bool
    {
        return Product::query()
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->where('slug', $slug)
            ->exists();
    }
}
