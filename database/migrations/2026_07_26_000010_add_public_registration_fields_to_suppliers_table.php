<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table): void {
            $table->text('application_note')->nullable()->after('ai_status');
            $table->text('custom_categories')->nullable()->after('application_note');
            $table->timestamp('terms_accepted_at')->nullable()->after('custom_categories');
            $table->string('source', 16)->default('admin')->after('terms_accepted_at');
            $table->text('rejection_reason')->nullable()->after('source');
            $table->timestamp('rejected_at')->nullable()->after('rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table): void {
            $table->dropColumn([
                'application_note',
                'custom_categories',
                'terms_accepted_at',
                'source',
                'rejection_reason',
                'rejected_at',
            ]);
        });
    }
};
