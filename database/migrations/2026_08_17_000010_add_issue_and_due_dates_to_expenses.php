<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->date('issue_date')->nullable()->after('information');
            $table->date('due_date')->nullable()->after('issue_date');
            $table->index('issue_date', 'expenses_issue_date_index');
            $table->index('due_date', 'expenses_due_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            try {
                $table->dropIndex('expenses_issue_date_index');
            } catch (\Throwable) {
            }

            try {
                $table->dropIndex('expenses_due_date_index');
            } catch (\Throwable) {
            }

            $table->dropColumn(['issue_date', 'due_date']);
        });
    }
};
