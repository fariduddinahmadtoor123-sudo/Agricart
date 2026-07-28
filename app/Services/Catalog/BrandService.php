<?php

namespace App\Services\Catalog;

use App\Models\Brand;
use App\Models\Product;
use App\Services\Catalog\BrandImageWatermarkService;
use App\Services\Identifiers\DocumentCounterService;
use App\Support\Catalog\BrandAgricartReviewCriteria;
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

                if ($brand->status === 'inactive') {
                    $this->deactivateProductsForBrand($brand);
                }

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

                if (empty($validated['image_original_path']) && filled($brand->image_original_path)) {
                    $validated['image_original_path'] = $brand->image_original_path;
                }

                return DB::transaction(function () use ($brand, $validated): Brand {
                    $wasActive = $brand->status === 'active';
                    $brand->update($validated);

                    if ($brand->wasChanged(['name_en', 'name_ur'])) {
                        $this->refreshProductLabelsForBrand($brand);
                    }

                    if ($wasActive && $brand->status === 'inactive') {
                        $this->deactivateProductsForBrand($brand);
                    }

                    CatalogCache::forgetOverviewStats();

                    return $brand->fresh();
                });
            },
        );
    }

    public function delete(Brand $brand): void
    {
        $imagePath = null;
        $imageOriginalPath = null;

        Cache::lock('catalog-brand-write', 30)->block(
            10,
            function () use ($brand, &$imagePath, &$imageOriginalPath): void {
                $brand->refresh();

                if (Product::query()->where('brand_id', $brand->id)->exists()) {
                    throw ValidationException::withMessages([
                        'brand' => [__('agricart.brands.messages.delete_has_products')],
                    ]);
                }

                $imagePath = $brand->image_path;
                $imageOriginalPath = $brand->image_original_path;
                $brand->delete();
            },
        );

        app(BrandImageWatermarkService::class)->deletePair($imagePath, $imageOriginalPath);

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
            'inactive_reason_en' => ['nullable', 'string', 'max:2000'],
            'inactive_reason_ur' => ['nullable', 'string', 'max:2000'],
            'agricart_rating' => ['nullable', 'integer', 'min:0', 'max:'.BrandAgricartReviewCriteria::scoreMax()],
            'agricart_rating_criteria' => ['nullable', 'array'],
            'agricart_rating_reason_en' => ['nullable', 'string', 'max:20000'],
            'agricart_rating_reason_ur' => ['nullable', 'string', 'max:20000'],
            'agricart_rating_revision_en' => ['nullable', 'string', 'max:4000'],
            'agricart_rating_revision_ur' => ['nullable', 'string', 'max:4000'],
            'name_en' => ['required', 'string', 'max:255'],
            'name_ur' => ['required', 'string', 'max:255'],
            'image_path' => [$exceptId ? 'nullable' : 'required', 'string', 'max:255'],
            'image_original_path' => ['nullable', 'string', 'max:500'],
            'image_watermark_version' => ['nullable', 'integer', 'min:1'],
            'description_en' => ['nullable', 'string'],
            'description_ur' => ['nullable', 'string'],
            'website_url' => ['nullable', 'url:http,https', 'max:255'],
            'ai_status' => ['nullable', 'string', 'in:none,pending,enriched,failed,manual'],
        ];

        foreach (BrandAgricartReviewCriteria::keys() as $criterionKey) {
            $rules['agricart_rating_criteria.'.$criterionKey] = ['nullable', 'array'];
            $rules['agricart_rating_criteria.'.$criterionKey.'.rating'] = [
                'nullable',
                'integer',
                'min:'.BrandAgricartReviewCriteria::MIN_RATING,
                'max:'.BrandAgricartReviewCriteria::MAX_RATING,
            ];
            $rules['agricart_rating_criteria.'.$criterionKey.'.reason_en'] = ['nullable', 'string', 'max:2000'];
            $rules['agricart_rating_criteria.'.$criterionKey.'.reason_ur'] = ['nullable', 'string', 'max:2000'];
        }

        $status = (string) ($data['status'] ?? 'active');

        if ($status === 'inactive') {
            $rules['inactive_reason_en'] = ['required', 'string', 'max:2000'];
            $rules['inactive_reason_ur'] = ['required', 'string', 'max:2000'];
        }

        $criteria = BrandAgricartReviewCriteria::normalizeDetailed(
            is_array($data['agricart_rating_criteria'] ?? null) ? $data['agricart_rating_criteria'] : null,
        );
        $criteria = app(BrandAgricartReviewMessageService::class)->fillMissingReasons($criteria);
        $data['agricart_rating_criteria'] = $criteria;

        if (BrandAgricartReviewCriteria::hasAnyAnswer($criteria) && ! BrandAgricartReviewCriteria::isComplete($criteria)) {
            throw ValidationException::withMessages([
                'agricart_rating_criteria' => [__('agricart.brands.messages.review_criteria_incomplete')],
            ]);
        }

        $validator = validator($data, $rules);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $fieldKeys = [
            'code',
            'slug',
            'status',
            'inactive_reason_en',
            'inactive_reason_ur',
            'agricart_rating',
            'agricart_rating_criteria',
            'agricart_rating_reason_en',
            'agricart_rating_reason_ur',
            'agricart_rating_revision_en',
            'agricart_rating_revision_ur',
            'name_en',
            'name_ur',
            'image_path',
            'image_original_path',
            'image_watermark_version',
            'description_en',
            'description_ur',
            'website_url',
            'ai_status',
        ];

        $validated = Arr::only($validator->validated(), $fieldKeys);
        $validated['status'] = $validated['status'] ?? 'active';
        $validated['ai_status'] = $validated['ai_status'] ?? 'none';

        if ($validated['status'] === 'active') {
            $validated['inactive_reason_en'] = null;
            $validated['inactive_reason_ur'] = null;
        }

        $storedCriteria = BrandAgricartReviewCriteria::forStorage($criteria);
        $validated['agricart_rating_criteria'] = $storedCriteria;
        $validated['agricart_rating'] = BrandAgricartReviewCriteria::score($criteria);

        if ($validated['agricart_rating'] === null) {
            $validated['agricart_rating_reason_en'] = null;
            $validated['agricart_rating_reason_ur'] = null;
            $validated['agricart_rating_revision_en'] = null;
            $validated['agricart_rating_revision_ur'] = null;
        } else {
            $composed = BrandAgricartReviewCriteria::composeReview($criteria);
            $providedEn = trim((string) ($data['agricart_rating_reason_en'] ?? ''));
            $providedUr = trim((string) ($data['agricart_rating_reason_ur'] ?? ''));

            // Keep AI / manually provided polished review when present; otherwise use checklist template.
            $validated['agricart_rating_reason_en'] = $providedEn !== '' ? $providedEn : ($composed['en'] ?? null);
            $validated['agricart_rating_reason_ur'] = $providedUr !== '' ? $providedUr : ($composed['ur'] ?? null);
        }

        if ($exceptId && empty($validated['image_path'])) {
            unset($validated['image_path']);
        }

        if ($exceptId && empty($validated['image_original_path'])) {
            unset($validated['image_original_path']);
        }

        return $validated;
    }

    private function deactivateProductsForBrand(Brand $brand): void
    {
        Product::query()
            ->where('brand_id', $brand->id)
            ->where('status', '!=', 'inactive')
            ->update(['status' => 'inactive']);
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
