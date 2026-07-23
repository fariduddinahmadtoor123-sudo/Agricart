<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table): void {
            $table->json('agricart_rating_criteria')->nullable()->after('agricart_rating');
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table): void {
            $table->dropColumn('agricart_rating_criteria');
        });
    }
};
