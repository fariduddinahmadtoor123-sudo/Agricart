<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table): void {
            $table->text('agricart_rating_reason_en')->nullable()->after('agricart_rating');
            $table->text('agricart_rating_reason_ur')->nullable()->after('agricart_rating_reason_en');
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table): void {
            $table->dropColumn([
                'agricart_rating_reason_en',
                'agricart_rating_reason_ur',
            ]);
        });
    }
};
