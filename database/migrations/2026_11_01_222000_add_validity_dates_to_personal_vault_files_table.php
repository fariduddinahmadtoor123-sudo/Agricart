<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_vault_files', function (Blueprint $table) {
            $table->date('valid_from')->nullable()->after('doc_type');
            $table->date('valid_until')->nullable()->after('valid_from');
            $table->index(['user_id', 'valid_until']);
        });
    }

    public function down(): void
    {
        Schema::table('personal_vault_files', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'valid_until']);
            $table->dropColumn(['valid_from', 'valid_until']);
        });
    }
};
