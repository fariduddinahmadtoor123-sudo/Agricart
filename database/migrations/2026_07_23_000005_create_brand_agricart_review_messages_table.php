<?php

use App\Support\Catalog\BrandAgricartReviewCriteria;
use App\Support\Catalog\BrandAgricartReviewMessageDefaults;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand_agricart_review_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('criterion_key', 64)->unique();
            $table->text('high_en');
            $table->text('high_ur');
            $table->text('low_en');
            $table->text('low_ur');
            $table->timestamps();
        });

        $now = now();

        foreach (BrandAgricartReviewCriteria::keys() as $key) {
            $defaults = BrandAgricartReviewMessageDefaults::for($key);

            if ($defaults === null) {
                continue;
            }

            DB::table('brand_agricart_review_messages')->insert([
                'criterion_key' => $key,
                'high_en' => $defaults['positive_en'] ?? $defaults['high_en'] ?? '',
                'high_ur' => $defaults['positive_ur'] ?? $defaults['high_ur'] ?? '',
                'low_en' => $defaults['needs_improvement_en'] ?? $defaults['low_en'] ?? '',
                'low_ur' => $defaults['needs_improvement_ur'] ?? $defaults['low_ur'] ?? '',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_agricart_review_messages');
    }
};
