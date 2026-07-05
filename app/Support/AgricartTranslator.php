<?php

namespace App\Support;

class AgricartTranslator
{
    public static function module(string $slug): string
    {
        return __('agricart.modules.' . $slug);
    }

    public static function page(string $moduleSlug, string $pageSlug, ?string $fallback = null): string
    {
        $key = "agricart.pages.{$moduleSlug}.{$pageSlug}";
        $translated = __($key);

        if ($translated !== $key) {
            return $translated;
        }

        return $fallback ?? ucwords(str_replace('-', ' ', $pageSlug));
    }

    public static function line(string $key, array $replace = []): string
    {
        return __('agricart.' . $key, $replace);
    }
}
