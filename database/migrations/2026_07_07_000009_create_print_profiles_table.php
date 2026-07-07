<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name_en');
            $table->string('name_ur');
            $table->string('category', 32);
            $table->string('label_kind', 32)->nullable();
            $table->decimal('width_mm', 8, 2);
            $table->decimal('height_mm', 8, 2)->nullable();
            $table->decimal('margin_mm', 8, 2)->default(2);
            $table->string('orientation', 16)->default('portrait');
            $table->string('printer_hint')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_default')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();

        DB::table('print_profiles')->insert([
            [
                'key' => 'a4',
                'name_en' => 'A4 document',
                'name_ur' => 'A4 دستاویز',
                'category' => 'document',
                'label_kind' => null,
                'width_mm' => 210,
                'height_mm' => 297,
                'margin_mm' => 10,
                'orientation' => 'portrait',
                'printer_hint' => 'A4 / Letter office printer',
                'is_system' => true,
                'is_default' => true,
                'sort_order' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'legal',
                'name_en' => 'Legal document',
                'name_ur' => 'Legal دستاویز',
                'category' => 'document',
                'label_kind' => null,
                'width_mm' => 216,
                'height_mm' => 356,
                'margin_mm' => 10,
                'orientation' => 'portrait',
                'printer_hint' => 'Legal size office printer',
                'is_system' => true,
                'is_default' => false,
                'sort_order' => 20,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'thermal_75',
                'name_en' => '75mm receipt roll',
                'name_ur' => '75mm رسید رول',
                'category' => 'receipt',
                'label_kind' => null,
                'width_mm' => 75,
                'height_mm' => null,
                'margin_mm' => 2,
                'orientation' => 'portrait',
                'printer_hint' => '75mm thermal roll printer',
                'is_system' => true,
                'is_default' => true,
                'sort_order' => 30,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'label_38x50',
                'name_en' => '38 × 50 mm label',
                'name_ur' => '38 × 50 mm لیبل',
                'category' => 'label',
                'label_kind' => 'price',
                'width_mm' => 38,
                'height_mm' => 50,
                'margin_mm' => 1,
                'orientation' => 'portrait',
                'printer_hint' => 'Brother P-touch / label printer',
                'is_system' => true,
                'is_default' => true,
                'sort_order' => 40,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'label_50x30',
                'name_en' => '50 × 30 mm label',
                'name_ur' => '50 × 30 mm لیبل',
                'category' => 'label',
                'label_kind' => 'price',
                'width_mm' => 50,
                'height_mm' => 30,
                'margin_mm' => 1,
                'orientation' => 'landscape',
                'printer_hint' => 'Brother P-touch / label printer',
                'is_system' => true,
                'is_default' => false,
                'sort_order' => 50,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'label_75x50',
                'name_en' => '75 × 50 mm product label',
                'name_ur' => '75 × 50 mm پروڈکٹ لیبل',
                'category' => 'label',
                'label_kind' => 'product',
                'width_mm' => 75,
                'height_mm' => 50,
                'margin_mm' => 1.5,
                'orientation' => 'landscape',
                'printer_hint' => 'Brother P-touch / product sticker',
                'is_system' => true,
                'is_default' => true,
                'sort_order' => 60,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('print_profiles');
    }
};
