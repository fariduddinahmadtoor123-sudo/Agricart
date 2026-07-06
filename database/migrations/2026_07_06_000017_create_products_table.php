<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('slug')->unique();
            $table->string('status')->default('active');

            $table->string('name_en');
            $table->string('name_ur');
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->string('image_path')->nullable();
            $table->string('sku')->nullable();

            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('unit_qty')->nullable();
            $table->foreignId('pack_unit_id')->nullable()->constrained('packing_units')->nullOnDelete();
            $table->string('pack_qty')->nullable();
            $table->string('required_qty')->nullable();
            $table->string('alert_qty')->nullable();

            $table->string('retail_price')->nullable();
            $table->string('wholesale_price')->nullable();
            $table->string('super_wholesale_price')->nullable();
            $table->string('distributor_price')->nullable();

            $table->text('short_description_en')->nullable();
            $table->text('short_description_ur')->nullable();
            $table->longText('full_description_en')->nullable();
            $table->longText('full_description_ur')->nullable();
            $table->text('usage_guidelines_en')->nullable();
            $table->text('usage_guidelines_ur')->nullable();
            $table->text('benefits_en')->nullable();
            $table->text('benefits_ur')->nullable();
            $table->text('warnings_en')->nullable();
            $table->text('warnings_ur')->nullable();
            $table->text('buying_guide_en')->nullable();
            $table->text('buying_guide_ur')->nullable();

            $table->string('seo_title_en')->nullable();
            $table->string('seo_title_ur')->nullable();
            $table->text('seo_description_en')->nullable();
            $table->text('seo_description_ur')->nullable();
            $table->string('seo_keywords_en')->nullable();
            $table->string('seo_keywords_ur')->nullable();
            $table->string('seo_focus_keyword_en')->nullable();
            $table->string('seo_focus_keyword_ur')->nullable();

            $table->json('search_terms')->nullable();
            $table->string('hs_code')->nullable();
            $table->text('customs_notes_en')->nullable();
            $table->text('customs_notes_ur')->nullable();
            $table->text('import_notes_en')->nullable();
            $table->text('import_notes_ur')->nullable();
            $table->text('export_notes_en')->nullable();
            $table->text('export_notes_ur')->nullable();

            $table->string('label_code')->nullable()->unique();
            $table->string('category_visual_map_code')->nullable();
            $table->unsignedInteger('label_sequence')->nullable();
            $table->json('label_qr')->nullable();
            $table->json('label_barcode')->nullable();

            $table->string('ai_status')->default('none');
            $table->timestamp('ai_generated_at')->nullable();
            $table->string('ai_version')->nullable();

            $table->timestamps();

            $table->index(['category_id', 'status']);
            $table->index('ai_status');
            $table->index('category_visual_map_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
