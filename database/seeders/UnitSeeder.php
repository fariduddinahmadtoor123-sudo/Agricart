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
        ['name_en' => 'Piece', 'abbreviation_en' => 'pc', 'name_ur' => 'عدد', 'abbreviation_ur' => 'عدد'],
        ['name_en' => 'Unit', 'abbreviation_en' => 'unit', 'name_ur' => 'یونٹ', 'abbreviation_ur' => 'یونٹ'],
        ['name_en' => 'Pair', 'abbreviation_en' => 'pair', 'name_ur' => 'جوڑا', 'abbreviation_ur' => 'جوڑا'],
        ['name_en' => 'Set', 'abbreviation_en' => 'set', 'name_ur' => 'سیٹ', 'abbreviation_ur' => 'سیٹ'],
        ['name_en' => 'Kit', 'abbreviation_en' => 'kit', 'name_ur' => 'کٹ', 'abbreviation_ur' => 'کٹ'],
        ['name_en' => 'Dozen', 'abbreviation_en' => 'dz', 'name_ur' => 'درجن', 'abbreviation_ur' => 'درجن'],
        ['name_en' => 'Box', 'abbreviation_en' => 'box', 'name_ur' => 'ڈبہ', 'abbreviation_ur' => 'ڈبہ'],
        ['name_en' => 'Carton', 'abbreviation_en' => 'ctn', 'name_ur' => 'کارٹن', 'abbreviation_ur' => 'کارٹن'],
        ['name_en' => 'Packet', 'abbreviation_en' => 'pkt', 'name_ur' => 'پیکٹ', 'abbreviation_ur' => 'پیکٹ'],
        ['name_en' => 'Pack', 'abbreviation_en' => 'pack', 'name_ur' => 'پیک', 'abbreviation_ur' => 'پیک'],
        ['name_en' => 'Bag', 'abbreviation_en' => 'bag', 'name_ur' => 'بوری', 'abbreviation_ur' => 'بوری'],
        ['name_en' => 'Sack', 'abbreviation_en' => 'sack', 'name_ur' => 'تھیلا', 'abbreviation_ur' => 'تھیلا'],
        ['name_en' => 'Bundle', 'abbreviation_en' => 'bdl', 'name_ur' => 'بنڈل', 'abbreviation_ur' => 'بنڈل'],
        ['name_en' => 'Roll', 'abbreviation_en' => 'roll', 'name_ur' => 'رول', 'abbreviation_ur' => 'رول'],
        ['name_en' => 'Coil', 'abbreviation_en' => 'coil', 'name_ur' => 'کوائل', 'abbreviation_ur' => 'کوائل'],
        ['name_en' => 'Reel', 'abbreviation_en' => 'reel', 'name_ur' => 'ریل', 'abbreviation_ur' => 'ریل'],
        ['name_en' => 'Sheet', 'abbreviation_en' => 'sht', 'name_ur' => 'شیٹ', 'abbreviation_ur' => 'شیٹ'],
        ['name_en' => 'Strip', 'abbreviation_en' => 'strip', 'name_ur' => 'پٹی', 'abbreviation_ur' => 'پٹی'],
        ['name_en' => 'Rod', 'abbreviation_en' => 'rod', 'name_ur' => 'راڈ', 'abbreviation_ur' => 'راڈ'],
        ['name_en' => 'Bar', 'abbreviation_en' => 'bar', 'name_ur' => 'بار', 'abbreviation_ur' => 'بار'],
        ['name_en' => 'Pipe', 'abbreviation_en' => 'pipe', 'name_ur' => 'پائپ', 'abbreviation_ur' => 'پائپ'],
        ['name_en' => 'Tube', 'abbreviation_en' => 'tube', 'name_ur' => 'ٹیوب', 'abbreviation_ur' => 'ٹیوب'],
        ['name_en' => 'Wire', 'abbreviation_en' => 'wire', 'name_ur' => 'تار', 'abbreviation_ur' => 'تار'],
        ['name_en' => 'Cable', 'abbreviation_en' => 'cable', 'name_ur' => 'کیبل', 'abbreviation_ur' => 'کیبل'],
        ['name_en' => 'Bottle', 'abbreviation_en' => 'btl', 'name_ur' => 'بوتل', 'abbreviation_ur' => 'بوتل'],
        ['name_en' => 'Can', 'abbreviation_en' => 'can', 'name_ur' => 'کین', 'abbreviation_ur' => 'کین'],
        ['name_en' => 'Jar', 'abbreviation_en' => 'jar', 'name_ur' => 'جار', 'abbreviation_ur' => 'جار'],
        ['name_en' => 'Tin', 'abbreviation_en' => 'tin', 'name_ur' => 'ٹِن', 'abbreviation_ur' => 'ٹِن'],
        ['name_en' => 'Drum', 'abbreviation_en' => 'drum', 'name_ur' => 'ڈرم', 'abbreviation_ur' => 'ڈرم'],
        ['name_en' => 'Bucket', 'abbreviation_en' => 'bkt', 'name_ur' => 'بالٹی', 'abbreviation_ur' => 'بالٹی'],
        ['name_en' => 'Container', 'abbreviation_en' => 'cont', 'name_ur' => 'کنٹینر', 'abbreviation_ur' => 'کنٹینر'],
        ['name_en' => 'Bale', 'abbreviation_en' => 'bale', 'name_ur' => 'گٹھا', 'abbreviation_ur' => 'گٹھا'],
        ['name_en' => 'Tray', 'abbreviation_en' => 'tray', 'name_ur' => 'ٹرے', 'abbreviation_ur' => 'ٹرے'],
        ['name_en' => 'Crate', 'abbreviation_en' => 'crate', 'name_ur' => 'کریٹ', 'abbreviation_ur' => 'کریٹ'],
        ['name_en' => 'Kilogram', 'abbreviation_en' => 'kg', 'name_ur' => 'کلوگرام', 'abbreviation_ur' => 'کلو'],
        ['name_en' => 'Gram', 'abbreviation_en' => 'g', 'name_ur' => 'گرام', 'abbreviation_ur' => 'گرام'],
        ['name_en' => 'Milligram', 'abbreviation_en' => 'mg', 'name_ur' => 'ملی گرام', 'abbreviation_ur' => 'ملی'],
        ['name_en' => 'Metric Ton', 'abbreviation_en' => 't', 'name_ur' => 'میٹرک ٹن', 'abbreviation_ur' => 'ٹن'],
        ['name_en' => 'Pound', 'abbreviation_en' => 'lb', 'name_ur' => 'پاؤنڈ', 'abbreviation_ur' => 'پاؤنڈ'],
        ['name_en' => 'Ounce', 'abbreviation_en' => 'oz', 'name_ur' => 'اونس', 'abbreviation_ur' => 'اونس'],
        ['name_en' => 'Liter', 'abbreviation_en' => 'L', 'name_ur' => 'لیٹر', 'abbreviation_ur' => 'لیٹر'],
        ['name_en' => 'Milliliter', 'abbreviation_en' => 'ml', 'name_ur' => 'ملی لیٹر', 'abbreviation_ur' => 'ملی'],
        ['name_en' => 'Gallon', 'abbreviation_en' => 'gal', 'name_ur' => 'گیلن', 'abbreviation_ur' => 'گیلن'],
        ['name_en' => 'Cubic Meter', 'abbreviation_en' => 'm³', 'name_ur' => 'مکعب میٹر', 'abbreviation_ur' => 'م³'],
        ['name_en' => 'Cubic Foot', 'abbreviation_en' => 'ft³', 'name_ur' => 'مکعب فٹ', 'abbreviation_ur' => 'فٹ³'],
        ['name_en' => 'Meter', 'abbreviation_en' => 'm', 'name_ur' => 'میٹر', 'abbreviation_ur' => 'میٹر'],
        ['name_en' => 'Centimeter', 'abbreviation_en' => 'cm', 'name_ur' => 'سینٹی میٹر', 'abbreviation_ur' => 'سم'],
        ['name_en' => 'Millimeter', 'abbreviation_en' => 'mm', 'name_ur' => 'ملی میٹر', 'abbreviation_ur' => 'ملی'],
        ['name_en' => 'Kilometer', 'abbreviation_en' => 'km', 'name_ur' => 'کلومیٹر', 'abbreviation_ur' => 'کلومیٹر'],
        ['name_en' => 'Inch', 'abbreviation_en' => 'in', 'name_ur' => 'انچ', 'abbreviation_ur' => 'انچ'],
        ['name_en' => 'Foot', 'abbreviation_en' => 'ft', 'name_ur' => 'فٹ', 'abbreviation_ur' => 'فٹ'],
        ['name_en' => 'Yard', 'abbreviation_en' => 'yd', 'name_ur' => 'گز', 'abbreviation_ur' => 'گز'],
        ['name_en' => 'Square Meter', 'abbreviation_en' => 'm²', 'name_ur' => 'مربع میٹر', 'abbreviation_ur' => 'م²'],
        ['name_en' => 'Square Foot', 'abbreviation_en' => 'ft²', 'name_ur' => 'مربع فٹ', 'abbreviation_ur' => 'فٹ²'],
        ['name_en' => 'Acre', 'abbreviation_en' => 'ac', 'name_ur' => 'ایکڑ', 'abbreviation_ur' => 'ایکڑ'],
        ['name_en' => 'Hectare', 'abbreviation_en' => 'ha', 'name_ur' => 'ہیکٹر', 'abbreviation_ur' => 'ہیکٹر'],
        ['name_en' => 'Hour', 'abbreviation_en' => 'hr', 'name_ur' => 'گھنٹہ', 'abbreviation_ur' => 'گھنٹہ'],
        ['name_en' => 'Day', 'abbreviation_en' => 'day', 'name_ur' => 'دن', 'abbreviation_ur' => 'دن'],
        ['name_en' => 'Month', 'abbreviation_en' => 'mon', 'name_ur' => 'مہینہ', 'abbreviation_ur' => 'ماہ'],
        ['name_en' => 'Year', 'abbreviation_en' => 'yr', 'name_ur' => 'سال', 'abbreviation_ur' => 'سال'],
        ['name_en' => 'Bundle Pack', 'abbreviation_en' => 'bp', 'name_ur' => 'بنڈل پیک', 'abbreviation_ur' => 'بنڈل'],
        ['name_en' => 'Bottle Pack', 'abbreviation_en' => 'bpk', 'name_ur' => 'بوتل پیک', 'abbreviation_ur' => 'بوتل'],
        ['name_en' => 'Case', 'abbreviation_en' => 'case', 'name_ur' => 'کیس', 'abbreviation_ur' => 'کیس'],
        ['name_en' => 'Sleeve', 'abbreviation_en' => 'slv', 'name_ur' => 'سلیو', 'abbreviation_ur' => 'سلیو'],
        ['name_en' => 'Pallet', 'abbreviation_en' => 'plt', 'name_ur' => 'پیلیٹ', 'abbreviation_ur' => 'پیلیٹ'],
        ['name_en' => 'Hopper Load', 'abbreviation_en' => 'hop', 'name_ur' => 'ہوپر لوڈ', 'abbreviation_ur' => 'ہوپر'],
        ['name_en' => 'Tank', 'abbreviation_en' => 'tank', 'name_ur' => 'ٹینک', 'abbreviation_ur' => 'ٹینک'],
        ['name_en' => 'Barrel', 'abbreviation_en' => 'bbl', 'name_ur' => 'بیرل', 'abbreviation_ur' => 'بیرل'],
        ['name_en' => 'Capsule', 'abbreviation_en' => 'cap', 'name_ur' => 'کیپسول', 'abbreviation_ur' => 'کیپسول'],
        ['name_en' => 'Tablet', 'abbreviation_en' => 'tab', 'name_ur' => 'گولی', 'abbreviation_ur' => 'گولی'],
    ];

    public function run(): void
    {
        foreach ($this->units as $index => $unit) {
            $slug = Str::slug($unit['name_en']) ?: 'unit-'.($index + 1);
            $code = 'UNT-'.str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT);

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
