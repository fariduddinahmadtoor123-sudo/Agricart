<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrintProfile extends Model
{
    public const CATEGORY_DOCUMENT = 'document';

    public const CATEGORY_RECEIPT = 'receipt';

    public const CATEGORY_LABEL = 'label';

    public const LABEL_KIND_PRODUCT = 'product';

    public const LABEL_KIND_PRICE = 'price';

    protected $fillable = [
        'key',
        'name_en',
        'name_ur',
        'category',
        'label_kind',
        'width_mm',
        'height_mm',
        'margin_mm',
        'orientation',
        'printer_hint',
        'is_system',
        'is_default',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'width_mm' => 'float',
            'height_mm' => 'float',
            'margin_mm' => 'float',
            'is_system' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function localizedName(): string
    {
        return app()->getLocale() === 'ur' && filled($this->name_ur)
            ? $this->name_ur
            : $this->name_en;
    }

    public function pageSizeCss(): string
    {
        $width = rtrim(rtrim(number_format($this->width_mm, 2, '.', ''), '0'), '.');

        if ($this->height_mm === null) {
            return "{$width}mm auto";
        }

        $height = rtrim(rtrim(number_format($this->height_mm, 2, '.', ''), '0'), '.');

        return "{$width}mm {$height}mm";
    }

    public function dimensionsLabel(): string
    {
        $width = rtrim(rtrim(number_format($this->width_mm, 1, '.', ''), '0'), '.');

        if ($this->height_mm === null) {
            return "{$width} mm roll";
        }

        $height = rtrim(rtrim(number_format($this->height_mm, 1, '.', ''), '0'), '.');

        return "{$width} × {$height} mm";
    }
}
