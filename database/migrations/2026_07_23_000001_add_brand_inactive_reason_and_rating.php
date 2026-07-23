<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table): void {
            $table->text('inactive_reason_en')->nullable()->after('status');
            $table->text('inactive_reason_ur')->nullable()->after('inactive_reason_en');
            $table->unsignedTinyInteger('agricart_rating')->nullable()->after('inactive_reason_ur');
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table): void {
            $table->dropColumn([
                'inactive_reason_en',
                'inactive_reason_ur',
                'agricart_rating',
            ]);
        });
    }
};
