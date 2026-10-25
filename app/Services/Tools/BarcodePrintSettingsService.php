<?php

namespace App\Services\Tools;

use App\Models\ToolsBarcodePrintSetting;

class BarcodePrintSettingsService
{
    /**
     * @return list<array{width_cm: float, height_cm: float, label: string}>
     */
    public function sizesForUser(?int $userId = null): array
    {
        $data = $this->getForUser($userId);
        $sizes = $data['sticker_sizes'] ?? [];

        if (! is_array($sizes)) {
            return [];
        }

        $out = [];

        foreach ($sizes as $size) {
            if (! is_array($size)) {
                continue;
            }

            $w = round((float) ($size['width_cm'] ?? 0), 2);
            $h = round((float) ($size['height_cm'] ?? 0), 2);

            if ($w <= 0 || $h <= 0) {
                continue;
            }

            $out[] = [
                'width_cm' => $w,
                'height_cm' => $h,
                'label' => $this->formatLabel($w, $h),
            ];
        }

        return $out;
    }

    /**
     * @param  list<array{width_cm: float|int|string, height_cm: float|int|string}>  $sizes
     * @return list<array{width_cm: float, height_cm: float, label: string}>
     */
    public function saveSizes(array $sizes, ?int $userId = null): array
    {
        $normalized = [];
        $seen = [];

        foreach ($sizes as $size) {
            if (! is_array($size)) {
                continue;
            }

            $w = round((float) ($size['width_cm'] ?? 0), 2);
            $h = round((float) ($size['height_cm'] ?? 0), 2);

            if ($w <= 0 || $h <= 0) {
                continue;
            }

            $key = $w.'x'.$h;

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $normalized[] = [
                'width_cm' => $w,
                'height_cm' => $h,
                'label' => $this->formatLabel($w, $h),
            ];
        }

        $data = $this->getForUser($userId);
        $data['sticker_sizes'] = $normalized;
        $this->saveForUser($data, $userId);

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    public function getForUser(?int $userId = null): array
    {
        $userId = $userId ?? (int) (auth()->id() ?? 0);

        if ($userId <= 0) {
            return [];
        }

        $row = ToolsBarcodePrintSetting::query()->where('user_id', $userId)->first();

        return is_array($row?->data) ? $row->data : [];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function saveForUser(array $data, ?int $userId = null): array
    {
        $userId = $userId ?? (int) (auth()->id() ?? 0);

        if ($userId <= 0) {
            return $data;
        }

        ToolsBarcodePrintSetting::query()->updateOrCreate(
            ['user_id' => $userId],
            ['data' => $data],
        );

        return $data;
    }

    public function formatLabel(float $widthCm, float $heightCm): string
    {
        return rtrim(rtrim(number_format($widthCm, 2, '.', ''), '0'), '.').' × '
            .rtrim(rtrim(number_format($heightCm, 2, '.', ''), '0'), '.').' cm';
    }
}
