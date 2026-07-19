<?php

namespace App\Services\Catalog;

use App\Models\Brand;
use App\Models\Product;
use App\Services\Identifiers\DocumentCounterService;
use App\Services\Media\MediaLibraryService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BrandService
{
    public function create(array $data): Brand
    {
        return Cache::lock('catalog-brand-write', 30)->block(10, function () use ($data): Brand {
            $data['code'] = $this->nextCode();
            $slugBase = filled($data['slug'] ?? null)
                ? (string) $data['slug']
                : $this->slugFromName((string) ($data['name_en'] ?? 'brand'));
            $data['slug'] = $this->uniqueSlug($slugBase);
            $validated = $this->validate($data);

            return DB::transaction(function () use ($validated): Brand {
                $brand = Brand::query()->create($validated);
                CatalogCache::forgetOverviewStats();

                return $brand;
            });
        });
    }

    public function update(Brand $brand, array $data): Brand
    {
        return Cache::lock('catalog-brand-write', 30)->block(
            10,
            function () use ($brand, $data): Brand {
                $brand->refresh();
                $data['code'] = $brand->code;
                $validated = $this->validate($data, $brand->id);

                if (empty($validated['image_path']) && filled($brand->image_path)) {
                    $validated['image_path'] = $brand->image_path;
                }

                return DB::transaction(function () use ($brand, $validated): Brand {
                    $brand->update($validated);

                    if ($brand->wasChanged(['name_en', 'name_ur'])) {
                        $this->refreshProductLabelsForBrand($brand);
                    }

                    CatalogCache::forgetOverviewStats();

                    return $brand->fresh();
                });
            },
        );
    }

    public function delete(Brand $brand): void
    {
        $imagePath = Cache::lock('catalog-brand-write', 30)->block(
            10,
            function () use ($brand): ?string {
                $brand->refresh();

                if (Product::query()->where('brand_id', $brand->id)->exists()) {
                    throw ValidationException::withMessages([
                        'brand' => ['Move this brand’s products first, then delete the brand.'],
                    ]);
                }

                $path = $brand->image_path;
                $brand->delete();

                return $path;
            },
        );

        if (filled($imagePath)) {
            app(MediaLibraryService::class)->deleteFileIfUnused($imagePath);
        }

        CatalogCache::forgetOverviewStats();
    }

    public function slugFromName(string $name): string
    {
        return Str::slug($name);
    }

    public function uniqueSlug(string $baseSlug, ?int $exceptId = null): string
    {
        $slug = Str::slug($baseSlug) ?: 'brand';
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
            'brands.code', 'BRD-', 5,
            fn (): int => DocumentCounterService::maximumSuffix('brands', 'code', 'BRD-'),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validate(array $data, ?int $exceptId = null): array
    {
        $rules = [
            'code' => ['nullable', 'string', 'max:32', Rule::unique('brands', 'code')->ignore($exceptId)],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('brands', 'slug')->ignore($exceptId),
            ],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'name_en' => ['required', 'string', 'max:255'],
            'name_ur' => ['required', 'string', 'max:255'],
            'image_path' => [$exceptId ? 'nullable' : 'required', 'string', 'max:255'],
            'description_en' => ['nullable', 'string'],
            'description_ur' => ['nullable', 'string'],
            'website_url' => ['nullable', 'url:http,https', 'max:255'],
            'ai_status' => ['nullable', 'string', 'in:none,pending,enriched,failed,manual'],
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
        return Brand::query()
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->where('slug', $slug)
            ->exists();
    }

    private function refreshProductLabelsForBrand(Brand $brand): void
    {
        Product::query()
            ->where('brand_id', $brand->id)
            ->with(['brand', 'unit', 'packUnit', 'attributes', 'controls', 'category'])
            ->chunkById(100, function ($products): void {
                foreach ($products as $product) {
                    app(ProductLabelService::class)->generateForProduct($product);
                }
            });
    }
}
