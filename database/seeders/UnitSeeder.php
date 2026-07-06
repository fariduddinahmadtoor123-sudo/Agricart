<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UnitSeeder extends Seeder
{
    /**
     * @var list<array{name_en: string, abbreviation_en: string, name_ur: string, abbreviation_ur: string}>
     */
    private array $units = [
        ['name_en' => 'Kilogram', 'abbreviation_en' => 'kg', 'name_ur' => 'کلوگرام', 'abbreviation_ur' => 'کلو'],
        ['name_en' => 'Gram', 'abbreviation_en' => 'g', 'name_ur' => 'گرام', 'abbreviation_ur' => 'گرام'],
        ['name_en' => 'Liter', 'abbreviation_en' => 'L', 'name_ur' => 'لیٹر', 'abbreviation_ur' => 'لیٹر'],
        ['name_en' => 'Milliliter', 'abbreviation_en' => 'ml', 'name_ur' => 'ملی لیٹر', 'abbreviation_ur' => 'ملی'],
        ['name_en' => 'Meter', 'abbreviation_en' => 'm', 'name_ur' => 'میٹر', 'abbreviation_ur' => 'میٹر'],
        ['name_en' => 'Foot', 'abbreviation_en' => 'ft', 'name_ur' => 'فٹ', 'abbreviation_ur' => 'فٹ'],
        ['name_en' => 'Inch', 'abbreviation_en' => 'in', 'name_ur' => 'انچ', 'abbreviation_ur' => 'انچ'],
        ['name_en' => 'Piece', 'abbreviation_en' => 'pc', 'name_ur' => 'عدد', 'abbreviation_ur' => 'عدد'],
        ['name_en' => 'Dozen', 'abbreviation_en' => 'dz', 'name_ur' => 'درجن', 'abbreviation_ur' => 'درجن'],
        ['name_en' => 'Box', 'abbreviation_en' => 'box', 'name_ur' => 'ڈبہ', 'abbreviation_ur' => 'باکس'],
    ];

    public function run(): void
    {
        foreach ($this->units as $index => $unit) {
            $slug = Str::slug($unit['name_en']);
            $code = 'UNT-' . str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT);

            Unit::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'code' => $code,
                    'status' => 'active',
                    'name_en' => $unit['name_en'],
                    'abbreviation_en' => $unit['abbreviation_en'],
                    'name_ur' => $unit['name_ur'],
                    'abbreviation_ur' => $unit['abbreviation_ur'],
                    'ai_status' => 'manual',
                ],
            );
        }
    }
}
