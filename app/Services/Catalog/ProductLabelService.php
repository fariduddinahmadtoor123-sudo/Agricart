<?php

namespace App\Services\Catalog;

use App\Models\Category;
use App\Models\Product;

class ProductLabelService
{
    public function resolveVisualMapCode(Category $category): string
    {
        $code = CategoryVisualMappingService::formatMapCode(
            (string) ($category->visual_mapping['map_code'] ?? ''),
        );

        if ($code !== '') {
            return $code;
        }

        return CategoryVisualMappingService::formatMapCode(
            app(CategoryVisualMappingService::class)->generateMapCode($category->parent_id, $category->id),
        );
    }

    /**
     * @return array{label_sequence: int, label_code: string}
     */
    public function nextLabelAssignment(string $visualMapCode): array
    {
        $sequence = (int) Product::query()
            ->where('category_visual_map_code', $visualMapCode)
            ->max('label_sequence') + 1;

        return [
            'label_sequence' => max(1, $sequence),
            'label_code' => $visualMapCode . '-P' . max(1, $sequence),
        ];
    }

    public function generateForProduct(Product $product): void
    {
        if (! filled($product->label_code)) {
            return;
        }

        $product->update([
            'label_qr' => [
                'en' => $this->buildSticker($product, 'en', 'qr'),
                'ur' => $this->buildSticker($product, 'ur', 'qr'),
            ],
            'label_barcode' => [
                'en' => $this->buildSticker($product, 'en', 'barcode'),
                'ur' => $this->buildSticker($product, 'ur', 'barcode'),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSticker(Product $product, string $locale, string $type): array
    {
        $isUrdu = $locale === 'ur';
        $name = $isUrdu ? $product->name_ur : $product->name_en;
        $lines = $this->buildLines($product, $locale);

        return [
            'locale' => $locale,
            'type' => $type,
            'label_code' => $product->label_code,
            'system_code' => $product->code,
            'title' => $name,
            'lines' => $lines,
            'qr_payload' => $product->label_code,
            'barcode_value' => $product->label_code,
        ];
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    private function buildLines(Product $product, string $locale): array
    {
        $isUrdu = $locale === 'ur';
        $lines = [];

        $lines[] = [
            'label' => $isUrdu ? 'سسٹم کوڈ' : 'System Code',
            'value' => $product->code,
        ];

        if ($product->brand) {
            $lines[] = [
                'label' => $isUrdu ? 'برانڈ' : 'Brand',
                'value' => $isUrdu ? $product->brand->name_ur : $product->brand->name_en,
            ];
        }

        if ($product->unit) {
            $unitLabel = $isUrdu ? $product->unit->name_ur : $product->unit->name_en;
            $qty = filled($product->unit_qty) ? ' × ' . $product->unit_qty : '';
            $lines[] = [
                'label' => $isUrdu ? 'اکائی' : 'Unit',
                'value' => $unitLabel . $qty,
            ];
        }

        if ($product->packUnit) {
            $packLabel = $isUrdu ? $product->packUnit->name_ur : $product->packUnit->name_en;
            $qty = filled($product->pack_qty) ? ' × ' . $product->pack_qty : '';
            $lines[] = [
                'label' => $isUrdu ? 'پیک' : 'Pack',
                'value' => $packLabel . $qty,
            ];
        }

        foreach ($product->attributes as $attribute) {
            $attrName = $isUrdu ? $attribute->name_ur : $attribute->name_en;
            $lines[] = [
                'label' => $attrName,
                'value' => (string) ($attribute->pivot->value ?? ''),
            ];
        }

        foreach ($product->controls as $control) {
            $controlName = $isUrdu ? $control->name_ur : $control->name_en;
            $controlValue = $isUrdu ? $control->value_ur : $control->value_en;
            $lines[] = [
                'label' => $controlName,
                'value' => $controlValue,
            ];
        }

        if (filled($product->required_qty)) {
            $lines[] = [
                'label' => $isUrdu ? 'ضروری مقدار' : 'Required Qty',
                'value' => $product->required_qty,
            ];
        }

        if (filled($product->alert_qty)) {
            $lines[] = [
                'label' => $isUrdu ? 'ری آرڈر مقدار' : 'Reorder Qty',
                'value' => $product->alert_qty,
            ];
        }

        if ($product->category) {
            $lines[] = [
                'label' => $isUrdu ? 'زمرہ' : 'Category',
                'value' => $isUrdu ? $product->category->name_ur : $product->category->name_en,
            ];
        }

        return $lines;
    }
}
