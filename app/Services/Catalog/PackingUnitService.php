<?php

namespace App\Services\Catalog;

use App\Models\PackingUnit;
use App\Models\Product;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PackingUnitService
{
    public function create(array $data): PackingUnit
    {
        return Cache::lock('catalog-packing-unit-write', 30)->block(10, function () use ($data): PackingUnit {
            $data['code'] = $this->nextCode();
            $data['slug'] = $this->uniqueSlug(
                filled($data['slug'] ?? null)
                    ? (string) $data['slug']
                    : $this->slugFromName((string) ($data['name_en'] ?? 'packing-unit')),
            );
            $validated = $this->validate($data);

            return DB::transaction(function () use ($validated): PackingUnit {
                $packingUnit = PackingUnit::query()->create($validated);
                CatalogCache::forgetOverviewStats();

                return $packingUnit;
            });
        });
    }

    public function update(PackingUnit $packingUnit, array $data): PackingUnit
    {
        return Cache::lock('catalog-packing-unit-write', 30)->block(10, function () use ($packingUnit, $data): PackingUnit {
            $packingUnit->refresh();
            $data['code'] = $packingUnit->code;
            $validated = $this->validate($data, $packingUnit->id);

            return DB::transaction(function () use ($packingUnit, $validated): PackingUnit {
                $packingUnit->update($validated);

                if ($packingUnit->wasChanged(['name_en', 'name_ur'])) {
                    $this->refreshProductLabelsForPackingUnit($packingUnit);
                }

                CatalogCache::forgetOverviewStats();

                return $packingUnit->fresh();
            });
        });
    }

    public function delete(PackingUnit $packingUnit): void
    {
        Cache::lock('catalog-packing-unit-write', 30)->block(10, function () use ($packingUnit): void {
            $packingUnit->refresh();

            if (Product::query()->where('pack_unit_id', $packingUnit->id)->exists()) {
                throw ValidationException::withMessages([
                    'packing_unit' => ['Move this packing unit’s products first, then delete the packing unit.'],
                ]);
            }

            $packingUnit->delete();
            CatalogCache::forgetOverviewStats();
        });
    }

    public function slugFromName(string $name): string
    {
        return Str::slug($name);
    }

    public function uniqueSlug(string $baseSlug, ?int $exceptId = null): string
    {
        $slug = Str::slug($baseSlug) ?: 'packing-unit';
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
        $next = (int) PackingUnit::query()->max('id') + 1;

        do {
            $code = 'PKG-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            $next++;
        } while (PackingUnit::query()->where('code', $code)->exists());

        return $code;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validate(array $data, ?int $exceptId = null): array
    {
        $rules = [
            'code' => ['nullable', 'string', 'max:32', Rule::unique('packing_units', 'code')->ignore($exceptId)],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('packing_units', 'slug')->ignore($exceptId),
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
        return PackingUnit::query()
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->where('slug', $slug)
            ->exists();
    }

    private function refreshProductLabelsForPackingUnit(PackingUnit $packingUnit): void
    {
        Product::query()
            ->where('pack_unit_id', $packingUnit->id)
            ->with(['brand', 'unit', 'packUnit', 'attributes', 'controls', 'category'])
            ->chunkById(100, function ($products): void {
                foreach ($products as $product) {
                    app(ProductLabelService::class)->generateForProduct($product);
                }
            });
    }
}
