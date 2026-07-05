<?php

namespace App\Services\Catalog;

use App\Models\Category;
use Illuminate\Support\Arr;
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
        $validated = $this->validate($data);

        return DB::transaction(function () use ($validated, $displayCategoryIds): Category {
            $category = Category::query()->create($validated);
            $this->syncDisplayCategories($category, $displayCategoryIds);

            return $category->fresh(['displayInCategories']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $displayCategoryIds
     */
    public function update(Category $category, array $data, array $displayCategoryIds = []): Category
    {
        $validated = $this->validate($data, $category->id);

        if (empty($validated['image_path']) && filled($category->image_path)) {
            $validated['image_path'] = $category->image_path;
        }

        return DB::transaction(function () use ($category, $validated, $displayCategoryIds): Category {
            $category->update($validated);
            $this->syncDisplayCategories($category, $displayCategoryIds);

            return $category->fresh(['displayInCategories']);
        });
    }

    public function delete(Category $category): void
    {
        $category->delete();
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
            $slug = $original . '-' . $counter;
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
                'max:500',
                Rule::unique('categories', 'slug')->ignore($exceptId),
            ],
            'code' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:active,inactive,draft'],
            'name_en' => ['required', 'string'],
            'name_ur' => ['required', 'string'],
            'image_path' => [$exceptId ? 'nullable' : 'required', 'string', 'max:500'],
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
            'visual_mapping' => ['nullable', 'array'],
            'faqs' => ['nullable', 'array'],
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
        return Category::query()
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->where('slug', $slug)
            ->exists();
    }
}
