<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('code')->nullable();
            $table->string('status')->default('active');

            $table->string('name_en');
            $table->string('name_ur');

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

            $table->json('visual_mapping')->nullable();
            $table->json('faqs')->nullable();
            $table->string('ai_status')->default('none');

            $table->timestamps();

            $table->index(['parent_id', 'status']);
            $table->index('ai_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
