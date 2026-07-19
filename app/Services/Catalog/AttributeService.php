<?php

namespace App\Services\Catalog;

use App\Models\CatalogAttribute;
use App\Models\Product;
use App\Services\Identifiers\DocumentCounterService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AttributeService
{
    public function create(array $data): CatalogAttribute
    {
        return Cache::lock('catalog-attribute-write', 30)->block(10, function () use ($data): CatalogAttribute {
            $data['code'] = $this->nextCode();
            $data['slug'] = $this->uniqueSlug(
                $this->slugFromName((string) ($data['name_en'] ?? 'attribute')),
            );
            $validated = $this->validate($data);

            return DB::transaction(function () use ($validated): CatalogAttribute {
                $attribute = CatalogAttribute::query()->create($validated);
                CatalogCache::forgetOverviewStats();

                return $attribute;
            });
        });
    }

    public function update(CatalogAttribute $attribute, array $data): CatalogAttribute
    {
        return Cache::lock('catalog-attribute-write', 30)->block(10, function () use ($attribute, $data): CatalogAttribute {
            $attribute->refresh();
            $data['code'] = $attribute->code;
            $data['slug'] = $attribute->slug;
            $validated = $this->validate($data, $attribute->id);

            return DB::transaction(function () use ($attribute, $validated): CatalogAttribute {
                $attribute->update($validated);

                if ($attribute->wasChanged(['name_en', 'name_ur'])) {
                    $this->refreshProductLabelsForAttribute($attribute);
                }

                CatalogCache::forgetOverviewStats();

                return $attribute->fresh();
            });
        });
    }

    public function delete(CatalogAttribute $attribute): void
    {
        Cache::lock('catalog-attribute-write', 30)->block(10, function () use ($attribute): void {
            $attribute->refresh();

            if (DB::table('product_attribute')->where('attribute_id', $attribute->id)->exists()) {
                throw ValidationException::withMessages([
                    'attribute' => [__('agricart.attributes.messages.linked_delete')],
                ]);
            }

            $attribute->delete();
            CatalogCache::forgetOverviewStats();
        });
    }

    public function slugFromName(string $name): string
    {
        return Str::slug($name);
    }

    public function uniqueSlug(string $baseSlug, ?int $exceptId = null): string
    {
        $slug = Str::slug($baseSlug) ?: 'attribute';
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
        return app(DocumentCounterService::class)->next(
            'catalog_attributes.code', 'ATR-', 5,
            fn (): int => DocumentCounterService::maximumSuffix('attributes', 'code', 'ATR-'),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validate(array $data, ?int $exceptId = null): array
    {
        $rules = [
            'code' => ['nullable', 'string', 'max:32', Rule::unique('attributes', 'code')->ignore($exceptId)],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('attributes', 'slug')->ignore($exceptId),
            ],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'name_en' => ['required', 'string', 'max:128'],
            'name_ur' => ['required', 'string', 'max:128'],
            'ai_status' => ['nullable', 'string', 'in:none,pending,enriched,failed,manual'],
        ];

        $validator = validator($data, $rules);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $validated = Arr::only($validator->validated(), array_keys($rules));
        $validated['status'] = $validated['status'] ?? 'active';
        $validated['ai_status'] = $validated['ai_status'] ?? 'none';

        return $validated;
    }

    private function slugExists(string $slug, ?int $exceptId): bool
    {
        return CatalogAttribute::query()
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->where('slug', $slug)
            ->exists();
    }

    private function refreshProductLabelsForAttribute(CatalogAttribute $attribute): void
    {
        Product::query()
            ->whereHas('attributes', fn ($query) => $query->whereKey($attribute->id))
            ->with(['brand', 'unit', 'packUnit', 'attributes', 'controls', 'category'])
            ->chunkById(100, function ($products): void {
                foreach ($products as $product) {
                    app(ProductLabelService::class)->generateForProduct($product);
                }
            });
    }
}
