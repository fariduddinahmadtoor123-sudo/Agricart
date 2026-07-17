<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('controls', function (Blueprint $table): void {
            $table->string('value_en', 1000)->change();
            $table->string('value_ur', 1000)->change();
        });
    }

    public function down(): void
    {
        Schema::table('controls', function (Blueprint $table): void {
            $table->string('value_en')->change();
            $table->string('value_ur')->change();
        });
    }
};
