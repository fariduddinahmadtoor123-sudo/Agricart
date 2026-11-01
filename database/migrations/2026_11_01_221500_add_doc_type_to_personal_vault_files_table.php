<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_vault_files', function (Blueprint $table) {
            $table->string('doc_type', 40)->default('other')->after('label');
            $table->index(['user_id', 'doc_type']);
        });
    }

    public function down(): void
    {
        Schema::table('personal_vault_files', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'doc_type']);
            $table->dropColumn('doc_type');
        });
    }
};
