<?php

namespace Database\Seeders;

use App\Models\CatalogAttribute;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AttributeSeeder extends Seeder
{
    /**
     * @var list<array{name_en: string, name_ur: string}>
     */
    private array $attributes = [
        ['name_en' => 'Size', 'name_ur' => 'سائز'],
        ['name_en' => 'Color', 'name_ur' => 'رنگ'],
        ['name_en' => 'Material', 'name_ur' => 'مواد'],
        ['name_en' => 'Weight', 'name_ur' => 'وزن'],
        ['name_en' => 'Length', 'name_ur' => 'لمبائی'],
        ['name_en' => 'Width', 'name_ur' => 'چوڑائی'],
        ['name_en' => 'Thickness', 'name_ur' => 'موتائی'],
        ['name_en' => 'Diameter', 'name_ur' => 'قطر'],
        ['name_en' => 'Inner Bore', 'name_ur' => 'اندرونی بور'],
        ['name_en' => 'Outer Bore', 'name_ur' => 'بیرونی بور'],
        ['name_en' => 'Arbor Size', 'name_ur' => 'اربور سائز'],
        ['name_en' => 'Thread Pitch', 'name_ur' => 'تھریڈ پچ'],
        ['name_en' => 'Thread Type', 'name_ur' => 'تھریڈ کی قسم'],
        ['name_en' => 'Connection Type', 'name_ur' => 'کنکشن کی قسم'],
        ['name_en' => 'Hole Count', 'name_ur' => 'سوراخوں کی تعداد'],
        ['name_en' => 'Voltage', 'name_ur' => 'وولٹیج'],
        ['name_en' => 'Power Rating', 'name_ur' => 'پاور ریٹنگ'],
        ['name_en' => 'RPM', 'name_ur' => 'سپیڈ'],
        ['name_en' => 'Capacity', 'name_ur' => 'گنجائش'],
        ['name_en' => 'Pressure Rating', 'name_ur' => 'پریشر ریٹنگ'],
        ['name_en' => 'Flow Rate', 'name_ur' => 'فلو ریٹ'],
        ['name_en' => 'Blade Type', 'name_ur' => 'بلیڈ کی قسم'],
        ['name_en' => 'Tooth Count', 'name_ur' => 'دانتوں کی تعداد'],
        ['name_en' => 'Finish Type', 'name_ur' => 'فنشنگ'],
        ['name_en' => 'Hardness', 'name_ur' => 'سختی'],
        ['name_en' => 'Shape', 'name_ur' => 'شکل'],
        ['name_en' => 'Grip Type', 'name_ur' => 'گھیرنے کی قسم'],
        ['name_en' => 'Compatibility', 'name_ur' => 'مطابقت'],
        ['name_en' => 'Tolerance', 'name_ur' => 'رواداری'],
        ['name_en' => 'Durability Class', 'name_ur' => 'پائیداری'],
    ];

    public function run(): void
    {
        foreach ($this->attributes as $index => $attribute) {
            $slug = Str::slug($attribute['name_en']);
            $code = 'ATR-' . str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT);

            CatalogAttribute::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'code' => $code,
                    'status' => 'active',
                    'name_en' => $attribute['name_en'],
                    'name_ur' => $attribute['name_ur'],
                    'ai_status' => 'manual',
                ],
            );
        }
    }
}
