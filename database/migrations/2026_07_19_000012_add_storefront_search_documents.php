<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->text('search_document')->nullable();
        });

        DB::table('products')->orderBy('id')->chunkById(200, function ($products): void {
            foreach ($products as $product) {
                DB::table('products')->where('id', $product->id)->update([
                    'search_document' => $this->document((array) $product),
                ]);
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE products ADD FULLTEXT INDEX products_storefront_search_fulltext (search_document)');
        } else {
            Schema::table('products', function (Blueprint $table): void {
                $table->index('search_document', 'products_storefront_search_index');
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE products DROP INDEX products_storefront_search_fulltext');
        }

        Schema::table('products', function (Blueprint $table): void {
            if (DB::getDriverName() !== 'mysql') {
                $table->dropIndex('products_storefront_search_index');
            }
            $table->dropColumn('search_document');
        });
    }

    /**
     * @param  array<string, mixed>  $product
     */
    private function document(array $product): string
    {
        $fields = [
            'name_en', 'name_ur', 'code', 'sku', 'label_code', 'slug',
            'short_description_en', 'short_description_ur',
            'full_description_en', 'full_description_ur',
            'seo_title_en', 'seo_title_ur', 'seo_description_en', 'seo_description_ur',
            'seo_keywords_en', 'seo_keywords_ur', 'search_terms', 'hs_code',
        ];

        return trim(implode(' ', array_filter(array_map(
            static fn (string $field): string => is_scalar($product[$field] ?? null)
                ? strip_tags((string) $product[$field])
                : '',
            $fields,
        ))));
    }
};
