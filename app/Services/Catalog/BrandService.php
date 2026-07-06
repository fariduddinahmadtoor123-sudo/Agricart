<?php

namespace App\Services\Catalog;

use App\Models\Brand;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BrandService
{
    public function create(array $data): Brand
    {
        $validated = $this->validate($data);

        if (! filled($validated['code'] ?? null)) {
            $validated['code'] = $this->nextCode();
        }

        if (! filled($validated['slug'] ?? null) && filled($validated['name_en'])) {
            $validated['slug'] = $this->uniqueSlug($this->slugFromName($validated['name_en']));
        }

        return Brand::query()->create($validated);
    }

    public function update(Brand $brand, array $data): Brand
    {
        $validated = $this->validate($data, $brand->id);

        if (empty($validated['image_path']) && filled($brand->image_path)) {
            $validated['image_path'] = $brand->image_path;
        }

        if (! filled($validated['code'] ?? null)) {
            $validated['code'] = $brand->code;
        }

        $brand->update($validated);

        return $brand->fresh();
    }

    public function delete(Brand $brand): void
    {
        $brand->delete();
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
            $slug = $original . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function nextCode(): string
    {
        $next = (int) Brand::query()->max('id') + 1;

        do {
            $code = 'BRD-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            $next++;
        } while (Brand::query()->where('code', $code)->exists());

        return $code;
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
            'image_path' => [$exceptId ? 'nullable' : 'required', 'string', 'max:500'],
            'description_en' => ['nullable', 'string'],
            'description_ur' => ['nullable', 'string'],
            'website_url' => ['nullable', 'string', 'max:500'],
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
}
