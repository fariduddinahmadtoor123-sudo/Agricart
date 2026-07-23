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

            if ($exists) {
                DB::table('brand_agricart_review_messages')
                    ->where('criterion_key', $key)
                    ->update([
                        'high_en' => $row['high_en'],
                        'high_ur' => $row['high_ur'],
                        'low_en' => $row['low_en'],
                        'low_ur' => $row['low_ur'],
                        'updated_at' => $now,
                    ]);

                continue;
            }

            DB::table('brand_agricart_review_messages')->insert([
                'criterion_key' => $key,
                'high_en' => $row['high_en'],
                'high_ur' => $row['high_ur'],
                'low_en' => $row['low_en'],
                'low_ur' => $row['low_ur'],
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
