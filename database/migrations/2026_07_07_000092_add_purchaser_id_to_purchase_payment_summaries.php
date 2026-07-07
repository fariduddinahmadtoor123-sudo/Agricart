<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_payment_summaries', function (Blueprint $table): void {
            $table->foreignId('purchaser_id')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_payment_summaries', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('purchaser_id');
        });
    }
};
