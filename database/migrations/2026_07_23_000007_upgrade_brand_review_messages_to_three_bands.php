<?php

use App\Support\Catalog\BrandAgricartReviewMessageDefaults;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('brand_agricart_review_messages')) {
            return;
        }

        Schema::table('brand_agricart_review_messages', function (Blueprint $table): void {
            if (! Schema::hasColumn('brand_agricart_review_messages', 'positive_en')) {
                $table->text('positive_en')->nullable()->after('criterion_key');
            }
            if (! Schema::hasColumn('brand_agricart_review_messages', 'positive_ur')) {
                $table->text('positive_ur')->nullable()->after('positive_en');
            }
            if (! Schema::hasColumn('brand_agricart_review_messages', 'neutral_en')) {
                $table->text('neutral_en')->nullable()->after('positive_ur');
            }
            if (! Schema::hasColumn('brand_agricart_review_messages', 'neutral_ur')) {
                $table->text('neutral_ur')->nullable()->after('neutral_en');
            }
            if (! Schema::hasColumn('brand_agricart_review_messages', 'needs_improvement_en')) {
                $table->text('needs_improvement_en')->nullable()->after('neutral_ur');
            }
            if (! Schema::hasColumn('brand_agricart_review_messages', 'needs_improvement_ur')) {
                $table->text('needs_improvement_ur')->nullable()->after('needs_improvement_en');
            }
        });

        $now = now();

        foreach (BrandAgricartReviewMessageDefaults::all() as $key => $defaults) {
            $row = DB::table('brand_agricart_review_messages')->where('criterion_key', $key)->first();

            $payload = [
                'positive_en' => $defaults['positive_en'],
                'positive_ur' => $defaults['positive_ur'],
                'neutral_en' => $defaults['neutral_en'],
                'neutral_ur' => $defaults['neutral_ur'],
                'needs_improvement_en' => $defaults['needs_improvement_en'],
                'needs_improvement_ur' => $defaults['needs_improvement_ur'],
                'updated_at' => $now,
            ];

            if ($row === null) {
                $insert = [
                    'criterion_key' => $key,
                    ...$payload,
                    'created_at' => $now,
                ];

                if (Schema::hasColumn('brand_agricart_review_messages', 'high_en')) {
                    $insert['high_en'] = $defaults['positive_en'];
                    $insert['high_ur'] = $defaults['positive_ur'];
                    $insert['low_en'] = $defaults['needs_improvement_en'];
                    $insert['low_ur'] = $defaults['needs_improvement_ur'];
                }

                DB::table('brand_agricart_review_messages')->insert($insert);

                continue;
            }

            // Prefer existing high/low text when migrating so admin edits are kept.
            if (Schema::hasColumn('brand_agricart_review_messages', 'high_en') && filled($row->high_en ?? null)) {
                $payload['positive_en'] = (string) $row->high_en;
            }
            if (Schema::hasColumn('brand_agricart_review_messages', 'high_ur') && filled($row->high_ur ?? null)) {
                $payload['positive_ur'] = (string) $row->high_ur;
            }
            if (Schema::hasColumn('brand_agricart_review_messages', 'low_en') && filled($row->low_en ?? null)) {
                $payload['needs_improvement_en'] = (string) $row->low_en;
            }
            if (Schema::hasColumn('brand_agricart_review_messages', 'low_ur') && filled($row->low_ur ?? null)) {
                $payload['needs_improvement_ur'] = (string) $row->low_ur;
            }

            DB::table('brand_agricart_review_messages')
                ->where('criterion_key', $key)
                ->update($payload);
        }

        Schema::table('brand_agricart_review_messages', function (Blueprint $table): void {
            foreach (['high_en', 'high_ur', 'low_en', 'low_ur'] as $legacy) {
                if (Schema::hasColumn('brand_agricart_review_messages', $legacy)) {
                    $table->dropColumn($legacy);
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('brand_agricart_review_messages')) {
            return;
        }

        Schema::table('brand_agricart_review_messages', function (Blueprint $table): void {
            if (! Schema::hasColumn('brand_agricart_review_messages', 'high_en')) {
                $table->text('high_en')->nullable();
            }
            if (! Schema::hasColumn('brand_agricart_review_messages', 'high_ur')) {
                $table->text('high_ur')->nullable();
            }
            if (! Schema::hasColumn('brand_agricart_review_messages', 'low_en')) {
                $table->text('low_en')->nullable();
            }
            if (! Schema::hasColumn('brand_agricart_review_messages', 'low_ur')) {
                $table->text('low_ur')->nullable();
            }
        });

        $rows = DB::table('brand_agricart_review_messages')->get();

        foreach ($rows as $row) {
            DB::table('brand_agricart_review_messages')->where('id', $row->id)->update([
                'high_en' => $row->positive_en ?? '',
                'high_ur' => $row->positive_ur ?? '',
                'low_en' => $row->needs_improvement_en ?? '',
                'low_ur' => $row->needs_improvement_ur ?? '',
            ]);
        }

        Schema::table('brand_agricart_review_messages', function (Blueprint $table): void {
            foreach ([
                'positive_en',
                'positive_ur',
                'neutral_en',
                'neutral_ur',
                'needs_improvement_en',
                'needs_improvement_ur',
            ] as $col) {
                if (Schema::hasColumn('brand_agricart_review_messages', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
