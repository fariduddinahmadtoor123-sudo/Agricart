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
        return $this->generateMapCodeGuarded($parentId, $exceptCategoryId, []);
    }

    /**
     * @param  array<int, true>  $visited
     */
    private function generateMapCodeGuarded(
        ?int $parentId,
        ?int $exceptCategoryId,
        array $visited,
    ): string
    {
        if ($parentId === null) {
            $number = $this->nextSiblingNumber(null, '', 'R', $exceptCategoryId);

            return 'R' . $number;
        }

        $parent = Category::query()->find($parentId);

        if ($parent === null) {
            return $this->generateMapCodeGuarded(null, $exceptCategoryId, $visited);
        }

        if (isset($visited[$parent->id])) {
            return $this->generateMapCodeGuarded(null, $exceptCategoryId, $visited);
        }

        $visited[$parent->id] = true;
        $parentCode = self::formatMapCode((string) ($parent->visual_mapping['map_code'] ?? ''));

        if ($parentCode === '') {
            $parentCode = self::formatMapCode($this->generateMapCodeGuarded(
                $parent->parent_id,
                $parent->id,
                $visited,
            ));
        }

        $depth = $this->depth($parentId) + 1;
        $prefix = self::DEPTH_PREFIXES[min($depth, count(self::DEPTH_PREFIXES) - 1)];

        $number = $this->nextSiblingNumber($parentId, $parentCode, $prefix, $exceptCategoryId);

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
        $depth = -1;
        $visited = [];
        $currentId = $parentId;

        while ($currentId !== null && ! isset($visited[$currentId]) && $depth < 99) {
            $visited[$currentId] = true;
            $depth++;
            $currentId = Category::query()->whereKey($currentId)->value('parent_id');
            $currentId = $currentId !== null ? (int) $currentId : null;
        }

        return $depth;
    }

    private function nextSiblingNumber(
        ?int $parentId,
        string $parentCode,
        string $prefix,
        ?int $exceptCategoryId,
    ): int {
        $pattern = '/^'.preg_quote($parentCode.$prefix, '/').'(\d+)$/';
        $maximum = Category::query()
            ->when(
                $parentId === null,
                fn ($query) => $query->whereNull('parent_id'),
                fn ($query) => $query->where('parent_id', $parentId),
            )
            ->when($exceptCategoryId, fn ($query) => $query->where('id', '!=', $exceptCategoryId))
            ->get(['visual_map_code', 'visual_mapping'])
            ->reduce(function (int $highest, Category $category) use ($pattern): int {
                $code = self::formatMapCode(
                    (string) ($category->visual_map_code ?: ($category->visual_mapping['map_code'] ?? '')),
                );

                return preg_match($pattern, $code, $matches)
                    ? max($highest, (int) $matches[1])
                    : $highest;
            }, 0);

        return $maximum + 1;
    }
}
