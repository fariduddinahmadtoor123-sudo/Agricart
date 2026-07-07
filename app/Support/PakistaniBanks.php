<?php

namespace App\Support;

class PakistaniBanks
{
    /**
     * @return array<string, array{en: string, ur: string, category?: string, aliases?: list<string>}>
     */
    public static function entries(): array
    {
        $banks = config('pakistani_banks', []);

        return is_array($banks) ? $banks : [];
    }

    public static function english(?string $key): string
    {
        if ($key === null || $key === '') {
            return '';
        }

        $entry = self::entries()[$key] ?? null;

        return is_array($entry) ? (string) ($entry['en'] ?? '') : '';
    }

    public static function urdu(?string $key): string
    {
        if ($key === null || $key === '') {
            return '';
        }

        $entry = self::entries()[$key] ?? null;

        return is_array($entry) ? (string) ($entry['ur'] ?? '') : '';
    }

    public static function label(?string $key, ?string $locale = null): ?string
    {
        if ($key === null || $key === '') {
            return null;
        }

        $locale = $locale ?? app()->getLocale();

        return $locale === 'ur' ? self::urdu($key) : self::english($key);
    }

    public static function englishFromKey(?string $key): ?string
    {
        $english = self::english($key);

        return $english !== '' ? $english : null;
    }

    public static function bilingualLabel(?string $key): string
    {
        $en = self::english($key);
        $ur = self::urdu($key);

        if ($en === '' && $ur === '') {
            return '';
        }

        if ($ur === '' || $ur === $en) {
            return $en;
        }

        return $en . ' — ' . $ur;
    }

    /**
     * @return array<string, string> key => bilingual search result label
     */
    public static function search(string $search, ?string $locale = null): array
    {
        $needle = mb_strtolower(trim($search));

        if ($needle === '') {
            return [];
        }

        $locale = $locale ?? app()->getLocale();
        $results = [];

        foreach (self::entries() as $key => $labels) {
            if (! is_array($labels)) {
                continue;
            }

            if (self::matchesSearch($labels, $needle, $search)) {
                $results[$key] = $locale === 'ur'
                    ? self::formatSearchLabel($labels, urFirst: true)
                    : self::formatSearchLabel($labels);
            }
        }

        asort($results);

        return $results;
    }

    /**
     * @param  array{en?: string, ur?: string, aliases?: list<string>}  $labels
     */
    private static function matchesSearch(array $labels, string $needle, string $rawSearch): bool
    {
        $en = mb_strtolower((string) ($labels['en'] ?? ''));
        $ur = (string) ($labels['ur'] ?? '');

        if (str_contains($en, $needle)) {
            return true;
        }

        if ($ur !== '' && mb_stripos($ur, $rawSearch) !== false) {
            return true;
        }

        foreach ($labels['aliases'] ?? [] as $alias) {
            if (str_contains(mb_strtolower((string) $alias), $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{en?: string, ur?: string}  $labels
     */
    private static function formatSearchLabel(array $labels, bool $urFirst = false): string
    {
        $en = (string) ($labels['en'] ?? '');
        $ur = (string) ($labels['ur'] ?? '');

        if ($en === '') {
            return $ur;
        }

        if ($ur === '') {
            return $en;
        }

        return $urFirst ? ($ur . ' — ' . $en) : ($en . ' — ' . $ur);
    }

    /**
     * @return list<string> English names for legacy pickers.
     */
    public static function englishNames(): array
    {
        return array_values(array_filter(array_map(
            fn (array $labels): ?string => $labels['en'] ?? null,
            self::entries(),
        )));
    }

    public static function keyFromEnglish(string $englishName): ?string
    {
        $needle = mb_strtolower(trim($englishName));

        foreach (self::entries() as $key => $labels) {
            if (! is_array($labels)) {
                continue;
            }

            if (mb_strtolower((string) ($labels['en'] ?? '')) === $needle) {
                return $key;
            }
        }

        return null;
    }
}
