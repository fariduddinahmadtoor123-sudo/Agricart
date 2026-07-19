<?php

namespace App\Services\Catalog;

use App\Models\CatalogControl;
use App\Models\Product;
use App\Services\Identifiers\DocumentCounterService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ControlService
{
    public function create(array $data): CatalogControl
    {
        return Cache::lock('catalog-control-write', 30)->block(10, function () use ($data): CatalogControl {
            $data['code'] = $this->nextCode();
            $data['slug'] = $this->uniqueSlug(
                $this->slugFromName((string) ($data['name_en'] ?? 'control')),
            );
            $validated = $this->validate($data);

            return DB::transaction(function () use ($validated): CatalogControl {
                $control = CatalogControl::query()->create($validated);
                CatalogCache::forgetOverviewStats();

                return $control;
            });
        });
    }

    public function update(CatalogControl $control, array $data): CatalogControl
    {
        return Cache::lock('catalog-control-write', 30)->block(10, function () use ($control, $data): CatalogControl {
            $control->refresh();
            $data['code'] = $control->code;
            $data['slug'] = $control->slug;
            $validated = $this->validate($data, $control->id);

            return DB::transaction(function () use ($control, $validated): CatalogControl {
                $control->update($validated);

                if ($control->wasChanged(['name_en', 'name_ur', 'value_en', 'value_ur'])) {
                    $this->refreshProductLabelsForControl($control);
                }

                CatalogCache::forgetOverviewStats();

                return $control->fresh();
            });
        });
    }

    public function delete(CatalogControl $control): void
    {
        Cache::lock('catalog-control-write', 30)->block(10, function () use ($control): void {
            $control->refresh();

            if (DB::table('product_control')->where('control_id', $control->id)->exists()) {
                throw ValidationException::withMessages([
                    'control' => [__('agricart.controls.messages.linked_delete')],
                ]);
            }

            $control->delete();
            CatalogCache::forgetOverviewStats();
        });
    }

    public function slugFromName(string $name): string
    {
        return Str::slug($name);
    }

    public function uniqueSlug(string $baseSlug, ?int $exceptId = null): string
    {
        $slug = Str::slug($baseSlug) ?: 'control';
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
            'catalog_controls.code', 'CTL-', 5,
            fn (): int => DocumentCounterService::maximumSuffix('controls', 'code', 'CTL-'),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validate(array $data, ?int $exceptId = null): array
    {
        $rules = [
            'code' => ['nullable', 'string', 'max:32', Rule::unique('controls', 'code')->ignore($exceptId)],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('controls', 'slug')->ignore($exceptId),
            ],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'name_en' => ['required', 'string', 'max:128'],
            'name_ur' => ['required', 'string', 'max:128'],
            'value_en' => ['required', 'string', 'max:1000'],
            'value_ur' => ['required', 'string', 'max:1000'],
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
        return CatalogControl::query()
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->where('slug', $slug)
            ->exists();
    }

    private function refreshProductLabelsForControl(CatalogControl $control): void
    {
        Product::query()
            ->whereHas('controls', fn ($query) => $query->whereKey($control->id))
            ->with(['brand', 'unit', 'packUnit', 'attributes', 'controls', 'category'])
            ->chunkById(100, function ($products): void {
                foreach ($products as $product) {
                    app(ProductLabelService::class)->generateForProduct($product);
                }
            });
    }
}
