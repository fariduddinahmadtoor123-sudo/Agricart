<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('categories', 'visual_map_code')) {
            Schema::table('categories', function (Blueprint $table): void {
                $table->string('visual_map_code', 64)->nullable()->after('visual_mapping');
            });
        }

        $seen = [];

        DB::table('categories')
            ->orderBy('id')
            ->chunkById(500, function ($categories) use (&$seen): void {
                foreach ($categories as $category) {
                    $mapping = json_decode((string) ($category->visual_mapping ?? ''), true);
                    $code = is_array($mapping)
                        ? str_replace('.', '', trim((string) ($mapping['map_code'] ?? '')))
                        : '';

                    if ($code === '') {
                        continue;
                    }

                    if (isset($seen[$code])) {
                        if (DB::table('products')->where('category_id', $category->id)->exists()) {
                            throw new RuntimeException(
                                "Duplicate category visual map code [{$code}] exists on category ID "
                                ."{$category->id}, which has products. Move or relabel those products before migrating.",
                            );
                        }

                        $code = $this->nextAvailableCode($code, $seen);
                        $mapping['map_code'] = $code;

                        DB::table('categories')
                            ->where('id', $category->id)
                            ->update([
                                'visual_mapping' => json_encode($mapping, JSON_UNESCAPED_UNICODE),
                            ]);
                    }

                    $seen[$code] = $category->id;
                    DB::table('categories')
                        ->where('id', $category->id)
                        ->update(['visual_map_code' => $code]);
                }
            });

        Schema::table('categories', function (Blueprint $table): void {
            $table->unique('visual_map_code');
        });
    }

    /**
     * @param  array<string, int>  $seen
     */
    private function nextAvailableCode(string $code, array $seen): string
    {
        if (! preg_match('/^(.*?)([A-Z])(\d+)$/', $code, $matches)) {
            throw new RuntimeException("Cannot safely repair malformed visual map code [{$code}].");
        }

        $base = $matches[1].$matches[2];
        $number = (int) $matches[3] + 1;

        while (isset($seen[$base.$number])) {
            $number++;
        }

        return $base.$number;
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->dropUnique(['visual_map_code']);
            $table->dropColumn('visual_map_code');
        });
    }
};
