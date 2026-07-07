<?php

namespace App\Services\Backup;

use App\Models\Category;
use App\Models\Product;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExportService
{
    public function downloadProducts(): StreamedResponse
    {
        $filename = 'agricart-products-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'id',
                'code',
                'slug',
                'status',
                'name_en',
                'name_ur',
                'category_id',
                'category_name_en',
                'brand_id',
                'brand_name_en',
                'sku',
                'label_code',
                'retail_price',
                'wholesale_price',
                'image_path',
                'created_at',
            ]);

            Product::query()
                ->with(['category:id,name_en', 'brand:id,name_en'])
                ->orderBy('id')
                ->chunkById(500, function ($products) use ($handle): void {
                    foreach ($products as $product) {
                        fputcsv($handle, [
                            $product->id,
                            $product->code,
                            $product->slug,
                            $product->status,
                            $product->name_en,
                            $product->name_ur,
                            $product->category_id,
                            $product->category?->name_en,
                            $product->brand_id,
                            $product->brand?->name_en,
                            $product->sku,
                            $product->label_code,
                            $product->retail_price,
                            $product->wholesale_price,
                            $product->image_path,
                            $product->created_at?->toDateTimeString(),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function downloadCategories(): StreamedResponse
    {
        $filename = 'agricart-categories-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'id',
                'code',
                'slug',
                'status',
                'name_en',
                'name_ur',
                'parent_id',
                'image_path',
                'created_at',
            ]);

            Category::query()
                ->orderBy('id')
                ->chunkById(500, function ($categories) use ($handle): void {
                    foreach ($categories as $category) {
                        fputcsv($handle, [
                            $category->id,
                            $category->code,
                            $category->slug,
                            $category->status,
                            $category->name_en,
                            $category->name_ur,
                            $category->parent_id,
                            $category->image_path,
                            $category->created_at?->toDateTimeString(),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
