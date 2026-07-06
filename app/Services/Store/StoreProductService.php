<?php

namespace App\Services\Store;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class StoreProductService
{
    /**
     * @return Collection<int, Product>
     */
    public function productsForCategory(Category $category): Collection
    {
        return Product::query()
            ->where('status', 'active')
            ->where(function ($query) use ($category): void {
                $query->where('category_id', $category->id)
                    ->orWhereHas(
                        'displayCategories',
                        fn ($relation) => $relation->where('categories.id', $category->id),
                    );
            })
            ->with(['brand'])
            ->orderBy('name_en')
            ->get();
    }

    public function imageUrl(?Product $product): ?string
    {
        if ($product === null || ! filled($product->image_path)) {
            return null;
        }

        return Storage::disk('public')->url($product->image_path);
    }

    public function localizedName(Product $product): string
    {
        if (app()->getLocale() === 'ur' && filled($product->name_ur)) {
            return $product->name_ur;
        }

        return $product->name_en;
    }

    public function formattedRetailPrice(Product $product): ?string
    {
        if ($product->retail_price === null || $product->retail_price === '') {
            return null;
        }

        return number_format((float) $product->retail_price, 0);
    }

    public function findActiveBySlug(string $slug): ?Product
    {
        return Product::query()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->with(['brand', 'category', 'unit', 'packUnit', 'attributes'])
            ->first();
    }

    /**
     * @return list<string>
     */
    public function allImageUrls(Product $product): array
    {
        $urls = [];

        if ($main = $this->imageUrl($product)) {
            $urls[] = $main;
        }

        foreach ($product->gallery_images ?? [] as $path) {
            if (! is_string($path) || ! filled($path)) {
                continue;
            }

            $url = Storage::disk('public')->url($path);

            if (! in_array($url, $urls, true)) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    public function localizedField(Product $product, string $base): ?string
    {
        $localizedKey = $base . '_' . app()->getLocale();

        if (app()->getLocale() !== 'en' && filled($product->{$localizedKey})) {
            return $product->{$localizedKey};
        }

        $englishKey = $base . '_en';
        $value = $product->{$englishKey};

        return filled($value) ? $value : null;
    }
}
