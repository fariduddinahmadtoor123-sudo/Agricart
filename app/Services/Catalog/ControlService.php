<?php

namespace App\Services\Catalog;

use App\Models\CatalogControl;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ControlService
{
    public function create(array $data): CatalogControl
    {
        $validated = $this->validate($data);

        if (! filled($validated['code'] ?? null)) {
            $validated['code'] = $this->nextCode();
        }

        if (! filled($validated['slug'] ?? null) && filled($validated['name_en'])) {
            $validated['slug'] = $this->uniqueSlug($this->slugFromName($validated['name_en']));
        }

        return CatalogControl::query()->create($validated);
    }

    public function update(CatalogControl $control, array $data): CatalogControl
    {
        $validated = $this->validate($data, $control->id);

        if (! filled($validated['code'] ?? null)) {
            $validated['code'] = $control->code;
        }

        $control->update($validated);

        return $control->fresh();
    }

    public function delete(CatalogControl $control): void
    {
        $control->delete();
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
        $next = (int) CatalogControl::query()->max('id') + 1;

        do {
            $code = 'CTL-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            $next++;
        } while (CatalogControl::query()->where('code', $code)->exists());

        return $code;
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
            'value_en' => ['required', 'string', 'max:255'],
            'value_ur' => ['required', 'string', 'max:255'],
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
}
