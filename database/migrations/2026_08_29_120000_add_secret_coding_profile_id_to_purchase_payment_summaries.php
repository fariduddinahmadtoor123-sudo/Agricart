<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_payment_summaries', function (Blueprint $table): void {
            $table->foreignId('secret_coding_profile_id')
                ->nullable()
                ->after('purchaser_id')
                ->constrained('secret_coding_profiles')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_payment_summaries', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('secret_coding_profile_id');
        });
    }
};
