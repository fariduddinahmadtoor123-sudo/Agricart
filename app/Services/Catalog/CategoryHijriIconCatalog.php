<?php

namespace App\Services\Catalog;

class CategoryHijriIconCatalog
{
    /**
     * @return list<array{id: string, name_en: string, name_ur: string, keywords: list<string>}>
     */
    public static function all(): array
    {
        /** @var list<array{id: string, name_en: string, name_ur: string, keywords: list<string>}> */
        return config('agricart.hijri_icons', []);
    }

    /**
     * @return list<array{id: string, name_en: string, name_ur: string, keywords: list<string>}>
     */
    public static function search(string $query, int $limit = 20): array
    {
        $needle = mb_strtolower(trim($query));

        if (mb_strlen($needle) < 1) {
            return [];
        }

        $results = [];

        foreach (self::all() as $icon) {
            $haystacks = [
                $icon['name_en'],
                $icon['name_ur'],
                ...($icon['keywords'] ?? []),
            ];

            foreach ($haystacks as $haystack) {
                if (str_contains(mb_strtolower((string) $haystack), $needle)) {
                    $results[] = $icon;

                    continue 2;
                }
            }
        }

        return array_slice($results, 0, $limit);
    }

    /**
     * @return array{id: string, name_en: string, name_ur: string, keywords: list<string>}|null
     */
    public static function find(?string $id): ?array
    {
        if ($id === null || $id === '') {
            return null;
        }

        foreach (self::all() as $icon) {
            if ($icon['id'] === $id) {
                return $icon;
            }
        }

        return null;
    }

    public static function label(?string $id): ?string
    {
        $icon = self::find($id);

        if ($icon === null) {
            return null;
        }

        return $icon['name_en'] . ' — ' . $icon['name_ur'];
    }
}
