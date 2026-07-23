<?php

use App\Support\Catalog\BrandAgricartReviewNotice;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table): void {
            $table->text('brand_review_notice_en')->nullable()->after('footer_show_register_link');
            $table->text('brand_review_notice_ur')->nullable()->after('brand_review_notice_en');
        });

        if (Schema::hasTable('store_settings')) {
            DB::table('store_settings')->update([
                'brand_review_notice_en' => BrandAgricartReviewNotice::defaultEnglish(),
                'brand_review_notice_ur' => BrandAgricartReviewNotice::defaultUrdu(),
            ]);
        }

        Schema::table('brands', function (Blueprint $table): void {
            $table->text('agricart_rating_revision_en')->nullable()->after('agricart_rating_reason_ur');
            $table->text('agricart_rating_revision_ur')->nullable()->after('agricart_rating_revision_en');
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table): void {
            $table->dropColumn([
                'agricart_rating_revision_en',
                'agricart_rating_revision_ur',
            ]);
        });

        Schema::table('store_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'brand_review_notice_en',
                'brand_review_notice_ur',
            ]);
        });
    }
};
