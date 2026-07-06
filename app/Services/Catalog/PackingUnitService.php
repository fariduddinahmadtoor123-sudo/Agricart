<?php

namespace App\Services\Catalog;

use App\Models\PackingUnit;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PackingUnitService
{
    public function create(array $data): PackingUnit
    {
        $validated = $this->validate($data);

        if (! filled($validated['code'] ?? null)) {
            $validated['code'] = $this->nextCode();
        }

        if (! filled($validated['slug'] ?? null) && filled($validated['name_en'])) {
            $validated['slug'] = $this->uniqueSlug($this->slugFromName($validated['name_en']));
        }

        return PackingUnit::query()->create($validated);
    }

    public function update(PackingUnit $packingUnit, array $data): PackingUnit
    {
        $validated = $this->validate($data, $packingUnit->id);

        if (! filled($validated['code'] ?? null)) {
            $validated['code'] = $packingUnit->code;
        }

        $packingUnit->update($validated);

        return $packingUnit->fresh();
    }

    public function delete(PackingUnit $packingUnit): void
    {
        $packingUnit->delete();
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
}
