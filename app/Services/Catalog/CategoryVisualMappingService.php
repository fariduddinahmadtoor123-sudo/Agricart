<?php

namespace App\Services\Catalog;

use App\Models\Category;

class CategoryVisualMappingService
{
    /** @var list<string> */
    private const DEPTH_PREFIXES = ['R', 'M', 'S', 'C', 'L'];

    /**
     * Build a display-only hierarchy code (R1, R1.M1, R1.M1.S1, …).
     * Independent of database id — used for visual mapping only.
     */
    public function generateMapCode(?int $parentId, ?int $exceptCategoryId = null): string
    {
        if ($parentId === null) {
            $number = Category::query()
                ->whereNull('parent_id')
                ->when($exceptCategoryId, fn ($query) => $query->where('id', '!=', $exceptCategoryId))
                ->count() + 1;

            return 'R' . $number;
        }

        $parent = Category::query()->find($parentId);

        if ($parent === null) {
            return $this->generateMapCode(null, $exceptCategoryId);
        }

        $parentCode = self::formatMapCode((string) ($parent->visual_mapping['map_code'] ?? ''));

        if ($parentCode === '') {
            $parentCode = self::formatMapCode($this->generateMapCode($parent->parent_id, $parent->id));
        }

        $depth = $this->depth($parentId) + 1;
        $prefix = self::DEPTH_PREFIXES[min($depth, count(self::DEPTH_PREFIXES) - 1)];

        $number = Category::query()
            ->where('parent_id', $parentId)
            ->when($exceptCategoryId, fn ($query) => $query->where('id', '!=', $exceptCategoryId))
            ->count() + 1;

        return $parentCode . $prefix . $number;
    }

    public static function formatMapCode(string $code): string
    {
        return str_replace('.', '', $code);
    }

    /**
     * Depth of the category identified by parentId (0 = root level parent).
     */
    private function depth(?int $parentId): int
    {
        if ($parentId === null) {
            return -1;
        }

        $parent = Category::query()->find($parentId);

        if ($parent === null || $parent->parent_id === null) {
            return 0;
        }

        return 1 + $this->depth($parent->parent_id);
    }
}
