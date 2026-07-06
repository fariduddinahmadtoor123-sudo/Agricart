<?php

namespace App\Services\Catalog;

use App\Models\Category;
use App\Models\Product;
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
     */
    public function create(
        array $data,
        array $displayCategoryIds = [],
        array $attributes = [],
        array $controlIds = [],
    ): Product {
        $validated = $this->validate($data);

        return DB::transaction(function () use ($validated, $displayCategoryIds, $attributes, $controlIds): Product {
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
            $this->labelService->generateForProduct($product->fresh([
                'category',
                'brand',
                'unit',
                'packUnit',
                'attributes',
                'controls',
            ]));

            return $product->fresh([
                'category',
                'brand',
                'unit',
                'packUnit',
                'attributes',
                'controls',
                'displayCategories',
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $displayCategoryIds
     * @param  list<array{attribute_id: int, value: string}>  $attributes
     * @param  list<int>  $controlIds
     */
    public function update(
        Product $product,
        array $data,
        array $displayCategoryIds = [],
        array $attributes = [],
        array $controlIds = [],
    ): Product {
        $validated = $this->validate($data, $product->id);

        if (empty($validated['image_path']) && filled($product->image_path)) {
            $validated['image_path'] = $product->image_path;
        }

        return DB::transaction(function () use ($product, $validated, $displayCategoryIds, $attributes, $controlIds): Product {
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
            $this->labelService->generateForProduct($product->fresh([
                'category',
                'brand',
                'unit',
                'packUnit',
                'attributes',
                'controls',
            ]));

            return $product->fresh([
                'category',
                'brand',
                'unit',
                'packUnit',
                'attributes',
                'controls',
                'displayCategories',
            ]);
        });
    }

    public function delete(Product $product): void
    {
        $product->delete();
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
            $slug = $original . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function nextCode(): string
    {
        $next = (int) Product::query()->max('id') + 1;

        do {
            $code = 'PRD-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while (Product::query()->where('code', $code)->exists());

        return $code;
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
     */
    public function syncAttributes(Product $product, array $attributes): void
    {
        $sync = [];

        foreach ($attributes as $row) {
            $attributeId = (int) ($row['attribute_id'] ?? 0);
            $value = trim((string) ($row['value'] ?? ''));

            if ($attributeId <= 0 || $value === '') {
                continue;
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
        $ids = collect($controlIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        $product->controls()->sync($ids);
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
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'image_path' => ['nullable', 'string', 'max:500'],
            'gallery_images' => ['nullable', 'array'],
            'gallery_images.*' => ['string', 'max:500'],
            'sku' => ['nullable', 'string', 'max:128'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'unit_qty' => ['nullable', 'string', 'max:64'],
            'pack_unit_id' => ['nullable', 'integer', 'exists:packing_units,id'],
            'pack_qty' => ['nullable', 'string', 'max:64'],
            'required_qty' => ['nullable', 'string', 'max:64'],
            'alert_qty' => ['nullable', 'string', 'max:64'],
            'retail_price' => ['nullable', 'string', 'max:64'],
            'wholesale_price' => ['nullable', 'string', 'max:64'],
            'super_wholesale_price' => ['nullable', 'string', 'max:64'],
            'distributor_price' => ['nullable', 'string', 'max:64'],
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
