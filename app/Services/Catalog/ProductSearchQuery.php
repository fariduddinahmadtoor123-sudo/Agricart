<?php

namespace App\Services\Catalog;

use Illuminate\Database\Eloquent\Builder;

/**
 * Shared product lookup for POS, purchasing, and admin lists.
 * Prefers exact / prefix matches (index-friendly) before full wildcard scans.
 */
class ProductSearchQuery
{
    /**
     * @param  Builder<\App\Models\Product>  $query
     */
    public static function apply(Builder $query, string $term): void
    {
        $term = trim($term);

        if ($term === '') {
            $query->whereRaw('0 = 1');

            return;
        }

        if (mb_strlen($term) < 2) {
            $query->where(function ($inner) use ($term): void {
                $inner->where('code', $term)
                    ->orWhere('sku', $term)
                    ->orWhere('label_code', $term);

                if (ctype_digit($term)) {
                    $inner->orWhere('id', (int) $term);
                }
            });

            return;
        }

        if (! str_contains($term, ' ')) {
            $prefix = $term . '%';
            $query->where(function ($inner) use ($term, $prefix): void {
                $inner->where('code', $term)
                    ->orWhere('sku', $term)
                    ->orWhere('label_code', $term)
                    ->orWhere('code', 'like', $prefix)
                    ->orWhere('sku', 'like', $prefix)
                    ->orWhere('label_code', 'like', $prefix)
                    ->orWhere('name_en', 'like', $prefix)
                    ->orWhere('name_ur', 'like', $prefix);

                if (ctype_digit($term)) {
                    $inner->orWhere('id', (int) $term);
                }
            });

            return;
        }

        $needle = '%' . $term . '%';
        $query->where(function ($inner) use ($needle): void {
            $inner->where('name_en', 'like', $needle)
                ->orWhere('name_ur', 'like', $needle)
                ->orWhere('code', 'like', $needle)
                ->orWhere('label_code', 'like', $needle)
                ->orWhere('sku', 'like', $needle);
        });
    }
}
