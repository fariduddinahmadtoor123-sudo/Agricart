<?php

use App\Support\Catalog\BrandAgricartReviewMessageDefaults;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('brand_agricart_review_messages')) {
            return;
        }

        $now = now();

        foreach (BrandAgricartReviewMessageDefaults::all() as $key => $row) {
            $exists = DB::table('brand_agricart_review_messages')
                ->where('criterion_key', $key)
                ->exists();

            $highEn = $row['positive_en'] ?? $row['high_en'] ?? '';
            $highUr = $row['positive_ur'] ?? $row['high_ur'] ?? '';
            $lowEn = $row['needs_improvement_en'] ?? $row['low_en'] ?? '';
            $lowUr = $row['needs_improvement_ur'] ?? $row['low_ur'] ?? '';

            if ($exists) {
                DB::table('brand_agricart_review_messages')
                    ->where('criterion_key', $key)
                    ->update([
                        'high_en' => $highEn,
                        'high_ur' => $highUr,
                        'low_en' => $lowEn,
                        'low_ur' => $lowUr,
                        'updated_at' => $now,
                    ]);

                continue;
            }

            DB::table('brand_agricart_review_messages')->insert([
                'criterion_key' => $key,
                'high_en' => $highEn,
                'high_ur' => $highUr,
                'low_en' => $lowEn,
                'low_ur' => $lowUr,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Intentionally keep editable message rows.
    }
};
