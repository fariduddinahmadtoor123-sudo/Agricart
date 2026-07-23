<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->unsignedTinyInteger('agricart_rating')->nullable()->after('ai_version');
            $table->json('agricart_rating_criteria')->nullable()->after('agricart_rating');
            $table->text('agricart_rating_reason_en')->nullable()->after('agricart_rating_criteria');
            $table->text('agricart_rating_reason_ur')->nullable()->after('agricart_rating_reason_en');
            $table->text('agricart_rating_revision_en')->nullable()->after('agricart_rating_reason_ur');
            $table->text('agricart_rating_revision_ur')->nullable()->after('agricart_rating_revision_en');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn([
                'agricart_rating',
                'agricart_rating_criteria',
                'agricart_rating_reason_en',
                'agricart_rating_reason_ur',
                'agricart_rating_revision_en',
                'agricart_rating_revision_ur',
            ]);
        });
    }
};
