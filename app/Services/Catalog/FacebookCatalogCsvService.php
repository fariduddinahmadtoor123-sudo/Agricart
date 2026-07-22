<?php

namespace App\Services\Catalog;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;

/**
 * Meta Commerce Manager–compatible product CSV (manual upload).
 */
class FacebookCatalogCsvService
{
    /**
     * @return array{path: string, filename: string, count: int}
     */
    public function exportActiveProducts(): array
    {
        $products = Product::query()
            ->with(['brand:id,name_en', 'category:id,name_en'])
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        $filename = 'facebook-catalog-'.now()->format('Y-m-d').'.csv';
        $path = storage_path('app/tmp/'.$filename);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new \RuntimeException('Could not open Facebook catalog CSV for writing.');
        }

        fputcsv($handle, [
            'id',
            'title',
            'description',
            'availability',
            'condition',
            'price',
            'link',
            'image_link',
            'additional_image_link',
            'brand',
            'google_product_category',
            'fb_product_category',
        ]);

        foreach ($products as $product) {
            $imageUrl = $this->publicUrl($product->image_path);
            $gallery = collect($product->gallery_images ?? [])
                ->map(fn ($path) => $this->publicUrl(is_string($path) ? $path : null))
                ->filter()
                ->values()
                ->all();

            $price = number_format((float) ($product->retail_price ?? 0), 2, '.', '');
            $availability = ((float) ($product->stock_qty ?? 0)) > 0 ? 'in stock' : 'out of stock';
            $categoryName = $product->category?->name_en ?: 'Agriculture';

            fputcsv($handle, [
                (string) ($product->code ?: $product->id),
                (string) $product->name_en,
                strip_tags((string) ($product->short_description_en ?: $product->full_description_en ?: $product->name_en)),
                $availability,
                'new',
                $price.' PKR',
                route('store.product', ['slug' => $product->slug]),
                $imageUrl ?? '',
                implode(',', $gallery),
                (string) ($product->brand?->name_en ?: 'Agricart'),
                $categoryName,
                $categoryName,
            ]);
        }

        fclose($handle);

        return [
            'path' => $path,
            'filename' => $filename,
            'count' => $products->count(),
        ];
    }

    private function publicUrl(?string $path): ?string
    {
        if (! filled($path) || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
