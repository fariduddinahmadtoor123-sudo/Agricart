<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_reviews', function (Blueprint $table): void {
            $table->text('admin_reply')->nullable()->after('body');
            $table->foreignId('admin_replied_by')->nullable()->after('admin_reply')->constrained('users')->nullOnDelete();
            $table->timestamp('admin_replied_at')->nullable()->after('admin_replied_by');
        });

        Schema::create('product_review_photos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_review_id')->constrained('product_reviews')->cascadeOnDelete();
            $table->string('path', 500);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_review_id', 'sort_order'], 'product_review_photos_review_sort_idx');
        });

        Schema::create('product_review_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_review_id')->constrained('product_reviews')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('reason', 40);
            $table->string('note', 500)->nullable();
            $table->string('status', 20)->default('open');
            $table->timestamps();

            $table->unique(['product_review_id', 'customer_id'], 'product_review_reports_review_customer_uq');
            $table->index(['status', 'created_at'], 'product_review_reports_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_review_reports');
        Schema::dropIfExists('product_review_photos');

        Schema::table('product_reviews', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('admin_replied_by');
            $table->dropColumn(['admin_reply', 'admin_replied_at']);
        });
    }
};
