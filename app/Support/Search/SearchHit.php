<?php

namespace App\Support\Search;

use Illuminate\Support\Facades\Storage;

final class SearchHit
{
    /**
     * @param  array<string, mixed>|null  $panel
     * @return array{label: string, meta: string|null, url: string, image: string|null, panel: array<string, mixed>|null}
     */
    public static function make(
        string $label,
        ?string $meta,
        string $url,
        ?string $image = null,
        ?array $panel = null,
    ): array {
        return [
            'label' => $label,
            'meta' => filled($meta) ? $meta : null,
            'url' => $url,
            'image' => filled($image) ? $image : null,
            'panel' => $panel,
        ];
    }

    public static function image(?string $path): ?string
    {
        return filled($path) ? Storage::disk('public')->url($path) : null;
    }
}
