<?php

namespace Database\Seeders;

use App\Models\PackingUnit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PackingUnitSeeder extends Seeder
{
    /**
     * @var list<array{name_en: string, name_ur: string}>
     */
    private array $packingUnits = [
        ['name_en' => 'Carton', 'name_ur' => 'کارٹن'],
        ['name_en' => 'Box', 'name_ur' => 'ڈبہ'],
        ['name_en' => 'Roll', 'name_ur' => 'رول'],
        ['name_en' => 'Bundle', 'name_ur' => 'بنڈل'],
        ['name_en' => 'Bag', 'name_ur' => 'تھیلا'],
        ['name_en' => 'Sack', 'name_ur' => 'بوری'],
        ['name_en' => 'Pack', 'name_ur' => 'پیک'],
        ['name_en' => 'Packet', 'name_ur' => 'پیکٹ'],
        ['name_en' => 'Bottle', 'name_ur' => 'بوتل'],
        ['name_en' => 'Jar', 'name_ur' => 'جار'],
        ['name_en' => 'Can', 'name_ur' => 'ٹین'],
        ['name_en' => 'Drum', 'name_ur' => 'ڈرم'],
        ['name_en' => 'Pallet', 'name_ur' => 'پیلیٹ'],
        ['name_en' => 'Crate', 'name_ur' => 'کریٹ'],
        ['name_en' => 'Tray', 'name_ur' => 'ٹرے'],
        ['name_en' => 'Strip', 'name_ur' => 'سٹرپ'],
        ['name_en' => 'Blister', 'name_ur' => 'بلِسٹر'],
        ['name_en' => 'Tube', 'name_ur' => 'ٹیوب'],
        ['name_en' => 'Pouch', 'name_ur' => 'پاؤچ'],
        ['name_en' => 'Wrapper', 'name_ur' => 'ریپر'],
    ];

    public function run(): void
    {
        foreach ($this->packingUnits as $index => $packingUnit) {
            $slug = Str::slug($packingUnit['name_en']);
            $code = 'PKG-' . str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT);

            PackingUnit::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'code' => $code,
                    'status' => 'active',
                    'name_en' => $packingUnit['name_en'],
                    'name_ur' => $packingUnit['name_ur'],
                    'ai_status' => 'manual',
                ],
            );
        }
    }
}
