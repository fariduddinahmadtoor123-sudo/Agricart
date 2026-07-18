<?php

namespace App\Services\Catalog;

use App\Models\Unit;
use App\Models\Product;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UnitService
{
    public function create(array $data): Unit
    {
        return Cache::lock('catalog-unit-write', 30)->block(10, function () use ($data): Unit {
            $data['code'] = $this->nextCode();
            $data['slug'] = $this->uniqueSlug(
                filled($data['slug'] ?? null)
                    ? (string) $data['slug']
                    : $this->slugFromName((string) ($data['name_en'] ?? 'unit')),
            );
            $validated = $this->validate($data);

            return DB::transaction(function () use ($validated): Unit {
                $unit = Unit::query()->create($validated);
                CatalogCache::forgetOverviewStats();
                return $unit;
            });
        });
    }

    public function update(Unit $unit, array $data): Unit
    {
        return Cache::lock('catalog-unit-write', 30)->block(10, function () use ($unit, $data): Unit {
            $unit->refresh();
            $data['code'] = $unit->code;
            $validated = $this->validate($data, $unit->id);

            return DB::transaction(function () use ($unit, $validated): Unit {
                $unit->update($validated);

                if ($unit->wasChanged(['name_en', 'name_ur', 'abbreviation_en', 'abbreviation_ur'])) {
                    $this->refreshProductLabelsForUnit($unit);
                }

                CatalogCache::forgetOverviewStats();
                return $unit->fresh();
            });
        });
    }

    public function delete(Unit $unit): void
    {
        Cache::lock('catalog-unit-write', 30)->block(10, function () use ($unit): void {
            $unit->refresh();

            if (Product::query()->where('unit_id', $unit->id)->exists()) {
                throw ValidationException::withMessages([
                    'unit' => ['Move this unit’s products first, then delete the unit.'],
                ]);
            }

            $unit->delete();
            CatalogCache::forgetOverviewStats();
        });
    }

    public function slugFromName(string $name): string
    {
        return Str::slug($name);
    }

    public function uniqueSlug(string $baseSlug, ?int $exceptId = null): string
    {
        $slug = Str::slug($baseSlug) ?: 'unit';
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
        $next = (int) Unit::query()->max('id') + 1;

        do {
            $code = 'UNT-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            $next++;
        } while (Unit::query()->where('code', $code)->exists());

        return $code;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validate(array $data, ?int $exceptId = null): array
    {
        $rules = [
            'code' => ['nullable', 'string', 'max:32', Rule::unique('units', 'code')->ignore($exceptId)],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('units', 'slug')->ignore($exceptId),
            ],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'name_en' => ['required', 'string', 'max:128'],
            'abbreviation_en' => ['required', 'string', 'max:32'],
            'name_ur' => ['required', 'string', 'max:128'],
            'abbreviation_ur' => ['required', 'string', 'max:32'],
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
        return Unit::query()
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->where('slug', $slug)
            ->exists();
    }

    private function refreshProductLabelsForUnit(Unit $unit): void
    {
        Product::query()
            ->where('unit_id', $unit->id)
            ->with(['brand', 'unit', 'packUnit', 'attributes', 'controls', 'category'])
            ->chunkById(100, function ($products): void {
                foreach ($products as $product) {
                    app(ProductLabelService::class)->generateForProduct($product);
                }
            });
    }
}
