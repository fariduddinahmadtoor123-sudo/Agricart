<?php

namespace App\Services\Catalog;

use App\Models\Category;
use App\Models\Product;
use App\Services\Media\MediaLibraryService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $displayCategoryIds
     */
    public function create(array $data, array $displayCategoryIds = []): Category
    {
        return Cache::lock('catalog-category-write', 30)->block(
            10,
            function () use ($data, $displayCategoryIds): Category {
                if (blank($data['slug'] ?? null) && filled($data['name_en'] ?? null)) {
                    $data['slug'] = $this->uniqueSlug($this->slugFromName((string) $data['name_en']));
                }

                $validated = $this->validate($data);
                $mapping = is_array($validated['visual_mapping'] ?? null)
                    ? $validated['visual_mapping']
                    : [];
                $mapping['map_code'] = app(CategoryVisualMappingService::class)
                    ->generateMapCode(isset($validated['parent_id']) ? (int) $validated['parent_id'] : null);
                $validated['visual_mapping'] = $mapping;
                $validated['visual_map_code'] = $mapping['map_code'];

                return DB::transaction(function () use ($validated, $displayCategoryIds): Category {
                    $category = Category::query()->create($validated);
                    $this->syncDisplayCategories($category, $displayCategoryIds);

                    CatalogCache::forgetOverviewStats();

                    return $category->fresh(['displayInCategories']);
                });
            },
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $displayCategoryIds
     */
    public function update(Category $category, array $data, array $displayCategoryIds = []): Category
    {
        return Cache::lock('catalog-category-write', 30)->block(
            10,
            function () use ($category, $data, $displayCategoryIds): Category {
                $category->refresh();
                $requestedParentId = array_key_exists('parent_id', $data)
                    ? (isset($data['parent_id']) ? (int) $data['parent_id'] : null)
                    : ($category->parent_id !== null ? (int) $category->parent_id : null);
                $currentParentId = $category->parent_id !== null ? (int) $category->parent_id : null;
                $parentChanged = $requestedParentId !== $currentParentId;

                $validated = $this->validate($data, $category->id);

                if (empty($validated['image_path']) && filled($category->image_path)) {
                    $validated['image_path'] = $category->image_path;
                }

                $mapping = is_array($validated['visual_mapping'] ?? null)
                    ? $validated['visual_mapping']
                    : [];
                $existingMapCode = CategoryVisualMappingService::formatMapCode(
                    (string) ($category->visual_map_code ?: ($category->visual_mapping['map_code'] ?? '')),
                );
                $mapping['map_code'] = $parentChanged || $existingMapCode === ''
                    ? app(CategoryVisualMappingService::class)->generateMapCode($requestedParentId, $category->id)
                    : $existingMapCode;
                $validated['visual_mapping'] = $mapping;
                $validated['visual_map_code'] = $mapping['map_code'];

                return DB::transaction(function () use ($category, $validated, $displayCategoryIds, $parentChanged): Category {
                    $category->update($validated);
                    $categoryNamesChanged = $category->wasChanged(['name_en', 'name_ur']);
                    $this->syncDisplayCategories($category, $displayCategoryIds);

                    if ($parentChanged) {
                        // Keep printed product label_code stable; refresh map pointer + sticker payloads only.
                        Product::query()
                            ->where('category_id', $category->id)
                            ->update(['category_visual_map_code' => $category->visual_map_code]);
                        $this->refreshProductLabelsForCategory($category->fresh());
                    } elseif ($categoryNamesChanged) {
                        $this->refreshProductLabelsForCategory($category);
                    }

                    CatalogCache::forgetOverviewStats();

                    return $category->fresh(['displayInCategories']);
                });
            },
        );
    }

    public function delete(Category $category): void
    {
        $imagePath = Cache::lock('catalog-category-write', 30)->block(
            10,
            function () use ($category): ?string {
                $category->refresh();

                if ($category->children()->exists()) {
                    throw ValidationException::withMessages([
                        'category' => ['Move or delete this category’s child categories first.'],
                    ]);
                }

                if (Product::query()->where('category_id', $category->id)->exists()) {
                    throw ValidationException::withMessages([
                        'category' => ['Move this category’s products first, then delete the category.'],
                    ]);
                }

                $path = $category->image_path;
                $category->delete();

                return $path;
            },
        );

        if (filled($imagePath)) {
            app(MediaLibraryService::class)->deleteFileIfUnused($imagePath);
        }

        CatalogCache::forgetOverviewStats();
    }

    public function parentWouldCreateCycle(int $categoryId, ?int $parentId): bool
    {
        if ($parentId === null) {
            return false;
        }

        $visited = [];
        $currentId = $parentId;

        while ($currentId !== null) {
            if ($currentId === $categoryId || isset($visited[$currentId])) {
                return true;
            }

            $visited[$currentId] = true;
            $currentId = Category::query()->whereKey($currentId)->value('parent_id');
            $currentId = $currentId !== null ? (int) $currentId : null;
        }

        return false;
    }

    /**
     * @param  list<int>  $displayCategoryIds
     */
    public function syncDisplayCategories(Category $category, array $displayCategoryIds): void
    {
        $ids = collect($displayCategoryIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0 && $id !== $category->id)
            ->unique()
            ->values()
            ->all();

        if ($ids !== []) {
            $existingIds = Category::query()
                ->whereIn('id', $ids)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            if (count($existingIds) !== count($ids)) {
                throw ValidationException::withMessages([
                    'display_category_ids' => ['One or more selected display categories no longer exist.'],
                ]);
            }
        }

        $category->displayInCategories()->sync($ids);
    }

    public function slugFromName(string $name): string
    {
        return Str::slug($name);
    }

    public function uniqueSlug(string $baseSlug, ?int $exceptId = null): string
    {
        $slug = Str::slug($baseSlug) ?: 'category';
        $original = $slug;
        $counter = 1;

        while ($this->slugExists($slug, $exceptId)) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validate(array $data, ?int $exceptId = null): array
    {
        $rules = [
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($exceptId),
            ],
            'code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories', 'code')->ignore($exceptId),
            ],
            'status' => ['nullable', 'string', 'in:active,inactive,draft'],
            'name_en' => ['required', 'string', 'max:255'],
            'name_ur' => ['required', 'string', 'max:255'],
            'image_path' => [$exceptId ? 'nullable' : 'required', 'string', 'max:255'],
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
            'seo_title_en' => ['nullable', 'string', 'max:255'],
            'seo_title_ur' => ['nullable', 'string', 'max:255'],
            'seo_description_en' => ['nullable', 'string'],
            'seo_description_ur' => ['nullable', 'string'],
            'seo_keywords_en' => ['nullable', 'string', 'max:500'],
            'seo_keywords_ur' => ['nullable', 'string', 'max:500'],
            'seo_focus_keyword_en' => ['nullable', 'string', 'max:255'],
            'seo_focus_keyword_ur' => ['nullable', 'string', 'max:255'],
            'search_terms' => ['nullable', 'array'],
            'hs_code' => ['nullable', 'string', 'max:128'],
            'customs_notes_en' => ['nullable', 'string'],
            'customs_notes_ur' => ['nullable', 'string'],
            'import_notes_en' => ['nullable', 'string'],
            'import_notes_ur' => ['nullable', 'string'],
            'export_notes_en' => ['nullable', 'string'],
            'export_notes_ur' => ['nullable', 'string'],
            'visual_mapping' => ['nullable', 'array'],
            'faqs' => ['nullable', 'array'],
            'faqs.*' => ['array'],
            'faqs.*.question_en' => ['nullable', 'string'],
            'faqs.*.question_ur' => ['nullable', 'string'],
            'faqs.*.answer_en' => ['nullable', 'string'],
            'faqs.*.answer_ur' => ['nullable', 'string'],
            'ai_status' => ['nullable', 'string', 'in:none,pending,enriched,failed,manual'],
        ];

        $validator = validator($data, $rules);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $validated = Arr::only($validator->validated(), array_keys($rules));

        $parentId = isset($validated['parent_id']) ? (int) $validated['parent_id'] : null;

        if ($exceptId && $this->parentWouldCreateCycle($exceptId, $parentId)) {
            throw ValidationException::withMessages([
                'parent_id' => ['A category cannot use itself or one of its descendants as its parent.'],
            ]);
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
        return Category::query()
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->where('slug', $slug)
            ->exists();
    }

    private function refreshProductLabelsForCategory(Category $category): void
    {
        Product::query()
            ->where('category_id', $category->id)
            ->with(['brand', 'unit', 'packUnit', 'attributes', 'controls', 'category'])
            ->chunkById(100, function ($products): void {
                foreach ($products as $product) {
                    app(ProductLabelService::class)->generateForProduct($product);
                }
            });
    }
}
