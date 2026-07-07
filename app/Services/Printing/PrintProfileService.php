<?php

namespace App\Services\Printing;

use App\Models\PrintProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PrintProfileService
{
    /**
     * @return Collection<int, PrintProfile>
     */
    public function all(): Collection
    {
        return PrintProfile::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, PrintProfile>
     */
    public function labels(): Collection
    {
        return $this->all()->where('category', PrintProfile::CATEGORY_LABEL)->values();
    }

    public function findByKey(string $key): ?PrintProfile
    {
        return PrintProfile::query()->where('key', $key)->first();
    }

    public function defaultForCategory(string $category, ?string $labelKind = null): ?PrintProfile
    {
        $query = PrintProfile::query()
            ->where('category', $category)
            ->where('is_default', true);

        if ($labelKind !== null) {
            $query->where('label_kind', $labelKind);
        }

        $profile = $query->orderBy('sort_order')->first();

        if ($profile !== null) {
            return $profile;
        }

        return PrintProfile::query()
            ->where('category', $category)
            ->when($labelKind !== null, fn ($inner) => $inner->where('label_kind', $labelKind))
            ->orderBy('sort_order')
            ->first();
    }

    public function defaultProductLabel(): ?PrintProfile
    {
        return $this->defaultForCategory(PrintProfile::CATEGORY_LABEL, PrintProfile::LABEL_KIND_PRODUCT);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): PrintProfile
    {
        $profile = PrintProfile::query()->findOrFail($id);

        $profile->update([
            'name_en' => (string) ($data['name_en'] ?? $profile->name_en),
            'name_ur' => (string) ($data['name_ur'] ?? $profile->name_ur),
            'width_mm' => (float) ($data['width_mm'] ?? $profile->width_mm),
            'height_mm' => array_key_exists('height_mm', $data)
                ? ($data['height_mm'] === '' || $data['height_mm'] === null ? null : (float) $data['height_mm'])
                : $profile->height_mm,
            'margin_mm' => (float) ($data['margin_mm'] ?? $profile->margin_mm),
            'orientation' => in_array(($data['orientation'] ?? $profile->orientation), ['portrait', 'landscape'], true)
                ? $data['orientation']
                : $profile->orientation,
            'printer_hint' => $data['printer_hint'] ?? $profile->printer_hint,
        ]);

        return $profile->fresh();
    }

    public function setDefault(int $id): PrintProfile
    {
        $profile = PrintProfile::query()->findOrFail($id);

        PrintProfile::query()
            ->where('category', $profile->category)
            ->when(
                $profile->category === PrintProfile::CATEGORY_LABEL,
                fn ($query) => $query->where('label_kind', $profile->label_kind),
            )
            ->update(['is_default' => false]);

        $profile->update(['is_default' => true]);

        return $profile->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCustom(array $data): PrintProfile
    {
        $key = Str::slug((string) ($data['key'] ?? $data['name_en'] ?? 'custom-profile'), '_');

        if ($key === '') {
            $key = 'custom_profile';
        }

        $baseKey = $key;
        $suffix = 1;

        while ($this->findByKey($key) !== null) {
            $key = $baseKey . '_' . $suffix;
            $suffix++;
        }

        $category = (string) ($data['category'] ?? PrintProfile::CATEGORY_LABEL);

        if (! in_array($category, [
            PrintProfile::CATEGORY_DOCUMENT,
            PrintProfile::CATEGORY_RECEIPT,
            PrintProfile::CATEGORY_LABEL,
        ], true)) {
            throw ValidationException::withMessages([
                'category' => __('agricart.printing.messages.invalid_category'),
            ]);
        }

        $labelKind = $category === PrintProfile::CATEGORY_LABEL
            ? (string) ($data['label_kind'] ?? PrintProfile::LABEL_KIND_PRODUCT)
            : null;

        $maxSort = (int) PrintProfile::query()->max('sort_order');

        return PrintProfile::query()->create([
            'key' => $key,
            'name_en' => (string) ($data['name_en'] ?? $key),
            'name_ur' => (string) ($data['name_ur'] ?? $data['name_en'] ?? $key),
            'category' => $category,
            'label_kind' => $labelKind,
            'width_mm' => (float) ($data['width_mm'] ?? 50),
            'height_mm' => $category === PrintProfile::CATEGORY_RECEIPT
                ? null
                : (float) ($data['height_mm'] ?? 30),
            'margin_mm' => (float) ($data['margin_mm'] ?? 2),
            'orientation' => ($data['orientation'] ?? 'portrait') === 'landscape' ? 'landscape' : 'portrait',
            'printer_hint' => $data['printer_hint'] ?? null,
            'is_system' => false,
            'is_default' => false,
            'sort_order' => $maxSort + 10,
        ]);
    }

    public function delete(int $id): void
    {
        $profile = PrintProfile::query()->findOrFail($id);

        if ($profile->is_system) {
            throw ValidationException::withMessages([
                'profile' => __('agricart.printing.messages.system_locked'),
            ]);
        }

        $profile->delete();
    }
}
