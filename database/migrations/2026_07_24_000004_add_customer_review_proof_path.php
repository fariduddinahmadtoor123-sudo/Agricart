<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_reviews', function (Blueprint $table): void {
            $table->string('submission_path', 32)->default('verified_purchase')->after('online_sale_order_item_id');
            $table->string('proof_note', 500)->nullable()->after('body');
        });

        Schema::table('product_review_photos', function (Blueprint $table): void {
            $table->string('kind', 20)->default('gallery')->after('path');
            $table->index(['product_review_id', 'kind'], 'product_review_photos_review_kind_idx');
        });

        // Existing rows with an order stay verified; others (if any) become proof path.
        DB::table('product_reviews')
            ->whereNull('online_sale_order_id')
            ->update(['submission_path' => 'proof']);
    }

    public function down(): void
    {
        Schema::table('product_review_photos', function (Blueprint $table): void {
            $table->dropIndex('product_review_photos_review_kind_idx');
            $table->dropColumn('kind');
        });

        Schema::table('product_reviews', function (Blueprint $table): void {
            $table->dropColumn(['submission_path', 'proof_note']);
        });
    }
};
